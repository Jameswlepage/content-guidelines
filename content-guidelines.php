<?php
/**
 * Plugin Name: Content Guidelines
 * Plugin URI: https://github.com/WordPress/gutenberg
 * Description: Site-level editorial guidelines for WordPress. Define voice, tone, copy rules, and vocabulary that AI features can consume.
 * Version: 0.2.0
 * Author: WordPress Contributors
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: content-guidelines
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Requires Plugins: gutenberg
 *
 * @package ContentGuidelines
 */

namespace ContentGuidelines;

defined( 'ABSPATH' ) || exit;

define( 'CONTENT_GUIDELINES_VERSION', '0.2.0' );
define( 'CONTENT_GUIDELINES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CONTENT_GUIDELINES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 */
spl_autoload_register(
	function ( $class ) {
		$prefix   = 'ContentGuidelines\\';
		$base_dir = CONTENT_GUIDELINES_PLUGIN_DIR . 'includes/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Initialize the plugin.
 */
function init() {
	// Load text domain.
	load_plugin_textdomain( 'content-guidelines', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Initialize components.
	Post_Type::init();
	REST_Controller::init();
	Hooks::init();
	Abilities::init();

	// Register admin menu.
	add_action( 'admin_menu', __NAMESPACE__ . '\\register_admin_menu' );

	// Enqueue admin scripts.
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_scripts' );

	// Enqueue global command palette commands.
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_command_palette_scripts' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );

/**
 * Register the Content Guidelines menu item under Appearance.
 */
function register_admin_menu() {
	add_submenu_page(
		'themes.php',
		__( 'Guidelines', 'content-guidelines' ),
		__( 'Guidelines', 'content-guidelines' ),
		'edit_theme_options',
		'content-guidelines-wp-admin',
		__NAMESPACE__ . '\\render_admin_page'
	);
}

/**
 * Preload REST API data for the admin page.
 */
function preload_rest_data() {
	$preload_paths = array(
		'/?_fields=description,gmt_offset,home,name,site_icon,site_icon_url,site_logo,timezone_string,url,page_for_posts,page_on_front,show_on_front',
		'/wp/v2/content-guidelines',
		array( '/wp/v2/settings', 'OPTIONS' ),
	);

	$preload_data = array_reduce(
		$preload_paths,
		'rest_preload_api_request',
		array()
	);

	wp_add_inline_script(
		'wp-api-fetch',
		sprintf(
			'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
			wp_json_encode( $preload_data )
		),
		'after'
	);
}

/**
 * Enqueue scripts and styles for the admin page.
 *
 * @param string $hook_suffix The current admin page.
 */
function enqueue_admin_scripts( $hook_suffix ) {
	// Check if we're on our admin page.
	$is_our_page = isset( $_GET['page'] ) && 'content-guidelines-wp-admin' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! $is_our_page ) {
		return;
	}

	// Preload REST API data.
	preload_rest_data();

	$asset_file = CONTENT_GUIDELINES_PLUGIN_DIR . 'build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	// Define the routes for the boot system.
	$routes = array(
		array(
			'path' => '/',
		),
		array(
			'path' => '/history',
		),
		array(
			'path' => '/playground',
		),
	);

	// Register and enqueue the main script.
	wp_register_script(
		'content-guidelines-admin',
		CONTENT_GUIDELINES_PLUGIN_URL . 'build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	// Pass routes config to JS.
	wp_add_inline_script(
		'content-guidelines-admin',
		sprintf(
			'window.contentGuidelinesConfig = { mountId: "content-guidelines-wp-admin-app", routes: %s };',
			wp_json_encode( $routes, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
		),
		'before'
	);

	wp_enqueue_script( 'content-guidelines-admin' );

	// Enqueue styles.
	if ( file_exists( CONTENT_GUIDELINES_PLUGIN_DIR . 'build/style-index.css' ) ) {
		wp_enqueue_style(
			'content-guidelines-admin',
			CONTENT_GUIDELINES_PLUGIN_URL . 'build/style-index.css',
			array( 'wp-components' ),
			$asset['version']
		);
	}

	wp_set_script_translations( 'content-guidelines-admin', 'content-guidelines' );
}

/**
 * Enqueue Command Palette scripts globally across the admin.
 *
 * This enables Content Guidelines commands in the WordPress 6.9+ admin-wide
 * Command Palette (Ctrl/Cmd + K) from any admin screen.
 *
 * @param string $hook_suffix The current admin page.
 */
function enqueue_command_palette_scripts( $hook_suffix ) {
	// Only load if WordPress 6.9+ Command Palette is available.
	if ( ! wp_script_is( 'wp-commands', 'registered' ) ) {
		return;
	}

	$asset_file = CONTENT_GUIDELINES_PLUGIN_DIR . 'build/commands-loader.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	// Register and enqueue the commands loader script.
	wp_register_script(
		'content-guidelines-commands',
		CONTENT_GUIDELINES_PLUGIN_URL . 'build/commands-loader.js',
		array_merge( $asset['dependencies'], array( 'wp-commands' ) ),
		$asset['version'],
		true
	);

	// Pass configuration to the commands script.
	wp_add_inline_script(
		'content-guidelines-commands',
		sprintf(
			'window.contentGuidelinesCommandsConfig = { adminUrl: %s, restUrl: %s, nonce: %s };',
			wp_json_encode( admin_url() ),
			wp_json_encode( rest_url( 'wp/v2/content-guidelines' ) ),
			wp_json_encode( wp_create_nonce( 'wp_rest' ) )
		),
		'before'
	);

	wp_enqueue_script( 'content-guidelines-commands' );

	wp_set_script_translations( 'content-guidelines-commands', 'content-guidelines' );
}

/**
 * Render the admin page.
 */
function render_admin_page() {
	?>
	<style>
		/* Critical styles - match fonts library exactly */

		/* Dark background like fonts */
		#wpwrap {
			background: #1d2327;
		}

		/* Reset wp-admin padding */
		#wpcontent {
			padding-left: 0;
		}
		#wpbody-content {
			padding-bottom: 0;
		}

		/* Hide footer */
		#wpfooter {
			display: none;
		}

		/* Boot container - rounded white card */
		.boot-layout-container {
			background: #fff;
			border-radius: 8px;
			margin: 16px;
			min-height: calc(100vh - 32px - 32px);
			overflow: hidden;
			display: flex;
			flex-direction: column;
		}

		/* Admin menu arrow color */
		ul#adminmenu a.wp-has-current-submenu::after,
		ul#adminmenu > li.current > a.current::after {
			border-right-color: #1d2327;
		}

		/* Responsive */
		@media (max-width: 782px) {
			.boot-layout-container {
				margin: 10px;
				min-height: calc(100vh - 46px - 20px);
			}
		}

		@media (max-width: 600px) {
			.boot-layout-container {
				margin: 0;
				border-radius: 0;
				min-height: calc(100vh - 46px);
			}
		}
	</style>
	<div id="content-guidelines-wp-admin-app" class="boot-layout-container"></div>
	<?php
}

/**
 * Get the content guidelines for a site.
 *
 * @param string $use Which version to get: 'active' or 'draft'. Default 'active'.
 * @return array|null The guidelines data or null if not set.
 */
function get_content_guidelines( $use = 'active' ) {
	return Context_Packet_Builder::get_guidelines( $use );
}

/**
 * Get the context packet for AI consumption.
 *
 * @param array $args {
 *     Optional. Arguments for building the context packet.
 *
 *     @type string $task      Task type: 'writing', 'headline', 'cta', 'image', 'coach'. Default 'writing'.
 *     @type int    $post_id   Optional. Context post ID for per-post overrides.
 *     @type string $use       Which guidelines version: 'active' or 'draft'. Default 'active'.
 *     @type int    $max_chars Maximum characters for the packet text. Default 2000.
 *     @type string $locale    Optional. Locale for multilingual sites.
 * }
 * @return array {
 *     The context packet.
 *
 *     @type string $packet_text       Formatted text for LLM prompts.
 *     @type array  $packet_structured Structured subset of guidelines relevant to task.
 *     @type int    $guidelines_id     Post ID of the guidelines entity.
 *     @type int    $revision_id       Current revision ID.
 *     @type string $updated_at        ISO 8601 timestamp of last update.
 * }
 */
function get_content_guidelines_packet( $args = array() ) {
	return Context_Packet_Builder::get_packet( $args );
}

// Make the main functions available globally.
if ( ! function_exists( 'wp_get_content_guidelines' ) ) {
	/**
	 * Get site content guidelines.
	 *
	 * @param string $use Which version: 'active' or 'draft'.
	 * @return array|null Guidelines data.
	 */
	function wp_get_content_guidelines( $use = 'active' ) {
		return \ContentGuidelines\get_content_guidelines( $use );
	}
}

if ( ! function_exists( 'wp_get_content_guidelines_packet' ) ) {
	/**
	 * Get context packet for AI consumption.
	 *
	 * @param array $args Arguments for packet building.
	 * @return array Context packet.
	 */
	function wp_get_content_guidelines_packet( $args = array() ) {
		return \ContentGuidelines\get_content_guidelines_packet( $args );
	}
}
