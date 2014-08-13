<?php
/**
 * This page relates to booking functions (bookings are posts of type eo_booking)
 *
 * @package booking-functions
 */


/**
 * Inserts a booking for an event.
 *
 * The $booking array expects:
 *
 * * **booking_user** (int) ID of bookee
 * * **event_id** (int) Event (post ID)
 * * **occurrence_id** (int) Occurrence id - 0 if booking is for entire series
 * * **booking_date** (string) Date in 'Y-m-d H:i:s' format. Leave blank for current time.
 * * **booking_notes** (string) Any booking notes. Optional.
 * * **booking_status** (string). 'confirmed' or 'pending'. Default 'pending'
 * * **tickets** (array) Array of form ticket ID => qty
 * * **transaction_id** (string) A transaction number (e.g. gateway's ID for this booking). Optional.
 * 
 * ### Example
 * 
 * <code>
 *     $booking = array(
 *			'booking_user' => 1,
 *			'booking_status' => 'pending',
 *			'event_id' => 127,
 *			'occurrence_id' => 234,
 *			'tickets'=> array(
 *				13 => 1, //1 tickets of ID 13
 *				14 => 1, //1 tickets of ID 14
 *				16 => 3, //3 tickets of ID 16
 *          ),
 *    );
 *    $booking_id = eo_insert_booking( $booking );
 * </code>
 * 
 * @uses wp_insert_post()
 * @uses eventorganiser_insert_booking_ticket()
 * @since 1.0
 *
 * @param array   $booking Array containing booking data
 * @return int Booking ID or 0 on failure
 */
function eo_insert_booking( $booking ) {
	global $wpdb;

	$now = new DateTime();
	$booking = shortcode_atts( array(
			'booking_notes' => '', 'transaction_id' => 0, 'booking_user' => 0, 'event_id' => 0, 'occurrence_id' => 0,
			'booking_date' => false, 'booking_status' => 'pending', 'tickets' => false, 'ticket_details' => false, 'gateway' => false,
			'fname' => false, 'lname' => false, 'email' => false, 'form' => false,
		), $booking );

	//Validation
	$absint = array( /*'booking_user',*/ 'event_id', 'occurrence_id' );
	foreach ( $absint as $key ) {
		$booking[$key] = absint( $booking[$key] );
	}
	
	$booking_id = wp_insert_post( array(
		'post_type'=>'eo_booking',
		'post_content' => $booking['booking_notes'],
		'post_status' => $booking['booking_status'],
		'post_date' => $booking['booking_date'],
		'post_author' => $booking['booking_user']
	) );

	
	//Booking meta
	$meta_fields = array( 'transaction_id', 'event_id', 'occurrence_id', 'gateway' );
	if ( $booking_id ) {
		foreach ( $meta_fields as $key ) {
			if ( !isset( $booking[$key] ) )
				continue;
			update_post_meta( $booking_id, '_eo_booking_'.$key, $booking[$key] );
		}

		//Add each ticket into database
		foreach ( $booking['tickets'] as $ticket_id => $qty ) {
			while ( $qty > 0 ) {
	
				eventorganiser_insert_booking_ticket( $booking_id, $ticket_id, $booking['event_id'], $booking['occurrence_id'] );
				$qty--;
			}
		}
	}
	
	//Bookee details (if guest or 'anon' booking)
	if( $booking['booking_user'] == -1 ){
		//Add user name & email to booking meta
		update_post_meta( $booking_id, '_eo_booking_anon_first_name', $booking['fname'] );
		update_post_meta( $booking_id, '_eo_booking_anon_last_name', $booking['lname'] );
		update_post_meta( $booking_id, '_eo_booking_anon_display_name', trim( $booking['fname'].' '.$booking['lname'] ) );
		update_post_meta( $booking_id, '_eo_booking_anon_email', $booking['email'] );
	}
	
	$form = $booking['form'];
	
	if( $form ){
		//Store the booking form used
		update_post_meta( $booking_id, '_eo_booking_form', $form->id );
		
		$form->save( $booking_id );
	}

	do_action( 'eventorganiser_new_booking', $booking_id, $booking );
	return $booking_id;
}

