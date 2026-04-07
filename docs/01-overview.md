# WP Font Awesome Settings - Overview

## Introduction

WP Font Awesome Settings is a WordPress library that provides comprehensive Font Awesome integration and management for WordPress themes and plugins. It adds a centralized settings interface to control Font Awesome versions, loading methods, and various configuration options.

## Purpose

This library allows WordPress developers and site administrators to:

- Manage Font Awesome versions from a central location
- Choose between CSS or JavaScript loading methods
- Support Font Awesome Kits (cloud-based custom configurations)
- Enable Font Awesome Pro features
- Load Font Awesome files locally (for GDPR compliance and performance)
- Control where Font Awesome loads (frontend, backend, or both)
- Prevent conflicts from multiple Font Awesome versions
- Enable backward compatibility with v4 shims
- Support RTL (Right-to-Left) languages

## Key Features

### Version Management
- Automatic detection of the latest Font Awesome version via GitHub API
- Support for multiple Font Awesome versions (4.7.0 through 7.0.0+)
- Version caching to reduce API calls (48-hour cache)

### Loading Options
1. **CSS Method**: Traditional stylesheet approach
2. **JavaScript Method**: SVG with JavaScript approach (with pseudo-element support)
3. **Kit Method**: Font Awesome Kits for customized icon sets

### Font Awesome Pro Support
- Pro CDN integration
- Kit-based Pro configuration
- Domain authorization management

### Local Font Storage
- Download and store Font Awesome files locally
- Automatic version updates when new releases are available
- GDPR-friendly solution (no external CDN calls)

### Conflict Prevention
- Automatic dequeuing of other Font Awesome versions
- Detection and handling of the official Font Awesome plugin

### WordPress Integration
- Admin settings page under Settings menu
- WordPress options API integration
- Full Site Editor (FSE/Gutenberg) support
- RTL language support

## Installation

This library is designed to be installed via Composer:

```bash
composer require ayecode/wp-font-awesome-settings
```

The library automatically loads when included in a WordPress plugin or theme.

## Design Philosophy

### Single Instance Pattern
The class uses the Singleton pattern to ensure only one instance exists, preventing conflicts when multiple plugins/themes include the library.

### Non-intrusive
The library only loads Font Awesome if the official Font Awesome plugin is not active, preventing conflicts.

### Configurable
All settings are stored in the WordPress options table and can be controlled via the admin interface.

### Developer-friendly
Provides hooks and filters for developers to extend functionality.

## Target Audience

### For Developers
- Plugin developers who need Font Awesome in their products
- Theme developers requiring icon support
- Developers building WordPress applications with icon requirements

### For Site Administrators
- Users who need to manage Font Awesome versions across multiple plugins
- Users requiring GDPR-compliant font loading
- Users experiencing Font Awesome conflicts

## System Requirements

- PHP: 5.6.0 or higher
- WordPress: 4.0 or higher (recommended: latest version)
- Composer: For installation

## License

GPL-3.0-or-later

## Credits

Developed by AyeCode Ltd
- Homepage: https://ayecode.io/
- GitHub: https://github.com/AyeCode/wp-font-awesome-settings
