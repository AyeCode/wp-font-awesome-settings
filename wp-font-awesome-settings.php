<?php
/**
 * Plugin Name: WP Font Awesome Settings
 * Plugin URI:  https://github.com/AyeCode/wp-font-awesome-settings
 * Description: Manage Font Awesome loading and settings in WordPress.
 * Version:     3.0.1-beta
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

// Standalone-only hook — fires AFTER the framework has booted at priority 10.
//add_action( 'plugins_loaded', function () {
//	// Standalone-only bootstrap code here.
//}, 20 );
//
//// BC aliases for external code still referencing the old prefixed class names.
//if ( ! class_exists( 'AyeCode_FA_SVG_Loader' ) ) {
//	class_alias( 'AyeCode\\FontAwesome\\SVG_Loader', 'AyeCode_FA_SVG_Loader' );
//}
//if ( ! class_exists( 'AyeCode_FA_Custom_Icons' ) ) {
//	class_alias( 'AyeCode\\FontAwesome\\Custom_Icons', 'AyeCode_FA_Custom_Icons' );
//}
//if ( ! class_exists( 'AyeCode_FA_Icon_Library_Generator' ) ) {
//	class_alias( 'AyeCode\\FontAwesome\\Icon_Library_Generator', 'AyeCode_FA_Icon_Library_Generator' );
//}
