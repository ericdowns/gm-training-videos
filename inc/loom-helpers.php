<?php
/**
 * Loom helpers — thumbnail fetching via oEmbed.
 *
 * Used by archive + single templates so we only have one implementation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'training_videos_get_loom_thumbnail_url' ) ) {
	/**
	 * Get a thumbnail URL for a Loom video.
	 *
	 * Loom's plain-ID thumbnails (e.g. {id}-with-play.gif) return HTTP 403 for
	 * workspace-private videos. The oEmbed endpoint returns a hash-suffixed
	 * thumbnail that is publicly accessible regardless of video privacy, so we
	 * use that and cache for 7 days via WP transient. Failures are cached for
	 * 5 minutes so a stale 403 doesn't stick.
	 *
	 * @param string $video_url Loom share or embed URL.
	 * @return string|false Thumbnail URL on success, false otherwise.
	 */
	function training_videos_get_loom_thumbnail_url( $video_url ) {
		if ( empty( $video_url ) ) {
			return false;
		}
		if ( strpos( $video_url, 'loom.com' ) === false ) {
			return false;
		}
		if ( ! preg_match( '/loom\.com\/(?:embed|share)\/([a-zA-Z0-9]+)/', $video_url, $matches ) ) {
			return false;
		}
		$video_id = $matches[1];

		$cache_key = 'training_video_thumb_' . $video_id;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached ?: false;
		}

		$share_url = 'https://www.loom.com/share/' . $video_id;
		$response  = wp_remote_get(
			'https://www.loom.com/v1/oembed?url=' . rawurlencode( $share_url ),
			array( 'timeout' => 5 )
		);

		$thumb = '';
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $body['thumbnail_url'] ) ) {
				$thumb = $body['thumbnail_url'];
			}
		}

		set_transient(
			$cache_key,
			$thumb,
			$thumb ? 7 * DAY_IN_SECONDS : 5 * MINUTE_IN_SECONDS
		);
		return $thumb ?: false;
	}
}

if ( ! function_exists( 'get_video_thumbnail_url' ) ) {
	/**
	 * Backwards-compat alias. Old code (and the archive template before #15)
	 * called this unprefixed name. Keep it as a thin wrapper so any external
	 * customizations don't break.
	 */
	function get_video_thumbnail_url( $video_url ) {
		return training_videos_get_loom_thumbnail_url( $video_url );
	}
}
