<?php
/**
 * Parent-theme font detection.
 *
 * Two paths:
 *   1. Block themes — `wp_get_global_settings(['typography','fontFamilies'])`
 *      returns whatever `theme.json` declares. This is authoritative for
 *      modern block themes.
 *   2. Classic + block — scan `wp_styles()->registered` for Google Fonts
 *      URLs and parse `family` query params. Catches the ~80% of installs
 *      that load Google Fonts via wp_enqueue_style.
 *
 * Falls back to empty strings if nothing detected — the plugin's CSS
 * `--tv-font` system stack stays in effect downstream.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array{heading_family:string,body_family:string,google_url:string}
 */
function training_videos_detect_theme_fonts() {
	$result = array(
		'heading_family' => '',
		'body_family'    => '',
		'google_url'     => '',
	);

	// Block-theme path — theme.json typography.
	if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
		$settings = function_exists( 'wp_get_global_settings' ) ? wp_get_global_settings() : array();
		$families = isset( $settings['typography']['fontFamilies']['theme'] )
			? $settings['typography']['fontFamilies']['theme']
			: array();

		if ( ! empty( $families ) && is_array( $families ) ) {
			// theme.json typically lists body first, headings second when
			// the theme bothers to differentiate. If only one is declared,
			// use it for both.
			$body_entry    = $families[0];
			$heading_entry = isset( $families[1] ) ? $families[1] : $families[0];

			$result['body_family']    = isset( $body_entry['fontFamily'] )    ? $body_entry['fontFamily']    : '';
			$result['heading_family'] = isset( $heading_entry['fontFamily'] ) ? $heading_entry['fontFamily'] : '';
		}
	}

	// Google Fonts URL scan — works for both classic + block themes that
	// enqueue fonts.googleapis.com. If theme.json already gave us
	// families, we still want the Google URL so we can re-load the same
	// fonts inside the plugin's pages.
	$google_url = training_videos_find_google_fonts_url();
	if ( $google_url ) {
		$result['google_url'] = $google_url;

		// If we didn't get families from theme.json, parse them from the URL.
		if ( '' === $result['body_family'] || '' === $result['heading_family'] ) {
			$families = training_videos_parse_google_fonts_url_families( $google_url );
			if ( ! empty( $families ) ) {
				if ( '' === $result['body_family'] ) {
					$result['body_family'] = $families[0];
				}
				if ( '' === $result['heading_family'] ) {
					$result['heading_family'] = isset( $families[1] ) ? $families[1] : $families[0];
				}
			}
		}
	}

	return $result;
}

/**
 * Find the first registered Google Fonts URL on the site, if any.
 */
function training_videos_find_google_fonts_url() {
	if ( ! function_exists( 'wp_styles' ) ) {
		return '';
	}
	$styles = wp_styles();
	if ( ! $styles || empty( $styles->registered ) ) {
		return '';
	}

	foreach ( $styles->registered as $style ) {
		$src = isset( $style->src ) ? (string) $style->src : '';
		if ( '' === $src ) {
			continue;
		}
		if ( false !== strpos( $src, 'fonts.googleapis.com/css' ) ) {
			return $src;
		}
	}
	return '';
}

/**
 * Parse the `family` (or repeated `family`) parameters out of a Google
 * Fonts URL. Handles both v1 (?family=Inter|Playfair+Display) and v2
 * (?family=Inter&family=Playfair+Display:wght@400;700). Returns an
 * ordered list of family names with `+` decoded to space and weight/axis
 * suffixes stripped.
 */
function training_videos_parse_google_fonts_url_families( $url ) {
	$query_string = parse_url( $url, PHP_URL_QUERY );
	if ( ! $query_string ) {
		return array();
	}

	$families = array();

	// v2 form — `family=` may repeat. Split on `&` and pull every
	// `family=...` segment in order.
	foreach ( explode( '&', $query_string ) as $pair ) {
		if ( 0 !== strpos( $pair, 'family=' ) ) {
			continue;
		}
		$value = substr( $pair, strlen( 'family=' ) );
		$value = urldecode( $value );

		// v1 form: pipe-separated list inside one family= param.
		foreach ( explode( '|', $value ) as $entry ) {
			$entry = trim( $entry );
			if ( '' === $entry ) {
				continue;
			}
			// Strip weight/axis suffix: `Inter:wght@400;700` → `Inter`.
			$colon = strpos( $entry, ':' );
			if ( false !== $colon ) {
				$entry = substr( $entry, 0, $colon );
			}
			$entry = str_replace( '+', ' ', $entry );
			if ( '' !== $entry ) {
				$families[] = $entry;
			}
		}
	}

	return array_values( array_unique( $families ) );
}
