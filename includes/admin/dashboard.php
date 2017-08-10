<?php

class VA_Dashboard extends APP_Dashboard {

	public function __construct() {

		parent::__construct( array(
			'page_title' => __( 'Dashboard', APP_TD ),
			'menu_title' => __( 'Vantage', APP_TD ),
			'icon_url' => appthemes_locate_template_uri( 'images/admin-menu.png' ),
		) );

		add_filter( 'appthemes_dashboard_stats_box', array( $this, 'stats_box_items' ) );

		$this->boxes[] = array( 'stats_30_days', $this->box_icon( 'dashicons-chart-bar' ) . __( 'Last 30 Days', APP_TD ), 'side', 'high' );
		$this->boxes[] = array( 'support_forum', $this->box_icon( 'dashicons-format-chat' ) . __( 'Forums', APP_TD ), 'normal', 'low' );

		$stats_icon = $this->box_icon( 'dashicons-chart-pie' );
		$stats = array( 'stats', $stats_icon .  __( 'Overview', APP_TD ), 'normal' );
		array_unshift( $this->boxes, $stats );

	}

	public function page_head() {
		global $is_IE;

		// only load this support js when browser is IE
		if ( $is_IE ) {
			wp_enqueue_script( 'excanvas', get_template_directory_uri() . '/includes/admin/scripts/flot/excanvas.min.js', array( 'jquery' ), '0.8.3' );
		}

		wp_enqueue_script( 'flot', get_template_directory_uri() . '/includes/admin/scripts/flot/jquery.flot.min.js', array( 'jquery' ), '0.8.3' );
		wp_enqueue_script( 'flot-time', get_template_directory_uri() . '/includes/admin/scripts/flot/jquery.flot.time.min.js', array( 'jquery', 'flot' ), '0.8.3' );

		parent::page_head();
	}

	public function stats_box_items( $sections ) {

		$stats = array();
		$listings = self::_get_listing_counts( VA_LISTING_PTYPE );

		$stats[ __( 'New Listings', APP_TD ) ] = $listings['new'];
		// Published
		if ( isset( $listings['publish'] ) ) {
			$stats[ __( 'Published Listings', APP_TD ) ] = array(
				'text' => $listings['publish'],
				'url' => add_query_arg( array( 'post_type' => VA_LISTING_PTYPE, 'post_status' => 'publish' ), admin_url( 'edit.php' ) ),
			);
		}
		// Pending
		if ( isset( $listings['pending'] ) ) {
			$stats[ __( 'Pending Listings', APP_TD ) ] = array(
				'text' => $listings['pending'],
				'url' => add_query_arg( array( 'post_type' => VA_LISTING_PTYPE, 'post_status' => 'pending' ), admin_url( 'edit.php' ) ),
			);
		}
		// Pending Claimed
		if ( isset( $listings['pending_claimed'] ) ) {
			$stats[ __( 'Claimed Listings', APP_TD ) ] = array(
				'text' => $listings['pending_claimed'],
				'url' => add_query_arg( array( 'post_type' => VA_LISTING_PTYPE, 'post_status' => 'pending_claimed' ), admin_url( 'edit.php' ) ),
			);
		}
		// Total
		$stats[ __( 'Total Listings', APP_TD ) ] = array(
			'text' => $listings['all'],
			'url' => add_query_arg( array( 'post_type' => VA_LISTING_PTYPE ), admin_url( 'edit.php' ) ),
		);

		// services section
		$sections['listings'] = $stats;

		// app version section
		$sections['apps'][] = 'Vantage ' . VA_VERSION;

		return $sections;
	}


