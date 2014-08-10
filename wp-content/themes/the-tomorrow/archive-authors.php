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


	<?php $rows_count = 1; ?>

	<?php if (have_posts()) : ?>

	<div class="archive-boxes">

	<?php while (have_posts()) : the_post(); ?>

		<?php
		// print the post time of the last letter of this conversation
		$author_id = "";
		$author_id = $post->ID;
		$get_letters_by_author = array(
			'post_type' => 'letters',
		//	'posts_per_page' => 1,
			'tax_query' => array (
		      array (
		         'taxonomy' => 'authors',
		         'field' => 'ID',
		         'terms' => $author_id,
		         'operator' => 'IN'
		      )
		   )
		);
		$this_author_letters = new WP_Query($get_letters_by_author);
		if( $this_author_letters->have_posts() ){
			$i = 0;
			$letters_num = $this_author_letters->post_count;
		}
		wp_reset_postdata();
		?>

		<div class="archive-box post-<?php the_ID(); ?> counter_<?php echo $rows_count; ?>">

			
				<?php if ( has_post_thumbnail() ) { // controlla se il post ha un'immagine in evidenza assegnata. ?>
				<a class="img" href="<?php the_permalink() ?>" title="<?php the_title(); ?>">
				<?php
				  the_post_thumbnail('thumb');
				}else{
					if(is_user_logged_in()){
						echo '<a href="'.get_edit_post_link().'">';
						echo '<img src="http://placehold.it/220x180&text=add+author+image" />';
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
