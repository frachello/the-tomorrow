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
<div id="content">

<!-- main col -->
<section id="main_content" class="internal_page venue_page">

	<!-- Page header, display venue title-->
	<header class="main_internal_page_header">	
		
		<?php
			$venue_id = eo_get_venue();
			$venue_name = eo_get_venue_name();
			$venue_url = eo_get_venue_link();
			$address_details = eo_get_venue_address($venue_id);
		?>
		
		<p class="address"><strong><?php echo $address_details['state']; ?></strong>, <?php echo $address_details['country']; ?></p>
		
		<h1 class="venue_title">
			<?php printf( __( '%s', 'eventorganiser' ), eo_get_venue_name($venue_id) );?>
			<!-- <?php printf( __( 'Events at: %s', 'eventorganiser' ), '<span>' .eo_get_venue_name($venue_id). '</span>' );?> -->
		</h1>

	</header>

	<div class="page-content">

		<div class="col rightcol">
			
			<div class="li gallery"><a href="#">gallery</a></div>
			<div class="li map"><a href="#">view on map</a></div>
			
			<div class="addthis">
			    <p>share</p>
			    <div class="addthis_toolbox addthis_default_style ">		    

				    <a class="addthis_button_facebook" title="Facebook" href="#">
				    	Share on facebook</a>

				    <a class="addthis_button_twitter" title="Tweet" href="#">
						Share on twitter</a>

				    <a class="addthis_button_email" title="Email" href="#">
				    	Share on email</a>

			    </div>
			    <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=ra-53e1050f71fefa29"></script>
			</div>

		</div>

		<div class="col leftcol">
			<p>leftcol</p>
		</div>

		<div class="col content_col">

			<?php if( $venue_description = eo_get_venue_description( $venue_id ) ){
				 echo '<p class="venue-archive-meta">'.$venue_description.'</p>';
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

		<div class="venue-events-calendar">
		<?php
			// echo do_shortcode('[eo_fullcalendar]');
			echo eo_get_event_fullcalendar(array( 
			    'event-venue' => $venue_name
			));
		?>
		</div>

		<?php // comments_template(); ?>

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

	</section>

</div><!-- #content -->

<?php get_footer(); ?>
