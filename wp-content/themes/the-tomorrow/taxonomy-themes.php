<?php
/*
Template Name: Conversations Archive by theme
*/
?>

<?php get_header(); ?>
<?php $curr_page_term_name = get_queried_object()->name;  ?>
<?php $curr_page_term_count = get_queried_object()->count;  ?>
<?php $curr_page_term_slug = get_queried_object()->slug;  ?>

<!-- content -->
<div id="content">

	<div class="page-content">

	<h2 class="page-title"><span>Letters on</span> <?php echo $curr_page_term_name; ?></h2>
	<p class="rules-link"><a href="<?php bloginfo('url'); ?>/rules/">how does it work?</a></p>

	<?php $rows_count = 1; ?>
	<?php // $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;  ?>
	<?php
		$main_query_array = array(
			'post_type'=>'letters',
			'posts_per_page'=>12,
			'themes'=>$curr_page_term_slug
	 	);
		$wp_query = new WP_Query($main_query_array);
	?>

	<?php
	if ($wp_query->have_posts()) :
	?>

	<div class="archive-boxes">
	<?php $current_month = ''; ?>
	<?php
		while ($wp_query->have_posts()) : $wp_query->the_post();
		$conversation_slugs = wp_get_post_terms($post->ID, 'conversations', array("fields" => "slugs"));
	?>

<!--
	conversation_slugs
	<?php echo 'post id: '.$post->ID; ?>


	<?php print_r($conversation_slugs); ?>	
-->
	<?php
		$hash_permalink = get_bloginfo('url').'/conversations/'.$conversation_slugs[0].'/#letter-'.$post->ID;
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
				<?php the_title() ?>
			</a></h4>
			<div class="entry">
			  <?php the_excerpt('[leggi tutto]'); ?>
			</div>
			<div class="bottom">
				<p class="date"><?php echo date_ago(); ?></p>
			</div>

		</div>
	<?php
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
