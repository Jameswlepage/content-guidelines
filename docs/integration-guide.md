# Integration Guide

This guide explains how to integrate Content Guidelines with AI providers and other WordPress features.

## For AI Provider Plugins

Content Guidelines provides the storage, UI, and APIs. AI providers supply the intelligence. Here's how to integrate:

### 1. Register as an AI Provider

Tell Content Guidelines that your plugin can handle AI tasks:

```php
add_filter( 'wp_content_guidelines_has_ai_provider', '__return_true' );
```

### 2. Handle Playground Tests

Implement the playground test filter to generate AI-powered results:

```php
add_filter( 'wp_content_guidelines_run_playground_test', function( $result, $request ) {
    // $request contains:
    // - task: 'rewrite_intro', 'generate_headlines', 'write_cta'
    // - fixture_content: The text to work with
    // - guidelines: Full guidelines array
    // - context_packet: Pre-formatted packet with packet_text
    // - extra_instructions: User's additional instructions

    $prompt = $request['context_packet']['packet_text'] . "\n\n";

    switch ( $request['task'] ) {
        case 'rewrite_intro':
            $prompt .= "Rewrite this introduction:\n\n" . $request['fixture_content'];
            break;
        case 'generate_headlines':
            $prompt .= "Generate 5 headlines for:\n\n" . $request['fixture_content'];
            break;
        case 'write_cta':
            $prompt .= "Write a call-to-action for:\n\n" . $request['fixture_content'];
            break;
    }

    if ( ! empty( $request['extra_instructions'] ) ) {
        $prompt .= "\n\nAdditional instructions: " . $request['extra_instructions'];
    }

    // Call your AI API
    $ai_response = my_ai_api_call( $prompt );

    return array(
        'output'       => $ai_response['text'],
        'alternatives' => $ai_response['alternatives'] ?? array(),
        'metadata'     => array(
            'model'  => 'gpt-4',
            'tokens' => $ai_response['usage']['total_tokens'],
        ),
    );
}, 10, 2 );
```

### 3. Handle Guidelines Generation (Optional)

Generate initial guidelines from site content:

```php
add_filter( 'wp_content_guidelines_generate_draft', function( $draft, $site_context, $args ) {
    // $site_context contains:
    // - site_title: The site name
    // - tagline: Site description/tagline
    // - source_posts: Array of recent post content

    // $args contains:
    // - goal: User's primary goal
    // - constraints: User-specified constraints

    $prompt = "Analyze this website and generate content guidelines:\n\n";
    $prompt .= "Site: {$site_context['site_title']}\n";
    $prompt .= "Tagline: {$site_context['tagline']}\n\n";
    $prompt .= "Sample content:\n" . implode( "\n\n", $site_context['source_posts'] );

    // Call your AI API
    $generated = my_ai_api_call( $prompt );

    // Return structured guidelines
    return array(
        'brand_context' => array(
            'site_description' => $generated['description'],
            'audience'         => $generated['audience'],
            'primary_goal'     => $args['goal'],
        ),
        'voice_tone' => array(
            'tone_traits'  => $generated['tone_traits'],
            'pov'          => $generated['pov'],
            'readability'  => $generated['readability'],
        ),
        'copy_rules' => array(
            'dos'   => $generated['dos'],
            'donts' => $generated['donts'],
        ),
        // ... etc
    );
}, 10, 3 );
```

## For Theme/Plugin Developers

### Using Guidelines in Your AI Features

```php
// Get the context packet for your AI prompt
$packet = wp_get_content_guidelines_packet( array(
    'task' => 'writing',
) );

// Build your prompt with guidelines context
$system_prompt = "You are a writing assistant for this website.\n\n";
$system_prompt .= $packet['packet_text'];

// Use in your AI call
$response = my_ai_call( array(
    'system' => $system_prompt,
    'user'   => $user_input,
) );
```

### Displaying "Using Guidelines" Status

Show users when guidelines are being applied:

