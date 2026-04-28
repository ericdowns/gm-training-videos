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
				<nav class="flex gap-2 items-center">
					<a href="<?php echo esc_url( home_url() ); ?>"
					   class="tv-header-link inline-flex items-center gap-2 px-3 py-2 text-white/80 hover:text-white transition-colors text-sm"
					   aria-label="Back to main site">
						<i class="fa-sharp fa-solid fa-arrow-left" aria-hidden="true"></i>
						<span>Back to Site</span>
					</a>

					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=training_videos' ) ); ?>"
						   class="tv-header-link inline-flex items-center gap-2 px-3 py-2 bg-white/20 text-white rounded hover:bg-white/30 transition-colors text-sm"
						   aria-label="Manage training videos in admin">
							<i class="fa-sharp fa-solid fa-gear" aria-hidden="true"></i>
							<span>Manage</span>
						</a>
					<?php endif; ?>
				</nav>
			</div>
		</div>
	</header>

	<!-- Main Content Wrapper -->
	<main class="tv-main">
