if ( typeof EO_SCRIPT_DEBUG === 'undefined') { EO_SCRIPT_DEBUG = true;}
(function($) {

if( EO_SCRIPT_DEBUG ){
	console.log(eo);	
}
		
var formCustomiser = eo.bfc = {
	Model: {},
	View: {},
	Collection: {},
};

eo.gettext = function( msgid ){
	if( this.locale[msgid] !== undefined ){
		return this.locale[msgid];
	}
	return msgid;
};


eo.add_query_arg = function( key, value, uri ){
	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
	var separator = uri.indexOf('?') !== -1 ? "&" : "?";
	if (uri.match(re)) {
		return uri.replace(re, '$1' + key + "=" + value + '$2');
	}else {
		return uri + separator + key + "=" + value;
	}
};


//===============================================================
//Models
//===============================================================
formCustomiser.Model.EOFormController = Backbone.Model.extend();

formCustomiser.Model.EOForm = Backbone.Model.extend({

	defaults: {
		name: eo.gettext( "Booking form"),
		title: eo.gettext( "Bookings"),
		//button_text: eo.gettext( "Book" ),
		//button_classes: 'eo-booking-button',
		notice_classes: 'eo-booking-notice',
		error_classes: 'eo-booking-error',
	},
	
	url: function() {
		var url = eo.add_query_arg( 'action', 'eo-bfc-form', eo.url );
			url = eo.add_query_arg( '_nonce', eo.nonce, url );
		if ( this.isNew() ) return url;
		return url + '&id=' + this.id;
	},
	
	settings: [
	        { 'id': 'name', 'label': eo.gettext( "Name" ), 'type': 'input',		 
	        	'inline_help': {
				 title: eo.gettext( "Booking Form Name" ),
				 text: eo.gettext( "This is only used to help you identify the form on the event admin page." )
			 } },
	       	{ 'id': 'title', 'label': eo.gettext("Title" ), 'type': 'input' },
	       	{ 'id': 'error_classes', 'label': eo.gettext( "Error classes" ), 'type': 'input' },
	       	{ 'id': 'notice_classes', 'label': eo.gettext( "Notice classes" ), 'type': 'input' },
	],
	
	elements: false,
		
	initialize: function(){
		
		this.on('change:elements', this.setUpElements, this );
		this.on('change', this.setUpSettings, this );
		
		this.elements = new formCustomiser.Collection.EOFormElements();
		this._settings = new formCustomiser.Collection.EOFormSettings( this.settings );
		
		this.setUpElements();
		this.setUpSettings();		
	},
	
	setUpElements: function(){
		var self = this;
		
		this.elements.reset();
		if(  this.get('elements') ){
			_.each( this.get('elements'), function( element ){
				self.add( element );
			});
		}
		
		this.elements.sort({silent:true});
	},
	
	setUpSettings: function( ev ){
		var self = this;

		this._settings.each(function(setting){
			if( setting.get('type') == 'checkbox' ){
				setting.set( 'checked', parseInt( self.get(setting.get('id')), 10 ) );
			}else{
				setting.set( 'value', self.get(setting.get('id')) );	
			}
		});
	},
	
	add: function( element, settings ){
		settings = ( typeof settings !== 'undefined' ? settings : {} );
		
		settings.at = ( element instanceof Backbone.Model ? element.get( 'position' ) : element.position );
		
		if( !settings.at && settings.at !== '0' && settings.at !== 0 ){
			settings.at = this.elements.length;
		}
		
		this.elements.add( element, settings );
	},
	
	remove: function( element ){
		this.elements.remove( element );
	},
	
	toJSON: function(){
		var json = Backbone.Model.prototype.toJSON.call(this);
		json.elements = this.elements.toJSON();
		return json;
	}
	
});

formCustomiser.Model.EOFormElement = Backbone.Model.extend({
	
	settings: false,
	
	deletable: true,
	
	can_have_children: false,
	
	defaults: {
		label: false,
		placeholder: '',
		description: "",
		required: false,
		parent: 0,
	},
	
	url: function() {
		var url = eo.add_query_arg( 'action', 'eo-bfc-form-element', eo.url );
			url = eo.add_query_arg( '_nonce', eo.nonce, url );
		if ( this.isNew() ) return url;
		return url + '&id=' + this.id;
	},
	
	initialize: function(){
		
		//this.unset( 'position' );
		
		//Backwards compat radiobox -> radio
		if( this.get('type') == 'radiobox' ){
			this.set( 'type', 'radio' );
		}
		
		if( this.settings ){
			var self = this;
			var settingsCollection = new formCustomiser.Collection.EOFormSettings();
			
			_.each( this.settings, function( setting ){
				
				setting.live_update = 1;
				
				switch( setting.type ){
					
					case 'input':
					case 'textarea':
						setting.value = self.get(setting.id);
						break;
					case 'checkbox':
						setting.checked = self.get(setting.id);
						break;
					case 'options':
						setting.options = self.get(setting.id);
						setting.selected = self.get( 'selected' );
						break;
					case 'range':
						setting.value_min = self.get(setting.min_id);
						setting.value_max = self.get(setting.max_id);
						break;
				}

				setting = new formCustomiser.Model.EOFormSetting( setting );
				settingsCollection.add( setting );
			});
			
			this.settings = settingsCollection;
			
			this.on( 'change', this.update_settings_value );
		}	
			
		if( this.can_have_children ){
			this.elements = new formCustomiser.Collection.EOFormElements();
			this.elements.reset();
			if(  this.get('elements') ){
				_.each( this.get('elements'), function( element ){
					self.elements.add( element );
				});
			}
			
			this.elements.sort({silent:true});
		}
	
	},
	
	update_settings_value: function(){
		
		if( !this.settings ){
			return;
		}
		var self = this;
		
		this.settings.each(function( setting ){
			switch( setting.get('type') ){
				case 'input':
				case 'textarea':
					setting.set('value', self.get(setting.id) );
					break;
				case 'checkbox':
					setting.set('checked', self.get(setting.id) );
					break;
				case 'options':
					setting.set('options', self.get(setting.id) );
					setting.options = self.get(setting.id);
					setting.set('selected', self.get('selected') );
					break;
				case 'range':
					setting.set({
						'value_min': self.get( setting.get('min_id') ),
						'value_max': self.get( setting.get('max_id') )
					});
					break;
			}
		});
	},
	
	toJSON: function(){
		var json = Backbone.Model.prototype.toJSON.call(this);
		json.position = ( this.collection !== undefined ? this.collection.indexOf(this) : -1 );
		
		//Backwards compatible (checked --> selected )
		if ( typeof json.selected !== 'undefined' ) {
			json.checked = json.selected;
		}

		if ( this.can_have_children && typeof this.elements !== 'undefined' ) {
			json.elements = this.elements.toJSON();
		}

		return json;
	},
	
	is_deletable: function(){
		var deletable = this.deletable;
		if ( _.isFunction( deletable ) ){
			return deletable.apply( this );
		}else{
			return deletable;	
		}
	}
});

var formElement = formCustomiser.Model.EOFormElement;

formCustomiser.Model.EOFormElementAddress = formElement.extend({
	
	defaults: {
		label: eo.gettext( "Address" ),
		name: eo.gettext( "Address" ),
		components: ['street-address', '2nd-line', 'city', 'state', 'postcode', 'country'],
		required: false,
		description: "",
		parent: 0,
	},
	
	settings: [
	       	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	       	{ 'id': 'components', 'label': eo.gettext( "Address includes" ), 'type': 'checkbox', 'options': 
	       		{
	       			'street-address': eo.gettext( "Street address" ),
	       			'2nd-line': eo.gettext( "Second line" ),
	       			'city': eo.gettext( "City" ),
	       			'state': eo.gettext( "State" ),
	       			'postcode': eo.gettext( "Postcode" ),
	       			'country':  eo.gettext( "Country" )
	       		} 
	       	},
	       	{ 'id': 'required', 'label': eo.gettext( "Required" ), 'type': 'checkbox' },
	       	{ 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	       	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' },
	],
});

formCustomiser.Model.EOFormElementAntispam = formElement.extend({
	settings: [
	{ 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' },
	]
});

formCustomiser.Model.EOFormElementCheckbox = formElement.extend({
	
	defaults: {
		label: eo.gettext( "Label" ),
		name: eo.gettext( "Checkbox" ),
		options:['Option A', 'Option B', 'Option C'],
		required: false,
		selected: [],
		multiselect: true,
		description: "",
		parent: 0,
	},
	
	settings: [
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'options', 'label': eo.gettext( "Options" ), 'type': 'options', 'option_type': 'checkbox' },
	{ 'id': 'required', 'label': eo.gettext( "Required" ), 'type': 'checkbox' },
	{ 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
});

formCustomiser.Model.EOFormElementFieldset = formElement.extend({
	defaults: {
		name: eo.gettext("Fieldset"),
		label:eo.gettext("Label"),
		parent: 0,
	},
	can_have_children: true,
	settings: [ 
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	]
});

formCustomiser.Model.EOFormElementGateway = formElement.extend({
	defaults: {
		name: eo.gettext("Gateway picker"),
		label: eo.gettext( "Select a payment gateway" ),
		parent: 0,
	},
	settings: [ 
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }],
	deletable: false,
});

formCustomiser.Model.EOFormElementHook = formElement.extend({
	defaults: {
		name: eo.gettext("Hook"),
		'wp-action': 'some_custom_action',
		parent: 0,
	},
	settings: [ { 'id': 'wp-action', 'label': eo.gettext("Hook" ), 'type': 'input'} ]
});

formCustomiser.Model.EOFormElementHtml = formElement.extend({
	defaults: {
		name: eo.gettext("HTML"),
		parent: 0,
	},
	settings: [{ 'id': 'html', 'label': eo.gettext("HTML"), 'type': 'textarea' }]
});

formCustomiser.Model.EOFormElementName = formElement.extend({
	defaults: {
		label: eo.gettext( "Name" ),
		name: eo.gettext( "Bookee name" ),
		placeholder: '',
		required: true,
		parent: 0,
	},
	settings: [
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'lname', 'label': eo.gettext( "Include second name" ), 'type': 'checkbox' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
	deletable: function(){
		return ( this.id != 'name' );
	}
});

formCustomiser.Model.EOFormElementInput = formElement.extend({
	defaults: {
		label: eo.gettext("Label"),
		name: eo.gettext("Text field"),
		placeholder: '',
		description: "",
		required: false,
		field_type: 'text',
		parent: 0,
	},
	settings: [
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'required', 'label': eo.gettext("Required" ), 'type': 'checkbox' },
	{ 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	{ 'id': 'placeholder', 'label': eo.gettext( "Placeholder" ), 'type': 'input' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
});

formCustomiser.Model.EOFormElementAntispam = formCustomiser.Model.EOFormElementInput.extend({
	defaults:{
		name: eo.gettext("Antispam"),
		label: eo.gettext("What is x + y?"),
		placeholder: '',
		description: "",
		required: true,
		field_type: 'text',
		parent: 0,
	},
	settings: [
	{ 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
});

formCustomiser.Model.EOFormElementDate = formCustomiser.Model.EOFormElementInput.extend({
	defaults:{
		name: eo.gettext("Date"),
		label: eo.gettext("Date"),
		description: "",
		required: false,
		field_type: 'text',
		format: "Y-m-d",
		opening_date: "today",
		parent: 0,
	},
	settings: [
	   { 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	   { 'id': 'required', 'label': eo.gettext("Required" ), 'type': 'checkbox' },
	   { 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	   { 'id': 'format', 'label':  eo.gettext("Date format"), 'type': 'input' },
	   { 'id': 'opening_date', 'label':  eo.gettext("Opening date"), 'type': 'input' },
	   { 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
});

formCustomiser.Model.EOFormElementButton = formElement.extend({
	defaults: {
		name:   eo.gettext( "Button" ),
		label:  eo.gettext( "Submit" ),
		button_text: eo.gettext( "Book" ),
		'class': 'eo-booking-button',
		parent: 0,
	},
	settings: [ 
	     { 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	     { 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }],
	deletable: false,
});


formCustomiser.Model.EOFormElementEmail = formCustomiser.Model.EOFormElementInput.extend({
	defaults:{
		name: eo.gettext("E-mail"),
		label: eo.gettext("E-mail"),
		placeholder: 'john@example.com',
		description: "",
		required: false,
		field_type: 'text',
		parent: 0,
	},
	deletable: function(){
		return ( this.id != 'email' );
	}
});

formCustomiser.Model.EOFormElementNumber = formCustomiser.Model.EOFormElementInput.extend({
	defaults:{
		name: eo.gettext("Number"),
		label: eo.gettext("Number"),
		placeholder: '',
		description: "",
		required: false,
		min: false,
		max: false,
		field_type: 'number',
		parent: 0,
	},
	settings: [
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'required', 'label': eo.gettext( "Required" ), 'type': 'checkbox' },
	{ 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	{ 'id': 'placeholder', 'label': eo.gettext( "Placeholder" ), 'type': 'input' },
	{ 'id': 'range', 'label': eo.gettext("Range"), 'min_id': 'min', 'label_min': eo.gettext("Min"), 'max_id': 'max', 'label_max': eo.gettext("Max"), 'type': 'range' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
});

formCustomiser.Model.EOFormElementPhone = formCustomiser.Model.EOFormElementInput.extend({
	defaults:{
		name: eo.gettext("Phone"),
		label: eo.gettext("Phone"),
		placeholder: '',
		description: "",
		required: false,
		field_type: 'text',
		parent: 0,
	},
});

formCustomiser.Model.EOFormElementUrl = formCustomiser.Model.EOFormElementInput.extend({
	defaults:{
		label: eo.gettext("Website"),
		name: eo.gettext("Website"),
		placeholder: 'http://',
		description: "",
		required: false,
		field_type: 'text',
		parent: 0,
	},
});

formCustomiser.Model.EOFormElementMultiselect = formElement.extend({

	defaults: function(){ return {
		label: eo.gettext( "Label" ),
		name: eo.gettext("Multiselect"),
		options:['Option A', 'Option B', 'Option C'],
		required: false,
		selected: [],
		multiselect: true,
		description: "",
		parent: 0,
	}; },
	
	settings: [
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'options', 'label': eo.gettext( "Options" ), 'type': 'options', 'option_type': 'checkbox' },
	{ 'id': 'required', 'label': eo.gettext( "Required" ), 'type': 'checkbox' },
	{ 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
});

formCustomiser.Model.EOFormElementRadio = formElement.extend({

	defaults: {
		label: eo.gettext( "Label" ),
		name: eo.gettext("Radio"),
		options: ['Option A', 'Option B', 'Option C'],
		required: false,
		selected: false,
		description: "",
		parent: 0,
	},
	
	settings: [
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'options', 'label': eo.gettext( "Options" ), 'type': 'options', 'option_type': 'radio' },
	{ 'id': 'required', 'label': eo.gettext( "Required" ), 'type': 'checkbox' },
	{ 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
});

formCustomiser.Model.EOFormElementSection = formElement.extend({
	defaults: {
		name: eo.gettext("Section"),
		label: eo.gettext("Section"),
		parent: 0,
	},
	settings: [ 
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }]
});

formCustomiser.Model.EOFormElementSelect = formElement.extend({
	
	defaults:{
		label: eo.gettext( "Label" ),
		name: eo.gettext("Select"),
		options:['Option A', 'Option B', 'Option C'],
		required: false,
		multiselect: false,
		selected: [],
		description: "",
		parent: 0,
	},
	
	settings: [
	{ 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
	{ 'id': 'options', 'label': eo.gettext( "Options" ), 'type': 'options', 'option_type': 'radio' },
	{ 'id': 'required', 'label': eo.gettext( "Required" ), 'type': 'checkbox' },
	{ 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
	{ 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
});

formCustomiser.Model.EOFormElementTermsConditions = formElement.extend({
	defaults:{
		name: eo.gettext("Terms & Conditions"),
		label: eo.gettext("Terms & Conditions"),
		terms: eo.gettext("Your terms & conditions"),
		terms_accepted_label: eo.gettext("I have read and agree to the terms and conditions detailed above."),
		required: true,
		parent: 0,
	},
	settings: [ 			     
     { 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
     { 'id': 'required', 'label': eo.gettext( "Required" ), 'type': 'checkbox' },
     { 'id': 'terms', 'label': eo.gettext('Terms'), 'type': 'textarea' },
	 { 'id': 'terms_accepted_label', 'label': eo.gettext("Checkbox label"), 'type': 'input' },
	 { 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	 ]
});

formCustomiser.Model.EOFormElementTextarea = formElement.extend({
	defaults: {
		label: eo.gettext( "Label" ),
		name: eo.gettext( "Textarea" ),
		placeholder: '',
		description: "",
		required: false,
		parent: 0,
	},
	settings: [
     { 'id': 'label', 'label': eo.gettext( "Label" ), 'type': 'input' },
     { 'id': 'required', 'label': eo.gettext( "Required" ), 'type': 'checkbox' },
     { 'id': 'description', 'label': eo.gettext( "Description" ), 'type': 'textarea' },
     { 'id': 'class', 'label': eo.gettext( "CSS Class" ), 'type': 'input' }
	],
});

formCustomiser.Model.EOFormElementTicketpicker = formElement.extend({
	settings: [
	 { 'id': 'use_select', 'label': eo.gettext("Use drop-down list"), 'type': 'checkbox', 
		 'inline_help': {
			 title: eo.gettext("Date selection"),
			 text:  eo.gettext("If checked a drop-down select menu is used for selecting a date, rather than a datepicker.")
		 }
	 },
	 { 'id': 'simple_mode', 'label': eo.gettext( "Simple Booking Mode" ), 'type': 'checkbox', 
		 'inline_help': {
			 title: eo.gettext( "Simple Booking Mode" ),
			 text: eo.gettext( "Simple Booking Mode only comes into effect if:<ul><li>This option is selected.</li><li>The event has only one ticket currently on sale.</li><li>You are booking by series, or the event is non-recurring (no date needs to be selected).</li></ul>Under those conditions the date and ticket selection is hidden from the booking form, and the booking consist of only one ticket." )
		 }
	 },
    ],
	defaults: {
		use_select: 0,
		name: eo.gettext( "Ticket picker" ),
		parent: 0,
	},
	deletable: false,
});

formCustomiser.Model.EOFormSetting = Backbone.Model.extend({
	defaults:{
		inline_help: false,
	},
	toJSON: function() {
		  var json = Backbone.Model.prototype.toJSON.apply(this, arguments);
		  json.cid = this.cid;
		  return json;
	},
	
	//for options setting
	removeOptionAt: function( index ){
		var options = this.get('options');
		var selected = _.clone( this.get('selected') );
		
		if( 'radio' == this.get('option_type') ){
			if( selected ){
				if( selected == index ){
					 selected = false;
				}else if( selected > index ){
					selected--;
				}
			}
		}else{
			selected = _.filter( _.clone( selected ), function( n ){ return n !== index; });
			selected = _.map(selected, function( n ){ return ( n > index ? n-1 : n ); });
		}		
		
		if( options ){
			options = _.reject(options, function( option, _index){ return _index == index; } );	
		}
		this.set( {
			'options': options,
			'selected': selected
		});
	},
	
	insertOptionAt: function( option, index ){
		var options = _.clone( this.get('options') );
		var selected = _.clone( this.get('selected') );
		
		if( 'radio' == this.get('option_type') ){
			if( selected ){
				selected = ( selected >= index ? selected + 1 : selected );	
			}
		}else{
			selected = _.map(selected, function( n ){ return ( n>= index ? n+1 : n ); });	
		}
		
		if( options ){
			options.splice( index, 0, option );
		}else{
			options = [option];
		} 
		this.set( {
			'options': options,
			'selected': selected
		});
	},

	setSelected: function( selected ){
		if( 'radio' == this.get('option_type') ){
			selected = ( selected ? _.first( selected ) : false );
		}
		this.set( 'selected', selected );	
	},
	
	setOption: function( option, index ){
		var options = _.clone( this.get('options') );
		options[index] = option;
		this.set( 'options', options );
	},
	
	is_selected: function( index ){
		var selected = this.get('selected');
		if( 'radio' == this.get('option_type') ){
			return selected === index;
		}else{
			return _.indexOf( selected, index ) > -1;
		}
		
	}
	
});


//===============================================================
//Collections
//===============================================================
formCustomiser.Collection.EOFormElements = Backbone.Collection.extend({
	
	model: function( attrs, options) {
		
		var className = 'EOFormElement'+('_'+attrs.type).replace(/[_|-]([a-z])/g, function (g) { return g[1].toUpperCase(); });
		var modelClass = formCustomiser.Model[className];
			
		if( typeof modelClass == 'undefined' ){
			return new formCustomiser.Model.EOFormElement( attrs, options );
		}else{
			return new modelClass( attrs, options );
		}
	},
	
	comparator: function( model ){
		return model.get('position');
	}

});

formCustomiser.Collection.EOFormSettings = Backbone.Collection.extend({
	model: formCustomiser.Model.EOFormSetting,
});


//===============================================================
//Views
//===============================================================
formCustomiser.View.EOFormControllerView = Backbone.View.extend({
	el: '#poststuff',
	
	template: _.template( $( '#tmpl-' + 'eo-form-controller' ).html( ) ),
	
    initialize: function() {
		_.bindAll(this, 'render' );
		
		this.formView = false;
		
		this.model.get('form').on('change:id', this.render, this);
		this.model.get('form').on('change:name', this.renderForms, this);
		this.model.on('change:forms', this.renderForms, this);
	},
	
	events: {
		'click #eo-bfc-form-tabs li a': 'tabClicked',
		
		'click #side-sortables .handlediv': 'fieldBinToggleClicked',
		'click .eventorganiser-field-bin li': 'addField',
		
	//	'click #eventorganiser-save-form': 'saveForm',
		'click .eo-bfc-delete-form': 'deleteFormClicked',
		'click .eo-bfc-add-form-btn': 'addFormClicked',
		'click .eo-bfc-edit-form-btn': 'editFormClicked',
		'keyup .eo-bfc-setting-name': 'editFormName',
	},
	
	render: function(){
		
		//Remove automatically added (by EO) "submit" button.
		//TODO rework this so this isn't necessary
		$('.wrap .submit').remove();
		
		$( this.el ).html( this.template( this.model.toJSON() ) );
		this.renderForms();
		
		$( '#side-sortables', this.el ).html('');
		_.each( this.model.get('metaboxes'), function( metabox ){
			$('#side-sortables').append( _.template( $( '#tmpl-eo-form-element-bin' ).html(), metabox ) );			
		} );
		$('#side-sortables .postbox:gt(0)', this.el ).addClass('closed');
		
		if( this.formView ){
			this.formView.close();
		}
		this.formView = new formCustomiser.View.EOFormView({ model: this.model.get('form') });		
		this.formView.render();
		
		$('.form-toolbar-option input', this.el ).val( this.model.get('form').get('name') );
		
		$( window ).on( 'scroll click.postboxes', function( event ) {
			var y =  parseInt( $(this).scrollTop(), 10 );
			var footer_top = parseInt( $( '#wpfooter' ).position().top, 10 );
			var fixed_offset = parseInt( $("#eo-bfc-form").offset().top, 10 );
			var metabox_height = parseInt( $('#eventorganiser-form-fixed-mb').height(), 10 );
			var bar = y + fixed_offset + metabox_height;

	        if ( footer_top - bar >  -20 ) {
				 $('#eventorganiser-form-fixed-mb').css({
					 position: 'fixed',
					 top: $("#eo-bfc-form").offset().top
				 });
	        } else {
	        	$('#eventorganiser-form-fixed-mb').css({
	        		position: 'absolute',
	        		top: ( footer_top - metabox_height  ) + 'px'
	        	});
	        }
		}).trigger( 'click.postboxes' );
		
		return this;
	},
	
	rerender: function(){
		this.render();
	},
	
	renderForms: function(){		
		$( '.eo-bfc-edit-form', this.el ).html('');
		var self = this;
		_.each( this.model.get('forms'), function( form ) {
			var selected = ( form.id == self.model.get('form').get('id') ? 'selected="selected"' : '' ); 
			$( '.eo-bfc-edit-form', this.el ).append( '<option value="' + form.id + '" ' + selected + '> ' + form.name + ' (' + form.id + ')</option>' );
		});
	},
	
	tabClicked: function( ev ){
		ev.preventDefault();
		$('#eo-bfc-form-tabs li').removeClass('active');
		$(this.el).find( '.eo-booking-form-tabbed-area').hide();
		$(ev.currentTarget).parent( 'li' ).addClass('active');
		$(this.el).find( '#' + $(ev.currentTarget).attr('aria-controls') ).show();
	},
	
	fieldBinToggleClicked: function( ev ){
		$( ev.target, this.el ).parent('.postbox').toggleClass('closed');
	},
	
	addField: function( ev ){

		var self = this, type = $(ev.currentTarget).data('type'), element;

		var className = 'EOFormElement'+('_'+type).replace(/[_|-]([a-z])/g, function (g) { return g[1].toUpperCase(); });
		
		if( typeof formCustomiser.Model[className] != 'undefined' ){
			element = new formCustomiser.Model[className]({type:type});	
		}else{
			element = new formCustomiser.Model.EOFormElement({type:type});
		}
		
		
		
		element.save( null, {
			success: function( model, response ){
				model.set( response );
				self.model.get('form').elements.add( model, { scroll: true } );
			},
			emulateHTTP: true,
		});

	},
	
	deleteFormClicked: function( ev ){
		ev.preventDefault();
		
		if( !confirm( 'Are you sure you want to delete the form "'+this.model.get('form').get('name')+'"?\n\n This action cannot be undone.' ) ){
			return;
		}
		
		var self = this;
		
		$( '#eo-bfc-form', this.el ).append( $('<div class="eo-bfc-ajax-overlay">') );
		
		//Delete form on server
		this.model.get('form').destroy({
			emulateHTTP: true,
			success:function( model, response ){

				var form_id = model.get('id');
				var forms = _.filter( self.model.get('forms'), function( form ){ return form.id != form_id; });
				self.model.set( 'forms', forms );

				var next_form = _.first( forms );
				if( next_form !== undefined ){
					
					var form = new formCustomiser.Model.EOForm( { id: next_form.id } );
				
					form.fetch({
						success:function( model, response ){
							model.set( response );
							self.model.set('form',model);
							self.rerender();
						}
					});
				}else{
					//No form available
					$( '#eo-bfc-form', this.el ).html( '<h3>'+ eo.gettext( 'No form found... <a href="#" title="Create a new form" class="eo-bfc-add-form-btn">create new</a>' ) + '</h3>' );			}
			},
			error: function( model, response ){
				if( EO_SCRIPT_DEBUG ){
					console.log('error:');
					console.log(response);
				}
			}
		});
	},
	
	editFormClicked: function( ev ){
		ev.preventDefault();
		var form_id = $( '#eo-bfc-edit-form', this.el ).val();
		var form = new formCustomiser.Model.EOForm( { id: form_id } );
		
		$( '#eo-bfc-form', this.el ).append( $('<div class="eo-bfc-ajax-overlay">') );
		
		var self = this; 
		form.fetch({
			success:function( model, response ){
				model.set( response );
				self.model.set('form',model);
				self.rerender();
			}
		});
	},
	
	addFormClicked: function( ev ){
		ev.preventDefault();
		var self = this; 
		var newForm = new formCustomiser.Model.EOForm({});
		
		$( '#eo-bfc-form', this.el ).append( $('<div class="eo-bfc-ajax-overlay">') );
		
		newForm.save( null, {
			wait: true,
			success: function( model, response ){
				model.set( response );
				var forms = self.model.get('forms');
				forms.push( model.toJSON() );
				self.model.set( 'forms', forms );
				self.model.set('form',newForm);
				self.rerender();
			},
			error: function( model, response ){
				//error
			},
			emulateHTTP: true,
		});
	},
	
	editFormName: function( ev ){
		var self = this;
		
		forms = this.model.get('forms');
		_.map( forms, function( form ){
			if( form.id == self.model.get('form').get('id') ){
				form.name = $(ev.target).val();
			}
		});
		
		this.model.set('forms', forms );	
		this.model.get('form').set('name', $(ev.target).val() );
	}
});




formCustomiser.View.EOFormView = Backbone.View.extend({
	el: '#eo-bfc-form',
	
	template: _.template( $( '#tmpl-' + 'eo-form' ).html( ) ),
	
    events: {
        'update-sort': 'updatePositions',
        'click .eo-bfc-save-form-btn': 'saveForm',
    },
    
    initialize: function() {
		_.bindAll(this, 'render', 'updatePositions', 'addElement' );
		this.model.elements.on('add', this.addElement, this);
		//this.listenTo( this.model.elements, 'add', this.addElement );
		
        this.model.bind('request', this.ajaxStart, this);
        this.model.bind('sync', this.ajaxComplete, this);
	},	
	
	ajaxStart: function(){
		$(this.el).find('.eo-bfc-save-form-btn').prop('disabled', true);
		$(this.el).find('.spinner').show();
	},
	ajaxComplete: function(){
		$(this.el).find('.eo-bfc-save-form-btn').prop('disabled', false);
		$(this.el).find('.spinner').hide();
	},

	render: function(){
		
		if( EO_SCRIPT_DEBUG ){
			console.log('render form');	
		}
		
		$(this.el).html( this.template( this.model.toJSON() ) );
		
		var self = this;
		this.model.elements.each(function(element) {
			self.addElement( element );
		});
		
		

		$( 'ul.eo-bfc-element-root-list' ).nestedSortable({
				listType: 'ul',
				disableNesting: 'eo-bfc-element-no-children',
				errorClass: 'eo-bfc-placeholder-error',
				handle: 'h3',
				helper:	'clone',
				items: '.eo-bfc-element',
				placeholder: 'eo-bfc-placeholder',
				tolerance: 'pointer',
				toleranceElement: '.eo-bfc-form-element',
				maxLevels: 3,
				update: function( ev, ui ){
					ui.item.trigger('drop', ui.item.index());
				},
			});

		
		this.model._settings.each(function(setting){
			settingView = new formCustomiser.View.EOFormSettingView({
				model: setting
			});
			$( '#eventorganiser-form-settings', this.el ).append( settingView.render().el );
			setting.on('change', self.updateSetting, self );
			//self.listenTo( setting, 'change', self.updateSetting );
		});
	
		return this;
	},
	
	addElement: function( element, t, settings ){

		var views = formCustomiser.View;
		var elementView;		
		switch( element.get('type') ){
		
			case 'input':			
			case 'url':
			case 'email':
			case 'date':
			case 'phone':
			case 'number':
			case 'antispam':
			case 'discount-code':
				elementView = new views.EOFormElementInputView({model: element});
				break;
			
			case 'multiselect':
				elementView = new views.EOFormElementSelectView({model: element}); 
				break;
						
			default:
				elementView = new views.EOFormElementView({model: element});
		}
		
		var elementEL = elementView.render().el;
		$('ul#eventorganiser-form-fields',self.el).append( elementEL );
		if( settings && settings.scroll ){
			$("html, body").animate({ scrollTop: $(elementEL).offset().top-50 }, 400);	
		}
	},
	
	updatePositions: function( event, movedModel, to ){
	
		//Added to form
		
		var from = movedModel.collection.indexOf( movedModel );
		var fromParent = movedModel.get('parent');
		
		if( EO_SCRIPT_DEBUG ){
			console.log(
				movedModel.id + " moved "
				+ " from " + from + " under " + fromParent 
				+ " to " + to + " under " + 0 );
		}
		
		movedModel.set( 'parent', 0, {silent:true} );
		movedModel.collection.remove( movedModel, {silent:true} );
		this.model.elements.add( movedModel, {at:to, silent: true } );
	},
	
	updateSetting: function( setting ){
		var value =  ( setting.get('type') == 'checkbox' ) ? parseInt( setting.get('selected'), 10 ) : setting.get('value');
		this.model.set( setting.get('id'), value );
	},
	
	saveForm: function( ev ){
		ev.preventDefault();
		if( EO_SCRIPT_DEBUG ){
			console.log('saving form '+this.model.id);
			console.log(this.model.toJSON());
		}
		this.model.save( null, {silent:true, emulateHTTP: true} );
	},
	
	close: function(){
		this.remove();
		this.unbind();
		this.model.unbind();
	},
	
});


formCustomiser.View.EOFormElementView = Backbone.View.extend({
	
	tagName: 'li',
	
	className: 'eo-bfc-element',
	
	template: _.template( $( '#tmpl-eo-form-element' ).html( ) ),
	
	exampleTemplate: false,
	
	events: { 
		'click .inside, .handlediv': 'toggleSettingsVisibility',
		'drop' : 'dropped',
		'click .inside .eo-bfc-settings': 'stopPropagation',
		'click a.submitdelete': 'deleteElement',
		
		'update-sort ul': 'updatePositions',
	},
	
	initialize: function() {
		
		_.bindAll(this, 'render', 'toggleSettingsVisibility', 'dropped', 'rerender', 'updatePositions',  'addElement' );
		
		if( !this.exampleTemplate ){
			var type = this.model.get('type');
			this.exampleTemplate = $( '#tmpl-' + 'eo-form-element-example-' + type ).html();			
		}
		
		if( !this.model.can_have_children ){
			this.$el.addClass('eo-bfc-element-no-children');
		}else{
			this.model.elements.on( 'add', this.addElement, this );
			//this.listenTo( this.model.elements, 'add', this.addElement );
		}
		this.$el.addClass('eo-bfc-element-'+this.model.get('type'));	
		
		this.model.on( 'change', this.rerender, this );
		//this.listenTo(this.model, "change", this.rerender);
	},
		
	render: function(){	
		if( EO_SCRIPT_DEBUG ){
			console.log('render element ' + this.model.id );
		}
		var json = _.extend( this.model.toJSON(), {can_have_children: this.model.can_have_children } );
		this.$el.append( this.template( json ) );
		this.renderExample();
		this.renderSettings();
		
		if( this.model.can_have_children ) {
			var self = this;
			this.model.elements.each(function(element) {
				self.addElement( element );
			});
		}
		
		if( this.model.is_deletable() ){
			$( '#eo-bfc-element-'+this.model.id+' .eo-bfc-settings', this.el ).append( '<span class="submitbox"><a href="#" class="submitdelete" title="Delete this element">Delete Element</a></span>' );
		}
		
		return this;
	},

	rerender: function(){
		if( EO_SCRIPT_DEBUG ){
			console.log('rerender element');
		}
		this.renderExample();
		return this;
	},	
	
	deleteElement: function( ev ){
		
		ev.preventDefault();
		
		this.model.collection.remove( this.model );
		
		//COMPLETELY UNBIND THE VIEW
	    this.undelegateEvents();

	    this.$el.removeData().unbind(); 

	    //Remove view from DOM
	    this.remove();  
	    Backbone.View.prototype.remove.call(this);
	},
	
	renderExample: function(){
		var json = this.model.toJSON();
		//Backwards compatible
		if ( typeof json['wp-action'] !== 'undefined' ) {
			json.action = json['wp-action'];
		}
		if( this.exampleTemplate ){
			$( '#eo-bfc-element-'+this.model.id+' .eo-bfc-example', this.el ).html( _.template( this.exampleTemplate,  json ) );	
		}
		
	},
	
	renderSettings: function(){
		
		if( this.model.settings ){
			
			var elementView = this;
			$( '.eo-bfc-settings', this.el ).html( '<table class="form-table"></table>' );
			
			this.model.settings.each( function( setting ) {
				
				var settingView;
				
				switch( setting.get('type') ){
					case 'input':
					case 'textarea':
					case 'checkbox':
					case 'range':
						settingView = new formCustomiser.View.EOFormSettingView({ model: setting });
						break;
					case 'options':
						settingView = new formCustomiser.View.EOFormElementSettingOptionsView({ model: setting });
						break;
				}
				
				setting.on( 'change', elementView.updateSettings, elementView );
				//elementView.listenTo( setting, 'change', elementView.updateSettings );
				
				$('.eo-bfc-settings table', elementView.el).append( settingView.render().el );
			});
	

		}
	},
	
	updatePositions: function( ev, movedModel, to ){
		
		ev.stopPropagation();
		
		var from = movedModel.collection.indexOf( movedModel );
		var fromParent = movedModel.get('parent');
		
		if( EO_SCRIPT_DEBUG ){
			console.log(
				movedModel.id + " moved "
				+ " from " + from + " under " + fromParent 
				+ " to " + to + " under " + this.model.id );
		}
		
		movedModel.set( 'parent', this.model.id, {silent:true} );
		movedModel.collection.remove( movedModel, {silent:true} );
		this.model.elements.add( movedModel, {at:to, silent: true } );
	},
	
	addElement: function( element, t, settings ){

		var views = formCustomiser.View;
		var elementView;		
		switch( element.get('type') ){
		
			case 'input':			
			case 'url':
			case 'email':
			case 'date':
			case 'phone':
			case 'number':
			case 'antispam':
			case 'discount-code':
				elementView = new views.EOFormElementInputView({model: element});
				break;
			
			case 'multiselect':
				elementView = new views.EOFormElementSelectView({model: element}); 
				break;
						
			default:
				elementView = new views.EOFormElementView({model: element});
		}
		
		var elementEL = elementView.render().el;
		$('>ul',this.el).append( elementEL );
		if( settings && settings.scroll ){
			$("html, body").animate({ scrollTop: $(elementEL).offset().top-50 }, 400);	
		}
	},
	
	toggleSettingsVisibility: function( ev ){
		ev.stopPropagation();
		if( $( '#eo-bfc-element-'+this.model.id + ' .eo-bfc-settings', this.el ).toggle().is( ":visible" ) ){
			$( '#eo-bfc-element-'+this.model.id + ' .eo-bfc-settings-toggle-arrow', this.el ).html( '&#x25B2;' );
		}else{
			$( '#eo-bfc-element-'+this.model.id + ' .eo-bfc-settings-toggle-arrow', this.el ).html( '&#x25BC;' );
		}
		$("html, body").animate({ scrollTop: $(this.el).offset().top-50 }, 400);
	},
	
	stopPropagation: function( e ){
		e.stopPropagation();
	},
		
	dropped: function( ev, index ) {
		ev.stopPropagation();
		this.$el.trigger('update-sort', [this.model, index]);
	}, 
	
	updateSettings: function( setting ){
		if( EO_SCRIPT_DEBUG ){
			console.log( 'update setting') ;
		}
		switch( setting.get('type') ){
			case 'input':
			case 'textarea':
				this.model.set( setting.id, setting.get('value'));
				break;
			case 'checkbox':
				this.model.set( setting.id, setting.get('checked'));
				break;
			case 'range':
				the_min_id = setting.get('min_id');
				the_max_id = setting.get('max_id');
				
				var values = {}; 
				values[the_min_id] = setting.get('value_min');
				values[the_max_id] = setting.get('value_max');
				
				this.model.set( values );
				
				break;
			case 'options':
				this.model.set({ 
					'options': setting.get('options'),
					'selected': setting.get('selected') 
				});
				break;
		}
	},
});

var EOFormElementView = formCustomiser.View.EOFormElementView;

formCustomiser.View.EOFormElementInputView = EOFormElementView.extend({
	exampleTemplate: $( '#tmpl-' + 'eo-form-element-example-input' ).html()
});

formCustomiser.View.EOFormElementSelectView = EOFormElementView.extend({
	exampleTemplate: $( '#tmpl-' + 'eo-form-element-example-select' ).html()
});

formCustomiser.View.EOFormSettingView = Backbone.View.extend({

	tagName: 'tr',
	
	events: {
		'keyup': 'triggerSettingChanged',
		'change': 'triggerSettingChanged',
		'click .eo-bfc-inline-help': 'preventDefault'
	},
	
	initialize: function(){
		this.template = _.template( $( '#tmpl-eo-form-element-setting-'+this.model.get('type') ).html( ) );
		
		if( !this.model.get('live_update') ){
			delete this.events.keyup;
		}else{
			this.events.keyup = "triggerSettingChanged";
		}
	},
	
	render: function(){	
		this.$el.empty().append( this.template( this.model.toJSON() ) );
		
		if( this.model.get('inline_help') ){
			$('.eo-bfc-inline-help', this.$el ).qtip({
				content: {
					title: this.model.get('inline_help').title,
					text: this.model.get('inline_help').text
				},
				show: {
					solo: true 
				},
				//hide: 'unfocus',
				style: {
					classes: 'qtip-wiki qtip-light qtip-shadow'
				},
				position : {
					viewport: $(window)
				}
			});
		}
			
		return this;
	},

	triggerSettingChanged: function( ev ){
		var code = ev.keyCode || ev.which;
		if ( code && code == '9' ) {
		    return;
		}
		
		switch( this.model.get('type') ){
			case 'input':
				this.model.set( 'value', this.$el.find('input').val() );	
				break;
			case 'textarea':
				this.model.set( 'value', this.$el.find('textarea').val() );	
				break;
			case 'range':
				this.model.set({
					'value_min': this.$el.find('input[data-type="min"]').val(), 
					'value_max': this.$el.find('input[data-type="max"]').val() 
				});
				break;
			case 'checkbox':
				if( this.model.get('options') ){
					var checked =  this.$el.find('input[type="checkbox"]:checked').map(function(){ return $(this).val(); }).get();
					this.model.set( 'checked',  checked );
				}else{
					this.model.set( 'checked',  this.$el.find('input[type="checkbox"]').prop( 'checked' ) ? 1 : 0 );
				}
				break;
		}
	},
	
	preventDefault: function( ev ){
		ev.preventDefault();
	}
	
});

formCustomiser.View.EOFormElementSettingOptionsView = formCustomiser.View.EOFormSettingView.extend({
	
	initialize: function(){
		_.bindAll(this, 'render' );
		this.template = _.template( $( '#tmpl-eo-form-element-setting-'+this.model.get('type') ).html( ) );
	},
	
    events: {
		'keyup li input': 'optionChanged',
		'change input[type=radio]': 'optionSelected',
		'change input[type=checkbox]': 'optionSelected',
		'click .eo-bfc-option-add': 'addOptionClicked',
		'click .eo-bfc-option-remove': 'removeOptionClicked',
    },
	
	render: function(){	
		var self = this;
		this.$el.empty();
		this.$el.append( this.template( this.model.toJSON() ) );
		
		//Hide remove button when we're down to the last option
		show_remove_option = ( this.model.get('options').length > 1 );
		
		_.each( this.model.get('options'), function( option, index ) {
			var data = {
				option: option,
				index: index,
				selected: self.model.is_selected( index ),
				option_type: self.model.get('option_type'),
				group: self.model.cid,
				show_remove_button: show_remove_option 
			};
			$( 'ul', self.el ).append( _.template( $( '#tmpl-eo-form-element-setting-options-option' ).html(), data ) );		
		});
		
		return this;
	},
	
	addOptionClicked: function( ev ){
		var index = $(ev.currentTarget).parents('li').index();
		this.model.insertOptionAt( "New option", index + 1 );
		this.render();
	},
	
	removeOptionClicked: function( ev ){
		var index = $(ev.currentTarget).parents('li').index();
		this.model.removeOptionAt( index );
		this.render();
	},
	
	optionSelected: function(){
		var selected;
		if( this.model.get('option_type') == 'checkbox' ){
			selected = $('input:checkbox:checked', this.$el ).map(function(){ return $(this).parents('li').index(); }).get();
		}else{
			selected = $('input:radio:checked', this.$el ).map(function( index ){ return $(this).parents('li').index(); }).get();
		}
		
		this.model.setSelected( selected );
	},
	
	optionChanged: function( ev ){
		var code = ev.keyCode || ev.which;
		if ( code && code == '9' ) {
		    return;
		}
		
		var index = $(ev.currentTarget).parents('li').index();
		var option = $(ev.currentTarget).val();
		this.model.setOption( option, index );
	},
});


//======================================
// Initialize
//======================================
$(document).ready(function(){
	
	var form = new formCustomiser.Model.EOForm( eo.form );
	
	var formController = new formCustomiser.Model.EOFormController({ form: form, forms: eo.forms, metaboxes: eo.element_types });
	
	var formControllerView = new formCustomiser.View.EOFormControllerView({ model: formController });
	formControllerView.render();
});
})(jQuery);