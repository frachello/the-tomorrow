// remap jQuery to $
(function($){})(window.jQuery);


/* trigger when page is ready */
$(document).ready ( function () { //Work as soon as the DOM is ready for parsing
	
	var id  = location.hash.substr(1); //Get the word after the hash from the url
	console.log(id);
	if (id) $('#'+id).addClass('highlight'); // add class highlight to element whose id is the word after the hash

//	$('h2').bind( "click", function() {
//		$(this).next('div').toggle();
//	});

	// toggle megamenu
	$('#megamenu a.close').bind( "click", function(){
		$('#megamenu').slideUp();
	});
	$('.nav_menu_menu a').bind( "click", function(){
		$('#megamenu').slideDown();
	});

	// apply colors to event boxes
	$('#home_grid .home_box.event .top .cat a').each(function() {
		box_top_color = $(this).closest('.top').attr("data-color");
		$(this).css('color',box_top_color);
		$(this).closest('.top').append('<div class="bg" style="background: '+box_top_color+'">');
	});

	// toggle filters
	$('.nav_menu_filter a').bind( "click",function(){
        if ($('#filter_nav').is(':visible')) {
            $('#filter_nav').slideUp('fast');
        } else {
            $('#filter_nav').slideDown('fast');
        };
	});



	$('#home_grid .home_box.event').bind({
	  mouseenter: function() {
		$(this).children('.top').toggleClass( "over" );
	  },
	  mouseleave: function() {
		$(this).children('.top').toggleClass( "over" );
	  }
	});

//	// home isotope
//	$('#home_grid').isotope({
//		itemSelector: '.home_box',
//		layoutMode:'masonry',
//		masonry: {
//			columnWidth: 240
//		}
//	});

	// calendar (filters header)
//  	$( ".calendar" ).datepicker();
  	$(function () {
        $(".calendar_date input").datepicker({
//			constrainInput: true,
			showOn: 'button',
			buttonText: 'select start date'
        });
    });

});


/* optional triggers

$(window).load(function() {
	
});

$(window).resize(function() {
	
});

*/