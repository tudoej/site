<?php
/**
 * Theme specific widgets or widget overrides
 *
 * @package Vantage\Events\Widgets
 * @author  AppThemes
 * @since   Vantage 1.2
 */


/**
 * Event Attendees Widget
 */
class VA_Widget_Event_Attendees extends WP_Widget {

	const AJAX_ACTION = 'va_event_attendance';

	function __construct() {
		$widget_ops = array(
			'description' => __( 'A widget showing the attendees for an event. Use in Single Event Sidebar.', APP_TD )
		);

		parent::__construct( 'event_attendees', __( 'Vantage Event Attendees', APP_TD ), $widget_ops );

		add_action( 'template_redirect', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( $this, 'handle_ajax' ) );

	}

	function enqueue_scripts() {
		if ( ! is_singular( VA_EVENT_PTYPE ) ) {
			return;
		}

		wp_enqueue_style( 'colorbox' );
		wp_enqueue_script( 'colorbox' );
	}

	public function handle_ajax() {
		if ( ! isset( $_POST['event_attendance'] ) && ! isset( $_POST['event_id'] ) && ! isset( $_POST['current_url'] ) ) {
			return;
		}

		if ( ! in_array( $_POST['event_attendance'], array( 'add', 'remove' ) ) ) {
			return;
		}

		$event_id = (int) $_POST['event_id'];
		$user_id = get_current_user_id();

		check_ajax_referer( 'event-attend-' . $event_id );

		$redirect = '';
		$status = 'success';

		if ( is_user_logged_in() ) {
			if ( 'add' == $_POST['event_attendance'] ) {
				$notice = __( 'You are now attending!', APP_TD );
				$p2p = $this->modify_attendance( $event_id, $user_id, 'add' );
			} else {
				$notice = __( 'Thanks, please attend again soon! :)', APP_TD );
				$p2p = $this->modify_attendance( $event_id, $user_id, 'remove' );
			}

			if ( is_wp_error( $p2p ) ) {
				$status = 'error';
				$notice = sprintf( __( "Could not modify your attendence to '%s' at this time.", APP_TD ), get_the_title( $event_id ) );
			}
		} else {
			$redirect = esc_url( $_POST['current_url'] );
			$status = 'error';
			$notice = sprintf( __( 'You must <a href="%1$s">login</a> to be able to attend an event.', APP_TD ), wp_login_url( $redirect ) );
		}

		ob_start();
		appthemes_display_notice( $status, $notice );
		$notice = ob_get_clean();

		$result = array(
			'link' 	 	=> $this->create_attendance_link( $event_id, $user_id ),
			'banner' 	=> $this->create_attendance_banner( $event_id, $user_id ),
			'attendees'	=> $this->create_attendance_list( $event_id ),
			'status' 	=> $status,
			'notice' 	=> $notice,
			'redirect' 	=> $redirect,
		);

		die ( json_encode( $result ) );

	}

	function modify_attendance( $event_id, $user_id, $action = 'add' ) {

		$attendee_count = count( va_get_event_attendees( $event_id ) );

		if ( 'add' == $action ) {
			$p2p = p2p_type( VA_EVENT_ATTENDEE_CONNECTION )->connect( $event_id, $user_id, array( 'date' => current_time( 'mysql' ) ) );
			update_post_meta( $event_id, VA_EVENT_ATTENDEES_META_KEY, ( $attendee_count + 1 ) );
		} else {
			$p2p = p2p_type( VA_EVENT_ATTENDEE_CONNECTION )->disconnect( $event_id, $user_id );
			update_post_meta( $event_id, VA_EVENT_ATTENDEES_META_KEY, max( 0, ( $attendee_count - 1 ) ) );
		}

		return $p2p;
	}

	function is_attending( $event_id, $user_id ) {

		$p2p_id = p2p_type( VA_EVENT_ATTENDEE_CONNECTION )->get_p2p_id( $event_id, $user_id );

		return (bool) $p2p_id ? true : false;
	}

