<?php
/**
 * The Form element class
 * @package booking-form
 */

/**
 * The base form element class (abstract).
 * 
 * This must be extended by a class. The naming convention for child class is
 * `EO_Booking_Form_Element_{Type}`. where `Type` is the camel-casing of the
 * elements type (`input`,`select`,`radio` etc).
 */
abstract class EO_Booking_Form_Element implements iEO_Booking_Form_Element{

	/**
	 * The type of element. If not set, assumes class name `EO_Booking_Form_Element_{Type}` 
	 * @var string
	 */	
	var $type = false;
	
	/**
	 * Stores the booking form to which this element belongs. This is
	 * set in the constructor
	 * @var EO_Booking_Form
	 */
	var $form = false;
	
	/**
	 * Whether a label should be shown for this element.
	 * @var boolean
	 */
	var $show_label = true;
	
	/**
	 * Stores any errors associated with this element
	 * @see add_error()
	 * @var WP_Error
	 */
	var $errors = false;
	
	/**
	 * Whether this element can have other elements nested beneath it.
	 * @var boolean
	 */
	var $can_have_children = false;
	
	/**
	 * Constructs the element, parses the `$parameters` array and sets the 
	 * element's properties
	 * 
	 * If in context element is tied to a form, pass as `$parent_form`.
	 * 
	 * See {@see set()} for details on 'core' parameters. 
	 * 
	 * @param array $parameters Array of parameters. An ID must be provided.
	 * @param EO_Booking_Form $parent_form Pass the parent form if appropriate.
	 */
	final function __construct( $parameters = array(), $parent_form = false ){

		/*By default expect EO_Booking_Form_Element_{elment type}*/
		$element_type = ( $this->type ? $this->type : strtolower( substr( get_class( $this ), 24 ) ) );

		//Backwards compatible (element_type => type, element_id => id )
		$parameters['element_type'] = $element_type;
		if( !empty( $parameters['element_id'] ) && empty( $parameters['id'] ) ){
			$parameters['id'] = $parameters['element_id'];
		}
		
		if( isset( $parameters['id'] ) ){
			$this->id = $parameters['id'];
			unset( $parameters['id'] );
		}
		$this->type = $element_type;
		
		if( isset( $this->id ) && in_array( $this->id, array( 'id', 'type' ) ) ){
			trigger_error( "Invalid ID: reserved keyword", E_USER_WARNING );
		}
		
		$this->form = $parent_form;
		$this->errors = new WP_Error();

		$this->parameters = array_merge( $this->get_defaults(), $parameters );
		
		if( $this->can_have_children ){
			
			$this->_elements = new EO_Booking_Form_Elements();
			$this->_elements->parent = $this;
			if( $this->get('elements') ){
				$this->_elements->set( $this->get('elements'), array( 'form' => $this->form ) );
				$this->set('elements', null );
			}
		}
		
	}

	function toJSON(){
		
		$array = $this->parameters;
		if( !empty( $this->form ) ){
			//$array['form_id'] = $this->form->id; 
		}
		$array['id'] = $this->id;
		$array['type'] = $this->type;
		$array['name'] = $this->get_type_name();
		
		//Backwards compatible (checked --> selected )
		if( empty( $array['selected'] ) && !empty( $array['checked'] ) ){
			$array['selected'] = $array['checked'];
		}
		
		//Backwards compatible (selected should be integer / array of integers, not strings);
		if( !empty( $array['selected'] ) || ( isset( $array['selected'] ) && ( $array['selected'] === 0 || $array['selected'] === "0" ) ) ){
			$array['selected'] = is_array( $array['selected'] ) ? array_map( 'intval', $array['selected'] ) : intval ( $array['selected'] );
		}
		
		if( $this->can_have_children ){
			$array['elements'] = $this->_elements->toJSON();
			//$array['parent'] = ( $this->get_parent() ? $this->get_parent()->id : 0 );
		}
		
		//Deprecated, remove these
		$unset = array( 'element_type', 'element_id', 'parent' );
		foreach( $unset as $key ){
			if( isset( $array[$key] ) ){
				unset( $array[$key] );
			}
		}
		
		return $array;
	}
	
