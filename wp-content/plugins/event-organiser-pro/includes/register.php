<?php


add_action( 'admin_print_styles-event_page_bookings', 'eventorganiser_print_admin_style' );

add_action( 'init', 'eventorganiser_pro_register_cpt', 1 );
add_action( 'switch_blog', 'eventorganiser_pro_register_cpt' );

/**
 * Registers the eo_booking CPT, 'confirmed' status and $wpdb->eo_booking_tickets custom table
 * Hooked onto init and switch_blog
 *
 * @ignore
 * @access private
 * @since 1.0
 */
function eventorganiser_pro_register_cpt() {
	global $wpdb;

	/* Register custom table */
	$wpdb->eo_booking_tickets = "{$wpdb->prefix}eo_booking_tickets";

	/* Register 'eo_booking' post type*/
	$booking_labels = array(
		'name' => 'Bookings',
		'singular_name' => 'Booking',
		'add_new' => __( 'Add New', 'eventorganiserp' ),
		'add_new_item' => __( 'Add New Booking', 'eventorganiserp' ),
		'edit_item' => __( 'Edit Bookings', 'eventorganiserp' ),
		'new_item' => __( 'New Bookings', 'eventorganiserp' ),
		'all_items' => __( 'Bookings', 'eventorganiserp' ),
		'view_item' => __( 'View Booking', 'eventorganiserp' ),
		'search_items' => __( 'Search Bookings', 'eventorganiserp' ),
		'not_found' =>  __( 'No Bookings found', 'eventorganiserp' ),
		'not_found_in_trash' => __( 'No Bookings found in Trash', 'eventorganiserp' ),
		'menu_name' => __( 'Bookings', 'eventorganiserp' )
	);

	$booking_args = array(
		'labels' => $booking_labels,
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => false,
		'show_in_menu' => false,
		'query_var' => true,
		'rewrite' =>false,
		'capability_type' => 'post',
		'has_archive' => false,
		'hierarchical' => false,
		'supports' => array( 'title', 'editor' ),
	);
	register_post_type( 'eo_booking', apply_filters( 'eventorganiser_booking_properties', $booking_args ) );
	
	$confirmed_args =array(
		'label' => __( 'Confirmed', 'eventorganiserp' ),
		'label_count' => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>' ),
		'exclude_from_search' => false,
		'public' => true,
		'include_in_confirmed' => true,
	);
	eo_register_booking_status( 'confirmed', $confirmed_args );

	//Pending
	$pending_args = (array) get_post_status_object( 'pending') + array( 'include_in_confirmed' => false, 'reserve_spaces' => eventorganiser_pro_get_option( 'reserve_pending_tickets' ) );
	eo_register_booking_status( 'pending', $pending_args );
	
	//Cancelled
	$cancelled_args =array(
			'label' => __( 'Cancelled', 'eventorganiserp' ),
			'label_count' => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>' ),
			'public' => false,
			'show_in_admin_all_list' => false,
			'reserve_spaces' => false,
			'include_in_confirmed' => false,
	);
	eo_register_booking_status( 'cancelled', $cancelled_args );
	

	/* Register 'eo_booking' post type*/
	$booking_form_labels = array(
		'name' => 'Booking Forms',
		'singular_name' => 'Booking Form',
		'add_new' => __( 'Add New', 'eventorganiserp' ),
		'add_new_item' => __( 'Add New Booking Form', 'eventorganiserp' ),
		'edit_item' => __( 'Edit Booking Forms', 'eventorganiserp' ),
		'new_item' => __( 'New Booking Forms', 'eventorganiserp' ),
		'all_items' => __( 'Booking Forms', 'eventorganiserp' ),
		'view_item' => __( 'View Booking Form', 'eventorganiserp' ),
		'search_items' => __( 'Search Booking Forms', 'eventorganiserp' ),
		'not_found' =>  __( 'No Booking Forms found', 'eventorganiserp' ),
		'not_found_in_trash' => __( 'No Booking Forms found in Trash', 'eventorganiserp' ),
		'menu_name' => __( 'Booking Forms', 'eventorganiserp' )
	);

	$booking_form_args = array(
		'labels' => $booking_form_labels,
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => false,
		'show_in_menu' => false,
		'query_var' => true,
		'rewrite' =>false,
		'capability_type' => 'post',
		'has_archive' => false,
		'hierarchical' => false,
		'supports' => array(),
	);
	register_post_type( 'eo_booking_form', $booking_form_args );
}


