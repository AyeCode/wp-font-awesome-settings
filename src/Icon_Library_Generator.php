<?php
declare(strict_types=1);

/**
 * Font Awesome Icon Library Generator
 *
 * Handles fetching and generating icon library JSON files from Font Awesome sources.
 *
 * @package WP_Font_Awesome_Settings
 */

namespace AyeCode\FontAwesome;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icon Library Generator class.
 */
class Icon_Library_Generator {

	/**
	 * Font Awesome API base URL.
	 */
	const API_URL = 'https://api.fontawesome.com/';

	/**
	 * GitHub raw content base URL for Font Awesome.
	 */
	const GITHUB_RAW_URL = 'https://github.com/FortAwesome/Font-Awesome/raw/';

	/**
	 * Transient key for storing auth token.
	 */
	const AUTH_TOKEN_TRANSIENT = 'fa_pro_auth_token';

	/**
	 * Auth token cache duration (50 minutes).
	 */
	const AUTH_TOKEN_DURATION = 50 * MINUTE_IN_SECONDS;

	/**
	 * Instance of this class.
	 *
	 * @var Icon_Library_Generator
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Icon_Library_Generator
	 */
	public static function instance(): Icon_Library_Generator {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Private constructor for singleton.
	}

	/**
	 * Generate icon library files based on settings.
	 *
	 * @param array $settings Plugin settings.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function generate_icon_libraries( $settings ) {
		$version = ! empty( $settings['version'] ) ? $settings['version'] : AYECODE_FA_DEFAULT_VERSION;
		$is_pro  = ! empty( $settings['pro'] );
		$api_key = ! empty( $settings['api_key'] ) ? $settings['api_key'] : '';

		// Fetch icon data.
		if ( $is_pro && ! empty( $api_key ) ) {
			$icons_data = $this->fetch_pro_icons( $version, $api_key );
		} else {
			$icons_data = $this->fetch_free_icons( $version );
		}

		if ( \is_wp_error( $icons_data ) ) {
			return $icons_data;
		}

		// Generate JSON files.
		return $this->generate_json_files( $icons_data, $version, $is_pro );
	}

	/**
	 * Fetch icons from GitHub for FREE version.
	 *
	 * @param string $version Font Awesome version.
	 * @return array|WP_Error Array of icon data or WP_Error on failure.
	 */
	public function fetch_free_icons( $version ) {
		// Determine branch/tag for GitHub.
		$github_version = $this->get_github_version( $version );
		$url            = self::GITHUB_RAW_URL . $github_version . '/metadata/icons.yml';

		// Fetch YAML file.
		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( \is_wp_error( $response ) ) {
			return new \WP_Error(
				'fa_fetch_failed',
				sprintf( __( 'Failed to fetch icons from GitHub: %s', 'ayecode-connect' ), $response->get_error_message() )
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return new \WP_Error(
				'fa_fetch_failed',
				sprintf( __( 'GitHub returned status code %d for URL: %s', 'ayecode-connect' ), $status_code, $url )
			);
		}

		$yml_content = wp_remote_retrieve_body( $response );
		if ( empty( $yml_content ) ) {
			return new \WP_Error( 'fa_empty_response', __( 'Empty response from GitHub', 'ayecode-connect' ) );
		}

		// Parse YAML.
		return $this->parse_yml_to_icons( $yml_content );
	}

	/**
	 * Parse YAML content to icon data array.
	 *
	 * @param string $yml_content YAML content.
	 * @return array|WP_Error Array of icon data or WP_Error on failure.
	 */
	public function parse_yml_to_icons( $yml_content ) {
		// Load Spyc library.
		require_once dirname( __DIR__ ) . '/build/spyc.php';

		try {
			$data = spyc_load( $yml_content );
		} catch ( Exception $e ) {
			return new \WP_Error(
				'fa_parse_failed',
				sprintf( __( 'Failed to parse YAML: %s', 'ayecode-connect' ), $e->getMessage() )
			);
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error( 'fa_invalid_yaml', __( 'Invalid YAML data', 'ayecode-connect' ) );
		}

		// Convert YAML structure to our format.
		$icons_data = array();
		foreach ( $data as $icon_id => $icon_info ) {
			// Get styles from YAML.
			$styles = isset( $icon_info['styles'] ) ? (array) $icon_info['styles'] : array();

			// Get search terms.
			$search_terms = array();
			if ( isset( $icon_info['search']['terms'] ) && is_array( $icon_info['search']['terms'] ) ) {
				$search_terms = $icon_info['search']['terms'];
			}

			$icons_data[] = array(
				'id'           => $icon_id,
				'label'        => isset( $icon_info['label'] ) ? $icon_info['label'] : $icon_id,
				'search_terms' => $search_terms,
				'membership'   => array(
					'free' => $styles, // For free version from GitHub, all styles are available.
					'pro'  => $styles,
				),
			);
		}

		return $icons_data;
	}

	/**
	 * Fetch icons from Font Awesome API for PRO version.
	 *
	 * @param string $version Font Awesome version.
	 * @param string $api_key Font Awesome API key.
	 * @return array|WP_Error Array of icon data or WP_Error on failure.
	 */
	public function fetch_pro_icons( $version, $api_key ) {
		// Get auth token.
		$auth_token = $this->get_auth_token( $api_key );
		if ( \is_wp_error( $auth_token ) ) {
			return $auth_token;
		}

		// Prepare GraphQL query.
		$query = array(
			'query' => sprintf(
				'query { release(version: "%s") { icons { id label membership { pro free } } } }',
				esc_attr( $version )
			),
		);

		// Make API request.
		$response = wp_remote_post(
			self::API_URL,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $auth_token,
				),
				'body'    => wp_json_encode( $query ),
			)
		);



