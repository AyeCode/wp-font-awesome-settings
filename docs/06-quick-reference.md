# Quick Reference Guide

Quick reference for common tasks and code snippets.

---

## Installation

### Composer
```bash
composer require ayecode/wp-font-awesome-settings
```

### Manual Include
```php
require_once plugin_dir_path( __FILE__ ) . 'vendor/ayecode/wp-font-awesome-settings/wp-font-awesome-settings.php';
```

---

## Getting Started

### Get Instance
```php
$fa = WP_Font_Awesome_Settings::instance();
```

### Check if Loaded
```php
add_action( 'wp_font_awesome_settings_loaded', function() {
    // Font Awesome is ready
});
```

---

## Settings

### Get All Settings
```php
$settings = WP_Font_Awesome_Settings::instance()->get_settings();
```

### Update Settings
```php
$settings = get_option( 'wp-font-awesome-settings', array() );
$settings['type'] = 'JS';
$settings['version'] = '6.4.2';
update_option( 'wp-font-awesome-settings', $settings );
```

### Filter Settings
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['type'] = 'CSS';
    $settings['local'] = '1';
    return $settings;
});
```

---

## Version Management

### Get Current Version
```php
$fa = WP_Font_Awesome_Settings::instance();
$settings = $fa->get_settings();
$version = $settings['version'] ?: $fa->get_latest_version();
```

### Get Latest Version
```php
$version = WP_Font_Awesome_Settings::instance()->get_latest_version();
```

### Force API Check
```php
$version = WP_Font_Awesome_Settings::instance()->get_latest_version( true );
```

---

## URLs and Paths

### Get Font Awesome URL
```php
$url = WP_Font_Awesome_Settings::instance()->get_url();
```

### Get Shims URL
```php
$url = WP_Font_Awesome_Settings::instance()->get_url( true );
```

### Get Local Fonts Path
```php
$path = WP_Font_Awesome_Settings::instance()->get_fonts_dir();
```

### Get Local Fonts URL
```php
$url = WP_Font_Awesome_Settings::instance()->get_fonts_url();
```

---

## Feature Detection

### Check if Local Fonts Available
```php
if ( WP_Font_Awesome_Settings::instance()->has_local() ) {
    // Local fonts are available
}
```

### Check if Pro Enabled
```php
if ( defined( 'FAS_PRO' ) && FAS_PRO ) {
    // Font Awesome Pro is enabled
}
```

### Check if Official Plugin Active
```php
if ( defined( 'FONTAWESOME_PLUGIN_FILE' ) ) {
    // Official Font Awesome plugin is active
}
```

---

## Icon Picker

### Get Icon Picker URL
```php
if ( defined( 'FAS_ICONPICKER_JS_URL' ) ) {
    $url = FAS_ICONPICKER_JS_URL;
}
```

### Enqueue Icon Picker
```php
add_action( 'admin_enqueue_scripts', function() {
    if ( defined( 'FAS_ICONPICKER_JS_URL' ) ) {
        wp_enqueue_script( 'fa-iconpicker', FAS_ICONPICKER_JS_URL, array('jquery'), null, true );
    }
});
```

### Initialize Icon Picker
```php
// JavaScript
jQuery(document).ready(function($) {
    $('.fa-iconpicker').iconpicker();
});
```

### HTML Input
```html
<input type="text" class="fa-iconpicker" value="fas fa-home" />
```

---

## Common Configurations

### GDPR Compliant (Local Loading)
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['local'] = '1';
    return $settings;
});
```

### Force CSS Method
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['type'] = 'CSS';
    return $settings;
});
```

### Frontend Only
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['enqueue'] = 'frontend';
    return $settings;
});
```

### Enable Dequeue (Prevent Conflicts)
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['dequeue'] = '1';
    return $settings;
});
```

### Specific Version
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['version'] = '6.4.2';
    return $settings;
});
```

---

## Hooks

### Actions

#### Library Loaded
```php
add_action( 'wp_font_awesome_settings_loaded', function() {
    // Code here
});
```

#### Settings Added
```php
add_action( 'add_option_wp-font-awesome-settings', function( $option, $value ) {
    // Code here
}, 10, 2 );
```

