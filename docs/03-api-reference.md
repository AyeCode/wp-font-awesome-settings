# API Reference

## Public Methods

### `instance()`

```php
public static function instance(): WP_Font_Awesome_Settings
```

**Description:** Returns the singleton instance of the class.

**Returns:** `WP_Font_Awesome_Settings` - The main instance

**Usage:**
```php
$font_awesome = WP_Font_Awesome_Settings::instance();
```

---

### `get_settings()`

```php
public function get_settings(): array
```

**Description:** Retrieves the current Font Awesome settings.

**Returns:** `array` - Associative array of settings

**Default Values:**
```php
array(
    'type'          => 'CSS',
    'version'       => '',
    'enqueue'       => '',
    'shims'         => '0',
    'js-pseudo'     => '0',
    'dequeue'       => '0',
    'pro'           => '0',
    'local'         => '0',
    'local_version' => '',
    'kit-url'       => '',
)
```

**Usage:**
```php
$settings = WP_Font_Awesome_Settings::instance()->get_settings();
echo $settings['version']; // Current FA version
```

---

### `get_url()`

```php
public function get_url( bool $shims = false, bool $local = true ): string
```

**Description:** Generates the URL to the Font Awesome files (CSS or JS).

**Parameters:**
- `$shims` (bool) - Whether to get the v4-shims file URL. Default: `false`
- `$local` (bool) - Whether to use local files if available. Default: `true`

**Returns:** `string` - Complete URL to the Font Awesome file

**Examples:**
```php
$fa = WP_Font_Awesome_Settings::instance();

// Get main CSS/JS URL
$url = $fa->get_url();
// Returns: https://use.fontawesome.com/releases/v6.4.2/css/all.css?wpfas=true

// Get shims URL
$shims_url = $fa->get_url(true);
// Returns: https://use.fontawesome.com/releases/v6.4.2/css/v4-shims.css?wpfas=true

// Force CDN (skip local)
$cdn_url = $fa->get_url(false, false);
```

---

### `get_latest_version()`

```php
public function get_latest_version( bool $force_api = false, bool $force_latest = false ): string
```

**Description:** Gets the latest Font Awesome version. Uses cached version when available.

**Parameters:**
- `$force_api` (bool) - Force API check instead of using cache. Default: `false`
- `$force_latest` (bool) - Return actual latest (bypass FA7 compatibility hold). Default: `false`

**Returns:** `string` - Version number (e.g., "6.4.2")

**Usage:**
```php
$fa = WP_Font_Awesome_Settings::instance();

// Get cached version
$version = $fa->get_latest_version();

// Force fresh API check
$version = $fa->get_latest_version(true);

// Get true latest (even if FA7+)
$version = $fa->get_latest_version(false, true);
```

**Note:** Results are cached for 48 hours in transient `wp-font-awesome-settings-version`.

---

### `get_path_url()`

```php
public function get_path_url(): string
```

**Description:** Gets the URL path to the library's directory.

**Returns:** `string` - URL to the plugin/theme directory containing the library

**Usage:**
```php
$base_url = WP_Font_Awesome_Settings::instance()->get_path_url();
// Returns: https://example.com/wp-content/plugins/my-plugin/vendor/ayecode/wp-font-awesome-settings/
```

---

### `has_local()`

```php
public function has_local(): bool
```

**Description:** Checks if Font Awesome files are stored locally and available.

**Returns:** `bool` - True if local files exist and are enabled

**Usage:**
```php
if ( WP_Font_Awesome_Settings::instance()->has_local() ) {
    echo 'Loading Font Awesome from local storage';
}
```

---

### `get_fonts_dir()`

```php
public function get_fonts_dir(): string
```

**Description:** Gets the local directory path where Font Awesome files are stored.

**Returns:** `string` - Absolute filesystem path

**Example Return:** `/var/www/html/wp-content/uploads/ayefonts/fa/`

