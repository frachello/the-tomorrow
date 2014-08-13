<?php
/*
Plugin Name: Event Organiser Pro
Plugin URI: http://www.wp-event-organiser.com
Version: 1.8.1
Description: A premium add-on for Event Organiser. Adds booking management and many other features.
Author: Stephen Harris
Author URI: http://www.stephenharris.info
*/
/*  Copyright 2013 Stephen Harris (contact@stephenharris.info)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

/*
*  *  *
# Planned (Potential) Features.

All features listed below are **provisional**, and in no way guranteed. With view to stability, each major update will generally 
be 'themed', focussing on a particular aspect of the plug-in.
		
## Potential Future Features
	- Users select password at booking form (?)
	- Manually add bookings (?)
	- Priced fields (?) 
	- Ajax in booking form
	- http://wp-event-organiser.com/forums/topic/email-tags-event_start_time-and-event_end_time/
 	-http://wp-event-organiser.com/forums/topic/add-selections-from-booking-form-in-confirmation-e-mail/
	- 'per ticket' option for form customiser
	- cancelled booking email
	- MAYBELATER Allow booking meta to be edited
	
*  *  *
*/
define( 'EVENT_ORGANISER_PRO_DIR',plugin_dir_path(__FILE__ ));
define( 'EVENT_ORGANISER_PRO_VER', '1.8.1' );


function _eventorganiser_pro_set_constants(){
	/*
 	* Defines the plug-in directory url
 	* <code>url:http://mysite.com/wp-content/plugins/event-organiser-pro</code>
	*/
	define( 'EVENT_ORGANISER_PRO_URL',plugin_dir_url(__FILE__ ));
}
add_action( 'after_setup_theme', '_eventorganiser_pro_set_constants' );

