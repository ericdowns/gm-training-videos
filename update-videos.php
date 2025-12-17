<?php
/**
 * Update existing training videos with working video URLs
 * Visit: http://steven-ginn-architects.local/wp-content/plugins/training-videos/update-videos.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user can manage options
if (!current_user_can('manage_options')) {
    die('You must be logged in as an administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Training Videos</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; }
        .warning { color: orange; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Update Training Videos with Working URLs</h1>
    
    <?php
    // New video URLs (mix of YouTube demos and empty for placeholders)
    $video_updates = array(
        'Getting Started with Your WordPress Dashboard' => 'https://www.youtube.com/embed/qlPqA9VcrJo',
        'Creating and Editing Pages' => 'https://www.youtube.com/embed/JjfrzGeB5_g',
        'Managing Media Files and Images' => 'https://www.youtube.com/embed/VzPo79-4mD4',
        'Working with Menus and Navigation' => 'https://www.youtube.com/embed/6ArMNTLABpY',
        'Understanding SEO with Yoast' => '',
        'Managing Portfolio Projects' => '',
        'Using Advanced Custom Fields' => 'https://www.youtube.com/embed/QwENf-Vn4tE',
        'Contact Form Management' => '',
        'Homepage Hero Section Updates' => '',
        'Team Member Management' => '',
        'Blog Post Creation and Categories' => 'https://www.youtube.com/embed/Ka2pWqXfgA4',
        'Website Performance and Caching' => ''
    );
    
    // Get all training videos
    $videos = get_posts(array(
        'post_type' => 'training_videos',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));
    
    echo '<p>Found ' . count($videos) . ' training videos to update.</p>';
    
    $updated = 0;
    foreach ($videos as $video) {
        $title = $video->post_title;
        
        if (isset($video_updates[$title])) {
            $new_url = $video_updates[$title];
            $old_url = get_post_meta($video->ID, '_loom_video_url', true);
            
            // Update the URL
            update_post_meta($video->ID, '_loom_video_url', $new_url);
            
            if (!empty($new_url)) {
                echo '<p class="success">✓ Updated "' . $title . '" with YouTube demo video</p>';
            } else {
                echo '<p class="warning">○ Updated "' . $title . '" - no video (placeholder will show)</p>';
            }
            $updated++;
        } else {
            echo '<p class="info">ℹ Skipped "' . $title . '" - not in update list</p>';
        }
    }
    
    echo '<h3>Update Complete!</h3>';
    echo '<p>Updated ' . $updated . ' videos.</p>';
    ?>
    
    <hr>
    <p>
        <strong>Next steps:</strong><br>
        <a href="<?php echo get_post_type_archive_link('training_videos'); ?>" target="_blank">
            View Training Videos Archive →
        </a><br>
        <a href="<?php echo admin_url('edit.php?post_type=training_videos'); ?>">
            Manage in WP Admin →
        </a>
    </p>
    
    <h3>Notes:</h3>
    <ul>
        <li>Videos with YouTube URLs will show actual WordPress tutorial videos</li>
        <li>Videos without URLs will show "No preview available" placeholder</li>
        <li>When you add real Loom videos, use the embed URL format: <code>https://www.loom.com/embed/[video-id]</code></li>
        <li>You can also use YouTube embed URLs: <code>https://www.youtube.com/embed/[video-id]</code></li>
    </ul>
</body>
</html>