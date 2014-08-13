<?php

/**
 * Register event metaboxes
 */
function eventorganiser_pro_add_meta_boxes() {
	add_meta_box( 'eventorganiser_tickets', __( 'Event Tickets', 'eventorganiser' ), 'eventorganiser_tickets_metabox', 'event', 'normal' );
}
add_action( 'add_meta_boxes_event', 'eventorganiser_pro_add_meta_boxes' );

/**
 * Apend warning notice to recurring events
 */
function eventorganiser_pro_booking_warning( $notices, $post ) {
	if ( !empty( $notices ) ) $notices .= '</br>';
	return $notices . 
		esc_html__( 'If you edit the event\'s dates you will need to update any event tickets. If you remove a date, any bookings for that date will be ignored until you re-assign them an event date.', 'eventorganiserp' );
}
add_filter( 'eventorganiser_event_metabox_notice', 'eventorganiser_pro_booking_warning', 10, 2 );


/**
 * Display ticket metabox
 * @param object $post The event post
 */
function eventorganiser_tickets_metabox( $post ) {

	// Use nonce for verification
	wp_nonce_field( 'event_organiser_pro_edit_event_'.$post->ID, '_eventorganiser_pro_nonce', false, true );

	$tickets_table = new EO_Event_Tickets( $post->ID );
	$tickets_table->display();

	$cap = (int) get_post_meta( $post->ID, '_eventorganiser_booking_cap', true );

	printf(
		'<p>'.esc_html__( 'Cap booking at %s places', 'eventorganiserp' ).'</p>',
		eventorganiser_text_field( array(
				'id' => 'eo-cap-booking',
				'name' => 'eo_cap_booking',
				'placeholder' => 'Do not cap',
				'echo' => 0,
				'type' => 'number',
				'size' => 3,
				'style' => 'width:auto;',
				'min' => 0,
				'value' => $cap ? $cap : null,
			) )
	);
	
	printf( '<label> %s', __( 'Select a booking form:', 'eventorganiserp' ) ) . ' ';
	eventorganiser_forms_dropdown( array(
		'selected' => (int) get_post_meta( $post->ID, '_eventorganiser_booking_form', true ),
		'name' => 'eo_booking_form',
		'echo' => '1'
	));
	echo '</label>';
	
	do_action( 'eventorganiser_booking_metabox_bottom', $post->ID );
}


/**
 * Save event tickets.
 * Hooked onto to eventorganiser_save_event
 * @param int $post_id Event post ID
 */
function eventorganiser_pro_save_event( $post_id ) {
	global $wpdb;

	// verify this is not an auto save routine.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

	//authentication checks
	if ( !current_user_can( 'edit_event', $post_id ) ) return;

	//Sanity check
	if ( !isset( $_POST['eventorganiser_pro'] ) || !isset( $_POST['_eventorganiser_pro_nonce'] ) ) return;

	//Nonce check
	if( !wp_verify_nonce( $_POST['_eventorganiser_pro_nonce'], 'event_organiser_pro_edit_event_'.$post_id ) ) return;


	$schedule = eo_get_event_schedule( $post_id );

	/* Expect ticket dates to be in d-m-Y format! See default value of eo_format_datetime()*/
	$occurrences = array_map( 'eo_format_datetime', $schedule['_occurrences'] );

	$cap = isset( $_POST['eo_cap_booking'] ) ? intval( $_POST['eo_cap_booking'] ) : 0;
	$booking_form = isset( $_POST['eo_booking_form'] ) ? intval( $_POST['eo_booking_form'] ) : 0;
	update_post_meta( $post_id, '_eventorganiser_booking_cap', $cap );
	update_post_meta( $post_id, '_eventorganiser_booking_form', $booking_form );

	if( !empty($_POST['eventorganiser_pro']['ticket']) ){
		$tickets = $_POST['eventorganiser_pro']['ticket'];
		foreach ( $tickets as $ticket_id => $ticket_data ) {

			if ( !isset( $ticket_data['action'] ) || 'delete' == $ticket_data['action'] ) {
				//Delete ticket

				//If ticket hasn't been created yet, skip
				if ( empty( $ticket_data['ticket_id'] ) )
					continue;

				//Delete ticket
				$ticket_id = (int) $ticket_data['ticket_id'];
				eventorganiser_delete_ticket( $ticket_id );

			}else {
								
				if ( !empty( $ticket_data['to'] ) ) {
					$time = !empty( $ticket_data['to_time'] ) ? $ticket_data['to_time'] : '23:59';
					//Potentially need to parse 24
					$time = date( "H:i", strtotime( $time ) );
					$ticket_data['to'] = _eventorganiser_check_datetime( $ticket_data['to'] . ' ' . $time );
				}else {
					$ticket_data['to'] = false;
				}

				if ( !empty( $ticket_data['from'] ) ) {
					$time = !empty( $ticket_data['from_time'] ) ? $ticket_data['from_time'] : '00:00';
					//Potentially need to parse 24
					$time = date( "H:i", strtotime( $time ) );
					
					$ticket_data['from'] = _eventorganiser_check_datetime( $ticket_data['from'] . ' ' . $time );
				}else {
					$ticket_data['from'] = false;
				}
				

				//For each ticket... insert/update ticket information
				if ( !empty( $ticket_data['ticket_id'] ) ) {
					//update
					$ticket_id = (int) $ticket_data['ticket_id'];
	
					//Get selected occurrence dates
					if( $ticket_data['selected_dates'] == -1 ){
						//When creating a ticket for the first time, we deselect dates rather than select them
						$excluded_dates = array_filter( explode( ',', $ticket_data['deselected_dates'] ) );
						$selected_dates =array_diff( $occurrences, $excluded_dates );

					}else{
						$selected_dates = array_filter( explode( ',', $ticket_data['selected_dates'] ) );
						$selected_dates = array_intersect( $occurrences, $selected_dates );
					}
					
					//Array of occurrence IDs for which this ticket is available
					$selected_ids = array_keys( $selected_dates );
					$ticket_data['occurrence_ids'] = $selected_ids;

					eventorganiser_update_ticket( $ticket_id, $ticket_data );

				}else {
					
					//Used to remove ticket from $_POST, see below.
					$index = $ticket_id; 
					
					//When creating a ticket for the first time, we deselect dates rather than select them
					$excluded_dates = array_filter( explode( ',', $ticket_data['deselected_dates'] ) );
					$selected_dates =array_diff( $occurrences, $excluded_dates );

					//Array ofccurrence IDs for which this ticket is available
					$selected_ids = array_keys( $selected_dates );
					$ticket_data['occurrence_ids'] = $selected_ids;
					$ticket_id = eventorganiser_insert_ticket( $post_id, $ticket_data );
					
					//Remove ticket from $_POST, @see http://wp-event-organiser.com/forums/topic/tickets-being-duplicated/
					unset( $_POST['eventorganiser_pro']['ticket'][$index] );
				}
				
			}//If deleting / updating
			
		}//Foreach ticket
		
	}//If tickets
	
}
add_action( 'eventorganiser_save_event', 'eventorganiser_pro_save_event' );