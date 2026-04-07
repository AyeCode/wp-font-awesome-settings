# Troubleshooting Guide

Common issues and their solutions.

---

## Icons Not Displaying

### Symptom
Icons appear as empty squares or text codes like `\f015`.

### Possible Causes

1. **Font Awesome not loading**
2. **Wrong icon class names**
3. **CSS conflicts**
4. **Mixed Font Awesome versions**

### Solutions

#### Check if Font Awesome is Loaded

**Method 1: View Page Source**
```html
<!-- Look for lines like these -->
<link rel="stylesheet" href="...fontawesome.../css/all.css">
<script src="...fontawesome.../js/all.js"></script>
```

**Method 2: Browser Console**
```javascript
// Open browser console (F12) and type:
document.querySelectorAll('link[href*="fontawesome"]')
document.querySelectorAll('script[src*="fontawesome"]')
```

**Method 3: WordPress Debug**
```php
add_action( 'wp_footer', function() {
    global $wp_scripts, $wp_styles;
    echo '<!-- FA Enqueued: ';
    echo wp_style_is('font-awesome', 'enqueued') ? 'Style Yes, ' : 'Style No, ';
    echo wp_script_is('font-awesome', 'enqueued') ? 'Script Yes' : 'Script No';
    echo ' -->';
}, 999 );
```

#### Verify Icon Class Names

**FA v6 uses different names than v4:**
```html
<!-- ✗ Wrong (FA v4) -->
<i class="fa fa-home"></i>

<!-- ✓ Correct (FA v6) -->
<i class="fas fa-home"></i>
```

**Check style prefix:**
- `fas` = Solid (free)
- `far` = Regular (free)
- `fab` = Brands (free)
- `fal` = Light (Pro only)
- `fat` = Thin (Pro only)

#### Enable v4 Shims

If you have old v4 icon names:
1. Go to Settings → Font Awesome
2. Enable "v4 Shims Compatibility"
3. Save settings

Or programmatically:
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['shims'] = '1';
    return $settings;
});
```

#### Check for CSS Conflicts

**Look for CSS that might hide icons:**
```css
/* Bad CSS that breaks icons */
i { display: none; }
i::before { content: ''; }
* { font-family: Arial !important; }
```

**Fix: Add more specific CSS:**
```css
i.fas, i.far, i.fab {
    font-family: "Font Awesome 6 Free" !important;
    font-style: normal;
}
```

---

## Multiple Font Awesome Versions Loading

### Symptom
- Icons display incorrectly
- Console errors about Font Awesome
- Different icons on different pages

### Diagnosis

**Check loaded versions:**
```javascript
// Browser console
Array.from(document.querySelectorAll('link[href*="font"]')).map(l => l.href);
Array.from(document.querySelectorAll('script[src*="font"]')).map(s => s.src);
```

### Solutions

#### Enable Dequeue Option

**Via Settings:**
1. Go to Settings → Font Awesome
2. Check "Dequeue"
3. Save settings

**Programmatically:**
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['dequeue'] = '1';
    return $settings;
}, 999 ); // High priority
```

#### Manually Dequeue Specific Handles

**Find the conflicting handle:**
```php
add_action( 'wp_print_styles', function() {
    global $wp_styles;
    foreach ( $wp_styles->queue as $handle ) {
        $src = $wp_styles->registered[$handle]->src ?? '';
        if ( strpos( $src, 'fontawesome' ) !== false || strpos( $src, 'font-awesome' ) !== false ) {
            error_log( 'FA Style Handle: ' . $handle );
        }
    }
}, 999 );
```

**Dequeue it:**
```php
add_action( 'wp_enqueue_scripts', function() {
    wp_dequeue_style( 'other-plugin-fontawesome' );
    wp_deregister_style( 'other-plugin-fontawesome' );
}, 100 );
```

---

## Local Fonts Not Loading

### Symptom
Settings show "Font Awesome fonts are not loading locally!" error.

### Possible Causes

1. **File permissions**
2. **WP_Filesystem not configured**
3. **Download failed**
4. **Server firewall blocking fontawesome.com**

### Solutions

#### Check File Permissions

