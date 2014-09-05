


<?php get_header(); ?>
<!-- content -->
<div id="content">

	<div id="home_grid">

	<?php $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;  ?>
	<?php

	if ( isset($_GET['type']) && ($_GET['type'])!='' ){ $cpts = $_GET['type']; }
	if ( isset($_GET['city']) && ($_GET['city'])!='' ){ $city = $_GET['city']; }
	if ( isset($_GET['from_date']) && ($_GET['from_date'])!='' ){ $from_date = $_GET['from_date']; }
	if ( isset($_GET['to_date']) && ($_GET['to_date'])!='' ){ $to_date = $_GET['to_date']; }
	if ( isset($_GET['search']) && ($_GET['search'])!='' ){ $search = $_GET['search']; }

/*

	if( isset($from_date) && isset($to_date) ){
		$from_date = implode("-", ( array_reverse(explode("/", $from_date))) );
		$to_date = implode("-", ( array_reverse(explode("/", $to_date))) );
	 	$home_boxes_array = array(
			'post_type' => 'event',
			'event_start_after'=>$from_date,
			'event_start_before'=>$to_date,
			'posts_per_page'=>20, // il valore di "Blog pages show at most" deve essere inferiore a questo (http://thetomorrow.dev/wp-admin/options-reading.php?settings-updated=true)
			'paged' => $paged
	 	);
	 	if(isset($city)){
		 	$city_array = array( 
			    array(
			        'key' => '_city',
			        'value' => $city
			    )
			);
	 	}
	 	$home_boxes_array['venue_query'] = $city_array;
		$wp_query = new WP_Query($home_boxes_array); // must be called $wp_query or the paging won't work
		
	}elseif(isset($city)){

	//	echo ('<p class="current-city">'.$city.'</p>');
	 	$home_boxes_array = array(
			'post_type' => 'event',
			'venue_query' => array( 
			    array(
			        'key' => '_city',
			        'value' => $city
			    )
			),
			'event_start_after' => 'today',
			'posts_per_page'=>20, // il valore di "Blog pages show at most" deve essere inferiore a questo (http://thetomorrow.dev/wp-admin/options-reading.php?settings-updated=true)
			'paged' => $paged
	 	);
		$wp_query = new WP_Query($home_boxes_array); // must be called $wp_query or the paging won't work


	}elseif(isset($cpts)){
		echo '<!-- results for ' . implode(', ', $cpts) . ' -->';
	 	$home_boxes_array = array(
	 		'post_type'=>$cpts,
	 		'posts_per_page'=>20, // il valore di "Blog pages show at most" deve essere inferiore a questo (http://thetomorrow.dev/wp-admin/options-reading.php?settings-updated=true)
	 		'paged' => $paged
	 	);
		$wp_query = new WP_Query($home_boxes_array); // must be called $wp_query or the paging won't work
	}else{

		$home_boxes_array = array(
			'post_type'=>array('event','conversations'),
		//	'post_type'=>array('event'),
			'posts_per_page'=>20, // il valore di "Blog pages show at most" deve essere inferiore a questo (http://thetomorrow.dev/wp-admin/options-reading.php?settings-updated=true)
			'paged' => $paged

	 	);
		$wp_query = new WP_Query($home_boxes_array); // must be called $wp_query or the paging won't work

	}
*/

	?>

	<?php	// merge different queries
			// http://wordpress.stackexchange.com/questions/71576/combining-queries-with-different-arguments-per-post-type


			/* --------------------------------------------------------------------------------
			events query */

			$events_array = array(
				'post_type'=>'event',
			//	'orderby'=>'eventstart' // default
				'posts_per_page'=>15, // il valore di "Blog pages show at most" deve essere inferiore a questo (http://thetomorrow.dev/wp-admin/options-reading.php?settings-updated=true)
				'paged' => $paged
		 	);

			// filter by city
		 	if(isset($city)){
			 	$city_array = array( 
				    array(
				        'key' => '_city',
				        'value' => $city
				    )
				);
		 	}
		 	$events_array['venue_query'] = $city_array;

		 	// filter by date
		 	if( isset($from_date) ){
				$from_date = implode("-", ( array_reverse(explode("/", $from_date))) );
				$events_array['event_start_after'] = $from_date;
			}
		 	if( isset($to_date) ){
				$to_date = implode("-", ( array_reverse(explode("/", $to_date))) );
				$events_array['event_start_before'] = $to_date;
			}

		 	// filter by search
			if( isset($search) ){
				$events_array['s'] = $search;
			}


			/* --------------------------------------------------------------------------------
			conversations query */

			$conversations_array = array(
				'post_type'=>'conversations',
				'posts_per_page'=>5, // il valore di "Blog pages show at most" deve essere inferiore a questo (http://thetomorrow.dev/wp-admin/options-reading.php?settings-updated=true)
				'paged' => $paged
		 	);

			/* filter by date
		 	if( isset($from_date) && isset($to_date) ){
				$from_date = implode("-", ( array_reverse(explode("/", $from_date))) );
				$to_date = implode("-", ( array_reverse(explode("/", $to_date))) );
			 	$date_array = array(  
			        array(  
			            'after' => $from_date,  
			            'before' => $to_date,  
			        )
				);
			 	$conversations_array['date_query'] = $date_array;
		 	} */

		 	// filter by search
			if( isset($search) ){
				$conversations_array['s'] = $search;
			}

			/* --------------------------------------------------------------------------------
			merge queries */

			$events_query = new WP_Query( $events_array );
			$conversations_query = new WP_Query( $conversations_array );

			if( isset($city) || isset($from_date) || isset($to_date) ){
				$wp_query->posts = $events_query->posts;
			}else{
				$merged_query = new WP_Query();
				// start putting the contents in the new object
				$wp_query->posts = array_merge( $conversations_query->posts, $events_query->posts );
			}


