<?php
/**
 * Functions used in the Templates and Loops
 *
 * @package Vantage\TemplateTags
 * @author  AppThemes
 * @since   Vantage 1.0
 */


/**
 * Displays reviews count.
 *
 * @param int $listing_id (optional)
 *
 * @return void
 */
function the_review_count( $listing_id = '' ) {
	$review_count = va_get_reviews_count( $listing_id );

	echo sprintf( _n( '1 review', '%s reviews', $review_count, APP_TD ), number_format_i18n( $review_count ) );
}


/**
 * Displays listing address.
 *
 * @param int $listing_id (optional)
 *
 * @return void
 */
function the_listing_address( $listing_id = '' ) {
	$listing_id = ! empty( $listing_id ) ? $listing_id : get_the_ID();

	echo esc_html( get_post_meta( $listing_id, 'address', true ) );
}


/**
 * Displays listing tags.
 *
 * @param string $before
 * @param string $sep
 * @param string $after
 *
 * @return void
 */
function the_listing_tags( $before = null, $sep = ', ', $after = '' ) {
	if ( null === $before ) {
		$before = __( 'Tags: ', APP_TD );
	}
	echo get_the_term_list( 0, VA_LISTING_TAG, $before, $sep, $after );
}


/**
 * Displays listing categories.
 *
 * @param int $listing_id (optional)
 *
 * @return void
 */
function the_listing_categories( $listing_id = 0 ) {

	$listing_id = $listing_id ? $listing_id : get_the_ID();

	$cats = get_the_listing_categories( $listing_id );
	if ( ! $cats ) {
		return;
	}

	$_cats = array();

	foreach ( $cats as $cat ) {
		$_cats[] = html_link( get_term_link( $cat ), $cat->name );
	}

	$cats_list = implode( ', ', $_cats );

	printf( __( 'Listed in %s', APP_TD ), $cats_list );
}


/**
 * Displays listing form.
 *
 * @param int $listing_id
 * @param array|int $categories
 *
 * @return void
 */
function va_listing_render_form( $listing_id, $categories ) {
	$listing_categories = array();

	if ( is_array( $categories ) ) {
		$listing_categories = array_keys( $categories );
	} else {
		$listing_categories[] = $categories;
	}

	va_render_form( $listing_categories, VA_LISTING_CATEGORY, $listing_id );
}


/**
 * Displays listing fields.
 *
 * @param int $listing_id (optional)
 *
 * @return void
 */
function the_listing_fields( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	$cats = array_keys( get_the_listing_categories( $listing_id ) );
	if ( ! $cats ) {
		return;
	}

	$fields = array();
	foreach ( $cats as $cat ) {
		foreach ( va_get_fields_for_cat( $cat, VA_LISTING_CATEGORY ) as $field ) {
			$fields[ $field['name'] ] = $field;
		}
	}

	foreach ( $fields as $field ) {
		if ( 'checkbox' === $field['type'] ) {
			$value = implode( ', ', get_post_meta( $listing_id, $field['name'] ) );
		} else {
			$value = get_post_meta( $listing_id, $field['name'], true );
		}

		if ( ! $value ) {
			continue;
		}

		$wrapper = 'span';

		if ( 'textarea' === $field['type'] ) {
			$wrapper = 'div';
			$value = nl2br( $value );
		}

		$field['id_tag'] = va_make_custom_field_id_tag( $field['name'] );

		echo html( 'div', array( 'class' => 'listing-custom-field', 'id' => $field['id_tag'] ),
			html( 'span', array( 'class' => 'custom-field-label' ), $field['desc'] ). html( 'span', array( 'class' => 'custom-field-sep' ), ': ' ) . html( $wrapper, array( 'class' => 'custom-field-value' ), $value ) );
	}
}


/**
 * Returns generated ID for custom field.
 *
 * @param string $id_tag
 * @param string $prefix (optional)
 *
 * @return string
 */
function va_make_custom_field_id_tag( $id_tag, $prefix = 'listing-custom-field-' ) {
	return esc_attr( $prefix . sanitize_title_with_dashes( $id_tag ) );
}


/**
 * Displays a post info - date, author, and category.
 *
 * @return void
 */
function va_the_post_byline() {
	// Can't use the_date() because it only shows up once per date
	printf( __( '%1$s | %2$s %3$s', APP_TD ),
		get_the_time( get_option( 'date_format' ) ),
		va_get_author_posts_link(),
		get_the_category_list()
	);
}


/**
 * Returns listing categories ids.
 *
 * @param int $listing_id (optional)
 *
 * @return array
 */
