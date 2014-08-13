<?php
/**
 * Class used to create a widget which lists the events a user is attending
 */
class EO_User_Attending_Widget extends WP_Widget{

	var $w_arg = array(
		'title'=> "Events you're attending",
		'numberposts'=> 5,
		'no_events'=>''
		);

	function __construct() {
		$widget_ops = array('classname' => 'EO_User_Attending_Widget', 'description' => __('Displays a list of events a user is attending','eventorganiser') );
		parent::__construct('EO_User_Attending_Widget', __("Events user is attending",'eventorganiserp'), $widget_ops);
	}

 
  function form($instance){	
	$instance = wp_parse_args( (array) $instance, $this->w_arg );
  	?>
  	<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'eventorganiser'); ?>: </label>
		<input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" />
	</p>
  	<p>
  		<label for="<?php echo $this->get_field_id('numberposts'); ?>"><?php _e('Number of events','eventorganiser');?>:   </label>
	  	<input id="<?php echo $this->get_field_id('numberposts'); ?>" name="<?php echo $this->get_field_name('numberposts'); ?>" type="number" size="3" value="<?php echo intval($instance['numberposts']);?>" />
	</p>
	<?php
  }
 
  
  function update($new_instance, $old_instance){  
	$validated=array();
	$validated['title'] = sanitize_text_field( $new_instance['title'] );
	$validated['numberposts'] = intval($new_instance['numberposts']);
	return $validated;
    }

 
	function widget($args, $instance){
		extract($args, EXTR_SKIP);
		
		$args = array_merge(array(
				'id'=>'',
				'class'=>'eo-user-attending',
				'type'=>'user-attending-widget',
				'no_events' => '',
		),$args);
		
		$query = array(
			'bookee_id' => get_current_user_id(),
			'event_start_after' => 'now',
			'numberposts' => $instance['numberposts']
		);

		if( !is_user_logged_in() )
			return;
		
    	echo $before_widget;

			$widget_title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base );

    		if ( $widget_title )
   				echo $before_title.esc_html($widget_title).$after_title;
    		
    		eventorganiser_list_events( $query, $args );

     	echo $after_widget;
  	}
 
}