	function create_attendance_url( $event_id = '', $action = 'add' ) {

		$args = array(
			'event_attendance' => $action,
			'event_id' => $event_id,
			'ajax_nonce' => wp_create_nonce( 'event-attend-' . $event_id ),
		);

		return add_query_arg( $args, home_url() );
	}

	function create_attendance_link( $event_id, $user_id ) {

		$attending = $this->is_attending( $event_id, $user_id );

		$icon = html( 'span', array(
			'class' => 'action-icon ' . ( $attending ? 'cancel' : 'check' )
		), '' );

		$text = $attending ? __( 'Change my reservation', APP_TD ) : __( 'Yes, I want to attend!', APP_TD );

		$button = html( 'a', array(
			'class' => 'event-attend-link',
			'data-event_attendance' => ( $attending ? 'remove' : 'add' ),
			'data-event_id' => $event_id,
			'data-ajax_nonce' => wp_create_nonce( 'event-attend-' . $event_id ),
			'href' => esc_url( admin_url( 'admin-ajax.php' ) ),
		), $icon . ' ' . $text );

		return $button;
	}

	function create_attendance_banner( $event_id, $user_id ) {
		$attending = $this->is_attending( $event_id, $user_id );

		$icon = html( 'div', array(
			'class' => 'icon ' . ( $attending ? 'check' : '' )
		), '' );

		$text = $attending ? __( "Yes, I'm Attending", APP_TD ) : __( 'Are you attending?', APP_TD );

		$banner = html( 'h3', array(
			'class' => 'banner-text',
		), $text );

		return $icon . $banner;

	}

	function create_attendance_list( $event_id ) {

		$users = va_get_event_attendees( $event_id );

		if ( ! $users ) {
			return html( 'h3', array( 'class' => 'banner' ), __( 'There is no one Attending Yet', APP_TD ) );
		}

		$li = '';
		foreach ( $users as $user ) {
			$user_url = get_author_posts_url( $user->ID, $user->display_name );
			$user_url = apply_filters( 'va_event_attendee_link', $user_url, $user, $event_id );
			$a_img = html( 'a', array( 'href' => $user_url, 'class' => 'img' ), get_avatar( $user->ID, 15 ) );
			$a_name = html( 'a', array( 'href' => $user_url, 'class' => 'name' ), $user->display_name );

			$li .= html( 'li', array(), $a_img, $a_name );
		}

		$ul = html( 'ul', array(), $li );
		$banner = html( 'h3', array( 'class' => 'banner'), sprintf( _n( '%d Person is Attending', '%d People are Attending', count( $users ), APP_TD ), count( $users ) ) );
		$title = html( 'h4', array( 'class' => 'title' ), get_the_title( $event_id ) );

		$html = $banner . $title . $ul;

		return $html;
	}

