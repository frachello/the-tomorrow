<?php
/**
 * These functions relate users and bookings.
 *
 * @package user-booking-functions
 */

/**
 * Whether a user has previously made a booking. Optionally filter by event and occurrence and status.
 *
 * By default includes pending bookings. By specifying an event ID this checks if the user has made any bookings
 * for this event (any occurrence / 'series' booking). By additionally specifying an occurrence ID you can check if
 * a booking was made for that particular occurrence.
 *
 * @since 1.0
 *
 * @param int     $user_id       The user's ID
 * @param int     $event_id      If checking bookings for a particular event, this is the event's ID
 * @param int     $occurrence_id If checking bookings for a particular occurrence of an event, this is the occurrence's ID
 * @param string  $status 		 Which status to include. Default 'any'
 * @return bool Whether the user has made a booking
 */
function eo_user_has_bookings( $user_id=0, $event_id=0, $occurrence_id=0, $status = 'any' ) {

	if ( empty( $user_id ) )
		return false;

	if ( eo_get_bookings( array( 'bookee_id' => $user_id, 'event_id' => $event_id, 'occurrence_id' => $occurrence_id, 'status'=> $status, 'fields'=>'ids' ) ) )
		return true;

	return false;
}


/**
 * Get events all future events a user is attending 
 * 
 * ### Example
 * Get events the current user is attending:
 * <code>
 *   $events = eo_get_events_user_is_attending( get_current_user_id() );
 * </code>
 * 
 * Or you can use {@see eo_get_events()} directly. The above is identical to:
 * <code>
 * $events = eo_get_events(array(
 *				'posts_per_page'=>-1,
 *				'bookee_id' => get_current_user_id(),
 *				'post_type'=>'event',
 *				'supress_filters'=>false,
 *				'orderby'=> 'eventstart',
 *				'order'=> 'ASC',
 *				'showrepeats'=>1,
 *				'group_events_by'=>'',
 *				'event_start_after' => 'now'
 8		));
 * </code>
 * 
 * @uses eo_get_events()
 * @since 1.2
 * @param int $user_id The user ID
 * @return array Array of events the user is attending
 */
function eo_get_events_user_is_attending( $user_id ){
	
		return eo_get_events(array(
				'posts_per_page'=>-1,
				'bookee_id' => $user_id,
				'post_type'=>'event',
				'supress_filters'=>false,
				'orderby'=> 'eventstart',
				'order'=> 'ASC',
				'showrepeats'=>1,
				'group_events_by'=>'',
				'event_start_after' => 'now'
		));
}


/**
 * Counts the future events a user is attending
 *
 * @uses eo_get_events_user_is_attending()
 * @since 1.2
 * @param int $user_id The user ID
 * @return int The number of events (counts occurrences)
 */
function eo_number_events_user_is_attending( $user_id ){
	return count( eo_get_events_user_is_attending() );
}


/**
 * Gets bookings a user has made.
 * 
 * Bookings include confirmed and pending bookings.
 * 
 * ### Example
 * Display a user's previous bookings 
 *  <code>
 *      if( get_current_user_id() ){ 
 *        $bookings = eo_get_user_booking_history( get_current_user_id() );
 *        if( $bookings ){
 *       	echo '<ul>';
 *      	foreach( $bookings as $booking ){
 *      		printf( 
 *      			'<li> You booked %1$d tickets for %2$s on %3$s </li>',
 *      			eo_get_booking_meta( $booking->ID, 'ticket_quantity' ),
 *      			get_the_title( eo_get_booking_meta( $booking->ID, 'event_id' ) ),
 *      			eo_get_booking_date( $booking->ID, 'jS F Y' ),
 *      		);
 *      	}
 *      	echo '</ul>';
 *         }
 *      }
 * </code>
 * @uses eo_get_bookings()
 * @since 1.2
 * @param unknown_type $user_id
 * @return array Array of bookings the user has made
 */
function eo_get_user_booking_history( $user_id ){
	return eo_get_bookings( array(
			'bookee_id' => $user_id
	));
}


/**
 * Alters SQL WHERE query segment to include only events the user is attending
 * 
 * Hooked onto posts_where.
 * 
 * @since 1.2
 * @access private
 * @ignore
 * @param string $where
 * @param WP_Query $query
 * @return string
 */
function _eventorganiser_events_where_user_is_attending( $where, $query ){
	global $wpdb;
	
	if( defined( 'EVENT_ORGANISER_DIR' ) && eventorganiser_is_event_query( $query ) && '' !== $query->get('bookee_id') ){
		
		$bookee_id = $query->get('bookee_id'); //0 means logged out.
		
		if( !empty( $bookee_id ) ){
			$bookings = eo_get_bookings( array(
				'status' => eo_get_confirmed_booking_statuses(),
				'bookee_id' => $bookee_id,
			));
		}else{
			$bookings = false;
		}
		
		if( $bookings ){
			$event_ids = array();
			$occurrence_ids = array();
			foreach( $bookings as $booking ){
				if( eo_get_booking_meta( $booking->ID, 'occurrence_id' ) ){
					$occurrence_ids[] = (int) eo_get_booking_meta( $booking->ID, 'occurrence_id' );
				}else{
					$event_ids[] = (int) eo_get_booking_meta( $booking->ID, 'event_id' );
				}
			}

			$clauses = array();
			if( $occurrence_ids ){
				$clauses[] = "{$wpdb->eo_events}.event_id IN(" .implode( ',', $occurrence_ids ) .")";
			}
			if( $event_ids ){
				$clauses[] = "{$wpdb->posts}.ID IN(" .implode( ',', $event_ids ) .")";
			}

			if( $clauses ){
				$where .= " AND ( " . implode( " OR ", $clauses ) . " )";
			}

		}else{
			//Logged out user bookee query - return nothing. 
			$where .= " AND 1 = 0 ";
		}
	}

	return $where;
}
add_filter( 'posts_where', '_eventorganiser_events_where_user_is_attending', 300, 2 );


/**
 * Purges the front-end calendar when a booking is made.
 * TODO Improve this so there isn't needless purging.
 * TODO clear on status update 
 * @ignore
 * @since 1.2
 * @param unknown_type $booking_id
 */
function eventorganiser_purge_public_calendar_cache( $booking_id ){
	delete_transient( 'eo_full_calendar_public' );	
}
add_action( 'eventorganiser_booking_confirmed', 'eventorganiser_purge_public_calendar_cache' );
