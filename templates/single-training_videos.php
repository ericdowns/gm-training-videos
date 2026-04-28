<?php
/**
 * Single Template for Training Videos
 * Video player with sidebar navigation
 * Using California Forever theme colors
 */

// Check if user is logged in (optional - remove if you want public access)
if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( get_permalink() ) );
	exit;
}

// Get navigation videos
$current_video_id    = get_the_ID();
$current_video_order = get_post_field( 'menu_order', $current_video_id );

// Get all training videos for sidebar
$args       = array(
	'post_type'      => 'training_videos',
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
	'posts_per_page' => 100,
);
$all_videos = get_posts( $args );

// Find current index, then wrap PREV/NEXT around so the pager is always present.
// First video → PREV is last, last video → NEXT is first. Loop never dead-ends.
$prev_video    = null;
$next_video    = null;
$current_index = -1;
$total_videos  = count( $all_videos );

foreach ( $all_videos as $index => $video ) {
	if ( $video->ID == $current_video_id ) {
		$current_index = $index;
		break;
	}
}

if ( $total_videos > 1 && $current_index > -1 ) {
	$prev_index = ( $current_index - 1 + $total_videos ) % $total_videos;
	$next_index = ( $current_index + 1 ) % $total_videos;
	$prev_video = $all_videos[ $prev_index ];
	$next_video = $all_videos[ $next_index ];
}

// Include header
include plugin_dir_path( __FILE__ ) . 'training-header.php';
?>

