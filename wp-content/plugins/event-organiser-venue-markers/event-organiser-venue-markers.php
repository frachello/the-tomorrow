<?php
/*
Plugin Name: Event Organiser Venue Markers
Plugin URI: http://www.wp-event-organiser.com
Version: 1.0
Description: Upload custom map markers for venues
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
//TODO Deal with incorrectly sized images?
//TODO Deal with delay on load? Fewer default images?
//TODO Auto-select after upload?


//Initiates the plug-in
add_action( 'plugins_loaded', array( 'EO_Venue_Markers', 'init' ) );

register_activation_hook( __FILE__, array( 'EO_Venue_Markers', 'install' ) );
register_uninstall_hook(    __FILE__, array( 'EO_Venue_Markers', 'uninstall' ) );

/**
 * @ignore
 * @author stephen
 */
class EO_Venue_Markers{

	/**
	 * Instance of the class
	 * @static
	 * @access protected
	 * @var object
	 */
	protected static $instance;
	
	static $version = '1.0';
	
	static $subdir = 'venue-markers'; 
	
	/**
	 * Instantiates the class
	 * @return object $instance
	 */
	public static function init() {
		is_null( self :: $instance ) AND self :: $instance = new self;
		return self :: $instance;
	}
	
	/**
	 * Constructor.
	 */
	public function __construct() {
		
		//Admin scripts
		add_action( 'load-event_page_venues', array( $this, 'setup_scripts' ) );
		
		//Add venue metabox and save callback
		add_action( 'add_meta_boxes_event_page_venues', array( $this, 'add_metabox' ) );
		add_action( 'eventorganiser_save_venue', array( $this, 'save_marker' ) );

		//Ajax handle marker up load
		add_action( 'wp_ajax_eo-venue-marker-upload', array( $this, 'upload_marker' ) );
		
		//Change marker
		add_filter( 'eventorganiser_venue_marker', array( $this, 'change_marker' ), 10, 2 );
		
	}
	
	/**
	 * Runs on activation. Unzips default markers in to uploads/venue-markers
	 * @return boolean
	 */
	function install(){
				
		WP_Filesystem();
		
		$uploads = wp_upload_dir();
		$from = trailingslashit( plugin_dir_path( __FILE__ ) ). 'venue-markers.zip';
		$to = trailingslashit( $uploads['basedir'] );
		$response = true;
		
		if ( !is_dir( $to.'venue-markers' ) ){
			wp_mkdir_p( $to );
			$response = unzip_file( $from, $to );
		}
		
		if( !$response || is_wp_error( $response ) ){

			$admin_notices = EO_Venue_Marker_Admin_Notice_Handler::get_instance();
			$code = $response->get_error_code();
			$admin_notices->add_notice(
					'unzip-error',
					sprintf(
							'<p>%s: <strong>%s</strong></p><p>%s: <code>%s</code></p>',
							esc_html__( 'The following error was encountered when attempting to unzip the default markers to your uploads directory:', 'eventorganiser' ), 
							$response->get_error_message( $code ),									
							esc_html__( 'If you would like to use the default markers, please unzip this file manually to', 'eventorganiservm' ),
							$to
					),
					'',
					'error'
			);
		}
	}
	
	/**
	 * Removes options saved to database
	 */
	function uninstall(){

		//Remove options
		$admin_notices = EO_Venue_Marker_Admin_Notice_Handler::get_instance();
		$admin_notices->clean();
	}
	
