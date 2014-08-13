<?php
/**
 * Random functions - should find a better home for these.
 *
 * @package general-functions
 */

/**
 * The url for cancelling (permantly deleting) a booking
 *
 * @since 1.0
 * @ignore 
 * @access private
 * @param int     $booking_id The booking ID
 * @return string The url for deleting the booking.
 */
function eventorganiser_cancel_booking_url( $booking_id ) {
	$url = admin_url( 'edit.php?post_type=event&page=bookings' );
	return wp_nonce_url( add_query_arg( array( 'action'=>'cancel', 'booking_id'=>$booking_id ), $url ), 'eo-cancel-booking-'.$booking_id );
}

/**
 * The url for editing a booking
 *
 * @since 1.0
 * @ignore 
 * @access private
 * @param int     $booking_id The booking ID
 * @return string The url for editing the booking.
 */
function eventorganiser_edit_booking_url( $booking_id ) {
	$url = admin_url( 'edit.php?post_type=event&page=bookings' );
	return add_query_arg( array( 'action'=>'edit', 'booking_id'=>$booking_id ), $url );
}


/**
 * The url for the bookings admin page
 *
 * By specifying an event (and additionally an occurrence) it returns an url which
 * filters the admin booking page by event (and occurrence).
 *
 * @since 1.0
 * @ignore 
 * @access private
 * @param int     $event      The event ID
 * @param int     $occurrence The occurrence ID
 * @return string The url for bookings admin page
 */
function eventorganiser_bookings_admin_url( $event=false, $occurrence=false ) {
	$bookings_url = add_query_arg( array(
			'event_id'=>$event,
			'occurrence_id'=>$occurrence
		), admin_url( 'edit.php?post_type=event&page=bookings' )
	);
	return $bookings_url;
}

/**
 * The url for exporting tickets
 *
 * @since 1.0
 * @ignore 
 * @access private
 * @param array   $args
 * @return string The url for exporting tickets
 */
function eventorganiser_ticket_export_url( $args=array() ) {
	$args['action'] = 'export-tickets';
	return add_query_arg( $args );
}

/**
 * The url for exporting bookings
 *
 * @since 1.0
 * @ignore 
 * @access private
 * @param array   $args
 * @return string The url for exporting bookings
 */
function eventorganiser_booking_export_url( $args=array() ) {
	$args['eo-action'] = 'export-bookings';
	return add_query_arg( $args );
}

/**
 * Returns plug-in option.
 *
 * In case the option does not exist, a default can be specified.
 *
 * @since 1.0
 *
 * @param string  $option  The option key
 * @param string  $default Value to use if the option doesn't exist
 * @return mixed The option value.
 */
function eventorganiser_pro_get_option( $option, $default=false ) {
	$options = get_option( 'eventorganiser_pro_options' );
	
	$defaults = array(
		'currency'=>'USD',
		'element_id' => 2,
		'field_id' => 2,
		'currency_position'=>1,
		'book_series'=>0,
		'email_template' => 'eo-email-template-event-organiser.php',
		'disable_automatic_form' => false, //Hidden option - dont auto-add booking form to events
		'paypal_username'=>'',
		'paypal_password'=>'',
		'paypal_page_style' => '',
		'paypal_signature'=>'',
		'paypal_live_status' => 0,
		'offline_live_status'=>-1,
		'offline_instructions'=>'',
		'paypal_local_site'=>'US',
		/* 0 = no, 1 = yes, but register, 2 = yes, optional register, 3 yes, but dont register */
		'allow_guest_booking' => 1, 
		'allow_anon_booking' => 0,
		'notify_bookings'=>array( 'new', 'confirmed' ),
		'reserve_pending_tickets'=>1,
		'email_tickets_message'=> 'Dear %display_name%, Thank you for booking with Event Organiser. Your tickets: <p> %tickets%</p>. Your booking reference is %booking_reference%',
	);

	$default = isset( $defaults[$option] ) ? $defaults[$option] : $default;
	$value = isset( $options[$option] ) ? $options[$option] : $default;
	return apply_filters( 'eventorganiser_pro_get_option_'.$option, $value );
}

