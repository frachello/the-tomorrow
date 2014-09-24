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

	<h2 class="page-title"><?php echo get_queried_object()->label; ?></h2>


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
			'status' => 'published',
			'post_type' => 'letters',
			'posts_per_page' => -1,
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
		$this_cpt_item_letters_arr = $this_cpt_item_letters->query_vars;
//		print_r($this_cpt_item_letters);

		
//		print_r($this_cpt_item_letters_arr);
//		echo('ayy');
//		print_r ($this_cpt_item_letters);
		$letters_num = $this_cpt_item_letters->found_posts;
		wp_reset_postdata();
		
		if($letters_num>0):

		?>


		<div class="archive-box post-<?php the_ID(); ?> counter_<?php echo $col_count; ?>">

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
				    if ( $attachments ) {
				?>
				
				<?php
					$thumb_src = "";
					foreach ( $attachments as $attachment ) {
					    $image_attributes = wp_get_attachment_image_src( $attachment->ID,'full' );
					//	$attachment_meta = wp_get_attachment($attachment->ID);
					//	$attachment_mime_type = get_post_mime_type($attachment->ID);
					//	$slideClass = "slide-type-image";
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
					    
					//    if ( var_dump(endsWith($image_attributes[0],"small.png")) ){
					//    	$thumb_src = $image_attributes[0];
					//    }

						if (strpos($image_attributes[0], "small.png") !== false){
							$thumb_src = $image_attributes[0];
						}

					}

					}
				?>
				    
				<?php if ( has_post_thumbnail() ) { // controlla se il post ha un'immagine in evidenza assegnata. ?>
				<a class="img" href="<?php the_permalink() ?>" title="<?php the_title(); ?>">
				<?php if( isset($thumb_src) && $thumb_src != '' ){ ?>
				<img src="<?php echo $thumb_src; ?>" alt="<?php echo apply_filters( 'the_title', $attachment->post_title ); ?>" title="<?php echo apply_filters( 'the_title', $attachment->post_title ); ?>" data-type="<?php echo $attachment_meta['description']; ?>" data-uri="<?php echo $attachment_meta['caption']; ?>" class="slide-image">
				<?php }else{ ?>
				<?php the_post_thumbnail('thumb'); ?>
				<?php } ?>

				<?php
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
			<p class="letters_count"><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>"><?php echo $letters_num; ?> letter<?php if($letters_num>1){ echo 's'; } ?></a></p>
		</div>
	<?php
	if($col_count == $cols_number){ $col_count=0; }
	$col_count ++; $row_count ++;
	endif;
	endwhile;
	?>

	</div>

    <?php else : ?>

        <h2 class="page-title">Non ci sono post, spiacente.</h2>

    <?php endif; wp_reset_postdata(); ?>

	</div>

	<br class="clear" />

</div> <!-- chiuso content -->

<?php get_footer(); ?>
