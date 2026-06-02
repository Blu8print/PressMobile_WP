<?php
/**
 * Plugin Name: PressOnTheGO Connect
 * Plugin URI:  https://pressonthego.io
 * Description: Connect your WordPress site to the PressOnTheGO mobile app via QR code.
 * Version:     1.0.0
 * Author:      PressOnTheGO
 * License:     GPL-2.0+
 * Update URI:  https://github.com/Blu8print/PressOnTheGO_WP
 */

defined( 'ABSPATH' ) || exit;

define( 'PRESSONTHEGO_OPTION_KEY',  'pressonthego_api_key' );
define( 'PRESSONTHEGO_API_NS',      'pressonthego/v1' );
define( 'PRESSONTHEGO_SETTINGS_KEY', 'pressonthego_settings' );

// ── Activation ────────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 'pressonthego_activate' );
function pressonthego_activate(): void {
	if ( ! get_option( PRESSONTHEGO_OPTION_KEY ) ) {
		update_option( PRESSONTHEGO_OPTION_KEY, pressonthego_generate_key() );
	}
}

function pressonthego_generate_key(): string {
	return 'pg_' . bin2hex( random_bytes( 24 ) );
}

// ── Admin menu + assets ───────────────────────────────────────────────────────

add_action( 'admin_menu', 'pressonthego_admin_menu' );
function pressonthego_admin_menu(): void {
	add_options_page(
		'PressOnTheGO Connect',
		'PressOnTheGO',
		'manage_options',
		'pressonthego',
		'pressonthego_render_admin_page'
	);
}

add_action( 'admin_enqueue_scripts', 'pressonthego_enqueue_admin_assets' );
function pressonthego_enqueue_admin_assets( string $hook ): void {
	if ( $hook !== 'settings_page_pressonthego' ) {
		return;
	}
	wp_enqueue_script(
		'pressonthego-qrcode',
		plugin_dir_url( __FILE__ ) . 'qrcode.min.js',
		[],
		'1.0.0',
		true  // load in footer, after DOM is ready
	);
}

// ── Regenerate key ────────────────────────────────────────────────────────────

add_action( 'admin_post_pressonthego_save_settings', 'pressonthego_handle_save_settings' );
function pressonthego_handle_save_settings(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized', 403 );
	}
	check_admin_referer( 'pressonthego_save_settings' );

	$fields = [
		'company', 'industry', 'company_description', 'target_audience',
		'unique_selling_points', 'business_goals', 'primary_keywords',
		'secondary_keywords', 'social_media_platforms', 'jargon',
		'tone_of_voice', 'language',
	];

	$existing = get_option( PRESSONTHEGO_SETTINGS_KEY ) ?: [];
	foreach ( $fields as $field ) {
		$existing[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) );
	}
	update_option( PRESSONTHEGO_SETTINGS_KEY, $existing );

	wp_redirect( admin_url( 'options-general.php?page=pressonthego&settings_saved=1' ) );
	exit;
}

add_action( 'admin_post_pressonthego_regenerate', 'pressonthego_handle_regenerate' );
function pressonthego_handle_regenerate(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized', 403 );
	}
	check_admin_referer( 'pressonthego_regenerate' );
	update_option( PRESSONTHEGO_OPTION_KEY, pressonthego_generate_key() );
	wp_redirect( admin_url( 'options-general.php?page=pressonthego&regenerated=1' ) );
	exit;
}

// ── Admin page ────────────────────────────────────────────────────────────────

