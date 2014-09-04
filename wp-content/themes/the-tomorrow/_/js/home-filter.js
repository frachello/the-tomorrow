//  Isotope combination filters with checkboxes -> http://codepen.io/desandro/pen/btFfG
//  Isotope - filtering with search field -> http://codepen.io/desandro/pen/wfaGu
//  date? http://stackoverflow.com/questions/14531504/combining-jquery-ui-datepicker-and-isotope-data-filters

var $container;
var filters = {};

$(function(){



  /* ----------------------------------------------------------------------------------------------------------------
  initialize isotope */
  $grid = $('#home_grid');

  var $filterDisplay = $('#filter-display');

  var qsRegex;

  var $container = $grid.isotope({
    itemSelector: '.home_box',
    layoutMode:'masonry',
    masonry: {
      columnWidth: 240
    },
    
    // ricerca libera
    filter: function() {
      return qsRegex ? $(this).text().match( qsRegex ) : true;
    },
    
    /* filter element with numbers greater than 50
    filter: function() {
      // `this` is the item element. Get text of element's .number
      var number = $(this).find('.number').text();
      // return true to show, false to hide
      return parseInt( number, 10 ) > 50;
    }  */

    filter: function() {
      var address = $(this).find('.address').text();
      return qsRegex ? address.match( qsRegex ) : true;
    }
     
  });

  // use value of search field to filter (ricerca libera)
  var $quicksearch = $('#main_search .text').keyup( function() {
    qsRegex = new RegExp( $quicksearch.val(), 'gi' );
    $container.isotope();
  } );

  // use value of search field to filter (ricerca per città)
  var $city_search = $('#city_search').keyup( debounce(function() {
    qsRegex = new RegExp( $city_search.val(), 'gi' );
    $container.isotope();
  }, 200 ) );

  /* ----------------------------------------------------------------------------------------------------------------
  do stuff when checkbox change */
  $('#options').on( 'change', function( jQEvent ) {

    var $checkbox = $( jQEvent.target );

    manageCheckbox( $checkbox );

    var comboFilter = getComboFilter( filters );

    $grid.isotope({ filter: comboFilter });

    $filterDisplay.text( comboFilter );

  });



  /* ----------------------------------------------------------------------------------------------------------------
  search 
  // quick search regex
  var qsRegex;
  
      // init Isotope
      var $container = $('.isotope').isotope({
        itemSelector: '.element-item',
        layoutMode: 'fitRows',
        filter: function() {
          return qsRegex ? $(this).text().match( qsRegex ) : true;
        }
      });

  // use value of search field to filter
  var $quicksearch = $('#quicksearch').keyup( debounce( function() {
    qsRegex = new RegExp( $quicksearch.val(), 'gi' );
    $container.isotope();
  }, 200 ) );

*/
  

});


var data = {
    type: 'event conversations'.split(' ')
//  ,
//  brands: 'brand1 brand2 brand3 brand4'.split(' '),
//  productTypes: 'type1 type2 type3 type4'.split(' '),
//  colors: 'red blue yellow green'.split(' '),
//  sizes: 'uk-size8 uk-size9 uk-size10 uk-size11'.split(' ')
};

function getComboFilter( filters ) {
  var i = 0;
  var comboFilters = [];
  var message = [];

  for ( var prop in filters ) {
    message.push( filters[ prop ].join(' ') );
    var filterGroup = filters[ prop ];
    // skip to next filter group if it doesn't have any values
    if ( !filterGroup.length ) {
      continue;
    }
    if ( i === 0 ) {
      // copy to new array
      comboFilters = filterGroup.slice(0);
    } else {
      var filterSelectors = [];
      // copy to fresh array
      var groupCombo = comboFilters.slice(0); // [ A, B ]
      // merge filter Groups
      for (var k=0, len3 = filterGroup.length; k < len3; k++) {
        for (var j=0, len2 = groupCombo.length; j < len2; j++) {
          filterSelectors.push( groupCombo[j] + filterGroup[k] ); // [ 1, 2 ]
        }

      }
      // apply filter selectors to combo filters for next group
      comboFilters = filterSelectors;
    }
    i++;
  }

  var comboFilter = comboFilters.join(', ');
  return comboFilter;
}

/* ----------------------------------------------------------------------------------------------------------------
*/
  function manageCheckbox( $checkbox ) {
  var checkbox = $checkbox[0];

  var group = $checkbox.parents('.option-set').attr('data-group');
  // create array for filter group, if not there yet
  var filterGroup = filters[ group ];
  if ( !filterGroup ) {
    filterGroup = filters[ group ] = [];
  }

  var isAll = $checkbox.hasClass('all');
  // reset filter group if the all box was checked
  if ( isAll ) {
    delete filters[ group ];
    if ( !checkbox.checked ) {
      checkbox.checked = 'checked';
    }
  }
  // index of
  var index = $.inArray( checkbox.value, filterGroup );

  if ( checkbox.checked ) {
    var selector = isAll ? 'input' : 'input.all';
    $checkbox.siblings( selector ).removeAttr('checked');


    if ( !isAll && index === -1 ) {
      // add filter to group
      filters[ group ].push( checkbox.value );
    }

  } else if ( !isAll ) {
    // remove filter from group
    filters[ group ].splice( index, 1 );
    // if unchecked the last box, check the all
    if ( !$checkbox.siblings('[checked]').length ) {
      $checkbox.siblings('input.all').attr('checked', 'checked');
    }
  }

}


/* ----------------------------------------------------------------------------------------------------------------
debounce so filtering doesn't happen every millisecond */

function debounce( fn, threshold ) {
  var timeout;
  return function debounced() {
    if ( timeout ) {
      clearTimeout( timeout );
    }
    function delayed() {
      fn();
      timeout = null;
    }
    timeout = setTimeout( delayed, threshold || 100 );
  }
}