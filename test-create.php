<?php
/**
 * Direct test to create sample videos
 */

// Load WordPress
$wp_load_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php',
];

$loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists(dirname(__FILE__) . '/' . $path)) {
        require_once(dirname(__FILE__) . '/' . $path);
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    die("Could not load WordPress\n");
}

// Check current videos
$count = wp_count_posts('training_videos');
echo "Current published videos: " . $count->publish . "\n";
echo "Current draft videos: " . $count->draft . "\n";
echo "Current total videos: " . ($count->publish + $count->draft) . "\n\n";

// Include and run the sample creation
include_once(dirname(__FILE__) . '/create-sample-videos.php');

if (function_exists('create_sample_training_videos')) {
    echo "Creating sample videos...\n";
    create_sample_training_videos();
} else {
    echo "Function create_sample_training_videos not found!\n";
}

// Check again
$count_after = wp_count_posts('training_videos');
echo "\nAfter creation:\n";
echo "Published videos: " . $count_after->publish . "\n";
?>