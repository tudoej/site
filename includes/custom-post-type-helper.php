<?php
add_action( 'wp_ajax_va-contact', 'va_contact_form' );
add_action( 'wp_ajax_nopriv_va-contact', 'va_contact_form' );
add_filter( 'va_contact_form_fields', 'va_contact_form_validate', 10, 2 );

function _va_get_initial_field_value( $field ) {
	return isset( $_POST[$field] ) ? stripslashes( $_POST[$field] ) : '';
}

function va_get_category_surcharge( $category, $taxonomy, $by = 'slug' ) {
	global $va_options;
	if ( is_numeric( $category ) || is_object( $category ) ) {
		$category = get_term( $category, $taxonomy );
	} else {
		$category = get_term_by( $by, $category, $taxonomy );
	}

	if(!$category)
		return 0;

	$category_slug = $category->slug;

	return isset( $va_options->category_surcharges[$category_slug]['surcharge'] ) ? $va_options->category_surcharges[$category_slug]['surcharge'] : 0;
}

function va_update_form_builder( $listing_cat, $listing_id, $taxonomy ) {

	if ( !$listing_cat )
		return;

	$fields = array();
	foreach($listing_cat as $_cat){
		foreach ( va_get_fields_for_cat( $_cat, $taxonomy ) as $field ) {
			$fields[$field['name']] = $field;
		}
	}

	$to_update = scbForms::validate_post_data( $fields );

	scbForms::update_meta( $fields, $to_update, $listing_id );
}

function va_terms_checklist($post_id = 0, $args = array()) {
 	$defaults = array(
		'descendants_and_self' => 0,
		'selected_cats' => false,
		'popular_cats' => false,
		'walker' => null,
		'taxonomy' => 'category',
		'checked_ontop' => true,
		'disabled' => null,
		'hidden_on_disabled' => true,
		'name' => '',
		'input_class' => '',
		'get_terms' => array()
	);

	$args = apply_filters( 'wp_terms_checklist_args', $args, $post_id );
	$args = apply_filters( 'va_terms_checklist_args', $args, $post_id );

	extract( wp_parse_args($args, $defaults), EXTR_SKIP );

	if ( empty($walker) || !is_a($walker, 'Walker') )
		$walker = new Walker_Category_Checklist;

	$descendants_and_self = (int) $descendants_and_self;

	$args = array('taxonomy' => $taxonomy);

	$tax = get_taxonomy($taxonomy);
	$args['disabled'] = !is_null( $disabled ) ? $disabled : !current_user_can($tax->cap->assign_terms);
	$args['hidden_on_disabled'] = $hidden_on_disabled;
	$args['name'] = $name;
	$args['input_class'] = $input_class;

	if ( is_array( $selected_cats ) )
		$args['selected_cats'] = $selected_cats;
	elseif ( $post_id )
		$args['selected_cats'] = wp_get_object_terms($post_id, $taxonomy, array_merge($args, array('fields' => 'ids')));
	else
		$args['selected_cats'] = array();

	if ( is_array( $popular_cats ) )
		$args['popular_cats'] = $popular_cats;
	else
		$args['popular_cats'] = get_terms( $taxonomy, array( 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );



	if ( $descendants_and_self ) {
		$categories = (array) get_terms($taxonomy, array_merge( $get_terms, array( 'child_of' => $descendants_and_self, 'hierarchical' => 0, 'hide_empty' => 0 ) ) );
		$self = get_term( $descendants_and_self, $taxonomy );
		array_unshift( $categories, $self );
	} else {
		$categories = (array) get_terms($taxonomy, array_merge( $get_terms, array('get' => 'all') ) );
	}

	if ( $checked_ontop ) {
		// Post process $categories rather than adding an exclude to the get_terms() query to keep the query the same across all posts (for any query cache)
		$checked_categories = array();
		$keys = array_keys( $categories );

		foreach( $keys as $k ) {
			if ( in_array( $categories[$k]->term_id, $args['selected_cats'] ) ) {
				$checked_categories[] = $categories[$k];
				unset( $categories[$k] );
			}
		}

		// Put checked cats on top
		echo call_user_func_array(array(&$walker, 'walk'), array($checked_categories, 0, $args));
	}
	// Then the rest of them
	echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));
}

add_action( 'init', 'va_init_multiple_category_checklist_walker' );
/*
 * Override the 'Walker_Category_Checklist' method, 'start_el', to replace checkboxes with radio buttons
 */
