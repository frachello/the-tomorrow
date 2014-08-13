<?php
/**
 * Functions relating to booked tickets. For functions relating to event tickets see event-tickets.
 *
 * @package ticket-functions
 */

/**
 * Delete a booking ticket in booking by ID.
 *
 * This permantly removes this ticket from the booking.
 *
 * @since 1.0
 * @access private
 * @ignore
 * @param int     $booking_ticket_id The booking ticket ID
 * @return bool True on success. Flase on failure.
 */
function eventorganiser_delete_booking_ticket( $booking_ticket_id ) {
	global $wpdb;

	//Get the corresponding booknig ID
	$booking_id = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT booking_id FROM {$wpdb->eo_booking_tickets}
		WHERE {$wpdb->eo_booking_tickets}.booking_ticket_id=%d;",
		$booking_ticket_id
	));

	$sql = $wpdb->prepare(
		"DELETE FROM {$wpdb->eo_booking_tickets}
		WHERE {$wpdb->eo_booking_tickets}.booking_ticket_id=%d;",
		$booking_ticket_id 
	);
		
	do_action( 'eventorganiser_delete_booking_ticket', $booking_ticket_id, $booking_id );

	if ( $wpdb->query( $sql ) ) {
		eventorganiser_clear_cache( 'eo_booking', $booking_id );
		eventorganiser_clear_cache( 'eo_booking_tickets', $booking_id );
		do_action( 'eventorganiser_deleted_booking_ticket', $booking_ticket_id, $booking_id );
		return true;
	}

	return false;
}

/**
 * Adds a booking-ticket to a booking.
 *
 * Inserts the ticket into the the database.
 *
 * @since 1.0
 * @used-by eo_insert_booking()
 * @access private
 * @ignore
 * @param int     $booking_id      The booking to add the ticket too ID
 * @param int     $event_ticket_id The event ticket ID to add
 * @param int     $event_id        The event ID the ticket is for
 * @param int     $occurrence_id   The occurrence ID the ticket is for. If bookings are for entire series, this should be 0.
 * @return bool True on success. Flase on failure.
 */
function eventorganiser_insert_booking_ticket( $booking_id, $event_ticket_id, $event_id, $occurrence_id=0 ) {
	global $wpdb;
	//TODO refactor so we can normalise?
	//Get ticket price
	$price = eventorganiser_get_ticket_meta( $event_ticket_id, 'price' );
	$name = eventorganiser_get_ticket_meta( $event_ticket_id, 'name' );

	$booking_ticket = array(
		'booking_id' => $booking_id,
		'ticket_id' => $event_ticket_id,
		'ticket_name' => $name,
		'event_id' => $event_id,
		'occurrence_id' => $occurrence_id,
		'ticket_price' => $price,
		'ticket_reference' => substr( wp_hash( $booking_id.'-'.$event_id.'-'.$occurrence_id.'-'.$event_ticket_id.'-'.wp_rand( 1, 999 ).'-'.time() ), 0, 6 ),
	);

	return (bool) $wpdb->insert( $wpdb->eo_booking_tickets, $booking_ticket );
}

/**
 * Get all purchased tickets matching the (booking) query
 *
 * The $args array accepts anything accepted by {@see eventorganiser_get_bookings()}
 *
 * See {@see eo_get_booking_tickets()} for information on the the returned ticket objects.
 *
 * @since 1.0
 * @ignore
 * @uses eventorganiser_get_bookings()
 * @uses eo_get_booking_tickets()
 * @access private
 * @param array   $args The query
 * @return array Array of booking ticket objects
 */
function eventorganiser_get_tickets( $args = array() ) {

	$_args = $args;
	$_args['fields']='ids';
	$booking_ids = eventorganiser_get_bookings( $_args, false );
	$tickets = eo_get_booking_tickets( $booking_ids, false );
	return $tickets;
}

