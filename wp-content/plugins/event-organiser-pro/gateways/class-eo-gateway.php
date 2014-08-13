<?php
/**
 * Functions relating to event tickets. For functions relating to booked tickets see booking-tickets.
 *
 * @package payment-gateway
 */

/**
 * Abstract class which can be extended to add additional payment gateways. Please note
 * 
 * 1. The subclass must be defined after `plugins_loaded` to ensure this class has been defined.
 * 2. You must register the class with Event Organiser via {@see `eventorganiser_register_gateway()`} 
 * 
 * Please see inline documentation for details. Online tutorials and documentation will follow shortly.
 * 
 * You must over-ride abstract methods in your child class. You cannot over-ride final methods, and all
 * others may be provided by your child class if needed or desired.
 * 
 * @author stephen
 */
abstract class EO_Payment_Gateway{

	/**
	 * Singleton model, store all child instances in a static array.
	 * @ignore
	 * @var array
	 */
	private static $instances = array();
	
	/**
	 * **Unique** gateway identifier. Must be lower-case alpha-numerics slashes and underscores only,
	 * e.g. 'stripe', 'paypal' 
	 * @var string
	 */
	static $gateway = false;
	
	/**
	 * Settings priority. Determines the order in which gateways appear on the admin settings.
	 * @var int
	 */
	protected static $settings_priority = 20;
	
	/**
	 * Constructor. 
	 * 
	 * Checks that an instance hasn't already been created and checks the gateway identifier.
	 * Adds callbacks to the appropriate hooks.
	 */
	final function __construct() {
		
		//Eurgh, php5.2 support..., bring on late static binding 
		$class = get_class( $this );
		
		//php5.2 support again, $class::$gateway
		$vars = get_class_vars( $class );
		$gateway = $vars['gateway'];	
		$settings_group = 'eventorganiser-'.$gateway;
		$settings_priority = $vars['settings_priority'];
				
		//Singletons!
		if ( array_key_exists( $class, self::$instances ) )
			trigger_error( "Tried to construct a second instance of class \"$class\"", E_USER_WARNING );

		//Gateway must be provided
		if( !$gateway )
			trigger_error( "No gateway identifier specified in class \"$class\"", E_USER_WARNING );
		
		//Gateway must be lowercase alpha-numberics and underscores and dashes only.
		if( sanitize_key( $gateway ) !== $gateway )
			trigger_error( "Invalid gateway identifier in class \"$class\"", E_USER_WARNING );
		
		//Allow gateways to do stuff at this point.
		$this->init();
		
		//Register gateway
		add_filter( 'eventorganiser_gateways', array( $this, 'register_gateway' ) );
		add_filter( 'eventorganiser_enabled_gateways', array( $this, 'enabled_gateway' ) );
		
		//Register gateway settings
		add_action( "eventorganiser_register_tab_bookings", array( $this, 'register_settings'), $settings_priority );
		add_action( "load-settings_page_event-settings", array( $this, 'add_settings'), $settings_priority );
		
		//Add callback for form output
		add_action( 'eventorganiser_booking_form_element_gateway', array( $this, 'payment_form' ) );
		add_action( 'eventorganiser_get_event_booking_form', array( $this, 'setup_form' ) );
		

		//Add callback processing payment / handling IPN
		add_action( 'eventorganiser_pre_gateway_booking_' . $gateway, array( $this, 'process' ), 10, 4 );
		add_action( 'eventorganiser_gateway_listener_' . $gateway . '_ipn', array( $this, 'handle_ipn' ) );

	}
	