function va_init_multiple_category_checklist_walker() {

	require_once ABSPATH . '/wp-admin/includes/template.php';

	class VA_Multiple_Category_Checklist_Walker extends Walker_Category_Checklist {

		function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {

			extract($args);
			if ( empty($taxonomy) )
				$taxonomy = 'category';

			if ( !empty( $name ) ) {

			} else if ( $taxonomy == 'category' ) {
				$name = 'post_category';
			} else {
				$name = 'tax_input['.$taxonomy.']';
			}

			$class = in_array( $category->term_id, $popular_cats ) ? 'popular-category ' : '';

			$input_class = !empty( $input_class ) ? ' class="' . $input_class . '"' : '';

			$label = apply_filters('the_category', $category->name );
			$label = apply_filters('va_multiple_category_checklist_label', $label, $category, $taxonomy );

			$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" ' . $input_class . ' name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( $label ) . '</label>';

			if ( $hidden_on_disabled && $args['disabled'] ) {
				$output .= "\n".'<input type="hidden" name="'.$name.'[]" value="' . $category->term_id . '" />';
			}
		}
	}
}

function va_multiple_category_checkboxes( $taxonomy, $selected_cats = array(), $included_cats = array(), $disabled = false ) {
	require_once ABSPATH . '/wp-admin/includes/template.php';

	$get_terms = array();
	if ( !empty( $included_cats ) ) {
		$get_terms['include'] = implode(',', $included_cats);
	}

	ob_start();
	va_terms_checklist( 0, array(
		'taxonomy' => $taxonomy,
		'selected_cats' => $selected_cats,
		'checked_ontop' => false,
		'disabled' => $disabled,
		'hidden_on_disabled' => true,
		'get_terms' => $get_terms,
		'name' => '_'.$taxonomy,
		'input_class' => 'required',
		'walker' => new VA_Multiple_Category_Checklist_Walker(),
	) );

	$output = ob_get_clean();

	return html( 'div', array('id' => 'multi-categories-checkboxes' ), html( 'ul' , $output ) );
}

function va_category_dropdown( $taxonomy, $selected_category = array(), $include_category = array() ) {
	wp_dropdown_categories( array(
		'taxonomy' => $taxonomy,
		'hide_empty' => false,
		'hierarchical' => true,
		'name' => '_'.$taxonomy.'[]',
		'id' => '_'.$taxonomy,
		'selected' => $selected_category,
		'show_option_none' => __( 'Select Category', APP_TD ),
		'class' => 'required',
		'orderby' => 'name',
		'include' => $include_category
	) );
}

function va_get_edit_categories( $post, $label, $taxonomy, $disabled = false ) {

	$selected_categories = !empty( $post->categories ) ? array_keys( $post->categories ) : array();
	$include_categories = $disabled ? $selected_categories : array();
	?>
	<label for="_<?php echo $taxonomy; ?>[]" class="error" style="display:none;" ><?php /* This will get populated by an error message 'VA_i18n.error_category' */ ?></label>
	<label><?php echo $label; ?></label>
	<?php

	echo va_multiple_category_checkboxes( $taxonomy, $selected_categories, $include_categories, $disabled );
}

function _va_set_post_name( $title, $ID, $post_type = '', $post_status = '', $post_parent = 0 ) {
	$post_name = sanitize_title( $title );

	$post_name = wp_unique_post_slug( $post_name, $ID, $post_status, $post_type, $post_parent );

	return $post_name;
}

function _va_set_guid( $post_ID ) {
	global $wpdb;
	$wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $post_ID ) ), array( 'ID' => $post_ID ) );
}