function get_the_listing_categories( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	$_terms = get_the_terms( $listing_id, VA_LISTING_CATEGORY );

	if ( ! $_terms ) {
		return array();
	}

	// WordPress does not always key with the term_id, but thats what we want for the key.
	$terms = array();
	foreach ( $_terms as $_term ) {
		$terms[ $_term->term_id ] = $_term;
	}

	return $terms;
}


/**
 * Displays listing edit link.
 *
 * @param int $listing_id (optional)
 * @param string $text (optional)
 *
 * @return void
 */
function the_listing_edit_link( $listing_id = 0, $text = '' ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	if ( ! current_user_can( 'edit_post', $listing_id ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Edit Listing', APP_TD );
	}

	echo html( 'a', array(
		'class' => 'listing-edit-link',
		'href' => va_get_listing_edit_url( $listing_id ),
	), $text );
}


/**
 * Displays listing renew link.
 *
 * @param int $listing_id (optional)
 * @param string $text (optional)
 *
 * @return void
 */
function the_listing_renew_link( $listing_id = 0, $text = '' ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	if ( ! current_user_can( 'edit_post', $listing_id ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Renew Listing', APP_TD );
	}

	echo html( 'a', array(
		'class' => 'listing-edit-link listing-renew-link',
		'href' => va_get_listing_renew_url( $listing_id ),
	), $text );
}


/**
 * Displays listing claimable link.
 *
 * @param int $listing_id (optional)
 * @param string $text (optional)
 *
 * @return void
 */
function the_listing_claimable_link( $listing_id = '', $text = '' ) {
	$listing_id = ! empty( $listing_id ) ? $listing_id : get_the_ID();

	if ( ! _va_is_claimable( $listing_id ) ) {
		return;
	}

	if ( get_post_status( $listing_id ) == 'pending-claimed' ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Claim Listing', APP_TD );
	}

	echo html( 'a', array(
		'class' => 'listing-claim-link',
		'href' => va_get_listing_claim_url( $listing_id ),
	), $text );
}


/**
 * Returns listing edit url.
 *
 * @param int $listing_id
 *
 * @return string
 */
function va_get_listing_edit_url( $listing_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->edit_listing_permalink;
		return home_url( user_trailingslashit( "$listing_permalink/$permalink/$listing_id" ) );
	}

	return home_url( "?listing_edit=$listing_id" );
}


/**
 * Returns listing renew url.
 *
 * @param int $listing_id
 *
 * @return string
 */
function va_get_listing_renew_url( $listing_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->renew_listing_permalink;
		return home_url( user_trailingslashit( "$listing_permalink/$permalink/$listing_id" ) );
	}

	return home_url( "?listing_renew=$listing_id" );
}


/**
 * Displays listing purchase link.
 *
 * @param int $listing_id (optional)
 * @param string $text (optional)
 *
 * @return void
 */
function the_listing_purchase_link( $listing_id = 0, $text = '' ) {
	global $va_options;

	if ( ! $va_options->listing_charge ) {
		return;
	}

	if ( ! va_any_featured_addon_enabled() ) {
		return;
	}

	$listing_id = $listing_id ? $listing_id : get_the_ID();

	if ( ! current_user_can( 'edit_post', $listing_id ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Upgrade Listing', APP_TD );
	}

	$prior_plan = _va_get_last_plan_info( $listing_id );

	if ( $prior_plan && _va_no_featured_purchasable( $prior_plan, get_post( $listing_id ) ) ) {
		return;
	}

	echo html( 'a', array(
		'class' => 'listing-edit-link',
		'href' => va_get_listing_purchase_url( $listing_id ),
	), $text );
}


/**
 * Returns listing purchase url.
 *
 * @param int $listing_id
 *
 * @return string
 */
function va_get_listing_purchase_url( $listing_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->purchase_listing_permalink;
		return home_url( user_trailingslashit( "$listing_permalink/$permalink/$listing_id" ) );
	}

	return home_url( "?listing_purchase=$listing_id" );
}


/**
 * Returns listing claim url.
 *
 * @param int $listing_id
 *
 * @return string
 */
function va_get_listing_claim_url( $listing_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->claim_listing_permalink;
		return home_url( user_trailingslashit( "$listing_permalink/$permalink/$listing_id" ) );
	}

	return home_url( "?listing_claim=$listing_id" );
}


/**
 * Displays listing faves link.
 *
 * @param int $listing_id (optional)
 *
 * @return void
 */
function the_listing_faves_link( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();
	va_display_fave_button( $listing_id );
}

