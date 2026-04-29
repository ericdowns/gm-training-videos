<?php
/**
 * Brand palette derivation.
 *
 * Given a primary + secondary hex, return a 7-key palette covering every
 * surface in `training_videos_brand_fields()`. The wizard saves these
 * derived values into the same wp_options that the existing
 * `training_videos_render_brand_styles()` reads, so no runtime change is
 * needed downstream — derived palettes flow through the existing CSS
 * custom-property overlay.
 *
 * Algorithm intent (per plan):
 *  - Header/heading bg: primary as-is
 *  - Accent: secondary as-is
 *  - Page bg + border: secondary hue, low saturation, very light → warm tint
 *  - Body text: primary hue, medium saturation, dark → desaturated brand
 *  - Accent hover: secondary at L − 12
 *  - Card bg: pure white
 *
 * Sanity-checked against the CalForever defaults: feeding #112D40 + #FFBC21
 * produces a palette whose body text matches stone-blue near-exactly and
 * whose other surfaces fall in the same warm/cool family.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert #RRGGBB or #RGB to [r,g,b] 0-255 ints. Returns null on parse fail.
 */
function training_videos_hex_to_rgb( $hex ) {
	$hex = trim( (string) $hex );
	if ( '' === $hex || $hex[0] !== '#' ) {
		return null;
	}
	$hex = substr( $hex, 1 );
	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( ! preg_match( '/^[a-fA-F0-9]{6}$/', $hex ) ) {
		return null;
	}
	return array(
		hexdec( substr( $hex, 0, 2 ) ),
		hexdec( substr( $hex, 2, 2 ) ),
		hexdec( substr( $hex, 4, 2 ) ),
	);
}

function training_videos_rgb_to_hex( $r, $g, $b ) {
	$clamp = function( $n ) {
		return max( 0, min( 255, (int) round( $n ) ) );
	};
	return strtoupper( sprintf( '#%02X%02X%02X', $clamp( $r ), $clamp( $g ), $clamp( $b ) ) );
}

/**
 * RGB (0-255) → HSL (h: 0-360, s/l: 0-100).
 */
function training_videos_rgb_to_hsl( $r, $g, $b ) {
	$r /= 255; $g /= 255; $b /= 255;
	$max = max( $r, $g, $b );
	$min = min( $r, $g, $b );
	$l = ( $max + $min ) / 2;
	$d = $max - $min;

	if ( $d == 0 ) {
		$h = 0; $s = 0;
	} else {
		$s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
		switch ( $max ) {
			case $r: $h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 ); break;
			case $g: $h = ( $b - $r ) / $d + 2; break;
			default: $h = ( $r - $g ) / $d + 4; break;
		}
		$h *= 60;
	}
	return array( $h, $s * 100, $l * 100 );
}

/**
 * HSL → RGB. h: 0-360, s/l: 0-100. Returns [r,g,b] 0-255.
 */
function training_videos_hsl_to_rgb( $h, $s, $l ) {
	$h = fmod( fmod( $h, 360 ) + 360, 360 ) / 360;
	$s = max( 0, min( 100, $s ) ) / 100;
	$l = max( 0, min( 100, $l ) ) / 100;

	if ( $s == 0 ) {
		$r = $g = $b = $l;
	} else {
		$hue2rgb = function( $p, $q, $t ) {
			if ( $t < 0 ) $t += 1;
			if ( $t > 1 ) $t -= 1;
			if ( $t < 1/6 ) return $p + ( $q - $p ) * 6 * $t;
			if ( $t < 1/2 ) return $q;
			if ( $t < 2/3 ) return $p + ( $q - $p ) * ( 2/3 - $t ) * 6;
			return $p;
		};
		$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
		$p = 2 * $l - $q;
		$r = $hue2rgb( $p, $q, $h + 1/3 );
		$g = $hue2rgb( $p, $q, $h );
		$b = $hue2rgb( $p, $q, $h - 1/3 );
	}
	return array( $r * 255, $g * 255, $b * 255 );
}

