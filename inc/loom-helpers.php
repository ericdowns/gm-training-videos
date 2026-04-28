<?php
/**
 * Loom helpers — thumbnails, descriptions, oEmbed cache, Media Library
 * sideloading.
 *
 * Used by archive + single templates and the cron-driven cache.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'training_videos_extract_loom_id' ) ) {
	/**
	 * Pull the video ID out of any Loom URL (share/embed).
	 */
	function training_videos_extract_loom_id( $video_url ) {
		if ( empty( $video_url ) || strpos( $video_url, 'loom.com' ) === false ) {
			return false;
		}
		if ( ! preg_match( '/loom\.com\/(?:embed|share)\/([a-zA-Z0-9]+)/', $video_url, $matches ) ) {
			return false;
		}
		return $matches[1];
	}
}

if ( ! function_exists( 'training_videos_fetch_loom_oembed' ) ) {
	/**
	 * Fetch + cache the full oEmbed response for a Loom video.
	 *
	 * Returns the decoded array (title, description, thumbnail_url, etc) or
	 * false on failure. Cached 7 days on success / 5 minutes on failure.
	 */
	function training_videos_fetch_loom_oembed( $video_url, $force_refresh = false ) {
		$video_id = training_videos_extract_loom_id( $video_url );
		if ( ! $video_id ) {
			return false;
		}

		$cache_key = 'training_video_oembed_' . $video_id;
		if ( ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached ?: false;
			}
		}

		$share_url = 'https://www.loom.com/share/' . $video_id;
		$response  = wp_remote_get(
			'https://www.loom.com/v1/oembed?url=' . rawurlencode( $share_url ),
			array( 'timeout' => 5 )
		);

		$data = false;
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $body ) ) {
				$data = $body;
			}
		}

		set_transient(
			$cache_key,
			$data ?: '',
			$data ? 7 * DAY_IN_SECONDS : 5 * MINUTE_IN_SECONDS
		);
		return $data ?: false;
	}
}

if ( ! function_exists( 'training_videos_get_loom_thumbnail_url' ) ) {
	/**
	 * Get a thumbnail URL for a Loom video.
	 *
	 * Resolution order:
	 *   1. Local Media Library attachment (post meta `_loom_thumbnail_url`),
	 *      populated by the save-triggered cron in card #3.
	 *   2. Remote oEmbed thumbnail (cached 7 days via transient).
	 *
	 * Loom's plain-ID thumbnails (e.g. {id}-with-play.gif) return HTTP 403 for
	 * workspace-private videos. oEmbed returns a hash-suffixed URL that's
	 * publicly accessible regardless of video privacy.
	 *
	 * @param string $video_url Loom share or embed URL.
	 * @param int    $post_id   Optional post ID — used to read the local cache.
	 * @return string|false Thumbnail URL on success, false otherwise.
	 */
	function training_videos_get_loom_thumbnail_url( $video_url, $post_id = 0 ) {
		// Local Media Library cache wins if it exists (card #3).
		if ( $post_id ) {
			$local = get_post_meta( (int) $post_id, '_loom_thumbnail_url', true );
			if ( $local ) {
				return $local;
			}
		}

		$data = training_videos_fetch_loom_oembed( $video_url );
		return ( $data && ! empty( $data['thumbnail_url'] ) ) ? $data['thumbnail_url'] : false;
	}
}

if ( ! function_exists( 'training_videos_get_loom_description' ) ) {
	/**
	 * Get the producer-authored description for a Loom video (from oEmbed).
	 *
	 * Loom's oEmbed `description` field surfaces the text the video owner
	 * wrote in the Loom UI (Edit Video → Description). Used by cards #7/#8
	 * to auto-populate the post meta when the producer authors there first.
	 *
	 * @param string $video_url     Loom share or embed URL.
	 * @param bool   $force_refresh Skip the oEmbed cache.
	 * @return string Description (may be empty).
	 */
	function training_videos_get_loom_description( $video_url, $force_refresh = false ) {
		$data = training_videos_fetch_loom_oembed( $video_url, $force_refresh );
		if ( ! $data || empty( $data['description'] ) ) {
			return '';
		}
		return wp_strip_all_tags( $data['description'] );
	}
}