	function widget( $args, $instance ) {
		if ( ! is_singular( VA_EVENT_PTYPE ) ) {
			return;
		}

		$event_id = get_queried_object_id();
		$user_id = get_current_user_id();

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';

		extract( $args );

		echo $before_widget;

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		?>
		<div class="event-attending-widget">
			<div class="banner"><?php echo $this->create_attendance_banner( $event_id, $user_id ); ?></div>
			<div>
				<p class="action"><?php echo $this->create_attendance_link( $event_id, $user_id ); ?></p>
				<p class="action"><a class="event-attending-list" href="#event-whos-attending"><span class="action-icon"></span><?php _e( "See Who's Attending", APP_TD ); ?></a></p>
				<div class="clear"></div>
				<div style="display:none;">
					<div id="event-whos-attending"><?php echo $this->create_attendance_list( $event_id ); ?>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>

		<?php echo $after_widget; ?>

		<script type="text/javascript">
			jQuery(function($){
				$(".action").on("click", ".event-attend-link", function(e){
					e.preventDefault();

					var _link = $(this);

					jQuery.post(Vantage.ajaxurl, {
						action: 'va_event_attendance',
						current_url: Vantage.current_url,
						_ajax_nonce: _link.data('ajax_nonce'),
						event_attendance: _link.data('event_attendance'),
						event_id: _link.data('event_id')
					}, function( data ) {
						$('.notice').remove();

						_link.parents('.event-attending-widget').find('.banner').html( data.banner );

						$('#event-whos-attending').html( data.attendees );

						_link.parents('.action').before( data.notice );

						if( 'error' != data.status ) {
							setTimeout( function(){
								$('.notice').slideUp(400, function() {
									$('.notice').remove();
								});
							}, 2000 );
						}

						_link.parents('.action').html( data.link );

					}, "json");

				});

				$('.event-attending-list').colorbox({
					inline: true,
					width: "50%"
				});

			});
		</script>
		<?php
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
 * Recent Events Widget
 */
class VA_Widget_Recent_Events extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'The most recently added Events', APP_TD )
		);
		parent::__construct( 'recent_events', __( 'Vantage Recently Added Events', APP_TD ), $widget_ops );

		$this->alt_option_name = 'widget_recent_events';

		add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );
	}

	function widget( $args, $instance ) {
		$cache = wp_cache_get( 'widget_recent_events', 'widget' );

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

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Recently Added Events', APP_TD ) : $instance['title'], $instance, $this->id_base );
		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
			$number = 10;
		}

		$r = new WP_Query( array( 'post_type' => VA_EVENT_PTYPE, 'posts_per_page' => $number, 'no_found_rows' => true, 'post_status' => 'publish', 'ignore_sticky_posts' => true ) );
		if ( $r->have_posts() ) :
			echo $before_widget;
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}
		?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
		<li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr( get_the_title() ? get_the_title() : get_the_ID() ); ?>"><?php echo va_get_the_event_day( get_the_ID(), get_option('date_format') ); ?> - <?php echo get_the_title(); ?></a></li>
		<?php endwhile; ?>
		</ul>
		<?php
			echo $after_widget;
			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();

		endif;

		$cache[ $args['widget_id'] ] = ob_get_flush();
		wp_cache_set( 'widget_recent_events', $cache, 'widget' );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['widget_recent_events'] ) ) {
			delete_option( 'widget_recent_events' );
		}

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete( 'widget_recent_events', 'widget' );
	}

	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of Events to show:', APP_TD ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
}


/**
 * Upcoming Events Widget
 */