	/**
	 * Loads scripts & styles on edit-venue page
	 */
	function setup_scripts(){
		
		if( 
			( empty( $_GET['event-venue'] ) || empty( $_GET['action'] ) || 'edit' !== $_GET['action']  ) //edit venue
			&& ( empty( $_GET['action'] ) || 'create' !== $_GET['action'] ) //create venue
		){
			return false;
		}
		add_action( 'print_media_templates', array( $this, 'print_media_templates' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_scripts' ) );
		
		$admin_notices = EO_Venue_Marker_Admin_Notice_Handler::get_instance();
		$admin_notices->add_notice(
				'cc-license',
				sprintf(
					'<p>'. __( 'The default markers provided with this add-on were created by <a href="%s">Nicolas Mollet</a> and released under the <a href="%s">Creative Commons CC BY SA 3.0</a> license.', 'eventorganiser' )
					. '<br/>' . __( 'You can download more icons like these from <a href="%s">http://mapicons.nicolasmollet.com</a>.', 'eventorganiser' )
					. '</p>',
					'http://mapicons.nicolasmollet.com',
					'http://creativecommons.org/licenses/by-sa/3.0/',
					'http://mapicons.nicolasmollet.com'
				),
				'',
				'alert'
		);
	}
	
	/**
	 * Add metabox to venue edit screen.
	 */
 	function add_metabox(){
 		if( current_user_can( 'upload_files' ) ){
			add_meta_box( 
				'eo-venue-map-marker-metabox', 
				__( 'Venue Map Marker', 'eventorganiservm' ), 
				array( $this, 'metabox' ), 
				'event_page_venues', 
				'side', 
				'high'
			);
 		}
 	}

 	
 	/**
 	 * Metabox callback
 	 */
 	function metabox( $venue ){

 		if( $venue ){
    		$url = eo_get_venue_meta( $venue->term_id,  '_eventorganiser_venue_marker' );
 		}else{
 			$url = false;
 		}		
    		
 		$thumb = ( $url ? $url : plugins_url( "default.png", __FILE__ ) );
 
	    wp_nonce_field( 'eventorganiser-venue-marker', 'eo_venue_marker_nonce' );
    	?>
    	<img src="<?php echo esc_url( $thumb );?>" id="eo-venue-marker-thumbnail"/>
    	<input type="hidden" name="eo_venue_marker_url" id="eo-venue-marker-url" value="<?php echo esc_attr( $url );?>"/>
    	<input type="button" class="button open-media-button" id="open-venue-marker-picker" value="Select a marker" />
    	<?php
 	}
	
 	/**
 	 * Save venue callback
 	 * @param int $venue_id
 	 */
 	function save_marker( $venue_id ){

		if( !isset( $_POST['eo_venue_marker_url'] ) )
			return;
		
 		//Check permissions
 		$tax = get_taxonomy( 'event-venue');
 		if ( !current_user_can( $tax->cap->edit_terms ) )
 			return;
 	
 		//Check nonce
 		check_admin_referer( 'eventorganiser-venue-marker', 'eo_venue_marker_nonce' );
 	
 		//Retrieve meta value(s)
 		$url = esc_url_raw( $_POST['eo_venue_marker_url'] );
 	
 		//Update venue meta
 		eo_update_venue_meta( $venue_id,  '_eventorganiser_venue_marker', $url );
 		return;
 	}
 	
 	/**
 	 * Scans uploads/venue-markers directory for image files
 	 * @return array List of markers, each of the form of associated array with 'url' and 'name' key
 	 */
 	function get_markers(){

		$sub_dir = self::$subdir;
		$uploads = wp_upload_dir();
		$directory = trailingslashit( $uploads['basedir'] );
		$url = trailingslashit( $uploads['baseurl'] );

		//get all image files with an allowed image extension.
		$file_types = implode( ',', array_keys( $this->get_allowed_mime_types() ) );
		$file_types = str_replace( '|', ',', $file_types );
		$images = glob( "" . trailingslashit( $directory. $sub_dir ). "*.{".$file_types."}", GLOB_BRACE );

		$markers = array();
		foreach( $images as $image ){

			$name = basename( $image );
			$markers[] = array(
				'url' => $url . "$sub_dir/$name", 
				'name' => $name,
				'selected' => false,
			);
		}
		
		return $markers;
	}

 	
	/**
	 * Enqueue admin scripts
	 */
	function enqueue_scripts(){
		
		$venue_slug = isset(  $_GET['event-venue'] ) ?  $_GET['event-venue'] : false;
		
		if( $venue_slug ){
			$marker = eo_get_venue_map_marker( $venue_slug );
		}else{
			$marker = false;
		}
		
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		
		wp_enqueue_media();
		wp_enqueue_script(
			'eo-venue-marker-modal',
			plugins_url( "venue-marker-modal{$min}.js", __FILE__ ),
			array( 'media-editor', 'media-views' ),
			self::$version
		);
		wp_enqueue_style(
			'eo-venue-marker-style',
			plugins_url( 'venue-marker-admin.css', __FILE__ ),
			array(),
			self::$version
		);
		$args = array(
			'markers' => $this->get_markers(),
			'marker' => $marker,
			'default_marker' => plugins_url( "default.png", __FILE__ ),
		);
		wp_localize_script( 'eo-venue-marker-modal', 'eo_venue', $args );
	}	
	
	/**
	 * Print backone.js templates
	 */
	function print_media_templates(){	
		?>
		<script type="text/html" id="tmpl-eo-upload-marker">			
			<form id="eo-marker-upload-form" action="<?php echo admin_url('admin-ajax.php?action=eo-venue-marker-upload');?>" method="post" enctype="multipart/form-data" target="upload_target" >
				<div class="error" style="display:none"><p></p></div>
				<h3> Select a file to upload below </h3>
    			<input type="file" name="eo-venue-marker" id="eo-upload-marker" />
          		<input type="submit" class="button-primary button-large button" name="submitBtn" value="Upload" />
				<?php wp_nonce_field( 'eo-venue-marker-upload', '_eo-venue-marker-nonce' ); ?>
				<span class="spinner"></span>
			</form> 
			<iframe id="upload_target" name="upload_target" src="#" style="width:0;height:0;border:0px solid #fff;"></iframe>              
			</p>
		</script>
		<script type="text/html" id="tmpl-venue-marker-list">
		<ul class="attachments ui-sortable ui-sortable-disabled"></ul>
		</script>
		<script type="text/html" id="tmpl-venue-marker-toolbar">
		<p>
			<input type="button" class="button-large button-secondary button" id="eo-venue-marker-cancel" value="Cancel">
			<input type="button" class="button-large button-primary button" id="eo-venue-marker-submit" value="Use Marker">
		</p>
		</script>

		<script type="text/html" id="tmpl-venue-marker">
			<div class="attachment-preview type-audio subtype-mpeg landscape">
				<img src="{{ data.url }}" class="icon" draggable="false">
				<div class="filename"><div>{{ data.name }}</div></div>
				<a class="check" href="#" title="Deselect"><div class="media-modal-icon"></div></a>	
			</div>
		</script>
		<?php
	}
	
	/**
	 * Check is a custom marker has been provided. If so, replaces default marker with custom one.
	 * 
	 * @param string|null $icon Url to current marker or null for default marker
	 * @param int $venue_id The venue
	 * @return string|null Url to the custom marker or null to use default marker
	 */
	function change_marker( $icon, $venue_id ){

		if( $marker = eo_get_venue_map_marker( $venue_id ) ){
			return $marker;
		}
	
		return $icon;
	}
	
	
	/**
	 * Changes the uploads directory to point to 'uploads/venue-marker'
	 * Hooked onto `upload_dir` (and removed again) in {@see EO_Venue_Markers::upload_marker}.
	 * 
	 * @param array $uploads array
	 * @return array
	 */
	function change_upload_directory( $uploads ){
		if( !empty( $uploads['error'] ) )
			return $uploads;
		
		/* Expects:
			[path] => C:\path\to\wordpress\wp-content\uploads\2013\07
			[url] => http://example.com/wp-content/uploads/2013/07
			[subdir] => /2010/05
			[basedir] => C:\path\to\wordpress\wp-content\uploads
			[baseurl] => http://example.com/wp-content/uploads
			[error] =>
		*/
		
		$uploads['path'] = trailingslashit( $uploads['basedir'] ) . self::$subdir; 
		$uploads['url'] = trailingslashit( $uploads['baseurl'] ) . self::$subdir;
		$uploads['subdir'] = self::$subdir;
		return $uploads;
	}
	
	/**
	 * Returns a whitelisted subset of `{@see get_allowed_mime_types()}`.
	 * Only allows selected images.
	 * 
	 * @uses get_allowed_mime_types()
	 * @return array:
	 */
	function get_allowed_mime_types(){

		$image_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif' => 'image/gif',
			'png' => 'image/png',
			'bmp' => 'image/bmp',
			'tif|tiff' => 'image/tiff',
			'ico' => 'image/x-icon',
		);

		$mimes = get_allowed_mime_types();
		return array_intersect_key( $mimes, $image_mimes );
	}
	
	
	/**
	 * Upload marker ajax callback.
	 * Recieves file, checks permissons and nonces and uploads file.
	 * @uses wp_handle_upload()
	 */
	function upload_marker(){
			
		$error = false;	

		if( !current_user_can( 'upload_files' ) ){
			$error = __( 'You do not have sufficient permission to upload files.', 'eventorganiser' );
			
		}elseif( !isset( $_FILES['eo-venue-marker'] ) ){
			$error = __( 'No file was found. Please select a marker to upload.', 'eventorganiser' );
		}
		
		if( !$error ){
			$_file = $_FILES['eo-venue-marker'];
			$name = $_FILES['eo-venue-marker']['name'];
			check_admin_referer( 'eo-venue-marker-upload', '_eo-venue-marker-nonce' );
		
			$overrides = array(
				'test_form' => false,
				'mimes' => $this->get_allowed_mime_types(),
			);
		
			//Add filter to change upload directory, then remove it again afterwards.
			add_filter( 'upload_dir', array( $this, 'change_upload_directory' ) );
			$file = wp_handle_upload( $_file, $overrides );
			remove_filter( 'upload_dir', array( $this, 'change_upload_directory' ) );
		
			if ( !empty( $file['error'] ) ){
				$error = $file['error'];
				
			}else{
				$name_parts = pathinfo($name);
				$name = trim( substr( $name, 0, -(1 + strlen($name_parts['extension'])) ) );
				$url = $file['url'];
				$type = $file['type'];
				$file = $file['file'];
			}
		}
		
		$success = empty( $error );
		?>
		<script language="javascript" type="text/javascript">
			window.top.window.eo_venue_marker.media.frame().content.get().uploadComplete( '<?php echo $error; ?>' );
			<?php if( $success ): ?>
				the_marker = new window.top.window.eo_venue_marker.media.model.VenueMarker( { 
					'url': '<?php echo esc_js( $url ); ?>',
					'name' : '<?php echo esc_js( $name ); ?>'
				});
				window.top.window.eo_venue_marker.media.markers.add( the_marker, { at: 0 } );
				window.top.window.eo_venue_marker.media.frame().setState('venue-marker-list-state');
			<?php endif; ?>
		</script>
		<?php 
		exit();
	}
}

function eo_get_venue_map_marker( $venue_slug_or_id ){
	$venue_id = eo_get_venue_id_by_slugorid( $venue_slug_or_id );
	return eo_get_venue_meta( $venue_id, '_eventorganiser_venue_marker', true );
}


/**
 * Handles admin notices
 *
 * This is a class which automates (semi-permenant) admin notices. This are notices which persist until an
 * action is performed or they are manually dismissed by the user. 
 *
 * @access private
 * @ignore
 */

class EO_Venue_Marker_Admin_Notice_Handler{

