<?php
/**
 * Loom share-URL bulk importer.
 *
 * Settings page (and onboarding wizard step 3) accept a textarea of Loom
 * share URLs, one per line. This module turns each URL into a
 * `training_videos` post using the existing public-oEmbed flow for title,
 * description, and thumbnail — no Loom auth required.
 *
 * Idempotent: if a post with the matching `_loom_video_url` already
 * exists, the row is reported as `skipped` and not duplicated. The
 * `menu_order` of new posts is `(i+1) * 10` matching the order the URLs
 * appear in the textarea, so admins control viewing order by editing
 * the paste-list.
 *
 * Mirrors the production-tested pattern from
 * ~/.claude-royal/skills/loom/examples/xomox-training-import.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Run a bulk import.
 *
 * @param string $textarea Raw textarea value — one URL per line.
 * @return array{
 *   created:array<int,array{title:string,id:int,url:string}>,
 *   skipped:array<int,array{title:string,id:int,url:string,reason:string}>,
 *   failed:array<int,array{url:string,reason:string}>,
 * }
 */
function training_videos_bulk_import( $textarea ) {
	$result = array(
		'created' => array(),
		'skipped' => array(),
		'failed'  => array(),
	);

	$lines = preg_split( '/\r\n|\r|\n/', (string) $textarea );
	$urls  = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		// Dedupe within this batch.
		if ( ! in_array( $line, $urls, true ) ) {
			$urls[] = $line;
		}
	}

	$position = 0;
	foreach ( $urls as $url ) {
		$id = training_videos_extract_loom_id( $url );
		if ( ! $id ) {
			$result['failed'][] = array(
				'url'    => $url,
				'reason' => 'Not a recognized Loom URL',
			);
			continue;
		}

		$position++;
		$embed_url = 'https://www.loom.com/embed/' . $id;

		// Idempotency check: already a post with this embed URL?
		$existing = get_posts( array(
			'post_type'      => 'training_videos',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => '_loom_video_url',
			'meta_value'     => $embed_url,
			'fields'         => 'ids',
		) );

		if ( ! empty( $existing ) ) {
			$existing_id = (int) $existing[0];
			$result['skipped'][] = array(
				'title'  => get_the_title( $existing_id ),
				'id'     => $existing_id,
				'url'    => $url,
				'reason' => 'Post already exists',
			);
			continue;
		}

		// Pull metadata from public oEmbed.
		$oembed = training_videos_fetch_loom_oembed( $url );
		if ( ! $oembed ) {
			$result['failed'][] = array(
				'url'    => $url,
				'reason' => 'Loom oEmbed lookup failed (private video, or Loom rejected the request)',
			);
			continue;
		}

		$title = isset( $oembed['title'] ) ? wp_strip_all_tags( $oembed['title'] ) : '';
		if ( '' === $title ) {
			$title = 'Loom video ' . $id;
		}

		$description = '';
		if ( ! empty( $oembed['description'] ) ) {
			$description = wp_strip_all_tags( $oembed['description'] );
		}

		$post_id = wp_insert_post( array(
			'post_type'   => 'training_videos',
			'post_title'  => $title,
			'post_status' => 'publish',
			'menu_order'  => $position * 10,
		), true );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			$result['failed'][] = array(
				'url'    => $url,
				'reason' => is_wp_error( $post_id ) ? $post_id->get_error_message() : 'wp_insert_post returned 0',
			);
			continue;
		}

		update_post_meta( $post_id, '_loom_video_url', $embed_url );
		if ( '' !== $description ) {
			update_post_meta( $post_id, '_video_description', $description );
		}

		// Sideload the thumbnail synchronously so the archive grid shows
		// a local image immediately. Failure here is non-fatal — the
		// archive falls back to oEmbed thumbnail_url.
		if ( function_exists( 'training_videos_sideload_loom_thumbnail' ) ) {
			training_videos_sideload_loom_thumbnail( $post_id );
		}

		$result['created'][] = array(
			'title' => $title,
			'id'    => $post_id,
			'url'   => $url,
		);
	}

	return $result;
}
