# Architecture Documentation

## Class Structure

### Main Class: `WP_Font_Awesome_Settings`

The library consists of a single main class that follows the Singleton design pattern.

#### Class Properties

```php
public $version = '1.1.10';           // Library version
public $textdomain = 'font-awesome-settings'; // Localization textdomain
public $latest = "6.4.2";             // Latest known FA version
public $name = 'Font Awesome';        // Display name
private $settings;                     // Runtime settings array
private static $instance = null;       // Singleton instance
```

### Design Patterns

#### Singleton Pattern
The class implements the Singleton pattern via the `instance()` method to ensure only one instance exists across the WordPress installation.

**Why Singleton?**
- Prevents multiple plugins/themes from creating conflicting instances
- Ensures consistent settings across the entire site
- Reduces memory overhead

#### Lazy Loading
Font Awesome files are only enqueued when needed, based on configuration.

## File Structure

```
wp-font-awesome-settings/
├── wp-font-awesome-settings.php  # Main class file
├── assets/
│   └── js/
│       ├── fa-iconpicker-v5.js   # Icon picker for FA v5
│       ├── fa-iconpicker-v6.js   # Icon picker for FA v6
│       └── *.min.js              # Minified versions
├── build/
│   ├── build_font_awesome_array.php  # Build tool for icon arrays
│   ├── spyc.php                      # YAML parser
│   ├── v5.yml                        # FA v5 icon metadata
│   └── v6.yml                        # FA v6 icon metadata
├── composer.json                 # Composer configuration
└── README.md                     # Basic readme
```

## WordPress Integration Points

### Hooks

#### Actions (Registered)

**Initialization Hooks:**
- `init` (priority: default) → `init()` - Initialize settings
- `admin_menu` → `menu_item()` - Add settings page
- `admin_init` → `register_settings()` - Register options
- `admin_init` → `constants()` - Define constants

**Enqueue Hooks:**
- `wp_enqueue_scripts` (priority: 5000) → `enqueue_style()` or `enqueue_scripts()`
- `admin_enqueue_scripts` (priority: 5000) → `enqueue_style()` or `enqueue_scripts()`

**Header Hooks:**
- `wp_head` (priority: 99) → `add_generator()` - Add meta tag
- `admin_head` (priority: 99) → `add_generator()` - Add meta tag

**Option Hooks:**
- `add_option_wp-font-awesome-settings` → `add_option_wp_font_awesome_settings()` - Handle new option
- `update_option_wp-font-awesome-settings` → `update_option_wp_font_awesome_settings()` - Handle option update

**Notice Hooks:**
- `admin_notices` → `admin_notices()` - Display admin warnings

#### Filters (Registered)

- `script_loader_tag` (priority: 20) → `script_loader_tag()` - Modify script tags
- `clean_url` (priority: 5000) → `remove_font_awesome()` - Remove conflicting versions
- `block_editor_settings_all` → `enqueue_editor_styles()` or `enqueue_editor_scripts()` - FSE support
- `wp-font-awesome-settings` → Applied to settings array (available for extension)

### Custom Actions (Triggered)

- `wp_font_awesome_settings_loaded` - Fired after class instantiation

## Data Flow

### Settings Initialization Flow

```
WordPress Loads
    ↓
Composer Autoload
    ↓
WP_Font_Awesome_Settings::instance()
    ↓
Hook Registration (admin_menu, admin_init, etc.)
    ↓
'init' action fires
    ↓
get_settings() - Load from database
    ↓
Apply 'wp-font-awesome-settings' filter
    ↓
Settings available for use
```

### Font Loading Flow

**CSS Method:**
```
Enqueue hook fires
    ↓
enqueue_style() called
    ↓
get_url() builds Font Awesome URL
    ↓
Check if local loading enabled
    ↓
has_local() checks for local files
    ↓
If local: serve from uploads/ayefonts/fa/
    ↓
If remote: serve from FontAwesome CDN
    ↓
wp_enqueue_style() registers stylesheet
    ↓
Optional: Add RTL inline styles
    ↓
Optional: Add v4 shims stylesheet
```

