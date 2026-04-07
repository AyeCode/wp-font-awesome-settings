# Developer Guide

## Installation & Integration

### Via Composer (Recommended)

```bash
composer require ayecode/wp-font-awesome-settings
```

**Composer.json Configuration:**
```json
{
  "require": {
    "ayecode/wp-font-awesome-settings": "^1.1"
  }
}
```

### Manual Installation

1. Download the library from GitHub
2. Place in your plugin/theme directory
3. Include the main file:

```php
require_once plugin_dir_path( __FILE__ ) . 'vendor/ayecode/wp-font-awesome-settings/wp-font-awesome-settings.php';
```

### Autoloading

The library uses Composer autoloading:

```json
{
  "autoload": {
    "files": ["wp-font-awesome-settings.php"]
  }
}
```

---

## Plugin Integration

### Basic Plugin Example

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Description: A plugin using Font Awesome
 * Version: 1.0.0
 */

// Include Composer autoloader
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Font Awesome is now available
add_action( 'wp_font_awesome_settings_loaded', function() {
    // Your code here - Font Awesome will be loaded automatically
});

// Use Font Awesome icons in your plugin
function my_plugin_display_icon() {
    echo '<i class="fas fa-heart"></i> Like this!';
}
```

### Plugin with Custom Configuration

```php
<?php
/**
 * Plugin Name: My Pro Plugin
 * Description: A plugin requiring specific Font Awesome settings
 */

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Set specific Font Awesome configuration for your plugin
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    // Ensure JS loading for advanced features
    if ( $settings['type'] !== 'KIT' ) {
        $settings['type'] = 'JS';
    }

    // Enable dequeue to prevent conflicts
    $settings['dequeue'] = '1';

    return $settings;
}, 10 );

// Check if Pro is available
function my_plugin_init() {
    if ( defined( 'FAS_PRO' ) ) {
        // Use Pro icons
        add_action( 'admin_notices', 'my_plugin_pro_notice' );
    }
}
add_action( 'wp_font_awesome_settings_loaded', 'my_plugin_init' );

function my_plugin_pro_notice() {
    echo '<div class="notice notice-success"><p>Font Awesome Pro icons available!</p></div>';
}
```

---

## Theme Integration

### Basic Theme Example

```php
<?php
/**
 * functions.php
 */

// Include via Composer
require_once get_template_directory() . '/vendor/autoload.php';

// Configure Font Awesome for your theme
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    // Use CSS method
    $settings['type'] = 'CSS';

    // Load on frontend only
    $settings['enqueue'] = 'frontend';

    // Use latest version
    $settings['version'] = '';

    return $settings;
}, 5 ); // Low priority to allow child themes to override

// Use in your theme
function mytheme_display_social_icons() {
    ?>
    <div class="social-icons">
        <a href="#"><i class="fab fa-facebook"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
    </div>
    <?php
}
```

### Theme with Icon Picker

```php
<?php
/**
 * Add icon picker to WordPress customizer
 */