	protected static $instance;

	static $prefix = 'event-organiesr-venue-marker';
	
	static $notices = array();

	/**
	 * Hooks the dismiss listener (ajax and no-js) and maybe shows notices on admin_notices
	 */
	function __construct(){
		add_action( 'admin_notices', array(__CLASS__,'admin_notice'));
		add_action( 'admin_init',array(__CLASS__, 'dismiss_handler'));
		add_action( 'wp_ajax_'.self::$prefix.'-dismiss-notice', array( __CLASS__, 'dismiss_handler' ) );
	}

	
	/**
	 * Instantiates the class
	 * @return object $instance
	 */
	public static function get_instance() {
		is_null( self :: $instance ) AND self :: $instance = new self;
		return self :: $instance;
	}
	
	/**
	 * Print appropriate notices.
	 * Hooks EO_Venue_Marker_Admin_Notice_Handler::print_footer_scripts to admin_print_footer_scripts to
	 * print js to handle AJAX dismiss.
	 */
	
	function add_notice( $id, $message, $screen_id = '', $type = 'alert' ){
		self::$notices[$id]= array(
			'screen_id' => $screen_id,
			'message' => $message,
			'type' => $type
		);
	}
	
	static function admin_notice(){

		$screen_id = get_current_screen()->id;

		/* Notices of the form ID=> array('screen_id'=>screen ID, 'message' => Message,'type'=>error|alert)*/
		$notices = self::$notices;

		if( !$notices )
			return;

		$seen_notices = get_option(self::$prefix.'_admin_notices',array());

		foreach( $notices as $id => $notice ){
			$id = sanitize_key($id);

			//Notices cannot have been dismissed and must have a message
			if( in_array($id, $seen_notices)  || empty($notice['message'])  )
				continue;

			$notice_screen_id = (array) $notice['screen_id'];
			$notice_screen_id = array_filter($notice_screen_id);

			//Notices must for this screen. If empty, its for all screens.
			if( !empty($notice_screen_id) && !in_array($screen_id, $notice_screen_id) )
				continue;

			$class = $notice['type'] == 'error' ? 'error' : 'updated';

			printf("<div class='%s-notice {$class}' id='%s'>%s<p> <a class='%s' href='%s' title='%s'><strong>%s</strong></a></p></div>",
			esc_attr(self::$prefix),
			esc_attr(self::$prefix.'-notice-'.$id),
			$notice['message'],
			esc_attr(self::$prefix.'-dismiss'),
			esc_url(add_query_arg(array(
					'action'=>self::$prefix.'-dismiss-notice',
					'notice'=>$id,
					'_wpnonce'=>wp_create_nonce(self::$prefix.'-dismiss-'.$id),
			))),
			__( 'Dismiss this notice', 'eventorganiser' ),
			__( 'Dismiss','eventorganiser' )
			);
		}
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_footer_scripts' ), 11 );
	}

