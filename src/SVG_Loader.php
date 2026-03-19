<?php
/**
 * JIT SVG Icon Loader for Font Awesome
 *
 * Loads individual SVG icons on-demand with multi-layer caching strategy.
 * Supports Font Awesome Free, Pro, and custom uploaded SVG icons.
 *
 * @package WP_Font_Awesome_Settings
 * @since 2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AyeCode Font Awesome SVG Loader Class
 *
 * Implements a Just-In-Time SVG rendering engine to replace webfont/JS library loading.
 * Uses a three-layer caching strategy: Object Cache → Filesystem → Remote CDN.
 */
class AyeCode_Font_Awesome_SVG_Loader {

	/**
	 * Singleton instance.
	 *
	 * @var AyeCode_Font_Awesome_SVG_Loader|null
	 */
	private static $instance = null;

	/**
	 * Font Awesome CDN endpoints.
	 *
	 * @var array
	 */
	private $cdn_endpoints = array(
		'free' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@{version}/svgs/{style}/{name}.svg',
		'pro'  => 'https://pro.fontawesome.com/releases/v{version}/svgs/{style}/{name}.svg', // Placeholder - requires auth
	);

	/**
	 * Available icon styles by type.
	 *
	 * @var array
	 */
	private $available_styles = array(
		'free' => array( 'solid', 'regular', 'brands' ),
		'pro'  => array( 'solid', 'regular', 'light', 'thin', 'duotone', 'brands', 'sharp-solid', 'sharp-regular' ),
	);

	/**
	 * Cache expiration time for fetch locks (seconds).
	 *
	 * @var int
	 */
	private $lock_timeout = 10;

	/**
	 * Reference to main settings instance.
	 *
	 * @var WP_Font_Awesome_Settings
	 */
	private $settings_instance;

	/**
	 * Get singleton instance.
	 *
	 * @return AyeCode_Font_Awesome_SVG_Loader
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings_instance = WP_Font_Awesome_Settings::instance();
	}

	/**
	 * Get an inline SVG icon with applied options.
	 *
	 * Main entry point for rendering SVG icons. Parses identifier, checks caches,
	 * fetches remotely if needed, sanitizes, and applies options.
	 *
	 * @param string $identifier Icon identifier (e.g., 'fa-solid fa-user' or 'custom-logo').
	 * @param array  $options    Optional. Rendering options including class, aria_label, width, height, fill, attributes.
	 *
	 * @return string SVG markup or empty string on failure.
	 */
	public function get_inline_icon( string $identifier, array $options = array() ): string {
		// Parse the identifier to determine routing.
		$parsed = $this->parse_identifier( $identifier );
//        print_r($parsed);echo '###'.$identifier;
		if ( is_wp_error( $parsed ) ) {
			return '';
		}

		$style  = $parsed['style'];
		$name   = $parsed['name'];
		$type   = $parsed['type']; // 'free', 'pro', or 'custom'
		$family = isset( $parsed['family'] ) ? $parsed['family'] : null;
		$weight = isset( $parsed['weight'] ) ? $parsed['weight'] : null;

		// Merge extra classes from identifier into options.
		if ( ! empty( $parsed['extra_classes'] ) ) {
			$existing_classes = isset( $options['class'] ) ? $options['class'] : array();
			if ( is_string( $existing_classes ) ) {
				$existing_classes = explode( ' ', $existing_classes );
			}
			$options['class'] = array_merge( (array) $existing_classes, $parsed['extra_classes'] );
		}

		// Generate cache key.
		$cache_key = $this->get_cache_key( $style, $name );

		// Layer 1: Check object cache.
		$svg = $this->get_from_object_cache( $cache_key );
		if ( $svg ) {
			return $this->apply_svg_options( $svg, $options );
		}

		// Layer 2: Check filesystem cache.
		$svg = $this->get_from_filesystem( $style, $name );
		if ( $svg && ! is_wp_error( $svg ) ) {
			// Save to object cache for faster future access.
			$this->save_to_object_cache( $cache_key, $svg );
			return $this->apply_svg_options( $svg, $options );
		}

		// Layer 3: Fetch from remote CDN (with concurrency lock).
		if ( 'custom' !== $type ) {
			// Check if another process is already fetching this icon.
			if ( ! $this->acquire_fetch_lock( $cache_key ) ) {
				// Another process is fetching, return empty to avoid race condition.
				return '';
			}

			// Fetch from remote.
			$version = $this->settings_instance->settings['version'] ?: $this->settings_instance->get_latest_version();
			$svg     = $this->fetch_from_remote( $style, $name, $version, $type, $family, $weight );

			// Release the lock.
			$this->release_fetch_lock( $cache_key );

			if ( is_wp_error( $svg ) ) {
				return '';
			}

			// Sanitize the SVG before caching.
			$svg = $this->sanitize_svg_content( $svg );

			// Save to both caches.
			$this->save_to_caches( $cache_key, $svg, $style, $name );

			return $this->apply_svg_options( $svg, $options );
		}

		return '';
	}