/**
 * Displays listing delete link.
 *
 * @param int $listing_id (optional)
 */
function the_listing_delete_link( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();
	va_display_delete_listing_button( $listing_id );
}

/**
 * Returns a listing create url.
 *
 * @return string
 */
function va_get_listing_create_url() {
	return get_permalink( VA_Listing_Create::get_id() );
}


/**
 * Displays listing star rating.
 *
 * @param int $post_id (optional)
 *
 * @return void
 */
function the_listing_star_rating( $post_id = '' ) {
	$rating = str_replace( '.', '_', va_get_rating_average( $post_id ) );

	if ( '' == $rating ) {
		$rating = '0';
	}
?>
		<div class="stars-cont">
			<div class="stars stars-<?php echo $rating; ?>"></div>
		</div>
		<meta itemprop="worstRating" content="1" />
		<meta itemprop="bestRating" content="5" />
		<meta itemprop="ratingValue" content="<?php echo esc_attr( $rating ); ?>" />
		<meta itemprop="reviewCount" content="<?php echo esc_attr( va_get_reviews_count( $post_id ) ); ?>" />
<?php
}


/**
 * Displays field for refine distance.
 *
 * @return void
 */
function the_refine_distance_ui() {
	global $va_options, $wp_query;

	$current_radius = (int) get_query_var( 'radius' );

	$geo_query = $wp_query->get( 'app_geo_query' );

	$current_radius = $geo_query['rad'];

	extract( va_calc_radius_slider_controls( $current_radius ) );

?>
<label>
	<input name="radius" value="<?php echo esc_attr( $current_radius ); ?>" type="hidden" />
	<div class="refine-slider" data-min="<?php echo $min; ?>" data-max="<?php echo $max; ?>" data-step="<?php echo $step; ?>" data-start="<?php echo esc_attr( $current_radius ); ?>"></div>
	<div class="radius-info-box"><span id="radius-info"><?php echo $current_radius; ?></span> <?php 'km' == $va_options->geo_unit ? _e( 'km', APP_TD ) : _e( 'miles', APP_TD ); ?></div>
</label>
<?php
}


/**
 * Displays field for refine by category.
 *
 * @return void
 */
function the_refine_category_ui() {
	require_once ABSPATH . '/wp-admin/includes/template.php';

	$options = array(
		'taxonomy' => VA_LISTING_CATEGORY,
		'request_var' => 'listing_cat',
	);

	$options = apply_filters( 'va_sidebar_refine_category_ui', $options );
	ob_start();
	wp_terms_checklist( 0, array(
		'taxonomy' => $options['taxonomy'],
		'selected_cats' => isset( $_GET[ $options['request_var'] ] ) ? $_GET[ $options['request_var'] ] : array(),
		'checked_ontop' => false
	) );
	$output = ob_get_clean();

	$output = str_replace( 'tax_input[' . $options['taxonomy'] . ']', $options['request_var'], $output );
	$output = str_replace( 'disabled=\'disabled\'', '', $output );

	echo html( 'ul', $output );
}
//repeti a função pra tentar trazer só as categorias em que houve resultado, chamada no sidebar.php
function the_refine_category_ui_exclusive() {
	require_once ABSPATH . '/wp-admin/includes/template.php';

	$options = array(
		'taxonomy' => VA_LISTING_CATEGORY,
		'request_var' => 'listing_cat',
	);

	$options = apply_filters( 'va_sidebar_refine_category_ui', $options );
	ob_start();
	wp_terms_checklist( 0, array(
		'taxonomy' => $options['taxonomy'],
		'selected_cats' => isset( $_GET[ $options['request_var'] ] ) ? $_GET[ $options['request_var'] ] : array(),
		'checked_ontop' => false
	) );
	$output = ob_get_clean();

	$output = str_replace( 'tax_input[' . $options['taxonomy'] . ']', $options['request_var'], $output );
	$output = str_replace( 'disabled=\'disabled\'', '', $output );

	echo html( 'ul', $output );
}



function the_search_refinements() {
	appthemes_pass_request_var( array( 'orderby', 'radius', 'listing_cat' ) );
	do_action( 'va_header_search_refinements' );
}


/**
 * Displays navigation menu.
 *
 * @return void
 */
