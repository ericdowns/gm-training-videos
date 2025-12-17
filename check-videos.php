<?php
// Quick check script for training videos
require_once('../../../wp-load.php');

$args = array(
    'post_type' => 'training_videos',
    'post_status' => 'any',
    'posts_per_page' => -1
);

$videos = get_posts($args);

echo "Total Training Videos: " . count($videos) . "\n\n";

if (count($videos) > 0) {
    foreach ($videos as $video) {
        echo "ID: " . $video->ID . "\n";
        echo "Title: " . $video->post_title . "\n";
        echo "Status: " . $video->post_status . "\n";
        echo "Menu Order: " . $video->menu_order . "\n";
        echo "Loom URL: " . get_post_meta($video->ID, '_loom_video_url', true) . "\n";
        echo "Description: " . get_post_meta($video->ID, '_video_description', true) . "\n";
        echo "---\n";
    }
} else {
    echo "No training videos found!\n";
    echo "\nTo create sample videos:\n";
    echo "1. Go to WP Admin > Training Videos\n";
    echo "2. Click 'Create 12 Sample Videos' button\n";
    echo "OR\n";
    echo "Visit: " . admin_url('edit.php?post_type=training_videos&create_samples=1') . "\n";
}
?>