/**
 * Update the value of an Event Organiser Pro option
 *
 * The passed value should be validated (not escaped) as the validation callback is removed.
 *
 * @since 1.0
 * @ignore
 * @access private
 *
 * @param string  $option   Option name. Expected to not be SQL-escaped.
 * @param mixed   $newvalue Option value. Expected to not be SQL-escaped.
 * @return bool False if value was not updated and true if value was updated.
 */
function eventorganiser_pro_update_option( $option, $value ) {
	$options = get_option( 'eventorganiser_pro_options' );
	
	//Don't run validatino callback - its expensive. If updating one value, expect it to be already
	//sanitised. Do not escape.
	$r = remove_filter( "sanitize_option_eventorganiser_pro_options", 'eventorganiser_pro_validate_settings' );
	$options[$option] = $value;
	$update = update_option( 'eventorganiser_pro_options', $options );
	if( $r )
		add_filter( "sanitize_option_eventorganiser_pro_options", 'eventorganiser_pro_validate_settings' );
	return $update;
}


/**
 * Returns an array of unique values corresponding to a meta key in the venue meta table
 *
 * @since 1.0
 * @ignore
 * @access private
 *
 * @param string  $key The meta key for which to get the meta values.
 * @return array Array of meta values corresponding to the meta key.
 */
function eventorganiser_get_venue_meta_values( $key ) {

	global $wpdb;

	$r = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT meta_value FROM {$wpdb->eo_venuemeta}
			WHERE {$wpdb->eo_venuemeta}.meta_key = '%s' AND {$wpdb->eo_venuemeta}.meta_value != ''
			ORDER BY {$wpdb->eo_venuemeta}.meta_value ASC",
			$key
		)
	);
	return $r;
}

/**
 * Returns an array of unique cities from the venue meta table
 *
 * @since 1.0
 * @ignore
 * @access private
 * @uses eventorganiser_get_venue_meta_values()
 *
 * @return array Array of meta values corresponding to the meta key.
 */
function eo_get_venue_cities() {
	return eventorganiser_get_venue_meta_values( '_city' );
}

/**
 * Returns an array of unique states from the venue meta table
 *
 * @since 1.0
 * @ignore
 * @access private
 * @uses eventorganiser_get_venue_meta_values()
 *
 * @return array Array of meta values corresponding to the meta key.
 */
function eo_get_venue_states() {
	return eventorganiser_get_venue_meta_values( '_state' );
}

/**
 * Returns an array of unique countries from the venue meta table
 *
 * @since 1.0
 * @ignore
 * @access private
 * @uses eventorganiser_get_venue_meta_values()
 *
 * @return array Array of meta values corresponding to the meta key.
 */
function eo_get_venue_countries() {
	return eventorganiser_get_venue_meta_values( '_country' );
}


/**
 * Returns a bookings form's name (slug).
 * @ignore 
 * @access private
 * @param int $id The ID of the form
 * @return string The name of the form
 */
function eventorganiser_get_form_name( $id ){
	$form = get_post( $id );
	return $form->post_name;
}

/**
 * Returns an array of forms. A wrapper for get_posts().
 * @ignore 
 * @access private
 * @return array of post objects of 'form8_form' type.
 */
function eventorganiser_get_booking_forms( $args = array() ){
	$args = array_merge( array( 'post_type' => 'eo_booking_form', 'order' => 'ASC', 'numberposts' => -1 ), $args );
	return get_posts( $args );
}

/**
 * Return or print mark-up for a select form of existing forms.
 * @ignore 
 * @access private
 * @uses eventorganiser_select_field()
 * @param bool $echo Whether to print the markup
 * @return string The drop-down select field markup
 */
function eventorganiser_forms_dropdown( $args ){
	
	$forms = eventorganiser_get_booking_forms();
	$form_options = array();

	foreach( $forms as $form ){
		$form_options[$form->ID] = $form->post_name.' ( ID: '.$form->ID.')';
	}

	$html = eventorganiser_select_field( array(
			'selected' => $args['selected'],
			'options' => $form_options,
			'name' => $args['name'],
			'echo' => 0,
	));

	if( !empty( $args['echo'] ) )
		echo $html;

	return $html;
}

