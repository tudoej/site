<?php
/**
 * Admin Single Listing Metaboxes
 *
 * @package Vantage\Admin\Metaboxes\Listing
 * @author  AppThemes
 * @since   Vantage 1.0
 */

add_action( 'admin_init', 'va_listing_metaboxes' );
add_action( 'save_post', 'va_set_listing_meta_defaults', 10, 2 );
add_action( 'save_post', array( 'APP_Search_Index', 'save_post'), 100, 2 );

add_action( 'wp_ajax_vantage_single_listing_geocode', 'va_handle_listing_geocode_ajax' );


/**
 * Sets listing meta defaults
 *
 * @param int $post_id
 * @param object $post
 *
 * @return void
 */
function va_set_listing_meta_defaults( $post_id, $post ) {
	if ( VA_LISTING_PTYPE !== $post->post_type ) {
		return;
	}

	va_set_meta_defaults( $post_id );
}


/**
 * Removes unnecessary metaboxes
 *
 * @return void
 */
function va_listing_metaboxes() {
	$remove_boxes = array( 'commentstatusdiv', 'commentsdiv', 'postexcerpt', 'revisionsdiv', 'authordiv' );

	foreach ( $remove_boxes as $id ) {
		remove_meta_box( $id, VA_LISTING_PTYPE, 'normal' );
	}
}


/**
 * Handles listing geocoding
 *
 * @return void
 */
function va_handle_listing_geocode_ajax() {
	if ( ! isset( $_GET['address'] ) && ( ! isset( $_GET['lat'] ) && ! isset( $_GET['lng'] ) ) ) {
		return;
	}

	if ( isset( $_GET['address'] ) ) {
		$api_response = va_geocode_address_api( $_GET['address'] );
	} else if ( isset( $_GET['lat'] ) ) {
		$api_response = va_geocode_lat_lng_api( $_GET['lat'], $_GET['lng'] );
	}

	if ( ! $api_response ) {
		die( 'error' );
	}

	die( json_encode( $api_response ) );
}


/**
 * Listing Location Metabox
 */
class VA_Listing_Location_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listing-location', __( 'Location', APP_TD ), VA_LISTING_PTYPE, 'normal' );
	}

	public function admin_enqueue_scripts() {
		appthemes_load_map_provider();
	}

	public function after_form( $post ) {

		echo html( 'input', array(
			'type' => 'button',
			'class' => 'button',
			'value' => __( 'Find on Map', APP_TD ),
			'name' => '_blank',
			'id' => 'listing-find-on-map',
		) );

		$coord = appthemes_get_coordinates( $post->ID );

		echo html( 'input', array(
			'type' => 'hidden',
			'value' => esc_attr( $coord->lat ),
			'name' => 'lat',
		) );

		echo html( 'input', array(
			'type' => 'hidden',
			'value' => esc_attr( $coord->lng ),
			'name' => 'lng',
		) );

		echo html( 'div', array(
			'id' => 'listing-map',
		) );

		?>
		<script>
			jQuery(function() {
				vantage_map_edit();
			});
		</script>
		<?php
	}

	public function form_fields() {

		return array(
			array(
				'title' => __( 'Address', APP_TD ),
				'type' => 'text',
				'name' => 'address',
				'extra' => array(
					'id' => 'listing-address',
				)
			),
		);

	}

	public function before_save( $data, $post_id ) {

		appthemes_set_coordinates( $post_id, $_POST['lat'], $_POST['lng'] );

		return $data;
	}

}


/**
 * Listing Claimable Metabox
 */
class VA_Listing_Claimable_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listing-claimable', __( 'Claimable Listing', APP_TD ), VA_LISTING_PTYPE );
	}

	public function form_fields() {

		return array(
			array(
				'title' => __( 'Users can claim this listing', APP_TD ),
				'type' => 'checkbox',
				'name' => 'listing_claimable',
				'desc' => __( 'Yes', APP_TD ),
			),
		);

	}

	public function after_form( $post ) {
		echo html( 'p', array(
				'class' => 'howto'
			), __( 'Claimable listings will have a link that allows users to claim them. You can enable moderation on claimed listings in settings.', APP_TD ) );
	}

	function before_save( $data, $post_id ) {
		if ( ! empty( $data['listing_claimable'] ) ) {
			delete_post_meta( $post_id, 'claimee' );
		}

		return $data;
	}
}


