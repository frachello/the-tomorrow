( function( $ ) {
	$( function() {
		$.fn.eventorganiserVenueImg = function( options ) {
			var selector = $( this ).selector; // Get the selector
	        // Set default options
	        var defaults = {
	        		'button'  : '.button-upload'
	        };
	        options  = $.extend( defaults, options );
	            
	        //When the Button is clicked...
	        $(selector).on( 'click', options.button, function( e ){
	        	e.preventDefault();
	        	
	        	//Show WP Media Uploader popup
	        	tb_show( 'Upload a image', 'media-upload.php?referer=eventorganiser-venue&type=image&TB_iframe=true&post_id=0', false );
	 
	        	//Re-define the global function 'send_to_editor'
	        	window.send_to_editor = function( html ) {
	        		//Get the ID of attachment
	        		var classes = jQuery('img', html).attr('class');
	        		var id = classes.replace(/(.*?)wp-image-/, '');
	                	
	    			jQuery.post(ajaxurl, {
	    				action:"eo-set-venue-thumbnail", 
	    				eo_venue_id: $('#eo_venue_id').val(), 
	    				thumbnail_id: id,
	    				_ajax_nonce: eventorganiser_featured_img.nonce
	    			}, function( r ){
	    				if( r != -1 && r != 0 && r.errors == false ){
	    					$( '.inside', '#eo-featured-image' ).html( r.data );
	    					if( r.venue_id )
	    						$('#eo_venue_id').val(r.venue_id );
	    				}else {
	    					alert( 'Error' );
	    				}
	    			},'JSON'
	    			);
	                	
	        		// Then close the popup window
	        		tb_remove(); 
	        	};
	        	return false;
	        } );
		};
	
		//Usage
		$( '#eo-featured-image' ).eventorganiserVenueImg({'button': '#eventorganiser-set-venue-thumbnail'});
	});
	   
	$('#eo-featured-image').on( 'click', '#eventorganiser-remove-venue-thumbnail', function(e){
		e.preventDefault(); 
		jQuery.post(ajaxurl, {
			action:"eo-set-venue-thumbnail", 
			eo_venue_id: $('#eo_venue_id').val(), 
			thumbnail_id: -1, 
			_ajax_nonce: eventorganiser_featured_img.nonce
		}, function(str){
			if ( str == -1 || str == 0 ) {
				alert( 'Error' );
			} else {
				$( '.inside', '#eo-featured-image' ).html( str );
			}
		});
	});
} ( jQuery ) );
