<div id="main">

	<?php appthemes_before_blog_loop(); ?>

	<?php while ( have_posts() ) : the_post(); ?>

	<?php appthemes_before_blog_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php appthemes_before_blog_post_title(); ?>

			<h1 class="post-heading"><span class="left-hanger"><?php the_title(); ?></span></h1>
			<?php comments_popup_link( "0", "1", "%", "comment-count" ); ?>

			<?php appthemes_after_blog_post_title(); ?>


		<section class="overview">
			<?php appthemes_before_blog_post_content(); ?>
			<?php the_content(); ?>
			<?php appthemes_after_blog_post_content(); ?>
		</section>

	<small><?php va_the_post_byline(); ?></small>
	<?php edit_post_link( __( 'Edit', APP_TD ), '<span class="edit-link">', '</span>' ); ?>

	<?php if ( function_exists( 'sharethis_button' ) && $va_options->blog_post_sharethis ): ?>
		<div class="sharethis"><?php sharethis_button(); ?></div>
	<?php endif; ?>

    <?php comments_template(); ?>

	</article>

	<?php appthemes_after_blog_post(); ?>

	<?php endwhile; ?>

	<?php appthemes_after_blog_loop(); ?>

</div><!-- /#main -->

<div id="sidebar" class="threecol last">
	<?php get_sidebar( app_template_base() ); ?>
</div>