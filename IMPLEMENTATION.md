# Implementation Plan — Content Guidelines

Linear-ready breakdown of engineering work.

---

## Epic: Content Guidelines MVP

Ship a core-owned Content Guidelines feature with structured editing, draft/publish workflow, versioning, and a Playground test loop.

---

## Milestone 0: Foundation

### Issue: Register `wp_content_guidelines` CPT

**Description:**
Create the custom post type that stores site-level guidelines with revision support.

**Acceptance Criteria:**
- [ ] CPT registered with `show_in_rest: true`
- [ ] Supports revisions and custom-fields
- [ ] Single-record constraint enforced (one per site)
- [ ] Record created on first access if not exists
- [ ] Capability gated to `edit_theme_options`

**Technical Notes:**
```php
register_post_type( 'wp_content_guidelines', array(
    'public'       => false,
    'show_in_rest' => true,
    'supports'     => array( 'revisions', 'custom-fields' ),
    'capabilities' => array( ... ),
) );
```

---

### Issue: REST endpoints — CRUD + Draft/Publish

**Description:**
Implement REST API for reading, updating draft, publishing, and discarding.

**Acceptance Criteria:**
- [ ] `GET /wp/v2/content-guidelines` returns `{ active, draft, hasDraft, updatedAt }`
- [ ] `PUT /wp/v2/content-guidelines/draft` saves draft to post meta
- [ ] `POST /wp/v2/content-guidelines/publish` promotes draft → active (post_content)
- [ ] `POST /wp/v2/content-guidelines/discard-draft` clears draft meta
- [ ] All endpoints require `edit_theme_options` capability
- [ ] JSON schema validated on write

---

### Issue: Site Editor route and view shell

**Description:**
Add "Guidelines" as a top-level item in Site Editor navigation.

**Acceptance Criteria:**
- [ ] Route `/guidelines` registered in Site Editor
- [ ] Appears in left nav alongside Styles/Templates
- [ ] Basic shell renders: header, sidebar placeholder, canvas placeholder
- [ ] Header shows title "Content Guidelines"

---

### Issue: Guidelines editor — panel structure

**Description:**
Implement the structured editor UI with accordion panels.

**Acceptance Criteria:**
- [ ] Sidebar renders with TabPanel (Guidelines / Playground)
- [ ] Guidelines tab has 6 PanelBody sections:
  - Brand & site context
  - Voice & tone
  - Copy rules
  - Vocabulary
  - Images
  - Notes
- [ ] Each panel has appropriate controls (see spec for field types)
- [ ] Changes update local state

---

### Issue: Draft save and publish buttons

**Description:**
Wire up Save Draft and Publish actions in the header.

**Acceptance Criteria:**
- [ ] "Save draft" button saves to REST draft endpoint
- [ ] "Publish" button calls publish endpoint
- [ ] Status pill shows "Active" or "Draft changes"
- [ ] Success/error notices displayed
- [ ] Publish creates a revision checkpoint

---

### Issue: Empty state and first-time setup

**Description:**
Show onboarding UI when no guidelines exist.

**Acceptance Criteria:**
- [ ] Centered card with "Set Content Guidelines" message
- [ ] "Start writing" button creates empty draft and enters edit mode
- [ ] "Generate draft" button shown only if provider hook returns capability
- [ ] Fixture selector still visible below

---

## Milestone 1: Versioning

### Issue: REST endpoints — revisions and restore

**Description:**
Add endpoints for listing revision history and restoring prior versions.

**Acceptance Criteria:**
- [ ] `GET /wp/v2/content-guidelines/revisions` returns list with id, author, date
- [ ] `POST /wp/v2/content-guidelines/restore/{id}` sets that revision as active
- [ ] Restore creates a new revision checkpoint (audit trail)
- [ ] Draft is preserved on restore (not overwritten)

---

### Issue: History view UI

**Description:**
Implement the revision history screen accessible from overflow menu.

**Acceptance Criteria:**
- [ ] "History" in overflow menu opens history view
- [ ] List shows: timestamp, author, actions (Preview, Restore)
- [ ] Preview opens read-only modal with that version's data
- [ ] Restore shows confirmation modal
- [ ] After restore, returns to editor with success notice

---

## Milestone 2: Playground

### Issue: Fixture selector and preview canvas

**Description:**
Let users select an example post/page to use for testing.

**Acceptance Criteria:**
- [ ] Combobox above canvas: "Preview content"
- [ ] Search posts and pages
- [ ] Selection persists per-user (user meta or localStorage)
- [ ] Default: last selected → most recent post → built-in sample
- [ ] Canvas renders selected post content (BlockPreview or similar)

---

### Issue: Playground tab — task runner UI

**Description:**
Build the Playground interface for running test tasks.

**Acceptance Criteria:**
- [ ] Toggle: Use Draft / Use Active
- [ ] Checkbox: Compare Draft vs Active
- [ ] Task picker with 3 presets:
  - Rewrite intro paragraph
  - Generate 5 headline options
  - Write a CTA paragraph
- [ ] Optional "Extra instructions" textarea
- [ ] Run button
- [ ] Results panel (single or side-by-side for compare)
- [ ] Copy button on each result

---

### Issue: REST endpoint — test execution

**Description:**
Create the test endpoint that invokes provider hook.

**Acceptance Criteria:**
- [ ] `POST /wp/v2/content-guidelines/test`
- [ ] Payload: `{ task, fixture_post_id, use: "draft|active", compare: bool, extra_instructions }`
- [ ] Builds context packet from guidelines + fixture excerpt
- [ ] Calls `wp_ai_run_guidelines_playground_task` filter
- [ ] Returns `{ output_text }` or `{ draft_output, active_output }` for compare
- [ ] Returns error structure if no provider

