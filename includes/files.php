<?php
add_filter( 'va_render_form_field' , 'va_display_allowed_extensions', 10, 4 );
add_filter( 'va_render_form_field', 'va_hide_file_upload', 10, 3 );


/**
 * Displays the allowed extensions for a specific custom form field.
 *
 * @param string $html The HTML code to be displayed
 * @param array $field The field being displayed
 * @param int $listing_id The listing ID
 * @param int $cat (optional) The category ID related to the field
 *
 * @return string
 */
function va_display_allowed_extensions( $html, $field, $listing_id, $cat = '' ) {

	if ( isset( $field['type'] ) && 'file' == $field['type']  ) {

		$html  = html( 'div class="form-field file-field"', scbForms::input_from_meta( $field, $listing_id ) );
		$html .= html( 'div', array(
			'class' => 'field-info',
		), sprintf( __( 'Allowed extensions: %1$s', APP_TD ), $field['extensions'] ) );

	}

	return $html;
}


/**
 * Limit file uploads on a custom form field by hidding/clearing the HTML output.
 *
 * @param string  $html			The field HTML output
 * @param array	  $field		The field associative array
 * @param int     $listing_id	The listing ID
 *
 * @return string Returns the original or modified field HTML ouput
 */
function va_hide_file_upload( $html, $field, $listing_id ) {
	$limit = 1;
	if ( 'file' == $field['type'] ) {
		if ( va_field_uploads( $listing_id, $field ) >= $limit ) {
			$html = '';
		}
	}
	return $html;
}


/**
 * Retrieves the valid mime types for a file or gallery upload
 *
 * @param array   $file				The file being uploaded
 * @param array	  $valid_extensions	(optional) Valid file extensions
 *
 * @return array  Returns a list of valid mime types
 */
function va_valid_mime_types( $file, $valid_extensions = array() ) {

	if ( ! is_array( $valid_extensions ) ) {
		$valid_extensions = explode( ',', $valid_extensions );
	}

	if ( empty( $valid_extensions ) ) {

		// limit gallery uploads to image mime types
		$mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'tif|tiff'     => 'image/tiff',
			'ico'          => 'image/x-icon',
		);

	} else {

		// use the extension and mime type from the uploaded file, if valid
		if ( in_array( $file['extension'], $valid_extensions ) ) {
			$mimes = array( $file['extension'] => $file['type'] );
		} else {
			$mimes = array( 'invalid' => 'invalid' );
		}

	}

	return $mimes;
}


/**
 * Queries the database for attachments from custom fields or gallery
 * Uses the meta key '_va_attachment_type' to filter the available attachment types: gallery | file
 *
 * @param int  	  $post_id The post ID
 * @param int     $how_many (optional) The number of attachments to retrieve
 * @param string  $type     (optional) The type of attachment to return (gallery|file)
 * @param string  $fields   (optional) The fields to be returned
 *
 * @return array
 */
function va_get_post_attachments( $post_id, $how_many = -1, $type = VA_ATTACHMENT_GALLERY, $fields = '' ) {
	if ( ! $post_id ) {
		return array();
	}

	// In case if featured attachement has different post parent
	$feat_attachement = array();

	if ( VA_ATTACHMENT_GALLERY == $type ) {
			// display gallery images
			$meta_query = array(
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => '_va_attachment_type',
						'value'   => VA_ATTACHMENT_FILE,
						'compare'	=> '!=',
					),
					array(
						'key'     => '_va_attachment_type',
						'value'   => 'dummy', // added dummy text since empty string or NULL won't work with 'NOT EXISTS'
						'compare'	=> 'NOT EXISTS'
					),
				),
			);

			$featured_id = get_post_thumbnail_id( $post_id );

			if ( $featured_id && ! is_wp_error( $featured_id ) ) {
				$feat_post = get_post( $featured_id );
				if ( $feat_post->post_parent != $post_id ) {
					$feat_attachement[] = $feat_post;
				}
			}

	} else {
			// display file attachments only
			$meta_query = array(
					'meta_key'		=> '_va_attachment_type',
					'meta_value'	=> $type,
			);

	}

	$args = array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_parent'    => $post_id,
		'posts_per_page' => $how_many,
		'orderby'        => 'menu_order',
		'order'          => 'asc',
		'fields'         => $fields,
		'update_post_term_cache' => false,
	);
	$args = array_merge( $args, $meta_query );

	$results = get_posts( $args );

	return array_merge( $feat_attachement, $results );
}


