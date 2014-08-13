(function($) {
$(document).ready(function(){
	$('.eo-bookings-ticket-table .row-actions .deletion').click(function(e){
		e.preventDefault();
		$(this).closest('.row-actions').find('.eo-delete-ticket-cb').attr('checked','checked');
		$(this).closest('tr').css('background-color','red').fadeOut();
	});
});

if( $('#booking-event-date').length > 0 ){
	
	$('#datepicker').datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd',
        monthNamesShort: EO_Event.locale.monthAbbrev,
        dayNamesMin: EO_Event.locale.dayAbbrev,
        firstDay: parseInt( EO_Event.startday, 10 ),
        onSelect: function(date) {
        	var date_obj = eventorganiser_parseISO8601( date );
        	var date_str = $.datepicker.formatDate('dd-mm-yy', date_obj );
        	var dates = EO_Event.dates;
        	for( var id in dates ) {
        		if( dates.hasOwnProperty( id ) && dates[id] == date_str ) {
        			$( '#eo-booking-occurrence-id' ).val( id );
        		}
        	}
        	$( '#eo-booking-occurrence-date' ).text( $.datepicker.formatDate( EO_Event.format, date_obj ) );
        },
        beforeShowDay: function(date) {
        	var date_str = $.datepicker.formatDate('dd-mm-yy', date);
        	var dates = EO_Event.dates;
        	
            for( var id in dates ) {
                if( dates.hasOwnProperty( id ) ) {
                     if( dates[ id ] === date_str )
                    	 return [true, "ui-state-active", ""];
                }
            }
            
            return [false, "", ""];
         }
    });
	
	$('#booking-event-date').click(function(e){
		e.preventDefault();
		if ( !$("#datepicker").datepicker( "widget" ).is(":visible") ) {
			$("#datepicker").show();
			$("#datepicker").datepicker('show');
			$("#datepicker").datepicker('widget').position({
				my: "left top",
				at: "right bottom",
				of: $(this)
			});
			$("#datepicker").hide();
		}else{
			$("#datepicker").datepicker('hide');
		}
	});
}

function eventorganiser_parseISO8601(dateStringInRange) {
	var isoExp = /^\s*(\d{4})-(\d\d)-(\d\d)\s*$/,
	date = new Date(NaN), month,
	parts = isoExp.exec(dateStringInRange);

	if(parts) {
		month = +parts[2];
		date.setFullYear(parts[1], month - 1, parts[3]);
		if(month != date.getMonth() + 1) {
			date.setTime(NaN);
		}
	}
	return date;
}
$(document).ready(function () {postboxes.add_postbox_toggles(pagenow);});
})(jQuery);