function training_videos_hex_to_hsl( $hex ) {
	$rgb = training_videos_hex_to_rgb( $hex );
	if ( ! $rgb ) return null;
	return training_videos_rgb_to_hsl( $rgb[0], $rgb[1], $rgb[2] );
}

function training_videos_hsl_to_hex( $h, $s, $l ) {
	$rgb = training_videos_hsl_to_rgb( $h, $s, $l );
	return training_videos_rgb_to_hex( $rgb[0], $rgb[1], $rgb[2] );
}

/**
 * WCAG relative luminance (0-1) for an RGB triple.
 */
function training_videos_relative_luminance( $r, $g, $b ) {
	$lin = function( $c ) {
		$c = $c / 255;
		return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	};
	return 0.2126 * $lin( $r ) + 0.7152 * $lin( $g ) + 0.0722 * $lin( $b );
}

function training_videos_contrast_ratio( $hex_a, $hex_b ) {
	$a = training_videos_hex_to_rgb( $hex_a );
	$b = training_videos_hex_to_rgb( $hex_b );
	if ( ! $a || ! $b ) return 0;
	$la = training_videos_relative_luminance( $a[0], $a[1], $a[2] );
	$lb = training_videos_relative_luminance( $b[0], $b[1], $b[2] );
	$lighter = max( $la, $lb );
	$darker  = min( $la, $lb );
	return ( $lighter + 0.05 ) / ( $darker + 0.05 );
}

/**
 * Derive a 7-color palette from primary + secondary hex.
 *
 * Returns an associative array keyed by `training_videos_brand_fields()`
 * keys (bg, heading, text, accent, accent_alt, border, card_bg). Returns
 * null if either input is invalid.
 */
function training_videos_derive_palette( $primary_hex, $secondary_hex ) {
	$primary   = training_videos_hex_to_hsl( $primary_hex );
	$secondary = training_videos_hex_to_hsl( $secondary_hex );
	if ( ! $primary || ! $secondary ) {
		return null;
	}

	list( $ph, $ps, $pl ) = $primary;
	list( $sh, $ss, $sl ) = $secondary;

	// Page background: secondary hue, low saturation, very light. Cap S at
	// 30% so highly-saturated brand colors don't produce gaudy backgrounds.
	$bg_s = min( 30, $ss * 0.35 );
	$bg   = training_videos_hsl_to_hex( $sh, $bg_s, 95 );

	// Border: same hue as bg, slightly more saturated, mid-light. Sits
	// between page bg and card_bg in luminance.
	$border_s = min( 25, $ss * 0.30 );
	$border   = training_videos_hsl_to_hex( $sh, $border_s, 88 );

	// Heading / header bg: primary, untouched.
	$heading = strtoupper( $primary_hex );

	// Body text: primary hue, mid saturation, dark. Cap S at 30% so we
	// don't render headings-as-text-with-too-much-personality.
	$text_s = min( 30, $ps * 0.5 );
	$text   = training_videos_hsl_to_hex( $ph, $text_s, 30 );

	// Contrast guard: if derived text doesn't clear AA (4.5:1) against bg,
	// fall back to a near-black charcoal that always passes.
	if ( training_videos_contrast_ratio( $text, $bg ) < 4.5 ) {
		$text = '#1A1A1A';
	}

	// Accent: secondary, untouched.
	$accent = strtoupper( $secondary_hex );

	// Accent hover: secondary darkened 12% L, same hue/S. Clamp to L>=10
	// so we never end up at pure black.
	$accent_alt = training_videos_hsl_to_hex( $sh, $ss, max( 10, $sl - 12 ) );

	// Card background: white. Always. Guarantees cards stand out from
	// any page bg and any border tint without further math.
	$card_bg = '#FFFFFF';

	return array(
		'bg'         => $bg,
		'heading'    => $heading,
		'text'       => $text,
		'accent'     => $accent,
		'accent_alt' => $accent_alt,
		'border'     => $border,
		'card_bg'    => $card_bg,
	);
}