//Install
register_activation_hook(__FILE__, 'eventorganiser_pro_install' );
register_deactivation_hook( __FILE__, 'eventorganiser_pro_deactivate' );
register_uninstall_hook( __FILE__,'eventorganiser_pro_uninstall' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/install.php' );

/**
 * Load the translation file for current language. Checks in wp-content/languages first
 * and then the event-organiser-pro/languages.
 *
 * Edits to translation files inside event-organiser/languages will be lost with an update
 * **If you're creating custom translation files, please use the global language folder.**
 *
 * @since 1.0
 * @ignore
 * @uses apply_filters() Calls 'plugin_locale' with the get_locale() value
 * @uses load_textdomain() To load the textdomain from global language folder
 * @uses load_plugin_textdomain() To load the textdomain from plugin folder
 */
function eventorganiser_pro_load_textdomain() {
	$domain = 'eventorganiserp';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	$mofile = $domain . '-' . $locale . '.mo';
	
	/* Check the global language folder */
	$files = array( WP_LANG_DIR . '/event-organiser-pro/' . $mofile, WP_LANG_DIR . '/' . $mofile );
	foreach ( $files as $file ){
		if( file_exists( $file ) )
			return load_textdomain( $domain, $file );
	}

	//If we got this far, fallback to the plug-in language folder.
	//We could use load_textdomain - but this avoids touching any more constants.
	load_plugin_textdomain( 'eventorganiserp', false, basename( dirname( __FILE__ ) ).'/languages' );
}
add_action( 'plugins_loaded', 'eventorganiser_pro_load_textdomain' );


//Add-on handlings
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/addon.php' );

//General functions
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/functions.php' );

//Register post types, scripts & tables
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/register.php' );

//Utility functions
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/utility-functions.php' );

//Booking & ticket functions
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/tickets.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/booking-tickets.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/bookings.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/booking-actions.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/booking-status.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/booking-form-handler.php' );

//Email handling
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/email.php' );

//User booking functions
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/user-bookings.php' );

//Venue functions
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/venue-functions.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/advanced-queries.php' );


//Booking form customiser
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/class-eo-booking-form-controller.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/class-eo-booking-form.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/interface-eo-booking-form-element.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/interface-eo-booking-form-view.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/class-eo-booking-form-element.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/class-eo-booking-form-elements.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/class-eo-booking-form-elements-view.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/class-eo-booking-form-view.php' );

if( !is_admin() ){
	require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/class-eo-booking-form-element-view.php' );
}
	
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/template-tags.php' );

/*Gateways */
require_once( EVENT_ORGANISER_PRO_DIR . 'includes/gateways.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'gateways/paypal-standard.php' );
require_once( EVENT_ORGANISER_PRO_DIR . 'gateways/class-eo-gateway.php' );

//Add settigns
add_filter( 'eventorganiser_settings_tabs','eventorganiser_pro_add_settings' );


function eventorganiser_pro_load_files(){
	
	if( !defined( 'EVENT_ORGANISER_DIR' ) )
		return;
	
	if( is_admin() ):
		/* Admin pages */
		require_once( EVENT_ORGANISER_PRO_DIR . 'admin/settings.php' );
		require_once( EVENT_ORGANISER_PRO_DIR . 'admin/edit.php' );
		require_once( EVENT_ORGANISER_PRO_DIR . 'admin/post.php' );
		require_once( EVENT_ORGANISER_PRO_DIR . 'admin/calendar.php' );
		require_once( EVENT_ORGANISER_PRO_DIR . 'admin/includes/ajax-actions.php' );
		require_once( EVENT_ORGANISER_PRO_DIR . 'admin/bookings.php' );
		require_once( EVENT_ORGANISER_PRO_DIR . 'admin/includes/venues.php' );
		require_once( EVENT_ORGANISER_PRO_DIR . 'admin/includes/event_ticket_table.php' );
	endif;
	
	//Shortcodes & Widgets
	require_once( EVENT_ORGANISER_PRO_DIR . 'includes/shortcodes.php' );
	require_once( EVENT_ORGANISER_PRO_DIR . 'includes/class-eo-user-attending-widget.php' );
	
	add_action( 'widgets_init', 'eventorganiser_pro_widgets_init' );
	
	//Register scripts
	add_action( 'admin_init', 'eventorganiser_pro_admin_register_scripts', 15 );
	add_action( 'admin_enqueue_scripts', 'eventorganiser_pro_admin_enqueue_scripts', 15 );
	add_action( 'init', 'eventorganiser_pro_register_scripts', 15 );
	
}
add_action( 'plugins_loaded', 'eventorganiser_pro_load_files' );



function eventorganiser_pro_widgets_init(){
	//eventorganiser_load_textdomain();
	register_widget( 'EO_User_Attending_Widget' );
}



function eventorganiser_admin_action(){
	if ( is_admin() && !empty($_REQUEST['eo-action']) )
		do_action( 'eventorganiser_admin_action_' . $_REQUEST['eo-action']);
}	
add_action( 'admin_init','eventorganiser_admin_action',12);


function eventorganiser_init_action(){
	if ( !empty($_REQUEST['eo-action']) )
		do_action( 'eventorganiser_action_' . $_REQUEST['eo-action']);
}
add_action( 'wp_loaded','eventorganiser_init_action',12);


function eventorganiser_csv_export_listener(){

	require_once( 'includes/class-eo-export-csv.php' );
	$occurrence_id =( !empty( $_REQUEST['occurrence_id'] )  ? intval($_REQUEST['occurrence_id']) : 0);
	$event_id =( !empty( $_REQUEST['event_id'] )  ? intval($_REQUEST['event_id']) : 0);
	$bookee_id =( !empty( $_REQUEST['bookee_id'] )  ? intval($_REQUEST['bookee_id']) : 0);
	$status =( !empty( $_REQUEST['status'] )  ? $_REQUEST['status'] : '' );
	$meta = ( !empty( $_REQUEST['meta'] )  ? $_REQUEST['meta'] : '' );
	$args = compact( 'occurrence_id','event_id','bookee_id','status', 'meta' );

	$delimiter = isset( $_REQUEST['delimiter'] ) ? stripslashes( $_REQUEST['delimiter'] ) : ",";
	$text_delimiter = isset(  $_REQUEST['text_delimiter'] ) ? stripslashes( $_REQUEST['text_delimiter'] ) : '"';
	
	if( 'export-bookings' == $_REQUEST['eo-action'] ){
		$csv = new EO_Export_Bookings_CSV( $args );
		$csv->init();
		$re1 = $csv->set_delimiter( $delimiter );
		$re2 = $csv->set_text_delimiter( $text_delimiter );
		$csv->export();
		
	}elseif( 'export-tickets' == $_REQUEST['eo-action'] ){		
		$csv = new EO_Export_Tickets_CSV( $args );
		$csv->init();
		$csv->set_delimiter( $delimiter );
		$csv->set_text_delimiter( $text_delimiter );
		$csv->export();
	}
}

add_action( 'eventorganiser_admin_action_export-bookings','eventorganiser_csv_export_listener' );
add_action( 'eventorganiser_admin_action_export-tickets','eventorganiser_csv_export_listener' );
