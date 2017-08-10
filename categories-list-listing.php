<?php
// Template Name: Listing Categories
?>

<div id="main">
	<div class="section-head">
		<h1><?php _e( 'Listing Categories', APP_TD ); ?></h1>
	</div>

	<div class="categories-list">
      <?php
        echo va_cat_menu_drop_down( 'dir', VA_LISTING_CATEGORY );
      ?>
	</div>

</div>

<div id="sidebar">
	<?php get_sidebar( app_template_base() ); ?>
</div>
