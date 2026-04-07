# Configuration Guide

## Accessing Settings

### Admin Interface

Navigate to **Settings → Font Awesome** in the WordPress admin menu.

**Requirements:**
- User must have `manage_options` capability (typically Administrator role)

---

## Settings Options

### 1. Type

**Description:** Choose how Font Awesome should be loaded.

**Options:**
- **CSS (default)** - Traditional stylesheet method
  - Uses webfonts
  - More compatible with older browsers
  - Slightly faster initial load
  - Best for: General use, simple icon needs

- **JS** - JavaScript/SVG method
  - Uses SVG icons
  - Better performance for many icons
  - Automatic accessibility features
  - Icon layering and transformation support
  - Best for: Advanced icon features, modern sites

- **Kits** - Font Awesome Kits (managed on fontawesome.com)
  - Cloud-based custom configuration
  - Subset icons for smaller file size
  - Advanced features (icon uploading, custom configurations)
  - Requires Font Awesome account (free)
  - Best for: Custom icon sets, optimized performance

**WordPress Settings Field:** `wp-font-awesome-settings[type]`

**Programmatic Access:**
```php
$settings = get_option( 'wp-font-awesome-settings' );
$type = $settings['type']; // 'CSS', 'JS', or 'KIT'
```

---

### 2. Kit URL

**Visible when:** Type = "Kits"

**Description:** Your Font Awesome Kit URL from fontawesome.com.

**Format:** `https://kit.fontawesome.com/{YOUR-KIT-ID}.js`

**How to get a Kit URL:**
1. Create a free account at https://fontawesome.com
2. Go to Kits section
3. Create a new Kit
4. Copy the Kit URL

**Example:** `https://kit.fontawesome.com/a1b2c3d4e5.js`

**WordPress Settings Field:** `wp-font-awesome-settings[kit-url]`

**Notes:**
- Requires a Font Awesome account (free or pro)
- Kit settings (version, icons) managed on fontawesome.com
- Other settings (shims, version) are disabled when using Kits

---

### 3. Version

**Visible when:** Type = "CSS" or "JS"

**Description:** Select which Font Awesome version to load.

**Options:**
- **6.7.2 (default)** - Currently recommended version
- **7.0.0+** - Latest version (if available)
- **6.4.2** - Stable v6 release
- **6.1.0** - Earlier v6 release
- **6.0.0** - First v6 release
- **5.15.4** - Latest v5 release
- **5.6.0** through **5.1.0** - Earlier v5 releases
- **4.7.0 (CSS only)** - Legacy version

**WordPress Settings Field:** `wp-font-awesome-settings[version]`

**Notes:**
- Empty value = Latest compatible version
- Font Awesome 7 support is currently limited pending full compatibility testing
- Font Awesome 4.7.0 only supports CSS method

**Version Compatibility:**

| Version | CSS | JS | Kits | Shims | Pro |
|---------|-----|-----|------|-------|-----|
| 7.0.0+  | ✓   | ✓   | ✓    | ?     | ✓   |
| 6.x     | ✓   | ✓   | ✓    | ✓     | ✓   |
| 5.x     | ✓   | ✓   | ✗    | ✓     | ✓   |
| 4.7.x   | ✓   | ✗   | ✗    | N/A   | ✗   |

---

### 4. Enqueue

**Description:** Choose where Font Awesome should load.

**Options:**
- **Frontend + Backend (default)** - Load everywhere
  - Frontend: public-facing pages
  - Backend: admin area and block editor
  - Best for: General use

- **Frontend** - Public pages only
  - Does not load in admin area
  - Best for: Theme developers, frontend-only needs

- **Backend** - Admin area only
  - Does not load on public pages
  - Best for: Admin-only plugins, dashboard widgets

**WordPress Settings Field:** `wp-font-awesome-settings[enqueue]`

**Values:** `''` (both), `'frontend'`, `'backend'`