	/**
	 * Parse icon identifier into style and name components.
	 *
	 * Supports formats:
	 * - 'fa-solid fa-user' → ['style' => 'solid', 'name' => 'user', 'type' => 'free', 'extra_classes' => []]
	 * - 'fas fa-user' → ['style' => 'solid', 'name' => 'user', 'type' => 'free', 'extra_classes' => []]
	 * - 'fa-sharp fa-regular fa-sun' → ['style' => 'sharp-regular', 'name' => 'sun', 'type' => 'pro', 'extra_classes' => []]
	 * - 'fa-duotone fa-solid fa-acorn' → ['style' => 'duotone', 'name' => 'acorn', 'type' => 'pro', 'extra_classes' => []]
	 * - 'fa-sharp-duotone fa-thin fa-acorn' → ['style' => 'sharp-duotone', 'name' => 'acorn', 'type' => 'pro', 'extra_classes' => []]
	 * - 'fas fa-sign-out-alt animate-target me-2' → includes extra_classes
	 * - 'aui-icon-logo' → ['style' => 'custom', 'name' => 'logo', 'type' => 'custom', 'extra_classes' => []]
	 *
	 * @param string $identifier Icon identifier string.
	 *
	 * @return array|WP_Error Parsed components or error.
	 */
	public function parse_identifier( string $identifier ) {
		$identifier = trim( $identifier );

		// Check for custom icon format: 'aui-icon-{name}'.
		if ( strpos( $identifier, 'aui-icon-' ) === 0 ) {
			$parts = explode( ' ', $identifier );
			$name  = str_replace( 'aui-icon-', '', $parts[0] );

			// Extract any extra classes after the custom icon identifier.
			$extra_classes = array_slice( $parts, 1 );

			return array(
				'style'         => 'custom',
				'name'          => sanitize_file_name( $name ),
				'type'          => 'custom',
				'extra_classes' => $extra_classes,
			);
		}

		// Parse Font Awesome format.
		$parts = explode( ' ', $identifier );
		if ( count( $parts ) < 2 ) {
			return new WP_Error( 'invalid_identifier', 'Invalid icon identifier format. Expected "fa-{style} fa-{name}" or "fas fa-{name}".' );
		}

		// Initialize family and weight.
		$family = null;
		$weight = null;

		// Check for shorthand syntax first (fas, far, fab, etc).
		$style_map = array(
			'fas'  => 'solid',
			'far'  => 'regular',
			'fab'  => 'brands',
			'fal'  => 'light',
			'fat'  => 'thin',
			'fad'  => 'duotone',
			'fass' => 'sharp-solid',
			'fasr' => 'sharp-regular',
		);

		if ( isset( $style_map[ $parts[0] ] ) ) {
			// Shorthand format: fas fa-user
			$style = $style_map[ $parts[0] ];
			$name  = str_replace( 'fa-', '', $parts[1] );
			$extra_classes = array_slice( $parts, 2 );

			// Map shorthand to family/weight for API calls.
			if ( 'sharp-solid' === $style ) {
				$family = 'sharp';
				$weight = 'solid';
			} elseif ( 'sharp-regular' === $style ) {
				$family = 'sharp';
				$weight = 'regular';
			} elseif ( 'brands' === $style ) {
				$family = null;
				$weight = null;
			} else {
				// Classic family with weight.
				$family = null;
				$weight = $style;
			}
		} else {
			// Full format: Parse using family/weight extraction.
			// Define known family and weight classes.
			// Note: fa-duotone can be BOTH a family class OR a weight class depending on context.
			$family_only_classes = array( 'fa-sharp', 'fa-sharp-duotone' );
			$weight_only_classes = array( 'fa-solid', 'fa-regular', 'fa-light', 'fa-thin' );
			$duotone_class = 'fa-duotone';

			$icon_name = null;
			$extra_classes = array();
			$has_duotone = false;

			// First pass: identify all components.
			foreach ( $parts as $part ) {
				if ( in_array( $part, $family_only_classes, true ) ) {
					// Found a family-only class.
					if ( null === $family ) {
						$family = str_replace( 'fa-', '', $part );
					}
				} elseif ( $duotone_class === $part ) {
					// Found fa-duotone - mark it for later processing.
					$has_duotone = true;
				} elseif ( in_array( $part, $weight_only_classes, true ) ) {
					// Found a weight class.
					if ( null === $weight ) {
						$weight = str_replace( 'fa-', '', $part );
					}
				} elseif ( strpos( $part, 'fa-' ) === 0 ) {
					// This is the icon name (first fa-* class that isn't family/weight).
					if ( null === $icon_name ) {
						$icon_name = str_replace( 'fa-', '', $part );
					} else {
						// Additional fa- classes are treated as extra classes.
						$extra_classes[] = $part;
					}
				} else {
					// Non-fa class, add to extra classes.
					$extra_classes[] = $part;
				}
			}

			// Process fa-duotone based on context:
			// - If there's a weight class (fa-solid, fa-regular, etc), fa-duotone is the FAMILY
			// - If there's NO weight class, fa-duotone is the WEIGHT (for classic family)
			if ( $has_duotone ) {
				if ( null !== $weight ) {
					// fa-duotone is the family (e.g., "fa-duotone fa-solid fa-acorn")
					if ( null === $family ) {
						$family = 'duotone';
					}
				} else {
					// fa-duotone is the weight for classic family (e.g., "fa-duotone fa-acorn")
					$weight = 'duotone';
				}
			}

			// Validate we found an icon name.
			if ( null === $icon_name ) {
				return new WP_Error( 'invalid_identifier', 'Could not identify icon name in identifier.' );
			}

			// Map family + weight to filesystem style.
			// Sharp family: sharp-solid, sharp-regular, sharp-light, sharp-thin
			// Duotone family: duotone (single style, weight ignored for filesystem)
			// Sharp-duotone family: sharp-duotone (single style, weight ignored for filesystem)
			// Classic family (no family class): solid, regular, light, thin, duotone

			if ( 'brands' === $icon_name || ( null === $family && null === $weight ) ) {
				// Brands or invalid - default to solid for classic.
				$style = $weight ?: 'solid';
			} elseif ( 'sharp' === $family ) {
				// Sharp family: combine with weight (sharp-solid, sharp-regular, etc).
				$weight = $weight ?: 'solid';
				$style = 'sharp-' . $weight;
			} elseif ( 'duotone' === $family ) {
				// Duotone family: single style (weight is ignored for filesystem lookup).
				$style = 'duotone';
			} elseif ( 'sharp-duotone' === $family ) {
				// Sharp-duotone family: single style (weight is ignored for filesystem lookup).
				$style = 'sharp-duotone';
			} else {
				// Classic family (no family class): use weight directly.
				$style = $weight ?: 'solid';
			}

			$name = $icon_name;
		}

		// Determine if Pro or Free based on style.
		$is_pro = $this->settings_instance->settings['pro'] && in_array( $style, $this->available_styles['pro'], true );
		$type   = $is_pro ? 'pro' : 'free';

		// Validate style.
		$valid_styles = $is_pro ? $this->available_styles['pro'] : $this->available_styles['free'];
		if ( ! in_array( $style, $valid_styles, true ) ) {
			return new WP_Error( 'invalid_style', sprintf( 'Invalid icon style "%s".', $style ) );
		}

		return array(
			'style'         => $style,
			'name'          => sanitize_file_name( $name ),
			'type'          => $type,
			'family'        => $family,
			'weight'        => $weight,
			'extra_classes' => $extra_classes,
		);
	}

