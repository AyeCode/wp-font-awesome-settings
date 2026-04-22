<?php
/**
 * AyeCode Package Loader (v1.0.0)
 *
 * Handles version negotiation, PSR-4 autoloading, and bootstrapping for AyeCode packages.
 * Shared across all copies of the package (standalone plugin install + composer dependency).
 *
 * Do NOT edit below the configuration block.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// AyeCode Package Loader (v1.0.0)
( function () {
	// -------------------------------------------------------------------------
	// CONFIGURATION — update these values for each new package.
	// -------------------------------------------------------------------------

	// A unique key used to register this package in the global version registry.
	// Convention: ayecode_{package_slug}_registry  (use underscores, lowercase)
	$registry_key = 'ayecode_font_awesome_registry';

	// Must match the Version header in the main plugin file. This drives negotiation.
	$this_version = '3.0.2-beta';

	$this_path = dirname( __FILE__ );

	// The root PSR-4 namespace for this package's classes (include trailing backslash).
	$prefix = 'AyeCode\\FontAwesome\\';

	// Fully-qualified class name of the package bootstrapper.
	// Set to '' to disable auto-instantiation (e.g. for library-only packages).
	$loader_class = 'AyeCode\\FontAwesome\\Loader';

	// Hook and priority at which the Loader class is instantiated.
	$loader_hook     = 'plugins_loaded';
	$loader_priority = 10;

	// Constants to define ONLY if this package version wins the negotiation.
	// Leave array empty if your package requires no path/version constants.
	$winning_constants = [
		'AYECODE_FA_VERSION'                    => $this_version,
		'AYECODE_FA_PLUGIN_DIR'                 => $this_path . '/',
		'AYECODE_FA_PLUGIN_FILE'                => $this_path . '/wp-font-awesome-settings.php',
		'AYECODE_FA_DEFAULT_VERSION'            => '7.2.0',
		'AYECODE_FA_CACHE_DIR_NAME'             => 'ayecode-icon-cache',
		'AYECODE_FA_LIBRARIES_DIR_NAME'         => 'icons-libraries',
		'AYECODE_FA_CUSTOM_ICONS_DIR_NAME'      => 'custom',
		'AYECODE_FA_CUSTOM_ICONS_JSON_FILENAME' => 'custom-icons.json',
		'AYECODE_FA_JSON_FILENAME_PATTERN'      => 'font-awesome-%s.min.json',
		'AYECODE_FA_JSON_SCHEMA_VERSION'        => '2.0',
	];

	// -------------------------------------------------------------------------
	// DO NOT EDIT BELOW THIS LINE. CORE PACKAGE NEGOTIATION LOGIC.
	// -------------------------------------------------------------------------

	/**
	 * Step 1: Version Negotiation (Priority 1)
	 *
	 * Every installed copy of this package registers itself. The highest version
	 * wins and its path is stored as the canonical source for Steps 2 and 3.
	 */
	add_action( 'plugins_loaded', function () use ( $registry_key, $this_version, $this_path ) {
		if ( empty( $GLOBALS[ $registry_key ] ) || version_compare( $this_version, $GLOBALS[ $registry_key ]['version'], '>' ) ) {
			$GLOBALS[ $registry_key ] = [
				'version' => $this_version,
				'path'    => $this_path,
			];
		}
	}, 1 );

	/**
	 * Step 2: Lazy Loading Registration (Priority 2)
	 *
	 * Only the winning version registers an SPL autoloader. Losing copies bail
	 * out early, preventing duplicate class definitions.
	 */
	add_action( 'plugins_loaded', function () use ( $registry_key, $this_path, $prefix ) {
		if ( empty( $GLOBALS[ $registry_key ] ) || $GLOBALS[ $registry_key ]['path'] !== $this_path ) {
			return;
		}

		$base_dir = $this_path . '/src/';

		spl_autoload_register( function ( $class ) use ( $prefix, $base_dir ) {
			if ( strpos( $class, $prefix ) !== 0 ) {
				return;
			}

			$relative_class = substr( $class, strlen( $prefix ) );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require $file;
			}
		}, true, true );

	}, 2 );

	/**
	 * Step 3: Package Initialization (Configurable Hook/Priority)
	 *
	 * Defines constants and boots the Loader class, but only for the winning
	 * version. All other copies have already bailed in Step 2.
	 */
	if ( ! empty( $loader_class ) ) {
		add_action( $loader_hook, function () use ( $registry_key, $this_path, $loader_class, $winning_constants ) {
			if ( empty( $GLOBALS[ $registry_key ] ) || $GLOBALS[ $registry_key ]['path'] !== $this_path ) {
				return;
			}

			foreach ( $winning_constants as $name => $value ) {
				if ( ! defined( $name ) ) {
					define( $name, $value );
				}
			}

			// class_exists() triggers the autoloader registered in Step 2.
			if ( class_exists( $loader_class ) ) {
				new $loader_class();
			}
		}, $loader_priority );
	}

} )();