//			print_r($merged_query->posts);

			// here you might wanna apply some sort of sorting on $result->posts
/*
			$postids = array();
			foreach( $merged_query->posts as $item ) {
				$postids[]=$item->ID; //create a new query only of the post ids
			}
			$uniqueposts = array_unique($postids);

//			print_r($postids);
*/
			// we also need to set post count correctly so as to enable the looping
			$wp_query->post_count = count( $wp_query->posts );
/*
			$wp_query = get_posts(array(
			    //new query of only the unique post ids on the merged queries from above
			    'post__in' => $uniqueposts,  
			    'post_type'=>array('event','conversations'),
				'posts_per_page'=>12, // il valore di "Blog pages show at most" deve essere inferiore a questo (http://thetomorrow.dev/wp-admin/options-reading.php?settings-updated=true)
				'paged' => $paged
			    ));
//			foreach( $posts as $post ) :
//			setup_postdata($post);
			?>
			<?php // echo get_the_title(); ?>
			<?php // echo eo_get_venue_name(); ?>
*/	
	 // } ?>

	<?php if( $wp_query->have_posts() ): ?>


	<!-- <?php echo 'paged: ' . $paged; ?> -->


			<?php
			while( $wp_query->have_posts() ): $wp_query->the_post();
		//	while ( have_posts() ) : the_post();
			$cur_post_type = get_post_type( $post->ID );
			?>
			

				<?php if($cur_post_type=='event'):
				/* ############################## event ############################## */
				?>

				<?php
					$venue_id = eo_get_venue();
					$venue_name = eo_get_venue_name();
					$venue_url = eo_get_venue_link();
					$address_details = eo_get_venue_address($venue_id);
				?>

				<?php if( eo_is_all_day() ){ // Choose a different date format depending on whether we want to include time
					$date_format = 'j F Y'; 
				}else{
					$date_format = 'j F Y ' . get_option('time_format'); 
				} ?>
				<?php // Is event recurring or a single event ?>
				<?php // if( eo_reoccurs() ): ?>

					<?php // Event reoccurs - is there a next occurrence? ?>
					<?php $next =   eo_get_next_occurrence($date_format);?>

					<?php if($next): ?>

				<div class="home_box event">

					<div class="top" style=" border-top: 8px solid <?php echo eo_get_event_color(); ?>; " data-color="<?php echo eo_get_event_color(); ?>">

								
								<?php
								//	If the event is occurring again in the future, display the date
									$day = eo_get_schedule_start('d');
									$ordinal = eo_get_schedule_start('S');
									$month = eo_get_schedule_start('M');
								?>
								<p class="date">
									<span class="day"><?php echo $day; ?></span>
									<span class="ordinal-month"><?php echo $ordinal; ?>, <?php echo $month; ?></span>
								</p>


						<?php $categories_list = get_the_term_list( $post->ID, 'event-category', '', ', ',''); ?>
						<p class="cat" data-color="<?php echo eo_get_event_color(); ?>">
							<?php echo $categories_list; ?>
						</p>

						<div class="bg" style="background: <?php echo eo_get_event_color(); ?>"></div>

					</div> <!-- /close .top -->

					
					<p class="title venue_<?php echo $venue_id; ?>">
						<strong><a href="<?php echo $venue_url; ?>"><?php echo $venue_name ?></a></strong> <br />
						<a href="<?php the_permalink() ?>" title="<?php the_title(); ?>"><?php the_title() ?></a>
					</p>
					
					<a class="venue-more" href="<?php echo $venue_url; ?>">enter venue</a>

					<div class="bottom">
						<p class="address"><?php echo $address_details['state']; ?>, <?php echo $address_details['country']; ?></p>
					</div>

					<div class="share_wrap">
						
						<a class="share" href="#">share</a>

						<div class="share_baloon hide">

						    <div class="addthis_toolbox addthis_default_style ">		    

							    <a class="addthis_button_facebook" title="Facebook" href="#">
							    	Share on facebook</a>

							    <a class="addthis_button_twitter" title="Tweet" href="#">
									Share on twitter</a>

							    <a class="addthis_button_email" title="Email" href="#">
							    	Share on email</a>

						    </div>

						</div>

					</div>

				</div>

					<?php else: ?>
						<?php
						// The event has finished (no more occurrences)
						// printf('<p class="date">'.__('This event finished on %s').'.</p>', eo_get_schedule_last('d F Y',''));
						?>
					<?php endif; ?>

				<?php // endif; ?>

				<?php elseif($cur_post_type=='conversations'):					
				/* ############################## conversation ############################## */
				$conversation_title = get_the_title();
				?>

				<div class="home_box conversations">

					<a class="more" href="<?php the_permalink() ?>" title="<?php echo $conversation_title; ?>">
						<?php echo $conversation_title; ?>
					</a>

					<div class="top" style=" border-top: 8px solid <?php echo eo_get_event_color(); ?>; ">
						<p class="title">
							<a href="<?php the_permalink() ?>" title="<?php echo $conversation_title; ?>"><?php echo $conversation_title; ?></a>
							<?php

							// get this conversation authors
							$authors = "";
							$arr_authors = "";
							$authors = wp_get_post_terms($post->ID, "authors", array("fields" => "all"));
							if ( !empty( $authors ) && !is_wp_error( $authors ) ){
								echo '<span class="authors">';
								foreach ( $authors as $author ) {
									$arr_authors[] = $author->name;
								}
								echo implode(', ', $arr_authors);
								echo "</span>";
							}

							?>

							<?php
								// get this conversation themes
								$themes = "";
								$arr_themes = "";
								$arr_theme_slugs = "";
								$conversation_themes = "";
								$conversation_theme_slugs = "";
							
								// print the post time of the last letter of this conversation
								$conversation_id = "";
								$conversation_id = $post->ID;
								$this_conv_letters_last_args = array(
									'post_type' => 'letters',
								//	'posts_per_page' => 1,
									'tax_query' => array (
								      array (
								         'taxonomy' => 'conversations',
								         'field' => 'ID',
								         'terms' => $conversation_id,
								         'operator' => 'IN'
								      )
								   )
								);
								$this_conv_letters = new WP_Query($this_conv_letters_last_args);
								if( $this_conv_letters->have_posts() ){
									$i = 0;
									$letters_num = $this_conv_letters->post_count;
									while( $this_conv_letters->have_posts() ): $this_conv_letters->the_post();
									$i++;
										if ($i == $letters_num):
										$themes = wp_get_post_terms($post->ID, "themes", array("fields" => "all"));
										if ( !empty( $themes ) && !is_wp_error( $themes ) ){
											
											foreach ( $themes as $theme ) {
												$arr_themes[] = $theme->name;
												$arr_theme_slugs[] = $theme->slug;
											}
											$conversation_themes = implode(', ', $arr_themes);
											$conversation_theme_slugs = implode(', ', $arr_theme_slugs);
											
										}
										endif;
									endwhile;
								}
								wp_reset_postdata();
								
							?>

						</p>

						<div class="bottom">

							<p class="date"><?php echo date_ago(); ?></p>

							<?php
							// print the post time of the last letter of this conversation
							$conversation_id = "";
							$conversation_id = $post->ID;
							$this_conv_letters_last_args = array(
								'post_type' => 'letters',
								'posts_per_page' => 999,
								'tax_query' => array (
							      array (
							         'taxonomy' => 'conversations',
							         'field' => 'ID',
							         'terms' => $conversation_id,
							         'operator' => 'IN'
							      )
							   )
							);
							$this_conv_letters = new WP_Query($this_conv_letters_last_args);
							if( $this_conv_letters->have_posts() ){
								$i = 0;
								$letters_num = $this_conv_letters->post_count;
								while( $this_conv_letters->have_posts() ): $this_conv_letters->the_post();
								$i++;
									if ($i == $letters_num): ?>

								<?php if(get_field("author_from")||get_field("author_to")): ?>
								<ul class="authors">
									<li>to <strong><?php echo get_field( "author_to" ); ?></strong></li>
									<li>from <strong><?php echo get_field( "author_from" ); ?></strong></li>
								</ul> 
								<?php endif; ?>
							
							<?php
									endif;
								endwhile;
							}
							wp_reset_postdata();
							?>

							<p class="count-theme">

								<strong>
									<?php echo $letters_num; ?>
								</strong> <?php print ' letter' . ($letters_num  == 1 ? '' : 's') ?>
								<?php if($conversation_themes!==''): ?>
								on <strong><?php echo $conversation_themes; ?></strong>
								<?php endif; ?>
							</p>

						</div>

					</div>

					</div>

				<?php else: ?>
					<p><?php the_title(); ?> <br /></p>
				<?php endif; ?>

			<?php endwhile; ?>

			<div class="pagination">

				<span class="prev"><?php next_posts_link('&laquo; previous') ?></span>
				<span class="next"><?php previous_posts_link('next &raquo;') ?></span>

			</div>

		</div>

	<?php else: ?>
		<h2 class="no-content">Sorry, no posts matched your search criteria.</h2>
	<?php
	endif;
	wp_reset_postdata();
	?>

</div> <!-- chiuso content -->

<?php get_footer(); ?>