	/**
	 * Handles AJAX and no-js requests to dismiss a notice
	 */
	static function dismiss_handler(){

		$notice = isset($_REQUEST['notice']) ? $_REQUEST['notice'] : false;
		if( empty($notice) )
			return;

		if ( defined('DOING_AJAX') && DOING_AJAX ){
			//Ajax dismiss handler
			if( empty($_REQUEST['notice'])  || empty($_REQUEST['_wpnonce'])  || $_REQUEST['action'] !== self::$prefix.'-dismiss-notice' )
				return;

			if( !wp_verify_nonce( $_REQUEST['_wpnonce'],self::$prefix."-ajax-dismiss") )
				return;

		}else{
			//Fallback dismiss handler
			if( empty($_REQUEST['action']) || empty($_REQUEST['notice'])  || empty($_REQUEST['_wpnonce'])  || $_REQUEST['action'] !== self::$prefix.'-dismiss-notice' )
				return;

			if( !wp_verify_nonce( $_REQUEST['_wpnonce'],self::$prefix.'-dismiss-'.$notice ) )
				return;
		}

		self::dismiss_notice($notice);

		if ( defined('DOING_AJAX') && DOING_AJAX )
			wp_die(1);
	}

	/**
	 * Dismiss a given a notice
	 *@param string $notice The notice (ID) to dismiss
	 */
	static function dismiss_notice( $notice ){
		$seen_notices = get_option( self::$prefix.'_admin_notices', array() );
		$seen_notices[] = $notice;
		$seen_notices = array_unique( $seen_notices );
		update_option( self::$prefix.'_admin_notices', $seen_notices );
	}