/**
 * Updates an existing booking for an event.
 *
 * *Currently only updates booking user, status*
 *
 * @ignore
 * @access private
 * @uses wp_update_post()
 * @since 1.1
 *
 * @param int $booking_id The ID of the booking to be updated
 * @param array $booking Array containing booking data
 * @return int Booking ID or 0 on failure
 */
function eo_update_booking( $booking_id, $booking ){
	
	$booking['ID'] = $booking_id;
	$booking['edit_date'] =1;//Prevents updating of booking date
	
	if( empty( $booking['post_author'] ) ){
		
		$user_id = eo_get_booking_meta( $booking['ID'], 'bookee' );
	
		if( empty( $user_id ) )
			$user_id = -1;

		$booking['post_author'] = $user_id;
		
	}
	
	return wp_update_post( $booking );
}


/**
 * Gets the date on which the booking was made and format according to `$format`,
 *
 * If `$format` is not provided, `get_option('date_format')` is used instead
 *
 * @uses eo_format_date()
 * @since 1.1
 * @link http://php.net/manual/en/function.date.php PHP Date
 * @param int $booking_id The ID of the booking
 * @param string $format How to format the date, see http://php.net/manual/en/function.date.php or DATETIMEOBJ constant to return the datetime object.
 * @return string The formatted date on which the booking was made
 */
function eo_get_booking_date( $booking_id = 0, $format = '' ){
	
	$post = get_post( $booking_id );

	if ( '' == $format ){
		$format = get_option('date_format');
	}
	
	if( $post->post_date_gmt != '0000-00-00 00:00:00' ){
		$datetime = new DateTime( $post->post_date_gmt, new DateTimeZone( 'UTC' ) );
		$datetime->setTimezone( eo_get_blog_timezone() );	
	}else{
		$datetime = new DateTime( $post->post_date, eo_get_blog_timezone() );
	}
	
	
	return eo_format_datetime( $datetime, $format );
}


/**
 * Gets the start date of the event being booked. If tickets are sold 'by series'
 * then it get's the start date of the first occurrence of that. 
 *
 * If `$format` is not provided, `get_option('date_format')` is used instead
 *
 * @uses eo_format_date()
 * @since 1.1
 * @link http://php.net/manual/en/function.date.php PHP Date
 * @param int $booking_id The ID of the booking
 * @param string $format How to format the date, see http://php.net/manual/en/function.date.php or DATETIMEOBJ constant to return the datetime object.
 * @return string The formatted date of the event the booking is for
 */
function eo_get_booking_event_start_date( $booking_id = 0, $format = '' ){

	$event_id = (int) eo_get_booking_meta( $booking_id, 'event_id' );
	$occurrence_id = (int) eo_get_booking_meta( $booking_id, 'occurrence_id' );
	
	if ( '' == $format ){
		$format = get_option('date_format');
	}
	
	if( $occurrence_id != 0 ){
		return eo_get_the_start( $format, $event_id, null, $occurrence_id );
	}else{
		return eo_get_schedule_start( $format, $event_id );
	}
}

/**
 * Retrieves 'core' booking meta. You should use `get_post_meta()` for 'non-core' booking meta/custom fields.
 * 
 * Available keys are:
 * 
 * * **bookee** - The bookee ID. 0 if its a guest booking.
 * * **bookee_first_name** - The bookee's first name
 * * **bookee_last_name** - The bookee's last name (if provided).
 * * **bookee_display_name** - The bookee's display name (as selected in profile).
 * * **bookee_email** - The bookee's email address
 * * **booking_notes** - The booking notes
 * * **event_id** - The ID of the event the booking is for
 * * **occurrence_id** The ID of the occurrence the booking is for 
 * * **date** - The date the booking was made
 * * **ticket_quantity** - The number of tickets contained in the booking 
 * * **booking_amount** - The total amount the booking came to (as a float)
 * * **meta_{$ID}** - The value entered for booking form element with ID $ID.
 *
 * Applies the filter `eventorganiser_get_booking_meta_{$key}` to the returned value.
 *
 * @uses `get_post_meta()`
 * @since 1.0
 *
 * @param id      $booking_id The booking (post) ID
 * @param string  $key        The booking meta key
 * @param bool    $single     Whether to return a single value
 * @return mixed Will be an array if `$single` is false. Will be value of meta data field if `$single` is true.
 */