/**
 * Collects and returns the meta info for a specific attachment ID.
 *
 * @param int $attachment_id The attachment ID
 *
 * @return array Returns the attachment meta
 */
function va_get_file_meta( $attachment_id ) {
	$filename = wp_get_attachment_url( $attachment_id );

	$title = trim( strip_tags( get_the_title( $attachment_id ) ) );
	$basename = basename( $filename );
	$size = size_format( filesize( get_attached_file( $attachment_id ) ), 2 );

	$meta = array(
		'title'     => ( ! $title ? $basename : $title ),
		'url'       => $filename,
		'mime_type' => get_post_mime_type( $attachment_id ),
		'size'      => $size,
	);
	return $meta;
}


/**
 * Returns the file link for a specific attachment ID.
 *
 * @param int $attachment_id The attachment ID
 *
 * @return string Returns the HTML link
 */
function va_get_file_link( $attachment_id ) {

	$file = va_get_file_meta( $attachment_id );

	$link = html( 'a', array(
		'href' => $file['url'],
		'title' => $file['title'],
		'target' => '_blank',
	), $file['title'] );

	return html( 'div', array(
		'class' => 'file-extension ' . va_get_mime_type_icon( $file['mime_type'] ),
	), $link );
}


/**
 * Reads $_FILES data to separate custom form fields uploads from gallery uploads.
 *
 * @param array   $categories The categories
 * @param string  $taxonomy   The taxonomy
 *
 * @return array  Returns the list of custom form file fields in $_FILES
 */
function va_get_custom_form_file_fields( $categories, $taxonomy ) {

	$file_fields = array();

	foreach ( $categories as $category ) {
		foreach ( va_get_fields_for_cat( $category, $taxonomy ) as $field ) {
			if ( 'file' == $field['type'] && isset( $_FILES[ $field['name'] ] ) && ! $_FILES[ $field['name'] ]['error'] )
				$file_fields[ $field['name'] ] = $field;
		}
	}

	return $file_fields;
}


/**
 * Returns the related icon filename for a mime-type
 *
 * @param string  $mime_type
 *
 * @return string  Returns the mime type icon file name
 */
function va_get_mime_type_icon( $mime_type ) {

	if ( ! $mime_type ) {
		$mime_type = 'generic';
	}

	$file_ext_ico = array (
		'pdf'          => 'file-pdf',
		'msword'       => 'file-word',
		'vnd.ms-excel' => 'file-excel',
		'csv'          => 'file-excel',
		'image'        => 'file-image',
		'other'        => 'file-other',
	);

	$mime_type = explode( '/' , $mime_type );
	if ( is_array( $mime_type ) ) {
		// simplify the mime match for image types by using the 'image' part (i.e: image/png, image/jpg, etc)
		$mime_type = ( 'image' == $mime_type[0] ? $mime_type[0] : $mime_type[1] );
	}

	if ( ! isset( $file_ext_ico[ $mime_type ] ) ) {
		$mime_type = 'other';
	}

	return apply_filters( 'va_mime_type_icon', $file_ext_ico[ $mime_type ], $mime_type );
}

/**
 * Deletes $_POST data files.
 *
 * @param int  $listing_id  The listing ID
 *
 * @return array The deleted files list
 */
function va_handle_delete_files( $listing_id ) {

	$deleted_files = array();
	if ( isset( $_POST['files_to_delete'] ) ) {
		array_walk( $_POST['files_to_delete'], 'wp_delete_attachment' );
		$deleted_files = $_POST['files_to_delete'];

		va_update_featured_image( $listing_id );
	}

	return $deleted_files;
}