function mytheme_customize_register( $wp_customize ) {
    // Wait for Font Awesome to load
    if ( ! defined( 'FAS_ICONPICKER_JS_URL' ) ) {
        return;
    }

    $wp_customize->add_setting( 'mytheme_header_icon', array(
        'default' => 'fas fa-home',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control( 'mytheme_header_icon', array(
        'label' => __( 'Header Icon', 'mytheme' ),
        'section' => 'title_tagline',
        'type' => 'text',
        'input_attrs' => array(
            'class' => 'fa-iconpicker',
        ),
    ));
}
add_action( 'customize_register', 'mytheme_customize_register' );

function mytheme_customize_controls_enqueue_scripts() {
    if ( defined( 'FAS_ICONPICKER_JS_URL' ) ) {
        wp_enqueue_script( 'fa-iconpicker', FAS_ICONPICKER_JS_URL, array('jquery'), null, true );
    }
}
add_action( 'customize_controls_enqueue_scripts', 'mytheme_customize_controls_enqueue_scripts' );
```

---

## Advanced Usage

### Checking Font Awesome Status

```php
function my_plugin_check_fa_status() {
    // Check if class exists
    if ( ! class_exists( 'WP_Font_Awesome_Settings' ) ) {
        return 'not_installed';
    }

    // Check if official plugin is active
    if ( defined( 'FONTAWESOME_PLUGIN_FILE' ) ) {
        return 'official_plugin';
    }

    // Get instance
    $fa = WP_Font_Awesome_Settings::instance();

    // Check if Pro is enabled
    if ( defined( 'FAS_PRO' ) ) {
        return 'pro';
    }

    // Check if local loading
    if ( $fa->has_local() ) {
        return 'local';
    }

    return 'cdn';
}

// Usage
$status = my_plugin_check_fa_status();
switch ( $status ) {
    case 'not_installed':
        // Handle: Font Awesome not available
        break;
    case 'official_plugin':
        // Handle: Official plugin is active
        break;
    case 'pro':
        // Handle: Pro features available
        break;
    case 'local':
        // Handle: Loading locally
        break;
    case 'cdn':
        // Handle: Loading from CDN
        break;
}
```

### Dynamic Version Selection

```php
/**
 * Force specific version based on browser
 */
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    // Detect old browsers and use v5 instead of v6
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if ( strpos( $user_agent, 'MSIE' ) !== false || strpos( $user_agent, 'Trident' ) !== false ) {
        // Internet Explorer - use v5
        $settings['version'] = '5.15.4';
        $settings['type'] = 'CSS'; // CSS more compatible
    }

    return $settings;
});
```

### Conditional Enqueuing

```php
/**
 * Load Font Awesome only where needed
 */
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    // Get current page
    global $post;

    // Only load on specific post types
    if ( is_singular( array( 'product', 'portfolio' ) ) ) {
        $settings['enqueue'] = 'frontend';
    } elseif ( is_admin() ) {
        $settings['enqueue'] = 'backend';
    } else {
        // Don't load Font Awesome on other pages
        // This doesn't actually prevent loading in this version
        // Better to use wp_dequeue_style/script in this case
    }

    return $settings;
});
```

### Programmatic Local Font Management

```php
/**
 * Manage local fonts programmatically
 */
class My_Plugin_FA_Manager {
    private $fa;

    public function __construct() {
        add_action( 'wp_font_awesome_settings_loaded', array( $this, 'init' ) );
    }

    public function init() {
        $this->fa = WP_Font_Awesome_Settings::instance();
    }

    /**
     * Check if local fonts are available
     */
    public function has_local_fonts() {
        return $this->fa->has_local();
    }

    /**
     * Get local fonts directory
     */
    public function get_fonts_path() {
        return $this->fa->get_fonts_dir();
    }

    /**
     * Get local fonts URL
     */
    public function get_fonts_url() {
        return $this->fa->get_fonts_url();
    }

    /**
     * Force download specific version
     */
    public function download_version( $version ) {
        // Get current settings
        $settings = get_option( 'wp-font-awesome-settings', array() );

        // Enable local loading
        $settings['local'] = '1';
        $settings['version'] = $version;

        // Update settings (this will trigger download)
        update_option( 'wp-font-awesome-settings', $settings );
    }
}

// Initialize
new My_Plugin_FA_Manager();
```

---

## Icon Picker Integration

### Admin Settings Page with Icon Picker

```php
/**
 * Add admin page with Font Awesome icon picker
 */
class My_Plugin_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function add_menu() {
        add_options_page(
            'My Plugin Settings',
            'My Plugin',
            'manage_options',
            'my-plugin-settings',
            array( $this, 'settings_page' )
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'settings_page_my-plugin-settings' ) {
            return;
        }

        // Enqueue icon picker
        if ( defined( 'FAS_ICONPICKER_JS_URL' ) ) {
            wp_enqueue_script( 'fa-iconpicker', FAS_ICONPICKER_JS_URL, array( 'jquery' ), null, true );

            // Initialize icon picker
            wp_add_inline_script( 'fa-iconpicker', '
                jQuery(document).ready(function($) {
                    $(".fa-iconpicker").iconpicker();
                });
            ' );
        }
    }

