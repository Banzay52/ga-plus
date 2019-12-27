<?php
namespace sf\gaplus\admin;

use sf\gaplus\includes\Options as Options;

class Admin {

	private $plugin_slug;
	private $version;

	public function __construct( $plugin_slug, $version ) {
		$this->plugin_slug = $plugin_slug;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '_admin_style', plugin_dir_url( __FILE__ ) . 'css/gaplus-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_slug . '_google_places_style', plugin_dir_url( __FILE__ ) . 'css/google-places-admin.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '_admin_script', plugin_dir_url( __FILE__ ) . 'js/gaplus-admin.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->plugin_slug . '_google_places_script', plugin_dir_url( __FILE__ ) . 'js/google-places-api-admin.js', array(), $this->version, false );
		wp_localize_script( $this->plugin_slug . '_admin_script', 'google_places_key', array( get_google_places_key() ) );
		if ( 'ga_services' === get_post_type() ) {
			wp_enqueue_script( $this->plugin_slug . '_service_admin_script', plugin_dir_url( __FILE__ ) . 'js/gaplus-service-admin.js', array( 'jquery' ), $this->version, true );
			wp_localize_script( $this->plugin_slug . '_service_admin_script', 'cf_generator_form', $this->get_cft_attributes() );
			wp_localize_script( $this->plugin_slug . '_service_admin_script', 'cft_rows_data', $this->get_stored_cft_data() );
		}
	}

	public function use_global_notifications($service_id = null) {
		static $use_global = null;
		if ( ! $service_id ) {
			$service_id = get_the_ID();
		}
		if ( is_null( $use_global ) && ( 'ga_services' === get_post_type($service_id) ) ) {
			$use_global = (bool) get_post_meta( $service_id, '_use_global_notifications', false );

		}
		return $use_global;
	}
//----  Service location part -----
/*
 * add meta field "Service location" to "Service details" options group
 */
	public function gaplus_ga_services_add_location_field() {
		$prefix = 'ga_service_';
		if ( ! class_exists('CMB2_Bootstrap_230') ) {
			add_action('admin_notices', array($this, 'no_cmb2_class_error'));
		} else {
				$cmb = cmb2_get_metabox('ga_services_details');
				if ( $cmb !== NULL ) {
					$cmb->add_field( array(
						'name'        => 'Service Location',
						'desc'        => 'Enter location of service. Type arbitrary text or use <span id="show_gpac">Google places autocomplete</span>',
						'id'          => 'ga_service_location',
						'type'        => 'text',
						'default_cb'  => 'ga_service_set_default_location',
						'attributes'  => array(
								'placeholder' => 'Enter address',
								'required'    => 'required',
						),
						'sanitization_cb' => 'sanitize_ga_service_location',
						'after_field'     => 'draw_autocomplete',
					) );
				} else {
					add_action('admin_notices', 'no_cmb2_object_error');
				}
		}
	}

//----  Service custom meta field part -----
/*
 * add meta box "Appointment Custom Fields" to service add/edit page
 */