function eo_get_booking_meta( $booking_id, $key, $single = true ) {

	switch ( $key ) {
		case 'bookee':
			$booking = get_post( $booking_id );
			$value = (int) $booking->post_author;
		break;
		case 'bookee_first_name':
			$user_id = (int) eo_get_booking_meta( $booking_id, 'bookee' );
			if( $user_id == 0 ){
				$value = get_post_meta( $booking_id, '_eo_booking_anon_first_name', true );
			}elseif( $user_data = get_userdata( $user_id ) ){
				$value = $user_data->user_firstname;
			}else{
				$value = false;
			}
		break;
		case 'bookee_last_name':
			$user_id = (int) eo_get_booking_meta( $booking_id, 'bookee' );
			if( $user_id == 0 ){
				$value = get_post_meta( $booking_id, '_eo_booking_anon_last_name', true );		
			}elseif( $user_data = get_userdata( $user_id ) ){
				$value = $user_data->user_lastname;
			}else{
				$value = false;
			}
		break;
		case 'bookee_display_name':
			$user_id = (int) eo_get_booking_meta( $booking_id, 'bookee' );
			if( $user_id == 0 ){
				$value = get_post_meta( $booking_id, '_eo_booking_anon_display_name', true );
			}elseif( $user_data = get_userdata( $user_id ) ){
				$value = $user_data->display_name;
			}else{
				$value = false;
			}
		break;
		case 'email':
		case 'bookee_email':
			$user_id = (int) eo_get_booking_meta( $booking_id, 'bookee' );
			if( $user_id == 0 ){
				$value = get_post_meta( $booking_id, '_eo_booking_anon_email', true );
				
			}elseif( $user_data = get_userdata( $user_id ) ){
				$value = $user_data->__get( 'user_email' );
				
			}else{
				$value = false;
			}
		break;

		case 'notes':
		case 'booking_notes':
			$booking = get_post( $booking_id );
			$value = $booking->post_content;
		break;

		case 'status':
			$value = eo_get_booking_status( $item );
		break;

		case 'date':
			$value = get_the_time( __( 'Y/m/d g:i:s A' ), $booking_id );
		break;
		case 'occurrence_id':
		case 'event_id':
			$value = (int) get_post_meta( $booking_id, '_eo_booking_'.$key, true );
		break;
		case 'ticket_quantity':
		case 'booking_amount':
			global $wpdb;

			$cached_object = eventorganiser_cache_get( $booking_id, 'eo_booking' );

			if ( !$cached_object || !isset( $cached_object['quantity'] )  || !isset( $cached_object['quantity'] ) ) {
				//Booking data has not be cached - get from db.
				$sql = $wpdb->prepare(
					"SELECT COUNT(*) as quantity, SUM(ticket_price) as amount
						FROM {$wpdb->eo_booking_tickets}
						WHERE {$wpdb->eo_booking_tickets}.booking_id=%d;",
					$booking_id );

				if ( $data = $wpdb->get_row( $sql ) ) {
					$cached_object = array(
						'quantity' => (int) $data->quantity,
						'amount' => (float) $data->amount,
					);
				}else {
					$cached_object = array(
						'quantity' => 0,
						'amount' => 0,
					);
				}
				eventorganiser_cache_set( $booking_id, $cached_object, 'eo_booking' );
				if( !get_post_meta( $booking_id, '_eo_booking_booking_amount' ) )
					update_post_meta( $booking_id, '_eo_booking_booking_amount', $cached_object['amount'] );
			}

			if ( $key == 'ticket_quantity' )
				$value = $cached_object['quantity'];
			else
				$value = $cached_object['amount'];
		break;

		default:
			$value = get_post_meta( $booking_id, '_eo_booking_'.$key, $single );
	}
	$value = apply_filters( 'eventorganiser_get_booking_meta', $value, $booking_id, $key, $single );
	return $value = apply_filters( 'eventorganiser_get_booking_meta_'.$key, $value, $booking_id, $key, $single );
}

