<?php

//Initialise the TinyMCE shortcode button handler
function eventorganiser_shortcode_buttons() {

	// Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) )
		return;

	// Add only in Rich Editor mode
	if ( get_user_option( 'rich_editing' ) == 'true' ) {
		add_filter( "mce_external_plugins", "eventorganiser_add_tinymce_plugin" );
		add_filter( 'mce_buttons', 'eventorganiser_register_buttons' );
		
		$venues = eo_get_venues();
		if( $venues && !is_wp_error( $venues ) ){
			$venues = eventorganiser_convert_for_autocomplete( $venues, 'slug', 'name' );
		}else{
			$venues = false;
		}
		
		$cats = eventorganiser_convert_for_autocomplete( get_terms( 'event-category', array( 'hide_empty' => 0 ) ), 'slug', 'name' );
		wp_localize_script( 'jquery-ui-core', 'EOPro', array( 'venue' => $venues, 'category' => $cats ) );
	}
}
add_action( 'init', 'eventorganiser_shortcode_buttons', 20 );

//Print dialog box for shortcode dialog
function eventorganiser_event_shortcode_dialog() {
	include EVENT_ORGANISER_PRO_DIR.'admin/includes/shortcode_popup.php';
}
add_action( 'after_wp_tiny_mce', 'eventorganiser_event_shortcode_dialog' );


// Register the tinyMCE plug-in
function eventorganiser_add_tinymce_plugin( $plugin_array ) {
	$plugin_array['eo_button'] = EVENT_ORGANISER_PRO_URL.'admin/js/shortcode-buttons.js';
	return $plugin_array;
}

// registers the buttons for use
function eventorganiser_register_buttons( $buttons ) {

	// inserts a separator between existing buttons and our new one
	array_push( $buttons, "|", "eo_button" );
	wp_register_style( 'eventorganiser-jquery-ui-style', EVENT_ORGANISER_URL.'css/eventorganiser-admin-fresh.css', array() );
	wp_enqueue_style( 'eventorganiser-jquery-ui-style' );
	wp_enqueue_script( 'jquery-ui' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-tabs' );
	wp_enqueue_script( 'jquery-ui-widget' );
	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script( 'jquery-ui-autocomplete' );
	return $buttons;
}
?>