/**
 * @ignore 
 * @access private
 * @param unknown_type $event_id
 * @return EO_Booking_Form
 */
function eo_get_event_booking_form( $event_id = 0 ){

	$event_id = ( empty( $event_id ) ? get_the_ID() : $event_id );
	$form_id = get_post_meta( $event_id, '_eventorganiser_booking_form', true );
	
	if ( false === $form_id || get_post_type( $form_id ) != 'eo_booking_form' ){
		$form_ids = eventorganiser_get_booking_forms( array( 'fields' => 'ids', 'posts_per_page' => 1 ) );
		$form_id = array_pop( $form_ids ); 	
	}
	
	if( EO_Booking_Form_Controller::$form && EO_Booking_Form_Controller::$form->id == $form_id ){
		$form = EO_Booking_Form_Controller::$form;
		return $form;
	}else{
		$form = new EO_Booking_Form( array( 'id' => $form_id ) );	
	}

	//Set occurrence ID - 0 if we are booking a series
	if ( eventorganiser_pro_get_option( 'book_series' ) ) {
		$occurrence_id = 0;
		$disabled = false;
			
	}elseif ( 'event' == get_post_type() && !eo_reoccurs() ) {
		global $post;
		$occurrence_id = $post->occurrence_id;
		$disabled = false;
			
	}else {
		$occurrence_id = isset( $_POST['eventorganiser']['booking']['occurrence_id'] ) ? (int) $_POST['eventorganiser']['booking']['occurrence_id'] : '';
		$disabled = true;
	}
	
	$form->fetch();
		
	//Add occurrence ID hidden field
	$form->add_element( new EO_Booking_Form_Element_Hidden( array(
		'id'			=> 'occurrence_id',
		'field_id' 		=> 'eo-booking-occurrence-id',
		'disabled' 		=> $disabled, //Disable then enable with js
		'class' 		=> ( $disabled ? 'eo-enable-if-js' : '' ),
		'value' 		=> $occurrence_id,
	)));
	
	//Occurrence date
	$form->add_element( new EO_Booking_Form_Element_Hidden( array(
		'id' 			=> 'occurrence_date',
		'field_id' 		=> 'eo-booking-occurrence-date',
		'value' 		=> isset( $_POST['occurrence_date'] ) ? esc_attr( $_POST['occurrence_date'] ) : '',
	)));
	
	
	if( !is_user_logged_in() && 2 == eventorganiser_pro_get_option( 'allow_guest_booking' ) ){
		$email = $form->get_element( 'email' );
		if( $email ){
			$position = intval( $email->get( 'position' ) ) + 1;
			$parent   = $email->get_parent();
			$parent   = ( $parent ? $parent->id : false );
			
			$account_checkbox =  new EO_Booking_Form_Element_Checkbox( array(
				'id'         => 'create-account',
				'field_name' => 'account',
				'name'       => 'account',
		    	'options'    => array( 1 => __( 'Create an account (optional)', 'eventorganiserp' ) ),
			));
			$form->add_element( $account_checkbox, array( 'at' => $position, 'parent' => $parent ) );
		}
	}	

	do_action_ref_array( 'eventorganiser_get_event_booking_form', array( &$form, $event_id ) );

	if( isset( $_POST['eventorganiser'] ) && isset( $_POST['eventorganiser']['booking'] ) ){

		if( $form->get_elements() ){
			
			$input = $_POST;
			
			foreach( $form->flatten_elements() as $element ){
				
				$value = null;
				$name = $element->get_field_name();
				$name_parts = preg_split( '/[\[\]]+/', $name, -1, PREG_SPLIT_NO_EMPTY );
				
				$ref = &$input;
    			
				while ( $name_parts ) {
					$part = array_shift( $name_parts );

            		if( $name_parts && !isset( $ref[$part] ) ){
            			break;
            		}
            		
            		
            		if ( !isset( $ref[$part] ) ) {
                		break;
            		}

            		if( !$name_parts && isset( $ref[$part] ) ){
            			$value = $ref[$part];
            			break;
            		}
    				
            		$ref = &$ref[$part];            		
        		}
        		if( isset( $value ) ){
        			$element->set_value( stripslashes_deep( $value ) );	
        		}
			}
		}
	}
	
	return $form;
}

