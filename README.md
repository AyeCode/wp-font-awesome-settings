# wp-font-awesome-settings

A WordPress Font Awesome management library for AyeCode products.

## Installation

```bash
composer require ayecode/wp-font-awesome-settings
```

## Overview

This package adds Font Awesome settings to WordPress, allowing users to control which versions and icon sets are loaded.

## ⚠️ Important Notice

**Version 3.0.0+ is intended for internal AyeCode use only.**

This package is no longer standalone and requires:
- AyeCode Settings Framework
- AyeCode UI packages

It is designed to be integrated into AyeCode products and may not function correctly as a standalone plugin.

## Features

- Font Awesome version management (v4, v5, v6)
- Icon family selection (Solid, Regular, Brands, Light, Duotone)
- SVG icon support with sanitization
- Custom icon upload and management
- Webfont and SVG loading options
- CDN and local file support

## Usage

### Basic Icon Rendering

```php
// Render a Font Awesome icon (returns string)
echo ayecode_get_icon( 'fa-solid fa-user' );

// Echo icon directly (convenience function)
ayecode_icon( 'fa-solid fa-user' );
```

### With Options

```php
// Add custom classes and accessibility
echo ayecode_get_icon( 'fa-solid fa-user', array(
    'class'      => 'my-custom-class',
    'aria_label' => 'User Profile',
) );

// Control SVG dimensions
echo ayecode_get_icon( 'fa-brands fa-wordpress', array(
    'width'  => '32',
    'height' => '32',
    'fill'   => '#0073aa',
) );

// Force SVG output regardless of Type setting
echo ayecode_get_icon( 'fa-solid fa-heart', array(
    'force_svg' => true,
) );
```

### Custom Icons

```php
// Render custom uploaded icon (always outputs SVG)
echo ayecode_get_icon( 'aui-icon-my-logo' );

// Check if custom icon exists
if ( ayecode_custom_icon_exists( 'my-logo' ) ) {
    ayecode_icon( 'aui-icon-my-logo' );
}

// Get all custom icons
$icons = ayecode_get_custom_icons();
foreach ( $icons as $icon ) {
    echo $icon['slug'] . ': ' . $icon['url'];
}
```

### Icon Styles

Font Awesome 6 supports multiple styles:
```php
ayecode_icon( 'fa-solid fa-star' );      // Solid (free)
ayecode_icon( 'fa-regular fa-star' );    // Regular (free)
ayecode_icon( 'fa-brands fa-github' );   // Brands (free)
ayecode_icon( 'fa-light fa-star' );      // Light (pro)
ayecode_icon( 'fa-duotone fa-star' );    // Duotone (pro)
ayecode_icon( 'fa-thin fa-star' );       // Thin (pro)
```