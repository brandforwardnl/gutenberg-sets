<?php
/**
 * Admin page.
 *
 * @package Gutenberg_Default_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDB_Admin_Page {
	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'gdb-sets';
	const LICENSE_SLUG = 'gdb-sets-license';
	const HELP_SLUG = 'gdb-sets-help';

	/**
	 * Singleton.
	 *
	 * @var GDB_Admin_Page|null
	 */
	private static $instance = null;

	/**
	 * Page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Get instance.
	 *
	 * @return GDB_Admin_Page
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
		// Menu removed; sets are managed via CPT menu.
	}

	/**
	 * Register menu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		// Intentionally left empty.
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_sets_redirect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . GDB_Post_Type::POST_TYPE ) );
		exit;
	}

	/**
	 * Render license page.
	 *
	 * @return void
	 */
	public function render_license_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'License', 'gutenberg-default-blocks' ) . '</h1>';
		echo '<p>' . esc_html__( 'Hier komt straks de licentie-instelling.', 'gutenberg-default-blocks' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Render help page.
	 *
	 * @return void
	 */
	public function render_help_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Help', 'gutenberg-default-blocks' ) . '</h1>';
		echo '<p>' . esc_html__( 'Gebruik Gutenberg Sets om vooraf ingestelde blokken te beheren.', 'gutenberg-default-blocks' ) . '</p>';
		echo '<ol>';
		echo '<li>' . esc_html__( 'Ga naar Gutenberg Sets → Sets en maak een nieuwe set aan.', 'gutenberg-default-blocks' ) . '</li>';
		echo '<li>' . esc_html__( 'Open een set en voeg blokken of patterns toe in de editor sidebar.', 'gutenberg-default-blocks' ) . '</li>';
		echo '<li>' . esc_html__( 'In een bericht kun je via het plus-icoon een set invoegen.', 'gutenberg-default-blocks' ) . '</li>';
		echo '</ol>';
		echo '</div>';
	}
}
