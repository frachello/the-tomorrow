	// // home isotope
	// $('#home_grid').isotope({
	// 	itemSelector: '.home_box',
	// 	layoutMode:'masonry',
	// 	masonry: {
	// 		columnWidth: 240
	// 	}
 //  });


var $container;
var filters = {};

$(function(){

  $container = $('#home_grid');

  // createContent();

  var $filterDisplay = $('#filter-display');

  $container.isotope({
    itemSelector: '.home_box',
    layoutMode:'masonry',
    masonry: {
      columnWidth: 240
    }
  });

  // do stuff when checkbox change
  $('#options').on( 'change', function( jQEvent ) {
    var $checkbox = $( jQEvent.target );
    manageCheckbox( $checkbox );

    var comboFilter = getComboFilter( filters );

    $container.isotope({ filter: comboFilter });

    $filterDisplay.text( comboFilter );
  });

});


var data = {
    type: 'event conversations'.split(' '),
    brands: 'brand1 brand2 brand3 brand4'.split(' '),
    productTypes: 'type1 type2 type3 type4'.split(' '),
    colors: 'red blue yellow green'.split(' '),
    sizes: 'uk-size8 uk-size9 uk-size10 uk-size11'.split(' ')
};

function createContent() {
  var brand, productType, color, size;
  var items = '';
  // dynamically create content
  for (var i=0, len1 = data.brands.length; i < len1; i++) {
    brand = data.brands[i];
    for (var j=0, len2 = data.productTypes.length; j < len2; j++) {
      productType = data.productTypes[j];
        for (var l=0, len3 = data.colors.length; l < len3; l++) {
        color = data.colors[l];
        for (var k=0, len4 = data.sizes.length; k < len4; k++) {
          size = data.sizes[k];
          var itemHtml = '<div class="home_box ' + brand + ' ' +
            productType + ' ' + color + ' ' + size + '">' +
            '<p>' + brand + '</p>' +
            '<p>' + productType + '</p>' +
            '<p>' + size + '</p>' +
            '</div>';
            items += itemHtml;
        }
      }
    }
  }

  $container.append( items );
}


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
