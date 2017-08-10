<?php
/**
 * Theme specific widgets or widget overrides
 *
 * @package Vantage\Widgets
 * @author  AppThemes
 * @since   Vantage 1.0
 */


/**
 * Create Listing Button Widget
 */
class VA_Widget_Create_Listing_Button extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'A button for creating a new listing', APP_TD )
		);

		parent::__construct( 'create_listing_button', __( 'Vantage Create Listing Button', APP_TD ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$url = va_get_listing_create_url();

		echo $before_widget;
		echo html_link( $url, __( 'Add a business now', APP_TD ) );
		echo $after_widget;
	}
}


/**
 * Multiple Listings/Events Map Widget
 */
class VA_Widget_Listings_Events_Map extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'A map containing the locations of multiple listings and/or events. Use in List Page Top Sidebar.', APP_TD )
		);

		parent::__construct( 'listings_events_map', __( 'Vantage Multiple Listings/Events Map', APP_TD ), $widget_ops );
	}

	function widget( $args, $instance ) {

		if ( is_singular( VA_LISTING_PTYPE ) || ( va_events_enabled() && is_singular( VA_EVENT_PTYPE ) ) ) {
			appthemes_display_notice( 'error', __( 'The \'Vantage Multiple Listing/Event Map\' widget must only be used on the \'List Page Top Sidebar\'', APP_TD ) );
			return;
		}

		$attr['id'] = 'listings-events-map';
		$attr['class'] = 'listings-events-map rounded';

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';

		extract( $args );

		appthemes_load_map_provider();

		echo $before_widget;

		?>
			<div id="header-map" class="row">
				<?php
					if ( $title ) {
						echo $before_title . $title . $after_title;
					}
				?>
				<div id="header-map-cont" class="rounded">
					<div id="listings-events-map-cont">
						<div class="map_corner tl"></div>
						<div class="map_corner tr"></div>
						<?php echo html( 'div', $attr ); ?>
						<div class="map_corner bl"></div>
						<div class="map_corner br"></div>
					</div>
				</div>
			</div>

			<script type="text/javascript">
				jQuery(function() {

					var <?php echo esc_js( str_replace( '-', '_', $widget_id ) ); ?>_markers_opts = [];
					jQuery('article').each(function(){
						var lat = jQuery(this).data('lat');
						var lng = jQuery(this).data('lng');

						if ( lat == 0 || lng == 0 ) {
							return true;
						}

						if ( typeof lat == 'undefined' || typeof lng == 'undefined' ) {
							return true;
						}

						var permalink = jQuery(this).find('a[rel=bookmark]');

						if ( jQuery(this).hasClass('listing') ) {
							var icon_color = 'teal';
							var icon_shape = 'round';
						} else {
							var icon_color = 'red';
							var icon_shape = 'square';
						}
						var marker = {
							"lat" : lat,
							"lng" : lng,
							'marker_text' : permalink.attr('title'),
							'anchor' : permalink.attr('href'),
							'icon_color' : icon_color,
							'icon_shape' : icon_shape
						}
						<?php echo esc_js( str_replace( '-', '_', $widget_id ) ); ?>_markers_opts.push( marker);
					});


					if ( <?php echo esc_js( str_replace( '-', '_', $widget_id ) ); ?>_markers_opts.length > 0 ) {
						jQuery('#header-map').slideDown(10, function(){
							jQuery('#<?php echo esc_js( $attr['id'] ); ?>').appthemes_map({
								zoom: 15,
								markers: <?php echo esc_js( str_replace( '-', '_', $widget_id ) ); ?>_markers_opts,
								center_lat: <?php echo esc_js( str_replace( '-', '_', $widget_id ) ); ?>_markers_opts[0].lat,
								center_lng: <?php echo esc_js( str_replace( '-', '_', $widget_id ) ); ?>_markers_opts[0].lng
							});
						});
					}

				});
			</script>
		<?php

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'text' => '' ) );
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
<?php

	}
}


/**
 * Single Listing/Event Map Widget
 */
class VA_Widget_Listing_Map extends WP_Widget {

	const AJAX_ACTION = 'vantage_listing_geocode';

