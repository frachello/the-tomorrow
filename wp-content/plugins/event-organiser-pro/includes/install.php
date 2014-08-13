<?php
/**
 * Install routine
 *
 * @since 1.0
 * @access private
 * @ignore
 */
 function eventorganiser_pro_install( $is_networkwide = false ){
    global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Is this multisite and did the user click network activate?
    $is_multisite = ( function_exists('is_multisite') && is_multisite() );

    if ($is_multisite && $is_networkwide) {
		//Get the current blog so we can return to it.
		$current_blog_id = get_current_blog_id();

		//Get a list of all blogs.
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		if( $blog_ids ){
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
	            		eventorganiser_pro_site_install();
	       		 }
			switch_to_blog( $current_blog_id );
		}else{
			eventorganiser_pro_site_install();
		}
    }else{
		eventorganiser_pro_site_install();
	}

}

function eventorganiser_pro_site_install(){

	global $wpdb;
	global $charset_collate;

	eventorganiser_pro_register_cpt();

	$sql_booking_tickets_table = "CREATE TABLE ". $wpdb->eo_booking_tickets." (
		booking_ticket_id bigint(20) NOT NULL AUTO_INCREMENT,
		booking_id bigint(20) NOT NULL,
		ticket_id bigint(20) NOT NULL,
		ticket_name text,
		ticket_reference varchar(255),
		ticket_price  float default NULL,
		event_id bigint(20) NOT NULL,
		occurrence_id bigint(20) NOT NULL,
		checked_in TINYINT(1) NOT NULL default 0,
		last_updated TIMESTAMP NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (booking_ticket_id),
		KEY booking_id (booking_id),
		KEY ticket_id (ticket_id),
		KEY event_id (event_id),
		KEY occurrence_id (occurrence_id)
		) $charset_collate; ";

	dbDelta( $sql_booking_tickets_table );

	/*Insert blank booking form */
	if( !eventorganiser_get_booking_forms( array( 'fields' => 'ids', 'numberposts' => 1 ) ) )
		wp_insert_post( array( 'post_status' => 'publish', 'post_type' => 'eo_booking_form', 'post_title' => '' ) );
	
	/*Fields IDs start from 2 to allow for default ticketpicker/gateway fields */
	add_option( 'eventorganiser_pro_options', array(
			'field_id'=>2,
		) );
	
	
	//Add manage booking roles to administrator
	global $wp_roles;
	
	foreach ( get_editable_roles() as $role_name => $display_name ):
	
		$role = $wp_roles->get_role( $role_name );
	
		if( $role->has_cap( 'manage_options' ) ){
			$role->add_cap( 'manage_eo_bookings' );
			$role->add_cap( 'manage_others_eo_bookings' );
		}
	
	endforeach;
}


/**
 * Upgrade routine. Hooked onto admin_init
 *
 *@since 1.0
 *@access private
 *@ignore
 */
function eventorganiser_pro_upgradecheck(){
	
	$installed_ver = get_option( 'eventorganiser_pro_version' );

	if( empty( $installed_ver ) ){
		//This is a fresh install. Add current database version
		add_option( 'eventorganiser_pro_version', EVENT_ORGANISER_PRO_VER );
		add_option( 'eventorganiser_initial_pro_version', EVENT_ORGANISER_PRO_VER );
		
		eventorganiser_pro_install();
	}
	
	if( $installed_ver && version_compare( $installed_ver, EVENT_ORGANISER_PRO_VER, '<' ) ){
		//Run for 1.5 updates from earlier versions, but not fresh installs.
		//Send through install routine to add capabilities to admin & update db schema
		eventorganiser_pro_install( false );
		
		if( version_compare( $installed_ver, '1.7.0', '<' ) ){
			_eventorganiser_pro_170_update();
		}
	}
	
	if( $installed_ver != EVENT_ORGANISER_PRO_VER ){
		add_action( 'admin_notices', 'eventorganiser_pro_db_checks' );
		update_option( 'eventorganiser_pro_version', EVENT_ORGANISER_PRO_VER );
	}
	
	if( defined( 'EVENT_ORGANISER_DIR' ) && _eventorganiser_has_changed_booking_form_template() ){
		$notice_handler = EO_Pro_Admin_Notice_Handler::get_instance();
		if( current_user_can( 'manage_options' ) ){
			$notice_handler->add_notice( '1.8.0-booking-form-template', false,
				"<h3>The event booking form template has changed... </h3>
				In 1.8 the booking form template changed. <br> It looks like you're using a customised booking
				form template, so you may have to make some alterations. 
				See <a href='http://wp-event-organiser.com/blog/announcements/booking-form-template-changes-1-8/'>this post</a> for more details."
			);
		}
	}
	
}
add_action('init', 'eventorganiser_pro_upgradecheck');


