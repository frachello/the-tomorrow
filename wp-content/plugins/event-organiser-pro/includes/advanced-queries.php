<?php
/**
 * @package advanced-venue-queries
 */

/**
 * Adds 'venue_query' argument to WP_Query / get_posts() / eo_get_events()
 *
 * Hooked onto `pre_get_posts`
 *
 * @since 1.0
 * @access private
 * @ignore
 *
 * @param WP_Query $query The query object
 */
function _eventorganiser_events_venue_query( $query ) {
	//MAYBELATER More advanced queries, apart form meta queries. E.g. proximity and/or order;
	$venue_query = $query->get( 'venue_query' );

	//Check EO is installed as a precaution
	if ( defined( 'EVENT_ORGANISER_DIR' ) && $venue_query ) {

		//MAYBELATER add caching for eo_get_venues
		$venues = eo_get_venues( array( 'meta_query' => $venue_query ) );
		$venue_ids = $venues ? array_map( 'intval', wp_list_pluck( $venues, 'term_id' ) ) : array( -1 );

		$tax_query = (array) $query->get( 'tax_query' );
		
		array_unshift( $tax_query, array(
			'taxonomy' => 'event-venue',
			'field'    => 'id',
			'terms'    => $venue_ids,
		));
		
		$query->set( 'tax_query', $tax_query );
		
		if( $venues && $query->get('orderby') == 'distance' ){
			add_filter( 'posts_clauses', '_eventorganiser_event_distance_sort', 10, 2 );
		}
	}
}
add_action( 'pre_get_posts', '_eventorganiser_events_venue_query', 8 );


/**
 * Alters SQL to include meta_query when querying venues
 *
 * Hooked onto `terms_clauses`
 *
 * @since 1.0
 * @access private
 * @ignore
 * @todo More advanced queries, apart form meta queries. E.g. proximity and/or order
 * @todo Deal with caching
 *
 * @param array   $pieces     Array of SQL pieces
 * @param array   $taxonomies Array of taxonomies
 * @param array   $args       The term query
 * @return array The altered SQL pieces
 */
function _eventorganiser_venue_meta_query( $pieces, $taxonomies, $args ) {
	global $wpdb;
	//MAYBELATER deal with venue caching
	
	if ( !in_array( 'event-venue', $taxonomies ) || empty( $args['meta_query'] ) )
		return $pieces;

	/* Meta query support for venue terms */
	$meta_query = get_meta_sql( $args['meta_query'] , 'eo_venue', 't', 'term_id' );
	$pieces['join'] .= $meta_query['join'];
	$pieces['where'] .= $meta_query['where'];
	
	if( !empty( $args['meta_query']['proximity'] ) ){
		
		global $wpdb;
		
		//Parse defaults
		$proximity_query = array_merge(
			array(
				'unit' => 'miles',
				'compare' => '<',
				'distance' => false,
				'center' => array( 'lat' => 0, 'lng' => 0 ),
			),
			$args['meta_query']['proximity']
		);

		//Validate unit
		$radius_of_earth = array( 'km' => 6371, 'miles' => 3959 );
		$unit = isset( $radius_of_earth[$proximity_query['unit']] ) ? $proximity_query['unit'] : 'miles'; 
		
		//Validate compare
		$compares = array( '<', '<=' , '>', '>=' );
		$compare = in_array( $proximity_query['compare'], $compares ) ? $proximity_query['compare'] : '<';
		

		$pieces['join'] .= " INNER JOIN {$wpdb->eo_venuemeta} AS lat ON (t.term_id = lat.eo_venue_id AND lat.meta_key='_lat')" 
					. " INNER JOIN {$wpdb->eo_venuemeta} AS lng ON (t.term_id = lng.eo_venue_id AND lng.meta_key='_lng')";
		
		$pieces['fields'] .= $wpdb->prepare(
							", ( %d * acos( cos( radians(%f) ) * cos( radians( lat.meta_value ) ) * cos( radians( lng.meta_value ) - radians(%f) ) 
									+ sin( radians(%f) ) * sin( radians( lat.meta_value ) ) ) ) AS distance",
							$radius_of_earth[$unit],
							$proximity_query['center']['lat'],
							$proximity_query['center']['lng'],
							$proximity_query['center']['lat']
						);
	
		if( $proximity_query['distance'] > 0 ){
			$pieces['where'] .= $wpdb->prepare( " HAVING distance {$compare} %f", $proximity_query['distance'] );
		}
		
		if( 'distance' == $args['orderby'] ){
			$pieces['orderby'] = "ORDER BY distance";
		}
	}
	
	if( !empty( $args['meta_query'][0] ) && 'meta_value' == $args['orderby'] ){
		$pieces['orderby'] = "ORDER BY $wpdb->eo_venuemeta.meta_value";
	
	}elseif( !empty( $args['meta_query'][0] ) && 'meta_value_num' == $args['orderby'] ){
		$pieces['orderby'] = "ORDER BY $wpdb->eo_venuemeta.meta_value.meta_value+0";
	
	}elseif( 'rand' == $args['orderby'] ){
		$pieces['orderby'] = "ORDER BY RAND()";
	}
	
	return $pieces;
}
add_filter( 'terms_clauses', '_eventorganiser_venue_meta_query', 10, 3 );

