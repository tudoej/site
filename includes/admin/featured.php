<?php

add_action( 'admin_init', 'va_featured_setup_tab_init' );

function va_featured_setup_tab_init() {
	global $admin_page_hooks;

	if ( ! empty( $admin_page_hooks['app-payments'] ) ) {
		add_action( 'tabs_' . $admin_page_hooks['app-payments'] . '_page_app-payments-settings', array( 'VA_Featured_Settings_Tab', 'init' ) );
	}
}

class VA_Featured_Settings_Tab {

	private static $page;

	static function init( $page ) {
		global $admin_page_hooks;

		self::$page = $page;

		$page->tabs->add_after( 'general', 'listings', __( 'Listings', APP_TD ) );

		$fields = array();

		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
			$fields = array_merge( $fields, self::generate_fields( $addon ) );
		}

		$page->tab_sections['listings']['featured'] = array(
			'title' => __( 'Listing Add-ons', APP_TD ),
			'renderer' => array( __CLASS__, 'render' ),
			'fields' => $fields
		);

		if ( isset( $_GET['tab'] ) && 'listings' === $_GET['tab'] ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_action( 'tabs_' . $admin_page_hooks['app-payments'] . '_page_app-payments-settings_form_handler', array( __CLASS__, 'form_handler' ) );
		}

	}

	static function form_handler( $page ) {
		global $va_options;

		if ( ! isset( $_POST['addons'] ) ) {
			return;
		}

		$data = $va_options->addons;

		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
			$addon_data =& $data[ $addon ];

			if ( ! empty( $_POST['addons'][ $addon ] ) && $addon_data['enabled'] ) {

				if ( ! isset( $_POST['addons'][ $addon ]['period'] ) ) {
					$addon_data['period'] = $addon_data['duration'];
					$addon_data['period_type'] = APP_Order::RECUR_PERIOD_TYPE_DAYS;
				} else if ( '0' ===  $addon_data['period'] ) {
					$addon_data['duration' ] = 0;
					$addon_data['period' ]   = 0;
					$addon_data['period_type'] = APP_Order::RECUR_PERIOD_TYPE_DAYS;
				} else {
					if ( $addon_data['period_type'] == APP_Order::RECUR_PERIOD_TYPE_YEARS ) {
						$addon_data['period'] = min( 5, $addon_data['period'] );
						$addon_data['duration'] = $addon_data['period'] * 365;
					} elseif( $addon_data['period_type'] == APP_Order::RECUR_PERIOD_TYPE_MONTHS  ) {
						$addon_data['period'] = min( 24, $addon_data['period'] );
						$addon_data['duration'] = $addon_data['period'] * 30;
					} else {
						$addon_data['period_type'] = APP_Order::RECUR_PERIOD_TYPE_DAYS;
						$addon_data['period'] = min( 90, $addon_data['period'] );
						$addon_data['duration'] = $addon_data['period'];
					}
				}
			}
		}
		$va_options->addons = $data;
	}

	static function admin_enqueue_scripts() {
		wp_enqueue_script(
			'va-admin-addons-edit',
			get_template_directory_uri() . '/includes/admin/scripts/addons-edit.js',
			array( 'jquery' ),
			VA_VERSION,
			true
		);

		wp_localize_script( 'va-admin-addons-edit', 'VA_addons_l18n', array(
			'period_type_days'   => APP_Order::RECUR_PERIOD_TYPE_DAYS,
			'period_type_months' => APP_Order::RECUR_PERIOD_TYPE_MONTHS,
			'period_type_years'  => APP_Order::RECUR_PERIOD_TYPE_YEARS,
		) );
	}

	static function render( $section ) {
		$columns = array(
			'type'        => __( 'Type', APP_TD ),
			'enabled'     => __( 'Enabled', APP_TD ),
			'price'       => __( 'Price', APP_TD ),
			'period'      => __( 'Duration', APP_TD ),
			'period_type' => __( 'Period Type', APP_TD ),
			'duration'    => '',
		);

		$header = '';
		foreach ( $columns as $key => $label )
			$header .= html( 'th', $label );

		$rows = '';
		foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
			$row = html( 'td', APP_Item_Registry::get_title( $addon ) );

			foreach ( self::generate_fields( $addon ) as $field )
				$row .= html( 'td', self::$page->input( $field ) );

			$rows .= html( 'tr', $row );
		}

		echo html( 'table id="featured-pricing" class="widefat"', html( 'tr', $header ), html( 'tbody', $rows ) );
	}

	private static function generate_fields( $addon ) {

		$period_values = array();
		for ( $x = 0; $x <= 90 ; $x++ ){
			$period_values[$x] = $x;
		}

		return array(
			array(
				'type' => 'checkbox',
				'name' => array( 'addons', $addon, 'enabled' ),
				'desc' => __( 'Yes', APP_TD ),
			),
			array(
				'type'     => 'text',
				'name'     => array( 'addons', $addon, 'price' ),
				'sanitize' => 'appthemes_absfloat',
				'extra'    => array( 'size' => 3 ),
			),
			array(
				'title'    => __( 'Duration', APP_TD ),
				'type'     => 'select',
				'name'     => array( 'addons', $addon, 'period' ),
				'values'   => $period_values,
				'sanitize' => 'absint',
				'extra'    => array (
					'id' => $addon . '_period'
				),
			),
			array (
				'title'  => __( 'Period Type', APP_TD ),
				'type'   => 'select',
				'name'   => array( 'addons', $addon, 'period_type' ),
				'values' => array(
					APP_Order::RECUR_PERIOD_TYPE_DAYS   => __( 'Days', APP_TD ),
					APP_Order::RECUR_PERIOD_TYPE_MONTHS => __( 'Months', APP_TD ),
					APP_Order::RECUR_PERIOD_TYPE_YEARS  => __( 'Years', APP_TD ),
				),
				'extra'  => array (
					'class'            => 'period_type',
					'data-period-item' => $addon,
				),
			),
			array(
				'type'     => 'hidden',
				'name'     => array( 'addons', $addon, 'duration' ),
				'sanitize' => 'absint',
				'extra' => array (
					'id' => $addon . '_duration'
				),
			),
		);
	}
}

