var eventorganiser = eventorganiser || {};
(function ($) {
	
	wp.hooks.addFilter( 'eventorganiser.fullcalendar_options', function( args, calendar ){
		
		args.countries = eventorganiser.fullcal.countries;
		args.cities = eventorganiser.fullcal.cities;
		args.states = eventorganiser.fullcal.states;
		
		args.customButtons.country =  eventorganiser_venue_country_dropdown;
		args.customButtons.city =  eventorganiser_venue_city_dropdown;
		args.customButtons.state = eventorganiser_venue_state_dropdown;
        
		args.buttonText.city = eventorganiser.locale.view_all_cities;
		args.buttonText.state = eventorganiser.locale.view_all_states;
		args.buttonText.country = eventorganiser.locale.view_all_countries;

		args.users_events = calendar.users_events;
		
		return args;
	},2);
	
	wp.hooks.addFilter( 'eventorganiser.fullcalendar_render_event', function( bool, event, element, view ){
	    var venue_meta = ['country','state','city'];
	    for( var i = 0; i < venue_meta.length; i++ ){
	    	var key = venue_meta[i];
	    	var value = $(view.calendar.options.id).find(".filter-venue-"+key+" .eo-cal-filter").val(); 
	       	if ( typeof value !== "undefined" && value !== "" ) {
	       		if( typeof event[key] == "undefined" || value != event[key] ){ 
	       			return false;
	       		}
	       	}
	    }	
	    return bool;
	}, 4 );
	
	function eventorganiser_venue_country_dropdown(options){
		
		if( typeof options.countries !== 'undefined' ){
			var countries = options.countries;
			
			var html="<select class='eo-cal-filter' id='eo-event-venue-country'>";
			html+="<option value=''>"+options.buttonText.country+"</option>";

			for (var i=0; i< countries.length; i++){
				html+= "<option value='"+countries[i]+"'>"+countries[i]+"</option>";
			}
			html+="</select>";
			var element = $("<span class='fc-header-dropdown filter-venue-country'></span>");
			element.append(html);
			return element;
		}
	}

	function eventorganiser_venue_city_dropdown(options){
		if( typeof options.cities !== 'undefined' ){
			var cities = options.cities;
			
			var html="<select class='eo-cal-filter' id='eo-event-venue-city'>";
			html+="<option value=''>"+options.buttonText.city+"</option>";

			for (var i=0; i< cities.length; i++){
				html+= "<option value='"+cities[i]+"'>"+cities[i]+"</option>";
			}
			html+="</select>";
			var element = $("<span class='fc-header-dropdown filter-venue-city'></span>");
			element.append(html);
			return element;
		}
	}
	
	function eventorganiser_venue_state_dropdown(options){
		
		if( typeof options.states !== 'undefined' ){
			var states = options.states;
			
			var html="<select class='eo-cal-filter' id='eo-event-venue-state'>";
			html+="<option value=''>"+options.buttonText.state+"</option>";

			for (var i=0; i< states.length; i++){
				html+= "<option value='"+states[i]+"'>"+states[i]+"</option>";
			}
			html+="</select>";
			var element = $("<span class='fc-header-dropdown filter-venue-state'></span>");
			element.append(html);
			return element;
		}
	}

	
})(jQuery);