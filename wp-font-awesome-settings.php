<?php
/**
 * Plugin Name: WP Font Awesome Settings
 * Plugin URI:  https://github.com/AyeCode/wp-font-awesome-settings
 * Description: Manage Font Awesome loading and settings in WordPress.
 * Version:     3.0.3-beta
 * Author:      AyeCode Ltd
 * Author URI:  https://ayecode.io/
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: ayecode-connect
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package WP_Font_Awesome_Settings
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Boot the package loader.
require_once __DIR__ . '/package-loader.php';

// Update version:
// 1. Here
// 2. pacakge-loader.php
// 3. composer.json
