<?php
/*
 * Additional functions
*/

use sf\gaplus\includes\Sender as Sender;
defined( 'ABSPATH' ) or exit;


function parent_plugin_not_active_message() {
	echo '<div id="gaplus_error" class="notice notice-error is-dismissible"><p>gAppointments Plus addon requires gAppointments plugin. <span style="color: #a00">Activate gAppointments plugin</span>.</p></div>';
}

function no_cmb2_class_error() {
	echo '<div class="notice notice-error is-dismissible">No CMB2 class present</div>';
}

function no_cmb2_object_error() {
	echo '<div class="notice notice-error is-dismissible">No CMB2 object present</div>';
}

function ga_service_set_default_location() {
	$options = get_option( 'ga_appointments_add_to_calendar' );
	$location = $options['location'] || '' ;

	return $location;
}

function get_google_places_key() {
	return get_option( 'ga_appointments_calendar' )['google_places_key'];
}

/*
 * metabox fields validation
  */
function sanitize_ga_service_location( $value, $field_args, $field ) {
	return wp_strip_all_tags( $value, true );
}

function get_ajax_attributes(){
	return array( 'url' => admin_url('admin-ajax.php'), );
}

/*
 *  sheduling of reminder messages
 */
add_action('save_post_ga_appointments', 'ga_appointment_schedule_reminder', 10, 3);

add_action('ga_schedule_reminder_hook', 'ga_send_reminder', 10, 2);

function ga_appointment_schedule_reminder($post_id, $post, $update) {
	$serv_id = (int) get_post_meta( $post_id, 'ga_appointment_service', 1 );

	if ( 'publish' === $post->post_status ) {
		add_to_schedule($post_id, $serv_id);
	} else {
		remove_from_shedule($post_id, $serv_id);
	}
}

function ga_send_reminder($app_id, $serv_id) {
	$email_reminder_is_on = get_post_meta($serv_id, 'client_notifications_reminder', 1);
	$sms_reminder_is_on = get_post_meta($serv_id, 'client_notifications_reminder_sms', 1);

	require_once(( GAPLUS_PLUGIN_PATH . 'includes/Sender.php' ));
	$sender = new Sender();

	if ( $email_reminder_is_on == 'on' ) {
		$email_reminder = get_post_meta($serv_id, 'client_notification_reminder', 1 );
		$sender->send_reminder($app_id, $email_reminder);
	}
	if ( $sms_reminder_is_on == 'on' ) {
		$sms_reminder = get_post_meta($serv_id, 'client_notification_reminder_sms', 1 );
		$sender->reminder_sms( $app_id, $sms_reminder );
	}
}

function get_appointment_timestamp($post_id) {
	$sel_timezone = get_option( 'ga_appointments_calendar' );
	$time_zone =  isset( $sel_timezone['time_zone'] ) ? $sel_timezone['time_zone'] : 'Europe/Bucharest';

	$date_time = new DateTime( sprintf( '%s %s', get_post_meta( $post_id, 'ga_appointment_date', 1 ), get_post_meta( $post_id, 'ga_appointment_time', 1 ) ), new DateTimeZone( $time_zone ) );

	$post_timestamp = $date_time->getTimestamp();
	return $post_timestamp;
}

function add_to_schedule($post_id, $service_id) {
	$fire_time = get_appointment_timestamp($post_id);

	$args = array($post_id, $service_id);
	$old_time = wp_next_scheduled( 'ga_schedule_reminder_hook', $args );

	if ( $fire_time !== $old_time ) {
		if ( $old_time ) {
			wp_unschedule_event( $old_time, 'ga_schedule_reminder_hook', $args );
		}
		wp_schedule_single_event($fire_time - 14400, 'ga_schedule_reminder_hook', $args );
	}
}

function remove_from_shedule($post_id, $service_id) {
	$args = array($post_id, $service_id);
	$time = wp_next_scheduled( 'ga_schedule_reminder_hook', $args );
	if ( $time ) {
		wp_unschedule_event( $time, 'ga_schedule_reminder_hook', $args );
	}
}

function draw_autocomplete($field_args, $field){

	if( !empty(get_google_places_key()) ) {
?>
<div class="cmb-td ga_hide" id="location_autocomplete">
			<div id="locationField">
				<input type="text" class="regular-text" name="autocomplete" id="autocomplete" placeholder="Start to type address" value=""/>
			</div>
		</div>
<?php
		echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . get_google_places_key() . '&libraries=places&callback=initAutocomplete" async defer></script>';
	} else {
?>
	<div class="cmb-td ga_hide" id="location_autocomplete">
		<span style="color: red">Error: No valid Google places API key! Please enter and save valid Google places API key on gAppointments settings page</span>
	</div>
<?php
	}
}
?>
