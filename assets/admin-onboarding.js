/**
 * Onboarding wizard — live preview.
 *
 * Mirrors the HSL math in inc/brand-derive.php so the swatches + mini
 * preview update as the user types. The PHP function is the source of
 * truth on submit; this is purely for "what will it look like" feedback.
 */
(function () {
	'use strict';

	function hexToRgb(hex) {
		if (typeof hex !== 'string' || hex.charAt(0) !== '#') return null;
		hex = hex.slice(1);
		if (hex.length === 3) hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
		if (!/^[a-fA-F0-9]{6}$/.test(hex)) return null;
		return [parseInt(hex.slice(0, 2), 16), parseInt(hex.slice(2, 4), 16), parseInt(hex.slice(4, 6), 16)];
	}

	function rgbToHex(r, g, b) {
		var clamp = function (n) { return Math.max(0, Math.min(255, Math.round(n))); };
		return '#' + [clamp(r), clamp(g), clamp(b)]
			.map(function (n) { return n.toString(16).padStart(2, '0'); })
			.join('').toUpperCase();
	}

	function rgbToHsl(r, g, b) {
		r /= 255; g /= 255; b /= 255;
		var max = Math.max(r, g, b), min = Math.min(r, g, b);
		var h, s, l = (max + min) / 2, d = max - min;
		if (d === 0) { h = 0; s = 0; }
		else {
			s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
			switch (max) {
				case r: h = (g - b) / d + (g < b ? 6 : 0); break;
				case g: h = (b - r) / d + 2; break;
				default: h = (r - g) / d + 4;
			}
			h *= 60;
		}
		return [h, s * 100, l * 100];
	}

	function hslToRgb(h, s, l) {
		h = ((h % 360) + 360) % 360 / 360;
		s = Math.max(0, Math.min(100, s)) / 100;
		l = Math.max(0, Math.min(100, l)) / 100;
		if (s === 0) return [l * 255, l * 255, l * 255];
		var hue2rgb = function (p, q, t) {
			if (t < 0) t += 1;
			if (t > 1) t -= 1;
			if (t < 1 / 6) return p + (q - p) * 6 * t;
			if (t < 1 / 2) return q;
			if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
			return p;
		};
		var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
		var p = 2 * l - q;
		return [hue2rgb(p, q, h + 1 / 3) * 255, hue2rgb(p, q, h) * 255, hue2rgb(p, q, h - 1 / 3) * 255];
	}

	function hexToHsl(hex) {
		var rgb = hexToRgb(hex);
		if (!rgb) return null;
		return rgbToHsl(rgb[0], rgb[1], rgb[2]);
	}

	function hslToHex(h, s, l) {
		var rgb = hslToRgb(h, s, l);
		return rgbToHex(rgb[0], rgb[1], rgb[2]);
	}

	function relLum(r, g, b) {
		var lin = function (c) {
			c = c / 255;
			return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
		};
		return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
	}

	function contrast(hexA, hexB) {
		var a = hexToRgb(hexA), b = hexToRgb(hexB);
		if (!a || !b) return 0;
		var la = relLum(a[0], a[1], a[2]), lb = relLum(b[0], b[1], b[2]);
		var lighter = Math.max(la, lb), darker = Math.min(la, lb);
		return (lighter + 0.05) / (darker + 0.05);
	}

	/**
	 * Mirror of training_videos_derive_palette() in inc/brand-derive.php.
	 */
	function derivePalette(primaryHex, secondaryHex) {
		var primary = hexToHsl(primaryHex);
		var secondary = hexToHsl(secondaryHex);
		if (!primary || !secondary) return null;

		var ph = primary[0], ps = primary[1];
		var sh = secondary[0], ss = secondary[1], sl = secondary[2];

		var bg_s = Math.min(30, ss * 0.35);
		var bg = hslToHex(sh, bg_s, 95);

		var border_s = Math.min(25, ss * 0.30);
		var border = hslToHex(sh, border_s, 88);

		var heading = primaryHex.toUpperCase();

		var text_s = Math.min(30, ps * 0.5);
		var text = hslToHex(ph, text_s, 30);
		if (contrast(text, bg) < 4.5) text = '#1A1A1A';

		var accent = secondaryHex.toUpperCase();
		var accent_alt = hslToHex(sh, ss, Math.max(10, sl - 12));

		return {
			bg: bg,
			heading: heading,
			text: text,
			accent: accent,
			accent_alt: accent_alt,
			border: border,
			card_bg: '#FFFFFF'
		};
	}

	function updatePreview() {
		var primaryEl = document.getElementById('tv-color-primary');
		var secondaryEl = document.getElementById('tv-color-secondary');
		if (!primaryEl || !secondaryEl) return;

		var palette = derivePalette(primaryEl.value, secondaryEl.value);
		if (!palette) return;

		// Update each swatch.
		Object.keys(palette).forEach(function (key) {
			var el = document.querySelector('[data-tv-swatch="' + key + '"]');
			if (!el) return;
			var color = el.querySelector('.tv-onboarding__swatch-color');
			var hex = el.querySelector('.tv-onboarding__swatch-hex');
			if (color) {
				color.style.background = palette[key];
				// Add a soft border for white/light surfaces so they're visible.
				color.style.borderColor = (palette[key] === '#FFFFFF' || palette[key].slice(1, 3) === 'F')
					? 'rgba(0,0,0,0.08)' : 'transparent';
			}
			if (hex) hex.textContent = palette[key];
		});

		// Update the mini preview component.
		var preview = document.getElementById('tv-mini-preview');
		if (preview) {
			preview.style.background = palette.bg;
			preview.style.borderColor = palette.border;
			preview.style.color = palette.text;

			var header = preview.querySelector('.tv-onboarding__preview-header');
			if (header) header.style.background = palette.heading;

			var brand = preview.querySelector('.tv-onboarding__preview-brand');
			if (brand) brand.style.color = '#FFFFFF';

			var cta = preview.querySelector('.tv-onboarding__preview-cta');
			if (cta) {
				cta.style.background = palette.accent;
				cta.style.color = palette.heading;
			}

			var h = preview.querySelector('.tv-onboarding__preview-h');
			if (h) h.style.color = palette.heading;

			var card = preview.querySelector('.tv-onboarding__preview-card');
			if (card) {
				card.style.background = palette.card_bg;
				card.style.borderColor = palette.border;
			}

			var thumb = preview.querySelector('.tv-onboarding__preview-card-thumb');
			if (thumb) thumb.style.background = palette.border;
		}
	}

	// Wire up — color picker → text input two-way bind, both trigger preview.
	document.addEventListener('DOMContentLoaded', function () {
		var pickers = document.querySelectorAll('input[type="color"][data-tv-color-target]');
		pickers.forEach(function (picker) {
			picker.addEventListener('input', function () {
				var target = picker.getAttribute('data-tv-color-target');
				var text = document.querySelector('input[name="' + target + '"]');
				if (text) text.value = picker.value.toUpperCase();
				updatePreview();
			});
		});

		var texts = document.querySelectorAll('input[data-tv-color-source]');
		texts.forEach(function (text) {
			text.addEventListener('input', function () {
				if (/^#[a-fA-F0-9]{6}$/.test(text.value)) {
					var picker = document.getElementById('tv-color-' + text.getAttribute('data-tv-color-source') + '-picker');
					if (picker) picker.value = text.value;
					updatePreview();
				}
			});
		});

		updatePreview();
	});
})();