/**
 * A wrapper for `get_posts()` to retrieve bookings
 *
 * This function queries the booking and returns the matching bookings as an array (via `get_posts()`)
 *
 * Accepts the following arguments:
 *
 * * **status** - the status of the bookings (e.g. `any`,`confirmed`,`pending`).
 * * **event_id** - Get bookings for this event (post ID)
 * * **occurrence_id** - Get bookings for this occurrence (occurrence ID)
 * * **bookee_id** - Get bookings by user (ID)
 * * **booking_id** - Get a particular booking by ID
 * * **numberposts** - The number of bookings to return
 * * **orderby** - How to order by the bookings 'date', 'ID', 'price'. Default 'date'.
 * * **order** - ASC | DESC
 * * **offset** The number of bookings to offset by in the query
 * 
 * 
 * ### Example
 * Displays a list of confirmed bookings for event with ID 1986 and date with ID 2334. 
 *  <code>
 *      $bookings = eo_get_bookings( array(
 *      	'status'=>'confirmed',
 *      	'event_id' => 1986,
 *      	'occurrence_id' => 2334,
 *      ) );
 *      
 *      if( $bookings ){
 *      	echo '<ul>';
 *      	foreach( $bookings as $booking ){
 *      		printf( 
 *      			'<li> %1$s booked %2$s places for %3$s </li>',
 *      			eo_get_booking_meta( $booking->ID, 'bookee_display_name' ),
 *      			eo_get_booking_meta( $booking->ID, 'ticket_quantity' ),
 *      			get_the_title( eo_get_booking_meta( $booking->ID, 'event_id' ) )
 *      		);
 
 *      	}
 *      	echo '</ul>';
 *      }
 * </code>
 * @uses eventorganiser_get_bookings()
 * @since 1.1
 * @param array $args Array containing submission data
 * @return array Array of booking (post) objects matching criteria
 */
function eo_get_bookings( $args=array() ) {
	return eventorganiser_get_bookings( $args, false );
}

/**
 * A wrapper for WP_Query or get_posts to retrieve bookings
 *
 * This function queries the bookings. It can either return the matching bookings as an array (via `get_posts()`)
 * or the entire query object (via `WP_Query`).
 *
 * Accepts the following arguments:
 *
 * **fields** - what to return. E.g. `all`, `count_attending`
 * **status** - the status of the bookings (e.g. `any`,`confirmed`,`pending`).
 * **event_id** - get bookings for this event
 * **occurrence_id** - get bookings for this occurrence
 * **bookee_id** - get bookings by user
 * **booking_id** - get this particular booking
 * **numberposts** - the number of bookings per page ??
 * **orderby** - How to order by the bookings (e.g. 'date')
 * **order** - ASC | DESC
 * **offset** The number of bookings to offset by in the query
 * **update_ticket_cache** - whether to update the ticket cache.
 *
 * @since 1.0
 * @access private
 * @ignore
 * @param array   $args Array containing submission data
 * @return WP_Error Errors object. Null if booking is successful, as user is redirected to gateway.
 */