    public function settings_page() {
        $icon = get_option( 'my_plugin_icon', 'fas fa-star' );
        ?>
        <div class="wrap">
            <h1>My Plugin Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'my-plugin-settings' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="my_plugin_icon">Choose Icon</label></th>
                        <td>
                            <input type="text"
                                   id="my_plugin_icon"
                                   name="my_plugin_icon"
                                   value="<?php echo esc_attr( $icon ); ?>"
                                   class="fa-iconpicker"
                                   data-placement="bottom" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new My_Plugin_Settings();

// Register setting
add_action( 'admin_init', function() {
    register_setting( 'my-plugin-settings', 'my_plugin_icon', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
});
```

### Frontend Icon Display

```php
/**
 * Display selected icon on frontend
 */
function my_plugin_display_icon() {
    $icon = get_option( 'my_plugin_icon', 'fas fa-star' );
    return sprintf( '<i class="%s"></i>', esc_attr( $icon ) );
}

// Usage in template
echo my_plugin_display_icon();
```

---

## Debugging & Troubleshooting

### Debug Information

```php
/**
 * Display Font Awesome debug information
 */
function my_plugin_fa_debug_info() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $fa = WP_Font_Awesome_Settings::instance();
    $settings = $fa->get_settings();

    echo '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; font-family: monospace;">';
    echo '<h3>Font Awesome Debug Info</h3>';

    echo '<strong>Library Version:</strong> ' . $fa->version . '<br>';
    echo '<strong>Font Awesome Version:</strong> ' . ( $settings['version'] ?: $fa->get_latest_version() ) . '<br>';
    echo '<strong>Loading Method:</strong> ' . $settings['type'] . '<br>';
    echo '<strong>Enqueue:</strong> ' . ( $settings['enqueue'] ?: 'Both' ) . '<br>';
    echo '<strong>Pro Enabled:</strong> ' . ( $settings['pro'] ? 'Yes' : 'No' ) . '<br>';
    echo '<strong>Local Loading:</strong> ' . ( $settings['local'] ? 'Yes' : 'No' ) . '<br>';

    if ( $settings['local'] ) {
        echo '<strong>Local Files Exist:</strong> ' . ( $fa->has_local() ? 'Yes' : 'No' ) . '<br>';
        echo '<strong>Local Version:</strong> ' . $settings['local_version'] . '<br>';
        echo '<strong>Local Path:</strong> ' . $fa->get_fonts_dir() . '<br>';
    }

    echo '<strong>Main URL:</strong> ' . $fa->get_url() . '<br>';

    if ( defined( 'FAS_ICONPICKER_JS_URL' ) ) {
        echo '<strong>Icon Picker URL:</strong> ' . FAS_ICONPICKER_JS_URL . '<br>';
    }

    if ( defined( 'FAS_PRO' ) ) {
        echo '<strong>FAS_PRO Constant:</strong> Defined<br>';
    }

    if ( defined( 'FONTAWESOME_PLUGIN_FILE' ) ) {
        echo '<strong>Official Plugin:</strong> Active<br>';
    }

    echo '<h4>Settings Array:</h4>';
    echo '<pre>' . print_r( $settings, true ) . '</pre>';

    echo '</div>';
}

// Add to admin page
add_action( 'admin_notices', 'my_plugin_fa_debug_info' );
```

### Common Issues & Solutions

#### Issue: Icons not displaying

**Possible Causes:**
1. Font Awesome not loading
2. Wrong icon class names
3. CSS conflicts

**Solution:**
```php
// Check if Font Awesome is enqueued
add_action( 'wp_footer', function() {
    global $wp_scripts, $wp_styles;

    // Check scripts
    if ( isset( $wp_scripts->registered['font-awesome'] ) ) {
        echo '<!-- FA Script: Registered -->';
    }
    if ( in_array( 'font-awesome', $wp_scripts->queue ) ) {
        echo '<!-- FA Script: Enqueued -->';
    }

    // Check styles
    if ( isset( $wp_styles->registered['font-awesome'] ) ) {
        echo '<!-- FA Style: Registered -->';
    }
    if ( in_array( 'font-awesome', $wp_styles->queue ) ) {
        echo '<!-- FA Style: Enqueued -->';
    }
}, 999 );
```

#### Issue: Multiple versions loading

**Solution:**
```php
// Enable dequeue
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['dequeue'] = '1';
    return $settings;
}, 999 );

// Or manually dequeue specific versions
add_action( 'wp_enqueue_scripts', function() {
    wp_dequeue_style( 'other-plugin-fontawesome' );
    wp_dequeue_script( 'other-plugin-fontawesome' );
}, 100 );
```

#### Issue: Local files not downloading

**Solution:**
```php
// Check filesystem access
function my_plugin_check_filesystem() {
    $fa = WP_Font_Awesome_Settings::instance();
    $wp_filesystem = $fa->get_wp_filesystem();

    if ( ! $wp_filesystem ) {
        echo 'ERROR: WP_Filesystem not available';
        return false;
    }

    $fonts_dir = $fa->get_fonts_dir();
    $parent_dir = dirname( $fonts_dir );

    if ( ! $wp_filesystem->is_writable( $parent_dir ) ) {
        echo 'ERROR: Directory not writable: ' . $parent_dir;
        return false;
    }

    return true;
}
```

---

## Testing

### Unit Testing Example

```php
<?php
/**
 * PHPUnit tests for Font Awesome integration
 */
