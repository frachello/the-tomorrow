<?php
/*
 * Deals with the plug-in's AJAX requests
 */

/**
 * Event Category search
 * Returns a list of categories that match the term
 */
function eventorganiser_search_categories() {
	// Query the venues with the given term
	$value = trim(esc_attr($_GET["term"]));
	$args =array('hide_empty'=>0, 'search'=>$value);
	$cats = get_terms('event-category',$args);	
	$response = $_GET["callback"] . "(" . json_encode($cats) . ")";  
	echo $response;  
	exit;
}
add_action( 'wp_ajax_eo-search-category', 'eventorganiser_search_categories' );


/**
 * Bookings search
 * Returns an array of bookigns matching criteria
 */
function _eventorganiser_search_bookings() {
	
	global $wpdb;
	
	$value = trim( $_GET["term"] );
	$results = eo_get_bookings( array(
		'no_found_rows' => true, 
		'update_post_term_cache' => false,
		'fields' => 'ids', 
		'search' => $value,
		'posts_per_page' => 10,
	) );

	$bookings = array();
	
	if( $results ){
		foreach( $results as $result ){
			
			$bookings[] = array(
				'booking_id' => (string) $result,
				'bookee' => (string) eo_get_booking_meta( $result, 'bookee_display_name' ),
				'event' => get_the_title( eo_get_booking_meta( $result, 'event_id' ) ),
				'bookee_email' => (string) eo_get_booking_meta( $result, 'bookee_email' ),
				'edit_link' => eventorganiser_edit_booking_url( $result ),
			);
			
		}
	}
	
	$response = $_GET["callback"] . "(" . json_encode($bookings) . ")";
	echo $response;
	exit;
}
add_action( 'wp_ajax_eo-search-bookings', '_eventorganiser_search_bookings' );


/**
 * Generates email preview based on message and chosen template
 */
function eventorganiser_preview_email(){
	$body = wpautop(stripslashes($_POST['message']));
	$template = stripslashes($_POST['template']);
	echo eventorganiser_get_email_preview($body, $template);
	exit;
}
add_action( 'wp_ajax_eo-preview-email', 'eventorganiser_preview_email');


/**
 * Ajax action for deleting venue meta (triggered on venue admin page).
 */	
function eventorganiser_delete_venue_meta(){

	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$venue_id = (int) $_POST['eo_venue_id'];
	$tax = get_taxonomy( 'event-venue');
	check_ajax_referer( 'delete-eo-venue-meta_' . $id,  '_ajax_nonce' );
	
	if ( !current_user_can( $tax->cap->edit_terms ) ){
		echo -1;
		exit;
	}
	if ( !$meta = get_metadata_by_mid( 'eo_venue', $id ) ){
		echo 1;
		exit;
	}
	if ( delete_metadata_by_mid( 'eo_venue' , $meta->meta_id ) ){
		echo 1;
		exit;
	}
	exit;
}
add_action('wp_ajax_delete-eo-venue-meta','eventorganiser_delete_venue_meta');

/**
 * Create / Update venue meta. If the venue ID doesn't exist, create a venue for it.
 */	
