<?php
/**
 * Flush rewrite rules for Training Videos
 * Visit this file directly to flush permalinks
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user can manage options
if (!current_user_can('manage_options')) {
    die('You must be logged in as an administrator.');
}

// Flush rewrite rules
flush_rewrite_rules();

echo "Rewrite rules flushed successfully!<br><br>";
echo '<a href="' . get_post_type_archive_link('training_videos') . '">Visit Training Videos Archive</a><br>';
echo '<a href="' . admin_url('edit.php?post_type=training_videos') . '">View in Admin</a>';
?>