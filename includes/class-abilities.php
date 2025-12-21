<?php
/**
 * WordPress 6.9 Abilities API integration.
 *
 * Registers content guidelines abilities for AI orchestration and external discovery.
 *
 * @package ContentGuidelines
 * @since 0.2.0
 */

namespace ContentGuidelines;

defined( 'ABSPATH' ) || exit;

/**
 * Abilities API integration for Content Guidelines.
 *
 * Exposes content guidelines functionality through the WordPress 6.9 Abilities API,
 * enabling AI assistants and external services to discover and execute
 * guidelines-related actions in a standardized way.
 */
class Abilities {

	/**
	 * Ability category slug.
	 */
	const CATEGORY = 'content-guidelines';

	/**
	 * Ability namespace.
	 */
	const NAMESPACE = 'content-guidelines';

	/**
	 * Initialize abilities registration.
	 */
	public static function init() {
		// Only register if the Abilities API is available (WordPress 6.9+).
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register the content guidelines ability category.
	 */
	public static function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Content Guidelines', 'content-guidelines' ),
				'description' => __( 'Manage site-level editorial guidelines for voice, tone, vocabulary, and copy rules that AI features can consume.', 'content-guidelines' ),
				'icon'        => 'edit',
			)
		);
	}

	/**
	 * Register all content guidelines abilities.
	 */
	public static function register_abilities() {
		self::register_get_guidelines_ability();
		self::register_get_packet_ability();
		self::register_update_draft_ability();
		self::register_publish_draft_ability();
		self::register_discard_draft_ability();
		self::register_run_test_ability();
		self::register_check_lint_ability();
	}

	/**
	 * Register the get-guidelines ability.
	 */
	private static function register_get_guidelines_ability() {
		wp_register_ability(
			self::NAMESPACE . '/get-guidelines',
			array(
				'label'               => __( 'Get Content Guidelines', 'content-guidelines' ),
				'description'         => __( 'Retrieve the site content guidelines including brand context, voice and tone, copy rules, vocabulary, and image style preferences.', 'content-guidelines' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'use' => array(
							'type'        => 'string',
							'description' => __( 'Which version to retrieve: "active" for published guidelines or "draft" for unpublished changes.', 'content-guidelines' ),
							'enum'        => array( 'active', 'draft' ),
							'default'     => 'active',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'active'         => array(
							'type'        => 'object',
							'description' => __( 'The currently active (published) guidelines.', 'content-guidelines' ),
						),
						'draft'          => array(
							'type'        => array( 'object', 'null' ),
							'description' => __( 'The draft guidelines if any unpublished changes exist.', 'content-guidelines' ),
						),
						'has_draft'      => array(
							'type'        => 'boolean',
							'description' => __( 'Whether there are unpublished draft changes.', 'content-guidelines' ),
						),
						'post_id'        => array(
							'type'        => array( 'integer', 'null' ),
							'description' => __( 'The post ID of the guidelines entity.', 'content-guidelines' ),
						),
						'updated_at'     => array(
							'type'        => array( 'string', 'null' ),
							'description' => __( 'ISO 8601 timestamp of the last update.', 'content-guidelines' ),
						),
						'revision_count' => array(
							'type'        => 'integer',
							'description' => __( 'Number of saved revisions.', 'content-guidelines' ),
						),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_get_guidelines' ),
				'permission_callback' => array( __CLASS__, 'can_view_guidelines' ),
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Register the get-packet ability.
	 */
	private static function register_get_packet_ability() {
		wp_register_ability(
			self::NAMESPACE . '/get-context-packet',
			array(
				'label'               => __( 'Get Context Packet', 'content-guidelines' ),
				'description'         => __( 'Get a task-specific context packet formatted for LLM consumption. The packet contains relevant guidelines sections based on the task type.', 'content-guidelines' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'task'      => array(
							'type'        => 'string',
							'description' => __( 'The type of task to get guidelines for.', 'content-guidelines' ),
							'enum'        => array( 'writing', 'headline', 'cta', 'image', 'coach' ),
							'default'     => 'writing',
						),
						'post_id'   => array(
							'type'        => 'integer',
							'description' => __( 'Optional post ID for context-specific overrides.', 'content-guidelines' ),
						),
						'use'       => array(
							'type'        => 'string',
							'description' => __( 'Which guidelines version to use.', 'content-guidelines' ),
							'enum'        => array( 'active', 'draft' ),
							'default'     => 'active',
						),
						'max_chars' => array(
							'type'        => 'integer',
							'description' => __( 'Maximum characters for the packet text.', 'content-guidelines' ),
							'default'     => 2000,
							'minimum'     => 100,
							'maximum'     => 10000,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'packet_text'       => array(
							'type'        => 'string',
							'description' => __( 'Formatted text optimized for LLM system prompts.', 'content-guidelines' ),
						),
						'packet_structured' => array(
							'type'        => 'object',
							'description' => __( 'Structured subset of guidelines relevant to the task.', 'content-guidelines' ),
						),
						'guidelines_id'     => array(
							'type'        => array( 'integer', 'null' ),
							'description' => __( 'Post ID of the guidelines entity.', 'content-guidelines' ),
						),
						'revision_id'       => array(
							'type'        => array( 'integer', 'null' ),
							'description' => __( 'Current revision ID.', 'content-guidelines' ),
						),
						'updated_at'        => array(
							'type'        => array( 'string', 'null' ),
							'description' => __( 'ISO 8601 timestamp of last update.', 'content-guidelines' ),
						),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_get_packet' ),
				'permission_callback' => array( __CLASS__, 'can_view_guidelines' ),
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Register the update-draft ability.
	 */
	private static function register_update_draft_ability() {
		wp_register_ability(
			self::NAMESPACE . '/update-draft',
			array(
				'label'               => __( 'Update Draft Guidelines', 'content-guidelines' ),
				'description'         => __( 'Save changes to draft guidelines without publishing. Use this to incrementally update guidelines before publishing.', 'content-guidelines' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'guidelines' ),
					'properties' => array(
						'guidelines' => array(
							'type'        => 'object',
							'description' => __( 'The guidelines object to save as draft.', 'content-guidelines' ),
							'properties'  => array(
								'brand_context' => array(
									'type'       => 'object',
									'properties' => array(
										'site_description' => array( 'type' => 'string' ),
										'audience'         => array( 'type' => 'string' ),
										'primary_goal'     => array(
											'type' => 'string',
											'enum' => array( 'subscribe', 'sell', 'inform', 'community', 'other' ),
										),
									),
								),
								'voice_tone'    => array(
									'type'       => 'object',
									'properties' => array(
										'tone_traits'  => array(
											'type'  => 'array',
											'items' => array( 'type' => 'string' ),
										),
										'pov'          => array(
											'type' => 'string',
											'enum' => array( 'we_you', 'i_you', 'third_person' ),
										),
										'readability'  => array(
											'type' => 'string',
											'enum' => array( 'simple', 'general', 'expert' ),
										),
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
											'enum' => array( 'never', 'only_if_requested', 'ok' ),
										),
									),
								),
								'notes'         => array( 'type' => 'string' ),
							),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the draft was saved successfully.', 'content-guidelines' ),
						),
						'message' => array(
							'type'        => 'string',
							'description' => __( 'Status message.', 'content-guidelines' ),
						),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_update_draft' ),
				'permission_callback' => array( __CLASS__, 'can_edit_guidelines' ),
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Register the publish-draft ability.
	 */
	private static function register_publish_draft_ability() {
		wp_register_ability(
			self::NAMESPACE . '/publish-draft',
			array(
				'label'               => __( 'Publish Draft Guidelines', 'content-guidelines' ),
				'description'         => __( 'Publish the current draft guidelines, making them the active guidelines for the site.', 'content-guidelines' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the publish was successful.', 'content-guidelines' ),
						),
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post ID of the published guidelines.', 'content-guidelines' ),
						),
						'message' => array(
							'type'        => 'string',
							'description' => __( 'Status message.', 'content-guidelines' ),
						),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_publish_draft' ),
				'permission_callback' => array( __CLASS__, 'can_edit_guidelines' ),
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Register the discard-draft ability.
	 */
	private static function register_discard_draft_ability() {
		wp_register_ability(
			self::NAMESPACE . '/discard-draft',
			array(
				'label'               => __( 'Discard Draft Guidelines', 'content-guidelines' ),
				'description'         => __( 'Discard all unpublished draft changes and revert to the active guidelines.', 'content-guidelines' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the discard was successful.', 'content-guidelines' ),
						),
						'message' => array(
							'type'        => 'string',
							'description' => __( 'Status message.', 'content-guidelines' ),
						),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_discard_draft' ),
				'permission_callback' => array( __CLASS__, 'can_edit_guidelines' ),
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Register the run-test ability.
	 */
	private static function register_run_test_ability() {
		wp_register_ability(
			self::NAMESPACE . '/run-test',
			array(
				'label'               => __( 'Run Playground Test', 'content-guidelines' ),
				'description'         => __( 'Test how guidelines affect AI-generated content by running a task against a fixture post. Returns lint results, context packet, and optionally AI-generated output if a provider is available.', 'content-guidelines' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'fixture_post_id' ),
					'properties' => array(
						'task'               => array(
							'type'        => 'string',
							'description' => __( 'The type of test to run.', 'content-guidelines' ),
							'enum'        => array( 'rewrite_intro', 'generate_headlines', 'write_cta' ),
							'default'     => 'rewrite_intro',
						),
						'fixture_post_id'    => array(
							'type'        => 'integer',
							'description' => __( 'The post ID to use as test fixture content.', 'content-guidelines' ),
						),
						'use'                => array(
							'type'        => 'string',
							'description' => __( 'Which guidelines version to test with.', 'content-guidelines' ),
							'enum'        => array( 'active', 'draft' ),
							'default'     => 'draft',
						),
						'compare'            => array(
							'type'        => 'boolean',
							'description' => __( 'Whether to also run the test with active guidelines for comparison.', 'content-guidelines' ),
							'default'     => false,
						),
						'extra_instructions' => array(
							'type'        => 'string',
							'description' => __( 'Additional instructions to pass to the AI provider.', 'content-guidelines' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'lint_results'   => array(
							'type'        => 'object',
							'description' => __( 'Results from vocabulary and copy rule lint checks.', 'content-guidelines' ),
						),
						'context_packet' => array(
							'type'        => 'object',
							'description' => __( 'The context packet that would be sent to AI.', 'content-guidelines' ),
						),
						'fixture'        => array(
							'type'        => 'object',
							'description' => __( 'Information about the fixture post used.', 'content-guidelines' ),
						),
						'ai_result'      => array(
							'type'        => 'object',
							'description' => __( 'AI-generated result if a provider is available.', 'content-guidelines' ),
						),
						'ai_available'   => array(
							'type'        => 'boolean',
							'description' => __( 'Whether AI generation was available.', 'content-guidelines' ),
						),
						'compare'        => array(
							'type'        => 'object',
							'description' => __( 'Comparison results using active guidelines.', 'content-guidelines' ),
						),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_run_test' ),
				'permission_callback' => array( __CLASS__, 'can_edit_guidelines' ),
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Register the check-lint ability.
	 */
	private static function register_check_lint_ability() {
		wp_register_ability(
			self::NAMESPACE . '/check-lint',
			array(
				'label'               => __( 'Check Content Lint', 'content-guidelines' ),
				'description'         => __( 'Run vocabulary and copy rule lint checks against provided content without running AI generation.', 'content-guidelines' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'content' ),
					'properties' => array(
						'content' => array(
							'type'        => 'string',
							'description' => __( 'The content to check against guidelines.', 'content-guidelines' ),
						),
						'use'     => array(
							'type'        => 'string',
							'description' => __( 'Which guidelines version to check against.', 'content-guidelines' ),
							'enum'        => array( 'active', 'draft' ),
							'default'     => 'active',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'issues'       => array(
							'type'        => 'array',
							'description' => __( 'Array of lint issues found.', 'content-guidelines' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'type'       => array( 'type' => 'string' ),
									'term'       => array( 'type' => 'string' ),
									'message'    => array( 'type' => 'string' ),
									'suggestion' => array( 'type' => 'string' ),
								),
							),
						),
						'issue_count'  => array(
							'type'        => 'integer',
							'description' => __( 'Total number of issues found.', 'content-guidelines' ),
						),
						'passed'       => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the content passed all lint checks.', 'content-guidelines' ),
						),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_check_lint' ),
				'permission_callback' => array( __CLASS__, 'can_view_guidelines' ),
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * Check if user can view guidelines.
	 *
	 * @return bool True if can view.
	 */
	public static function can_view_guidelines() {
		return current_user_can( 'edit_theme_options' );
	}

	/**
	 * Check if user can edit guidelines.
	 *
	 * @return bool True if can edit.
	 */
	public static function can_edit_guidelines() {
		return current_user_can( 'edit_theme_options' );
	}

	// -------------------------------------------------------------------------
	// Execute callbacks
	// -------------------------------------------------------------------------

	/**
	 * Execute get-guidelines ability.
	 *
	 * @param array $input The input parameters.
	 * @return array|WP_Error The guidelines data.
	 */
	public static function execute_get_guidelines( $input ) {
		$post   = Post_Type::get_guidelines_post();
		$active = Post_Type::get_active_guidelines();
		$draft  = Post_Type::get_draft_guidelines();

		return array(
			'active'         => $active ? $active : Post_Type::get_default_guidelines(),
			'draft'          => $draft,
			'has_draft'      => ! empty( $draft ),
			'post_id'        => $post ? $post->ID : null,
			'updated_at'     => $post ? $post->post_modified_gmt : null,
			'revision_count' => $post ? count( wp_get_post_revisions( $post->ID ) ) : 0,
		);
	}

	/**
	 * Execute get-context-packet ability.
	 *
	 * @param array $input The input parameters.
	 * @return array The context packet.
	 */
	public static function execute_get_packet( $input ) {
		return Context_Packet_Builder::get_packet(
			array(
				'task'      => isset( $input['task'] ) ? $input['task'] : 'writing',
				'post_id'   => isset( $input['post_id'] ) ? $input['post_id'] : null,
				'use'       => isset( $input['use'] ) ? $input['use'] : 'active',
				'max_chars' => isset( $input['max_chars'] ) ? $input['max_chars'] : 2000,
			)
		);
	}

	/**
	 * Execute update-draft ability.
	 *
	 * @param array $input The input parameters.
	 * @return array|WP_Error Result.
	 */
	public static function execute_update_draft( $input ) {
		if ( ! isset( $input['guidelines'] ) ) {
			return new \WP_Error(
				'missing_guidelines',
				__( 'Guidelines data is required.', 'content-guidelines' )
			);
		}

		$sanitized = Post_Type::sanitize_guidelines( $input['guidelines'] );
		$result    = Post_Type::save_draft( $sanitized );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success' => true,
			'message' => __( 'Draft saved.', 'content-guidelines' ),
		);
	}

	/**
	 * Execute publish-draft ability.
	 *
	 * @param array $input The input parameters.
	 * @return array|WP_Error Result.
	 */
	public static function execute_publish_draft( $input ) {
		$result = Post_Type::publish_draft();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success' => true,
			'post_id' => $result,
			'message' => __( 'Guidelines published.', 'content-guidelines' ),
		);
	}

	/**
	 * Execute discard-draft ability.
	 *
	 * @param array $input The input parameters.
	 * @return array Result.
	 */
	public static function execute_discard_draft( $input ) {
		Post_Type::discard_draft();

		return array(
			'success' => true,
			'message' => __( 'Draft discarded.', 'content-guidelines' ),
		);
	}

	/**
	 * Execute run-test ability.
	 *
	 * @param array $input The input parameters.
	 * @return array|WP_Error Result.
	 */
	public static function execute_run_test( $input ) {
		if ( ! isset( $input['fixture_post_id'] ) ) {
			return new \WP_Error(
				'missing_fixture',
				__( 'Fixture post ID is required.', 'content-guidelines' )
			);
		}

		$fixture_post = get_post( $input['fixture_post_id'] );

		if ( ! $fixture_post ) {
			return new \WP_Error(
				'invalid_fixture',
				__( 'Invalid fixture post.', 'content-guidelines' )
			);
		}

		$task               = isset( $input['task'] ) ? $input['task'] : 'rewrite_intro';
		$use                = isset( $input['use'] ) ? $input['use'] : 'draft';
		$compare            = isset( $input['compare'] ) ? $input['compare'] : false;
		$extra_instructions = isset( $input['extra_instructions'] ) ? $input['extra_instructions'] : '';

		// Get guidelines.
		$guidelines = 'draft' === $use
			? Post_Type::get_draft_guidelines()
			: Post_Type::get_active_guidelines();

		if ( ! $guidelines ) {
			$guidelines = Post_Type::get_default_guidelines();
		}

		// Extract fixture content.
		$fixture_content = self::extract_fixture_content( $fixture_post, $task );

		// Run lint checks.
		$lint_results = Lint_Checker::check( $fixture_content, $guidelines );

		// Build context packet.
		$context_packet = Context_Packet_Builder::get_packet(
			array(
				'task'    => self::map_task_type( $task ),
				'post_id' => $input['fixture_post_id'],
				'use'     => $use,
			)
		);

		$result = array(
			'lint_results'   => $lint_results,
			'context_packet' => $context_packet,
			'fixture'        => array(
				'title'   => $fixture_post->post_title,
				'excerpt' => wp_trim_words( $fixture_content, 100 ),
			),
		);

		// Try AI provider.
		$ai_request = array(
			'task'               => $task,
			'fixture_content'    => $fixture_content,
			'guidelines'         => $guidelines,
			'context_packet'     => $context_packet,
			'extra_instructions' => $extra_instructions,
		);

		$ai_result = apply_filters( 'wp_content_guidelines_run_playground_test', null, $ai_request );

		if ( null !== $ai_result ) {
			$result['ai_result'] = $ai_result;
		} else {
			$result['ai_available'] = false;
		}

		// Comparison mode.
		if ( $compare && 'draft' === $use ) {
			$active_guidelines = Post_Type::get_active_guidelines();

			if ( $active_guidelines ) {
				$active_lint   = Lint_Checker::check( $fixture_content, $active_guidelines );
				$active_packet = Context_Packet_Builder::get_packet(
					array(
						'task'    => self::map_task_type( $task ),
						'post_id' => $input['fixture_post_id'],
						'use'     => 'active',
					)
				);

				$result['compare'] = array(
					'lint_results'   => $active_lint,
					'context_packet' => $active_packet,
				);
			}
		}

		return $result;
	}

	/**
	 * Execute check-lint ability.
	 *
	 * @param array $input The input parameters.
	 * @return array|WP_Error Result.
	 */
	public static function execute_check_lint( $input ) {
		if ( ! isset( $input['content'] ) || empty( $input['content'] ) ) {
			return new \WP_Error(
				'missing_content',
				__( 'Content is required.', 'content-guidelines' )
			);
		}

		$use        = isset( $input['use'] ) ? $input['use'] : 'active';
		$guidelines = 'draft' === $use
			? Post_Type::get_draft_guidelines()
			: Post_Type::get_active_guidelines();

		if ( ! $guidelines ) {
			$guidelines = Post_Type::get_default_guidelines();
		}

		$lint_results = Lint_Checker::check( $input['content'], $guidelines );

		return array(
			'issues'      => isset( $lint_results['issues'] ) ? $lint_results['issues'] : array(),
			'issue_count' => isset( $lint_results['issue_count'] ) ? $lint_results['issue_count'] : 0,
			'passed'      => empty( $lint_results['issues'] ),
		);
	}

	// -------------------------------------------------------------------------
	// Helper methods
	// -------------------------------------------------------------------------

	/**
	 * Extract content from fixture post for a specific task.
	 *
	 * @param \WP_Post $post The post object.
	 * @param string   $task The task type.
	 * @return string The extracted content.
	 */
	private static function extract_fixture_content( $post, $task ) {
		$content = $post->post_content;
		$content = wp_strip_all_tags( do_blocks( $content ) );

		switch ( $task ) {
			case 'rewrite_intro':
				return mb_substr( $content, 0, 500 );

			case 'generate_headlines':
				return $post->post_title . "\n\n" . wp_trim_words( $content, 150 );

			case 'write_cta':
				return wp_trim_words( $content, 300 );

			default:
				return wp_trim_words( $content, 200 );
		}
	}

	/**
	 * Map playground task to context packet task type.
	 *
	 * @param string $task The playground task.
	 * @return string The context packet task.
	 */
	private static function map_task_type( $task ) {
		$map = array(
			'rewrite_intro'      => 'writing',
			'generate_headlines' => 'headline',
			'write_cta'          => 'cta',
		);

		return isset( $map[ $task ] ) ? $map[ $task ] : 'writing';
	}
}