	/**
	 * Get the instance (or create one) of the child class.
	 * @return multitype:
	 */
	public static function getInstance() {
		
		if( version_compare(PHP_VERSION, '5.3.0') >= 0 ){
			$class = get_called_class();
			
		}else{
			//Look away now. Oh the horror! Why do you make me do this PHP 5.2? Why?!
			//@see http://stackoverflow.com/questions/3498510/php-get-called-class-alternative
			$arr = array(); 
    		$arrTraces = debug_backtrace();

    		foreach ( $arrTraces as $arrTrace ){
    			
    			if( $arrTrace['function'] == 'call_user_func' &&  '::getInstance' == substr( $arrTrace['args'][0], -13 ) ){
    				$class = substr( $arrTrace['args'][0], 0, $arrTrace['args'][0].length - 13 );
    					
    			}elseif( array_key_exists( "class", $arrTrace ) ){
    				$class = $arrTrace['class'];
    			
    			}else{
					continue;
    			}
    				
    			if( count( $arr ) == 0 ){
    				$arr[] = $class;
    					 
    			}else if( get_parent_class( $class ) == end( $arr ) ){
    				$arr[] = $class;
    			}
    			
    		}
    		
    		$class = end($arr);
		}
		
		if ( array_key_exists( $class, self::$instances ) === false)
			self::$instances[$class] = new $class();
		
		return self::$instances[$class];
	}
	
	/**
	 * Filters the gateways. Hooked onto eventorganiser_gateways adds the gateway and label
	 * @ignore
	 * @param array $gateways
	 * @return array
	 */
	final function register_gateway( $gateways ){
		$class = get_class( $this );
		//php5.2 support again, $class::$gateway
		$vars = get_class_vars( $class );
		$gateway = $vars['gateway'];
		$gateways[$gateway] = $this->get_front_end_label();
		return $gateways;
	}
		

	/**
	 * Filters the gateways. Hooked onto eventorganiser_enabled_gateways. If the gateway is
	 * not enabled it is removed form the array.
	 * @ignore
	 * @param array $gateways
	 * @return array
	 */
	final function enabled_gateway( $enabled_gateways ){
		$class = get_class( $this );
		$vars = get_class_vars( $class );
		$gateway = $vars['gateway'];
		if( !$this->is_enabled() && isset( $enabled_gateways[$gateway] ) ){
			unset( $enabled_gateways[$gateway] );
		}
		
		return $enabled_gateways;
	}
	
	/**
	 * Registers the gateway's settings with WordPress.
	 * @ignore
	 */
	final function register_settings( $settings ){
		register_setting( 'eventorganiser_bookings', 'eventorganiser-gateway' );	
	}

	/**
	 * Creates gateway's settings sections and adds the gateway options as specified by 
	 * child class in ::get_options(). Automatically prepends the 'live status' option.
	 * @ignore
	 */
	final function add_settings(){
		
		$class = get_class( $this );
		$vars = get_class_vars( $class );
		$gateway = $vars['gateway'];
		$settings_group = 'eventorganiser-'.$gateway;

		add_settings_section(
			$settings_group, //Unique ID for our section
			$this->get_label(),
			array( $this, 'settings_section_text' ),
			'eventorganiser_bookings' //bookings page
		);
		
		$defaults = array(
						array(
							'field_type' => 'select',
							'name' => 'live_status',
							'options' => array(
								'1'=>__( 'Live', 'eventorganiser' ),
								'0'=>__( 'Sandbox Mode', 'eventorganiser' ),
								'-1'=>__( 'Disable', 'eventorganiser' ),
							),
							'label' => __( 'Live Switch', 'eventorganiserp' ),
							'data'=> array( 'eo-gateway-live-switch' => $gateway ),
						)
					);
		
		$options = array_merge( $defaults, $this->get_options() );
		$option_values = get_option( 'eventorganiser-gateway' );
		$option_values = isset( $option_values[$gateway] ) ?  $option_values[$gateway] : array(); 
			
		if( !$options )
			return;
		
		foreach( $options as $option ){
			
			$type = isset( $option['field_type'] ) ? $option['field_type'] : false; //textarea / input / select / radiobox / checkbox	
			$name = isset( $option['name'] ) ? $option['name'] : false;
			$name = sanitize_key( $name );
			$label = isset( $option['label'] ) ? $option['label'] : false;
			
			if( !$name || !$type )
				continue;
			
			$id = $settings_group.'-'.$name;
			$value = $selected = $checked = isset( $option_values[$name] ) ? $option_values[$name] : false;
			$name = "eventorganiser-gateway[$gateway][$name]";
			
			$option['name'] = $name;
			$option['id'] = $id;
			$option['value'] = $value;
			$option['selected'] = $selected;
			$option['checked'] = $checked;
			
			$types = array(
				'input'	=> 'eventorganiser_text_field',
				'text' => 'eventorganiser_text_field',
				'textarea' => 'eventorganiser_textarea_field',
				'select' => 'eventorganiser_select_field',
				'radiobox' => 'eventorganiser_radio_field',
				'radio' => 'eventorganiser_radio_field',
				'checkbox' => 'eventorganiser_checkbox_field'
			);
			if( !isset( $types[$type] ) )
				continue;
			
			if( 'text' == $type || 'input' == $type ){
				$option['class'] = 'regular-text';
			}
			
			$callback = $types[$type];
		
			add_settings_field( $id, $label, $callback, 'eventorganiser_bookings', $settings_group, $option );	
		}
	} 