function eventorganiser_get_bookings( $args=array(), $wp_query = false ) {

	$defaults = array(
		'orderby' => 'date', 'status' => 'any', 'order' => 'ASC', 'numberposts' => -1,
		'fields' => '', 'offset' => '', 'ticket' => null, 'event_id' => null,
		'occurrence_id' => null, 'booking_id'=>null, 'bookee_id' => null, 'update_ticket_cache' => true
	);

	$args = wp_parse_args( $args, $defaults );
	$args['post_type']='eo_booking';

	/* Booking ID */
	if ( ! empty( $args['booking_id'] ) ) {
		$args['p'] =$args['booking_id'];
	}
	unset( $args['booking_id'] );

	/* Bookee */
	if ( isset( $args['bookee_id'] ) ) {
		$args['author'] = !empty( $args['bookee_id'] ) ? $args['bookee_id'] : 0;
	}
	unset( $args['bookee_id'] );

	/* Status */
	if ( !empty( $args['status'] ) ) {
		$args['post_status'] = $args['status'];
	}else {
		$args['post_status'] = 'any';
	}
	unset( $args['status'] );

	/* Query by post meta */
	$meta_fields = array( 'event_id', 'occurrence_id', 'booking_amount', 'ticket' );
	foreach ( $meta_fields as $key ) {
		if ( !empty( $args[$key] ) ) {
			$args['meta_query'][] = array(
				'key'=> '_eo_booking_'.$key,
				'value'=>$args[$key]
			);
		}
		unset( $args[$key] );
	}
	
	if( !empty( $args['search'] ) ){
		$args['suppress_filters'] = false;
		add_filter( 'posts_clauses', '_eventorgansier_pro_bookings_search', 10, 2 );
	}

	/* If counting attendees, query the relevant ticket rows */
	if ( 'count_attending' == $args['fields'] || 'count_tickets' == $args['fields'] ) {

		global $wpdb;
		
		if( 'count_attending' == $args['fields'] ){
			$args['post_status'] = eo_get_confirmed_booking_statuses();
		}
		$args['fields'] = 'ids';
		$args['numberposts']=-1;
		
		$booking_ids = get_posts( $args );
		
		//Remove search filter
		remove_filter( 'posts_clauses', '_eventorgansier_pro_bookings_search' );
		$attending =0;

		if ( $booking_ids ) {
			$booking_ids = implode( ',', $booking_ids );
			$attending =$wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->eo_booking_tickets} WHERE booking_id IN({$booking_ids})" );
		}
		return $attending;
	}

	/* Order */
	switch ( $args['orderby'] ):
		case 'id':
			$args['orderby'] = 'ID';
		break;
		case 'price':
			$args['orderby'] ='meta_value_num';
			$args['meta_key'] ='_eo_booking_booking_amount';
		break;
	endswitch;

	/* Use WP_Query or get_posts() */
	if ( $wp_query ) {
		if( empty($args['posts_per_page'] ) ){
			$args['posts_per_page'] = $args['numberposts'];
		}
		unset( $args['numberposts'] );
		
		$bookings = new WP_Query( $args );
		$booking_ids = wp_list_pluck( $bookings->posts, 'ID' );

	}else {
		$bookings = get_posts( $args );
		if( $args['fields'] != 'ids' )
			$booking_ids = wp_list_pluck( $bookings, 'ID' );
		else
			$booking_ids = $bookings;
	}
	
	//Remove search filter
	remove_filter( 'posts_clauses', '_eventorgansier_pro_bookings_search' );

	/* Update booking ticket cache */
	if ( $args['update_ticket_cache'] && $booking_ids ) {
		_eventorganiser_update_booking_ticket_cache( $booking_ids );
	}

	return $bookings;
}