/**
 * Get all purchased tickets by ID
 *
 * The `$args` array allows you to group purchased tickets by the (event) ticket ID.
 *
 * Each ticket is of the form
 * <code>
 *      object{
 *            booking_ticket_id => [booking-ticket ID]
 *            booking_id => [booking ID]
 *            event_id => [Event (post) ID for which the ticket corresponds]
 *            occurrence_id => [Occurrence ID for which the ticket corresponds - 0 if booking for series]
 *            booking_id => [booking ID]
 *            ticket_id => [event-ticket (type) ID]
 *            ticket_name => [event-ticket name]
 *            ticket_reference => [booking-ticket reference number]
 *            ticket_price => [booking-ticket price]#
 *            
 *            ticket_quantity => [only when grouping tickets - #tickets of this type in booking]
 *      }
 * </code>
 * @since 1.0.1
 * @used-by eventorganiser_get_tickets()
 * @access private
 * @param int $booking_ids Array of booking IDs to retrieve the tickets of
 * @param boolean $group_tickets Whether to group tickets by ticket-type ID
 * @return array Array of booking ticket objects
 */
function eo_get_booking_tickets( $booking_ids=0, $group_tickets = true ) {

	global $wpdb;

	if ( empty( $booking_ids ) )
		return false;

	if ( $group_tickets )
		$select = "SELECT*, COUNT(*) as ticket_quantity FROM {$wpdb->eo_booking_tickets}";
	else
		$select = "SELECT * FROM {$wpdb->eo_booking_tickets}";

	$where = "WHERE 1=1 ";

	if ( is_array( $booking_ids ) ) {
		$booking_ids = array_map( 'intval', $booking_ids );
		$booking_ids = implode( ',', $booking_ids );
		$where .= " AND {$wpdb->eo_booking_tickets}.booking_id IN ({$booking_ids})";
	}else {
		$where .= $wpdb->prepare( " AND {$wpdb->eo_booking_tickets}.booking_id=%d", $booking_ids );
	}

	$groupby='';
	if ( $group_tickets )
		$groupby = "GROUP BY {$wpdb->eo_booking_tickets}.ticket_id";

	return $wpdb->get_results( "$select $where $groupby" );
}

/**
 * Kept for backwards compatibility. Remove 1.3+
 * @ignore
 * @access private
 */
function eventorganiser_get_booking_tickets( $booking_ids=0, $args = array() ) {
	_deprecated_function( __FUNCTION__, '1.0.1', 'eo_get_booking_tickets()' );

	$args = wp_parse_args( $args, array(
			'groupby'=>'ticket_id',
	) );
	
	$group_tickets = ( 'ticket_id' == $args['groupby'] );
		
	return eo_get_booking_tickets( $booking_ids, $group_tickets );
}

/**
 * Array indexed by (event) ticket ID, with the number of confirmed attendees with that
 * ticket for the event/occurrence.
 *
 * For booking series, set the occurrence ID to 0.
 *
 * @since 1.0
 * @access private
 * @ignore
 *
 * @param int     $event_id      The event (post) ID to get confirmed numbers for.
 * @param int     $occurrence_id The occurrence ID to get confirmed numbers for. 0 for booking series.
 * @return array|false Array of ticket ID => #confirmed.
 */
function eventorganiser_get_confirmed_numbers( $event_id, $occurrence_id ) {

	global $wpdb;

	/* Get confirmed tickets */
	$confirmed_bookings = eventorganiser_get_bookings( array(
			'fields'=>'ids',
			'status'=> eo_get_confirmed_booking_statuses(),
			'event_id'=> $event_id,
			'occurrence_id' => $occurrence_id
		) );

	if ( !$confirmed_bookings )
		return false;

	$booking_ids = array_map( 'intval', $confirmed_bookings );
	$booking_ids = implode( ',', $booking_ids );

	$query = "SELECT ticket_id, COUNT(*) as confirmed FROM {$wpdb->eo_booking_tickets}
				WHERE {$wpdb->eo_booking_tickets}.booking_id IN ({$booking_ids})
				GROUP BY {$wpdb->eo_booking_tickets}.ticket_id";

	return $wpdb->get_results( $query, OBJECT_K );
}

