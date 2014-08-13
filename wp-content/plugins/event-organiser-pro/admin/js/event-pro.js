jQuery(document).ready(function($) {
/* Occurrence Picker Handler */
$.widget("eventorganiser.eo_occurrencepicker", {
	
	// Default options.
    options: {
        value: 0,
        ticket: false,
        selected: [-1],
        deslected: [],
    },
    
    year: false,
    month: false,
    rule: {},
    
    /**
     * Set or retreive the ticket ID.
     * 
     * This is not the tickets ID but rather a temporary and unique ID. This may change
     * between page loads.
     * 
     * If no argument is passed the function acts as a getter. Othewerwise it sets
     * the ticket ID to passed value
     * 
     * @param int id The ticket ID
     * @returns null or the current ticket ID if used as a getter.
     */
    ticket: function( id ) {
    	 
        // No value passed, act as a getter.
        if ( id === undefined ) {
            return this.options.ticket;
        }
 
        // Value passed, act as a setter.
        this.options.ticket = this._absint( id );
    },
    
    
    /**
     * Create: set-up callbacks. Retrieve selected/deselected dates from DOM.
     */
    _create: function(){
    	
    	//Set pre-defined
		this.options.onSelect = this.add_or_remove_date;
		this.options.beforeShowDay = this.pre_show_date;
		this.options.onChangeMonthYear = this.generate_dates;
		
		var selected = $(' #eop_ticket_selected_'+ this.ticket() ).val().split(",");
		var deselected = $(' #eop_ticket_deselected_'+ this.ticket() ).val().split(",");

		if( selected.length === 0 && deselected.length === 0 ){
			this.selected( [-1] );
			this.deselected( [] );	
		}else{
			this.selected( selected );
			this.deselected( deselected );
		}
	
    },
    
    /**
     * Init: create datepicker instance and set year/month being viewed
     */
	_init: function() {
		this.element.datepicker(this.options);
		var now = new Date();
		this.year =  now.getFullYear();
		this.month = now.getMonth() + 1 ;
	},
	
	/**
	 * Gets the day being viewed and re-sets the occurrences.
	 * Refreshes the datepicker
	 */
	refresh: function(){

        //Set occurrences
        this.set_occurrences( this.year, this.month );
	
        //Refresh calendar
		this.element.datepicker( "refresh" );	
	},
	
    /**
     * Set the occurrences that have been selected.
     * 
     * If no argument is passed the function acts as a getter. Othewerwise it sets
     * the selected occurrences to those passed.
     * 
     * @param array value If not passed the function returns the current selected dates
     * @returns null if acting as setter. The current selected dates if acting as a getter.
     */
    selected: function( value ) {
 
        // No value passed, act as a getter.
        if ( value === undefined ) {
            return this.options.selected;
        }
 
        // Value passed, act as a setter.
        this.options.selected = this._array_filter( value );
		$(' #eop_ticket_selected_'+ this.ticket() ).val( this.options.selected.join(","));
    },
    
    /**
     * Set the occurrences that have been deselected.
     * 
     * If no argument is passed the function acts as a getter. Othewerwise it sets
     * the deselected occurrences to those passed.
     * 
     * @param array value If not passed the function returns the current deselected dates
     * @returns null if acting as setter. The current deselected dates if acting as a getter.
     */
    deselected: function( value ) {
    	 
        // No value passed, act as a getter.
        if ( value === undefined ) {
            return this.options.deselected;
        }
 
        // Value passed, act as a setter.
        this.options.deselected = this._array_filter( value );
        $(' #eop_ticket_deselected_'+ this.ticket() ).val( this.options.deselected.join(","));
    },

	select_all: function(){
		this.selected( [-1] );
		this.deselected( [] );
		this.element.datepicker( "refresh" );
	},
	deselect_all: function(){
		this.selected( [""] );
		this.deselected( [] );
		this.element.datepicker( "refresh" );
	},

	pre_show_date: function( date ) {
	
		var date_str = $.datepicker.formatDate('dd-mm-yy', date);
		
		var selected = $(this).eo_occurrencepicker('selected');
		var deselected = $(this).eo_occurrencepicker('deselected');
	
		var isEventful = $(this).eo_occurrencepicker( 'is_date_eventful', date );
	    
        if ( isEventful[0] ) {
        	var index = $.inArray( date_str, selected );
        	var de_index =  $.inArray( date_str , deselected );
        	if( ( index>-1 && selected[0] != "-1" ) || ( selected[0] == "-1" && de_index == -1) ){
            	return [true, "ui-state-active eo-pro-selected", ""];
        	}else{
       	    	return [true, "ui-state-active", ""];
			}
        }
        return [false, "ui-state-disabled", ''];
	},

	
	add_or_remove_date: function(date){
	
		var selected = $(this).eo_occurrencepicker( 'selected' );
		var deselected = $(this).eo_occurrencepicker( 'deselected' );
				
		var index =  $.inArray( date, selected );
		var de_index =  $.inArray( date, deselected );
		
		if( selected[0] == "-1" && de_index > -1 ){
			deselected.splice(de_index, 1);
		
		}else if( selected[0] == "-1"  ){
			deselected.push(date);

		}else if( index >-1 ){
			selected.splice(index, 1);
		}else{
			selected.push(date);
		}
		
		$(this).eo_occurrencepicker( 'selected', selected );
		$(this).eo_occurrencepicker( 'deselected', deselected );
	},

	is_date_eventful: function(date_obj) {
		
	    var date = $.datepicker.formatDate( 'dd-mm-yy', date_obj );
	    //Included/exlude arrays expect Y-m-d format
	    var ymd_date = $.datepicker.formatDate( 'yy-mm-dd', date_obj );
	    
		var index = $.inArray( date, this.options.occurrences_by_rule );
        if (index > -1) {
        	//Occurs by rule - is it excluded manually?
           	var excluded = $.inArray( ymd_date, eo_exclude_dates );
            if (excluded > -1) {
				return [false, excluded];
            } else {
                return [true, -1];
            }
        } else {
          	//Doesn't occurs by rule - is it included manually?
            var included = $.inArray( ymd_date, eo_include_dates );
            if (included > -1) {
              	return [true, included];
            } else {
               	return [false, -1];
            }
        }
	},
	

	/**
	 * 
	 * @param int year - 4 digit interger
	 * @param in month - 1-12 (Jan-Dec)
	 * @param objecct inst
	 */
	generate_dates: function (year, month, inst ){        	
        $(this).eo_occurrencepicker( 'set_occurrences', year, month  );
	},
	
	/**
	 * Sets the occurrences for a given month
	 * @param month_start
	 * @param month_end
	 */
	set_occurrences: function( year, month ){

        //Get month start/end dates. Date expects month 0-11.
        var month_start = new Date(year, month-1, 1);
        var nxt_mon = new Date(year, month, 1);
        var month_end = new Date(nxt_mon - 1);
        
		this.rule = rule = {
	        	start: $("#from_date").datepicker("getDate"),
        		end: $("#recend").datepicker("getDate"),
        		schedule: $('#HWSEventInput_Req').val(),
        		frequency: parseInt( $('#HWSEvent_freq').val(), 10 )
		};
		
		this.year = month_start.getFullYear();
		this.month = month_start.getMonth() + 1;
		
		if (rule.end >= month_start && rule.start <= month_end) {
			this.options.occurrences_by_rule  = this.generate_dates_by_rule( rule, month_start,month_end);
		}else{
			this.options.occurrences_by_rule = [];
		}
	},
	
	/**
	 * Generates an array of dates present in a given month (between month_start &
	 * month_end), according to a rule:
	 * 
	 * rule = {
	 *  start: start date (may be outside given month)
	 *  end: end date (may be outside given month)
	 *  schedule: once|custom|daily|weekly|monthly|yearly
	 *  frequency: integer frequency of schedule. 
	 * }
	 * 
	 * Returns an array of dates that match the rule, and lie between month and start
	 */
	generate_dates_by_rule: function( rule ,month_start , month_end ){
		var occurrences = [];

		var streams = [];
		var pointer = false;
		var start =  rule.start;
		var end =  rule.end;
		var schedule =  rule.schedule;
		var frequency =  rule.frequency;

	    //If event starts in previous month - how many days from start to first occurrence in current month?
	    // Depends on occurrence (and 'stream' for weekly events.
	    switch (schedule) {
	    	case 'once':
		    case 'custom':
		    	var formateddate = $.datepicker.formatDate('dd-mm-yy', start);
		    	occurrences.push(formateddate);
		    	return occurrences;
		    //break

		    case 'daily':
		    	var count_days = 0;
		    	
				if (start < month_start) {
					//Days from schedule start to month start
					count_days = Math.round(Math.abs((month_start - start) / (1000 * 60 * 60 * 24)));
					count_days = (frequency - count_days % frequency);
				} else {
					count_days = parseInt( start.getDate(), 10 ) - 1;
				}
            	var skip = frequency;
            	var start_stream = new Date(month_start);
				start_stream.setDate(month_start.getDate() + count_days);
				streams.push(start_stream);
				//We iterate over the streams after switch statement
			break;

			case 'weekly':
            	var selected = $("#dayofweekrepeat :checkbox:checked");

				var ical_weekdays = new Array("SU", "MO", "TU", "WE", "TH", "FR", "SA");
				selected.each(function(index) {
					index = ical_weekdays.indexOf($(this).val());
					var start_stream = new Date(start);
					start_stream.setDate(start.getDate() + (index - start.getDay() + 7) % 7);

					if (start_stream < month_start) {
						var count_days = Math.abs((month_start - start) / (1000 * 60 * 60 * 24));
						count_days = count_days - count_days % (frequency * 7);
						start_stream.setDate(start_stream.getDate() + count_days);
					}
					streams.push(start_stream);
				});
				//We iterate over the streams after switch statement
				skip = 7 * frequency;
            break;

            //These are easy - can be at most date.          
			case 'monthly':
				var month_difference = (month_start.getFullYear() - start.getFullYear()) * 12 + (month_start.getMonth() -  start.getMonth() );
            	if ( month_difference % frequency !== 0 ) {
            		return occurrences;
            	}
           	 	var meta = $('input[name="eo_input[schedule_meta]"]:checked').val();
            	if (meta == 'BYMONTHDAY=') {
                	var day = start.getDate();
                	var daysinmonth = month_end.getDate();
                	if (day <= daysinmonth) {
                		//If valid date
						pointer = new Date(month_start.getFullYear(), month_start.getMonth() , day);
                	}
				} else {
                	//e.g. 3rd friday of month:
					var n = Math.ceil(start.getDate() / 7), occurrence_day = start.getDay(), occurence_date;
					if (n >= 5) {
                    	//Last day
                  		var month_end_day = month_end.getDay();
                   		occurence_date = month_end.getDate() + (occurrence_day - month_end_day - 7) % 7;
					} else {
						var month_start_day = month_start.getDay();
						var offset = (occurrence_day - month_start_day + 7) % 7;
                    	occurence_date = offset + (n - 1) * 7 + 1;
                	}
				    pointer = new Date(month_start);
				    pointer.setDate(occurence_date);
				}

				if (pointer <= end) {
					//If before end
					formateddate = $.datepicker.formatDate('dd-mm-yy', pointer);
                	occurrences.push(formateddate);
            	}
            	return occurrences;
            //break;

			case 'yearly':
				var year_difference = (month_start.getFullYear() - start.getFullYear());
           		if ( year_difference % frequency !== 0 ) {
               		return occurrences;
           		}

           		//Does the date in this year make sense (e.g. leap years!). If it doesn't the date will overflow
            	var dateCheck = new Date( month_start.getFullYear(), start.getMonth(), start.getDate() );
            	if ( dateCheck.getMonth() == start.getMonth() && dateCheck.getMonth() == start.getMonth()) {
                	pointer = new Date(start);
                	pointer.setYear( month_start.getFullYear() );
                	if ( pointer <= end ){
                		//If before end
                		formateddate = $.datepicker.formatDate('dd-mm-yy', pointer);
                    	occurrences.push(formateddate);
                	}
            	}
				return occurrences;
			//break;

			default:
				return occurrences;
			//break;
        }
	    //End switch
	    
	    //For daily / monthly schedules,
	    //while in current month, and event has not finished - generate occurrences.
		for ( var x in streams) {
			pointer = new Date(streams[x]);
			while (pointer <= month_end && pointer <= end) {
			formateddate = $.datepicker.formatDate('dd-mm-yy', pointer);
               		occurrences.push(formateddate);
               		pointer.setDate(pointer.getDate() + skip);
            	}
		}

		return occurrences;
    },
    
    
    /**
	 * Casts input as a positive integer
	 * @param value
	 * @return int Positive integer
	 */
    _absint: function( value ){
    	return Math.abs( parseInt( value, 10 ) );
    },
        
    
    /**
	 * Cast value as a positive integer
	 * @param arr
	 * @returns
	 */
    _array_filter: function( arr ){
    	return $.grep(arr,function(n){ return(n); });
    },

});


/**
 * Ensure the first and last (visible) ticket rows (tbody) have the appropriate classes.
 */
function eventorganiser_update_move_arrows(){
	$('#eventorganiser_tickets tbody.eo-ticket-row').removeClass( 'eo-first-ticket eo-last-ticket' );
	$('#eventorganiser_tickets tbody.eo-ticket-row:visible:first').addClass( 'eo-first-ticket' );
	$('#eventorganiser_tickets tbody.eo-ticket-row:visible:last').addClass( 'eo-last-ticket' );
}


/**
 * Initiate datepickers
 */
$('#eventorganiser_tickets').on( 'click', '.eo-ticket-input-to, .eo-ticket-input-from', function(e){
	$(this).datepicker({
		dateFormat: EO_Ajax_Event.format,
		changeMonth: true,
		changeYear: true,
		monthNamesShort: EO_Ajax_Event.locale.monthAbbrev,
		dayNamesMin: EO_Ajax_Event.locale.dayAbbrev,
		firstDay: parseInt( EO_Ajax_Event.startday, 10 ),
		/*onSelect: function(selectedDate) {
			var option = this.id == "eo-ticket-dialog-from" ? "minDate": "maxDate",
			instance = $(this).data("datepicker"),
			date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
			availability_dates.not(this).datepicker("option", option, date);
		}*/
	}).datepicker( "show" );
});

/**
 * Initiate timepickers
 */
$('#eventorganiser_tickets').on( 'click', '.eo-ticket-input-to-time, .eo-ticket-input-from-time', function(e){
	var options = eo_pro;
	$(this).timepicker({
	    showPeriodLabels: !options.is24hour,
	    showPeriod: !options.is24hour,
	    showLeadingZero: options.is24hour,
	    periodSeparator: '',
	    amPmText: options.locale.meridian,
	    hourText: options.locale.hour,
	    minuteText: options.locale.minute
	}).timepicker( "show" );
});


$('#eventorganiser_tickets').on( 'click', '.eo-move-ticket-up, .eo-move-ticket-down', function(e){
	e.preventDefault();
	var row = $(this).parents("tbody");
	var thisOrder = row.find('.eo-ticket-order').val(), theirOrder;
	
	if ( $(this).is( ".eo-move-ticket-up") ){
		
		theirOrder = row.prev(':visible').find('.eo-ticket-order').val();
		row.find('.eo-ticket-order').val( theirOrder );
		row.prev(':visible').find('.eo-ticket-order').val( thisOrder );
		row.insertBefore( row.prev(':visible') );
		
	} else if( $(this).is(".eo-move-ticket-down") ) {
		
		theirOrder = row.next(':visible').find('.eo-ticket-order').val();
		row.find('.eo-ticket-order').val( theirOrder );
		row.next(':visible').find('.eo-ticket-order').val( thisOrder );
		row.insertAfter( row.next(':visible') );
	} 
	eventorganiser_update_move_arrows();
});

eventorganiser_update_move_arrows();


/**
 * Delete ticket. Hide row and set action.
 */
$('#eventorganiser_tickets').on( 'click', '.eo-delete-ticket', function(e){
	e.preventDefault();
	var row = $(this).parents('tbody');
	row.find('td.column-ticket .eo-ticket-action').val('delete');
	row.css('background-color','red').fadeOut( 400, eventorganiser_update_move_arrows );
	
});


/**
 * Create new ticket.
 */
$('#eventorganiser_tickets').on( 'click', '#eo-add-ticket', function(e){

	e.preventDefault();
	$('#eventorganiser_tickets .eo-ticket-table tr.no-items').remove();
	var rows = $('#eventorganiser_tickets .eo-ticket-table tbody.eo-ticket-row').length;
	
	var html = eo_ticket_table_template;
	html = html.replace(/%%rows%%/g, rows );
	html = html.replace(/%%name%%/g, "" );
	html = html.replace(/%%selected%%/g, "-1" );
	html = html.replace(/%%deselected%%/g, "" );
	html = html.replace(/%%spaces%%/g, "" );
	html = html.replace(/%%price%%/g, "" );
	html = html.replace(/%%from%%/g, "" );
	html = html.replace(/%%from_time%%/g, "" );
	html = html.replace(/%%to%%/g, "" );
	html = html.replace(/%%to_time%%/g, "" );
	html = html.replace(/%%ticket_id%%/g, "" );
	//ticket.row_id = rows;

	$('#eventorganiser_tickets .eo-ticket-table').append( html );
	$('#eo-ticket-row-' + rows + ' .eo-edit-ticket').trigger('click');
	
	//Ensure inline-help appears
	$('#eventorganiser_tickets .eo-ticket-table .eo-inline-help').each(function() {
		var id = $(this).attr('id').substr(15);
		$(this).click(function(e){e.preventDefault();});
		$(this).qtip({
			content: {
				text: eoHelp[id].content,
				title: {
					text: eoHelp[id].title
				}
			},
			show: {
				solo: true 
			},
			hide: 'unfocus',
			style: {
				classes: 'qtip-wiki qtip-light qtip-shadow'
			},
			position : {
				 viewport: $(window)
			}
		});
	});
	
	eventorganiser_update_move_arrows();
});


/**
 * 'Live update' of ticket name
 */
$('#eventorganiser_tickets').on( 'keyup', '.eo-ticket-input-name', function(){
	var row = $(this).parents('tbody.eo-ticket-row');
	row.find('.eo-ticket-name').text( $(this).val() );
});


/**
 * Auto-correct of ticket price. Remove commas.
 */
$('#eventorganiser_tickets').on( 'keyup', '.eo-ticket-input-price', function(){
	var row = $(this).parents('tbody.eo-ticket-row');
	var price = $(this).val().replace(/,/g, '');
	$(this).val( price );
	row.find('.eo-ticket-price').text( price );
});

/**
 * 'Live update' of ticket price - cast to 2dp float
 */
$('#eventorganiser_tickets').on( 'change', '.eo-ticket-input-price', function(){
	$(this).val(parseFloat($(this).val()).toFixed(2));
	var row = $(this).parents('tbody.eo-ticket-row');
	row.find('.eo-ticket-price').text( $(this).val() );
});

/**
 * 'Live update' of ticket spaces
 */
$('#eventorganiser_tickets').on( 'keyup click', '.eo-ticket-input-spaces', function(){
	var row = $(this).parents('tbody.eo-ticket-row');
	row.find('.eo-ticket-spaces').text( $(this).val() );
});


/**
 * Select/Deselect all for occurrence picker
 */
$('#eventorganiser_tickets').on('click','.eo-select-all, .eo-deselect-all',function(e){
	e.preventDefault();
	var row = $(this).parents('tbody.eo-ticket-row');

	var op = row.find('.eo-ticket-occurrences-input');

	if( $(this).hasClass('eo-select-all') ){
		op.eo_occurrencepicker( 'select_all' );
	}else if( $(this).hasClass('eo-deselect-all') ){
		op.eo_occurrencepicker( 'deselect_all' );
	}
});




/**
 * On 'edit' display options for the ticket. Set-up the occurrence picker. 
 */
$('#eventorganiser_tickets').on( 'click', '.eo-edit-ticket', function(e){
	//Editting a ticket, open dialog and populate fields

	e.preventDefault();
	var row = $(this).parents('tbody.eo-ticket-row');
	var row_id = row.data('eo-ticket-row-id');
	
	$('#ticket-'+row_id+'-settings').toggle();
	
	if( $('#ticket-'+row_id+'-settings').is(":visible") ){
		row.addClass('eo-ticket-row-expanded');
		row.find('.eo-edit-ticket .eo-settings-toggle-arrow').html('&#x25B2;');
		var op = row.find('.eo-ticket-occurrences-input');
		var options = {
				dateFormat: "dd-mm-yy",
				changeMonth: true,
				changeYear: true,
				monthNamesShort: EO_Ajax_Event.locale.monthAbbrev,
				dayNamesMin: EO_Ajax_Event.locale.dayAbbrev,
				firstDay: parseInt( EO_Ajax_Event.startday, 10 ),
				ticket: row_id,
			};
		op.eo_occurrencepicker(options);
		op.datepicker("refresh");
		op.eo_occurrencepicker("refresh");
				
	}else{
		row.removeClass('eo-ticket-row-expanded');
		row.find('.eo-edit-ticket .eo-settings-toggle-arrow').html('&#x25BC;');
	}
});
});