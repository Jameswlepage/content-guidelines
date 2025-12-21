# Content Guidelines

Site-level editorial guidelines for WordPress. Define voice, tone, copy rules, and vocabulary that AI features can consume.

**Global Styles = how your site looks. Content Guidelines = how your site sounds.**

## Installation

1. Download the plugin
2. Upload to `/wp-content/plugins/content-guidelines`
3. Activate via Plugins menu
4. Access via **Appearance > Guidelines**

## Quick Start

### Get Guidelines in PHP

```php
// Get a context packet formatted for AI prompts
$packet = wp_get_content_guidelines_packet( array(
    'task' => 'writing',  // or 'headline', 'cta', 'image', 'coach'
) );

// Use in your AI prompt
$prompt = $packet['packet_text'] . "\n\nWrite a headline for: " . $title;
```

### Get Guidelines via REST API

```bash
# Get the context packet
curl -X GET \
  'https://yoursite.com/wp-json/wp/v2/content-guidelines/packet?task=writing' \
  -H 'Authorization: Basic YOUR_APP_PASSWORD'
```

### Get Guidelines via Abilities API (WordPress 6.9+)

```php
$ability = wp_get_ability( 'content-guidelines/get-context-packet' );
$result = $ability->execute( array( 'task' => 'headline' ) );
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
  "notes": "Additional context..."
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
