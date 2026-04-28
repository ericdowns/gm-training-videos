<?php
/**
 * Plugin Name: Training Videos
 * Plugin URI: https://grainandmortar.com
 * Description: A custom plugin made by Grain & Mortar that displays training videos.
 * Version: 1.3.1
 * Author: Grain & Mortar | Technical Director - Eric Downs (eric@grainandmortar.com)
 * Author URI: https://grainandmortar.com
 * License: Grain & Mortar 
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'inc/loom-helpers.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/brand.php';



// Enqueue self-contained styles + Font Awesome only on plugin pages.
// The CSS is theme-independent so the plugin renders correctly regardless of
// the parent theme. Brand-theming card #4 will overlay CSS variables on top.
function training_videos_enqueue_styles() {
    if ( ! is_singular( 'training_videos' ) && ! is_post_type_archive( 'training_videos' ) ) {
        return;
    }
    $version = '1.3.1';
    wp_enqueue_style(
        'training-videos-fontawesome',
        'https://use.fontawesome.com/releases/v6.5.1/css/all.css',
        array(),
        '6.5.1'
    );
    wp_enqueue_style(
        'training-videos',
        plugins_url( 'css/training-videos.css', __FILE__ ),
        array( 'training-videos-fontawesome' ),
        $version
    );
}
add_action( 'wp_enqueue_scripts', 'training_videos_enqueue_styles' );








// Register custom post type for training videos
function create_training_videos_post_type() {
    $labels = array(
        'name' => __( 'Training Videos' ),
        'singular_name' => __( 'Training Video' ),
        'menu_name' => __( 'Training Videos' ),
        'all_items' => __( 'All Training Videos' ),
        'add_new' => __( 'Add New' ),
        'add_new_item' => __( 'Add New Training Video' ),
        'edit_item' => __( 'Edit Training Video' ),
        'new_item' => __( 'New Training Video' ),
        'view_item' => __( 'View Training Video' ),
        'search_items' => __( 'Search Training Videos' ),
        'not_found' => __( 'No training videos found' ),
        'not_found_in_trash' => __( 'No training videos found in trash' ),
        'parent_item_colon' => ''
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-video-alt',
        'rewrite' => array('slug' => 'training-videos'), // Set custom slug for training videos
        'supports' => array( 'title', 'custom-fields' ),
        'publicly_queryable' => true,
        'exclude_from_search' => true, // Add this line to exclude from search
        'noindex' => true, // Add this line to add "noindex" meta tag
        'menu_order' => true, // Add this line to support menu order
        'show_in_menu' => true, // Add this line to show in admin menu

    );

    register_post_type( 'training_videos', $args ); // Register the post type
}
add_action( 'init', 'create_training_videos_post_type' );




// Hide Yoast SEO 
function hide_yoast_seo_from_custom_post_type() {
    $post_types = array( 'training_videos' ); // Add your custom post types here
    
    foreach ( $post_types as $post_type ) {
        remove_meta_box( 'wpseo_meta', $post_type, 'normal' );
    }
}
add_action( 'add_meta_boxes', 'hide_yoast_seo_from_custom_post_type', 100 );



// Modify posts per page for training videos archive
function modify_training_videos_posts_per_page( $query ) {
    if ( is_post_type_archive( 'training_videos' ) && $query->is_main_query() && !is_admin() ) {
        $query->set( 'posts_per_page', 100 );
        $query->set( 'orderby', 'menu_order' );
        $query->set( 'order', 'ASC' );
        $query->set( 'post_status', 'publish' );
        
        // Debug logging
        error_log('Training Videos Query Modified - Posts per page: 100');
    }
}
add_action( 'pre_get_posts', 'modify_training_videos_posts_per_page', 10 );






// Specify custom archive template for training videos post type
function training_videos_archive_template( $archive_template ) {
    global $post;

    if ( is_post_type_archive( 'training_videos' ) ) {
        $archive_template = plugin_dir_path( __FILE__ ) . 'templates/archive-training_videos.php';
    }

    return $archive_template;
}
add_filter( 'archive_template', 'training_videos_archive_template' );






// Specify custom single template for training videos post type
function training_videos_single_template( $single_template ) {
    global $post;

    if ( $post->post_type == 'training_videos' ) {
        $single_template = plugin_dir_path( __FILE__ ) . 'templates/single-training_videos.php';
    }

    return $single_template;
}
add_filter( 'single_template', 'training_videos_single_template' );

// Flush rewrite rules to include custom slug for training videos
function flush_rewrite_rules_on_activation() {
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'flush_rewrite_rules_on_activation' );

// Note: Theme styles are now intentionally loaded to use Tailwind CSS
// This provides consistent styling with the rest of the site



// ============================================================================
// PLUGIN SETTINGS - Google Doc Resource
// ============================================================================

/**
 * Register settings page under Training Videos menu
 */
