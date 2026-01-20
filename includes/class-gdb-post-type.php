<?php
/**
 * Post type registration for sets.
 *
 * @package Gutenberg_Default_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDB_Post_Type {
	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'gdb_set';

	/**
	 * Meta key for items.
	 *
	 * @var string
	 */
	const META_KEY = 'gdb_items';

	/**
	 * Singleton.
	 *
	 * @var GDB_Post_Type|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return GDB_Post_Type
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
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'init', array( $this, 'maybe_migrate_sets' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'               => __( 'Gutenberg Sets', 'gutenberg-default-blocks' ),
					'singular_name'      => __( 'Set', 'gutenberg-default-blocks' ),
					'add_new'            => __( 'Add set', 'gutenberg-default-blocks' ),
					'add_new_item'       => __( 'Add set', 'gutenberg-default-blocks' ),
					'edit_item'          => __( 'Edit set', 'gutenberg-default-blocks' ),
					'new_item'           => __( 'New set', 'gutenberg-default-blocks' ),
					'view_item'          => __( 'View set', 'gutenberg-default-blocks' ),
					'search_items'       => __( 'Search sets', 'gutenberg-default-blocks' ),
					'not_found'          => __( 'No sets found.', 'gutenberg-default-blocks' ),
					'not_found_in_trash' => __( 'No sets found in Trash.', 'gutenberg-default-blocks' ),
					'all_items'          => __( 'All sets', 'gutenberg-default-blocks' ),
					'menu_name'          => __( 'Gutenberg Sets', 'gutenberg-default-blocks' ),
					'name_admin_bar'     => __( 'Add set', 'gutenberg-default-blocks' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'has_archive'  => false,
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
				'menu_icon'    => 'dashicons-block-default',
			)
		);

	}

	/**
	 * Register meta for items.
	 *
	 * @return void
	 */
	public function register_meta() {
		register_post_meta(
			self::POST_TYPE,
			self::META_KEY,
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => function () {
					return current_user_can( 'manage_options' );
				},
				'sanitize_callback' => function ( $value ) {
					return is_array( $value ) ? $value : array();
				},
			)
		);

	}

	/**
	 * Add columns to sets list.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_columns( $columns ) {
		$columns['gdb_items'] = __( 'Items', 'gutenberg-default-blocks' );
		return $columns;
	}

	/**
	 * Render columns.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_columns( $column, $post_id ) {
		if ( 'gdb_items' !== $column ) {
			return;
		}
		$items = get_post_meta( $post_id, self::META_KEY, true );
		if ( is_string( $items ) ) {
			$decoded = json_decode( $items, true );
			if ( is_array( $decoded ) ) {
				$items = $decoded;
			}
		}
		$items = is_array( $items ) ? $items : array();
		echo esc_html( (string) count( $items ) );
	}

	/**
	 * Migrate sets from legacy option storage.
	 *
	 * @return void
	 */
	public function maybe_migrate_sets() {
		$migrated = get_option( 'gdb_sets_migrated', false );
		if ( $migrated ) {
			return;
		}

		$existing = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $existing ) ) {
			update_option( 'gdb_sets_migrated', true, false );
			return;
		}

		$legacy = get_option( 'gdb_default_blocks', array() );
		if ( ! is_array( $legacy ) ) {
			update_option( 'gdb_sets_migrated', true, false );
			return;
		}

		$sets = array();
		if ( isset( $legacy['sets'] ) && is_array( $legacy['sets'] ) ) {
			$sets = $legacy['sets'];
		} elseif ( isset( $legacy[0] ) ) {
			$sets = $legacy;
		} else {
			$values = array_values( $legacy );
			if ( isset( $values[0]['sets'] ) && is_array( $values[0]['sets'] ) ) {
				$sets = $values[0]['sets'];
			}
		}

		foreach ( $sets as $set ) {
			if ( empty( $set['name'] ) ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => self::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => sanitize_text_field( $set['name'] ),
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			$items = isset( $set['items'] ) && is_array( $set['items'] ) ? $set['items'] : array();
			update_post_meta( $post_id, self::META_KEY, $items );
		}

		update_option( 'gdb_sets_migrated', true, false );
	}
}
