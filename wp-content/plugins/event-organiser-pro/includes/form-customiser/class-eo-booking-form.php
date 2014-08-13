<?php
/**
 * The Booking Form class
 * @package booking-form
 */
class EO_Booking_Form{

	/**
	 * The form's ID (post ID)
	 * @var int
	 */
	var $id = 0;

	
	/**
	 * Array of EO_Booking_Form_Element
	 */
	public $elements = array();


	/**
	 * WP_Error object containing errors from the user submission. 
	 * @see EO_Booking_Form::add_error()
	 * @see EO_Booking_Form::get_error_codes()
	 * @see EO_Booking_Form::get_error_message()
	 * @var WP_Error
	 */
	public $errors = false;
	
	
	/**
	 * Array of attributes. Currently used for
	 * 
	 * **event_id** - ID of the event for which are displaying the booking form
	 * **page_id** - ID of the post where the form is displayed. May be different from event_id.
	 * 
	 * Use {@see get()} to retrieve values. 
	 * @access protected
	 * @var array
	 */
	protected $attributes = array();
		
	
	/**
	 * Booking Form constructor which accepts an array of attributes
	 * 
	 * An ID **must** be provided. The $attributes array is used to 
	 * populate the form. Alternatively, you can just provide an ID
	 * and use {@see EO_Booking_Form::fetch()} to populate the form. 
	 * 
	 * @param array $attributes An array of attributes. An ID must be provided. 
	 */
	function __construct( $attributes = array() ){

		$id = 0;
		if( isset( $attributes['id'] ) ){
			$id = intval( $attributes['id'] );
			unset( $attributes['id'] );
		}
		
		if( $id <= 0 ){
			trigger_error( "Invalid booking form ID", E_USER_WARNING );
		}
		//Set ID
		$this->id = (int) $id;
		
		$this->_elements = new EO_Booking_Form_Elements();
		$this->_elements->form = $this;
		$this->errors = new WP_Error();
		
		//Backwards compat
		$this->elements =& $this->_elements->elements;
		
		$this->attributes = $attributes;
	}

