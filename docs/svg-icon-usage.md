# SVG Icon Mode - Usage Guide

## Overview

When Font Awesome Type is set to **SVG**, icons are rendered as inline SVG elements using Just-In-Time (JIT) loading instead of loading the full Font Awesome CSS or JavaScript library on the frontend.

### Benefits
- **Reduced Page Weight**: Only loads icons you actually use
- **Improved Performance**: Three-layer caching (Object Cache → Filesystem → CDN)
- **Better SEO**: Inline SVG is readable by search engines
- **Full Customization**: Complete control over SVG attributes and styling
- **Custom Icons**: Upload and use your own SVG files

### Frontend vs Backend Behavior
- **Frontend**: No Font Awesome CSS/JS loaded - icons rendered as inline SVG via `ayecode_get_icon()`
- **Backend (wp-admin)**: Font Awesome CSS always loaded - normal `<i class="fa-solid fa-user"></i>` tags work as usual (no need to use `ayecode_get_icon()`)

---

## Configuration

### 1. Enable SVG Mode

Navigate to: **Settings → Font Awesome → General Settings**

Set **Type** to: `SVG - Inline Icons (No CSS/JS loaded on frontend)`

### 2. Verify Cache Directory

The settings page shows cache directory status. Ensure it's writable:

**Cache Directory**: `wp-content/uploads/ayecode-icon-cache/`

### 3. Configure Version

Set the Font Awesome version under **Version** - this determines which CDN version to fetch icons from.

---

## Functions

### `ayecode_get_icon()` - Returns Icon Markup

Returns icon markup as a string.

```php
ayecode_get_icon( string $identifier, array $options = [] ): string
```

### `ayecode_icon()` - Echo Icon Markup

Convenience function that echoes icon markup directly (follows WordPress convention like `the_permalink()` vs `get_permalink()`).

```php
ayecode_icon( string $identifier, array $options = [] ): void
```

### Behavior by Type Setting

| Type Setting | Frontend | Backend (wp-admin) |
|---|---|---|
| `SVG` | Inline `<svg>` via `ayecode_get_icon()` | CSS loaded, use normal `<i>` tags |
| `CSS` / `JS` / `KIT` | `<i>` tag via `ayecode_get_icon()` | CSS/JS loaded, use normal `<i>` tags |

**Important**: `ayecode_get_icon()` is designed for **frontend use only**. The backend always loads Font Awesome CSS normally, so you can use standard `<i class="fa-solid fa-user"></i>` tags in admin areas.

### Parameters

#### `$identifier` (string, required)

**Font Awesome Icons:**
```
'fa-{style} fa-{name}'
```

Examples:
- `'fa-solid fa-user'`
- `'fa-regular fa-heart'`
- `'fa-brands fa-github'`

**Custom Icons:**
```
'aui-icon-{filename}'
```

Example: `'aui-icon-logo'` (for uploaded `logo.svg`)

#### `$options` (array, optional)

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `class` | string\|array | `''` | CSS class(es) for the icon |
| `aria_label` | string | `''` | Accessible label (adds `role="img"` + `<title>`) |
| `width` | int\|string | auto | Width attribute |
| `height` | int\|string | auto | Height attribute |
| `fill` | string | `'currentColor'` | Fill color (SVG mode only) |
| `attributes` | array | `[]` | Additional HTML attributes |

---

## Usage Examples

### Basic Icon

```php
// Using get function (returns string)
echo ayecode_get_icon( 'fa-solid fa-user' );

// Using echo function (outputs directly)
ayecode_icon( 'fa-solid fa-user' );
```

**Output when Type = SVG:**
```html
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" aria-hidden="true">
  <path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8..."/>
</svg>
```

**Output when Type = CSS:**
```html
<i class="fa-solid fa-user" aria-hidden="true"></i>
```

### Icon with Custom Styling

```php
// Using get function
echo ayecode_get_icon( 'fa-solid fa-heart', [
    'class'  => ['text-danger', 'icon-lg'],
    'width'  => '32',
    'height' => '32',
    'fill'   => '#e74c3c',
] );

// Using echo function
ayecode_icon( 'fa-solid fa-heart', [
    'class'  => ['text-danger', 'icon-lg'],
    'width'  => '32',
    'height' => '32',
    'fill'   => '#e74c3c',
] );
```

### Accessible Icon

```php
echo ayecode_get_icon( 'fa-solid fa-check-circle', [
    'aria_label' => 'Success: Form submitted',
    'class'      => 'success-icon',
] );
```

**Output (SVG mode):**
```html
<svg role="img" aria-label="Success: Form submitted" class="success-icon">
  <title>Success: Form submitted</title>
  <path d="..."/>
</svg>
```

### Icon with Data Attributes

```php
echo ayecode_get_icon( 'fa-solid fa-info-circle', [
    'attributes' => [
        'data-toggle'    => 'tooltip',
        'data-placement' => 'top',
        'id'             => 'info-tooltip',
    ],
] );
```

### Brand Icons

```php
// GitHub
echo ayecode_get_icon( 'fa-brands fa-github', ['width' => '24'] );

// Twitter with brand color
echo ayecode_get_icon( 'fa-brands fa-twitter', [
    'width' => '24',
    'fill'  => '#1DA1F2',
] );
```

### Conditional Icons

```php
if ( is_user_logged_in() ) {
    echo ayecode_get_icon( 'fa-solid fa-user-check', [
        'aria_label' => 'Logged in',
    ] );
} else {
    echo ayecode_get_icon( 'fa-solid fa-user', [
        'aria_label' => 'Not logged in',
    ] );
}
```

### Dynamic Icon Loop