```php
// Add to theme functions.php temporarily
add_action( 'admin_notices', function() {
    $fa = WP_Font_Awesome_Settings::instance();
    $fonts_dir = $fa->get_fonts_dir();
    $parent_dir = dirname( $fonts_dir );

    echo '<div class="notice notice-info"><p>';
    echo 'Fonts Dir: ' . $fonts_dir . '<br>';
    echo 'Exists: ' . ( file_exists( $fonts_dir ) ? 'Yes' : 'No' ) . '<br>';
    echo 'Writable: ' . ( is_writable( $parent_dir ) ? 'Yes' : 'No' ) . '<br>';
    echo 'CSS File: ' . ( file_exists( $fonts_dir . 'css/all.css' ) ? 'Yes' : 'No' ) . '<br>';
    echo '</p></div>';
});
```

**Fix permissions:**
```bash
# SSH/Terminal
cd wp-content/uploads
chmod 755 ayefonts
chmod -R 644 ayefonts/fa/*
find ayefonts/fa -type d -exec chmod 755 {} \;
```

#### Configure WP_Filesystem

**Add to wp-config.php if using FTP:**
```php
define('FTP_HOST', 'ftp.example.com');
define('FTP_USER', 'username');
define('FTP_PASS', 'password');
```

#### Manual Download

If automatic download fails:

1. Download Font Awesome from https://fontawesome.com/download
2. Extract the ZIP file
3. Upload contents to: `wp-content/uploads/ayefonts/fa/`
4. Ensure this structure:
   ```
   ayefonts/fa/
   ├── css/
   │   ├── all.css
   │   └── all.min.css
   ├── js/
   │   ├── all.js
   │   └── all.min.js
   └── webfonts/
   ```

#### Check Server Connection

```php
// Test if server can reach fontawesome.com
$response = wp_remote_get( 'https://use.fontawesome.com/releases/v6.4.2/fontawesome-free-6.4.2-web.zip' );
if ( is_wp_error( $response ) ) {
    echo 'Error: ' . $response->get_error_message();
} else {
    echo 'Status: ' . wp_remote_retrieve_response_code( $response );
}
```

---

## Font Awesome Pro Not Working

### Symptom
Pro icons not displaying even though Pro is enabled.

### Solutions

#### For Font Awesome v6 Pro

**Font Awesome v6 Pro requires Kits:**

1. Create a Kit at https://fontawesome.com/kits
2. In Settings → Font Awesome:
   - Type: Kits
   - Kit URL: (your kit URL)
3. Save settings

#### For Font Awesome v5 Pro

**Configure CDN Pro:**

1. Add domain at https://fontawesome.com/account/cdn
2. In Settings → Font Awesome:
   - Type: CSS or JS
   - Version: 5.15.4 (or earlier v5)
   - Enable Pro: Yes
3. Save settings

#### Check Domain Authorization

**Ensure your domain is authorized:**
1. Log into fontawesome.com
2. Go to Account → CDN
3. Add your domain (e.g., example.com)
4. Wait a few minutes for propagation

---

## Icon Picker Not Working

### Symptom
Icon picker doesn't initialize or shows errors.

### Solutions

#### Check Constant is Defined

```php
add_action( 'admin_notices', function() {
    if ( ! defined( 'FAS_ICONPICKER_JS_URL' ) ) {
        echo '<div class="notice notice-error"><p>FAS_ICONPICKER_JS_URL not defined!</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Icon Picker URL: ' . FAS_ICONPICKER_JS_URL . '</p></div>';
    }
});
```

#### Verify Script Enqueued

```php
add_action( 'admin_footer', function() {
    if ( wp_script_is( 'fa-iconpicker', 'enqueued' ) ) {
        echo '<!-- Icon Picker: Enqueued -->';
    } else {
        echo '<!-- Icon Picker: NOT Enqueued -->';
    }
}, 999 );
```

#### Check jQuery Dependency

Icon picker requires jQuery:

```php
wp_enqueue_script( 'fa-iconpicker', FAS_ICONPICKER_JS_URL, array('jquery'), null, true );
//                                                                    ^^^^^^^^ Required!
```

#### Initialize Properly

```javascript
jQuery(document).ready(function($) {
    // Check if iconpicker method exists
    if ( typeof $.fn.iconpicker === 'function' ) {
        $('.fa-iconpicker').iconpicker();
    } else {
        console.error('Icon picker not loaded');
    }
});
```

---

## Settings Not Saving

### Symptom
Changes in Settings → Font Awesome don't persist.

### Solutions