/**
 * Updates bookings and bookings ticket if they point to an occurrence being broken.
 *
 * When an occurrence is broken we need to update the event ID & occurrence ID associated 
 * with bookings for the breaking occurrence. We also need to copy across all tickets that
 * are available for that date. Lastly, we update the booking tickts to reflect the new event, occurrence
 * and ticket ID.
 *
 * Note: When booking series, booking tickets are not copied across.
 *
 * @ignore 
 * @access private
 *
 * @since 1.0
 * @ignore
 * @access private
 *
 */
function eventorganiser_pro_broken_occurrence_handle_tickets( $event_id, $occurrence_id, $new_event_id ){

	global $wpdb;

	//Get tickets for broken occurrence
	$occurrence_tickets = eo_get_event_tickets( $event_id, $occurrence_id );

	//Get the occurrences of the new event
	$occurrences =  eo_get_the_occurrences_of( $new_event_id );

	//Record trail of ticket types ID
	$ticket_type_id_trail = array();

	if( $occurrence_tickets && $occurrences ){

		//Create new tickets for new event
		foreach( $occurrence_tickets as $ticket_type_id => $ticket ){

			//Get the new occurrence ID
			$new_occurrence_id = array_pop( array_keys( $occurrences ) );
			
			//Create ticket for broken event
			$new_ticket_type_id = eventorganiser_insert_ticket( $new_event_id, array(
				'spaces' => $ticket['spaces'],
				'occurrence_ids' => array( $new_occurrence_id ),
				'price' => $ticket['price'],
				'name' => $ticket['name'],
				'from' => $ticket['from'],
				'to' => $ticket['to']
			) );
			
			$ticket_type_id_trail[$ticket_type_id] = $new_ticket_type_id;
		}

		//Get bookings for the broken occurrence and update them
		if( !eventorganiser_pro_get_option( 'book_series' ) ){

			//Get all affected bookings
			$bookings = eventorganiser_get_bookings( array(
				'occurrence_id' => $occurrence_id,
				'event_id' => $event_id
			 ) );

			if( $bookings ){
				foreach( $bookings as $booking ){
						
					//Update booking occurrence & event ID
					update_post_meta( $booking->ID, '_eo_booking_occurrence_id', $new_occurrence_id );
					update_post_meta( $booking->ID, '_eo_booking_event_id', $new_event_id );

					//Update booking tickets
					foreach( $ticket_type_id_trail as $old_ticket_id => $new_ticket_id ){

						//Update booking tickets with ticket, event and occurrence ID.
						$wpdb->update( 
							$wpdb->eo_booking_tickets, 
							array( 
								'ticket_id' => $new_ticket_id,
								'event_id' => $new_event_id,
								'occurrence_id' => $new_occurrence_id,
							), 
							array(
								'ticket_id' => $old_ticket_id,
								'booking_id' => $booking->ID,
							), array( '%d', '%d', '%d' ), array( '%d', '%d' ) 
						);
					}
				}
			}//If bookings
		}//If booking series
	}
}
add_action( 'eventorganiser_occurrence_broken', 'eventorganiser_pro_broken_occurrence_handle_tickets', 10, 3 );

/**
 * @ignore 
 * @access private
 */
