<?php
/**
 * Functions relating to event tickets. For functions relating to booked tickets see booking-tickets.
 *
 * @package ticket-functions
 */

/**
 * Retrieve a list of tickets for this event
 *
 * Can be used inside the loop by not specifying the event (post) ID. The spaces indicates the number
 * of spaces the ticket was *created* with - not how many remaining. For remaining tickets see
 * {@see eo_get_the_occurrences_tickets()}
 *
 * Returns an array of tickets of the form
 * <code>
 *     ticket ID => array(
 *               name => [Ticket Name]
 *               spaces => [How Many Spaces this ticket was *created* with]
 *               price => [The ticket price]
 *               occurrence_ids => [Array of occurrence IDs corresponding to the dates this ticket is available]
 *               mid => [Ticket ID (meta table ID)]
 *               to => [DateTime when ticket is valid till / false if indefinite]
 *               from => [DateTime when ticket is valid from / false if indefinite]
 *      )
 * </code>
 * 
 * @see has_meta()
 * @since 1.0
 *
 * @param int $post_id The Event (post) ID
 * @param array|int Occurrence ID or Array of occurrence IDs
 * @return array An array of tickets
 */
function eo_get_event_tickets( $post_id=0, $occurrence_ids = 0 ) {
	global $wpdb;
	$meta_key =  '_eo_tickets';
	
	// Because of the way post meta is handled we need to query the database directly
	// to ge the meta_id from the table. This is a bit like how WordPress handles custom fields
	// using has_meta()
	
	if( !is_array( $occurrence_ids ) )
		$occurrence_ids = (int) $occurrence_ids;

	$post_id = ( !empty( $post_id ) ? (int) $post_id : get_the_ID() );

	if ( empty( $post_id ) )
		return;

	$_tickets = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value, meta_id, post_id
		FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s
		ORDER BY meta_id", $post_id, $meta_key ), ARRAY_A );

	$tickets=array();
	if ( $_tickets ):
		foreach ( $_tickets as $_ticket ):
			$id = $_ticket['meta_id'];
			$ticket_data = (array) maybe_unserialize( $_ticket['meta_value'] );
			$ticket_data['mid'] = $id;

			if ( !empty( $ticket_data['from'] ) ) {
				$ticket_data['from'] = new DateTime( $ticket_data['from'], eo_get_blog_timezone() );
			}else {
				$ticket_data['from'] = false;
			}

			if ( !empty( $ticket_data['to'] ) ) {
				$ticket_data['to'] = new DateTime( $ticket_data['to'], eo_get_blog_timezone() );
			}else {
				$ticket_data['to'] = false;
			}

			$tickets[$id] = $ticket_data;
		endforeach;
	endif;
	
	
	if ( $tickets && $occurrence_ids !== 0 ) {
		if ( !is_array( $occurrence_ids ) )
			$occurrence_ids = array( $occurrence_ids );
			foreach ( $tickets as $ticket_id => $ticket ) {
				if ( !array_intersect( $occurrence_ids, $ticket['occurrence_ids'] ) ) {
					unset( $tickets[$ticket_id] );
			}
		}
	}
	
	//Important: Use uasort (not usort) to retain ticket ID index.
	uasort( $tickets, '_eo_sort_tickets_by_order' );
	
	return apply_filters( 'eventorganiser_get_event_tickets', $tickets, $post_id, $occurrence_ids );
}

/**
 * Retrieve a list of tickets for this event which are currently on sale.
 *
 * Arguments are identical to {@see eo_get_event_tickets()}
 *
 * Returns an array of tickets of the form
 * <code>
 *     ticket ID => array(
 *               name => [Ticket Name]
 *               spaces => [How Many Spaces this ticket was *created* with]
 *               price => [The ticket price]
 *               occurrence_ids => [Array of occurrence IDs corresponding to the dates this ticket is available]
 *               mid => [Ticket ID (meta table ID)]
 *               to => [DateTime when ticket is valid till / false if indefinite]
 *               from => [DateTime when ticket is valid from / false if indefinite]
 *      )
 * </code>
 * @uses eo_get_event_tickets()
 * @since 1.4
 * @param int $post_id The Event (post) ID
 * @param array|int Occurrence ID or Array of occurrence IDs
 * @return array An array of tickets
 */
