# Content Guidelines for WordPress Core

## Product Specification v1.0

**Status:** Final MVP Spec
**Target:** WordPress Core / Gutenberg
**Last Updated:** December 2024

---

## Executive Summary

Content Guidelines is a site-level, user-controlled editorial profile that stores tone, voice, copy rules, vocabulary, and brand context. It feeds into all AI experiences in WordPress via a canonical context packet API, enabling consistent, on-brand AI outputs without requiring users to re-explain themselves.

**Mental model:** Global Styles defines how the site *looks*. Content Guidelines defines how the site *sounds*.

---

## Problem & Opportunity

### Customer Problems

1. **Blank-page paralysis:** Users struggle with what to publish
2. **Brand drift:** Inconsistent voice across posts/pages/newsletters with multiple authors
3. **Prompt fatigue:** Re-explaining tone/rules/goals repeatedly to AI tools
4. **Enterprise governance:** Need auditability (who changed what when) and safe iteration

### Why WordPress Can Win

WordPress has first-party context (site content, About page, authorship history). This feature turns that into stable, reusable editorial context that improves AI output quality across all tools.

---

## Goals & Non-Goals

### Goals (MVP)

- Central guidelines hub: one canonical, editable site-level record
- Draft → Publish workflow: safe iteration without affecting production behavior
- Versioning: view history + restore prior versions
- Iteration loop: built-in Playground to test changes against example content
- Extensibility: provider-agnostic generation/testing + public APIs for plugins/MCP

### Non-Goals (Explicitly Deferred)

- Multiple guideline sets (per section/category/brand line)
- Full editorial approval workflow beyond WordPress capabilities
- Per-author style profiles
- Automated drift detection across the entire archive
- Rich diff visualizations between guideline versions

---

## Architecture & Conventions

### Alignment with Modern WordPress

This feature follows established WordPress Core patterns:

| Concept | Precedent | Content Guidelines Approach |
|---------|-----------|----------------------------|
| Site-wide entity storage | `wp_global_styles` CPT | `wp_content_guidelines` CPT |
| Revisions/History | Global Styles revisions REST controller | Same pattern for guidelines |
| Resolution/Merge | `WP_Theme_JSON_Resolver` | Guidelines resolver with context packet builder |
| UI Location | Site Editor → Styles | Site Editor → Guidelines |
| Alternate Views | Style Book, Style Revisions | Playground, History |

### Why CPT Over Options

- **Revisions:** Built-in version history for rollback
- **REST Support:** First-class API with existing infrastructure
- **Capabilities:** Standard permission model
- **Consistency:** Matches templates, template parts, global styles patterns

### Scope: Per-Site (Not Per-Theme)

Unlike Global Styles (theme-scoped), Content Guidelines is **per-site** because:
- Editorial voice shouldn't change when the theme changes
- AI experiences should remain consistent across theme swaps

---

## Data Model

### Entity Definition

```php
// Custom Post Type Registration
register_post_type( 'wp_content_guidelines', array(
    'public'       => false,
    'show_in_rest' => true,
    'supports'     => array( 'revisions', 'custom-fields' ),
    'capabilities' => array(
        'edit_post'   => 'edit_theme_options',
        'read_post'   => 'edit_theme_options',
        'delete_post' => 'edit_theme_options',
    ),
) );
```

**Constraint:** Exactly one record per site (create on first access).

### Storage Strategy

| Data | Location | Rationale |
|------|----------|-----------|
| Active Guidelines | `post_content` (JSON) | Versioned via WP revisions |
| Draft Guidelines | `_wp_content_guidelines_draft` meta | Frequent saves without revision spam |
| User's Fixture Selection | User meta or localStorage | Per-user preference |
| Generation Sources | `_wp_content_guidelines_sources` meta | Provenance for regeneration |

### JSON Schema (v1)

```json
{
  "version": 1,
  "brand_context": {
    "site_description": "string",
    "audience": "string",
    "primary_goal": "subscribe|sell|inform|community|other",
    "topics": ["string"]
  },
  "voice_tone": {
    "tone_traits": ["warm", "confident", "plain-english"],
    "pov": "we_you|i_you|third_person",
    "readability": "simple|general|expert",
    "examples": {
      "good": "string",
      "avoid": "string"
    }
  },
  "copy_rules": {
    "dos": ["string"],
    "donts": ["string"],
    "formatting": ["h2s", "bullets", "short_paragraphs", "single_cta"]
  },
  "vocabulary": {
    "prefer": [{ "term": "string", "note": "string" }],
    "avoid": [{ "term": "string", "note": "string" }]
  },
  "image_style": {
    "dos": ["string"],
    "donts": ["string"],
    "text_policy": "never|only_if_requested|ok"
  },
  "notes": "string",
  "generation": {
    "reference_posts": [123, 456],
    "sources": ["about_page", "homepage", "recent_posts"]
  }
}
```

