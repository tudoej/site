<div id="masthead" class="container">
	<div class="row">
		<div class="site-header">
			<?php if ( get_header_image() ) { ?>
				<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img src="<?php header_image(); ?>" class="header-image" width="<?php echo get_custom_header()->width; ?>" height="<?php echo get_custom_header()->height; ?>" alt="" />
				</a>
			<?php } elseif ( display_header_text() ) { ?>
				<h1 class="site-title">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
						<?php bloginfo( 'name' ); ?>
					</a>
				</h1>
			<?php } ?>
			<?php if ( display_header_text() ) { ?>
				<h2 id="site-description" class="site-description"><?php bloginfo( 'description' ); ?></h2>
			<?php } ?>
		</div>
		<?php if ( is_active_sidebar( 'va-header' ) ) : ?>
			<div class="advert">
				<?php appthemes_before_sidebar_widgets( 'va-header' ); ?>

				<?php dynamic_sidebar( 'va-header' ); ?>

				<?php appthemes_after_sidebar_widgets( 'va-header' ); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
<div id="main-navigation" class="container">
	<div class="row">
		<div id="rounded-nav-box" class="rounded">
			<div id="rounded-nav-box-overlay">
				<?php va_display_navigation_menu(); ?>
				<?php if ( false !== apply_filters('va_show_search_controls', true ) ) : ?>
				<form method="get" action="<?php echo trailingslashit( home_url() ); ?>">
					<div id="main-search">
						<div class="search-for">
							<div>
								<label for="search-text" class="search-title"><?php _e( 'Search For ', APP_TD ); ?></label>
								<?php do_action('va_search_for_above'); ?>
							</div>
							<div class="input-cont h39">
								<div class="left h39"></div>
								<div class="mid h39">
									<input type="text" name="ls" id="search-text" class="text" value="<?php va_show_search_query_var( 'ls' ); ?>" />
								</div>
								<div class="right h39"></div>
							</div>
						</div>

						<div class="search-location">
							<label for="search-location">
								<span class="search-title"><?php _e( 'Near ', APP_TD ); ?></span>
								<span class="search-help"><?php _e( '(city, country)', APP_TD ); ?></span>
							</label>
							<div class="input-cont h39">
								<div class="left h39"></div>
								<div class="mid h39">
									<input type="text" name="location" id="search-location" class="text" value="<?php va_show_search_query_var( 'location' ); ?>" />
								</div>
								<div class="right h39"></div>
							</div>
						</div>

						<div class="search-button">
							<button type="submit" id="search-submit" class="rounded-small"><?php _e( 'Search', APP_TD ); ?></button>
						</div>
					</div>
					<?php the_search_refinements(); ?>
				</form>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<div id="breadcrumbs" class="container">
	<div class="row">
		<?php breadcrumb_trail( array(
			'separator' => '&raquo;',
			'show_browse' => false,
			'labels' => array(
				'home' => '<img src="' . appthemes_locate_template_uri( 'images/breadcrumb-home.png' ) . '" />',
			),
		) ); ?>
	</div>
</div>

