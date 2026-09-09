<?php
/**
 * License gating and activation against ReviewLoop's own license server
 * (a separate WordPress plugin — "reviewloop-license-server" — running on
 * ops.growthcraft.org.za). ReviewLoop is sold as a once-off purchase per
 * site, not a subscription: once a license activates, its plan (Starter or
 * Pro) stays unlocked permanently — nothing here re-locks a feature over a
 * missed payment. This class is the client side, built against a small
 * JSON REST API that server exposes:
 *
 *   POST {server}/activate    { license_key, site_url }  -> { status, plan }
 *   POST {server}/deactivate  { license_key, site_url }  -> { status: ok }
 *   POST {server}/validate    { license_key, site_url }  -> { status, plan, updates_expire_at }
 *
 * "plan" is 'starter' or 'pro' — everything gates off get_plan(), not a
 * single yes/no Pro flag, since there are three tiers (free being the
 * absence of an active license). "updates_expire_at" is the one thing that
 * genuinely can lapse — an optional annual renewal that only controls
 * whether ReviewLoop_Updater is offered a newer plugin version; it never
 * feeds into get_plan() or is_at_least().
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
			'expired'            => __( 'This license isn\'t active. Please check with support.', 'reviewloop' ),
			'cancelled'          => __( 'This license has been cancelled.', 'reviewloop' ),
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
				'license_key'    => $license_key,
				'license_status' => 'active',
				'license_plan'   => isset( $body['plan'] ) ? $body['plan'] : 'starter',
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
	 * daily cron tick so a revoked/refunded license — or a plan change —
	 * is caught within a day. This never expires a license on its own; it
	 * only reflects whatever the server says, and the server only ever
	 * revokes `status` by hand (a refund) or lets `updates_expire_at` lapse
	 * (which this also picks up, purely for the "Updates valid until"
	 * display — it doesn't affect get_plan()).
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
		if ( isset( $body['updates_expire_at'] ) ) {
			$update['license_updates_expire'] = $body['updates_expire_at'];
		}
		if ( $update ) {
			ReviewLoop_Settings::update( $update );
		}
	}

	/**
	 * "Updates valid until 8 September 2027", or an empty string before the
	 * first daily revalidate has run, or for a free-plan site. Purely
	 * informational — shown on the License panel next to a Renew link.
	 */
	public static function updates_expire_label() {
		$settings = ReviewLoop_Settings::get_all();
		if ( empty( $settings['license_updates_expire'] ) ) {
			return '';
		}
		return date_i18n( get_option( 'date_format' ), strtotime( $settings['license_updates_expire'] ) );
	}

	public static function updates_lapsed() {
		$settings = ReviewLoop_Settings::get_all();
		if ( empty( $settings['license_updates_expire'] ) ) {
			return false;
		}
		return strtotime( $settings['license_updates_expire'] ) < strtotime( gmdate( 'Y-m-d' ) );
	}

	/**
	 * Best-effort visitor country, used only to decide which currency to
	 * *display* — actual billing is always ZAR via PayFast regardless of
	 * what's shown here (PayFast doesn't support charging in USD). Tries
	 * Cloudflare's country header first (free, needs no setup on a
	 * Cloudflare-proxied site), then WooCommerce's bundled geolocation if
	 * that plugin happens to be active, then gives up.
	 */
	public static function detect_country_code() {
		static $country = null;

		if ( null !== $country ) {
			return $country;
		}

		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
			return $country;
		}

		if ( class_exists( 'WC_Geolocation' ) ) {
			$located = WC_Geolocation::geolocate_ip();
			if ( ! empty( $located['country'] ) ) {
				$country = $located['country'];
				return $country;
			}
		}

		$country = '';
		return $country;
	}

	public static function is_south_african_visitor() {
		return 'ZA' === self::detect_country_code();
	}

	/**
	 * "R4,500 once-off" for a South African visitor, "$240 once-off" (a
	 * converted label only, not a real charge amount) for everyone else, or
	 * for anyone when the visitor's country can't be determined at all —
	 * defaulting to USD there since ReviewLoop is sold internationally.
	 */
	public static function price_label( $plan ) {
		if ( self::is_south_african_visitor() ) {
			$zar = 'pro' === $plan
				? ( defined( 'REVIEWLOOP_PRO_PRICE_ZAR' ) ? REVIEWLOOP_PRO_PRICE_ZAR : 9500 )
				: ( defined( 'REVIEWLOOP_STARTER_PRICE_ZAR' ) ? REVIEWLOOP_STARTER_PRICE_ZAR : 4500 );
			/* translators: %s: price in South African Rand */
			return sprintf( __( 'R%s once-off', 'reviewloop' ), number_format_i18n( $zar ) );
		}

		$usd = 'pro' === $plan
			? ( defined( 'REVIEWLOOP_PRO_PRICE_USD' ) ? REVIEWLOOP_PRO_PRICE_USD : 500 )
			: ( defined( 'REVIEWLOOP_STARTER_PRICE_USD' ) ? REVIEWLOOP_STARTER_PRICE_USD : 240 );
		/* translators: %s: price in US Dollars */
		return sprintf( __( '$%s once-off', 'reviewloop' ), number_format_i18n( $usd ) );
	}

	/**
	 * The 3-card plan comparison shown on both the Dashboard and the
	 * Settings screen — kept in one place so the two never drift apart.
	 * Returns HTML (already escaped internally); echo it directly.
	 */
	public static function render_plan_cards() {
		$current       = self::get_plan();
		$starter_price = self::price_label( 'starter' );
		$pro_price     = self::price_label( 'pro' );
		$pricing_url   = defined( 'REVIEWLOOP_PRICING_URL' ) ? REVIEWLOOP_PRICING_URL : '#';
		$free_limit    = defined( 'REVIEWLOOP_FREE_REPLY_LIMIT' ) ? REVIEWLOOP_FREE_REPLY_LIMIT : 10;

		$plans = array(
			'free'    => array(
				'badge'       => __( 'Free', 'reviewloop' ),
				'name'        => __( 'Free', 'reviewloop' ),
				'price'       => __( 'Free', 'reviewloop' ),
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
				'price'       => $starter_price,
				'features'    => array(
					__( 'Everything in Free', 'reviewloop' ),
					__( 'Unlimited AI-drafted (or self-written) replies', 'reviewloop' ),
					__( 'Bulk CSV import (QuickBooks, Sage, etc.)', 'reviewloop' ),
				),
				'description' => __( 'One-time payment, yours to keep — a low-cost annual renewal (optional) keeps new versions coming.', 'reviewloop' ),
			),
			'pro'     => array(
				'badge'       => __( 'Pro', 'reviewloop' ),
				'name'        => __( 'Pro', 'reviewloop' ),
				'price'       => $pro_price,
				'features'    => array(
					__( 'Everything in Starter', 'reviewloop' ),
					__( 'Automatic WooCommerce order sync', 'reviewloop' ),
					__( 'Priority support', 'reviewloop' ),
				),
				'description' => __( 'One-time payment, yours to keep — a low-cost annual renewal (optional) keeps new versions coming.', 'reviewloop' ),
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
						<a class="rl-plan-cta" href="<?php echo esc_url( $pricing_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( sprintf( /* translators: %s: plan name */ __( 'Buy %s', 'reviewloop' ), $plan['name'] ) ); ?></a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
