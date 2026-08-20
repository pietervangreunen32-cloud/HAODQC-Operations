<?php
/**
 * Renders the Google Business Profile connection panel on the Settings
 * screen and handles its two form actions (save credentials, disconnect).
 * Kept separate from ReviewLoop_Admin_Menu so the settings screen doesn't
 * grow into one giant class as more integrations are added.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Google_Settings {

	public function init() {
		add_action( 'reviewloop_after_settings_panels', array( $this, 'render_panel' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	public function handle_actions() {
		if ( ! isset( $_POST['reviewloop_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['reviewloop_action'] ) );

		if ( 'save_google_config' === $action ) {
			check_admin_referer( 'reviewloop_save_google_config' );
			ReviewLoop_Settings::save_google_config( $_POST );
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'disconnect_google' === $action ) {
			check_admin_referer( 'reviewloop_disconnect_google' );
			( new ReviewLoop_Google_Api() )->disconnect();
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'google_disconnected' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	public function render_notices() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'reviewloop' ) === false || ! isset( $_GET['rl_msg'] ) ) {
			return;
		}

		$msg = sanitize_key( wp_unslash( $_GET['rl_msg'] ) );

		$map = array(
			'google_connected'            => array( 'success', __( 'Google Business Profile connected.', 'reviewloop' ) ),
			'google_disconnected'         => array( 'success', __( 'Google Business Profile disconnected.', 'reviewloop' ) ),
			'google_connect_failed'       => array( 'error', __( 'Could not connect to Google. Please try again.', 'reviewloop' ) ),
			'google_missing_credentials'  => array( 'error', __( 'Add your Google Client ID and Client Secret first, then connect.', 'reviewloop' ) ),
		);

		if ( isset( $map[ $msg ] ) ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $map[ $msg ][0] ), esc_html( $map[ $msg ][1] ) );
		}
	}

	public function render_panel( $settings ) {
		$google     = new ReviewLoop_Google_Api();
		$connected  = $google->is_connected();
		$redirect   = ReviewLoop_Google_Api::redirect_uri();
		?>
		<div class="reviewloop-panel">
			<h2><?php esc_html_e( 'Google Business Profile connection', 'reviewloop' ); ?></h2>

			<?php if ( $connected ) : ?>
				<p><span class="rl-status rl-status-active"><?php esc_html_e( 'Connected', 'reviewloop' ); ?></span></p>
				<p class="description">
					<?php
					echo esc_html(
						! empty( $settings['google_location_name'] )
							? sprintf( /* translators: %s: Google location resource name */ __( 'Polling reviews for location: %s', 'reviewloop' ), $settings['google_location_name'] )
							: __( 'Connected, but no location was auto-detected. Set the location resource name below.', 'reviewloop' )
					);
					?>
				</p>

				<form method="post" style="margin-bottom:16px;">
					<?php wp_nonce_field( 'reviewloop_save_google_config' ); ?>
					<input type="hidden" name="reviewloop_action" value="save_google_config">
					<input type="hidden" name="google_client_id" value="<?php echo esc_attr( $settings['google_client_id'] ); ?>">
					<input type="hidden" name="google_client_secret" value="<?php echo esc_attr( $settings['google_client_secret'] ); ?>">
					<label for="google_location_name"><?php esc_html_e( 'Location resource name (only needed if you have multiple locations, or auto-detection didn\'t find one)', 'reviewloop' ); ?></label><br>
					<input type="text" id="google_location_name" name="google_location_name" class="regular-text" placeholder="accounts/123456789/locations/987654321" value="<?php echo esc_attr( $settings['google_location_name'] ); ?>">
					<button type="submit" class="button"><?php esc_html_e( 'Save', 'reviewloop' ); ?></button>
				</form>

				<form method="post">
					<?php wp_nonce_field( 'reviewloop_disconnect_google' ); ?>
					<input type="hidden" name="reviewloop_action" value="disconnect_google">
					<button type="submit" class="button rl-confirm" data-confirm="<?php esc_attr_e( 'Disconnect Google Business Profile? Review polling will stop.', 'reviewloop' ); ?>"><?php esc_html_e( 'Disconnect', 'reviewloop' ); ?></button>
				</form>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Requires a Google Cloud project with the Business Profile API enabled. Create OAuth 2.0 credentials and add the redirect URI below.', 'reviewloop' ); ?></p>

				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Redirect URI', 'reviewloop' ); ?></th>
						<td><code><?php echo esc_html( $redirect ); ?></code></td>
					</tr>
				</table>

				<form method="post" style="margin-bottom:16px;">
					<?php wp_nonce_field( 'reviewloop_save_google_config' ); ?>
					<input type="hidden" name="reviewloop_action" value="save_google_config">
					<table class="form-table">
						<tr>
							<th><label for="google_client_id"><?php esc_html_e( 'Client ID', 'reviewloop' ); ?></label></th>
							<td><input type="text" id="google_client_id" name="google_client_id" class="regular-text" value="<?php echo esc_attr( $settings['google_client_id'] ); ?>"></td>
						</tr>
						<tr>
							<th><label for="google_client_secret"><?php esc_html_e( 'Client Secret', 'reviewloop' ); ?></label></th>
							<td><input type="password" id="google_client_secret" name="google_client_secret" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $settings['google_client_secret'] ); ?>"></td>
						</tr>
					</table>
					<button type="submit" class="button"><?php esc_html_e( 'Save Credentials', 'reviewloop' ); ?></button>
				</form>

				<?php if ( ! empty( $settings['google_client_id'] ) && ! empty( $settings['google_client_secret'] ) ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=reviewloop_google_connect' ), 'reviewloop_google_connect' ) ); ?>"><?php esc_html_e( 'Connect with Google', 'reviewloop' ); ?></a>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}
