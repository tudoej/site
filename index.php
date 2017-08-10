<?php
// Template Name: Blog
?>

<div id="main" class="list">
	<div class="section-head">
		<h1><?php echo get_blog_page_title(); ?></h1>
	</div>

	<?php va_the_archive_description( '<div class="taxonomy-description">', '<div class="clear"></div></div>' ); ?>

<?php appthemes_before_blog_loop(); ?>
<?php while ( have_posts() ) : the_post(); ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php appthemes_before_blog_post_title(); ?>
		<h2 class="post-heading"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
		<?php comments_popup_link( "0", "1", "%", "comment-count" ); ?>
		<?php appthemes_after_blog_post_title(); ?>

		<section class="overview">
			<?php appthemes_before_blog_post_content(); ?>
			<?php the_content(); ?>
			<?php appthemes_after_blog_post_content(); ?>
        </section>
		<small><?php va_the_post_byline(); ?></small>
	</article>
<?php endwhile; ?>
<?php appthemes_after_blog_loop(); ?>

<?php if ( $wp_query->max_num_pages > 1 ) : ?>
	<nav class="pagination">
		<?php appthemes_pagenavi(); ?>
	</nav>
<?php endif; ?>
</div>

<div id="sidebar">
	<?php get_sidebar( app_template_base() ); ?>
</div>