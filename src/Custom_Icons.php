<?php
declare(strict_types=1);

/**
 * Custom Icons Helper Class
 *
 * Manages custom SVG icons stored in the filesystem.
 * Provides methods to retrieve, scan, and manage custom icon data.
 *
 * @package WP_Font_Awesome_Settings
 * @since 2.0.0
 */

namespace AyeCode\FontAwesome;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Icons Helper Class
 */
class Custom_Icons {

	/**
	 * Singleton instance.
	 *
	 * @var Custom_Icons|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Custom_Icons
	 */
	public static function instance(): Custom_Icons {
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
	 * Get the custom icons directory path.
	 *
	 * @return string Directory path with trailing slash.
	 */
	public function get_custom_icons_dir(): string {
		$svg_loader = SVG_Loader::instance();
		return $svg_loader->get_icon_cache_dir() . AYECODE_FA_CUSTOM_ICONS_DIR_NAME . DIRECTORY_SEPARATOR;
	}

	/**
	 * Get the custom icons directory URL.
	 *
	 * @return string Directory URL with trailing slash.
	 */
	public function get_custom_icons_url(): string {
		$svg_loader = SVG_Loader::instance();
		return $svg_loader->get_icon_cache_url() . AYECODE_FA_CUSTOM_ICONS_DIR_NAME . '/';
	}

	/**
	 * Get the icon libraries directory path (for JSON files).
	 *
	 * @return string Directory path with trailing slash.
	 */
	public function get_icon_libraries_dir(): string {
		$svg_loader = SVG_Loader::instance();
		return $svg_loader->get_icon_cache_dir() . AYECODE_FA_LIBRARIES_DIR_NAME . DIRECTORY_SEPARATOR;
	}

	/**
	 * Get all custom icons from the filesystem.
	 *
	 * Scans the custom icons directory and returns an array of icon data.
	 *
	 * @param bool $include_html Whether to include HTML image preview (default: true).
	 * @return array Array of custom icons with id, slug, and optionally image HTML.
	 */
	public function get_all_icons( bool $include_html = true ): array {
		$icons = [];

		$custom_dir = $this->get_custom_icons_dir();
		$custom_url = $this->get_custom_icons_url();

		// Check if directory exists.
		if ( ! file_exists( $custom_dir ) || ! is_dir( $custom_dir ) ) {
			return $icons;
		}

		// Scan directory for SVG files.
		$files = scandir( $custom_dir );

		if ( false === $files ) {
			return $icons;
		}

		foreach ( $files as $file ) {
			// Skip directories and non-SVG files.
			if ( '.' === $file || '..' === $file ) {
				continue;
			}

			$filepath = $custom_dir . $file;

			// Only process SVG files.
			if ( ! is_file( $filepath ) || 'svg' !== strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) ) {
				continue;
			}

			// Get filename without extension as the slug/id.
			$slug = pathinfo( $file, PATHINFO_FILENAME );

			$icon = [
				'id'       => sanitize_key( $slug ),
				'slug'     => sanitize_text_field( $slug ),
				'filename' => $file,
				'filepath' => $filepath,
				'url'      => esc_url( $custom_url . $file ),
			];

			// Optionally include HTML image preview.
			if ( $include_html ) {
				$icon['image'] = $this->get_icon_preview_html( $slug, $icon['url'] );
			}

			$icons[] = $icon;
		}

		return $icons;
	}

	/**
	 * Get a single custom icon by slug.
	 *
	 * @param string $slug Icon slug (filename without .svg extension).
	 * @return array|null Icon data array or null if not found.
	 */
	public function get_icon( string $slug ): ?array {
		$custom_dir = $this->get_custom_icons_dir();
		$custom_url = $this->get_custom_icons_url();
		$filename   = sanitize_file_name( $slug ) . '.svg';
		$filepath   = $custom_dir . $filename;

		// Check if file exists.
		if ( ! file_exists( $filepath ) || ! is_file( $filepath ) ) {
			return null;
		}

		return [
			'id'       => sanitize_key( $slug ),
			'slug'     => sanitize_text_field( $slug ),
			'filename' => $filename,
			'filepath' => $filepath,
			'url'      => esc_url( $custom_url . $filename ),
			'image'    => $this->get_icon_preview_html( $slug, esc_url( $custom_url . $filename ) ),
		];
	}

