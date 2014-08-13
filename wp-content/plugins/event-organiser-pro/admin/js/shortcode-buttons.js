var eventorganiser = eventorganiser || {};
/**
 * Simply compares two string version values.
 * 
 * Example:
 * versionCompare('1.1', '1.2') => -1
 * versionCompare('1.1', '1.1') =>  0
 * versionCompare('1.2', '1.1') =>  1
 * versionCompare('2.23.3', '2.22.3') => 1
 * 
 * Returns:
 * -1 = left is LOWER than right
 *  0 = they are equal
 *  1 = left is GREATER = right is LOWER
 *  And FALSE if one of input versions are not valid
 *
 * @function
 * @param {String} left  Version #1
 * @param {String} right Version #2
 * @return {Integer|Boolean}
 * @author Alexey Bass (albass)
 * @since 2011-07-14
 */
eventorganiser.versionCompare = function(left, right) {
    if (typeof left + typeof right != 'stringstring')
        return false;
    
    var a = left.split('.'),   b = right.split('.'),   i = 0, len = Math.max(a.length, b.length);
        
    for (; i < len; i++) {
        if ((a[i] && !b[i] && parseInt(a[i], 10 ) > 0) || (parseInt(a[i], 10 ) > parseInt(b[i], 10 ))) {
            return 1;
        } else if ((b[i] && !a[i] && parseInt(b[i], 10 ) > 0) || (parseInt(a[i], 10 ) < parseInt(b[i], 10 ))) {
            return -1;
        }
    }
    
    return 0;
};

