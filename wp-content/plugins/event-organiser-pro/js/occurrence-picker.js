if ( typeof EO_SCRIPT_DEBUG === 'undefined') { EO_SCRIPT_DEBUG = true;}
var eventorganiserpro = eventorganiserpro || {};
eventorganiserpro.Model = {}; eventorganiserpro.View = {}; eventorganiserpro.Collection = {};

(function($) {
if( EO_SCRIPT_DEBUG ){
	console.log( eventorganiserpro );
}

jQuery(document).ready(function($) {
/* Suppress / show fields since JS is enabled */
$( '.eo-show-if-js' ).show();
$( '.eo-hide-if-js').hide();
$( '.eo-enable-if-js').attr( 'disabled', false );
$( '.eo-disable-if-js').attr( 'disabled', true );

$('.eo-booking-form-login-form').hide();
$('.eo-booking-no-account-prompt').hide();
$( '.eo-booking-login-toggle' ).click(function(ev){
	ev.preventDefault();
	$( '.eo-booking-login-prompt' ).hide();
	$( '.eo-booking-no-account-prompt' ).show();
	$('.eo-booking-form-login-form').show();
	$('#eo-booking-form').hide();
});
$( '.eo-booking-no-account-toggle' ).click(function(ev){
	ev.preventDefault();
	$( '.eo-booking-no-account-prompt' ).hide();
	$( '.eo-booking-login-prompt' ).show();
	$('.eo-booking-form-login-form').hide();
	$('#eo-booking-form').show();
});
});

//======================================
// Models
//======================================
eventorganiserpro.Model.EOTicket = Backbone.Model.extend({
	
	defaults: {
		"name": "",
		"available": 0,
		"quantity": 0,
		"price": 0
	},
	
	/**
	 * Increase the quantity of ticket in the basket by 1
	 */
	increase_quantity: function(){
		this.set( 'quantity', this.get( 'quantity' ) + 1 );
	},
	
	/**
	 * Decrease the quantity of ticket in the basket by 1
	 */
	decrease_quantity: function(){
		qty = Math.max( 0, this.get( 'quantity' ) - 1  );
		this.set( 'quantity', qty );
	},
     
});

eventorganiserpro.Collection.EOTickets = Backbone.Collection.extend({
	model: eventorganiserpro.Model.EOTicket
});

eventorganiserpro.Model.EOEvent = Backbone.Model.extend({

	defaults: {
		occurrence_id: false,
		occurrence_date: false,
		show_datepicker: false,
	},
	
	initialize: function(){
		
		if( this.get( 'show_datepicker' ) && !this.get('occurrence_id') ){
			if( this.get('occurrence_date') ){
				occurrence = this.get_occurrence_by_date( this.get('occurrence_date') );
			}else{
				occurrence = this.get_next_available_occurrence();	
			}
			this.set_occurrence( occurrence );
		}
		
		if( EO_SCRIPT_DEBUG ){
			console.log( 'show datepicker?', this.get( 'show_datepicker' ) );
		}
	},
	
	/**
	 * Get occurrence by ID 
	 */
	get_occurrence: function( id ){
		var result = _.where( this.get('occurrences'), {id: parseInt( id, 10 ) });
		return result ? result[0] : false;  
	},

	/**
	 * Get occurrence from the date.
	 * @param string date Date in Y-m-d format
	 */
	get_occurrence_by_date: function( date ){
		var result = _.where( this.get('occurrences'), {date: date});
		return result ? result[0] : false;  
	},
	
	/**
	 * Get the next occurrence which available tickets
	 */
	get_next_available_occurrence: function(){
		var tonight = new Date();
		tonight.setHours( 23 );
		tonight.setMinutes( 59 );
		
		var occurrences = _.chain(this.get('occurrences'))
		  .filter(function( o ){ date = new Date( o.date );return o.available && ( date > tonight ); })
		  .sortBy(function( o ){ return new Date(o.date); })
		  .first()
		  .value();
		
		return occurrences ? occurrences : false;  
	},
	
	/**
	 * Set the occurrence by ID/object
	 */
	set_occurrence: function( occurrence_id_or_obj ){
		
		var occurrence;
		
		if( !isNaN( parseFloat( occurrence_id_or_obj ) ) && isFinite( occurrence_id_or_obj ) ){
			occurrence = this.get_occurrence( occurrence_id_or_obj );
		}else{
			occurrence = occurrence_id_or_obj;
		}
		
		if( EO_SCRIPT_DEBUG ){
			console.log( 'set occurrence (ID)', occurrence.id );
		}
		
		this.set( 'occurrence_date', occurrence.date );			
		this.set( 'occurrence_id', occurrence.id );

	},		

	/**
	 * Set the occurrence by date
	 */
	set_occurrence_by_date: function( date ){
		var o = this.get_occurrence_by_date( date );
		this.set_occurrence( o.id );
	},
	
	/**
	 * Does this date have tickets available
	 * @return int 1 = yes, 0 = no (sold out), -1 = no (no tickets for date)
	 */
	has_tickets_available: function( date ){
		var result = _.where( this.get('occurrences'), { date: date } );
		return ( result && result.length > 0 ) ? result[0].available : -1;
	}	
	
});

eventorganiserpro.Model.EOCart = Backbone.Model.extend({
	
	defaults: {
		"total": 0,
		"tickets": [],
		"ticket_quantity": 0,
		"event" : false,
	},
	
	initialize: function() {
		
		this.on('change:total', this._validate_total, this );
		this.get('tickets').on( "change:quantity", this.calculate, this);
		this.get('tickets').on( "change:price", this.calculate, this);
		this.get('event').on( "change:occurrence_id", this.set_ticket_event_availability, this);
		
		this.calculate();
		this.set_ticket_event_availability();
	},
	
	/**
	 * Ensurse total is a float 2 decimal places
	 */
	_validate_total: function( model, total ) {
		total = parseFloat( parseFloat( total ).toFixed(2) );
		this.set(total);
		if( EO_SCRIPT_DEBUG ){
			console.log( 'total= ' + total );
		}
	},
	
	/**
	 * When occurrence changes, set availability of tickets and
	 * adjust quanitity if required 
	 */
	set_ticket_event_availability: function(){
		
		var event = this.get('event');
		
		if( !event.get( 'show_datepicker' ) ){
			return;
		}
		
		
		var occurrence_id = event.get('occurrence_id');
		var occurrence = event.get_occurrence( occurrence_id );
		
		if( this.get('tickets') ){
			this.get('tickets').each( function( ticket, index, list ){
				var max = 0;
			
				if( occurrence && _.has( occurrence.tickets, ticket.get('id') )  ){
					max = Math.max( 0, occurrence.tickets[ticket.get('id')] );
				}
			
				ticket.set( 'available', max );
				if( ticket.get('quantity') > max ){
					ticket.set( 'quantity', max );
				}
			});
		}

	},
			
	/**
	 * Calculate booking total and quantity of tickets in 
	 * basket. 
	 */
	calculate: function(){
		
		var qty = 0, total = 0;
		
		this.get('tickets').each( function( ticket, index, list ){
			qty += parseInt( ticket.get('quantity'), 10 );
			total += parseInt( ticket.get('quantity'), 10 ) * parseFloat( ticket.get('price') );
		});

		total = parseFloat( total );
		qty = parseInt( qty, 10 );
		
		//Now filter cart and cart total
		//Backwards compataility
		var cart = { 
			total: total, 
			quantity: qty, 
			basket: this, 
			occurrence: this.get('event').get_occurrence( this.get('event').get('occurrence_id') ),
			event_id: this.get('event').get('id'),
		};

		cart =  wp.hooks.applyFilter( 'eventorganiser.checkout_cart', cart );
		cart.total =  wp.hooks.applyFilter( 'eventorganiser.checkout_total', cart.total, cart );

		this.set( 'total', cart.total.toFixed( 2 ) );
		this.set( 'ticket_quantity', cart.quantity );
		
	},
});

//======================================
// Views
//======================================
eventorganiserpro.View.EOEventView = Backbone.View.extend({
    
	events: {
		"change select#eo_occurrence_picker_select": "set_occurrence"
	},
	
	initialize: function() {
		this.setElement( $(".eo-booking-ticket-picker" ) );
		
		this.model.on( "change:occurrence_id", this.update_view, this );
		
		_.bindAll(this, "beforeShowDay", "set_occurrence_by_date" );
		
		this.render();
		
		this.update_view();
    },
    
    render: function() {
    	
    	var dp = this.$el.find( '#eo-booking-occurrence-picker' );
    		
    	if ( dp.length == 0 ) {
    		return;
    	}
    	
    	dp.datepicker({
				
    		dateFormat: "yy-mm-dd",
			firstDay: parseInt( EO_Pro_DP.startday, 10 ),
			changeMonth: true,
			changeYear: true,
					
			nextText: EO_Pro_DP.locale.nextText,
			prevText: EO_Pro_DP.locale.prevText,
			monthNamesShort: EO_Pro_DP.locale.monthAbbrev,
			dayNamesMin: EO_Pro_DP.locale.dayAbbrev,
					
			beforeShowDay: this.beforeShowDay,
					
			onSelect: this.set_occurrence_by_date,
					
		}).children().addClass('eo-datepicker');
	},
	
	beforeShowDay: function(date) {
		var date_str = $.datepicker.formatDate('yy-mm-dd', date);
		var available = this.model.has_tickets_available( date_str );

		if( available === 1 ){
			return [true, "ui-state-active eo-occurrence-id-"+this.model.get('occurrence_id'), ""];
		}else if(available === 0){
			return [false, "ui-state-active eo-booking-no-tickets-available", ""];
		}
		return [false, "ui-state-disabled", ''];
	},
	
	set_occurrence: function(){
		var occurrence_id = this.$el.find( '#eo_occurrence_picker_select' ).val();
		this.model.set_occurrence( occurrence_id );
	},
	
	set_occurrence_by_date: function( date ){ 
		this.model.set_occurrence_by_date( date );
	},
	    
	update_view: function(){
		var date = new Date( this.model.get('occurrence_date') );
		this.$el.find('#eo-booking-occurrence-picker').datepicker("setDate", date );
		this.$el.find('#eo_occurrence_picker_select').val( this.model.get('occurrence_id') );
		//Hide the 'please select a date'
		$('#eo-booking-select-date').hide();			
		$('#eo-booking-occurrence-id').val( this.model.get('occurrence_id') );
		$('#eo-booking-occurrence-date').val( this.model.get('occurrence_date') );
	}
});

eventorganiserpro.View.EOTicketView = Backbone.View.extend({
	
	events: {
	    "change input": "update_ticket",
	    "deselect": "update_ticket",
	},

	initialize: function() {
    	
		this.setElement( $("#eo-booking-ticket-"+this.model.get('id') ) );
		
		// _.bindAll binds functions called by events to the 
        // view (by passing it the view context as 'this'
        _.bindAll(this, "update_ticket");
        
        this.model.on( "change:available", this.render, this );
        this.model.on( "change:quantity", this.render, this );
        
        this.render();
    },
    
    render: function() {

    	var $input = this.$el.find('[data-eo-ticket-qty="'+ this.model.get('id') + '"]' );
    	
    	if( $input.is(':checkbox') || $input.is(':radio') ){
			qty = $input.is(":checked") ? 1 : 0;
			if( $input.is(':radio') ){
				$input.val( this.model.get('id' ) );	
			}else{
				$input.val( this.model.get('quantity' ) );
			}
			
		}else{
			qty = parseInt( $input.val(), 10 );
			$input.attr( 'max', this.model.get( 'available' ) );
			$input.val( this.model.get('quantity' ) );
		}
    	
    	if( this.model.get( 'available' ) > 0 ){
    		this.$el.show();
    	}else{
    		this.$el.hide();
    	}
    	
    },
    
    /**
     * When input is adjusted, update model
     */
    update_ticket: function(){
    	var qty = 0, $input = this.$el.find('[data-eo-ticket-qty="'+ this.model.get('id') + '"]' );

		if( $input.val() !== '' ){
			if( $input.is(':checkbox') || $input.is(':radio') ){
				qty = $input.is(":checked") ? 1 : 0;
				
				if( qty > 0 && $input.is(':radio') ){
					$('input[name="' + $input.attr('name') + '"]').not($input).trigger('deselect');
				}

			}else{
				qty = parseInt( $input.val(), 10 );
			}
		}
		this.model.set('quantity', qty );
    }
  
});

eventorganiserpro.View.EOCartView = Backbone.View.extend({
		
	initialize: function() {
    	
		this.setElement( $('.eo-booking-ticket-picker') );
		
		this.$el.find('.eo-booking-total-row').css( 'visibility', 'hidden' );
		this.$el.find('.eo-booking-total-row').show();
		
        _.bindAll(this, "update_view");
        
        this.model.on( "change", this.update_view, this );
        
        this.update_view();
    },
    
    update_view: function() {
		if( this.model.get('ticket_quantity') > 0 ){
			this.$el.find('.eo-booking-total-row').css( 'visibility', 'visible' );
		}else{
			this.$el.find('.eo-booking-total-row').css( 'visibility', 'hidden' );
		}
		
		this.$el.find('.eo-booking-total-amount').text( this.model.get('total') );
		this.$el.find('.eo-booking-total-quantity').text( this.model.get('ticket_quantity') );
    }
  
});


})(jQuery);
jQuery(document).ready(function($) {
	//======================================
	// Initialize
	//======================================
	var occurrence_date = false,occurrence_id = false;
	if( $('#eo-booking-occurrence-id').val() && 0 !== parseInt( $('#eo-booking-occurrence-id').val(), 10 ) ){
		occurrence_date = $('#eo-booking-occurrence-date').val();
		occurrence_id = parseInt( $('#eo-booking-occurrence-id').val(), 10 );
	}
	
	if( eventorganiserpro.book_series ){
		occurrence_id = 0;
	}else if( !occurrence_id && !eventorganiserpro.event.is_recurring ){
		occurrence_id = eventorganiserpro.event.occurrence_ids[0];
	}

	eventorganiserpro.models = {}; eventorganiserpro.collections = {}; eventorganiserpro.views = { eoTicketViews: false };
	
	eventorganiserpro.models.eoEvent = new eventorganiserpro.Model.EOEvent( {
		occurrences: eventorganiserpro.occurrences,
		event_id: eventorganiserpro.event.id,
		show_datepicker: eventorganiserpro.event.show_datepicker,
		occurrence_date: occurrence_date,
		occurrence_id: occurrence_id
	} );
	
	eventorganiserpro.collections.eoTickets = new eventorganiserpro.Collection.EOTickets();
	for ( var t_id in eventorganiserpro.tickets_obj ) {
		if( eventorganiserpro.tickets_obj.hasOwnProperty( t_id ) ){
			var ticket = eventorganiserpro.tickets_obj[t_id];
			ticket.id = t_id;
			eoTicket = new eventorganiserpro.Model.EOTicket( ticket );

			eventorganiserpro.collections.eoTickets.push( eoTicket );
			eventorganiserpro.views.eoTicketViews[t_id] = new eventorganiserpro.View.EOTicketView({ model: eoTicket });
		}
	}
		
	eventorganiserpro.eoCart = new eventorganiserpro.Model.EOCart( { 
		tickets: eventorganiserpro.collections.eoTickets,
		event: eventorganiserpro.models.eoEvent,
	} );
	
	eventorganiserpro.views.eoEventView = new eventorganiserpro.View.EOEventView({ model: eventorganiserpro.eoCart.get('event') });
	eventorganiserpro.views.eoCartView = new eventorganiserpro.View.EOCartView({ model: eventorganiserpro.eoCart });
	
	eventorganiserpro.eoCart.calculate();
	//======================================
	// Booking Form
	//======================================
	/* Datepicker custom field in booking form */
	if( $(".eo-booking-field-date").length > 0 ){
		$(".eo-booking-field-date").datepicker({
			dateFormat: 'yy-mm-dd',
			nextText: EO_Pro_DP.locale.nextText,
			prevText: EO_Pro_DP.locale.prevText,
			beforeShow: function(input, inst) {
				if( inst.hasOwnProperty( 'dpDiv' ) ){
					inst.dpDiv.addClass('eo-datepicker');
				}else{
					$('#ui-datepicker-div').addClass('eo-datepicker');
				}
			},
			changeMonth: true,
			changeYear: true,
			monthNamesShort: EO_Pro_DP.locale.monthAbbrev,
			dayNamesMin: EO_Pro_DP.locale.dayAbbrev,
			firstDay: parseInt( EO_Pro_DP.startday, 10 )
		});
		$(".eo-booking-field-date").each( function() {
			$(this).datepicker( 'option', 'dateFormat', $(this).data( "dateformat" ) );
			if( $(this).data( "defaultdate" ) ){
				var defaultDate = new Date( $(this).data( "defaultdate" ) );
				$(this).datepicker( 'option', 'defaultDate', defaultDate );
			}
		});
	}
	
	/**
	 * On submit, disable button to prevent repeated clicks
	 * and show a 'waiting' icon
	 */
  	$('#eo-booking-form').submit(function(event) {
  		var $form = $(this);
  		$form.find('input[type=submit]').prop('disabled', true);
  		$form.find('.eo-booking-form-waiting').show();
  	});
});