**JavaScript Method:**
```
Enqueue hook fires
    ↓
enqueue_scripts() called
    ↓
get_url() builds Font Awesome URL
    ↓
wp_enqueue_script() registers script
    ↓
script_loader_tag filter adds attributes
    ↓
Attributes: defer, crossorigin="anonymous"
    ↓
Optional: data-search-pseudo-elements
```

### Local Font Download Flow

```
Settings Saved
    ↓
update_option_wp-font-awesome-settings fires
    ↓
Check if 'local' enabled
    ↓
download_package($version) called
    ↓
download_url() fetches ZIP from fontawesome.com
    ↓
extract_package() unzips to temp directory
    ↓
WP_Filesystem moves files to uploads/ayefonts/fa/
    ↓
Update 'local_version' in settings
    ↓
Cleanup temp files
```

### Version Detection Flow

```
get_latest_version() called
    ↓
Check transient 'wp-font-awesome-settings-version'
    ↓
If cached (< 48 hours): Return cached version
    ↓
If not cached or forced:
    ↓
get_latest_version_from_api()
    ↓
GitHub API call: /repos/FortAwesome/Font-Awesome/releases/latest
    ↓
Parse JSON response for 'tag_name'
    ↓
Validate version number
    ↓
Set 48-hour transient cache
    ↓
Return version
```

## Database Schema

### Options Table

**Option Name:** `wp-font-awesome-settings`

**Data Structure:**
```php
array(
    'type'          => 'CSS'|'JS'|'KIT',    // Loading method
    'version'       => '',                   // FA version (empty = latest)
    'enqueue'       => ''|'frontend'|'backend', // Where to load
    'shims'         => '0'|'1',             // v4 compatibility
    'js-pseudo'     => '0'|'1',             // JS pseudo-elements
    'dequeue'       => '0'|'1',             // Remove other versions
    'pro'           => '0'|'1',             // Enable Pro
    'local'         => '0'|'1',             // Store locally
    'local_version' => '',                   // Local files version
    'kit-url'       => '',                   // Kit URL if type=KIT
)
```

### Transients

**Transient Name:** `wp-font-awesome-settings-version`
**Duration:** 48 hours
**Purpose:** Cache the latest Font Awesome version from GitHub API

## Constants Defined

### `FAS_ICONPICKER_JS_URL`
**Purpose:** URL to the icon picker JavaScript file
**Scope:** Global
**Value:** Depends on Font Awesome version
- FA 6+: `{plugin_url}/assets/js/fa-iconpicker-v6.min.js`
- FA 5: `{plugin_url}/assets/js/fa-iconpicker-v5.min.js`

### `FAS_PRO`
**Purpose:** Indicates Font Awesome Pro is enabled
**Scope:** Global
**Value:** `true` (only defined when Pro is enabled)

## File System Integration

### Upload Directory Structure

```
wp-content/uploads/
└── ayefonts/
    ├── fa/                    # Font Awesome files
    │   ├── css/
    │   │   ├── all.css
    │   │   └── all.min.css
    │   ├── js/
    │   │   ├── all.js
    │   │   └── all.min.js
    │   └── webfonts/
    │       └── *.woff2, *.ttf, etc.
    └── fa-tmp/                # Temporary extraction (auto-deleted)
```

### WP_Filesystem Usage

The library uses WordPress's `WP_Filesystem` API for all file operations:
- Creating directories
- Moving files
- Deleting files/directories
- File existence checks

**Filesystem Method Support:**
- Direct filesystem access (preferred)
- FTP access (if credentials defined)

## Security Considerations

### Capability Checks
- Settings page: `manage_options` capability required
- All admin operations verify user permissions

### Input Sanitization
- URLs: `esc_url_raw()`, `sanitize_text_field()`
- Output: `esc_attr()`, `esc_html()`, `wp_sprintf()`

### Nonce Verification
- WordPress settings API handles nonce verification automatically

### Safe External Requests
- Uses `wp_remote_get()` for API calls
- Validates API responses before use
- Error handling for failed requests

## Performance Optimizations

1. **Version Caching**: 48-hour transient cache for GitHub API calls
2. **High Priority Enqueue**: Priority 5000 to load early and prevent conflicts
3. **Conditional Loading**: Only loads on frontend/backend as configured
4. **Deferred JavaScript**: Scripts loaded with `defer` attribute
5. **Minified Files**: Serves `.min.css` and `.min.js` for local files
