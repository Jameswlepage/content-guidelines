<?php
/**
 * Context Packet Builder for AI consumption.
 *
 * @package ContentGuidelines
 */

namespace ContentGuidelines;

defined( 'ABSPATH' ) || exit;

/**
 * Builds task-specific context packets from guidelines.
 */
class Context_Packet_Builder {

	/**
	 * Get guidelines data.
	 *
	 * @param string $use Which version: 'active' or 'draft'.
	 * @return array|null Guidelines data.
	 */
	public static function get_guidelines( $use = 'active' ) {
		if ( 'draft' === $use ) {
			$draft = Post_Type::get_draft_guidelines();
			if ( $draft ) {
				return $draft;
			}
		}

		return Post_Type::get_active_guidelines();
	}

	/**
	 * Get context packet for AI consumption.
	 *
	 * @param array $args {
	 *     Arguments for building the packet.
	 *
	 *     @type string $task      Task type. Default 'writing'.
	 *     @type int    $post_id   Optional context post ID.
	 *     @type string $use       Which version: 'active' or 'draft'. Default 'active'.
	 *     @type int    $max_chars Maximum characters. Default 2000.
	 *     @type string $locale    Optional locale.
	 * }
	 * @return array The context packet.
	 */
	public static function get_packet( $args = array() ) {
		$defaults = array(
			'task'      => 'writing',
			'post_id'   => null,
			'use'       => 'active',
			'max_chars' => 2000,
			'locale'    => get_locale(),
		);

		$args       = wp_parse_args( $args, $defaults );
		$guidelines = self::get_guidelines( $args['use'] );
		$post       = Post_Type::get_guidelines_post();

		if ( ! $guidelines ) {
			return array(
				'packet_text'       => '',
				'packet_structured' => array(),
				'guidelines_id'     => null,
				'revision_id'       => null,
				'updated_at'        => null,
			);
		}

		// Get task-relevant subset of guidelines.
		$relevant = self::get_task_relevant_sections( $guidelines, $args['task'] );

		// Build text packet.
		$packet_text = self::build_text_packet( $relevant, $args['task'], $args['max_chars'] );

		// Get revision info.
		$revision_id = null;
		if ( $post ) {
			$revisions = wp_get_post_revisions( $post->ID, array( 'posts_per_page' => 1 ) );
			if ( ! empty( $revisions ) ) {
				$revision_id = current( $revisions )->ID;
			}
		}

		return array(
			'packet_text'       => $packet_text,
			'packet_structured' => $relevant,
			'guidelines_id'     => $post ? $post->ID : null,
			'revision_id'       => $revision_id,
			'updated_at'        => $post ? $post->post_modified_gmt : null,
		);
	}

	/**
	 * Get sections relevant to a specific task.
	 *
	 * @param array  $guidelines Full guidelines data.
	 * @param string $task       The task type.
	 * @return array Relevant subset.
	 */
	private static function get_task_relevant_sections( $guidelines, $task ) {
		$relevant = array();

		// Task-specific section mapping.
		$task_sections = array(
			'writing'  => array( 'brand_context', 'voice_tone', 'copy_rules', 'vocabulary', 'notes' ),
			'headline' => array( 'voice_tone', 'copy_rules', 'vocabulary' ),
			'cta'      => array( 'brand_context', 'copy_rules', 'vocabulary' ),
			'image'    => array( 'brand_context', 'image_style' ),
			'coach'    => array( 'voice_tone', 'copy_rules', 'vocabulary' ),
		);

		$sections = isset( $task_sections[ $task ] ) ? $task_sections[ $task ] : $task_sections['writing'];

		foreach ( $sections as $section ) {
			if ( isset( $guidelines[ $section ] ) && ! empty( $guidelines[ $section ] ) ) {
				$relevant[ $section ] = $guidelines[ $section ];
			}
		}

		return $relevant;
	}

