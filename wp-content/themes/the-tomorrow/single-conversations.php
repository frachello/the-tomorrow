<?php get_header(); ?>
<!-- content -->
<div id="content">

	<div class="page-content">

		<?php $cur_id = get_the_id(); ?>
		<?php $cur_title = get_the_title(); ?>
		<?php $cur_post_type = get_post_type(); ?>

		<?php
		// define query
		$this_conv_letters_last_args = array(
			'post_type' => 'letters',
			'order' => 'ASC',
			'posts_per_page' => 999,
			'tax_query' => array (
		      array (
		         'taxonomy' => 'conversations',
		         'field' => 'ID',
		         'terms' => $cur_id,
		         'operator' => 'IN',
		      )
		      
		   )
		);
		$this_conv_letters = new WP_Query($this_conv_letters_last_args);
		?>

		<?php
		// get letters number and last letter date
		if( $this_conv_letters->have_posts() ){
			$i = 0;
			$letters_num = $this_conv_letters->post_count; // letters number
		}
		wp_reset_postdata();
		?>

		<?php
			// get themes
			$themes = "";
			$arr_themes = "";
			$conversation_themes = "";
			$themes = wp_get_post_terms($cur_id, "themes", array("fields" => "all"));
			if ( !empty( $themes ) && !is_wp_error( $themes ) ){
				
				foreach ( $themes as $theme ) {
					$arr_themes[] = $theme->name;
				}
				$conversation_themes = implode(', ', $arr_themes);
				
			}
		?>
		<p class="count-theme"><strong><?php echo $letters_num; ?></strong> letter<?php if($letters_num>1){ ?>s<?php } ?>
		<?php if($conversation_themes!==''): ?> on <strong><?php echo $conversation_themes; ?></strong><?php endif; ?>
		</p>

		<div class="title_wrap">
			<h2 class="post_<?php echo $cur_id; ?>"><?php echo $cur_title; ?></h2>
		</div>

		<div id="rightcol" class="col">
			
			<div class="li toggle_letters expand"><a href="#">expand all</a></div>
			<div class="li prev_letter"><a href="#">previous</a></div>
			<div class="li next_letter"><a href="#">next</a></div>
			
			<div class="addthis_col">
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

		<div class="col content_col">

		<?php

		if( $this_conv_letters->have_posts() ){
			$i=0;
			while( $this_conv_letters->have_posts() ): $this_conv_letters->the_post();
			$i++;
			?>

				<article id="letter-<?php echo $post->ID; ?>" class="letter <?php if($i==1){ echo 'first'; } ?>">

					<?php if(get_field("forwarded_by") && get_field("forwarded_to")): ?>
					<div class="meta_forward">
						<h3>
							<strong class="ico">»</strong>
							<span class="forwarded"><strong><?php echo get_field( "forwarded_to" ); ?></strong> forwarded the conversation to  <strong><?php echo get_field( "author_to" ); ?></strong></span>
						</h3>
					</div>
					<?php endif; ?>

					<div class="meta">
						<h3>
							<strong class="id"><?php echo $i; ?></strong>
							<?php if(get_field("author_from") || get_field("author_to")): ?>
								<span class="from_to"><strong><?php echo get_field( "author_from" ); ?></strong> to <strong><?php echo get_field( "author_to" ); ?></strong></span>
							<?php endif; ?>
						</h3>
						<p class="date"><?php echo date_ago(); ?></p>
					</div>
					
					<div class="entry">

					  <?php the_content('[leggi tutto]'); ?>

                        <?php
                            $args = array(
                                'post_type' => 'attachment',
                                'numberposts' => -1,
                                'post_status' => null,
                                'orderby' => 'menu_order',
                                'order' => 'ASC',
                                'post_parent' => $post->ID
							);
							$attachments = get_posts( $args );
						//	print_r($attachments);
							if ( $attachments ) :

                        ?>

                        <div class="slides-wrapper jcarousel">

	                        <ul>
	                        <?php
	                                foreach ( $attachments as $attachment ) {
	                                    $image_attributes = wp_get_attachment_image_src( $attachment->ID,'letters-in-page-slider' );
	                                //	$attachment_meta = wp_get_attachment($attachment->ID);
	                                //	$attachment_mime_type = get_post_mime_type($attachment->ID);
	                                	$slideClass = "slide-type-image";
									//	if ( $attachment_meta['description'] === 'virtual' ) {
									//	    $slideClass = 'slide-type-virtual';
									//	}
									//	if ( $attachment_meta['description'] === 'video' ) {
									//	    $slideClass = 'slide-type-video';
									//	}
									//	if ( $attachment_meta['description'] === 'mobile' ) {
									//	    $slideClass = 'slide-type-mobile';
									//	}
									//	if ( ( $attachment_mime_type === 'application/pdf' ) || ( $attachment_meta['description'] === 'mobile' ) ) {
									//	}else{
	                        ?>
	                            <li class="slide-item cf slide-item-<?php echo $attachment->ID; ?> <?php echo $slideClass; ?>">
	                                <figure class="slide-figure">
	                                    <img src="<?php echo $image_attributes[0]; ?>" alt="<?php echo apply_filters( 'the_title', $attachment->post_title ); ?>" title="<?php echo apply_filters( 'the_title', $attachment->post_title ); ?>" data-type="<?php echo $attachment_meta['description']; ?>" data-uri="<?php echo $attachment_meta['caption']; ?>" class="slide-image">
	                                </figure>
	                            </li>
	                        <?php } // end foreach ?>
	                        </ul>
							<p class="jcarousel-controls">
								<a title="previous image" href="#" class="jcarousel-control-prev">&lsaquo;</a>
								<a title="next image" href="#" class="jcarousel-control-next">&rsaquo;</a>
							</p>
                    	</div>
						<?php
						endif;
                        wp_reset_postdata();
                        ?>

					</div>

					<br class="clear" />

					<div class="comments">
						<p class="show_comments">
							<a onclick="loadDisqus(jQuery(this), '<?= $post->ID ?>','<?= $post->guid ?>', '<? the_permalink() ?>');">
							Show comments
							</a>
						</p>
					</div>

				</article> 

				<?php
			endwhile;
		}
		wp_reset_postdata();
		?>

		</div>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