	/**
	 * Get the url to return after transaction is complete. This is just a helper function and can over over-ridden
	 * by a child class.
	 * @param array $booking Array of booking details. E.g. $booking['booking_id']
	 * @param EO_Booking_Form Form instance
	 * @return string Return url
	 */
	function get_return_url( $booking, $form ){
		$class = get_class( $this );
		$vars = get_class_vars( $class );
		$gateway = $vars['gateway'];
		return add_query_arg( 'booking-confirmation', $gateway, get_permalink( $form->get('page_id') ) );
	}
	

	/**
	 * Returns the IPN url (url for the gateway to send instant payment notifications to). This is of the form:
	 * <code>
	 *  www.yoursite.coom?eo-listener=ipn&eo-gateway=[gateway-identifier]
	 * </code>
	 * If you need the IPN url you can retrieve it via `$this->get_ipn_url()`.
	 * 
	 * @return string IPN url
	 */
	final function get_ipn_url(){
		$class = get_class( $this );
		$vars = get_class_vars( $class );
		$gateway = $vars['gateway'];
		return add_query_arg( array( 'eo-listener' => 'ipn', 'eo-gateway' => $gateway ), trailingslashit( site_url() ) );
	}
	
	/**
	 * Returns a gateway option specified by `$key`. `$key` here is the 'name' associated to the option. as specified
	 * in {@see ::get_option()}. 
	 * 
	 * @see{ ::get_option()}
	 * @param string $key The key of the option to 
	 * @return mixed The value of the option. 
	 */
	final function get_option( $key ){
		$class = get_class( $this );
		$vars = get_class_vars( $class );
		$gateway = $vars['gateway'];
		$option_values = get_option( 'eventorganiser-gateway' );
		return isset( $option_values[$gateway][$key] ) ? $option_values[$gateway][$key] : false;
	}

	/**
	 * Is the gateway enabled. This will return true if the gateway is in 'live' or 'sandbox' mode.
	 * Enabled gateways will appear on the booking form
	 * @see is_live()
	 * @return boolean true if gateway is enabled, false otherwise.
	 */
	function is_enabled(){
		return ( $this->get_option( 'live_status' ) != -1 );
	}
	
	/**
	 * Is the gateway live. This will return true only if the gateway is in 'live' mode.
	 *
	 * @see is_enabled()
	 * @return boolean true if gateway is enabled, false otherwise.
	 */
	function is_live(){
		return ( $this->get_option( 'live_status' ) == 1 );
	}
	
	/* Methods to over-ride with childe class */
	
	/**
	 * Called when the class is constructed, allows child class to add additional hooks if needed.
	 */
	function init(){}
		