	/**
	 * Returns the type of the booking form element.
	 * 
	 * Default types include:
	 *  - `input`
	 *  - `select`
	 *  - `radio`
	 *  - `date`,
	 *  - `terms_conditions`...	 
	 * @return string
	 */
	final function get_type(){
		return $this->type; 
	}

	/**
	 * Sets a parameter of the element
	 * 
	 * What attributes are used will depending on the implementing classes,
	 * but some core attributes include:
	 * - `id` - The elements ID (unique to the booking form)
	 * - `required` - Whether the user must enter a value for this element 
	 * - `class` - HTML class to add to the element
	 * - `options` - An array of possible options (e.g. in `select`/`radio`/`checkbox` fields)
	 * - `selected` - The selected value (e.g. in `select`/`radio`/`checkbox` fields)
	 * - `value` - The user-entered value ({@see set_value()});
	 * 
	 * @param $param
	 * @param $value 
	 */
	final function set( $param, $value ){
		$this->parameters[$param] = $value;
	}
	
	/**
	 * Returns the specified parameter
	 * @param $param
	 * @return mixed
	 */
	final function get( $param ){
		if( $param == 'id' ){
			return $this->id;
		}
		return isset( $this->parameters[$param] ) ? $this->parameters[$param] : null;
	}
	
	
	/**
	 * Returns the HTML name attribute for this element.
	 * 
	 * Some elements might have multiple fields (e.g. address element).
	 * The `$component` can be used to return the field name for a 
	 * specific field.
	 * 
	 * @return string Optional, get the field name for a specific component.
	 */
	function get_field_name( $component = false ){
		$name = ( $this->get( 'field_name' ) ? $this->get( 'field_name' ) :  'eventorganiser[booking]['.$this->id.']' );
		if( $component !== false ){
			$name .='['.esc_attr( $component ) . ']';
		}
		return $name;
	}
	
	
	/**
	 * Returns the CSS class of this element
	 * 
	 * Gets admin-set classes and adds the appropriate core class.
	 * @see get()
	 * @return string The HTML class attribute for this element
	 */
	function get_class(){
		$class = $this->get( 'class' ) ? trim( $this->get( 'class' ) ) : '';
		$class .= ' eo-booking-field-' . str_replace( '_', '-', $this->type );
		$class = apply_filters( 'eventorganiser_booking_element_classes', $class, $this );
		return trim( $class );
	}
	
	
	/**
	 * Sets the element's value as entered by the user.
	 * 
	 * If the booking form has associated user-entered data, this 
	 * sets the field's value to the given value. The `$component`
	 * argument can be used if an element has multiple fields
	 * 
	 * @param mixed $value The given value to assign to this field. 
	 * @param string Optional, set the value of a specific field component.
	 */
	function set_value( $value, $component = false ){
	
		if( $component !== false ){
			$current_value = $this->get('value');
			$current_value[$component] = $value;
			$value = $current_value;
		}
		
		$this->set( 'value', $value );
	}
	
	
	/**
	 * Returns the user-entered value associated with this element.
	 * 
	 * @param string Optional, get the value of a specific field component.
	 * @return mixed
	 */
	function get_value( $component = false ){
	
		$value = $this->get('value');
		if( $component !== false ){
			$value = ( is_array( $value ) && isset( $value[$component] ) ) ? $value[$component] : false;
		}
		return $value;
	}
	
	/**
	 * Whether this form element is required.
	 * @return boolean
	 */
	function is_required(){
		return (bool) $this->get( 'required' );
	}

	
	/**
	 * Whether a label should be displayed for this element
	 * @return boolean
	 */
	function show_label(){
		return (bool) $this->show_label;
	}
	
	/**
	 * The parent of the element, if it's nested beneath another element.
	 * 
	 * If the element is 'top level', then this returns `false`/
	 * 
	 * @return EO_Booking_Form_Element|bool
	 */
	function get_parent(){
		if( empty( $this->collection ) ){
			return false;
		}
		return isset( $this->collection->parent ) ? $this->collection->parent : false;
	}