**Programmatic Example:**
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    // Load only on frontend
    $settings['enqueue'] = 'frontend';
    return $settings;
});
```

---

### 5. Enable Pro

**Visible when:** Type = "CSS" or "JS"

**Description:** Enable Font Awesome Pro features.

**Requirements:**
- Active Font Awesome Pro subscription
- Domain must be authorized on fontawesome.com account

**Pro Features:**
- 24,000+ icons (vs 2,000+ free)
- Additional icon styles (thin, light, sharp)
- Icon uploading
- Kit customization

**Important:**
- Font Awesome Pro v6+ requires using Kits (Type = "Kits")
- Direct CDN loading only works for Pro v5
- Manage allowed domains at: https://fontawesome.com/account/cdn

**WordPress Settings Field:** `wp-font-awesome-settings[pro]`

**Links:**
- Subscribe: https://fontawesome.com/plans
- Manage domains: https://fontawesome.com/account/cdn

---

### 6. Load Fonts Locally

**Visible when:** Type = "CSS" or "JS" AND Pro = disabled

**Description:** Download and serve Font Awesome files from your own server.

**Benefits:**
- GDPR compliance (no external CDN calls)
- Faster loading in some cases
- Works offline
- No external dependencies

**How it works:**
1. Downloads Font Awesome ZIP from fontawesome.com
2. Extracts to `wp-content/uploads/ayefonts/fa/`
3. Serves files from your domain
4. Auto-updates when you change versions

**Storage Location:** `{uploads}/ayefonts/fa/`

**WordPress Settings Field:** `wp-font-awesome-settings[local]`

**Programmatic Example:**
```php
// Force local loading
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['local'] = '1';
    return $settings;
});
```

**Limitations:**
- Only available for free version
- Requires write permissions to uploads directory
- Uses WordPress filesystem API

**Troubleshooting:**
If local loading fails:
1. Check file permissions on `wp-content/uploads/`
2. Ensure WP_Filesystem is configured correctly
3. Check error messages in settings page
4. Verify server can download from fontawesome.com

---

### 7. Enable v4 Shims Compatibility

**Visible when:** Type = "CSS" or "JS"

**Description:** Enable backward compatibility with Font Awesome v4 icon names.

**Purpose:**
- Allows old v4 class names to work with v5/v6
- Example: `fa-home` instead of `fa-house`

**When to enable:**
- Migrating from Font Awesome v4 to v5/v6
- Using themes/plugins with v4 icon names
- Legacy content with v4 icons

**When to disable:**
- New projects starting with v5/v6
- All code updated to v5/v6 names
- Performance-critical sites (reduces file size)

**File Size Impact:**
- Adds additional CSS/JS file (v4-shims)
- Small performance penalty

**WordPress Settings Field:** `wp-font-awesome-settings[shims]`

**Default:** Disabled (recommended for new projects)

---

### 8. Enable JS Pseudo Elements

**Visible when:** Type = "JS"

**Description:** Allow Font Awesome JavaScript to search for and replace pseudo-elements.

**What are pseudo-elements?**
CSS pseudo-elements like `::before` and `::after` with icon content.

**Example:**
```css
.my-icon::before {
    content: "\f015"; /* Font Awesome home icon */
    font-family: "Font Awesome 6 Free";
}
```

**Warning:** CPU intensive
- Scans entire DOM for pseudo-elements
- Can slow down page rendering
- Not recommended for most sites

**When to enable:**
- CSS-based icons that must work with JS method
- Legacy code that can't be updated

**When to disable:**
- Using Font Awesome normally (recommended)
- Performance is important
- All icons use HTML `<i>` tags

**WordPress Settings Field:** `wp-font-awesome-settings[js-pseudo]`

**Default:** Disabled (recommended)

**Programmatic Example:**
```php
// Enable if absolutely necessary
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    if ( $settings['type'] === 'JS' && my_theme_needs_pseudo_elements() ) {
        $settings['js-pseudo'] = '1';
    }
    return $settings;
});
```

---

### 9. Dequeue

**Description:** Attempt to remove other Font Awesome versions loaded by other plugins/themes.

**Purpose:**
- Prevent conflicts from multiple Font Awesome versions
- Ensure only one version loads

**How it works:**
- Scans all enqueued scripts/styles
- Removes files with "fontawesome" or "font-awesome" in handle/URL
- Keeps only the version configured in this settings page

**When to enable:**
- Multiple plugins loading different FA versions
- Icon display issues (wrong icons, missing icons)
- Console errors about Font Awesome

**When to disable:**
- Only one plugin uses Font Awesome
- No conflicts detected
- Other plugins require their specific FA version

**WordPress Settings Field:** `wp-font-awesome-settings[dequeue]`

**Default:** Disabled

**Compatibility:**
- Works with most plugins/themes
- May not catch all versions (different naming)
- Test thoroughly after enabling

**Programmatic Example:**
```php
// Enable dequeue for conflict resolution
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['dequeue'] = '1';
    return $settings;
});
```

---

## Configuration Scenarios

### Scenario 1: GDPR-Compliant Setup

**Goal:** No external CDN calls

**Configuration:**
- Type: CSS or JS
- Enable Pro: No
- Load Fonts Locally: Yes
- Enqueue: Frontend + Backend

**Result:** All Font Awesome files served from your domain.

---

### Scenario 2: Maximum Performance

**Goal:** Smallest file size, fastest loading

**Configuration:**
- Type: Kits
- Kit URL: (create a Kit with only icons you need)

**Result:** Only icons you use are included, optimized delivery.

---

### Scenario 3: Font Awesome Pro

**Goal:** Access Pro icons

**Configuration (v6+):**
- Type: Kits
- Kit URL: (your Pro Kit URL)

**Configuration (v5):**
- Type: CSS or JS
- Version: 5.15.4 or earlier
- Enable Pro: Yes
- Add domain to fontawesome.com account

---

### Scenario 4: Legacy Site Migration

**Goal:** Upgrade from Font Awesome v4 to v6

**Configuration:**
- Type: CSS or JS
- Version: 6.4.2 (or latest)
- Enable v4 Shims: Yes
- Dequeue: Yes (if other plugins load v4)

**Migration Path:**
1. Enable shims initially
2. Update icon names in your code over time
3. Disable shims once all code is updated

---

### Scenario 5: Backend-Only Plugin

**Goal:** Icons only in WordPress admin

**Configuration:**
- Type: CSS or JS
- Enqueue: Backend
- Version: Latest

**Result:** Font Awesome loads only in admin area, not on frontend.

---

## Programmatic Configuration

### Via Filter

```php
/**
 * Override Font Awesome settings
 */
