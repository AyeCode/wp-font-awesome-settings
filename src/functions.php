<?php
/**
 * Global Helper Functions for Font Awesome Settings
 *
 * Provides convenient helper functions for accessing Font Awesome and custom icon functionality.
 *
 * @package WP_Font_Awesome_Settings
 * @since 2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Global helper function to render Font Awesome icons.
 *
 * Automatically renders icons based on the configured Type setting:
 * - Type = SVG: Outputs inline SVG markup with JIT loading and caching (frontend only)
 * - Type = CSS/JS/KIT: Outputs <i> tag for webfont/JavaScript rendering
 * - Custom icons: Always outputs inline SVG regardless of Type setting
 *
 * Note: This function is intended for frontend use only. The backend loads Font Awesome CSS
 * normally and uses standard <i> tags regardless of the Type setting.
 *
 * Usage:
 *   echo ayecode_get_icon( 'fa-solid fa-user', array(
 *       'class' => 'my-icon',
 *       'aria_label' => 'User Profile',
 *       'width' => '24',
 *       'height' => '24',
 *   ) );
 *
 * @param string $identifier Icon identifier (e.g., 'fa-solid fa-user' or 'aui-icon-logo').
 * @param array  $options    Optional. Rendering options including class, aria_label, width, height, fill, attributes.
 *
 * @return string Icon markup (SVG or <i> tag) or empty string on failure.
 */
function ayecode_get_icon( string $identifier, array $options = array() ): string {
    $settings = WP_Font_Awesome_Settings::instance()->settings;

    // Parse identifier first to check if it's a custom icon
    $parsed = AyeCode_Font_Awesome_SVG_Loader::instance()->parse_identifier( $identifier );

    if ( is_wp_error( $parsed ) ) {
        return '';
    }

    // Custom icons ALWAYS render as SVG regardless of Type setting
    $is_custom = ( $parsed['type'] === 'custom' );
    $use_svg = ( isset( $settings['type'] ) && $settings['type'] === 'SVG' ) || $is_custom;

    // If type is SVG or custom icon, render inline SVG
    if ( $use_svg ) {
        // Add identifier-based class (e.g., aui-icon-solid-user or aui-icon-custom-logo)
        $icon_class = 'aui-icon-' . $parsed['style'] . '-' . $parsed['name'];

        // Merge into existing classes
        if ( ! empty( $options['class'] ) ) {
            if ( is_array( $options['class'] ) ) {
                $options['class'][] = $icon_class;
            } else {
                $options['class'] .= ' ' . $icon_class;
            }
        } else {
            $options['class'] = $icon_class;
        }

        return AyeCode_Font_Awesome_SVG_Loader::instance()->get_inline_icon( $identifier, $options );
    }

    // Otherwise, render as <i> tag for CSS/JS/KIT (Font Awesome icons only)
    // Build <i> tag
    $classes = array();

    // Handle Sharp icons (sharp-solid, sharp-regular) - they need to be split into two classes
    if ( strpos( $parsed['style'], 'sharp-' ) === 0 ) {
        $classes[] = 'fa-sharp';
        $classes[] = 'fa-' . str_replace( 'sharp-', '', $parsed['style'] );
    } else {
        $classes[] = 'fa-' . $parsed['style'];
    }

    $classes[] = 'fa-' . $parsed['name'];

    // Add extra classes from identifier
    if ( ! empty( $parsed['extra_classes'] ) ) {
        $classes = array_merge( $classes, $parsed['extra_classes'] );
    }

    // Add custom classes from options
    if ( ! empty( $options['class'] ) ) {
        if ( is_array( $options['class'] ) ) {
            $classes = array_merge( $classes, $options['class'] );
        } else {
            $classes[] = $options['class'];
        }
    }

    $attrs = array( 'class' => implode( ' ', $classes ) );

    // Add aria attributes
    if ( ! empty( $options['aria_label'] ) ) {
        $attrs['aria-label'] = $options['aria_label'];
        $attrs['role']       = 'img';
    } else {
        $attrs['aria-hidden'] = 'true';
    }

    // Add custom attributes
    if ( ! empty( $options['attributes'] ) && is_array( $options['attributes'] ) ) {
        $attrs = array_merge( $attrs, $options['attributes'] );
    }

    // Build attribute string
    $attr_string = '';
    foreach ( $attrs as $key => $value ) {
        $attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
    }

    return '<i' . $attr_string . '></i>';
}



/**
 * Get all custom icons.
 *
 * Retrieves all custom SVG icons from the filesystem.
 *
 * Usage:
 *   $icons = ayecode_get_custom_icons();
 *   foreach ( $icons as $icon ) {
 *       echo $icon['slug'] . ': ' . $icon['url'];
 *   }
 *
 * @param bool $include_html Whether to include HTML image preview (default: true).
 * @return array Array of custom icons with id, slug, filename, filepath, url, and optionally image HTML.
 */