class VA_Widget_Upcoming_Events extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'Upcoming Events', APP_TD )
		);
		parent::__construct( 'upcoming_events', __( 'Vantage Upcoming Events', APP_TD ), $widget_ops );

		$this->alt_option_name = 'widget_upcoming_events';

		add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );
	}

	function widget( $args, $instance ) {
		global $va_locale;

		$cache = wp_cache_get( 'widget_upcoming_events', 'widget' );

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

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Upcoming Events', APP_TD ) : $instance['title'], $instance, $this->id_base );
		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
			$number = 10;
		}

		if ( empty( $instance['days_ahead'] ) || ! $days_ahead = absint( $instance['days_ahead'] ) ) {
			$days_ahead = 30;
		}


		$days = array();
		for ( $x = 1 ; $x <= $days_ahead; $x++ ) {
			$days[] = date( 'Y-m-d', strtotime( '+' . $x . ' days' ) );
		}

		$events_args = array(
			'post_type' => VA_EVENT_PTYPE,
			'posts_per_page' => $number,
			'no_found_rows' => true,
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,
			'tax_query' => array(
				array(
					'taxonomy' => VA_EVENT_DAY,
					'field' => 'slug',
					'terms' => $days,
					'include_children' => false,
				),
			),
			'meta_key' => VA_EVENT_DATE_META_KEY,
			'orderby' => 'meta_value',
			'order' => 'asc'
		);

		$r = new WP_Query( $events_args );

		if ( $r->have_posts() ) :
			echo $before_widget;
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}

			$event_count = 0;

			echo '<ul>';
			foreach ( $days as $day ) {
				foreach ( $r->posts as $post ) {
					if ( $event_count >= $number ) {
						break 2;
					}

					if ( is_object_in_term( $post->ID, VA_EVENT_DAY, $day ) ) {
						$event_title = get_the_title( $post->ID ) ? get_the_title( $post->ID ) : $post->ID;
						$event_date = $va_locale->date( get_option( 'date_format' ), strtotime( $day ) );
						echo html( 'li',
							html( 'a', array(
								'href' => get_permalink( $post->ID ),
								'title' => esc_attr( $event_title ),
							), $event_date . ' - ' . $event_title )
						);
						$event_count++;
					}
				}
			}
			echo '</ul>';
			echo $after_widget;
			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();

		endif;

		$cache[ $args['widget_id'] ] = ob_get_flush();
		wp_cache_set( 'widget_upcoming_events', $cache, 'widget' );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$instance['days_ahead'] = (int) $new_instance['days_ahead'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['widget_upcoming_events'] ) ) {
			delete_option( 'widget_upcoming_events' );
		}

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete( 'widget_upcoming_events', 'widget' );
	}

	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$days_ahead = isset( $instance['days_ahead'] ) ? absint( $instance['days_ahead'] ) : 30;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Maximum number of Events to show:', APP_TD ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

		<p><label for="<?php echo $this->get_field_id( 'days_ahead' ); ?>"><?php _e( 'Include Events how many days away?', APP_TD ); ?></label>
		<input id="<?php echo $this->get_field_id( 'days_ahead' ); ?>" name="<?php echo $this->get_field_name( 'days_ahead' ); ?>" type="text" value="<?php echo $days_ahead; ?>" size="3" /></p>
<?php
	}
}


/**
 * Similar Events Widget
 */
class VA_Widget_Similar_Events extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'Similar Events. Use in Single Event Sidebar.', APP_TD )
		);
		parent::__construct( 'similar_events', __( 'Vantage Similar Events', APP_TD ), $widget_ops );

		$this->alt_option_name = 'widget_similar_events';

		add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );
	}

	function widget( $args, $instance ) {
		global $wp_query;

		$cache = wp_cache_get( 'widget_similar_events', 'widget' );

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

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Similar Events' , APP_TD ) : $instance['title'], $instance, $this->id_base );
		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
			$number = 10;
		}


		if ( ! isset( $wp_query->posts ) || count( $wp_query->posts ) < 1 ) {
			return;
		}

		if ( ! is_singular( VA_EVENT_PTYPE ) && ! is_post_type_archive( VA_EVENT_PTYPE ) ) {
			return;
		}

		$categories = array();
		foreach ( $wp_query->posts as $event ) {
			$terms = get_the_terms( $event->ID, VA_EVENT_CATEGORY );
			if ( $terms ) {
				$_terms = array();
				foreach ( $terms as $term ) {
					$_terms[] = $term->term_id;
				}
				$categories = array_merge( $categories, $_terms );
			}
		}

		$categories = array_unique( $categories );

		if ( empty( $categories ) ) {
			return;
		}

		$events_args = array(
			'post_type' => VA_EVENT_PTYPE,
			'posts_per_page' => $number,
			'no_found_rows' => true,
			'orderby' => 'rand',
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,
			'tax_query' => array(
				array(
					'taxonomy' => VA_EVENT_CATEGORY,
					'field' => 'id',
					'terms' => $categories,
					'include_children' => false,
				),
			)
		);

		$r = new WP_Query( $events_args );

		if ( $r->have_posts() ) :
			echo $before_widget;
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}
		?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
		<li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr( get_the_title() ? get_the_title() : get_the_ID() ); ?>"><?php echo va_get_the_event_day( get_the_ID(), get_option( 'date_format' ) ); ?> - <?php echo get_the_title(); ?></a></li>
		<?php endwhile; ?>
		</ul>
		<?php
			echo $after_widget;
			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();

		endif;

		$cache[ $args['widget_id'] ] = ob_get_flush();
		wp_cache_set( 'widget_similar_events', $cache, 'widget' );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['widget_similar_events'] ) ) {
			delete_option( 'widget_similar_events' );
		}

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete( 'widget_similar_events', 'widget' );
	}

	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of Events to show:', APP_TD ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
}