	/**
	 * Should be over-ridden by a child class. Specifies the options for the gateway. This should be returned as 
	 * an array of options. Each option is an array specifying:
	 *  
	 *  * **field_type** - one of text, textarea, radio, checkbox, select
	 *  * **name** - lowercase alpha-numerics and dashes only!
	 *  * **label** - human readable label for the option
	 *  * **options** - array of value => label pairs for select/radio/checkbox options. 
	 *  
	 *  Options can be retreived by {@see subclass::get_option()}
	 *  
	 * @return array Array of options for this gateway.
	 */
	function get_options(){
		/* array(
		 *   array(
		 *     'field_type' => 'text',
		 *     'name' => 'account_number', 
		 *     'label' => 'My gateway account'
		 *   )
		 * )
		 */
		return array();
	}
	
	/**
	 * Optional. Set the text to appear at the top of your gateway's settings section.
	 * @return string. Default is the empty string.
	 */
	function settings_section_text(){
		return '';
	}
		
	/** 
	 * This method must be specified by a child class. It handles the payment processing of a gateway. Depending 
	 * on your gateway's specifications you may need to:
	 * 
	 *  - Redirect the user your paypal to complete payment offsite (e.g. PayPal)
	 *  - Collect user entered credit card details and charge the card (e.g. PayPal Pro)
	 *  - Use a generated token to process payment (e.g. Stripe)
	 * 
	 * If you wish to display an error message on the booking form simpley add an error to `$error`:
	 * <code>
	 *    $error->add( 'my-error-code', 'This error message will appear on the booking form' );
	 * </code>
	 * 
	 * @param int $booking_id The ID of the booking be processed
	 * @param array $booking Array of booking be processed 
	 * @param WP_Error $error Error object containing any errors occuring when processing payment
	 * @param EO_Booking_Form $form
	 */
	abstract function process( $booking_id, $booking, $error, $form );
	
	/**
	 * Specify a human-readable label for your gateway. This will be used for the admin settings page and 
	 * the front-end. To specify a different label for the front-end you can over-ride the 
	 * {@see subclass::get_front_end_label()} method.
	 * @return string
	 */
	abstract function get_label();
	
	/**
	 * Specify a human-readable label for your gateway for the front-end. Defaults to {@see subclass::get_label()} method.
	 * @return string
	 */
	function get_front_end_label(){
		return $this->get_label();
	}

	/**
	 * If you redirect the user to complete payment off-site, you will need to specify a payment notification url.
	 * This method is fired whenever that notification url is hit. You should inspect $_POST for a response from your
	 * payment gateway, validate it, and then take appropriate action (i.e. confirm the booking via {@see `eo_confirm_booking()`}).
	 */
	function handle_ipn(){
		return false;
	}
	
	/**
	 * If processing payment on-site you shall need to take credit card details from. For security reasons do not use
	 * the form customiser for this, as this saves the data unencrypted in the database. Instead use this method to 
	 * add form fields for collecting credit card details.
	 * 
	 * See the helper functions {@see eo_gateway_select_year()} and {@see eo_gateway_select_month()}. 
	 * 
	 * You can retrieve these values from `$_POST` in {@see `subclass::process()`}.
	 */
	function payment_form( $element ){
		/*
		 <div class="eo-booking-field-row">
			<label style="float:left;width:230px;">
				<span>Card Number</span>
				<input type="text" size="20" name="creditcardnumber"/>
			</label>
			<label style="right;width:30px;">
				<span>CVC</span>
				<input type="text" size="4" style="width:auto" name="cvc"/>
			</label>
		</div>
		*/
	}
	
	function setup_form( $form ){
		
	}
	
	final function log( $booking_id, $timestamp = false, $log = false ){
		
		if( !$log )
			return;
		
		if( !$timestamp )
			$timestamp = date( 'Y-m-d H:i:s' );
		
		$class = get_class( $this );
		$vars = get_class_vars( $class );
		$gateway = $vars['gateway'];
		
		$log['timestamp'] = $timestamp;
		$log['log_stored'] = date( 'Y-m-d H:i:s' );
		$log['gateway'] = $gateway;
		
		add_post_meta( $booking_id, '_eo_booking_gateway_log', $log );
	}
}