**Extensibility:** Unknown keys should be tolerated for plugin-added sections.

---

## API Design

### PHP API

```php
/**
 * Get context packet for AI consumption.
 *
 * @param array $args {
 *     @type string $task       Task type: 'writing', 'headline', 'cta', 'image', 'coach'
 *     @type int    $post_id    Optional. Context post ID.
 *     @type string $use        'active' or 'draft'. Default 'active'.
 *     @type int    $max_chars  Token budget proxy. Default 2000.
 *     @type string $locale     Optional. For multilingual sites.
 * }
 * @return array {
 *     @type string $packet_text       Formatted text for LLM prompt.
 *     @type array  $packet_structured Subset of schema relevant to task.
 *     @type int    $guidelines_id     Post ID of guidelines entity.
 *     @type int    $revision_id       Current revision ID.
 *     @type string $updated_at        ISO 8601 timestamp.
 * }
 */
function wp_get_content_guidelines_packet( array $args ): array;

/**
 * Get raw guidelines data.
 *
 * @param string $use 'active' or 'draft'.
 * @return array|null Schema-compliant array or null if not set.
 */
function wp_get_content_guidelines( string $use = 'active' ): ?array;
```

### REST Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/wp/v2/content-guidelines` | Returns `{ active, draft, hasDraft, updatedAt, revisionCount }` |
| `PUT` | `/wp/v2/content-guidelines/draft` | Update draft |
| `POST` | `/wp/v2/content-guidelines/publish` | Promote draft → active |
| `POST` | `/wp/v2/content-guidelines/discard-draft` | Clear draft |
| `GET` | `/wp/v2/content-guidelines/revisions` | List revision history |
| `POST` | `/wp/v2/content-guidelines/restore/{id}` | Restore a revision |
| `POST` | `/wp/v2/content-guidelines/test` | Run Playground task |
| `POST` | `/wp/v2/content-guidelines/generate` | Generate draft from sources |

### Provider Hooks (AI-Agnostic)

```php
/**
 * Generate guidelines draft from site content.
 * Core provides UI; providers supply generation.
 */
apply_filters(
    'wp_ai_generate_content_guidelines_draft',
    $draft_json,      // Empty array or partial draft
    $site_context,    // { title, tagline, source_content[] }
    $args             // { goal, constraints }
);

/**
 * Run Playground test task.
 */
apply_filters(
    'wp_ai_run_guidelines_playground_task',
    $result,          // null initially
    $request_args     // { task, fixture_content, guidelines, extra_instructions }
);
```

**No provider installed:** Return structured error; UI shows context preview + local lint checks.

---

## User Interface

### Entry Points

| Location | Context | Description |
|----------|---------|-------------|
| **Site Editor → Guidelines** | Block themes | Primary home (peer to Styles) |
| **Settings → Writing → Content Guidelines** | Universal | wp-admin fallback for classic themes |
| **AI surfaces (chip)** | Everywhere | "Using: Site guidelines" + Edit link |