function training_videos_register_settings_page() {
    add_submenu_page(
        'edit.php?post_type=training_videos',
        'Training Videos Settings',
        'Settings',
        'manage_options',
        'training-videos-settings',
        'training_videos_settings_page_html'
    );
}
add_action( 'admin_menu', 'training_videos_register_settings_page' );

/**
 * Register settings
 */
function training_videos_register_settings() {
    register_setting( 'training_videos_settings', 'training_videos_resource_title' );
    register_setting( 'training_videos_settings', 'training_videos_resource_url' );
    register_setting( 'training_videos_settings', 'training_videos_resource_description' );
    foreach ( training_videos_brand_fields() as $field ) {
        register_setting( 'training_videos_settings', $field['option'] );
    }
}
add_action( 'admin_init', 'training_videos_register_settings' );

/**
 * Settings page HTML
 */
function training_videos_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Save settings
    if ( isset( $_POST['training_videos_settings_nonce'] ) && wp_verify_nonce( $_POST['training_videos_settings_nonce'], 'training_videos_settings' ) ) {
        update_option( 'training_videos_resource_title', sanitize_text_field( $_POST['resource_title'] ?? '' ) );
        update_option( 'training_videos_resource_url', esc_url_raw( $_POST['resource_url'] ?? '' ) );
        update_option( 'training_videos_resource_description', sanitize_text_field( $_POST['resource_description'] ?? '' ) );

        foreach ( training_videos_brand_fields() as $key => $field ) {
            $raw = $_POST[ 'brand_' . $key ] ?? '';
            switch ( $field['type'] ) {
                case 'color':
                    $clean = training_videos_sanitize_hex_color( $raw );
                    break;
                case 'font':
                    $clean = training_videos_sanitize_font_family( $raw );
                    break;
                case 'url':
                    $clean = esc_url_raw( $raw );
                    break;
                default:
                    $clean = sanitize_text_field( $raw );
            }
            update_option( $field['option'], $clean );
        }

        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }

    $resource_title = get_option( 'training_videos_resource_title', '' );
    $resource_url = get_option( 'training_videos_resource_url', '' );
    $resource_description = get_option( 'training_videos_resource_description', '' );
    $brand          = training_videos_get_brand();
    $brand_fields   = training_videos_brand_fields();
    ?>
    <div class="wrap">
        <h1>Training Videos Settings</h1>

        <form method="post">
            <?php wp_nonce_field( 'training_videos_settings', 'training_videos_settings_nonce' ); ?>

            <h2>Documentation Resource</h2>
            <p class="description">Add a link to a Google Doc or other documentation that will appear at the top of the Training Library. This is separate from video content.</p>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="resource_title">Resource Title</label></th>
                    <td>
                        <input type="text" id="resource_title" name="resource_title" value="<?php echo esc_attr( $resource_title ); ?>" class="regular-text" placeholder="e.g., Module Documentation">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="resource_url">Resource URL</label></th>
                    <td>
                        <input type="url" id="resource_url" name="resource_url" value="<?php echo esc_attr( $resource_url ); ?>" class="large-text" placeholder="https://docs.google.com/document/d/...">
                        <p class="description">Google Doc URL or any external link</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="resource_description">Description</label></th>
                    <td>
                        <input type="text" id="resource_description" name="resource_description" value="<?php echo esc_attr( $resource_description ); ?>" class="large-text" placeholder="e.g., Complete guide to all website modules">
                    </td>
                </tr>
            </table>

            <hr style="margin: 30px 0;">

            <h2>Brand Theme</h2>
            <p class="description">
                Override the default California Forever palette and fonts on this site. Leave any field empty to fall back to the plugin default.
                Hex colors only (e.g. <code>#272727</code>). Font families take any valid CSS <code>font-family</code> value.
            </p>

            <table class="form-table">
                <?php foreach ( $brand_fields as $key => $field ) :
                    $value = $brand[ $key ];
                    $id    = 'brand_' . $key;
                    ?>
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
                        <td>
                            <?php if ( 'color' === $field['type'] ) : ?>
                                <input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="#FFBC21" pattern="^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$">
                                <input type="color" value="<?php echo esc_attr( $value ?: '#000000' ); ?>" onchange="this.previousElementSibling.value=this.value.toUpperCase();" style="vertical-align: middle; margin-left: 8px;">
                            <?php elseif ( 'url' === $field['type'] ) : ?>
                                <input type="url" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" class="large-text" placeholder="https://fonts.googleapis.com/css2?family=Inter">
                            <?php else : ?>
                                <input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $field['help'] ); ?>">
                            <?php endif; ?>
                            <p class="description"><?php echo esc_html( $field['help'] ); ?></p>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php submit_button( 'Save Settings' ); ?>
        </form>
    </div>
    <?php
}

