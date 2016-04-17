
jQuery(document).ready( function() {
	jQuery('form').change( 'input[type="checkbox"]', function() {
		var button = jQuery(this).closest('form').find('.save-button');
		button.val( "Save" );
		button.prop( "disabled", false );
	} );

	jQuery('.save-button').click( function() {
		var button = jQuery(this);
		var form = button.closest('form');
		var data = form.serializeArray();

		button.val( "Saving" );
		button.prop( "disabled", true );

		jQuery.post( ajaxurl, data, function( response ) {
			if ( response == 'success' ) {
				button.val( "Saved" );
			} else {
				button.val( "Save" );
				button.prop( "disabled", false );
			}
		} );
	} );
} );

console.log("Loaded evaluate-admin.js");
