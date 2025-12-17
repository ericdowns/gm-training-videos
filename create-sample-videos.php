<?php
/**
 * Create Sample Training Videos
 * Run this file to populate sample training videos for testing
 * 
 * Usage: Add to functions.php temporarily or run via WP-CLI eval-file
 */

function create_sample_training_videos() {
    
    // Sample video data with Loom examples
    // These use a mix of real Loom demo videos and placeholders
    $sample_videos = array(
        array(
            'title' => 'Getting Started with Your WordPress Dashboard',
            'description' => 'Learn the basics of navigating your WordPress admin area, understanding the dashboard widgets, and customizing your workspace.',
            'loom_url' => 'https://www.loom.com/embed/7a8e91e8a3e74a18a982e09647ad2495',  // Example Loom video
            'menu_order' => 1
        ),
        array(
            'title' => 'Creating and Editing Pages',
            'description' => 'Master the page editor, work with blocks, and understand page settings to create compelling content for your website.',
            'loom_url' => 'https://www.youtube.com/embed/JjfrzGeB5_g',  // WordPress pages tutorial
            'menu_order' => 2
        ),
        array(
            'title' => 'Managing Media Files and Images',
            'description' => 'Upload, organize, and optimize your images and media files. Learn about alt text, captions, and image SEO best practices.',
            'loom_url' => 'https://www.youtube.com/embed/VzPo79-4mD4',  // WordPress media library
            'menu_order' => 3
        ),
        array(
            'title' => 'Working with Menus and Navigation',
            'description' => 'Create and customize your site navigation, manage menu locations, and build user-friendly navigation structures.',
            'loom_url' => 'https://www.youtube.com/embed/6ArMNTLABpY',  // WordPress menus
            'menu_order' => 4
        ),
        array(
            'title' => 'Understanding SEO with Yoast',
            'description' => 'Optimize your content for search engines using Yoast SEO. Learn about meta descriptions, focus keywords, and readability.',
            'loom_url' => '',  // Leave empty to show placeholder
            'menu_order' => 5
        ),
        array(
            'title' => 'Managing Portfolio Projects',
            'description' => 'Add and organize your portfolio items, set featured images, and categorize your work effectively.',
            'loom_url' => '',  // Leave empty to show placeholder
            'menu_order' => 6
        ),
        array(
            'title' => 'Using Advanced Custom Fields',
            'description' => 'Work with custom fields to add specialized content to your pages. Understand field types and how to use them effectively.',
            'loom_url' => 'https://www.youtube.com/embed/QwENf-Vn4tE',  // ACF tutorial
            'menu_order' => 7
        ),
        array(
            'title' => 'Contact Form Management',
            'description' => 'Set up and manage contact forms, configure email notifications, and handle form submissions effectively.',
            'loom_url' => '',  // Leave empty to show placeholder
            'menu_order' => 8
        ),
        array(
            'title' => 'Homepage Hero Section Updates',
            'description' => 'Learn how to update your homepage hero image, text, and call-to-action buttons to keep your site fresh and engaging.',
            'loom_url' => '',  // Leave empty to show placeholder
            'menu_order' => 9
        ),
        array(
            'title' => 'Team Member Management',
            'description' => 'Add, edit, and organize team member profiles. Include bios, photos, and contact information for your staff.',
            'loom_url' => '',  // Leave empty to show placeholder
            'menu_order' => 10
        ),
        array(
            'title' => 'Blog Post Creation and Categories',
            'description' => 'Write engaging blog posts, use categories and tags effectively, and schedule posts for future publication.',
            'loom_url' => 'https://www.youtube.com/embed/Ka2pWqXfgA4',  // WordPress blogging
            'menu_order' => 11
        ),
        array(
            'title' => 'Website Performance and Caching',
            'description' => 'Understand how caching works, when to clear cache, and basic performance optimization tips for your website.',
            'loom_url' => '',  // Leave empty to show placeholder
            'menu_order' => 12
        )
    );
    
    // Create each sample video
    foreach ($sample_videos as $video) {
        // Check if a post with this title already exists
        $existing = get_page_by_title($video['title'], OBJECT, 'training_videos');
        
        if (!$existing) {
            $post_id = wp_insert_post(array(
                'post_title'    => $video['title'],
                'post_status'   => 'publish',
                'post_type'     => 'training_videos',
                'menu_order'    => $video['menu_order']
            ));
            
            if ($post_id && !is_wp_error($post_id)) {
                // Add meta fields
                update_post_meta($post_id, '_video_description', $video['description']);
                if (!empty($video['loom_url'])) {
                    update_post_meta($post_id, '_loom_video_url', $video['loom_url']);
                }
                
                echo "Created: " . $video['title'] . " (ID: $post_id)\n";
            }
        } else {
            echo "Already exists: " . $video['title'] . "\n";
        }
    }
    
    echo "\nSample training videos created successfully!\n";
    echo "Visit " . get_post_type_archive_link('training_videos') . " to see them.\n";
}

// Only run if this file is accessed directly (not recommended for production)
// Better to call this function from elsewhere
if (defined('WP_CLI') && WP_CLI) {
    create_sample_training_videos();
}

// Or add this temporarily to your theme's functions.php and visit any page once:
// add_action('init', function() {
//     if (current_user_can('manage_options') && isset($_GET['create_sample_videos'])) {
//         create_sample_training_videos();
//         die('Sample videos created! <a href="' . get_post_type_archive_link('training_videos') . '">View them here</a>');
//     }
// });
// Then visit: yoursite.com/?create_sample_videos=1
?>