### Screen Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ← Editor    Content Guidelines    [Active ●]    [Save draft]  [Publish ▼] │
│                                                          ⋯ (History, etc.) │
├─────────────────────────────────────────────────────────────────────────────┤
│  Preview content: [ Search post/page… ▼ ]                                   │
├───────────────────────────────────┬─────────────────────────────────────────┤
│                                   │  [ Guidelines ]  [ Playground ]         │
│   PREVIEW CANVAS                  │                                         │
│   (renders selected fixture       │  ▾ Brand & site context                 │
│    post/page as blocks)           │  ▾ Voice & tone                         │
│                                   │  ▾ Copy rules                           │
│                                   │  ▾ Vocabulary                           │
│                                   │  ▾ Images                               │
│                                   │  ▾ Notes                                │
│                                   │                                         │
│                                   │  ┌─────────────────────────────────┐    │
│                                   │  │ ⚠ Draft changes not published  │    │
│                                   │  │ [Publish] [Discard]            │    │
│                                   │  └─────────────────────────────────┘    │
└───────────────────────────────────┴─────────────────────────────────────────┘
```

### Tab: Guidelines (Editor)

#### Panel: Brand & Site Context

| Field | Type | Description |
|-------|------|-------------|
| What is this site about? | Textarea | Core brand description |
| Audience | TextControl | Target reader/customer |
| Primary goal | SelectControl | Subscribe / Sell / Inform / Community / Other |
| Topics/coverage areas | TokenField | Optional categories/themes |

#### Panel: Voice & Tone

| Field | Type | Description |
|-------|------|-------------|
| Tone traits | TokenField (chips) | e.g., "warm", "confident", "plain English" |
| Point of view | SelectControl | "we → you" / "I → you" / "third person" |
| Readability | SelectControl | Simple / General / Expert |
| Good example | Textarea | Optional example sentence |
| Avoid example | Textarea | Optional anti-pattern |

#### Panel: Copy Rules

| Field | Type | Description |
|-------|------|-------------|
| Do list | Repeater | Rules to follow |
| Don't list | Repeater | Rules to avoid |
| Formatting defaults | CheckboxControl group | H2s, bullets, short paragraphs, single CTA |

#### Panel: Vocabulary

| Field | Type | Description |
|-------|------|-------------|
| Prefer these terms | Repeater (term + note) | Brand terminology |
| Avoid these terms | Repeater (term + note) | Banned words |

#### Panel: Images

| Field | Type | Description |
|-------|------|-------------|
| Preferred style | Repeater | "Clean, minimal, editorial" |
| Avoid | Repeater | "No stock photo vibes" |
| Text in images | SelectControl | Never / Only if asked / OK |

#### Panel: Notes

| Field | Type | Description |
|-------|------|-------------|
| Other notes | Textarea | Freeform "anything else AI should know" |

### Tab: Playground (Iteration Loop)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  PLAYGROUND                                                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│  Use guidelines:  (● Draft)  (○ Active)     ☐ Compare Draft vs Active       │
├─────────────────────────────────────────────────────────────────────────────┤
│  Task: [ Rewrite intro paragraph          ▼ ]                               │
│                                                                             │
│  Extra instructions (optional):                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                                                                     │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                             │
│  [ Run ]                                                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│  RESULTS                                                                    │
│  ┌──────────────────────────────┐  ┌──────────────────────────────┐         │
│  │ Draft                        │  │ Active                       │         │
│  │                              │  │                              │         │
│  │ "Your intro rewritten with  │  │ "Your intro rewritten with  │         │
│  │  draft guidelines..."        │  │  active guidelines..."       │         │
│  │                              │  │                              │         │
│  │ [Copy]                       │  │ [Copy]                       │         │
│  └──────────────────────────────┘  └──────────────────────────────┘         │
├─────────────────────────────────────────────────────────────────────────────┤
│  ▸ Context preview (what was sent to AI)                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│  LOCAL CHECKS (always available)                                            │
│  ⚠ "utilize" found — prefer "use"                                           │
│  ⚠ "best in class" found — avoid superlatives                               │
└─────────────────────────────────────────────────────────────────────────────┘
```

#### Preset Tasks (MVP)

1. **Rewrite intro paragraph** — tests tone + clarity
2. **Generate 5 headline options** — tests voice + claims rules + glossary
3. **Write a CTA paragraph** — tests goal alignment + urgency rules

#### No-Provider Fallback

When no AI provider is installed:
- "Run" returns friendly message: "No AI provider connected."
- Still shows: Context preview + Local lint checks
- Playground remains useful for validation

### Screen: History

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ← Guidelines                        HISTORY                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Dec 19, 2024 — Published by Jane Editor                                    │
│  "Updated vocabulary rules for Q1 campaign"                                 │
│                                                              [Preview] [Restore]│
│  ─────────────────────────────────────────────────────────────────────────  │
│  Dec 15, 2024 — Published by Scott Admin                                    │
│  "Initial guidelines from generation"                                       │
│                                                              [Preview] [Restore]│
│  ─────────────────────────────────────────────────────────────────────────  │
│  Dec 12, 2024 — Published by Maeve Lander                                   │
│                                                              [Preview] [Restore]│
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

#### Restore Behavior

1. Confirm modal: "Restore this version as Active?"
2. Sets Active to that prior version
3. Creates a new revision checkpoint (restore is itself a versioned action)
4. Draft remains intact unless user explicitly overwrites

