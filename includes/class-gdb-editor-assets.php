<?php
/**
 * Editor assets.
 *
 * @package Gutenberg_Default_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDB_Editor_Assets {
	/**
	 * Singleton.
	 *
	 * @var GDB_Editor_Assets|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return GDB_Editor_Assets
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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( empty( $screen->post_type ) || ! use_block_editor_for_post_type( $screen->post_type ) ) {
			return;
		}

		wp_enqueue_script(
			'gdb-editor',
			GDB_PLUGIN_URL . 'build/editor.js',
			array( 'wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch', 'wp-data', 'wp-edit-post', 'wp-blocks', 'wp-notices', 'wp-plugins', 'wp-block-editor' ),
			GDB_VERSION,
			true
		);

		wp_enqueue_style(
			'gdb-editor',
			GDB_PLUGIN_URL . 'build/editor.css',
			array(),
			GDB_VERSION
		);

		wp_localize_script(
			'gdb-editor',
			'GDB_EDITOR',
			array(
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'restPath'  => '/gdb/v1',
				'adminUrl'  => esc_url_raw( admin_url( 'edit.php?post_type=' . GDB_Post_Type::POST_TYPE ) ),
				'postType'  => $screen->post_type,
				'postId'    => get_the_ID(),
			)
		);
	}
}
