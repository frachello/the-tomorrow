<?php
/**
 * A collection of utility functions.
 * @package utility-functions
 */

/**
 * Format a price (float). Optionally appends/prepends currency symbol.
 *
 * Formats a float value to two decimal places. If $currency is true it then appends
 * (or prepends depending on currency_position option) the currency symbol as
 * selected by currency.
 *
 * Applies filter `eventorganiser_format_price`.
 *
 * @uses eventorganiser_get_currency_symbol()
 * @uses eventorganiser_pro_get_option()
 * @since 1.0
 * @param float   $price    The value to be formatted
 * @param bool    $currency True to add currency symbol. False otherwise.
 * @return string The formatted price
 */
function eo_format_price( $price, $currency = true ) {

	$price = floatval( $price );
	$negative = ( $price < 0 ) ? '-' : '';
	$formated_price = number_format_i18n( abs( $price ), 2 );

	if ( $currency ) {
		$symbol = eventorganiser_get_currency_symbol( eventorganiser_pro_get_option( 'currency', 'USD' ) );
		$before = eventorganiser_pro_get_option( 'currency_position' );
		if ( $before )
			$formated_price = $negative.$symbol.$formated_price;
		else
			$formated_price = $negative.$formated_price.$symbol;
	}

	return apply_filters( 'eventorganiser_format_price', $formated_price, $price, $currency );
}

/**
 * Format a price (float). Optionally appends/prepends currency symbol.
 * 
 * @deprecated 1.2 Use `{@see eo_format_price()}`
 */
function eventorganiser_format_price( $price, $currency = true ){
	_deprecated_function( __FUNCTION__, '1.2', 'eo_format_price()' );
	return eo_format_price( $price, $currency );
}

/**
 * Returns the symbol for a given currency (identified by its currency code).
 *
 * The currency code is as given by {@see eventorganiser_get_currencies()}.
 *
 * @since 1.0
 * @uses eo_get_currencies()
 * @param string  $currency The currency code
 * @return string The currency symbol. False if currency is not recognised.
 */
function eo_get_currency_symbol( $currency ) {

	$currencies = eventorganiser_get_currencies();

	if ( isset( $currencies[$currency] ) )
		return $currencies[$currency]['symbol'];

	return false;
}

/**
 * Returns the symbol for a given currency (identified by its currency code).
 * @deprecated 1.2 Use `{@see eo_get_currency_symbol()}`
 */
function eventorganiser_get_currency_symbol( $currency ) {
	return eo_get_currency_symbol( $currency );
}

/**
 * Returns an array of available currencies.
 *
 * Applies the filter `eventorganiser_currencies`. Returns an array of the form
 *
 * <code>
 *      currency identifier => array(
 *                              'name' => currency name,
 *                              'symbol' => currency symbol,
 *                              'symbol_html' => symbol in html
 *                             )
 * </code>
 * @since 1.0
 * @uses filter eventorganiser_currencies to allow currencies to be added/removed/altered
 * @return array An array of available currencies
 */
