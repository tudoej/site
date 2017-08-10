<?php

add_action( 'appthemes_first_run', 'va_init_image_sizes' );
add_filter( 'intermediate_image_sizes_advanced', 'va_set_image_crop' );
add_filter( 'wp_get_attachment_image_attributes', 'va_attachment_attributes' );


/**
 * Initialize image sizes.
 *
 * @return void
 */
function va_init_image_sizes( $sizes ) {
	update_option( 'thumbnail_size_w', 50 );
	update_option( 'thumbnail_size_h', 50 );
	update_option( 'thumbnail_crop', true );

	update_option( 'medium_size_w', 230 );
	update_option( 'medium_size_h', 230 );

	va_legacy_image_update();
}


/**
 * Add custom meta '_va_attachment_type' to all existing images before Vantage 1.1
 *
 * @return void
 */
function va_legacy_image_update() {

	list( $args ) = get_theme_support( 'app-versions' );
	$previous_version = get_option( $args['option_key'] );

	if ( version_compare( $previous_version, '1.1.1', '<' ) ) {

		$args = array (
			'post_type' => 'attachment',
			'post_mime_type' => array( 'image/png', 'image/jpeg', 'image/gif' ),
			'nopaging' => true,
		);

		$images = get_posts( $args );
		foreach ( $images as $image ) {
			add_post_meta( $image->ID, '_va_attachment_type', VA_ATTACHMENT_GALLERY, true );
		}
	}
}


/**
 * Sets crop mode for images.
 *
 * @param array $sizes
 *
 * @return array
 */
function va_set_image_crop( $sizes ) {
	$sizes['thumbnail']['crop'] = true;
	$sizes['medium']['crop'] = true;

	return $sizes;
}


/**
 * Changes attributes of attachments.
 *
 * @param array $attr
 *
 * @return array
 */
function va_attachment_attributes( $attr ) {
	global $post;

	if ( ! empty( $post->post_type ) && VA_LISTING_PTYPE == $post->post_type ) {
		$attr['class'] = $attr['class'] . ' listing_thumbnail';
	}

	unset( $attr['title'] );

	return $attr;
}


/**
 * Updates the listing featured image after adding/deleting an gallery image
 *
 * @param int     $listing_id	The listing ID
 * @param int 	  $attach_id	(optional) The attachment ID
 * @param string  $type			(optional) The attachment type (gallery|file)
 *
 */
function va_update_featured_image( $listing_id, $attach_id = '', $type = VA_ATTACHMENT_GALLERY ) {

	// an attachment was deleted, get the next featured image candidate
	if ( ! $attach_id ) {
		$gallery = va_get_post_attachments( $listing_id, -1, $type, 'ids' );
		$attach_id = array_shift( $gallery );
	}

	// if user is uploading a gallery attachment, set the first available gallery image as the featured image
	if ( VA_ATTACHMENT_GALLERY == $type && $attach_id ) {
		set_post_thumbnail( $listing_id, $attach_id );
	}
}


/**
 * Returns attachment link.
 *
 * @param int $att_id
 * @param string $size (optional)
 *
 * @return string
 */
function va_get_attachment_link( $att_id, $size = 'thumbnail' ) {
	$alt = get_post_meta( $att_id, '_wp_attachment_image_alt', true );
	$url = wp_get_attachment_url( $att_id );

	if ( ! $alt ) {
		$alt = basename( get_post_meta( $att_id, '_wp_attached_file', true ) );
	}

	return html( 'a', array(
		'href' => $url,
		'rel' => 'colorbox',
		'title' => trim( strip_tags( $alt ) )
	), wp_get_attachment_image( $att_id, $size, false, array( 'alt' => $alt ) ) );
}


/**
 * Returns thumbnail for listing
 *
 * @param int $listing_id (optional)
 *
 * @return string
 */
