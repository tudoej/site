<?php

// Listing columns
add_filter( 'manage_' . VA_LISTING_PTYPE . '_posts_columns', 'va_listing_manage_columns', 11 );
add_action( 'manage_' . VA_LISTING_PTYPE . '_posts_custom_column', 'va_listing_add_column_data', 10, 2 );
add_filter( 'manage_edit-' . VA_LISTING_PTYPE . '_sortable_columns', 'va_listing_columns_sort' );


function va_listing_manage_columns( $columns ) {
	$comments = $columns['comments'];
	$date = $columns['date'];

	unset($columns['date']);
	unset($columns['comments']);
	if ( !empty( $_GET['post_status'] ) && VA_LISTING_PTYPE == $_GET['post_type'] && 'pending-claimed' == $_GET['post_status'] ) {
		unset($columns['author']);
		$columns['claimee'] = __( 'Claimee', APP_TD );
	}

	$columns['expire'] = __( 'Expire Date', APP_TD );
	$columns['comments'] = $comments;
	$columns['date'] = $date;
	$columns['thumbnail'] = '';
	$columns['claimable'] = '';
	return $columns;
}

function va_listing_columns_sort($columns) {
	$columns['expire'] = 'expire';
	$columns['tax_listing_category'] = 'listing_category';
	return $columns;
}

function va_listing_add_column_data( $column_index, $post_id ) {
	switch ( $column_index ) {
	case 'expire' :
		$expiration_date = va_get_listing_exipration_date( $post_id );
		echo mysql2date( get_option('date_format'), $expiration_date );
		break;
	case 'thumbnail' :
		the_listing_thumbnail( $post_id );
		break;
	case 'claimee' :
		echo va_get_the_author_listings_link( get_post_meta( $post_id, 'claimee', true ) );
		break;
	case 'claimable' :
		echo '<input type="hidden" disabled="disabled" name="listing_claimable['.$post_id.']" value="'. ( 1 == get_post_meta( $post_id, 'listing_claimable', true ) ? 1 : '') .'" />';
		break;
	}
}

add_action( 'bulk_edit_custom_box', 'va_bulk_edit_claimee', 10, 2 );
function va_bulk_edit_claimee( $column_name, $post_type ) {
	switch ( $column_name ) {
	case 'claimable':
	?>
		<fieldset class="inline-edit-col-right inline-edit-<?php echo $column_name ?>">
			<div class="inline-edit-col inline-edit-<?php echo $column_name ?>">
				<div class="inline-edit-group">
					<label class="inline-edit-<?php echo $column_name; ?> alignleft">
						<select name="listing_claimable[bulk]">
							<option value="-1" selected="selected">No Change</option>
							<option value="1">Claimable</option>
							<option value="0">Not Claimable</option>
						</select>
						<span class="checkbox-title"><?php _e( 'Listing Claimable?' , APP_TD ); ?></span>
					</label>
				</div>
			</div>
		</fieldset>
	<?php
		break;
	}
}

add_action( 'quick_edit_custom_box', 'va_quick_edit_claimee', 10, 2 );
function va_quick_edit_claimee( $column_name, $post_type ) {
	switch ( $column_name ) {
	case 'claimable':
	?>
		<fieldset class="inline-edit-col-right inline-edit-<?php echo $column_name ?>">
			<div class="inline-edit-col inline-edit-<?php echo $column_name ?>">
				<div class="inline-edit-group">
					<label class="inline-edit-<?php echo $column_name; ?> alignleft">
						<input type="checkbox" name="listing_claimable" value="1" />
						<span class="checkbox-title"><?php _e( 'Listing Claimable?' , APP_TD ); ?></span>
					</label>
				</div>
			</div>
		</fieldset>
	<?php
		break;
	}
}

add_action( 'save_post', 'va_listing_bulk_save_categories' );
function va_listing_bulk_save_categories( $post_id ) {
	if ( empty( $_REQUEST['post_type'] ) || VA_LISTING_PTYPE !== $_REQUEST['post_type'] )
		return;

	if ( !current_user_can( 'edit_post', $post_id ) )
		return;

	if ( !isset( $_REQUEST['bulk_edit'] ) )
		return;

	if ( !empty( $_REQUEST['post'] ) && in_array( $post_id, $_REQUEST['post'] ) && !empty( $_REQUEST['tax_input'][ VA_LISTING_CATEGORY ] ) ) {

		$categories = $_REQUEST['tax_input'][ VA_LISTING_CATEGORY ];
		foreach( $categories as $k => $category ) {
			if ( empty( $category ) )
				unset( $categories[ $k ] );
		}

		va_set_listing_categories( $post_id, $categories );
	}

}

add_action( 'save_post', 'va_listing_bulk_save' );
function va_listing_bulk_save( $post_id ) {

	if ( empty( $_REQUEST['post_type'] ) || VA_LISTING_PTYPE !== $_REQUEST['post_type'] )
		return;

	if ( !current_user_can( 'edit_post', $post_id ) )
		return;

	if ( isset( $_REQUEST['listing_claimable']['bulk'] ) ) {
		if ( 1 == $_REQUEST['listing_claimable']['bulk'] ) {
			update_post_meta( $post_id, 'listing_claimable', 1 );
		} elseif( 0 == $_REQUEST['listing_claimable']['bulk'] ) {
			delete_post_meta( $post_id, 'listing_claimable', 1 );
		}
	} elseif( isset( $_REQUEST['_inline_edit'] ) ) {
		if ( isset( $_REQUEST['listing_claimable'] ) ) {
			update_post_meta( $post_id, 'listing_claimable', 1 );
		} else {
			delete_post_meta( $post_id, 'listing_claimable' );
		}
	}
}
