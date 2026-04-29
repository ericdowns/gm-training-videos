<?php
/**
 * Plugin Name: Training Videos
 * Plugin URI: https://grainandmortar.com
 * Description: A custom plugin made by Grain & Mortar that displays training videos.
 * Version: 1.4.3
 * Author: Grain & Mortar | Technical Director - Eric Downs (eric@grainandmortar.com)
 * Author URI: https://grainandmortar.com
 * License: Grain & Mortar 
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin root URL — used by inc/* files to resolve assets/. Without this
// constant, plugin_dir_url(__FILE__) inside inc/onboarding.php would
// resolve to /inc/ and assets would 404.
if ( ! defined( 'TRAINING_VIDEOS_PLUGIN_URL' ) ) {
    define( 'TRAINING_VIDEOS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once plugin_dir_path( __FILE__ ) . 'inc/loom-helpers.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/brand.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/brand-derive.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/font-detect.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/bulk-import.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/onboarding.php';

// Onboarding flag — set on activation, redirected to the wizard on first
// admin pageload. The actual logic lives in inc/onboarding.php; this hook
// must be in the main plugin file so __FILE__ resolves correctly.
register_activation_hook( __FILE__, 'training_videos_on_activate' );



// Enqueue self-contained styles + Font Awesome only on plugin pages.
// The CSS is theme-independent so the plugin renders correctly regardless of
// the parent theme. Brand-theming card #4 will overlay CSS variables on top.
function training_videos_enqueue_styles() {
    if ( ! is_singular( 'training_videos' ) && ! is_post_type_archive( 'training_videos' ) ) {
        return;
    }
    $version = '1.4.3';
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
        // 'thumbnail' is the override path — set a Featured Image to replace
        // the auto-fetched Loom thumbnail. We DO NOT include 'custom-fields'
        // because that exposes internal underscore-prefixed meta as raw
        // editable rows in the UI (confusing, leaks implementation detail).
        'supports' => array( 'title', 'thumbnail' ),
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
    register_setting( 'training_videos_settings', 'training_videos_brand_primary' );
    register_setting( 'training_videos_settings', 'training_videos_brand_secondary' );
    foreach ( training_videos_brand_fields() as $field ) {
        register_setting( 'training_videos_settings', $field['option'] );
    }
}
add_action( 'admin_init', 'training_videos_register_settings' );

/**
 * Enqueue the wizard's CSS + JS on the Settings page too — same swatch
 * + live-preview UX.
 */