```php
$social_links = [
    'github'   => 'https://github.com/yourcompany',
    'twitter'  => 'https://twitter.com/yourcompany',
    'facebook' => 'https://facebook.com/yourcompany',
];

foreach ( $social_links as $network => $url ) {
    echo '<a href="' . esc_url( $url ) . '">';
    echo ayecode_get_icon( "fa-brands fa-{$network}", [
        'width'      => '24',
        'aria_label' => ucfirst( $network ),
    ] );
    echo '</a>';
}
```

---

## Custom SVG Icons

### Uploading Icons

1. Navigate to **Settings → Font Awesome → General Settings**
2. Set **Type** to `SVG`
3. Scroll to **Custom SVG Icons**
4. Click **Upload SVG Icons**
5. Select one or multiple `.svg` files

### Using Uploaded Icons

If you upload `company-logo.svg`:

```php
echo ayecode_get_icon( 'aui-icon-company-logo', [
    'width'  => '150',
    'height' => '50',
    'class'  => 'header-logo',
] );
```

### Deleting Icons

Click the **Delete** button under any uploaded icon in the settings grid.

---

## Available Styles

### Free Version
- `fa-solid` - Filled icons
- `fa-regular` - Outlined icons
- `fa-brands` - Brand logos

### Pro Version
All Free styles plus:
- `fa-light` - Light weight
- `fa-thin` - Thin stroke
- `fa-duotone` - Two-tone
- `fa-sharp-solid` - Sharp solid
- `fa-sharp-regular` - Sharp regular

---

## Caching Architecture

### Three-Layer Strategy

**Layer 1: Object Cache** (Fastest)
- Uses WordPress Object Cache (Redis/Memcached if available)
- Instant retrieval
- Cache key: `ayecode_icon_{style}_{name}`
- Cache group: `ayecode_icons`
- TTL: 24 hours

**Layer 2: Filesystem** (Fast)
- Location: `wp-content/uploads/ayecode-icon-cache/{style}/{name}.svg`
- Persistent across page loads
- Automatic directory creation

**Layer 3: Remote CDN** (Slowest, first load only)
- Free: `https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@{version}/svgs/{style}/{name}.svg`
- Pro: Custom authenticated endpoint
- Fetched once per icon
- Concurrency protection prevents duplicate downloads

### Clearing Cache

**Via Admin UI:**
1. Settings → Font Awesome → General Settings
2. Scroll to **Cache Directory**
3. Click **Clear Icon Cache**

**Programmatically:**
```php
$svg_loader = AyeCode_Font_Awesome_SVG_Loader::instance();
$result = $svg_loader->clear_icon_cache();
```

---

## CSS Styling

Since icons are inline SVG (in SVG mode) or `<i>` tags (in other modes), you can style them with CSS:

```css
/* Size via font-size (works for both SVG and <i> tags) */
.icon-sm { font-size: 14px; }
.icon-md { font-size: 18px; }
.icon-lg { font-size: 24px; }

/* Color via currentColor or fill */
.text-primary { color: #007bff; }
.text-danger { color: #dc3545; }
.text-success { color: #28a745; }

/* SVG-specific fill color */
svg.icon-primary { fill: #007bff; }

/* Hover effects */
.icon-hover:hover {
    color: #007bff;
    transform: scale(1.1);
    transition: all 0.2s ease;
}

/* Animations */
.icon-spin {
    animation: fa-spin 2s linear infinite;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
```

---

## Security

All SVG content (both remote and uploaded) is sanitized to remove:
- `<script>` tags
- Event handlers (`onclick`, `onload`, etc.)
- Dangerous tags (`<iframe>`, `<embed>`, `<object>`)
- JavaScript protocols
- External resource references (except safe image types)
- Generic ID attributes are scoped to prevent DOM conflicts

---

## Troubleshooting

### Icons Not Displaying

**Check Type setting:**
- Ensure Type = SVG in Settings → Font Awesome

**Check cache directory:**
```bash
ls -la wp-content/uploads/ayecode-icon-cache/
```
Must be writable (755 permissions)

**Check PHP version:**
```bash
php -v
```
Must be PHP 7.4+

**Verify identifier format:**
```php
// Correct
ayecode_get_icon( 'fa-solid fa-user' );

// Incorrect
ayecode_get_icon( 'solid user' ); // Missing 'fa-' prefix
ayecode_get_icon( 'fa-user' ); // Missing style
```

### CDN Fetch Failures

- Check Font Awesome version in settings
- Ensure Pro domain is authorized (Pro users)
- Check server firewall allows outbound HTTPS
- Review `wp-content/debug.log`

### Performance Issues

**Enable persistent object caching:**
- Install Redis Object Cache or Memcached plugin

**Check cache hit rate:**
```php
$svg_loader = AyeCode_Font_Awesome_SVG_Loader::instance();
$cache_dir = $svg_loader->get_icon_cache_dir();
$icon_count = count( glob( $cache_dir . '*/*.svg' ) );
echo "Cached icons: {$icon_count}";
```

---

## Migration from Other Types

### From CSS/JS to SVG

1. **Update settings:** Change Type to SVG
2. **Find all icon usage:** Search codebase for `fa-solid`, `fa-regular`, `fa-brands` class usage
3. **Replace with function:**
   ```php
   // Before (CSS mode)
   <i class="fa-solid fa-user"></i>

   // After (SVG mode)
   <?php echo ayecode_get_icon( 'fa-solid fa-user' ); ?>
   ```

### From SVG back to CSS/JS

Simply change Type setting back to `CSS`, `JS`, or `KIT`. The `ayecode_get_icon()` function automatically adapts and outputs `<i>` tags instead of SVG.

---

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher
- Writable `wp-content/uploads/` directory

---

## Support

- GitHub: https://github.com/AyeCode/wp-font-awesome-settings
- Email: contact@ayecode.io
