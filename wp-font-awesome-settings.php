<?php
/**
 * A class for adjusting font awesome settings on WordPress
 *
 * This class can be added to any plugin or theme and will add a settings screen to WordPress to control Font Awesome settings.
 *
 * @link https://github.com/AyeCode/wp-font-awesome-settings
 *
 * @internal This file should not be edited directly but pulled from the github repo above.
 */

/**
 * Bail if we are not in WP.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Constants - Can be overridden in mu-plugins
 */
if ( ! defined( 'AYECODE_FA_DEFAULT_VERSION' ) ) {
	define( 'AYECODE_FA_DEFAULT_VERSION', '6.7.2' );
}
if ( ! defined( 'AYECODE_FA_CACHE_DIR_NAME' ) ) {
	define( 'AYECODE_FA_CACHE_DIR_NAME', 'ayecode-icon-cache' );
}
if ( ! defined( 'AYECODE_FA_LIBRARIES_DIR_NAME' ) ) {
	define( 'AYECODE_FA_LIBRARIES_DIR_NAME', 'icons-libraries' );
}
if ( ! defined( 'AYECODE_FA_CUSTOM_ICONS_DIR_NAME' ) ) {
	define( 'AYECODE_FA_CUSTOM_ICONS_DIR_NAME', 'custom' );
}
if ( ! defined( 'AYECODE_FA_CUSTOM_ICONS_JSON_FILENAME' ) ) {
	define( 'AYECODE_FA_CUSTOM_ICONS_JSON_FILENAME', 'custom-icons.json' );
}
if ( ! defined( 'AYECODE_FA_JSON_FILENAME_PATTERN' ) ) {
	define( 'AYECODE_FA_JSON_FILENAME_PATTERN', 'font-awesome-%s.min.json' );
}

/**
 * Load composer autoloader for dependencies.
 */
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

/**
 * Only add if the class does not already exist.
 */