	function __construct() {
		$widget_ops = array(
			'description' => __( 'A map containing the location of a single listing or event. Use in Single Listing or Single Event Sidebar.', APP_TD )
		);

		parent::__construct( 'listing_event_map', __( 'Vantage Single Listing/Event Map', APP_TD ), $widget_ops );

	}

	function widget( $args, $instance ) {

		if ( ! is_singular( VA_LISTING_PTYPE ) && ( va_events_enabled() && ! is_singular( VA_EVENT_PTYPE ) ) ) {
			echo '<div class="row" style="position:relative;bottom:50px;">';
			appthemes_display_notice( 'error', __( 'The \'Vantage Single Listing/Event Map\' widget must only be used on the \'Single Listing Sidebar\' or \'Single Event Sidebar\'', APP_TD ) );
			echo '</div>';
			return;
		}

		$post_id = get_queried_object_id();

		$coord = va_geocode_address( $post_id, false );

		if ( ! empty( $coord->lat ) && $coord->lat !== '0.000000' ) {
			$lat = $coord->lat;
			$lng = $coord->lng;
		} else {
			return false;
		}

		$attr['id'] = 'listing-event-map';

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$zoom = ! empty( $instance['zoom'] ) ? $instance['zoom'] : 15;

		$directions = false;
		if ( 'google' == APP_Map_Provider_Registry::get_active_map_provider()->identifier() ) {
			$directions = ! empty( $instance['directions'] ) ? true : false;
		}

		extract( $args );

		echo $before_widget;

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		appthemes_load_map_provider();

		if ( is_singular( VA_LISTING_PTYPE ) ) {
			$icon_color = 'teal';
			$icon_shape = 'round';
		} else {
			$icon_color = 'red';
			$icon_shape = 'square';
		}

		if ( $directions ) {
			echo html( 'h1', array( 'id' => 'map_directions_title' ), get_the_title( $post_id ) );
		}

		echo html( 'div', $attr );

		if ( $directions ) {

			$html = html( 'label', array(), __( 'Get Directions From: ', APP_TD ) . html( 'br' , array() ) . html( 'input', array( 'type' => 'text', 'id' => 'directions_from', 'placeholder' => __( 'city, country', APP_TD ) ) ) );

			$html .= html( 'input', array( 'type' => 'button', 'id' => 'get_directions', 'value' => __( 'Get Directions', APP_TD ) ) );

			$html .= html( 'input', array( 'type' => 'button', 'id' => 'print_directions', 'value' => __( 'Print Directions', APP_TD ) ) );

			echo html( 'div', array( 'id' => 'directions_from_address' ), $html );

			echo html( 'div', array( 'id' => 'directions_panel' ) );
		}

		$css_link = '<link id="va-print-directions-css" media="print" type="text/css" href="' . get_template_directory_uri() . '/styles/google-directions-print.css" rel="stylesheet">';
		?>
			<script type="text/javascript">
				jQuery(function() {
					var <?php echo esc_js( str_replace( '-', '_', $widget_id ) ) ; ?>_markers_opts = [
						{
							"lat" : <?php echo $lat; ?>,
							"lng" : <?php echo $lng; ?>,
							'icon_color' : '<?php echo $icon_color; ?>',
							'icon_shape' : '<?php echo $icon_shape; ?>'
						}
					];

					jQuery('#<?php echo esc_js( $attr['id'] ); ?>').appthemes_map({
						zoom: <?php echo $zoom; ?>,
						auto_zoom: false,
						<?php if ( $directions ) { ?>
							directions: true,
							get_directions_btn: 'get_directions',
							directions_from: 'directions_from',
							directions_panel: 'directions_panel',
							end_address: '<?php echo esc_js( get_post_meta( $post_id, 'address', true ) ); ?>',
							print_directions_btn: 'print_directions',
						<?php } ?>
						markers: <?php echo esc_js( str_replace( '-', '_', $widget_id ) ); ?>_markers_opts,
						center_lat: <?php echo $lat; ?>,
						center_lng: <?php echo $lng; ?>
					});

					jQuery( document ).on( 'click', '#print_directions', function() {
						jQuery('head').append('<?php echo $css_link; ?>');
						setTimeout( function() {
							window.print();
							jQuery('#va-print-directions-css').remove();
						}, 500);
					} );
				});
			</script>
		<?php

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['zoom'] = strip_tags( $new_instance['zoom'] );
		if ( 'google' == APP_Map_Provider_Registry::get_active_map_provider()->identifier() ) {
			$instance['directions'] = $new_instance['directions'];
		} else {
			$instance['directions'] = 0;
		}
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'text' => '' ) );
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$zoom = isset( $instance['zoom'] ) ? esc_attr( $instance['zoom'] ) : '';
		if ( 'google' == APP_Map_Provider_Registry::get_active_map_provider()->identifier() ) {
			$directions = isset( $instance['directions'] ) ? (bool) $instance['directions'] : false;
		}
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'zoom' ); ?>"><?php _e( 'Map Zoom:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'zoom' ); ?>" name="<?php echo $this->get_field_name( 'zoom' ); ?>" type="text" size="2" value="<?php echo $zoom; ?>" /></p>
		<?php if ( 'google' == APP_Map_Provider_Registry::get_active_map_provider()->identifier() ) { ?>
		<p><label for="<?php echo $this->get_field_id( 'directions' ); ?>">
		<input type="checkbox" value="1" class="checkbox" id="<?php echo $this->get_field_id( 'directions' ); ?>" name="<?php echo $this->get_field_name( 'directions' ); ?>"<?php checked( $directions ); ?> />
		<?php _e( 'Show driving directions', APP_TD ); ?></label></p>
		<?php } ?>
