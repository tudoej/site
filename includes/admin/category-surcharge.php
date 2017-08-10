<?php

add_action( 'admin_init', 'va_category_surcharge_setup_tab_init', 99 );

function va_category_surcharge_setup_tab_init() {
	global $admin_page_hooks;

	if ( ! empty( $admin_page_hooks['app-payments'] ) ) {
		add_action( 'tabs_' . $admin_page_hooks['app-payments'] . '_page_app-payments-settings', array( 'VA_Category_Surcharge_Settings_Tab', 'init' ) );
	}
}

class VA_Category_Surcharge_Settings_Tab {

	private static $page;

	static function init( $page ) {
		self::$page = $page;

		$taxonomies = $taxonomy_to_tab = array();

		$taxonomies[] = VA_LISTING_CATEGORY;
		$taxonomy_to_tab[VA_LISTING_CATEGORY] = 'listings';

		if ( va_events_enabled() ) {
			$taxonomies[] = VA_EVENT_CATEGORY;
			$taxonomy_to_tab[VA_EVENT_CATEGORY] = 'events';
		}

		foreach( $taxonomies as $taxonomy ) {
			$_tax = get_taxonomy( $taxonomy );

			$args = array(
				'hide_empty' => false,
			);

			$terms = get_terms( $taxonomy, $args );

			$fields = array();
			foreach ( $terms as $term ) {
				$fields = array_merge( $fields, self::generate_fields( $term ) );
			}

			$page->tab_sections[ $taxonomy_to_tab[ $taxonomy ] ]['category-surcharge-taxonomy_'.$_tax->name] = array(
				'title' => sprintf( __( 'Surcharges for %s', APP_TD ),  $_tax->labels->name),
				'renderer' => array( __CLASS__, 'render' ),
				'taxonomy' => $taxonomy,
				'terms' => $terms,
				'fields' => $fields
			);
		}
	}

	static function render( $section ) {

		$columns = array(
			'type' => __( 'Category', APP_TD ),
			'price' => sprintf( __( 'Price %s', APP_TD ), html( 'p', array('class'=>'description' ), APP_Currencies::get_current_currency('code') ) ),
		);

		$header = '';
		foreach ( $columns as $key => $label )
			$header .= html( 'th', $label );

		$rows = '';
		foreach ( $section['terms'] as $term ) {
			$row = html( 'td', $term->name );

			foreach ( self::generate_fields( $term ) as $field )
				$row .= html( 'td', self::$page->input( $field ) );

			$rows .= html( 'tr', $row );
		}

		echo html( 'table id="category-surcharge-pricing" class="widefat"', html( 'thead', html( 'tr', $header ) ), html( 'tbody', $rows ) );
	}

	private static function generate_fields( $term ) {
		return array(
			array(
				'type' => 'text',
				'name' => array( 'category_surcharges', $term->slug, 'surcharge' ),
				'sanitize' => 'appthemes_absfloat',
				'extra' => array( 'size' => 3 ),
			),
		);
	}
}