function eo_get_event_tickets_on_sale( $post_id=0, $occurrence_ids = 0 ){
	$tickets = eo_get_event_tickets( $post_id, $occurrence_ids ); 
	$now = new DateTime( 'now', eo_get_blog_timezone() );
	if( $tickets ){
		foreach ( $tickets as $ticket_id => $ticket ) {
			if ( ( $ticket['from']  && $ticket['from'] > $now ) || ( $ticket['to'] && $ticket['to'] < $now ) ){
				unset( $tickets[$ticket_id] );
			}
			
			if( empty( $ticket['occurrence_ids'] ) && !eventorganiser_pro_get_option( 'book_series' ) ){
				unset( $tickets[$ticket_id] );
			}
		}
	}
	
	return $tickets;
}
/**
 * Retrieve a specific event ticket by ID
 *
 * Returns a ticket of the form
 * <code>
 *     ticket ID => array(
 *               name => [Ticket Name]
 *               spaces => [How Many Spaces this ticket was *created* with]
 *               price => [The ticket price]
 *               occurrence_ids => [Array of occurrence IDs corresponding to the dates this ticket is available]
 *      )
 * </code>
 * @uses get_metadata_by_mid()
 * @since 1.0
 *
 * @param int     $ticket_id The ticket ID
 * @return array An array of tickets
 */
function eo_get_ticket( $ticket_id ) {
	$ticket = get_metadata_by_mid( 'post', $ticket_id );
	
	if( !$ticket ){
		return false;
	}
	
	$ticket_data = $ticket->meta_value;

	//Convert from/to to boolean or DateTime object
	if ( !empty( $ticket_data['from'] ) ) {
		$ticket_data['from'] = new DateTime( $ticket_data['from'], eo_get_blog_timezone() );
	}else {
		$ticket_data['from'] = false;
	}

	if ( !empty( $ticket_data['to'] ) ) {
		$ticket_data['to'] = new DateTime( $ticket_data['to'], eo_get_blog_timezone() );
	}else {
		$ticket_data['to'] = false;
	}

	return $ticket_data;
}

/**
 * @ignore
 * @param unknown_type $ticket_id
 * @deprecated 1.4 eo_get_ticket()
 */
function eventorganiser_get_ticket( $ticket_id ){
	return eo_get_ticket( $ticket_id );
}


/**
 * Retrieve a information about an event ticket by event ticket ID (event meta ID)
 *
 * The key can be one of the following
 *
 * **price** - (float) The event ticket price
 * **name** - (string) The name of the event ticket
 * **occurrence_ids** - (array) An array of occurrence IDs corresponding to the occurrences the ticket can be purchased for.
 *
 * @uses eventorganiser_get_ticket()
 * @ignore
 * @access private
 * @since 1.0
 *
 * @param int     $ticket_id The ticket ID
 * @param string  $key       The information to retrieve
 * @return array An array of tickets
 */
function eventorganiser_get_ticket_meta( $ticket_id, $key ) {

	$ticket = eo_get_ticket( $ticket_id );
	
	if( !$ticket ){
		return false;
	}

	switch ( strtolower( $key ) ) {
		case 'price':
		case 'name':
		case 'from':
		case 'to':
		case 'spaces':
		case 'order':
			$value = $ticket[$key];
		break;
		case 'occurrence_ids':
			//MAYBELATER cache this - break when event saves
			$value = $ticket['occurrence_ids'];
		break;
		default:
			$value = false;
	}
	
	return apply_filters('eventorganiser_get_event_ticket_meta', $value, $ticket_id, $key );
}