	function clean(){
		delete_option( self::$prefix.'_admin_notices' );
	}
	/**
	 * Prints javascript in footer to handle AJAX dismiss.
	 */
	static function print_footer_scripts() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($){
				var dismissClass = '<?php echo esc_js(self::$prefix."-dismiss");?>';
        		var ajaxaction = '<?php echo esc_js(self::$prefix."-dismiss-notice"); ?>';
				var _wpnonce = '<?php echo wp_create_nonce(self::$prefix."-ajax-dismiss")?>';
				var noticeClass = '<?php echo esc_js(self::$prefix."-notice");?>';

				jQuery('.'+dismissClass).click(function(e){
					e.preventDefault();
					var noticeID= $(this).parents('.'+noticeClass).attr('id').substring(noticeClass.length+1);

					$.post(ajaxurl, {
						action: ajaxaction,
						notice: noticeID,
						_wpnonce: _wpnonce
					}, function (response) {
						if ('1' === response) {
							$('#'+noticeClass+'-'+noticeID).fadeOut('slow');
						} else {
							$('#'+noticeClass+'-'+noticeID).removeClass('updated').addClass('error');
						}
					});
				});
			});
		</script><?php
	}
}
$admin_notices = EO_Venue_Marker_Admin_Notice_Handler::get_instance();