/**
 * Register jQuery scripts and CSS files for the front end
 * Hooked onto wp_enqueue_scripts
 *
 * @since 1.0.0
 * @ignore
 * @access private
 */
function eventorganiser_pro_register_scripts( $hook ) {
	global $wp_locale;

	$version = EVENT_ORGANISER_PRO_VER;

	$ext = (defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG) ? '' : '.min';

	/* WP-JS-Hooks */
	wp_register_script( 'eo-wp-js-hooks', EVENT_ORGANISER_PRO_URL."js/event-manager{$ext}.js",array('jquery'),$version,true);
	
	wp_register_style( 'eo_pro_frontend', EVENT_ORGANISER_PRO_URL."css/frontend{$ext}.css", array(), $version );

	//Backwards compatibility for 3.3-3.4.
	if ( !eventorganiser_blog_version_is_atleast( '3.5' ) ) {
		wp_register_script( 'underscore', EVENT_ORGANISER_PRO_URL."js/vendor/underscore.min.js", array( 'jquery' ), '1.6.0' );
		wp_register_script( 'backbone', EVENT_ORGANISER_PRO_URL."js/vendor/backbone.min.js", array( 'jquery','underscore' ), '1.1.2' );
	}

	wp_register_script( 'eo_pro_occurrence_picker', EVENT_ORGANISER_PRO_URL."js/occurrence-picker{$ext}.js", array(
			'jquery',
			'jquery-ui-datepicker',
			'eo-wp-js-hooks',
			'underscore',
			'backbone'
		), $version );
	wp_register_script( 'eo_pro_event_search', EVENT_ORGANISER_PRO_URL."js/event-search{$ext}.js", array(
			'jquery',
			'jquery-ui-datepicker'
		), $version );

	/* Add js variables to frontend script */
	wp_localize_script( 'jquery-ui-datepicker', 'EO_Pro_DP', array(
			'adminajax'=>admin_url( 'admin-ajax.php' ),
			'locale'=>array(
				'locale' => substr( get_locale(), 0, 2 ),
				'monthNames'=>array_values( $wp_locale->month ),
				'monthAbbrev'=>array_values( $wp_locale->month_abbrev ),
				'dayNames'=>array_values( $wp_locale->weekday ),
				'dayAbbrev'=>array_values( $wp_locale->weekday_abbrev ),
				//Allow themes to over-ride juqery ui styling and not use images
				'nextText' => '>',
				'prevText' => '<'
			)
		) );
	
	/* Fullcalendar filtes*/
	wp_register_script( 'eo-fullcalendar-pro-filters', EVENT_ORGANISER_PRO_URL."js/fullcalendar-filters{$ext}.js",array('jquery'),$version,true);
	
	//Remove this check in 1.3 and increment minimum requirement to EOv2.1
	if( function_exists( 'eventorganiser_append_dependency' ) ){
		eventorganiser_append_dependency( 'eo_front','eo-fullcalendar-pro-filters' );
	
		eo_localize_script( 'eo_front', array(
				'locale'=>array(
							'view_all_countries' => __('View all countries','eventorganiserp'),
							'view_all_states' => __('View all states','eventorganiserp'),
							'view_all_cities' => __('View all cities','eventorganiserp'),
							),
				'fullcal' =>array(
					'countries' => eo_get_venue_countries(),
					'states' => eo_get_venue_states(),
					'cities' => eo_get_venue_cities()
				)			
		));
		
	
	}
}