class Test_Font_Awesome_Integration extends WP_UnitTestCase {

    public function test_class_exists() {
        $this->assertTrue( class_exists( 'WP_Font_Awesome_Settings' ) );
    }

    public function test_instance() {
        $fa = WP_Font_Awesome_Settings::instance();
        $this->assertInstanceOf( 'WP_Font_Awesome_Settings', $fa );
    }

    public function test_settings_default() {
        $fa = WP_Font_Awesome_Settings::instance();
        $settings = $fa->get_settings();

        $this->assertEquals( 'CSS', $settings['type'] );
        $this->assertEquals( '', $settings['version'] );
        $this->assertEquals( '0', $settings['shims'] );
    }

    public function test_get_url() {
        $fa = WP_Font_Awesome_Settings::instance();
        $url = $fa->get_url();

        $this->assertStringContainsString( 'fontawesome.com', $url );
        $this->assertStringContainsString( 'wpfas=true', $url );
    }

    public function test_version_validation() {
        $fa = WP_Font_Awesome_Settings::instance();

        $this->assertEquals( '6.4.2', $fa->validate_version_number( '6.4.2' ) );
        $this->assertEquals( '', $fa->validate_version_number( 'invalid' ) );
    }

    public function test_filter() {
        add_filter( 'wp-font-awesome-settings', function( $settings ) {
            $settings['type'] = 'JS';
            return $settings;
        });

        $fa = WP_Font_Awesome_Settings::instance();
        $settings = $fa->get_settings();

        $this->assertEquals( 'JS', $settings['type'] );
    }
}
```

---

## Performance Optimization

### Lazy Loading Icons

```php
/**
 * Load Font Awesome only when needed
 */
function my_theme_conditional_fa_loading() {
    // Check if page uses Font Awesome
    global $post;

    if ( ! is_singular() ) {
        return;
    }

    // Check if content has Font Awesome icons
    if ( strpos( $post->post_content, 'class="fa' ) === false ) {
        // No icons found - dequeue Font Awesome
        add_action( 'wp_enqueue_scripts', function() {
            wp_dequeue_style( 'font-awesome' );
            wp_dequeue_script( 'font-awesome' );
        }, 9999 );
    }
}
add_action( 'wp', 'my_theme_conditional_fa_loading' );
```

### Subset Loading with Kits

Use Font Awesome Kits to load only icons you need:

1. Create Kit at fontawesome.com
2. Select only icons you use
3. Configure in settings: Type = "Kits"

---

## Best Practices

1. **Always check if class exists**
   ```php
   if ( class_exists( 'WP_Font_Awesome_Settings' ) ) {
       // Use Font Awesome
   }
   ```

2. **Use the `wp_font_awesome_settings_loaded` action**
   ```php
   add_action( 'wp_font_awesome_settings_loaded', 'my_plugin_init' );
   ```

3. **Don't assume Pro is available**
   ```php
   if ( defined( 'FAS_PRO' ) ) {
       // Use Pro features
   }
   ```

4. **Use filters with appropriate priority**
   ```php
   // Low priority = runs early = can be overridden
   add_filter( 'wp-font-awesome-settings', 'my_filter', 5 );

   // High priority = runs late = hard to override
   add_filter( 'wp-font-awesome-settings', 'my_filter', 999 );
   ```

5. **Sanitize icon class names**
   ```php
   $icon = sanitize_text_field( $_POST['icon'] );
   // Validate it's a valid FA class
   if ( ! preg_match( '/^fa[bsrl]? fa-[\w-]+$/', $icon ) ) {
       $icon = 'fas fa-star'; // fallback
   }
   ```

6. **Handle missing Font Awesome gracefully**
   ```php
   if ( ! class_exists( 'WP_Font_Awesome_Settings' ) ) {
       add_action( 'admin_notices', function() {
           echo '<div class="notice notice-error"><p>My Plugin requires Font Awesome Settings library.</p></div>';
       });
       return;
   }
   ```
