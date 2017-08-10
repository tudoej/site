<div id="main">

	<?php appthemes_before_page_loop(); ?>

	<?php while ( have_posts() ) : the_post(); ?>

	<?php appthemes_before_page(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php appthemes_before_page_title(); ?>

		<header class="section-head">
			<h1><?php the_title(); ?></h1>
		</header>

		<?php appthemes_after_page_title(); ?>

		<section id="overview">

			<?php appthemes_before_page_content(); ?>

			<?php the_content(); ?>

			<?php appthemes_after_page_content(); ?>

		</section>

		<?php edit_post_link( __( 'Edit', APP_TD ), '<span class="edit-link">', '</span>' ); ?>

		<?php comments_template(); ?>

	</article>

	<?php appthemes_after_page(); ?>

	<?php endwhile; ?>

	<?php appthemes_after_page_loop(); ?>

</div><!-- /#main -->

<div id="sidebar" class="threecol last">
	<?php get_sidebar( app_template_base() ); ?>
</div>