#### Settings Updated
```php
add_action( 'update_option_wp-font-awesome-settings', function( $old_value, $new_value ) {
    // Code here
}, 10, 2 );
```

### Filters

#### Modify Settings
```php
add_filter( 'wp-font-awesome-settings', function( $settings, $db_settings, $defaults ) {
    // Modify $settings
    return $settings;
}, 10, 3 );
```

#### Modify Script Tag
```php
add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
    if ( $handle === 'font-awesome' ) {
        // Modify $tag
    }
    return $tag;
}, 25, 3 );
```

---

## Constants

### Define Custom Icon Picker URL
```php
if ( ! defined( 'FAS_ICONPICKER_JS_URL' ) ) {
    define( 'FAS_ICONPICKER_JS_URL', 'https://example.com/fa-iconpicker.js' );
}
```

### Check Constants
```php
// Icon Picker
if ( defined( 'FAS_ICONPICKER_JS_URL' ) ) { }

// Pro
if ( defined( 'FAS_PRO' ) ) { }

// Official Plugin
if ( defined( 'FONTAWESOME_PLUGIN_FILE' ) ) { }
```

---

## Debugging

### Basic Debug Info
```php
$fa = WP_Font_Awesome_Settings::instance();
$settings = $fa->get_settings();

echo '<pre>';
echo 'Version: ' . $fa->version . "\n";
echo 'FA Version: ' . ( $settings['version'] ?: $fa->get_latest_version() ) . "\n";
echo 'Type: ' . $settings['type'] . "\n";
echo 'Local: ' . ( $fa->has_local() ? 'Yes' : 'No' ) . "\n";
echo 'Pro: ' . ( defined('FAS_PRO') ? 'Yes' : 'No' ) . "\n";
echo '</pre>';
```

### Check if Font Awesome Enqueued
```php
add_action( 'wp_footer', function() {
    global $wp_scripts, $wp_styles;

    // Scripts
    if ( wp_script_is( 'font-awesome', 'enqueued' ) ) {
        echo '<!-- FA Script: Enqueued -->';
    }

    // Styles
    if ( wp_style_is( 'font-awesome', 'enqueued' ) ) {
        echo '<!-- FA Style: Enqueued -->';
    }
}, 999 );
```

### Display Settings Array
```php
echo '<pre>' . print_r( WP_Font_Awesome_Settings::instance()->get_settings(), true ) . '</pre>';
```

---

## Icon Usage

### Basic Icon
```html
<i class="fas fa-home"></i>
```

### Different Styles
```html
<i class="fas fa-home"></i>  <!-- Solid -->
<i class="far fa-home"></i>  <!-- Regular -->
<i class="fal fa-home"></i>  <!-- Light (Pro) -->
<i class="fab fa-facebook"></i>  <!-- Brands -->
```

### Icon Sizes
```html
<i class="fas fa-home fa-xs"></i>
<i class="fas fa-home fa-sm"></i>
<i class="fas fa-home fa-lg"></i>
<i class="fas fa-home fa-2x"></i>
<i class="fas fa-home fa-3x"></i>
```

### Fixed Width
```html
<i class="fas fa-home fa-fw"></i>
```

---

## Validation

### Validate Version Number
```php
$fa = WP_Font_Awesome_Settings::instance();
$version = $fa->validate_version_number( '6.4.2' );
// Returns '6.4.2' if valid, '' if invalid
```

### Validate Icon Class
```php
function validate_fa_icon( $icon ) {
    // Basic validation
    if ( ! preg_match( '/^fa[bsrl]? fa-[\w-]+$/', $icon ) ) {
        return 'fas fa-star'; // Default fallback
    }
    return sanitize_text_field( $icon );
}
```

---

## Plugin Integration Pattern

### Complete Plugin Example
```php
<?php
/**
 * Plugin Name: My Plugin
 */

// Include library
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Wait for library to load
add_action( 'wp_font_awesome_settings_loaded', 'my_plugin_init' );

function my_plugin_init() {
    // Configure Font Awesome
    add_filter( 'wp-font-awesome-settings', 'my_plugin_fa_config' );
}

function my_plugin_fa_config( $settings ) {
    $settings['type'] = 'CSS';
    $settings['dequeue'] = '1';
    return $settings;
}

// Use Font Awesome
function my_plugin_display_icon() {
    return '<i class="fas fa-star"></i>';
}
```

