<?php
interface iEO_Booking_Form_Element{
	
	static function get_type_name();
	
	public function get_defaults();
	
	public function toJSON();
	
	public function get( $param );
	
	public function set( $param, $value );
	
	public function get_field_name();
	
	public function get_type();
	
	public function get_value( $component = false );
	
	public function set_value( $value, $component = false);
	
	public function is_required();
	
	public function show_label();
	
	public function validate( $input );
	
	public function is_valid( $input );
	
	public function add_error( $code, $message = false, $data = '' );
	
	public function get_error_codes();
	
	public function has_errors();
	
	//public function save( $booking_id );

}
