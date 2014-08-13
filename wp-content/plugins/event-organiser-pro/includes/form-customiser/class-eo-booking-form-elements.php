<?php
class EO_Booking_Form_Elements{
	
	//Public, for now (backwards compatibility, seo EO_Booking_Form)
	public $elements = array();
	
	public $form = false;
	
	
	public function __construct( $elements = array(), $settings = array() ){
		$this->set( $elements, $settings );
	}
	
	
	public function set( $elements = array(), $settings = array() ){
	
		if( $elements ){
			$elements = ( $this->_is_array( $elements )  ? $elements : array( $elements ) );
		
			foreach( $elements as $element ){
				$this->add( $element, $settings );
			}
		}else{
			$this->elements = array();
		}
	}
	
	
	public function add( $element, $settings = array() ){
		
		if ( !($element instanceof iEO_Booking_Form_Element) ){

			$element_types = EO_Booking_Form_Controller::get_element_types();
			$type = $element['type'];
				
			if( !isset( $element_types[$type] ) )
				return false;
			
			$classname = $element_types[$type];
			
			$form = isset( $settings['form'] ) ? $settings['form'] : $this->form;
			
			$element = new $classname( $element, $form );
						
		}
		
		if( isset( $this->elements[$element->id] ) )
			return false;
		
		$flattened = $this->flatten();
		if( isset( $flattened[$element->id] ) )
			return false;
		
		$element->collection = $this;
		
		if( isset( $settings['at'] ) ){
			$this->elements = array_slice( $this->elements, 0, $settings['at'], true )
					+ array( $element->id => $element ) 
					+ array_slice( $this->elements, $settings['at'], count( $this->elements ), true ) ;
			$this->_sort();
		}else{
			$this->elements[$element->id] = $element;
		}

		return true;
	}
	
	
	public function remove( $element ){
	
		$element_id = ( $element instanceof iEO_Booking_Form_Element ? $element->id : $element );
		
		if( isset( $this->elements[$element_id] ) ){
			unset( $this->elements[$element_id] );
			$this->_sort();
			return true;
		}
		
		return false;
		
	}
	
	
	/**
	 * Get `EO_Booking_Form_Elements` from the form
	 * @return boolean false if element ID does no exist, the element if it does
	 */
	function get( $element = false ){
		
		if( $element == false ){
			return $this->elements;
		}
		
		$element_id = ( $element instanceof iEO_Booking_Form_Element ? $element->id : $element );
		
		if( isset( $this->elements[$element_id] ) ){
			return $this->elements[$element_id];
		}
		return false;
	}
	
	
	/**
	 * Get `EO_Booking_Form_Elements` from the form
	 * @return boolean false if element ID does no exist, the element if it does
	 */
	function filter( $args = array() ){
	
		$elements = array();
	
		if ( empty( $args ) )
			return $this->elements;
	
		if( $this->elements ){
			foreach( $this->elements as $element ){
				$matched = 0;
				foreach ( $args as $m_key => $m_value ) {
					if( $element->get( $m_key ) == $m_value){
						$matched++;
					}
				}
				if( $matched == count( $element ) ){
					$elements[] = $element;
				}
			}
		}
		return $elements;
	}
	
	
	/**
	 * Get `EO_Booking_Form_Elements` from the form
	 * @return boolean false if element ID does no exist, the element if it does
	 */
	function get_where( $args = array() ){
	
		if( $this->elements ){
				
			if ( empty( $args ) )
				return array_shift( array_values( $this->elements ) );
				
			foreach( $this->elements as $element ){
				$matched = 0;
				foreach ( $args as $m_key => $m_value ) {
					if( $element->get( $m_key ) == $m_value){
						$matched++;
					}
				}
				if( $matched == count( $element ) ){
					return $element;
				}
			}
		}
	
		return false;
	}
	
	function flatten(){
		$flattened = array();
		foreach( $this->elements as $element ){
			$flattened[$element->id] = $element;
			if( $element->can_have_children ){
				$flattened = $flattened + $element->_elements->flatten();
			}
		}
		return $flattened;
	}
	
	
	function toJSON(){
		$array = array();
		$position = 0;		
		foreach( $this->get() as $element ){
			$array[$element->id] = $element->toJSON();
			if( empty( $array[$element->id]['position'] ) ){
				$array[$element->id]['position'] = $position;
			}
			$position++;
		}
		
		return $array;
	}
	
	
	function _sort(){
		$i = 0;
		if( $this->elements ){
			foreach( $this->elements as $_element ){
				$_element->set( 'position', $i++ );
			}
		}
	}
		
	/**
	 * A method to determine if $elements is an array of elements, or just one.
	 * @private
	 * @return bool
	 */
	private function _is_array( $elements ){
	
		if( !is_array( $elements ) ){
			return false;
		}
		//If its an array it could be an array of elements or and element given in 'JSON' form
		//To check for the later we look for some reserved keywords that MUST NOT be used as 
		//element IDs: id, type
		if( array_key_exists( 'id', $elements ) || array_key_exists( 'type', $elements ) ){
			return false;
		}
	
		return true;
	}

}