<?php

	/* Change the post excerpt length */
//	add_filter('excerpt_length', 'my_excerpt_length');
//	function my_excerpt_length($length) {
//	return 30; }
	
	/* Disable the Admin Bar. */
	add_filter( 'show_admin_bar', '__return_false' );
	
	/* featured image */
	add_theme_support( 'post-thumbnails' );
	
	/* sidebars */
	if ( function_exists('register_sidebar') ) {
	
	    register_sidebar(array(
		'name' => 'main menu',
	        'before_widget' => '<nav id="%1$s" class="main_menu %2$s boxD">',
	        'after_widget' => '</nav>'
	    ));    
	
	    register_sidebar(array(
		'name' => 'secondary menu',
	        'before_widget' => '<nav id="%1$s" class="secondary_menu %2$s boxD">',
	        'after_widget' => '</nav>'
	    ));    

	}

	/**
	* Conditional function to check if post belongs to term in a custom taxonomy.
	*
	* @param    tax        string                taxonomy to which the term belons
	* @param    term    int|string|array    attributes of shortcode
	* @param    _post    int                    post id to be checked
	* @return             BOOL                True if term is matched, false otherwise
	*/
	function pa_in_taxonomy($tax, $term, $_post = NULL) {
		// if neither tax nor term are specified, return false
		if ( !$tax || !$term ) { return FALSE; }
		// if post parameter is given, get it, otherwise use $GLOBALS to get post
		if ( $_post ) {
			$_post = get_post( $_post );
		} else {
			$_post =& $GLOBALS['post'];
		}
		// if no post return false
		if ( !$_post ) { return FALSE; }
		// check whether post matches term belongin to tax
		$return = is_object_in_term( $_post->ID, $tax, $term );
		// if error returned, then return false
		if ( is_wp_error( $return ) ) { return FALSE; }
		return $return;
	}










	add_action('add_meta_boxes','my_add_metabox');

	function my_add_metabox(){
		add_meta_box('my_id','Venues meta', 'my_metabox_callback', 'event_page_venues', 'side', 'high');
	}

	function my_metabox_callback( $venue ){
	
		//Metabox's innards:
//		$time = eo_get_venue_meta($venue->term_id, '_opening_times',true);
		$suggested_by = eo_get_venue_meta($venue->term_id, '_suggested_by',true);
		$website = eo_get_venue_meta($venue->term_id, '_website',true);
		$header_img = eo_get_venue_meta($venue->term_id, '_header_img',true);

		//Remember to use nonces!
		wp_nonce_field('my_venue_meta_save', 'my_plugin_nonce_field' );	
	?>	
		<!--
		<label> Opening times:</label>
		<input type="text" name="my_opening_time" value="<?php echo esc_attr($time);?>" >
		<br /><br />
		-->
		<label> Suggested by:</label>
		<input type="text" name="my_suggested_by" value="<?php echo esc_attr($suggested_by);?>" >
		<br /><br />
		<label> Venue website:</label>
		<input type="text" name="my_website" value="<?php echo esc_attr($website);?>" >
		<br /><br />
		<label> Image:</label><br />
		<input type="text" name="my_header_img" value="<?php echo esc_attr($header_img);?>" >

	<?php 
	}

	add_action ('eventorganiser_save_venue','my_save_venue_meta');
	function my_save_venue_meta( $venue_id ){

	    //If our nonce isn't present just silently abort.    
	    if( !isset( $_POST['my_plugin_nonce_field'] ) )
	        return;

	    //Check permissions
	    $tax = get_taxonomy( 'event-venue');
	    if ( !current_user_can( $tax->cap->edit_terms ) )
	        return;

	    //Check nonce
	    check_admin_referer( 'my_venue_meta_save', 'my_plugin_nonce_field' );

	    //Retrieve meta value(s)
	    $value_suggested_by = $_POST['my_suggested_by'];
	    $value_website = $_POST['my_website'];
	    $value_header_img = $_POST['my_header_img'];

	    //Update venue meta
	    eo_update_venue_meta($venue_id,  '_suggested_by', $value_suggested_by);
	    eo_update_venue_meta($venue_id,  '_website', $value_website);
	    eo_update_venue_meta($venue_id,  '_header_img', $value_header_img);
	    return;
	}


	/* ======================================== define taxonomies ======================================== */
	/* http://wordpress.org/support/topic/list-posts-by-taxonomy-tag */ 
	
	function build_taxonomies() {  

		// theme
		register_taxonomy(
			'themes',
			'post',
			array(
				'hierarchical' => true,
				'label' => 'Theme',
				'query_var' => true,
				'rewrite' => true
			)
		);

	}
	add_action( 'init', 'build_taxonomies', 10 );	


?>