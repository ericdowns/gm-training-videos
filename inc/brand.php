<?php
/**
 * Brand theming — per-site CSS custom property overrides.
 *
 * The plugin's CSS file defines :root { --tv-color-* } tokens with the
 * California Forever defaults baked in. This module reads per-site overrides
 * from wp_options and emits an inline <style> block that overrides those
 * tokens on plugin pages only. Empty values fall back to the CSS defaults.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brand setting keys, their option names, and CSS custom-property names.
 *
 * Editing this map adds new themable surfaces.
 */
function training_videos_brand_fields() {
	return array(
		'bg' => array(
			'option' => 'training_videos_brand_bg',
			'css'    => '--tv-color-beige',
			'label'  => 'Page background',
			'type'   => 'color',
			'help'   => 'Hex e.g. #FDF9E3 (beige) or #FFFFFF',
		),
		'heading' => array(
			'option' => 'training_videos_brand_heading',
			'css'    => '--tv-color-navy',
			'label'  => 'Heading + header background',
			'type'   => 'color',
			'help'   => 'Used for page title, sticky header, sidebar header',
		),
		'text' => array(
			'option' => 'training_videos_brand_text',
			'css'    => '--tv-color-stone-blue',
			'label'  => 'Body text',
			'type'   => 'color',
			'help'   => 'Paragraph + secondary text color',
		),
		'accent' => array(
			'option' => 'training_videos_brand_accent',
			'css'    => '--tv-color-orange',
			'label'  => 'Accent (CTAs, play button)',
			'type'   => 'color',
			'help'   => 'Used for the play button, active states, badges',
		),
		'accent_alt' => array(
			'option' => 'training_videos_brand_accent_alt',
			'css'    => '--tv-color-brick',
			'label'  => 'Accent hover',
			'type'   => 'color',
			'help'   => 'Hover state for accent — usually a darker tone',
		),
		'border' => array(
			'option' => 'training_videos_brand_border',
			'css'    => '--tv-color-linen',
			'label'  => 'Borders + soft surfaces',
			'type'   => 'color',
			'help'   => 'Card borders, dividers, sidebar item bg',
		),
		'card_bg' => array(
			'option' => 'training_videos_brand_card_bg',
			'css'    => '--tv-color-white',
			'label'  => 'Card background',
			'type'   => 'color',
			'help'   => 'Video tile + sidebar background',
		),
		'heading_font' => array(
			'option' => 'training_videos_brand_heading_font',
			'css'    => null, // Applied via heading selector, not custom property
			'label'  => 'Heading font family',
			'type'   => 'font',
			'help'   => 'CSS font-family value, e.g. "Playfair Display", serif',
		),
		'body_font' => array(
			'option' => 'training_videos_brand_body_font',
			'css'    => null,
			'label'  => 'Body font family',
			'type'   => 'font',
			'help'   => 'CSS font-family value, e.g. "Inter", sans-serif',
		),
		'font_url' => array(
			'option' => 'training_videos_brand_font_url',
			'css'    => null,
			'label'  => 'Font import URL',
			'type'   => 'url',
			'help'   => 'Google Fonts URL or other CSS @import — added to <head> on plugin pages',
		),
	);
}

/**
 * Read all brand values from options.
 *
 * @return array Map of field key → saved value (string, possibly empty)
 */
function training_videos_get_brand() {
	$values = array();
	foreach ( training_videos_brand_fields() as $key => $field ) {
		$values[ $key ] = trim( (string) get_option( $field['option'], '' ) );
	}
	return $values;
}

/**
 * Sanitize a hex color (#RRGGBB or #RGB). Returns empty string if invalid.
 */
function training_videos_sanitize_hex_color( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( preg_match( '/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $value ) ) {
		return strtoupper( $value );
	}
	return '';
}

/**
 * Sanitize a font-family string. Strips anything dangerous, keeps quoted family
 * names + common fallback keywords.
 */
function training_videos_sanitize_font_family( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	// Allow letters, numbers, spaces, commas, hyphens, single/double quotes.
	$value = preg_replace( '/[^a-zA-Z0-9 ,\'\"-]/', '', $value );
	return $value;
}

/**
 * Output the brand's <style> + <link> block. Called from training-header.php
 * inside <head>.
 */
function training_videos_render_brand_styles() {
	$brand  = training_videos_get_brand();
	$fields = training_videos_brand_fields();

	// Font URL — output a <link> for Google Fonts or any CSS sheet.
	if ( ! empty( $brand['font_url'] ) ) {
		printf(
			'<link rel="stylesheet" href="%s" />' . "\n",
			esc_url( $brand['font_url'] )
		);
	}

	// Build CSS custom property overrides.
	$root_lines = array();
	foreach ( $fields as $key => $field ) {
		if ( null === $field['css'] || empty( $brand[ $key ] ) ) {
			continue;
		}
		// Only color fields land in :root vars.
		if ( 'color' !== $field['type'] ) {
			continue;
		}
		$root_lines[] = sprintf( "  %s: %s;", $field['css'], $brand[ $key ] );
	}

	$heading_font = training_videos_sanitize_font_family( $brand['heading_font'] );
	$body_font    = training_videos_sanitize_font_family( $brand['body_font'] );

	if ( empty( $root_lines ) && empty( $heading_font ) && empty( $body_font ) ) {
		return; // Nothing to override — let the CSS defaults stand.
	}

	echo "<style id=\"tv-brand-overrides\">\n";

	if ( ! empty( $root_lines ) ) {
		echo "body.post-type-archive-training_videos,\nbody.single-training_videos {\n";
		echo implode( "\n", $root_lines ) . "\n";
		echo "}\n";
	}

	if ( ! empty( $body_font ) ) {
		echo "body.post-type-archive-training_videos,\nbody.single-training_videos {\n";
		echo "  font-family: " . esc_attr( $body_font ) . ";\n";
		echo "}\n";
	}

	if ( ! empty( $heading_font ) ) {
		echo "body.post-type-archive-training_videos h1,\n";
		echo "body.post-type-archive-training_videos h2,\n";
		echo "body.post-type-archive-training_videos h3,\n";
		echo "body.single-training_videos h1,\n";
		echo "body.single-training_videos h2,\n";
		echo "body.single-training_videos h3 {\n";
		echo "  font-family: " . esc_attr( $heading_font ) . ";\n";
		echo "}\n";
	}

	echo "</style>\n";
}