### Screen: Empty State (First-Time Setup)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│                         ┌─────────────────────────┐                         │
│                         │  📝                     │                         │
│                         │                         │                         │
│                         │  Set Content Guidelines │                         │
│                         │  for your site          │                         │
│                         │                         │                         │
│                         │  Guidelines keep AI     │                         │
│                         │  outputs consistent     │                         │
│                         │  with your voice.       │                         │
│                         │                         │                         │
│                         │  [Start writing]        │                         │
│                         │  [Generate a draft]*    │                         │
│                         │                         │                         │
│                         │  *Requires AI provider  │                         │
│                         └─────────────────────────┘                         │
│                                                                             │
│  Preview content: [ Select a post to preview ▼ ]                            │
│  ───────────────────────────────────────────────────────────────────────    │
│  (Fixture preview still visible so user sees what they'll test against)    │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Generate Draft Flow (Modal)

**Step 1: Choose Sources**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  GENERATE GUIDELINES DRAFT                                     Step 1 of 3  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Choose content to analyze:                                                 │
│                                                                             │
│  ☑ About page                                                               │
│  ☑ Homepage                                                                 │
│  ☑ Last 10 posts                                                            │
│  ☐ Select specific posts...  [ Search posts ▼ ]                             │
│                                                                             │
│                                                    [Cancel]  [Next →]       │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Step 2: Confirm Goals**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  GENERATE GUIDELINES DRAFT                                     Step 2 of 3  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Primary goal:  [ Get email subscribers ▼ ]                                 │
│                                                                             │
│  Anything to avoid? (optional)                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ No urgency tactics, no superlatives like "best" or "#1"            │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                             │
│                                                   [← Back]  [Generate →]    │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Step 3: Review Draft**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  GENERATE GUIDELINES DRAFT                                     Step 3 of 3  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ▾ Brand & site context                                        [✓ Accept]  │
│    Site description: "A design blog for creative professionals..."         │
│    Audience: "Designers, developers, creative directors"                   │
│    Primary goal: Subscribe                                                  │
│                                                                             │
│  ▾ Voice & tone                                      [✓ Accept] [↻ Redo]   │
│    Tone: warm, confident, plain English                                    │
│    POV: we → you                                                           │
│    Readability: general                                                    │
│                                                                             │
│  ▾ Copy rules                                                   [✓ Accept] │
│    Do: Use H2s, short paragraphs, single CTA...                            │
│    Don't: No urgency tactics, no superlatives...                           │
│                                                                             │
│  ▾ Vocabulary                                                    [✓ Accept] │
│    Prefer: "readers" over "users"                                          │
│    Avoid: "utilize", "leverage"                                            │
│                                                                             │
│                                                   [← Back]  [Save as Draft] │
└─────────────────────────────────────────────────────────────────────────────┘
```

### In-Editor AI Surfaces: "Using Guidelines" Pattern

Wherever AI appears (writer, image generator, coach, page generator):

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  AI Writer                                                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  [Using: Site guidelines ▾]  [+ Add instructions]                           │
│                                                                             │
│  ↓ Popover on click:                                                        │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  SITE GUIDELINES                                    [Edit →]        │    │
│  │  ───────────────────────────────────────────────────────────────    │    │
│  │  Voice: Warm, confident, plain English                              │    │
│  │  POV: We speak to "you"                                             │    │
│  │  ───────────────────────────────────────────────────────────────    │    │
│  │  • Use H2s, bullets, short paragraphs                               │    │
│  │  • Single CTA at end                                                │    │
│  │  • No urgency tactics                                               │    │
│  │  • Prefer "readers" over "users"                                    │    │
│  │  ───────────────────────────────────────────────────────────────    │    │
│  │  ☐ Don't use guidelines for this request                            │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## User Flows

### Flow 1: First-Time Setup

```
User opens Site Editor → Guidelines
        ↓
Sees empty state + fixture preview
        ↓
Chooses: [Start writing] or [Generate draft]
        ↓
    ┌───────────────────────────────────────┐
    │ If Generate:                          │
    │   → Select sources                    │
    │   → Confirm goals                     │
    │   → Review generated draft            │
    │   → Accept/edit per section           │
    └───────────────────────────────────────┘
        ↓
Saves as Draft
        ↓
Uses Playground to test against fixture
        ↓
Publishes → becomes Active
```

### Flow 2: Safe Iteration (Newsroom/Enterprise)

```
Editor opens Guidelines
        ↓
Updates copy rules ("no urgency tactics")
Adds vocabulary terms
        ↓
Saves Draft (Active unchanged)
        ↓
Opens Playground tab
        ↓
Selects: Compare Draft vs Active
Runs: "Generate 5 headlines"
        ↓
Reviews side-by-side comparison
        ↓
Satisfied → Publishes Draft → becomes Active
        ↓
If issues later: History → Restore prior version
```

### Flow 3: Everyday Author Use

```
Author opens AI writer in post editor
        ↓
Sees chip: "Using: Site guidelines"
        ↓
AI generates content following guidelines
        ↓
If output seems off:
        ↓
Clicks chip → sees preview of applied rules
        ↓
Clicks "Edit" (if permitted) or asks admin
```

---

## Permissions & Governance

### Capabilities

| Action | Required Capability |
|--------|---------------------|
| View guidelines | `edit_theme_options` (Site Editor access) |
| Edit/Save draft | `edit_theme_options` |
| Publish | `edit_theme_options` |
| Restore from history | `edit_theme_options` |

### Transparency Requirements

- **Context preview** in Playground always shows what will be sent
- **Version metadata** displayed: which version, last published by whom, when
- **Inline disclosure** near Generate/Test actions:
  > "Your guidelines and selected content may be sent to your configured AI provider."

---

## Success Metrics

### Primary (Feature Adoption)

| Metric | Description |
|--------|-------------|
| Guidelines engagement | View/edit/save/publish rates |
| Playground usage | Test runs, compare mode usage |
| History restore rate | How often users roll back |

### Secondary (AI Quality Improvement)

| Metric | Description |
|--------|-------------|
| AI satisfaction ratings | In-product feedback on AI outputs |
| Regenerate loops | Should decrease with guidelines |
| Draft → Publish completion | Content task completion rate |

### Instrumentation Approach

Fire JS events via `wp.data` dispatch so Jetpack/VIP can wire telemetry without Core hard dependencies:

```js
wp.data.dispatch('core/content-guidelines').recordEvent('guidelines_published');
wp.data.dispatch('core/content-guidelines').recordEvent('playground_task_run', { task: 'headline' });
```

---

## Implementation Milestones

### Milestone 0: Scaffolding

**Backend:**
- Register `wp_content_guidelines` CPT
- Single-record management (create on first visit)
- REST endpoints: read, write draft, publish, discard

**Frontend:**
- Site Editor route/view: "Guidelines"
- Basic panel structure with structured fields
- Save Draft / Publish buttons

**Acceptance Criteria:**
- User can create/edit Draft and Publish Active
- Data persists correctly

### Milestone 1: Versioning

**Backend:**
- Enable revisions for Active (`post_content`)
- Revision list endpoint
- Restore endpoint

**Frontend:**
- History view listing revisions
- Preview revision (read-only)
- Restore action with confirmation

**Acceptance Criteria:**
- Users can view revision history
- Restore creates new checkpoint
- Prior versions are recoverable

### Milestone 2: Playground (Test Loop)

**Backend:**
- `/test` endpoint with provider hook
- Context packet builder (task-aware, token-budgeted)
- Local lint check utilities

**Frontend:**
- Fixture selector + preview canvas
- Playground tab UI
- Task picker + Run button
- Results display (single + compare mode)
- Context preview panel
- Local lint check display

**Acceptance Criteria:**
- Without provider: lint + context preview works
- With provider: tasks return outputs, compare works

### Milestone 3: Auto-Generation

**Backend:**
- `/generate` endpoint with provider hook
- Source content aggregation
- Reference posts metadata storage

**Frontend:**
- Generate Draft modal flow (3 steps)
- Per-section accept/redo/edit
- Regenerate action in overflow menu

**Acceptance Criteria:**
- If provider exists, generates structured draft
- Sources are tracked for provenance

### Milestone 4: AI Surface Integration

**Backend:**
- Public `wp_get_content_guidelines_packet()` function
- Documentation for plugin integration

**Frontend:**
- "Using: Site guidelines" chip component
- Preview popover with edit link
- Per-request disable toggle

**Acceptance Criteria:**
- Plugins can reliably fetch and use guidelines
- Users see transparency in AI surfaces

---

## Component Architecture (Gutenberg)

### Routes

```js
// Site Editor registration
registerRoute({
    name: 'content-guidelines',
    path: '/guidelines',
    areas: {
        sidebar: ContentGuidelinesSidebar,
        content: ContentGuidelinesPreview,
    },
});
```

### Key Components

```
ContentGuidelinesScreen
├── Header
│   ├── StatusPill (Active/Draft)
│   ├── SaveDraftButton
│   ├── PublishButton
│   └── OverflowMenu (History, Regenerate, Export)
├── FixtureSelector
│   └── PostSearchCombobox
├── PreviewCanvas
│   └── BlockPreview (selected fixture)
└── Sidebar
    ├── TabPanel
    │   ├── GuidelinesTab
    │   │   ├── BrandContextPanel
    │   │   ├── VoiceTonePanel
    │   │   ├── CopyRulesPanel
    │   │   ├── VocabularyPanel
    │   │   ├── ImagesPanel
    │   │   └── NotesPanel
    │   └── PlaygroundTab
    │       ├── GuidelinesSourceToggle
    │       ├── TaskPicker
    │       ├── ExtraInstructionsField
    │       ├── RunButton
    │       ├── ResultsDisplay
    │       ├── ContextPreview
    │       └── LocalLintChecks
    └── DraftNotice

HistoryScreen
├── Header
├── RevisionList
│   └── RevisionItem (timestamp, author, actions)
└── PreviewModal (read-only panels)

GenerateDraftModal
├── SourcesStep
├── GoalsStep
└── ReviewStep
    └── SectionReview (accept/redo/edit)
```

### State Management

```js
// Data store
registerStore('core/content-guidelines', {
    reducer,
    actions: {
        setDraft,
        publishGuidelines,
        discardDraft,
        restoreRevision,
        runPlaygroundTask,
        generateDraft,
    },
    selectors: {
        getActiveGuidelines,
        getDraftGuidelines,
        hasDraft,
        getRevisions,
        getLastPlaygroundResult,
    },
    resolvers: {
        getActiveGuidelines,
        getRevisions,
    },
});
```

---

## Open Questions

| Question | Status | Notes |
|----------|--------|-------|
| Include Images panel in MVP? | Recommend yes | Defer only if no image AI exists |
| Capability: `edit_theme_options` vs `manage_options`? | Recommend `edit_theme_options` | Aligns with Site Editor |
| Token budget for Playground tasks? | TBD | Start with ~800 chars excerpt |
| Store reference posts permanently? | Recommend yes | Enables "Regenerate with same sources" |
| wp-admin fallback: full editor or link-only? | TBD | Link to Site Editor is simpler for MVP |

---

## Appendix: Example Outputs

### Example: Generated Guidelines (Newsroom)

```json
{
  "brand_context": {
    "site_description": "Independent local news covering Portland metro area politics, community, and culture.",
    "audience": "Portland residents aged 25-55 who care about local issues",
    "primary_goal": "subscribe",
    "topics": ["local politics", "city council", "housing", "transit", "arts"]
  },
  "voice_tone": {
    "tone_traits": ["authoritative", "accessible", "fair"],
    "pov": "third_person",
    "readability": "general"
  },
  "copy_rules": {
    "dos": [
      "Lead with the most newsworthy element",
      "Include relevant context for complex stories",
      "Quote diverse sources"
    ],
    "donts": [
      "No editorializing in news stories",
      "No anonymous sources without editor approval",
      "No sensationalist headlines"
    ],
    "formatting": ["short_paragraphs"]
  },
  "vocabulary": {
    "prefer": [
      { "term": "unhoused", "note": "preferred over 'homeless'" },
      { "term": "council member", "note": "not 'councilman/councilwoman'" }
    ],
    "avoid": [
      { "term": "drug addict", "note": "use 'person with substance use disorder'" }
    ]
  }
}
```

### Example: Context Packet (Task: Headline)

```text
SITE VOICE & GUIDELINES
Voice: Authoritative, accessible, fair. Third person.

RULES:
- Lead with the most newsworthy element
- No sensationalist headlines
- No editorializing

VOCABULARY:
Prefer: "unhoused" (not "homeless"), "council member" (not "councilman")
Avoid: "drug addict"

SITE CONTEXT:
Independent local news covering Portland metro politics, community, culture.
Audience: Portland residents who care about local issues.
```

---

## References

- [Global Styles CPT pattern](https://developer.wordpress.org/reference/classes/wp_theme_json_resolver/)
- [Global Styles revisions REST](https://developer.wordpress.org/rest-api/reference/)
- [Site Editor alternate views (Style Book)](https://github.com/WordPress/gutenberg)
- [Template/Template Part storage](https://developer.wordpress.org/themes/templates/)

---

*Document authored for WordPress Core/Gutenberg contribution. Provider-agnostic by design.*
