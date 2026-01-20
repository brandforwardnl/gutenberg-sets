<?php
/**
 * Plugin Name: Gutenberg Sets
 * Description: Beheer sets van Gutenberg-blokken en -patronen en voeg ze toe in de editor.
 * Author: Brandforward
 * Author URI: https://brandforward.nl
 * Plugin URI: https://brandforward.nl
 * Text Domain: gutenberg-default-blocks
 * Version: 1.0.1
 *
 * @package Gutenberg_Default_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GDB_VERSION', '1.0.1' );
define( 'GDB_PLUGIN_FILE', __FILE__ );
define( 'GDB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GDB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once GDB_PLUGIN_DIR . 'includes/class-gdb-plugin.php';
require_once GDB_PLUGIN_DIR . 'includes/class-gdb-admin-page.php';
require_once GDB_PLUGIN_DIR . 'includes/class-gdb-default-content.php';
require_once GDB_PLUGIN_DIR . 'includes/class-gdb-rest-controller.php';
require_once GDB_PLUGIN_DIR . 'includes/class-gdb-editor-assets.php';
require_once GDB_PLUGIN_DIR . 'includes/class-gdb-post-type.php';

add_action(
	'plugins_loaded',
	function () {
		GDB_Plugin::get_instance();
	}
);