/**
 * Updates $_POST data file descriptions.
 *
 * @param array  $files_to_skip	(optional) List of files to skip the update
 *
 * @return void
 */
function va_handle_file_descriptions( $files_to_skip = array() ) {

	if ( isset( $_POST['file_descriptions'] ) && is_array( $_POST['file_descriptions'] ) ) {
		$i = 0;

		foreach ( $_POST['file_descriptions'] as $file_id => $desc ) {
			if ( in_array( $file_id, $files_to_skip ) ) {
				continue;
			}

			$mime_type = get_post_mime_type( $file_id );
			$type = explode( '/', $mime_type );

			$value = strip_tags( stripslashes( $desc ) );

			// update the attachment
			$post = array();
			$post['ID'] = $file_id;
			$post['post_title'] = $value;
			$post['menu_order'] = $i++;

			// update the post in the database
			wp_update_post( $post );

			// update additional meta for images
			if ( 'image' == $type[0] ) {
				update_post_meta( $file_id, '_wp_attachment_image_alt', $value );
			}

		}

	}

}


/**
 * Handles uploaded files
 *
 * @param int $listing_id	The listing ID
 * @param int $listing_cat The listing category ID
 *
 * @return void
 */
function va_handle_files( $listing_id, $listing_cat, $taxonomy ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	require_once ABSPATH . '/wp-admin/includes/image.php';
	require_once ABSPATH . '/wp-admin/includes/media.php';

	if ( ! isset( $_FILES ) ) {
		return;
	}

	$errors = va_get_listing_error_obj();

	$deleted_files = va_handle_delete_files( $listing_id );
	va_handle_file_descriptions( $files_to_skip = $deleted_files );

	// get the custom form file fields
	$cust_form_fields = va_get_custom_form_file_fields( $listing_cat, $taxonomy );

	$files = va_get_post_attachments( $listing_id, -1, VA_ATTACHMENT_FILE, 'ids' );
	$gallery = va_get_post_attachments( $listing_id );

	$count_gallery = count( $gallery );
	$count_files = count( $files );
	$menu_order = $count_files + $count_gallery;

	$attachments = array (
		VA_ATTACHMENT_GALLERY => array(
			'text'    	=> __( 'gallery', APP_TD ),
			'current' 	=> $count_gallery,
			'limit'	  	=> VA_MAX_IMAGES,
			'left' 	  	=> VA_MAX_IMAGES - $count_gallery,
			'valid_ext' => array(),
		),
	);

	// iterate through the uploaded files (gallery/files)
	foreach ( $_FILES as $key => $file ) {

		if ( ! $file['name'] ) {
			continue;
		}

		// check for a file OR gallery upload
		if ( isset( $cust_form_fields[ $key ] ) ) {
			$type = VA_ATTACHMENT_FILE;
		} else {
			$type = VA_ATTACHMENT_GALLERY;
		}

		if ( VA_ATTACHMENT_FILE == $type ) {
			$field_uploads = va_field_uploads( $listing_id, $cust_form_fields[ $key ], $files );
			$attachments[ VA_ATTACHMENT_FILE ] = array(
				'text'      => __( 'attachment', APP_TD ),
				'limit'	    => 1,
				'current'   => $field_uploads,
				'left' 	    => 1 - $field_uploads,
				'valid_ext' => $cust_form_fields[ $key ]['extensions'],
			);
		}

		// skip the image/file if there's no available slots
		if ( ! $attachments[ $type ]['left'] ) {
			$errors->add( 'upload_limit',
				sprintf( __( 'Maximum %1$s uploads reached [max: %2$s]', APP_TD ), $attachments[ $type ]['text'], $attachments[ $type ]['limit'] )
			);
			continue;
		}

		$file['extension'] = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$valid_mimes = va_valid_mime_types( $file, $attachments[ $type ]['valid_ext'] );

		$file_id = media_handle_upload( $key, $listing_id, $post_data = array(), array( 'test_form' => false, 'mimes' => $valid_mimes ) );
		if ( is_wp_error( $file_id ) ) {
			$errors->add( $file_id->get_error_code(), $file_id->get_error_message() );
			$errors->add( 'upload_mime_warning', sprintf( __( 'Please make sure you are uploading a valid %1$s file type (uploaded file type: %2$s)', APP_TD ), $attachments[ $type ]['text'], '*.' . $file['extension'] ) );
			continue;
		}

		if ( VA_ATTACHMENT_FILE == $type ) {
			update_post_meta( $file_id, '_va_field_' . $cust_form_fields[ $key ]['name'], 1 );
		}

		set_post_field( 'menu_order', $menu_order, $file_id );

		update_post_meta( $file_id, '_va_attachment_type', $type );
		$attachments[ $type ]['left']--;
	}

	va_update_featured_image( $listing_id );

}


