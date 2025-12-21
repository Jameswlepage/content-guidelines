# Content Guidelines — One-Page Summary

## What We're Building

A central "editorial profile" for WordPress sites that all AI features can reference. Users set their voice, tone, rules, and vocabulary once — AI experiences use it automatically.

**Think:** "Global Styles for how your site sounds, not how it looks."

---

## Why It Matters

| Problem | Solution |
|---------|----------|
| Users re-explain brand voice to AI every time | Guidelines persist site-wide |
| Inconsistent tone across posts/authors | Single source of truth |
| No visibility into what AI "knows" | Explicit, editable, transparent |
| Enterprise needs: audit trail, rollback | Versioning with history + restore |

---

## Core Experience

```
┌────────────────────────────────────────────────────────────────┐
│  Site Editor → Guidelines                                       │
├─────────────────────────┬──────────────────────────────────────┤
│                         │                                       │
│   PREVIEW CANVAS        │   [ Guidelines ]  [ Playground ]      │
│   (example post)        │                                       │
│                         │   ▸ Brand & site context              │
│                         │   ▸ Voice & tone                      │
│                         │   ▸ Copy rules                        │
│                         │   ▸ Vocabulary                        │
│                         │   ▸ Images                            │
│                         │                                       │
│                         │   [Save draft]  [Publish]             │
│                         │                                       │
└─────────────────────────┴──────────────────────────────────────┘
```

---

## Key Capabilities

### 1. Structured Editorial Profile
- Brand context (what is this site, who's the audience, what's the goal)
- Voice & tone (traits, POV, readability level)
- Copy rules (dos, don'ts, formatting)
- Vocabulary (prefer/avoid terms)
- Image style (if image AI exists)

### 2. Draft → Publish Workflow
- Edit in draft mode without affecting live AI behavior
- Publish when ready (creates versioned checkpoint)
- Safe iteration for multi-author teams

### 3. Playground (Test Loop)
- Select an example post as "fixture"
- Run AI tasks: rewrite intro, generate headlines, write CTA
- Compare Draft vs Active guidelines side-by-side
- See exactly what context is sent to AI

### 4. Versioning & History
- Full revision history (who, when, what)
- One-click restore to prior version
- Enterprise-ready audit trail

### 5. AI Surface Integration
- Every AI feature shows: "Using: Site guidelines"
- Click to preview what rules are applied
- Edit link for quick access

---

## Where It Lives

| Entry Point | Audience |
|-------------|----------|
| Site Editor → Guidelines | Block theme users (primary) |
| Settings → Writing | Classic themes, admin users |
| AI surfaces (chip) | Authors during content creation |

---

## Technical Approach

| Aspect | Approach | Precedent |
|--------|----------|-----------|
| Storage | CPT (`wp_content_guidelines`) | Like `wp_global_styles` |
| Versioning | WP revisions on `post_content` | Like Global Styles revisions |
| UI | Site Editor view with panels | Like Styles panel |
| AI Integration | Provider hooks (pluggable) | Core owns storage, providers own generation |

**Core is provider-agnostic:** Core provides storage + UI + APIs. Jetpack, VIP, or any plugin can supply the AI generation/testing.

---

## MVP Scope

| In Scope | Out of Scope (Deferred) |
|----------|------------------------|
| Single site-level guidelines | Multiple guideline sets |
| Draft/Publish workflow | Multi-step approval workflows |
| Revision history + restore | Rich diff visualization |
| Playground with 3 test tasks | Automated compliance scanning |
| Auto-generate from content | Per-author profiles |
| "Using: Guidelines" transparency | Drift detection alerts |

---

## Success Metrics

**Adoption:**
- Guidelines view/edit/publish rates
- Playground usage (tests run, compare mode)

**Quality:**
- AI output satisfaction ratings
- Reduced regenerate loops
- Draft → publish completion rate

---

## Implementation Path

1. **Scaffolding** — CPT, REST, basic editor UI
2. **Versioning** — History list, restore flow
3. **Playground** — Fixture selection, test tasks, compare
4. **Auto-Generation** — Generate draft from site content
5. **AI Integration** — "Using: Guidelines" chip in AI surfaces

---

## Open Questions for Scoping

1. Include Images panel in MVP, or defer until image AI ships?
2. wp-admin fallback: full embedded editor or link to Site Editor?
3. Token budget for Playground fixture excerpts?

---

## Links

- [Full Specification](./SPEC.md)
- [Technical README](./README.md)
