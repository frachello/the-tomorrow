<?php
// this file contains the contents of the popup window
?>
<div id="eo-shortcode-button-dialog" style="display:none;">
	<form action="/" method="get" accept-charset="utf-8">
		<div id="eo-shortcode-tabs" style="padding:0px;border:none;">
    		<ul>
				<li><a href="#eo-shortcode-tab-0"><?php esc_html_e( 'Event List', 'eventorganiserp' );?></a></li>
		      	<li><a href="#eo-shortcode-tab-1"><?php esc_html_e( 'Calendar', 'eventorganiserp' );?></a></li>
		      	<li><a href="#eo-shortcode-tab-2"><?php esc_html_e( 'Venue Map', 'eventorganiserp' );?></a></li>
		      	<li><a href="#eo-shortcode-tab-3"><?php esc_html_e( 'Event Search', 'eventorganiserp' );?></a></li>
		    </ul>

			<table id="eo-shortcode-tab-0" class="form-table" style="padding:0px;"><tbody>
			<tr valign="top">
				<td colspan="2"> <?php esc_html_e( 'Display a list of events', 'eventorganiserp' );?> </td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Events starting after', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="" placeholder="yyyy-mm-dd" class="event-start-after" />
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Events starting before', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="" placeholder="yyyy-mm-dd" class="event-start-before" />
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Events starting after', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="" placeholder="yyyy-mm-dd" class="event-end-after" />

				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Events ending before', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="" placeholder="yyyy-mm-dd" class="event-end-before" />
				<p class="help"> <?php esc_html_e( 'You can use relative dates, e.g. \'now\', \'next Tuesday\'', 'eventorganiserp' );?> </p>
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Events in categories', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="" class="event-shortcode-cat-selection" />
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Events at venues', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="" class="event-shortcode-venue-selection"/>
				</td>
			</tr>

			</tbody></table>

			<table id="eo-shortcode-tab-1" class="form-table" style="padding:0px;"><tbody>
			<tr valign="top">
				<td colspan="2"> <?php esc_html_e( 'Display a calendar of events, similar to the admin calendar.', 'eventorganiserp' );?> </td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'View', 'eventorganiserp' );?> </td>
				<td>
				<label> <input type="radio" name="button-url" checked="checked" value="month" class="event-shortcode-calendar-view" /> <?php _e('Month','eventorganiserp');?> </label>
				<label> <input type="radio" name="button-url" value="agendaWeek" class="event-shortcode-calendar-view" /> <?php _e('Week','eventorganiserp');?> </label>
				<label> <input type="radio" name="button-url" value="agendaDay" class="event-shortcode-calendar-view" /> <?php _e('Day','eventorganiserp');?> </label>
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Time format', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="H:i" class="event-shortcode-time-format" />
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Include tooltip', 'eventorganiserp' );?> </td>
				<td>
				<input type="checkbox" name="button-url" value="" class="event-shortcode-tooltip"/>
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Events in categories', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="" class="event-shortcode-cat-selection" />
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Events at venues', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="" class="event-shortcode-venue-selection"/>
				</td>
			</tr>
			
			<tr valign="top">
				<td> <?php esc_html_e( 'Show only events current user is attending', 'eventorganiserp' );?> </td>
				<td><input type="checkbox" name="button-url" value="" class="event-shortcode-bookee"/></td>
			</tr>
			
			
			</tbody></table>

			<table id="eo-shortcode-tab-2" class="form-table" style="padding:0px;"><tbody>
			<tr valign="top">
				<td colspan="2"> <?php esc_html_e( 'Display a Google map of venue or venues', 'eventorganiserp' );?> </td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Venues', 'eventorganiserp' );?> </td>
				<td>
				<input type="text" name="button-url" value="" class="event-shortcode-venue-selection"/>
				<p class="help"><?php esc_html_e( 'Leave blank for venue of current event (if this is being used on an event\'s page', 'eventorganiserp' );?></p>
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Include tooltip', 'eventorganiserp' );?> </td>
				<td>
				<input type="checkbox" name="button-url" value="" class="event-shortcode-tooltip"/>
				</td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Dimensions', 'eventorganiserp' );?> </td>
				<td>
				<label> <?php esc_html_e( 'Height:', 'eventorganiserp' );?> <input type="text" name="button-url" value="" class="event-shortcode-map-height"/> </label>
				<br>
				<label> <?php esc_html_e( 'Width:', 'eventorganiserp' );?>  <input type="text" name="button-url" value="" class="event-shortcode-map-width"/></label>
				</td>
			</tr>
			</tbody></table>
			
			<table id="eo-shortcode-tab-3" class="form-table" style="padding:0px;"><tbody>
			<tr valign="top">
				<td colspan="2"> <?php esc_html_e( 'Allow your users to search events. Select which filters they can use.', 'eventorganiserp' );?> </td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Category', 'eventorganiserp' );?> </td>
				<td><input type="checkbox" name="button-url" value="" class="event-search-filter-event_category"/></td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Venue', 'eventorganiserp' );?> </td>
				<td><input type="checkbox" name="button-url" value="" class="event-search-filter-event_venue"/></td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'City', 'eventorganiserp' );?> </td>
				<td><input type="checkbox" name="button-url" value="" class="event-search-filter-city"/></td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'State', 'eventorganiserp' );?> </td>
				<td><input type="checkbox" name="button-url" value="" class="event-search-filter-state"/></td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Country', 'eventorganiserp' );?> </td>
				<td><input type="checkbox" name="button-url" value="" class="event-search-filter-country"/></td>
			</tr>
			<tr valign="top">
				<td> <?php esc_html_e( 'Date range', 'eventorganiserp' );?> </td>
				<td><input type="checkbox" name="button-url" value="" class="event-search-filter-date"/></td>
			</tr>
			</tbody></table>
		</div>
	</form>
	<p style="bottom:0px;position:absolute;"> 
	<?php printf( 
			__( 'You can view the full range of attributes for these shortcodes on the <a href="%s" target="_blank">plug-in website</a>', 'eventorganiserp' ),
			'http://wp-event-organiser.com/documentation/shortcodes/'
		  );
	;?>
	</p>
</div>