/**
 * Update & License handling
 */


/**
 * Register settings & add License field to general tab
 */
function eventorganiser_venue_marker_register_liciense_field(){
	register_setting( 'eventorganiser_general', 'eventorganiser_venue_marker_license' );

	$installed_plugins = get_plugins();
	$eo_version = isset( $installed_plugins['event-organiser/event-organiser.php'] )  ? $installed_plugins['event-organiser/event-organiser.php']['Version'] : false;
	if( $eo_version && ( version_compare( $eo_version, '2.3'  ) >= 0 ) ){
		$section_id = 'general_licence';
	}else{
		$section_id = 'general';
	}

	add_settings_field(
		'eo-venue-markers-license',
		__( 'Venue Markers', 'eventorganiservm' ),
		'_eventorganiser_venue_marker_license_field',
		'eventorganiser_general',
		$section_id
	);
}
add_action( 'eventorganiser_register_tab_general', 'eventorganiser_venue_marker_register_liciense_field' );

/**
 * Display settings field
 */
function _eventorganiser_venue_marker_license_field(){
	$license = get_option( 'eventorganiser_venue_marker_license' );
	$check = eventorganiser_venue_markers_plm_license_check( $license );
	$valid = !is_wp_error( $check );

	eventorganiser_text_field ( array(
		'label_for' => 'eo-venue-markers-license',
		'value' => $license,
		'name' => 'eventorganiser_venue_marker_license',
		'style' => $valid ? 'background:#D7FFD7' : 'background:#FFEBE8',
		'help' => $valid ? '' : __( 'The license key you have entered is invalid.', 'eventorganiserp' )
			.' <a href="http://wp-event-organiser.com/downloads/event-organiser-venue-markers/">'.__('Purchase a license key', 'eventorganiservm' ).'</a>',
	) );	
}



