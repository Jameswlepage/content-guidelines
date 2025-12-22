<?php
/**
 * Populate sample guidelines data.
 * Run with: wp eval-file populate-sample.php
 */

$json_path = dirname(__FILE__) . '/samples/mailchimp.json';
$json = file_get_contents($json_path);
$data = json_decode($json, true);

if (!$data || !isset($data['guidelines'])) {
    WP_CLI::error('Failed to parse JSON from ' . $json_path);
}

$guidelines = $data['guidelines'];

// Get existing post or create new
$post_id = get_option('wp_content_guidelines_post_id');
if ($post_id) {
    $post = get_post($post_id);
}

if (empty($post)) {
    $post_id = wp_insert_post(array(
        'post_type'    => 'content_guidelines',
        'post_title'   => $data['title'],
        'post_status'  => 'publish',
        'post_content' => wp_json_encode($guidelines, JSON_UNESCAPED_UNICODE),
    ));

    if (is_wp_error($post_id)) {
        WP_CLI::error('Failed to create post: ' . $post_id->get_error_message());
    }

    update_option('wp_content_guidelines_post_id', $post_id);
    WP_CLI::success('Created guidelines post: ' . $post_id);
} else {
    wp_update_post(array(
        'ID'           => $post_id,
        'post_content' => wp_json_encode($guidelines, JSON_UNESCAPED_UNICODE),
    ));
    WP_CLI::success('Updated guidelines post: ' . $post_id);
}

$content = json_decode(get_post($post_id)->post_content, true);
WP_CLI::log('Blocks count: ' . count($content['blocks'] ?? []));