function ayecode_get_custom_icons( $include_html = true ) {
	return WP_Font_Awesome_Custom_Icons::instance()->get_all_icons( $include_html );
}

/**
 * Get a single custom icon by slug.
 *
 * Usage:
 *   $icon = ayecode_get_custom_icon( 'my-logo' );
 *   if ( $icon ) {
 *       echo '<img src="' . esc_url( $icon['url'] ) . '" />';
 *   }
 *
 * @param string $slug Icon slug (filename without .svg extension).
 * @return array|null Icon data array or null if not found.
 */
function ayecode_get_custom_icon( $slug ) {
	return WP_Font_Awesome_Custom_Icons::instance()->get_icon( $slug );
}

/**
 * Check if a custom icon exists.
 *
 * Usage:
 *   if ( ayecode_custom_icon_exists( 'my-logo' ) ) {
 *       echo ayecode_get_icon( 'aui-icon-my-logo' );
 *   }
 *
 * @param string $slug Icon slug (filename without .svg extension).
 * @return bool True if icon exists, false otherwise.
 */
function ayecode_custom_icon_exists( $slug ) {
	return WP_Font_Awesome_Custom_Icons::instance()->icon_exists( $slug );
}

/**
 * Get total count of custom icons.
 *
 * Usage:
 *   $count = ayecode_get_custom_icon_count();
 *   echo "You have {$count} custom icons uploaded.";
 *
 * @return int Number of custom icons.
 */
function ayecode_get_custom_icon_count() {
	return WP_Font_Awesome_Custom_Icons::instance()->get_icon_count();
}

/**
 * Get all custom icon slugs.
 *
 * Useful for validation, autocomplete, or listing available custom icons.
 *
 * Usage:
 *   $slugs = ayecode_get_custom_icon_slugs();
 *   // Returns: ['my-logo', 'custom-marker', 'brand-icon']
 *
 * @return array Array of icon slugs.
 */
function ayecode_get_custom_icon_slugs() {
	return WP_Font_Awesome_Custom_Icons::instance()->get_icon_slugs();
}

/**
 * Get the custom icons directory path.
 *
 * @return string Directory path with trailing slash.
 */
function ayecode_get_custom_icons_dir() {
	return WP_Font_Awesome_Custom_Icons::instance()->get_custom_icons_dir();
}

/**
 * Get the custom icons directory URL.
 *
 * @return string Directory URL with trailing slash.
 */
function ayecode_get_custom_icons_url() {
	return WP_Font_Awesome_Custom_Icons::instance()->get_custom_icons_url();
}

/**
 * Get the current Font Awesome mode/type setting.
 *
 * Returns the configured Font Awesome loading type: CSS, JS, KIT, or SVG.
 *
 * Usage:
 *   $mode = ayecode_get_fa_mode();
 *   if ( $mode === 'SVG' ) {
 *       echo ayecode_get_icon( 'fa-solid fa-user' );
 *   } else {
 *       echo '<i class="fa-solid fa-user"></i>';
 *   }
 *
 * @return string The Font Awesome type: 'CSS', 'JS', 'KIT', or 'SVG'. Defaults to 'CSS'.
 */
function ayecode_get_fa_mode() {
	$settings = WP_Font_Awesome_Settings::instance()->get_settings();
	return ! empty( $settings['type'] ) ? $settings['type'] : 'CSS';
}

/**
 * Check if Font Awesome is in SVG mode.
 *
 * Usage:
 *   if ( ayecode_is_fa_svg_mode() ) {
 *       echo ayecode_get_icon( 'fa-solid fa-user' );
 *   }
 *
 * @return bool True if SVG mode is enabled, false otherwise.
 */
function ayecode_is_fa_svg_mode() {
	return ayecode_get_fa_mode() === 'SVG';
}

/**
 * Get all Font Awesome settings.
 *
 * Returns the complete settings array including type, version, enqueue, pro, etc.
 *
 * Usage:
 *   $settings = ayecode_get_fa_settings();
 *   echo $settings['version'];
 *   echo $settings['pro'];
 *
 * @return array Complete Font Awesome settings array.
 */
function ayecode_get_fa_settings() {
	return WP_Font_Awesome_Settings::instance()->get_settings();
}

/**
 * Check if Font Awesome Pro is enabled.
 *
 * Usage:
 *   if ( ayecode_is_fa_pro() ) {
 *       echo ayecode_get_icon( 'fa-light fa-user' );
 *   }
 *
 * @return bool True if Pro is enabled, false otherwise.
 */
function ayecode_is_fa_pro() {
	$settings = ayecode_get_fa_settings();
	return ! empty( $settings['pro'] );
}
