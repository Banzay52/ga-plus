<?php
namespace sf\gaplus\includes;

defined( 'ABSPATH' ) or exit;

class Sender {

	public function get_provider_name_title( $provider_id ) {
		return 'ga_providers' == get_post_type($provider_id) ? esc_html( ucwords(get_the_title($provider_id)) ) : '';
	}

	private function get_provider_id( $post_id ) {
		return (int) get_post_meta( $post_id, 'ga_appointment_provider', true );
	}

	public function get_service_id( $post_id ) {
		return (int) get_post_meta( $post_id, 'ga_appointment_service', true );
	}

	private function get_client_name($post_id) {
		$client_id  = get_post_meta( $post_id, 'ga_appointment_client', true );
		$new_client = get_post_meta( $post_id, 'ga_appointment_new_client', true );

		if( $client_id == 'new_client' ) {
			$name = isset( $new_client['name'] ) && !empty( $new_client['name'] ) ? $new_client['name'] : '';
			return $name;
		} elseif( $user_info = get_userdata($client_id) ) {
			$name = isset( $new_client['name'] ) && !empty( $new_client['name'] ) ? $new_client['name'] : $user_info->user_nicename;
			return $name;
		} else {
			return '';
		}
	}

	private function get_client_email($post_id) {
		$client_id  = get_post_meta( $post_id, 'ga_appointment_client', true );
		$new_client = get_post_meta( $post_id, 'ga_appointment_new_client', true );

		if( $client_id == 'new_client' ) {
			$new_client = get_post_meta( $post_id, 'ga_appointment_new_client', true );
			$email = isset( $new_client['email'] ) && !empty( $new_client['email'] ) ? $new_client['email'] : '';
			return $email;
		} elseif( $user_info = get_userdata($client_id) ) {
			$email = isset( $new_client['email'] ) && !empty( $new_client['email'] ) ? $new_client['email'] : $user_info->user_email;
			return $email;
		} else {
			return '';
		}
	}

	private function get_client_phone($post_id) {

		$client_id  = get_post_meta( $post_id, 'ga_appointment_client', true );
		$new_client = get_post_meta( $post_id, 'ga_appointment_new_client', true );

		if( $client_id == 'new_client' ) {
			$new_client = get_post_meta( $post_id, 'ga_appointment_new_client', true );
			$phone = isset( $new_client['phone'] ) && !empty( $new_client['phone'] ) ? $new_client['phone'] : '';
			return $phone;
		} elseif( $user_phone = get_user_meta($client_id,'phone',true) ) {
			$phone = isset( $new_client['phone'] ) && !empty( $new_client['phone'] ) ? $new_client['phone'] : $user_phone;
			return $phone;
		} else {
			return '';
		}
	}

	private function get_provider_email($post_id) {
		$provider_id = (int) get_post_meta( $post_id, 'ga_appointment_provider', true );

		if( 'ga_providers' == get_post_type( $provider_id ) ) {

			$user_assigned = (int) get_post_meta( $provider_id, 'ga_provider_user', true );

			if( $provider_data = get_userdata( $user_assigned ) ) {
				$provider_email = $provider_data->user_email;
				return $provider_email;
			} else {
				return false;
			}

		} else {
			return false;
		}
	}

	private function get_service_name($post_id) {
		$service_id = (int) get_post_meta( $post_id, 'ga_appointment_service', true );
		$service_name = 'ga_services' == get_post_type($service_id) ? esc_html( get_the_title( $service_id ) ) : 'Not defined';

		return $service_name;
	}

	private function get_provider_name($post_id) {
		$provider_id = (int) get_post_meta( $post_id, 'ga_appointment_provider', true );
		$provider_name = 'ga_providers' == get_post_type($provider_id) ? esc_html( get_the_title( $provider_id ) ) : '';

		return $provider_name;
	}

	private function get_app_date_time($post_id) {
		return sprintf( '%s %s', get_post_meta( $post_id, 'ga_appointment_date', 1 ), get_post_meta( $post_id, 'ga_appointment_time', 1 ) );
	}
	public function get_reminder_custom_shortcodes($service_id, $appointment_id) {
		$pairs = array (
			'shortcodes' => array(),
			'values'     => array(),
		);
		$custom_fields = get_post_meta($service_id, Options::get_gaplus_option('cft_metafield_name'), true);
		$custom_values = get_post_meta($appointment_id, Options::get_gaplus_option('appointment_cf_name'), true);

		if ( $custom_fields ) {
			foreach($custom_fields as $field) {
				$shortcode = $field['shortcode'];
				$value = $custom_values[$shortcode]['value'];

				$pairs['shortcodes'][] = '%' . $shortcode . '%';
				$pairs['values'][] = ( $value ? $value : '');
			}
		}
		return $pairs;
	}

