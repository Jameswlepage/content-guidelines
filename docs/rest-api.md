# REST API Reference

All endpoints require authentication and the `edit_theme_options` capability.

## Base URL

```
/wp-json/wp/v2/content-guidelines
```

## Authentication

Use any standard WordPress REST API authentication method:

- **Cookie Authentication** (for logged-in users)
- **Application Passwords** (recommended for external apps)
- **OAuth 2.0** (via plugin)

## Endpoints

### GET /content-guidelines

Get the current guidelines state including active, draft, and metadata.

**Response:**

```json
{
  "active": {
    "version": 1,
    "brand_context": {
      "site_description": "A blog about sustainable living",
      "audience": "Environmentally conscious millennials",
      "primary_goal": "inform"
    },
    "voice_tone": {
      "tone_traits": ["friendly", "encouraging"],
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
    "notes": ""
  },
  "draft": null,
  "has_draft": false,
  "post_id": 123,
  "updated_at": "2025-01-15T10:30:00",
  "revision_count": 5
}
```

### PUT /content-guidelines/draft

Save draft guidelines without publishing.

**Request Body:**

```json
{
  "guidelines": {
    "brand_context": {
      "site_description": "Updated description"
    }
  }
}
```

**Response:**

```json
{
  "success": true,
  "message": "Draft saved."
}
```

### POST /content-guidelines/publish

Publish the current draft, making it the active guidelines.

**Response:**

```json
{
  "success": true,
  "post_id": 123,
  "message": "Guidelines published."
}
```

### POST /content-guidelines/discard-draft

Discard all unpublished draft changes.

**Response:**

```json
{
  "success": true,
  "message": "Draft discarded."
}
```

### GET /content-guidelines/packet

Get a task-specific context packet for AI consumption.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `task` | string | `writing` | Task type: `writing`, `headline`, `cta`, `image`, `coach` |
| `post_id` | integer | - | Optional context post ID |
| `use` | string | `active` | Guidelines version: `active` or `draft` |
| `max_chars` | integer | `2000` | Maximum characters for packet_text |
| `block_name` | string | - | Optional: include block-specific rules (e.g., `core/button`) |

**Example Request:**

```
GET /wp-json/wp/v2/content-guidelines/packet?task=cta&block_name=core/button
```

**Response:**

```json
{
  "packet_text": "## SITE CONTENT GUIDELINES\n\n### Voice & Tone\nTone: friendly, encouraging\nPoint of view: Write as \"we\" speaking to \"you\"\n...",
  "packet_structured": {
    "voice_tone": {
      "tone_traits": ["friendly", "encouraging"],
      "pov": "we_you",
      "readability": "general"
    },
    "copy_rules": {
      "dos": ["Use active voice"],
      "donts": ["Avoid jargon"]
    },
    "vocabulary": {
      "prefer": [{"term": "sustainable"}],
      "avoid": [{"term": "green"}]
    }
  },
  "guidelines_id": 123,
  "revision_id": 456,
  "updated_at": "2025-01-15T10:30:00"
}
```

### GET /content-guidelines/for-post/{post_id}

**Primary endpoint for agents working with post content.** Analyzes the blocks in a post and returns guidelines with block-specific rules merged.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `post_id` | integer | The post ID to analyze |

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `task` | string | `writing` | Task type |
| `use` | string | `active` | Guidelines version: `active` or `draft` |

**Example Request:**

```
GET /wp-json/wp/v2/content-guidelines/for-post/123?task=writing
```

**Response:**

```json
{
  "packet_text": "## SITE CONTENT GUIDELINES\n\nAbout this site: ...\n\n### Block-Specific Rules\n\n**core/button:**\nDO:\n- Use action verbs\n...",
  "packet_structured": {
    "brand_context": { ... },
    "voice_tone": { ... }
  },
  "blocks_in_post": [
    "core/paragraph",
    "core/heading",
    "core/image",
    "core/button"
  ],
  "block_guidelines": {
    "core/button": {
      "copy_rules": {
        "dos": ["Use action verbs", "Keep under 5 words"],
        "donts": ["Don't use 'Click here'"]
      },
      "notes": "Button text should create urgency"
    }
  },
  "guidelines_id": 42,
  "updated_at": "2025-01-15T10:30:00"
}
```

### GET /content-guidelines/blocks

Get guidelines for multiple block types in a single request.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `blocks` | array | required | Block names (array or comma-separated) |
| `task` | string | `writing` | Task type |
| `use` | string | `active` | Guidelines version: `active` or `draft` |

**Example Requests:**

```
# Array syntax
GET /wp-json/wp/v2/content-guidelines/blocks?blocks[]=core/heading&blocks[]=core/button

# Comma-separated
GET /wp-json/wp/v2/content-guidelines/blocks?blocks=core/heading,core/paragraph,core/button
```

**Response:**