function eventorganiser_add_venue_meta(){

	check_ajax_referer( 'add-eo-venue-meta', '_ajax_nonce' );

	$venue_id = (int) $_POST['eo_venue_id'];
	$tax = get_taxonomy( 'event-venue');
	$c=0;
	
	if( isset($_POST['eo_venue_id']) && 0 == $_POST['eo_venue_id'] ){
		//Ajax call, but venue doesn't exist in database yet, so create it.
		$return = eo_insert_venue( 'New Venue');
		if( is_wp_error($return) )
			wp_die( 0 );

		$venue_id = (int) $return['term_id'];
	}

	$metakeyselect = isset($_POST['metakeyselect']) ? stripslashes( trim( $_POST['metakeyselect'] ) ) : '';
	$metakeyinput = isset($_POST['metakeyinput']) ? stripslashes( trim( $_POST['metakeyinput'] ) ) : '';
	$value = isset($_POST['metavalue']) ? $_POST['metavalue'] : '';

	// Permision and sanity checks
	if ( !current_user_can( $tax->cap->edit_terms ) )
		wp_die( -1 );
	
	if ( !empty($metakeyselect) || !empty($metakeyinput)  ) {//Creating new

		if ( '#NONE#' == $metakeyselect && empty($metakeyinput) )
			wp_die( 1 );

	 	if ( '#NONE#' != $metakeyselect )
			$key = $metakeyselect;

		if ( $metakeyinput )
			$key = $metakeyinput; // default

		if ( is_string( $value ) )
			$value = trim( $value );

		//Try to add meta
		if ( !$mid = eo_add_venue_meta($venue_id, $key, $value ) ) {
			wp_die( __( 'Please provide a custom field value.' ) );
		}

		//Adding meta succeeded - create row and send back
		$meta = get_metadata_by_mid( 'eo_venue', $mid );
		$venue_id = (int) $meta->eo_venue_id;
		$meta = get_object_vars( $meta );
		$x = new WP_Ajax_Response( array(
			'what' => 'meta',
			'id' => $mid,
			'data' => _eventorganiser_list_venuemeta_row( $meta, $c ),
			'position' => 1,
			'supplemental' => array('venueid' => $venue_id)
		) );

	} else { // Updating...

		$mid = (int) key( $_POST['eo-venue-meta'] );
		$key = stripslashes( $_POST['eo-venue-meta'][$mid]['key'] );
		$value = stripslashes( $_POST['eo-venue-meta'][$mid]['value'] );

		//Sanity checks
		if ( '' == trim($key) )
			wp_die( __( 'Please provide a custom field name.' ) );
		if ( '' == trim($value) )
			wp_die( __( 'Please provide a custom field value.' ) );

		//Try to find meta
		if ( ! $meta = get_metadata_by_mid( 'eo_venue', $mid ) )
			wp_die( 0 ); // if meta doesn't exist

		//Try to update it
		if ( $meta->meta_value != $value || $meta->meta_key != $key ) {
			if ( !$u = update_metadata_by_mid( 'eo_venue', $mid, $value, $key ) )
				wp_die( 0 ); // We know meta exists; we also know it's unchanged (or DB error, in which case there are bigger problems).
		}

		//Respond
		$x = new WP_Ajax_Response( array(
			'what' => 'meta',
			'id' => $mid, 'old_id' => $mid,
			'data' =>  _eventorganiser_list_venuemeta_row( array(
				'meta_key' => $key,
				'meta_value' => $value,
				'meta_id' => $mid
			), $c ),
			'position' => 0,
			'supplemental' => array('venueid' => $meta->eo_venue_id)
		));
	}	
	$x->send();
}
add_action( 'wp_ajax_add-eo-venue-meta', 'eventorganiser_add_venue_meta');

/**
 * Create / Update venue thumbnail. If the venue ID doesn't exist, create a venue for it.
 */
function eventorganiser_add_venue_thumbnail(){

	$venue_id = (int) $_POST['eo_venue_id'];
	$thumbnail_id = (int) $_POST['thumbnail_id'];

	if( !current_user_can('manage_venues') ){
		wp_die( -1 );
	}
	
	check_ajax_referer( 'set-venue-thumbnail-'.$venue_id, '_ajax_nonce' );
	
	if( isset($_POST['eo_venue_id']) && 0 == $_POST['eo_venue_id'] ){
		//Ajax call, but venue doesn't exist in database yet, so create it.
		$return = eo_insert_venue( 'New Venue');
		if( is_wp_error($return) )
			wp_die( 0 );
	
		$venue_id = (int) $return['term_id'];
	}
	
	if ( $thumbnail_id == '-1' ) {
		if ( eo_delete_venue_thumbnail( $venue_id ) ) {
			echo _eo_venue_thumbnail_html( null, $venue_id );
			exit();
		} else {
			wp_die( 0 );
		}
	}
	
	if ( eo_set_venue_thumbnail( $venue_id, $thumbnail_id ) ) {
		echo json_encode(array(
			'venue_id' => $venue_id,
			'thumbnail_id' => $thumbnail_id,
			'data' => _eo_venue_thumbnail_html( $thumbnail_id, $venue_id ),
			'errors' => false,
		));
		exit();
	}
	echo 'c';
	wp_die( 0 );
}
add_action( 'wp_ajax_eo-set-venue-thumbnail', 'eventorganiser_add_venue_thumbnail');
?>