<div class="mx-auto px-6 lg:px-8 py-8" style="max-width: 1400px;">
	<div class="lg:flex lg:gap-8 lg:items-start">

		<!-- Sidebar -->
		<aside class="hidden lg:block flex-shrink-0 bg-white rounded-lg shadow-sm border border-linen lg:sticky lg:top-24 overflow-hidden" style="width: 300px;">
			<!-- Sidebar Header -->
			<div class="p-4 bg-navy">
				<h3 class="text-base font-medium flex items-center gap-2 text-white">
					<i class="fa-sharp fa-solid fa-list"></i>
					All Videos
					<span class="ml-auto text-sm opacity-60"><?php echo count( $all_videos ); ?></span>
				</h3>
			</div>

			<!-- Video List -->
			<div class="overflow-y-auto" style="max-height: calc(100vh - 200px);">
				<ul class="divide-y divide-linen">
					<?php foreach ( $all_videos as $index => $video ) : ?>
						<?php $is_current = $video->ID == $current_video_id; ?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $video->ID ) ); ?>"
							   class="flex items-start gap-3 p-4 transition-colors <?php echo $is_current ? 'bg-beige border-l-4 border-orange' : 'hover:bg-beige/50'; ?>">
								<span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium <?php echo $is_current ? 'bg-orange text-navy' : 'bg-linen text-stone-blue'; ?>">
									<?php echo $index + 1; ?>
								</span>
								<span class="text-sm <?php echo $is_current ? 'text-navy font-medium' : 'text-stone-blue'; ?> leading-tight">
									<?php echo esc_html( $video->post_title ); ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Back to All Link -->
			<div class="p-4 border-t border-linen bg-beige/50">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'training_videos' ) ); ?>"
				   class="flex items-center justify-center gap-2 text-sm text-navy hover:text-brick transition-colors font-medium">
					<i class="fa-sharp fa-solid fa-grid-2"></i>
					View All Videos
				</a>
			</div>
		</aside>

		<!-- Main Content -->
		<div class="lg:flex-1 min-w-0">
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					?>

				<!-- Mobile-only "All videos" pill (lg+ uses the sidebar) -->
				<a href="<?php echo esc_url( get_post_type_archive_link( 'training_videos' ) ); ?>"
				   class="tv-back-pill">
					<i class="fa-sharp fa-solid fa-arrow-left" aria-hidden="true"></i>
					All videos
				</a>

				<!-- Video Title -->
				<div class="mb-6">
					<span class="tv-position">
						Video <?php echo (int) $current_index + 1; ?> of <?php echo count( $all_videos ); ?>
					</span>
					<h1 class="text-navy mb-3"><?php the_title(); ?></h1>

					<?php
					$video_description = get_post_meta( get_the_ID(), '_video_description', true );
					if ( $video_description ) :
						?>
						<p class="text-stone-blue text-lg leading-relaxed">
							<?php echo esc_html( $video_description ); ?>
						</p>
					<?php endif; ?>
				</div>

				<!-- Video Embed (lazy-loaded — click poster to load iframe) -->
				<?php
				$loom_video_url = get_post_meta( get_the_ID(), '_loom_video_url', true );
				if ( $loom_video_url ) :
					$thumbnail_url = training_videos_get_loom_thumbnail_url( $loom_video_url, get_the_ID() );
					$embed_url     = add_query_arg(
						array(
							'hide_owner'       => 'true',
							'hide_share'       => 'true',
							'hide_title'       => 'true',
							'hideEmbedTopBar'  => 'true',
						),
						$loom_video_url
					);
					?>
					<div class="tv-poster-wrap mb-8 rounded-lg overflow-hidden shadow-lg">
						<button type="button"
								class="tv-poster"
								data-embed="<?php echo esc_url( $embed_url ); ?>"
								aria-label="Play video: <?php echo esc_attr( get_the_title() ); ?>">
							<?php if ( $thumbnail_url ) : ?>
								<img class="tv-poster__img"
									 src="<?php echo esc_url( $thumbnail_url ); ?>"
									 alt=""
									 loading="lazy" />
							<?php endif; ?>
							<span class="tv-poster__overlay" aria-hidden="true">
								<span class="tv-poster__play">
									<i class="fa-sharp fa-solid fa-play"></i>
								</span>
							</span>
						</button>
					</div>
				<?php else : ?>
					<div class="tv-poster-wrap mb-8 rounded-lg overflow-hidden">
						<div class="tv-poster tv-poster--empty" aria-hidden="true">
							<div class="tv-poster__empty">
								<i class="fa-sharp fa-solid fa-video-slash text-5xl mb-3"></i>
								<p class="text-lg">No video available</p>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<!-- Additional Content -->
				<?php if ( get_the_content() ) : ?>
					<div class="prose prose-lg max-w-none text-stone-blue mb-8">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<!--
					Navigation: PREV ↔ NEXT loop around the library so navigation
					never dead-ends. "Back to all videos" sits centered between them.
					Mobile: vertical stack. md+: 3-column row, equal widths.
				-->
				<?php if ( $prev_video && $next_video ) : ?>
					<nav class="tv-pager pt-8 border-t border-linen">
						<a href="<?php echo esc_url( get_permalink( $prev_video->ID ) ); ?>"
						   class="tv-pager__item tv-pager__item--prev group">
							<span class="tv-pager__icon">
								<i class="fa-sharp fa-solid fa-arrow-left" aria-hidden="true"></i>
							</span>
							<span class="tv-pager__text">
								<span class="tv-pager__label">Previous</span>
								<span class="tv-pager__title"><?php echo esc_html( $prev_video->post_title ); ?></span>
							</span>
						</a>

						<a href="<?php echo esc_url( get_post_type_archive_link( 'training_videos' ) ); ?>"
						   class="tv-pager__center">
							<i class="fa-sharp fa-solid fa-grid-2" aria-hidden="true"></i>
							<span>All videos</span>
						</a>

						<a href="<?php echo esc_url( get_permalink( $next_video->ID ) ); ?>"
						   class="tv-pager__item tv-pager__item--next group">
							<span class="tv-pager__text">
								<span class="tv-pager__label">Next</span>
								<span class="tv-pager__title"><?php echo esc_html( $next_video->post_title ); ?></span>
							</span>
							<span class="tv-pager__icon">
								<i class="fa-sharp fa-solid fa-arrow-right" aria-hidden="true"></i>
							</span>
						</a>
					</nav>
				<?php endif; ?>

					<?php
				endwhile;
			else :
				?>
				<div class="text-center py-12">
					<p class="text-xl text-stone-blue">Training video not found.</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
/**
 * Lazy-load the Loom iframe on click. Until clicked, only the poster image +
 * a play button render — no Loom JS, no third-party network requests.
 */
(function () {
	var posters = document.querySelectorAll( '.tv-poster[data-embed]' );
	Array.prototype.forEach.call( posters, function ( btn ) {
		btn.addEventListener( 'click', function () {
			var embedUrl = btn.getAttribute( 'data-embed' );
			if ( ! embedUrl ) { return; }
			var sep   = embedUrl.indexOf( '?' ) === -1 ? '?' : '&';
			var iframe = document.createElement( 'iframe' );
			iframe.src           = embedUrl + sep + 'autoplay=1';
			iframe.title         = btn.getAttribute( 'aria-label' ) || 'Video';
			iframe.allow         = 'autoplay; fullscreen; picture-in-picture';
			iframe.allowFullscreen = true;
			iframe.setAttribute( 'frameborder', '0' );
			btn.replaceWith( iframe );
		} );
	} );
})();
</script>

<?php
// Include footer
include plugin_dir_path( __FILE__ ) . 'training-footer.php';
?>
