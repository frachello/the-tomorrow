jQuery(document).ready( function($) {
	var before, addBefore, addAfter, delBefore;

	before = function() {
		var nonce = $('#newmeta [name="_ajax_nonce"]').val(), eo_venue_id = $('#eo_venue_id').val();
		if ( !nonce || !eo_venue_id ) { return false; }
		return [nonce,eo_venue_id];
	};

	addBefore = function( s ) {
		var b = before();
		if ( !b ) { return false; }
		s.data = s.data.replace(/_ajax_nonce=[a-f0-9]+/, '_ajax_nonce=' + b[0]) + '&eo_venue_id=' + b[1];
		return s;
	};

	delBefore = function( s ) {
		var b = before(); if ( !b ) return false;
		s.data.eo_venue_id = b[1];
		return s;
	};

	addAfter = function( r, s ) {
		var res = wpAjax.parseAjaxResponse(r, s.response, s.element);
		if( !res.errors ){
			//If venue didn't yet exist in db, it will have been created with ajax call.
			var id = res.responses[0].supplemental.venueid;
			$('#eo_venue_id').val(id);
		}
		$('#list-table').show();
	};
	
	$('#the-list')
		.wpList( { addBefore: addBefore,addAfter: addAfter, delBefore: delBefore } );
} );