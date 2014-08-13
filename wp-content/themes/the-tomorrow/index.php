<?php get_header(); ?>
<!-- content -->
<div id="content">

	<?php $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;  ?>
	<?php $cpts = $_GET['type']; ?>
	
	<?php
	if(isset($cpts)){
		echo '<!-- results for ' . implode(', ', $cpts) . ' -->';
	 	$home_boxes_array = array(
	 		'post_type'=>$cpts,
	 		'posts_per_page'=>20, // il valore di "Blog pages show at most" deve essere inferiore a questo (http://thetomorrow.dev/wp-admin/options-reading.php?settings-updated=true)
	 		'paged' => $paged
	 	);
	}else{
		$home_boxes_array = array(
			'post_type'=>array('event','conversations'),
			'posts_per_page'=>20, // il valore di "Blog pages show at most" deve essere inferiore a questo (http://thetomorrow.dev/wp-admin/options-reading.php?settings-updated=true)
			'paged' => $paged
	 	);
	}
	$wp_query = new WP_Query($home_boxes_array); // must be called $wp_query or the paging won't work

	if( $wp_query->have_posts() ): ?>

	<!-- <?php echo 'paged: ' . $paged; ?> -->

		<div id="home_grid">

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

				<div class="home_box event">

					<div class="top" style=" border-top: 8px solid <?php echo eo_get_event_color(); ?>; " data-color="<?php echo eo_get_event_color(); ?>">

						<?php if( eo_is_all_day() ){ // Choose a different date format depending on whether we want to include time
							$date_format = 'j F Y'; 
						}else{
							$date_format = 'j F Y ' . get_option('time_format'); 
						} ?>
						<!-- Is event recurring or a single event -->
						<?php // if( eo_reoccurs() ):?>
							<!-- Event reoccurs - is there a next occurrence? -->
							<?php $next =   eo_get_next_occurrence($date_format);?>

							<?php if($next): ?>
								<!-- If the event is occurring again in the future, display the date -->
								<?php
									$day = eo_get_schedule_start('d');
									$ordinal = eo_get_schedule_start('S');
									$month = eo_get_schedule_start('M');
								?>
								<p class="date">
									<span class="day"><?php echo $day; ?></span>
									<span class="ordinal-month"><?php echo $ordinal; ?>, <?php echo $month; ?></span>
								</p>
							<?php else: ?>
								<!-- Otherwise the event has finished (no more occurrences) -->
								<?php printf('<p class="date">'.__('This event finished on %s').'.</p>', eo_get_schedule_last('d F Y',''));?>
							<?php endif; ?>
						<?php // endif; ?>

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
					

					<div class="bottom">
						<p class="address"><?php echo $address_details['state']; ?>, <?php echo $address_details['country']; ?></p>
						<a class="share" href="#">share</a>
					</div>

				</div>

				<?php elseif($cur_post_type=='conversations'):					
				/* ############################## conversation ############################## */
				$conversation_title = get_the_title();
				?>

				<div class="home_box conversations">

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

							<p class="date">

							<?php
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
									echo date_ago(); 
									endif;
								endwhile;
							}
							wp_reset_postdata();
							?>

							</p>

							<p class="count-theme">

								<strong>
									<?php echo $letters_num; ?>
								</strong> <?php print ' letter' . ($letters_num  == 1 ? '' : 's') ?>
								<?php if($conversation_themes!==''): ?>
								on <a href="<?php bloginfo('url'); ?>/themes/<?php echo $term->slug; ?><?php echo $conversation_theme_slugs; ?>"><?php echo $conversation_themes; ?></strong>
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
		<h2>Sorry, no posts matched your search criteria.</h2>
	<?php
	endif;
	wp_reset_postdata();
	?>

</div> <!-- chiuso content -->

<?php get_footer(); ?>
