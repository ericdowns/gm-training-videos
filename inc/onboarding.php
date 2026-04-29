<?php
/**
 * Onboarding wizard.
 *
 * Single-page admin screen at Training Videos → Onboarding. Three
 * sections stacked vertically:
 *   1. Brand Colors — primary + secondary inputs, live preview swatches.
 *   2. Fonts — auto-detected from parent theme, user can override.
 *   3. Bulk Import (optional) — paste Loom share URLs.
 *
 * On submit, the wizard:
 *   - derives the 7-surface palette from primary/secondary
 *   - writes derived values + chosen fonts to wp_options (the same
 *     options the existing Settings page reads)
 *   - optionally runs bulk-import on the textarea
 *   - sets training_videos_onboarding_completed = true
 *   - redirects to the training_videos archive in admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TRAINING_VIDEOS_ONBOARDING_FLAG     = 'training_videos_onboarding_completed';
const TRAINING_VIDEOS_JUST_ACTIVATED_FLAG = 'training_videos_just_activated';

/**
 * Register the Onboarding submenu under Training Videos.
 */
function training_videos_register_onboarding_page() {
	add_submenu_page(
		'edit.php?post_type=training_videos',
		'Training Videos Onboarding',
		'Onboarding',
		'manage_options',
		'training-videos-onboarding',
		'training_videos_onboarding_page_html'
	);
}
add_action( 'admin_menu', 'training_videos_register_onboarding_page' );

/**
 * Activation hook — set the not-completed flag if missing, and stash a
 * one-shot transient so the next admin pageload bounces to the wizard.
 */
function training_videos_on_activate() {
	if ( null === get_option( TRAINING_VIDEOS_ONBOARDING_FLAG, null ) ) {
		add_option( TRAINING_VIDEOS_ONBOARDING_FLAG, false );
		set_transient( TRAINING_VIDEOS_JUST_ACTIVATED_FLAG, true, 30 );
	}
}

/**
 * If the just-activated transient is set, redirect to the wizard once.
 */