/**
 * Retrieves the number of uploads for a listing file field
 *
 * @param int     $listing_id		The listing ID
 * @param array	  $field			The field associative array
 * @param array	  $attacments		(optional) The listing attachments
 *
 * @return int     Returns the total uploads for a file field
 */
function va_field_uploads( $listing_id, $field, $attachments = array() ) {

	$total = 0;
	if ( empty ( $attachments ) ) {
		$attachments = va_get_post_attachments( $listing_id, -1, VA_ATTACHMENT_FILE, 'ids' );
	}

	foreach ( $attachments as $attach_id ) {
		$total = get_post_meta( $attach_id, '_va_field_' . $field['name'], true );
		if ( $total ) {
			break;
		}
	}
	return $total;
}

/**
 * Displays the files list for a listing/event/post_type
 *
 * @return void
 */
function va_the_files_list() {
	$post_id = get_the_ID();
	$post_type = get_post_type( $post_id );
	$post_type_obj = get_post_type_object( $post_type );

	$attachments = va_get_post_attachments( $post_id, -1, VA_ATTACHMENT_FILE );

	if ( empty( $attachments ) ) {
		return;
	}

	echo '<section id="' . $post_type . '-files">';

	echo '<div class="' . $post_type . '-files">';

	echo sprintf( __( '%s Attachments:', APP_TD ), $post_type_obj->labels->singular_name );


	foreach ( $attachments as $attachment ) {
		echo va_get_file_link( $attachment->ID );
	}

	echo '</div>';

	echo '</section>';

}


/**
 * Displays the listing file editor.
 *
 * @param int $listing_id  The listing ID
 * @param string $label (optional)
 *
 * @return void
 */
function the_files_editor( $listing_id, $label = '' ) {
	$attachments = va_get_post_attachments( $listing_id, $limit = -1, VA_ATTACHMENT_FILE );

	if ( ! $attachments ) {
		return;
	}

	echo '<div class="form-field">';

	echo html( 'label', array(), $label );

	echo '<ul class="uploaded file-editor">';

	foreach ( $attachments as $attachment ) {
		$meta = wp_get_attachment_metadata( $attachment->ID );

		$file = va_get_file_meta( $attachment->ID );
?>
		<li>
			<p class="file-delete"><label><input class="checkbox" type="checkbox" name="files_to_delete[]" value="<?php echo $attachment->ID; ?>">&nbsp;<?php _e( 'Delete File', APP_TD ); ?></label></p>

			<?php echo va_get_file_link( $attachment->ID ); ?>

			<p class="file-meta"><strong><?php _e( 'File Info:', APP_TD ); ?></strong> <?php echo $file['size']; ?> <?php echo $attachment->post_mime_type; ?></p>

			<p class="file-title"><label>
				<?php _e( 'Description:', APP_TD ); ?>
				<input type="text" class="text" name="file_descriptions[<?php echo $attachment->ID; ?>]" value="<?php echo esc_attr( $file['title'] ); ?>" />
			</label></p>

		</li>
<?php
	}

	echo '</ul>';

	echo '</div>';
}