/**
 * Create a ticket for an event
 *
 * The ticket array should contain the following
 *
 * **name** - (string) The name of the event ticket
 * **price** - (float) The event ticket price
 * **occurrence_ids** - (array) An array of occurrence IDs corresponding to the occurrences the ticket can be purchased for.
 * **spaces** - (int) The maximum number of this ticket available (if selling tickets on an occurrence basis, this is the maximum number of this ticket per occurrence).
 *
 * @ignore
 * @uses add_post_meta()
 * @since 1.0
 *
 * @param int     $event_id The event ID
 * @param array   $ticket   Elements that make up ticket to insert.
 * @return bool False for failure. True for success.
 */
function eventorganiser_insert_ticket( $event_id, $_ticket=array() ) {
	global $wpdb;

	$allowed_fields = array( 'name', 'spaces', 'price', 'occurrence_ids', 'from', 'to', 'order' );
	$ticket = array_intersect_key( $_ticket, array_flip( $allowed_fields ) );

	$ticket['spaces'] = absint( $ticket['spaces'] );
	$ticket['occurrence_ids'] = is_array( $ticket['occurrence_ids'] ) ? array_map( 'intval', $ticket['occurrence_ids'] ) : array( intval( $ticket['occurrence_ids'] ) );
	$ticket['price'] = floatval( $ticket['price'] );
	$ticket['order'] = isset( $ticket['order'] ) ? absint( $ticket['order'] ) : 0;

	if ( !empty( $ticket['from'] ) )
		$ticket['from'] = $ticket['from']->format( 'Y-m-d H:i' );

	if ( !empty( $ticket['to'] ) )
		$ticket['to'] = $ticket['to']->format( 'Y-m-d H:i' );


	eventorganiser_clear_cache( 'eo_occurrence_tickets' );
	
	$ticket = apply_filters('eventorganiser_insert_event_ticket', $ticket, $event_id, $_ticket );

	return add_post_meta( $event_id, '_eo_tickets', $ticket );
}


/**
 * Update an event ticket for an event with new attributes
 *
 * The ticket array can contain the following
 *
 * **name** - (string) The name of the event ticket
 * **price** - (float) The event ticket price
 * **occurrence_ids** - (array) An array of occurrence IDs corresponding to the occurrences the ticket can be purchased for.
 * **spaces** - (int) The maximum number of this ticket available (if selling tickets on an occurrence basis, this is the maximum number of this ticket per occurrence).
 * **from** - (bool|DateTime) DateTime object from when the ticket is available for purcahse. False for indefinite.
 * **to** - (bool|DateTime) DateTime object after which the ticket is *unavailable* for purchase. False for indefinite.
 *
 * @ignore
 * @uses update_metadata_by_mid()
 * @since 1.0
 *
 * @param int     $ticket_id The ticket ID (metadata ID).
 * @param array   $ticket    Elements that make up ticket to update.
 * @return bool False for failure. True for success.
 */
function eventorganiser_update_ticket( $ticket_id, $_ticket=array() ) {
	global $wpdb;

	
	$allowed_fields = array( 'name', 'spaces', 'price', 'occurrence_ids', 'from', 'to', 'order' );
	$ticket = array_intersect_key( $_ticket, array_flip( $allowed_fields ) );
	
	$old_ticket = eventorganiser_get_ticket( $ticket_id );
	$ticket = array_merge( $old_ticket, $ticket );

	$ticket['spaces'] = absint( $ticket['spaces'] );
	$ticket['price'] = floatval( $ticket['price'] );
	$ticket['occurrence_ids'] = is_array( $ticket['occurrence_ids'] ) ? array_map( 'intval', $ticket['occurrence_ids'] ) : array( intval( $ticket['occurrence_ids'] ) );
	$ticket['order'] = isset( $ticket['order'] ) ? absint( $ticket['order'] ) : 0;

	if ( !empty( $ticket['from'] ) )
		$ticket['from'] = $ticket['from']->format( 'Y-m-d H:i' );

	if ( !empty( $ticket['to'] ) )
		$ticket['to'] = $ticket['to']->format( 'Y-m-d H:i' );

	eventorganiser_clear_cache( 'eo_occurrence_tickets' );
	
	$ticket = apply_filters('eventorganiser_update_event_ticket', $ticket, $ticket_id, $_ticket );

	return update_metadata_by_mid( 'post', $ticket_id, $ticket );
}