/**
 * Listing Reviews Status Metabox
 */
class VA_Listing_Reviews_Status_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listing-reviews', __( 'Reviews Status', APP_TD ), VA_LISTING_PTYPE );
	}

	public function display( $post ) {

		$form_fields = $this->form_fields();

		$form_data = array(
			'comment_status' => ( $post->comment_status == 'open' ? 'open' : '' )
		);

		$form = $this->table( $form_fields, $form_data );

		echo $form;
	}

	public function form_fields() {
		return array(
			array(
				'title' => __( 'Enable Reviews to be submitted on this listing?', APP_TD ),
				'type' => 'checkbox',
				'name' => 'comment_status',
				'desc' => __( 'Yes', APP_TD ),
				'value' => 'open',
			),
		);
	}

	function save( $post_id ) {
		// Handled by WordPress
	}
}


/**
 * Listing Claim Moderation Metabox
 */
class VA_Listing_Claim_Moderation_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listing-claim-moderation', __( 'Moderation Queue', APP_TD ), VA_LISTING_PTYPE, 'side', 'high' );

		add_action( 'admin_init', array( $this, 'reject_claim' ), 10, 1 );
	}

	function condition() {
		return ( isset( $_GET['post'] ) && get_post_status( $_GET['post'] ) == 'pending-claimed' );
	}

	public function display( $post ) {

		echo html( 'p', array(), __( 'Someone wants to claim this listing.', APP_TD ) );

		$claimee = get_userdata( get_post_meta( $post->ID, 'claimee', true ) );

		echo html( 'p', array(), sprintf( __( '<strong>New Owner:</strong> %s', APP_TD ), html( 'a', array( 'href' => va_dashboard_url( 'listings', $claimee->ID ), 'target' => '_blank' ), $claimee->display_name ) ) );

		echo html( 'p', array(), html( 'a', array('href'=>'mailto: ' . $claimee->user_email, 'target'=>'_blank' ), sprintf( __( 'Email %s', APP_TD ), $claimee->display_name ) ) );

		echo html( 'input', array(
			'type' => 'submit',
			'class' => 'button-primary',
			'value' => __( 'Accept', APP_TD ),
			'name' => 'publish',
			'style' => 'padding-left: 30px; padding-right: 30px; margin-right: 20px; margin-left: 15px;',
		) );

		echo html( 'a', array(
			'class' => 'button',
			'style' => 'padding-left: 30px; padding-right: 30px;',
			'href' => $this->get_edit_post_link( $post->ID, 'display', array( 'reject' => 1 ) ),
		), __( 'Reject', APP_TD ) );

		echo html( 'p', array(
				'class' => 'howto'
			), __( 'Rejecting will return it to being published on the site.', APP_TD ) );

	}

	function get_edit_post_link( $post_id, $context, $vars ) {
		$link = get_edit_post_link( $post_id, $context );

		if ( ! empty( $vars ) && is_array( $vars ) ) {
			$context_and = 'display' == $context ? '&amp;' : '&';
			foreach ( $vars as $k => $v ) {
				$link .= $context_and . $k . '=' . $v;
			}
		}

		return $link;
	}

	function reject_claim() {
		global $pagenow;

		if ( 'post.php' != $pagenow ) {
			return;
		}

		if ( ! isset( $_GET['reject'] ) ) {
			return;
		}

		if ( VA_Listing_Claim::reject_claim() ) {
			wp_redirect( $this->get_edit_post_link( $_GET['post'], 'url', array( 'rejected' => 1 ) ) );
		}
	}

	function rejected_claim_success_notice() {
		echo scb_admin_notice( __( 'You have rejected the claim, and now this listing has been reset to <a href="#listing-claimable">claimable</a>.', APP_TD ) );
	}
}


/**
 * Listing Contact Information Metabox
 */
