<?php
/**
 * Handles form submissions
 * @ignore
 */
class EO_Booking_Form_Controller{

	private static $instance = null;

	public static $elements = array();
	
	public static $metaboxes = array();
	
	public static $form = false;
	
	/**
	 * Singleton model
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Construct the controller and listen for form submission 
	 */
	public function __construct() {

		//Singletons!
		if ( !is_null( self::$instance ) )
			trigger_error( "Tried to construct a second instance of class \"$class\"", E_USER_WARNING );
		
		add_action ( 'plugins_loaded', array( __CLASS__, 'load' ) );
	}
	
	static function load(){
		
		//Register element types & metaboxes
		self::element_types_init();
		
		//Submission listener
		add_action( 'init', array( __CLASS__, 'listen_for_submission' ), 11 );
		
		//Booking form customiser admin view
		add_action( 'eventorganiser_event_settings_booking-form', array( __CLASS__, 'display_form_for_admin' ) );
		
		//Booking form customiser (admin) listener
		add_action( 'wp_ajax_eo-bfc-form', array( __CLASS__, 'form_server_handler' ) );
		add_action( 'wp_ajax_eo-bfc-form-element', array( __CLASS__, 'form_element_server_handler' ) );
	}
	
	/**
	 * Set up the element types and metaboxes, runs only runce and applies the filters
	 * `eventorganiser_booking_form_element_types` and `eventorganiser_booking_form_element_metaboxes`
	 */
	static function element_types_init(){
	
		//Only run once
		if( !empty( self::$elements ) && !empty( self::$metaboxes ) )
			return;
			
		self::$elements = array(
			'standard' => array(
				'select'      => 'EO_Booking_Form_Element_Select', 
				'input'       => 'EO_Booking_Form_Element_Input', 
				'textarea'    => 'EO_Booking_Form_Element_Textarea',
				'radio'       => 'EO_Booking_Form_Element_Radio', 
				'checkbox'    => 'EO_Booking_Form_Element_Checkbox',
				'multiselect' => 'EO_Booking_Form_Element_Multiselect',
			),
			'advanced' => array(
				'number'   => 'EO_Booking_Form_Element_Number',
				'section'  => 'EO_Booking_Form_Element_Section', 
				'html'     => 'EO_Booking_Form_Element_Html', 
				'fieldset' => 'EO_Booking_Form_Element_Fieldset',
				'address'  =>'EO_Booking_Form_Element_Address', 
				'phone'    => 'EO_Booking_Form_Element_Phone', 
				'email'    => 'EO_Booking_Form_Element_Email',
				'date'     => 'EO_Booking_Form_Element_Date', 
				'url'      => 'EO_Booking_Form_Element_Url',
				'antispam' => 'EO_Booking_Form_Element_Antispam', 
				'terms_conditions' => 'EO_Booking_Form_Element_Terms_Conditions',
				'hook'     => 'EO_Booking_Form_Element_Hook',
			),
			'_required' => array(
				'gateway'      => 'EO_Booking_Form_Element_Gateway',
				'ticketpicker' => 'EO_Booking_Form_Element_Ticketpicker',
				'name'         => 'EO_Booking_Form_Element_Name',
				'button'       => 'EO_Booking_Form_Element_Button',
			),
		);
	
		self::$metaboxes = array(
				'standard' => __( 'Standard Fields','eventorganiserp' ), 'advanced' => __( 'Advanced Fields','eventorganiserp' ),
		);
	
		//Filter these to allow add-ons to add field types & metaboxes
		self::$elements = apply_filters( 'eventorganiser_booking_form_element_types', self::$elements );
		self::$metaboxes = apply_filters( 'eventorganiser_booking_form_element_metaboxes', self::$metaboxes );
	}
	
