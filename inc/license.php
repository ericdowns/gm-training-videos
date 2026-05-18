<?php
/**
 * License key + status.
 *
 * Per the v1.4.5 decision (card #9), the plugin runs a soft-fail license
 * model: front-end templates render normally regardless of license state;
 * an admin notice nudges admins to activate or re-activate when the key
 * is missing or invalid. A 7-day grace window covers transient server
 * outages so a single network blip doesn't cascade into "license invalid"
 * across every install.
 *
 * Server URL is configurable via:
 *   define( 'TRAINING_VIDEOS_LICENSE_SERVER', 'https://portal.grainandmortar.com/api/training-videos' );
 * The default points at the G&M Maintenance Portal (which is where the
 * heartbeat + license endpoints will live — see card #10 / portal repo).
 *
 * Local-by-Flywheel sites firing heartbeats from *.local URLs should be
 * recognized as dev installs server-side and not billed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TRAINING_VIDEOS_LICENSE_SERVER' ) ) {
	define( 'TRAINING_VIDEOS_LICENSE_SERVER', 'https://portal.grainandmortar.com/api/training-videos' );
}

const TRAINING_VIDEOS_LICENSE_OPTION       = 'training_videos_license_key';
const TRAINING_VIDEOS_LICENSE_STATUS_OPT   = 'training_videos_license_status';
const TRAINING_VIDEOS_LICENSE_CHECKED_OPT  = 'training_videos_license_last_checked';
const TRAINING_VIDEOS_LICENSE_LASTGOOD_OPT = 'training_videos_license_last_good';
const TRAINING_VIDEOS_LICENSE_TRANSIENT    = 'training_videos_license_status_cache';
const TRAINING_VIDEOS_LICENSE_REQUIRED_OPT = 'training_videos_license_required';
const TRAINING_VIDEOS_LICENSE_GRACE_DAYS   = 7;

/**
 * Is a license key required on this install? Defaults to true so existing
 * sites keep their current behavior. Site admins can flip this off in
 * Settings to silence the unlicensed/invalid nag — useful for internal
 * G&M installs, demos, or sites under a maintenance contract that doesn't
 * include a key. The daily heartbeat still fires regardless; this only
 * governs the admin notice surface.
 */
function training_videos_license_required() {
	$opt = get_option( TRAINING_VIDEOS_LICENSE_REQUIRED_OPT, '1' );
	return '1' === (string) $opt;
}

/**
 * @return string Empty if not set.
 */
function training_videos_get_license_key() {
	return trim( (string) get_option( TRAINING_VIDEOS_LICENSE_OPTION, '' ) );
}

/**
 * Effective status used by callers. Resolves the cached + grace-period
 * logic so callers don't have to think about it.
 *
 * Returns one of: 'active', 'invalid', 'unreachable', 'unlicensed'.
 */
function training_videos_get_license_status() {
	$key = training_videos_get_license_key();
	if ( '' === $key ) {
		return 'unlicensed';
	}

	$cached = get_transient( TRAINING_VIDEOS_LICENSE_TRANSIENT );
	if ( $cached === 'active' || $cached === 'invalid' ) {
		return $cached;
	}

	// No fresh cache — check stored last-known status.
	$stored = (string) get_option( TRAINING_VIDEOS_LICENSE_STATUS_OPT, '' );

	// Grace period: if the last successful check was within
	// TRAINING_VIDEOS_LICENSE_GRACE_DAYS, treat 'unreachable' as
	// 'active' so a network blip doesn't cascade.
	if ( 'unreachable' === $stored ) {
		$last_good = (int) get_option( TRAINING_VIDEOS_LICENSE_LASTGOOD_OPT, 0 );
		$grace_seconds = TRAINING_VIDEOS_LICENSE_GRACE_DAYS * DAY_IN_SECONDS;
		if ( $last_good > 0 && ( time() - $last_good ) < $grace_seconds ) {
			return 'active'; // Within grace window — treat as active.
		}
	}

	return $stored ?: 'unreachable';
}

/**
 * Validate a license key against the server. Caches the result for 24h.
 *
 * Side effects: writes to the status option + last-good timestamp option.
 * Failure mode is soft: never throws, never breaks wp-admin.
 *
 * @param bool $force Skip cache.
 * @return string 'active' | 'invalid' | 'unreachable' | 'unlicensed'
 */
