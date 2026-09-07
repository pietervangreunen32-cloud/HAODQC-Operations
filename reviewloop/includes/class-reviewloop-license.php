<?php
/**
 * License gating and activation against ReviewLoop's own license server
 * (a separate WordPress plugin — "reviewloop-license-server" — running on
 * ops.growthcraft.org.za, backed by PayFast for recurring billing). This
 * class is the client side, built against a small JSON REST API that
 * server exposes:
 *
 *   POST {server}/activate    { license_key, site_url }  -> { status, plan, expires_at }
 *   POST {server}/deactivate  { license_key, site_url }  -> { status: ok }
 *   POST {server}/validate    { license_key, site_url }  -> { status, plan, expires_at }
 *
 * "plan" is 'starter' or 'pro' — everything gates off get_plan(), not a
 * single yes/no Pro flag, since there are now three tiers (free being the
 * absence of an active license). "expires_at" is mostly informational — an
 * active PayFast subscription keeps renewing automatically, so `status`
 * (not a fixed expiry date) is what actually gates paid features.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_License {

	/**
	 * Tiers in ascending order — used to answer "is this site at least on
	 * plan X" without hardcoding pairwise comparisons everywhere.
	 */
	const TIER_ORDER = array( 'free', 'starter', 'pro' );

	public static function get_plan() {
		$settings = get_option( 'reviewloop_settings', array() );

		if ( empty( $settings['license_status'] ) || 'active' !== $settings['license_status'] ) {
			return 'free';
		}

		$plan = isset( $settings['license_plan'] ) ? $settings['license_plan'] : '';
		return in_array( $plan, array( 'starter', 'pro' ), true ) ? $plan : 'starter';
	}

	public static function is_at_least( $tier ) {
		$current = array_search( self::get_plan(), self::TIER_ORDER, true );
		$needed  = array_search( $tier, self::TIER_ORDER, true );

		if ( false === $current || false === $needed ) {
			return false;
		}

		return $current >= $needed;
	}

	/**
	 * Kept for readability at call sites that just mean "any paid plan".
	 */
	public static function is_pro_active() {
		return self::is_at_least( 'starter' );
	}

	private static function server_url() {
		return defined( 'REVIEWLOOP_LICENSE_SERVER_URL' ) ? REVIEWLOOP_LICENSE_SERVER_URL : 'https://ops.growthcraft.org.za/wp-json/reviewloop-license/v1';
	}

	private static function call( $endpoint, $license_key ) {
		$response = wp_remote_post(
			trailingslashit( self::server_url() ) . $endpoint,
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'license_key' => $license_key,
						'site_url'    => home_url(),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) ? $body : new WP_Error( 'reviewloop_license_bad_response', __( 'The license server returned an unexpected response.', 'reviewloop' ) );
	}

	private static function error_for_status( $status ) {
		$messages = array(
			'invalid'            => __( 'That license key isn\'t valid.', 'reviewloop' ),
			'expired'            => __( 'This license has expired or the subscription payment failed. Please check your billing.', 'reviewloop' ),
			'cancelled'          => __( 'This subscription has been cancelled.', 'reviewloop' ),
			'site_limit_reached' => __( 'This license is already active on another site. Deactivate it there first.', 'reviewloop' ),
		);

		return isset( $messages[ $status ] ) ? $messages[ $status ] : __( 'That license key isn\'t valid or active.', 'reviewloop' );
	}

	public static function activate( $license_key ) {
		$body = self::call( 'activate', $license_key );

		if ( is_wp_error( $body ) ) {
			return new WP_Error( 'reviewloop_license_unreachable', __( 'Could not reach the license server. Please try again shortly.', 'reviewloop' ) );
		}

		if ( empty( $body['status'] ) || 'active' !== $body['status'] ) {
			ReviewLoop_Settings::update( array( 'license_key' => $license_key, 'license_status' => 'inactive' ) );
			return new WP_Error( 'reviewloop_license_invalid', self::error_for_status( isset( $body['status'] ) ? $body['status'] : 'invalid' ) );
		}

		ReviewLoop_Settings::update(
			array(
				'license_key'     => $license_key,
				'license_status'  => 'active',
				'license_plan'    => isset( $body['plan'] ) ? $body['plan'] : 'starter',
				'license_expires' => isset( $body['expires_at'] ) ? $body['expires_at'] : '',
			)
		);

		return true;
	}

	public static function deactivate() {
		$settings = ReviewLoop_Settings::get_all();

		if ( ! empty( $settings['license_key'] ) ) {
			self::call( 'deactivate', $settings['license_key'] );
		}

		ReviewLoop_Settings::update( array( 'license_status' => 'inactive', 'license_plan' => '' ) );
	}

	/**
	 * Re-checks the stored license against the server. Called from the
	 * daily cron tick so a lapsed subscription — or a plan change — is
	 * caught within a day.
	 */
	public static function revalidate() {
		$settings = ReviewLoop_Settings::get_all();

		if ( empty( $settings['license_key'] ) || 'active' !== $settings['license_status'] ) {
			return;
		}

		$body = self::call( 'validate', $settings['license_key'] );

		if ( is_wp_error( $body ) ) {
			return; // Don't lock a business out over a transient network issue.
		}

		if ( empty( $body['status'] ) || 'active' !== $body['status'] ) {
			ReviewLoop_Settings::update( array( 'license_status' => 'inactive' ) );
			return;
		}

		$update = array();
		if ( isset( $body['plan'] ) ) {
			$update['license_plan'] = $body['plan'];
		}
		if ( isset( $body['expires_at'] ) ) {
			$update['license_expires'] = $body['expires_at'];
		}
		if ( $update ) {
			ReviewLoop_Settings::update( $update );
		}
	}

	/**
	 * The 3-card plan comparison shown on both the Dashboard and the
	 * Settings screen — kept in one place so the two never drift apart.
	 * Returns HTML (already escaped internally); echo it directly.
	 */
	public static function render_plan_cards() {
		$current       = self::get_plan();
		$starter_price = defined( 'REVIEWLOOP_STARTER_PRICE_DISPLAY' ) ? REVIEWLOOP_STARTER_PRICE_DISPLAY : '$20/month';
		$pro_price     = defined( 'REVIEWLOOP_PRO_PRICE_DISPLAY' ) ? REVIEWLOOP_PRO_PRICE_DISPLAY : '$49/month';
		$pricing_url   = defined( 'REVIEWLOOP_PRICING_URL' ) ? REVIEWLOOP_PRICING_URL : '#';
		$free_limit    = defined( 'REVIEWLOOP_FREE_REPLY_LIMIT' ) ? REVIEWLOOP_FREE_REPLY_LIMIT : 10;

		$plans = array(
			'free'    => array(
				'badge'       => __( 'Free', 'reviewloop' ),
				'name'        => __( 'Free', 'reviewloop' ),
				'price'       => __( '$0/month', 'reviewloop' ),
				'features'    => array(
					__( 'Manual customer entry', 'reviewloop' ),
					__( 'Full message sequence (check-in, review ask, reminder)', 'reviewloop' ),
					__( 'AI reply drafting — Claude, OpenAI, or Gemini', 'reviewloop' ),
					sprintf( /* translators: %d: reply limit */ __( 'Up to %d review replies total', 'reviewloop' ), $free_limit ),
				),
				'description' => __( 'Best for trying ReviewLoop before you commit.', 'reviewloop' ),
			),
			'starter' => array(
				'badge'       => __( 'Popular', 'reviewloop' ),
				'name'        => __( 'Starter', 'reviewloop' ),
				/* translators: %s: price display, e.g. $20/month */
				'price'       => sprintf( __( 'From %s', 'reviewloop' ), $starter_price ),
				'features'    => array(
					__( 'Everything in Free', 'reviewloop' ),
					__( 'Unlimited AI-drafted (or self-written) replies', 'reviewloop' ),
					__( 'Bulk CSV import (QuickBooks, Sage, etc.)', 'reviewloop' ),
				),
				'description' => __( 'Best for businesses ready to automate reviews without limits.', 'reviewloop' ),
			),
			'pro'     => array(
				'badge'       => __( 'Pro', 'reviewloop' ),
				'name'        => __( 'Pro', 'reviewloop' ),
				/* translators: %s: price display, e.g. $49/month */
				'price'       => sprintf( __( 'From %s', 'reviewloop' ), $pro_price ),
				'features'    => array(
					__( 'Everything in Starter', 'reviewloop' ),
					__( 'Automatic WooCommerce order sync', 'reviewloop' ),
					__( 'Priority support', 'reviewloop' ),
				),
				'description' => __( 'Best for businesses selling through WooCommerce.', 'reviewloop' ),
			),
		);

		ob_start();
		?>
		<div class="rl-plans-grid">
			<?php foreach ( $plans as $key => $plan ) : ?>
				<?php $is_current = ( $key === $current ); ?>
				<div class="rl-plan-card<?php echo 'starter' === $key ? ' rl-plan-card--highlight' : ''; ?>">
					<span class="rl-plan-badge"><?php echo esc_html( $plan['badge'] ); ?></span>
					<h3><?php echo esc_html( $plan['name'] ); ?></h3>
					<div class="rl-plan-price"><?php echo esc_html( $plan['price'] ); ?></div>
					<ul class="rl-plan-features">
						<?php foreach ( $plan['features'] as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="rl-plan-description"><?php echo esc_html( $plan['description'] ); ?></p>
					<?php if ( $is_current ) : ?>
						<span class="rl-plan-cta rl-plan-cta-current"><?php esc_html_e( 'Current Plan', 'reviewloop' ); ?></span>
					<?php elseif ( 'free' !== $key ) : ?>
						<a class="rl-plan-cta" href="<?php echo esc_url( $pricing_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( sprintf( /* translators: %s: plan name */ __( 'Upgrade to %s', 'reviewloop' ), $plan['name'] ) ); ?></a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
