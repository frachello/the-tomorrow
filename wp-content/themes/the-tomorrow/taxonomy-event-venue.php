<?php
/**
 * The template for displaying the venue page
 *
 ***************** NOTICE: *****************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 *
 * To overwrite this template with your own, make a copy of it (with the same name)
 * in your theme directory. See http://docs.wp-event-organiser.com/theme-integration for more information
 *
 * WordPress will automatically prioritise the template in your theme directory.
 ***************** NOTICE: *****************
 *
 * @package Event Organiser (plug-in)
 * @since 1.0.0
 */

//Call the template header
get_header(); ?>

<!-- content -->
<div id="content" class="venue_page">

	<!-- Page header, display venue title-->
	<header class="main_internal_page_header">	
		
		<?php
			$venue_id = eo_get_venue();
			$venue_name = eo_get_venue_name();
			$venue_slug = eo_get_venue_slug();
			$venue_url = eo_get_venue_link();
			$address_details = eo_get_venue_address($venue_id);
			$venue_meta = eo_get_venue_meta($venue_id);
			$venue_suggested_by = eo_get_venue_meta($venue_id, '_suggested_by', true);
			$venue_website = eo_get_venue_meta($venue_id, '_website', true);
			$venue_website_name = eo_get_venue_meta($venue_id, '_website_name', true);
		?>
		
		<p class="address"><strong><?php echo $address_details['state']; ?></strong>, <?php echo $address_details['country']; ?></p>
		
		<h1 class="venue_title">
			<?php printf( __( '%s', 'eventorganiser' ), eo_get_venue_name($venue_id) );?>
			<!-- <?php printf( __( 'Events at: %s', 'eventorganiser' ), '<span>' .eo_get_venue_name($venue_id). '</span>' );?> -->
		</h1>

	</header>

	<div class="page-content">

		<div id="rightcol" class="col relative">
			
			<div class="li map"><a href="<?php bloginfo('url'); ?>/places/">view on map</a></div>
			<?php include (TEMPLATEPATH . '/inc/addthis-rightcol.inc.php'); ?>
			
		</div>

		<?php if($venue_suggested_by||$venue_website):?>
		<div class="col leftcol">
			<?php if($venue_suggested_by):?>
			<p>
				suggested by<br />
				<strong><?php echo $venue_suggested_by; ?></strong>
			</p>
			<?php endif; ?>
			<?php if($venue_website):?>
			<p>
				more on<br />
				<strong><a href="<?php echo $venue_website; ?>" target="_blank">
					<?php
					if( isset($venue_website_name) && $venue_website_name!='' ){
						echo $venue_website_name;
					}else{
						echo $venue_website;
					}
					?>
				</a></strong>
			</p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="col content_col">

			<?php if( $venue_description = eo_get_venue_description( $venue_id ) ){
				 echo $venue_description;
			} ?>

			<?php if ( have_posts() ) : ?>

				<?php while ( have_posts()) : the_post(); ?>

					<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

						<p>

							<a href="<?php the_permalink(); ?>">
								<?php 
									//If it has one, display the thumbnail
									if( has_post_thumbnail() )
										the_post_thumbnail('thumbnail', array('style'=>'float:left;margin-right:20px;'));

									//Display the title
									the_title()
								;?>
							</a>
					
							<?php
								if( eo_is_all_day() ){
								$format = 'd F Y';
								$microformat = 'Y-m-d';
							}else{
								$format = 'd F Y '.get_option('time_format');
								$microformat = 'c';
							}?>
							<time itemprop="startDate" datetime="<?php eo_the_start($microformat); ?>"><?php eo_the_start($format); ?></time>
						
						</p>

						<?php echo eo_get_event_meta_list(); ?>

						<?php the_excerpt(); ?>

					</div><!-- #post-<?php the_ID(); ?> -->

		    		<?php endwhile; ?><!--The Loop ends-->

					<!-- Navigate between pages
					<?php 
					if ( $wp_query->max_num_pages > 1 ) : ?>
						<nav id="nav-below">
							<div class="nav-next events-nav-newer"><?php next_posts_link( __( 'Later events <span class="meta-nav">&rarr;</span>' , 'eventorganiser' ) ); ?></div>
							<div class="nav-previous events-nav-newer"><?php previous_posts_link( __( ' <span class="meta-nav">&larr;</span> Newer events', 'eventorganiser' ) ); ?></div>
						</nav>
					<?php endif; ?> -->


			<?php else : ?>
					<!-- If there are no events -->
					<article id="post-0" class="post no-results not-found">
						<header class="entry-header">
							<h1 class="entry-title"><?php _e( 'Nothing Found', 'eventorganiser' ); ?></h1>
						</header><!-- .entry-header -->
						<div class="entry-content">
							<p><?php _e( 'Apologies, but no events were found for the requested venue. ', 'eventorganiser' ); ?></p>
						</div><!-- .entry-content -->
					</article><!-- #post-0 -->
			<?php endif; ?>

		</div>



		<?php // if( $_GET['dev']==1 ): ?>
		
		<?php 
		$events_array = array(
		//	'orderby'=>'eventstart' // default
			'post_type'=>'event',
			'posts_per_page'=>4,
			'showpastevents'=>true,
			'event_start_after'=>'today',
			'event-venue'=>$venue_slug,
			'group_events_by' => 'series',
	 	);
	 	$events_query = new WP_Query( $events_array );
	 	?>
						 	
		<?php if( $events_query->have_posts() ): ?>

		<div class="upcoming_events">

			<h3 class="upcoming_events_title">Upcoming events</h3>

			<div class="box_grid upcoming_events_grid">

			<?php while( $events_query->have_posts() ): $events_query->the_post(); ?>

			<div class="home_box event">

					<?php $categories_list = get_the_term_list( $post->ID, 'event-category', '', ', ',''); ?>
					<p class="cat" data-color="<?php echo eo_get_event_color(); ?>">
						<?php echo $categories_list; ?>
					</p>

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

						<div class="bg" style="background: <?php echo eo_get_event_color(); ?>"></div>

					</div> <!-- /close .top -->

					
					<p class="title venue_<?php echo $venue_id; ?>">
						<strong><?php echo $venue_name ?></strong> <br />
						<?php the_title() ?>
					</p>
					
					<a class="venue-more" href="<?php echo $venue_url; ?>">enter venue</a>

					<?php
						$event_website=get_the_content();
						if($event_website):
					?>
					<div class="bottom">
						<a class="site_link" href="<?php echo $event_website; ?>" target="_blank">
							link
						</a>
					</div>
					<?php endif; ?>

					<p class="share_text">share</p>
					<div class="share_wrap">
						
						<a class="share" href="#">share</a>

						<div class="share_baloon hide">

							<?php $event_website=get_the_content(); ?>
						    <div class="addthis_toolbox addthis_default_style " addthis:url="<?php if($event_website){ echo $event_website; }else{ echo $venue_url; } ?>" addthis:title="<?php if($event_website){ the_title(); }else{ echo $venue_name; } ?> via @theTomorrownet ">

							    <a class="addthis_button_facebook" title="Facebook" href="#">
							    	Share on facebook</a>

							    <a class="addthis_button_twitter" title="Tweet" href="#" tw:via="theTomorrownet">
									Share on twitter</a>

							    <a class="addthis_button_email" title="Email" href="#">
							    	Share on email</a>

						    </div>

						</div>

					</div>

				</div>

			<?php endwhile; ?>

			</div>

			</div> <!-- / .upcoming_events -->

		<?php // else: ?>
			<!-- <h2 class="no-content">Sorry, no posts matched your search criteria.</h2> -->
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>

		<?php // endif; // close if dev mode ?>



		<div class="venue-events-calendar">
		<?php
			// echo do_shortcode('[eo_fullcalendar]');
			echo eo_get_event_fullcalendar(array( 
			    'event-venue' => $venue_name,
		//		'showpastevents' => true,
			));
		?>
		</div>


	    <div id="disqus_thread"></div>
	    <script type="text/javascript">
	        /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
	        var disqus_shortname = 'frachello'; // required: replace example with your forum shortname

	        /* * * DON'T EDIT BELOW THIS LINE * * */
	        (function() {
	            var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
	            dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
	            (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
	        })();
	    </script>
	    <noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
	    <a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>

	</div> <!-- / .page-content -->

</div><!-- #content -->

<?php get_footer(); ?>