---

## Theme Integration Pattern

### Complete Theme Example
```php
<?php
/**
 * functions.php
 */

// Include library
require_once get_template_directory() . '/vendor/autoload.php';

// Configure for theme
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['enqueue'] = 'frontend';
    $settings['type'] = 'CSS';
    return $settings;
}, 5 );

// Use in templates
function mytheme_icon( $icon = 'fas fa-home' ) {
    return sprintf( '<i class="%s"></i>', esc_attr( $icon ) );
}
```

---

## Settings Field Reference

| Field | Type | Values | Default |
|-------|------|--------|---------|
| type | string | CSS, JS, KIT | CSS |
| version | string | version number or empty | empty (latest) |
| enqueue | string | empty, frontend, backend | empty (both) |
| shims | string | 0, 1 | 0 |
| js-pseudo | string | 0, 1 | 0 |
| dequeue | string | 0, 1 | 0 |
| pro | string | 0, 1 | 0 |
| local | string | 0, 1 | 0 |
| local_version | string | version number | empty |
| kit-url | string | URL or empty | empty |

---

## Priority Reference

| Hook | Recommended Priority | Why |
|------|---------------------|-----|
| wp-font-awesome-settings filter | 10 | Standard |
| wp-font-awesome-settings filter (theme) | 5 | Allow child theme override |
| wp-font-awesome-settings filter (plugin) | 10-20 | Standard to late |
| wp_enqueue_scripts | 5000 | Late (FA uses this) |
| admin_enqueue_scripts | 5000 | Late (FA uses this) |

---

## File Locations

| Path | Purpose |
|------|---------|
| wp-font-awesome-settings.php | Main class file |
| assets/js/fa-iconpicker-v5.js | Icon picker for FA v5 |
| assets/js/fa-iconpicker-v6.js | Icon picker for FA v6 |
| build/ | Build tools for icon arrays |
| uploads/ayefonts/fa/ | Local font storage |
| uploads/ayefonts/fa-tmp/ | Temporary extraction |

---

## Version Support Matrix

| FA Version | CSS | JS | Kits | Shims | Pro | Local |
|------------|-----|-----|------|-------|-----|-------|
| 7.0.0+ | ✓ | ✓ | ✓ | ? | ✓ | ✓ |
| 6.x | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 5.x | ✓ | ✓ | ✗ | ✓ | ✓ | ✓ |
| 4.7.x | ✓ | ✗ | ✗ | N/A | ✗ | ✓ |

---

## Error Handling

### Check Class Exists
```php
if ( ! class_exists( 'WP_Font_Awesome_Settings' ) ) {
    // Handle: Library not loaded
    return;
}
```

### Check Method Exists
```php
if ( method_exists( $fa, 'get_latest_version' ) ) {
    $version = $fa->get_latest_version();
}
```

### Try-Catch for Downloads
```php
try {
    $response = WP_Font_Awesome_Settings::instance()->download_package( '6.4.2' );
    if ( is_wp_error( $response ) ) {
        error_log( 'FA Download Error: ' . $response->get_error_message() );
    }
} catch ( Exception $e ) {
    error_log( 'FA Exception: ' . $e->getMessage() );
}
```

---

## Performance Tips

1. **Use Kits for subset loading**
2. **Enable local loading** for faster repeated access
3. **Dequeue unused versions**
4. **Load only where needed** (frontend vs backend)
5. **Use CSS over JS** if you don't need JS features
6. **Disable pseudo-elements** (CPU intensive)
7. **Disable shims** if not needed

---

## Security Tips

1. **Sanitize icon inputs**
   ```php
   $icon = sanitize_text_field( $_POST['icon'] );
   ```

2. **Validate icon classes**
   ```php
   if ( ! preg_match( '/^fa[bsrl]? fa-[\w-]+$/', $icon ) ) {
       $icon = 'fas fa-star'; // Safe default
   }
   ```

3. **Check capabilities**
   ```php
   if ( ! current_user_can( 'manage_options' ) ) {
       return;
   }
   ```

4. **Escape output**
   ```php
   echo '<i class="' . esc_attr( $icon ) . '"></i>';
   ```

---

This quick reference provides instant access to the most commonly used code snippets and patterns.