	public function gaplus_ga_services_add_custom_fields_metabox() {
		$cmb_s = new_cmb2_box( array(
			'id'            => 'ga_services_custom_fields',
			'title'         => 'Appointment Custom Fields',
			'object_types'  => array( 'ga_services', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true, // Show field names on the left
		) );
	}

	public function get_cft_attributes() {
		/* define attributes of custom field*/
		$template = array(
			array(
				'attr_name'   => 'label',
				'label'       => 'Field label',
				'type'        => 'text',
				'value'       => array('',),
				'placeholder' => 'Enter field label here',
				'order'       => 0,
			),
			array(
				'attr_name'  => 'type',
				'label'      => 'Field type',
				'type'       => 'select',
				'value'      => array(
									'text',
									'textarea',
									'select',
									'checkbox',
									'radio'
								),
				'placeholder' => 'Select field type here',
				'order'       => 1,
			),
			array(
				'attr_name'   => 'options',
				'label'       => 'Options',
				'type'        => 'textarea',
				'value'       => array('',),
				'placeholder' => 'Enter options separated by end-of-line',
				'order'       => 2,
			),
			array(
				'attr_name'   => 'required',
				'label'       => 'Required',
				'type'        => 'select',
				'value'       => array( 'Yes', 'No' ),
				'placeholder' => 'Select Yes if field is required',
				'order'       => 3,
			),
			 array(
				'attr_name'   => 'shortcode',
				'label'       => 'Field shortcode',
				'type'        => 'text',
				'value'       => array('',),
				'placeholder' => 'Enter unique shortcode (spaces not allowed)',
				'order'       => 4,
			),
		);

		return array(
				'atts'       => $template,
				'post_id'    => get_the_ID(),
				'nonce'      => wp_create_nonce('gaplus_nonce'),
				'metabox_id' => 'ga_services_custom_fields',
			);
	}

	public function update_cft_field() {
		static $debug = false;
		$stored_data = array();
		if ( empty($_POST['nonce']) || $_POST['nonce'] !== wp_create_nonce( 'gaplus_nonce' ) ) {
			wp_die('Wrong request!');
		}

		$service_id = $_POST['post_id'];

		unset($_POST['action'], $_POST['nonce'], $_POST['post_id']);

		foreach ( $_POST as $key => $value) {
			$atts[$key] = esc_attr($value);
		}
		if ( $debug ) {
			delete_post_meta($service_id, Options::get_gaplus_option('cft_metafield_name'));
			$debug = false;
		}
		$stored_data = $this->get_stored_cft_data($service_id);

		if ( !empty($atts) ) {
			$stored_data[] = $atts;
			update_post_meta($service_id, Options::get_gaplus_option('cft_metafield_name'), $stored_data);
		}
		echo json_encode($stored_data);

		wp_die();
	}

	public function get_stored_cft_data($serv_id = null) {
		if ( empty($serv_id) ) {
			$serv_id = get_the_ID();
		}
		if ( !empty($serv_id) && 'ga_services' == get_post_type($serv_id) ) {
			$data = get_post_meta($serv_id, Options::get_gaplus_option('cft_metafield_name'), true);
		}
		if ( empty( $data ) ) {
			return false;
		}
		return $data;
	}

	public function get_app_custom_fields($app_id) {
		if ( !empty($app_id) && 'ga_appointments' == get_post_type($app_id) ) {
			$data = get_post_meta($app_id, Options::get_gaplus_option('appointment_cf_name'), true);
		}
		if ( empty( $data ) ) {
			return false;
		}
		return $data;
	}

//--------  Service notifications part -----------
// add metabox "Notifications" to service add/edit page

	/*
	 * create metafield type "email message" (subject, title, body)
	 */
	public function draw_email_msg_field( $field, $value, $object_id, $object_type, $field_type ) {
		$value = wp_parse_args( $value, array(
					'subject' => '',
					'title'   => '',
					'body'    => '',
				) );
		?>
		<div><p><label for="<?php echo $field_type->_id( '_subject' ); ?>">Subject</label></p>
			<?php echo $field_type->input( array(
				'name'  => $field_type->_name( '[subject]' ),
				'id'    => $field_type->_id( '_subject' ),
				'value' => $value['subject'],
				'desc'  => '',
			) ); ?>
		</div>
		<div><p><label for="<?php echo $field_type->_id( '_title' ); ?>">Title</label></p>
			<?php echo $field_type->input( array(
				'name'  => $field_type->_name( '[title]' ),
				'id'    => $field_type->_id( '_title' ),
				'value' => $value['title'],
				'desc'  => '',
			) ); ?>
		</div>
		<div><p><label for="<?php echo $field_type->_id( '_body' ); ?>">Body</label></p>
			<?php echo $field_type->textarea( array(
				'class' => 'cmb-type-textarea',
				'name'  => $field_type->_name( '[body]' ),
				'id'    => $field_type->_id( '_body' ),
				'value' => $value['body'],
				'desc'  => '',
			) ); ?>
		</div>
		<div><p><label for="msg_shortcodes">Shortcodes:
			<?php
				$agent = explode('_', $field->args['id'])[0];
				echo $this->get_notification_shortcodes( $agent );
			?>
			</label></p>
		</div>
		<br class="clear">
		<?php
	}

	public function ga_service_notifications_metabox() {
		$prefix = 'ga_service_notifications';
		$cmb = new_cmb2_box( array(
			'id'           => 'ga_service_notification',
			'title'        => 'Service Notifications',
			'object_types' => array( 'ga_services' ),
			'priority'      => 'default',
		) );

		$cmb->add_field( array(
			'name' => 'Use global notifications',
			'id'   => '_use_global_notifications',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Use global notification settings',
		) );

		// Checkboxes client appointment notification email
		$cmb->add_field( array(
			'name' => 'Send appointment notification email to client',
			'id'   => 'client_notifications_pending',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Pending email',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'client_notifications_confirmation',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Confirmation email',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'client_notifications_cancelled',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Cancelled email',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'client_notifications_reminder',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Reminder email',
			'classes' => 'gp_checkbox',
		) );
		// Checkboxes client appointment notification sms
		$cmb->add_field( array(
			'name' => 'Send appointment notification sms to client',
			'id'   => 'client_notifications_pending_sms',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Pending sms',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'client_notifications_confirmation_sms',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Confirmation sms',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'client_notifications_cancelled_sms',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Cancelled sms',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'client_notifications_reminder_sms',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Reminder sms',
			'classes' => 'gp_checkbox',
		) );
		// Checkboxes provider appointment notification email
		$cmb->add_field( array(
			'name' => 'Send appointment notifications email to provider',
			'id'   => 'provider_notifications_pending',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Pending email',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'provider_notifications_confirmation',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Confirmation email',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'provider_notifications_cancelled',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Cancelled email',
			'classes' => 'gp_checkbox',
		) );
		// Checkboxes provider appointment notification sms
		$cmb->add_field( array(
			'name' => 'Send appointment notifications sms to provider',
			'id'   => 'provider_notifications_pending_sms',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Pending sms',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'provider_notifications_confirmation_sms',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Confirmation sms',
			'classes' => 'gp_checkbox',
		) );
		$cmb->add_field( array(
			'name' => ' ',
			'id'   => 'provider_notifications_cancelled_sms',
			'type' => 'checkbox',
			'default' => false,
			'desc' => 'Cancelled sms',
			'classes' => 'gp_checkbox',
		) );
		// Client email notifications
		$cmb->add_field( array(
			'name' => 'Client Appointment Pending Email',
			'id'   => 'client_notification_pending',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		$cmb->add_field( array(
			'name' => 'Client Appointment Confirmation Email',
			'id'   => 'client_notification_confirmation',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		$cmb->add_field( array(
			'name' => 'Client Appointment Cancelled Email',
			'id'   => 'client_notification_cancelled',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		$cmb->add_field( array(
			'name' => 'Client Appointment Reminder Email',
			'id'   => 'client_notification_reminder',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		$cmb->add_field( array(
			'name' => 'Client Multiple Appointments Pending Email',
			'id'   => 'client_notification_bulk_pending',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		$cmb->add_field( array(
			'name' => 'Client Multiple Appointments Confirmation Email',
			'id'   => 'client_notification_bulk_confirmation',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		//Provider email notifications
		$cmb->add_field( array(
			'name' => 'Provider Appointment Pending Email',
			'id'   => 'provider_notification_pending',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		$cmb->add_field( array(
			'name' => 'Provider Appointment Confirmation Email',
			'id'   => 'provider_notification_confirmation',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		$cmb->add_field( array(
			'name' => 'Provider Appointment Cancelled Email',
			'id'   => 'provider_notification_cancelled',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		$cmb->add_field( array(
			'name' => 'Provider Multiple Appointments Pending Email',
			'id'   => 'provider_notification_bulk_pending',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		$cmb->add_field( array(
			'name' => 'Provider Multiple Appointments Confirmation Email',
			'id'   => 'provider_notification_bulk_confirmation',
			'type' => 'email_msg_field',
			'desc' => '',
		) );
		// Client sms notifications
		$cmb->add_field( array(
			'name' => 'Client Appointment Pending sms',
			'id'   => 'client_notification_pending_sms',
			'type' => 'textarea',
			'desc' => '',
			'after_field' => array($this, 'textarea_after_field'),
		) );
		$cmb->add_field( array(
			'name' => 'Client Appointment Confirmation sms',
			'id'   => 'client_notification_confirmation_sms',
			'type' => 'textarea',
			'desc' => '',
			'after_field' => array($this, 'textarea_after_field'),
		) );
		$cmb->add_field( array(
			'name' => 'Client Appointment Cancelled sms',
			'id'   => 'client_notification_cancelled_sms',
			'type' => 'textarea',
			'desc' => '',
			'after_field' => array($this, 'textarea_after_field'),
		) );
		$cmb->add_field( array(
			'name' => 'Client Appointment Reminder sms',
			'id'   => 'client_notification_reminder_sms',
			'type' => 'textarea',
			'desc' => '',
			'after_field' => array($this, 'textarea_after_field'),
		) );
		//Provider sms notifications
		$cmb->add_field( array(
			'name' => 'Provider Appointment Pending sms',
			'id'   => 'provider_notification_pending_sms',
			'type' => 'textarea',
			'desc' => '',
			'after_field' => array($this, 'textarea_after_field'),
		) );
		$cmb->add_field( array(
			'name' => 'Provider Appointment Confirmation sms',
			'id'   => 'provider_notification_confirmation_sms',
			'type' => 'textarea',
			'desc' => '',
			'after_field' => array($this, 'textarea_after_field'),
		) );
		$cmb->add_field( array(
			'name' => 'Provider Appointment Cancelled sms',
			'id'   => 'provider_notification_cancelled_sms',
			'type' => 'textarea',
			'desc' => '',
			'after_field' => array($this, 'textarea_after_field'),
		) );
	}

/*
 * Returns shortcodes collection, allowed to use in notification message
 *
 * @param 	int 	$service_id 	post id
 * @param 	string 	$agent 			notification recipient
 */

	public function get_notification_shortcodes( $agent = 'client', $to_array = false ) {
		$notification_shorcodes = array(
			'client' => array(
					'%client_name%',
					'%appointment_date%',
					'%service_name%',
					'%provider_name%',
					'%provider_email%',
				),
			'provider' => array(
					'%client_name%',
					'%client_email%',
					'%client_phone%',
					'%appointment_date%',
					'%service_name%',
					'%provider_name%'
				),
		);
		$custom_fields = $this->get_stored_cft_data();
		if ( $custom_fields ) {
			foreach($custom_fields as $field) {
				$notification_shorcodes[$agent][] = '%' . $field['shortcode'] . '%';
			}
		}
		return $this->draw_shortcode_list( $notification_shorcodes[$agent], $to_array );
	}

	public function draw_shortcode_list( $shortcodes, $to_array = false, $glue = ' | ' ) {
		$out = $to_array ? $shortcodes : implode( $glue, $shortcodes) ;
		return $out;
	}

	public function textarea_after_field($field_args, $field) {
		$out = '<p>Shortcodes: ';
		$agent = explode('_', $field->args['id'])[0];
		$out .= $this->get_notification_shortcodes( $agent );
		$out .= '</p>';
		return $out;
	}

	public function get_custom_shortcodes($service_id, $appointment_id) {
		$pairs = array (
			'shortcodes' => array(),
			'labels'     => array(),
		);
		$custom_fields = $this->get_stored_cft_data($service_id);
		$custom_values = $this->get_app_custom_fields($appointment_id);

		if ( $custom_fields ) {
			foreach($custom_fields as $field) {
				$shortcode = $field['shortcode'];
				$label = $custom_values[$shortcode]['value'];

				$pairs['shortcodes'][] = '%' . $shortcode . '%';
				$pairs['labels'][] = ( $label ? $label : '');
			}
		}
		return $pairs;
	}


	public function gp_client_pending_sms_notifications($sms, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$sms = ( get_post_meta( $serv_id, 'client_notifications_pending_sms', 1 ) ? 'yes' : 'no' );
		}
		return $sms;
	}

	public function gp_client_pending_sms_body($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
					$find = array_merge($find, $pairs['shortcodes']);
					$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, get_post_meta( $serv_id, 'client_notification_pending_sms', 1 ) );
		}
		return $msg;
	}

	public function gp_client_confirmation_sms_notifications($sms, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$sms = ( get_post_meta( $serv_id, 'client_notifications_confirmation_sms', 1 ) ? 'yes' : 'no' );
		}
		return $sms;
	}

	public function gp_client_confirmation_sms_body($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, get_post_meta( $serv_id, 'client_notification_confirmation_sms', 1 ) );
		}
		return $msg;
	}

	public function gp_client_cancelled_sms_notifications($sms, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$sms = ( get_post_meta( $serv_id, 'client_notifications_cancelled_sms', 1 ) ? 'yes' : 'no' );
		}
		return $sms;
	}

	public function gp_client_cancelled_sms_body($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, get_post_meta( $serv_id, 'client_notification_cancelled_sms', 1 ) );
		}
		return $msg;
	}

	public function gp_provider_pending_sms_notifications($sms, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$sms = ( get_post_meta( $serv_id, 'provider_notifications_pending_sms', 1 ) ? 'yes' : 'no' );
		}
		return $sms;
	}

	public function gp_provider_pending_sms_body($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, get_post_meta( $serv_id, 'provider_notification_pending_sms', 1 ) );
		}
		return $msg;
	}

	public function gp_provider_confirmation_sms_notifications($sms, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$sms = ( get_post_meta( $serv_id, 'provider_notifications_confirmation_sms', 1 ) ? 'yes' : 'no' );
		}
		return $sms;
	}

	public function gp_provider_confirmation_sms_body($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, get_post_meta( $serv_id, 'provider_notification_confirmation_sms', 1 ) );
		}
		return $msg;
	}

	public function gp_provider_cancelled_sms_notifications($sms, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$sms = ( get_post_meta( $serv_id, 'provider_notifications_cancelled_sms', 1 ) ? 'yes' : 'no' );
		}
		return $sms;
	}

	public function gp_provider_cancelled_sms_body($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, get_post_meta( $serv_id, 'provider_notification_cancelled_sms', 1 ) );
		}
		return $msg;
	}

	public function gp_client_cancelled_email_notifications($ntf, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$ntf = ( get_post_meta( $serv_id, 'client_notifications_cancelled', 1 ) ? 'yes' : 'no' );
		}
		return $ntf;
	}

	public function gp_client_cancelled_email_subject($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field  = get_post_meta( $serv_id, 'client_notification_cancelled', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['subject'] );
		}
		return $msg;
	}

	public function gp_client_cancelled_email_title($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = get_post_meta( $serv_id, 'client_notification_cancelled', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['title'] );
		}
		return $msg;
	}

