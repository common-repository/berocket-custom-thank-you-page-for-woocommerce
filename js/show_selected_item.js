( function ($){
    $( document ).ready(function() {
        $( 'select[name="berocket_global_page_id"]' ).change( 	function() {
            var el = $( this ).val();
            if ( el=='None' ){
                $('#berocket_content_options_for_js').hide();
            } else {
                $('#berocket_content_options_for_js').show();
            }
        } );
	var el1 = $( 'select[name="berocket_global_page_id"]' ).val();
            if ( el1=='None' ){
                $('#berocket_content_options_for_js').hide();
            } else {
                $('#berocket_content_options_for_js').show();
            }

	$('#_is_add_option_to_product').click(function () {
         var $this = $(this);
         if ($this.is(':checked')) {
             $('#options_group').show();
         } else {
             $('#options_group').hide();
         }



     });
	var el3 = $('#_is_add_option_to_product');
         if ( el3.is(':checked')) {
             $('#options_group').show();
         } else {
             $('#options_group').hide();
         }
    } );
} )(jQuery);