```json
{
  "site_rules": {
    "dos": ["Use active voice", "Keep sentences short"],
    "donts": ["Avoid jargon", "Don't use passive voice"]
  },
  "blocks": {
    "core/heading": null,
    "core/paragraph": {
      "copy_rules": {
        "dos": ["Limit to 3-4 sentences"],
        "donts": ["Don't start with 'In this article'"]
      }
    },
    "core/button": {
      "copy_rules": {
        "dos": ["Use action verbs"],
        "donts": ["Don't use 'Click here'"]
      },
      "notes": "Keep CTA text under 5 words"
    }
  },
  "packet_text": "## CONTENT GUIDELINES\n\n### Site Rules\nDO:\n- Use active voice\n...",
  "guidelines_id": 42
}
```

### GET /content-guidelines/revisions

Get revision history.

**Response:**

```json
[
  {
    "id": 456,
    "author": {
      "id": 1,
      "name": "Admin"
    },
    "date": "2025-01-15 10:30:00",
    "date_gmt": "2025-01-15 10:30:00",
    "modified": "2025-01-15 10:30:00",
    "modified_gmt": "2025-01-15 10:30:00"
  }
]
```

### POST /content-guidelines/restore/{id}

Restore a specific revision.

**Response:**

```json
{
  "success": true,
  "post_id": 123,
  "message": "Revision restored."
}
```

### POST /content-guidelines/test

Run a playground test against fixture content.

**Request Body:**

```json
{
  "task": "rewrite_intro",
  "fixture_post_id": 789,
  "use": "draft",
  "compare": true,
  "extra_instructions": "Make it more casual"
}
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `task` | string | `rewrite_intro` | Test type: `rewrite_intro`, `generate_headlines`, `write_cta` |
| `fixture_post_id` | integer | required | Post ID to use as test content |
| `use` | string | `draft` | Guidelines version to test |
| `compare` | boolean | `false` | Also run with active guidelines for comparison |
| `extra_instructions` | string | - | Additional instructions for AI |

**Response:**

```json
{
  "lint_results": {
    "issues": [
      {
        "type": "vocabulary_avoid",
        "term": "green",
        "message": "Avoid using 'green'",
        "suggestion": "Use 'sustainable' instead"
      }
    ],
    "issue_count": 1
  },
  "context_packet": {
    "packet_text": "...",
    "packet_structured": {}
  },
  "fixture": {
    "title": "How to Live Sustainably",
    "excerpt": "This article covers..."
  },
  "ai_result": {
    "output": "Rewritten intro text...",
    "metadata": {}
  },
  "ai_available": true,
  "compare": {
    "lint_results": {},
    "context_packet": {},
    "ai_result": {}
  }
}
```

## Usage Examples

### JavaScript (wp.apiFetch)

```javascript
// Get guidelines
const guidelines = await wp.apiFetch({
  path: '/wp/v2/content-guidelines'
});

// Get context packet for AI
const packet = await wp.apiFetch({
  path: '/wp/v2/content-guidelines/packet?task=headline'
});

// Save draft
await wp.apiFetch({
  path: '/wp/v2/content-guidelines/draft',
  method: 'PUT',
  data: {
    guidelines: {
      brand_context: {
        site_description: 'New description'
      }
    }
  }
});

// Publish
await wp.apiFetch({
  path: '/wp/v2/content-guidelines/publish',
  method: 'POST'
});
```

### cURL

```bash
# Get guidelines
curl -X GET \
  'https://example.com/wp-json/wp/v2/content-guidelines' \
  -H 'Authorization: Basic BASE64_ENCODED_APP_PASSWORD'

# Get context packet
curl -X GET \
  'https://example.com/wp-json/wp/v2/content-guidelines/packet?task=writing' \
  -H 'Authorization: Basic BASE64_ENCODED_APP_PASSWORD'

# Save draft
curl -X PUT \
  'https://example.com/wp-json/wp/v2/content-guidelines/draft' \
  -H 'Authorization: Basic BASE64_ENCODED_APP_PASSWORD' \
  -H 'Content-Type: application/json' \
  -d '{"guidelines":{"brand_context":{"site_description":"New description"}}}'
```

### Python

```python
import requests
from requests.auth import HTTPBasicAuth

base_url = 'https://example.com/wp-json/wp/v2/content-guidelines'
auth = HTTPBasicAuth('username', 'application_password')

# Get context packet
response = requests.get(
    f'{base_url}/packet',
    params={'task': 'writing'},
    auth=auth
)
packet = response.json()

# Use packet_text in your AI prompt
prompt = packet['packet_text'] + '\n\nWrite a blog post about...'
```

## Error Responses

All endpoints return standard WordPress REST API errors:

```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {
    "status": 403
  }
}
```

Common error codes:

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `rest_forbidden` | 403 | User lacks required capability |
| `invalid_fixture` | 400 | Invalid fixture post ID |
| `no_draft` | 400 | No draft to publish |
| `invalid_revision` | 400 | Revision not found |
