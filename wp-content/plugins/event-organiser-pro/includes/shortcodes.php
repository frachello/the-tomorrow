<?php
/**
*/
function _eventorganiser_events_attending_shortcode_handler( $atts ) {
	
	global $eventorganiser_user_attending;
	
	$atts = wp_parse_args( $atts, array(
			'posts_per_page' => 20,//bookings per page
			'order' => 'DESC',
			'event_start_after' => 'now',
	));
	
	//Set required values
	$atts['post_type'] = 'event';
	$atts['suppress_filters'] = false;
	$atts['bookee_id'] = get_current_user_id();
	$atts['paged'] = get_query_var('paged');
	$atts['context'] = 'eo-events-attending-shortcode';
	
	
	// Retrieve bookings for the current user
	$eventorganiser_user_attending = new WP_Query( $atts );

	ob_start();
	eo_locate_template( 'events-user-attending.php', true );
	$html = ob_get_contents();
	ob_end_clean();	
	
	return $html;

}
add_shortcode( 'events_attending' , '_eventorganiser_events_attending_shortcode_handler' );

/**
 */
function _eventorganiser_booking_history_shortcode_handler( $atts = array() ) {

	global $eventorganiser_booking_history;

	$atts = wp_parse_args( $atts, array(
			'posts_per_page' => 20,//bookings per page
			'order' => 'DESC',//Most recent bookings first
	));
	
	$atts['bookee_id'] = get_current_user_id();
	$atts['paged'] = get_query_var('paged');
	$atts['context'] = 'eo-user-booking-history-shortcode';
	
	if( !empty( $atts['bookee_can_cancel'] ) && is_user_logged_in() ){
		add_action( 'eventorganiser_booking_history_header_after', '_eo_booking_history_cancel_column', 20 );
		add_action( 'eventorganiser_booking_history_header_row_end', '_eo_booking_history_cancel_cell', 20 );
		add_action( 'eventorganiser_pre_booking_history', '_eo_booking_history_user_feedback' );
	}

	// Retrieve bookings for the current user
	$eventorganiser_booking_history = eventorganiser_get_bookings( $atts, true );

	if( $user_id = get_current_user_id() ){
		ob_start();
		eo_locate_template( 'event-booking-history.php', true );
		$html = ob_get_contents();
		ob_end_clean();
	}else{
		$html = '';
	}
	
	if( !empty( $atts['bookee_can_cancel'] ) ){
		remove_action( 'eventorganiser_booking_history_header_after', '_eo_booking_history_cancel_column', 20 );
		remove_action( 'eventorganiser_booking_history_header_row_end', '_eo_booking_history_cancel_cell', 20 );
		remove_action( 'eventorganiser_pre_booking_history', '_eo_booking_history_user_feedback' );
	}

	return $html;

}
add_shortcode( 'booking_history' , '_eventorganiser_booking_history_shortcode_handler' );


function _eo_booking_history_cancel_column(){
	printf( '<th class="eo-booking-cancel">%s</th>',  __('Cancel', 'eventorganiserp') );	
}

function _eo_booking_history_cancel_cell( $booking_id ){
	
	$status = eo_get_booking_status( $booking_id );
	$bookee = eo_get_booking_meta( $booking_id, 'bookee' );
	$date = eo_get_booking_event_start_date( $booking_id, DATETIMEOBJ );
	$now = new DateTime( 'now', eo_get_blog_timezone() );
	
	if( 'cancelled' == $status || get_current_user_id() != $bookee || $date <= $now ){
		return;
	}
	
	$return = (int) $GLOBALS['wp_the_query']->post->ID; //get_the_ID() won't work here!
	
	$url = add_query_arg(array(
			'eo-action' => 'cancel-booking',
			'booking_id' => $booking_id,
			'return' => $return
	), get_permalink( get_the_ID() ) );
	
	$url = wp_nonce_url( $url, 'cancel-booking-'.$booking_id.'-'.get_current_blog_id().'-'.$return, 'n' );
	
	printf( 
		'<td class="eo-booking-cancel">
			<a href="%s">%s</a>
		</td>', 
		$url, 
		__('Cancel booking', 'eventorganiserp') 
	); 
}