/**
 * Get the number of tickets available for an event.
 * 
 * If you are selling tickets for entire event series (i.e. not by date) then you should not specify an occurrence ID.
 * If you are selling tickets by date then you should specify the occurrence for which you want
 * the number of remaining tickets.
 * 
 * If there are tickets which are **not yet on sale**, these are not counted.
 * 
 * ### Example
 * 
 * The following example - for booking by series - displays the number of tickets remaining. 
 * <code>
 * //In your single-event.php template:
 * $remaining = eo_get_remaining_tickets_count( get_the_ID() );
 * if( $remaining > 1 ){
 *		printf( 'Hurry, only %d tickets remaining', $remaining );
 * }elseif( $remaining == 1 ){
 *      echo 'Only one ticket remaining!'; 
 * }else{
 *      echo 'Sorry, there are not tickets available';
 * }
 * </code>
 * 
 * The following example - for booking by dates - displays a list of dates which fewer than 5 tickets
 * <code>
 * $future_occurrences = eo_get_the_future_occurrences_of( get_the_ID() );
 * if( $future_occurrences ){
 *     echo '<ul>';
 *     foreach( $future_occurrences as $occurrence_id => $dates ){
 *           $remaining = eo_get_remaining_tickets_count( get_the_ID(), $occurrence_id );
 *           if( $remaining < 5 ){
 *                printf( '<li> %s (Tickets remaining: %d)</li>', eo_format_datetime( $dates['start'], 'jS F' ), $remaining );
 *           }  
 *     }
 *     echo '</ul>'; 
 * }
 * </code>
 * 
 * @param int $event_id Event ID
 * @param int $occurrence_id Occurrence ID. Use only when selling tickets by date
 * @return int The number of tickets *currently* available 
 */
function eo_get_remaining_tickets_count( $event_id, $occurrence_id = 0 ){
	
	$availability = eo_get_the_occurrences_tickets( $event_id, $occurrence_id );
	
	if( !$availability )
		return 0;
	
	$occurrence_availability = array_pop( $availability );
	
	if( !$occurrence_availability['available'] || empty( $occurrence_availability ) )
		return 0;
	
	$num_available_tickets = array_sum( $occurrence_availability['tickets'] );
	
	//Handle the booking cap
	if( $cap = (int) get_post_meta( $event_id, '_eventorganiser_booking_cap', true ) ){
	
		//This may include pending bookings if pending reserves spaces
		$total_reserved =  isset( $occurrence_availability['reserved'] ) ? (int) $occurrence_availability['reserved'] : 0;
	
		$num_available_tickets = min( $num_available_tickets, $cap - $total_reserved );
	}
	
	return max( $num_available_tickets, 0 );

}

/**
 * Retrieves the *remaining tickets* for an event.
 *
 * This function takes the available tickets and subtracts the number of confirmed or pending
 * tickets (depending on the 'reserve_pending_tickets' option).
 *
 * It also ignores occurrences that have occurred.
 *
 * The response is an array indexed by occurrence ID - the values are arrays with keys
 *
 * * **date:** (string) representaton of date in Y-m-d format (when 'book_series' is to false)
 * * **available:** (string) Acts as a status. 0 = no tickets available. 1 = tickets available.
 * * **tickets:** (string) An array of form (ticket_id => number available)
 *
 * If the 'book_series' option is set to true, it contains an array with one element (with key 0).
 *
 * For each occurrence & ticket, this function finds all relevant bookings and forms an array of remaining tickets.
 * In then goes through each occurrence and ticket populating any tickets that haven't be sold yet.
 *
 * @since 1.0
 * @ignore
 * @access private
 * @param int     $post_id        The event (post) ID. Uses current event by default
 * @param int|arrray $occurrence_ids If specified, restrict only to those occurrences.
 * @return array Array indexed by occurrence IDs with available tickets
 */
