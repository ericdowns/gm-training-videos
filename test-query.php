<?php
/**
 * Test Training Videos Query
 * Visit: http://steven-ginn-architects.local/wp-content/plugins/training-videos/test-query.php
 */

// Load WordPress
require_once('../../../wp-load.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Training Videos Query</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Training Videos Query Test</h1>
    
    <h2>1. Direct Database Query</h2>
    <?php
    global $wpdb;
    $sql = "SELECT ID, post_title, post_status, menu_order FROM {$wpdb->posts} WHERE post_type = 'training_videos' ORDER BY menu_order ASC";
    $results = $wpdb->get_results($sql);
    
    echo '<p>Found ' . count($results) . ' training videos in database</p>';
    
    if (count($results) > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Title</th><th>Status</th><th>Order</th></tr>';
        foreach ($results as $video) {
            echo '<tr>';
            echo '<td>' . $video->ID . '</td>';
            echo '<td>' . $video->post_title . '</td>';
            echo '<td>' . $video->post_status . '</td>';
            echo '<td>' . $video->menu_order . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    ?>
    
    <h2>2. WP_Query Test</h2>
    <?php
    $args = array(
        'post_type' => 'training_videos',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC'
    );
    
    $query = new WP_Query($args);
    
    echo '<p>WP_Query found: ' . $query->found_posts . ' posts</p>';
    echo '<p>SQL Query: <pre>' . $query->request . '</pre></p>';
    
    if ($query->have_posts()) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Title</th><th>Loom URL</th><th>Description</th></tr>';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<tr>';
            echo '<td>' . get_the_ID() . '</td>';
            echo '<td>' . get_the_title() . '</td>';
            echo '<td>' . substr(get_post_meta(get_the_ID(), '_loom_video_url', true), 0, 50) . '...</td>';
            echo '<td>' . get_post_meta(get_the_ID(), '_video_description', true) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        wp_reset_postdata();
    }
    ?>
    
    <h2>3. get_posts() Test</h2>
    <?php
    $posts = get_posts(array(
        'post_type' => 'training_videos',
        'numberposts' => -1,
        'post_status' => 'publish'
    ));
    
    echo '<p>get_posts() found: ' . count($posts) . ' posts</p>';
    ?>
    
    <h2>4. Archive Link</h2>
    <?php
    $archive_link = get_post_type_archive_link('training_videos');
    echo '<p>Archive link: <a href="' . $archive_link . '">' . $archive_link . '</a></p>';
    ?>
    
    <h2>5. Post Type Registration Check</h2>
    <?php
    $post_type_obj = get_post_type_object('training_videos');
    if ($post_type_obj) {
        echo '<p class="success">✓ Post type is registered</p>';
        echo '<p>Has archive: ' . ($post_type_obj->has_archive ? 'Yes' : 'No') . '</p>';
        echo '<p>Public: ' . ($post_type_obj->public ? 'Yes' : 'No') . '</p>';
        echo '<p>Publicly queryable: ' . ($post_type_obj->publicly_queryable ? 'Yes' : 'No') . '</p>';
        echo '<p>Rewrite slug: ' . (isset($post_type_obj->rewrite['slug']) ? $post_type_obj->rewrite['slug'] : 'N/A') . '</p>';
    } else {
        echo '<p class="error">✗ Post type not registered!</p>';
    }
    ?>
    
    <h2>6. Rewrite Rules</h2>
    <?php
    global $wp_rewrite;
    $rules = $wp_rewrite->wp_rewrite_rules();
    $training_rules = array_filter($rules, function($rule) {
        return strpos($rule, 'training') !== false || strpos($rule, 'training_videos') !== false;
    }, ARRAY_FILTER_USE_KEY);
    
    if (!empty($training_rules)) {
        echo '<p>Training video rewrite rules:</p>';
        echo '<pre>';
        print_r($training_rules);
        echo '</pre>';
    } else {
        echo '<p class="error">No rewrite rules found for training videos!</p>';
    }
    ?>
    
    <hr>
    <h2>Actions</h2>
    <p>
        <a href="<?php echo admin_url('options-permalink.php'); ?>" target="_blank">
            Flush Permalinks in Settings
        </a> | 
        <a href="<?php echo get_post_type_archive_link('training_videos'); ?>" target="_blank">
            Visit Archive Page
        </a> | 
        <a href="<?php echo admin_url('edit.php?post_type=training_videos'); ?>" target="_blank">
            View in Admin
        </a>
    </p>
</body>
</html>