function _eventorgansier_pro_bookings_search( $clauses, $query ){
	
	$search_term = $query->get( 'search' );
	
	if( empty( $search_term ) || 'eo_booking' != $query->get( 'post_type' ) )
		return $clauses;
	
	global $wpdb;

	//Searching by ID
	if( $search_term[0] == '#' ){

		$search_term = trim( $search_term, '#' );
		
		$clauses['where'] .= $wpdb->prepare(
								" AND( {$wpdb->posts}.ID LIKE '%s' ) ",
								'%'. like_escape( $search_term ) . '%'
							);

	//Searching by email
	}elseif ( strpos( $search_term,'@') !== false) {

		$clauses['join'] .= "INNER JOIN $wpdb->users ON $wpdb->posts.post_author = $wpdb->users.ID ";
		
		$where = $wpdb->prepare(
				" AND( {$wpdb->users}.user_email LIKE '%s' ) ",
				'%'. like_escape( $search_term ) . '%'
						);
		$clauses['where'] .= $where;
		
	//Searching by bookee name
	}else{
	
		$clauses['join'] .= "INNER JOIN $wpdb->users ON $wpdb->posts.post_author = $wpdb->users.ID ";

		$where = $wpdb->prepare(
				" AND( {$wpdb->users}.user_nicename LIKE '%s' OR {$wpdb->users}.display_name LIKE '%s' ) ",
				'%'. like_escape( $search_term ) . '%',
				'%'. like_escape( $search_term ) . '%'
		);
		$clauses['where'] .= $where;
	}
	
	return $clauses;
}



/**
 * Cancels a booking.
 * 
 * **Note:** This function changed behaviour in 1.6.0. Since 1.6.0 it no longer
 * permantly deletes a booking, but only 'trashes' it. See `eo_delete_booking()`
 * on how to permantly delete bookings. Unlike posts, cancelled bookings are *not* 
 * deleted after a set period.
 * Triggers `eventorganiser_booking_cancelled` action.
 *
 * @since 1.0
 * @see   eo_delete_booking()
 *
 * @param  int  $booking_id the booking ID
 * @return bool True on success, false on failure
 */
function eo_cancel_booking( $booking_id ) {

	if ( !$booking = get_post( $booking_id, ARRAY_A ) )
		return false;
	
	if ( 'eo_booking' != $booking['post_type'] )
		return false;
	
	if ( $booking['post_status'] == 'cancelled' )
		return false;
	
	do_action( 'eventorganiser_cancel_booking', $booking_id );
		
	add_post_meta( $booking_id, '_eo_cancel_meta_status', $booking['post_status'] );
	add_post_meta( $booking_id, '_eo_cancel_meta_time', time() );
	
	$booking['post_status'] = 'cancelled';
	eo_update_booking( $booking_id, $booking );
	
	do_action( 'eventorganiser_booking_cancelled', $booking_id );//Deprecated
	do_action( 'eventorganiser_cancelled_booking', $booking_id );

	return true;
}

/**
 * Restores a cancelled booking.
 *
 * This function restores a booking from its 'cancelled' status to
 * the status it was at when it was cancelled.
 *
 * @since 1.0
 * @see   eo_delete_booking()
 *
 * @param  int  $booking_id the Booking ID
 * @return bool True on success, false on failure
 */
function eo_restore_booking( $booking_id ) {

	if ( !$booking = get_post( $booking_id, ARRAY_A ) )
		return false;
	
	if ( 'eo_booking' != $booking['post_type'] )
		return false;
	
	if ( $booking['post_status'] != 'cancelled' )
		return false;
	
	do_action( 'eventorganiser_restore_booking', $booking_id );
	
	$booking['post_status'] = get_post_meta( $booking_id, '_eo_cancel_meta_status', true );
	
	delete_post_meta( $booking_id, '_eo_cancel_meta_status');
	delete_post_meta( $booking_id, '_eo_cancel_meta_time');

	eo_update_booking( $booking_id, $booking );
	
	do_action( 'eventorganiser_restored_booking', $booking_id );
	
	return true;

}

/**
 * **Permanantly** deletes a booking it. This cannot be undone.
 * Deletes corresponding tickets from the booking_tickets table and
 * triggers `eventorganiser_booking_deleted` action. To only cancel
 * and not delete a booking see `eo_cancel_booking()`.
 *
 * @since 1.6
 *
 * @param  int $booking_id the booking ID
 * @return bool True on success, false on failure
 */
