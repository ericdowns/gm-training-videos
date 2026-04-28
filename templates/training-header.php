<?php
// Pull all training videos for the mobile drawer (visible <lg).
$tv_drawer_videos = get_posts(
	array(
		'post_type'      => 'training_videos',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
		'posts_per_page' => 100,
		'post_status'    => 'publish',
	)
);
$tv_archive_url    = get_post_type_archive_link( 'training_videos' );
$tv_current_post_id = is_singular( 'training_videos' ) ? get_the_ID() : 0;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">

	<title><?php echo wp_title( '|', false, 'right' ); ?> Training Library - <?php bloginfo( 'name' ); ?></title>

	<?php
	// Load WordPress head items including theme styles
	wp_head();

	// Per-site brand overrides (Settings → Training Videos → Brand Theme)
	if ( function_exists( 'training_videos_render_brand_styles' ) ) {
		training_videos_render_brand_styles();
	}
	?>
</head>
<body <?php body_class( 'bg-beige' ); ?>>

	<!-- Training Portal Header -->
	<header class="bg-navy sticky top-0 z-50">
		<div class="mx-auto px-6 lg:px-8 py-4" style="max-width: 1400px;">
			<div class="flex justify-between items-center">
				<!-- Brand -->
				<a href="<?php echo esc_url( get_post_type_archive_link( 'training_videos' ) ); ?>" class="flex items-center gap-3 text-white hover:text-orange transition-colors">
					<i class="fa-sharp fa-solid fa-graduation-cap text-2xl"></i>
					<div>
						<span class="text-lg font-medium block leading-tight">Training Library</span>
						<span class="text-xs text-white/60 block"><?php bloginfo( 'name' ); ?></span>
					</div>
				</a>

				<!-- Navigation -->
				<nav class="tv-header-nav">
					<button type="button"
							class="tv-drawer-toggle"
							aria-controls="tv-drawer"
							aria-expanded="false"
							aria-label="Open all videos menu">
						<i class="fa-sharp fa-solid fa-bars" aria-hidden="true"></i>
					</button>

					<a href="<?php echo esc_url( home_url() ); ?>"
					   class="tv-header-link"
					   aria-label="Back to main site">
						<i class="fa-sharp fa-solid fa-arrow-left" aria-hidden="true"></i>
						<span>Back to Site</span>
					</a>

					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=training_videos' ) ); ?>"
						   class="tv-header-link tv-header-link--primary"
						   aria-label="Manage training videos in admin">
							<i class="fa-sharp fa-solid fa-gear" aria-hidden="true"></i>
							<span>Manage</span>
						</a>
					<?php endif; ?>
				</nav>
			</div>
		</div>
	</header>

	<!-- Mobile drawer (visible <lg). Renders globally so archive + single both have it. -->
	<div class="tv-drawer-backdrop" data-tv-drawer-close aria-hidden="true"></div>
	<aside id="tv-drawer"
	       class="tv-drawer"
	       aria-label="All training videos"
	       aria-hidden="true">
		<div class="tv-drawer__head">
			<h2 class="tv-drawer__title">
				<i class="fa-sharp fa-solid fa-list" aria-hidden="true"></i>
				All Videos
				<span class="tv-drawer__count"><?php echo count( $tv_drawer_videos ); ?></span>
			</h2>
			<button type="button" class="tv-drawer__close" data-tv-drawer-close aria-label="Close menu">
				<i class="fa-sharp fa-solid fa-xmark" aria-hidden="true"></i>
			</button>
		</div>
		<ul class="tv-drawer__list">
			<?php foreach ( $tv_drawer_videos as $tv_i => $tv_video ) :
				$tv_is_current = ( $tv_current_post_id === $tv_video->ID ); ?>
				<li>
					<a href="<?php echo esc_url( get_permalink( $tv_video->ID ) ); ?>"
					   class="tv-drawer__item<?php echo $tv_is_current ? ' is-current' : ''; ?>">
						<span class="tv-drawer__num"><?php echo (int) $tv_i + 1; ?></span>
						<span class="tv-drawer__label"><?php echo esc_html( $tv_video->post_title ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $tv_archive_url ) : ?>
			<div class="tv-drawer__foot">
				<a href="<?php echo esc_url( $tv_archive_url ); ?>" class="tv-drawer__archive-link">
					<i class="fa-sharp fa-solid fa-grid-2" aria-hidden="true"></i>
					View All Videos
				</a>
			</div>
		<?php endif; ?>
	</aside>

	<!-- Main Content Wrapper -->
	<main class="tv-main">
