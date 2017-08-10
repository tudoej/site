<?php
/**
 * Theme functions file
 *
 * DO NOT MODIFY THIS FILE. Make a child theme instead: http://codex.wordpress.org/Child_Themes
 *
 * @package Vantage
 * @author AppThemes
 */

// Constants
define( 'VA_VERSION', '3.0.6' );

define( 'VA_META_KEY_PREFIX', 'va_' );

define( 'VA_LISTING_PTYPE', 'listing' );
define( 'VA_LISTING_CATEGORY', 'listing_category' );
define( 'VA_LISTING_TAG', 'listing_tag' );
define( 'VA_LISTING_FAVORITES', 'va_favorites' );

define( 'VA_REVIEWS_CTYPE', 'review' );
define( 'VA_REVIEWS_RATINGS', 'rating' );
define( 'VA_REVIEWS_PER_PAGE', 10 );

define( 'VA_ITEM_REGULAR', 'regular' );
define( 'VA_ITEM_FEATURED_HOME', 'featured-home' );
define( 'VA_ITEM_FEATURED_CAT', 'featured-cat' );
define( 'VA_ITEM_FEATURED', 'featured' );

define( 'VA_MAX_FEATURED', 5 );
define( 'VA_MAX_IMAGES', 5 );

define( 'VA_ATTACHMENT_FILE', 'file' );
define( 'VA_ATTACHMENT_GALLERY', 'gallery' );

define( 'APP_TD', 'vantage' );

global $va_options;

// Framework
require dirname(__FILE__) . '/framework/load.php';
require dirname(__FILE__) . '/theme-framework/load.php';

$load_files = array(
	'payments/load.php',
	'recurring/recurring-payments.php',
	'checkout/load.php',
	'events/load.php',
	'geo/load.php',
	'open-graph/load.php',
	'search-index/load.php',
	'utils.php',
	'options.php',
	'admin-bar.php',
	'capabilities.php',
	'categories.php',
	'core.php',
	'custom-forms.php',
	'custom-header.php',
	'custom-comment-type-helper.php',
	'custom-post-type-helper.php',
	'customizer.php',
	'dashboard.php',
	'deprecated.php',
	'emails.php',
	'favorites.php',
	'delete-listing.php',
	'featured.php',
	'files.php',
	'images.php',
	'listing-activate.php',
	'listing-claim.php',
	'listing-form.php',
	'listing-purchase.php',
	'listing-renew.php',
	'listing-status.php',
	'locale.php',
	'payments.php',
	'reviews.php',
	'template-tags.php',
	'views.php',
	'views-checkout.php',
	'widgets.php',
	'social.php',
);
appthemes_load_files( dirname( __FILE__ ) . '/includes/', $load_files );

$load_classes = array(
	'APP_User_Profile',
	'VA_Blog_Archive',
	'VA_Gateway_Select',
	'VA_Gateway_Process',
	'VA_Home_Archive',
	'VA_Listing_Archive',
	'VA_Listing_Categories',
	'VA_Listing_Claim',
	'VA_Listing_Create',
	'VA_Listing_Dashboard',
	'VA_Listing_Edit',
	'VA_Listing_Info_Edit',
	'VA_Listing_Info_Purchase',
	'VA_Listing_Purchase',
	'VA_Listing_Renew',
	'VA_Listing_Search',
	'VA_Listing_Single',
	'VA_Listing_Taxonomy',
	'VA_Order_Summary',
	'VA_Select_Plan_New',
	'VA_Select_Plan_Existing',
	'VA_Login_Redirect',
);
appthemes_add_instance( $load_classes );

global $va_locale;
$va_locale = new VA_Locale;

APP_Mail_From::init();

// Admin only
if ( is_admin() ) {
	require_once( APP_FRAMEWORK_DIR . '/admin/importer.php' );

	$load_files = array(
		'admin.php',
		'class-attachments-metabox.php',
		'category-surcharge.php',
		'dashboard.php',
		'featured.php',
		'listing-list.php',
		'listing-single.php',
		'pricing.php',
		'settings.php',
		'addons-mp/load.php',
	);
	appthemes_load_files( dirname( __FILE__ ) . '/includes/admin/', $load_files );

	$load_classes = array(
		'APP_System_Info',
		'VA_Dashboard',
		'VA_Listing_Author_Metabox',
		'VA_Listing_Claim_Moderation_Metabox',
		'VA_Listing_Claimable_Metabox',
		'VA_Listing_Contact_Metabox',
		'VA_Listing_Custom_Forms_Metabox',
		'VA_Listing_Location_Metabox',
		'VA_Listing_Pricing_Metabox',
		'VA_Listing_Publish_Moderation_Metabox',
		'VA_Listing_Reviews_Status_Metabox',
		'VA_Listing_Attachments_Metabox',
		// order of following classes is important
		'VA_Pricing_General_Metabox',
		'VA_Pricing_Duration_Period_Metabox',
		'VA_Pricing_Addon_Metabox',
	);
	appthemes_add_instance( $load_classes );


	add_filter( 'manage_' . VA_LISTING_PTYPE . '_posts_columns', 'va_listing_manage_columns' );


	$va_settings_admin = new VA_Settings_Admin( $va_options );
	add_action( 'admin_init', array( $va_settings_admin, 'init_integrated_options' ), 10 );

}

