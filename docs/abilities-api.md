# Abilities API Reference (WordPress 6.9+)

The Content Guidelines plugin registers abilities with the WordPress 6.9 Abilities API, enabling AI assistants and external services to discover and execute guidelines-related actions.

## Overview

The Abilities API provides a standardized way for AI systems (like ChatGPT, Claude, Gemini) to interact with WordPress through the Model Context Protocol (MCP). When paired with an MCP adapter plugin, these abilities become available to AI assistants.

## Available Abilities

All abilities are registered under the `content-guidelines` category.

### content-guidelines/get-guidelines

Retrieve the site content guidelines.

**Input:**
```json
{
  "use": "active"  // or "draft"
}
```

**Output:**
```json
{
  "active": { /* guidelines object */ },
  "draft": null,
  "has_draft": false,
  "post_id": 123,
  "updated_at": "2025-01-15T10:30:00",
  "revision_count": 5
}
```

### content-guidelines/get-context-packet

Get a task-specific context packet formatted for LLM consumption.

**Input:**
```json
{
  "task": "writing",      // writing, headline, cta, image, coach
  "post_id": 123,         // optional
  "use": "active",        // active or draft
  "max_chars": 2000       // 100-10000
}
```

**Output:**
```json
{
  "packet_text": "## SITE CONTENT GUIDELINES\n\n...",
  "packet_structured": { /* relevant sections */ },
  "guidelines_id": 123,
  "revision_id": 456,
  "updated_at": "2025-01-15T10:30:00"
}
```

### content-guidelines/update-draft

Save changes to draft guidelines without publishing.

**Input:**
```json
{
  "guidelines": {
    "brand_context": {
      "site_description": "Updated description",
      "audience": "Our target readers",
      "primary_goal": "inform"
    },
    "voice_tone": {
      "tone_traits": ["friendly", "professional"],
      "pov": "we_you",
      "readability": "general"
    }
  }
}
```

**Output:**
```json
{
  "success": true,
  "message": "Draft saved."
}
```

### content-guidelines/publish-draft

Publish the current draft guidelines.

**Input:** None required

**Output:**
```json
{
  "success": true,
  "post_id": 123,
  "message": "Guidelines published."
}
```

### content-guidelines/discard-draft

Discard all unpublished draft changes.

**Input:** None required

**Output:**
```json
{
  "success": true,
  "message": "Draft discarded."
}
```

### content-guidelines/run-test

Test guidelines against fixture content with lint checks and optional AI generation.

**Input:**
```json
{
  "task": "rewrite_intro",    // rewrite_intro, generate_headlines, write_cta
  "fixture_post_id": 789,     // required
  "use": "draft",
  "compare": false,
  "extra_instructions": ""
}
```

**Output:**
```json
{
  "lint_results": {
    "issues": [],
    "issue_count": 0
  },
  "context_packet": { /* packet data */ },
  "fixture": {
    "title": "Post Title",
    "excerpt": "Post excerpt..."
  },
  "ai_result": null,
  "ai_available": false
}
```

### content-guidelines/check-lint

Run vocabulary and copy rule lint checks against content.

**Input:**
```json
{
  "content": "Text to check against guidelines...",
  "use": "active"
}
```

**Output:**
```json
{
  "issues": [
    {
      "type": "vocabulary_avoid",
      "term": "green",
      "message": "Avoid using 'green'",
      "suggestion": "Use 'sustainable' instead"
    }
  ],
  "issue_count": 1,
  "passed": false
}
```

## Using Abilities in PHP

```php
// Check if Abilities API is available (WordPress 6.9+)
if ( function_exists( 'wp_get_ability' ) ) {

    // Get the ability
    $ability = wp_get_ability( 'content-guidelines/get-context-packet' );

    if ( $ability ) {
        // Execute the ability
        $result = $ability->execute( array(
            'task'      => 'headline',
            'max_chars' => 1500,
        ) );

        if ( ! is_wp_error( $result ) ) {
            $packet_text = $result['packet_text'];
        }
    }
}
```

## Using Abilities via REST API

All abilities are exposed via the WordPress Abilities REST API:

```bash
# List all content-guidelines abilities
curl -X GET \
  'https://example.com/wp-json/wp-abilities/v1/abilities?category=content-guidelines' \
  -H 'Authorization: Basic BASE64_ENCODED_APP_PASSWORD'

# Execute an ability
curl -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/content-guidelines/get-context-packet/run' \
  -H 'Authorization: Basic BASE64_ENCODED_APP_PASSWORD' \
  -H 'Content-Type: application/json' \
  -d '{"task":"writing","max_chars":2000}'
```

## MCP Integration

When using the WordPress MCP Adapter plugin, these abilities become available to AI assistants as MCP tools. The AI can:

1. **Discover** available abilities through the MCP server
2. **Execute** abilities with validated input
3. **Receive** structured output

Example AI interaction:

```
User: "What are the content guidelines for this WordPress site?"

AI: [Executes content-guidelines/get-guidelines ability]
    "Based on the site guidelines, the tone should be friendly and
    encouraging, written from a 'we' perspective speaking to 'you'..."
```

## Permissions

All abilities require the `edit_theme_options` capability, which is typically granted to Administrators.

| Ability | Permission |
|---------|------------|
| get-guidelines | edit_theme_options |
| get-context-packet | edit_theme_options |
| update-draft | edit_theme_options |
| publish-draft | edit_theme_options |
| discard-draft | edit_theme_options |
| run-test | edit_theme_options |
| check-lint | edit_theme_options |

## Backward Compatibility

The Abilities API is only available in WordPress 6.9+. The plugin gracefully degrades on older versions:

```php
// In the plugin initialization
public static function init() {
    // Only register if Abilities API is available
    if ( ! function_exists( 'wp_register_ability' ) ) {
        return;
    }

    // Register abilities...
}
```

For older WordPress versions, use the [PHP API](./php-api.md) or [REST API](./rest-api.md) directly.