function eventorganiser_venue_markers_plm_license_check( $license='')
{
	$license = strtoupper( str_replace( '-', '', $license ) );

	$prefix= 'eventorganiser_venue_markers';

	$public_key = '-----BEGIN PUBLIC KEY-----
MEwwDQYJKoZIhvcNAQEBBQADOwAwOAIxAMLNmUtiu8fYuthqj7secWdxL9K25rQm
DqYp4yZw4lxg0E/Sy/7R9dQbtPDFJgGNQwIDAQAB
-----END PUBLIC KEY-----
';

	$product_id ='event-organiser-venue-markers/event-organiser-venue-markers.php';

	$grace_period = 0;

	$local_period = 7;

	$plm_url = 'http://wp-event-organiser.com';

	$local_key = get_option($prefix.'_plm_local_key');

	$last_checked =0;

	$check_lock = 15*60;

	//Token depends on key being checked to instantly invalidate the local period when key is changed.
	$token = wp_hash($license.'|'.$_SERVER['SERVER_NAME'].'|'.$_SERVER['SERVER_ADDR'].'|'.$product_id);

	if( $local_key ){
		//Checking local key
		$signature = $local_key['signature'];
		$response = $local_key['response'];
		$signature = base64_decode($signature);
		$verified = openssl_verify($response, $signature, $public_key);

		//Unserialise response. Its an array with keys: 'valid', 'date_checked'
		$response = maybe_unserialize($response);

		if( $verified && $token == $response['token'] ){

			$last_checked = isset($response['date_checked']) ?  intval($response['date_checked'] ) : 0;
			$expires = $last_checked + intval($local_period)*24*60*60;

			if( $response['valid'] == 'TRUE' &&  ( time() < $expires ) ){
				//Local key is still valid
				return true;
			}
		}

	}

	//Check license format
	if( empty( $license ) )
		return new WP_Error( 'no-key-given' );
	if( preg_match('/[^A-Z234567]/i', $license) )
		return new WP_Error( 'invalid-license-format' );

	if( $is_valid_license = get_transient( '_check' ) && false !== get_transient( $prefix . '_check_lock' ) ){
		if( true === $is_valid_license )
			return $is_valid_license;
	}

	//Check license remotely
	$resp = wp_remote_post($plm_url, array(
			'method' => 'POST',
			'timeout' => 45,
			'body' => array(
					'plm-action' => 'check_license',
					'license' => $license,
					'product'=>$product_id,
					'domain'=>$_SERVER['SERVER_NAME'],
					'token'=> $token,
			),
	));

	$body =(array) json_decode(wp_remote_retrieve_body( $resp ));

	if( !$body || !isset($body['signature']) || !isset($body['response']) ){
		//No response or error
		$grace =  $last_checked + intval($grace_period)*24*60*60;

		if(  time() < $grace )
			return true;

		return new WP_Error( 'invalid-response' );
	}

	$signature = $body['signature'];
	$response = $body['response'];
	$signature = base64_decode($signature);

	if( !function_exists( 'openssl_verify' ) )
		return new WP_Error( 'openssl-not-enabled' );

	$verified = openssl_verify($response, $signature, $public_key);

	if( !$verified )
		return false;

	update_option($prefix.'_plm_local_key',$body);

	$response = maybe_unserialize($response);

	if( $token != $response['token'] )
		return new WP_Error( 'invalid-token' );

	if( $response['valid'] == 'TRUE' )
		$is_valid_license = true;
	else
		$is_valid_license = new WP_Error( $response['reason'] );

	if( $response['valid'] == 'TRUE' && $token == $response['token'] )
		return true;

	if( $check_lock ){
		set_transient( $prefix . '_check_lock', $license, $check_lock  );
		set_transient( $prefix . '_check', $is_valid_license, $check_lock );
	}

	return $is_valid_license;
}

class eventorganiser_venue_markers_PLM_Update_Handler
{

	var $prefix= 'eventorganiser_venue_markers';

	var $plugin_slug ='event-organiser-venue-markers/event-organiser-venue-markers.php';

	var $plm_url ='http://wp-event-organiser.com';

	var $license = false;