function pressonthego_render_admin_page(): void {
	$api_key       = get_option( PRESSONTHEGO_OPTION_KEY );
	$site_url      = get_site_url();
	$qr_payload    = wp_json_encode( [ 'url' => $site_url, 'key' => $api_key ] );
	$regenerated   = isset( $_GET['regenerated'] );
	$settings_saved = isset( $_GET['settings_saved'] );
	$s             = get_option( PRESSONTHEGO_SETTINGS_KEY ) ?: [];
	?>
	<div class="wrap">
		<h1>PressOnTheGO Connect</h1>

		<?php if ( $regenerated ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>API key regenerated. Scan the new QR code in the PressOnTheGO app to reconnect.</p>
			</div>
		<?php endif; ?>

		<?php if ( $settings_saved ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Company profile saved.</p>
			</div>
		<?php endif; ?>

		<div style="max-width:520px;margin-top:24px;">

			<p style="color:#555;">
				Scan this QR code in the PressOnTheGO app to connect your site instantly.
			</p>

			<div style="margin:24px 0;padding:16px;background:#fff;border:1px solid #ddd;display:inline-block;border-radius:8px;">
				<div id="pressonthego-qr"></div>
				<p id="pressonthego-qr-error" style="color:#b00020;display:none;margin:8px 0 0;max-width:220px;"></p>
			</div>

			<table class="form-table" style="margin-top:0;">
				<tr>
					<th scope="row" style="width:100px;">Site URL</th>
					<td><code><?php echo esc_html( $site_url ); ?></code></td>
				</tr>
				<tr>
					<th scope="row">API key</th>
					<td>
						<code id="pressonthego-key" style="word-break:break-all;"><?php echo esc_html( $api_key ); ?></code>
					</td>
				</tr>
			</table>

			<hr style="margin:20px 0;">

			<h3 style="margin-top:0;">Rotate API key</h3>
			<p style="color:#555;">
				Generate a new key if your current one is compromised.
				<strong>Your existing PressOnTheGO connection will stop working</strong> until you scan the new QR code.
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pressonthego_regenerate">
				<?php wp_nonce_field( 'pressonthego_regenerate' ); ?>
				<button
					type="submit"
					class="button button-secondary"
					onclick="return confirm('This will invalidate your current API key.\n\nYou will need to scan the new QR code in PressOnTheGO to reconnect.\n\nContinue?');"
				>
					Regenerate key
				</button>
			</form>

			<hr style="margin:20px 0;">

		<hr style="margin:20px 0;">

		<details open>
			<summary style="cursor:pointer;color:#1d2327;font-weight:600;font-size:14px;">Company profile</summary>
			<p style="color:#555;margin:8px 0 16px;">
				Fill in your company details so the PressOnTheGO app can write content tailored to your business.
				You can also set these up in the app itself.
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pressonthego_save_settings">
				<?php wp_nonce_field( 'pressonthego_save_settings' ); ?>

				<h4 style="margin:0 0 8px;">Company</h4>
				<table class="form-table" style="margin-top:0;">
					<tr>
						<th scope="row" style="width:200px;">Company name</th>
						<td><input type="text" name="company" value="<?php echo esc_attr( $s['company'] ?? '' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row">Industry / sector</th>
						<td><input type="text" name="industry" value="<?php echo esc_attr( $s['industry'] ?? '' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row">Company description</th>
						<td><textarea name="company_description" rows="3" class="large-text"><?php echo esc_textarea( $s['company_description'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row">Language</th>
						<td>
							<?php
							$langs     = [ 'nl' => 'Dutch', 'en' => 'English', 'de' => 'German', 'fr' => 'French', 'es' => 'Spanish', 'pl' => 'Polish' ];
							$wp_lang   = strtolower( substr( get_locale(), 0, 2 ) );
							$auto      = array_key_exists( $wp_lang, $langs ) ? $wp_lang : 'en';
							$cur       = isset( $s['language'] ) && $s['language'] !== '' ? $s['language'] : null;
							$effective = $cur ?? $auto;
							?>
							<select name="language">
								<?php
								foreach ( $langs as $code => $label ) {
									printf(
										'<option value="%s"%s>%s</option>',
										esc_attr( $code ),
										selected( $effective, $code, false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<?php if ( $cur === null ) : ?>
								<p class="description">Auto-detected from WordPress site language (<?php echo esc_html( get_locale() ); ?>). Save to lock in a choice.</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<h4 style="margin:16px 0 8px;">Audience</h4>
				<table class="form-table" style="margin-top:0;">
					<tr>
						<th scope="row" style="width:200px;">Target audience</th>
						<td><textarea name="target_audience" rows="2" class="large-text"><?php echo esc_textarea( $s['target_audience'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row">Unique selling points</th>
						<td><textarea name="unique_selling_points" rows="2" class="large-text"><?php echo esc_textarea( $s['unique_selling_points'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row">Business goals</th>
						<td><textarea name="business_goals" rows="2" class="large-text"><?php echo esc_textarea( $s['business_goals'] ?? '' ); ?></textarea></td>
					</tr>
				</table>

				<h4 style="margin:16px 0 8px;">SEO</h4>
				<table class="form-table" style="margin-top:0;">
					<tr>
						<th scope="row" style="width:200px;">Primary keywords</th>
						<td><input type="text" name="primary_keywords" value="<?php echo esc_attr( $s['primary_keywords'] ?? '' ); ?>" class="large-text" placeholder="e.g. sourdough bread, Amsterdam bakery"></td>
					</tr>
					<tr>
						<th scope="row">Secondary keywords</th>
						<td><input type="text" name="secondary_keywords" value="<?php echo esc_attr( $s['secondary_keywords'] ?? '' ); ?>" class="large-text"></td>
					</tr>
				</table>

				<h4 style="margin:16px 0 8px;">Brand voice</h4>
				<table class="form-table" style="margin-top:0;">
					<tr>
						<th scope="row" style="width:200px;">Tone of voice</th>
						<td><input type="text" name="tone_of_voice" value="<?php echo esc_attr( $s['tone_of_voice'] ?? '' ); ?>" class="large-text" placeholder="e.g. warm, informal, expert"></td>
					</tr>
					<tr>
						<th scope="row">Industry jargon</th>
						<td><textarea name="jargon" rows="2" class="large-text" placeholder="Terms to use or avoid"><?php echo esc_textarea( $s['jargon'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row">Social media platforms</th>
						<td><input type="text" name="social_media_platforms" value="<?php echo esc_attr( $s['social_media_platforms'] ?? '' ); ?>" class="large-text" placeholder="e.g. Instagram, LinkedIn"></td>
					</tr>
				</table>

				<p class="submit" style="padding-top:8px;">
					<button type="submit" class="button button-primary">Save company profile</button>
				</p>
			</form>
		</details>

		<hr style="margin:20px 0;">

			<details>
				<summary style="cursor:pointer;color:#555;font-weight:600;">Debug info</summary>
				<table class="widefat" style="margin-top:12px;">
					<tr><th style="width:160px;">PHP version</th><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
					<tr><th>Plugin dir URL</th><td><?php echo esc_html( plugin_dir_url( __FILE__ ) ); ?></td></tr>
					<tr><th>API key set</th><td><?php echo $api_key ? 'Yes (' . esc_html( substr( $api_key, 0, 8 ) ) . '…)' : '<strong style="color:#b00020">NO — deactivate and reactivate the plugin</strong>'; ?></td></tr>
					<tr><th>QR payload</th><td><code style="word-break:break-all;"><?php echo esc_html( $qr_payload ); ?></code></td></tr>
					<tr><th>QRCode library</th><td id="pressonthego-debug-lib">Checking…</td></tr>
					<tr><th>QR render</th><td id="pressonthego-debug-render">Pending…</td></tr>
				</table>
			</details>

		</div>
	</div>

	<script>
	// DOMContentLoaded fires after all synchronous footer scripts (including qrcode.min.js) have run.
	document.addEventListener('DOMContentLoaded', function() {
	(function() {
		var payload = <?php echo wp_json_encode( $qr_payload ); ?>;
		var libEl   = document.getElementById('pressonthego-debug-lib');
		var renEl   = document.getElementById('pressonthego-debug-render');
		var errEl   = document.getElementById('pressonthego-qr-error');

		if (typeof QRCode === 'undefined') {
			libEl.innerHTML = '<strong style="color:#b00020">NOT loaded — check browser console (F12) for script errors</strong>';
			renEl.textContent = 'Skipped (library missing)';
			errEl.textContent = 'QR library failed to load. Open browser console (F12) for details.';
			errEl.style.display = 'block';
			return;
		}

		libEl.textContent = 'Loaded ✓';

		try {
			new QRCode(document.getElementById('pressonthego-qr'), {
				text:         payload,
				width:        220,
				height:       220,
				colorDark:    '#000000',
				colorLight:   '#ffffff',
				correctLevel: QRCode.CorrectLevel.M,
			});
			renEl.textContent = 'Success ✓';
		} catch (e) {
			renEl.innerHTML = '<strong style="color:#b00020">Error: ' + e.message + '</strong>';
			errEl.textContent = 'QR render failed: ' + e.message;
			errEl.style.display = 'block';
		}
	})();
	}); // end DOMContentLoaded
	</script>
	<?php
}

// ── Auto-update (GitHub releases) ────────────────────────────────────────────

add_filter( 'pre_set_site_transient_update_plugins', 'pressonthego_check_for_update' );
function pressonthego_check_for_update( $transient ) {
	if ( empty( $transient->checked ) ) return $transient;

	$release = pressonthego_get_latest_release();
	if ( ! $release ) return $transient;

	$latest  = ltrim( $release->tag_name, 'v' );
	$current = get_file_data( __FILE__, [ 'Version' => 'Version' ] )['Version'];

	if ( version_compare( $latest, $current, '<=' ) ) return $transient;

	$zip_url = pressonthego_release_zip_url( $release );
	if ( ! $zip_url ) return $transient;

	$transient->response[ plugin_basename( __FILE__ ) ] = (object) [
		'slug'        => 'pressonthego-connect',
		'plugin'      => plugin_basename( __FILE__ ),
		'new_version' => $latest,
		'url'         => 'https://github.com/Blu8print/PressOnTheGO_WP',
		'package'     => $zip_url,
	];

	return $transient;
}

add_filter( 'plugins_api', 'pressonthego_plugin_info', 20, 3 );
function pressonthego_plugin_info( $result, $action, $args ) {
	if ( $action !== 'plugin_information' || ( $args->slug ?? '' ) !== 'pressonthego-connect' ) {
		return $result;
	}

	$release = pressonthego_get_latest_release();
	if ( ! $release ) return $result;

	return (object) [
		'name'          => 'PressOnTheGO Connect',
		'slug'          => 'pressonthego-connect',
		'version'       => ltrim( $release->tag_name, 'v' ),
		'author'        => 'PressOnTheGO',
		'homepage'      => 'https://github.com/Blu8print/PressOnTheGO_WP',
		'download_link' => pressonthego_release_zip_url( $release ),
		'sections'      => [
			'description' => 'Connect your WordPress site to the PressOnTheGO mobile app.',
			'changelog'   => nl2br( esc_html( $release->body ?? '' ) ),
		],
	];
}

function pressonthego_get_latest_release(): ?object {
	$cached = get_transient( 'pressonthego_latest_release' );
	if ( $cached ) return $cached;

	$response = wp_remote_get(
		'https://api.github.com/repos/Blu8print/PressOnTheGO_WP/releases/latest',
		[
			'headers' => [ 'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) ],
			'timeout' => 10,
		]
	);

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return null;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ) );
	if ( empty( $data->tag_name ) ) return null;

	set_transient( 'pressonthego_latest_release', $data, 12 * HOUR_IN_SECONDS );
	return $data;
}

function pressonthego_release_zip_url( object $release ): ?string {
	foreach ( $release->assets ?? [] as $asset ) {
		if ( pathinfo( $asset->name, PATHINFO_EXTENSION ) === 'zip' ) {
			return $asset->browser_download_url;
		}
	}
	return null;
}

// ── REST routes ───────────────────────────────────────────────────────────────

add_action( 'rest_api_init', 'pressonthego_register_routes' );
function pressonthego_register_routes(): void {
	// Site info (categories, tags, site name)
	register_rest_route( PRESSONTHEGO_API_NS, '/info', [
		'methods'             => 'GET',
		'callback'            => 'pressonthego_get_info',
		'permission_callback' => 'pressonthego_authenticate',
	] );

	// List posts
	register_rest_route( PRESSONTHEGO_API_NS, '/posts', [
		'methods'             => 'GET',
		'callback'            => 'pressonthego_get_posts',
		'permission_callback' => 'pressonthego_authenticate',
	] );

	// Create post
	register_rest_route( PRESSONTHEGO_API_NS, '/posts', [
		'methods'             => 'POST',
		'callback'            => 'pressonthego_create_post',
		'permission_callback' => 'pressonthego_authenticate',
	] );

	// Get single post
	register_rest_route( PRESSONTHEGO_API_NS, '/posts/(?P<id>\d+)', [
		'methods'             => 'GET',
		'callback'            => 'pressonthego_get_post',
		'permission_callback' => 'pressonthego_authenticate',
		'args'                => [
			'id' => [ 'validate_callback' => fn( $v ) => is_numeric( $v ) ],
		],
	] );

	// Update post
	register_rest_route( PRESSONTHEGO_API_NS, '/posts/(?P<id>\d+)', [
		'methods'             => 'PUT',
		'callback'            => 'pressonthego_update_post',
		'permission_callback' => 'pressonthego_authenticate',
		'args'                => [
			'id' => [ 'validate_callback' => fn( $v ) => is_numeric( $v ) ],
		],
	] );

	// Update company settings (from app)
	register_rest_route( PRESSONTHEGO_API_NS, '/settings', [
		'methods'             => 'PUT',
		'callback'            => 'pressonthego_update_settings',
		'permission_callback' => 'pressonthego_authenticate',
	] );

	// Upload media
	register_rest_route( PRESSONTHEGO_API_NS, '/media', [
		'methods'             => 'POST',
		'callback'            => 'pressonthego_upload_media',
		'permission_callback' => 'pressonthego_authenticate',
	] );

	// Trigger plugin self-update from app
	register_rest_route( PRESSONTHEGO_API_NS, '/update', [
		'methods'             => 'POST',
		'callback'            => 'pressonthego_trigger_update',
		'permission_callback' => 'pressonthego_authenticate',
	] );

	// Adopt post (mark as PressOnTheGO-managed so it can be edited in the app)
	register_rest_route( PRESSONTHEGO_API_NS, '/posts/(?P<id>\d+)/adopt', [
		'methods'             => 'POST',
		'callback'            => 'pressonthego_adopt_post',
		'permission_callback' => 'pressonthego_authenticate',
		'args'                => [
			'id' => [ 'validate_callback' => fn( $v ) => is_numeric( $v ) ],
		],
	] );

	// Create category
	register_rest_route( PRESSONTHEGO_API_NS, '/categories', [
		'methods'             => 'POST',
		'callback'            => 'pressonthego_create_category',
		'permission_callback' => 'pressonthego_authenticate',
	] );
}

// ── Authentication ────────────────────────────────────────────────────────────

function pressonthego_authenticate( WP_REST_Request $request ): bool {
	$key = $request->get_header( 'X-PressOnTheGO-Key' );
	if ( ! $key || $key !== get_option( PRESSONTHEGO_OPTION_KEY ) ) {
		return false;
	}
	// Set current user to first admin so WP internals (capabilities, authorship) work correctly.
	$admins = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
	if ( $admins ) {
		wp_set_current_user( $admins[0]->ID );
	}
	return true;
}

// ── GET /info ─────────────────────────────────────────────────────────────────

function pressonthego_get_info(): WP_REST_Response {
	$s = get_option( PRESSONTHEGO_SETTINGS_KEY ) ?: [];

	return new WP_REST_Response( [
		'name'                   => get_bloginfo( 'name' ),
		'url'                    => get_site_url(),
		'categories'             => array_values( array_map(
			fn( $c ) => [ 'id' => $c->term_id, 'name' => $c->name ],
			get_categories( [ 'hide_empty' => false ] )
		) ),
		'tags'                   => array_values( array_map(
			fn( $t ) => [ 'id' => $t->term_id, 'name' => $t->name ],
			get_tags( [ 'hide_empty' => false ] )
		) ),
		// Plugin version info
		'plugin_version'         => get_file_data( __FILE__, [ 'Version' => 'Version' ] )['Version'],
		'latest_version'         => (function() {
			$release = pressonthego_get_latest_release();
			return $release ? ltrim( $release->tag_name, 'v' ) : null;
		})(),
		// Company profile fields
		'company'                => $s['company']                ?? null,
		'industry'               => $s['industry']               ?? null,
		'company_description'    => $s['company_description']    ?? null,
		'target_audience'        => $s['target_audience']        ?? null,
		'unique_selling_points'  => $s['unique_selling_points']  ?? null,
		'business_goals'         => $s['business_goals']         ?? null,
		'primary_keywords'       => $s['primary_keywords']       ?? null,
		'secondary_keywords'     => $s['secondary_keywords']     ?? null,
		'social_media_platforms' => $s['social_media_platforms'] ?? null,
		'jargon'                 => $s['jargon']                 ?? null,
		'tone_of_voice'          => $s['tone_of_voice']          ?? null,
		'language'               => (isset( $s['language'] ) && $s['language'] !== '') ? $s['language'] : (function() {
			$wp_lang   = strtolower( substr( get_locale(), 0, 2 ) );
			$supported = [ 'nl', 'en', 'de', 'fr', 'es', 'pl' ];
			return in_array( $wp_lang, $supported, true ) ? $wp_lang : 'en';
		})(),
	] );
}

// ── PUT /settings ─────────────────────────────────────────────────────────────

function pressonthego_update_settings( WP_REST_Request $request ): WP_REST_Response {
	$p = $request->get_json_params() ?? [];

	$fields = [
		'company', 'industry', 'company_description', 'target_audience',
		'unique_selling_points', 'business_goals', 'primary_keywords',
		'secondary_keywords', 'social_media_platforms', 'jargon',
		'tone_of_voice', 'language',
	];

	$existing = get_option( PRESSONTHEGO_SETTINGS_KEY ) ?: [];
	foreach ( $fields as $field ) {
		if ( array_key_exists( $field, $p ) ) {
			$existing[ $field ] = sanitize_text_field( (string) ( $p[ $field ] ?? '' ) );
		}
	}
	update_option( PRESSONTHEGO_SETTINGS_KEY, $existing );

	return new WP_REST_Response( [ 'ok' => true ], 200 );
}

// ── GET /posts ────────────────────────────────────────────────────────────────

function pressonthego_get_posts(): WP_REST_Response {
	$posts = get_posts( [
		'post_status'    => [ 'publish', 'draft' ],
		'posts_per_page' => 20,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );

	return new WP_REST_Response( array_map( fn( $p ) => [
		'id'              => $p->ID,
		'title'           => get_the_title( $p ),
		'status'          => $p->post_status,
		'date'            => get_post_datetime( $p )->format( 'c' ),
		'url'             => get_permalink( $p ),
		'is_pressonthego_post' => (bool) get_post_meta( $p->ID, '_pressonthego_post', true ),
	], $posts ) );
}

// ── GET /posts/{id} ───────────────────────────────────────────────────────────

function pressonthego_get_post( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$id   = intval( $request['id'] );
	$post = get_post( $id );
	if ( ! $post ) {
		return new WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
	}

	[ $seo_title, $meta_desc ] = pressonthego_read_seo_meta( $id );

	// Categories
	$categories = array_values( array_map(
		fn( $t ) => [ 'id' => $t->term_id, 'name' => $t->name ],
		wp_get_post_categories( $id, [ 'fields' => 'all' ] )
	) );

	// Tags
	$tags = array_values( array_map(
		fn( $t ) => $t->name,
		wp_get_post_tags( $id )
	) );

	// Featured image
	$featured_media_url = get_the_post_thumbnail_url( $id, 'full' ) ?: null;
	$featured_media_id  = (int) get_post_thumbnail_id( $id ) ?: null;

	return new WP_REST_Response( [
		'id'                  => $id,
		'title'               => get_the_title( $post ),
		'content'             => $post->post_content,
		'excerpt'             => $post->post_excerpt,
		'slug'                => $post->post_name,
		'status'              => $post->post_status,
		'seo_title'           => $seo_title,
		'meta_description'    => $meta_desc,
		'featured_media_url'  => $featured_media_url,
		'featured_media_id'   => $featured_media_id,
		'categories'          => $categories,
		'tags'                => $tags,
		'is_pressonthego_post'     => (bool) get_post_meta( $id, '_pressonthego_post', true ),
	] );
}

// ── POST /posts ───────────────────────────────────────────────────────────────

function pressonthego_create_post( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$p = $request->get_json_params();

	$status = pressonthego_resolve_status( $p['status'] ?? 'draft', $p['scheduled_at'] ?? null );

	$post_data = [
		'post_title'   => sanitize_text_field( $p['title']   ?? '' ),
		'post_content' => wp_kses_post( $p['content']        ?? '' ),
		'post_excerpt' => sanitize_text_field( $p['excerpt'] ?? '' ),
		'post_name'    => sanitize_title( $p['slug']         ?? '' ),
		'post_status'  => $status,
		'post_author'  => get_current_user_id(),
	];

	if ( $status === 'future' && ! empty( $p['scheduled_at'] ) ) {
		$post_data['post_date']     = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $p['scheduled_at'] ) ) );
		$post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime( $p['scheduled_at'] ) );
	}

	$post_id = wp_insert_post( $post_data, true );
	if ( is_wp_error( $post_id ) ) return $post_id;

	pressonthego_apply_meta( $post_id, $p );

	return new WP_REST_Response( [
		'id'  => $post_id,
		'url' => get_permalink( $post_id ),
	], 201 );
}

// ── PUT /posts/{id} ───────────────────────────────────────────────────────────

function pressonthego_update_post( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$id = intval( $request['id'] );
	if ( ! get_post( $id ) ) {
		return new WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
	}

	$p = $request->get_json_params();

	$post_data = [ 'ID' => $id ];
	if ( isset( $p['title'] ) )   $post_data['post_title']   = sanitize_text_field( $p['title'] );
	if ( isset( $p['content'] ) ) $post_data['post_content'] = wp_kses_post( $p['content'] );
	if ( isset( $p['excerpt'] ) ) $post_data['post_excerpt'] = sanitize_text_field( $p['excerpt'] );
	if ( isset( $p['slug'] ) )    $post_data['post_name']    = sanitize_title( $p['slug'] );
	if ( isset( $p['status'] ) )  $post_data['post_status']  = pressonthego_resolve_status( $p['status'], $p['scheduled_at'] ?? null );

	if ( isset( $post_data['post_status'] ) && $post_data['post_status'] === 'future' && ! empty( $p['scheduled_at'] ) ) {
		$post_data['post_date']     = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $p['scheduled_at'] ) ) );
		$post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime( $p['scheduled_at'] ) );
	}

	$result = wp_update_post( $post_data, true );
	if ( is_wp_error( $result ) ) return $result;

	pressonthego_apply_meta( $id, $p );

	return new WP_REST_Response( [
		'id'  => $id,
		'url' => get_permalink( $id ),
	], 200 );
}

// ── POST /media ───────────────────────────────────────────────────────────────

function pressonthego_upload_media( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$files = $request->get_file_params();
	if ( empty( $files['file'] ) ) {
		return new WP_Error( 'no_file', 'No file provided.', [ 'status' => 400 ] );
	}

	$upload = wp_handle_upload( $files['file'], [ 'test_form' => false ] );
	if ( isset( $upload['error'] ) ) {
		return new WP_Error( 'upload_failed', $upload['error'], [ 'status' => 500 ] );
	}

	$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : null;
	$attachment_id = wp_insert_attachment( [
		'post_title'     => $title ? $title : sanitize_file_name( $files['file']['name'] ),
		'post_mime_type' => $upload['type'],
		'post_status'    => 'inherit',
	], $upload['file'] );

	if ( is_wp_error( $attachment_id ) ) return $attachment_id;

	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
	);

	$alt = isset( $_POST['alt'] ) ? sanitize_text_field( wp_unslash( $_POST['alt'] ) ) : null;
	if ( $alt ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	}

	return new WP_REST_Response( [
		'id'  => $attachment_id,
		'url' => wp_get_attachment_url( $attachment_id ),
	], 201 );
}

// ── POST /posts/{id}/adopt ────────────────────────────────────────────────────

function pressonthego_adopt_post( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$id   = intval( $request['id'] );
	$post = get_post( $id );
	if ( ! $post ) {
		return new WP_Error( 'not_found', 'Post not found.', [ 'status' => 404 ] );
	}
	update_post_meta( $id, '_pressonthego_post', '1' );
	return new WP_REST_Response( [ 'ok' => true, 'id' => $id ], 200 );
}

// ── POST /categories ─────────────────────────────────────────────────────────

function pressonthego_create_category( WP_REST_Request $req ): WP_REST_Response {
	$name = sanitize_text_field( $req->get_param( 'name' ) ?? '' );
	if ( empty( $name ) ) {
		return new WP_REST_Response( [ 'error' => 'Name required' ], 400 );
	}
	$existing = get_term_by( 'name', $name, 'category' );
	if ( $existing ) {
		return new WP_REST_Response( [ 'id' => $existing->term_id, 'name' => $existing->name ], 200 );
	}
	$result = wp_insert_term( $name, 'category' );
	if ( is_wp_error( $result ) ) {
		return new WP_REST_Response( [ 'error' => $result->get_error_message() ], 500 );
	}
	$term = get_term( $result['term_id'], 'category' );
	return new WP_REST_Response( [ 'id' => $term->term_id, 'name' => $term->name ], 201 );
}

// ── POST /update ──────────────────────────────────────────────────────────────

function pressonthego_trigger_update(): WP_REST_Response|WP_Error {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	if ( ! WP_Filesystem() ) {
		return new WP_Error( 'fs_unavailable', 'Filesystem not available — update via WP admin instead.', [ 'status' => 503 ] );
	}

	$release = pressonthego_get_latest_release();
	if ( ! $release ) {
		return new WP_Error( 'no_release', 'Could not fetch latest release from GitHub.', [ 'status' => 503 ] );
	}

	$zip_url = pressonthego_release_zip_url( $release );
	if ( ! $zip_url ) {
		return new WP_Error( 'no_zip', 'No zip asset found in release.', [ 'status' => 503 ] );
	}

	$plugin_file = plugin_basename( __FILE__ );
	$new_version = ltrim( $release->tag_name, 'v' );

	// Inject the update into the transient so Plugin_Upgrader can find the package URL.
	$current = get_site_transient( 'update_plugins' ) ?: new stdClass();
	if ( ! isset( $current->response ) ) $current->response = [];
	$current->response[ $plugin_file ] = (object) [
		'slug'        => 'pressonthego-connect',
		'plugin'      => $plugin_file,
		'new_version' => $new_version,
		'package'     => $zip_url,
		'url'         => 'https://github.com/Blu8print/PressOnTheGO_WP',
	];
	set_site_transient( 'update_plugins', $current );

	$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->upgrade( $plugin_file );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( 'upgrade_failed', $result->get_error_message(), [ 'status' => 500 ] );
	}
	if ( $result === false ) {
		return new WP_Error( 'upgrade_failed', 'Plugin upgrade returned false — check WP filesystem permissions.', [ 'status' => 500 ] );
	}

	delete_transient( 'pressonthego_latest_release' );

	return new WP_REST_Response( [ 'ok' => true, 'version' => $new_version ], 200 );
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Resolve post status — 'publish', 'draft', or 'future' (when scheduled_at is set).
 */
function pressonthego_resolve_status( string $requested, ?string $scheduled_at ): string {
	if ( $scheduled_at && strtotime( $scheduled_at ) > time() ) {
		return 'future';
	}
	return in_array( $requested, [ 'publish', 'draft' ], true ) ? $requested : 'draft';
}

/**
 * Read SEO title and meta description from Yoast or Rank Math.
 * Returns [seo_title, meta_description].
 */
function pressonthego_read_seo_meta( int $post_id ): array {
	$seo_title = '';
	$meta_desc = '';

	if ( defined( 'WPSEO_VERSION' ) ) {
		$seo_title = get_post_meta( $post_id, '_yoast_wpseo_title',    true ) ?: '';
		$meta_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) ?: '';
	} elseif ( defined( 'RANK_MATH_VERSION' ) ) {
		$seo_title = get_post_meta( $post_id, 'rank_math_title',       true ) ?: '';
		$meta_desc = get_post_meta( $post_id, 'rank_math_description', true ) ?: '';
	}

	return [ $seo_title, $meta_desc ];
}

/**
 * Apply categories, tags, featured image, and SEO meta to a post.
 * Supports Yoast SEO and Rank Math automatically.
 */
function pressonthego_apply_meta( int $post_id, array $p ): void {
	// Mark this post as PressOnTheGO-managed so it can be loaded back for editing.
	update_post_meta( $post_id, '_pressonthego_post', '1' );
	if ( ! empty( $p['categories'] ) ) {
		wp_set_post_categories( $post_id, array_map( 'intval', $p['categories'] ) );
	}
	if ( ! empty( $p['tags'] ) ) {
		wp_set_post_tags( $post_id, array_map( 'sanitize_text_field', $p['tags'] ) );
	}
	if ( ! empty( $p['featured_media'] ) ) {
		set_post_thumbnail( $post_id, intval( $p['featured_media'] ) );
	}

	$meta_desc = sanitize_text_field( $p['meta_description'] ?? '' );
	$seo_title = sanitize_text_field( $p['seo_title']        ?? '' );

	if ( $meta_desc || $seo_title ) {
		// Yoast SEO
		if ( defined( 'WPSEO_VERSION' ) ) {
			if ( $meta_desc ) update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_desc );
			if ( $seo_title ) update_post_meta( $post_id, '_yoast_wpseo_title',    $seo_title );
		}
		// Rank Math
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			if ( $meta_desc ) update_post_meta( $post_id, 'rank_math_description', $meta_desc );
			if ( $seo_title ) update_post_meta( $post_id, 'rank_math_title',       $seo_title );
		}
	}
}
