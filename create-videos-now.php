<?php
/**
 * Direct creation of sample training videos
 * Run this file by visiting: http://steven-ginn-architects.local/wp-content/plugins/training-videos/create-videos-now.php
 */

// Try multiple paths to find wp-load.php
$wp_paths = [
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../../../wp-load.php',
    dirname(dirname(dirname(__DIR__))) . '/wp-load.php',
];

$wp_loaded = false;
foreach ($wp_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Error: Could not load WordPress. Please check the path to wp-load.php');
}

// Check if user can manage options
if (!current_user_can('manage_options')) {
    die('Error: You must be logged in as an administrator to run this script.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Sample Training Videos</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Create Sample Training Videos</h1>
    
    <?php
    // Check current status
    $existing = get_posts([
        'post_type' => 'training_videos',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ]);
    
    echo '<p class="info">Current training videos: ' . count($existing) . '</p>';
    
    if (count($existing) > 0) {
        echo '<p class="error">Training videos already exist. Delete them first if you want to recreate.</p>';
        echo '<h3>Existing Videos:</h3><ul>';
        foreach ($existing as $video) {
            echo '<li>' . $video->post_title . ' (Status: ' . $video->post_status . ')</li>';
        }
        echo '</ul>';
    } else {
        // Create sample videos
        $sample_videos = array(
            array(
                'title' => 'Getting Started with Your WordPress Dashboard',
                'description' => 'Learn the basics of navigating your WordPress admin area, understanding the dashboard widgets, and customizing your workspace.',
                'loom_url' => 'https://www.loom.com/embed/3d0e2f5c9e6a4d3b9c5e8f7a2b1c4d5e',
                'menu_order' => 1
            ),
            array(
                'title' => 'Creating and Editing Pages',
                'description' => 'Master the page editor, work with blocks, and understand page settings to create compelling content for your website.',
                'loom_url' => 'https://www.loom.com/embed/7f8e9d0c1b2a3c4d5e6f7a8b9c0d1e2f',
                'menu_order' => 2
            ),
            array(
                'title' => 'Managing Media Files and Images',
                'description' => 'Upload, organize, and optimize your images and media files. Learn about alt text, captions, and image SEO best practices.',
                'loom_url' => 'https://www.loom.com/embed/1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p',
                'menu_order' => 3
            ),
            array(
                'title' => 'Working with Menus and Navigation',
                'description' => 'Create and customize your site navigation, manage menu locations, and build user-friendly navigation structures.',
                'loom_url' => 'https://www.loom.com/embed/9i8h7g6f5e4d3c2b1a0z9y8x7w6v5u4t',
                'menu_order' => 4
            ),
            array(
                'title' => 'Understanding SEO with Yoast',
                'description' => 'Optimize your content for search engines using Yoast SEO. Learn about meta descriptions, focus keywords, and readability.',
                'loom_url' => 'https://www.loom.com/embed/4t5y6u7i8o9p0a1s2d3f4g5h6j7k8l9z',
                'menu_order' => 5
            ),
            array(
                'title' => 'Managing Portfolio Projects',
                'description' => 'Add and organize your portfolio items, set featured images, and categorize your work effectively.',
                'loom_url' => 'https://www.loom.com/embed/2w3e4r5t6y7u8i9o0p1q2w3e4r5t6y7u',
                'menu_order' => 6
            )
        );
        
        echo '<h3>Creating Sample Videos...</h3>';
        
        $created = 0;
        foreach ($sample_videos as $video) {
            $post_id = wp_insert_post(array(
                'post_title'    => $video['title'],
                'post_status'   => 'publish',
                'post_type'     => 'training_videos',
                'menu_order'    => $video['menu_order']
            ));
            
            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_video_description', $video['description']);
                update_post_meta($post_id, '_loom_video_url', $video['loom_url']);
                echo '<p class="success">✓ Created: ' . $video['title'] . ' (ID: ' . $post_id . ')</p>';
                $created++;
            } else {
                echo '<p class="error">✗ Failed to create: ' . $video['title'] . '</p>';
                if (is_wp_error($post_id)) {
                    echo '<pre>' . $post_id->get_error_message() . '</pre>';
                }
            }
        }
        
        echo '<h3 class="success">Successfully created ' . $created . ' training videos!</h3>';
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    ?>
    
    <hr>
    <p>
        <a href="<?php echo get_post_type_archive_link('training_videos'); ?>" target="_blank">
            View Training Videos on Frontend →
        </a>
        |
        <a href="<?php echo admin_url('edit.php?post_type=training_videos'); ?>">
            Manage in WP Admin →
        </a>
    </p>
</body>
</html>