<?php

	}
}


/**
 * Related Categories Widget
 */
class VA_Widget_Categories extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'Related Listing Categories', APP_TD )
		);

		parent::__construct( 'listing_categories', __( 'Vantage Related Categories', APP_TD ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Categories', APP_TD ) : $instance['title'], $instance, $this->id_base );
		$show_count = $instance['count'] ? '1' : '0';
		$app_pad_counts = $show_count;
		$pad_counts = false;
		$hierarchical = 1;
		$orderby = 'name';

		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		$taxonomy = VA_LISTING_CATEGORY;
		$hide_empty = false;
		$depth = 1;

		$curr_cat = null;

		if ( is_home() ) {
			$child_of = 0;

		} elseif ( is_page() ) {

			$child_of = 0;

		} elseif ( is_tax( VA_LISTING_CATEGORY ) ) {

			$term_slug = get_query_var( VA_LISTING_CATEGORY );

			$term_info = get_term_by( 'slug', $term_slug, VA_LISTING_CATEGORY );
			$child_of = $term_info->term_id;

			$args = array(
				'taxonomy' => VA_LISTING_CATEGORY,
				'child_of' => $child_of,
				'hide_empty'=> 0,
			);

			$term_children = get_categories( $args );
			if ( empty( $term_children ) ) {
				//$cat =
				$category_id = $child_of;
				$category_tax = VA_LISTING_CATEGORY;
				$category_alt = get_term( $category_id, $category_tax );
				$cat_parent = $category_alt->parent;
				$child_of = $cat_parent;
			}


		} elseif ( is_singular( VA_LISTING_PTYPE ) ) {
			$the_terms = get_the_terms( get_the_ID(), VA_LISTING_CATEGORY );

			foreach ( $the_terms as $term ) {
				$first_term = $term;
				break;
			}

			$cat = $first_term->term_id;
			$child_of = $cat;
			$curr_cat = $cat;

			$category_id = $cat;
			$category_tax = VA_LISTING_CATEGORY;
			$category_alt = get_term( $category_id, $category_tax );
			$cat_parent = $category_alt->parent;
			$child_of = $cat_parent;
		} else {
			$child_of = 0;
		}

		$cat_args = compact( 'orderby', 'show_count', 'pad_counts', 'app_pad_counts', 'hierarchical', 'taxonomy', 'child_of', 'hide_empty', 'depth' );
?>
		<ul>
<?php
		$cat_args['title_li'] = '';
		wp_list_categories( apply_filters( 'widget_categories_args', $cat_args ) );
?>
		</ul>
<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['count'] = ! empty( $new_instance['count'] ) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = esc_attr( $instance['title'] );
		$count = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>



		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>"<?php checked( $count ); ?> />
		<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show post counts', APP_TD ); ?></label><br />
<?php
	}

}


