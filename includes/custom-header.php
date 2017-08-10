<?php
/**
 * Implement an optional custom header for Vantage
 *
 * See http://codex.wordpress.org/Custom_Headers
 *
 * @package Vantage\Header
 * @author  AppThemes
 * @since   Vantage 1.3.3
 */

/**
 * Set up the WordPress core custom header arguments and settings.
 *
 * @uses add_theme_support() to register support for 3.4 and up.
 * @uses va_header_style() to style front-end.
 * @uses va_admin_header_style() to style wp-admin form.
 * @uses va_admin_header_image() to add custom markup to wp-admin form.
 *
 * @since Vantage 1.3.3
 */
function va_custom_header_setup() {
	$args = array(
		// Text color and image (empty to use none).
		'default-text-color'     => '#444444',
		'header-text'            => true,
		'default-image'          => appthemes_locate_template_uri( 'images/vantage-logo.png' ),

		'flex-height'            => true,
		'flex-width'             => true,

		// Set height and width.
		'height'                 => 70,
		'width'                  => 400,

		// Random image rotation off by default.
		'random-default'         => false,

		// Callbacks for styling the header and the admin preview.
		'wp-head-callback'       => 'va_header_style',
		'admin-preview-callback' => 'va_admin_header_image',
	);

	add_theme_support( 'custom-header', $args );
}
va_custom_header_setup();

/**
 * Style the header text displayed on the blog.
 *
 * get_header_textcolor() options: fff is default, hide text (returns 'blank'), or any hex value.
 *
 * @since Vantage 1.3.3
 */
function va_header_style() {
	$text_color = get_header_textcolor();

	// If we get this far, we have custom styles.
	?>
	<style type="text/css" id="va-header-css">
	<?php
		if ( !get_header_image() ) {
	?>
		#site-description {
			position: relative !important;
			top: inherit !important;
			left: inherit !important;
			padding-top: 15px;
		}
	<?php
		}
		// Has the text been hidden?
		if ( ! display_header_text() ) {
	?>
		.site-title,
		.site-description {
			position: absolute;
			clip: rect(1px 1px 1px 1px); /* IE7 */
			clip: rect(1px, 1px, 1px, 1px);
		}
	<?php
		// If the user has set a custom color for the text, use that.
		} else {
	?>
		.site-header h1 a,
		.site-header h1 a:hover,
		.site-header h2 {
			color: #<?php echo $text_color; ?>;
		}
		<?php } ?>

	</style>
	<?php
}

/**
 * Output markup to be displayed on the Appearance > Header admin panel.
 *
 * This callback overrides the default markup displayed there.
 *
 * @since Vantage 1.3.3
 */
function va_admin_header_image() {
	?>
	<div id="headimg">
		<?php
		$nologo = '';
		if ( ! display_header_text() )
			$style = ' style="display:none;"';
		else
			$style = ' style="color:#' . get_header_textcolor() . ';"';
		?>
		<?php $header_image = get_header_image();
		if ( ! empty( $header_image ) ) { ?>
			<img src="<?php echo esc_url( $header_image ); ?>" class="header-image" width="<?php echo get_custom_header()->width; ?>" height="<?php echo get_custom_header()->height; ?>" alt="" />
		<?php } elseif ( display_header_text() ) {
			$nologo = ' nologo'; ?>
			<h1 class="displaying-header-text">
				<a id="name"<?php echo $style; ?> onclick="return false;" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php bloginfo( 'name' ); ?>
				</a>
			</h1>
		<?php } ?>
		<?php if ( display_header_text() ) { ?>
			<h2 id="desc" class="displaying-header-text<?php echo $nologo; ?>"<?php echo $style; ?>><?php bloginfo( 'description' ); ?></h2>
		<?php } ?>
	</div>
<?php }