---

### Issue: Context packet builder utility

**Description:**
Implement the PHP function that builds task-specific context packets.

**Acceptance Criteria:**
- [ ] `wp_get_content_guidelines_packet( $args )` exists
- [ ] Supports `task` parameter to select relevant sections
- [ ] Supports `max_chars` for token budgeting
- [ ] Returns `packet_text`, `packet_structured`, metadata
- [ ] Task mappings:
  - `headline` → voice_tone + copy_rules + vocabulary
  - `writing` → all sections
  - `cta` → brand_context.primary_goal + copy_rules
  - `image` → image_style + brand_context

---

### Issue: Context preview panel

**Description:**
Show users exactly what context is sent to AI.

**Acceptance Criteria:**
- [ ] Collapsible "Context preview" in Playground
- [ ] Shows formatted packet text
- [ ] Shows metadata: guidelines version, draft/active state
- [ ] Always visible even without provider

---

### Issue: Local lint checks (no-provider fallback)

**Description:**
Provide immediate validation without requiring AI provider.

**Acceptance Criteria:**
- [ ] Scan fixture content against vocabulary.avoid terms
- [ ] Highlight matches: "Found 'utilize' — prefer 'use'"
- [ ] Optional: simple readability heuristics (sentence length)
- [ ] Displayed in Playground even when no provider installed
- [ ] Runs on fixture selection and guidelines changes

---

## Milestone 3: Auto-Generation

### Issue: Generate draft modal — source selection

**Description:**
Step 1 of generation flow: choose content sources.

**Acceptance Criteria:**
- [ ] Modal opens from empty state or overflow menu
- [ ] Checkboxes: About page, Homepage, Last N posts
- [ ] "Select specific posts" with search
- [ ] Next button proceeds to step 2

---

### Issue: Generate draft modal — goals confirmation

**Description:**
Step 2: confirm primary goal and constraints.

**Acceptance Criteria:**
- [ ] Primary goal dropdown
- [ ] "Anything to avoid?" textarea
- [ ] Back button returns to step 1
- [ ] Generate button proceeds to step 3

---

### Issue: Generate draft modal — review and accept

**Description:**
Step 3: review generated draft per section.

**Acceptance Criteria:**
- [ ] Shows each section with generated content
- [ ] Per-section actions: Accept, Redo (if provider), Edit
- [ ] "Save as Draft" button saves and closes modal
- [ ] Back button returns to step 2

---

### Issue: REST endpoint — generation

**Description:**
Create endpoint that invokes generation provider hook.

**Acceptance Criteria:**
- [ ] `POST /wp/v2/content-guidelines/generate`
- [ ] Payload: `{ sources: [], goal, constraints }`
- [ ] Aggregates source content (truncated for token budget)
- [ ] Calls `wp_ai_generate_content_guidelines_draft` filter
- [ ] Returns structured draft JSON
- [ ] Returns error if no provider

---

### Issue: Store generation sources metadata

**Description:**
Track which content was used to generate guidelines.

**Acceptance Criteria:**
- [ ] `_wp_content_guidelines_sources` meta stores source post IDs
- [ ] Updated on each generation
- [ ] Exposed in REST response for transparency

---

## Milestone 4: AI Surface Integration

### Issue: "Using: Site guidelines" chip component

**Description:**
Create reusable component for AI surfaces to show guidelines usage.

**Acceptance Criteria:**
- [ ] Chip displays "Using: Site guidelines"
- [ ] Click opens popover with:
  - Compact preview (tone, key rules, vocabulary)
  - "Edit" link to Guidelines screen
  - "Don't use for this request" toggle
- [ ] Exported for use by other packages/plugins

---

### Issue: Documentation — plugin integration guide

**Description:**
Document how plugins can consume guidelines.

**Acceptance Criteria:**
- [ ] PHP API usage examples
- [ ] REST API usage examples
- [ ] Provider hook implementation guide
- [ ] Published in developer docs

---

## Milestone 5: wp-admin Fallback

### Issue: Settings → Writing integration

**Description:**
Add Content Guidelines access point in wp-admin.

**Acceptance Criteria:**
- [ ] Settings → Writing shows "Content Guidelines" section
- [ ] Link to Site Editor Guidelines view
- [ ] Works for classic themes (Site Editor still accessible)
- [ ] Consider: embed minimal React app for non-block themes (optional)

---

## Dependencies

```
Milestone 0 ─┬─► Milestone 1 (Versioning)
             │
             └─► Milestone 2 (Playground) ──► Milestone 3 (Generation)
                                          │
                                          └─► Milestone 4 (Integration)

Milestone 5 can proceed independently after Milestone 0
```

---

## Estimated Scope

| Milestone | Issues | Complexity |
|-----------|--------|------------|
| 0: Foundation | 6 | Medium |
| 1: Versioning | 2 | Small |
| 2: Playground | 6 | Large |
| 3: Generation | 5 | Medium |
| 4: Integration | 2 | Small |
| 5: wp-admin | 1 | Small |
| **Total** | **22** | |

---

## Definition of Done (Feature)

- [ ] User can create, edit, save, and publish guidelines
- [ ] Draft does not affect AI surfaces until published
- [ ] User can view history and restore prior versions
- [ ] User can test guidelines against fixture content in Playground
- [ ] Without AI provider: lint checks + context preview work
- [ ] With AI provider: generation and testing work
- [ ] "Using: Site guidelines" chip available for AI surfaces
- [ ] REST and PHP APIs documented
- [ ] Accessible (keyboard, screen reader)
- [ ] Internationalized (all strings translatable)