/**
 * Recent Reviews Widget
 */
class VA_Widget_Recent_Reviews extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'The most recent reviews', APP_TD )
		);
		parent::__construct( 'recent_reviews', __( 'Vantage Recent Reviews', APP_TD ), $widget_ops );

		$this->alt_option_name = 'va_widget_recent_reviews';

		if ( is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'wp_head', array( &$this, 'recent_comments_style' ) );
		}

		add_action( 'comment_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'transition_comment_status', array( &$this, 'flush_widget_cache' ) );
	}

	function recent_comments_style() {
		// Temp hack #14876
		if ( ! current_theme_supports( 'widgets' ) || ! apply_filters( 'show_recent_comments_widget_style', true, $this->id_base ) ) {
			return;
		}
?>
	<style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
<?php
	}

	function flush_widget_cache() {
		wp_cache_delete( 'va_widget_recent_reviews', 'widget' );
	}

	function widget( $args, $instance ) {

		$cache = wp_cache_get('va_widget_recent_reviews', 'widget');

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		extract( $args, EXTR_SKIP );
		$output = '';
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Recent Reviews', APP_TD ) : $instance['title'] );

		if ( ! $number = absint( $instance['number'] ) ) {
			$number = 5;
		}

		$reviews = va_get_reviews( array(
			'number' => $number,
			'status' => 'approve',
			'post_status' => 'publish'
		) );


		$output .= $before_widget;
		if ( $title ) {
			$output .= $before_title . $title . $after_title;
		}

		$output .= '<ul>';
		if ( $reviews ) {
			foreach ( (array) $reviews as $review ) {
				$user = get_userdata( $review->user_id );

				$output .= '<li class="recent-review clear">' .
					'<div class="review-author">' .
					html_link( va_dashboard_url( 'reviews', $user->ID ), get_avatar( $user->ID, 45 ) ) .
					'</div>' .
					'<div class="review-content">' .
					'<div class="review-meta">' .
					'<h4 class="listing-title">' . html_link( get_permalink( $review->comment_post_ID ), get_the_title( $review->comment_post_ID ) ) . '</h4>' .
					'<div class="stars-cont">' .
					'<div class="stars stars-' . va_get_rating( $review->comment_ID ) . '"></div>' .
					'</div>' .
					'<span class="reviewer-date">' . va_get_the_author_reviews_link( $user->ID ) . ' ' . va_string_ago( $review->comment_date ) . '</span>' .
					'</div>' .
					'<div>' . apply_filters( 'comment_text', va_truncate( $review->comment_content, 120, '', " " ), $review ) . ' ' . html_link( va_get_review_link( $review->comment_ID ), __( ' Read More', APP_TD ) ) . '</div>' .
					'</div>' .
					'</li>';
			}
		}
		$output .= '</ul>';
		$output .= $after_widget;

		echo $output;

		$cache[ $args['widget_id'] ] = $output;
		wp_cache_set( 'va_widget_recent_reviews', $cache, 'widget' );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['va_widget_recent_reviews'] ) ) {
			delete_option( 'va_widget_recent_reviews' );
		}

		return $instance;
	}

	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of reviews to show:', APP_TD ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
}


/**
 * Recent Listings Widget
 */
class VA_Widget_Recent_Listings extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'The most recent listings', APP_TD )
		);
		parent::__construct( 'recent_listings', __( 'Vantage Recent Listings', APP_TD ), $widget_ops );

		$this->alt_option_name = 'va_widget_recent_listings';

		add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );
	}

	function widget( $args, $instance ) {
		$cache = wp_cache_get( 'widget_recent_listings', 'widget' );

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();
		extract( $args );

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Recent Listings', APP_TD ) : $instance['title'], $instance, $this->id_base );
		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
			$number = 10;
		}

		$r = new WP_Query( array( 'post_type' => VA_LISTING_PTYPE, 'posts_per_page' => $number, 'no_found_rows' => true, 'post_status' => 'publish', 'ignore_sticky_posts' => true ) );
		if ( $r->have_posts() ) :
			echo $before_widget;
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}
		?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
		<li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr( get_the_title() ? get_the_title() : get_the_ID() ); ?>"><?php if ( get_the_title() ) the_title(); else the_ID(); ?></a></li>
		<?php endwhile; ?>
		</ul>
		<?php
			echo $after_widget;
			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();

		endif;

	$cache[ $args['widget_id'] ] = ob_get_flush();
	wp_cache_set( 'va_widget_recent_listings', $cache, 'widget' );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['va_widget_recent_listings'] ) ) {
			delete_option( 'va_widget_recent_listings' );
		}

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete( 'va_widget_recent_listings', 'widget' );
	}

	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of listings to show:', APP_TD ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
}