function eo_get_the_occurrences_tickets( $post_id='', $occurrence_ids=array() ) {
	global $wpdb;

	$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );
	$occurrence_ids_array = array();

	//Sanity check: Need event post ID
	if ( empty( $post_id ) )
		return false;

	//Sanity check: If there are no tickets associated with the event, abort
	if ( !$available_tickets = eo_get_event_tickets( $post_id ) )
		return false;

	$now = new DateTime( 'now', eo_get_blog_timezone() );

	foreach ( $available_tickets as $ticket_id => $ticket ) {
		if ( ( $ticket['from']  && $ticket['from'] > $now ) || ( $ticket['to'] && $ticket['to'] < $now ) )
			unset( $available_tickets[$ticket_id] );
	}

	if ( !$available_tickets )
		return false;
	
	//If only one non-zero ID is passed, cast it as an array
	if ( !is_array( $occurrence_ids ) && 0 != $occurrence_ids )
		$occurrence_ids = array( $occurrence_ids );

	//Get bookable occurrences
	$occurrences = eo_get_bookable_occurrences( $post_id );
	if( empty( $occurrences ) ){
		return false;
	}
	
	$occurrence_ids_array = array_keys( $occurrences ); 

	$key = 'eo_pro_occurrence_tickets_'.$post_id.serialize( $occurrence_ids );
	$remaining_tickets = eventorganiser_cache_get( $key , 'eo_occurrence_tickets' );

	if ( !$remaining_tickets ) {
		$remaining_tickets = array();

		if ( !eventorganiser_pro_get_option( 'book_series' ) ) {
			//Getting tickets for specific occurrence(s).
			if ( !empty( $occurrence_ids ) ) {
				$occurrence_ids_array = array_intersect( $occurrence_ids_array, $occurrence_ids );
			}

			//TODO Maybe cater for $occurrence_ids === 0 -> 'all occurrences'
		}else {
			//Booking entire series - add 0 for occurrence ID
			$occurrence_ids_array[] = 0;
		}

		//If there are no matching occurrences abort
		if ( empty( $occurrence_ids_array ) )
			return false;
			
		/* Get purchased tickets */
		$booking_ids = eventorganiser_get_bookings( array(
				'fields'=>'ids',
				'status'=> eo_get_reserved_booking_statuses(),
				'event_id'=> $post_id,
			) );

		if ( $booking_ids ) {
			$_booking_ids  = implode( ', ', array_map( 'intval', $booking_ids ) );
			$_occurrence_ids = implode( ', ', $occurrence_ids_array );
			$select = "SELECT occurrence_id, ticket_id, COUNT(*) AS qty FROM {$wpdb->eo_booking_tickets}";
			$where =  $wpdb->prepare( "WHERE event_id=%d AND booking_id IN($_booking_ids) AND occurrence_id IN({$_occurrence_ids})", $post_id );

			if ( eventorganiser_pro_get_option( 'book_series' ) ) {
				$groupby = "GROUP BY ticket_id";
			}else {
				$groupby= "GROUP BY ticket_id, occurrence_id";
			}

			$purchased_tickets = $wpdb->get_results( $select.' '.$where.' '.$groupby, OBJECT );
		}else {
			//No bookings means no purchased tickets
			$purchased_tickets =false;
		}

		if ( $purchased_tickets ) {
			/* For each purchased ticket - calculate & store the remaining tickets in $remaining_tickets */
			foreach ( $purchased_tickets as $purchased_ticket ) {

				$ticket_id = $purchased_ticket->ticket_id;

				//Set up occurrence ID
				$occurrence_id = ( eventorganiser_pro_get_option( 'book_series' ) ? 0 : $purchased_ticket->occurrence_id );

				//Set reserved tickets (could be confirmed or confirmed/pending depending on settings).
				if ( !isset( $remaining_tickets[$occurrence_id]['reserved'] ) )
					$remaining_tickets[$occurrence_id]['reserved'] = 0;
				
				$remaining_tickets[$occurrence_id]['reserved'] += intval( $purchased_ticket->qty );
				
				
				//No longer available
				if ( !isset( $available_tickets[$ticket_id] ) )		
					continue;
				
				//Not for right date
				if ( !eventorganiser_pro_get_option( 'book_series' ) && !in_array( $occurrence_id, $available_tickets[$ticket_id]['occurrence_ids'] ) )
					continue;
				

				/* Ticket is valid - calculate how many tickets are remaining */
				
				//Subtract #purchsed-tickets from #tickets-available
				$available = intval( $available_tickets[$ticket_id]['spaces'] ) - intval( $purchased_ticket->qty );
				$remaining_tickets[$occurrence_id]['tickets'][$ticket_id] = $available;
				
				//Initialise availabilty
				if ( !isset( $remaining_tickets[$occurrence_id]['available'] ) )
					$remaining_tickets[$occurrence_id]['available'] = 0;

				//Set availablity flag
				if ( $available > 0 )
					$remaining_tickets[$occurrence_id]['available']=1;
				
			}//Endforeach purchased ticket

		}

		/* For any ticket not yet purchased, add the remaining tickets to $remaining_tickets*/
		if ( eventorganiser_pro_get_option( 'book_series' ) ) {

			//Booking a series - so no need to worry about occurrences.
			foreach ( $available_tickets as $ticket_id => $ticket ) {
				if ( !isset( $remaining_tickets[0]['tickets'][$ticket_id] ) ) {
					$remaining_tickets[0]['tickets'][$ticket_id] = (int) $available_tickets[$ticket_id]['spaces'];
					$remaining_tickets[0]['available'] = 1;
				}
			}

		}else {
		
			foreach ( $occurrence_ids_array as $occurrence_id ) {
				foreach ( $available_tickets as $ticket_id => $ticket ) {
					//Is this ticket valid for this occurrence?
					if ( !in_array( $occurrence_id, $ticket['occurrence_ids'] ) )
						continue;

					$remaining_tickets[$occurrence_id]['id'] = $occurrence_id;
					$remaining_tickets[$occurrence_id]['date'] = eo_format_datetime( $occurrences[$occurrence_id]['start'], 'Y-m-d' );

					//Ticket-occurrence has no booking
					if ( !isset( $remaining_tickets[$occurrence_id]['tickets'][$ticket_id ] ) ) {
						$remaining_tickets[$occurrence_id]['tickets'][$ticket_id ] = (int) $available_tickets[$ticket_id]['spaces'];
						$remaining_tickets[$occurrence_id]['available'] = 1;
					}
				}
			}
		}
		if ( $remaining_tickets )
			$remaining_tickets = array_values( $remaining_tickets );
		else
			$remaining_tickets = array();
		
		//Now for each check that the #tickets sold (or reserved) doesn't exceed the cap
		$cap = (int) get_post_meta( $post_id, '_eventorganiser_booking_cap', true );
		if ( $cap && $remaining_tickets ) {
			foreach ( $remaining_tickets as $occurrence_id => $info ) {
				
				if( !isset( $remaining_tickets[$occurrence_id]['reserved'] ) )
					$remaining_tickets[$occurrence_id]['reserved'] = 0;
				
				if ( $remaining_tickets[$occurrence_id]['reserved'] >= $cap )
					$remaining_tickets[$occurrence_id]['available'] = 0;
			}
		}
		
		eventorganiser_cache_set( $key, $remaining_tickets, 'eo_occurrence_tickets' );
	}
	return $remaining_tickets;
}

/**
* Returns an array of occurrence IDs of the event which are 'bookable' (even if they are fully booked)
* or have no tickets.
*
* Default behaviour is to return only IDs of future dates. If booking 'by series', occurrence IDs
* still need to be passed. If an empty array or false is returned, the event will not be available
* fo booking.
*
* @ignore
* @since 1.8.0
* @param int $event_id ID of the event for which we want 'bookable' occurence IDs
*/
function eo_get_bookable_occurrences( $event_id ){	
	$future = eo_get_the_future_occurrences_of( $event_id );	
    return apply_filters( 'eventorganiser_bookable_occurrences', $future, $event_id );
 }
?>