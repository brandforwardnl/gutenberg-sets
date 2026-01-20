<?php
/**
 * Default content handler.
 *
 * @package Gutenberg_Default_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDB_Default_Content {
	/**
	 * Option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'gdb_default_blocks';

	/**
	 * Singleton.
	 *
	 * @var GDB_Default_Content|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return GDB_Default_Content
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
		// Intentionally left without auto-insert; insertion is handled via editor button.
	}

	/**
	 * Register REST insert hooks for public post types.
	 *
	 * @return void
	 */
	public function register_post_type_hooks() {}

	/**
	 * Apply default content when creating a new post.
	 *
	 * @param WP_Post         $post     Inserted post object.
	 * @param WP_REST_Request $request  The request object.
	 * @param bool            $creating Whether creating a new post.
	 * @return void
	 */
	public function maybe_apply_default_content( $post, $request, $creating ) {}

	/**
	 * Build the default content for a post type.
	 *
	 * @param string $post_type Post type name.
	 * @return string
	 */
	public function build_content_for_post_type( $post_type ) {
		$config = get_option( self::OPTION_NAME, array() );
		if ( empty( $config[ $post_type ] ) || ! is_array( $config[ $post_type ] ) ) {
			return '';
		}

		$items = $config[ $post_type ];
		$parts = array();

		foreach ( $items as $item ) {
			if ( empty( $item['content'] ) || ! is_string( $item['content'] ) ) {
				continue;
			}

			$parts[] = $item['content'];
		}

		return trim( implode( "\n\n", $parts ) );
	}
}