/**
 * Social Connect Widget
 */
class VA_Widget_Connect extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'A set of icons to link to many social networks', APP_TD )
		);

		parent::__construct( 'connect', __( 'Vantage Connect', APP_TD ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Connect', APP_TD ) : $instance['title'], $instance, $this->id_base );


		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		$img_url = get_template_directory_uri() . '/images/';

		echo '<ul class="connect">';

		if ( $instance['twitter_inc'] && ! empty( $instance['twitter'] ) ) {
			echo '<li><a class="" href="' . esc_url( $instance['twitter'] ) . '" target="_blank"><img src="' . $img_url . 'connect-twitter.png" /></a></li>';
		}
		if ( $instance['facebook_inc'] && ! empty( $instance['facebook'] ) ) {
			echo '<li><a class="" href="' . esc_url( $instance['facebook'] ) . '" target="_blank"><img src="' . $img_url . 'connect-facebook.png" /></a></li>';
		}
		if ( $instance['linkedin_inc'] && ! empty( $instance['linkedin'] ) ) {
			echo '<li><a class="" href="' . esc_url( $instance['linkedin'] ) . '" target="_blank"><img src="' . $img_url . 'connect-linkedin.png" /></a></li>';
		}
		if ( $instance['youtube_inc'] && ! empty( $instance['youtube'] ) ) {
			echo '<li><a class="" href="' . esc_url( $instance['youtube'] ) . '" target="_blank"><img src="' . $img_url . 'connect-youtube.png" /></a></li>';
		}
		if ( $instance['google_inc'] && ! empty( $instance['google'] ) ) {
			echo '<li><a class="" href="' . esc_url( $instance['google'] ) . '" target="_blank"><img src="' . $img_url . 'connect-google.png" /></a></li>';
		}
		if ( $instance['rss_inc'] && ! empty( $instance['rss'] ) ) {
			echo '<li><a class="" href="' . esc_url( $instance['rss'] ) . '" target="_blank"><img src="' . $img_url . 'connect-rss.png" /></a></li>';
		}
		echo '</ul>';

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['twitter'] = strip_tags( $new_instance['twitter'] );
		$instance['twitter_inc'] = !empty( $new_instance['twitter_inc'] ) ? 1 : 0;
		$instance['facebook'] = strip_tags( $new_instance['facebook'] );
		$instance['facebook_inc'] = !empty( $new_instance['facebook_inc'] ) ? 1 : 0;
		$instance['linkedin'] = strip_tags( $new_instance['linkedin'] );
		$instance['linkedin_inc'] = !empty( $new_instance['linkedin_inc'] ) ? 1 : 0;
		$instance['youtube'] = strip_tags( $new_instance['youtube'] );
		$instance['youtube_inc'] = !empty( $new_instance['youtube_inc'] ) ? 1 : 0;
		$instance['google'] = strip_tags( $new_instance['google'] );
		$instance['google_inc'] = !empty( $new_instance['google_inc'] ) ? 1 : 0;
		$instance['rss'] = strip_tags( $new_instance['rss'] );
		$instance['rss_inc'] = !empty( $new_instance['rss_inc'] ) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$twitter = isset( $instance['twitter'] ) ? esc_attr( $instance['twitter'] ) : '';
		$twitter_inc = isset( $instance['twitter_inc'] ) ? (bool) $instance['twitter_inc'] :false;
		$facebook = isset( $instance['facebook'] ) ? esc_attr( $instance['facebook'] ) : '';
		$facebook_inc = isset( $instance['facebook_inc'] ) ? (bool) $instance['facebook_inc'] :false;
		$linkedin = isset( $instance['linkedin'] ) ? esc_attr( $instance['linkedin'] ) : '';
		$linkedin_inc = isset( $instance['linkedin_inc'] ) ? (bool) $instance['linkedin_inc'] :false;
		$youtube = isset( $instance['youtube'] ) ? esc_attr( $instance['youtube'] ) : '';
		$youtube_inc = isset( $instance['youtube_inc'] ) ? (bool) $instance['youtube_inc'] :false;
		$google = isset( $instance['google'] ) ? esc_attr( $instance['google'] ) : '';
		$google_inc = isset( $instance['google_inc'] ) ? (bool) $instance['google_inc'] :false;
		$rss = isset( $instance['rss'] ) ? esc_attr( $instance['rss'] ) : '';
		$rss_inc = isset( $instance['rss_inc'] ) ? (bool) $instance['rss_inc'] :false;
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'twitter' ); ?>"><?php _e( 'Twitter URL:', APP_TD ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'twitter' ); ?>" name="<?php echo $this->get_field_name( 'twitter' ); ?>" type="text" value="<?php echo $twitter; ?>" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'twitter_inc' ); ?>" name="<?php echo $this->get_field_name( 'twitter_inc' ); ?>"<?php checked( $twitter_inc ); ?> />
			<label for="<?php echo $this->get_field_id( 'twitter_inc' ); ?>"><?php _e( 'Show Twitter Button?', APP_TD ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'facebook' ); ?>"><?php _e( 'Facebook URL:', APP_TD ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'facebook' ); ?>" name="<?php echo $this->get_field_name( 'facebook' ); ?>" type="text" value="<?php echo $facebook; ?>" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'facebook_inc' ); ?>" name="<?php echo $this->get_field_name( 'facebook_inc' ); ?>"<?php checked( $facebook_inc ); ?> />
			<label for="<?php echo $this->get_field_id( 'facebook_inc' ); ?>"><?php _e( 'Show Facebook Button?', APP_TD ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'linkedin' ); ?>"><?php _e( 'LinkedIn URL:', APP_TD ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'linkedin' ); ?>" name="<?php echo $this->get_field_name( 'linkedin' ); ?>" type="text" value="<?php echo $linkedin; ?>" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'linkedin_inc' ); ?>" name="<?php echo $this->get_field_name( 'linkedin_inc' ); ?>"<?php checked( $linkedin_inc ); ?> />
			<label for="<?php echo $this->get_field_id( 'linkedin_inc' ); ?>"><?php _e( 'Show LinkedIn Button?', APP_TD ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'youtube' ); ?>"><?php _e( 'YouTube URL:', APP_TD ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'youtube' ); ?>" name="<?php echo $this->get_field_name( 'youtube' ); ?>" type="text" value="<?php echo $youtube; ?>" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'youtube_inc' ); ?>" name="<?php echo $this->get_field_name( 'youtube_inc' ); ?>"<?php checked( $youtube_inc ); ?> />
			<label for="<?php echo $this->get_field_id( 'youtube_inc' ); ?>"><?php _e( 'Show YouTube Button?', APP_TD ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'google' ); ?>"><?php _e( 'Google URL:', APP_TD ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'google' ); ?>" name="<?php echo $this->get_field_name( 'google' ); ?>" type="text" value="<?php echo $google; ?>" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'google_inc' ); ?>" name="<?php echo $this->get_field_name( 'google_inc' ); ?>"<?php checked( $google_inc ); ?> />
			<label for="<?php echo $this->get_field_id( 'google_inc' ); ?>"><?php _e( 'Show Google Button?', APP_TD ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'rss' ); ?>"><?php _e( 'RSS URL:', APP_TD ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'rss' ); ?>" name="<?php echo $this->get_field_name( 'rss' ); ?>" type="text" value="<?php echo $rss; ?>" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'rss_inc' ); ?>" name="<?php echo $this->get_field_name( 'rss_inc' ); ?>"<?php checked( $rss_inc ); ?> />
			<label for="<?php echo $this->get_field_id( 'rss_inc' ); ?>"><?php _e( 'Show RSS Button?', APP_TD ); ?></label>
		</p>