	/**
	 * Build a text-based packet for LLM consumption.
	 *
	 * @param array  $relevant  Relevant guidelines sections.
	 * @param string $task      The task type.
	 * @param int    $max_chars Maximum characters.
	 * @return string The formatted packet text.
	 */
	private static function build_text_packet( $relevant, $task, $max_chars ) {
		$lines = array();
		$lines[] = '## SITE CONTENT GUIDELINES';
		$lines[] = '';

		// Brand context.
		if ( ! empty( $relevant['brand_context'] ) ) {
			$bc = $relevant['brand_context'];

			if ( ! empty( $bc['site_description'] ) ) {
				$lines[] = 'About this site: ' . $bc['site_description'];
			}

			if ( ! empty( $bc['audience'] ) ) {
				$lines[] = 'Target audience: ' . $bc['audience'];
			}

			if ( ! empty( $bc['primary_goal'] ) ) {
				$goal_labels = array(
					'subscribe' => 'Get email subscribers',
					'sell'      => 'Sell products/services',
					'inform'    => 'Inform and educate',
					'community' => 'Build community',
					'other'     => 'Other',
				);
				$goal_text   = isset( $goal_labels[ $bc['primary_goal'] ] )
					? $goal_labels[ $bc['primary_goal'] ]
					: $bc['primary_goal'];
				$lines[]     = 'Primary goal: ' . $goal_text;
			}

			$lines[] = '';
		}

		// Voice & tone.
		if ( ! empty( $relevant['voice_tone'] ) ) {
			$vt      = $relevant['voice_tone'];
			$lines[] = '### Voice & Tone';

			if ( ! empty( $vt['tone_traits'] ) ) {
				$lines[] = 'Tone: ' . implode( ', ', $vt['tone_traits'] );
			}

			if ( ! empty( $vt['pov'] ) ) {
				$pov_labels = array(
					'we_you'       => 'Write as "we" speaking to "you"',
					'i_you'        => 'Write as "I" speaking to "you"',
					'third_person' => 'Write in third person',
				);
				$pov_text   = isset( $pov_labels[ $vt['pov'] ] ) ? $pov_labels[ $vt['pov'] ] : $vt['pov'];
				$lines[]    = 'Point of view: ' . $pov_text;
			}

			if ( ! empty( $vt['readability'] ) ) {
				$read_labels = array(
					'simple'  => 'Simple (elementary level)',
					'general' => 'General audience',
					'expert'  => 'Expert/technical',
				);
				$read_text   = isset( $read_labels[ $vt['readability'] ] )
					? $read_labels[ $vt['readability'] ]
					: $vt['readability'];
				$lines[]     = 'Readability: ' . $read_text;
			}

			$lines[] = '';
		}

		// Copy rules.
		if ( ! empty( $relevant['copy_rules'] ) ) {
			$cr      = $relevant['copy_rules'];
			$lines[] = '### Copy Rules';

			if ( ! empty( $cr['dos'] ) ) {
				$lines[] = 'DO:';
				foreach ( $cr['dos'] as $rule ) {
					$lines[] = '- ' . $rule;
				}
			}

			if ( ! empty( $cr['donts'] ) ) {
				$lines[] = 'DON\'T:';
				foreach ( $cr['donts'] as $rule ) {
					$lines[] = '- ' . $rule;
				}
			}

			if ( ! empty( $cr['formatting'] ) ) {
				$format_labels = array(
					'h2s'              => 'Use H2 headings',
					'bullets'          => 'Use bullet points',
					'short_paragraphs' => 'Keep paragraphs short',
					'single_cta'       => 'Single CTA at end',
				);
				$format_items  = array();
				foreach ( $cr['formatting'] as $fmt ) {
					$format_items[] = isset( $format_labels[ $fmt ] ) ? $format_labels[ $fmt ] : $fmt;
				}
				$lines[] = 'Formatting: ' . implode( ', ', $format_items );
			}

			$lines[] = '';
		}

		// Vocabulary.
		if ( ! empty( $relevant['vocabulary'] ) ) {
			$vocab   = $relevant['vocabulary'];
			$lines[] = '### Vocabulary';

			if ( ! empty( $vocab['prefer'] ) ) {
				$lines[] = 'PREFER these terms:';
				foreach ( $vocab['prefer'] as $item ) {
					$term = is_array( $item ) ? $item['term'] : $item;
					$note = is_array( $item ) && ! empty( $item['note'] ) ? ' (' . $item['note'] . ')' : '';
					$lines[] = '- "' . $term . '"' . $note;
				}
			}

			if ( ! empty( $vocab['avoid'] ) ) {
				$lines[] = 'AVOID these terms:';
				foreach ( $vocab['avoid'] as $item ) {
					$term = is_array( $item ) ? $item['term'] : $item;
					$note = is_array( $item ) && ! empty( $item['note'] ) ? ' (' . $item['note'] . ')' : '';
					$lines[] = '- "' . $term . '"' . $note;
				}
			}

			$lines[] = '';
		}

		// Image style.
		if ( ! empty( $relevant['image_style'] ) ) {
			$img     = $relevant['image_style'];
			$lines[] = '### Image Style';

			if ( ! empty( $img['dos'] ) ) {
				$lines[] = 'Image style:';
				foreach ( $img['dos'] as $style ) {
					$lines[] = '- ' . $style;
				}
			}

			if ( ! empty( $img['donts'] ) ) {
				$lines[] = 'Avoid in images:';
				foreach ( $img['donts'] as $avoid ) {
					$lines[] = '- ' . $avoid;
				}
			}

			if ( ! empty( $img['text_policy'] ) ) {
				$text_labels = array(
					'never'             => 'Never include text in images',
					'only_if_requested' => 'Only include text if explicitly requested',
					'ok'                => 'Text in images is acceptable',
				);
				$text_text   = isset( $text_labels[ $img['text_policy'] ] )
					? $text_labels[ $img['text_policy'] ]
					: $img['text_policy'];
				$lines[]     = 'Text in images: ' . $text_text;
			}

			$lines[] = '';
		}

		// Notes.
		if ( ! empty( $relevant['notes'] ) ) {
			$lines[] = '### Additional Notes';
			$lines[] = $relevant['notes'];
			$lines[] = '';
		}

		$packet = implode( "\n", $lines );

		// Truncate if needed.
		if ( strlen( $packet ) > $max_chars ) {
			$packet = mb_substr( $packet, 0, $max_chars - 3 ) . '...';
		}

		return $packet;
	}
}