function training_videos_validate_license( $force = false ) {
	$key = training_videos_get_license_key();
	if ( '' === $key ) {
		update_option( TRAINING_VIDEOS_LICENSE_STATUS_OPT, 'unlicensed' );
		delete_transient( TRAINING_VIDEOS_LICENSE_TRANSIENT );
		return 'unlicensed';
	}

	if ( ! $force ) {
		$cached = get_transient( TRAINING_VIDEOS_LICENSE_TRANSIENT );
		if ( $cached === 'active' || $cached === 'invalid' ) {
			return $cached;
		}
	}

	$endpoint = trailingslashit( TRAINING_VIDEOS_LICENSE_SERVER ) . 'license/validate';
	$response = wp_remote_post(
		$endpoint,
		array(
			'timeout' => 6,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'site_url'       => home_url(),
				'plugin_version' => training_videos_plugin_version(),
			) ),
		)
	);

	if ( is_wp_error( $response ) ) {
		update_option( TRAINING_VIDEOS_LICENSE_STATUS_OPT, 'unreachable' );
		update_option( TRAINING_VIDEOS_LICENSE_CHECKED_OPT, time() );
		// No transient set so we re-check on next tick.
		return 'unreachable';
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 === $code ) {
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$is_active = is_array( $body ) && ! empty( $body['valid'] );
		$status = $is_active ? 'active' : 'invalid';
	} elseif ( 401 === $code || 403 === $code || 404 === $code ) {
		// Server accepted the request but rejected the key.
		$status = 'invalid';
	} else {
		// 5xx, network weirdness, etc. — treat as unreachable.
		$status = 'unreachable';
	}

	update_option( TRAINING_VIDEOS_LICENSE_STATUS_OPT, $status );
	update_option( TRAINING_VIDEOS_LICENSE_CHECKED_OPT, time() );
	if ( 'active' === $status ) {
		update_option( TRAINING_VIDEOS_LICENSE_LASTGOOD_OPT, time() );
	}

	if ( $status === 'active' || $status === 'invalid' ) {
		set_transient( TRAINING_VIDEOS_LICENSE_TRANSIENT, $status, DAY_IN_SECONDS );
	}

	return $status;
}

/**
 * Compact "(version) (status)" line for templates / admin meta.
 */
function training_videos_plugin_version() {
	$plugin_data = function_exists( 'get_plugin_data' )
		? get_plugin_data( dirname( __DIR__ ) . '/training-videos.php', false, false )
		: array();
	return isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '0.0.0';
}

/**
 * Admin notice when license is invalid or unlicensed. Per the soft-fail
 * decision: front-end keeps working, admin gets nudged.
 */
function training_videos_license_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! training_videos_license_required() ) {
		return;
	}
	$status = training_videos_get_license_status();
	if ( $status === 'active' || $status === 'unreachable' ) {
		// 'unreachable' produces no nag — server outage is our problem, not the admin's.
		return;
	}

	$settings_url = admin_url( 'edit.php?post_type=training_videos&page=training-videos-settings#license' );

	if ( $status === 'unlicensed' ) {
		printf(
			'<div class="notice notice-warning"><p><strong>Training Videos:</strong> no license key configured. <a href="%s">Add one in Settings</a> to enable updates and registration.</p></div>',
			esc_url( $settings_url )
		);
	} elseif ( $status === 'invalid' ) {
		printf(
			'<div class="notice notice-error"><p><strong>Training Videos:</strong> license key invalid or expired. <a href="%s">Re-activate in Settings</a>.</p></div>',
			esc_url( $settings_url )
		);
	}
}
add_action( 'admin_notices', 'training_videos_license_admin_notice' );

/**
 * Settings handler — when the license_key option is updated, immediately
 * fire a fresh validation so the admin sees a status change without
 * waiting for the next cron tick.
 */
function training_videos_on_license_key_change( $old, $new ) {
	if ( $old === $new ) {
		return;
	}
	delete_transient( TRAINING_VIDEOS_LICENSE_TRANSIENT );
	training_videos_validate_license( true );
}
add_action( 'update_option_' . TRAINING_VIDEOS_LICENSE_OPTION, 'training_videos_on_license_key_change', 10, 2 );
