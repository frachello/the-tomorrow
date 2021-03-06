<?php
/*
Template Name: Letters Archive
*/
?>

<?php get_header(); ?>
<?php $curr_page_term_name = get_queried_object()->name;  ?>

<!-- content -->
<div id="content">

	<div class="page-content">

	<h2 class="page-title"><?php echo get_queried_object()->label; ?></h2>
	<p class="rules-link"><a href="<?php bloginfo('url'); ?>/rules/">how does it work?</a></p>

	<?php $rows_count = 1; ?>

	<?php $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;  ?>
	<?php
	$wp_query_array = array(
		'post_type'=>$curr_page_term_name,
		'posts_per_page'=>20,
		'paged' => $paged
	);

	$wp_query = new WP_Query($wp_query_array); // must be called $wp_query or the paging won't work
	?>

	<?php if( $wp_query->have_posts() ): ?>

	<div class="archive-boxes">
	<?php $current_month = ''; ?>
	<?php while( $wp_query->have_posts() ): $wp_query->the_post(); ?>
		<?php
			$conversation_slugs = wp_get_post_terms($post->ID, 'conversations', array("fields" => "slugs"));
			$hash_permalink = get_bloginfo('url').'/conversations/'.$conversation_slugs[0].'/#letter-'.$post->ID;
		
		if(isset($conversation_slugs[0])){
		?>
		<?php
			$post_month = get_the_date('F Y');
		    if ( $post_month != $current_month ) {
		        $current_month = $post_month;
		        echo "<h3>$current_month</h3>";
				$rows_count = 1;
		    }
	    ?>
		<div class="archive-box post-<?php the_ID(); ?> counter_<?php echo $rows_count; ?>">

			<h4><a href="<?php echo $hash_permalink; ?>" title="<?php the_title(); ?>">
				<span class="title"><?php the_title() ?></span> -
				<span class="number">
				<!-- http://stackoverflow.com/questions/8102221/php-multidimensional-array-searching-find-key-by-specific-value/8102246#8102246 -->
				/ <?php
				$conversations = get_the_terms( $post->ID, 'conversations' );
				if ( !empty( $conversations ) ){
				    // get the first term
				    $conversation = array_shift( $conversations );
					$cpt_item_id = $conversation->term_id;
				}
				$get_letters_by_cpt_item = array(
					'post_type' => 'letters',
					'posts_per_page' => -1,
					'tax_query' => array (
				      array (
				         'taxonomy' => 'conversations',
				         'field' => 'ID',
				         'terms' => $cpt_item_id,
				         'operator' => 'IN'
				      )
				   )
				);
				$this_cpt_item_letters = new WP_Query($get_letters_by_cpt_item);
				$letters_num = $this_cpt_item_letters->post_count;
				echo $letters_num;
				wp_reset_postdata();

				?></span>
			</a></h4>
			<div class="entry">
			  <?php the_excerpt('[leggi tutto]'); ?>
			</div>
			<div class="bottom">
				<p class="date"><?php echo date_ago(); ?></p>
			</div>

		</div>
	<?php
	}
	if($rows_count==4){
		$rows_count=0;
	}
	$rows_count++;

	endwhile;
	?>

	<div class="pagination">
		<span class="prev"><?php next_posts_link('&laquo; previous') ?></span>
		<span class="next"><?php previous_posts_link('next &raquo;') ?></span>
	</div>

	</div>

    <?php else : ?>

        <h2 class="page-title">Non ci sono post, spiacente.</h2>

    <?php endif; ?>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