/**
 * Sets cache_domain for venue queries with a meta_query component
 * 
 * WordPress caches taxonomy queries - we add a cache_domain unique to each meta_query.
 * @ignore
 * @access private
 * @param array $args
 * @param array $taxonomies
 * @return array
 */
function _eventorganiser_venue_meta_query_clear_cache( $args, $taxonomies ){
	
	if ( in_array( 'event-venue', $taxonomies ) && !empty( $args['meta_query'] ) ){
		$args['cache_domain'] .= 'eo_get_venues:'.md5( serialize( $args['meta_query']  ) );
	}
	return $args;
}
add_filter( 'get_terms_args', '_eventorganiser_venue_meta_query_clear_cache', 10, 2 );


/**
 * This function handles the case where we want to sort events by distance. 
 * 
 * A corresponding 'proximity' argument of a venue_query must be present.
 * This functon just handles the ordering. (and is a bit of a hack).
 * 
 * @ignore
 * @since 1.5
 * @param array $pieces Array of SQL pieces
 * @param WP_Query $query The query
 * @return array The modified SQL pieces
 */
function _eventorganiser_event_distance_sort( $pieces, $query ){

	global $wpdb;

	//Check if EO is installed before using eventorganiser_is_event_query()
	if( defined( 'EVENT_ORGANISER_DIR' ) && eventorganiser_is_event_query( $query ) ){
		
		$venue_query = $query->get( 'venue_query' );
		
		//Check if a venue proxomity query is given AND we're sorting event sby distance
		if( $venue_query && !empty( $venue_query['proximity'] ) && $query->get('orderby') == 'distance' ){		
			
			//Set up proximity query
			$proximity_query = array_merge(
				array(
					'unit' => 'miles',
					'compare' => '<',
					'distance' => false,
					'center' => array( 'lat' => 0, 'lng' => 0 ),
				),
				$venue_query['proximity']
			);
			
			//Validate unit
			$radius_of_earth = array( 'km' => 6371, 'miles' => 3959 );
			$unit = isset( $radius_of_earth[$proximity_query['unit']] ) ? $proximity_query['unit'] : 'miles';
			
			$pieces['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id";
			$pieces['join'] .= " LEFT JOIN {$wpdb->eo_venuemeta} AS lat ON {$wpdb->term_taxonomy}.term_id = lat.eo_venue_id";
			$pieces['join'] .= " LEFT JOIN {$wpdb->eo_venuemeta} AS lng ON {$wpdb->term_taxonomy}.term_id = lng.eo_venue_id";

			$pieces['where'] .= " AND lat.meta_key = '_lat'";
			$pieces['where'] .= " AND lng.meta_key = '_lng'";
			
			$pieces['fields'] .= $wpdb->prepare(
					", ( %d * acos( cos( radians(%f) ) * cos( radians( lat.meta_value ) ) * cos( radians( lng.meta_value ) - radians(%f) )
						+ sin( radians(%f) ) * sin( radians( lat.meta_value ) ) ) ) AS distance, lat.meta_value as lat, lng.meta_value as lng",
					$radius_of_earth[$unit],
					$proximity_query['center']['lat'],
					$proximity_query['center']['lng'],
					$proximity_query['center']['lat']
			);
			
			$pieces['orderby'] = "distance";
		}
	}

	return $pieces;
}
add_filter( 'posts_clauses', '_eventorganiser_event_distance_sort', 10, 2 );


function _eventorganiser_handle_event_search( $template ){
	
	global $wp_query;
	
	if( !defined( 'EVENT_ORGANISER_DIR' ) ){
		return; //abort if EO is not activated.
	}
	
	if( $wp_query->is_main_query() ){
		if( ( $wp_query->is_search() ||  isset( $_GET['s'] ) && $_GET['s'] == '' ) && eventorganiser_is_event_query( $wp_query, true ) ){
			$_template = eo_locate_template( 'archive-event.php' );
			$template = ( $_template ? $_template : $template );
		}
	}
	
	return $template;
}
add_filter( 'template_include', '_eventorganiser_handle_event_search', 15 );
?>