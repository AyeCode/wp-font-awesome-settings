<?php
/**
 * Package entry point for Font Awesome Settings.
 *
 * Sole location for WordPress hook registrations. No business logic lives here —
 * all callbacks delegate to dedicated classes in this namespace.
 */

namespace AyeCode\FontAwesome;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Loader
 */
class Loader {

	/**
	 * Register all WordPress hooks for this package.
	 *
	 * The constructor is the only place hooks are registered. Each hook
	 * delegates to a dedicated class method; no logic runs here directly.
	 */
	public function __construct() {
		// Load composer dependencies when running as a standalone plugin.
		if ( file_exists( AYECODE_FA_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
			require_once AYECODE_FA_PLUGIN_DIR . 'vendor/autoload.php';
		}

		// Load global helper functions — done here (not via composer files autoload) so that
		// only the winning negotiation version loads them, preventing redeclaration conflicts.
		require_once AYECODE_FA_PLUGIN_DIR . 'src/functions.php';

		$fa = Font_Awesome::instance();

		add_action( 'init', array( $fa, 'init' ), 4 );

		if ( is_admin() ) {
			add_action( 'admin_init',    array( $fa, 'constants' ) );
			add_action( 'admin_notices', array( $fa, 'admin_notices' ) );
		}

		add_action( 'rest_api_init', array( $fa, 'register_rest_api' ) );

		// BC alias so external code calling WP_Font_Awesome_Settings::instance() keeps working.
		// Placed here (not in the plugin header) so it works for composer dependency installs too.
		if ( ! class_exists( 'WP_Font_Awesome_Settings' ) ) {
			class_alias( 'AyeCode\\FontAwesome\\Font_Awesome', 'WP_Font_Awesome_Settings' );
		}

		do_action( 'wp_font_awesome_settings_loaded' );
	}
}
