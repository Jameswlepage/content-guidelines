<?php
/**
 * Content Guidelines Post Type registration.
 *
 * @package ContentGuidelines
 */

namespace ContentGuidelines;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the wp_content_guidelines custom post type.
 */
class Post_Type {

	/**
	 * Post type name.
	 */
	const POST_TYPE = 'wp_content_guidelines';

	/**
	 * Meta key for draft guidelines.
	 */
	const DRAFT_META_KEY = '_wp_content_guidelines_draft';

	/**
	 * Meta key for generation sources.
	 */
	const SOURCES_META_KEY = '_wp_content_guidelines_sources';

	/**
	 * Initialize the post type.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_post_meta' ) );
	}

	/**
	 * Register the custom post type.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'          => _x( 'Content Guidelines', 'post type general name', 'content-guidelines' ),
			'singular_name' => _x( 'Content Guidelines', 'post type singular name', 'content-guidelines' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'query_var'           => false,
			'capability_type'     => 'post',
			'capabilities'        => array(
				'read'                   => 'edit_theme_options',
				'create_posts'           => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'delete_posts'           => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'edit_others_posts'      => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'publish_posts'          => 'edit_theme_options',
			),
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => array( 'revisions', 'custom-fields' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'show_in_rest'        => true,
			'rest_base'           => 'content-guidelines',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register post meta fields.
	 */
	public static function register_post_meta() {
		register_post_meta(
			self::POST_TYPE,
			self::DRAFT_META_KEY,
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => self::get_guidelines_schema(),
				),
				'auth_callback'     => function () {
					return current_user_can( 'edit_theme_options' );
				},
				'sanitize_callback' => array( __CLASS__, 'sanitize_guidelines' ),
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::SOURCES_META_KEY,
			array(
				'type'          => 'array',
				'single'        => true,
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'integer',
						),
					),
				),
				'auth_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);
	}

	/**
	 * Get the JSON schema for guidelines.
	 *
	 * @return array The schema definition.
	 */
	public static function get_guidelines_schema() {
		return array(
			'type'                 => 'object',
			'additionalProperties' => true,
			'properties'           => array(
				'version'       => array(
					'type'    => 'integer',
					'default' => 1,
				),
				'brand_context' => array(
					'type'       => 'object',
					'properties' => array(
						'site_description' => array( 'type' => 'string' ),
						'audience'         => array( 'type' => 'string' ),
						'primary_goal'     => array(
							'type' => 'string',
							'enum' => array( 'subscribe', 'sell', 'inform', 'community', 'other', '' ),
						),
						'topics'           => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
				'voice_tone'    => array(
					'type'       => 'object',
					'properties' => array(
						'tone_traits'   => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'pov'           => array(
							'type' => 'string',
							'enum' => array( 'we_you', 'i_you', 'third_person', '' ),
						),
						'readability'   => array(
							'type' => 'string',
							'enum' => array( 'simple', 'general', 'expert', '' ),
						),
						'example_good'  => array( 'type' => 'string' ),
						'example_avoid' => array( 'type' => 'string' ),
					),
				),
				'copy_rules'    => array(
					'type'       => 'object',
					'properties' => array(
						'dos'        => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'donts'      => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'formatting' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
				'vocabulary'    => array(
					'type'       => 'object',
					'properties' => array(
						'prefer' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'term' => array( 'type' => 'string' ),
									'note' => array( 'type' => 'string' ),
								),
							),
						),
						'avoid'  => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'term' => array( 'type' => 'string' ),
									'note' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'image_style'   => array(
					'type'       => 'object',
					'properties' => array(
						'dos'         => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'donts'       => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'text_policy' => array(
							'type' => 'string',
							'enum' => array( 'never', 'only_if_requested', 'ok', '' ),
						),
					),
				),
				'notes'         => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Sanitize guidelines data.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array Sanitized guidelines.
	 */
	public static function sanitize_guidelines( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		// Deep sanitize string values.
		return self::deep_sanitize( $value );
	}

	/**
	 * Recursively sanitize an array.
	 *
	 * @param array $data The data to sanitize.
	 * @return array Sanitized data.
	 */
	private static function deep_sanitize( $data ) {
		$sanitized = array();

		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $key ] = self::deep_sanitize( $value );
			} elseif ( is_string( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( is_int( $value ) ) {
				$sanitized[ $key ] = intval( $value );
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $key ] = (bool) $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Get or create the guidelines post for the current site.
	 *
	 * @return \WP_Post|null The guidelines post or null on failure.
	 */
	public static function get_guidelines_post() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => 1,
				'post_status'    => array( 'publish', 'draft' ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( ! empty( $posts ) ) {
			return $posts[0];
		}

		return null;
	}

	/**
	 * Create a new guidelines post.
	 *
	 * @param array $data Optional initial guidelines data.
	 * @return int|\WP_Error The post ID or error.
	 */
	public static function create_guidelines_post( $data = array() ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => __( 'Content Guidelines', 'content-guidelines' ),
				'post_content' => wp_json_encode( self::get_default_guidelines( $data ) ),
			),
			true
		);

		return $post_id;
	}

	/**
	 * Get default guidelines structure.
	 *
	 * @param array $overrides Optional values to merge.
	 * @return array Default guidelines.
	 */
	public static function get_default_guidelines( $overrides = array() ) {
		$defaults = array(
			'version'       => 1,
			'brand_context' => array(
				'site_description' => '',
				'audience'         => '',
				'primary_goal'     => '',
				'topics'           => array(),
			),
			'voice_tone'    => array(
				'tone_traits'   => array(),
				'pov'           => '',
				'readability'   => 'general',
				'example_good'  => '',
				'example_avoid' => '',
			),
			'copy_rules'    => array(
				'dos'        => array(),
				'donts'      => array(),
				'formatting' => array(),
			),
			'vocabulary'    => array(
				'prefer' => array(),
				'avoid'  => array(),
			),
			'image_style'   => array(
				'dos'         => array(),
				'donts'       => array(),
				'text_policy' => '',
			),
			'notes'         => '',
		);

		return array_replace_recursive( $defaults, $overrides );
	}

	/**
	 * Get active guidelines data.
	 *
	 * @return array|null Guidelines data or null.
	 */
	public static function get_active_guidelines() {
		$post = self::get_guidelines_post();

		if ( ! $post ) {
			return null;
		}

		$content = json_decode( $post->post_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $content ) ) {
			return self::get_default_guidelines();
		}

		return $content;
	}

	/**
	 * Get draft guidelines data.
	 *
	 * @return array|null Draft guidelines or null.
	 */
	public static function get_draft_guidelines() {
		$post = self::get_guidelines_post();

		if ( ! $post ) {
			return null;
		}

		$draft = get_post_meta( $post->ID, self::DRAFT_META_KEY, true );

		if ( empty( $draft ) || ! is_array( $draft ) ) {
			return null;
		}

		return $draft;
	}

	/**
	 * Save draft guidelines.
	 *
	 * @param array $data The draft data.
	 * @return bool|int|\WP_Error True/post ID on success, WP_Error on failure.
	 */
	public static function save_draft( $data ) {
		$post = self::get_guidelines_post();

		// Create post if it doesn't exist.
		if ( ! $post ) {
			$post_id = self::create_guidelines_post();
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			$post = get_post( $post_id );
		}

		$sanitized = self::sanitize_guidelines( $data );

		return update_post_meta( $post->ID, self::DRAFT_META_KEY, $sanitized );
	}

	/**
	 * Publish draft guidelines (promote to active).
	 *
	 * @return int|\WP_Error Post ID on success, WP_Error on failure.
	 */
	public static function publish_draft() {
		$post = self::get_guidelines_post();

		if ( ! $post ) {
			return new \WP_Error(
				'no_guidelines',
				__( 'No guidelines exist to publish.', 'content-guidelines' )
			);
		}

		$draft = self::get_draft_guidelines();

		if ( ! $draft ) {
			return new \WP_Error(
				'no_draft',
				__( 'No draft changes to publish.', 'content-guidelines' )
			);
		}

		// Update post content with draft (creates a revision).
		$result = wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => wp_json_encode( $draft ),
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Clear the draft.
		delete_post_meta( $post->ID, self::DRAFT_META_KEY );

		return $result;
	}

	/**
	 * Discard draft guidelines.
	 *
	 * @return bool True on success.
	 */
	public static function discard_draft() {
		$post = self::get_guidelines_post();

		if ( ! $post ) {
			return true;
		}

		return delete_post_meta( $post->ID, self::DRAFT_META_KEY );
	}

	/**
	 * Restore a revision as active.
	 *
	 * @param int $revision_id The revision ID to restore.
	 * @return int|\WP_Error Post ID on success, WP_Error on failure.
	 */
	public static function restore_revision( $revision_id ) {
		$revision = wp_get_post_revision( $revision_id );

		if ( ! $revision ) {
			return new \WP_Error(
				'invalid_revision',
				__( 'Invalid revision ID.', 'content-guidelines' )
			);
		}

		$post = self::get_guidelines_post();

		if ( ! $post || $revision->post_parent !== $post->ID ) {
			return new \WP_Error(
				'revision_mismatch',
				__( 'Revision does not belong to guidelines.', 'content-guidelines' )
			);
		}

		// Restore creates a new revision.
		return wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => $revision->post_content,
			),
			true
		);
	}
}