#### Check User Capabilities

```php
if ( ! current_user_can( 'manage_options' ) ) {
    // User cannot save settings
}
```

#### Check for Filter Overrides

```php
// Check if a filter is overriding settings
add_action( 'admin_notices', function() {
    $db_settings = get_option( 'wp-font-awesome-settings' );
    $runtime_settings = WP_Font_Awesome_Settings::instance()->get_settings();

    if ( $db_settings !== $runtime_settings ) {
        echo '<div class="notice notice-warning"><p>Settings are being filtered!</p></div>';
    }
});
```

#### Verify Option Updates

```php
// Add this temporarily to see if option is updating
add_action( 'update_option_wp-font-awesome-settings', function( $old, $new ) {
    error_log( 'FA Settings Updated: ' . print_r( $new, true ) );
}, 10, 2 );
```

#### Clear Object Cache

If using object caching:

```php
wp_cache_delete( 'wp-font-awesome-settings', 'options' );
```

---

## Official Font Awesome Plugin Conflict

### Symptom
Message: "The Official Font Awesome Plugin is active, please adjust your settings there."

### Explanation
This library defers to the official Font Awesome plugin if it's active.

### Solutions

#### Option 1: Use Official Plugin
1. Keep official plugin active
2. Configure it via Settings → Font Awesome (official plugin's settings)
3. This library will not interfere

#### Option 2: Deactivate Official Plugin
1. Go to Plugins → Installed Plugins
2. Deactivate "Font Awesome"
3. This library will take over
4. Configure via Settings → Font Awesome

#### Check Which is Active

```php
if ( defined( 'FONTAWESOME_PLUGIN_FILE' ) ) {
    echo 'Official plugin is active';
} else {
    echo 'This library is active';
}
```

---

## Version Not Updating

### Symptom
Latest version not showing or old version stuck.

### Solutions

#### Force Version Check

1. Visit: `Settings → Font Awesome → Add ?force-version-check=1 to URL`
   - Example: `https://example.com/wp-admin/options-general.php?page=wp-font-awesome-settings&force-version-check=1`

Or programmatically:
```php
// Force fresh API check
$version = WP_Font_Awesome_Settings::instance()->get_latest_version( true );
```

#### Clear Transient Cache

```php
delete_transient( 'wp-font-awesome-settings-version' );
```

#### Check GitHub API Access

```php
$response = wp_remote_get( 'https://api.github.com/repos/FortAwesome/Font-Awesome/releases/latest' );
if ( is_wp_error( $response ) ) {
    echo 'Error: ' . $response->get_error_message();
} else {
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    echo 'Latest Version: ' . $data['tag_name'];
}
```

---

## RTL (Right-to-Left) Issues

### Symptom
Icons facing wrong direction in RTL languages.

### Solution

RTL support is automatic when `is_rtl()` returns true.

**Verify RTL is detected:**
```php
if ( is_rtl() ) {
    echo 'RTL mode: Active';
}
```

**Manual RTL CSS:**
```css
[dir=rtl] .fa-arrow-right {
    transform: scaleX(-1);
}
```

---

## Performance Issues

### Symptom
Slow page loading with Font Awesome enabled.

### Solutions

#### Use Kits with Subset

1. Create Kit at fontawesome.com
2. Select only icons you use
3. Configure: Type = Kits

#### Disable Pseudo-Elements

If using JS method:
1. Settings → Font Awesome
2. Uncheck "Enable JS pseudo elements"

Or:
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['js-pseudo'] = '0';
    return $settings;
});
```

#### Load Locally

```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    $settings['local'] = '1';
    return $settings;
});
```

#### Conditional Loading

Only load where needed:
```php
add_filter( 'wp-font-awesome-settings', function( $settings ) {
    if ( ! is_singular() ) {
        // Dequeue on archive pages
        // Note: This doesn't actually work in this version
        // Use wp_dequeue instead
    }
    return $settings;
});