/**
 * Popular Events Widget
 */
class VA_Widget_Popular_Events extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'Popular Events. Use in Single Event Sidebar.', APP_TD )
		);
		parent::__construct( 'popular_events', __( 'Vantage Popular Events', APP_TD ), $widget_ops );

		$this->alt_option_name = 'widget_popular_events';

		add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( &$this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );
	}

	function widget( $args, $instance ) {
		global $wp_query;

		$cache = wp_cache_get( 'widget_popular_events', 'widget' );

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

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Popular Events', APP_TD ) : $instance['title'], $instance, $this->id_base );
		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
			$number = 10;
		}


		if ( ! isset( $wp_query->posts ) || count( $wp_query->posts ) < 1 ) {
			return;
		}

		if ( ! is_singular( VA_EVENT_PTYPE ) && ! is_post_type_archive( VA_EVENT_PTYPE ) ) {
			return;
		}

		$categories = array();
		foreach ( $wp_query->posts as $event ) {
			$terms = get_the_terms( $event->ID, VA_EVENT_CATEGORY );
			if ( $terms ) {
				$_terms = array();
				foreach ( $terms as $term ) {
					$_terms[] = $term->term_id;
				}
				$categories = array_merge( $categories, $_terms );
			}
		}

		$categories = array_unique( $categories );

		$events_args = array(
			'post_type' => VA_EVENT_PTYPE,
			'posts_per_page' => $number,
			'no_found_rows' => true,
			'meta_key' => VA_EVENT_ATTENDEES_META_KEY,
			'orderby' => 'meta_value_num',
			'order' => 'DESC',
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,

		);

		$r = new WP_Query( $events_args );

		if ( $r->have_posts() ) :
			echo $before_widget;
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}
		?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
		<li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr( get_the_title() ? get_the_title() : get_the_ID() ); ?>"><?php echo va_get_the_event_day( get_the_ID(), get_option( 'date_format' ) ); ?> - <?php echo get_the_title(); ?></a></li>
		<?php endwhile; ?>
		</ul>
		<?php
			echo $after_widget;
			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();

		endif;

		$cache[ $args['widget_id'] ] = ob_get_flush();
		wp_cache_set( 'widget_popular_events', $cache, 'widget' );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['widget_popular_events'] ) ) {
			delete_option( 'widget_popular_events' );
		}

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete( 'widget_popular_events', 'widget' );
	}

	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', APP_TD ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of Events to show:', APP_TD ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
}


/**
 * Create Event Button Widget
 */
class VA_Widget_Create_Event_Button extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'A button for creating a new event', APP_TD )
		);

		parent::__construct( 'create_event_button', __( 'Vantage Create Event Button', APP_TD ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$url = va_get_event_create_url();

		echo $before_widget;
		echo html_link( $url, __( 'Add an event now', APP_TD ) );
		echo $after_widget;
	}
}


/**
 * Register events widgets
 *
 * @return void
 */
function _va_register_events_widgets() {
	register_widget( 'VA_Widget_Event_Attendees' );
	register_widget( 'VA_Widget_Recent_Events' );
	register_widget( 'VA_Widget_Upcoming_Events' );
	register_widget( 'VA_Widget_Similar_Events' );
	register_widget( 'VA_Widget_Popular_Events' );
	register_widget( 'VA_Widget_Create_Event_Button' );
}
add_action( 'widgets_init', '_va_register_events_widgets' );
