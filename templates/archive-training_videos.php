<?php
/**
 * Archive Template for Training Videos
 * Grid of thumbnail images - click to watch on single page
 * Using California Forever theme colors
 */

// Check if user is logged in (optional - remove if you want public access)
if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( get_post_type_archive_link( 'training_videos' ) ) );
	exit;
}

// Thumbnail helper lives in inc/loom-helpers.php (loaded by training-videos.php).

// Include header
include plugin_dir_path( __FILE__ ) . 'training-header.php';
?>

<div class="mx-auto px-6 lg:px-8 py-12" style="max-width: 1200px;">
	<!-- Welcome Section -->
	<div class="text-center mb-12">
		<h1 class="text-navy mb-4">Training Library</h1>

		<?php
		$count       = wp_count_posts( 'training_videos' );
		$video_count = $count->publish;
		?>
		<p class="text-stone-blue text-lg max-w-2xl mx-auto">
			<?php echo $video_count; ?> training <?php echo 1 === $video_count ? 'video' : 'videos'; ?> to help you manage your website.
			Click any video to start watching.
		</p>
	</div>

	<?php
	// Get documentation resource settings
	$resource_title       = get_option( 'training_videos_resource_title', '' );
	$resource_url         = get_option( 'training_videos_resource_url', '' );
	$resource_description = get_option( 'training_videos_resource_description', '' );

	if ( $resource_url ) :
		?>
		<!-- Documentation Resource Card -->
		<div class="mb-10">
			<a href="<?php echo esc_url( $resource_url ); ?>"
			   target="_blank"
			   rel="noopener noreferrer"
			   class="block bg-navy rounded-lg p-6 hover:bg-stone-blue transition-colors group">
				<div class="flex items-center gap-6">
					<!-- Icon -->
					<div class="flex-shrink-0 w-14 h-14 rounded-full bg-orange flex items-center justify-center">
						<i class="fa-sharp fa-solid fa-file-lines text-navy text-2xl"></i>
					</div>

					<!-- Content -->
					<div class="flex-1 min-w-0">
						<h3 class="text-white text-lg font-medium group-hover:text-beige transition-colors">
							<?php echo esc_html( $resource_title ?: 'Documentation' ); ?>
							<i class="fa-sharp fa-solid fa-arrow-up-right text-sm ml-2 opacity-60"></i>
						</h3>
						<?php if ( $resource_description ) : ?>
							<p class="text-beige/70 text-sm mt-2">
								<?php echo esc_html( $resource_description ); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<?php
		// Adapt grid columns to video count so a sparse library doesn't leave a
		// dead trailing column. 1 → centered single card, 2 → halves, 3 → thirds,
		// 4+ → quarters. wp_count_posts() returns strings, so int-cast first.
		$count_int  = (int) $video_count;
		$grid_class = 'grid grid-cols-1 gap-6 tv-grid';
		if ( $count_int >= 4 ) {
			$grid_class .= ' md:grid-cols-2 lg:grid-cols-4';
		} elseif ( 3 === $count_int ) {
			$grid_class .= ' md:grid-cols-2 lg:grid-cols-3';
		} elseif ( 2 === $count_int ) {
			$grid_class .= ' md:grid-cols-2';
		} else {
			// Single video — center it with a max-width so it doesn't stretch full bleed.
			$grid_class .= ' tv-grid-single';
		}
		?>
		<!-- Video Grid -->
		<div class="<?php echo esc_attr( $grid_class ); ?>">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<?php
				$video_description = get_post_meta( get_the_ID(), '_video_description', true );
				$video_url         = get_post_meta( get_the_ID(), '_loom_video_url', true );
				$thumbnail_url     = get_video_thumbnail_url( $video_url );
				?>
				<a href="<?php the_permalink(); ?>" class="group block bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition-shadow duration-300 border border-linen">
					<!-- Thumbnail -->
					<div class="relative bg-linen overflow-hidden" style="padding-bottom: 56.25%;">
						<?php if ( $thumbnail_url ) : ?>
							<img src="<?php echo esc_url( $thumbnail_url ); ?>"
								 alt="<?php the_title_attribute(); ?>"
								 class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
								 loading="lazy"
								 onerror="this.parentElement.innerHTML='<div class=\'absolute inset-0 flex items-center justify-center bg-linen\'><i class=\'fa-sharp fa-solid fa-play text-4xl text-dune\'></i></div>'">
							<!-- Play overlay on hover -->
							<div class="absolute inset-0 bg-navy/0 group-hover:bg-navy/20 transition-colors duration-300 flex items-center justify-center">
								<div class="w-16 h-16 rounded-full bg-orange flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 shadow-lg">
									<i class="fa-sharp fa-solid fa-play text-navy text-xl ml-1"></i>
								</div>
							</div>
						<?php else : ?>
							<div class="absolute inset-0 flex items-center justify-center bg-linen">
								<div class="text-center">
									<i class="fa-sharp fa-solid fa-video text-4xl text-dune mb-2"></i>
									<p class="text-sm text-stone-blue">Video</p>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<!-- Card Content -->
					<div class="p-5">
						<h3 class="text-lg font-medium text-navy mb-2 group-hover:text-brick transition-colors leading-tight">
							<?php the_title(); ?>
						</h3>

						<?php if ( $video_description ) : ?>
							<p class="text-stone-blue text-sm leading-relaxed line-clamp-2">
								<?php echo esc_html( $video_description ); ?>
							</p>
						<?php endif; ?>
					</div>
				</a>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<!-- Empty State -->
		<div class="bg-white rounded-lg p-12 text-center border border-linen">
			<i class="fa-sharp fa-solid fa-video-slash text-5xl text-dune mb-4"></i>
			<p class="text-xl text-stone-blue mb-6">No training videos have been added yet.</p>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=training_videos' ) ); ?>"
				   class="btn">
					<i class="fa-sharp fa-solid fa-plus mr-2"></i>
					Add First Video
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

<?php
// Include footer
include plugin_dir_path( __FILE__ ) . 'training-footer.php';
?>