	/**
	 * Check if a custom icon exists.
	 *
	 * @param string $slug Icon slug (filename without .svg extension).
	 * @return bool True if icon exists, false otherwise.
	 */
	public function icon_exists( string $slug ): bool {
		$custom_dir = $this->get_custom_icons_dir();
		$filename   = sanitize_file_name( $slug ) . '.svg';
		$filepath   = $custom_dir . $filename;

		return file_exists( $filepath ) && is_file( $filepath );
	}

	/**
	 * Get total count of custom icons.
	 *
	 * @return int Number of custom icons.
	 */
	public function get_icon_count(): int {
		return count( $this->get_all_icons( false ) );
	}

	/**
	 * Get HTML preview for an icon.
	 *
	 * @param string $slug Icon slug.
	 * @param string $url Icon URL.
	 * @return string HTML img tag.
	 */
	private function get_icon_preview_html( $slug, $url ) {
		return sprintf(
			'<img src="%s" alt="%s" style="max-width: 50px; max-height: 50px; width: 50px;" />',
			esc_url( $url ),
			esc_attr( $slug )
		);
	}

	/**
	 * Get all icon slugs (identifiers).
	 *
	 * Useful for validation or autocomplete features.
	 *
	 * @return array Array of icon slugs.
	 */
	public function get_icon_slugs(): array {
		$icons = $this->get_all_icons( false );
		return array_column( $icons, 'slug' );
	}

	/**
	 * Upload a custom SVG icon.
	 *
	 * Supports both file upload and direct SVG code input.
	 *
	 * @param array|string $file_or_code The file data from $_FILES OR SVG code string.
	 * @param string       $slug         Optional custom slug. If not provided, uses filename without extension.
	 * @param bool         $optimize     Whether to optimize SVG for dynamic UI (default: true).
	 * @param bool         $is_code      Whether $file_or_code is SVG code string (default: false).
	 * @return array|WP_Error Icon data array on success, WP_Error on failure.
	 */
	public function upload_icon( $file_or_code, $slug = '', $optimize = true, $is_code = false ) {
		$svg_content = '';

		if ( $is_code ) {
			// Direct SVG code input
			$svg_content = $file_or_code;

			// Check code size (limit to 5MB equivalent)
			if ( strlen( $svg_content ) > 5 * 1024 * 1024 ) {
				return new \WP_Error( 'code_too_large', __( 'SVG code exceeds 5MB limit.', 'font-awesome-settings' ) );
			}

			// Slug is required for code input
			if ( empty( $slug ) ) {
				return new \WP_Error( 'slug_required', __( 'Icon identifier is required for code input.', 'font-awesome-settings' ) );
			}

		} else {
			// File upload (existing logic)
			$file = $file_or_code;

			// Validate file array.
			if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
				return new \WP_Error( 'invalid_file', __( 'Invalid file upload.', 'font-awesome-settings' ) );
			}

			// Check file size (limit to 5MB).
			if ( $file['size'] > 5 * 1024 * 1024 ) {
				return new \WP_Error( 'file_too_large', __( 'File size exceeds 5MB limit.', 'font-awesome-settings' ) );
			}

			// Validate file extension.
			$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
			if ( 'svg' !== $ext ) {
				return new \WP_Error( 'invalid_extension', __( 'File must be an SVG.', 'font-awesome-settings' ) );
			}

			// Determine slug.
			if ( empty( $slug ) ) {
				$slug = pathinfo( $file['name'], PATHINFO_FILENAME );
			}

			// Read SVG content from file.
			$svg_content = file_get_contents( $file['tmp_name'] );

			if ( false === $svg_content ) {
				return new \WP_Error( 'read_error', __( 'Failed to read uploaded file.', 'font-awesome-settings' ) );
			}
		}

