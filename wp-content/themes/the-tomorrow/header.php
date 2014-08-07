<?php
	global $options;
//	foreach ($options as $value) {
//	    if (get_settings( $value['id'] ) === FALSE) { $$value['id'] = $value['std']; } else { $$value['id'] = get_settings( $value['id'] ); } }
?>
<!doctype html>

<!--[if lt IE 7 ]> <html class="ie ie6 no-js" lang="en"> <![endif]-->
<!--[if IE 7 ]>    <html class="ie ie7 no-js" lang="en"> <![endif]-->
<!--[if IE 8 ]>    <html class="ie ie8 no-js" lang="en"> <![endif]-->
<!--[if IE 9 ]>    <html class="ie ie9 no-js" lang="en"> <![endif]-->
<!--[if gt IE 9]><!--><html class="no-js" lang="en"><!--<![endif]-->
<!-- the "no-js" class is for Modernizr. -->

<head id="www-thetomorrow-net" data-template-set="html5-reset">

	<meta charset="utf-8">
	
	<!-- Always force latest IE rendering engine (even in intranet) & Chrome Frame -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	
	<title>
	<?php if (is_home () ) { bloginfo('name'); echo " | "; bloginfo('description'); } elseif ( is_category() ) {
		single_cat_title(); echo " - "; bloginfo('name');
	} elseif (is_single() || is_page() ) {
		single_post_title();
	} elseif (is_search() ) {
		bloginfo('name'); echo " Risultati di ricerca per: "; echo wp_specialchars($s);
	} else { wp_title('',true); } ?>
	</title>

	<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" />
	<meta name="description" content="<?php bloginfo('description'); ?>" />

	<meta name="google-site-verification" content="">
	
	<script type="text/javascript" src="//use.typekit.net/isf6qgj.js"></script>
	<script type="text/javascript">try{Typekit.load();}catch(e){}</script>

	<link rel="stylesheet" href="<?php bloginfo('template_directory') ?>/_/css/style.css">
	
	<script src="<?php bloginfo('template_directory') ?>/_/js/modernizr-1.7.min.js"></script>
	
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
	
	<!-- wp_head() -->
	<?php wp_head(); ?>

</head>

<body <?php body_class(); ?>> 

	<div id="wrapper">

		<header class="container">

			<header class="main"
			<?php
				$venue_id = eo_get_venue();
				if($venue_id){
					$venue_header_img = eo_get_venue_meta($venue_id, '_header_img', true); ?>
					style=" background-image:url(<?php echo $venue_header_img; ?>); "
			<?php } ?>>

				
				<div class="inner">

					<h1><a href="<?php bloginfo('url'); ?>" title="" accesskey="1">
					<?php bloginfo('name') ?></a></h1>
					<?php if(is_home()): ?><h2><?php bloginfo('description') ?></h2><?php endif; ?>

					<?php /* Our navigation menu.  If one isn't filled out, wp_nav_menu falls back to wp_page_menu.  The menu assiged to the primary position is the one used.  If none is assigned, the menu with the lowest ID is used.  */ ?>
					<?php // wp_nav_menu( array( 'container_class' => 'menu-header', 'theme_location' => 'primary' ) ); ?>

					<ul class="nav_menu">
						<li class="nav_menu_search"><a href="#">search</a></li>
						<?php if(is_home()): ?><li class="nav_menu_filter"><a href="#">filter</a></li><?php endif; ?>
						<li class="nav_menu_menu"><a href="#">menu</a></li>
					</ul>

				</div>

			</header> 

			<div id="filter_nav"><div class="form">

				<form method="#" action="#">

					<div class="filter show">
						
						<p class="title">show</p>

						<p>

							<div id="options">


								<div class="option-set" data-group="type">

									<input type="checkbox" value=".event" id="event" />
									<label for="event">events</label>

									<input type="checkbox" value=".conversations" id="conversations" />
									<label for="convestations">letters</label>

								</div>

							</div>

						</p>

					</div>

					<div class="filter city">
						
						<p class="title">choose your city</p>

						<p>
							<label>city</label>
							<input class="text city" type="text" value="" id="city_search" />
						</p>

					</div>

					<div class="filter date">
						
						<?php
							$today = date("d/m/Y"); // current date
							$from_day = $today;
						?>

						<p class="title">set a date</p>

						<p class="calendar_date">
							<label>from</label>
							<input class="text from" type="text" value="<?php echo $from_day; ?>" />
							<!-- <span class="calendar"></span> -->
						</p>

						<p class="calendar_date">
							<label>to</label>
							<input class="text city" type="text" value="<?php echo $today; ?>" />
							<!-- <span class="calendar"></span> -->
						</p>

					</div>

					<input class="submit" type="submit" value="apply" />

				</form>

			</div></div>

			<div id="main_search"><div class="form">

				<form method="#" action="#">

					<input class="text" type="text" value="" />
					<input class="submit" type="submit" value="search" />

				</form>

			</div></div>

		</header> <!-- close header.container -->

		<nav id="megamenu">

			<div class="inner">

				<div class="megahead">
					<a class="logotipo" href="/">theTomorrow</a>
					<a class="close" href="#">close</a>
				</div>

				<!-- ######################## main menu ######################## -->
				<?php
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("main menu") ) : ?>			
				<?php endif; ?>
				<!-- ######################## / main menu ######################## -->

				<!-- ######################## nav 2 ######################## -->
				<div class="secondary-nav">
					<?php
						if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("secondary menu") ) : ?>			
					<?php endif; ?>
				</div>
				<!-- ######################## / nav 2 ######################## -->

				<p class="megamenu-footer">© the Tomorrow Associazione Culturale - Via Donizetti, 4 20122 Milan - All rights reserved</p>

			</div>

		</nav>



