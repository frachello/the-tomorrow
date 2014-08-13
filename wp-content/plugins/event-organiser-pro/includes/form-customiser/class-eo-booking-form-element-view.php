<?php
/**
 * Abstract class for booking form element (front-end) view.
 * @author stephen
 */
abstract class EO_Booking_Form_Element_View implements iEO_Booking_Form_View{

	/**
	 * The EO_Booking_Form_Element instance attached to this model.
	 * @var EO_Booking_Form_Element
	 */
	var $element = false;
	
	/**
	 * Prefix used by the view.
	 * @var string
	 */
	static $prefix = 'booking';
	
	/**
	 * How the field should be wrapped
	 *  %1$s - Classes for the form field (e.g. classes to indicate an error for this field)
	 *  %2$s - The actual content of the element view
	 *  %3$s - The element ID
	 * @var string
	 */
	var $wrap = '<div id="%3$s" class="eo-booking-field %1$s">%2$s</div>';

	/**
	 * Sets up the view instance and attaches the mode.
	 * @param EO_Booking_Form_Element $element
	 */
	final function __construct( $element ){
		$this->element = $element;
	}

	/**
	 * Returns the mark-up for the element on the front-end
	 * @return string HTML mark-up for this form element
	 */
	//abstract function render(); Issues on php5.2? @see http://stackoverflow.com/questions/17525620/php-fatal-error-cant-inherit-abstract-function
}

/** @ignore **/
class EO_Booking_Form_Element_Input_View extends EO_Booking_Form_Element_View{