	public function gp_client_cancelled_email_body($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = get_post_meta( $serv_id, 'client_notification_cancelled', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, wpautop($field['body']) );
		}
		return $msg;
	}


	public function gp_provider_cancelled_email_notifications($ntf, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$ntf = ( get_post_meta( $serv_id, 'provider_notifications_cancelled', 1 ) ? 'yes' : 'no' );
		}
		return $ntf;
	}

	public function gp_provider_cancelled_email_subject($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = get_post_meta( $serv_id, 'provider_notification_cancelled', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['subject'] );
		}
		return $msg;
	}

	public function gp_provider_cancelled_email_title($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = get_post_meta( $serv_id, 'provider_notification_cancelled', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['title'] );
		}
		return $msg;
	}

	public function gp_provider_cancelled_email_body($msg, $post_id, $serv_id, $find, $replace) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = get_post_meta( $serv_id, 'provide_notification_cancelled', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, wpautop($field['body']) );
		}
		return $msg;
	}

	// Client confirmation email
	public function gp_client_confirmation_email_notifications($ntf, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$ntf = ( get_post_meta( $serv_id, 'client_notifications_confirmation', 1 ) ? 'yes' : 'no' );
		}
		return $ntf;
	}

	public function gp_client_confirmation_email_subject($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = $bulk ?
				get_post_meta( $serv_id, 'client_notification_bulk_confirmation', 1 ) :
				get_post_meta( $serv_id, 'client_notification_confirmation', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['subject'] );
		}
		return $msg;
	}

	public function gp_client_confirmation_email_title($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = $bulk ?
				get_post_meta( $serv_id, 'client_notification_bulk_confirmation', 1 ) :
				get_post_meta( $serv_id, 'client_notification_confirmation', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['title'] );
		}
		return $msg;
	}

	public function gp_client_confirmation_email_body($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			if ( $bulk ) {
				$field = get_post_meta( $serv_id, 'client_notification_bulk_confirmation', 1 );
			} else {
				$field = get_post_meta( $serv_id, 'client_notification_confirmation', 1 );
			}
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, wpautop($field['body']) );
		}
		return $msg;
	}

	public function gp_client_pending_email_notifications($ntf, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$ntf = ( get_post_meta( $serv_id, 'client_notifications_pending', 1 ) ? 'yes' : 'no' );
		}
		return $ntf;
	}

	public function gp_client_pending_email_subject($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = $bulk ?
				get_post_meta( $serv_id, 'client_notification_bulk_pending', 1 ) :
				get_post_meta( $serv_id, 'client_notification_pending', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['subject'] );
		}
		return $msg;
	}

	public function gp_client_pending_email_title($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = $bulk ?
				get_post_meta( $serv_id, 'client_notification_bulk_pending', 1 ) :
				get_post_meta( $serv_id, 'client_notification_pending', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['title'] );
		}
		return $msg;
	}

	public function gp_client_pending_email_body($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$field = $bulk ?
				get_post_meta( $serv_id, 'client_notification_bulk_pending', 1 ) :
				get_post_meta( $serv_id, 'client_notification_pending', 1 );
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, wpautop($field['body']) );
		}
		return $msg;
	}

	public function gp_provider_confirmation_email_notifications($ntf, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$ntf = ( get_post_meta( $serv_id, 'provider_notifications_confirmation', 1 ) ? 'yes' : 'no' );
		}
		return $ntf;
	}

	public function gp_provider_confirmation_email_subject($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			if ( $bulk ) {
				$field = get_post_meta( $serv_id, 'provider_notification_bulk_confirmation', 1 );
			} else {
				$field = get_post_meta( $serv_id, 'provider_notification_confirmation', 1 );
			}
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['subject'] );
		}
		return $msg;
	}
	
	public function gp_provider_confirmation_email_title($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			if ( $bulk ) {
				$field = get_post_meta( $serv_id, 'provider_notification_bulk_confirmation', 1 );
			} else {
				$field = get_post_meta( $serv_id, 'provider_notification_confirmation', 1 );
			}
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['title'] );
		}
		return $msg;
	}
	public function gp_provider_confirmation_email_body($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			if ( $bulk ) {
				$field = get_post_meta( $serv_id, 'provider_notification_bulk_confirmation', 1 );
			} else {
				$field = get_post_meta( $serv_id, 'provider_notification_confirmation', 1 );
			}
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg = str_ireplace( $find, $replace, wpautop($field['body']) );
		}
		return $msg;
	}


	public function gp_provider_pending_email_notifications($ntf, $serv_id) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			$ntf = ( get_post_meta( $serv_id, 'provider_notifications_pending' ) ? 'yes' : 'no' );
		}
		return $ntf;
	}
	
	public function gp_provider_pending_email_subject($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			if ( $bulk ) {
				$field = get_post_meta( $serv_id, 'provider_notification_bulk_pending', 1 );
			} else {
				$field = get_post_meta( $serv_id, 'provider_notification_pending', 1 );
			}
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['subject'] );
		}
		return $msg;
	}
	
	public function gp_provider_pending_email_title($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			if ( $bulk ) {
				$field = get_post_meta( $serv_id, 'provider_notification_bulk_pending', 1 );
			} else {
				$field = get_post_meta( $serv_id, 'provider_notification_pending', 1 );
			}
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, $field['title'] );
		}
		return $msg;
	}
	
	public function gp_provider_pending_email_body($msg, $post_id, $serv_id, $find, $replace, $bulk = false) {
		$use_global = $this->use_global_notifications($serv_id);
		if ( ! $use_global ) {
			if ( $bulk ) {
				$field = get_post_meta( $serv_id, 'provider_notification_bulk_pending', 1 );
			} else {
				$field = get_post_meta( $serv_id, 'provider_notification_pending', 1 );
			}
			$pairs = $this->get_custom_shortcodes($serv_id, $post_id);
			if ( $pairs ) {
				$find = array_merge($find, $pairs['shortcodes']);
				$replace = array_merge($replace, $pairs['labels']);
			}
			$msg =  str_ireplace( $find, $replace, wpautop($field['body']) );
		}
		return $msg;
	}

	public function add_notification_filters() {
		add_filter( 'client/pending/sms/notifications', array($this, 'ga_client_pending_sms_notifications'), 10, 2 );
		add_filter( 'client/pending/sms/body', array($this, 'gp_client_pending_sms_body'), 10, 5 );
		add_filter( 'client/confirmation/sms/notifications', array($this, 'gp_client_confirmation_sms_notifications'), 10, 2 );
		add_filter( 'client/confirmation/sms/body', array($this, 'gp_client_confirmation_sms_body'), 10, 5 );
		add_filter( 'client/cancelled/sms/notifications', array($this, 'gp_client_cancelled_sms_notifications'), 10, 2 );
		add_filter( 'client/cancelled/sms/body', array($this, 'gp_client_cancelled_sms_body'), 10, 5 );
		add_filter( 'provider/pending/sms/notifications', array($this, 'gp_provider_pending_sms_notifications'), 10, 2 );
		add_filter( 'provider/pending/sms/body', array($this, 'gp_provider_pending_sms_body'), 10, 5 );
		add_filter( 'provider/confirmation/sms/notifications', array($this, 'gp_provider_confirmation_sms_notifications'), 10, 2 );
		add_filter( 'provider/confirmation/sms/body', array($this, 'gp_provider_confirmation_sms_body'), 10, 5 );
		add_filter( 'provider/cancelled/sms/notifications', array($this, 'gp_provider_cancelled_sms_notifications'), 10, 2 );
		add_filter( 'provider/cancelled/sms/body', array($this, 'gp_provider_cancelled_sms_body'), 10, 5 );
		add_filter( 'client/cancelled/email/notifications', array($this, 'gp_client_cancelled_email_notifications'), 10, 2 );
		add_filter( 'client/cancelled/email/subject', array($this, 'gp_client_cancelled_email_subject'), 10, 5 );
		add_filter( 'client/cancelled/email/title', array($this, 'gp_client_cancelled_email_title'), 10, 5 );
		add_filter( 'client/cancelled/email/body', array($this, 'gp_client_cancelled_email_body'), 10, 5 );
		add_filter( 'provider/cancelled/email/notifications', array($this, 'gp_provider_cancelled_email_notifications'), 10 , 2 );
		add_filter( 'provider/cancelled/email/subject', array($this, 'gp_provider_cancelled_email_subject'), 10 , 5 );
		add_filter( 'provider/cancelled/email/title', array($this, 'gp_provider_cancelled_email_title'), 10, 5 );
		add_filter( 'provider/cancelled/email/body', array($this, 'gp_provider_cancelled_email_body'), 10, 5 );
		add_filter( 'client/confirmation/email/notifications', array($this, 'gp_client_confirmation_email_notifications'), 10, 2 );
		add_filter( 'client/confirmation/email/subject', array($this, 'gp_client_confirmation_email_subject'), 10, 6 );
		add_filter( 'client/confirmation/email/title', array($this, 'gp_client_confirmation_email_title'), 10, 6 );
		add_filter( 'client/confirmation/email/body', array($this, 'gp_client_confirmation_email_body'), 10, 6 );
		add_filter( 'client/pending/email/notifications', array($this, 'gp_client_pending_email_notifications'), 10 ,2 );
		add_filter( 'client/pending/email/subject', array($this, 'gp_client_pending_email_subject'), 10 , 6 );
		add_filter( 'client/pending/email/title', array($this, 'gp_client_pending_email_title'), 10, 6 );
		add_filter( 'client/pending/email/body', array($this, 'gp_client_pending_email_body'), 10, 6 );

		add_filter( 'provider/confirmation/email/notifications', array($this, 'gp_provider_confirmation_email_notifications'), 10, 2 );
		add_filter( 'provider/confirmation/email/subject', array($this, 'gp_provider_confirmation_email_subject'), 10, 6 );
		add_filter( 'provider/confirmation/email/title', array($this, 'gp_provider_confirmation_email_title'), 10, 6 );
		add_filter( 'provider/confirmation/email/body', array($this, 'gp_provider_confirmation_email_body'), 10, 6 );
		add_filter( 'provider/pending/email/notifications', array($this, 'gp_provider_pending_email_notifications'), 10, 2 );
		add_filter( 'provider/pending/email/subject', array($this, 'gp_provider_pending_email_subject'), 10, 6 );
		add_filter( 'provider/pending/email/title', array($this, 'gp_provider_pending_email_title'), 10, 6 );
		add_filter( 'provider/pending/email/body', array($this, 'gp_provider_pending_email_body'), 10, 6 );

	}
}