```php
function render_guidelines_badge() {
    $guidelines = wp_get_content_guidelines();

    if ( ! empty( $guidelines ) ) {
        echo '<span class="guidelines-badge">Using: Site Guidelines</span>';
    }
}
```

### Block-Specific Guidelines

Access guidelines for specific block types:

```php
$guidelines = wp_get_content_guidelines();

// Get paragraph block guidelines
$paragraph_rules = $guidelines['blocks']['core/paragraph'] ?? array();

if ( ! empty( $paragraph_rules['copy_rules']['dos'] ) ) {
    // Apply paragraph-specific rules
}
```

## For JavaScript/Block Editor

### Reading Guidelines in the Editor

```javascript
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

function useContentGuidelines( task = 'writing' ) {
    const [ packet, setPacket ] = useState( null );

    useEffect( () => {
        apiFetch( {
            path: `/wp/v2/content-guidelines/packet?task=${ task }`,
        } ).then( setPacket );
    }, [ task ] );

    return packet;
}

// In your component
function MyAIFeature() {
    const guidelines = useContentGuidelines( 'headline' );

    if ( ! guidelines ) {
        return <Spinner />;
    }

    return (
        <div>
            <p>Guidelines loaded: { guidelines.packet_text.length } chars</p>
            { /* Use guidelines.packet_text in your AI prompt */ }
        </div>
    );
}
```

### Registering Commands (WordPress 6.9+)

Add your own Command Palette commands:

```javascript
import { useCommand } from '@wordpress/commands';

function MyPluginCommands() {
    useCommand( {
        name: 'my-plugin/generate-with-guidelines',
        label: 'Generate Content with Guidelines',
        icon: sparkles,
        callback: async ( { close } ) => {
            const packet = await wp.apiFetch( {
                path: '/wp/v2/content-guidelines/packet?task=writing',
            } );

            // Use packet in your AI generation
            myAIGenerate( packet.packet_text );
            close();
        },
    } );

    return null;
}
```

## API Summary

| Method | Use Case |
|--------|----------|
| `wp_get_content_guidelines_packet()` | Get formatted context for AI prompts |
| `wp_get_content_guidelines()` | Get raw guidelines data |
| REST `/wp/v2/content-guidelines/packet` | JavaScript/external access to packets |
| Abilities API | AI assistant integration (WP 6.9+) |
| Filter hooks | Provide AI generation capability |

## Best Practices

1. **Always use `packet_text`** for AI prompts - it's pre-formatted and optimized
2. **Respect `max_chars`** to stay within token budgets
3. **Use task-specific packets** - they only include relevant sections
4. **Cache when appropriate** - guidelines don't change frequently
5. **Check for empty guidelines** - gracefully handle sites without guidelines
6. **Show attribution** - let users know when guidelines are being applied

## Testing Your Integration

1. Install Content Guidelines plugin
2. Create some test guidelines via Appearance > Guidelines
3. Publish the guidelines
4. Test your integration:
   - `wp_get_content_guidelines_packet()` returns data
   - Your AI features respect the guidelines
   - Playground tests work with your provider

## Example: Complete AI Provider Plugin

```php
<?php
/**
 * Plugin Name: My AI Provider for Content Guidelines
 */

// Register as provider
add_filter( 'wp_content_guidelines_has_ai_provider', '__return_true' );

// Handle playground tests
add_filter( 'wp_content_guidelines_run_playground_test', function( $result, $request ) {
    $response = wp_remote_post( 'https://api.myai.com/generate', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . MY_AI_API_KEY,
            'Content-Type'  => 'application/json',
        ),
        'body' => wp_json_encode( array(
            'prompt' => $request['context_packet']['packet_text'] . "\n\n" .
                        "Task: {$request['task']}\n\n" .
                        $request['fixture_content'],
        ) ),
    ) );

    if ( is_wp_error( $response ) ) {
        return null; // Fall back to lint-only
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    return array(
        'output'   => $body['text'],
        'metadata' => array( 'model' => $body['model'] ),
    );
}, 10, 2 );
```
