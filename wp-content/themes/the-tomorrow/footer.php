
<footer>

  <div class="inner">

    <div class="info">
      <a class="logo" href="<?php bloginfo('url'); ?>"><?php bloginfo('name') ?></a>
      <p class="desc">the Tomorrow is a daily web journal that reports the untiring generation of ideas, events, conversations that unite the thinkers who live in the mental and geographical space of Europe.</p>
    </div>

    <div class="secondary-nav">
      <?php
        if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("footer nav") ) : ?>     
      <?php endif; ?>
    </div>

    <div class="bottom">
      <p class="address">the Tomorrow, via Donizetti 4 — 20122 Milan, Italy</p>
      <p class="disclaimer"><a target="_blank" href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Some rights reserved - </a></p>
    </div>

  </div>

</footer>
    
</div> <!-- chiudo #wrapper -->



<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<!-- <script>window.jQuery || document.write("<script src='<?php bloginfo('template_directory') ?>/_/js/jquery-1.5.1.min.js'>\x3C/script>")</script> -->
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php bloginfo('template_directory') ?>/_/js/jquery.ui.datepicker-it.js"></script>

<script src="<?php bloginfo('template_directory') ?>/_/js/imagesloaded.pkgd.min.js"></script>
<script src="<?php bloginfo('template_directory') ?>/_/js/jquery.jcarousel.min.js"></script>
<!-- <script src="<?php bloginfo('template_directory') ?>/_/js/jquery.touchSwipe.min.js"></script> -->
<?php // if( is_home() ): ?>
<script src="<?php bloginfo('template_directory') ?>/_/js/isotope.pkgd.min.js"></script>
<?php // endif; ?>
<script src="<?php bloginfo('template_directory') ?>/_/js/jquery.infinitescroll.min.js"></script>

<?php wp_footer(); ?>
<script src="<?php bloginfo('template_directory') ?>/_/js/classie.js"></script>
<script src="<?php bloginfo('template_directory') ?>/_/js/retina.min.js"></script>
<script src="<?php bloginfo('template_directory') ?>/_/js/functions.js"></script>


<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js"></script>
<script type="text/javascript">
var addthis_config = {
  pubid: "ra-53fc91b61d96150f"
}
addthis.layers({
  'recommended' : false   
});
</script>             
  
</body>
</html>