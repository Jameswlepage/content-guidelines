# Content Guidelines

Site-level editorial guidelines for WordPress. Define voice, tone, copy rules, and vocabulary that AI features can consume.

**Global Styles = how your site looks. Content Guidelines = how your site sounds.**

## Installation

1. Download the plugin
2. Upload to `/wp-content/plugins/content-guidelines`
3. Activate via Plugins menu
4. Access via **Appearance > Guidelines**

## Quick Start

### Agent Working with a Post (Recommended)

```php
// Get guidelines with automatic block analysis
// This merges site-level AND block-specific rules for all blocks in the post
$result = wp_get_content_guidelines_for_post( $post_id );

// Use in your AI prompt
$prompt = $result['packet_text'] . "\n\nImprove this content:\n" . $post->post_content;

// Also available: list of blocks found and their specific guidelines
$result['blocks_in_post'];    // ['core/paragraph', 'core/heading', 'core/button']
$result['block_guidelines'];  // Per-block rules for blocks with custom guidelines
```

### Get Guidelines for Specific Blocks

```php
// When you know which blocks you're working with
$result = wp_get_block_guidelines( array( 'core/button', 'core/heading' ) );

// Returns site_rules + block-specific rules in a ready-to-use packet
$prompt = $result['packet_text'];
```

### Get Guidelines via REST API

```bash
# Get guidelines for a specific post (with block analysis)
curl -X GET \
  'https://yoursite.com/wp-json/wp/v2/content-guidelines/for-post/123' \
  -H 'Authorization: Basic YOUR_APP_PASSWORD'

# Get guidelines for specific blocks
curl -X GET \
  'https://yoursite.com/wp-json/wp/v2/content-guidelines/blocks?blocks=core/button,core/heading' \
  -H 'Authorization: Basic YOUR_APP_PASSWORD'
```

### Basic Packet (Without Block Context)

```php
$packet = wp_get_content_guidelines_packet( array(
    'task' => 'writing',  // or 'headline', 'cta', 'image', 'coach'
) );
```

## Documentation

| Document | Description |
|----------|-------------|
| [PHP API](./docs/php-api.md) | PHP functions and usage examples |
| [REST API](./docs/rest-api.md) | REST endpoints and authentication |
| [Abilities API](./docs/abilities-api.md) | WordPress 6.9+ Abilities integration |
| [Integration Guide](./docs/integration-guide.md) | How to build AI provider plugins |

## Features

- **Site-level guidelines** - One source of truth for editorial voice
- **Draft/Publish workflow** - Iterate safely without affecting production
- **Version history** - Full revision history with restore capability
- **Playground** - Test changes against real content before publishing
- **Block-specific rules** - Set guidelines per block type
- **AI-agnostic** - Works with any AI provider

## For AI Providers

Content Guidelines provides storage and UI. Your plugin provides AI:

```php
// Register as an AI provider
add_filter( 'wp_content_guidelines_has_ai_provider', '__return_true' );

// Handle playground tests
add_filter( 'wp_content_guidelines_run_playground_test', function( $result, $request ) {
    // $request['context_packet']['packet_text'] contains formatted guidelines
    // $request['fixture_content'] contains the test content
    // Return: array( 'output' => 'AI generated text...' )
    return my_ai_generate( $request );
}, 10, 2 );
```

See [Integration Guide](./docs/integration-guide.md) for complete examples.

## Data Schema

The schema follows the `theme.json` pattern used in WordPress core:

```json
{
  "version": 1,
  "brand_context": {
    "site_description": "About this site",
    "audience": "Target readers",
    "primary_goal": "inform"
  },
  "voice_tone": {
    "tone_traits": ["friendly", "professional"],
    "pov": "we_you",
    "readability": "general"
  },
  "copy_rules": {
    "dos": ["Use active voice"],
    "donts": ["Avoid jargon"],
    "formatting": ["h2s", "bullets"]
  },
  "vocabulary": {
    "prefer": [{"term": "sustainable", "note": "Our core value"}],
    "avoid": [{"term": "green", "note": "Too vague"}]
  },
  "image_style": {
    "dos": ["Natural lighting"],
    "donts": ["Stock photos"],
    "text_policy": "never"
  },
  "notes": "Additional context...",
  "blocks": {
    "core/paragraph": {
      "copy_rules": {
        "dos": ["Limit paragraphs to 3-4 sentences"],
        "donts": ["Don't start with 'In this article'"]
      },
      "notes": "Keep paragraphs scannable"
    },
    "core/button": {
      "copy_rules": {
        "dos": ["Use action verbs", "Keep under 5 words"],
        "donts": ["Don't use 'Click here'", "Avoid 'Submit'"]
      }
    }
  }
}
```

## Requirements

- WordPress 6.7+
- PHP 7.4+
- Gutenberg plugin (for full UI)

For WordPress 6.9+, the plugin also registers with the Abilities API for AI assistant integration.

## Related Docs

- [Full Specification](./SPEC.md) - Complete product and technical spec
- [Summary](./SUMMARY.md) - One-page overview for stakeholders
- [Implementation Notes](./IMPLEMENTATION.md) - Development details

## License

GPL-2.0-or-later