    /**
     * Change WP_MAIL Name From
     */
	public function wp_mail_from_name() {
		$options    = get_option( 'ga_appointments_notifications' );
		$from_name = isset( $options['from_name'] ) ? $options['from_name'] : get_bloginfo();
		return $from_name;
	}

    /**
     * Change WP_MAIL Email From
     */
	public function wp_mail_from() {
		$options    = get_option( 'ga_appointments_notifications' );
		$from_email = isset( $options['from_email'] ) ? $options['from_email'] : get_bloginfo('admin_email');
		return $from_email;
	}

	public function ga_mail($to, $subject, $body) {
		add_filter('wp_mail_from_name', array($this, 'wp_mail_from_name'));
		add_filter('wp_mail_from', array($this, 'wp_mail_from'));
		$headers = array('Content-Type: text/html; charset=UTF-8');
		wp_mail( $to, $subject, $body, $headers );
		remove_filter('wp_mail_from_name', array($this, 'wp_mail_from_name'));
		remove_filter('wp_mail_from', array($this, 'wp_mail_from'));
	}

    /**
     * WP Twilio Core: Plugin active
     */
	public function twl_active() {
		if( in_array('wp-twilio-core/core.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {
			return true;
		} else {
			return false;
		}
	}

    /**
     * Send SMS using WP Twilio Core
     */
	public function ga_sms($number_to, $message) {
		if( function_exists('twl_send_sms') ) {
			// Send SMS
			$args = array(
				'number_to' => $number_to,
				'message'   => $message,
			);
			twl_send_sms( $args );
		}
	}

    /**
     * Client Reminder SMS
     */
	public function reminder_sms( $post_id, $text ) {
		if( !$this->twl_active() ) {
			return;
		}
		$client_phone  = $this->get_client_phone($post_id);
		$serv_id = $this->get_service_id($post_id);

		if( empty($client_phone) ) {
			return;
		}

		$find = array(
			'%client_name%',
			'%service_name%',
			'%provider_name%',
			'%provider_email%',
			'%appointment_date%',
		);

		$replace = array(
			$this->get_client_name($post_id),
			$this->get_service_name($post_id),
			$this->get_provider_name($post_id),
			$this->get_provider_email($post_id),
			$this->get_app_date_time($post_id),
		);

			$pairs = $this->get_reminder_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
					$find = array_merge($find, $pairs['shortcodes']);
					$replace = array_merge($replace, $pairs['values']);
			}


		$message = isset( $text ) ? str_ireplace( $find, $replace, $text )
					: str_ireplace( $find, $replace, $this->reminder_body() );
		$this->ga_sms( $client_phone , $message );
	}
    /**
     * Send reminder Email To Client
     */
	public function send_reminder($post_id, $template) {

		$heading_title = isset( $template['title'] ) ? $template['title'] : $this->reminder_title();
		$serv_id = $this->get_service_id($post_id);

		if( $this->get_client_email($post_id) == '' ) {
			return;
		}
		$find = array(
			'%client_name%',
			'%service_name%',
			'%provider_name%',
			'%provider_email%',
			'%appointment_date%',
		);

		$replace = array(
			$this->get_client_name($post_id),
			$this->get_service_name($post_id),
			$this->get_provider_name($post_id),
			$this->get_provider_email($post_id),
			$this->get_app_date_time($post_id),
		);
			$pairs = $this->get_reminder_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
					$find = array_merge($find, $pairs['shortcodes']);
					$replace = array_merge($replace, $pairs['values']);
			}

		$body    = isset( $template['body'] ) ? str_ireplace( $find, $replace, wpautop($template['body']) )
					: str_ireplace( $find, $replace, wpautop($this->reminder_body()) );
		$subject = str_ireplace( $find, $replace, $template['subject'] );

		// Html template
		ob_start();
		require ('html_email.php');
		$html_email = ob_get_clean();

		$find = array(
			'%appointment_heading_content%',
			'%appointment_body_content%'
		);

		$replace = array(
			$heading_title,
			$body
		);

		$body = str_ireplace( $find, $replace, $html_email);

		$this->ga_mail( $this->get_client_email($post_id), $subject, $body );
	}

	public function reminder_subject() {
		return 'Appointment reminder - %appointment_date%';
	}

	public function reminder_title() {
		return 'Appointment reminder';
	}

	public function reminder_body() {
		$output = 'Hi %client_name%' . PHP_EOL;
		$output .= '' . PHP_EOL; // new line
		$output .= 'Your %service_name% with %provider_name%(%provider_email%) is coming soon.' . PHP_EOL;
		$output .= '' . PHP_EOL; // new line
		$output .= '%appointment_date%';
		return $output;
	}
// SMS message template
	public function confirmation_sms_body() {
		$output = 'Hi %client_name%' . PHP_EOL;
		$output .= '' . PHP_EOL; // new line
		$output .= 'Your %service_name% on %appointment_date% will take place in 4 hours.';
		return $output;
	}

}
