<?php

add_action( 'save_post', 'va_event_bulk_save_categories' );
function va_event_bulk_save_categories( $post_id ) {
	if ( empty( $_REQUEST['post_type'] ) || VA_EVENT_PTYPE !== $_REQUEST['post_type'] )
		return;

	if ( !current_user_can( 'edit_post', $post_id ) )
		return;

	if ( !isset( $_REQUEST['bulk_edit'] ) )
		return;

	if ( !empty( $_REQUEST['post'] ) && in_array( $post_id, $_REQUEST['post'] ) && !empty( $_REQUEST['tax_input'][ VA_EVENT_CATEGORY ] ) ) {

		$categories = $_REQUEST['tax_input'][ VA_EVENT_CATEGORY ];
		foreach( $categories as $k => $category ) {
			if ( empty( $category ) )
				unset( $categories[ $k ] );
		}

		va_set_event_categories( $post_id, $categories );
	}

}