if ( ! class_exists( 'WP_Font_Awesome_Settings' ) ) {

	/**
	 * A Class to be able to change settings for Font Awesome.
	 *
	 * Class WP_Font_Awesome_Settings
	 */
	class WP_Font_Awesome_Settings {

		/**
		 * Class version version.
		 *
		 * @var string
		 */
		public $version = '2.0.0';

		/**
		 * Class textdomain.
		 *
		 * @var string
		 */
		public $textdomain = 'font-awesome-settings';

		/**
		 * Latest version of Font Awesome at time of publish published.
		 *
		 * @var string
		 */
		public $latest = "6.4.2";

		/**
		 * The title.
		 *
		 * @var string
		 */
		public $name = 'Font Awesome';

		/**
		 * Holds the settings values.
		 *
		 * @var array
		 */
		public $settings;


	/**
	 * Settings Framework instance.
	 *
	 * @var WP_Font_Awesome_Settings_Framework
	 */
	private $settings_framework;
		/**
		 * WP_Font_Awesome_Settings instance.
		 *
		 * @access private
		 * @since  1.0.0
		 * @var    WP_Font_Awesome_Settings There can be only one!
		 */
		private static $instance = null;

		/**
		 * Main WP_Font_Awesome_Settings Instance.
		 *
		 * Ensures only one instance of WP_Font_Awesome_Settings is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return WP_Font_Awesome_Settings - Main instance.
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Font_Awesome_Settings ) ) {
				self::$instance = new WP_Font_Awesome_Settings;

				add_action( 'init', array( self::$instance, 'init' ) ); // set settings

				if ( is_admin() ) {
					add_action( 'admin_init', array( self::$instance, 'constants' ) );
					add_action( 'admin_notices', array( self::$instance, 'admin_notices' ) );
				}

				do_action( 'wp_font_awesome_settings_loaded' );
			}

			return self::$instance;
		}

		/**
         * Define any constants that may be needed by other packages.
         *
		 * @return void
		 */
		public function constants(){

			// register iconpicker constant
			if ( ! defined( 'FAS_ICONPICKER_JS_URL' ) ) {
				$url = $this->get_path_url();
				$version = $this->settings['version'];

				if( !$version || version_compare($version,'5.999','>')){
					$url .= 'assets/js/fa-iconpicker-v6.min.js';
				}else{
					$url .= 'assets/js/fa-iconpicker-v5.min.js';
				}

				define( 'FAS_ICONPICKER_JS_URL', $url );

			}

            // Set a constant if pro enabled
			if ( ! defined( 'FAS_PRO' ) && $this->settings['pro'] ) {
				define( 'FAS_PRO', true );
			}
		}

		/**
		 * Get the url path to the current folder.
		 *
		 * @return string
		 */
		public function get_path_url() {
			$content_dir = wp_normalize_path( untrailingslashit( WP_CONTENT_DIR ) );
			$content_url = untrailingslashit( WP_CONTENT_URL );

			// Replace http:// to https://.
			if ( strpos( $content_url, 'http://' ) === 0 && strpos( plugins_url(), 'https://' ) === 0 ) {
				$content_url = str_replace( 'http://', 'https://', $content_url );
			}

			// Check if we are inside a plugin
			$file_dir = str_replace( "/includes", "", wp_normalize_path( dirname( __FILE__ ) ) );
			$url = str_replace( $content_dir, $content_url, $file_dir );

			return trailingslashit( $url );
		}

		/**
		 * Initiate the settings and add the required action hooks.
		 *
		 * @since 1.0.8 Settings name wrong - FIXED
		 */
		public function init() {
			// Download fontawesome locally.
		// Load Settings Framework if in admin
		if ( is_admin() && ! $this->settings_framework ) {
			require_once dirname( __FILE__ ) . '/src/Settings.php';
			$this->settings_framework = new WP_Font_Awesome_Settings_Framework( $this );

			// Register AJAX handlers for SVG loader.
			add_action( 'wp_ajax_ayecode_fa_clear_cache', array( $this, 'ajax_clear_svg_cache' ) );
		}

		// Register custom icons library for iconpicker.
		add_filter( 'aui_iconpicker_libraries', array( $this, 'register_custom_icons_library' ) );

			add_action( 'add_option_wp-font-awesome-settings', array( $this, 'add_option_wp_font_awesome_settings' ), 10, 2 );
			add_action( 'update_option_wp-font-awesome-settings', array( $this, 'update_option_wp_font_awesome_settings' ), 10, 2 );

			$this->settings = $this->get_settings();

			// Check if the official plugin is active and use that instead if so.
			if ( ! defined( 'FONTAWESOME_PLUGIN_FILE' ) ) {
				// Always add generator in admin.
				if ( $this->settings['enqueue'] == '' || $this->settings['enqueue'] == 'backend' ) {
					add_action( 'admin_head', array( $this, 'add_generator' ), 99 );
				}

				// Frontend generator - skip if SVG mode.
				if ( $this->settings['type'] != 'SVG' && ( $this->settings['enqueue'] == '' || $this->settings['enqueue'] == 'frontend' ) ) {
					add_action( 'wp_head', array( $this, 'add_generator' ), 99 );
				}

				// Frontend loading - skip if SVG mode.
				if ( $this->settings['type'] != 'SVG' ) {
					if ( $this->settings['type'] == 'CSS' ) {
						if ( $this->settings['enqueue'] == '' || $this->settings['enqueue'] == 'frontend' ) {
							add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ), 5000 );
						}
					} elseif ( $this->settings['type'] != 'SVG' ) {
						// JS or KIT on frontend.
						if ( $this->settings['enqueue'] == '' || $this->settings['enqueue'] == 'frontend' ) {
							add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 5000 );
						}
					}
				}

				// Backend always loads (CSS or JS based on type).
			// Backend always loads (CSS or JS based on type).
			if ( $this->settings['enqueue'] == '' || $this->settings['enqueue'] == 'backend' ) {
				// Use CSS for backend when frontend is SVG (FREE) or CSS.
				// For SVG + PRO, use KIT if available.
				if ( ( $this->settings['type'] == 'CSS' ) || ( $this->settings['type'] == 'SVG' && empty( $this->settings['pro'] ) ) ) {
					add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_style' ), 5000 );
					add_action( 'enqueue_block_assets', array( $this, 'enqueue_style_admin_only' ), 5000 );
				} else {
					// JS, KIT, or SVG + PRO (uses KIT).
					add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 5000 );
					add_action( 'enqueue_block_assets', array( $this, 'enqueue_scripts_admin_only' ), 5000 );
				}
			}

				// Script loader tag filter (for JS/KIT only).
				if ( $this->settings['type'] == 'JS' || $this->settings['type'] == 'KIT' ) {
					add_filter( 'script_loader_tag', array( $this, 'script_loader_tag' ), 20, 3 );
				}

				// Remove font awesome if set to do so (not applicable to SVG mode).
				if ( $this->settings['type'] != 'SVG' && $this->settings['dequeue'] == '1' ) {
					add_action( 'clean_url', array( $this, 'remove_font_awesome' ), 5000, 3 );
				}
			}

		}

		/**
		 * Add FA to the FSE.
		 *
		 * @param $editor_settings
		 * @param $block_editor_context
		 *
		 * @return array
		 */
		public function enqueue_editor_styles( $editor_settings, $block_editor_context ){

			if ( ! empty( $editor_settings['__unstableResolvedAssets']['styles'] ) ) {
				$url = $this->get_url();
				$editor_settings['__unstableResolvedAssets']['styles'] .= "<link rel='stylesheet' id='font-awesome-css'  href='$url' media='all' />";
			}

			return $editor_settings;
		}

		/**
		 * Add FA to the FSE.
		 *
		 * @param $editor_settings
		 * @param $block_editor_context
		 *
		 * @return array
		 */
		public function enqueue_editor_scripts( $editor_settings, $block_editor_context ) {
			$url = $this->get_url();

			$editor_settings['__unstableResolvedAssets']['scripts'] .= "<script src='$url' id='font-awesome-js' defer crossorigin='anonymous'></script>";

			return $editor_settings;
		}

		/**
		 * Adds the Font Awesome styles.
		 */
		public function enqueue_style() {
			// build url
			$url = $this->get_url();
			$version = ! empty( $this->settings['local'] ) && empty( $this->settings['pro'] ) ? wp_strip_all_tags( $this->settings['local_version'] ) : null;

			wp_deregister_style( 'font-awesome' ); // deregister in case its already there
			wp_register_style( 'font-awesome', $url, array(), $version );
			wp_enqueue_style( 'font-awesome' );

			// RTL language support CSS.
			if ( is_rtl() ) {
				wp_add_inline_style( 'font-awesome', $this->rtl_inline_css() );
			}

			if ( $this->settings['shims'] ) {
				$url = $this->get_url( true );
				wp_deregister_style( 'font-awesome-shims' ); // deregister in case its already there
				wp_register_style( 'font-awesome-shims', $url, array(), $version );
				wp_enqueue_style( 'font-awesome-shims' );
			}
		}

		/**
		 * Wrapper for enqueue_style that only runs in admin (for block editor).
		 */
		public function enqueue_style_admin_only() {
			if ( is_admin() ) {
				$this->enqueue_style();
			}
		}

		/**
		 * Wrapper for enqueue_scripts that only runs in admin (for block editor).
		 */
		public function enqueue_scripts_admin_only() {
			if ( is_admin() ) {
				$this->enqueue_scripts();
			}
		}

		/**
		 * Adds the Font Awesome JS.
		 */
		public function enqueue_scripts() {
			// build url
			$url = $this->get_url();

			$deregister_function = 'wp' . '_' . 'deregister' . '_' . 'script';
			call_user_func( $deregister_function, 'font-awesome' ); // deregister in case its already there
			wp_register_script( 'font-awesome', $url, array(), null );
			wp_enqueue_script( 'font-awesome' );

			if ( $this->settings['shims'] ) {
				$url = $this->get_url( true );
				call_user_func( $deregister_function, 'font-awesome-shims' ); // deregister in case its already there
				wp_register_script( 'font-awesome-shims', $url, array(), null );
				wp_enqueue_script( 'font-awesome-shims' );
			}
		}

		/**
		 * Get the url of the Font Awesome files.
		 *
		 * @param bool $shims If this is a shim file or not.
		 * @param bool $local Load locally if allowed.
		 *
		 * @return string The url to the file.
		 */
		public function get_url( $shims = false, $local = true ) {
			$script  = $shims ? 'v4-shims' : 'all';
			$sub     = $this->settings['pro'] ? 'pro' : 'use';
			$type    = $this->settings['type'];
			$version = $this->settings['version'];
			$kit_url = $this->settings['kit-url'] ? sanitize_text_field( $this->settings['kit-url'] ) : '';
			$url     = '';

			// Use KIT if type is KIT, or if SVG + PRO (needs kit for backend icons).
			if ( ( $type == 'KIT' || ( $type == 'SVG' && ! empty( $this->settings['pro'] ) ) ) && $kit_url ) {
				if ( $shims ) {
					// if its a kit then we don't add shims here
					return '';
				}
				$url .= $kit_url; // CDN
				$url .= "?wpfas=true"; // set our var so our version is not removed
			} else {
				$v = '';
				// Check and load locally.
				if ( $local && $this->has_local() ) {
					$script .= ".min";
					$v .= '&ver=' . wp_strip_all_tags( $this->settings['local_version'] );
					$url .= $this->get_fonts_url(); // Local fonts url.
				} else {
					$url .= "https://$sub.fontawesome.com/releases/"; // CDN
					$url .= ! empty( $version ) ? "v" . $version . '/' : "v" . $this->get_latest_version() . '/'; // version
				}
				// SVG type uses CSS files (for admin compatibility)
				$use_css = ( $type == 'CSS' || $type == 'SVG' );
				$url .= $use_css ? 'css/' : 'js/'; // type
				$url .= $use_css ? $script . '.css' : $script . '.js'; // type
				$url .= "?wpfas=true" . $v; // set our var so our version is not removed
			}

			return $url;
		}

		/**
		 * Try and remove any other versions of Font Awesome added by other plugins/themes.
		 *
		 * Uses the clean_url filter to try and remove any other Font Awesome files added, it can also add pseudo-elements flag for the JS version.
		 *
		 * @param $url
		 * @param $original_url
		 * @param $_context
		 *
		 * @return string The filtered url.
		 */
		public function remove_font_awesome( $url, $original_url, $_context ) {

			if ( $_context == 'display'
			     && ( strstr( $url, "fontawesome" ) !== false || strstr( $url, "font-awesome" ) !== false )
			     && ( strstr( $url, ".js" ) !== false || strstr( $url, ".css" ) !== false )
			) {// it's a font-awesome-url (probably)

				if ( strstr( $url, "wpfas=true" ) !== false ) {
					if ( $this->settings['type'] == 'JS' ) {
						if ( $this->settings['js-pseudo'] ) {
							$url .= "' data-search-pseudo-elements defer='defer";
						} else {
							$url .= "' defer='defer";
						}
					}
				} else {
					$url = ''; // removing the url removes the file
				}

			}

			return $url;
		}


		/**
		 * Get the current Font Awesome output settings.
		 *
		 * @return array The array of settings.
		 */
		public function get_settings() {
			$db_settings = get_option( 'wp-font-awesome-settings' );

			$defaults = array(
				'type'      => 'CSS', // type to use, CSS or JS or KIT
				'version'   => '', // latest
				'enqueue'   => '', // front and backend
				'shims'     => '0', // default OFF now in 2020
				'js-pseudo' => '0', // if the pseudo elements flag should be set (CPU intensive)
				'dequeue'   => '0', // if we should try to remove other versions added by other plugins/themes
				'pro'       => '0', // if pro CDN url should be used
				'local'     => '0', // Store fonts locally.
				'local_version' => '', // Local fonts version.
				'kit-url'   => '', // the kit url
			);

			$settings = wp_parse_args( $db_settings, $defaults );

			// Normalize local_icon_styles to always be an array.
			if ( isset( $settings['local_icon_styles'] ) ) {
				if ( is_string( $settings['local_icon_styles'] ) && ! empty( $settings['local_icon_styles'] ) ) {
					$decoded = json_decode( $settings['local_icon_styles'], true );
					$settings['local_icon_styles'] = is_array( $decoded ) ? $decoded : array();
				} elseif ( ! is_array( $settings['local_icon_styles'] ) ) {
					$settings['local_icon_styles'] = array();
				}
			}

			/**
			 * Filter the Font Awesome settings.
			 *
			 * @todo if we add this filer people might use it and then it defeates the purpose of this class :/
			 */
			return $this->settings = apply_filters( 'wp-font-awesome-settings', $settings, $db_settings, $defaults );
		}


		/**
		 * Check a version number is valid and if so return it or else return an empty string.
		 *
		 * @param $version string The version number to check.
		 *
		 * @since 1.0.6
		 *
		 * @return string Either a valid version number or an empty string.
		 */
		public function validate_version_number( $version ) {

			if ( version_compare( $version, '0.0.1', '>=' ) >= 0 ) {
				// valid
			} else {
				$version = '';// not validated
			}

			return $version;
		}


		/**
		 * Get the latest version of Font Awesome.
		 *
		 * We check for a cached version and if none we will check for a live version via API and then cache it for 48 hours.
		 *
		 * @since 1.0.7
		 * @return mixed|string The latest version number found.
		 */
		public function get_latest_version( $force_api = false, $force_latest = false ) {
			$latest_version = $this->latest;

			$cache = get_transient( 'wp-font-awesome-settings-version' );

			if ( $cache === false || $force_api ) { // its not set
				$api_ver = $this->get_latest_version_from_api();
				if ( version_compare( $api_ver, $this->latest, '>=' ) >= 0 ) {
					$latest_version = $api_ver;
					set_transient( 'wp-font-awesome-settings-version', $api_ver, 48 * HOUR_IN_SECONDS );
				}
			} elseif ( $this->validate_version_number( $cache ) ) {
				if ( version_compare( $cache, $this->latest, '>=' ) >= 0 ) {
					$latest_version = $cache;
				}
			}

			// @todo remove after FA7 compatibility
			if ( ! $force_latest && version_compare( $cache, '7.0.0', '>=' ) >= 0 ) {
				$latest_version = '6.7.2';
			}

			// Check and auto download fonts locally.
			if ( empty( $this->settings['pro'] ) && empty( $this->settings['version'] ) && $this->settings['type'] != 'KIT' && ! empty( $this->settings['local'] ) && ! empty( $this->settings['local_version'] ) && ! empty( $latest_version ) ) {
				if ( version_compare( $latest_version, $this->settings['local_version'], '>' ) && is_admin() && ! wp_doing_ajax() ) {
					$this->download_package( $latest_version );
				}
			}

			return $latest_version;
		}

		/**
		 * Get the latest Font Awesome version from the github API.
		 *
		 * @since 1.0.7
		 * @return string The latest version number or `0` on API fail.
		 */
		public function get_latest_version_from_api() {
			$version  = "0";
			$response = wp_remote_get( "https://api.github.com/repos/FortAwesome/Font-Awesome/releases/latest" );
			if ( ! is_wp_error( $response ) && is_array( $response ) ) {
				$api_response = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $api_response['tag_name'] ) && version_compare( $api_response['tag_name'], $this->latest, '>=' ) >= 0 && empty( $api_response['prerelease'] ) ) {
					$version = $api_response['tag_name'];
				}
			}

			return $version;
		}

		/**
		 * Inline CSS for RTL language support.
		 *
		 * @since 1.0.13
		 * @return string Inline CSS.
		 */
		public function rtl_inline_css() {
			$inline_css = '[dir=rtl] .fa-address,[dir=rtl] .fa-address-card,[dir=rtl] .fa-adjust,[dir=rtl] .fa-alarm-clock,[dir=rtl] .fa-align-left,[dir=rtl] .fa-align-right,[dir=rtl] .fa-analytics,[dir=rtl] .fa-angle-double-left,[dir=rtl] .fa-angle-double-right,[dir=rtl] .fa-angle-left,[dir=rtl] .fa-angle-right,[dir=rtl] .fa-arrow-alt-circle-left,[dir=rtl] .fa-arrow-alt-circle-right,[dir=rtl] .fa-arrow-alt-from-left,[dir=rtl] .fa-arrow-alt-from-right,[dir=rtl] .fa-arrow-alt-left,[dir=rtl] .fa-arrow-alt-right,[dir=rtl] .fa-arrow-alt-square-left,[dir=rtl] .fa-arrow-alt-square-right,[dir=rtl] .fa-arrow-alt-to-left,[dir=rtl] .fa-arrow-alt-to-right,[dir=rtl] .fa-arrow-circle-left,[dir=rtl] .fa-arrow-circle-right,[dir=rtl] .fa-arrow-from-left,[dir=rtl] .fa-arrow-from-right,[dir=rtl] .fa-arrow-left,[dir=rtl] .fa-arrow-right,[dir=rtl] .fa-arrow-square-left,[dir=rtl] .fa-arrow-square-right,[dir=rtl] .fa-arrow-to-left,[dir=rtl] .fa-arrow-to-right,[dir=rtl] .fa-balance-scale-left,[dir=rtl] .fa-balance-scale-right,[dir=rtl] .fa-bed,[dir=rtl] .fa-bed-bunk,[dir=rtl] .fa-bed-empty,[dir=rtl] .fa-border-left,[dir=rtl] .fa-border-right,[dir=rtl] .fa-calendar-check,[dir=rtl] .fa-caret-circle-left,[dir=rtl] .fa-caret-circle-right,[dir=rtl] .fa-caret-left,[dir=rtl] .fa-caret-right,[dir=rtl] .fa-caret-square-left,[dir=rtl] .fa-caret-square-right,[dir=rtl] .fa-cart-arrow-down,[dir=rtl] .fa-cart-plus,[dir=rtl] .fa-chart-area,[dir=rtl] .fa-chart-bar,[dir=rtl] .fa-chart-line,[dir=rtl] .fa-chart-line-down,[dir=rtl] .fa-chart-network,[dir=rtl] .fa-chart-pie,[dir=rtl] .fa-chart-pie-alt,[dir=rtl] .fa-chart-scatter,[dir=rtl] .fa-check-circle,[dir=rtl] .fa-check-square,[dir=rtl] .fa-chevron-circle-left,[dir=rtl] .fa-chevron-circle-right,[dir=rtl] .fa-chevron-double-left,[dir=rtl] .fa-chevron-double-right,[dir=rtl] .fa-chevron-left,[dir=rtl] .fa-chevron-right,[dir=rtl] .fa-chevron-square-left,[dir=rtl] .fa-chevron-square-right,[dir=rtl] .fa-clock,[dir=rtl] .fa-file,[dir=rtl] .fa-file-alt,[dir=rtl] .fa-file-archive,[dir=rtl] .fa-file-audio,[dir=rtl] .fa-file-chart-line,[dir=rtl] .fa-file-chart-pie,[dir=rtl] .fa-file-code,[dir=rtl] .fa-file-excel,[dir=rtl] .fa-file-image,[dir=rtl] .fa-file-pdf,[dir=rtl] .fa-file-powerpoint,[dir=rtl] .fa-file-video,[dir=rtl] .fa-file-word,[dir=rtl] .fa-flag,[dir=rtl] .fa-folder,[dir=rtl] .fa-folder-open,[dir=rtl] .fa-hand-lizard,[dir=rtl] .fa-hand-point-down,[dir=rtl] .fa-hand-point-left,[dir=rtl] .fa-hand-point-right,[dir=rtl] .fa-hand-point-up,[dir=rtl] .fa-hand-scissors,[dir=rtl] .fa-image,[dir=rtl] .fa-long-arrow-alt-left,[dir=rtl] .fa-long-arrow-alt-right,[dir=rtl] .fa-long-arrow-left,[dir=rtl] .fa-long-arrow-right,[dir=rtl] .fa-luggage-cart,[dir=rtl] .fa-moon,[dir=rtl] .fa-pencil,[dir=rtl] .fa-pencil-alt,[dir=rtl] .fa-play-circle,[dir=rtl] .fa-project-diagram,[dir=rtl] .fa-quote-left,[dir=rtl] .fa-quote-right,[dir=rtl] .fa-shopping-cart,[dir=rtl] .fa-thumbs-down,[dir=rtl] .fa-thumbs-up,[dir=rtl] .fa-user-chart{filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=0, mirror=1);transform:scale(-1,1)}[dir=rtl] .fa-spin{animation-direction:reverse}';

			return $inline_css;
		}

		/**
		 * Show any warnings as an admin notice.
		 *
		 * @return void
		 */
		public function admin_notices() {
			$settings = $this->settings;

			// Check for icon generation success/error messages.
			$icon_gen_error = get_transient( 'fa_icon_gen_error' );
			if ( $icon_gen_error ) {
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( sprintf( __( 'Font Awesome Icon Library Generation Error: %s', 'font-awesome-settings' ), $icon_gen_error ) ); ?></p>
				</div>
				<?php
				delete_transient( 'fa_icon_gen_error' );
			}

			$icon_gen_success = get_transient( 'fa_icon_gen_success' );
			if ( $icon_gen_success ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $icon_gen_success ); ?></p>
				</div>
				<?php
				delete_transient( 'fa_icon_gen_success' );
			}

			if ( defined( 'FONTAWESOME_PLUGIN_FILE' ) ) {
				if ( ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'wp-font-awesome-settings' ) {
					?>
                    <div class="notice  notice-error is-dismissible">
                        <p><?php _e( 'The Official Font Awesome Plugin is active, please adjust your settings there.', 'ayecode-connect' ); ?></p>
                    </div>
					<?php
				}
			} else {
                /*
				if ( ! empty( $settings ) ) {
					if ( $settings['type'] != 'KIT' && $settings['pro'] && ( $settings['version'] == '' || version_compare( $settings['version'], '6', '>=' ) ) ) {
						$link = admin_url('options-general.php?page=wp-font-awesome-settings');
						?>
                        <div class="notice  notice-error is-dismissible">
                            <p><?php echo wp_sprintf( __( 'Font Awesome Pro v6 requires the use of a kit, please setup your kit in %ssettings.%s', 'ayecode-connect' ),"<a href='". esc_url_raw( $link )."'>","</a>" ); ?></p>
                        </div>
						<?php
					}
				}*/
			}
		}

		/**
		 * Handle fontawesome add settings to download fontawesome to store locally.
		 *
		 * @since 1.1.1
		 *
		 * @param string $option The option name.
		 * @param mixed  $value  The option value.
		 */
		public function add_option_wp_font_awesome_settings( $option, $value ) {
			// Do nothing if WordPress is being installed.
			if ( wp_installing() ) {
				return;
			}

			if ( ! empty( $value['local'] ) && empty( $value['pro'] ) && ! ( ! empty( $value['type'] ) && $value['type'] == 'KIT' ) ) {
				$version = isset( $value['version'] ) && $value['version'] ? $value['version'] : $this->get_latest_version();

				if ( ! empty( $version ) ) {
					$response = $this->download_package( $version, $value );

					if ( is_wp_error( $response ) ) {
						add_settings_error( 'general', 'fontawesome_download', __( 'ERROR:', 'ayecode-connect' ) . ' ' . $response->get_error_message(), 'error' );
					}
				}
			}
		}

		/**
		 * Handle fontawesome update settings to download fontawesome to store locally.
		 *
		 * @since 1.1.0
		 *
		 * @param mixed $old_value The old option value.
		 * @param mixed $value     The new option value.
		 */
		public function update_option_wp_font_awesome_settings( $old_value, $new_value ) {
			// Do nothing if WordPress is being installed.
			if ( wp_installing() ) {
				return;
			}

			if ( ! empty( $new_value['local'] ) && empty( $new_value['pro'] ) && ! ( ! empty( $new_value['type'] ) && $new_value['type'] == 'KIT' ) ) {
				// Old values
				$old_version = isset( $old_value['version'] ) && $old_value['version'] ? $old_value['version'] : ( isset( $old_value['local_version'] ) ? $old_value['local_version'] : '' );
				$old_local = isset( $old_value['local'] ) ? (int) $old_value['local'] : 0;

				// New values
				$new_version = isset( $new_value['version'] ) && $new_value['version'] ? $new_value['version'] : $this->get_latest_version();

				if ( empty( $old_local ) || $old_version !== $new_version || ! file_exists( $this->get_fonts_dir() . 'css' . DIRECTORY_SEPARATOR . 'all.css' ) ) {
					$response = $this->download_package( $new_version, $new_value );

					if ( is_wp_error( $response ) ) {
						add_settings_error( 'general', 'fontawesome_download', __( 'ERROR:', 'ayecode-connect' ) . ' ' . $response->get_error_message(), 'error' );
					}
				}
			}
		}

		/**
		 * Get the fonts directory local path.
		 *
		 * @since 1.1.0
		 *
		 * @param string Fonts directory local path.
		 */
		public function get_fonts_dir() {
			$upload_dir = wp_upload_dir( null, false );

			return $upload_dir['basedir'] . DIRECTORY_SEPARATOR .  'ayefonts' . DIRECTORY_SEPARATOR . 'fa' . DIRECTORY_SEPARATOR;
		}

		/**
		 * Get the fonts directory local url.
		 *
		 * @since 1.1.0
		 *
		 * @param string Fonts directory local url.
		 */
		public function get_fonts_url() {
			$upload_dir = wp_upload_dir( null, false );

			return $upload_dir['baseurl'] .  '/ayefonts/fa/';
		}

		/**
		 * Check whether load locally active.
		 *
		 * @since 1.1.0
		 *
		 * @return bool True if active else false.
		 */
		public function has_local() {
			if ( ! empty( $this->settings['local'] ) && empty( $this->settings['pro'] ) && file_exists( $this->get_fonts_dir() . 'css' . DIRECTORY_SEPARATOR . 'all.css' ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get the WP Filesystem access.
		 *
		 * @since 1.1.0
		 *
		 * @return object The WP Filesystem.
		 */
		public function get_wp_filesystem() {
			if ( ! function_exists( 'get_filesystem_method' ) ) {
				require_once( ABSPATH . "/wp-admin/includes/file.php" );
			}

			$access_type = get_filesystem_method();

			if ( $access_type === 'direct' ) {
				/* You can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL */
				$creds = request_filesystem_credentials( trailingslashit( site_url() ) . 'wp-admin/', '', false, false, array() );

				/* Initialize the API */
				if ( ! WP_Filesystem( $creds ) ) {
					/* Any problems and we exit */
					return false;
				}

				global $wp_filesystem;

				return $wp_filesystem;
				/* Do our file manipulations below */
			} else if ( defined( 'FTP_USER' ) ) {
				$creds = request_filesystem_credentials( trailingslashit( site_url() ) . 'wp-admin/', '', false, false, array() );

				/* Initialize the API */
				if ( ! WP_Filesystem( $creds ) ) {
					/* Any problems and we exit */
					return false;
				}

				global $wp_filesystem;

				return $wp_filesystem;
			} else {
				/* Don't have direct write access. Prompt user with our notice */
				return false;
			}
		}

		/**
		 * Download the fontawesome package file.
		 *
		 * @since 1.1.0
		 *
		 * @param mixed $version The font awesome.
		 * @param array $option Fontawesome settings.
		 * @return WP_ERROR|bool Error on fail and true on success.
		 */
		public function download_package( $version, $option = array() ) {
			$filename = 'fontawesome-free-' . $version . '-web';
			$url = 'https://use.fontawesome.com/releases/v' . $version . '/' . $filename . '.zip';

			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$download_file = download_url( esc_url_raw( $url ) );

			if ( is_wp_error( $download_file ) ) {
				return new WP_Error( 'fontawesome_download_failed', __( $download_file->get_error_message(), 'ayecode-connect' ) );
			} else if ( empty( $download_file ) ) {
				return new WP_Error( 'fontawesome_download_failed', __( 'Something went wrong in downloading the font awesome to store locally.', 'ayecode-connect' ) );
			}

			$response = $this->extract_package( $download_file, $filename, true );

			// Update local version.
			if ( is_wp_error( $response ) ) {
				return $response;
			} else if ( $response ) {
				if ( empty( $option ) ) {
					$option = get_option( 'wp-font-awesome-settings' );
				}

				$option['local_version'] = $version;

				// Remove action to prevent looping.
				remove_action( 'update_option_wp-font-awesome-settings', array( $this, 'update_option_wp_font_awesome_settings' ), 10, 2 );

				update_option( 'wp-font-awesome-settings', $option );

				return true;
			}

			return false;
		}

		/**
		 * Extract the fontawesome package file.
		 *
		 * @since 1.1.0
		 *
		 * @param string $package The package file path.
		 * @param string $dirname Package file name.
		 * @param bool   $delete_package Delete temp file or not.
		 * @return WP_Error|bool True on success WP_Error on fail.
		 */
		public function extract_package( $package, $dirname = '', $delete_package = false ) {
			global $wp_filesystem;

			$wp_filesystem = $this->get_wp_filesystem();

			if ( empty( $wp_filesystem ) && isset( $wp_filesystem->errors ) && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				return new WP_Error( 'fontawesome_filesystem_error', __( $wp_filesystem->errors->get_error_message(), 'ayecode-connect' ) );
			} else if ( empty( $wp_filesystem ) ) {
				return new WP_Error( 'fontawesome_filesystem_error', __( 'Failed to initialise WP_Filesystem while trying to download the Font Awesome package.', 'ayecode-connect' ) );
			}

			$fonts_dir = $this->get_fonts_dir();
			$fonts_tmp_dir = dirname( $fonts_dir ) . DIRECTORY_SEPARATOR . 'fa-tmp' . DIRECTORY_SEPARATOR;

			if ( $wp_filesystem->is_dir( $fonts_tmp_dir ) ) {
				$wp_filesystem->delete( $fonts_tmp_dir, true );
			}

			// Unzip package to working directory.
			$result = unzip_file( $package, $fonts_tmp_dir );

			if ( is_wp_error( $result ) ) {
				$wp_filesystem->delete( $fonts_tmp_dir, true );

				if ( 'incompatible_archive' === $result->get_error_code() ) {
					return new WP_Error( 'fontawesome_incompatible_archive', __( $result->get_error_message(), 'ayecode-connect' ) );
				}

				return $result;
			}

			if ( $wp_filesystem->is_dir( $fonts_dir ) ) {
				$wp_filesystem->delete( $fonts_dir, true );
			}

			$extract_dir = $fonts_tmp_dir;

			if ( $dirname && $wp_filesystem->is_dir( $extract_dir . $dirname . DIRECTORY_SEPARATOR ) ) {
				$extract_dir .= $dirname . DIRECTORY_SEPARATOR;
			}

			try {
				$return = $wp_filesystem->move( $extract_dir, $fonts_dir, true );
			} catch ( Exception $e ) {
				$return = new WP_Error( 'fontawesome_move_package', __( 'Fail to move font awesome package!', 'ayecode-connect' ) );
			}

			if ( $wp_filesystem->is_dir( $fonts_tmp_dir ) ) {
				$wp_filesystem->delete( $fonts_tmp_dir, true );
			}

			// Once extracted, delete the package if required.
			if ( $delete_package ) {
				unlink( $package );
			}

			return $return;
		}

		/**
		 * AJAX handler to clear SVG icon cache.
		 *
		 * @since 2.0.0
		 */
		public function ajax_clear_svg_cache() {
			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ayecode_fa_clear_cache' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'font-awesome-settings' ) ) );
			}

			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'font-awesome-settings' ) ) );
			}

			// Clear the cache.
			$svg_loader = \AyeCode\FontAwesome\SVG_Loader::instance();
			$result     = $svg_loader->clear_icon_cache();

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 'message' => __( 'Icon cache cleared successfully.', 'font-awesome-settings' ) ) );
		}

		/**
		 * AJAX handler to upload custom SVG icons.
		 *
		 * @since 2.0.0
		 */
		public function ajax_upload_custom_svg() {
			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ayecode_fa_upload_svg' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'font-awesome-settings' ) ) );
			}

			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'font-awesome-settings' ) ) );
			}

			// Check if files were uploaded.
			if ( empty( $_FILES['svg_files'] ) ) {
				wp_send_json_error( array( 'message' => __( 'No files uploaded.', 'font-awesome-settings' ) ) );
			}

			$svg_loader = \AyeCode\FontAwesome\SVG_Loader::instance();
			$custom_dir = $svg_loader->get_icon_cache_dir() . 'custom' . DIRECTORY_SEPARATOR;

			// Ensure custom directory exists.
			if ( ! file_exists( $custom_dir ) ) {
				wp_mkdir_p( $custom_dir );
			}

			$files          = $_FILES['svg_files'];
			$uploaded_count = 0;
			$errors         = array();

			// Handle multiple files.
			if ( is_array( $files['name'] ) ) {
				foreach ( $files['name'] as $key => $filename ) {
					// Validate file extension.
					$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
					if ( 'svg' !== $ext ) {
						$errors[] = sprintf( __( '%s is not an SVG file.', 'font-awesome-settings' ), $filename );
						continue;
					}

					// Sanitize filename.
					$filename = sanitize_file_name( $filename );
					$filepath = $custom_dir . $filename;

					// Read file content.
					$svg_content = file_get_contents( $files['tmp_name'][ $key ] );

					// Basic SVG validation - check if it contains <svg> tag.
					if ( false === strpos( $svg_content, '<svg' ) ) {
						$errors[] = sprintf( __( '%s does not appear to be a valid SVG file.', 'font-awesome-settings' ), $filename );
						continue;
					}

					// Sanitize SVG content using our sanitization method.
					$svg_content = $this->sanitize_uploaded_svg( $svg_content );

					// Save file.
					$result = file_put_contents( $filepath, $svg_content );

					if ( false === $result ) {
						$errors[] = sprintf( __( 'Failed to save %s.', 'font-awesome-settings' ), $filename );
						continue;
					}

					$uploaded_count++;
				}
			}

			if ( $uploaded_count > 0 ) {
				$message = sprintf(
					_n( '%d icon uploaded successfully.', '%d icons uploaded successfully.', $uploaded_count, 'font-awesome-settings' ),
					$uploaded_count
				);

				if ( ! empty( $errors ) ) {
					$message .= ' ' . __( 'Some files had errors:', 'font-awesome-settings' ) . ' ' . implode( ' ', $errors );
				}

				wp_send_json_success( array( 'message' => $message ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Upload failed: ', 'font-awesome-settings' ) . implode( ' ', $errors ) ) );
			}
		}

		/**
		 * AJAX handler to delete custom SVG icon.
		 *
		 * @since 2.0.0
		 */
		public function ajax_delete_custom_svg() {
			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ayecode_fa_delete_svg' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'font-awesome-settings' ) ) );
			}

			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'font-awesome-settings' ) ) );
			}

			// Get icon name.
			$name = isset( $_POST['name'] ) ? sanitize_file_name( $_POST['name'] ) : '';
			if ( empty( $name ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid icon name.', 'font-awesome-settings' ) ) );
			}

			$svg_loader = \AyeCode\FontAwesome\SVG_Loader::instance();
			$filepath   = $svg_loader->get_icon_cache_dir() . 'custom' . DIRECTORY_SEPARATOR . $name . '.svg';

			// Check if file exists.
			if ( ! file_exists( $filepath ) ) {
				wp_send_json_error( array( 'message' => __( 'Icon file not found.', 'font-awesome-settings' ) ) );
			}

			// Delete the file.
			$result = unlink( $filepath );

			if ( ! $result ) {
				wp_send_json_error( array( 'message' => __( 'Failed to delete icon.', 'font-awesome-settings' ) ) );
			}

			// Clear object cache for this icon.
			wp_cache_delete( 'ayecode_icon_custom_' . $name, 'ayecode_icons' );

			wp_send_json_success( array( 'message' => __( 'Icon deleted successfully.', 'font-awesome-settings' ) ) );
		}

		/**
		 * Sanitize uploaded SVG content.
		 *
		 * Applies same sanitization as SVG_Loader but without ID normalization.
		 *
		 * @param string $svg SVG content.
		 *
		 * @return string Sanitized SVG.
		 * @since 2.0.0
		 */
		private function sanitize_uploaded_svg( string $svg ): string {
		// Use enshrined/svg-sanitize library for secure sanitization
		$sanitizer = new \enshrined\svgSanitize\Sanitizer();
		$sanitizer->removeRemoteReferences( true );

		$sanitized_svg = $sanitizer->sanitize( $svg );

		// Return empty string if sanitization fails (fallback)
		if ( false === $sanitized_svg || empty( $sanitized_svg ) ) {
			return '';
		}

		return $sanitized_svg;
		}

		/**
		 * Output the version in the header.
		 */
		public function add_generator() {
			$file = str_replace( array( "/", "\\" ), "/", realpath( __FILE__ ) );
			$plugins_dir = str_replace( array( "/", "\\" ), "/", realpath( WP_PLUGIN_DIR ) );

			// Find source plugin/theme.
			$source = array();
			if ( strpos( $file, $plugins_dir ) !== false ) {
				$source = explode( "/", plugin_basename( $file ) );
			} else if ( function_exists( 'get_theme_root' ) ) {
				$themes_dir = str_replace( array( "/", "\\" ), "/", realpath( get_theme_root() ) );

				if ( strpos( $file, $themes_dir ) !== false ) {
					$source = explode( "/", ltrim( str_replace( $themes_dir, "", $file ), "/" ) );
				}
			}

			echo '<meta name="generator" content="WP Font Awesome Settings v' . esc_attr( $this->version ) . '"' . ( ! empty( $source[0] ) ? ' data-ac-source="' . esc_attr( $source[0] ) . '"' : '' ) . ' />';
		}

		/**
		 * Add extra parameters to the script tag.
		 *
		 * Add crossorigin="anonymous" to prevent OpaqueResponseBlocking
		 * (NS_BINDING_ABORTED) http error.
		 *
		 * @since 1.1.8
		 *
		 * @param string $tag The script tag.
		 * @param string $handle The script handle.
		 * @param string $src The script url.
		 * @return string The script tag.
		 */
		public function script_loader_tag( $tag, $handle, $src ) {
			if ( ( $handle == 'font-awesome' || $handle == 'font-awesome-shims' ) && ( strpos( $src, "kit.fontawesome.com" ) !== false || strpos( $src, ".fontawesome.com/releases/" ) !== false ) ) {
				$tag = preg_replace(
					'/<script[\s]+(.*?)>/',
					'<script defer crossorigin="anonymous" \1>',
					$tag
				);
			}

			return $tag;
		}

		/**
		 * Register custom icons library for AUI iconpicker.
		 *
		 * Adds custom-icons.json to the iconpicker libraries array if custom icons exist.
		 *
		 * @param array $libraries Array of icon library URLs.
		 * @return array Modified libraries array.
		 */
	public function register_custom_icons_library( $libraries ) {
		$upload_dir = wp_upload_dir( null, false );

		// Replace Font Awesome libraries with local versions if available.
		// local_icon_styles is now normalized as an array in get_settings().
		$local_icon_version = isset( $this->settings['local_icon_version'] ) ? $this->settings['local_icon_version'] : '';
		$local_icon_styles  = isset( $this->settings['local_icon_styles'] ) ? $this->settings['local_icon_styles'] : array();
		if ( ! empty( $local_icon_version ) && ! empty( $local_icon_styles ) ) {
			// If PRO is active, replace ALL Font Awesome files with our local ones.
			if ( ! empty( absint( $this->settings['pro'] ) ) ) {
				// Remove all Font Awesome libraries.
				foreach ( $libraries as $key => $library_url ) {
					if ( preg_match( '/font-awesome-[a-z\-]+\.min\.json$/', $library_url ) ) {
						unset( $libraries[ $key ] );
					}
				}
				// Reindex array to prevent it from being converted to an object.
				$libraries = array_values( $libraries );


				// Add our local PRO files (brands and pro).
				$cache_url = $upload_dir['baseurl'] . '/' . AYECODE_FA_CACHE_DIR_NAME . '/' . AYECODE_FA_LIBRARIES_DIR_NAME . '/';
				$libraries[] = $cache_url . sprintf( AYECODE_FA_JSON_FILENAME_PATTERN, 'brands' );
				$libraries[] = $cache_url . sprintf( AYECODE_FA_JSON_FILENAME_PATTERN, 'pro' );
			} else {
				// FREE: Track which styles exist in the incoming libraries.
				$existing_styles = array();

				// Loop through libraries and replace matching Font Awesome files.
				foreach ( $libraries as $key => $library_url ) {
					// Check if this is a Font Awesome library URL.
					if ( preg_match( '/font-awesome-(solid|regular|brands)\.min\.json$/', $library_url, $matches ) ) {
						$style = $matches[1];
						$existing_styles[] = $style;

						// If we have this style generated locally, replace with local URL.
						if ( in_array( $style, $local_icon_styles, true ) ) {
							$cache_url = $upload_dir['baseurl'] . '/' . AYECODE_FA_CACHE_DIR_NAME . '/' . AYECODE_FA_LIBRARIES_DIR_NAME . '/';
							$libraries[ $key ] = $cache_url . sprintf( AYECODE_FA_JSON_FILENAME_PATTERN, $style );
						}
					}
				}

				// Add any local styles that don't exist in the incoming libraries.
				$cache_url = $upload_dir['baseurl'] . '/' . AYECODE_FA_CACHE_DIR_NAME . '/' . AYECODE_FA_LIBRARIES_DIR_NAME . '/';
				foreach ( $local_icon_styles as $style ) {
					if ( ! in_array( $style, $existing_styles, true ) ) {
						$libraries[] = $cache_url . sprintf( AYECODE_FA_JSON_FILENAME_PATTERN, $style );
					}
				}
			}
		}

		// Add custom icons if they exist.
		if ( ayecode_get_custom_icon_count() > 0 ) {
			$cache_url = $upload_dir['baseurl'] . '/' . AYECODE_FA_CACHE_DIR_NAME . '/' . AYECODE_FA_LIBRARIES_DIR_NAME . '/';
			$libraries[] = $cache_url . AYECODE_FA_CUSTOM_ICONS_JSON_FILENAME;
		}

//            print_r($local_icon_styles);
//            print_r($libraries);exit;

		return $libraries;
	}
	}

	/**
	 * Run the class if found.
	 */
	WP_Font_Awesome_Settings::instance();
}

/**
 * Class aliases for backward compatibility.
 * External code may still reference the old prefixed names.
 */
if ( ! class_exists( 'AyeCode_FA_SVG_Loader' ) ) {
	class_alias( 'AyeCode\FontAwesome\SVG_Loader', 'AyeCode_FA_SVG_Loader' );
}
if ( ! class_exists( 'AyeCode_FA_Custom_Icons' ) ) {
	class_alias( 'AyeCode\FontAwesome\Custom_Icons', 'AyeCode_FA_Custom_Icons' );
}
if ( ! class_exists( 'AyeCode_FA_Icon_Library_Generator' ) ) {
	class_alias( 'AyeCode\FontAwesome\Icon_Library_Generator', 'AyeCode_FA_Icon_Library_Generator' );
}
