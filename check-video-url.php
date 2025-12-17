<?php
/**
 * Check video URL for debugging
 * Visit: http://steven-ginn-architects.local/wp-content/plugins/training-videos/check-video-url.php
 */

// Load WordPress
require_once('../../../wp-load.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Video URLs</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .url { word-break: break-all; font-family: monospace; font-size: 12px; }
        .test-iframe { width: 400px; height: 225px; border: 2px solid #ddd; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Training Videos URL Check</h1>
    
    <?php
    // Get the video by title
    $target_video = get_page_by_title('Creating and Editing Pages', OBJECT, 'training_videos');
    
    if ($target_video) {
        echo '<h2>Video: Creating and Editing Pages</h2>';
        echo '<p>Post ID: ' . $target_video->ID . '</p>';
        echo '<p>Status: ' . $target_video->post_status . '</p>';
        
        $video_url = get_post_meta($target_video->ID, '_loom_video_url', true);
        echo '<p>Stored URL: <span class="url">' . htmlspecialchars($video_url) . '</span></p>';
        
        if ($video_url) {
            echo '<h3>Test Iframe (exactly as stored):</h3>';
            echo '<iframe src="' . esc_url($video_url) . '" class="test-iframe" frameborder="0" allowfullscreen></iframe>';
            
            echo '<h3>Test Iframe (with YouTube parameters):</h3>';
            echo '<iframe src="' . esc_url($video_url) . '" class="test-iframe" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        }
    } else {
        echo '<p style="color: red;">Video "Creating and Editing Pages" not found!</p>';
    }
    
    echo '<hr>';
    echo '<h2>All Training Videos URLs:</h2>';
    
    $videos = get_posts(array(
        'post_type' => 'training_videos',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ));
    
    if ($videos) {
        echo '<table>';
        echo '<tr><th>Title</th><th>URL</th><th>Type</th><th>Preview</th></tr>';
        
        foreach ($videos as $video) {
            $url = get_post_meta($video->ID, '_loom_video_url', true);
            $type = 'Unknown';
            
            if (empty($url)) {
                $type = 'Empty';
            } elseif (strpos($url, 'youtube.com') !== false) {
                $type = 'YouTube';
            } elseif (strpos($url, 'loom.com') !== false) {
                $type = 'Loom';
            } elseif (strpos($url, 'vimeo.com') !== false) {
                $type = 'Vimeo';
            }
            
            echo '<tr>';
            echo '<td>' . $video->post_title . '</td>';
            echo '<td class="url">' . htmlspecialchars($url) . '</td>';
            echo '<td>' . $type . '</td>';
            echo '<td>';
            if (!empty($url)) {
                echo '<a href="' . get_permalink($video->ID) . '" target="_blank">View →</a>';
            } else {
                echo 'N/A';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    ?>
    
    <hr>
    <h2>Actions:</h2>
    <p>
        <a href="<?php echo get_post_type_archive_link('training_videos'); ?>" target="_blank">View Archive</a> | 
        <a href="/wp-content/plugins/training-videos/update-videos.php">Update Video URLs</a> | 
        <a href="<?php echo admin_url('edit.php?post_type=training_videos'); ?>">Edit in Admin</a>
    </p>
</body>
</html>