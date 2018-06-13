jQuery(function($){
	/*
	 * Select/Upload image(s) event
	 */
	$( '#wpbc_upload_image_button' ).on( 'click', function( e ){
		e.preventDefault();

		var button = $(this),
		    custom_uploader = wp.media({
				title: wbc_uploads.title_text,
				library : {
					type : 'image'
				},
				button: {
					text: wbc_uploads.button_text
				},
				multiple: false
			}).on('select', function() {
				var attachment = custom_uploader.state().get('selection').first().toJSON();
				$(button).removeClass('button').html('<img class="true_pre_image" src="' + attachment.url + '" style="max-width:95%;display:block;" />').next().val(attachment.id).next().show();
			})
			.open();
	});

	/*
	 * Remove image event
	 */
	$('body').on('click', '#wpbc_remove_image_button', function(){
		$(this).hide().prev().val('').prev().addClass('button').html('Upload image');
		return false;
	});

});