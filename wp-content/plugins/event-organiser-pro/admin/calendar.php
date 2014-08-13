<?php

/**
 * Add tab to the admin calendar event dialog
 * @since 1.0
 * @access private
 * 
 * @param array $tabs Array of tabs
 */
function _eventorganiser_pro_admin_calendar_booking_tab( $tabs ) {
	$tabs['booking-detail'] = __( 'Bookings', 'eventorganiserp' );
	return $tabs;
}
add_filter( 'eventorganiser_calendar_dialog_tabs', '_eventorganiser_pro_admin_calendar_booking_tab' );


/**
 * Appends booking summary to the event summary in the admin calendar event dialog
 * @since 1.0
 * @access private
 * 
 * @param string $summary The event summary
 * @param int $post_id
 * @param int $occurrence_id
 * @param object $post (Event post object)
 * @return string The event summary
 */
function eventorganiser_pro_booking_summary( $summary, $post_id, $occurrence_id, $post ) {

	$available = 0;

	//If the we book series we are 'occurrence blind'.
	if ( eventorganiser_pro_get_option( 'book_series' ) )
		$occurrence_id = 0;

	$_tickets_cache = (array) wp_cache_get( '_eopro_ticketstats' );

	if ( isset( $_tickets_cache[$post_id][$occurrence_id] ) ):
		//Use cache
		$ticket_details = $_tickets_cache[$post_id][$occurrence_id];

	else:
		//Regenerate cache
		if ( !isset( $_tickets_cache[$post_id] ) )
			$_tickets_cache[$post_id] = array();

		$attending = eventorganiser_get_bookings( array( 'fields'=>'count_attending', 'occurrence_id'=>$occurrence_id, 'event_id' => $post_id ) );
		$available = eventorganiser_get_capacity( $post_id, $occurrence_id );
		$ticket_details = $_tickets_cache[$post_id][$occurrence_id] = compact( 'available', 'attending' );
		wp_cache_set( '_eopro_ticketstats', $_tickets_cache, false, 60 );
	endif;

	extract( $ticket_details );

	if ( ! empty ( $available ) ) {
		$summary .="<tr><th>".__( 'Attending', 'eventorganiserp' ).": </th><td>{$attending} / {$available}";

		if ( $attending ) {
			$summary .= sprintf( ' <a href="%s"> %s </a>  | <a href="%s"> %s </a>',
				eventorganiser_bookings_admin_url( $post_id, $occurrence_id ),
				__( 'View bookings', 'eventorganiserp' ),
				esc_url( eventorganiser_booking_export_url( array( 'event_id' => $post_id, 'occurrence_id' => $occurrence_id ) ) ),
				__( 'Export bookings', 'eventorganiserp' )
			);
		}
		$summary .="</td></tr>";
	}
	return $summary;
}
add_filter( 'eventorganiser_admin_cal_summary', 'eventorganiser_pro_booking_summary', 10, 4 );



/**
 * Filters the event being added to the calendar, adds the key booking-detail 
 * (corresponding to the tab key in _eventorganiser_pro_admin_calendar_booking_tab)
 * with value a table of bookings.
 * 
 * @param array $event Event array being sent to the calendar
 * @param object $post The event post object
 * @return array The event array
 */
function eventorganiser_calendar_booking_summary( $event, $post ) {

	$occurrence_id = eventorganiser_pro_get_option( 'book_series' ) ? 0 : $post->occurrence_id;
	$bookings = eventorganiser_get_bookings( array(
			'event_id'=> $post->ID,
			'occurrence_id'=> $occurrence_id,
			'numberposts'=>-1,
		) );

	if ( !$bookings ) {
		return $event;
	}

	$headers = array(
		'booking_id'=> __( 'Booking', 'eventorganiserp' ),
		'bookee'=> __( 'Bookee', 'eventorganiserp' ),
		'tickets'=> __( 'Tickets', 'eventorganiserp' ),
		'amount'=> __( 'Amount', 'eventorganiserp' ),
		'status'=> __( 'Status', 'eventorganiserp' ),
	);

	//TODO support "non-confirmed" custom status?
	$pending = eventorganiser_get_bookings( array( 'status' => 'pending', 'fields'=>'count_tickets', 'occurrence_id'=> $occurrence_id, 'event_id'=> $post->ID ));
	$confirmed = eventorganiser_get_bookings( array( 'status' => eo_get_confirmed_booking_statuses(), 'fields'=>'count_tickets', 'occurrence_id'=> $occurrence_id, 'event_id'=> $post->ID ));
	$capacity = eventorganiser_get_capacity( $post->ID, $occurrence_id );
	$total = intval( $confirmed ) + intval( $pending );

	$booking_table = sprintf(
			'<p> <strong>%s:</strong> %s | <strong>%s:</strong> %s | <strong>%s:</strong> %s | <strong>%s:</strong> %s </p> ',
				__( 'Confirmed', 'eventorganiserp' ),
				$confirmed,
				__( 'Pending', 'eventorganiserp' ),
				$pending,
				__( 'Total', 'eventorganiserp' ),
				$total,
				__( 'Capacity', 'eventorganiserp' ),
				$capacity				
			);
	
	$booking_table .= '<table class="form-table"><tbody>';
	$booking_table .= '<tr>';
	
	foreach ( $headers as $header ) {
		$booking_table .= sprintf( '<td> <strong> %s </strong> </td>', $header );
	}
	$booking_table .= '</tr>';
	foreach ( $bookings as $booking ) {
		$booking_table .= '<tr>';
		foreach ( $headers as $header_id => $header ) {
			switch ( $header_id ) {
				case 'booking_id':
					$booking_table .= sprintf( '<td> <a href="%s">#%d</a></td>', eventorganiser_edit_booking_url( $booking->ID ), $booking->ID );
				break;
				case 'bookee':
					$user_id = (int) eo_get_booking_meta( $booking->ID, 'bookee' );
					$user_data = get_userdata( $user_id );
					if ( $user_data )
						$booking_table .= sprintf( '<td> %s </td>', $user_data->display_name );
				break;
				case 'tickets':
					$booking_table .= sprintf( '<td> %s </td>', eo_get_booking_meta( $booking->ID, 'ticket_quantity' ) );
				break;
				case 'amount':
					$booking_table .= sprintf( '<td> %s </td>', eo_format_price( eo_get_booking_meta( $booking->ID, 'booking_amount' ) ) );
				break;
				case 'status':
					$booking_table .= sprintf( '<td> %s </td>', eo_get_booking_status( $booking ) );
				break;
			}
		}
		$booking_table .= '</tr>';
	}
	$booking_table .= '</tbody></table>';

	$event['booking-detail'] = $booking_table;
	return $event;
}
add_filter( 'eventorganiser_admin_calendar', 'eventorganiser_calendar_booking_summary', 10, 2 );
?>