function eo_get_currencies() {
	$currencies =  array(
		'USD' => array('name'=>'USD - U.S. Dollars', 'symbol'=>'$','symbol_html'=>'$'),
		'GBP' => array('name'=>'GBP - British Pounds', 'symbol'=>'£','symbol_html'=>'&pound;'),
		'EUR' => array('name'=>'EUR - Euros', 'symbol'=>'€','symbol_html'=>'&euro;'),
		'AUD' => array('name'=>'AUD - Australian Dollars', 'symbol'=>'$','symbol_html'=>'$'),
		'BRL' => array('name'=>'BRL - Brazilian Real', 'symbol'=>'R$','symbol_html'=>'R$'),
		'CAD' => array('name'=>'CAD - Canadian Dollars', 'symbol'=>'$','symbol_html'=>'$'),
		'CZK' => array('name'=>'CZK - Czech koruny', 'symbol'=>'Kč','symbol_html'=>''),
		'DKK' => array('name'=>'DKK - Danish Kroner', 'symbol'=>'kr','symbol_html'=>'kr'),
		'HKD' => array('name'=>'HKD - Hong Kong Dollars', 'symbol'=>'$','symbol_html'=>'$'),
		'HUF' => array('name'=>'HUF - Hungarian Forints', 'symbol'=>'Ft','symbol_html'=>'Ft'),
		'ILS' => array('name'=>'ILS - Israeli Shekels', 'symbol'=>'₪','symbol_html'=>'&#8362;'),
		'JPY' => array('name'=>'JPY - Japanese Yen', 'symbol'=>'¥','symbol_html'=>'&#165;'),
		'MYR' => array('name'=>'MYR - Malaysian Ringgits', 'symbol'=>'RM','symbol_html'=>'RM'),
		'MXN' => array('name'=>'MXN - Mexican Pesos', 'symbol'=>'$','symbol_html'=>'$'),
		'NZD' => array('name'=>'NZD - New Zealand Dollars', 'symbol'=>'$','symbol_html'=>'$'),
		'NOK' => array('name'=>'NOK - Norwegian Kroner', 'symbol'=>'kr','symbol_html'=>'kr'),
		'PHP' => array('name'=>'PHP - Philippine Pesos', 'symbol'=>'Php','symbol_html'=>'Php'),
		'PLN' => array('name'=>'PLN - Polish zloty', 'symbol'=>'zł','symbol_html'=>''),
		'SGD' => array('name'=>'SGD - Singapore Dollars', 'symbol'=>'$','symbol_html'=>'$'),
		'SEK' => array('name'=>'SEK - Swedish Kronor', 'symbol'=>'kr','symbol_html'=>'kr'),
		'CHF' => array('name'=>'CHF - Swiss Francs', 'symbol'=>'CHF','symbol_html'=>'CHF'),
		'TWD' => array('name'=>'TWD - Taiwan New Dollars', 'symbol'=>'$','symbol_html'=>'$'),
		'THB' => array('name'=>'THB - Thai Baht', 'symbol'=>'฿','symbol_html'=>' &#3647;'),
		'TRY' => array('name'=>'TRY - Turkish Liras', 'symbol'=>'TL','symbol_html'=>' &#3647;'),
	);
	return apply_filters( 'eventorganiser_currencies', $currencies );
}

/**
 * Returns an array of available currencies.
 * @deprecated 1.2 Use `{@see eo_get_currencies()}`
 */
function eventorganiser_get_currencies() {
	return eo_get_currencies();
}

/**
 * Sums a particular property in list of objects
 *
 * The objects can be arrays. This function is used, for instance, to find the total price in a list of tickets.
 *
 * @ignore
 * @since 1.0
 * @param array   $list  An array of objects, to sum over
 * @param string  $field The particular property of the objects to sum
 * @return int The sum
 */
function eventorganiser_list_sum( $list, $field ) {
	$sum = 0;
	if( !$list )
		return false;
	
	foreach ( $list as $key => $el ) {
		if ( is_object( $el ) )
			$sum = $sum + $el->$field;
		else
			$sum = $sum + $el[ $field ];
	}
	return $sum;
}

/**
 * Logs a user in using an e-mail and a password. Optional specify to 'remember' the user.
 * 
 * Gets user's username from their e-mail via `{@see get_user_by()}`, and signs them in via
 * `{@see wp_signon()}`.
 * 
 * @uses wp_signon()
 * @uses get_user_by();
 * @since 1.2
 * @param string $email The user's e-mail
 * @param string $user_password The user's password
 * @param boolean $remember Whether to remember the user
 * @return WP_User|WP_Error Either WP_Error on failure, or WP_User on success.
 */
function eo_login_by_email( $email, $user_password, $remember = false ) {
	$user = get_user_by( 'email', $email );
	if ( $user )
		$user_login = $user->user_login;
	else
		$user_login = false;

	return wp_signon( compact( 'user_login', 'user_password', 'remember' ) );
}