	/**
	 * Fetches the data for the booking form from the database. 
	 * 
	 * This is used when initialising the booking with an ID only:
	 * <code>
	 * $form = new EO_Booking_Form( 3 );
	 * $form->fetch(); //populates form fields from databse
	 * </code>
	 */
	function fetch(){
		
		if( 'eo_booking_form' !== get_post_type( $this->id ) ){
			return false;
		}
		
		$element_types = EO_Booking_Form_Controller::get_element_types();
		
		$raw_elements = get_post_meta( $this->id, '_eo_booking_form_fields', true );
		$elements = ( $raw_elements ? $raw_elements : array() );
		
		if( $elements ){
			foreach( $elements as $id => $element ){
				
				//Backwards compatible (element_type => type, element_id => id )
				$element['element_id'] = $id;
				if( !empty( $element['element_type'] ) && empty( $element['type'] ) ) {
					//Backwards compatible (radiobox -> radio )
					$element['element_type'] = ( $element['element_type'] == 'radiobox' ? 'radio' : $element['element_type'] );
					$element['type'] = $element['element_type'];
				}
				
				$type = $element['type'];
				$element['id'] = $id;
		
				if( !isset( $element_types[$type] ) )
					continue;
		
				$classname = $element_types[$type];
				$this->add_element( new $classname( $element, $this ) );
			}
		}
		
		//Add required elements if they are not present
		if( !$this->get_element( 'ticketpicker' ) ){
			$classname = $element_types['ticketpicker'];
			$this->add_element( new $classname( array(
					'id'		=> 'ticketpicker',
					'type'		=> 'ticketpicker',
					'required'	=> 1,
					'position'	=> count( $this->get_elements() ),
			), $this ) );
		}
		
		if( !$this->get_element( 'gateway' ) ){
			$classname = $element_types['gateway'];
			$this->add_element( new $classname( array(
					'id'		=> 'gateway',
					'type'		=> 'gateway',
					'required'	=> 1,
					'label'		=> __( 'Select a payment gateway', 'eventorganiserp' ),
					'position'	=> count( $this->get_elements() )
			), $this ) );
		}
		
		if( !$this->get_element( 'submit' ) ){
			$classname = $element_types['button'];
			$this->add_element( new $classname( array(
					'id'		=> 'submit',
					'type'		=> 'button',
					'required'	=> 1,
					'label'		=> __( 'Book', 'eventorganiserp' ),
					'position'	=> count( $this->get_elements() )
			), $this ) );
		}	
		
		if( !$this->get_element( 'email' ) ){
			$this->add_element( 
				new EO_Booking_Form_Element_Email( array(
					'id'         => 'email',
					'field_name' => 'email',
					'required'   => true,
					'label'      => __( 'Email', 'eventorganiserp')
				)), 
				array( 'at' => 0 ) 
			);
		}
	
		if( !$this->get_element( 'name' ) ){
			$this->add_element( 
				new EO_Booking_Form_Element_Name( array(
					'id'         => 'name',
					'field_name' => 'name',
					'required'   => true,
					'label'      => __( 'Name', 'eventorganiserp')
				)), 
				array( 'at' => 0 ) 
			);
		}
		
		//Set name
		$form_obj = get_post( $this->id );
		$this->set( 'name', $form_obj->post_name );
		
		//Set title
		$meta = get_post_custom( $this->id );
		if( !isset( $meta['_eventorganiser_booking_form_title'] ) )
			$title = esc_html__( 'Booking', 'eventorganiserp' );
		else
			$title = $meta['_eventorganiser_booking_form_title'][0];		
		$this->set( 'title', $title );
		
		//Set notice classes
		$classes = get_post_meta( $this->id, '_eventorganiser_booking_notice_classes', true );
		if( empty( $classes ) ) $classes = 'eo-booking-notice';
		$classes = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $classes ) ) );
		$this->set( 'notice_classes', $classes );
		
		//Button classes
		$class = get_post_meta( $this->id, '_eventorganiser_booking_button_classes', true );
		if( empty( $class ) ){
			$class = 'eo-booking-button';
		}
		$this->set( 'button_classes', $class );
		
		//Error classes
		$classes = get_post_meta( $this->id, '_eventorganiser_booking_error_classes', true );
		if( empty( $classes ) ) $classes = 'eo-booking-error';
		$classes = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $classes ) ) );
		$this->set( 'error_classes', $classes );
		
		//Button text
		$text = get_post_meta( $this->id, '_eventorganiser_booking_button_text', true );
		if( empty( $text ) ){
			$text = esc_attr__( 'Book', 'eventorganiserp' );
		}
		$this->set( 'button_text', $text );
				
		return true;
	}
	
	/**
	 * Returns the booking form in JSON format.
	 * 
	 * The JSON array includes all form attributes, and all
	 * elements (including nested ones) and all their attributes. 
	 * 
	 * @return array The booking form in JSON
	 */
	function toJSON(){
		
		$array = array_merge(
			$this->attributes, 
			array(
				'id' => $this->id,
				'elements' => array(),
			)	
		);
		$array['elements'] = $this->_elements->toJSON();

		return $array;
	}
	
	
	/**
	 * Add an element to the form.
	 * 
	 * `$element` may be an instance of @see{EO_Booking_Form_Element} or JSON array
	 * specifying an element type (`input`, `select`, `radio` etc) 
	 * 
	 * @param EO_Booking_Form_Element|array An element to add to the form
	 * @param array Settings currently only 'parent' and 'at' are supported.
	 * @return boolean False if element ID already exists, true if not
	 */
	function add_element( $element, $settings = array() ){
		$added = false;
		if ( $element instanceof iEO_Booking_Form_Element ){
			$element->form = $this;
		}
		if( !empty( $settings['parent'] ) ){
			$parent = $this->get_element( $settings['parent'] );
			if( $parent && !empty( $parent->can_have_children ) ){
				$added = $parent->_elements->add( $element, $settings );
			}
		}else{
			$added = $this->_elements->add( $element, $settings );
		}
		return $added;
	}
	
	/**
	 * Remove an element from the form.
	 * 
	 * @see EO_Booking_Form_Elements::remove()
	 * @param EO_Booking_Form_Element|int The (ID of) the element to remove
	 * @return boolean Whether the element was successfully removed
	 */
	function remove_element( $element_id ){
		$element = $this->get_element( $element_id );
		if( !$element ){
			return false;
		}
		$parent  = $element->get_parent();
		
		if( $parent ){
			$result = $parent->_elements->remove( $element );
		}else{
			$result = $this->_elements->remove( $element_id );
		}
		
		return $result;
	}
	
	/**
	 * Get the specified form element.
	 * 
	 * This method looks in nested elements too. 
	 * @return EO_Form_Element|bool false if element ID does not exist, the element if it does
	 */
	function get_element( $element_id ){
		$flattened_form = $this->flatten_elements();
		return isset( $flattened_form[$element_id] ) ? $flattened_form[$element_id] : false;
		
	}
	
	/**
	 * Returns an array of form elements ({@see EO_Booking_Form::fetch()})
	 * 
	 * @return boolean|array False on error, Otherwise an array of element instances.
	 */
	function get_elements(){		
		return apply_filters( 'eventorganiser_booking_form_elements', $this->_elements->get(), $this->id );
	}
	
	/**
	 * Returns all elements in the form in a flattened array
	 * @return array Array of elements (`EO_Form_Element`)
	 */
	function flatten_elements(){
		return $this->_elements->flatten();
	}
		
	/**
	 * Helper function, set a booking form attribute.
	 * 
	 * See {@see EO_Booking_Form::get()} fora list of 'core' attributes.
	 * 
	 * @param string $attribute The attribute identifier
	 * @param mixed $attribute The value to set for the attribute.
	 */
	function set( $attribute, $value ){
		return $this->attributes[$attribute] = $value;
	}
	
	/**
	 * Helper function, get a booking form attribute. 'Core' attributes
	 * included:
	 * - `title`
	 * - `name`
	 * - `notice_classes`
	 * - `error_classes`
	 * - `button_text`
	 * - `button_classes`
	 * 
	 * @param string $attribute The attribute identifier
	 * @return multitype: The value of the attribute
	 */
	function get( $attribute ){
		return isset( $this->attributes[$attribute] ) ? $this->attributes[$attribute] : null;
	}
	
	
	/**
	 * Validates the recieved data for this booking form
	 * 
	 * Goes through elements, and calls validate on each.
	 * The passed `$input` array is DEPRECATED.
	 * 
	 * @see EO_Booking_Form_Element::validate()  
	 * @param array $input DEPRECATED.
	 */
	function validate( $input ){
		foreach( $this->get_elements() as $element_id => $element ){
			$element->validate( $input );
		}
	}
	
	/**
	 * Adds an error to the *booking form*, codes should be unique. 
	 * Message is displayed at the top of the form
	 * @see WP_Error
	 * @param string $code    A unique error code
	 * @param string $message A message as it appears on the form
	 * @param array  $data    Any data associated witht this error
	 */
	function add_error( $code, $message, $data = '' ){
		$this->errors->add( $code, $message, $data );
	}
	
	/**
	 * Gets the error codes of the form's errors.
	 * @see WP_Error
	 * @return array An array of error codes added to the form
	 */
	function get_error_codes(){
		return $this->errors->get_error_codes();
	}
	
	/**
	 * Get the error message for the specified code.
	 * 
	 * This will get the first message available for the code. If no code is
	 * given then the first code available will be used.
	 *
	 * @param string|int $code Optional. Error code to retrieve message.
	 * @return string
	 */
	function get_error_message( $code = '' ){
		return $this->errors->get_error_message( $code );
	}
	
	/**
	 * Returns true if any errors have been added to the form
	 * or to the form's elements
	 * 
	 * @param bool True if any error have been added to the form  or to the form's elements
	 */
	function has_errors(){
		if( $this->errors->get_error_codes() ){
			return true;
		}else{
			$flatten = $this->flatten_elements();
			foreach( $flatten as $element ){
				if( $element->has_errors() ){
					return true;
				}
			}
		}
		return false;
	}


	/**
	 * Method for saving received data to the database.
	 * 
	 * Loops through each of the form's elements calling their save method,
	 * passing the booking ID associated with the form submission.
	 * 
	 * @see EO_Booking_Form_Element::save()
	 * @param unknown_type $booking_id
	 */
	public function save( $booking_id ){
		
		//Save custom booking meta
		foreach( $this->get_elements() as $element_id => $element ){
			//Pass input for backwards combat with discount codes
			$input = $_POST['eventorganiser']['booking'];
			$element->save( $booking_id, $input );
		}
	
		do_action( 'eventorganiser_booking_form_saved', $this, $booking_id );
	}
	
	/**
	 * Returns the classes added to the form notices
	 * @return string The user entered notice classes
	 */
	function get_form_notice_classes(){
		return apply_filters( 'eventorganiser_booking_notice_classes', $this->get('notice_classes'), $this );
	}
	
	/**
	 * Returns the classes added to the error messages
	 * @return string The user entered error classes
	 */
	function get_form_error_classes(){
		return apply_filters( 'eventorganiser_booking_error_classes', $this->get('error_classes'), $this );
	}
	
	/**
	 * Get the user-specified option for the booking section title.
	 * @return string The user entered booking title.
	 */
	function get_form_title(){
		return apply_filters( 'eventorganiser_booking_title', $this->get('title') );
	}
	
	/**
	 * Is the form option set to 'simple booking mode'
	 * @return boolean True if SBM is enabled.
	 */
	function is_simple_booking_mode(){
		$ticketpicker = $this->get_element( 'ticketpicker' );
		return (bool) $ticketpicker->get( 'simple_mode' );
	}
	
	/**
	 * Deprecated. Kept for backwards compatability. Do not use.
	 * @deprecated
	 * @ignore
	 */
	function form_hidden_fields(){}
	
	/**
	 * Deprecated. Kept for backwards compatability. Do not use.
	 * @deprecated
	 * @ignore
	 */
	function get_view(){
	
		if( !isset( $this->view ) ){
			$this->view = new EO_Booking_Form_View( $this );
		}
	
		return $this->view;
	}
	
		/**
	 * Returns the booking submit button text
	 * This will be deprecated as of 1.8.0
	 * @return string The user entered booking form button text.
	 */
	function get_form_button_text(){
		return apply_filters( 'eventorganiser_booking_button_text', $this->get('button_text'), $this );
	}
	
	/**
	 * Returns the classes added to the booking submit button
	 * This will be deprecated as of 1.8.0
	 * @return string The user entered booking form button classes
	 */
	function get_form_button_classes(){
		return apply_filters( 'eventorganiser_booking_button_classes', $this->get('button_classes'), $this );
	}
	

}