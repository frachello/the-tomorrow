<?php
/*
Template Name: Conversation Themes archive
*/
?>

<?php get_header(); ?>
<?php $curr_page_term_name = 'themes'  ?>

<!-- content -->
<div id="content">

	<div class="page-content">

	<h2 class="page-title">Themes<?php // echo get_queried_object()->label; ?></h2>

	<?php

		$row_count = 1; $col_count = 1; $col_number = 3;
		$terms = get_terms($curr_page_term_name);
		if ( !empty( $terms ) && !is_wp_error( $terms ) ){
			echo '<ul class="themes-boxes">';
			foreach ( $terms as $term ) {
				$get_conversations_by_cpt_item = array(
					'post_type' => 'letters',
				//	'posts_per_page' => 1,
					'tax_query' => array (
				      array (
				         'taxonomy' => $curr_page_term_name,
				         'field' => 'slug',
				         'terms' => $term->slug
				      )
				   )
				);
				$this_cpt_item_letters = new WP_Query($get_conversations_by_cpt_item);
				if( $this_cpt_item_letters->have_posts() ){
					$letters_num = $this_cpt_item_letters->post_count;
				}
				wp_reset_postdata();
				?>

		    	<li class="col_<?php echo $col_count ; ?> <?php if ($row_count<4) { echo 'first_row'; } ?>">
		    		<a href="<?php bloginfo('url'); ?>/<?php echo $curr_page_term_name; ?>/<?php echo $term->slug; ?>">
		    		<?php echo $term->name; ?> <span><?php print $term->count . ' letter' . ($term->count  == 1 ? '' : 's') ?></span>
		    		</a>
		    	</li>
		    	<?php
				if($col_count ==3){ $col_count =0; }
				$col_count ++; $row_count ++;
			} // end foreach
			echo "</ul>";
		}

	?>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