function va_contact_form() {
	$result = '';

	if ( empty( $_POST['post_id'] ) || !wp_verify_nonce( $_POST['contact_nonce'], "va-contact-" . $_POST['post_id'] ) ) {
		$result = __( 'Contact Form submission error. Please try refreshing the page and submitting the contact form again.', APP_TD );
		ob_start();
		appthemes_display_notice( 'error', $result );
		$result = ob_get_clean();
		die ( $result );
	}

	$post_id = (int) $_POST['post_id'];

	$post = get_post( $post_id );
	if ( !$post ) {
		$result = __( 'Contact Form submission error. Please try refreshing the page and submitting the contact form again.', APP_TD );
		ob_start();
		appthemes_display_notice( 'error', $result );
		$result = ob_get_clean();
		die ( $result );
	}

	$post_type_obj = get_post_type_object( $post->post_type );

	$contact_form_verbiage = array(
		'singular_name' => $post_type_obj->labels->singular_name,
		'singular_name_lcase' => strtolower( $post_type_obj->labels->singular_name ),
		'author' => __( 'Author', APP_TD ),
	);

	$contact_form_verbiage = apply_filters('va_contact_form_verbiage', $contact_form_verbiage, $post );

	$form_fields = array();

	foreach( array( 'name', 'email', 'phone', 'website', 'message' ) as $form_field ) {
		// If we want to check for presence of the submitted fields, i.e. required fields, this would be where to grab it and die() the error.
		$form_fields[$form_field] = apply_filters('va_contact_form_fields', sanitize_text_field( $_POST[ 'contact_' . $form_field ] ), $form_field, $post_id );
	}

	ob_start();
	appthemes_load_template('email-contact.php', $form_fields );
	$content = ob_get_clean();

	if ( empty( $content ) ) {

		$content  = html( 'p', sprintf( __( '%1$s %2$s Contact Form Submission:', APP_TD ), $contact_form_verbiage['singular_name'], $contact_form_verbiage['author'] ) );
		$content .= html( 'p', sprintf( __('Someone is contacting you regarding your %s: %s', APP_TD ), $contact_form_verbiage['singular_name_lcase'], html_link( get_permalink( $post_id ), $post->post_title ) ) );
		$content .= html( 'p', sprintf( __('Name: %s', APP_TD ), $form_fields['name'] ) );
		$content .= html( 'p', sprintf( __('Email: %s', APP_TD ), $form_fields['email'] ) );
		$content .= html( 'p', sprintf( __('Phone: %s', APP_TD ), $form_fields['phone'] ) );
		$content .= html( 'p', sprintf( __('Website: %s', APP_TD ), $form_fields['website'] ) );
		$content .= html( 'p', sprintf( __('Message: %s', APP_TD ), '<br />' . $form_fields['message'] ) );
	}

	$recipient = get_user_by( 'id', $post->post_author );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __('[%s] Contact Form Submission - %s', APP_TD ), $blogname, $post->post_title );

	APP_Mail_From::apply_once( array( 'email' => $form_fields['email'], 'name' => $form_fields['name'], 'reply' => true ) );

	$args = array(
		'to' => $recipient->user_email,
		'subject' => $subject,
		'message' => $content,
		'listing' => $post,
		'form_fields' => $form_fields
	);

	va_appthemes_send_email( 'va_email_listing_owner', $args );

	$result = __( 'Your message was successfully sent!', APP_TD );
	ob_start();
	appthemes_display_notice( 'success', $result );
	$result = ob_get_clean();
	die ( $result );
}

function va_contact_form_validate( $value, $name ) {
	$errors = array();
	switch ( $name ) {
		case 'name':
			$errors = va_contact_form_validate_field( $value, __( 'Name: %s', APP_TD ), array( 'required' ) );
			break;
		case 'message':
			$errors = va_contact_form_validate_field( $value, __( 'Message: %s', APP_TD ), array( 'required' ) );
			break;
		case 'email':
			$errors = va_contact_form_validate_field( $value, __( 'Email: %s', APP_TD ), array( 'required', 'email' ) );
			break;
		case 'website':
			$errors = va_contact_form_validate_field( $value, __( 'Website: %s', APP_TD ), array( 'url' ) );
			break;
		default:
			break;
	}

	if ( empty( $errors ) ) {
		return $value;
	}

	$result = '';
	ob_start();
	foreach ( $errors as $error ) {
		$result .= appthemes_display_notice( 'error', $error );
	}
	$result = ob_get_clean();
	die ( $result );
}

function va_contact_form_validate_field( $value = '', $title = '%s', $methods = array() ) {
	$errors = array();
	foreach ( $methods as $method ) {
		switch ( $method ) {
			case 'required':
				if ( ! $value ) {
					$errors[] = sprintf( $title, __( 'This field is required.', APP_TD ) );
				}
				break;
			case 'email':
				if ( $value && ! is_email( $value ) ) {
					$errors[] = sprintf( $title, __( 'Please enter a valid email address.', APP_TD ) );
				}
				break;
			case 'url':
				if ( $value && ! preg_match( "/^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.)+(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\x{E000}-\x{F8FF}]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/iu", $value ) ) {
					$errors[] = sprintf( $title, __( 'Please enter a valid URL.', APP_TD ) );
				}
				break;
			default:
				break;
		}
	}
	return $errors;
}