/**
 * Checks if the meta cap is 'manage_eo_booking', and if so maps its to the necessary primitive
 * caps.
 * 
 * Primitive caps required are:
 * * `manage_eo_bookings`, if the user is organiser of the event for which the booking is made
 * * `manage_others_eo_bookings`, if they're not.
 * 
 * WP automatically adds the meta cap into the primitive caps array, so this is removed.
 * 
 * @since 1.5
 * @ignore
 */
function eventorganiser_pro_map_meta_cap( $primitive_caps, $meta_cap, $user_id, $args ){
	
	if( 'manage_eo_booking' !== $meta_cap )
		return $primitive_caps;
		
	$booking_id = (int) $args[0];
	$event_id = eo_get_booking_meta( $booking_id, 'event_id' );
	$event = get_post( $event_id );
	
	//Remove meta-cap
	$primitive_caps = array_diff( $primitive_caps, array( 'manage_eo_booking' ) );
	
	if( $event &&  ( $user_id === intval( $event->post_author ) ) ){
		//User is event's organiser.
		$primitive_caps[] = 'manage_eo_bookings';	
	}else{
		//User is not event's organiser
		$primitive_caps[] = 'manage_others_eo_bookings';
	}
	
	return $primitive_caps;
}
add_filter( 'map_meta_cap', 'eventorganiser_pro_map_meta_cap', 10, 4 );


/**
 * Register jQuery scripts and CSS files for admin
 * Hooked onto admin_enqueue_scripts
 *
 * @since 1.0.0
 * @ignore
 * @access private
 */