// ============================================================================
// META BOXES
// ============================================================================

// Add custom meta box for Loom video URL
function add_training_video_meta_box() {
    add_meta_box(
        'training_video_meta_box',
        'Loom Video URL',
        'training_video_meta_box_html',
        'training_videos',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'add_training_video_meta_box' );




// Display custom meta box for Loom video URL
function training_video_meta_box_html( $post ) {
    $loom_video_url = get_post_meta( $post->ID, '_loom_video_url', true );
    wp_nonce_field( 'save_training_video_meta', 'training_video_meta_nonce' );
    ?>
    <div style="padding: 10px; background: #f0f0f1; border-left: 4px solid #7c3aed; margin-bottom: 15px;">
        <p style="margin: 0 0 10px 0; font-weight: bold; color: #7c3aed;">
            🎥 Loom Video URL Helper
        </p>
        <p style="margin: 0 0 5px 0; font-size: 13px;">
            <strong>Option 1:</strong> Paste your Loom share URL (we'll auto-convert it)<br>
            Example: <code style="background: white; padding: 2px 4px;">https://www.loom.com/share/abc123...</code>
        </p>
        <p style="margin: 0 0 5px 0; font-size: 13px;">
            <strong>Option 2:</strong> Use the embed URL directly<br>
            Example: <code style="background: white; padding: 2px 4px;">https://www.loom.com/embed/abc123...</code>
        </p>
        <p style="margin: 10px 0 0 0; font-size: 13px;">
            <a href="<?php echo plugins_url('loom-helper.php', __FILE__); ?>" target="_blank" style="color: #7c3aed; text-decoration: none; font-weight: bold;">
                → Open Loom Helper Tool
            </a> | 
            <a href="https://www.loom.com/my-videos" target="_blank" style="color: #7c3aed; text-decoration: none;">
                View Your Loom Videos
            </a>
        </p>
    </div>
    <p>
       <label for="loom_video_url"><strong><?php _e( 'Video URL:', 'training-videos' ); ?></strong></label>
       <br>
       <input type="text" 
              id="loom_video_url" 
              name="loom_video_url" 
              value="<?php echo esc_attr( $loom_video_url ); ?>" 
              style="width: 100%; max-width: 600px; padding: 8px;"
              placeholder="Paste Loom share or embed URL here...">
   </p>
   <?php if ($loom_video_url && strpos($loom_video_url, 'loom.com/embed/') !== false): ?>
       <?php 
       // Extract video ID for thumbnail
       preg_match('/embed\/([a-zA-Z0-9]+)/', $loom_video_url, $matches);
       $video_id = isset($matches[1]) ? $matches[1] : '';
       if ($video_id):
       ?>
       <div style="margin-top: 15px;">
           <p style="font-weight: bold; margin-bottom: 10px;">Preview Thumbnail:</p>
           <img src="https://cdn.loom.com/sessions/thumbnails/<?php echo $video_id; ?>-with-play.gif" 
                style="max-width: 300px; border: 1px solid #ddd; border-radius: 4px;"
                onerror="this.src='https://cdn.loom.com/sessions/thumbnails/<?php echo $video_id; ?>-00001.jpg'">
       </div>
       <?php endif; ?>
   <?php endif; ?>
   <?php
}



// Save custom meta box data for Loom video URL
function save_training_video_meta( $post_id ) {
    if ( ! isset( $_POST['training_video_meta_nonce'] ) || ! wp_verify_nonce( $_POST['training_video_meta_nonce'], 'save_training_video_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['loom_video_url'] ) ) {
        $url = sanitize_text_field( $_POST['loom_video_url'] );
        
        // Auto-convert Loom share URLs to embed URLs
        if ( strpos( $url, 'loom.com/share/' ) !== false ) {
            // Extract video ID and convert to embed format
            if ( preg_match( '/loom\.com\/share\/([a-zA-Z0-9]+)/', $url, $matches ) ) {
                $url = 'https://www.loom.com/embed/' . $matches[1];
            }
        }
        
        update_post_meta( $post_id, '_loom_video_url', $url );
    }
}
add_action( 'save_post_training_videos', 'save_training_video_meta' );



// Display Loom video on single training video page
function display_loom_video() {
    $loom_video_url = get_post_meta( get_the_ID(), '_loom_video_url', true );
    if ( ! empty( $loom_video_url ) ) {
        ?>
        <div style="position: relative; padding-bottom: 66.01466992665037%; height: 0;"><iframe src="<?php echo esc_url( $loom_video_url ); ?>?hide_owner=true&hide_share=true&hide_title=true&hideEmbedTopBar=true" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe></div>
        <?php
    }
}

add_action( 'training_video_content', 'display_loom_video' );




// Add custom meta box for training video description
function add_training_video_description_meta_box() {
    add_meta_box(
        'training_video_description_meta_box',
        'Description',
        'training_video_description_meta_box_html',
        'training_videos',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'add_training_video_description_meta_box' );




// Display custom meta box for training video description
function training_video_description_meta_box_html( $post ) {
    $video_description = get_post_meta( $post->ID, '_video_description', true );
    wp_nonce_field( 'save_training_video_description_meta', 'training_video_description_meta_nonce' );
    ?>
    <p>
        <label for="video_description"><?php _e( 'Enter a 140 character description for this training video:', 'training-videos' ); ?></label>
        <br>
        <textarea id="video_description" name="video_description" rows="3" cols="90"><?php echo esc_attr( $video_description ); ?></textarea>
    </p>
    <?php
}



// Save custom meta box data for training video description
function save_training_video_description_meta( $post_id ) {
    if ( ! isset( $_POST['training_video_description_meta_nonce'] ) || ! wp_verify_nonce( $_POST['training_video_description_meta_nonce'], 'save_training_video_description_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['video_description'] ) ) {
        update_post_meta( $post_id, '_video_description', sanitize_textarea_field( $_POST['video_description'] ) );
    }
}
add_action( 'save_post_training_videos', 'save_training_video_description_meta' );


// ============================================================================
// LOOM DATA META BOX — refresh buttons (cards #6, #8)
// ============================================================================

function training_videos_add_loom_data_meta_box() {
    add_meta_box(
        'training_video_loom_data',
        'Loom Data',
        'training_videos_loom_data_meta_box_html',
        'training_videos',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'training_videos_add_loom_data_meta_box' );

function training_videos_loom_data_meta_box_html( $post ) {
    $video_url      = get_post_meta( $post->ID, '_loom_video_url', true );
    $thumb_local    = get_post_meta( $post->ID, '_loom_thumbnail_url', true );
    $thumb_attach   = (int) get_post_meta( $post->ID, '_loom_thumbnail_attachment_id', true );
    $description    = get_post_meta( $post->ID, '_video_description', true );

    if ( ! $video_url ) {
        echo '<p style="color: #666;">Add a Loom URL above to enable Loom data sync.</p>';
        return;
    }

    $loom_id = training_videos_extract_loom_id( $video_url );
    $oembed  = $loom_id ? training_videos_fetch_loom_oembed( $video_url ) : false;

    // Notice from a recent refresh action
    if ( isset( $_GET['tv_loom_msg'] ) ) {
        $msg = sanitize_text_field( wp_unslash( $_GET['tv_loom_msg'] ) );
        $is_err = isset( $_GET['tv_loom_err'] );
        printf(
            '<div class="notice notice-%s inline" style="margin: 0 0 12px 0; padding: 6px 10px;"><p style="margin: 0;">%s</p></div>',
            $is_err ? 'error' : 'success',
            esc_html( $msg )
        );
    }

    ?>
    <p style="margin: 0 0 8px 0; font-size: 12px; color: #666;">
        <strong>Video ID:</strong> <code style="font-size: 11px;"><?php echo esc_html( $loom_id ?: '—' ); ?></code>
    </p>
    <?php if ( $oembed ) : ?>
        <p style="margin: 0 0 4px 0; font-size: 12px;">
            <strong>Loom title:</strong><br>
            <span style="color: #444;"><?php echo esc_html( $oembed['title'] ?? '—' ); ?></span>
        </p>
        <?php if ( ! empty( $oembed['duration'] ) ) : ?>
            <p style="margin: 0 0 8px 0; font-size: 12px;">
                <strong>Duration:</strong> <?php echo esc_html( gmdate( 'i:s', (int) $oembed['duration'] ) ); ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <p style="margin: 0 0 8px 0; font-size: 12px;">
        <strong>Thumbnail:</strong><br>
        <?php if ( $thumb_local ) : ?>
            <span style="color: #2e7d32;">✓ Cached locally</span>
            <?php if ( $thumb_attach ) : ?>
                (<a href="<?php echo esc_url( get_edit_post_link( $thumb_attach ) ); ?>">attachment #<?php echo (int) $thumb_attach; ?></a>)
            <?php endif; ?>
        <?php else : ?>
            <span style="color: #b26500;">⚠ Not yet cached</span> (will download on next save)
        <?php endif; ?>
    </p>

    <hr style="margin: 12px 0; border: none; border-top: 1px solid #e1e1e1;">

    <p style="margin: 0 0 8px 0; font-size: 12px; color: #666;">
        Pull the latest description + thumbnail from Loom. The producer's description in Loom is copied verbatim.
    </p>

    <p style="margin: 0; display: flex; gap: 8px; flex-wrap: wrap;">
        <?php
        $refresh_desc_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=training_videos_refresh_description&post=' . $post->ID ),
            'training_videos_refresh_description_' . $post->ID
        );
        $refresh_thumb_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=training_videos_refresh_thumbnail&post=' . $post->ID ),
            'training_videos_refresh_thumbnail_' . $post->ID
        );
        ?>
        <a href="<?php echo esc_url( $refresh_desc_url ); ?>" class="button button-secondary">
            ↻ Refresh description
        </a>
        <a href="<?php echo esc_url( $refresh_thumb_url ); ?>" class="button button-secondary">
            ↻ Refresh thumbnail
        </a>
    </p>
    <?php
}

/**
 * admin-post handler — pull description from Loom + redirect back
 */
function training_videos_handle_refresh_description() {
    $post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( 'Permission denied.' );
    }
    check_admin_referer( 'training_videos_refresh_description_' . $post_id );

    $description = training_videos_refresh_description_from_loom( $post_id );

    $args = array(
        'post'        => $post_id,
        'action'      => 'edit',
        'tv_loom_msg' => $description ? 'Description refreshed from Loom.' : 'Could not fetch a description from Loom — left unchanged.',
    );
    if ( ! $description ) {
        $args['tv_loom_err'] = 1;
    }
    wp_safe_redirect( add_query_arg( $args, admin_url( 'post.php' ) ) );
    exit;
}
add_action( 'admin_post_training_videos_refresh_description', 'training_videos_handle_refresh_description' );

/**
 * admin-post handler — re-sideload thumbnail from Loom + redirect back
 */
function training_videos_handle_refresh_thumbnail() {
    $post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( 'Permission denied.' );
    }
    check_admin_referer( 'training_videos_refresh_thumbnail_' . $post_id );

    $local_url = training_videos_sideload_loom_thumbnail( $post_id, true );

    $args = array(
        'post'        => $post_id,
        'action'      => 'edit',
        'tv_loom_msg' => $local_url ? 'Thumbnail re-cached from Loom.' : 'Could not refresh thumbnail — left unchanged.',
    );
    if ( ! $local_url ) {
        $args['tv_loom_err'] = 1;
    }
    wp_safe_redirect( add_query_arg( $args, admin_url( 'post.php' ) ) );
    exit;
}
add_action( 'admin_post_training_videos_refresh_thumbnail', 'training_videos_handle_refresh_thumbnail' );

/**
 * Register bulk action on the training_videos list table — card #2.
 */
function training_videos_register_bulk_actions( $actions ) {
    $actions['training_videos_pull_descriptions'] = 'Pull descriptions from Loom';
    $actions['training_videos_pull_thumbnails']   = 'Re-cache thumbnails from Loom';
    return $actions;
}
add_filter( 'bulk_actions-edit-training_videos', 'training_videos_register_bulk_actions' );

/**
 * Handle the bulk action — pull descriptions + thumbnails for selected videos.
 */
function training_videos_handle_bulk_actions( $redirect_to, $action, $post_ids ) {
    if ( $action !== 'training_videos_pull_descriptions' && $action !== 'training_videos_pull_thumbnails' ) {
        return $redirect_to;
    }

    $count    = 0;
    $failures = 0;
    foreach ( $post_ids as $post_id ) {
        $post_id = (int) $post_id;
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            $failures++;
            continue;
        }
        if ( $action === 'training_videos_pull_descriptions' ) {
            $result = training_videos_refresh_description_from_loom( $post_id );
        } else {
            $result = training_videos_sideload_loom_thumbnail( $post_id, true );
        }
        if ( $result ) {
            $count++;
        } else {
            $failures++;
        }
    }

    return add_query_arg(
        array(
            'tv_bulk_action'  => $action,
            'tv_bulk_count'   => $count,
            'tv_bulk_failures' => $failures,
        ),
        $redirect_to
    );
}
add_filter( 'handle_bulk_actions-edit-training_videos', 'training_videos_handle_bulk_actions', 10, 3 );

/**
 * Show admin notice after bulk action runs.
 */
function training_videos_bulk_action_notice() {
    if ( empty( $_GET['tv_bulk_action'] ) ) {
        return;
    }
    $action   = sanitize_text_field( wp_unslash( $_GET['tv_bulk_action'] ) );
    $count    = isset( $_GET['tv_bulk_count'] ) ? (int) $_GET['tv_bulk_count'] : 0;
    $failures = isset( $_GET['tv_bulk_failures'] ) ? (int) $_GET['tv_bulk_failures'] : 0;

    $label = $action === 'training_videos_pull_descriptions' ? 'descriptions' : 'thumbnails';
    $msg   = sprintf(
        '%d %s pulled from Loom.%s',
        $count,
        $label,
        $failures ? ' ' . $failures . ' could not be fetched (likely missing Loom URL or oEmbed failed).' : ''
    );

    printf(
        '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
        $failures ? 'warning' : 'success',
        esc_html( $msg )
    );
}
add_action( 'admin_notices', 'training_videos_bulk_action_notice' );





// Add a meta box to the WordPress dashboard home screen
function add_training_videos_meta_box() {
    $screen = get_current_screen();
    if ( $screen->base === 'dashboard' ) {
        add_meta_box(
            'training_videos_meta_box',
            'Training Videos',
            'training_videos_meta_box_html',
            'dashboard',
            'side',
            'high'
        );
    }
}
add_action( 'wp_dashboard_setup', 'add_training_videos_meta_box' );




// Display custom meta box for training videos link
function training_videos_meta_box_html() {
    // Create a new instance of WP_Query to retrieve all training videos
    $training_videos = new WP_Query( array(
        'post_type' => 'training_videos',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ) );

    // Get the number of training videos available
    $num_videos = $training_videos->post_count;

    // Output HTML markup to display the number of training videos available
    ?>
    <p>
        <?php printf( __( '%d training videos available', 'training-videos' ), $num_videos ); ?>
    </p>

    <?php
    // If training videos are available, display a list of links to the video pages
    if ( $training_videos->have_posts() ) : ?>
        <ul>
            <?php 
            // Iterate through each video post and output the title as a hyperlink
            while ( $training_videos->have_posts() ) : $training_videos->the_post(); ?>
                <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
            <?php endwhile; ?>
        </ul>
        <?php
        // Reset the post data to the main query
        wp_reset_postdata();
    endif; 
    // End if statement
    ?>
    <?php
    // Close the function with the final PHP tags
}

// ============================================================================
// ADMIN BAR - Training Videos Link
// ============================================================================

/**
 * Add "Need Help?" link to admin bar
 */
function training_videos_admin_bar_link( $wp_admin_bar ) {
	// Only show for logged-in users
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Add parent "Need Help?" item
	$wp_admin_bar->add_node( array(
		'id'    => 'training-videos-help',
		'title' => '<span class="ab-icon dashicons dashicons-video-alt3" style="font-family: dashicons; font-size: 20px; line-height: 1; margin-right: 6px;"></span>Need Help?',
		'href'  => get_post_type_archive_link( 'training_videos' ),
		'meta'  => array(
			'target' => '_blank',
			'title'  => 'Watch Training Videos',
		),
	) );

	// Add child link for clarity
	$wp_admin_bar->add_node( array(
		'id'     => 'training-videos-watch',
		'parent' => 'training-videos-help',
		'title'  => 'Watch Training Videos',
		'href'   => get_post_type_archive_link( 'training_videos' ),
		'meta'   => array(
			'target' => '_blank',
		),
	) );

	// Add link to documentation resource if set
	$resource_url   = get_option( 'training_videos_resource_url', '' );
	$resource_title = get_option( 'training_videos_resource_title', 'Documentation' );
	if ( $resource_url ) {
		$wp_admin_bar->add_node( array(
			'id'     => 'training-videos-docs',
			'parent' => 'training-videos-help',
			'title'  => $resource_title,
			'href'   => $resource_url,
			'meta'   => array(
				'target' => '_blank',
			),
		) );
	}
}
add_action( 'admin_bar_menu', 'training_videos_admin_bar_link', 100 );

// ============================================================================
// ADMIN NOTICES
// ============================================================================

// Add admin notice for creating sample videos
function training_videos_admin_notices() {
    if (current_user_can('manage_options')) {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'training_videos') {
            $count = wp_count_posts('training_videos');
            if ($count->publish == 0) {
                ?>
                <div class="notice notice-info is-dismissible">
                    <p>
                        <strong>No training videos found!</strong> 
                        Would you like to create sample training videos for testing? 
                        <a href="<?php echo admin_url('edit.php?post_type=training_videos&create_samples=1'); ?>" class="button button-primary" style="margin-left: 10px;">
                            Create 12 Sample Videos
                        </a>
                    </p>
                </div>
                <?php
            }
        }
    }
}
add_action('admin_notices', 'training_videos_admin_notices');

// Handle sample video creation
function handle_sample_video_creation() {
    if (current_user_can('manage_options') && isset($_GET['create_samples']) && $_GET['create_samples'] == '1') {
        // Include the sample creation file
        include_once(plugin_dir_path(__FILE__) . 'create-sample-videos.php');
        
        // Create the videos
        if (function_exists('create_sample_training_videos')) {
            create_sample_training_videos();
            
            // Redirect back without the parameter
            wp_redirect(admin_url('edit.php?post_type=training_videos&samples_created=1'));
            exit;
        }
    }
    
    // Show success message
    if (isset($_GET['samples_created']) && $_GET['samples_created'] == '1') {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Success!</strong> Sample training videos have been created. <a href="<?php echo get_post_type_archive_link('training_videos'); ?>" target="_blank">View them on the frontend →</a></p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'handle_sample_video_creation');








