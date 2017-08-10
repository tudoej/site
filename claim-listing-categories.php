<div id="main">
	<div class="section-head">
		<h1><?php echo $title; ?></h1>
	</div>

	<?php do_action( 'appthemes_notices' ); ?>

	<form id="create-listing" class="listing-categories claim-listing-categories" enctype="multipart/form-data" method="post" action="<?php echo $form_action; ?>">
		<?php wp_nonce_field( 'va_claim_listing_categories' ); ?>
		<input type="hidden" name="action" value="claim-listing-categories">
		<input type="hidden" name="ID" value="<?php the_ID(); ?>" />

		<fieldset id="category-fields">
			<div class="featured-head"><h3><?php printf( _n( 'Listing Category', 'Listing Categories', $included_categories, APP_TD ), $included_categories ); ?></h3></div>
		
			<div class="form-field" id="categories" <?php echo isset( $included_categories ) ? 'data-category-limit="' . esc_attr( $included_categories ) . '"' : '' ; ?>>
				<?php

				if ( ! isset( $included_categories ) || $categories_locked ) {
					$label = __( 'Categories', APP_TD );
				} else if ( $included_categories == 0 ) {
					$label = __( 'Categories (choose unlimited categories)', APP_TD );
				} else {
					$label = sprintf( _n( 'Category (choose %d category)', 'Categories (choose %d categories)', $included_categories, APP_TD ), $included_categories );
				}

				va_get_edit_categories( $listing, $label, VA_LISTING_CATEGORY, $categories_locked );
				?>
			</div>
			<div id="custom-fields"></div>

		</fieldset>

		<?php do_action( 'va_after_claim_listing_categories_form' ); ?>

		<fieldset>
			<div class="form-field"><input type="submit" value="<?php echo esc_attr( $action ); ?>" /></div>
		</fieldset>
	</form>
</div>