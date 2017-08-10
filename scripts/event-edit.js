function vantage_map_edit() {

	var success_callback = jQuery.noop();

	function map_init( lat, lng ) {

		map_initialized = true;

		var markers_opts = [
			{
				"lat" : lat,
				"lng" : lng,
				'draggable' : true,
			}
		];

		jQuery('#event-map').appthemes_map({
			zoom : 15,
			center_lat : lat,
			center_lng : lng,
			markers: markers_opts,
			auto_zoom: false,
			marker_drag_end: function( marker_key, lat, lng ) {

				jQuery('input[name="lat"]').val( lat );
				jQuery('input[name="lng"]').val( lng );

				update_position( lat, lng, marker_key );
				geocode_lat_lng( lat, lng );
			}
		});

		var address = jQuery('#event-address').val();
		var lat = jQuery('input[name="lat"]').val();
		var lng = jQuery('input[name="lng"]').val();

		if ( address != '' && ( lat == 0 && lng == 0 ) )
			update_map(jQuery.noop);
	}

	function geocode_lat_lng(lat, lng) {

		jQuery.getJSON( Vantage.ajaxurl, {
			action: 'vantage_create_event_geocode',
			lat: lat,
			lng: lng
		}, function(response) {

			if( response.address ) {
				jQuery('#event-address').val( response.address );
			}
		} );
	}

	function update_position( lat, lng, marker_key ) {

		if ( !map_initialized ) {
			return map_init( lat, lng );
		}

		var marker_update_opts = {
			marker_key: marker_key,
			lat: lat,
			lng: lng
		};

		jQuery('#event-map').appthemes_map('update_marker_position', marker_update_opts );
	}

	jQuery('#event-address').keydown(function(e) {
		if (e.keyCode == 13) {
			jQuery('#event-find-on-map').click();
			e.preventDefault();
		}
	});

	jQuery('#event-find-on-map').click(function(ev) {

		jQuery.getJSON( Vantage.ajaxurl, {
			action: 'vantage_create_event_geocode',
			address: jQuery('#event-address').val(),
		}, function(response) {
			if ( response.formatted_address ) {
				jQuery('#event-address').val(response.formatted_address);
			}

			if ( response.coords.lat && response.coords.lng ) {
				jQuery('input[name="lat"]').val(response.coords.lat);
				jQuery('input[name="lng"]').val(response.coords.lng);

				update_position( response.coords.lat, response.coords.lng, 0 );
			}
		} );

	});

	var map_initialized = false;
	var address = jQuery('#event-address').val();
	var lat = jQuery('input[name="lat"]').val();
	var lng = jQuery('input[name="lng"]').val();

	if ( address != '' && ( lat == 0 && lng == 0 ) )
		update_map(jQuery.noop);

	if ( lat != 0 && lng != 0 )
		update_position( lat, lng, 0);

	function update_map( callback ) {
		if ( typeof Vantage === 'undefined' ) {
			return setTimeout('update_map( callback )', 500);
		}

		if ( !map_initialized ) {
			var lat = jQuery('input[name="lat"]').val();
			var lng = jQuery('input[name="lng"]').val();
			return map_init( lat, lng );
		}

		jQuery.getJSON( Vantage.ajaxurl, {
			action: 'vantage_create_event_geocode',
			address: jQuery('#event-address').val(),
		}, function(response) {
			if( response.address ) {
				jQuery('#event-address').val( response.address );
				jQuery('input[name="lat"]').val( response.coords.lat );
				jQuery('input[name="lng"]').val( response.coords.lng );
				update_position( response.coords.lat, response.coords.lng, 0 );
				success_callback;
			}
		} );

	}

	function disable_submit_button() {
		jQuery('input[type=submit]').attr('disabled', true).attr('value', VA_i18n.processing ).addClass('clicked');
	}

	function submit_form(form) {
		disable_submit_button();
		form.submit();
	}

	function ensureMapInit(form) {
		if ( map_initialized ) {
			submit_form(form);
			return;
		}

		update_map(function(status) {
			submit_form();
		});

		success_callback = submit_form();
	}

	function loadFormFields() {
		var matches = [];
		var total_categories_selected = jQuery('input[name="_event_category[]"]:checked').length;
		var category_limit = jQuery('#categories').data('category-limit');

		if ( category_limit != 0 && total_categories_selected > category_limit ) {
			return;
		}

		jQuery('input[name="_event_category[]"]:checked').each(function() {
			matches.push(jQuery(this).val());
		});

		var data = {
			action: 'app-render-event-form',
			_event_category: matches,
			event_id: jQuery('input[name="ID"]').val()
		};

		jQuery.post(VA_i18n.ajaxurl, data, function(response) {
			jQuery('#custom-fields').html(response);
			set_sortable();
		});
	}

	jQuery('input[name="_event_category[]"]').change(loadFormFields);

	if ( jQuery('input[name="_event_category[]"]:checked').length > 0 )
		loadFormFields();

	function set_sortable() {
		jQuery('.uploaded').sortable({
			axis: "y",
			containment: "parent",
			tolerance: "pointer",
			distance: 5,
			opacity: 0.7,
			placeholder: "placeholder",
			forcePlaceholderSize: true,
			forceHelperSize: true
		});
	}

	set_sortable();

	jQuery('#create-event').validate({
		submitHandler: ensureMapInit,
		messages: {
			"required" : VA_i18n.error_required,
			"_event_day[0][_blank_date]" : VA_i18n.error_event_date,
			"_event_category[]" : VA_i18n.error_category
		},
		errorPlacement: function(error, element) {
			if (element.attr('type') === 'checkbox' || element.attr('type') === 'radio') {
				element.closest('div').append(error);
			} else {
				error.insertAfter(element);
			}
		}
	});
}

jQuery(function() {
	jQuery('#create-event input[type="file"]').after('<input type="button" class="clear-file" value="' + VA_i18n.clear + '">');

	jQuery( document ).on('click', '#create-event .clear-file', function() {
		jQuery(this).parent().html( jQuery(this).parent().html() );
		return false;
	});

	jQuery('input[name="_event_category[]"]').click(function() {
		jQuery('#category_limit_error').remove();

		var total_categories_selected = jQuery('input[name="_event_category[]"]:checked').length;

		var category_limit = jQuery('#categories').data('category-limit');

		if ( category_limit != 0 && total_categories_selected > category_limit ) {
			jQuery('#categories').prepend('<label for="_event_category[]" id="category_limit_error" class="error" style="">' + VA_i18n.category_limit + '</label>');
			setTimeout( function(){
				jQuery('#category_limit_error').slideUp(400, function() {
					jQuery('#category_limit_error').remove();
				});
			}, 3000 );
			jQuery(this).attr('checked', false);
		}
	});
});