function eo_delete_booking( $booking_id ) {

	global $wpdb;

	$booking = get_post( $booking_id );

	if ( 'eo_booking' != get_post_type( $booking ) )
		return false;

	do_action( 'eventorganiser_delete_booking', $booking_id );

	$sql = $wpdb->prepare( "DELETE FROM {$wpdb->eo_booking_tickets} WHERE {$wpdb->eo_booking_tickets}.booking_id=%d;", $booking_id );

	$deleted = $wpdb->get_results( $sql );
	wp_delete_post( $booking_id, true );

	eventorganiser_clear_cache( 'eo_booking', $booking_id );
	eventorganiser_clear_cache( 'eo_booking_tickets', $booking_id );

	do_action( 'eventorganiser_deleted_booking', $booking_id );

	return true;
}

/**
 * Updates a booking status to 'confirmed'
 *
 * Checks for errors (i.e. over booked events, ticket removed from event etc).
 * These can be ignored by setting `$force_confirmation` to true.
 * Returns true on success, false on error (e.g. invalid booking ID), or WP_Error on failure to confirm booking 
 * (e.g. over booked). 
 * 
 * Triggers `eventorganiser_booking_confirmed` action if it confirms the booking.
 *
 * @since 1.0
 *
 * @param int     $booking_id the Booking ID
 * @return WP_Error|bool True on success, false on error or WP_Error on failure
 */
function eo_confirm_booking( $booking_id, $force_confirmation = false ){

	//Sanithy checks
	$booking = get_post( $booking_id, 'ARRAY_A' );

	//Passing array to get_post_type causes problems
	if ( 'eo_booking' != $booking['post_type'] )
		return false;

	//Already confirmed!
	if ( 'confirmed' == $booking['post_status'] )
		return true;

	$error = new WP_Error();

	if( !$force_confirmation ){

		//Get booking details
		$tickets_to_confirm = eo_get_booking_tickets( $booking_id );
		$post_id = eo_get_booking_meta( $booking_id, 'event_id' );
		$occurrence_id = eo_get_booking_meta( $booking_id, 'occurrence_id' );

		//Get available event tickets & #confirmed
		$event_tickets = eo_get_event_tickets( $post_id, $occurrence_id );
		$confirmed = eventorganiser_get_confirmed_numbers( $post_id, $occurrence_id );
		
		foreach ( $tickets_to_confirm as $ticket ) {
			$ticket_id = $ticket->ticket_id;
			$name = $ticket->ticket_name;

			if ( !isset(  $event_tickets[$ticket_id] ) ) {
				//This ticket has been removed from the event!
				$message = sprintf( '<strong>%s</strong>: is no longer available for this event', $name );
				$error->add( 'ticket_no_long_exists_'.$ticket_id, $message, compact( 'name', 'ticket_id' ) );
				continue;
			}

			//How many spaces for this ticket type is there, how many have been booked and how many are we booking:
			$spaces = $event_tickets[$ticket_id]['spaces'];
			$booked = isset( $confirmed[$ticket_id]->confirmed ) ? $confirmed[$ticket_id]->confirmed : 0;
			$required = $ticket->ticket_quantity;

			if ( $spaces - $booked < $required ) {
				//I cannae fit them in captain!
				$message = sprintf( '<strong>%s</strong>: %d spaces remaining, attempted to confirm %d tickets', $name, $spaces-$booked, $required );
				$error->add( 'event_full_'.$ticket_id, $message , compact( 'spaces', 'booked', 'required', 'name', 'ticket_id' ) );
			}
		}

		if ( $cap = (int) get_post_meta( $post_id, '_eventorganiser_booking_cap', true ) ) {
			$total_confirmed = eventorganiser_list_sum( $confirmed, 'confirmed' );
			$total_to_confirm = eventorganiser_list_sum( $tickets_to_confirm, 'ticket_quantity' );
			if ( $total_confirmed + $total_to_confirm > $cap ) {
				$message = sprintf( 'Booking limit ( %d places) reached. Attempting to confirm %d tickets (currently %d tickets confirmed)', $cap, $total_to_confirm, $total_confirmed );
				$error->add( 'event_full', $message , compact( 'cap', 'total_to_confirmed', 'total_confirmed' ) );
			}
		}
	}//Endif force confirmation

	if( $force_confirmation || !$error->get_error_codes() ){
		//Either we don't care about any 'overful' events or it isn't a problem
		$booking['post_status'] = 'confirmed';
		$booking['edit_date'] =1;
		$booking['post_author'] = ( !empty( $booking['post_author'] ) ? $booking['post_author'] : -1 );
		$booking['ID'] = $booking_id;
		
		do_action( 'eventorganiser_confirm_booking', $booking_id );
		wp_update_post( $booking );
		do_action( 'eventorganiser_confirmed_booking', $booking_id );
		do_action( 'eventorganiser_booking_confirmed', $booking_id );//Deprecated
		
		return true;
	}

	return $error;
}