function get_the_listing_thumbnail( $listing_id = '' ) {
	$listing_id = ( ! empty( $listing_id ) ) ? $listing_id : get_the_ID();

	$featured_id = get_post_thumbnail_id( $listing_id );

	if ( ! $featured_id || is_wp_error( $featured_id ) ) {
		$attachments = va_get_post_attachments( $listing_id, 1 );

		if ( empty( $attachments ) ) {
			return html( 'img', array( 'src' => appthemes_locate_template_uri( 'images/no-thumb-sm.jpg' ), 'class' => 'listing_thumbnail' ) );
		}

		$featured_id = $attachments[0]->ID;
	}

	return wp_get_attachment_image( $featured_id, 'thumbnail' );

}


/**
 * Displays thumbnail for listing.
 *
 * @param int $listing_id (optional)
 *
 * @return void
 */
function the_listing_thumbnail ( $listing_id = '' ) {

	$listing_id = ( ! empty( $listing_id ) ) ? $listing_id : get_the_ID();

	echo get_the_listing_thumbnail( $listing_id );
}


/**
 * Displays listing image gallery.
 *
 * @return void
 */
function the_listing_image_gallery() {
	$listing_id = get_the_ID();

	$attachments = va_get_post_attachments( $listing_id, VA_MAX_IMAGES );
	if ( empty( $attachments ) ) {
		return;
	}

	$featured_id = get_post_thumbnail_id( $listing_id );

	if ( ! $featured_id ) {
		$featured_id = $attachments[0]->ID;
	}

	echo '<section id="listing-images" class="tb">';

	echo html( 'div class="larger"', va_get_attachment_link( $featured_id, 'medium' ) );

	echo '<div class="smaller">';
	$i = 0;
	foreach ( $attachments as $attachment ) {
		if ( $i == VA_MAX_IMAGES - 1 ) {
			break;
		}

		if ( $attachment->ID == $featured_id ) {
			continue;
		}

		echo va_get_attachment_link( $attachment->ID );
		$i++;
	}
	echo '</div>';

	echo '</section>';
}


/**
 * Displays listing images editor.
 *
 * @param int $listing_id
 *
 * @return void
 */
function the_listing_image_editor( $listing_id ) {
	$images = va_get_post_attachments( $listing_id );

	$available_slots = VA_MAX_IMAGES - count( $images );

	echo '<ul class="uploaded">';

	foreach ( $images as $image ) :
		$meta = wp_get_attachment_metadata( $image->ID );

		if ( is_array( $meta ) && isset( $meta['width'] ) && isset( $meta['height'] ) ) {
			$media_dims = "<span id='media-dims-" . $image->ID . "'>" . $meta['width'] . '&nbsp;&times;&nbsp;' . $meta['height'] . "</span>";
		} else {
			$media_dims = '';
		}

		$alt = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
?>
		<li>
			<?php echo html( 'a', array(
				'href' => get_attachment_link( $image->ID ),
				'title' => $image->post_title,
				'target' => '_blank'
			), wp_get_attachment_image( $image->ID, 'thumbnail', false ) ); ?>

			<p class="image-delete"><label><input class="checkbox" type="checkbox" name="files_to_delete[]" value="<?php echo $image->ID; ?>">&nbsp;<?php _e( 'Delete Image', APP_TD ); ?></label></p>

			<p class="image-meta"><strong><?php _e( 'File Info:', APP_TD ); ?></strong> <?php echo $media_dims; ?> <?php echo $image->post_mime_type; ?></p>

			<p class="image-alt"><label>
				<?php _e( 'Description:', APP_TD ); ?>
				<input type="text" class="text" name="file_descriptions[<?php echo $image->ID; ?>]" value="<?php echo esc_attr( $alt ); ?>" />
			</label></p>
		</li>
<?php
	endforeach;

	echo '</ul>';

	echo '<ul class="uploadable">';

	if ( $available_slots > 0 ) {
		foreach ( range( 1, $available_slots ) as $i ) {
?>
		<li><input type="file" name="image_<?php echo $i; ?>" /></li>
<?php
		}
	}

	echo '</ul>';
}
