<?php
/*
Template Name: Single Author
*/
?>

<?php get_header(); ?>

<!-- content -->
<div id="content">

	<div class="page-content">

	<h2><?php the_title(); ?></h2>

	<?php if (have_posts()) : ?>

	<?php $current_month = ''; ?>
	<?php while (have_posts()) : the_post(); ?>
	<div class="author_desc"><?php the_content(); ?></div>
	<?php
	endwhile;
	?>


	<?php
	$author_id = "";
	$author_id = $post->ID;
	$authors_args = array(
		'post_type' => 'letters',
		'posts_per_page' => -1,
		'tax_query' => array (
	      array (
	         'taxonomy' => 'authors',
	         'field' => 'ID',
	         'terms' => $author_id,
	         'operator' => 'IN'
	      )
	   )
	);
	$authors = new WP_Query($authors_args);
	
	if( $authors->have_posts() ){

		?> <div class="archive-boxes"> <?php

		$i = 0;
		$letters_num = $authors->post_count;
		while( $authors->have_posts() ): $authors->the_post();
			$conversation_slugs = wp_get_post_terms($post->ID, 'conversations', array("fields" => "slugs"));
			$hash_permalink = get_bloginfo('url').'/conversations/'.$conversation_slugs[0].'/#letter-'.$post->ID;
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
		endwhile;
		echo '</div>';
	}
	wp_reset_postdata();
	?>

	</div>

    <?php else : ?>

        <h2>Non ci sono post, spiacente.</h2>

    <?php endif; ?>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