		if ( \is_wp_error( $response ) ) {
			return new \WP_Error(
				'fa_api_failed',
				sprintf( __( 'Failed to fetch icons from Font Awesome API: %s', 'ayecode-connect' ), $response->get_error_message() )
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return new \WP_Error(
				'fa_api_failed',
				sprintf( __( 'Font Awesome API returned status code %d', 'ayecode-connect' ), $status_code )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['data']['release']['icons'] ) ) {
			return new \WP_Error( 'fa_invalid_api_response', __( 'Invalid API response structure', 'ayecode-connect' ) );
		}

		// Transform GraphQL response to our format (without search terms for now).
		$icons_data = array();
		foreach ( $data['data']['release']['icons'] as $icon ) {
			$icons_data[] = array(
				'id'           => $icon['id'],
				'label'        => isset( $icon['label'] ) ? $icon['label'] : $icon['id'],
				'search_terms' => array(), // No search terms for PRO (for now).
				'membership'   => array(
					'free' => isset( $icon['membership']['free'] ) ? $icon['membership']['free'] : array(),
					'pro'  => isset( $icon['membership']['pro'] ) ? $icon['membership']['pro'] : array(),
				),
			);
		}

		return $icons_data;
	}

	/**
	 * Get or refresh Font Awesome Pro auth token.
	 *
	 * @param string $api_key Font Awesome API key.
	 * @return string|WP_Error Auth token or WP_Error on failure.
	 */
	public function get_auth_token( $api_key ) {
		// Check for cached token.
		$cached_token = get_transient( self::AUTH_TOKEN_TRANSIENT );
		if ( false !== $cached_token ) {
			return $cached_token;
		}

		// Request new token from the token endpoint.
		$response = wp_remote_post(
			self::API_URL . 'token',
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( \is_wp_error( $response ) ) {
			return new \WP_Error(
				'fa_auth_failed',
				sprintf( __( 'Failed to authenticate with Font Awesome API: %s', 'ayecode-connect' ), $response->get_error_message() )
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return new \WP_Error(
				'fa_auth_failed',
				sprintf( __( 'Font Awesome API authentication returned status code %d', 'ayecode-connect' ), $status_code )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['access_token'] ) ) {
			return new \WP_Error( 'fa_auth_failed', __( 'Invalid API key or authentication failed', 'ayecode-connect' ) );
		}

		$token = $data['access_token'];

		// Cache token for 50 minutes (API returns 3600 seconds = 1 hour expiry).
		set_transient( self::AUTH_TOKEN_TRANSIENT, $token, self::AUTH_TOKEN_DURATION );

		return $token;
	}

	/**
	 * Generate JSON files for each icon style.
	 *
	 * @param array  $icons_data Array of icon data.
	 * @param string $version    Font Awesome version.
	 * @param bool   $is_pro     Whether PRO version is enabled.
	 * @return array|WP_Error Array of generated styles on success, WP_Error on failure.
	 */
	public function generate_json_files( $icons_data, $version, $is_pro ) {
		if ( $is_pro ) {
			// PRO: Organize into brands and pro (all other icons).
			$categories = array(
				'brands' => array(),
				'pro'    => array(),
			);

			foreach ( $icons_data as $icon ) {
				$icon_id      = $icon['id'];
				$search_terms = isset( $icon['search_terms'] ) ? $icon['search_terms'] : array();

				// Build search terms string (space-separated).
				$search_string = ! empty( $search_terms ) ? implode( ' ', $search_terms ) : '';

				// Build icon string with pipe-delimited format: icon-name|search terms
				$icon_string = $icon_id;
				if ( ! empty( $search_string ) ) {
					$icon_string .= '|' . $search_string;
				}

				// Get PRO styles.
				$available_styles = ! empty( $icon['membership']['pro'] ) ? $icon['membership']['pro'] : array();

				// Check if this is a brand icon or a regular pro icon.
				if ( in_array( 'brands', $available_styles, true ) ) {
					$categories['brands'][] = $icon_string;
				} else {
					// All non-brand icons go into the pro category.
					// These can be used with any style (solid, regular, light, thin, duotone)
					// and any family (classic, sharp, duotone, sharp-duotone).
					$categories['pro'][] = $icon_string;
				}
			}

			// Save JSON files.
			$errors              = array();
			$generated_categories = array();

			foreach ( $categories as $category => $icons ) {
				if ( empty( $icons ) ) {
					continue;
				}

				$result = $this->save_json_file( $category, $icons, $version, true );
				if ( \is_wp_error( $result ) ) {
					$errors[] = $result->get_error_message();
				} else {
					$generated_categories[] = $category;
				}
			}

			if ( ! empty( $errors ) ) {
				return new \WP_Error( 'fa_save_failed', implode( '; ', $errors ) );
			}

			// Return array of successfully generated categories.
			return $generated_categories;

		} else {
			// FREE: Organize by style (solid, regular, brands).
			$styles = array(
				'brands'  => array(),
				'solid'   => array(),
				'regular' => array(),
			);

			foreach ( $icons_data as $icon ) {
				$icon_id      = $icon['id'];
				$search_terms = isset( $icon['search_terms'] ) ? $icon['search_terms'] : array();

				// Build search terms string (space-separated).
				$search_string = ! empty( $search_terms ) ? implode( ' ', $search_terms ) : '';

				// Build icon string with pipe-delimited format: icon-name|search terms
				$icon_string = $icon_id;
				if ( ! empty( $search_string ) ) {
					$icon_string .= '|' . $search_string;
				}

				// Get FREE styles.
				$available_styles = ! empty( $icon['membership']['free'] ) ? $icon['membership']['free'] : array();

				foreach ( $available_styles as $style ) {
					if ( isset( $styles[ $style ] ) ) {
						// Add icon with search terms.
						$styles[ $style ][] = $icon_string;
					}
				}
			}

			// Save JSON file for each style and track which ones were generated.
			$errors           = array();
			$generated_styles = array();

			foreach ( $styles as $style => $icons ) {
				if ( empty( $icons ) ) {
					continue; // Skip empty styles.
				}

				$result = $this->save_json_file( $style, $icons, $version, false );
				if ( \is_wp_error( $result ) ) {
					$errors[] = $result->get_error_message();
				} else {
					$generated_styles[] = $style;
				}
			}

			if ( ! empty( $errors ) ) {
				return new \WP_Error( 'fa_save_failed', implode( '; ', $errors ) );
			}

			// Return array of successfully generated styles.
			return $generated_styles;
		}
	}

	/**
	 * Save JSON file for a specific icon style or family.
	 *
	 * @param string $style_or_family Icon style (e.g., 'solid', 'brands') or family (e.g., 'classic', 'sharp').
	 * @param array  $icons           Array of icon strings (format: "icon-name|search terms" or "icon-name").
	 * @param string $version         Font Awesome version.
	 * @param bool   $is_pro          Whether this is a PRO family file (adds modifiers field).
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function save_json_file( $style_or_family, $icons, $version, $is_pro = false ) {
		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['basedir'] ) . AYECODE_FA_CACHE_DIR_NAME . '/' . AYECODE_FA_LIBRARIES_DIR_NAME . '/';
		$filename   = sprintf( AYECODE_FA_JSON_FILENAME_PATTERN, $style_or_family );
		$filepath   = $dir . $filename;

		// Determine if modifiers should be enabled (all except brands).
		$has_modifiers = ( 'brands' !== $style_or_family );

		// For the generic "pro" category, use neutral FA classes without style prefix.
		if ( 'pro' === $style_or_family ) {
			$data = array(
				'schema_version' => AYECODE_FA_JSON_SCHEMA_VERSION,
				'prefix'         => 'fa-solid fa-',
				'icon-style'     => 'fa-solid',
				'list-icon'      => 'fa-solid fa-font-awesome',
				'version'        => $version,
				'modifiers'      => $has_modifiers,
				'icons'          => array_values( array_unique( $icons ) ),
			);
		} else {
			// Prepare JSON data for specific styles (brands, solid, regular, etc.).
			$data = array(
				'schema_version' => AYECODE_FA_JSON_SCHEMA_VERSION,
				'prefix'         => 'fa-' . $style_or_family . ' fa-',
				'icon-style'     => 'fa-' . $style_or_family,
				'list-icon'      => 'fa-' . $style_or_family . ' fa-font-awesome',
				'version'        => $version,
				'modifiers'      => $has_modifiers,
				'icons'          => array_values( array_unique( $icons ) ),
			);
		}

		// Encode to JSON (minified).
		$json_content = wp_json_encode( $data );

		if ( false === $json_content ) {
			return new \WP_Error(
				'fa_json_encode_failed',
				sprintf( __( 'Failed to encode JSON for style: %s', 'ayecode-connect' ), $style )
			);
		}

		// Ensure directory exists.
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			// Add index.php protection to cache and libraries directories.
			$cache_dir = dirname( $dir );
			$this->create_index_protection( $cache_dir );
			$this->create_index_protection( $dir );
		}

		// Write file.

		$result = file_put_contents( $filepath, $json_content );

		if ( false === $result ) {
			return new \WP_Error(
				'fa_file_write_failed',
				sprintf( __( 'Failed to write JSON file: %s', 'ayecode-connect' ), $filepath )
			);
		}

		return true;
	}

	/**
	 * Delete icon library files that are no longer needed.
	 *
	 * Removes files for styles that exist in $old_styles but not in $new_styles.
	 *
	 * @param array $old_styles Array of previously generated styles.
	 * @param array $new_styles Array of newly generated styles.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function cleanup_old_styles( $old_styles, $new_styles ) {
		// Find styles that need to be removed.
		$styles_to_remove = array_diff( $old_styles, $new_styles );

		if ( empty( $styles_to_remove ) ) {
			return true; // Nothing to clean up.
		}

		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['basedir'] ) . AYECODE_FA_CACHE_DIR_NAME . '/' . AYECODE_FA_LIBRARIES_DIR_NAME . '/';
		$errors     = array();

		foreach ( $styles_to_remove as $style ) {
			$filename = sprintf( AYECODE_FA_JSON_FILENAME_PATTERN, $style );
			$filepath = $dir . $filename;

			if ( file_exists( $filepath ) ) {
				$result = wp_delete_file( $filepath );
				if ( false === $result ) {
					$errors[] = sprintf( __( 'Failed to delete file: %s', 'ayecode-connect' ), $filename );
				}
			}
		}

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'fa_cleanup_failed', implode( '; ', $errors ) );
		}

		return true;
	}

	/**
	 * Convert version to GitHub branch/tag format.
	 *
	 * @param string $version Font Awesome version.
	 * @return string GitHub version string.
	 */
	private function get_github_version( $version ) {
		// If version is empty, default to latest branch.
		if ( empty( $version ) ) {
			return '6.x';
		}

		// If version contains 'x', it's already a branch format.
		if ( strpos( $version, 'x' ) !== false ) {
			return $version;
		}

		// For specific versions, extract major version for branch.
		$parts = explode( '.', $version );
		if ( isset( $parts[0] ) ) {
			return $parts[0] . '.x';
		}

		return $version;
	}

	/**
	 * Create index.php protection file in a directory.
	 *
	 * Prevents direct directory listing and PHP execution by adding an index.php file.
	 * More compatible across servers than .htaccess.
	 *
	 * @param string $directory Directory path.
	 * @return bool True on success, false on failure.
	 */
	private function create_index_protection( string $directory ): bool {
		$index_file = trailingslashit( $directory ) . 'index.php';

		// Don't overwrite if it already exists.
		if ( file_exists( $index_file ) ) {
			return true;
		}

		$content = "<?php\n// Silence is golden.\n";
		$result = file_put_contents( $index_file, $content );

		return false !== $result;
	}
}
