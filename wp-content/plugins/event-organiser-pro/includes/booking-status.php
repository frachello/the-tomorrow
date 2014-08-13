<?php
/**
 * @package booking-status
 */

/**
 * Register a booking status. Do not use before init.
 *
 * Optional `$args` contents:
 *
 * * **label** - A name for the booking status. Defaults to `$status`.
 * * **include_in_confirmed** - Whether bookings of this status should be considered 'confirmed'.
 * * **reserve_spaces** - Whether tickets in this booking should be reserved, preventing another user purchasing the ticket. 
 * * **show_in_admin_all_list** - Whether to include bookings in the admin bookings view. Defaults to true.
 * * **show_in_admin_status_list** - Show in the list of statuses with post counts at the top of the edit
 *                             listings, e.g. All (12) | Confirmed (9) | Pending (1) | My Custom Status (2) ...
 * * **public** - Whether bookings of this status should be shown in the front end of the site. Defaults to false.
 *                             
 * Please note that even if `include_in_confirmed` is set to `true`, confirmation e-mails are only sent
 * when the booking has status 'confirmed'.
 * 
 * ### Example
 * <code>
 * function register_my_deposit_paid_status(){
 *    eo_register_booking_status( 'deposit-paid', array(
 *        'label' => 'Deposit paid',
 *        'reserve_spaces' => true,
 *        'include_in_confirmed' => false,
 *        'public' => true,
 *    ));
 * }
 * add_action( 'init', 'register_my_deposit_paid_status' );
 * </code>
 *
 * @since 1.6.0
 * @see register_post_status
 * @link http://codex.wordpress.org/Function_Reference/register_post_status `register_post_status()`
 * 
 * @param string $status Identifier the status.
 * @param array $args See above description.
 */ 
function eo_register_booking_status( $status, $args = array() ){
	
	$defaults = array(
			'include_in_confirmed' => false,
			'reserve_spaces' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'public' => false,
	);
	$args = wp_parse_args( $args, $defaults );
	
	$args['eventorganiser_include_in_confirmed'] = $args['include_in_confirmed'] ? true : false;
	
	if( $args['eventorganiser_include_in_confirmed'] ){
		$args['eventorganiser_reserve_spaces'] = true;
	}else{
		$args['eventorganiser_reserve_spaces'] = $args['reserve_spaces'] ? true : false;
	}
	
	$args['eventorganiser_booking_status'] = true;
	
	unset( $args['reserve_spaces'] );
	unset( $args['include_in_confirmed'] );
	
	register_post_status( $status, $args );
}

/**
 * Get all booking stati which reserve ticket spaces. 
 * @since 1.6.0
 * @return array Array of status names
 */
function eo_get_reserved_booking_statuses(){
	return get_post_stati( array( 'eventorganiser_reserve_spaces' => true ), 'names', 'and' );
}

/**
 * Get all booking stati which are considered 'confirmed'
 * @since 1.6.0
 * @return array Array of status names
 */
function eo_get_confirmed_booking_statuses(){
	return get_post_stati( array( 'eventorganiser_include_in_confirmed' => true ), 'names', 'and' );
}

/**
 * Get a list of all registered booking status objects.
 * 
 * You can optionally provide conditions to filter by with `$args` and `$operator`. 
 * `$output`  allows to toggle whether names or objects (default) are returned.
 * 
 * ### Example
 * Return a list of status names which are to be included in the 'All' filter on 
 * the booings admin page.
 * <code>
 * $statues_in_list = eo_get_booking_statuses( array( 'show_in_admin_all_list' => true ), 'names', 'and' ) )
 * </code>
 *
 * @see eo_register_booking_status
 *
 * @param array  $args     An array of key => value arguments to match against the booking status objects.
 * @param string $output   The type of output to return, either 'names' or 'objects'. 'objects' is the default.
 * @param string $operator The logical operation to perform. 'or' means only one element from the array needs 
 *                         to match; 'and' means all elements must match. The default is 'and'.
 * @return array A list of booking status names or objects
 */
function eo_get_booking_statuses( $args = array(), $output = false, $operator = 'and' ){
	
	//Get all booking status objects
	$booking_statuses = get_post_stati( array( 'eventorganiser_booking_status' => true ), false, 'and' );
	
	//Filter using $args and $operator
	$field = ('names' == $output) ? 'name' : false;
	return wp_filter_object_list( $booking_statuses, $args, $operator, $field );
}