/**
 * @ignore
 * @deprecated 1.2 Use eo_login_by_email()
 * @since 1.0
 */
function eventorganiser_login_by_email( $email, $user_password, $remember = false ) {
	return eo_login_by_email( $email, $user_password, $remember );
}

/**
 * Generates a unique username from a given username.
 * 
 * If the username is already unique, it is just returned. Otherwise
 * an integer is appended to ensure its unique.
 * 
 * @ignore
 * @since 1.0
 * @param string $username
 * @return string A unique username
 */
function eventorganiser_generate_unique_username( $username ) {

	if ( username_exists( $username ) ) {
		//Ensure uniqueness
		$num =2;
		while ( username_exists( $username . "-{$num}" ) ) {
			$num++;
		}
		$username = $username . "-{$num}";
	}
	return $username;
}

/**
 * Helper function for convering an array of objects to an array suitable for jQuery autcomplete
 * @access private
 * @since 1.0
 * @ignore
 */
function eventorganiser_convert_for_autocomplete( $list, $value, $label ) {
	$array = array();
	if ( $list ) {
		foreach ( $list as $item ) {
			$array[] = array( 'value' => $item->$value, 'label' => $item->$label );
		}
	}
	return $array;
}

/**
 * Returns true if installed WordPress version is equal to or greater than $version
 * 
 * @ignore
 * @since 1.0
 * @param string $version The version to compare
 * @return boolean
 */
function eventorganiser_blog_version_is_atleast( $version ){
	$compare = version_compare( $version, get_bloginfo( 'version' ) );
	return $compare <= 0;
}


/**
 * Checks if an array is an associative array
 * If there is one non-integer key the array is consider associatve.
 * @ignore
 * @since 1.0
 * @param (array) The array (or object) to check
 * @return (bool) True if it is an associative array, false otherwise.
 */
function eventorganiser_is_associative( $array ) {
	return (bool) ( is_array($array)  && count( array_filter( array_keys( $array ), 'is_string' ) ) );
}

/**
 * Returns the email address for admin notifications.
 * 
 * This function acts as a holder for the filter: `eventorganiser_admin_email`. This 
 * allows plugins to change the email to which booking notifications are sent.
 * 
 * @since 1.5
 * 
 * @return string Email use for admin notificatinos.
 */
function eo_get_admin_email(){	
	$email = get_option( 'admin_email' );
	return apply_filters( 'eventorganiser_admin_email', $email );
}

/**
 * Returns the email address for admin notifications for a specific booking. 
 *
 * This function acts as a holder for the filter: `eventorganiser_booking_notification_email`. This
 * allows plugins to change the email to which booking notifications are sent, on a per booking (and
 * so per event basis).
 *
 * @since 1.5
 *
 * @return arrays Emails use for booking notificatinos.
 */
function eo_get_booking_notification_email( $booking_id ){
	$emails = array( eo_get_admin_email() );
	return apply_filters( 'eventorganiser_booking_notification_email', $emails, $booking_id );
}


/**
 * Validates names
 *
 * This function offers a limited validation of names, by removing specific characters 
 * that are not expected in names. It also trims additional white spaces. 
 *
 * @since 1.6.2
 * @param string $name The name to validate
 * @return string The validated name
 */
function eo_sanitize_name( $name ){
	return trim( preg_replace("/[~!@#\$%\^&\*\(\)=\+\|\[\]\{\};\\:\",\.\<\>\?\/1-9]+/", "", $name) );	
}

/**
 * Helper class for sorting an array by a specified key
 * <code>
 * $sorter = EO_Uasort::get_instance()
 * $sorted = $sorter->sort( $array, $key, 'desc' ); 
 * </code>
 * @ignore
 */
class EO_Uasort{
	
	private static $instance = null;
	private $key;
	private $order;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function __construct() {
		//Singleton! Lonely, oh so lonely
		if ( !is_null( self::$instance ) )
			trigger_error( "Tried to construct a second instance of class \"$class\"", E_USER_WARNING );
	}