	/**
	 * Get SVG from object cache (Layer 1).
	 *
	 * @param string $cache_key Cache key.
	 *
	 * @return string|false SVG content or false if not found.
	 */
	private function get_from_object_cache( string $cache_key ) {
		return wp_cache_get( $cache_key, 'ayecode_icons' );
	}

	/**
	 * Save SVG to object cache.
	 *
	 * @param string $cache_key Cache key.
	 * @param string $svg       SVG content.
	 */
	private function save_to_object_cache( string $cache_key, string $svg ): void {
		wp_cache_set( $cache_key, $svg, 'ayecode_icons', DAY_IN_SECONDS );
	}

	/**
	 * Get SVG from filesystem cache (Layer 2).
	 *
	 * @param string $style Icon style.
	 * @param string $name  Icon name.
	 *
	 * @return string|WP_Error SVG content or error if not found.
	 */
	private function get_from_filesystem( string $style, string $name ) {
		$file_path = $this->get_icon_cache_dir() . $style . DIRECTORY_SEPARATOR . $name . '.svg';

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', 'SVG file not found in cache.' );
		}

		// Use WP_Filesystem for reading.
		$svg = file_get_contents( $file_path );

		if ( false === $svg ) {
			return new WP_Error( 'read_error', 'Failed to read SVG file from cache.' );
		}