	/**
	 * Displays charts box with stats for last 30 days.
	 *
	 * @return void
	 */
	public function stats_30_days_box() {
		global $wpdb;

		$sql = "SELECT COUNT(post_title) as total, post_date FROM $wpdb->posts WHERE post_type = %s AND post_date > %s GROUP BY DATE(post_date) DESC";
		$results = $wpdb->get_results( $wpdb->prepare( $sql, VA_LISTING_PTYPE, appthemes_mysql_date( current_time( 'mysql' ), -30 ) ) );

		$listings = array();

		// put the days and total posts into an array
		foreach ( (array) $results as $result ) {
			$the_day = date( 'Y-m-d', strtotime( $result->post_date ) );
			$listings[ $the_day ] = $result->total;
		}

		// setup the last 30 days
		for ( $i = 0; $i < 30; $i++ ) {
			$each_day = date( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
			// if there's no day with posts, insert a goose egg
			if ( ! in_array( $each_day, array_keys( $listings ) ) ) {
				$listings[ $each_day ] = 0;
			}
		}

		// sort the values by date
		ksort( $listings );

		// Get sales - completed orders with a cost
		$results = array();
		$currency_symbol = '$';
		if ( current_theme_supports( 'app-payments' ) ) {
			$sql = "SELECT sum( m.meta_value ) as total, p.post_date FROM $wpdb->postmeta m INNER JOIN $wpdb->posts p ON m.post_id = p.ID WHERE m.meta_key = 'total_price' AND p.post_status IN ( '" . APPTHEMES_ORDER_COMPLETED . "', '" . APPTHEMES_ORDER_ACTIVATED . "' ) AND p.post_date > %s GROUP BY DATE(p.post_date) DESC";
			$results = $wpdb->get_results( $wpdb->prepare( $sql, appthemes_mysql_date( current_time( 'mysql' ), -30 ) ) );
			$currency_symbol = APP_Currencies::get_current_symbol();
		}

		$sales = array();

		// put the days and total posts into an array
		foreach ( (array) $results as $result ) {
			$the_day = date( 'Y-m-d', strtotime( $result->post_date ) );
			$sales[ $the_day ] = $result->total;
		}

		// setup the last 30 days
		for ( $i = 0; $i < 30; $i++ ) {
			$each_day = date( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
			// if there's no day with posts, insert a goose egg
			if ( ! in_array( $each_day, array_keys( $sales ) ) ) {
				$sales[ $each_day ] = 0;
			}
		}

		// sort the values by date
		ksort( $sales ); ?>

<style type="text/css">
/* dashboard charts */

	#placeholder {
		width: 100%;
		height: 250px;
	}
	#charttooltip {
		font-size: 11px;
		border: 1px solid #e3e3e3;
		background-color: #f1f1f1;
		padding: 3px 7px;
		margin-left: 15px;
		-khtml-border-radius: 6px;
		-moz-border-radius: 6px;
		-webkit-border-radius: 6px;
		border-radius: 6px;
		text-shadow: 1px 1px 0 #ffffff;
	}
</style>

<div class="charts-widget">

	<div id="placeholder"></div>

	<script type="text/javascript">
	// <![CDATA[
	jQuery(function () {

		var posts = [
			<?php
			foreach ( $listings as $day => $value ) {
				$sdate = strtotime( $day );
				$sdate = $sdate * 1000; // js timestamps measure milliseconds vs seconds
				$newoutput = "[$sdate, $value],\n";
				echo $newoutput;
			}
			?>
		];

		var sales = [
			<?php
			foreach ( $sales as $day => $value ) {
				$sdate = strtotime( $day );
				$sdate = $sdate * 1000; // js timestamps measure milliseconds vs seconds
				$newoutput = "[$sdate, $value],\n";
				echo $newoutput;
			}
			?>
		];


		var placeholder = jQuery("#placeholder");

		var output = [
			{
				data: posts,
				label: "<?php _e( 'New Listings', APP_TD ); ?>",
				symbol: ''
			},
			{
				data: sales,
				label: "<?php _e( 'Total Sales', APP_TD ); ?>",
				symbol: '<?php echo $currency_symbol; ?>',
				yaxis: 2
			}
		];

		var options = {
			series: {
				lines: { show: true },
				points: { show: true }
			},
			grid: {
				tickColor:'#f4f4f4',
				hoverable: true,
				clickable: true,
				borderColor: '#f4f4f4',
				backgroundColor:'#FFFFFF'
			},
			xaxis: {
				mode: 'time',
				timeformat: "%m/%d"
			},
			yaxis: {
				min: 0
			},
			y2axis: {
				min: 0,
				tickFormatter: function(v, axis) {
					return "<?php echo $currency_symbol; ?>" + v.toFixed(axis.tickDecimals)
				}
			},
			legend: {
				position: 'nw'
			}
		};

		jQuery.plot(placeholder, output, options);

		// reload the plot when browser window gets resized
		jQuery(window).resize(function() {
			jQuery.plot(placeholder, output, options);
		});

		function showChartTooltip(x, y, contents) {
			jQuery('<div id="charttooltip">' + contents + '</div>').css( {
				position: 'absolute',
				display: 'none',
				top: y + 5,
				left: x + 5,
				opacity: 1
			} ).appendTo("body").fadeIn(200);
		}

		var previousPoint = null;
		jQuery("#placeholder").bind("plothover", function (event, pos, item) {
			jQuery("#x").text(pos.x.toFixed(2));
			jQuery("#y").text(pos.y.toFixed(2));
			if (item) {
				if (previousPoint != item.datapoint) {
					previousPoint = item.datapoint;

					jQuery("#charttooltip").remove();
					var x = new Date(item.datapoint[0]), y = item.datapoint[1];
					var xday = x.getDate(), xmonth = x.getMonth()+1; // jan = 0 so we need to offset month
					showChartTooltip(item.pageX, item.pageY, xmonth + "/" + xday + " - <b>" + item.series.symbol + y + "</b> " + item.series.label);
				}
			} else {
				jQuery("#charttooltip").remove();
				previousPoint = null;
			}
		});
	});
	// ]]>
	</script>
</div>
	<?php
	}

}
