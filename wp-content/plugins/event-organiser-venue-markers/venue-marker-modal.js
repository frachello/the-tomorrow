var eo_venue_marker = eo_venue_marker || {};

( function( $ ) {
	var media;
	
	eo_venue_marker.media = media = {
		buttonId: '#open-venue-marker-picker',
		selected: false,

		init: function() {
			$( media.buttonId ).on( 'click', this.openMediaDialog );
			media.markers = new media.model.VenueMarkerList();
			
			var selected = eo_venue.marker;
				
			for( var i=0; i< eo_venue.markers.length; i++ ){
				var marker = new media.model.VenueMarker( eo_venue.markers[i] );
					
				var url = marker.get( 'url' );
					
				if( selected == url ){
					media.markers.setCurrent( marker );
					marker.set({ selected: true });
				 }
					
				media.markers.add( marker );
			}
		},
		
		openMediaDialog: function( e ) {
			e.preventDefault();
			// An unique ID
			media.frame().open('eventorganiser-venue-marker');
		},

		closeMediaDialog: function( id ) {
			wp.media.editor.remove( id );
		},


		frame: function() {
			if ( this._frame )
				return this._frame;

			var states = [
				//new wp.media.controller.Library(),
				new media.controller.VenueMarkerList( {
					title:    'Map Markers',
					id:       'venue-marker-list-state',
					priority: 50
				} ),
				new media.controller.UploadMarker( {
					title:    'Upload',
					id:       'eo-upload-marker-state',
					priority: 50
				} )
			];

			this._frame = wp.media( {
				className: 'media-frame no-sidebar',
				states: states,
				state:    'venue-marker-list-state'
				//frame: 'post'
			} );

			this._frame.on( 'content:create:venue_marker_state', function() {
				var view = new eo_venue_marker.media.view.VenueMarkerList( {
					controller: media.frame(),
					model:      media.frame().state()
				} );

				media.frame().content.set( view );
			} );
			
			this._frame.on( 'content:create:eo_upload_marker_state', function() {
				var view = new eo_venue_marker.media.view.UploadMarker( {
					controller: media.frame(),
					model:      media.frame().state()
				} );
				media.frame().content.set( view );
			} );
			
			this._frame.on( 'ready', this.ready );
			this._frame.on( 'toolbar:create:eo-venue-marker-tooblar', this.createToolbar, this );
			return this._frame;
		},
		
		ready: function() {
			$( '.media-modal' ).addClass( 'eo-venue-marker-modal' );
		},
		
		createToolbar: function( toolbar ){
			var options = {};
			options.controller = this;
			toolbar.view = new media.view.SelectMarkerToolbar( options );
		}
	};

	_.extend( media, { view: {}, controller: {}, model: {} } );

	media.model.VenueMarker = Backbone.Model.extend({
		    defaults: {
		      url: false,
		      name: false
		    },
		    toggleSelected: function(){
		    	if( this.collection.getCurrent() == this ){
		    		this.collection.setCurrent(false);	
		    	}else{
		    		this.collection.setCurrent( this );	
		    	}
		    	
		    }
	 });

	 media.model.VenueMarkerList = Backbone.Collection.extend({
		    model: media.model.VenueMarker,

		    initialise: function(){
			    var self = this;
		 		_.bindAll(this, 'setCurrent', 'getCurrent', '_setupCurrent' ); // every function that uses 'this' as the current object should be in here
		    	self._current = null;
		    	self.on( "change:current", this.rerenderViews);
		    },
		    
		    rerenderViews: function(){
		    	
		    },
		    
		    setCurrent: function ( id ){
		        var isModel = !(_.isString(id) || _.isNumber(id));

		        var triggerChange = this._setupCurrent(isModel
		                                ? id
		                                : this.get(id));

		        if (triggerChange)
		            this.trigger("change:current", this._current);

		        return this.getCurrent();;
		    },

		    getCurrent: function (){
		        return this._current;
		    },
		    
		    _setupCurrent: function (current){
		        var old = this._current;
		        var self = this;
		        self._current = current;
		        media.selected = current;

		        if (current && old && old.id == current.id)
		            return false;

		        return true;
		    }
	 });
	
	 media.view.UploadMarker = wp.media.View.extend( {
		 template:  wp.media.template( 'eo-upload-marker' ),
		 
		 events: {
	        'submit': 'startUpload'
	      },
	      render: function() {
				this.$el.html( this.template() );
				$('#eo-venue-marker-submit').prop('disabled',true);
				return this;
	      },
	      startUpload: function( e ){
			    this.$el.find('input[type="submit"]').prop( 'disabled', true );
			    this.$el.find('.spinner').css( { 'display': 'inline-block'} );
			    return true;
		},
		uploadComplete: function( error ){
		    this.$el.find('input[type="submit"]').prop( 'disabled', false );
		    this.$el.find('.spinner').css( { 'display': 'none'} );
		    
		    if ( error ){
		    	this.$el.find('div.error').show().children().text( error );
		    }
		    
		    return true;   
		}
	 });
	 
	media.view.VenueMarkerList = wp.media.View.extend( {
		className: "venue-marker-list",
		template:  wp.media.template( 'venue-marker-list' ), // <script type="text/html" id="tmpl-venue-marker-list">
		
		initialize: function(){
			_.bindAll(this, 'render', 'appendItem', 'prependItem'); // every function that uses 'this' as the current object should be in here
			var self = this;
			
			self.collection = media.markers;
			self.collection.bind( 'add', this.prependItem ); // collection event binder
			
			self.render();
			
		     _( self.collection.models ).each( function(item){ // in case collection is not empty
		    	 	self.appendItem( item );
		      }, self);
		},

		prependItem: function( item ){
			var VenueMarker = new media.view.VenueMarker({ model: item });
			this.$el.children('ul').prepend( VenueMarker.render().el );
		},
		appendItem: function( item ){
			var VenueMarker = new media.view.VenueMarker({ model: item });
			this.$el.children('ul').append( VenueMarker.render().el );
		}
	} );
	
	media.view.VenueMarker = wp.media.View.extend( {
		tagName: 'li',
		className: 'attachment save-ready',
		template:  wp.media.template( 'venue-marker' ), // <script type="text/html" id="tmpl-venue-marker">
		events: {
			'click':  'toggleSelect'
		 },
		 initialize: function(){
		      _.bindAll(this, 'render', 'toggleSelect'); // every function that uses 'this' as the current object should be in here
		      
		      this.model.bind('change', this.render);
		 },
		    
		render: function() {
			var data = this.model.toJSON();
			
			this.$el.html( this.template( data ) );
			if( data.selected ){
				this.$el.addClass('details selected');
			}else{
				this.$el.removeClass('details selected');
			}
			return this;
		},
		
		toggleSelect: function(){;
			var old = this.model.collection.getCurrent();
			this.model.toggleSelected();
			this.model.set({ selected: true });
			
			if( old ){
				old.set({ selected: false } )
			}

		}
	} );
	
	media.controller.VenueMarkerList = wp.media.controller.State.extend( {
		defaults: {
			id:       'venue-marker-list-state',
			menu:     'default',
			content:  'venue_marker_state',
			toolbar:   'eo-venue-marker-tooblar'
		}
	} );
	
	media.controller.UploadMarker = wp.media.controller.State.extend( {
		defaults: {
			id:       'eo-upload-marker-state',
			menu:     'default',
			content:  'eo_upload_marker_state',
			toolbar:   'eo-no-toolbar'
		}
	} );
	
	media.view.SelectMarkerToolbar = wp.media.View.extend({		
		className: 'eo-venue-marker-tooblar',
		template: wp.media.template( 'venue-marker-toolbar' ),
	    events: {
	        'click input#eo-venue-marker-submit': 'useMarker',
	        'click input#eo-venue-marker-cancel': 'closeModal'
	      },
	      
	    useMarker: function(){
	    	if(  media.selected ){
	    		var url = media.selected.get('url');
	    	}else{
	    		var url = null;
	    	}

	    	eo_venue.marker = url; //Set current url in global
    		marker.setIcon( url ); //Update map
    		$('#eo-venue-marker-url').val(url); //Update input
    		//Update thumbnail:
    		if( url ){
    			$('#eo-venue-marker-thumbnail').attr( 'src', url ); 
    		}else{
    			$('#eo-venue-marker-thumbnail').attr( 'src', eo_venue.default_marker );
    		}
    		
	    	media.frame().close();
	    },
	    
	    closeModal: function(){
	    	media.frame().close();
	    }
	});

	$( document ).ready( function() {
		media.init();
	} );
} )( jQuery );