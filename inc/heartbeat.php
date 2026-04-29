<?php
/**
 * Daily heartbeat to the central registry (card #9).
 *
 * Posts a JSON envelope describing this install — site_url, plugin
 * version, WP version, multisite flag, active theme, license key,
 * timestamp — to TRAINING_VIDEOS_LICENSE_SERVER. The server side is the
 * G&M Maintenance Portal (card on portal repo).
 *
 * Failure mode is soft. Heartbeat HTTP failures are logged via
 * error_log() (so they show up in debug.log if WP_DEBUG_LOG is on) but
 * never break wp-admin. The license-status logic in inc/license.php
 * already covers the 7-day grace window for unreachable servers.
 *
 * Schedule: WP cron, daily. Single event re-armed at the end of each
 * fire so we don't double-schedule.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TRAINING_VIDEOS_HEARTBEAT_HOOK = 'training_videos_heartbeat';

function training_videos_register_heartbeat() {
	if ( ! wp_next_scheduled( TRAINING_VIDEOS_HEARTBEAT_HOOK ) ) {
		// Random small offset so we don't hammer the server at the top of the hour.
		wp_schedule_event( time() + wp_rand( 60, 600 ), 'daily', TRAINING_VIDEOS_HEARTBEAT_HOOK );
	}
}
add_action( 'init', 'training_videos_register_heartbeat' );

function training_videos_unregister_heartbeat() {
	$timestamp = wp_next_scheduled( TRAINING_VIDEOS_HEARTBEAT_HOOK );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, TRAINING_VIDEOS_HEARTBEAT_HOOK );
	}
}

/**
 * The actual cron firing — POST a snapshot of this install.
 */
function training_videos_send_heartbeat() {
	if ( ! defined( 'TRAINING_VIDEOS_LICENSE_SERVER' ) ) {
		return;
	}

	$endpoint = trailingslashit( TRAINING_VIDEOS_LICENSE_SERVER ) . 'heartbeat';

	$payload = array(
		'site_url'       => home_url(),
		'plugin_version' => function_exists( 'training_videos_plugin_version' ) ? training_videos_plugin_version() : '0.0.0',
		'wp_version'     => get_bloginfo( 'version' ),
		'php_version'    => PHP_VERSION,
		'multisite'      => is_multisite(),
		'active_theme'   => get_stylesheet(),
		'license_key'    => training_videos_get_license_key(),
		'video_count'    => (int) wp_count_posts( 'training_videos' )->publish,
		'is_local'       => training_videos_is_local_install(),
		'sent_at'        => time(),
	);

	$response = wp_remote_post(
		$endpoint,
		array(
			'timeout'  => 8,
			'blocking' => false, // Fire-and-forget so we never block cron.
			'headers'  => array(
				'Content-Type' => 'application/json',
			),
			'body'     => wp_json_encode( $payload ),
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( '[training-videos] heartbeat failed: ' . $response->get_error_message() );
	}
}
add_action( TRAINING_VIDEOS_HEARTBEAT_HOOK, 'training_videos_send_heartbeat' );

/**
 * Best-effort detection of dev installs so the server side can flag
 * them and not bill clients for our local copies. Local-by-Flywheel
 * sites use *.local; common dev TLDs are also caught.
 */
function training_videos_is_local_install() {
	$host = parse_url( home_url(), PHP_URL_HOST );
	if ( ! $host ) {
		return false;
	}
	$dev_tlds = array( '.local', '.test', '.localhost', '.dev' );
	foreach ( $dev_tlds as $tld ) {
		if ( substr( $host, -strlen( $tld ) ) === $tld ) {
			return true;
		}
	}
	return ( $host === '127.0.0.1' || $host === 'localhost' );
}