function _eo_booking_history_user_feedback(){
	
	if( !empty( $_GET['success'] ) ){
		if( !empty( $_GET['n'] ) && wp_verify_nonce( $_GET['n'], 'cancelled-'.$_GET['booking_id'] ) ){
			printf(
				'<div class="eo-booking-cancellation-success"><p>%s</p></div>',
				sprintf( __( 'Booking #%s has been successfully cancelled.', 'eventorganiserp' ), $_GET['booking_id'] )
			);
			wp_enqueue_style( 'eo_pro_frontend' );
		}
		
	}elseif( !empty( $_GET['failure'] ) ){
		if( !empty( $_GET['n'] ) && wp_verify_nonce( $_GET['n'], 'failed-'.$_GET['booking_id'] ) ){
			printf(
				'<div class="eo-booking-cancellation-failure"><p>%s</p></div>',
				sprintf( __( 'Booking #%s could not be cancelled.', 'eventorganiserp' ), $_GET['booking_id'] )
			);
			wp_enqueue_style( 'eo_pro_frontend' );
		}
	}
	
}



/**
 * The shortcode handler for the [event_search] shortcode.
*
* Currently the only attribute accepted is 'filters' which takes a
* comma seperated list specifying which filters are available to the user:
*
* * event-category
* * event-venue
* * state
* * city
* * date
*
* @access private
* @ignore
*
* @param unknown $atts - Array of attributes for the shortcode
* @return string HTML for the search form and results.
*/
function _eventorganiser_event_search_shortcode_handler( $atts ) {
	$atts = wp_parse_args( $atts, array(
			'filters' => 'event_venue,event_category,date',
			'posts_per_page' => 10,
	) );

	$filters = array_unique( explode( ',', $atts['filters'] ) );
	$input = isset( $_REQUEST['eo_search'] ) ? $_REQUEST['eo_search'] : array();

	//Enqueue styling
	if( !eventorganiser_get_option( 'disable_css' ) ){
		wp_enqueue_style( 'eo_front' );
		wp_add_inline_style( 'eo_front',
			'.eo-event-search ul{ float: left;display: block;height: 60px;position: relative;width: 100%;}
			.eo-event-search li {list-style: none;float: left;display: block;position: relative;margin: 0px;width: '. min( ( 100/ ( count( $filters ) +1 ) -2), 47 ).'%;}
			.eo-event-search ul li input.event-search-datepicker{float:left;}
			.eo-event-search li.submit{width: 5%;}
			.eo-event-search ul li input, .search ul li select{font-size: 13px;padding: 2px;width: 90%;}
			p.submit button {width: 35px;height: 49px;outline: 0;border: 0;cursor: pointer;text-indent: -5000px;display: block;}'
		);
	}
	
	//The search bar. TODO remove generic 'search' class (after/on 1.6)
	$html = '<div class="eo-event-search search"><form action="'.get_permalink().'" method="get">';
	$html .= '<ul>';
	$html .= sprintf( '<input type="hidden" name="page_id" value="%d">', get_the_ID() );
	$html .= sprintf(
			'<li class="%1$s"><label for="%2$s">%4$s</label><p><input type="text" name="eo_search[%3$s]" id="%2$s" value="%5$s"></p></li>',
			'show',
			'event-search',
			's',
			__( 'Event', 'eventorganiser' ),
			isset( $input['s'] ) ? $input['s'] : ''
	);

	if ( $filters ) {
		foreach ( $filters as $filter ) {

			switch ( $filter ):
			case 'event-category':
			case 'category':
			case 'event_category':
				$cat_objs = get_terms( 'event-category', array( 'hide_empty' => 0 ) );
			if( $cat_objs )
				$cats = array_combine( wp_list_pluck( $cat_objs, 'slug' ), wp_list_pluck( $cat_objs, 'name' ) );
			else
				$cats = array();
			$html .= sprintf(
					'<li class="%1$s"><label for="%2$s">%3$s</label><p>%4$s</p></li>',
					'category event-category',
					'event-category',
					__( 'Category', 'eventorganiserp' ),
					eventorganiser_select_field( array(
							'name' => 'eo_search[event-category]',
							'id' => 'event-category',
							'echo' => false,
							'options' => $cats,
							'show_option_all' => __( 'All categories', 'eventorganiserp' ),
							'selected' => isset( $input['event-category'] ) ? $input['event-category'] : '',
					) )
			);
			break;
			case 'event-venue':
			case 'venue':
			case 'event_venue':
				$venue_objs = eo_get_venues();
				if ( $venue_objs ) {
					$venues = array_combine( wp_list_pluck( $venue_objs, 'slug' ), wp_list_pluck( $venue_objs, 'name' ) );
					$html .= sprintf(
							'<li class="%1$s"><label for="%2$s">%3$s</label><p>%4$s</p></li>',
							'venue event-venue',
							'event-venue',
							__( 'Venue', 'eventorganiser' ),
							eventorganiser_select_field( array(
									'name' => 'eo_search[event-venue]',
									'id' => 'event-venue',
									'echo' => false,
									'options' => $venues,
									'show_option_all' => __( 'All venues', 'eventorganiserp' ),
									'selected' => isset( $input['event-venue'] ) ? $input['event-venue'] : '',
							) )
					);
				}
				break;
			case 'city':
				$cities = eo_get_venue_cities();
				if ( $cities ) {
					$cities = array_combine( $cities, $cities );
					$html .= sprintf(
							'<li class="%1$s"><label for="%2$s">%3$s</label><p>%4$s</p></li>',
							'city event-city',
							'event-city',
							__( 'City', 'eventorganiser' ),
							eventorganiser_select_field( array(
									'name' => 'eo_search[venue_query][0][value]',
									'id' => 'event-venue',
									'echo' => false,
									'options' => $cities,
									'show_option_all' => __( 'All cities', 'eventorganiserp' ),
									'selected' => isset( $input['venue_query']['0']['value'] ) ? $input['venue_query']['0']['value'] : '',
							) )
					);
					$html .= '<input type="hidden" name="eo_search[venue_query][0][key]" value="_city">';
				}
				break;
			case 'state':
				$states = eo_get_venue_states();
				if ( $states ) {
					$states = array_combine( $states, $states );
					$html .= sprintf(
							'<li class="%1$s"><label for="%2$s">%3$s</label><p>%4$s</p></li>',
							'state event-state',
							'event-state',
							__( 'State', 'eventorganiser' ),
							eventorganiser_select_field( array(
									'name' => 'eo_search[venue_query][1][value]',
									'id' => 'event-venue',
									'echo' => false,
									'options' => $states,
									'show_option_all' => __( 'All states', 'eventorganiserp' ),
									'selected' => isset( $input['venue_query']['1']['value'] ) ? $input['venue_query']['1']['value'] : '',
							) )
					);
					$html .= '<input type="hidden" name="eo_search[venue_query][1][key]" value="_state">';
				}
				break;
			case 'country':
				$countries = eo_get_venue_countries();
				if ( $countries ) {
					$countries = array_combine( $countries, $countries );
					$html .= sprintf(
							'<li class="%1$s"><label for="%2$s">%3$s</label><p>%4$s</p></li>',
							'country event-country',
							'event-country',
							__( 'Country', 'eventorganiser' ),
							eventorganiser_select_field( array(
									'name' => 'eo_search[venue_query][2][value]',
									'id' => 'event-venue',
									'echo' => false,
									'options' => $countries,
									'show_option_all' => __( 'All countries', 'eventorganiserp' ),
									'selected' => isset( $input['venue_query']['2']['value'] ) ? $input['venue_query']['2']['value'] : '',
							) )
					);
					$html .= '<input type="hidden" name="eo_search[venue_query][2][key]" value="_country">';
				}
				break;
			case 'date':
				wp_enqueue_script( 'eo_pro_event_search' );
				$html .= sprintf(
						'<li class="%1$s"><label for="%2$s">%4$s</label>
          						<div>
									<input data-range="start" class="event-search-datepicker eo-search-form-event-date-from" type="text" name="eo_search[event_start_after]"  placeholder="%7$s" id="%2$s" value="%5$s">
									<input data-range="end" class="event-search-datepicker eo-search-form-event-date-to" type="text" name="eo_search[event_start_before]" placeholder="%8$s" id="%3$s" value="%6$s">
									<div class="clear"></div>
		          				</div>
								</li>',
						'date event-date',
						'event_search_date_from',
						'event_search_date_to',
						__( 'Select dates', 'eventorganiserp' ),
						isset( $input['event_start_after'] ) ? $input['event_start_after'] : '',
						isset( $input['event_start_before'] ) ? $input['event_start_before'] : '',
						__('From', 'eventorganiserp' ),
						__('To', 'eventorganiserp' )
				);
				break;
				endswitch;
		}
	}

	/* Submit button */
	$html .= sprintf(
			'<li class="%1$s"><label for="%2$s">%3$s</label>
				<button id="%2$s" class="ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all">
   					<span class="ui-button-text"> %4$s </span>
				</button>
				</li>',
			'submit',
			'eo-submit-search',
			'</br>',
			__( 'Search' )
	);
	$html .= '</ul><div class="clear"></div></form></div>';

	$query = array_merge(
			array(
				'paged' => max( 1, get_query_var( 'paged' ) ),
			), //Defaults 
			$atts, //Shortcode-specified
			$input, //User submitted
			array(
				'context'	=> 'eo-events-search-shortcode',
				'post_type' => 'event',
				'suppress_filters' => false,
			)//must be set
	);

	if( empty( $query['venue_query'][0]['value'] ) ){
		unset( $query['venue_query'][0]);
	}
	if( empty( $query['venue_query'][1]['value'] ) ){
		unset( $query['venue_query'][1]);
	}
	if( empty( $query['venue_query'][2]['value'] ) ){
		unset( $query['venue_query'][2]);
	}
	
	global $eo_event_loop;
	$eo_event_loop = new WP_Query( $query );

	ob_start();
	$template_file = eo_locate_template( array( 'search-event-list.php', 'event-list.php' ), true, false );
	$html .= ob_get_contents();
	ob_end_clean();

	wp_reset_postdata();
	return $html;
}
add_shortcode( 'event_search' , '_eventorganiser_event_search_shortcode_handler' );