	/**
	 * Handles request from the booking form customiser (but not elements).
	 * Handles method:
	 * * **POST**	- insert new form
	 * * **GET**	- retrieve specified form
	 * * **DELETE** - delete specified form
	 * * **PUT** 	- update specified form 
	 */
	static function form_server_handler(){
		
		$payload = json_decode( file_get_contents('php://input'), true );
		$form_id = !empty( $payload['id'] ) ? (int) $payload['id'] : 0;
		$method  = self::_get_method(); 	 

		if( !$method ){
			wp_die( 'request not found' );
		}
		
		if( !current_user_can( 'manage_options' ) ){
			wp_die( 'You do not have permission to edit the booking forms' );
		}
		
		check_ajax_referer( 'eo-bfc-nonce', '_nonce' ); 
				
		//No form ID, creating new:
		if( empty( $form_id ) && 'POST' == $method ){
			$form_id = wp_insert_post( array( 'post_status' => 'publish', 'post_type' => 'eo_booking_form', 'post_title' => '' ) );

		}elseif( !empty( $_GET ) && !empty( $_GET['id'] ) && 'GET' == $method ){
			$form_id = (int) $_GET['id'];
			$form = new EO_Booking_Form( array( 'id' => $form_id ) );
			$form->fetch();
			wp_die( json_encode( $form->toJSON() ) );
			
		}elseif( 'DELETE' == $method ){
			$form_id = (int) $_GET['id'];
			wp_delete_post( $form_id );
			wp_die('1');
						
		}elseif( 'PUT' !== $method ){
			wp_die( 'die' );
		}

		//Elements
		$elements = array();
		if( !empty( $payload['elements']  ) ){
			foreach( $payload['elements'] as $index => $element ){
				$elements[$element['id']] = $element;
			}
		}
		update_post_meta( $form_id, '_eo_booking_form_fields', $elements );
		
		wp_update_post( array( 'ID' => $form_id, 'post_name' => $payload['name'] ) );
		
		$settings = array(
			'title' => '_eventorganiser_booking_form_title',
			'notice_classes' => '_eventorganiser_booking_notice_classes',
			'button_classes' => '_eventorganiser_booking_button_classes',
			'error_classes' => '_eventorganiser_booking_error_classes',
			'button_text' => '_eventorganiser_booking_button_text',
		);
		foreach( $settings as $setting => $meta_key ){
			update_post_meta( $form_id, $meta_key, $payload[$setting] );
		}
		
		$form = new EO_Booking_Form( array( 'id' => $form_id ) );
		$form->fetch();
		
		wp_die( json_encode( $form->toJSON() ) );
	}
	
	/**
	 * Returns a new (unique) ID back to the booking form customiser for use with
	 * a form element.
	 */
	static function form_element_server_handler(){
	
		$payload = json_decode( file_get_contents('php://input'), true );
		$form_id = !empty( $payload['id'] ) ? (int) $payload['id'] : 0;
		$method  = self::_get_method(); 	

		if( !$method ){
			wp_die( 'request not found' );
		}

		if( !current_user_can( 'manage_options' ) ){
			wp_die( 'You do not have permission to edit the booking forms' );
		}		
		
		//Creating new:
		if( 'POST' == $method ){
			$id = eventorganiser_pro_get_option( 'element_id', 2 );
			$id++;
			
			$type = $payload['type'];
			$types = EO_Booking_Form_Controller::get_element_types();
			
			if( !isset( $types[$type] ) )
				wp_die( -1 );
				
			$classname = $types[$type];
			$element = new $classname( array( 'id' => $id, 'type' => $type ) );
			
			eventorganiser_pro_update_option( 'element_id', $id );
			wp_die( json_encode( $element->toJSON() ) );
			
		}else{
			wp_die('error');
		}
	
	}
	
	static function _get_method(){
		
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : false;
		
		if( isset( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ) ){
			$method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];	
		}
		
