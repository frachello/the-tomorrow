<?php
/*
Template Name: Authors Archive
*/
?>

<?php get_header(); ?>
<?php $curr_page_term_name = get_queried_object()->name;  ?>

<!-- content -->
<div id="content">

	<div class="page-content">

	<h2><?php echo $curr_page_term_name; ?></h2>


	<?php $row_count = 1; $col_count = 1; $cols_number = 4; ?>

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

	<?php while( $wp_query->have_posts() ): $wp_query->the_post(); ?>

		<?php
		// print the post time of the last letter of this conversation
		$cpt_item_id = "";
		$cpt_item_id = $post->ID;
		$get_letters_by_cpt_item = array(
			'post_type' => 'letters',
		//	'posts_per_page' => 1,
			'tax_query' => array (
		      array (
		         'taxonomy' => $curr_page_term_name,
		         'field' => 'ID',
		         'terms' => $cpt_item_id,
		         'operator' => 'IN'
		      )
		   )
		);
		$this_cpt_item_letters = new WP_Query($get_letters_by_cpt_item);
		if( $this_cpt_item_letters->have_posts() ){
			$i = 0;
			$letters_num = $this_cpt_item_letters->post_count;
		}
		wp_reset_postdata();
		?>

		<div class="archive-box post-<?php the_ID(); ?> counter_<?php echo $col_count; ?>">

			
				<?php if ( has_post_thumbnail() ) { // controlla se il post ha un'immagine in evidenza assegnata. ?>
				<a class="img" href="<?php the_permalink() ?>" title="<?php the_title(); ?>">
				<?php
				  the_post_thumbnail('thumb');
				}else{
					if(is_user_logged_in()){
						echo '<a href="'.get_edit_post_link().'">';
						echo '<img src="http://placehold.it/220x180&text=add+cpt_item+image" />';
					}else{ ?>
						<a class="img" href="<?php the_permalink() ?>" title="<?php the_title(); ?>">
					<?php
						echo '<img src="http://placehold.it/220x180&text=image+coming+soon" />';
					}
				}
				?>
			</a>
			<h4><a href="<?php echo the_permalink() ?>" title="<?php the_title(); ?>">
				<?php the_title() ?>
			</a></h4>
			<p class="letters_count"><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>"><?php echo $letters_num; ?> letters</a></p>
		</div>
	<?php
	if($col_count == $cols_number){ $col_count=0; }
	$col_count ++; $row_count ++;
	endwhile;
	?>

	</div>

    <?php else : ?>

        <h2>Non ci sono post, spiacente.</h2>

    <?php endif; wp_reset_postdata(); ?>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