		return $svg;
	}

	/**
	 * Fetch SVG from remote CDN (Layer 3).
	 *
	 * @param string      $style   Icon style.
	 * @param string      $name    Icon name.
	 * @param string      $version Font Awesome version.
	 * @param string      $type    'free' or 'pro'.
	 * @param string|null $family  Icon family (for PRO icons).
	 * @param string|null $weight  Icon weight (for PRO icons).
	 *
	 * @return string|WP_Error SVG content or error.
	 */
	private function fetch_from_remote( string $style, string $name, string $version, string $type, $family = null, $weight = null ) {
		// For Pro v6/v7, use the Font Awesome API instead of CDN.
		if ( 'pro' === $type && version_compare( $version, '6.0.0', '>=' ) ) {
			return $this->fetch_from_fa_api( $style, $name, $version, $family, $weight );
		}

		// Build CDN URL.
		$endpoint = $this->cdn_endpoints[ $type ];
		$url      = str_replace(
			array( '{version}', '{style}', '{name}' ),
			array( $version, $style, $name ),
			$endpoint
		);

//        echo '###'.$url;exit
		// Fetch via wp_remote_get.
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'image/svg+xml',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return new WP_Error( 'fetch_failed', sprintf( 'Failed to fetch SVG from CDN. Status: %d', $status_code ) );
		}

		$svg = wp_remote_retrieve_body( $response );
		if ( empty( $svg ) ) {
			return new WP_Error( 'empty_response', 'Received empty SVG from CDN.' );
		}

		return $svg;
	}

	/**
	 * Fetch SVG from Font Awesome API using GraphQL (for Pro v6/v7).
	 *
	 * @param string      $style   Icon style (for filesystem/cache).
	 * @param string      $name    Icon name.
	 * @param string      $version Font Awesome version.
	 * @param string|null $family  Icon family (sharp, duotone, sharp-duotone, or null for classic).
	 * @param string|null $weight  Icon weight (solid, regular, light, thin).
	 *
	 * @return string|WP_Error SVG content or error.
	 */
	private function fetch_from_fa_api( string $style, string $name, string $version, $family = null, $weight = null ) {
		// Get or refresh auth token.
		$auth_token = $this->get_auth_token();

		if ( is_wp_error( $auth_token ) ) {
			return $auth_token;
		}

		// Map version to API format (6.x or 7.x).
		$api_version = version_compare( $version, '7.0.0', '>=' ) ? '7.x' : '6.x';

		// Map family to GraphQL FAMILY enum.
		$family_map = array(
			'sharp'         => 'SHARP',
			'duotone'       => 'DUOTONE',
			'sharp-duotone' => 'SHARP_DUOTONE',
			null            => 'CLASSIC', // Default to classic if no family.
		);

		// Map weight to GraphQL STYLE enum.
		$weight_map = array(
			'solid'   => 'SOLID',
			'regular' => 'REGULAR',
			'light'   => 'LIGHT',
			'thin'    => 'THIN',
			'duotone' => 'DUOTONE',
			null      => 'SOLID', // Default to solid if no weight.
		);

		// Special handling for brands.
		if ( 'brands' === $style ) {
			$graphql_family = 'BRANDS';
			$graphql_style  = 'REGULAR';
		} else {
			$graphql_family = isset( $family_map[ $family ] ) ? $family_map[ $family ] : 'CLASSIC';
			$graphql_style  = isset( $weight_map[ $weight ] ) ? $weight_map[ $weight ] : 'SOLID';
		}

		// Build GraphQL query.
		$query = sprintf(
			'query { release(version: "%s") { icon(name: "%s") { svgs(filter: { familyStyles: [{ family: %s, style: %s }] }) { html } } } }',
			$api_version,
			$name,
			$graphql_family,
			$graphql_style
		);

		// Make API request.
		$response = wp_remote_post(
			'https://api.fontawesome.com/',
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $auth_token,
				),
				'body'    => wp_json_encode( array( 'query' => $query ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		// If unauthorized, try refreshing the token once.
		if ( 401 === $status_code || 403 === $status_code ) {
			// Force refresh the token.
			$auth_token = $this->get_auth_token( true );

			if ( is_wp_error( $auth_token ) ) {
				return $auth_token;
			}

			// Retry the request with new token.
			$response = wp_remote_post(
				'https://api.fontawesome.com/',
				array(
					'timeout' => 10,
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $auth_token,
					),
					'body'    => wp_json_encode( array( 'query' => $query ) ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status_code = wp_remote_retrieve_response_code( $response );
		}

		if ( 200 !== $status_code ) {
			return new WP_Error( 'api_request_failed', sprintf( 'Font Awesome API request failed. Status: %d', $status_code ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for GraphQL errors (especially unauthorized)
		if ( ! empty( $data['errors'] ) ) {
			foreach ( $data['errors'] as $error ) {
				if ( isset( $error['message'] ) && $error['message'] === 'unauthorized' ) {
					// Force refresh the token.
					$auth_token = $this->get_auth_token( true );

					if ( is_wp_error( $auth_token ) ) {
						return $auth_token;
					}

					// Retry the request with new token.
					$response = wp_remote_post(
						'https://api.fontawesome.com/',
						array(
							'timeout' => 10,
							'headers' => array(
								'Content-Type'  => 'application/json',
								'Authorization' => 'Bearer ' . $auth_token,
							),
							'body'    => wp_json_encode( array( 'query' => $query ) ),
						)
					);

					if ( is_wp_error( $response ) ) {
						return $response;
					}

					$status_code = wp_remote_retrieve_response_code( $response );

					if ( 200 !== $status_code ) {
						return new WP_Error( 'api_request_failed', sprintf( 'Font Awesome API request failed after retry. Status: %d', $status_code ) );
					}

					$body = wp_remote_retrieve_body( $response );
					$data = json_decode( $body, true );

					// Check again for errors after retry
					if ( ! empty( $data['errors'] ) ) {
						return new WP_Error( 'api_error', 'Font Awesome API returned errors: ' . wp_json_encode( $data['errors'] ) );
					}

					break; // Exit the error loop after retry
				}
			}
		}

		if ( empty( $data['data']['release']['icon']['svgs'][0]['html'] ) ) {
			return new WP_Error( 'icon_not_found', sprintf( 'Icon "%s" not found in Font Awesome API response.', $name ) );
		}

		// Extract and unescape SVG.
		$svg = $data['data']['release']['icon']['svgs'][0]['html'];
		$svg = stripslashes( $svg );

		return $svg;
	}

	/**
	 * Get or refresh Font Awesome auth token.
	 *
	 * Exchanges api_key for auth_token via Font Awesome API.
	 * Caches auth_token in settings for reuse.
	 *
	 * @param bool $force_refresh Force refresh even if token exists.
	 *
	 * @return string|WP_Error Auth token or error.
	 */
	private function get_auth_token( bool $force_refresh = false ) {
		$settings = $this->settings_instance->settings;

		// Check if we have an existing auth_token (unless forcing refresh).
		if ( ! $force_refresh && ! empty( $settings['auth_token'] ) ) {
			return $settings['auth_token'];
		}

		// Check if we have an api_key.
		if ( empty( $settings['api_key'] ) ) {
			return new WP_Error( 'missing_api_key', 'Font Awesome API Key is required for Pro v6/v7 icons.' );
		}

		// Exchange api_key for auth_token.
		$response = wp_remote_post(
			'https://api.fontawesome.com/token',
			array(
				'timeout' => 10,
				'headers' => array(
					'Authorization' => 'Bearer ' . $settings['api_key'],
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			return new WP_Error( 'token_exchange_failed', sprintf( 'Failed to exchange API key for auth token. Status: %d', $status_code ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['access_token'] ) ) {
			return new WP_Error( 'invalid_token_response', 'Invalid response from Font Awesome token endpoint.' );
		}

		$auth_token = $data['access_token'];

		// Save the auth_token to settings.
		$this->save_auth_token( $auth_token );

		return $auth_token;
	}

	/**
	 * Save auth token to settings.
	 *
	 * @param string $auth_token The auth token to save.
	 */
	private function save_auth_token( string $auth_token ): void {
		$settings = get_option( 'wp-font-awesome-settings', array() );
		$settings['auth_token'] = $auth_token;
		update_option( 'wp-font-awesome-settings', $settings );

		// Update the cached settings in the main instance.
		$this->settings_instance->settings['auth_token'] = $auth_token;
	}

	/**
	 * Acquire a fetch lock to prevent concurrent downloads of the same icon.
	 *
	 * @param string $cache_key Cache key.
	 *
	 * @return bool True if lock acquired, false if locked by another process.
	 */
	private function acquire_fetch_lock( string $cache_key ): bool {
		$lock_key = 'ayecode_fa_lock_' . $cache_key;

		// Check if lock exists.
		if ( get_transient( $lock_key ) ) {
			return false; // Already locked.
		}

		// Set the lock.
		set_transient( $lock_key, true, $this->lock_timeout );
		return true;
	}

	/**
	 * Release a fetch lock.
	 *
	 * @param string $cache_key Cache key.
	 */
	private function release_fetch_lock( string $cache_key ): void {
		$lock_key = 'ayecode_fa_lock_' . $cache_key;
		delete_transient( $lock_key );
	}

	/**
	 * Sanitize SVG content for security using enshrined/svg-sanitize library.
	 *
	 * Uses battle-tested external library for comprehensive SVG sanitization,
	 * then normalizes IDs to prevent DOM conflicts.
	 *
	 * @param string $svg Raw SVG content.
	 *
	 * @return string Sanitized SVG.
	 */
	private function sanitize_svg_content( string $svg ): string {
		// Use enshrined/svg-sanitize library for secure sanitization
		$sanitizer = new \enshrined\svgSanitize\Sanitizer();
		$sanitizer->removeRemoteReferences( true );

		$svg = $sanitizer->sanitize( $svg );

		// Return empty string if sanitization fails
		if ( false === $svg || empty( $svg ) ) {
			return '';
		}

		// Normalize/remove internal IDs to prevent DOM conflicts.
		$svg = $this->normalize_svg_ids( $svg );

		return $svg;
	}

	/**
	 * Normalize SVG IDs to prevent DOM conflicts.
	 *
	 * Strips generic IDs or prefixes them with a unique hash.
	 *
	 * @param string $svg SVG content.
	 *
	 * @return string SVG with normalized IDs.
	 */
	private function normalize_svg_ids( string $svg ): string {
		// Generate a unique prefix for this icon instance.
		$prefix = 'ayefa-' . substr( md5( uniqid() ), 0, 8 );

		// Find all id attributes and prefix them.
		$svg = preg_replace_callback(
			'/\sid\s*=\s*["\']([^"\']+)["\']/i',
			function ( $matches ) use ( $prefix ) {
				return ' id="' . $prefix . '-' . $matches[1] . '"';
			},
			$svg
		);

		// Update corresponding references (url(#id) and href="#id").
		$svg = preg_replace_callback(
			'/(url\(#|href="#)([^")]+)([")])/i',
			function ( $matches ) use ( $prefix ) {
				return $matches[1] . $prefix . '-' . $matches[2] . $matches[3];
			},
			$svg
		);

		return $svg;
	}

	/**
	 * Apply rendering options to SVG markup.
	 *
	 * Injects classes, ARIA attributes, dimensions, fill color, and custom attributes.
	 *
	 * @param string $svg     SVG content.
	 * @param array  $options Rendering options.
	 *
	 * @return string Modified SVG markup.
	 */
	private function apply_svg_options( string $svg, array $options ): string {
		$defaults = array(
			'class'      => '',
			'aria_label' => '',
			'width'      => '',
			'height'     => '',
			'fill'       => 'currentColor',
			'attributes' => array(),
		);

		$options = wp_parse_args( $options, $defaults );

		// Build attributes array.
		$attributes = array();

		// Add classes (always include base aui-icon class).
		$base_classes = array( 'aui-icon' );
		if ( ! empty( $options['class'] ) ) {
			$custom_classes = is_array( $options['class'] ) ? $options['class'] : array( $options['class'] );
			$base_classes = array_merge( $base_classes, $custom_classes );
		}
		$attributes['class'] = esc_attr( implode( ' ', $base_classes ) );

		// Add ARIA attributes.
		if ( ! empty( $options['aria_label'] ) ) {
			$attributes['role'] = 'img';
			$attributes['aria-label'] = esc_attr( $options['aria_label'] );

			// Inject title tag for accessibility.
			$title = '<title>' . esc_html( $options['aria_label'] ) . '</title>';
			$svg = preg_replace( '/(<svg[^>]*>)/', '$1' . $title, $svg, 1 );
		} else {
			$attributes['aria-hidden'] = 'true';
		}

		// Add dimensions.
		if ( ! empty( $options['width'] ) ) {
			$attributes['width'] = esc_attr( $options['width'] );
		}
		if ( ! empty( $options['height'] ) ) {
			$attributes['height'] = esc_attr( $options['height'] );
		}

		// Add fill color.
		if ( ! empty( $options['fill'] ) ) {
			$attributes['fill'] = esc_attr( $options['fill'] );
		}

		// Add custom attributes.
		if ( ! empty( $options['attributes'] ) && is_array( $options['attributes'] ) ) {
			foreach ( $options['attributes'] as $key => $value ) {
				$attributes[ $key ] = esc_attr( $value );
			}
		}

		// Inject attributes into SVG tag.
		$svg = $this->inject_svg_attributes( $svg, $attributes );

		return $svg;
	}

	/**
	 * Inject attributes into the SVG opening tag.
	 *
	 * @param string $svg        SVG content.
	 * @param array  $attributes Key-value pairs of attributes.
	 *
	 * @return string Modified SVG.
	 */
	private function inject_svg_attributes( string $svg, array $attributes ): string {
		if ( empty( $attributes ) ) {
			return $svg;
		}

		// Build attribute string.
		$attr_string = '';
		foreach ( $attributes as $key => $value ) {
			$attr_string .= ' ' . $key . '="' . $value . '"';
		}

		// Inject into <svg> tag.
		$svg = preg_replace( '/(<svg\b[^>]*)>/i', '$1' . $attr_string . '>', $svg, 1 );

		return $svg;
	}

	/**
	 * Save SVG to both object cache and filesystem.
	 *
	 * @param string $cache_key Cache key.
	 * @param string $svg       SVG content.
	 * @param string $style     Icon style.
	 * @param string $name      Icon name.
	 */
	private function save_to_caches( string $cache_key, string $svg, string $style, string $name ): void {
		// Save to object cache.
		$this->save_to_object_cache( $cache_key, $svg );

		// Save to filesystem.
		$dir = $this->get_icon_cache_dir() . $style . DIRECTORY_SEPARATOR;

		// Ensure directory exists.
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$file_path = $dir . $name . '.svg';
		file_put_contents( $file_path, $svg );
	}

	/**
	 * Generate cache key for an icon.
	 *
	 * @param string $style Icon style.
	 * @param string $name  Icon name.
	 *
	 * @return string Cache key.
	 */
	private function get_cache_key( string $style, string $name ): string {
		return 'ayecode_icon_' . $style . '_' . $name;
	}

	/**
	 * Get icon cache directory path.
	 *
	 * @return string Directory path with trailing slash.
	 */
	public function get_icon_cache_dir(): string {
		$upload_dir = wp_upload_dir( null, false );
		return $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'ayecode-icon-cache' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Get icon cache directory URL.
	 *
	 * @return string Directory URL with trailing slash.
	 */
	public function get_icon_cache_url(): string {
		$upload_dir = wp_upload_dir( null, false );
		return $upload_dir['baseurl'] . '/ayecode-icon-cache/';
	}

	/**
	 * Check if cache directory is writable.
	 *
	 * Performs a pre-flight check to ensure the cache directory can be created and written to.
	 *
	 * @return bool|WP_Error True if writable, WP_Error otherwise.
	 */
	public function check_cache_writability() {
		$cache_dir = $this->get_icon_cache_dir();

		// Try to create directory if it doesn't exist.
		if ( ! file_exists( $cache_dir ) ) {
			if ( ! wp_mkdir_p( $cache_dir ) ) {
				return new WP_Error( 'mkdir_failed', 'Failed to create icon cache directory.' );
			}
		}

		// Check if writable.
		if ( ! is_writable( $cache_dir ) ) {
			return new WP_Error( 'not_writable', 'Icon cache directory is not writable.' );
		}

		return true;
	}

	/**
	 * Clear all cached icons.
	 *
	 * Recursively deletes all SVG files from the cache directory and flushes object cache.
	 * Note: This is a hard delete, not cache-busting via versioning.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function clear_icon_cache() {
		$cache_dir = $this->get_icon_cache_dir();

		if ( ! file_exists( $cache_dir ) ) {
			return true; // Nothing to clear.
		}

		// Use WP_Filesystem for recursive delete.
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( empty( $wp_filesystem ) ) {
			return new WP_Error( 'filesystem_error', 'Failed to initialize WP_Filesystem.' );
		}

		// Delete the directory and all contents.
		$result = $wp_filesystem->delete( $cache_dir, true );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', 'Failed to delete icon cache directory.' );
		}

		// Recreate the directory.
		wp_mkdir_p( $cache_dir );

		// Flush object cache for this group.
		wp_cache_flush();

		return true;
	}
}