function _eventorganiser_event_map_shortcode_handler( $atts = array() ){
	
	$atts = array_merge( array(
		'numberposts' => 10,
	), $atts );
	
	$taxs = array('category','tag','venue');
	foreach ($taxs as $tax){
		if(isset($atts['event_'.$tax])){
			$atts['event-'.$tax]=	$atts['event_'.$tax];
			unset($atts['event_'.$tax]);
		}
	}
	
	if((isset($atts['venue']) &&$atts['venue']=='%this%') ||( isset($atts['event-venue']) && $atts['event-venue']=='%this%' )){
		if( eo_get_venue_slug() ){
			$atts['event-venue']=  eo_get_venue_slug();
		}else{
			unset($atts['venue']);
			unset($atts['event-venue']);
		}
	}
	
	//Cast options as boolean:
	$bool_options = array('tooltip','scrollwheel','zoomcontrol','rotatecontrol','pancontrol','overviewmapcontrol','streetviewcontrol','draggable','maptypecontrol');
	foreach( $bool_options as $option  ){
		if( isset( $atts[$option] ) )
			$atts[$option] = ( $atts[$option] == 'false' ? false : true );
	}
	
	if( isset( $atts['users_events'] ) && strtolower( $atts['users_events'] ) == 'true' ){
		$atts['bookee_id'] = get_current_user_id();
	}
	
	$events = eo_get_events( $atts );
	$venues = array();
	
	if( !$events ){
		return;
	}

	global $eventorganiser_event_map_venue_tooltip;
	
	foreach( $events as $event ){
		$venue_id = (int) eo_get_venue( $event->ID );
		
		if( $venue_id ){
			$venues[] = $venue_id;
					
			if( !isset( $eventorganiser_event_map_venue_tooltip[$venue_id] ) )
				$eventorganiser_event_map_venue_tooltip[$venue_id] = array();
			
			$eventorganiser_event_map_venue_tooltip[$venue_id][] = $event;
		} 		
	}
	
	$venues = array_unique( $venues );

	add_filter( 'eventorganiser_venue_tooltip', '_eventorganiser_event_map_tooltip_content', 100, 2 );
	
	if( $venues )
		$return = eo_get_venue_map( $venues, $atts );
	else
		$return = false;
	
	remove_filter( 'eventorganiser_venue_tooltip', '_eventorganiser_event_map_tooltip_content', 100 );
	unset( $eventorganiser_event_map_venue_tooltip );
	
	return $return;
}
add_shortcode( 'event_map' , '_eventorganiser_event_map_shortcode_handler' );