	/**
	 * Validate an entry for this form element.
	 * 
	 * TODO - don't pass $input, but instead use ::get_value()
	 * 
	 * Validates the recieved data `$input` is. The value for this particular element can be 
	 * found at `$input[$this->id]`. This shall be deprecated, use `$this->get_value()` instead.
	 * 
	 * `$errors` is a `WP_Error` object to add error messages to. These added to the form and displayed
	 * as at the top of the form. To add an error to the field use ::set_errors();
	 * 
	 * @uses is_valid()
	 * @uses is_required()
	 * @param array $input All data receieved from the user (array indexed by field ID)
	 * @param WP_Error $errors - Form errors, add an errors to display error message at the top of the field
	 */
	function validate( $input ){
			
		if( 'ticketpicker' == $this->id )
			return;
		 
		if( $this->is_required() && !$this->get_value() ){
			//Empty value for required field
			$this->form->add_error( 'required_field_missing', __( '<strong>ERROR</strong>: Please fill in all required fields', 'eventorganiserp' ) );
			$this->add_error( 'required_field_missing', false ); //no messages are currently shown next to the field
		
		}elseif ( $this->is_required() && in_array( $this->type, array( 'address', 'profile_name', 'antispam' ) ) && !array_filter( $this->get_value() ) ) {
			//Profile name, antispam & address should give us an array
			$this->form->add_error( 'required_field_missing', __( '<strong>ERROR</strong>: Please fill in all required fields', 'eventorganiserp' ) );
			$this->add_error( 'required_field_missing', false ); //no messages are currently shown next to the field
				
		}elseif( $this->get_value() && !$this->is_valid( $input ) ){
			//Value is present, but
			$this->form->add_error( 'invalid_data', __( '<strong>ERROR</strong>: Some fields are not valid', 'eventorganiserp' ) );
			$this->add_error( 'invalid_data', false ); //no messages are currently shown next to the field
		}		
		
		
		//Deprecated - Allow users to validate input.
		//Use `eventorganiser_validate_booking_submission` instead
		do_action_ref_array( 'eventorganiser_validate_element_input', array( $input, $this->id, $this->form->errors ) );
		
	}
	
	/**
	 * Adds an error to the *element*, codes should be unique.
	 * 
	 * The provided message is currently not used (but support is planned and
	 * will appear next to the field).
	 * 
	 * To add an error the form (e.g display message at top, 
	 * use {@see EO_Booking_Form::add_error)}
	 * @see EO_Booking_Form::add_error
	 * @see WP_Error
	 */
	function add_error( $code, $message = false, $data = '' ){
		$this->errors->add( $code, $message, $data );
	}
	
	/**
	 * Gets the error codes of the form's errors.
	 * @see WP_Error
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
	function get_error_message( $code ){
		return $this->errors->get_error_message( $code );
	}
	
	/**
	 * Returns true if any errors have been added to this element
	 * 
	 * @param bool True if any error have been added to this element
	 */
	function has_errors(){
		return (bool) $this->errors->get_error_codes();
	}
	
	/**
	 * A method which should return true if the data provided for this element
	 * is 'valid'.
	 * 
	 * Data entered for this element can be retreived with 
	 * <code>
	 * $this->get_value()
	 * </code>
	 * 
	 * @param array $input DEPRECATED
	 * @return boolean Whether the input is valid or not.
	 */
	function is_valid( $input ){
		return true;
	}
	
	/**
	 * Return an array of default parameters.
	 * @return array
	 */
	function get_defaults(){
		return array();
	}
	
	/**
	 * Saves the element input to the database
	 * 
	 * By default, the entered value ({@see get_value()}) is saved with meta key
	 * `_eo_booking_meta_{element-id}`. The label for the element is stored in 
	 * `_eo_booking_label_meta_{element-id}`
	 * 
	 *  Fields which do not save data include: `ticketpicker`, `antispam`, `section` & `html`) 
	 *
	 * TODO This perhaps should be divorced to another class.
	 *
	 * @since 1.5
	 * @param int $booking_id The booking ID to which the data to be saved belongs
	 */
	function save( $booking_id ){
		
		//Don't save these
		if ( in_array( $this->type, array( 'ticketpicker', 'antispam', 'section', 'html' ) ) ){
			return;
		}
		
		$key = '_eo_booking_meta_'.$this->id;
		$value = $this->get_value();
		
		if ( !is_array( $value ) ){
			$value = array( $value );
		}
		
		foreach ( $value as $v ){
			add_post_meta( $booking_id, $key, $v );
		}
		
		//Store labels in case booking form element disappears...
		add_post_meta( $booking_id, '_eo_booking_label_meta_'.$this->id, $this->get( 'label' ) );
	}
	
}

