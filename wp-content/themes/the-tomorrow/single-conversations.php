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
			'tax_query' => array (
		      array (
		         'taxonomy' => 'conversations',
		         'field' => 'ID',
		         'terms' => $cur_id,
		         'operator' => 'IN'
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
			while( $this_conv_letters->have_posts() ): $this_conv_letters->the_post();
			$i++;
				if ($i == $letters_num):
				?>
				<?php
					$difference = round((strtotime(date("r")) - strtotime(get_the_time('r')))/(24*60*60),0);
					if ($difference > 3) { $last_letter_date = get_the_date('j F Y'); // last letter date
					}else{ $last_letter_date = human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'; } // last letter date
				?>														
				<?php
				endif;
			?>


			<?php
			endwhile;
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
		<p class="count-theme"><strong><?php echo $letters_num; ?></strong> letters
		<?php if($conversation_themes!==''): ?> on <strong><?php echo $conversation_themes; ?></strong><?php endif; ?>
		</p>

		<h2 class="post_<?php echo $cur_id; ?>"><?php echo $cur_title; ?></h2>

		<div class="col rightcol">
			
			
			<div class="li expand"><a href="#">expand all</a></div>
			<div class="li prev"><a href="#">previous</a></div>
			<div class="li next"><a href="#">next</a></div>
			
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

		<div class="col content_col">

		<?php

		if( $this_conv_letters->have_posts() ){
			$i=0;
			while( $this_conv_letters->have_posts() ): $this_conv_letters->the_post();
			$i++;
			?>

				<article class="letter" id="post-<?php echo $post->ID; ?>"  class="letter <?php if($i==1){ echo 'first'; } ?>">

					<div class="meta">
						<h3>
							<strong><?php echo $i; ?></strong> —
							<?php if(get_field("author_from")||get_field("author_to")): ?>
								<strong><?php echo get_field( "author_from" ); ?></strong> to <strong><?php echo get_field( "author_to" ); ?></strong>
							<?php endif; ?>
						</h3>
						<p class="date"><?php the_time('j F Y') ?></p>
					</div>
					
					<div class="entry">
					  <?php the_content('[leggi tutto]'); ?>
					</div>

					<div class="comments">
						<p class="show_comments"><a onclick="loadDisqus(jQuery(this), '<?= $post->ID ?>','<?= $post->guid ?>', '<? the_permalink() ?>');">
						Show comments
						</a></p>
					</div>

				</article> 

				<?php
			endwhile;
		}
		wp_reset_postdata();
		?>

		</div>

	</div>

</div> <!-- chiuso content -->

<?php get_footer(); ?>
