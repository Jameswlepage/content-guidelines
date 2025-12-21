# Agent Guidelines

## Commit Rules

**Never include co-authorship or AI attribution in commits.**

- No `Co-Authored-By:` lines referencing AI
- No "Generated with Claude Code" or similar
- No AI tool references in commit messages
- Keep messages clean and professional, describing only the changes

## Code Style

### PHP

```php
namespace ContentGuidelines;

defined( 'ABSPATH' ) || exit;
```

- All files must use the `ContentGuidelines` namespace
- All files must include the ABSPATH check
- Use static class methods (not instance-based)
- PHPDoc for all public methods
- WordPress coding standards: tabs for indentation, spaces inside parentheses
- Sanitize all input: `sanitize_text_field()`, `sanitize_key()`, `intval()`, etc.
- Check capabilities: `current_user_can( 'edit_theme_options' )`
- Use `wp_parse_args()` for function arguments with defaults
- Return `WP_Error` for error handling

### JavaScript/React

```javascript
/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../store';
import './style.scss';
```

- Import order: WordPress dependencies first, then internal, then styles
- Use comment headers to separate import sections
- Functional components only, with hooks
- JSDoc for all components with `@return {JSX.Element}`
- Use `useSelect`/`useDispatch` for store access
- BEM naming for CSS classes: `content-guidelines-screen__header`
- Tabs for indentation
- Use `__()` from `@wordpress/i18n` for all user-facing strings

## Architecture

### File Structure

```
includes/           # PHP classes only
src/
  ├── components/   # React components
  ├── store/        # Redux store (actions, reducer, selectors, resolvers)
  └── commands/     # Command palette integration
docs/               # Documentation markdown files
```

### Data Flow

- All data operations go through the REST API
- Frontend uses Redux store (`@wordpress/data`)
- Draft/active pattern: drafts in post meta, active in post content
- Never modify active guidelines directly; always publish from draft

### Store Pattern

- Actions: return plain objects with `type` and `payload`
- Reducer: immutable state updates with spread operator
- Selectors: pure functions that extract state
- Resolvers: generators for async data fetching with `apiFetch`

## Key Patterns

### Adding a New Section

1. Add schema in `class-rest-controller.php` sanitization
2. Add default values in `class-post-type.php`
3. Create component in `src/components/panels/`
4. Add to library panel navigation
5. Update `class-context-packet-builder.php` if AI-relevant

### Adding a REST Endpoint

1. Register route in `class-rest-controller.php`
2. Add permission callback checking `edit_theme_options`
3. Define schema for request/response
4. Add corresponding action in `src/store/actions.js`
5. Add resolver if fetching data

## Testing

- Run `pnpm lint:js` before committing JavaScript changes
- Run `pnpm lint:css` before committing style changes
- Test in WordPress admin with browser devtools open
- Verify REST API responses match expected schema

## Version Updates

When incrementing version, update all locations:
- `content-guidelines.php` (plugin header and `CONTENT_GUIDELINES_VERSION`)
- `package.json`

## What Not To Do

- Don't create new files unless necessary; prefer editing existing ones
- Don't add features beyond what was requested
- Don't refactor unrelated code while fixing bugs
- Don't add comments to code you didn't change
- Don't use instance-based classes; follow the static pattern
- Don't bypass the REST API for data operations
- Don't modify active guidelines directly; use the draft/publish flow