	function render(){
		$html = eventorganiser_text_field(array(
				'type' => $this->element->get_field_type(),
				'data' => $this->element->get_data(),
				'class' => $this->element->get_class(),
				'placeholder' => $this->element->get( 'placeholder' ),
				'size' => $this->element->get( 'size' ),
				'min' => $this->element->get( 'min' ) !== '' ? $this->element->get( 'min' ) : false ,
				'max' => $this->element->get( 'max' ) !== '' ? $this->element->get( 'max' ) : false ,
				'required' => $this->element->is_required(),
				'id' => 'eo-booking-field-'.$this->element->id,
				'style' => $this->element->get( 'style' ),
				'help' => $this->element->get( 'description' ),
				'name' =>  $this->element->get_field_name(),
				'value' => $this->element->get_value(),
				'echo' => 0
		));
		if( $this->element->has_errors() ){
			$codes = $this->element->get_error_codes();
			$error_markup = array();
			
			foreach( $codes as $code ){
				if(  $this->element->get_error_message( $code )	){
					$error_markup[] = sprintf( '<div class="eo-booking-form-field-errors"><p>%s</p></div>', $this->element->get_error_message( $code ) );
				}
			}
			if( $error_markup ){
				$html .= implode( '', $error_markup );
			}
		}
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Hidden_View extends EO_Booking_Form_Element_View{

	var $wrap = '%2$s';
	
	function render(){
		if( is_array( $this->element->get_value() ) ){
			$html = "";
			$base_id = $this->element->get('field_id');
			
			foreach( $this->element->get_value() as $key => $value ){
				$html .= eventorganiser_text_field(array(
					'type' 	=> 'hidden',
					'data' 	=> $this->element->get( 'data' ),
					'class' => $this->element->get_class(),
					'id' 	=> $base_id ? $base_id . '-'.  $key : false,
					'name' 	=> $this->element->get_field_name( $key ),
					'value' => $this->element->get_value( $key ),
					'echo' 	=> 0
				));
			}
			
		}else{
			$html = eventorganiser_text_field(array(
				'type' 	=> 'hidden',
				'data' 	=> $this->element->get( 'data' ),
				'class' => $this->element->get_class(),
				'id' 	=> $this->element->get('field_id'),
				'name' 	=> $this->element->get_field_name(),
				'value' => $this->element->get_value(),
				'echo' 	=> 0
			));
		}
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Select_View extends EO_Booking_Form_Element_View{

	function render(){
	
		$multiselect = ( $this->element->type == 'multiselect' );
		$options     = $this->element->get( 'options' );
	
		if( $multiselect ){
			$selected = $this->element->get_selected_values();
		}else{
			$selected = $this->element->get_selected_value();
		}
	
		if( eventorganiser_is_associative( $options ) ){
			$select_options = $this->element->get( 'options' );
		}else{
			$select_options = array_combine( $this->element->get( 'options' ), $this->element->get( 'options' ) );
		}
		
		$html = eventorganiser_select_field(array(
			'id'          => 'eo-booking-field-'.$this->element->id,
			'class'       => $this->element->get_class(),
			'options'     => $select_options,
			'multiselect' => $multiselect,
			'selected'    => $selected,
			'help'        => $this->element->get( 'description' ),
			'name'        => $this->element->get_field_name(),
			'echo'        => 0
		));
		
		if( $this->element->has_errors() ){
			$codes = $this->element->get_error_codes();
			$error_markup = array();
			$html .= implode( '', $codes );
			foreach( $codes as $codes ){
				if(  $this->element->get_error_message( $code )	){
					$error_markup[] = sprintf( '<div><p>%s</p></div>', $this->element->get_error_message( $code ) );
				}
			}
			if( $error_markup ){
				$html .= implode( '', $error_markup );
			}
		}
		
		return $html;
	}

}

/** @ignore **/
class EO_Booking_Form_Element_Antispam_View extends EO_Booking_Form_Element_View{

	function render(){
		$required = ( $this->element->get( 'required' ) ? '<span class="required eo-booking-form-required">*</span>' : '' );
		$n1 = rand( 1,12 );
		$n2 = rand( 1, 12 );

		$html = sprintf(
				'<label class="eo-booking-label" for="eo-booking-field-%1$d"> %2$s</label>',
				esc_attr( $this->element->id ),
				sprintf( __('What is %d + %d?','eventorganiserp' ), $n1, $n2 )
		);

		$html .= eventorganiser_text_field(array(
				'type' => 'number',
				'class' => $this->element->get_class(),
				'placeholder' => $this->element->get( 'placeholder' ),
				'size' => $this->element->get( 'size' ),
				'id' => 'eo-booking-field-'.$this->element->id,
				'style' => $this->element->get( 'style' ),
				'help' => $this->element->get( 'description' ),
				'name' =>  $this->element->get_field_name( 'i' ),
				'echo' => 0
		));
		$html .= eventorganiser_text_field(array(
				'type' => 'hidden',
				'value' => wp_hash( $n1 + $n2 ),
				'id' => 'eo-booking-field-'.$this->element->id.'-2',
				'name' =>  $this->element->get_field_name( 'h' ),
				'echo' => 0
		));
		return $html;
	}
	
}

/** @ignore **/
class EO_Booking_Form_Element_Checkbox_View extends EO_Booking_Form_Element_View{
	
	function render(){

		//Get selected values from selected indexes
		$selected_values = $this->element->get_selected_values();

		$html = sprintf('<ul id="eo-booking-field-%1$s" class="eo-booking-field-checkbox-list">', $this->element->id);
		foreach( $this->element->get( 'options' ) as $index => $value ) :
		$html .= sprintf(
				'<li><label><input type="checkbox" name="%1$s[]" style="%4$s" value="%2$s" %3$s> %2$s</label></li>',
				$this->element->get_field_name(),
				esc_attr( $value ),
				checked( in_array( $value, $selected_values), true, false ),
				esc_attr( $this->element->get_class() )
		);
		endforeach;
		$html .= '</ul>';
		$html .= sprintf('<p class="description">%s</p>',$this->element->get( 'description' ) );
		return $html;
	}

}

/** @ignore **/
class EO_Booking_Form_Element_Radio_View extends EO_Booking_Form_Element_View{

	function render(){

		$html = sprintf('<ul id="eo-booking-field-%1$d">', $this->element->id);
		foreach( $this->element->get( 'options' ) as $index => $value ) :
		$html .= sprintf(
				'<li><label>
					<input type="radio" name="eventorganiser['.self::$prefix.'][%1$s]" style="%4$s" class="%5$s" value="%2$s" %3$s> %2$s
				</label></li>',
				esc_attr( $this->element->id ),
				esc_attr( $value ),
				checked( $this->element->get_selected_value(), $value, false ),
				esc_attr( $this->element->get( 'style' ) ),
				esc_attr( $this->element->get_class() )
		);
		endforeach;
		$html .= '</ul>';
		$html .= sprintf('<p class="description">%s</p>',$this->element->get( 'description' ) );
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Section_View extends EO_Booking_Form_Element_View{
	function render(){
		$html = sprintf(
				'<h2 id="eo-booking-field-%1$d" class="%3$s"> %2$s</h2>
				<hr class="eo-booking-field-section-hr eventorganiser-section-break">',
				esc_attr( $this->element->id ),
				esc_html( $this->element->get( 'label' ) ),
				esc_attr( $this->element->get_class() )
		);
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Fieldset_View extends EO_Booking_Form_Element_View{

	var $wrap = '%2$s';

	function render(){
		
		$html = sprintf( 
			'<fieldset id="%s" class="eo-booking-field %s">',
			esc_attr( 'eo-booking-form-element-wrap-'.$this->element->id ), 
			esc_attr( $this->element->get_class() ) 
		);
		if( $this->element->get('label') ){
			$html .= '<legend><span>'.esc_html( $this->element->get('label') ).'</span></legend>';
		}
		
		$elements_view_class = 'EO_Booking_Form_Elements_View';
		$elements_view_class = apply_filters( 'eventorganiser_booking_form_elements_view', $elements_view_class, $this->element->form );
		
		$elements_view = new $elements_view_class( $this->element->_elements );
		$html .= $elements_view->render();
		
		$html .= '</fieldset>';
		
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Name_View extends EO_Booking_Form_Element_View{


	function render(){
		
		if( !$this->element->get( 'lname' ) ){
			//Just first name
			$html = eventorganiser_text_field(array(
				'type'        => 'text',
				'class'       => $this->element->get_class(),
				'placeholder' => $this->element->get( 'placeholder' ),
				'required'    => $this->element->is_required(),
				'id'          => 'eo-booking-field-'.$this->element->id.'-fname',
				'name'        => $this->element->get_field_name( 'fname' ),
				'value'       => $this->element->get_value( 'lname' ),
				'echo'        => 0
			));
			
		}else{
			//First and last name
			$html = '<p style="overflow:hidden">';
		
			$html .= sprintf(
				'<span class="eo-booking-field-name-subfield">
					<label class="eo-booking-sub-label eo-booking-sub-label-fname" for="%1$s"> %3$s </label> 
					%2$s 
				</span>',
				'eo-booking-field-'.$this->element->id.'-fname',
				eventorganiser_text_field(array(
					'type'        => 'text',
					'class'       => $this->element->get_class(),
					'echo'        => 0,
					'id'          => 'eo-booking-field-'.$this->element->id.'-fname',
					'required'    => $this->element->is_required(),
				    'placeholder' => __( 'First name', 'eventorganiserp' ),
					'name'        => $this->element->get_field_name( 'fname' ),
					'value'       => $this->element->get_value( 'fname' ),
				)),
				$this->element->get( 'label_fname' )
			);
		
			$html .= sprintf(
				'<span class="eo-booking-field-name-subfield">
					<label class="eo-booking-sub-label eo-booking-sub-label-lname" for="%1$s"> %3$s </label>
					%2$s
				</span>',
				'eo-booking-field-'.$this->element->id.'-lname',
				eventorganiser_text_field(array(
					'type'        => 'text',
					'class'       => $this->element->get_class(),
					'echo'        => 0,
					'id'          => 'eo-booking-field-'.$this->element->id.'-lname',
				    'placeholder' => __( 'Last name', 'eventorganiserp' ),
					'required'    => $this->element->get( 'lname_require' ),
					'name'        => $this->element->get_field_name( 'lname' ),
					'value'       => $this->element->get_value( 'lname' ),
				)),
				$this->element->get( 'label_lname' )
			);
			
			$html .= '</p>';
		}
		
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Textarea_View extends EO_Booking_Form_Element_View{

	function render(){
		$html = eventorganiser_textarea_field(array(
				'value' => $this->element->get_value(),
				'class' => $this->element->get_class(),
				'id' => 'eo-booking-field-'.$this->element->id,
				'help' => $this->element->get( 'description' ),
				'echo' => 0,
				'name' =>  $this->element->get_field_name(),
				'tinymce' => $this->element->get('tinymce'),
		));
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Html_View extends EO_Booking_Form_Element_View{
	function render(){
		return $this->element->get( 'html' );
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Hook_View extends EO_Booking_Form_Element_View{
	
	function render(){
		$html = '';
		if( $this->element->get( 'wp-action' ) ){
			$action = $this->element->get( 'wp-action' );
			ob_start();
			do_action( $action, $this );
			$html = ob_get_contents();
			ob_end_clean();
		}
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Ticketpicker_View extends EO_Booking_Form_Element_View{
	
	function render(){
	
		$event_id = $this->element->form->get('event_id');
		$booking_form = $this->element->form;
		
		$occurrence_tickets = eo_get_the_occurrences_tickets( $event_id );
		$tickets = eo_get_event_tickets_on_sale( $event_id );
	
	
		if( $booking_form && $booking_form->is_simple_booking_mode() //SBM is enabled
				&& count( $tickets ) == 1 //only 1 ticket available
				&& ( eventorganiser_pro_get_option( 'book_series' ) || !eo_reoccurs( $event_id ) ) //No date selection needed
		){
			$ticket = array_pop( $tickets );
			$ticket_id = $ticket['mid'];
			$html = sprintf(
					'<input type="hidden" name="eventorganiser[booking][tickets][%d]" max="1" style="width:auto;" min="1" value="1" />',
					$ticket_id
			);
		}else{
	
			$html = apply_filters( 'eventorganiser_pre_booking_table_form', '', $event_id );
	
			$template = eo_locate_template( 'eo-ticket-picker.php', false );
			ob_start();
			if( $template )
				include( $template );
				
			$html .= ob_get_contents();
			ob_end_clean();
				
			//The booking table...
			$html .= apply_filters( 'eventorganiser_post_booking_table_form', '', $event_id );
	
		}
	
		return $html;
	}
	
}

/** @ignore **/
class EO_Booking_Form_Element_Button_View extends EO_Booking_Form_Element_View{
	
	function render(){
		 
		 $html = sprintf(
		 	'<div class="eo-booking-purchase-row"><p>
				<button type="submit" class="%s">%s</button>
				<img class="eo-booking-form-waiting" src="%s" style="display:none" >
			</p></div>',
		 	esc_attr( $this->element->get_class() ), //esc_attr( $booking_form->get_form_button_classes() )
		 	esc_attr( $this->element->get_button_text() ), //esc_attr( $booking_form->get_form_button_text() );
		 	esc_attr( EVENT_ORGANISER_PRO_URL . 'images/loader.gif' )		
		);
		 
		return $html;
	}
	
}

/** @ignore **/
class EO_Booking_Form_Element_Gateway_View extends EO_Booking_Form_Element_View{

	function render(){
	
		$booking_form = $this->element->form;
	
		//Get tickets on save now
		$tickets = eo_get_event_tickets( $booking_form->get('event_id') );
		$now = new DateTime( 'now', eo_get_blog_timezone() );
		foreach ( $tickets as $ticket_id => $ticket ) {
			if ( ( $ticket['from']  && $ticket['from'] > $now ) || ( $ticket['to'] && $ticket['to'] < $now ) )
				unset( $tickets[$ticket_id] );
		}
	
		$enabled_gateways = $this->element->get_enabled_gateways();
		$total_prices = ( $tickets ? eventorganiser_list_sum( $tickets, 'price' ) : 0 );
		$_selected = $this->element->get_value();
	
		$html = '';
		
		if ( count( $enabled_gateways ) > 1 && $total_prices ) {
	
			//Gateway selection - only needed if there are tickets with a non-zero price
			$html .= sprintf( '<p><label class="eo-booking-label" for="eo-booking-%1$s">%2$s <span class="required eo-booking-form-required">*</span></label>',
					esc_attr( $this->element->id ),
					esc_html( $this->element->get( 'label' ) )
			);
	
			foreach ( $enabled_gateways as $gateway => $label ) {
	
				$html .= sprintf(
						'<label for="gateway_%1$s" class="eo-booking-sub-label">
                          		<input type="radio" class="%6$s" name="eventorganiser[booking][%5$s]" id="gateway_%1$s" value="%1$s" %4$s />
								%2$s
							</label>%3$s',
						esc_attr( $gateway ),
						$label,
						'offline' == $gateway ? eventorganiser_pro_get_option( 'offline_instructions' ) : '',
						checked( $gateway, $_selected, false ),
						esc_attr( $this->element->id ),
						$this->element->get_class()
				);
			}
	
			$html .= '</p>';
	
		}else{
			reset($enabled_gateways);
			$gateway = (  $total_prices ? key( $enabled_gateways ) : 'free' );
			$html .= sprintf(
					'<input type="hidden" class="%3$s" name="eventorganiser[booking][%2$s]" id="gateway_%1$s" value="%1$s" />',
					esc_attr( $gateway ),
					esc_attr( $this->element->id ),
					$this->element->get_class()
			);
	
			if( $gateway == 'offline' ){
				$html .= '<p>'.eventorganiser_pro_get_option( 'offline_instructions' ).'</p>';
			}
		}
	
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Terms_Conditions_View extends EO_Booking_Form_Element_View{
	function render(){
		$html = sprintf('<div class="eo-booking-field-terms-conditions-text">%s</div>', $this->element->get( 'terms' ) );
		$html .= sprintf( '<label><input type="checkbox" name="eventorganiser['.self::$prefix.'][%1$s]" style="%3$s" value="1"> %2$s</label>',
				esc_attr( $this->element->id ),
				esc_html( $this->element->get( 'terms_accepted_label' ) ),
				esc_attr( $this->element->get_class() )
		);
	
		return $html;
	}
}

/** @ignore **/
class EO_Booking_Form_Element_Address_View extends EO_Booking_Form_Element_View{

	function render(){

		$html = sprintf( '<p class="description"> %1$s </p>', $this->element->get( 'description' ) );

		$html .='<span class="example-address-components">';

		//Street address
		if( in_array( 'street-address', $this->element->get( 'components' ) ) ){
		
			$html .= sprintf(
				'<p><label class="eo-booking-sub-label" for="%1$s">  %3$s </label> %2$s </p>',
				'eo-booking-field-street-address-'.esc_attr( $this->element->id ),
				eventorganiser_text_field(array(
					'type'=>'text',
					'class' => $this->element->get_class(),
					'echo'=>0,
					'id' => 'eo-booking-field-street-address'.$this->element->id,
					'placeholder'=> __('Street address', 'eventorganiser'),
					'name' =>  $this->element->get_field_name( 'street-address' ),
					'value' => $this->element->get_value( 'street-address' ),
				)),
				__('Street Address','eventorganiserp')
			);
		}
		
		//2nd line addres
		if( in_array( '2nd-line', $this->element->get( 'components' ) ) ){

			$html .= sprintf(
				'<p> <label class="eo-booking-sub-label" for="%1$s">  %3$s </label> %2$s </p>',
				'eo-booking-field-2nd-line-'.$this->element->id,
				eventorganiser_text_field(array(
					'type'=>'text',
					'class' => $this->element->get_class(),
					'echo'=>0,
					'id'=>'eo-booking-field-2nd-line'.$this->element->id,
					'placeholder'=>'',
					'name' =>  $this->element->get_field_name( '2nd-line' ),
					'value' => $this->element->get_value( '2nd-line' ),
				)),
				__('Address Line 2','eventorganiserp')
			);
		}
		

		//City
		if( in_array( 'city', $this->element->get( 'components' ) ) ){
			$html .= sprintf(
				'<p> <label class="eo-booking-sub-label" for="%1$s">  %3$s </label> %2$s </p>',
				'eo-booking-field-city-'.$this->element->id,
				eventorganiser_text_field(array(
					'type'=>'text',
					'class' => $this->element->get_class(),
					'echo'=>0,
					'id'=>'eo-booking-field-city'.$this->element->id,
					'placeholder'=>__('City', 'eventorganiser'),
					'name' =>  $this->element->get_field_name( 'city' ),
					'value' => $this->element->get_value( 'city' ),
				)),
				__('City','eventorganiserp')
			);
		}
		

		//Postcode and state
		$html .= '<p style="overflow:hidden">';
		
		//State;
		if( in_array( 'state', $this->element->get( 'components' ) ) ){

			$html .= sprintf(
					'<label class="eo-booking-sub-label" for="%1$s" style="float:left;"> %3$s </br> %2$s </label>',
					'eventorganiser-example-field-state-'.$this->element->id,
					eventorganiser_text_field(array(
							'type'=>'text',
							'class' => $this->element->get_class(),
							'echo'=>0,
							'id'=>'eo-booking-field-state'.$this->element->id,
							'placeholder'=>  __('State/Province','eventorganiserp'),
							'style'=>'width:90%',
							'name' =>  $this->element->get_field_name( 'state' ),
							'value' => $this->element->get_value( 'state' ),
					)),
					__('State /Province','eventorganiserp')
			);
			
		}
		
		//Postcode;
		if( in_array( 'postcode', $this->element->get( 'components' ) ) ){
		
			$html .= sprintf(
				'<label class="eo-booking-sub-label" for="%1$s" style="float:left;"> %3$s </br> %2$s </label>',
				'eventorganiser-example-field-postcode-'.$this->element->id,
				eventorganiser_text_field(array(
					'type'=>'text',
					'class' => $this->element->get_class(),
					'echo'=>0,
					'id'=>'eo-booking-field-postcode'.$this->element->id,
					'placeholder'=>__('Post Code','eventorganiserp'),
					'style'=>'width:90%',
					'name' =>  $this->element->get_field_name( 'postcode' ),
					'value' => $this->element->get_value( 'postcode' ),
				)),
				__('Post Code','eventorganiserp')
			);
		}
		
		$html .= '</p>';
		
		
		//Country
		if( in_array( 'country', $this->element->get( 'components' ) ) ){

			$html .= sprintf(
				'<p> <label class="eo-booking-sub-label" for="%1$s">  %3$s </label> %2$s </p>',
				'eventorganiser-example-field-country-'.$this->element->id,
				eventorganiser_text_field(array(
					'type'=>'text',
					'class' => $this->element->get_class(),
					'echo'=>0,
					'id'=>'eo-booking-field-country'.$this->element->id,
					'placeholder'=>__('Country','eventorganiserp'),
					'name' =>  $this->element->get_field_name( 'country' ),
					'value' => $this->element->get_value( 'country' ),
				)),
				__('Country','eventorganiser')
			);
		}
		
		$html .= '</span">';
		return $html;
	}
}