<?php
	}
}


/**
 * Sidebar Ad Widget
 */
class VA_Widget_Sidebar_Ad extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'An HTML/text widget for placing a sidebar ad. Fits 250 pixel wide banner perfectly. ', APP_TD )
		);

		parent::__construct( 'sidebar_ad', __( 'Vantage Sidebar Ad', APP_TD ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';

		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		echo $instance['text'];
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['text'] = $new_instance['text'];
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'text' => '' ) );
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$text = esc_textarea( $instance['text'] );
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo $text; ?></textarea>
<?php
	}

}


/**
 * 468x60 Ad Banner Widget
 */
class VA_Widget_Listings_Ad extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'HTML/Text widget for 468x60 ad banner. Can be used in header and at bottom of listings pages (home page, search results, categories, etc).', APP_TD )
		);

		parent::__construct( 'listings_ad', __( 'Vantage 468x60 ad banner', APP_TD ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';

		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		echo $instance['text'];
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['text'] = $new_instance['text'];
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'text' => '' ) );
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$text = esc_textarea( $instance['text'] );
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo $text; ?></textarea>
<?php
	}

}


/**
 * Popular Categories Widget
 */
class VA_Widget_Popular_Categories extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'Popular Listing Categories', APP_TD )
		);

		parent::__construct( 'popular_listing_categories', __( 'Vantage Popular Categories', APP_TD ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Popular Categories', APP_TD ) : $instance['title'], $instance, $this->id_base );

		$taxonomy = VA_LISTING_CATEGORY;
		if ( ! $a = (int) $instance['amount'] ) {
			$a = 5;
		} elseif ( $a < 1 ) {
			$a = 1;
		}
		$s = 'count';
		$o = 'desc';

		$c = $instance['count'] ? '1' : '0';
		$h = 1;


		$top_cats = get_terms( $taxonomy, array( 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC', 'number' => $a, 'hierarchical' => false ) );
		$included_cats = implode( ",", $top_cats );

		$cat_args = array( 'taxonomy' => $taxonomy, 'include' => $included_cats, 'orderby' => $s, 'order' => $o, 'show_count' => $c, 'hide_empty' => false, 'hierarchical' => false, 'depth' => - 1, 'title_li' => '', );

		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		echo '<ul>';
		wp_list_categories( apply_filters( 'widget_categories_args', $cat_args ) );
		echo '</ul>';
		echo $after_widget;

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( stripslashes( $new_instance['title'] ) );
		$instance['amount'] = (int) $new_instance['amount'];
		$instance['count'] = ! empty( $new_instance['count'] ) ? 1 : 0;
		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'amount' => '' ) );
		$title = esc_attr( $instance['title'] );

		$count = isset( $instance['count'] ) ? (bool) $instance['count'] : false;

		if ( ! $amount = (int) $instance['amount'] ) {
			$amount = 5;
		}

		if ( $amount < 1 ) {
			$amount = 1;
		}

?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'amount' ); ?>"><?php _e( 'How Many Categories to Show?:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'amount' ); ?>" name="<?php echo $this->get_field_name( 'amount' ); ?>" type="text" value="<?php echo $amount; ?>" /></p>


		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>"<?php checked( $count ); ?> />
		<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show post counts', APP_TD ); ?></label><br />
<?php
	}

}



/**
 * Register widgets
 *
 * @return void
 */
function va_register_widgets() {
	register_widget( 'VA_Widget_Create_Listing_Button' );
	register_widget( 'VA_Widget_Listing_Map' );
	register_widget( 'VA_Widget_Listings_Events_Map' );
	register_widget( 'VA_Widget_Categories' );
	register_widget( 'VA_Widget_Recent_Reviews' );
	register_widget( 'VA_Widget_Recent_Listings' );
	register_widget( 'VA_Widget_Connect' );
	register_widget( 'VA_Widget_Sidebar_Ad' );
	register_widget( 'VA_Widget_Listings_Ad' );
	register_widget( 'VA_Widget_Popular_Categories' );

	unregister_widget( 'WP_Widget_Meta' );
}
add_action( 'widgets_init', 'va_register_widgets' );