class VA_Listing_Contact_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listing-contact', __( 'Contact Information', APP_TD ), VA_LISTING_PTYPE, 'normal' );
	}

	public function form_fields() {

		$fields = array(
			array(
				'title' => __( 'Phone Number', APP_TD ),
				'type'  => 'text',
				'name'  => 'phone',
			),
			array(
				'title' => __( 'Website', APP_TD ),
				'type'  => 'text',
				'name'  => 'website',
			),
			array(
				'title' => __( 'Email', APP_TD ),
				'type'  => 'email',
				'name'  => 'email',
			),
		);

		foreach ( va_get_allowed_listing_networks() as $social_network ) {
			$field = array(
				'title' => va_get_social_network_title( $social_network ),
				'tip'   => va_get_social_network_tip( $social_network ),
				'type'  => 'text',
				'name'  => $social_network,
			);

			$fields[] = $field;
		}

		return $fields;
	}

	function before_save( $data, $post_id ) {

		foreach ( va_get_listing_contact_fields() as $field ) {
			if ( ! empty( $data[ $field ] ) ) {
				$data[ $field ] = va_format_listing_contact_fields( $data[ $field ], $field );
			}
		}

		return $data;
	}

}


/**
 * Listing Pricing Information Metabox
 */
class VA_Listing_Pricing_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listing-pricing', __( 'Pricing Information', APP_TD ), VA_LISTING_PTYPE, 'normal', 'low' );
	}

	public function admin_enqueue_scripts() {
		if ( is_admin() ) {
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'wp-jquery-ui-datepicker', APP_FRAMEWORK_URI . '/styles/datepicker/datepicker.css' );
		}
	}

	public function before_display( $form_data, $post ) {

		$form_data['_blank_listing_start_date'] = $post->post_date;
		$form_data['_blank_js_listing_start_date'] = mysql2date( 'U', $post->post_date);

		$date_format = get_option( 'date_format' );
		$date_format = str_ireplace( 'm', 'n', $date_format );
		$date_format = str_ireplace( 'd', 'j', $date_format );

		if ( ! empty( $form_data['featured-home_start_date'] ) ) {
			$form_data['_blank_featured-home_start_date'] = mysql2date( $date_format, $form_data['featured-home_start_date']);
			$form_data['_blank_js_featured-home_start_date'] = mysql2date( 'U', $form_data['featured-home_start_date']);
			$form_data['featured-home_start_date'] = mysql2date( 'm/d/Y', $form_data['featured-home_start_date']);
		}

		if ( ! empty( $form_data['featured-cat_start_date'] ) ) {
			$form_data['_blank_featured-cat_start_date'] = mysql2date( $date_format, $form_data['featured-cat_start_date']);
			$form_data['_blank_js_featured-cat_start_date'] = mysql2date( 'U', $form_data['featured-cat_start_date']);
			$form_data['featured-cat_start_date'] = mysql2date( 'm/d/Y', $form_data['featured-cat_start_date']);
		}

		return $form_data;
	}

	public function before_form( $post ) {
		$date_format = get_option( 'date_format', 'm/d/Y' );

		switch ( $date_format ) {
			case "d/m/Y":
			case "j/n/Y":
				$ui_display_format = 'dd/mm/yy';
			break;
			case "Y/m/d":
			case "Y/n/j":
				$ui_display_format = 'yy/mm/dd';
			break;
			case "m/d/Y":
			case "n/j/Y":
			default:
				$ui_display_format = 'mm/dd/yy';
			break;
		}

		?>
		<script type="text/javascript">
			jQuery(function($){
				if ( $("#listing_duration").val() == '' ) {
					$("#listing_duration").val(0);
				}

				createExpireHandler( undefined, $("#listing_duration"), $("#_blank_listing_start_date"), $("#_blank_js_listing_start_date"), $(''), $("#_blank_expire_listing"), $ );
				$("#_blank_listing_start_date").parent().parent().hide();
				$("#_blank_js_listing_start_date").parent().parent().hide();

				createExpireHandler( $("#featured-home"), $("#featured-home_duration"), $("#featured-home_start_date"), $("#_blank_js_featured-home_start_date"), $("#_blank_featured-home_start_date"), $("#_blank_expire_featured-home"), $ );
				$( "#_blank_featured-home_start_date" ).datepicker({
					dateFormat: "<?php echo $ui_display_format; ?>",
					altField: "#featured-home_start_date",
					altFormat: "mm/dd/yy"
				});
				$("#featured-home_start_date").parent().parent().hide();
				$("#_blank_js_featured-home_start_date").parent().parent().hide();

				createExpireHandler( $("#featured-cat"), $("#featured-cat_duration"), $("#featured-cat_start_date"), $("#_blank_js_featured-cat_start_date"), $("#_blank_featured-cat_start_date"), $("#_blank_expire_featured-cat"), $ );
				$( "#_blank_featured-cat_start_date" ).datepicker({
					dateFormat: "<?php echo $ui_display_format; ?>",
					altField: "#featured-cat_start_date",
					altFormat: "mm/dd/yy"
				});
				$("#featured-cat_start_date").parent().parent().hide();
				$("#_blank_js_featured-cat_start_date").parent().parent().hide();
			});

			function createExpireHandler( enableBox, durationBox, startDateBox, startDateU, startDateDisplayBox, textBox, $ ){

				$(enableBox).change(function(){
					if( $(this).attr("checked") == "checked" && $(startDateBox).val() == "" ){
						$(startDateDisplayBox).val( dateToString( new Date ) );
						$(startDateBox).val( dateToStdString( new Date ) );
						$(durationBox).val( '0' );
					} else {
						$(startDateBox).val( '' );
						$(startDateDisplayBox).val( '' );
						$(durationBox).val( '' );
						$(textBox).val( '' );
					}
				});

				var checker = function(){
					var string = "";
					if( enableBox === undefined ){
						string = get_expiration_time();
					}
					else if( $(enableBox).attr('checked') !== undefined ){
						string = get_expiration_time();
					}
					update(string);
				}

				var get_expiration_time = function(){

					var startDate = $(startDateU).val() * 1000;
					if( startDate == "" ){
						startDate = new Date().getTime();
					}

					var duration = $(durationBox).val();
					if ( duration == "" ){
						return "";
					}

					return getDateString( parseInt( duration, 10 ), startDate );
				}

				var getDateString = function ( duration, start_date){
					if( isNaN(duration) )
						return "";

					if( duration === 0 )
						return "<?php _e( 'Never', APP_TD ); ?>";

					var _duration = parseInt( duration ) * 24 * 60 * 60 * 1000;
					var _expire_time = parseInt( start_date ) + parseInt( _duration );
					var expireTime = new Date( _expire_time );

					return dateToString( expireTime );
				}

				var update = function( string ){
					if( string  != $(textBox).val() ){
						$(textBox).val( string );
					}
				}

				var dateToStdString = function( date ){
					return ( date.getMonth() + 1 )+ "/" + date.getDate() + "/" + date.getFullYear();
				}

				var dateToString = function( date ){
					<?php
						$date_format = get_option('date_format', 'm/d/Y');

						switch ( $date_format ) {
							case "d/m/Y":
							case "j/n/Y":
								$js_date_format = 'date.getDate() + "/" + ( date.getMonth() + 1 ) + "/" + date.getFullYear()';
							break;
							case "Y/m/d":
							case "Y/n/j":
								$js_date_format = 'date.getFullYear() + "/" + ( date.getMonth() + 1 ) + "/" + date.getDate()';
							break;
							case "m/d/Y":
							case "n/j/Y":
							default:
								$js_date_format = '( date.getMonth() + 1 )+ "/" + date.getDate() + "/" + date.getFullYear()';
							break;
						}
					?>
					return <?php echo $js_date_format; ?>;
				}

				setInterval( checker, 10 );
			}
		</script>
		<p><?php _e( 'These settings allow you to override the defaults that have been applied to the listings based on the plan the owner chose. They will apply until the listing expires.', APP_TD ); ?></p>
		<?php

	}

	public function form_fields() {

		$output = array(
			 array(
				'title' => __( 'Listing Duration', APP_TD ),
				'type' => 'number',
				'name' => 'listing_duration',
				'desc' => __( 'days', APP_TD ),
				'extra' => array(
					'class' => 'small-text'
				),
			),
			array(
				'title' => __( 'Listing Start Date', APP_TD ),
				'type' => 'text',
				'name' => '_blank_listing_start_date',
			),
			array(
				'title' => __( 'Listing Start Date', APP_TD ),
				'type' => 'text',
				'name' => '_blank_js_listing_start_date',
			),
			array(
				'title' => __( 'Expires on', APP_TD ),
				'type' => 'text',
				'name' => '_blank',
				'extra' => array(
					'disabled' => 'disabled',
					'style' => 'background-color: #EEEEEF;',
					'id' => '_blank_expire_listing'
				)
			)
		);

		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {

			$enabled = array(
				'title' => APP_Item_Registry::get_title( $addon ),
				'type' => 'checkbox',
				'name' => $addon,
				'desc' => __( 'Yes', APP_TD ),
				'extra' => array(
					'id' => $addon,
				)
			);

			$duration = array(
				'title' => __( 'Duration', APP_TD ),
				'desc' => __( 'days (0 = infinite)', APP_TD ),
				'type' => 'number',
				'name' => $addon . '_duration',
				'extra' => array(
					'class' => 'small-text'
				),
			);

			$start = array(
				'title' => __( 'Start Date', APP_TD ),
				'type' => 'text',
				'name' => $addon . '_start_date',
			);

			$start_display = array(
				'title' => __( 'Start Date', APP_TD ),
				'type' => 'text',
				'name' => '_blank_'.$addon . '_start_date',
			);

			$start_js = array(
				'title' => __( 'Start Date', APP_TD ),
				'type' => 'text',
				'name' => '_blank_js_'.$addon . '_start_date',
			);


			$expires = array(
				'title' => __( 'Expires on', APP_TD ),
				'type' => 'text',
				'name' => '_blank',
				'extra' => array(
					'disabled' => 'disabled',
					'style' => 'background-color: #EEEEEF;',
					'id' => '_blank_expire_' . $addon,
				)
			);

			$output = array_merge( $output, array( $enabled, $duration, $start, $start_display, $start_js, $expires ) );

		}

		return $output;
	}

	function disable_save() {
		if ( ! empty( $_POST['original_post_status'] ) && $_POST['original_post_status'] == 'pending-claimed' && ! empty( $_POST['publish'] ) ) {
			return true;
		}

		return false;
	}

	function before_save( $data, $post_id ) {
		global $va_options;

		if ( $this->disable_save() ) {
			return array();
		}

		unset( $data['_blank_listing_start_date'] );
		unset( $data['_blank_js_listing_start_date'] );
		unset( $data['_blank'] );

		$data['featured'] = 0;

		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {

			unset( $data['_blank_' . $addon . '_start_date'] );
			unset( $data['_blank_js_' . $addon . '_start_date'] );

			if ( $data[ $addon . '_start_date'] ) {
				$data[ $addon . '_start_date'] = date( 'Y-m-d H:i:s', strtotime( $data[ $addon . '_start_date'] ) );
			}

			if ( $data[ $addon ] ) {

				if ( $data[ $addon . '_duration'] !== '0' && empty( $data[ $addon . '_duration'] ) ) {
					$data[ $addon . '_duration'] = $va_options->addons[ $addon ]['duration'];
				}

				if ( empty( $data[ $addon . '_start_date'] ) ) {
					$data[ $addon . '_start_date'] = current_time( 'mysql' );
				}

				$data['featured'] = 1;
			}
		}

		return $data;

	}
}