function _eventorganiser_pro_170_update(){
	
	$forms = eventorganiser_get_booking_forms( array( 'fields' => 'ids' ) );
	if( $forms ){
		foreach( $forms as $form_id ){
			_eventorganiser_pro_170_update_form( $form_id );
		}
	}
}

function _eventorganiser_pro_170_update_form( $form_id ){
	
	$origin_elements = get_post_meta( $form_id, '_eo_booking_form_fields', true );
	$new_elements = array();
	$add_to =& $new_elements;
	
	if( $origin_elements ){
	
		update_post_meta( $form_id, '_eo_booking_form_fields_old', $origin_elements );
		
		foreach( $origin_elements as $i => $element ){
				
			$element['id'] = $i;
			
			if( !empty( $element['element_type'] ) && $element['element_type'] == 'fieldset' ){
				unset( $add_to );
				$element['type'] = $element['element_type'];
				unset( $element['element_type'] );
				$add_to =& $element['elements'];
				$new_elements[$i] = $element;
			}else{
				if( 'radiobox' == $element['element_type'] ){
					$element['type'] = 'radio';
				}else{
					$element['type'] = $element['element_type'];
				}
				$add_to[$i] = $element;
			}
		}
	
	}
	
	update_post_meta( $form_id, '_eo_booking_form_fields', $new_elements );
}


/**
 * Uninstall routine
 *
 * @since 1.0
 * @access private
 * @ignore
 */
function eventorganiser_pro_uninstall( $is_networkwide ){
	global $wpdb;

    	// Is this multisite and did the user click network activate?
    	$is_multisite = ( function_exists('is_multisite') && is_multisite() );

    	if ( $is_multisite && $is_networkwide ) {
    	    	// Get the current blog so we can return to it.
	        $current_blog_id = get_current_blog_id();

	        // Get a list of all blogs.
	        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		if( $blog_ids ){
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
	            		eventorganiser_pro_uninstall_site();
	       		 }
			switch_to_blog( $current_blog_id );
		}else{
			eventorganiser_pro_uninstall_site();
		}
    	}else {
    	    eventorganiser_pro_uninstall_site();
    	}

}

function eventorganiser_pro_uninstall_site(){
	global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->eo_booking_tickets}" );

	//Delete plugin related post meta from events
	$keys = array( '_eventorganiser_tickets', '_eventorganiser_ticket_qty', '_eventorganiser_mc_list' );
	$events = get_posts(
		array( 'post_type'=>'event', 'post_status'=>'any', 'fields' => 'ids',
			'meta_query'=>array(
				'relation' => 'OR',
				array( 'key' => '_eventorganiser_tickets' ),
				array( 'key' => '_eventorganiser_ticket_qty' ),
				array( 'key' => '_eventorganiser_mc_list' ),
			),
			'showrepeats'=>false,
			'showpastevents'=>1
		) );

	foreach ( $events as $post_id ) {
		$post_id = (int) $post_id;

		foreach ( $keys as $key )
			delete_post_meta( $post_id, $key );
	}
}


/**
 * Deactivate routine
 *
 * Clears cron jobs and flushes rewrite rules
 *
 * @since 1.0
 * @access private
 * @ignore
 */
function eventorganiser_pro_deactivate() {
	if( is_plugin_active( 'event-organiser/event-organiser.php' ) ){
		eventorganiser_clear_cache( 'eo_booking' );
		eventorganiser_clear_cache( 'eo_booking_tickets' );
		eventorganiser_clear_cache( 'eo_occurrence_tickets' );
	}
}
?>