function eventorganiser_pro_admin_register_scripts( ) {

	global $wp_locale;
	
	$version = EVENT_ORGANISER_PRO_VER;
	$ext = (defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG) ? '' : '.min';
	
	/* Edit event, ticket manager */
	wp_register_script( 'eo_pro_event',  EVENT_ORGANISER_PRO_URL."admin/js/event-pro{$ext}.js", array(
			'jquery',
			'eo-wp-js-hooks',
			'eo-inline-help',
			'jquery-ui-datepicker',
			'jquery-ui-dialog',
			'jquery-ui-widget',
			'jquery-ui-position', 
			'jquery-ui-core',
			'eo_event'
		), $version, true );
		
	wp_localize_script( 'eo_pro_event', 'eo_pro', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'wpversion' => get_bloginfo('version'),
		'startday'=>intval(get_option('start_of_week')),
		'format'=> eventorganiser_php2jquerydate( eventorganiser_get_option('dateformat') ),
		'is24hour' => eventorganiser_blog_is_24(),
		'location'=>get_option('timezone_string'),
		'locale'=>array(
			'isrtl' => $wp_locale->is_rtl(),
			'monthNames'=>array_values($wp_locale->month),
			'monthAbbrev'=>array_values($wp_locale->month_abbrev),
			'dayAbbrev'=>array_values($wp_locale->weekday_abbrev),
			'showDates' => __( 'Show dates', 'eventorganiser' ),
			'hideDates' => __( 'Hide dates', 'eventorganiser' ),
			'weekDay'=>$wp_locale->weekday,
			'meridian' => array( $wp_locale->get_meridiem('am'), $wp_locale->get_meridiem('pm') ),
			'hour'=>__('Hour','eventorganiser'),
			'minute'=>__('Minute','eventorganiser'),
		)
	));
	
	/* Venue custom fields */
	wp_register_script( 'eo-venue-custom-fields',  EVENT_ORGANISER_PRO_URL."admin/js/custom-fields-venues{$ext}.js", array(
			'jquery',
			'wp-lists',
		), $version, true );

	if ( eventorganiser_blog_version_is_atleast( '3.5' ) ) {
		wp_register_script( 'eo-venue-featured-image',  EVENT_ORGANISER_PRO_URL."admin/js/venue-featured-image{$ext}.js", array(
				'jquery',
			), $version, true );
	}else {
		wp_register_script( 'eo-venue-featured-image',  EVENT_ORGANISER_PRO_URL."admin/js/venue-featured-image-pre-3-5{$ext}.js", array(
				'jquery',
			), $version, true );
	}
	
	/* Nested sortable */
	wp_register_script( 'eo-jquery-nested-sortable',  EVENT_ORGANISER_PRO_URL."js/vendor/jquery.nested-sortable.js", array(
			'jquery',
			'jquery-ui-sortable',
	), $version, true );
	
	/* Booking form customiser */
	wp_register_script( 'eo-booking-form',  EVENT_ORGANISER_PRO_URL."admin/js/booking-form{$ext}.js", array(
			'jquery',
			'backbone',
			'underscore',
			'eo_qtip2',
			'eo-wp-js-hooks',
			'jquery-ui-draggable',
			'eo-jquery-nested-sortable',
			'jquery-ui-droppable',
			'jquery-ui-mouse',
			'jquery-ui-button',
			'jquery-ui-widget',
		), $version, true );

	/* JS for editing booking */
	wp_register_script( 'eo-pro-edit-booking',  EVENT_ORGANISER_PRO_URL."admin/js/edit-booking{$ext}.js", array(
			'jquery',
			'jquery-ui-datepicker'
		), $version, true );

	/* JS for bulk edit/email booking */
	wp_register_script( 'eo_inline_booking',  EVENT_ORGANISER_PRO_URL."admin/js/inline-email-bookees{$ext}.js", array( 'jquery-ui-autocomplete'), $version );

	/* Shortcode Buttons */
	wp_register_script( 'eo_pro_shortcode_button', EVENT_ORGANISER_PRO_URL."admin/js/shortcode-buttons{$ext}.js", array( 'jquery-ui-autocomplete' ) );

	/* Settings page js */
	wp_register_script( 'eo-pro-settings', EVENT_ORGANISER_PRO_URL."admin/js/settings{$ext}.js", array( 'jquery' ) );
	
	/* Plug-in admin css */
	wp_register_style( 'eo_pro_admin',  EVENT_ORGANISER_PRO_URL."admin/css/admin{$ext}.css", array(), $version );
	
	/* Booking form customiser css */
	wp_register_style( 'eo-booking-form-customiser',  EVENT_ORGANISER_PRO_URL."admin/css/booking-form-customiser{$ext}.css", array(), $version );
	
	
	if ( ( defined( 'MP6' ) && MP6 ) || version_compare( '3.8-beta-1', get_bloginfo( 'version' ) ) <= 0 ){
		$style = '.eo-bfc-form-element .postbox {background: #EEE;}
			.eo-bfc-form-element .postbox .hndle { background: #E9E9E9; }
			#wp-email_tickets_message-editor-container{ background:none;border:none; }
			input[type="checkbox"]:checked:disabled::before {
				color: #DFDFDF;
			}
			.mceIcon.mce_eo_button:before{font-family: "dashicons" !important;content: "\f145"}
			.mceIcon.mce_eo_button img{display:none!important;}
			.mce-i-eo-calendar:before{font: normal 20px/1 "dashicons" !important;content: "\f145"}';

		//See trac ticket: https://core.trac.wordpress.org/ticket/24813
		if( !eventorganiser_blog_version_is_atleast( '3.7.0' ) && ( !defined( 'SCRIPT_DEBUG' ) || !SCRIPT_DEBUG ) ){
			$style = "<style>$style</style>";
		}
		wp_add_inline_style( 'eo_pro_admin', $style );
		
	}else{
		$style = '#eo-bfc-header,#eo-bfc-footer{background: #F1F1F1;}
			#eo-bfc-footer{ border-top:1px solid #CCC;}
			.eo-dashicon:before{display:none!important;}';
		
		//See trac ticket: https://core.trac.wordpress.org/ticket/24813
		if( !eventorganiser_blog_version_is_atleast( '3.7.0' ) && ( !defined( 'SCRIPT_DEBUG' ) || !SCRIPT_DEBUG ) ){
			$style = "<style>$style</style>";
		}
		wp_add_inline_style( 'eo_pro_admin', $style );
	}
}
	
