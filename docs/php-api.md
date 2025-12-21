# PHP API Reference

The Content Guidelines plugin provides simple PHP functions to access guidelines from anywhere in WordPress.

## Quick Start

```php
// Get the formatted context packet for AI consumption
$packet = wp_get_content_guidelines_packet();

// Use the packet text in your AI prompt
$prompt = $packet['packet_text'] . "\n\nNow write a headline for: " . $post_title;
```

## Functions

### wp_get_content_guidelines_packet()

Get a task-specific context packet formatted for LLM consumption.

```php
$packet = wp_get_content_guidelines_packet( array(
    'task'      => 'writing',  // Task type (see below)
    'post_id'   => null,       // Optional: context post ID
    'use'       => 'active',   // 'active' or 'draft'
    'max_chars' => 2000,       // Maximum characters for packet_text
) );
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `task` | string | `'writing'` | Task type: `writing`, `headline`, `cta`, `image`, `coach` |
| `post_id` | int | `null` | Post ID for context-specific overrides |
| `use` | string | `'active'` | Which guidelines version: `active` or `draft` |
| `max_chars` | int | `2000` | Maximum characters for the formatted text |

**Returns:**

```php
array(
    'packet_text'       => '## SITE CONTENT GUIDELINES\n\nAbout this site: ...',
    'packet_structured' => array(
        'brand_context' => array( ... ),
        'voice_tone'    => array( ... ),
        // ... task-relevant sections only
    ),
    'guidelines_id'     => 123,        // Post ID of guidelines
    'revision_id'       => 456,        // Current revision ID
    'updated_at'        => '2025-01-15T10:30:00+00:00',
)
```

**Task Types:**

Each task type returns only the relevant guideline sections:

| Task | Included Sections |
|------|-------------------|
| `writing` | brand_context, voice_tone, copy_rules, vocabulary, notes |
| `headline` | voice_tone, copy_rules, vocabulary |
| `cta` | brand_context, copy_rules, vocabulary |
| `image` | brand_context, image_style |
| `coach` | voice_tone, copy_rules, vocabulary |

### wp_get_content_guidelines()

Get the raw guidelines data.

```php
$guidelines = wp_get_content_guidelines( 'active' ); // or 'draft'
```

**Returns:** The full guidelines array or `null` if not set.

```php
array(
    'version' => 1,
    'brand_context' => array(
        'site_description' => 'A blog about sustainable living',
        'audience' => 'Environmentally conscious millennials',
        'primary_goal' => 'inform',
    ),
    'voice_tone' => array(
        'tone_traits' => array( 'friendly', 'encouraging' ),
        'pov' => 'we_you',
        'readability' => 'general',
    ),
    'copy_rules' => array(
        'dos' => array( 'Use active voice', 'Include actionable tips' ),
        'donts' => array( 'Avoid jargon', 'Never shame readers' ),
        'formatting' => array( 'h2s', 'bullets', 'short_paragraphs' ),
    ),
    'vocabulary' => array(
        'prefer' => array(
            array( 'term' => 'sustainable', 'note' => 'Our core value' ),
        ),
        'avoid' => array(
            array( 'term' => 'green', 'note' => 'Too vague' ),
        ),
    ),
    'image_style' => array(
        'dos' => array( 'Use natural lighting', 'Show real people' ),
        'donts' => array( 'No stock photos', 'Avoid filters' ),
        'text_policy' => 'never',
    ),
    'notes' => 'Additional context for AI...',
)
```

## Usage Examples

### Example 1: Generate a Headline

```php
function generate_headline_with_guidelines( $post_id ) {
    $post = get_post( $post_id );

    // Get guidelines formatted for headline generation
    $packet = wp_get_content_guidelines_packet( array(
        'task'    => 'headline',
        'post_id' => $post_id,
    ) );

    // Build your AI prompt
    $prompt = $packet['packet_text'] . "\n\n";
    $prompt .= "Generate 5 headline options for this article:\n\n";
    $prompt .= $post->post_title . "\n\n";
    $prompt .= wp_trim_words( $post->post_content, 100 );

    // Send to your AI provider
    return my_ai_provider_generate( $prompt );
}
```

### Example 2: Check if Guidelines Exist

```php
function has_site_guidelines() {
    $guidelines = wp_get_content_guidelines();
    return ! empty( $guidelines );
}
```

### Example 3: Get Vocabulary for Linting

```php
function get_words_to_avoid() {
    $guidelines = wp_get_content_guidelines();

    if ( empty( $guidelines['vocabulary']['avoid'] ) ) {
        return array();
    }

    return array_map( function( $item ) {
        return $item['term'];
    }, $guidelines['vocabulary']['avoid'] );
}
```

### Example 4: Inject Guidelines into Block Editor

```php
add_action( 'enqueue_block_editor_assets', function() {
    $packet = wp_get_content_guidelines_packet( array(
        'task' => 'writing',
    ) );

    wp_add_inline_script(
        'my-ai-plugin',
        sprintf(
            'window.siteGuidelines = %s;',
            wp_json_encode( $packet )
        ),
        'before'
    );
} );
```

## Checking Availability

The functions are available after the `plugins_loaded` hook:

```php
add_action( 'plugins_loaded', function() {
    if ( function_exists( 'wp_get_content_guidelines_packet' ) ) {
        // Content Guidelines plugin is active
    }
} );
```

For WordPress 6.9+ with the Abilities API:

```php
if ( function_exists( 'wp_get_ability' ) ) {
    $ability = wp_get_ability( 'content-guidelines/get-context-packet' );
    if ( $ability ) {
        $result = $ability->execute( array( 'task' => 'writing' ) );
    }
}
```

## Performance Notes

- Guidelines are cached in the post meta and only fetched once per request
- The `packet_text` is generated on-demand but is lightweight
- For high-traffic sites, consider caching the packet output in a transient

```php
function get_cached_guidelines_packet( $task = 'writing' ) {
    $cache_key = 'cg_packet_' . $task;
    $packet = get_transient( $cache_key );

    if ( false === $packet ) {
        $packet = wp_get_content_guidelines_packet( array( 'task' => $task ) );
        set_transient( $cache_key, $packet, HOUR_IN_SECONDS );
    }

    return $packet;
}
```