		// Sanitize slug.
		$slug = sanitize_file_name( $slug );

		// Validate slug is not empty after sanitization.
		if ( empty( $slug ) ) {
			return new \WP_Error( 'invalid_slug', __( 'Invalid icon identifier.', 'font-awesome-settings' ) );
		}

		// Check if slug already exists.
		if ( $this->icon_exists( $slug ) ) {
			return new \WP_Error( 'slug_exists', sprintf( __( 'Icon with identifier "%s" already exists.', 'font-awesome-settings' ), $slug ) );
		}

		// Basic SVG validation - check if it contains <svg> tag.
		if ( false === strpos( $svg_content, '<svg' ) ) {
			return new \WP_Error( 'invalid_svg', __( 'Content does not appear to be a valid SVG.', 'font-awesome-settings' ) );
		}

		// Optimize SVG if requested (only for initial add, not updates).
		if ( $optimize ) {
			$svg_content = $this->optimize_svg_for_ui( $svg_content );
		}

		// Sanitize SVG content (security).
		$svg_content = $this->sanitize_svg_content( $svg_content );

		// Check if sanitization failed
		if ( \is_wp_error( $svg_content ) ) {
			return $svg_content;
		}

		// Ensure custom directory exists.
		$custom_dir = $this->get_custom_icons_dir();
		if ( ! file_exists( $custom_dir ) ) {
			if ( ! wp_mkdir_p( $custom_dir ) ) {
				return new \WP_Error( 'directory_error', __( 'Failed to create custom icons directory.', 'font-awesome-settings' ) );
			}

			// Create index.php for security (prevent directory listing)
			$this->create_index_protection( $custom_dir );
		}

		// Save file.
		$filename = $slug . '.svg';
		$filepath = $custom_dir . $filename;

		// Verify path is within custom directory (prevent directory traversal)
		$real_custom_dir = realpath( $custom_dir );
		$real_filepath   = realpath( dirname( $filepath ) );

		if ( false === $real_custom_dir || false === $real_filepath || 0 !== strpos( $real_filepath, $real_custom_dir ) ) {
			return new \WP_Error( 'invalid_path', __( 'Invalid file path detected.', 'font-awesome-settings' ) );
		}

		$result = file_put_contents( $filepath, $svg_content );

		if ( false === $result ) {
			return new \WP_Error( 'save_error', __( 'Failed to save icon file.', 'font-awesome-settings' ) );
		}

		// Generate JSON file.
		$json_result = $this->generate_custom_icons_json();
		if ( \is_wp_error( $json_result ) ) {
			return $json_result;
		}

