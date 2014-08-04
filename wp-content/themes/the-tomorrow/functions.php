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

/* ======================================== define taxonomies ======================================== */
/* http://wordpress.org/support/topic/list-posts-by-taxonomy-tag */ 
	
	function build_taxonomies() {  
	
//		$author_labels = array(
//			'name'              => _x( 'Authors', 'taxonomy general name' ),
//			'singular_name'     => _x( 'Author', 'taxonomy singular name' ),
//			'search_items'      => __( 'Search Authors' ),
//			'all_items'         => __( 'All Authors' ),
//			'parent_item'       => __( 'Parent Author' ),
//			'parent_item_colon' => __( 'Parent Author:' ),
//			'edit_item'         => __( 'Edit Author' ),
//			'update_item'       => __( 'Update Author' ),
//			'add_new_item'      => __( 'Add New Author' ),
//			'new_item_name'     => __( 'New Author Name' ),
//			'menu_name'         => __( 'Author' ),
//		);

		// author
//		register_taxonomy(
//			'autore',
//			'post',
//			array(
//				'hierarchical' => true,
//				'label' => 'Autori',
//				'labels' => $author_labels,
//				'query_var' => true,
//				'rewrite' => true
//			)
//		);

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


/* ======================================== define post types  ======================================== */

	function create_multimedia_post_type() {

//		register_post_type( 'lettere',
//			array(
//				'labels' => array(
//					'name' => 'Lettere',
//					'singular_name' => 'Lettere'
//				),
//				'supports' => array('post-formats','title','editor','excerpt','trackbacks','custom-fields','comments','revisions','thumbnail','author'),
//				'taxonomies' => array('autore','tema','discussione'),
//				'public' => true
//			)
//		);

//		register_post_type( 'eventi',
//			array(
//				'labels' => array(
//					'name' => 'Eventi',
//					'singular_name' => 'Evento'
//				),
//				'supports' => array('post-formats','title','editor','excerpt','trackbacks','custom-fields','comments','revisions','thumbnail','author'),
//				'taxonomies' => array('luoghi',),
//				'public' => true
//			)
//		);


	}

//	add_action( 'init', 'create_multimedia_post_type',10 );	

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

?>