/**
 * Listing Moderation Metabox
 */
class VA_Listing_Publish_Moderation_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listing-publish-moderation', __( 'Moderation Queue', APP_TD ), VA_LISTING_PTYPE, 'side', 'high' );
	}

	function condition() {
		return ( isset( $_GET['post'] ) && get_post_status( $_GET['post'] ) == 'pending' );
	}

	public function display( $post ) {

		echo html( 'p', array(), __( 'You must approve this listing before it can be published.', APP_TD ) );

		echo html( 'input', array(
			'type' => 'submit',
			'class' => 'button-primary',
			'value' => __( 'Accept', APP_TD ),
			'name' => 'publish',
			'style' => 'padding-left: 30px; padding-right: 30px; margin-right: 20px; margin-left: 15px;',
		) );

		echo html( 'a', array(
			'class' => 'button',
			'style' => 'padding-left: 30px; padding-right: 30px;',
			'href' => get_delete_post_link( $post->ID ),
		), __( 'Reject', APP_TD ) );

		echo html( 'p', array(
				'class' => 'howto'
			), __( 'Rejecting a listing sends it to the trash.', APP_TD ) );

	}
}


/**
 * Listing Author Metabox
 */
class VA_Listing_Author_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listingauthordiv', __( 'Author', APP_TD ), VA_LISTING_PTYPE, 'side', 'low' );
	}

	public function display( $post ) {
		global $user_ID;
		?>
		<label class="screen-reader-text" for="post_author_override"><?php _e('Author'); ?></label>
		<?php
		wp_dropdown_users( array(
			/* 'who' => 'authors', */
			'name' => 'post_author_override',
			'selected' => empty( $post->ID ) ? $user_ID : $post->post_author,
			'include_selected' => true
		) );
	}
}


