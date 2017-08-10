
function va_populate_period_values( values_limit, period_selected, period_type ) {
	var period_id = '#'+period_type.data('period-item')+'_period';
	var period = jQuery(period_id);

	period.html('');
	for ( var i = 0; i <= values_limit; i++ ) {
		period.append('<option value="' + i + '">' + i + '</option>');
	}
	period.val(period_selected);
}

jQuery(document).ready(function($) {
	$('.period_type').each(function() {
		var period_id   = '#'+$(this).data('period-item')+'_period';
		var duration_id = '#'+$(this).data('period-item')+'_duration';

		$(this).change(function(){
			if ( $(this).val() === VA_addons_l18n.period_type_years ) {
				va_populate_period_values( 5, 1, $(this) );
			} else if( $(this).val() === VA_addons_l18n.period_type_months ) {
				va_populate_period_values( 24, 1, $(this) );
			} else {
				va_populate_period_values( 90, 30, $(this) );
			}
		});

		if ( $(this).val() === VA_addons_l18n.period_type_years ) {
			va_populate_period_values( 5, $(period_id).val(), $(this) );
		} else if( $(this).val() === VA_addons_l18n.period_type_months ) {
			va_populate_period_values( 24, $(period_id).val(), $(this) );
		} else if( $(period_id).val() !== $(duration_id).val() ) {
			va_populate_period_values( 90, $(duration_id).val(), $(this) );
		}

	});
});