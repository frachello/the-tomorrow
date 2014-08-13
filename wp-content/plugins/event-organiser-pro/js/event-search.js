var eventorganiser_seach_dates;
jQuery(document).ready(function($) {
	eventorganiser_seach_dates = $(".eo-search-form-event-date-to, .eo-search-form-event-date-from").datepicker({
		dateFormat: 'yy-mm-dd',
		changeMonth: true,
		changeYear: true,
		monthNamesShort: EO_Pro_DP.locale.monthAbbrev,
		dayNamesMin: EO_Pro_DP.locale.dayAbbrev,
		firstDay: parseInt( EO_Pro_DP.startday, 10 ),
		nextText: EO_Pro_DP.locale.nextText,
		prevText: EO_Pro_DP.locale.prevText,
		beforeShow: function(input, inst) {
			if( inst.hasOwnProperty( 'dpDiv' ) ){
				inst.dpDiv.addClass('eo-datepicker');
			}else{
				$('#ui-datepicker-div').addClass('eo-datepicker');
			}
		},
		onSelect: function(selectedDate) {
			var option = $(this).data( 'range' ) == "start" ? "minDate": "maxDate",
			instance = $(this).data("datepicker"),
			date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
			eventorganiser_seach_dates.not(this).datepicker("option", option, date);
	}
});
});