/**
 * Custom Forms Metabox
 */
class VA_Custom_Forms_Metabox extends APP_Meta_Box {

	public $post_type;
	public $taxonomy;

	public function __construct( $post_type, $taxonomy ) {
		$this->post_type = $post_type;
		$this->taxonomy = $taxonomy;
		$post = get_post( $this->get_post_id() );

		if ( $post && $post->post_type === $post_type ) {
			add_action( 'post_edit_form_tag' , array( $this, 'post_edit_form_tag' ) );
		}

		parent::__construct( $this->post_type . '-custom-forms', __( 'Custom Forms', APP_TD ), $this->post_type, 'normal' );
	}

	function post_edit_form_tag() {
		echo ' enctype="multipart/form-data"';
	}

	public function get_post_id() {
		if ( ! empty( $_GET['post'] ) ) {
			return $_GET['post'];
		} else if ( ! empty( $_POST['ID'] ) ) {
			return $_POST['ID'];
		} else {
			return 0;
		}
	}

	public function hide() {
		?>
		<style>
			#<?php echo $this->post_type; ?>-custom-forms { display: none; }
		</style>
		<?php
	}

	public function display( $post ) {

		$form_fields = $this->form_fields();

		if ( empty( $form_fields ) || ! $post_id = $this->get_post_id() ) {
			return $this->hide();
		}

		$post = is_object( $post ) ? $post : get_post( $post_id );

		$form_data = get_post_custom( $post->ID );
		foreach ( $form_data as $key => $values ) {
			if ( count( $form_data[ $key ] ) > 1 ) {
				$form_data[ $key ] = $form_data[ $key ];
			} else {
				$form_data[ $key ] = $form_data[ $key ][0];
			}
		}

		$form = $this->table( $form_fields, $form_data );

		echo $form;
		the_files_editor( $post_id );
	}

