<?php
/**
 * Plugin bootstrap.
 *
 * @package Gutenberg_Default_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDB_Plugin {
	/**
	 * Singleton.
	 *
	 * @var GDB_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return GDB_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->register_services();
	}

	/**
	 * Register services.
	 *
	 * @return void
	 */
	private function register_services() {
		GDB_Admin_Page::get_instance();
		GDB_Rest_Controller::get_instance();
		GDB_Default_Content::get_instance();
		GDB_Editor_Assets::get_instance();
		GDB_Post_Type::get_instance();
	}
}