function va_display_navigation_menu() {

	wp_nav_menu( array(
		'menu_id'         => 'navigation',
		'theme_location'  => 'header',
		'container_class' => 'menu rounded',
		'items_wrap'      => '<ul id="%1$s">%3$s</ul>',
		'fallback_cb'     => false
	) );
?>
	<script type="text/javascript">
		jQuery('#navigation').tinyNav({
			active: 'current-menu-item',
			header: '<?php _e( 'Navigation', APP_TD ); ?>',
			header_href: '<?php echo esc_js( home_url( '/' ) ); ?>',
			indent: '-',
			excluded: ['#adv_categories_listing', '#adv_categories_event']
		});
	</script>
<?php
}


/**
 * Returns author posts link.
 *
 * Taken from http://codex.wordpress.org/Template_Tags/the_author_posts_link.
 * Modified to return the link instead of display it
 *
 * @return string
 */
function va_get_author_posts_link() {
	global $authordata;

	if ( ! is_object( $authordata ) ) {
		return false;
	}

	$link = sprintf(
		'<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
		get_author_posts_url( $authordata->ID, $authordata->user_nicename ),
		esc_attr( sprintf( __( 'Posts by %s', APP_TD ), get_the_author() ) ),
		get_the_author()
	);

	return apply_filters( 'the_author_posts_link', $link );
}


/**
 * Outputs javascript redirect.
 *
 * @param string $url
 *
 * @return void
 */
function va_js_redirect( $url ) {
	echo html( 'a', array( 'href' => esc_url( $url ) ), __( 'Continue', APP_TD ) );
	echo html( 'script', 'location.href="' . esc_url_raw( $url ) . '"' );
}


/**
 * Outputs javascript redirect to listing.
 *
 * @param int $listing_id
 * @param array $query_args (optional)
 *
 * @return void
 */
function va_js_redirect_to_listing( $listing_id, $query_args = array() ) {
	if ( ! is_admin() ) {
		$url = add_query_arg( $query_args, get_permalink( $listing_id ) );
		va_js_redirect( $url );
	}
}


/**
 * Outputs javascript redirect to claimed listing.
 *
 * @param int $listing_id
 *
 * @return void
 */
function va_js_redirect_to_claimed_listing( $listing_id ) {
	if ( ! is_admin() ) {
		$url = va_get_claimed_listings_url() . '#post-'. $listing_id;
		va_js_redirect( $url );
	}
}


/**
 * Returns post geo coordinates.
 *
 * @param int $post_id (optional)
 *
 * @return object
 */
function va_post_coords( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$coord = va_geocode_address( $post_id, false );

	return $coord;
}


/**
 * Returns attributes with post geo coordinates.
 *
 * @param int $post_id (optional)
 *
 * @return string
 */
function va_post_coords_attr( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$coord = va_post_coords( $post_id );

	$attr = '';

	if ( $coord ) {
		$attr = ' data-lat="' . $coord->lat . '" data-lng="' . $coord->lng . '" ';
	}

	return $attr;
}


/**
 * Returns base url for listings.
 *
 * @return string
 */
function va_listings_base_url() {
	global $va_options;

	$url = '';
	$base = trailingslashit( get_bloginfo( 'url' ) );

	if ( is_tax( VA_LISTING_CATEGORY ) || is_tax( VA_LISTING_TAG ) ) {
		$url = get_term_link( get_queried_object() );
	}

	if ( is_post_type_archive( VA_LISTING_PTYPE ) || va_is_home() ) {
		$url = $va_options->listing_permalink;
		$url = trailingslashit( $base . $url );
	}

	return $url;
}


/**
 * Returns sort dropdown list.
 *
 * @param string $post_type (optional)
 * @param string $base_link (optional)
 * @param string $default_current_sort (optional)
 *
 * @return string
 */