	function __construct(){

		/* Fired just before setting the update_plugins site transient. Transient stores if new
		 version exists, so we must add this manually to the transient after checking if one exists */
		add_filter ('pre_set_site_transient_update_plugins', array($this,'check_update'));

		/* Remotely retrieve this plug-ins formation. Hook in late as some plug-ins do this wrong*/
		add_filter ('plugins_api', array($this,'plugin_info'),9999,3);

	}


	/**
	 * A callback hooked inside 'plugins_api()' is called. We use this hook to 'abort' plugins_api() early
	 *  and run our request to check the plug-in's data from our custom 'repository'.
	 */
	public function plugin_info( $check, $action, $args ){

		if ( $args->slug == $this->plugin_slug ) {
			$obj = $this->get_remote_plugin_info('plugin_info');
			return $obj;
		}
		return $check;
	}


	/**
	 * Get's current version of installed plug-in.
	 */
	public function get_current_version(){
		$plugins = get_plugins();

		if( !isset( $plugins[$this->plugin_slug] ) )
			return false;

		$plugin_data = $plugins[$this->plugin_slug];
		return $plugin_data['Version'];
	}


	/**
	 * Fired just before setting the update_plugins site transient. Remotely checks if a new version is available
	 */
	public function check_update($transient){

		/**
		 * wp_update_plugin() triggers this callback twice by saving the transient twice
		 * The repsonse is kept in a transient - so there isn't much of it a hit.
		 */

		//Get remote information
		$plugin_info = $this->get_remote_plugin_info('plugin_info');

		// If a newer version is available, add the update
		if (version_compare($this->get_current_version(), $plugin_info->new_version, '<') ){

			$obj = new stdClass();
			$obj->slug = $this->plugin_slug;
			$obj->new_version = $plugin_info->new_version;
			$obj->package =$plugin_info->download_link;
				
			if( isset( $plugin_info->sections['upgrade_notice'] ) ){
				$obj->upgrade_notice = $plugin_info->sections['upgrade_notice'];
			}

			//Add plugin to transient.
			$transient->response[$this->plugin_slug] = $obj;
		}

		return $transient;
	}


	/**
	 * Return remote data
	 * Store in transient for 12 hours for performance
	 *
	 * @param (string) $action -'info', 'version' or 'license'
	 * @return mixed $remote_version
	 */
	public function get_remote_plugin_info($action='plugin_info'){

		/* Get license from option */
		$this->license = get_option( 'eventorganiser_venue_marker_license' );

		$key = wp_hash('plm_eventorganiser_venue_markers_'.$action.'_'.$this->plugin_slug);
		if( false !== ($plugin_obj = get_site_transient( $key ) ) && !$this->force_request() ){
			return $plugin_obj;
		}

		$request = wp_remote_post($this->plm_url, array(
				'method' => 'POST',
				'timeout' => 45,
				'body' => array(
						'plm-action' => $action,
						'license' => $this->license,
						'product'=>$this->plugin_slug,
						'domain'=>$_SERVER['SERVER_NAME'],
				)
		));

		if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
			//If its the plug-in object, unserialize and store for 12 hours.
			$plugin_obj =   ( 'plugin_info' == $action ? unserialize($request['body']) : $request['body'] );
			set_site_transient( $key, $plugin_obj, 12*60*60 );
			return $plugin_obj;
		}
		//Don't try again for 5 minutes
		set_site_transient( $key, '', 5*60 );
		return false;
	}

	public function force_request(){
		//return false; //For now.
		//We don't use get_current_screen() because of conclict with InfiniteWP
		global $current_screen;

		if ( ! isset( $current_screen ) )
			return false;

		return isset($current_screen->id) && ( 'plugins' == $current_screen->id || 'update-core' == $current_screen->id );
	}
}
$eventorganiser_venue_markers_plm_update_handler = new eventorganiser_venue_markers_PLM_Update_Handler();
?>