	function sort ( $array, $key, $order = 'asc' ){
		$this->key = $key;
		$this->order = ( strtolower( $order ) == 'desc' ) ? 'desc' : 'asc'; 
		uasort( $array, array( $this, 'compare' ) );	
		return $array;
	}

	function compare( $x, $y ){
		
		if ( $x[$this->key] == $y[$this->key] )
			return 0;
		else if ( $x[$this->key] < $y[$this->key] )
			return ( $this->order == 'asc' ) ? -1 : 1;
		else
			return ( $this->order == 'asc' ) ? 1 : -1;
	}
}

if( !class_exists( 'EO_Pro_Admin_Notice_Handler' ) ):

/**
 * Helper class for adding notices to admin screen
 * <code>
 * $notice_handler = EO_Pro_Admin_Notice_Handler::get_instance()
 * $notice_handler->add_notice( 'foobar', 'screen_id', 'Notice...' ); 
 * </code>
 * @ignore
 */
class EO_Pro_Admin_Notice_Handler{

	static $prefix = 'eventorganiserpro';

	static $notices = array();

	static $instance;

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

		if( did_action( 'plugins_loaded') ){
			self::load();
		}else{
			add_action ( 'plugins_loaded', array( __CLASS__, 'load' ) );
		}
	}

	/**
	 * Hooks the dismiss listener (ajax and no-js) and maybe shows notices on admin_notices
	 */
	static function load(){
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_action( 'admin_init',array( __CLASS__, 'dismiss_handler' ) );
		add_action( 'wp_ajax_'.self::$prefix.'-dismiss-notice', array( __CLASS__, 'dismiss_handler' ) );
	}

	function add_notice( $id, $screen_id, $message, $type = 'alert' ){
		self::$notices[$id] = array(
				'screen_id' => $screen_id,
				'message'   => $message,
				'type'      => $type
		);
	}

	function remove_notice( $id ){
		if( isset( self::$notices[$id] ) ){
			unset( self::$notices[$id] );
		}
	}

	/**
	 * Print appropriate notices.
	 * Hooks EO_Admin_Notice_Handle::print_footer_scripts to admin_print_footer_scripts to
	 * print js to handle AJAX dismiss.
	 */
	static function admin_notice(){

		$screen_id = get_current_screen()->id;

		//Notices of the form ID=> array('screen_id'=>screen ID, 'message' => Message,'type'=>error|alert)
		if( !self::$notices )
			return;

		$seen_notices = get_option(self::$prefix.'_admin_notices',array());

		foreach( self::$notices as $id => $notice ){
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
			__('Dismiss this notice','eventorganiser'),
			__('Dismiss','eventorganiser')
			);
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_footer_scripts' ), 11 );
		}
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
	static function dismiss_notice($notice){
		$seen_notices = get_option(self::$prefix.'_admin_notices',array());
		$seen_notices[] = $notice;
		$seen_notices = array_unique($seen_notices);
		update_option(self::$prefix.'_admin_notices',$seen_notices);
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
}//End EO_Admin_Notice_Handler
endif;

/**
 * Helper function to determine if the booking form template has been moved/edited.
 * The 1.7.* -> 1.8.0 update will make alterations to the default template, and these
 * changes will need to be reflected in an customised copy of the template.
 * @ignore
 * @since 1.7.0
 */
function _eventorganiser_has_changed_booking_form_template(){
	if( defined( 'EVENT_ORGANISER_VER' ) ){
		if( EVENT_ORGANISER_PRO_DIR . 'templates/eo-booking-form.php' !==  eo_locate_template( 'eo-booking-form.php' ) ){
			return true;	
		}
		if( '74320f7dc504f6c9a0ecc13aa92bd9b1' !== hash_file( 'md5', eo_locate_template( 'eo-booking-form.php' ) ) ){
			return true;
		}	
	}
	return false;
}

add_action( 'init', '_eventorganiser_has_changed_booking_form_template' );
?>