// Better approach:
add_action( 'wp_enqueue_scripts', function() {
    if ( ! is_singular() ) {
        wp_dequeue_style( 'font-awesome' );
    }
}, 9999 );
```

---

## JavaScript Console Errors

### Common Errors

#### "Font Awesome not found"
**Cause:** Script not loading
**Fix:** Check enqueue settings and verify script loads

#### "CORB blocked cross-origin response"
**Cause:** Missing CORS headers
**Fix:** Automatically handled by library (adds `crossorigin="anonymous"`)

#### "Failed to decode downloaded font"
**Cause:** Incomplete font file download
**Fix:** Clear browser cache, re-download local fonts

---

## WP-CLI Debugging

### Check Settings
```bash
wp option get wp-font-awesome-settings
```

### Update Settings
```bash
wp option update wp-font-awesome-settings --format=json '{"type":"CSS","local":"1"}'
```

### Clear Cache
```bash
wp transient delete wp-font-awesome-settings-version
wp cache flush
```

---

## Debug Mode

### Enable WordPress Debug

Add to wp-config.php:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Font Awesome Debug Function

```php
function fa_debug() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $fa = WP_Font_Awesome_Settings::instance();
    $settings = $fa->get_settings();

    echo '<div style="background:#f5f5f5;padding:20px;margin:20px;font-family:monospace;">';
    echo '<h3>Font Awesome Debug</h3>';
    echo '<strong>Class Exists:</strong> ' . ( class_exists('WP_Font_Awesome_Settings') ? 'Yes' : 'No' ) . '<br>';
    echo '<strong>Version:</strong> ' . $fa->version . '<br>';
    echo '<strong>FA Version:</strong> ' . ( $settings['version'] ?: $fa->get_latest_version() ) . '<br>';
    echo '<strong>Type:</strong> ' . $settings['type'] . '<br>';
    echo '<strong>Enqueue:</strong> ' . ( $settings['enqueue'] ?: 'Both' ) . '<br>';
    echo '<strong>Local:</strong> ' . ( $fa->has_local() ? 'Yes' : 'No' ) . '<br>';
    echo '<strong>Pro:</strong> ' . ( defined('FAS_PRO') ? 'Yes' : 'No' ) . '<br>';
    echo '<strong>Official Plugin:</strong> ' . ( defined('FONTAWESOME_PLUGIN_FILE') ? 'Active' : 'Not Active' ) . '<br>';
    echo '<strong>Icon Picker:</strong> ' . ( defined('FAS_ICONPICKER_JS_URL') ? 'Available' : 'Not Available' ) . '<br>';
    echo '<strong>Main URL:</strong> ' . $fa->get_url() . '<br>';

    global $wp_scripts, $wp_styles;
    echo '<strong>Style Enqueued:</strong> ' . ( wp_style_is('font-awesome','enqueued') ? 'Yes' : 'No' ) . '<br>';
    echo '<strong>Script Enqueued:</strong> ' . ( wp_script_is('font-awesome','enqueued') ? 'Yes' : 'No' ) . '<br>';

    echo '<h4>Settings:</h4><pre>' . print_r($settings, true) . '</pre>';
    echo '</div>';
}
add_action( 'admin_notices', 'fa_debug' );
```

---

## Getting Help

### Information to Provide

When asking for help, provide:

1. **WordPress Version:** `wp --version`
2. **PHP Version:** `php -v`
3. **Library Version:** Check Settings → Font Awesome page footer
4. **Settings:** Copy settings array from debug output
5. **Error Messages:** From browser console and error_log
6. **Theme/Plugins:** List active plugins and theme

### Debug Checklist

- [ ] Check browser console for errors (F12)
- [ ] View page source for Font Awesome URLs
- [ ] Check WordPress error log
- [ ] Test with default WordPress theme
- [ ] Disable other plugins temporarily
- [ ] Clear all caches (browser, WordPress, CDN)
- [ ] Check file permissions
- [ ] Verify Font Awesome URL loads in browser

### Support Resources

- **GitHub Issues:** https://github.com/AyeCode/wp-font-awesome-settings/issues
- **Font Awesome Docs:** https://fontawesome.com/docs
- **WordPress Support:** https://wordpress.org/support/

---

## Prevention

### Best Practices to Avoid Issues

1. **Always check if class exists**
   ```php
   if ( class_exists( 'WP_Font_Awesome_Settings' ) ) { }
   ```

2. **Use proper hook priorities**
3. **Test before deploying**
4. **Keep Font Awesome updated**
5. **Use Kits for production** (better performance)
6. **Enable dequeue** if using multiple plugins
7. **Monitor error logs**

---

This troubleshooting guide covers the most common issues. For additional help, consult the other documentation files or open a GitHub issue.
