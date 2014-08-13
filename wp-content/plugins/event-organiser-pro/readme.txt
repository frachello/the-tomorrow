=== Event Organiser Pro ===
Contributors: stephenharris
Donate link: http://www.wp-event-organiser.com
Requires at least: 3.3
Tested up to: 3.9.2
Stable tag: 1.8.1

A premium add-on to WordPress event mangagement plugin Event Organiser.

== Description ==

Event Organiser Pro brings is a premium add-on to the to the Event Organiser plug-in. It allows you to

* Set up and start selling tickets for your events with minimial set up
* Customise your booking form to suit your needs: request meal preferences, t-shirt sizes or travel requirements.
* View and manage your bookings, and easily contact attendees.
* Add a 'featured image' of your venue and add additional information to your venue pages via venue custom fields.
* Add event searches to your site, via the `[event_search]` shortcode
* Query venues by custom fields, and display events by venue queries.  

To find out more please visit [http://wp-event-organiser.com/pro-features/](http://wp-event-organiser.com/pro-features/).

Event Organiesr Pro **WordPress 3.3** or higher and **Event Organiser 2** or higher

== Installation ==

Installation is standard and straight forward. 

1. Go to your 'Plug-ins' admin page, and go to 'Add New', 'Upload'
1. Select event-organiser-pro.zip and click 'install now'
1. Activty the plug-in
1. Proceed to 'Event Organsier' under your site's Settings admin tab to set up the plug-in

== Frequently Asked Questions ==


== Changelog ==


= 1.8.1 - 11th August 2014 =

* Fixes bug with button element settings not taking affect.
* Moves button settings to button form element from settings tab.

= 1.8.0 - 7th August 2014 =

**Markup changes**  

* Button, bookee name and bookee email fields now appear on the form customiser. If you have edited the booking form template or added your own styling, you may need to make some alterations. See [this post](http://wp-event-organiser.com/blog/announcements/booking-form-template-changes-1-8/) for more details
* The 'log-in form' (appears to logged-out users, if you want such users to log-in) has been moved to the top of the booking form. The form itself is now initially hidden.
* Added `.eo-booking-field-checkbox-list` class to checkbox elements
* Sublabels in fields are given the class `.eo-booking-sub-label`.
* Added `.eo-booking-field-gateway` class to gateway selection fields. Removed `<br>` tag

**API changes**  

* Added hooks `eventorganiser_delete_booking_ticket` and `eventorganiser_deleted_booking_ticket` triggered when tickets in a booking are deleted.
* Added filter `eventorganiser_booking_tickets_table` and action `eventorganiser_booking_tickets_table_column` to allow third-party plug-ins to add/remove/edit columns on the bookings admin table.
* Added filter `eventorganiser_booking_element_classes` to filter the HTML classes assigned to booking form fields.
* Allow empty event searches with event search form.
* $form->remove_element() removes nested elements too.
* Added function `eo_get_bookable_occurrences()` to get occurrences that are 'bookable' (by default, furture occurrences).

**Other changes**  
 
* Updated warning message: when editing an event, only removing a date with bookings will orphan bookings (for that date).
* Updated German translation

**Bug fixes**  
  
* Used visibility rather than display to show/hide total rows. Stops a page potentially 'jumping' when a ticket is first selected.
* Fixed bug where "sold out" message is displayed when the user has just had a booking confirmed (for the last ticket).
* Fixed bug where using `tax_query` with "orderby distance" query returns no results.
* Fixed bug where booking date is inaccurate after changing the blog's timezone
* Created `eventorganiser_pro_get_booking_complete_message()` to return "booking confirmed" message. Fixes bug where text is not translated.
* Fixed bug where class attribute not being added to number inputs
* Fixed bug with the min/max options of the number field
* Fixed bug with non-escaped values in underscore templates (form customiser).
* Escaped terms and conditions label
* Added check for asp_tags (can cause issues with the form customiser).
* Removed errant `</div>` from HTML returned by `eo_get_event_search_form()`.

= 1.7.3 =
* Use $form->flatten_elements() to capture nested form elements.

= 1.7.2 =
* Fixes bug in 1.7.1: Don't use method return value in write context (causes fatal error in php versions earlier than 5.5).

= 1.7.1 =
* Use HTTP_X_HTTP_METHOD_OVERRIDE instead of PUT/DELETE requests (not enabled by default by some hosts)
* Fixes bug with updating settings on booking form customiser
* Fixes bug with event page used for search results (if "include in search" is selected)
* Don't save hidden fields (booking form)
* Use 'visibility' rather than 'display' attribute to hide/show total row. Fixes booking table 'jumping'.


= 1.7.0 =
* Improved form customiser UI
* Booking form API (see http://wp-event-organiser.com/blog/announcements/event-organiser-pro-1-7/)
* Moved "Simple Booking Mode" option to "Ticket picker"
* Form elements can be nested inside fieldsets
* Added 'class' attribute options for form elements
* Allow users to cancel bookings with `[booking_history bookee_can_cancel="1"]`
* Added `eo_get_event_search_form()` template function (results use the events template)
* Developers: If you have added code which interacts with the booking form, please note the breaking 
changes listed here: http://wp-event-organiser.com/blog/announcements/event-organiser-pro-1-7/.
* Fixes bugs with translation .po and .mo files
* Fixes stylesheet & qtip2 being loaded when not required.
* Fixes, "You’ve made a previous booking for this event" still visible when the booking in question 
has been cancelled. 
* Fixes confirmation e-mails not sent when a booking's status changes from a custom booking status to 
confirmed.

= 1.6.4 =
* Tested up to WordPress 3.9
* Change TinyMCE button for WP3.8+ users
* Ensure jquery-dialog is enqueued on for the shortcode button

= 1.6.3 =
* Fixes booking form javascript API so that radioboxes or checkboxes rather than number input can be used for ticket selection
* Fixes shortcode dialog appearing below overlay
* Remove warning when cancelling a booking (since 1.6 booking cancellations can be reversed)  

= 1.6.2 =
* Retain gateway/date selection when form reloads after an error
* Correct casing of "PayPal"
* Fixes bug where bookee's names were lowercased in multisites
* Disables (and labels) 'sold out' dates in the date drop-down selection on in the booking form
* Fixes cancelling bookings looses bookee's name/email.
* Adds context arguments passed to WP_Query

= 1.6.1 =
* Fixes an issue with bookings for non-recurring events when selling bookings by date (introduced in 1.6.0)
* Fixes stdClass access error

= 1.6.0 =
* Added booking status API
* Cancelled bookings can now be retrieved
* CSV options added for bookings download
* Added 'fieldset' form component
* Added datepicker/drop-down option for the ticket picker
* Auto-select next available date on the booking form (when selling tickets by date)
* Minor tweak to booking form styling: removed `<br>` tags
* Improved booking form javascript API
* Fixed styling conflicts with themes using a common 'reset' in their stylesheets
* Fixed address field form component
* Included booking form details in admin notification e-mail
* Fixed "from" in bookee e-mails (bug introduced in 1.5)
* Compressed stylesheets used for the front-end (uncompressed copy included for developers)
* Fixed a bug with event/venue proxomity queries where a non-default database prefix is used


= 1.5.3 =
* Fixes activation bug introduced in 1.5.2

= 1.5.2 =
* Ensure IPN url has trailing slash
* Load register file only if Event Organiser is installed. Otherwise an undefined function error is thrown

= 1.5.1 =
* Fixed bug with (only) free bookings (see http://wp-event-organiser.com/forums/topic/error-some-fields-are-not-valid/) 
* Fixed translation (textdomain) issues with event search shortcode
* Fixed minor bug with settings page javascript
* Ensure site url has trailingslash when using it for an IPN.
* Fixed bug with German translation introduced in 1.5
* Fixes bug with duplicated tickets (see http://wp-event-organiser.com/forums/topic/tickets-being-duplicated/)

= 1.5 =
* WordPress 3.8 ready
* Added email template tags:
  - `form_submission`
  - `event_venue` (Venue name)
  - `event_venue_address`
  - `event_venue_city`
  - `event_venue_state`
  - `event_venue_postcode`
  - `event_venue_country`
  - `event_venue_url`
  - `event_url`
* Added two capabilities (see *Settings > Event Organiser > Permissions* )
  - "Manage bookings" - Ability to manage bookings for the user's event
  - "Manage other events' bookings" - Ability to manage bookings for the other users' events
* Enable gateways to be filtered with respect to booking form (and so event also).
* Theme compatability: Don't enqueue styles if 'disable CSS' is selected.
* Theme compatability: Add eo-datepicker class to all front-end datepickers.
* Filter email used for notifications: `eventorganiser_admin_email`.
* If bookees cannot create account hide log-in prompt (by default).
* Allow events to be sorted by distance in a proximity query
* Allow venues to be sorted by meta value, or randomly.
* Event query attributes can be passed to event search shortcode.
* Updated English (Canada), German, Dutch translations. Added Croatian translation.

= 1.4.2 =
* Fixes bug with 'preview email' when visual editor is not used
* Event Organiser (core) dependency stability patch 
* Fixes typos

= 1.4.1 =
* Fixes errors when EO is deactivated with Pro activated
* Adds query arguments to event search shortcode

= 1.4 =
* Refactored booking form class
* Added `eo-booking-form.php` and `eo-ticket-picker.php` templates
* Added 'Simple Booking Mode' option
* Added option for not allowing account creation on the booking form.
* Provides booking form options ( title, error/notice class, button text etc.)
* Added 'hook' field element.
* Added loading gif for booking form.
* Added `EO_Payment_Gateway` class to faciliate adding additional payment gateways
* Functions added
   - `eo_get_event_tickets_on_sale()` - Get events for a ticket which are curent available.
   - `eo_form_select_month()` - helper function displays a drop-down of months
   - `eo_form_select_year()` - helper function displays a drop-down of years 
   - `eventorganiser_register_gateway()` - registers a payment gateway
   - `eo_get_booking_form()` - displays booking form
* Added `[event_booking_form]` shortcode.
* Refactoring of booking form classes for consistancy.
* Improved JS handling of booking form.
* Works fully without javascript enabled
* Fixes bug with event search shortcode 'state' filter not remembering input

= 1.3.3 =
* Fixes 'check all' bookings not working on (WP 3.6)
* Fixes bug with event map shortcode and boolean values http://wp-event-organiser.com/forums/topic/disable-map-controls/
* Update documentation
* Display 'thank you for booking' notice above 'tickets sold out notice'
* Fixes conflict with NextGen
* Fixes issues with shortcode button (WP 3.6)
* JS refinements

= 1.3.2 =
* Fixes fatal error on booking admin page introduced in 1.3.1.
* Corrects translation errors

= 1.3.1 =
* Fixes ticket manager dialog appearing below overlay in WP 3.6
* Fixes conflict with Genesis framework
* Fixes issue where some strings are not translated
* Fixes 3.6 strict errors

= 1.3 =
* Add 'confirm bookings' to bulk actions dropdown in bookings admin screen
* Added booking search to bookings admin page
* Added event map shortcode - display events matching a query on a Google map
* API functions added 
  - `eo_get_event_capacity()` - total spaces avaialble for an event
  - `eo_get_remaining_tickets_count()` - total remaining tickets currently available
* Fixes not showing anymore than 5 booking forms [See #3](https://bitbucket.org/stephenharris/event-organiser-pro/issue/3)
* Fixes js error with ticket manager

= 1.2.1 =
* Added hooks to ticket export
* Load 'events attending' shortcode even when the user is not attending any events
* Remove hash from log-out redirect
* Fixes classes applied to front-end notices. [See this thread](http://wp-event-organiser.com/forums/topic/wrap-the-logged-in-message/).
* Added Canadian translation
* Updated French translation

= 1.2 = 
* Added venue city, state and country filters for fullCalendar
* Added 'booking history' shortcode, `[booking_history]`
* Added 'events you're attending' shortcode, `[events_attending]`
* Added 'events you're attending' widget
* Added attribute to fullCalendar shortcode, `users_events=true` to display only events user is attending
* Available user-bookings functions: `eo_user_has_bookings()`, `eo_get_events_user_is_attending()`, `eo_get_user_booking_history()`
* List user's recent bookings on the booking admin page 
* [Proximity queries](http://wp-event-organiser.com/pro-features/event-venue-queries/) are now supported
* Added `eo_remote_geocode()` function.
* Fixed bug relating to confirmation notices
* Fixes offline bookng instructions hidden when no other gateway is available
* Wrapped "You're logged in as ..." message


= 1.1.1 =
* Fixes booking/ticket export not filtering
* Allow individual parts of address (in booking form) to be accessed. See [topic](http://http://wp-event-organiser.com/forums/topic/passing-form-data-to-gateway/#post-5942).
* Adds Spanish, German, French, Dutch & Russian translations
* Fixes bugs with the address booking form element


= 1.1 =
* Adds option to make registering optional for guest bookings
* Adds support for multiple booking forms
* Include booking form data in booking download 
* Fixes gateway IPN bug on non-root installs
* Updates documentation (see [http://wp-event-organiser.com/documentation/function-reference](http://wp-event-organiser.com/documentation/function-reference))
* Adds country filter for event search shortcode
* Fixes bug with included/excluded occurrences on ticket date selection
* Inform user if they are logged in, provide log-out link
* Fixes conflict with InfiniteWP

= 1.0.3 =
* Fixes bug with adding tickets when booking series introduced in 1.0.1
* Fixes bug relating to remove venue thumbnail link
* Remove number_format from price validation for database

= 1.0.2 =
* Fixes global cap incorrectly applied
* Fixes bug with manually changing a booking's date


= 1.0.1 =

* Fixed bug in paypal checkout, cast price as float
* Added additions hooks & fitlers - documentation to follow
* Added 'WP JS Hooks' to make javascript and checkout hookable
* Adds 'resend tickets to bookee' button for confirmed bookings
* Settings for disabled gateways are now hidden
* Fixed typos


== Upgrade Notice ==
