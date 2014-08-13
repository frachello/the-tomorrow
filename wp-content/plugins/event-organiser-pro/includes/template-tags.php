<?php
/**
 * Booking Form Template functions
 *
 * @package booking-form-template-functions
 */

/**
 * 
 * @since 1.4
 * @return string
 */
function eo_get_booking_form_total_placholder(){
	$symbol = eventorganiser_get_currency_symbol( eventorganiser_pro_get_option( 'currency' ) );
	$placeholder = ( eventorganiser_pro_get_option( 'currency_position' ) == 1 ? '%1$s %2$s' : '%2$s %1$s' );
	return sprintf( $placeholder, $symbol, '<span class="eo-booking-total-amount"></span>' );
}

/**
 * 
 * @since 1.4
 * @return string
 */
function eo_get_booking_form_quantity_placholder(){
	return '<span class="eo-booking-total-quantity"></span>';
}

/**
 * @since 1.4
 * @param unknown_type $ticket
 */
function eo_booking_form_ticket_data_attr( $ticket ){
	echo sprintf( 'data-eo-ticket-id="%d"', $ticket['mid'] );
	echo sprintf( 'data-eo-ticket-name="%s"', $ticket['name'] );
	echo sprintf( 'data-eo-ticket-price="%s"', $ticket['price'] );	
}

/**
 * 
 * @since 1.4
 * @return string
 */
function eo_get_booking_occurrence_picker( $event_id, $use_datepicker = true ){

	$html = $use_datepicker ? '<div id="eo-booking-occurrence-picker" style="display:none" class="eo-show-if-js"></div>' : '';
	$occurrence_tickets = eo_get_the_occurrences_tickets( $event_id );
	
	$sorter = EO_Uasort::get_instance();
	$occurrence_tickets = $sorter->sort( $occurrence_tickets, 'date' );

	$occurrence_options = array();
	$format = get_option('date_format');
	
	$html .= sprintf( 
				'<select id="%s" name="%s" class="%s" >',
				'eo_occurrence_picker_select',
				'eventorganiser[booking][occurrence_id]',
				$use_datepicker ? 'eo-hide-if-js eo-disable-if-js' : ''
			);
	
	foreach( $occurrence_tickets as $occurrence ){
		$occurrence_options[] = eo_format_date( $occurrence['date'], $format );
		$html .= sprintf( 
			'<option value="%1$d" %3$s >%2$s %4$s</option>',
			$occurrence['id'],
			eo_format_date( $occurrence['date'], $format ),
			empty( $occurrence['available'] ) ? 'disabled' : '',
			empty( $occurrence['available'] ) ? '(' . __( 'Sold out', 'eventorganiserp' ) . ')' : ''
		);
	}
	$html .= '</select>';
	
	return $html;
}