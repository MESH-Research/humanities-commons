( function($) {
	var originalVal = $.fn.val;
    $.fn.val = function(){
        var prev;
        if(arguments.length>0){
            prev = originalVal.apply(this,[]);
        }
        var result =originalVal.apply(this,arguments);
        if(arguments.length>0 && prev!=originalVal.apply(this,[]))
            $(this).trigger('change');
        return result;
    };
	
	$(function() {
		if ( typeof( $('#doc_id').val() ) == 'undefined' || $('#doc_id').val() == 0 ) {
			$('#insert-media-button').addClass('disabled');
		}

		$(document).on('change', 'input#doc_id', function() {
			var doc_id = $(this).val();
			console.log( 'doc_id: ' + doc_id );
			if ( doc_id ) {
				$('#insert-media-button').removeClass('disabled');
			}

		});
	});
})(jQuery);