function training_videos_enqueue_settings_assets( $hook ) {
    if ( false === strpos( (string) $hook, 'training-videos-settings' ) ) {
        return;
    }
    $version = '1.4.3';
    wp_enqueue_style(
        'training-videos-onboarding',
        plugins_url( 'assets/admin-onboarding.css', __FILE__ ),
        array(),
        $version
    );
    wp_enqueue_script(
        'training-videos-onboarding',
        plugins_url( 'assets/admin-onboarding.js', __FILE__ ),
        array(),
        $version,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'training_videos_enqueue_settings_assets' );

/**
 * Settings page HTML
 */
function training_videos_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $brand_fields  = training_videos_brand_fields();
    $import_result = null;

    // Save settings
    if ( isset( $_POST['training_videos_settings_nonce'] ) && wp_verify_nonce( $_POST['training_videos_settings_nonce'], 'training_videos_settings' ) ) {
        update_option( 'training_videos_resource_title', sanitize_text_field( $_POST['resource_title'] ?? '' ) );
        update_option( 'training_videos_resource_url', esc_url_raw( $_POST['resource_url'] ?? '' ) );
        update_option( 'training_videos_resource_description', sanitize_text_field( $_POST['resource_description'] ?? '' ) );

        // Brand Colors — primary + secondary drive the auto-derivation.
        $primary   = training_videos_sanitize_hex_color( $_POST['brand_primary'] ?? '' );
        $secondary = training_videos_sanitize_hex_color( $_POST['brand_secondary'] ?? '' );
        update_option( 'training_videos_brand_primary',   $primary );
        update_option( 'training_videos_brand_secondary', $secondary );

        $derived = ( $primary && $secondary )
            ? training_videos_derive_palette( $primary, $secondary )
            : null;

        // For each brand field: Advanced override wins, else derived value,
        // else fall back to whatever was already in the option (no-op).
        foreach ( $brand_fields as $key => $field ) {
            $raw = $_POST[ 'brand_' . $key ] ?? '';
            switch ( $field['type'] ) {
                case 'color':
                    $clean = training_videos_sanitize_hex_color( $raw );
                    if ( '' === $clean && $derived && isset( $derived[ $key ] ) ) {
                        $clean = $derived[ $key ];
                    }
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

        // Bulk import (optional).
        $bulk = trim( (string) ( $_POST['bulk_import_urls'] ?? '' ) );
        if ( '' !== $bulk ) {
            $import_result = training_videos_bulk_import( $bulk );
        }

        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        if ( is_array( $import_result ) ) {
            printf(
                '<div class="notice notice-info"><p>Bulk import — created: <strong>%d</strong> · skipped: <strong>%d</strong> · failed: <strong>%d</strong>.</p></div>',
                count( $import_result['created'] ),
                count( $import_result['skipped'] ),
                count( $import_result['failed'] )
            );
        }
    }

    $resource_title       = get_option( 'training_videos_resource_title', '' );
    $resource_url         = get_option( 'training_videos_resource_url', '' );
    $resource_description = get_option( 'training_videos_resource_description', '' );
    $brand                = training_videos_get_brand();

    $primary   = (string) get_option( 'training_videos_brand_primary',   '' );
    $secondary = (string) get_option( 'training_videos_brand_secondary', '' );
    if ( '' === $primary )   { $primary   = '#112D40'; }
    if ( '' === $secondary ) { $secondary = '#FFBC21'; }

    // Auto-fill font fields from theme detection if currently blank.
    $detected = training_videos_detect_theme_fonts();
    if ( '' === $brand['heading_font'] && '' !== $detected['heading_family'] ) {
        $brand['heading_font'] = $detected['heading_family'];
    }
    if ( '' === $brand['body_font'] && '' !== $detected['body_family'] ) {
        $brand['body_font'] = $detected['body_family'];
    }
    if ( '' === $brand['font_url'] && '' !== $detected['google_url'] ) {
        $brand['font_url'] = $detected['google_url'];
    }
    ?>
    <div class="wrap tv-onboarding">
        <h1>Training Videos Settings</h1>

        <form method="post">
            <?php wp_nonce_field( 'training_videos_settings', 'training_videos_settings_nonce' ); ?>

            <!-- Brand Colors -->
            <section class="tv-onboarding__step">
                <header class="tv-onboarding__step-head">
                    <span class="tv-onboarding__step-num">1</span>
                    <div>
                        <h2>Brand colors</h2>
                        <p>Two colors. The other surfaces auto-derive. Override individual values under <em>Advanced</em> below.</p>
                    </div>
                </header>

                <div class="tv-onboarding__color-grid">
                    <label class="tv-onboarding__color-input">
                        <span>Primary <small>(headers, dark surfaces)</small></span>
                        <div class="tv-onboarding__color-row">
                            <input type="color" id="tv-color-primary-picker"
                                   value="<?php echo esc_attr( $primary ); ?>"
                                   data-tv-color-target="brand_primary">
                            <input type="text" id="tv-color-primary"
                                   name="brand_primary"
                                   value="<?php echo esc_attr( $primary ); ?>"
                                   pattern="^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$"
                                   data-tv-color-source="primary">
                        </div>
                    </label>

                    <label class="tv-onboarding__color-input">
                        <span>Secondary <small>(CTAs, accents)</small></span>
                        <div class="tv-onboarding__color-row">
                            <input type="color" id="tv-color-secondary-picker"
                                   value="<?php echo esc_attr( $secondary ); ?>"
                                   data-tv-color-target="brand_secondary">
                            <input type="text" id="tv-color-secondary"
                                   name="brand_secondary"
                                   value="<?php echo esc_attr( $secondary ); ?>"
                                   pattern="^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$"
                                   data-tv-color-source="secondary">
                        </div>
                    </label>
                </div>

                <h3 class="tv-onboarding__preview-title">Derived palette</h3>
                <div class="tv-onboarding__swatches" id="tv-swatch-grid">
                    <?php
                    $swatch_labels = array(
                        'bg'         => 'Page background',
                        'heading'    => 'Heading + header bg',
                        'text'       => 'Body text',
                        'accent'     => 'Accent (CTAs)',
                        'accent_alt' => 'Accent hover',
                        'border'     => 'Borders',
                        'card_bg'    => 'Card background',
                    );
                    foreach ( $swatch_labels as $key => $label ) :
                        ?>
                        <div class="tv-onboarding__swatch" data-tv-swatch="<?php echo esc_attr( $key ); ?>">
                            <div class="tv-onboarding__swatch-color"></div>
                            <div class="tv-onboarding__swatch-meta">
                                <strong><?php echo esc_html( $label ); ?></strong>
                                <code class="tv-onboarding__swatch-hex">—</code>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="tv-onboarding__preview" id="tv-mini-preview">
                    <div class="tv-onboarding__preview-header">
                        <span class="tv-onboarding__preview-brand">🎓 Training Library</span>
                        <span class="tv-onboarding__preview-cta">Manage</span>
                    </div>
                    <div class="tv-onboarding__preview-body">
                        <h4 class="tv-onboarding__preview-h">Welcome video</h4>
                        <p class="tv-onboarding__preview-p">A short tour of how to use this library.</p>
                        <div class="tv-onboarding__preview-card">
                            <span class="tv-onboarding__preview-card-thumb"></span>
                            <div>
                                <strong>How to add a page</strong>
                                <small>Step-by-step page creation in the editor.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Fonts -->
            <section class="tv-onboarding__step">
                <header class="tv-onboarding__step-head">
                    <span class="tv-onboarding__step-num">2</span>
                    <div>
                        <h2>Fonts</h2>
                        <p>
                            <?php if ( '' !== $detected['body_family'] || '' !== $detected['heading_family'] ) : ?>
                                Auto-detected from your active theme. Edit if needed.
                            <?php else : ?>
                                Optional — leave blank to use the system stack.
                            <?php endif; ?>
                        </p>
                    </div>
                </header>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="brand_heading_font">Heading family</label></th>
                        <td>
                            <input type="text" id="brand_heading_font" name="brand_heading_font"
                                   value="<?php echo esc_attr( $brand['heading_font'] ); ?>"
                                   class="regular-text"
                                   placeholder='"Playfair Display", serif'>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="brand_body_font">Body family</label></th>
                        <td>
                            <input type="text" id="brand_body_font" name="brand_body_font"
                                   value="<?php echo esc_attr( $brand['body_font'] ); ?>"
                                   class="regular-text"
                                   placeholder='"Inter", sans-serif'>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="brand_font_url">Font URL</label></th>
                        <td>
                            <input type="url" id="brand_font_url" name="brand_font_url"
                                   value="<?php echo esc_attr( $brand['font_url'] ); ?>"
                                   class="large-text"
                                   placeholder="https://fonts.googleapis.com/css2?family=Inter">
                        </td>
                    </tr>
                </table>
            </section>

            <!-- Bulk Import -->
            <section class="tv-onboarding__step">
                <header class="tv-onboarding__step-head">
                    <span class="tv-onboarding__step-num">3</span>
                    <div>
                        <h2>Bulk import from Loom <small>(optional)</small></h2>
                        <p>Paste Loom share URLs, one per line. Title, description, and thumbnail come from Loom's public oEmbed. Existing posts are skipped.</p>
                    </div>
                </header>

                <textarea name="bulk_import_urls" rows="6" class="large-text code"
                          placeholder="https://www.loom.com/share/abc123...&#10;https://www.loom.com/share/def456..."></textarea>
            </section>

            <!-- Documentation Resource -->
            <section class="tv-onboarding__step">
                <header class="tv-onboarding__step-head">
                    <span class="tv-onboarding__step-num">4</span>
                    <div>
                        <h2>Documentation resource</h2>
                        <p>Optional link to a Google Doc or external resource that appears above the video grid.</p>
                    </div>
                </header>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="resource_title">Resource title</label></th>
                        <td>
                            <input type="text" id="resource_title" name="resource_title" value="<?php echo esc_attr( $resource_title ); ?>" class="regular-text" placeholder="e.g., Module Documentation">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="resource_url">Resource URL</label></th>
                        <td>
                            <input type="url" id="resource_url" name="resource_url" value="<?php echo esc_attr( $resource_url ); ?>" class="large-text" placeholder="https://docs.google.com/document/d/...">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="resource_description">Description</label></th>
                        <td>
                            <input type="text" id="resource_description" name="resource_description" value="<?php echo esc_attr( $resource_description ); ?>" class="large-text" placeholder="e.g., Complete guide to all website modules">
                        </td>
                    </tr>
                </table>
            </section>

            <!-- Advanced -->
            <details class="tv-onboarding__step" style="padding: 0; cursor: default;">
                <summary style="padding: 18px 28px; cursor: pointer; font-weight: 600; font-size: 14px; list-style: revert;">
                    Advanced — override individual surface colors
                </summary>
                <div style="padding: 0 28px 24px;">
                    <p class="description" style="margin: 0 0 16px;">
                        Each derived value can be overridden here. Leave any field <strong>empty</strong> to fall back to the auto-derived value from primary + secondary above.
                    </p>
                    <table class="form-table">
                        <?php foreach ( $brand_fields as $key => $field ) :
                            if ( 'color' !== $field['type'] ) {
                                continue; // Fonts already handled above.
                            }
                            $value = $brand[ $key ];
                            $id    = 'brand_' . $key;
                            ?>
                            <tr>
                                <th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
                                <td>
                                    <input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="#FFBC21" pattern="^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$">
                                    <input type="color" value="<?php echo esc_attr( $value ?: '#000000' ); ?>" onchange="this.previousElementSibling.value=this.value.toUpperCase();" style="vertical-align: middle; margin-left: 8px;">
                                    <p class="description"><?php echo esc_html( $field['help'] ); ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </details>

            <?php submit_button( 'Save Settings' ); ?>
        </form>
    </div>
    <?php
}

// ============================================================================
// META BOXES
// ============================================================================

// Add custom meta box for Loom video URL.
// Priority order across all training_videos meta boxes (1.4.3 — critique fix):
//   high    → Loom Video URL  (the source-of-truth field, fill it first)
//   core    → Loom video info (preview + metadata, populated from URL)
//   default → Description     (auto-fills from Loom on save)
//   low     → Featured Image  (optional thumbnail override)
function add_training_video_meta_box() {
    add_meta_box(
        'training_video_meta_box',
        'Loom Video URL',
        'training_video_meta_box_html',
        'training_videos',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'add_training_video_meta_box' );




// Display custom meta box for Loom video URL
function training_video_meta_box_html( $post ) {
    $loom_video_url = get_post_meta( $post->ID, '_loom_video_url', true );
    wp_nonce_field( 'save_training_video_meta', 'training_video_meta_nonce' );
    ?>
    <p style="margin: 0;">
        <label for="loom_video_url" class="screen-reader-text"><?php _e( 'Loom video URL', 'training-videos' ); ?></label>
        <input type="url"
               id="loom_video_url"
               name="loom_video_url"
               value="<?php echo esc_attr( $loom_video_url ); ?>"
               style="width: 100%; padding: 10px; font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size: 13px;"
               placeholder="https://www.loom.com/share/...">
    </p>
    <p style="margin: 8px 0 0; font-size: 12px; color: #50575e;">
        Paste a Loom <strong>share</strong> or <strong>embed</strong> URL — we save the embed form on save.
        <a href="https://www.loom.com/my-videos" target="_blank" rel="noopener" style="margin-left: 6px;">Open your Loom library &nearr;</a>
    </p>
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
        <label for="video_description"><strong><?php _e( 'Description', 'training-videos' ); ?></strong></label>
        <br>
        <textarea id="video_description" name="video_description" rows="3" style="width: 100%; max-width: 100%;"><?php echo esc_attr( $video_description ); ?></textarea>
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
        'Loom video info',
        'training_videos_loom_data_meta_box_html',
        'training_videos',
        'normal', // Main column — was 'side', moved 1.4.3.
        'core'    // Renders below URL (high) and above Description (default).
    );
}
add_action( 'add_meta_boxes', 'training_videos_add_loom_data_meta_box' );

/**
 * Move the core Featured Image meta box from the sidebar into the main
 * column too. WordPress registers `postimagediv` on `'side'` by default;
 * we remove + re-add it to `'normal'` for the training_videos screen
 * only. The override-help-note is injected via the
 * `admin_post_thumbnail_html` filter below.
 */
function training_videos_relocate_featured_image_metabox() {
    remove_meta_box( 'postimagediv', 'training_videos', 'side' );
    add_meta_box(
        'postimagediv',
        __( 'Featured Image (thumbnail override)', 'training-videos' ),
        'post_thumbnail_meta_box',
        'training_videos',
        'normal',
        'low'
    );
}
add_action( 'add_meta_boxes_training_videos', 'training_videos_relocate_featured_image_metabox' );

/**
 * Inject a single short note inside the Featured Image meta box. The
 * canonical "how this page works" explainer lives at the top of the
 * screen via `edit_form_after_title` — this is just the action-relevant
 * micro-help.
 */
function training_videos_featured_image_help_text( $content, $post_id ) {
    if ( get_post_type( $post_id ) !== 'training_videos' ) {
        return $content;
    }
    $help = '<p style="margin: 0 0 8px 0; font-size: 12px; color: #50575e;">'
          . 'Optional. Replaces the auto-fetched Loom thumbnail. Clear to revert.'
          . '</p>';
    return $help . $content;
}
add_filter( 'admin_post_thumbnail_html', 'training_videos_featured_image_help_text', 10, 2 );

/**
 * Canonical help banner — renders once at the top of the edit screen,
 * above all meta boxes. Single source of truth for the auto-fetch story
 * so the per-box banners stay short and action-focused.
 */
function training_videos_edit_screen_help_banner( $post ) {
    if ( ! $post || get_post_type( $post ) !== 'training_videos' ) {
        return;
    }
    ?>
    <div style="margin: 16px 0 0; padding: 10px 14px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 2px; font-size: 13px; color: #1d2327; line-height: 1.5;">
        <strong>How this page works:</strong> paste a Loom share URL below.
        On save we auto-fetch the title, thumbnail, and the producer-authored description from Loom.
        Edit the description any time to override; set a Featured Image at the bottom to override the thumbnail.
    </div>
    <?php
}
add_action( 'edit_form_after_title', 'training_videos_edit_screen_help_banner' );

function training_videos_loom_data_meta_box_html( $post ) {
    $video_url      = get_post_meta( $post->ID, '_loom_video_url', true );
    $thumb_local    = get_post_meta( $post->ID, '_loom_thumbnail_url', true );
    $thumb_attach   = (int) get_post_meta( $post->ID, '_loom_thumbnail_attachment_id', true );
    $description    = get_post_meta( $post->ID, '_video_description', true );

    if ( ! $video_url ) {
        echo '<p style="margin: 0; color: #50575e;">Paste a Loom share URL above and save. We\'ll fetch the title, description, and thumbnail from Loom and show them here.</p>';
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

    // Resolve the thumbnail URL using the same priority chain the front-end
    // uses (Featured Image → local cache → oEmbed live).
    $thumbnail_url = function_exists( 'training_videos_get_loom_thumbnail_url' )
        ? training_videos_get_loom_thumbnail_url( $video_url, $post->ID )
        : false;

    $resync_url = wp_nonce_url(
        admin_url( 'admin-post.php?action=training_videos_resync&post=' . $post->ID ),
        'training_videos_resync_' . $post->ID
    );

    ?>
    <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
        <!-- Thumbnail preview -->
        <div style="flex: 0 0 280px;">
            <?php if ( $thumbnail_url ) : ?>
                <div style="position: relative; padding-bottom: 56.25%; background: #1d2327; border-radius: 4px; overflow: hidden;">
                    <img src="<?php echo esc_url( $thumbnail_url ); ?>"
                         alt="<?php echo esc_attr( get_the_title( $post ) ); ?>"
                         style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;">
                </div>
                <p style="margin: 6px 0 0 0; font-size: 11px; color: #50575e; text-align: center;">
                    <?php
                    if ( has_post_thumbnail( $post ) ) {
                        echo '<strong>Featured Image override</strong> in use';
                    } elseif ( $thumb_local ) {
                        echo '<strong>Cached locally</strong> · attachment ';
                        if ( $thumb_attach ) {
                            printf( '<a href="%s">#%d</a>', esc_url( get_edit_post_link( $thumb_attach ) ), (int) $thumb_attach );
                        }
                    } else {
                        echo '<strong>Live from Loom oEmbed</strong> (will cache on next save)';
                    }
                    ?>
                </p>
            <?php else : ?>
                <div style="padding-bottom: 56.25%; position: relative; background: #f0f0f1; border: 1px dashed #c3c4c7; border-radius: 4px;">
                    <span style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #8c8f94; font-size: 12px;">No thumbnail yet</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Metadata + actions -->
        <div style="flex: 1; min-width: 240px;">
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <tr>
                    <td style="padding: 4px 12px 4px 0; color: #50575e; vertical-align: top; width: 90px;">Video ID</td>
                    <td style="padding: 4px 0;"><code style="font-size: 11px; background: #f0f0f1; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html( $loom_id ?: '—' ); ?></code></td>
                </tr>
                <?php if ( $oembed ) : ?>
                    <tr>
                        <td style="padding: 4px 12px 4px 0; color: #50575e; vertical-align: top;">Loom title</td>
                        <td style="padding: 4px 0; color: #1d2327;"><?php echo esc_html( $oembed['title'] ?? '—' ); ?></td>
                    </tr>
                    <?php if ( ! empty( $oembed['duration'] ) ) : ?>
                        <tr>
                            <td style="padding: 4px 12px 4px 0; color: #50575e;">Duration</td>
                            <td style="padding: 4px 0;"><?php echo esc_html( gmdate( 'i:s', (int) $oembed['duration'] ) ); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
                <tr>
                    <td style="padding: 4px 12px 4px 0; color: #50575e; vertical-align: top;">Description</td>
                    <td style="padding: 4px 0; color: #1d2327;">
                        <?php if ( trim( (string) $description ) !== '' ) : ?>
                            <span style="color: #2e7d32;">✓ Set</span>
                            <span style="color: #50575e;"> (<?php echo (int) strlen( $description ); ?> chars)</span>
                        <?php else : ?>
                            <span style="color: #b26500;">⚠ Empty</span>
                            <span style="color: #50575e;"> — will auto-fill from Loom on save</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <hr style="margin: 14px 0; border: none; border-top: 1px solid #e1e1e1;">

            <p style="margin: 0 0 10px 0; font-size: 12px; color: #50575e; line-height: 1.5;">
                Pull a fresh title, description, and thumbnail from Loom. Manual edits to this post's description are preserved unless you clear it first.
            </p>

            <p style="margin: 0;">
                <a href="<?php echo esc_url( $resync_url ); ?>" class="button button-secondary">
                    <span aria-hidden="true">↻</span> Re-sync from Loom
                </a>
            </p>
        </div>
    </div>
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
 * admin-post handler — combined re-sync (description + thumbnail).
 * 1.4.3 critique fix: collapses the two-button refresh UX into one
 * "Re-sync from Loom" action. Internally fires both handlers and
 * reports a combined status message.
 */
function training_videos_handle_resync() {
    $post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( 'Permission denied.' );
    }
    check_admin_referer( 'training_videos_resync_' . $post_id );

    $description = training_videos_refresh_description_from_loom( $post_id );
    $thumb_url   = training_videos_sideload_loom_thumbnail( $post_id, true );

    $parts = array();
    $parts[] = ( false !== $description ) ? 'description ✓' : 'description —';
    $parts[] = ( $thumb_url ) ? 'thumbnail ✓' : 'thumbnail —';
    $msg = 'Re-synced from Loom: ' . implode( ', ', $parts );

    $any_failed = ( false === $description ) || ! $thumb_url;

    $args = array(
        'post'        => $post_id,
        'action'      => 'edit',
        'tv_loom_msg' => $msg,
    );
    if ( $any_failed ) {
        $args['tv_loom_err'] = 1;
    }
    wp_safe_redirect( add_query_arg( $args, admin_url( 'post.php' ) ) );
    exit;
}
add_action( 'admin_post_training_videos_resync', 'training_videos_handle_resync' );

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