**Usage:**
```php
$dir = WP_Font_Awesome_Settings::instance()->get_fonts_dir();
if ( file_exists( $dir . 'css/all.css' ) ) {
    // Local files exist
}
```

---

### `get_fonts_url()`

```php
public function get_fonts_url(): string
```

**Description:** Gets the URL to the local Font Awesome directory.

**Returns:** `string` - URL path

**Example Return:** `https://example.com/wp-content/uploads/ayefonts/fa/`

**Usage:**
```php
$url = WP_Font_Awesome_Settings::instance()->get_fonts_url();
// Use for building asset URLs
```

---

### `validate_version_number()`

```php
public function validate_version_number( string $version ): string
```

**Description:** Validates a Font Awesome version number.

**Parameters:**
- `$version` (string) - Version number to validate

**Returns:** `string` - Valid version number or empty string if invalid

**Usage:**
```php
$fa = WP_Font_Awesome_Settings::instance();
$version = $fa->validate_version_number('6.4.2'); // Returns '6.4.2'
$version = $fa->validate_version_number('invalid'); // Returns ''
```

---

## Filters

### `wp-font-awesome-settings`

**Description:** Filter the Font Awesome settings array.

**Parameters:**
- `$settings` (array) - Merged settings (database + defaults)
- `$db_settings` (array) - Raw database settings
- `$defaults` (array) - Default settings

**Usage:**
```php
add_filter( 'wp-font-awesome-settings', function( $settings, $db_settings, $defaults ) {
    // Force CSS loading method
    $settings['type'] = 'CSS';

    // Always enable local loading
    $settings['local'] = '1';

    return $settings;
}, 10, 3 );
```

**Location:** `wp-font-awesome-settings.php:414`

---

### `script_loader_tag`

**Description:** Modify Font Awesome script tags (handled internally, but available for extension).

**Usage:**
```php
add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
    if ( $handle === 'font-awesome' ) {
        // Modify the tag
    }
    return $tag;
}, 25, 3 ); // Use priority > 20 to run after library
```

**Location:** `wp-font-awesome-settings.php:1040-1050`

---

### `block_editor_settings_all`

**Description:** Add Font Awesome to Gutenberg/FSE editor (handled internally).

**Location:** `wp-font-awesome-settings.php:219-243`

---

## Actions

### `wp_font_awesome_settings_loaded`

**Description:** Fires after the Font Awesome Settings class is fully loaded.

**Usage:**
```php
add_action( 'wp_font_awesome_settings_loaded', function() {
    // Class is available
    $fa = WP_Font_Awesome_Settings::instance();

    // Check if Pro is enabled
    if ( defined( 'FAS_PRO' ) ) {
        // Pro-specific code
    }
});
```

**Location:** `wp-font-awesome-settings.php:97`

---

### `add_option_wp-font-awesome-settings`

**Description:** Fires when the Font Awesome settings are first added to the database.

**Parameters:**
- `$option` (string) - Option name
- `$value` (array) - Option value

**Usage:**
```php
add_action( 'add_option_wp-font-awesome-settings', function( $option, $value ) {
    error_log( 'Font Awesome settings created: ' . print_r( $value, true ) );
}, 10, 2 );
```

---

### `update_option_wp-font-awesome-settings`

**Description:** Fires when Font Awesome settings are updated.

**Parameters:**
- `$old_value` (array) - Previous settings
- `$value` (array) - New settings

**Usage:**
```php
add_action( 'update_option_wp-font-awesome-settings', function( $old_value, $new_value ) {
    if ( $old_value['version'] !== $new_value['version'] ) {
        error_log( 'Font Awesome version changed from ' . $old_value['version'] . ' to ' . $new_value['version'] );
    }
}, 10, 2 );
```

---

## Constants

### `FAS_ICONPICKER_JS_URL`

**Description:** URL to the Font Awesome icon picker JavaScript file.

**Type:** `string`