function training_videos_maybe_redirect_to_onboarding() {
	if ( ! get_transient( TRAINING_VIDEOS_JUST_ACTIVATED_FLAG ) ) {
		return;
	}
	delete_transient( TRAINING_VIDEOS_JUST_ACTIVATED_FLAG );
	if ( isset( $_GET['activate-multi'] ) ) {
		return; // Don't break bulk activation flows.
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=training_videos&page=training-videos-onboarding' ) );
	exit;
}
add_action( 'admin_init', 'training_videos_maybe_redirect_to_onboarding' );

/**
 * Admin notice: shown on every admin page until the wizard is completed.
 */
function training_videos_onboarding_admin_notice() {
	if ( get_option( TRAINING_VIDEOS_ONBOARDING_FLAG, false ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	// Don't double up on the wizard page itself.
	if ( $screen && isset( $screen->id ) && false !== strpos( $screen->id, 'training-videos-onboarding' ) ) {
		return;
	}
	$url = admin_url( 'edit.php?post_type=training_videos&page=training-videos-onboarding' );
	?>
	<div class="notice notice-info">
		<p>
			<strong>Training Videos:</strong> finish the
			<a href="<?php echo esc_url( $url ); ?>">setup wizard</a> to brand the library and import your videos.
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'training_videos_onboarding_admin_notice' );

/**
 * Enqueue the wizard's CSS + JS — only on the wizard page.
 */
function training_videos_enqueue_onboarding_assets( $hook ) {
	if ( 'training_videos_page_training-videos-onboarding' !== $hook
		&& 'training-videos_page_training-videos-onboarding' !== $hook ) {
		return;
	}

	$plugin_url = defined( 'TRAINING_VIDEOS_PLUGIN_URL' ) ? TRAINING_VIDEOS_PLUGIN_URL : plugin_dir_url( __FILE__ );
	$version    = '1.4.0';

	wp_enqueue_style(
		'training-videos-onboarding',
		$plugin_url . 'assets/admin-onboarding.css',
		array(),
		$version
	);
	wp_enqueue_script(
		'training-videos-onboarding',
		$plugin_url . 'assets/admin-onboarding.js',
		array(),
		$version,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'training_videos_enqueue_onboarding_assets' );

/**
 * Render the wizard.
 */
function training_videos_onboarding_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$result_message = '';

	// Form submit.
	if ( isset( $_POST['training_videos_onboarding_nonce'] )
		&& wp_verify_nonce( $_POST['training_videos_onboarding_nonce'], 'training_videos_onboarding' ) ) {

		$primary   = training_videos_sanitize_hex_color( $_POST['brand_primary'] ?? '' );
		$secondary = training_videos_sanitize_hex_color( $_POST['brand_secondary'] ?? '' );

		if ( $primary && $secondary ) {
			$palette = training_videos_derive_palette( $primary, $secondary );
			if ( $palette ) {
				// Store the source colors so re-edits can recompute.
				update_option( 'training_videos_brand_primary',   $primary );
				update_option( 'training_videos_brand_secondary', $secondary );

				// Map palette → existing brand_fields options.
				foreach ( training_videos_brand_fields() as $key => $field ) {
					if ( 'color' === $field['type'] && isset( $palette[ $key ] ) ) {
						update_option( $field['option'], $palette[ $key ] );
					}
				}
			}
		}

		// Fonts.
		$heading_family = training_videos_sanitize_font_family( $_POST['brand_heading_font'] ?? '' );
		$body_family    = training_videos_sanitize_font_family( $_POST['brand_body_font'] ?? '' );
		$google_url     = esc_url_raw( $_POST['brand_font_url'] ?? '' );
		update_option( 'training_videos_brand_heading_font', $heading_family );
		update_option( 'training_videos_brand_body_font',    $body_family );
		update_option( 'training_videos_brand_font_url',     $google_url );

		// Bulk import (optional).
		$bulk = trim( (string) ( $_POST['bulk_import_urls'] ?? '' ) );
		$import_result = null;
		if ( '' !== $bulk ) {
			$import_result = training_videos_bulk_import( $bulk );
		}

		update_option( TRAINING_VIDEOS_ONBOARDING_FLAG, true );

		$result_message = training_videos_onboarding_render_result( $import_result );
	}

	// Pre-fill state.
	$primary    = (string) get_option( 'training_videos_brand_primary',   '' );
	$secondary  = (string) get_option( 'training_videos_brand_secondary', '' );

	// Auto-detect fonts if not previously saved.
	$detected = training_videos_detect_theme_fonts();
	$heading_family = (string) get_option( 'training_videos_brand_heading_font', '' );
	$body_family    = (string) get_option( 'training_videos_brand_body_font',    '' );
	$font_url       = (string) get_option( 'training_videos_brand_font_url',     '' );
	if ( '' === $heading_family && '' !== $detected['heading_family'] ) {
		$heading_family = $detected['heading_family'];
	}
	if ( '' === $body_family && '' !== $detected['body_family'] ) {
		$body_family = $detected['body_family'];
	}
	if ( '' === $font_url && '' !== $detected['google_url'] ) {
		$font_url = $detected['google_url'];
	}

	// Default colors prefill: navy + orange so the wizard isn't visually
	// empty on first load.
	if ( '' === $primary ) {
		$primary = '#112D40';
	}
	if ( '' === $secondary ) {
		$secondary = '#FFBC21';
	}

	$archive_url = admin_url( 'edit.php?post_type=training_videos' );
	?>
	<div class="wrap tv-onboarding">
		<h1>Training Videos · Setup</h1>
		<p class="tv-onboarding__intro">
			Three quick steps to brand the library to your client. You can re-run this any time, or tweak individual values under <strong>Settings → Advanced</strong> later.
		</p>

		<?php echo $result_message; // Already wrapped in safe HTML. ?>

		<form method="post" class="tv-onboarding__form">
			<?php wp_nonce_field( 'training_videos_onboarding', 'training_videos_onboarding_nonce' ); ?>

			<!-- ============================================================ -->
			<!-- Step 1 — Brand Colors                                          -->
			<!-- ============================================================ -->
			<section class="tv-onboarding__step">
				<header class="tv-onboarding__step-head">
					<span class="tv-onboarding__step-num">1</span>
					<div>
						<h2>Brand colors</h2>
						<p>Two colors. We derive the rest.</p>
					</div>
				</header>

				<div class="tv-onboarding__color-grid">
					<label class="tv-onboarding__color-input">
						<span>Primary <small>(headers, dark surfaces)</small></span>
						<div class="tv-onboarding__color-row">
							<input type="color"
							       id="tv-color-primary-picker"
							       value="<?php echo esc_attr( $primary ); ?>"
							       data-tv-color-target="brand_primary">
							<input type="text"
							       id="tv-color-primary"
							       name="brand_primary"
							       value="<?php echo esc_attr( $primary ); ?>"
							       pattern="^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$"
							       placeholder="#112D40"
							       data-tv-color-source="primary">
						</div>
					</label>

					<label class="tv-onboarding__color-input">
						<span>Secondary <small>(CTAs, accents)</small></span>
						<div class="tv-onboarding__color-row">
							<input type="color"
							       id="tv-color-secondary-picker"
							       value="<?php echo esc_attr( $secondary ); ?>"
							       data-tv-color-target="brand_secondary">
							<input type="text"
							       id="tv-color-secondary"
							       name="brand_secondary"
							       value="<?php echo esc_attr( $secondary ); ?>"
							       pattern="^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$"
							       placeholder="#FFBC21"
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

			<!-- ============================================================ -->
			<!-- Step 2 — Fonts                                                 -->
			<!-- ============================================================ -->
			<section class="tv-onboarding__step">
				<header class="tv-onboarding__step-head">
					<span class="tv-onboarding__step-num">2</span>
					<div>
						<h2>Fonts</h2>
						<p>
							<?php if ( '' !== $detected['body_family'] || '' !== $detected['heading_family'] ) : ?>
								Detected from your active theme — edit if needed.
							<?php else : ?>
								We couldn't auto-detect fonts. Leave blank to use the system stack, or paste values manually.
							<?php endif; ?>
						</p>
					</div>
				</header>

				<table class="form-table">
					<tr>
						<th scope="row"><label for="brand_heading_font">Heading family</label></th>
						<td>
							<input type="text" id="brand_heading_font" name="brand_heading_font"
							       value="<?php echo esc_attr( $heading_family ); ?>"
							       class="regular-text"
							       placeholder='"Playfair Display", serif'>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="brand_body_font">Body family</label></th>
						<td>
							<input type="text" id="brand_body_font" name="brand_body_font"
							       value="<?php echo esc_attr( $body_family ); ?>"
							       class="regular-text"
							       placeholder='"Inter", sans-serif'>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="brand_font_url">Font URL <small>(optional)</small></label></th>
						<td>
							<input type="url" id="brand_font_url" name="brand_font_url"
							       value="<?php echo esc_attr( $font_url ); ?>"
							       class="large-text"
							       placeholder="https://fonts.googleapis.com/css2?family=Inter">
							<p class="description">If your theme already loads these fonts, you can leave this blank.</p>
						</td>
					</tr>
				</table>
			</section>

			<!-- ============================================================ -->
			<!-- Step 3 — Bulk import (optional)                                -->
			<!-- ============================================================ -->
			<section class="tv-onboarding__step">
				<header class="tv-onboarding__step-head">
					<span class="tv-onboarding__step-num">3</span>
					<div>
						<h2>Import your Loom videos <small>(optional)</small></h2>
						<p>Paste Loom share URLs, one per line. We'll create a training video for each, in the order listed.</p>
					</div>
				</header>

				<textarea name="bulk_import_urls" rows="8" class="large-text code"
				          placeholder="https://www.loom.com/share/abc123...&#10;https://www.loom.com/share/def456..."></textarea>
				<p class="description">
					Title, description, and thumbnail are pulled automatically from Loom's public oEmbed.
					Existing posts with the same URL are skipped (idempotent).
				</p>
			</section>

			<p class="submit">
				<button type="submit" class="button button-primary button-hero">Save &amp; Finish</button>
				<a href="<?php echo esc_url( $archive_url ); ?>" class="button button-secondary" style="margin-left: 8px;">Skip for now</a>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Format the post-submit success/result block.
 */
function training_videos_onboarding_render_result( $import_result ) {
	ob_start();
	?>
	<div class="notice notice-success">
		<p><strong>Setup saved.</strong> Brand palette, fonts, and any imported videos are live.</p>
		<?php if ( is_array( $import_result ) ) : ?>
			<p>
				Imported: <strong><?php echo count( $import_result['created'] ); ?></strong> ·
				Skipped (duplicates): <strong><?php echo count( $import_result['skipped'] ); ?></strong> ·
				Failed: <strong><?php echo count( $import_result['failed'] ); ?></strong>
			</p>
			<?php if ( ! empty( $import_result['failed'] ) ) : ?>
				<ul style="margin-left: 20px; list-style: disc;">
					<?php foreach ( $import_result['failed'] as $row ) : ?>
						<li><code><?php echo esc_html( $row['url'] ); ?></code> — <?php echo esc_html( $row['reason'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