function eventorganiser_print_admin_style( $hook ){

	echo "<style type='text/css'>\n";
	echo '#bulk-email-titles div a {background: url('.admin_url("images/xit.gif").') no-repeat;';
	echo '#bulk-email-titles div a:hover {background: url('.admin_url('images/xit.gif').') no-repeat -10px 0;}';
	echo "</style>\n";
}

function eventorganiser_pro_admin_enqueue_scripts( $hook ) {
	global $post;
	
	wp_enqueue_style( 'eo_pro_admin' );

	/* Conditionally load some scripts/styles on particular pages */
	if ( ( 'post-new.php' == $hook || 'post.php' == $hook ) &&  'event' == $post->post_type ) {
		
		//Load template for ticket-row
		ob_start();
		include( EVENT_ORGANISER_PRO_DIR . 'admin/includes/ticket-row-view.php' );
		$template = ob_get_contents();
		ob_end_clean();
		wp_localize_script( 'eo_pro_event', 'eo_ticket_table_template', $template );
		
		wp_enqueue_script( 'eo_pro_event' );
	}elseif ( 'event_page_bookings' == $hook ) {
		wp_enqueue_script( 'eo_inline_booking' );
	}elseif ( 'settings_page_event-settings' == $hook ) {
		wp_enqueue_style( 'eventorganiser-jquery-ui-style' );
		wp_enqueue_script( 'eo-pro-settings' );
	}
}

/**
 * Adds link to the plug-in settings on the settings page
 *
 * @since 1.0
 * @ignore
 * @access private
 */
function eventorganiser_pro_plugin_settings_link($links, $file) {

	if( $file == 'event-organiser-pro/event-organiser-pro.php' ) {
		/* Insert the link at the end*/
		$links['settings'] = sprintf('<a href="%s"> %s </a>',
				admin_url('options-general.php?page=event-settings&tab=bookings'),
				__('Settings','eventorganiser')
		);
	}
	return $links;
}
add_filter('plugin_action_links', 'eventorganiser_pro_plugin_settings_link', 10, 2);

/**
 * Perform database and WP version checks. Display appropriate error messages.
 * Triggered on update.
 *
 * @since 1.0
 * @ignore
 * @access private
 */
function eventorganiser_pro_db_checks(){
	global $wpdb;

	//Check tables exist
	$table_errors = array();
	if( $wpdb->get_var("show tables like '{$wpdb->eo_booking_tickets}'") != $wpdb->eo_booking_tickets ){ 
		printf( 
			'<div class="error"	><p>%s</p></div>',
			__( 'There has been an error with Event Organiser Pro. The <code>eo_booking_tickets</code> table is missing. Please try re-installing the plugin.', 'eventorganiserp' )
		);
	}
}

function eventorganiser_pro_register_stack( $stacks ){
	$stacks[] = EVENT_ORGANISER_PRO_DIR . 'templates';
	return $stacks;
}
add_filter( 'eventorganiser_template_stack', 'eventorganiser_pro_register_stack' );

function eventorganiser_debugger_check_pro_tables( $debugger ){
	$debugger->set_db_tables( 'eo_booking_tickets' );
} 
add_action( 'eventorganiser_debugger_setup', 'eventorganiser_debugger_check_pro_tables' );


function _eventorganiser_pro_set_edit_link( $link, $post_id ){
	if( 'eo_booking' == get_post_type( $post_id ) ){
		$link = eventorganiser_edit_booking_url( $post_id );		
	}
	return $link;
}
add_filter( 'get_edit_post_link', '_eventorganiser_pro_set_edit_link', 10, 2 );

?>