	function get_the_terms( $post_id = 0 ) {
		$post_id = $post_id ? $post_id : get_the_ID();

		$_terms = get_the_terms( $post_id, $this->taxonomy );

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

	public function form_fields() {
		if ( ! $post_id = $this->get_post_id() ) {
			return array();
		}

		$categories = $this->get_the_terms( $post_id );
		$categories = array_keys( $categories );

		$fields = array();
		foreach ( $categories as $category ) {
			foreach ( va_get_fields_for_cat( $category, $this->taxonomy ) as $field ) {
				$desc = '';
				if ( 'file' == $field['type'] ) {
					if ( va_field_uploads( $post_id, $field ) >= 1 ) {
						continue;
					} else {
						$desc = sprintf( __( 'Allowed extensions: %1$s', APP_TD ), $field['extensions'] );
					}
				}
				$fields[ $field['name'] ] = $field;
				$fields[ $field['name'] ]['title'] = $field['desc'];
				$fields[ $field['name'] ]['desc'] = $desc;
				$fields[ $field['name'] ]['desc_pos'] = 'after';
				$fields[ $field['name'] ]['cat'] = $category;
			}
		}

		return $fields;
	}

	function save( $post_id ) {
		$categories = $this->get_the_terms( $post_id );
		$categories = array_keys( $categories );
		va_update_form_builder( $categories, $post_id, $this->taxonomy );
		va_handle_files( $post_id, $categories, $this->taxonomy );
	}
}


/**
 * Listing Custom Forms Metabox
 */
class VA_Listing_Custom_Forms_Metabox extends VA_Custom_Forms_Metabox {

	public function __construct() {
		parent::__construct( VA_LISTING_PTYPE, VA_LISTING_CATEGORY );
	}

}


/**
 * Listing Attachments Metabox
 */
class VA_Listing_Attachments_Metabox extends APP_Post_Attachments_Metabox {

	public function __construct() {
		parent::__construct( 'listing-attachments', __( 'Listing Attachments', APP_TD ), VA_LISTING_PTYPE );
	}

	public function get_attachments( $post ) {
		return va_get_post_attachments( $post->ID, $limit = -1, VA_ATTACHMENT_GALLERY );
	}

}

