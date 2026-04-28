	</main>
	<!-- End Main Content Wrapper -->

	<!-- Training Portal Footer -->
	<footer class="bg-navy text-white py-8 mt-12">
		<div class="mx-auto px-6 lg:px-8" style="max-width: 1400px;">
			<div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
				<!-- Stats -->
				<div class="text-white/60">
					<?php
					$count       = wp_count_posts( 'training_videos' );
					$video_count = $count->publish;
					?>
					<i class="fa-sharp fa-solid fa-video mr-2"></i>
					<?php echo $video_count; ?> Training <?php echo 1 === $video_count ? 'Video' : 'Videos'; ?>
				</div>

				<!-- Support -->
				<div class="text-white/60">
					Need help?
					<a href="mailto:hello@grainandmortar.com" class="text-orange hover:text-white transition-colors ml-1">
						Contact Support
					</a>
				</div>

				<!-- Credits -->
				<div class="text-white/60">
					Built by
					<a href="https://grainandmortar.com" target="_blank" rel="noopener" class="text-orange hover:text-white transition-colors">
						Grain & Mortar
					</a>
				</div>
			</div>

			<?php if ( is_user_logged_in() ) : ?>
				<div class="mt-6 pt-4 border-t border-white/10 text-center text-xs text-white/40">
					Logged in as <span class="text-white/60"><?php echo esc_html( wp_get_current_user()->display_name ); ?></span>
					<span class="mx-2">|</span>
					<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="text-orange hover:text-white transition-colors">Logout</a>
				</div>
			<?php endif; ?>
		</div>
	</footer>

	<!-- Mark video as viewed (on single pages) -->
	<?php if ( is_singular( 'training_videos' ) ) : ?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const videoId = <?php echo get_the_ID(); ?>;
		const viewedVideos = JSON.parse(localStorage.getItem('training_viewed') || '[]');
		if (!viewedVideos.includes(videoId)) {
			viewedVideos.push(videoId);
			localStorage.setItem('training_viewed', JSON.stringify(viewedVideos));
		}
	});
	</script>
	<?php endif; ?>

	<script>
	/* Mobile drawer toggle. Hamburger opens, backdrop/close/Escape close. */
	(function () {
		var toggle   = document.querySelector( '.tv-drawer-toggle' );
		var drawer   = document.getElementById( 'tv-drawer' );
		var backdrop = document.querySelector( '.tv-drawer-backdrop' );
		if ( ! toggle || ! drawer ) { return; }

		function isOpen() { return drawer.getAttribute( 'aria-hidden' ) === 'false'; }

		function open() {
			drawer.setAttribute( 'aria-hidden', 'false' );
			if ( backdrop ) { backdrop.setAttribute( 'aria-hidden', 'false' ); }
			toggle.setAttribute( 'aria-expanded', 'true' );
			document.body.classList.add( 'tv-drawer-open' );
			var firstFocusable = drawer.querySelector( 'a, button' );
			if ( firstFocusable ) { firstFocusable.focus(); }
		}

		function close() {
			drawer.setAttribute( 'aria-hidden', 'true' );
			if ( backdrop ) { backdrop.setAttribute( 'aria-hidden', 'true' ); }
			toggle.setAttribute( 'aria-expanded', 'false' );
			document.body.classList.remove( 'tv-drawer-open' );
			toggle.focus();
		}

		toggle.addEventListener( 'click', function () {
			isOpen() ? close() : open();
		} );

		var closers = document.querySelectorAll( '[data-tv-drawer-close]' );
		Array.prototype.forEach.call( closers, function ( el ) {
			el.addEventListener( 'click', close );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && isOpen() ) { close(); }
		} );
	})();
	</script>

	<?php
	// Include WordPress footer scripts
	wp_footer();
	?>
</body>
</html>