add_theme_support( 'app-search-index' );

add_theme_support( 'app-versions', array(
	'update_page' => 'admin.php?page=app-settings&firstrun=1',
	'current_version' => VA_VERSION,
	'option_key' => 'vantage_version',
) );

add_theme_support( 'app-wrapping' );

add_theme_support( 'app-login', array(
	'login' => 'form-login.php',
	'register' => 'form-registration.php',
	'recover' => 'form-password-recovery.php',
	'reset' => 'form-password-reset.php',
) );

add_theme_support( 'app-open-graph', array(
	'default_image' => va_get_default_image(),
) );

add_theme_support( 'app-payments', array(
	'items' => array(
		array(
			'type' => VA_ITEM_REGULAR,
			'title' => __( 'Regular Listing', APP_TD ),
			'meta' => array(
				'price' => $va_options->listing_price
			)
		),
		array(
			'type' => VA_ITEM_FEATURED_HOME,
			'title' => __( 'Feature on Homepage', APP_TD ),
			'meta' => array(
				'price' => $va_options->addons[ VA_ITEM_FEATURED_HOME ]['price']
			)
		),
		array(
			'type' => VA_ITEM_FEATURED_CAT,
			'title' => __( 'Feature on Category', APP_TD ),
			'meta' => array(
				'price' => $va_options->addons[ VA_ITEM_FEATURED_CAT ]['price']
			)
		)
	),
	'items_post_types' => array( VA_LISTING_PTYPE ),
	'options' => $va_options,
) );

add_theme_support( 'app-price-format', array(
	'currency_default' => $va_options->currency_code,
	'currency_identifier' => $va_options->currency_identifier,
	'currency_position' => $va_options->currency_position,
	'thousands_separator' => $va_options->thousands_separator,
	'decimal_separator' => $va_options->decimal_separator,
	'hide_decimals' => (bool) ( ! $va_options->decimal_separator ),
) );

add_theme_support( 'app-events', array(
	'meta_key_prefix' => VA_META_KEY_PREFIX,
	'post_type' => 'event',
	'category' => 'event_category',
	'tag' => 'event_tag',
	'day' => 'event_day',
	'comment_type' => 'event_comment',
	'attendee_connection' => 'event-attendee',
	'options' => $va_options
) );

add_theme_support( 'app-geo-2', array(
	'options' => $va_options,
) );

add_theme_support( 'app-term-counts', array(
	'post_type' => array( VA_LISTING_PTYPE ),
	'post_status' => array( 'publish' ),
	'taxonomy' => array( VA_LISTING_CATEGORY ),
) );

add_theme_support( 'app-feed', array(
	'post_type' => VA_LISTING_PTYPE,
	'blog_template' => 'index.php',
	'alternate_feed_url' => '',
) );

add_theme_support( 'app-html-term-description', array(
	'taxonomy' => array( VA_LISTING_CATEGORY, VA_LISTING_TAG, 'category', 'post_tag' )
) );

add_theme_support( 'app-addons-mp', array(
	'product' => array( 'vantage' ),
) );

add_theme_support( 'app-require-updater', true );

// Taxonomies need to be registered before the post type, in order for the rewrite rules to work
add_action( 'init', 'va_register_taxonomies', 8 );
add_action( 'init', 'va_register_post_types', 9 );

// Flush rewrite rules if the related transient is set
add_action( 'init','va_check_rewrite_rules_transient', 10 );

// Add a very low priority action to make sure any extra settings have been added to the permalinks global
add_action( 'admin_init', 'va_enable_permalink_settings', 999999 );

add_action( 'user_contactmethods', 'va_user_contact_methods' );
if ( !is_admin() ) {
	add_action( 'user_profile_update_errors', 'va_user_update_profile', 10, 3 );
}

add_action( 'template_redirect', 'va_add_style' );
add_action( 'template_redirect', 'va_add_scripts' );

add_action( 'appthemes_before_login_template', 'va_add_login_style' );

add_action( 'after_setup_theme', 'va_setup_theme' );

add_filter( 'wp_nav_menu_objects', 'va_disable_hierarchy_in_footer', 9, 2 );

add_filter( 'body_class', 'va_body_class' );

add_filter( 'excerpt_more', 'va_excerpt_more' );
add_filter( 'excerpt_length', 'va_excerpt_length' );
add_filter( 'the_excerpt', 'strip_tags' );

add_action( 'wp_login', 'va_redirect_to_front_page' );
add_action( 'app_login', 'va_redirect_to_front_page' );
add_action( 'login_enqueue_scripts', 'va_login_styling' );
add_filter( 'login_headerurl', 'va_login_logo_url' );
add_filter( 'login_headertitle', 'va_login_logo_url_title' );

// Pings 'update services' while publish listing.
add_action( 'publish_' . VA_LISTING_PTYPE, '_publish_post_hook', 5, 1 );

// ShareThis plugin compatibility
remove_filter( 'the_content', 'st_add_widget' );

// Social Connect plugin compatibility
add_action( 'app_login_pre_redirect', 'social_connect_grab_login_redirect' );

appthemes_init();