		// Return icon data in the same format as get_icon().
		return $this->get_icon( $slug );
	}

	/**
	 * Update a custom icon (rename slug only, not the image file).
	 *
	 * @param string $old_slug Current icon slug.
	 * @param string $new_slug New icon slug.
	 * @return array|WP_Error Icon data array on success, WP_Error on failure.
	 */
	public function update_icon( $old_slug, $new_slug ) {
		// Sanitize slugs.
		$old_slug = sanitize_file_name( $old_slug );
		$new_slug = sanitize_file_name( $new_slug );

		// Validate slugs.
		if ( empty( $old_slug ) || empty( $new_slug ) ) {
			return new \WP_Error( 'invalid_slug', __( 'Invalid icon identifier.', 'font-awesome-settings' ) );
		}

		// Check if old icon exists.
		if ( ! $this->icon_exists( $old_slug ) ) {
			return new \WP_Error( 'not_found', sprintf( __( 'Icon "%s" not found.', 'font-awesome-settings' ), $old_slug ) );
		}

		// If slug hasn't changed, just return the icon.
		if ( $old_slug === $new_slug ) {
			return $this->get_icon( $old_slug );
		}

		// Check if new slug already exists.
		if ( $this->icon_exists( $new_slug ) ) {
			return new \WP_Error( 'slug_exists', sprintf( __( 'Icon with identifier "%s" already exists.', 'font-awesome-settings' ), $new_slug ) );
		}

		// Get file paths.
		$custom_dir = $this->get_custom_icons_dir();
		$old_filepath = $custom_dir . $old_slug . '.svg';
		$new_filepath = $custom_dir . $new_slug . '.svg';

		// Verify paths are within custom directory (prevent directory traversal)
		$real_custom_dir  = realpath( $custom_dir );
		$real_old_dir     = realpath( dirname( $old_filepath ) );
		$real_new_dir     = realpath( dirname( $new_filepath ) );

		if ( false === $real_custom_dir || false === $real_old_dir || 0 !== strpos( $real_old_dir, $real_custom_dir ) ) {
			return new \WP_Error( 'invalid_path', __( 'Invalid file path detected.', 'font-awesome-settings' ) );
		}

		// New file directory check (dirname might not exist yet)
		if ( false !== $real_new_dir && 0 !== strpos( $real_new_dir, $real_custom_dir ) ) {
			return new \WP_Error( 'invalid_path', __( 'Invalid destination path detected.', 'font-awesome-settings' ) );
		}

		// Rename the file.
		$result = rename( $old_filepath, $new_filepath );

		if ( ! $result ) {
			return new \WP_Error( 'rename_error', __( 'Failed to rename icon file.', 'font-awesome-settings' ) );
		}

		// Clear object cache for old slug.
		wp_cache_delete( 'ayecode_icon_custom_' . $old_slug, 'ayecode_icons' );

		// Generate JSON file.
		$json_result = $this->generate_custom_icons_json();
		if ( \is_wp_error( $json_result ) ) {
			return $json_result;
		}

		// Return updated icon data.
		return $this->get_icon( $new_slug );
	}

	/**
	 * Delete a custom icon.
	 *
	 * @param string $slug Icon slug (filename without .svg extension).
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_icon( $slug ) {
		$custom_dir = $this->get_custom_icons_dir();
		$filename   = sanitize_file_name( $slug ) . '.svg';
		$filepath   = $custom_dir . $filename;

		// Check if file exists.
		if ( ! file_exists( $filepath ) || ! is_file( $filepath ) ) {
			return new \WP_Error( 'not_found', __( 'Icon file not found.', 'font-awesome-settings' ) );
		}

		// Verify path is within custom directory (prevent directory traversal)
		$real_custom_dir = realpath( $custom_dir );
		$real_filepath   = realpath( $filepath );

		if ( false === $real_custom_dir || false === $real_filepath || 0 !== strpos( $real_filepath, $real_custom_dir ) ) {
			return new \WP_Error( 'invalid_path', __( 'Invalid file path detected.', 'font-awesome-settings' ) );
		}

		// Delete the file.
		$result = unlink( $filepath );

		if ( ! $result ) {
			return new \WP_Error( 'delete_error', __( 'Failed to delete icon file.', 'font-awesome-settings' ) );
		}

		// Clear object cache for this icon.
		wp_cache_delete( 'ayecode_icon_custom_' . $slug, 'ayecode_icons' );

		// Generate JSON file.
		$json_result = $this->generate_custom_icons_json();
		if ( \is_wp_error( $json_result ) ) {
			return $json_result;
		}

		return true;
	}

	/**
	 * Sanitize SVG content using enshrined/svg-sanitize library.
	 *
	 * Uses battle-tested external library for comprehensive SVG sanitization
	 * to prevent XSS attacks and other security vulnerabilities.
	 *
	 * @param string $svg SVG content.
	 * @return string|WP_Error Sanitized SVG or WP_Error on failure.
	 */
	private function sanitize_svg_content( $svg ) {
		// Use enshrined/svg-sanitize library for secure sanitization
		$sanitizer = new \enshrined\svgSanitize\Sanitizer();
		$sanitizer->removeRemoteReferences( true );

		$sanitized_svg = $sanitizer->sanitize( $svg );

		if ( false === $sanitized_svg || empty( $sanitized_svg ) ) {
			return new \WP_Error( 'svg_sanitization_failed', __( 'Failed to sanitize SVG content. The SVG may contain malicious code.', 'font-awesome-settings' ) );
		}

		return $sanitized_svg;
	}

	/**
	 * Optimize SVG for dynamic UI usage.
	 *
	 * Removes fixed colors and dimensions so the icon inherits text color and is responsive.
	 * This makes the icon suitable for use in dynamic UIs where colors should adapt.
	 *
	 * Optimizations:
	 * - Detects if icon is stroke-based or fill-based
	 * - For stroke icons: replaces stroke colors with "currentColor", removes fills
	 * - For fill icons: replaces fill colors with "currentColor", removes strokes
	 * - Removes width/height from <svg> tag
	 * - Removes style attributes with color/dimension properties
	 * - Preserves viewBox for proper scaling
	 *
	 * @param string $svg SVG content to optimize.
	 * @return string Optimized SVG.
	 */
	private function optimize_svg_for_ui( $svg ) {
		// Detect if this is primarily a stroke-based icon
		// Count actual color values, ignoring "none"
		$has_stroke_color = preg_match( '/\sstroke\s*=\s*["\'](?!none)([^"\']+)["\']/i', $svg );
		$has_fill_color = preg_match( '/\sfill\s*=\s*["\'](?!none)([^"\']+)["\']/i', $svg );

		// If it has stroke color but no fill color (or only fill="none"), it's stroke-based
		// If it has fill color but no stroke color (or only stroke="none"), it's fill-based
		$is_stroke_based = $has_stroke_color && ! $has_fill_color;

		if ( $is_stroke_based ) {
			// Stroke-based icon: keep strokes, preserve fill="none"
			// Replace stroke colors with currentColor
			$svg = preg_replace( '/\s+stroke\s*=\s*["\'](?!none)([^"\']+)["\']/i', ' stroke="currentColor"', $svg );

			// Remove fill attributes except fill="none"
			$svg = preg_replace( '/\s+fill\s*=\s*["\']((?!none)[^"\']+)["\']/', '', $svg );
		} else {
			// Fill-based icon: keep fills, remove strokes
			// Replace fill colors with currentColor (not "none")
			$svg = preg_replace( '/\s+fill\s*=\s*["\'](?!none)([^"\']+)["\']/', ' fill="currentColor"', $svg );

			// Remove all stroke attributes (including stroke="none")
			$svg = preg_replace( '/\s+stroke\s*=\s*["\'][^"\']*["\']/i', '', $svg );
		}

		// Remove width and height from opening <svg> tag only (not nested elements)
		$svg = preg_replace( '/(<svg[^>]*)\s+(width|height)\s*=\s*["\'][^"\']*["\']/i', '$1', $svg );

		// Remove style attributes that contain color or dimension properties
		$svg = preg_replace_callback(
			'/\s+style\s*=\s*["\']([^"\']*)["\']/',
			function( $matches ) use ( $is_stroke_based ) {
				$style = $matches[1];
				// Remove color-related and dimension properties, but preserve structure
				if ( $is_stroke_based ) {
					// For stroke icons, replace stroke colors with currentColor
					$style = preg_replace( '/stroke\s*:\s*[^;]+;?/i', 'stroke:currentColor;', $style );
					$style = preg_replace( '/fill\s*:\s*[^;]+;?/i', '', $style );
				} else {
					// For fill icons, replace fill colors with currentColor
					$style = preg_replace( '/fill\s*:\s*[^;]+;?/i', 'fill:currentColor;', $style );
					$style = preg_replace( '/stroke\s*:\s*[^;]+;?/i', '', $style );
				}
				$style = preg_replace( '/(width|height)\s*:[^;]+;?/i', '', $style );
				$style = trim( $style, '; ' );
				// Only keep style attribute if there's content left
				return $style ? ' style="' . $style . '"' : '';
			},
			$svg
		);

		// Ensure <svg> tag has the appropriate currentColor attribute
		if ( preg_match( '/<svg[^>]*>/i', $svg, $svg_tag ) ) {
			$tag = $svg_tag[0];

			if ( $is_stroke_based ) {
				// Add stroke="currentColor" if not present
				if ( ! preg_match( '/\sstroke\s*=/i', $tag ) ) {
					$tag = str_replace( '<svg', '<svg stroke="currentColor"', $tag );
					$svg = str_replace( $svg_tag[0], $tag, $svg );
				}
			} else {
				// Add fill="currentColor" if not present
				if ( ! preg_match( '/\sfill\s*=/i', $tag ) ) {
					$tag = str_replace( '<svg', '<svg fill="currentColor"', $tag );
					$svg = str_replace( $svg_tag[0], $tag, $svg );
				}
			}
		}

		return $svg;
	}

	/**
	 * Generate custom-icons.json file with all custom icons metadata.
	 *
	 * Creates/updates the JSON file at: wp-content/uploads/ayecode-icon-cache/icons-libraries/custom-icons.json
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function generate_custom_icons_json() {
		// Get libraries directory.
		$libraries_dir = $this->get_icon_libraries_dir();

		// Ensure directory exists.
		if ( ! file_exists( $libraries_dir ) ) {
			if ( ! wp_mkdir_p( $libraries_dir ) ) {
				return new \WP_Error( 'directory_error', __( 'Failed to create icons-libraries directory.', 'font-awesome-settings' ) );
			}
			// Add index.php protection to cache and libraries directories.
			$cache_dir = dirname( $libraries_dir );
			$this->create_index_protection( $cache_dir );
			$this->create_index_protection( $libraries_dir );
		}

		// Get all custom icons (without HTML).
		$icons = $this->get_all_icons( false );

		// Build icons array with just slug and filename.
		$icons_list = [];
		foreach ( $icons as $icon ) {
			$icons_list[] = [
				'slug' => $icon['slug'],
				'file' => $icon['filename'],
			];
		}

		// Build JSON structure.
		$json_data = [
			'prefix'     => 'aui-icon-',
			'icon-style' => 'custom',
			'list-icon'  => 'aui-icon-custom',
			'base-path'  => AYECODE_FA_CACHE_DIR_NAME . '/' . AYECODE_FA_CUSTOM_ICONS_DIR_NAME,
			'icons'      => $icons_list,
		];

		// Encode JSON with pretty print.
		$json_content = wp_json_encode( $json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === $json_content ) {
			return new \WP_Error( 'json_encode_error', __( 'Failed to encode JSON data.', 'font-awesome-settings' ) );
		}

		// Write JSON file.
		$json_filepath = $libraries_dir . AYECODE_FA_CUSTOM_ICONS_JSON_FILENAME;
		$result = file_put_contents( $json_filepath, $json_content );

		if ( false === $result ) {
			return new \WP_Error( 'file_write_error', sprintf( __( 'Failed to write %s file.', 'font-awesome-settings' ), AYECODE_FA_CUSTOM_ICONS_JSON_FILENAME ) );
		}

		return true;
	}

	/**
	 * Create index.php protection file in a directory.
	 *
	 * Prevents direct directory listing and PHP execution by adding an index.php file.
	 * More compatible across servers than .htaccess.
	 *
	 * @param string $directory Directory path with trailing slash.
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
