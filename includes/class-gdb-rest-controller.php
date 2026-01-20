<?php
/**
 * REST controller.
 *
 * @package Gutenberg_Default_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDB_Rest_Controller {
	/**
	 * Namespace.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'gdb/v1';

	/**
	 * Option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'gdb_default_blocks';

	/**
	 * Post type for sets.
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
	 * @var GDB_Rest_Controller|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return GDB_Rest_Controller
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/config',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_config' ),
					'permission_callback' => array( $this, 'get_config_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_config' ),
					'permission_callback' => array( $this, 'update_config_permissions' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/post-types',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_types' ),
				'permission_callback' => array( $this, 'update_config_permissions' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/sets',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sets' ),
					'permission_callback' => array( $this, 'get_config_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_set' ),
					'permission_callback' => array( $this, 'update_config_permissions' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/sets/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_set' ),
					'permission_callback' => array( $this, 'get_config_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_set' ),
					'permission_callback' => array( $this, 'update_config_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_set' ),
					'permission_callback' => array( $this, 'update_config_permissions' ),
				),
			)
		);
	}

	/**
	 * Permission check.
	 *
	 * @return bool
	 */
	public function get_config_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Permission check for updates.
	 *
	 * @return bool
	 */
	public function update_config_permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get config.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_config( $request ) {
		$config = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		return rest_ensure_response( $this->normalize_config( $config ) );
	}

	/**
	 * Update config.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function update_config( $request ) {
		$params = $request->get_json_params();
		$config = isset( $params['config'] ) && is_array( $params['config'] ) ? $params['config'] : array();

		$sanitized = $this->sanitize_config( $config );
		update_option( self::OPTION_NAME, $sanitized, false );

		return rest_ensure_response(
			array(
				'success' => true,
				'config'  => $this->normalize_config( $sanitized ),
			)
		);
	}

	/**
	 * Get public post types.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_post_types( $request ) {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$response   = array();

		foreach ( $post_types as $post_type ) {
			$response[] = array(
				'name'  => $post_type->name,
				'label' => $post_type->labels->singular_name,
			);
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Get all sets.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_sets( $request ) {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$sets = array();
		foreach ( $posts as $post ) {
			$sets[] = $this->prepare_set_response( $post );
		}

		return rest_ensure_response( $sets );
	}

	/**
	 * Get a single set.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_set( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new WP_REST_Response( array( 'message' => 'Set not found.' ), 404 );
		}

		$items = $this->get_items_meta( $post_id );

		return rest_ensure_response(
			array(
				'id'         => (int) $post->ID,
				'name'       => $post->post_title,
				'items'      => $items,
				'itemsCount' => count( $items ),
				'editUrl'    => esc_url_raw( admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) ),
			)
		);
	}

	/**
	 * Create a set.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function create_set( $request ) {
		$params = $request->get_json_params();
		$name   = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$name   = '' !== $name ? $name : __( 'Nieuwe set', 'gutenberg-default-blocks' );

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $name,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return new WP_REST_Response( array( 'message' => $post_id->get_error_message() ), 400 );
		}

		update_post_meta( $post_id, self::META_KEY, array() );
		return rest_ensure_response( $this->prepare_set_response( get_post( $post_id ) ) );
	}

	/**
	 * Update a set.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function update_set( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new WP_REST_Response( array( 'message' => 'Set not found.' ), 404 );
		}

		$params = $request->get_json_params();
		$name   = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : $post->post_title;
		$items  = isset( $params['items'] ) && is_array( $params['items'] ) ? $params['items'] : $this->get_items_meta( $post_id );
		$items  = $this->sanitize_items( $items );

		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $name,
			)
		);

		update_post_meta( $post_id, self::META_KEY, $items );

		return rest_ensure_response( $this->prepare_set_response( get_post( $post_id ) ) );
	}

	/**
	 * Delete a set.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function delete_set( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new WP_REST_Response( array( 'message' => 'Set not found.' ), 404 );
		}

		wp_delete_post( $post_id, true );
		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Sanitize config payload.
	 *
	 * @param array $config Raw config.
	 * @return array
	 */
	private function sanitize_config( $config ) {
		$allowed_types = array( 'block', 'pattern' );
		$sanitized     = array(
			'sets' => array(),
		);

		$sets = array();
		if ( isset( $config['sets'] ) && is_array( $config['sets'] ) ) {
			$sets = $config['sets'];
		} elseif ( isset( $config[0] ) ) {
			$sets = $config;
		}

		$sanitized_sets = array();
		foreach ( $sets as $set ) {
			if ( ! is_array( $set ) ) {
				continue;
			}

			$set_id   = isset( $set['id'] ) ? sanitize_key( $set['id'] ) : '';
			$set_name = isset( $set['name'] ) ? sanitize_text_field( $set['name'] ) : '';
			$set_id   = '' !== $set_id ? $set_id : sanitize_key( wp_generate_uuid4() );
			$set_name = '' !== $set_name ? $set_name : 'Set';

			$set_items = isset( $set['items'] ) && is_array( $set['items'] ) ? $set['items'] : array();
			$san_items = array();

			foreach ( $set_items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$type    = isset( $item['type'] ) && in_array( $item['type'], $allowed_types, true ) ? $item['type'] : 'block';
				$content = isset( $item['content'] ) && is_string( $item['content'] ) ? $item['content'] : '';
				$content = trim( wp_kses_no_null( wp_check_invalid_utf8( $content ) ) );

				if ( '' === $content ) {
					continue;
				}

				$san_items[] = array(
					'type'    => $type,
					'content' => $content,
				);
			}

			$sanitized_sets[] = array(
				'id'    => $set_id,
				'name'  => $set_name,
				'items' => $san_items,
			);
		}

		$sanitized['sets'] = $sanitized_sets;

		return $sanitized;
	}

	/**
	 * Normalize legacy config to sets structure.
	 *
	 * @param array $config Raw config.
	 * @return array
	 */
	private function normalize_config( $config ) {
		$normalized = array(
			'sets' => array(),
		);
		if ( ! is_array( $config ) ) {
			return $normalized;
		}

		if ( isset( $config['sets'] ) && is_array( $config['sets'] ) ) {
			$normalized['sets'] = $config['sets'];
			return $normalized;
		}

		if ( isset( $config[0] ) ) {
			$normalized['sets'] = $config;
			return $normalized;
		}

		foreach ( $config as $post_type => $value ) {
			if ( isset( $value['sets'] ) && is_array( $value['sets'] ) ) {
				$normalized['sets'] = $value['sets'];
				return $normalized;
			}

			if ( is_array( $value ) ) {
				$normalized['sets'] = array(
					array(
						'id'    => 'default',
						'name'  => 'Default',
						'items' => $value,
					),
				);
				return $normalized;
			}
		}

		return $normalized;
	}

	/**
	 * Sanitize items array.
	 *
	 * @param array $items Raw items.
	 * @return array
	 */
	private function sanitize_items( $items ) {
		$allowed_types = array( 'block', 'pattern' );
		$sanitized     = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$type    = isset( $item['type'] ) && in_array( $item['type'], $allowed_types, true ) ? $item['type'] : 'block';
			$content = isset( $item['content'] ) && is_string( $item['content'] ) ? $item['content'] : '';
			$content = trim( wp_kses_no_null( wp_check_invalid_utf8( $content ) ) );

			if ( '' === $content ) {
				continue;
			}

			$sanitized[] = array(
				'type'    => $type,
				'content' => $content,
			);
		}

		return $sanitized;
	}

	/**
	 * Get items from post meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_items_meta( $post_id ) {
		$items = get_post_meta( $post_id, self::META_KEY, true );
		if ( is_string( $items ) ) {
			$decoded = json_decode( $items, true );
			if ( is_array( $decoded ) ) {
				$items = $decoded;
			}
		}
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Prepare set response.
	 *
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	private function prepare_set_response( $post ) {
		$items = $this->get_items_meta( $post->ID );
		$image = get_post_thumbnail_id( $post->ID );
		$content = $post->post_content;

		return array(
			'id'         => (int) $post->ID,
			'name'       => $post->post_title,
			'items'      => $items,
			'itemsCount' => count( $items ),
			'content'    => $content,
			'imageUrl'   => $image ? esc_url_raw( wp_get_attachment_image_url( $image, 'medium' ) ) : '',
			'editUrl'    => esc_url_raw( admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) ),
		);
	}
}
