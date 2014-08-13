<?php
/**
 * Class for displaying a collection of field elements
 */
class EO_Booking_Form_Elements_View{

	/**
	 * @var $_elements EO_Booking_Form_Elements to display
	 */
	protected $_elements;

	/**
	 * Constructs and initialises the form
	 * @param EO_Booking_Form_Elements
	 */
	function __construct( $elements ){
		$this->_elements = $elements;
	}
	
	function render(){
		$html = '';
		
		foreach( $this->_elements->get() as $element ){
			
			$element_html = '';
			
			$class = 'eo-booking-form-element-'.$element->type;
			
			if( $codes = $element->get_error_codes() ){
				
				if( in_array( 'required_field_missing', $codes ) ){
					$class .= ' eo-booking-field-required';
				}elseif( in_array( 'invalid_data', $codes ) ){
					$class .= ' eo-booking-field-invalid';
				}else{
					$class .= ' eo-booking-field-element-error-'.implode( ' eo-booking-field-element-error-', $codes );
				}
				
				$class .= ' eo-booking-field-error';
	
			}
			
			$required = ( $element->is_required() ? '<span class="required eo-booking-form-element-required">*</span>' : '' );
				
			if( $element->show_label  ){
				$element_html .= sprintf(
						'<label class="eo-booking-label" for="eo-booking-field-%1$s"> %2$s</label>',
						esc_attr( $element->id ),
						esc_html( $element->get( 'label' ) ) . $required
				);
			}
			
			$element_view_class = get_class( $element ).'_View';
			$parents = class_parents( $element );
			while( !class_exists( $element_view_class ) && $parents ){
				$element_view_class = array_shift( $parents ).'_View';
			}
			
			$element_view_class = apply_filters( 'eventorganiser_booking_form_element_view', $element_view_class, $element );
			$element_view = new $element_view_class( $element );
			$element_html .= $element_view->render();
			
			ob_start();
			do_action( 'eventorganiser_booking_form_element_'. $element->type, $element );
			$element_html .= ob_get_contents();
			ob_end_clean();
			
			$html .= sprintf( $element_view->wrap, $class, $element_html, 'eo-booking-form-element-wrap-'.$element->id );
		}
		return $html;
	}
}