**Usage:**
```php
if ( defined( 'FAS_ICONPICKER_JS_URL' ) ) {
    wp_enqueue_script( 'my-iconpicker', FAS_ICONPICKER_JS_URL, array('jquery'), null );
}
```

**Defined:** `wp-font-awesome-settings.php:111-123`

**Values:**
- FA v6+: `{base_url}/assets/js/fa-iconpicker-v6.min.js`
- FA v5: `{base_url}/assets/js/fa-iconpicker-v5.min.js`

---

### `FAS_PRO`

**Description:** Indicates Font Awesome Pro is enabled.

**Type:** `bool` (always `true` when defined)

**Usage:**
```php
if ( defined( 'FAS_PRO' ) && FAS_PRO ) {
    // Use Pro icons
    echo '<i class="fa-solid fa-bat"></i>'; // Pro icon
}
```

**Defined:** `wp-font-awesome-settings.php:126-128`

**Note:** Only defined when Pro is enabled in settings.

---

### `FONTAWESOME_PLUGIN_FILE`

**Description:** Indicates the official Font Awesome plugin is active (not defined by this library).

**Type:** `string`

**Usage:**
```php
if ( defined( 'FONTAWESOME_PLUGIN_FILE' ) ) {
    // Official plugin is active - this library will defer to it
}
```

**Note:** This library checks for this constant and disables itself if found.

---

## Database Options

### Option: `wp-font-awesome-settings`

**Description:** Stores all Font Awesome configuration settings.

**Type:** `array`

**Retrieval:**
```php
$settings = get_option( 'wp-font-awesome-settings' );
```

**Update:**
```php
$settings = get_option( 'wp-font-awesome-settings' );
$settings['version'] = '6.4.2';
update_option( 'wp-font-awesome-settings', $settings );
```

**Schema:** See `get_settings()` method above.

---

## Helper Functions

### Internal Methods (Available but not intended for external use)

#### `enqueue_style()`
Enqueues Font Awesome CSS files. Called automatically by WordPress hooks.

#### `enqueue_scripts()`
Enqueues Font Awesome JavaScript files. Called automatically by WordPress hooks.

#### `remove_font_awesome()`
Filters URLs to remove conflicting Font Awesome versions.

#### `add_generator()`
Adds meta generator tag to HTML head.

#### `rtl_inline_css()`
Returns RTL-specific CSS for icon mirroring.

---

## Code Examples

### Example 1: Check Current Version

```php
add_action( 'wp_font_awesome_settings_loaded', function() {
    $fa = WP_Font_Awesome_Settings::instance();
    $settings = $fa->get_settings();

    $version = $settings['version'] ?: $fa->get_latest_version();
    echo "Using Font Awesome version: " . $version;
});
```

### Example 2: Force Local Loading

```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    // Force local loading for GDPR compliance
    $settings['local'] = '1';
    return $settings;
}, 999 );
```

### Example 3: Detect Font Awesome Pro

```php
function my_theme_has_fa_pro() {
    return defined( 'FAS_PRO' ) && FAS_PRO;
}

if ( my_theme_has_fa_pro() ) {
    // Use Pro features
}
```

### Example 4: Get Icon Picker URL

```php
function my_plugin_enqueue_iconpicker() {
    if ( defined( 'FAS_ICONPICKER_JS_URL' ) ) {
        wp_enqueue_script(
            'my-iconpicker',
            FAS_ICONPICKER_JS_URL,
            array('jquery'),
            null,
            true
        );
    }
}
add_action( 'admin_enqueue_scripts', 'my_plugin_enqueue_iconpicker' );
```

### Example 5: Override Default Settings

```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    // Use JS method by default
    if ( empty( $settings['type'] ) || $settings['type'] === 'CSS' ) {
        $settings['type'] = 'JS';
    }

    // Enable dequeue by default
    $settings['dequeue'] = '1';

    return $settings;
}, 5 ); // Priority 5 to run early
```