/**
 * Changes a booking's occurrence ID. 
 * 
 * @since 1.1
 * @ignore
 * @param int $booking_id The booking ID
 * @param int $new_occurrence_id The occurrence ID you want for the booking
 * @param bool $force_change ??
 * @return boolean
 */
function eo_change_booking_occurrence( $booking_id, $new_occurrence_id, $force_change = false ){
	global $wpdb;
	
	$occurrence_id = eo_get_booking_meta( $booking_id, 'occurrence_id' );
	$book_series = eventorganiser_pro_get_option( 'book_series' ) ? true : false;
	
	if( !$book_series && $occurrence_id != $new_occurrence_id ){
		update_post_meta( $booking_id, '_eo_booking_occurrence_id', $new_occurrence_id );
		$wpdb->update( $wpdb->eo_booking_tickets, array( 'occurrence_id' => $new_occurrence_id ), array( 'booking_id' => $booking_id ) );
		do_action( 'eventorganiser_change_booking_occurrence', $booking_id, $new_occurrence_id, $occurrence_id );
	}

	return true;
}

/**
 * Retrieve booking status
 *
 * @uses get_post_status()
 * @since 1.0
 *
 * @param int|object $booking Booking ID or booking object
 * @return string|bool Booking status or false on failure.
 */
function eo_get_booking_status( $booking ) {
	return get_post_status( $booking );
}


/**
 * Update the booking ticket cache.
 *
 * Caches an array of booking-ticket ID => booking-ticket object for the specified bookings.
 * *(A booking ticket is an actual 'ticket' in the sense its generated when a booking is made)*.
 *
 * @uses wp_cache_get()
 * @uses wp_cache_add()
 * @used-by `eventorganiser_get_bookings()`
 * @access private
 * @ignore
 * @since 1.0
 *
 * @param array   $booking_ids Array of booking IDs for which we need to update the ticket cache.
 */
function _eventorganiser_update_booking_ticket_cache( $booking_ids ) {

	$cached = array();
	$cache_these =array();

	/* Retrieve currently cached objects */
	foreach ( $booking_ids as $bid ) {
		$cached_object = eventorganiser_cache_get( $bid, 'eo_booking_tickets' );
		if ( false === $cached_object )
			$cache_these[] = $bid;
		else
			$cached[$bid] = $cached_object;
	}

	/* If currently cached, abort */
	if ( empty( $cache_these ) )
		return $cached;

	/* Retrieve tickets for bookings that need to be cached */
	$tickets = eo_get_booking_tickets( $cache_these, false );
	if ( $tickets ) {
		foreach ( $tickets as $ticket ) {
			$bid = intval( $ticket->booking_id );
			$btid = intval( $ticket->booking_ticket_id );

			// Force ticket subkey to be array type:
			if ( !isset( $cached[$bid] ) || !is_array( $cached[$bid] ) )
				$cached[$bid] = array();

			// Add a value to the current pid/key:
			$cached[$bid][$btid] = $ticket;
		}
	}

	/* Add tickets to cache */
	foreach ( $cache_these  as $bid ) {
		if ( ! isset( $cached[$bid] ) )
			$cached[$bid] = array();
		eventorganiser_cache_set( $bid, $cached[$bid], 'eo_booking_tickets' );
	}
}
