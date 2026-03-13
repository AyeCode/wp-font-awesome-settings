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
		if ( is_wp_error( $parsed ) ) {
			return '';
		}

		$style = $parsed['style'];
		$name  = $parsed['name'];
		$type  = $parsed['type']; // 'free', 'pro', or 'custom'

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
			$svg     = $this->fetch_from_remote( $style, $name, $version, $type );

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

		// Extract style and name from first two parts.
		$style_part = $parts[0];
		$name_part  = $parts[1];

		// Extract any extra classes after the icon name.
		$extra_classes = array_slice( $parts, 2 );

		// Map old shorthand syntax to full style names.
		$style_map = array(
			'fas' => 'solid',
			'far' => 'regular',
			'fab' => 'brands',
			'fal' => 'light',
			'fat' => 'thin',
			'fad' => 'duotone',
		);

		// Check if using old syntax (fas, far, fab, etc).
		if ( isset( $style_map[ $style_part ] ) ) {
			$style = $style_map[ $style_part ];
		} else {
			// Remove 'fa-' prefix from new syntax.
			$style = str_replace( 'fa-', '', $style_part );
		}

		// Remove 'fa-' prefix from name.
		$name = str_replace( 'fa-', '', $name_part );

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
	 * @param string $style   Icon style.
	 * @param string $name    Icon name.
	 * @param string $version Font Awesome version.
	 * @param string $type    'free' or 'pro'.
	 *
	 * @return string|WP_Error SVG content or error.
	 */
	private function fetch_from_remote( string $style, string $name, string $version, string $type ) {
		// Build CDN URL.
		$endpoint = $this->cdn_endpoints[ $type ];
		$url      = str_replace(
			array( '{version}', '{style}', '{name}' ),
			array( $version, $style, $name ),
			$endpoint
		);

//        echo '###'.$url;
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
	 * Sanitize SVG content for security.
	 *
	 * Strips dangerous elements, attributes, and normalizes IDs.
	 *
	 * @param string $svg Raw SVG content.
	 *
	 * @return string Sanitized SVG.
	 */
	private function sanitize_svg_content( string $svg ): string {
		// Strip dangerous elements.
		$svg = $this->strip_dangerous_elements( $svg );

		// Normalize/remove internal IDs to prevent DOM conflicts.
		$svg = $this->normalize_svg_ids( $svg );

		return $svg;
	}

	/**
	 * Strip dangerous SVG elements and attributes.
	 *
	 * Removes script tags, event handlers, and potentially dangerous references.
	 *
	 * @param string $svg SVG content.
	 *
	 * @return string Cleaned SVG.
	 */
	private function strip_dangerous_elements( string $svg ): string {
		// Remove HTML comments (including Font Awesome attribution comments).
		$svg = preg_replace( '/<!--.*?-->/s', '', $svg );

		// Remove script tags completely.
		$svg = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $svg );

		// Remove dangerous tags.
		$dangerous_tags = array( 'iframe', 'embed', 'object', 'foreignObject' );
		foreach ( $dangerous_tags as $tag ) {
			$svg = preg_replace( '/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $svg );
			$svg = preg_replace( '/<' . $tag . '\b[^>]*\/>/is', '', $svg );
		}

		// Remove event handler attributes (onclick, onload, etc.).
		$svg = preg_replace( '/\s+on\w+\s*=\s*["\'].*?["\']/is', '', $svg );

		// Remove javascript: protocol in attributes.
		$svg = preg_replace( '/\s+href\s*=\s*["\']javascript:.*?["\']/is', '', $svg );

		// Remove data: protocol in href/src (except safe image types).
		$svg = preg_replace( '/\s+(href|src)\s*=\s*["\']data:(?!image\/(png|jpg|jpeg|gif|svg))[^"\']*["\']/is', '', $svg );

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