/**
 * Delete an event ticket by ticket ID (Event meta ID)
 *
 * @uses delete_metadata_by_mid()
 * @since 1.0
 *
 * @ignore
 * @param int     $ticket_id The ticket ID (metadata ID).
 * @return bool False for failure. True for success.
 */
function eventorganiser_delete_ticket( $ticket_id ) {
	eventorganiser_clear_cache( 'eo_occurrence_tickets' );
	return delete_metadata_by_mid( 'post', $ticket_id );
}

/**
 * Gets the total capacity for an event.
 * 
 * This gets the total number of attendees that an event can hold. It sums the amount
 * of each ticket type. If a cap is set for the event, it takes the minimum of that number
 * with the cap.
 * 
 * If you are **not booking individual dates** set the occurrence ID to 0. If are booking 
 * individual dates, specify an occurrence ID to get capacity for that occurrence, otherwise
 * pass 0 to get the capacity for the entire event series.
 * 
 * @param int $event_id The event ID for which to get the capacityu
 * @param int $occurrence_id Occurence ID. Do not set if booking events as series.
 * @return int The total number of attendees an event can hold.
 */
function eo_get_event_capacity( $event_id, $occurrence_id = 0 ){
	
	//TODO Fix bug where the event cap is applied to the total of all tickets rather than 
	//per date (as should be the case when selling tickets by date).
	//Note this is correct behaviour if selling by series.
	$capacity = 0;
	$available_tickets = eo_get_event_tickets( $event_id );
	$cap = (int) get_post_meta( $event_id, '_eventorganiser_booking_cap', true );
	
	if ( $available_tickets ) {
		foreach ( $available_tickets as $ticket_id => $ticket ) {
			if ( 0 != $occurrence_id && in_array( $occurrence_id, $ticket['occurrence_ids'] ) ){
				$capacity += (int) $available_tickets[$ticket_id]['spaces'];
			}elseif ( 0 == $occurrence_id ){
				$num_occurrences = count( array_filter(  $ticket['occurrence_ids'] ) );
				$capacity += intval( $available_tickets[$ticket_id]['spaces'] ) * $num_occurrences;
			}
		}
	}
	if( $cap )
		$capacity = min( $capacity, $cap );
	
	return $capacity;
}

/**
 * @deprecated
 * @ignore
 * @param unknown_type $event_id
 * @param unknown_type $occurrence_id
 * @return Ambigous <Ambigous, number, mixed>
 */
function eventorganiser_get_capacity( $event_id, $occurrence_id = 0 ){
	return eo_get_event_capacity( $event_id, $occurrence_id  );
}



/**
 * DO NOT USE THIS FUNCTION, IT WILL BE REPURPOSED.
 * Use eo_get_remaining_tickets_count()
 * 
 * Gets the number of remaining tickets forn event.
 *
 *
 * If you are **not booking individual dates** set the occurrence ID to 0. If are booking
 * individual dates, specify an occurrence ID to get the number of remaining tickets for that occurrence, otherwise
 * pass 0 to get the number of remaining tickets for the entire event series.
 * 
 * @ignore
 * @access private
 * @param int $event_id The event ID for which to get the capacityu
 * @param int $occurrence_id Occurence ID. Do not set if booking events as series.
 * @return int The number of remaining tickets for this event.
 */
function eo_get_remaining_tickets( $event_id, $occurrence_id = 0 ){
	return eo_get_remaining_tickets_count( $event_id, $occurrence_id );
}

/**
 * @ignore
 * @since 1.5
 */
function _eo_sort_tickets_by_order( $a, $b ){
	$order_a = !empty( $a['order'] ) ? absint( $a['order'] ) : 0;
	$order_b = !empty( $b['order'] ) ? absint( $b['order'] ) : 0;
	return $order_a - $order_b;
}