function va_list_sort_dropdown( $post_type = '', $base_link = '', $default_current_sort = 'default' ) {
	global $wp_query;

	$options = array();

	if ( $wp_query->post_count == 0 ) {
		return false;
	}

	if ( empty( $post_type ) ) {
		$post_type = $wp_query->get( 'post_type' );
		$post_type = ! empty( $post_type ) ? $post_type : VA_LISTING_PTYPE;
	}

	$options['default'] = __( 'Default', APP_TD );

	if ( get_query_var( 'app_geo_query' ) ) {
		$options['distance'] = __( 'Closest', APP_TD );
	}

	if ( $post_type == VA_LISTING_PTYPE ) {
		$options['highest_rating'] = __( 'Highest Rating', APP_TD );
		$options['most_ratings'] = __( 'Most Ratings', APP_TD );
		$options['recently_reviewed'] = __( 'Recently Reviewed', APP_TD );
	}

	if ( va_events_enabled() ) {
		if ( $post_type == VA_EVENT_PTYPE ) {
			$options['event_date'] = __( 'Event Date', APP_TD );
			$options['popular'] = __( 'Popular', APP_TD );
			$options['most_comments'] = __( 'Most Comments', APP_TD );
			$options['recently_discussed'] = __( 'Recently Discussed', APP_TD );
		}
	}

	$options['title'] = __( 'Alphabetical', APP_TD );
	$options['newest'] = __( 'Newest', APP_TD );
	$options['rand'] = __( 'Random', APP_TD );

	$options = apply_filters( 'va_list_sort_ui', $options );

	$current_sort = get_va_query_var( 'orderby', false );

	// Settings backwards compatability
	if ( $current_sort == 'rating' ) {
		$current_sort = 'highest_rating';
	}

	$current_sort = ! empty( $current_sort ) ? $current_sort : $default_current_sort;

	$li = '';
	foreach ( $options as $value => $title ) {
		$args = array( 'data-value' => $value );

		if ( $value == $current_sort ) {
			$args['class'] = 'active';
		}

		if ( ! empty( $base_link ) ) {
			$href = add_query_arg( 'orderby', $value, $base_link );
		} else {
			$href = add_query_arg( 'orderby', $value );
		}

		$link = html( 'a', array( 'href' => esc_url( $href )  ), $title );

		$li .= html( 'li', $args, $link );
	}

	$top_div_text = html( 'p', array(), $options[ $current_sort ] );

	$top_div_control = html( 'div', array( 'class' => 'control' ) );
	$top_div = html( 'div', array( 'class' => 'va_sort_list_selected selected' ), $top_div_text . $top_div_control );

	$ul = html( 'ul', array( 'class'=> 'va_sort_list', 'id' => 'va_sort_list_' . $post_type ), $li );
	$list = html( 'div', array( 'class' => 'va_sort_list_wrap' ), $ul );

	ob_start();
	?>
	<script type="text/javascript">
		jQuery('#va_sort_list_<?php echo $post_type; ?>').tinyNav({
			active: 'active',
			header: '<?php _e( 'Sort Method', APP_TD ); ?>',
			header_href: '<?php echo esc_url( add_query_arg( 'orderby', 'default' ) ); ?>',
			indent: '-',
			append: '#va_sort_list_container_<?php echo $post_type; ?>'
		});
	</script>
	<?php
	$js = ob_get_clean();

	return html( 'div', array( 'class' => 'va_sort_list_container', 'id' => 'va_sort_list_container_' . $post_type ), $top_div . $list . $js );
}


/**
 * Returns contact post author button.
 *
 * @param int $listing_id (optional)
 *
 * @return string
 */
function get_the_contact_listing_owner_button( $listing_id = 0 ) {
	return va_contact_post_author_button( $listing_id );
}


/**
 * Displays contact post author button.
 *
 * @param int $listing_id (optional)
 *
 * @return void
 */
function the_contact_listing_owner_button( $listing_id = 0 ) {

	if ( _va_is_claimable( $listing_id ) || 'publish' !== get_post_status( $listing_id ) ) {
		return;
	}

	echo get_the_contact_listing_owner_button( $listing_id );
}

/**
 * Displays term description with content filters applied.
 *
 * @see term_description()
 *
 * @param string $before Optional. Content to prepend to the description. Default empty.
 * @param string $after  Optional. Content to append to the description. Default empty.
 */
function va_the_archive_description( $before = '', $after = '' ) {
	$description = term_description();
	if ( $description ) {
		echo $before . apply_filters( 'the_content', $description ) . $after;
	}
}

/**
 * Renders WP editor.
 *
 * @see wp-includes/class-wp-editor.php
 * @since Vantage 1.4
 *
 * @param string $content   Initial content for the editor.
 * @param string $editor_id HTML ID attribute value for the textarea and TinyMCE. Can only be /[a-z]+/.
 * @param array  $settings  See _WP_Editors::editor().
 */
function va_editor( $content = '', $editor_id = '', $settings = array() ) {

	$defaults = array(
		'media_buttons' => false,
		'textarea_name' => $editor_id,
		'textarea_rows' => 10,
		'quicktags'     => array(
			'buttons' => 'strong,em,link,block,del,ins,ul,li'
		)
	);

	$settings = apply_filters( 'va_editor_settings', wp_parse_args( $settings, $defaults ), $editor_id );

	wp_editor( $content, $editor_id, $settings );
}

function va_listing_editor( $content = '', $editor_id = '', $settings = array() ) {
	va_editor( $content, $editor_id, apply_filters( 'va_listing_editor_settings', $settings ) );
}