add_filter( 'wp-font-awesome-settings', function( $settings, $db_settings, $defaults ) {
    // Force specific version
    $settings['version'] = '6.4.2';

    // Always use CSS
    $settings['type'] = 'CSS';

    // Enable local loading
    $settings['local'] = '1';

    // Enable dequeue
    $settings['dequeue'] = '1';

    return $settings;
}, 10, 3 );
```

### Direct Option Update

```php
/**
 * Update settings programmatically
 */
function my_plugin_set_fa_settings() {
    $settings = get_option( 'wp-font-awesome-settings', array() );

    $settings['type'] = 'JS';
    $settings['version'] = '6.4.2';
    $settings['enqueue'] = 'frontend';

    update_option( 'wp-font-awesome-settings', $settings );
}
```

### Constants Override

```php
/**
 * Force settings via wp-config.php or plugin
 */
// Define custom icon picker URL
if ( ! defined( 'FAS_ICONPICKER_JS_URL' ) ) {
    define( 'FAS_ICONPICKER_JS_URL', 'https://cdn.example.com/fa-iconpicker.js' );
}
```

---

## Validation & Errors

### Setting Validation

Settings are validated on save:

1. **Version:** Must be valid version number (x.x.x format)
2. **Kit URL:** Must be valid URL format
3. **Type:** Must be CSS, JS, or KIT
4. **All checkboxes:** Converted to '0' or '1'

### Error Messages

**Font Awesome not loading locally:**
- Check: File permissions
- Check: WP_Filesystem configuration
- Check: Server can reach fontawesome.com
- Solution: See error notice in settings page

**Pro v6 requires Kit:**
- Pro + v6+ combination requires using Kits
- Solution: Switch to Kits type or use v5

**Official Plugin Active:**
- If official Font Awesome plugin is active, this library defers to it
- Solution: Use official plugin settings, or deactivate it

---

## Best Practices

1. **Choose the right type:**
   - CSS: Simple, compatible
   - JS: Advanced features
   - Kits: Optimized, customized

2. **Use local loading for GDPR:**
   - Required in EU if you want to avoid cookie notices
   - Improves privacy

3. **Enable dequeue when needed:**
   - Prevents version conflicts
   - Test thoroughly

4. **Keep Font Awesome updated:**
   - Security fixes
   - New icons
   - Performance improvements

5. **Disable shims eventually:**
   - Update legacy icon names
   - Reduce file size
   - Better performance

6. **Test with official plugin:**
   - If official plugin is installed, this library will not load
   - Ensure compatibility