jQuery(document).ready(function($) {
function eventorganiser_generate_shortocode(tabIndex){
	var shortcode ='';
	
	if( tabIndex === 0 ){
		shortcode = '[eo_events'
						+eo_generate_venue_attribute(tabIndex)
						+eo_generate_cat_attribute(tabIndex);

		if ($('#eo-shortcode-tab-0 .event-start-after').val() !== ''){
			shortcode += ' event_start_after="'+$('#eo-shortcode-tab-0 .event-start-after').val()+'"';
		}
		if ($('#eo-shortcode-tab-0 .event-end-after').val() !== ''){
			shortcode += ' event_end_after="'+$('#eo-shortcode-tab-0 .event-end-after').val()+'"';
		}

		shortcode += ']';

	}else if( tabIndex == 1 ){
		shortcode = '[eo_fullcalendar'
						+eo_generate_venue_attribute(tabIndex)
						+eo_generate_cat_attribute(tabIndex)
						+eo_generate_tooltip_attribute(tabIndex);

		if ($('#eo-shortcode-tab-1 .event-shortcode-calendar-view:checked').val() !== ''){
			var defaultView = $.trim($('#eo-shortcode-tab-1 .event-shortcode-calendar-view:checked').val());
			shortcode += ' defaultView="'+defaultView+'"';
		}
		if ($('#eo-shortcode-tab-1 .event-shortcode-time-format').val() !== ''){
			var timeFormat = $.trim($('#eo-shortcode-tab-1 .event-shortcode-time-format').val());
			shortcode += ' timeFormat="'+timeFormat+'"';
		}
		if ($('#eo-shortcode-tab-1 .event-shortcode-bookee').attr('checked') ){
			shortcode += ' users_events="true"';
		}
		shortcode += ']';

	}else if( tabIndex == 2 ){
		shortcode = '[eo_venue_map'
						+eo_generate_venue_attribute(tabIndex)
						+eo_generate_tooltip_attribute(tabIndex);

		if ($('#eo-shortcode-tab-2 .event-shortcode-map-height').val() !== ''){
			var height = $.trim($('#eo-shortcode-tab-2 .event-shortcode-map-height').val());
			shortcode += ' height="'+height+'"';
		}
		if ($('#eo-shortcode-tab-2 .event-shortcode-map-width').val() !== ''){
			var width = $.trim($('#eo-shortcode-tab-2 .event-shortcode-map-width').val());
			shortcode += ' width="'+width+'"';
		}

		shortcode += ']';
	}else {
		shortcode = '[event_search';
		
		var filters_attr =[];
		var filters = ['event_category', 'event_venue', 'city', 'state', 'date', 'country'];
		
		for( var i=0; i < filters.length; i ++ ){
			var filter = filters[i];
			
			if ( $('#eo-shortcode-tab-'+tabIndex+' .event-search-filter-'+filter).attr('checked') ){
				filters_attr.push( filter ); 
			}			
		}
		if( filters_attr != [] ){
			shortcode +=' filters="'+filters_attr.join(',')+'"';
		}

		shortcode += ']';
	}

	return shortcode;
}

	function eo_generate_venue_attribute(tabIndex){
		if ($('#eo-shortcode-tab-'+tabIndex+' .event-shortcode-venue-selection').val() !== ''){
			var venues = $.trim($('#eo-shortcode-tab-'+tabIndex+' .event-shortcode-venue-selection').val());
			venues = venues.replace(/^[,\s]+|[,\s]+$/g, '').replace(/,[,\s]*,/g, ',');
			return ' event_venue="'+venues+'"';
		}
		return '';
	}

	function eo_generate_tooltip_attribute(tabIndex){
		if ( $('#eo-shortcode-tab-'+tabIndex+' .event-shortcode-tooltip').attr('checked') ){
			return ' tooltip="true"';
		}else{
			return ' tooltip="false"';
		}
	}

	function eo_generate_cat_attribute(tabIndex){
		if ($('#eo-shortcode-tab-'+tabIndex+' .event-shortcode-cat-selection').val() !== ''){
			var cat = $.trim($('#eo-shortcode-tab-'+tabIndex+' .event-shortcode-cat-selection').val());
			cat = cat.replace(/^[,\s]+|[,\s]+$/g, '').replace(/,[,\s]*,/g, ',');
			return ' event_category="'+cat+'"';
		}
		return '';
	}

	$( "#eo-shortcode-button-dialog" ).dialog({
		height: 450,
		width: 550,
		modal: true,
		dialogClass: 'eo-shortcode-button-dialog',
		closeOnEscape: true,
		draggable: false,
		resizable: false,
		autoOpen: false,
		open: function() {$(this).find('.ui-dialog-titlebar-close').blur();$('#eo-shortcode-tabs ul.ui-tabs-nav li').blur();},
		buttons: {
			"Create shortcode": function() {
				/* Backwards compat with WP 3.3-3.5 (UI 1.8.16-1.8.2)*/ 
				var jquery_ui_version = $.ui ? $.ui.version || 0 : -1;
				var activeTab = ( eventorganiser.versionCompare( jquery_ui_version, '1.9' ) >= 0 ? 'active' : 'selected' );
				var shortcode = eventorganiser_generate_shortocode( $('#eo-shortcode-tabs').tabs( 'option', activeTab ) );
				tinymce.execCommand('mceInsertContent', false, shortcode); 
				$(this).dialog("close");
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}
	});

	tinymce.create('tinymce.plugins.buttonPlugin', {
		init : function(ed, url) {
				// Register commands
				ed.addCommand('mcebutton', function() {
				$( "#eo-shortcode-button-dialog" ).dialog("open");
			});
			 
			// Register buttons
			ed.addButton('eo_button', {
				title : 'Event Organiser ShortCodes', 
				cmd : 'mcebutton', 
				icon: 'eo-calendar', 
				image: ( eventorganiser.versionCompare( eventorganiser.wp_version, '3.8' ) >= 0 ? false : url + '/img/eoicon-20.png' )
			});
		}
	});
	 
	// Register plugin
	// first parameter is the button ID and must match ID elsewhere
	// second parameter must match the first parameter of the tinymce.create() function above
	tinymce.PluginManager.add('eo_button', tinymce.plugins.buttonPlugin);

	$( "#eo-shortcode-button-dialog" ).parent().find('.ui-dialog-titlebar').remove();
	$('#eo-shortcode-tabs').tabs();
	$('#eo-shortcode-tabs ul.ui-tabs-nav li').css('top','2px');
	$('#eo-shortcode-tabs ul.ui-tabs-nav li a').css('outline','none');

	var eo_autocomplete = {
			delay: 0,
			source: function( request, response ) {
                    		// delegate back to autocomplete, but extract the last term
                    		response( $.ui.autocomplete.filter(
                        		this.options.values, eventorganiser_extract_last( request.term ) ) );
			},	
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function(event, ui) {        
				var terms = eventorganiser_split( this.value );
				terms.pop();
				terms.push( ui.item.value );
				terms.push( "" );
				this.value = terms.join( ", " );
				return false;
        	}
		};
		eo_autocomplete.values = EOPro.venue;
		eo_autocomplete.action = 'eo-search-venue';
		$('.event-shortcode-venue-selection').autocomplete(eo_autocomplete);

		eo_autocomplete.values = EOPro.category;
		eo_autocomplete.action = 'eo-search-category';
		$('.event-shortcode-cat-selection').autocomplete(eo_autocomplete);

		function eventorganiser_split( val ) {
			return val.split( /,\s*/ );
		}
		function eventorganiser_extract_last( term ) {
			return eventorganiser_split( term ).pop();
		}
});
