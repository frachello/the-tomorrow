<?php
/**
 * Event Organiser Email Template: Event Organiser
 *
 * This template is used for sending e-mails to bookees.
 *
 * ********************** NOTICE: ***********************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 * ********************** NOTICE: ***********************
 * 
 * 
 * ********** WANT TO CREATE YOUR OWN TEMPLATE? *********
 * Simply create a file just like this in your theme.
 * Make sure "Event Organiser Email Template: [template name]" resides in 
 * the comment header at the top.
 * ********** WANT TO CREATE YOUR OWN TEMPLATE? ********* 
 *
 * @package Event Organiser Pro (plug-in)
 * @since 1.0
 */
?>
<!-- Event Organiser Email Template: Template Name -->
<div style="background: #f0f0f0; padding: 8px 10px;font-family:Arial;Helvetica;sans-serif;color:#737373;font-size:14px;">
	<div style="background: #21759b; width: 550px;  border: 1px solid #ccc; margin: 0 auto;border-radius:5px;">
		<div style="height:75px;"></div>
		<div id="eo-email-content" style="background: #fff;padding: 8px 15px;">
			<?php eventorganiser_email_content(); ?>
			<div  id="eo-email-content" style="height:50px;position:relative;">
				<div style="font-size:10px;color:black;font-family:Arial;Helvetica;sans-serifvertical-align:text-bottom;width:100%;text-align:center;position:absolute;bottom:0;">
				<?php echo __( 'Powered by', 'eventorganiserp' ) .' <a href="http://wp-event-organiser.com">Event Organiser</a>'; ?>
				</div>
			</div>
		</div>
	</div>
</div>
