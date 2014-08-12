<?php
/*
Template Name: Conversations Archive
*/
?>

<?php get_header(); ?>
<?php $curr_page_term_name = get_queried_object()->name;  ?>

<!-- content -->
<div id="content">

	<div class="page-content">

	<h2><?php echo $curr_page_term_name; ?></h2>
	<p class="rules-link"><a href="/rules/">how does it work?</a></p>

	<?php $rows_count = 1; ?>

	<?php $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;  ?>
	<?php
	$wp_query_array = array(
		'post_type'=>$curr_page_term_name,
		'posts_per_page'=>-1,
		'paged' => $paged
	);

	$wp_query = new WP_Query($wp_query_array); // must be called $wp_query or the paging won't work
	?>

	<?php if( $wp_query->have_posts() ): ?>

	<div class="archive-boxes">
	<?php $current_month = ''; ?>
	<?php while( $wp_query->have_posts() ): $wp_query->the_post(); ?>

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
				$conversation_display_date = date_ago(); 
				endif;
			endwhile;
		}
		wp_reset_postdata();
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

			<h4><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>">
				<?php the_title() ?>
			</a></h4>
			<div class="entry">
			  <?php the_excerpt('[leggi tutto]'); ?>
			</div>
			<div class="bottom">
				<p class="date"><?php echo $conversation_display_date; ?></p>
			</div>

		</div>
	<?php
	if($rows_count==4){
		$rows_count=0;
	}
	$rows_count++;
	endwhile;
	?>

	</div>

    <?php else : ?>

        <h2>Non ci sono post, spiacente.</h2>

    <?php endif; ?>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