function eo_get_event_archives( $type = 'month', $limit = 10, $format = false ){
	global $wpdb;
	
	$pointer = false;
	$dates = array();
	
	
	$default_formats = array(
		'year' => 'Y',
		'month' => 'F Y',
		'day' => 'jS F Y',
	);
	
	if( !$format ){
		$format = $default_formats[$type];
	}
	
	switch( $type ):
		case 'year':
			$query = $wpdb->prepare("SELECT YEAR(`StartDate`) AS startYear, YEAR(`EndDate`) AS endYear
				FROM `wp_eo_events` WHERE 1 =1
				GROUP BY YEAR(`StartDate`), YEAR(`EndDate`)
				ORDER BY `StartDate` DESC
				LIMIT %d", $limit);
		
			$years = $wpdb->get_results($query);
			
			foreach( $years as $year ){
				
				if( $limit == 0 )
					break;
				
				if( !$pointer ){
					$pointer = new DateTime( $year->endYear.'-01-01' );
				}
		
				$end_year = new DateTime( $year->endYear.'-01-01' );
				$start_year = new DateTime( $year->startYear.'-01-01' );
				
				if( $pointer > $end_year )
					$pointer = $end_year;
		
				while( $pointer >= $start_year && $limit > 0 ){
					
					$dates[] = sprintf(
						'<a href="%s">%s</a>',
						eo_get_event_archive_link( $pointer->format('Y') ),
						$pointer->format( $format )
					);
					
					$pointer->modify('-1 year');
					$limit--;
				}
			}
			
		
		break;
	
		case 'month':
			$query = $wpdb->prepare("SELECT YEAR(`StartDate`) AS startYear, MONTH(`StartDate`) AS startMonth,
				YEAR(`EndDate`) AS endYear, MONTH(`EndDate`) AS endMonth
				FROM `wp_eo_events` WHERE 1 =1 AND StartDate >= '2013-03-15'
				GROUP BY YEAR(`StartDate`), MONTH(`StartDate`), YEAR(`EndDate`), MONTH(`EndDate`)
				ORDER BY `StartDate` ASC
				LIMIT %d", $limit);
	 
			$months = $wpdb->get_results($query);
			foreach( $months as $month ){
				if( $limit == 0 )
					break;
				if( !$pointer ){
					$pointer = new DateTime( $month->startYear.'-'.$month->startMonth.'-01' );
				}

				$end_month = new DateTime( $month->endYear.'-'.$month->endMonth.'-01' );
				$start_month = new DateTime( $month->startYear.'-'.$month->startMonth.'-01' );

				if( $pointer < $start_month )
					$pointer = $start_month;

				while( $pointer <= $end_month && $limit > 0 ){
					$dates[] = sprintf( 
						'<a href="%s">%s</a>',
						eo_get_event_archive_link( $pointer->format('Y'), $pointer->format('m') ),
						$pointer->format( $format )
					);
			
					$pointer->modify('+1 month');
					$limit--;
				}
			}
		break;
		
		case 'day':
			$query = $wpdb->prepare("SELECT StartDate as startDate, EndDate as endDate
				FROM `wp_eo_events` WHERE 1 =1
				GROUP BY StartDate, EndDate
				ORDER BY `StartDate` DESC
				LIMIT %d", $limit);
		
			$days = $wpdb->get_results($query);
			foreach( $days as $day ){
				if( $limit == 0 )
					break;
				if( !$pointer ){
					$pointer = new DateTime( $day->endDate);
				}
		
				$end_day = new DateTime( $day->endDate );
				$start_day = new DateTime( $day->startDate );
		
				if( $pointer > $end_day )
					$pointer = $end_day;
		
				while( $pointer >= $start_day && $limit > 0 ){
					$dates[] = sprintf(
						'<a href="%s">%s</a>',
						eo_get_event_archive_link( $pointer->format('Y'), $pointer->format('m'), $pointer->format('d') ),
						$pointer->format( $format )
					);
						
					$pointer->modify('-1 day');
					$limit--;
				}
			}
		break;
		
	endswitch;
	print_r($dates);
}


/**
 * @ignore 
 * @access private
 * @param unknown_type $event
 * @param unknown_type $event_id
 * @param unknown_type $occurrence_id
 * @return Ambigous <mixed, string, multitype:, boolean, unknown, string>
 */
function _eventorganiser_pro_append_venue_details( $event, $event_id, $occurrence_id ){
	$venue_id = eo_get_venue( $event_id );
	$meta = array( 'country', 'city', 'state' );
	
	if( $venue_id ){
		foreach( $meta as $key ){
			$event[$key] = eo_get_venue_meta( $venue_id, '_'.$key );
		}
	}
	
	return $event;
}
add_filter( 'eventorganiser_fullcalendar_event', '_eventorganiser_pro_append_venue_details', 10, 3 );

/**
 * Given an address, returns the latitude-longtitude co-ordinates of that address.
 * 
 * By default uses *[Nominatim Search Service](http://open.mapquestapi.com/nominatim/)* to geocode an address. The result is cached for 1 week.
 * Returns the an array of latitude-longtitude co-ordinates, indexed by 'lat', and 'lng'.
 * 
 * Applies the filter `eventorganiser_remote_geocode` to allow plug-ins to change the service provider or append an API key
 * if this becomes necessary
 * 
 * Please note that this function is offered without any gurantee of accurracy. *Please note that
 * the default service provider may in the future require an API key - this canbe added usign the
 * above filter* 
 * 
 * ### Example
 * The following retreives all events within 10 miles of Windsor Castle
 * <code>
 *      $venue_query = array(
 *			'proximity' => array(
 *					'center' => eo_remote_geocode( "Windsor [castle]" ),
 *					'distance' => 10,
 *					'unit' => 'miles',
 *					'compare' => '<='
 *			),	
 *      );
 *
 *      $events = eo_get_events( array(
 *                  'event_start_after' => 'now',
 *                  'venue_query' => $venue_query,
 *                ));
 * </code>
 * @link http://open.mapquestapi.com/nominatim/ Nominatim Search Service
 * @since 1.2
 * @param string $address
 * @return array Indexed by 'lat' and 'lng'
 */
function eo_remote_geocode( $address ){

	$address = urlencode( $address );
	$key = 'eo_geocode:'.md5( $address );
	$cached = get_transient( $key );

	if( $cached ){
		return $cached;
	}
	
	//Allow plug-ins to use an alternative service 
	if( $latlng = apply_filters( 'eventorganiser_remote_geocode', false, $address ) ){
		set_transient( $key, $latlng, 7*24*60*60 );
		return $latlng;
	}

	$url = "http://open.mapquestapi.com/nominatim/v1/search.php?format=json&q={$address}&addressdetails=0&limit=1";
	$geo = wp_remote_retrieve_body( wp_remote_get( $url ) );

	if( $geo && $locations = json_decode( $geo ) ){
		$location = array_pop( $locations );
		$latlng = array( 'lat' => $location->lat, 'lng' => $location->lon );
		set_transient( $key, $latlng, 7*24*60*60 );
		return $latlng;
	}

	return false;
}

/**
 * Displays an event search form, the results of which is displayed on
 * the 'events' page. 
 * 
 * The `$args` array can specify
 * * **echo**    - whether the generated HTML should be printed (default: true )
 * * **filters** - array of strings indicating the filters to use 
 *                 (default 'event_venue', 'event_category', 'date')
 * 
 * @since 1.7 
 * @param array $args An array to set the 'echo' and 'filters' properties
 * @return string The HTML generated for the booking form 
 */
function eo_get_event_search_form( $args = array() ) {

	$args = wp_parse_args( $args, array( 
		'echo' => true,
		'filters' => array( 'event_venue', 'event_category', 'date' ),
	) );
	
	$format = current_theme_supports( 'html5' ) ? 'html5' : 'xhtml';
	$format = apply_filters( 'search_form_format', $format );

	$search_form_template = locate_template( 'searchform-event.php' );

	if ( '' != $search_form_template ) {
		ob_start();
		require( $search_form_template );
		$form = ob_get_clean();
	} else {
				
		//Enqueue styling
		if( !eventorganiser_get_option( 'disable_css' ) ){
			wp_enqueue_style( 'eo_front' );
			wp_add_inline_style( 'eo_front',
				'.eo-event-search-form ul{ float: left;display: block;height: 60px;position: relative;width: 100%;}
				.eo-event-search-form li {list-style: none;float: left;display: block;position: relative;margin: 0px;width: '. min( ( 100/ ( count( $args['filters'] ) +2 ) -2), 47 ).'%;padding: 5px;box-sizing: content-box;}
				.eo-event-search-form ul li input.event-search-datepicker{float:left;}
				.eo-event-search-form li.submit{width: 5%;}
				.eo-event-search-form ul li input, .search ul li select{font-size: 13px;padding: 2px;width: 90%;}
				.eo-submit-search-btn{ margin-top: 1.5em; }'
			);
		}
		
		$form = '<form role="search" method="get" class="eo-event-search-form" action="' . esc_url( home_url( '/' ) ) . '">';
		$form .= '<input type="hidden" name="post_type" value="event" />';
		$form .= '<ul>';
	
		//Search field
		$form .= sprintf(
			'<li><label>
				%1$s
				<input type="text" name="s" class="eo-search-form-event" value="%2$s">
				</label>
			</li>',
			__( 'Event', 'eventorganiser' ),
			get_search_query()
		);
		
		
		if ( $args['filters'] ) {
			foreach ( $args['filters'] as $filter ) {
		
				switch ( $filter ):
					case 'event-category':
					case 'category':
					case 'event_category':
						$cat_objs = get_terms( 'event-category', array( 'hide_empty' => 0 ) );
						if( $cat_objs )
							$cats = array_combine( wp_list_pluck( $cat_objs, 'slug' ), wp_list_pluck( $cat_objs, 'name' ) );
						else
							$cats = array();
					
						$form .= sprintf(
							'<li class="eo-search-form-event-category">
								<label>
									%1$s
									%2$s
								</label>
							</li>',
							__( 'Category', 'eventorganiserp' ),
							eventorganiser_select_field( array(
								'name' => 'event-category',
								'echo' => false,
								'options' => $cats,
								'show_option_all' => __( 'All categories', 'eventorganiserp' ),
								'selected' => get_query_var( 'event-category' ),
							) )
						);
					break;
				
					case 'event-venue':
					case 'venue':
					case 'event_venue':
						$venue_objs = eo_get_venues();
						if ( $venue_objs ) {
							$venues = array_combine( wp_list_pluck( $venue_objs, 'slug' ), wp_list_pluck( $venue_objs, 'name' ) );
							
							$form .= sprintf(
								'<li class="eo-search-form-event-venue">
									<label>
										%1$s
										%2$s
									</label>
								</li>',
								__( 'Venue', 'eventorganiser' ),
								eventorganiser_select_field( array(
									'name' => 'event-venue',
									'echo' => false,
									'options' => $venues,
									'show_option_all' => __( 'All venues', 'eventorganiserp' ),
									'selected' => get_query_var( 'event-venue' ),
								) )
							);
						}
					break;
					
					case 'date':
						wp_enqueue_script( 'eo_pro_event_search' );
						$form .= sprintf(
							'<li class="eo-search-form-event-date">
								<label> %1$s </label>
          						<div>
									<input data-range="start" class="event-search-datepicker eo-search-form-event-date-from" type="text" name="event_start_after"  placeholder="%4$s" value="%2$s">
									<input data-range="end" class="event-search-datepicker eo-search-form-event-date-to" type="text" name="event_start_before" placeholder="%5$s" value="%3$s">
									<div class="clear"></div>
		          				</div>
							</li>',
							__( 'Select dates', 'eventorganiserp' ),
							isset( $_REQUEST['event_start_after'] ) ? $_REQUEST['event_start_after'] : '',
							isset( $_REQUEST['event_start_before'] ) ? $_REQUEST['event_start_before'] : '',
							__('From', 'eventorganiserp' ),
							__('To', 'eventorganiserp' )
					);
					break;
				endswitch;
			}
		}//endif filters
		
		/* Submit button */
		$form .= sprintf(
			'<li class="eo-submit-search">
				<button class="eo-submit-search-btn ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all">
   					<span class="ui-button-text"> %1$s </span>
				</button>
			</li>',
			__( 'Search' )
		);
		$form .= '</ul><div class="clear"></div></form>';
	
	}//endif searchform-event.php	$	

	if ( $args['echo'] )
		echo $form;
	
	return $form;
}
?>