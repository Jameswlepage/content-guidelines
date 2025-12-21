# Content Guidelines for WordPress Core

A site-level editorial profile that feeds into all AI experiences in WordPress.

**Global Styles = how your site looks. Content Guidelines = how your site sounds.**

## Quick Links

- [Full Specification](./SPEC.md) — Complete product and technical spec
- [Summary](./SUMMARY.md) — One-page overview for stakeholders

## What This Is

Content Guidelines stores your site's tone, voice, copy rules, vocabulary, and brand context in a single canonical location. Every AI feature in WordPress can reference it, so you don't have to re-explain your brand every time.

### Key Features

- **Site-level guidelines** — One source of truth for editorial voice
- **Draft → Publish workflow** — Iterate safely without affecting production AI
- **Versioning** — Full history with restore capability
- **Playground** — Test changes instantly against real content
- **Provider-agnostic** — Core owns storage/UI/APIs; any AI provider can plug in

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     WordPress Core                               │
├─────────────────────────────────────────────────────────────────┤
│  wp_content_guidelines (CPT)                                     │
│  ├── post_content (JSON) ← Active guidelines, versioned         │
│  └── post_meta: _wp_content_guidelines_draft ← Draft state      │
├─────────────────────────────────────────────────────────────────┤
│  PHP API: wp_get_content_guidelines_packet()                     │
│  REST: /wp/v2/content-guidelines/*                               │
├─────────────────────────────────────────────────────────────────┤
│  UI: Site Editor → Guidelines                                    │
│  ├── Guidelines tab (structured editor)                          │
│  ├── Playground tab (test loop)                                  │
│  └── History (versioning)                                        │
├─────────────────────────────────────────────────────────────────┤
│  Provider Hooks (AI-agnostic)                                    │
│  ├── wp_ai_generate_content_guidelines_draft                     │
│  └── wp_ai_run_guidelines_playground_task                        │
└─────────────────────────────────────────────────────────────────┘
         ↓                    ↓                    ↓
    Jetpack AI           VIP Plugins          Third-party
```

## Data Schema (v1)

```json
{
  "version": 1,
  "brand_context": {
    "site_description": "",
    "audience": "",
    "primary_goal": "subscribe|sell|inform|community|other",
    "topics": []
  },
  "voice_tone": {
    "tone_traits": [],
    "pov": "we_you|i_you|third_person",
    "readability": "simple|general|expert"
  },
  "copy_rules": {
    "dos": [],
    "donts": [],
    "formatting": []
  },
  "vocabulary": {
    "prefer": [],
    "avoid": []
  },
  "image_style": {
    "dos": [],
    "donts": [],
    "text_policy": "never|only_if_requested|ok"
  },
  "notes": ""
}
```

## REST API

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/wp/v2/content-guidelines` | Get active + draft |
| `PUT` | `/wp/v2/content-guidelines/draft` | Update draft |
| `POST` | `/wp/v2/content-guidelines/publish` | Promote draft → active |
| `POST` | `/wp/v2/content-guidelines/discard-draft` | Clear draft |
| `GET` | `/wp/v2/content-guidelines/revisions` | List history |
| `POST` | `/wp/v2/content-guidelines/restore/{id}` | Restore revision |
| `POST` | `/wp/v2/content-guidelines/test` | Run Playground task |

## PHP API

```php
// Get context packet for AI consumption
$packet = wp_get_content_guidelines_packet( array(
    'task'      => 'headline',  // writing, headline, cta, image, coach
    'post_id'   => 123,         // optional context
    'use'       => 'active',    // or 'draft'
    'max_chars' => 2000,        // token budget
) );

// Returns:
// - packet_text: formatted string for LLM prompt
// - packet_structured: array subset of schema
// - guidelines_id, revision_id, updated_at
```

## Provider Hooks

```php
// Generation hook (providers supply AI)
add_filter( 'wp_ai_generate_content_guidelines_draft', function( $draft, $site_context, $args ) {
    // Generate structured guidelines from site content
    return $generated_draft;
}, 10, 3 );

// Playground hook (providers supply AI)
add_filter( 'wp_ai_run_guidelines_playground_task', function( $result, $request_args ) {
    // Run task using guidelines + fixture content
    return array( 'output_text' => $generated_output );
}, 10, 2 );
```

## Implementation Milestones

1. **Scaffolding** — CPT, REST, basic UI
2. **Versioning** — Revisions, history, restore
3. **Playground** — Test loop, fixture selection, compare mode
4. **Auto-Generation** — Generate from site content (provider-dependent)
5. **AI Surface Integration** — "Using: Site guidelines" chip everywhere

## Prior Art / Alignment

This follows established WordPress Core patterns:

- Storage: Like `wp_global_styles` (CPT with revisions)
- UI: Like Styles panel in Site Editor
- Resolution: Like `WP_Theme_JSON_Resolver` (merge defaults + user overrides)

## Status

Spec complete. Ready for engineering scoping and implementation.