		if( in_array( $method, array( 'PUT', 'DELETE', 'POST', 'GET' ) ) ){
			return $method;
		}
		return false;
	}
	/**
	 * Get element types in a specified metabox. If no metabox is specified, it gets all elements from all metaboxes,
	 * essentially flattening `EO_Booking_Form::$elements`.
	 *
	 * @param string $metabox Metabox identifier or leave blank to retrieve all elements.
	 * @return array Array of the form [element idenitfier] => [element class]
	 */
	static function get_element_types( $metabox = false ){
	
		if( empty( self::$elements ) )
			self::element_types_init();
	
		if( $metabox ){
			$elements = self::$elements[$metabox];
				
		}else{
			$elements = array();
			foreach( self::$elements as  $metabox => $m_elements ){
				$elements += $m_elements;
			}
		}
	
		return $elements;
	}
	
	
	static function display_form_for_admin( $args ) {

		$form_id = !empty( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0;
		
		//If no form is specified, retrieve the next one
		if( empty( $form_id ) ){
			$form_ids = eventorganiser_get_booking_forms( array( 'fields' => 'ids', 'posts_per_page' => 1 ) );
			$form_id = array_pop( $form_ids );
			
			//If no forms can be found, create a new one
			if( !$form_id ){
				$form_id = wp_insert_post( array( 'post_status' => 'publish', 'post_type' => 'eo_booking_form', 'post_title' => 'Booking Form' ) );
			}
		}
		
		//Sanity checks
		if ( false === $form_id || get_post_type( $form_id ) != 'eo_booking_form' )
			return false;
		
		
		//Get form and display
		$form = new EO_Booking_Form( array( 'id' => $form_id ) );
		$form->fetch();
		
		wp_localize_script( 'eo-booking-form', 'eo', array(
			'form'          => $form->toJSON(),
			'url'           => admin_url( 'admin-ajax.php' ),
			'forms'         => self::get_forms(),
			'element_types' => self::get_element_type_names(),
			'locale'        => self::get_locale(),
			'nonce'         => wp_create_nonce( 'eo-bfc-nonce' ),
		));

		wp_enqueue_script( 'eo-booking-form' );
		wp_enqueue_style( 'eo-booking-form-customiser' );
		?>
		<div id="poststuff"></div> 
		
		<?php if( ini_get( 'asp_tags' ) ): ?> 
 		
 			<div class="error below-h2">
 				<h3>Server Configuration Problem: asp_tags enabled</h3>
 				<p>You can disable asp_tags in .htaccess or php.ini </p>
 			</div>
 		
		<?php else:
 		
			require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/templates/admin-view/eo-booking-form-templates.php' );
			require_once( EVENT_ORGANISER_PRO_DIR . 'includes/form-customiser/templates/admin-view/eo-booking-form-elements-templates.php' );
		 	do_action( 'eventorganiser_booking_form_customiser_print_templates' );
		 
		endif; 		 
	}
	
	static function get_locale(){
		$locale = array();
		if( 'en_US' !== get_locale() && file_exists( EVENT_ORGANISER_PRO_DIR . 'languages/eventorganiserp-'.get_locale().'.json' ) ){
			$locale_file = file_get_contents( EVENT_ORGANISER_PRO_DIR . 'languages/eventorganiserp-'.get_locale().'.json' );
			$locale = json_decode( $locale_file, true );	
		}
		return $locale; 
	}
	
	static function get_form( $form_id ){
		$form = new EO_Booking_Form( array( 'id' => $form_id ) );
		$form->fetch();
		return $form;	
	}
	
	static function get_forms(){
	
		$forms = array_values( eventorganiser_get_booking_forms() );
		$_forms = array();
	
		foreach( $forms as $index => $form ){
			$forms[$index] = array(
				'name' => $form->post_name,
				'id' => $form->ID
			);
		}
		
		return $forms;
	}
	
	static function get_element_type_names(){
		
		$elements = array();
		
		foreach( self::$elements as $metabox => $element_types ){
		
			//Ignore 'hidden' metaboxes
			if( $metabox[0] == '_' )
				continue;
			
			$elements[$metabox] = array(
				'metabox' => $metabox,
				'label' => self::$metaboxes[$metabox],
				'element_types' => array()
			);
		
			foreach ( $element_types as $type => $element ){
				$name = call_user_func( array( $element, 'get_type_name' ) );
				$elements[$metabox]['element_types'][] = array(
					'type' => $type,
					'name' => $name,
				);
			}
		}
		
		return $elements;
	}
	
	/**
	 * Call back used to recognize a form submission
	 * @param array $data
	 * @return boolean True if this is a booking form, false otherwise
	 */
	static function is_form( $data = array() ){
		return isset( $data['action'] ) && 'eventorganiser-submit-form' == $data['action'] && !empty( $data['eventorganiser-form-id'] );
	}


	
	/**
	 * Callback performs is_form() check, and if so, triggers the form
	 * submission handler.
	 */
	static function listen_for_submission(){

		if( self::is_form( $_POST ) ){
			
			//Collect variables
			$input = $_POST['eventorganiser']['booking'];
			$form_id = (int) $_POST['eventorganiser-form-id'];
			
			//Trigger form submission routine
			self::form_submission( $input, $form_id );
			
		}
		
	}


	/**
	 * Form submission handler. In particular
	 * * Initialises the form
	 * * Validates the input
	 * * If there are errors aborts submission processing
	 * * Otherwise processes form
	 * * Triggers 'eventorganiser_process_booking_submission' hook
	 */
	static function form_submission( $input, $form_id ){
		
		$errors = new WP_Error();

		//Initialise variables
		$event_id = isset(  $input['event_id'] ) ?  $input['event_id'] : false;
		$occurrence_id = isset(  $input['occurrence_id'] ) ?  $input['occurrence_id'] : false;
		$page_id = isset(  $input['page_id'] ) ?  $input['page_id'] : false;
		
		//Initialise form
		$form = eo_get_event_booking_form( $event_id );		
		self::$form = $form;
		
		$form->set( 'event_id', $event_id );
		$form->set( 'occurrence_id', $occurrence_id );
		$form->set( 'page_id', $page_id );
		
		//If $tickets is just a value, assume its the ticket ID and we're purchasing 1 of that ticket.
		$ticketpicker = $form->get_element( 'ticketpicker' );
		$tickets = $ticketpicker->get_value();
		$tickets = is_array( $tickets ) ? $tickets : array( $tickets => 1 );
		$tickets = $ticketpicker->set_value( $tickets );
				
		//Deprecated -- to be removed in 1.8
		do_action_ref_array( 'eo_booking_form_submission', array( $input, $form_id, &$form ) );
		
		if( is_user_logged_in() ){
			$name    = $form->get_element( 'name' );
			$email   = $form->get_element( 'email' );
	
			global $current_user;
			get_currentuserinfo();
			$name->set_value( array( 
				'fname' => $current_user->user_firstname ? $current_user->user_firstname : $current_user->display_name,
				'lname' => $current_user->user_lastname
			) );	
			$email->set_value( $current_user->user_email );
		}
		
		//Validate the form
		$form->validate( $input );
		
		//Filtering $input no longer has any affect. Deprecated. 
		$input = apply_filters_ref_array( 'eventorganiser_validate_booking_submission', array( $input, &$form, $form->errors ) );
		
		do_action( 'eventorganiser_validate_booking_form_submission', $form );
						
		if( !$form->has_errors() ){
		
			//Deprecated
			do_action_ref_array( 'eventorganiser_process_form', array( $input, &$form, $form->errors ) );
			
			//Process the form
			do_action( 'eventorganiser_process_booking_form_submission', $form );
	
		}
			
	}
		
}
$eo_booking_form_controller = EO_Booking_Form_Controller::get_instance();


//TODO (Future) update db to replace 'checked' in Checkboxes with 'selected'
//TODO (Future) update db to replace 'radiobox' by 'radio'