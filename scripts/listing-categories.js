jQuery(function() {

	jQuery('input[name="_listing_category[]"]').click(function() {
		jQuery('#category_limit_error').remove();

		var total_categories_selected = jQuery('input[name="_listing_category[]"]:checked').length;

		var category_limit = jQuery('#categories').data('category-limit');

		if ( category_limit != 0 && total_categories_selected > category_limit ) {
			jQuery('#categories').prepend('<label for="_listing_category[]" id="category_limit_error" class="error" style="">' + VA_i18n.category_limit + '</label>');
			setTimeout( function(){
				jQuery('#category_limit_error').slideUp(400, function() {
					jQuery('#category_limit_error').remove();
				}); 
			}, 3000 );
			jQuery(this).attr('checked', false);
		}
	});

	jQuery('.listing-categories').validate();

});