if ( ! function_exists( 'training_videos_sideload_loom_thumbnail' ) ) {
	/**
	 * Download a Loom thumbnail and attach it to the post as a Media Library
	 * attachment. Saves the local URL to `_loom_thumbnail_url` so future
	 * front-end calls return the local copy instead of hitting Loom's CDN.
	 *
	 * Idempotent — does nothing if the post already has a local thumbnail.
	 * Card #3.
	 *
	 * @param int  $post_id training_videos post ID
	 * @param bool $force   Re-download even if already cached
	 * @return string|false Local URL on success, false on failure
	 */
	function training_videos_sideload_loom_thumbnail( $post_id, $force = false ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return false;
		}

		if ( ! $force ) {
			$existing = get_post_meta( $post_id, '_loom_thumbnail_url', true );
			if ( $existing ) {
				return $existing;
			}
		} else {
			// Forced refresh — drop the old attachment first.
			$old_id = (int) get_post_meta( $post_id, '_loom_thumbnail_attachment_id', true );
			if ( $old_id ) {
				wp_delete_attachment( $old_id, true );
			}
			delete_post_meta( $post_id, '_loom_thumbnail_url' );
			delete_post_meta( $post_id, '_loom_thumbnail_attachment_id' );
		}

		$video_url = get_post_meta( $post_id, '_loom_video_url', true );
		if ( ! $video_url ) {
			return false;
		}

		$data = training_videos_fetch_loom_oembed( $video_url, $force );
		if ( ! $data || empty( $data['thumbnail_url'] ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$desc          = sprintf( 'Loom thumbnail — %s', get_the_title( $post_id ) );
		$attachment_id = media_sideload_image( $data['thumbnail_url'], $post_id, $desc, 'id' );

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return false;
		}

		$local_url = wp_get_attachment_url( $attachment_id );
		if ( ! $local_url ) {
			return false;
		}

		update_post_meta( $post_id, '_loom_thumbnail_url', $local_url );
		update_post_meta( $post_id, '_loom_thumbnail_attachment_id', $attachment_id );
		return $local_url;
	}
}

/**
 * Hook save_post: schedule a one-off cron to sideload the thumbnail. Card #3.
 *
 * Wrapped in function_exists guard so the file stays safe to require_once
 * multiple times during testing.
 */
if ( ! function_exists( 'training_videos_schedule_thumbnail_cache' ) ) {
	function training_videos_schedule_thumbnail_cache( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$video_url = get_post_meta( $post_id, '_loom_video_url', true );
		if ( ! $video_url ) {
			return;
		}

		// If the video URL changed since last cache, blow away the stale one
		// so the new thumbnail gets sideloaded.
		$cached_for = get_post_meta( $post_id, '_loom_thumbnail_for_url', true );
		if ( $cached_for && $cached_for !== $video_url ) {
			$old_id = (int) get_post_meta( $post_id, '_loom_thumbnail_attachment_id', true );
			if ( $old_id ) {
				wp_delete_attachment( $old_id, true );
			}
			delete_post_meta( $post_id, '_loom_thumbnail_url' );
			delete_post_meta( $post_id, '_loom_thumbnail_attachment_id' );
		}
		update_post_meta( $post_id, '_loom_thumbnail_for_url', $video_url );

		if ( ! wp_next_scheduled( 'training_videos_run_thumbnail_cache', array( $post_id ) ) ) {
			wp_schedule_single_event( time() + 60, 'training_videos_run_thumbnail_cache', array( $post_id ) );
		}
	}
	add_action( 'save_post_training_videos', 'training_videos_schedule_thumbnail_cache', 20, 3 );
}

if ( ! function_exists( 'training_videos_run_thumbnail_cache_handler' ) ) {
	function training_videos_run_thumbnail_cache_handler( $post_id ) {
		training_videos_sideload_loom_thumbnail( $post_id );
	}
	add_action( 'training_videos_run_thumbnail_cache', 'training_videos_run_thumbnail_cache_handler' );
}

/**
 * Hook save_post: pull the producer-authored description from Loom oEmbed
 * and write it to post meta — but only if the description meta is empty.
 * Card #7. Never overwrites manual edits.
 */
if ( ! function_exists( 'training_videos_autopopulate_description' ) ) {
	function training_videos_autopopulate_description( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$existing = trim( (string) get_post_meta( $post_id, '_video_description', true ) );
		if ( $existing !== '' ) {
			return; // Don't overwrite producer/editor-authored content.
		}
		$video_url = get_post_meta( $post_id, '_loom_video_url', true );
		if ( ! $video_url ) {
			return;
		}
		$description = training_videos_get_loom_description( $video_url );
		if ( $description ) {
			update_post_meta( $post_id, '_video_description', $description );
		}
	}
	add_action( 'save_post_training_videos', 'training_videos_autopopulate_description', 25, 3 );
}

/**
 * Force-refresh the description from Loom oEmbed regardless of what's
 * currently in the post meta. Used by the "Refresh from Loom" button (card
 * #8) and the bulk action (card #2).
 *
 * @param int $post_id training_videos post ID
 * @return string|false Updated description on success, false otherwise
 */
if ( ! function_exists( 'training_videos_refresh_description_from_loom' ) ) {
	function training_videos_refresh_description_from_loom( $post_id ) {
		$post_id   = (int) $post_id;
		$video_url = get_post_meta( $post_id, '_loom_video_url', true );
		if ( ! $video_url ) {
			return false;
		}
		$description = training_videos_get_loom_description( $video_url, true );
		if ( $description === '' ) {
			return false;
		}
		update_post_meta( $post_id, '_video_description', $description );
		return $description;
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