function _eventorganiser_event_map_tooltip_content( $content, $venue_id ){
	global $eventorganiser_event_map_venue_tooltip;

	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );
	
	$events = $eventorganiser_event_map_venue_tooltip[$venue_id];

	$tooltip_content = '<h4 class="eo-event-map-title">' . eo_get_venue_name( $venue_id ) . '</h4>';
	$tooltip_content .= '<p class="eo-event-map-address">' . implode( ', ', array_filter( eo_get_venue_address( $venue_id ) ) ) . '</p>';
	
	$tooltip_content .= '<ul class="eo-event-map-event-list">';
	foreach( $events as $event ){
		$format = eo_is_all_day( $event->ID ) ? $date_format : $date_format . ' ' . $time_format;
		$tooltip_content .= sprintf(
			'<li class="eo-event-map-event"><a href="%s" title="%s">%s</a> %s %s </li>',
			get_permalink( $event->ID ),
			esc_attr( get_the_title( $event->ID ) ),
			get_the_title( $event->ID ),
			__( 'on', 'eventorganiser' ),
			eo_get_the_start( $format, $event->ID, null, $event->occurrence_id )
		);
	
	}
	$tooltip_content .= '</ul>';

	return apply_filters( 'eventorganiser_event_map_tooltip', $tooltip_content, $venue_id, $events );
}


function _eventorganiser_event_booking_form_shortcode_handler( $atts = array() ){
	$atts = shortcode_atts( array(
			'event_id' => get_the_ID(),
	), $atts );
	$html = eo_get_booking_form( $atts['event_id'] );
	return $html;
}
add_shortcode( 'event_booking_form' , '_eventorganiser_event_booking_form_shortcode_handler' );