/**
 * An abstract class of a form element which can have elements nested beneath it.
 * 
 * This class is responsible for calling methods such as {@see EO_Booking_Form_Element::validate()}
 * and {@see EO_Booking_Form_Element::save()} on each of its child elements.
 *
 */
abstract class EO_Booking_Form_Element_Parent extends EO_Booking_Form_Element{

	/**
	 * Returns the collection of form elements beneath this element.
	 * @return EO_Booking_Form_Elements
	 */
	function get_children(){
		return ( $this->_elements ? $this->_elements : false );
	}
	
	/**
	 * Validates the field itself and then each of its children
	 * @see EO_Booking_Form_Element::validate()
	 */
	function validate( $input ){

		parent::validate( $input );
		
		if( $this->get_children() ){
			foreach( $this->get_children()->get() as $element ){
				$element->validate( $input );
			}
		}
	}

	/**
	 * Saves the field itself and then each of its children
	 * @see EO_Booking_Form_Element::save()
	 */
	function save( $booking_id ){

		parent::save( $booking_id );
		
		if( $this->get_children() ){
			foreach( $this->get_children()->get() as $element ){
				$element->save( $booking_id );
			}
		}
	}

	/**
	 * Returns true if any errors have been added to this element
	 * or  to any of the element's children
	 */
	function has_errors(){
		if( $this->errors->get_error_codes() ){
			return true;
		}else{
			if( $this->get_children() ){
				foreach( $this->get_children()->get() as $element ){
					if( $element->has_errors() ){
						return true;
					}
				}
			}
		}
		return false;
	}
}


/** @ignore **/
class EO_Booking_Form_Element_Input extends EO_Booking_Form_Element{

	static function get_type_name(){
		return __( 'Input', 'eventorganiserp' );
	}
	
	function get_field_type(){
		return 'text';
	}
	
	function get_data(){
		return array();
	}
	
}

/** @ignore **/
class EO_Booking_Form_Element_Select extends EO_Booking_Form_Element{

	static function get_type_name(){
		return __( 'Select', 'eventorganiserp' );
	}
	
	function get_defaults(){
		return array(
				'label' => __( 'Label', 'eventorganiserp' ),
				'selected' => false,
		);
	}
	