function va_contact_post_author_button( $post_id = 0, $args = array() ) {

	$post_id = $post_id ? $post_id : get_the_ID();
	$post = get_post( $post_id );
	if ( !$post )
		return false;

	$post_type_obj = get_post_type_object( $post->post_type );

	$defaults = array (
		'button_text' => sprintf( __( 'Contact %s Owner' , APP_TD ), $post_type_obj->labels->singular_name ),
		'form_title' => sprintf( __( 'Contact the %s Owner', APP_TD ), $post_type_obj->labels->singular_name ),
		'form_title_helper' => sprintf( __( 'To inquire about this %1$s, complete the form below to send a message to the %1$s owner.', APP_TD ), strtolower( $post_type_obj->labels->singular_name ) ),
		'form_submit_button_text' => __( 'Send Inquiry' , APP_TD ),
		'form_class' => '',
	);

	$args = wp_parse_args( $args, $defaults );
	$args = apply_filters( 'va_contact_post_author_button', $args, $post );

	$button = html( 'a', array(
		'class' => 'va-contact-link '.$post_type_obj->name.'-contact-link',
		'href' => '#va-contact-form',
	), $args['button_text'] );

	$required = '<span class="required"> *</span>';

	$elements = '';
	$elements .= html( 'input', array( 'type' => 'hidden', 'value' => $post_id, 'id' => 'va-contact-post_id' ) );
	$elements .= html( 'h3', array( 'class' => 'title' ), $args['form_title'] );
	$elements .= html( 'p', array( 'class' => 'helper' ), $args['form_title_helper'] );
	$elements .= html( 'label', array(), __( 'Name', APP_TD ) . $required, html( 'input', array( 'type'=>'text', 'name' => 'va-contact-name', 'id' => 'va-contact-name', 'class' => 'required' ) ) );
	$elements .= html( 'label', array(), __( 'Email', APP_TD ) . $required, html( 'input', array( 'type'=>'text', 'name' => 'va-contact-email', 'id' => 'va-contact-email', 'class' => 'required email' ) ) );
	$elements .= html( 'label', array(), __( 'Phone Number', APP_TD ), html( 'input', array( 'type'=>'text', 'name' => 'va-contact-phone', 'id' => 'va-contact-phone' ) ) );
	$elements .= html( 'label', array(), __( 'Website', APP_TD ), html( 'input', array( 'type'=>'text', 'name' => 'va-contact-website', 'id' => 'va-contact-website', 'class' => 'url' ) ) );

	$elements .= html( 'label', array(), __( 'Message', APP_TD ) . $required, html( 'textarea', array( 'name' => 'va-contact-message', 'id' => 'va-contact-message', 'class' => 'required' ) ) );

	$elements .= wp_nonce_field( 'va-contact-' . $post_id, 'va-contact-nonce', true, false );

	$elements .= html( 'input', array( 'type' => 'button', 'value' => $args['form_submit_button_text'], 'id' => 'va-contact-send' ) );

	$box = html( 'form', array( 'id' => 'va-contact-form', 'class' => 'va-' . $post_type_obj->name . '-contact-form ' . $args['form_class'] ), $elements );

	$box = html( 'div', array( 'style' => 'display:none;' ), $box );

	return $button . $box;
}

function va_get_term_link_base( $taxonomy ) {
	global $wp_rewrite;

	$termlink = $wp_rewrite->get_extra_permastruct( $taxonomy );
	$termlink = str_replace("%$taxonomy%", '', $termlink);

	$t = get_taxonomy($taxonomy);

	if ( empty($termlink) ) {
		if ( $t->query_var )
			$termlink = "?$t->query_var=";
		else
			$termlink = "?taxonomy=$taxonomy&term=";
		$termlink = home_url($termlink);
	} else {
		$termlink = home_url( user_trailingslashit($termlink, 'category') );
	}

	return $termlink;
}

function get_va_query_var( $var, $fallback = true ) {
	if ( get_query_var( 'va_' . $var ) || !$fallback ) {
		return get_query_var( 'va_' . $var );
	}

	return get_query_var( $var );
}