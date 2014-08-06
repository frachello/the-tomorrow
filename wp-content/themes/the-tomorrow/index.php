<?php get_header(); ?>
<!-- content -->
<div id="content">

<!-- main col -->
<section id="main_content">

		<?php
		$events = new WP_Query(array(
			'post_type'=>array('event','conversations'),
			'post_per_page'=>-1
		));
		if( $events->have_posts() ){ ?>

			<div id="home_grid">

				<?php
				while( $events->have_posts() ): $events->the_post();
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

					<div class="home_box event brand1">

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

					<div class="home_box conversations brand2">

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
										$conversation_themes = "";
										$themes = wp_get_post_terms($post->ID, "themes", array("fields" => "all"));
										if ( !empty( $themes ) && !is_wp_error( $themes ) ){
											
											foreach ( $themes as $theme ) {
												$arr_themes[] = $theme->name;
											}
											$conversation_themes = implode(', ', $arr_themes);
											
										}
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
								// print_r($count_conv_letters );
								if( $this_conv_letters->have_posts() ){
									$i = 0;
									$letters_num = $this_conv_letters->post_count;
									while( $this_conv_letters->have_posts() ): $this_conv_letters->the_post();
									$i++;
										if ($i == $letters_num):
										?>
										<?php
											$difference = round((strtotime(date("r")) - strtotime(get_the_time('r')))/(24*60*60),0);
											if ($difference > 3) { echo get_the_date('j F Y');
											}else{ echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'; }
										?>														
										<?php
										endif;
									endwhile;
								}
								wp_reset_postdata();
								?>

								</p>

								<p class="count-theme">

									<strong>
										<?php echo $letters_num; ?>
									</strong> letters
									<?php if($conversation_themes!==''): ?>
									on <strong><?php echo $conversation_themes; ?></strong>
									<?php endif; ?>
								</p>

							</div>

						</div>

						</div>

					<?php endif; ?>

				
				<?php endwhile; ?>
			</div>
			<?php
		}
		wp_reset_postdata();
		?>

		<br />







		<!--

		<h3>authors:</h3>
		<?php
		$authors = new WP_Query(array(
			'post_type'=>array('authors'),
			'post_per_page'=>-1
		));
		if( $authors->have_posts() ){
			?><ul><?php
			while( $authors->have_posts() ): $authors->the_post();
			//Content of loop
			?>
			<li><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>"><?php the_title() ?></a></li>
			<?php
			endwhile;
			?></ul><?php
		}
		wp_reset_postdata();
		?>

		-->

		<!--

		<div class="discussione">

		<?php
		$taxonomy = 'discussione';
		$terms = get_terms( $taxonomy );
		?>

		<h2>discussioni</h2>
		<?php
		echo '<ul>';

		foreach ( $terms as $term ) {

		    // The $term is an object, so we don't need to specify the $taxonomy.
		    $term_link = get_term_link( $term );
		   
		    // If there was an error, continue to the next term.
		    if ( is_wp_error( $term_link ) ) {
		        continue;
		    }

		    // We successfully got a link. Print it out.
		    echo '<li><a href="' . esc_url( $term_link ) . '">' . $term->name . '</a></li>';
		}

		echo '</ul>';

		?>

		</div>

		<div id="post_col">

		<br /><br />

		<h2>lettere</h2>

		<?php
			$args = array(
                'orderby' => 'menu_order',
                'order' => 'ASC'
			);
			$home_posts = new WP_Query( $args );
			if ( $home_posts->have_posts() ) {
				while ( $home_posts->have_posts() ) {
					$home_posts->the_post();
					$post_slug = $slug = basename(get_permalink());
					$slug_lettera = wp_get_post_terms($post->ID, 'discussione', array("fields" => "slugs"));
					$letter_in_thread_url = get_bloginfo('url').'/discussione/'.$slug_lettera[0].'/#'.$post_slug;

		?>

				<p><a href="<?php echo $letter_in_thread_url; ?>"><?php echo get_the_title(); ?></a></p>
		<?php
				}
			}
		?>

		-->		

</section>
<!-- chiusa section#main_content -->	

</div> <!-- chiuso content -->

<?php get_footer(); ?>