	function get_selected_value(){
		
		$options = $this->get( 'options' );
	
		//Default value
		if( $this->get( 'selected' ) || is_numeric( $this->get( 'selected' ) ) ){
			$selected_value = $options[$this->get( 'selected' )];
		}else{
			$selected_value = false;
		}
	
		//Set value
		$selected_value = !is_null( $this->get_value() ) ? $this->get_value() : $selected_value;
	
		return $selected_value;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Textarea extends EO_Booking_Form_Element{
	static function get_type_name(){
		return __( 'Textarea', 'eventorganiserp' );
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Radio extends EO_Booking_Form_Element{

	static function get_type_name(){
		return __( 'Radio Buttons', 'eventorganiserp' );
	}
	
	function get_defaults(){
		return array(
				'label' => __( 'Label', 'eventorganiserp' ),
				'selected' => false,
		);
	}
	
	function get_selected_value(){
		
		$options = $this->get( 'options' );
		
		//Default value
		if( $this->get( 'selected' ) || is_numeric( $this->get( 'selected' ) ) ){
			$selected_value = $options[$this->get( 'selected' )];
		}else{
			$selected_value = false;
		}
		
		//Set value
		$selected_value = !is_null( $this->get_value() ) ? $this->get_value() : $selected_value;

		return $selected_value;
	}

}

/** @ignore **/
class EO_Booking_Form_Element_Checkbox extends EO_Booking_Form_Element{

	static function get_type_name(){
		return __( 'Checkbox', 'eventorganiserp' );
	}

	function get_selected_values(){
	
		$options = $this->get( 'options' );
		$selected_values = array();
	
		//Default value
		if(  $this->get( 'checked' ) ){
			$selected_values = array_intersect_key( $options, array_flip( $this->get( 'checked' ) ) );
		}
	
		//Set value
		$selected_values = !is_null( $this->get_value() ) ? $this->get_value() : $selected_values;
	
		return $selected_values;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Hook extends EO_Booking_Form_Element{

	var $show_label = false;
	
	static function get_type_name(){
		return __( 'Hook', 'eventorganiserp' );
	}
	
	function get_defaults(){
		return array(
			'wp-action' => 'some_custom_action',
			'label' => false,	
		);
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Html extends EO_Booking_Form_Element{
	
	var $show_label = false;
	
	static function get_type_name(){
		return __( 'HTML', 'eventorganiserp' );
	}
	
}

/** @ignore **/
class EO_Booking_Form_Element_Section extends EO_Booking_Form_Element{

	var $show_label = false;
	
	static function get_type_name(){
		return __( 'Section Break', 'eventorganiserp' );
	}
	
	function get_defaults(){
		return array(
				'label' => __( 'Section', 'eventorganiserp' ),
		);
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Fieldset extends EO_Booking_Form_Element_Parent{

	var $show_label = false;

	var $can_have_children = true;

	var $_elements;

	static function get_type_name(){
		return __( 'Fieldset', 'eventorganiserp' );
	}

	function get_defaults(){
		return array(
				'label' => '',
		);
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Terms_Conditions extends EO_Booking_Form_Element{

	static function get_type_name(){
		return __( 'Terms & Conditions', 'eventorganiserp' );
	}
	
	function get_defaults(){
		return array(
				'label' => __( 'Terms & Conditions', 'eventorganiserp' ),
				'terms' => __( 'Your terms & conditions' , 'eventorganiserp' ),
				'terms_accepted_label' => __(' I have read and agree to the terms and conditions detailed above.', 'eventorganiserp' ),
		);
	}

}

/** @ignore **/
class EO_Booking_Form_Element_Antispam extends EO_Booking_Form_Element{

	var $show_label = false;
	
	static function get_type_name(){
		return __( 'Antispam maths question', 'eventorganiserp' );
	}
	
	function is_valid( $input ){

		$i = $input[$this->id]['i']; //User input
		$h = $input[$this->id]['h']; //Hash of the answer

		return is_numeric( $i ) && wp_hash( $i ) === $h;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Date extends EO_Booking_Form_Element_Input{

	static function get_type_name(){
		return __( 'Date', 'eventorganiserp' );
	}
	
	function get_field_type(){
		return 'text';
	}
		
	function get_data(){
		
		$date = false;
		if( $this->get( 'opening_date' ) ){
			try{
				$date = new DateTime( $this->get( 'opening_date' ) );
				$date = $date->format( 'Y-m-d' );
			}catch( Exception $e ){
				$date = false;
			}
		}
		
		return array(
			'dateformat' => eventorganiser_php2jquerydate( $this->get( 'format' ) ),
			'defaultDate' => $date,
		);
	}
	
	function get_defaults(){
		return array(
			'label' => __( 'Date', 'eventorganiserp' ),
			'format' => 'Y-m-d',
			'placeholder' => date_i18n( $this->get( 'Y-m-d' ) ),
			'class' => 'eo-booking-field-date eventorganiser-date-input',
		);
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Email extends EO_Booking_Form_Element_Input{
	
	static function get_type_name(){
		return __( 'Email', 'eventorganiserp' );
	}

	function get_field_type(){
		return 'email';
	}

	function get_defaults(){
		return array(
				'label' => __( 'Email', 'eventorganiserp' ),
				'placeholder' => 'john@example.com',
		);
	}

	function is_valid( $input ){
		return is_email( $this->get_value() );
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Name extends EO_Booking_Form_Element{

	static function get_type_name(){
		return __( 'Name', 'eventorganiserp' );
	}
	
	function get_defaults(){
		return array(
			'label'          => __( 'Name', 'eventorganiserp' ),
			'label_fname'    => __( 'First Name', 'eventorganiserp' ),
		    'label_lname'    => __( 'Last Name', 'eventorganiserp' ),
			'lname'          => true,
			'required'       => true,
			'lname_required' => false,
		);
	}

	function is_valid( $input ){
		
		$value = $this->get_value();
		
		$first_name_valid = !empty( $value['fname'] );
		$last_name_valid =  !$this->get( 'lname' ) || !$this->get( 'lname_required' ) || !empty( $value['lname'] );
		
		return $first_name_valid && $last_name_valid;
	}
}


/** @ignore **/
class EO_Booking_Form_Element_Number extends EO_Booking_Form_Element_Input{

	static function get_type_name(){
		return __( 'Number', 'eventorganiserp' );
	}

	function get_field_type(){
		return 'number';
	}
	
	function get_defaults(){
		return array(
				'label' => __( 'Number', 'eventorganiserp' ),
				'type' => 'number',
				'style' => 'width:auto;'
		);
	}
	
	

	function is_valid( $input ){
				
		$valid = true;
		$value = $this->get_value();
		
		//Is numeric will return false if $value is empty string/null/false.
		if( $value || 0 === $value  ){
		
			if( !is_numeric( $this->get_value() ) ){
				$valid = false;
				$this->add_error( 
					'non-numeric', 
					__('<strong>ERROR:</strong> Entered value is not numeric.','eventorganiserfes')
				);
			
			}elseif( ( $this->get( 'min') && $value < $this->get( 'min') )
				|| ( $this->get( 'max') && $value > $this->get( 'max') ) ){
				$valid = false;
				$this->add_error( 
					'invalid-numberc', 
					sprintf(
						__('<strong>ERROR:</strong> Please enter a number between %1$s and %2$s.','eventorganiserfes'),
						$this->get( 'min'),
						$this->get( 'max')
					)
				);
			}
		
		}
		
		return $valid;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Phone extends EO_Booking_Form_Element_Input{
	
	static function get_type_name(){
		return __( 'Phone', 'eventorganiserp' );
	}
	
	function get_field_type(){
		return 'tel';
	}
	
	function get_defaults(){
		return array(
				'label' => __( 'Phone', 'eventorganiserp' ),
				'placeholder' => '(1234) 1234567'
		);
	}

}

/** @ignore **/
class EO_Booking_Form_Element_Time extends EO_Booking_Form_Element_Input{

	static function get_type_name(){
		return __( 'Time', 'eventorganiserp' );
	}

	function get_field_type(){
		return 'time';
	}
	
	function get_defaults(){
		return array(
				'label' => __( 'Time', 'eventorganiserp' ),
				'hour24' => true,
				'placeholder' => date_i18n('H:i'),
				'size' => 5,
				'style' => 'width:auto;',
		);
	}

}

/** @ignore **/
class EO_Booking_Form_Element_Url extends EO_Booking_Form_Element_Input{
	
	static function get_type_name(){
		return __( 'Website', 'eventorganiserp' );
	}
	
	function get_field_type(){
		return 'url';
	}
	
	function get_defaults(){
		return array(
				'label' => __( 'Website', 'eventorganiserp' ),
				'placeholder' => 'http://'
		);
	}

	function is_valid( $input ){
		return $input[$this->id] == esc_url_raw( $input[$this->id] );
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Hidden extends EO_Booking_Form_Element_Input{
	
	var $show_label = false;
	
	static function get_type_name(){
		return __( 'Hidden', 'eventorganiserp' );
	}
	
	function get_field_type(){
		return 'hidden';
	}

	function get_defaults(){
		return array();
	}
	
	function save( $booking_id ){
		return false;
	}

}

/** @ignore **/
class EO_Booking_Form_Element_Multiselect extends EO_Booking_Form_Element_Select{
	
	static function get_type_name(){
		return __( 'Multiselect', 'eventorganiserp' );
	}
	
	function get_defaults(){
		return array(
				'selected' => array()
		);
	}
	
	function get_field_name( $component = false ){
		$name = ( $this->get( 'field_name' ) ? $this->get( 'field_name' ) :  'eventorganiser[booking]['.$this->id.']' );
		if( $component !== false ){
			$name .='['.esc_attr( $component ) . ']';
		}else{
			$name .='[]';
		}
		return $name;
	}
	
	function get_selected_values(){
	
		$options = $this->get( 'options' );
		$selected_values = array();
		
		//Default value
		if(  $this->get( 'selected' ) ){
			$selected_values = array_intersect_key( $options, array_flip( $this->get( 'selected' ) ) );
		}

		//Set value
		$selected_values = !is_null( $this->get_value() ) ? $this->get_value() : $selected_values;
	
		return $selected_values;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Address extends EO_Booking_Form_Element{

	static function get_type_name(){
		return __( 'Address', 'eventorganiserp' );
	}
	
	function get_defaults(){
		return array(
				'label' => __( 'Address', 'eventorganiserp' ),
				'components' => array(
					'street-address', '2nd-line', 'city', 'state','postcode'		
				)
		);
	}
	

	/**
	 * Saves the element input to the database
	 * 
	 * This perhaps should be divorced to another class.
	 * 
	 * @since 1.5
	 * @see EO_Booking_Form_Element::save()
	 * 
	 * @param int $booking_id The booking ID to which the data to be saved belongs
	 * @param array $input Array of user recieved data of the form ( element ID => value ).
	 */
	function save( $booking_id ){
	
		$key = '_eo_booking_meta_'.$this->id;
		$value = $this->get_value();
	
		//Store each part seperately so it can be individually targetted
		foreach( $value as $subkey => $address_value ){
			add_post_meta( $booking_id, $key."_{$subkey}", $address_value );
		}
	
		reset( $value );
			
		foreach ( $value as $v ){
			add_post_meta( $booking_id, $key, $v );
		}
	
		//Store labels in case booking form element disappears...
		add_post_meta( $booking_id, '_eo_booking_label_meta_'.$this->id, $this->get( 'label' ) );
	}
	
	/**
	 * Returns an array of component identifiers which have been enabled for this element.
	 * @return array
	 */
	function get_components(){
		$components = $this->get( 'components' );
		return $components ? $components : array();
	}
	

}

/** @ignore **/
class EO_Booking_Form_Element_TicketPicker extends EO_Booking_Form_Element{
	
	var $show_label = false;
	
	static function get_type_name(){
		return __('Ticket picker');
	}
	
	function get_field_name( $component = false ){
		$name = 'eventorganiser[booking][tickets]';
		if( $component !== false ){
			$name .='['.esc_attr( $component ) . ']';
		}
		return $name;
	}

	function get_defaults(){
		return array(
				'use_select' => false,
				'simple_mode' => (bool) get_post_meta( $this->form->id, '_eventorganiser_booking_simple_mode', true ),
		);
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Gateway extends EO_Booking_Form_Element{
	
	var $show_label = false;
	
	static function get_type_name(){
		return __('Gateway picker');
	}
	
	function is_required(){
		return true;
	}
	
	/**
	 * This isn't really needed. A gateway check is done at
	 * `eventorganiser_validate_booking_submission`, where the booking amount
	 * is calculated. If the total is 0 then the 'free' gateway is allowed. Here 
	 * we just check if the gateway is enabled or is 'free'.  
	 * @link http://wp-event-organiser.com/forums/topic/error-some-fields-are-not-valid/
	 * @see EO_Booking_Form_Element::is_valid()
	 */
	function is_valid( $input ){
		$gateway = isset( $input['gateway'] ) ? $input['gateway'] : false;
		$enabled_gateways = $this->get_enabled_gateways();
		$enabled_gateways['free'] = 'Free';
		return isset( $enabled_gateways[$gateway] );
	}
	
	function get_enabled_gateways(){		
		$gateways = eventorganiser_get_enabled_gateways();
		$form = $this->form;
		$form_id = $form->id;
		return apply_filters_ref_array( 'eventorganiser_booking_form_gateways', array( $gateways, $form_id, &$form ) );
	}

}

class EO_Booking_Form_Element_Button extends EO_Booking_Form_Element{
	
	var $show_label = false;
	
	static function get_type_name(){
		return __( 'Submit button' );
	}
	
	function get_class(){
		$form_button_classes = $this->form->get('button_classes');
		$form_button_classes = $this->get( 'class' ) ? $this->get( 'class' ) : $form_button_classes; 
		
		$class = trim( $form_button_classes );
		$class .= ' eo-booking-field-' . str_replace( '_', '-', $this->type );
		return apply_filters( 'eventorganiser_booking_button_classes', $class, $this );
	}
	
	function get_button_text(){
		$form_button_text = $this->form->get('button_text');
		$form_button_text = $this->get('label') ? $this->get('label') : $form_button_text; 
		return apply_filters( 'eventorganiser_booking_button_text', $form_button_text, $this );
	}
	
	function is_required(){
		return false;
	}
}
