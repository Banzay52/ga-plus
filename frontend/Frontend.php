<?php
namespace sf\gaplus\frontend;

use sf\gaplus\includes\Options as Options;

class Frontend {
	private $plugin_slug;
	private $version;
	private $cft_metafield_name;
	private $appointment_cf_name;
	public  $app_status;

	public function __construct( $plugin_slug, $version ) {
		$this->plugin_slug = $plugin_slug;
		$this->version = $version;
		$this->cft_metafield_name = Options::get_gaplus_option('cft_metafield_name');
		$this->appointment_cf_name = Options::get_gaplus_option('appointment_cf_name');
	}
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '_style', plugin_dir_url( __FILE__ ) . 'css/gaplus-public.css', array(), $this->version, 'all' );
	}
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '_script', plugin_dir_url( __FILE__ ) . 'js/gaplus-public.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_slug . '_script', plugin_dir_url( __FILE__ ) . 'js/gaplus-public.js', array( 'jquery' ), $this->version, false );
	}

	public function gaplus_add_cft_to_appointments() {
		global $post;
		add_filter('test_body', array($this, 'test'), 10, 1);
		if( is_user_logged_in() && has_shortcode( $post->post_content, 'ga_provider_appointments') ) {
			wp_enqueue_script( $this->plugin_slug . '_provider_script', plugin_dir_url( __FILE__ ) . 'js/gaplus-provider.js', array( 'jquery' ), time(), true );
			wp_localize_script( $this->plugin_slug . '_provider_script', 'gaplus_ajax', array( 'url' => admin_url('admin-ajax.php') ) );
			add_filter( 'gappointments/appointments/provider/custom_row', array($this, 'gaplus_cft_injector'), 10, 4 );
			add_filter( 'gappointments/appointments/app_status_name', array($this, 'get_app_status'), 10, 1 );
		}
	}

	public function gaplus_cft_injector( $custom_row, $client_id, $service_id, $provider_id ) {
		$out = '';
		if ( 'pending' === strtolower( $this->app_status ) ) {
			$fields = $this->get_appointment_cf();
			if ( $fields ) {
				$out = $this->show_cf_data($fields);
			} else {
				$out = $this->show_cf_form($service_id);
			}
		}
		$custom_row .= $out;
		return $custom_row;
	}

	private function get_service_stored_templates($serv_id) {
		$stored_data = get_post_meta( $serv_id, $this->cft_metafield_name );
		return $stored_data[0];
	}

	public function get_appointment_cf($app_id = null) {
		if ( empty($app_id) ) {
			$app_id = get_the_ID();
		}

		$data = get_post_meta($app_id, $this->appointment_cf_name, true);
		if ( empty( $data ) ) {
			return false;
		}
		return $data;
	}

	public function save_app_cf_data() {
		$stored_data = array();
		$app_id = '';
		if ( empty($_POST['nonce']) || $_POST['nonce'] !== wp_create_nonce( 'gaplus_app_cf_nonce' ) ) {
			wp_die('Wrong request source!');
		}
		if ( !empty( $_POST['app_id']) ) {
			$app_id = esc_attr( $_POST['app_id'] );
		} else {
			wp_die('Wrong post ID!');
		}
		unset($_POST['action'], $_POST['nonce'], $_POST['app_id']);

		foreach ( $_POST['app_fields'] as $shorcode => $values) {
			foreach ($values as $key => $value) {
				$values[$key] = esc_attr( $value );
			}
			$atts['app_fields'][$shorcode] = $values;
		}
		if ( !empty($atts['app_fields']) ) {
			update_post_meta( $app_id, $this->appointment_cf_name, $atts['app_fields'] );
		} else {
			wp_die('Empty data');
		}
		$stored_data = $this->get_appointment_cf( $app_id );
		if ( $stored_data ) {
			echo json_encode( $stored_data );
		}

		wp_die();
	}

	public function show_cf_data($fields) {
		$out = '<div class="gaplus_cf_wrapper_label action_done">Preconfirmation done</div><div class="gaplus_cf_wrapper flx" style="display: none">';

		foreach ( $fields as $shortcode => $values ) {
			$fld_value = preg_replace("/[\n\r]/", "<br>", $values["value"]);
			$fld_name = $values['name'];
			$out .= sprintf("<div class='gaplus_app_cf'> <div class='gaplus_app_cf_name'><b>%s</b><br>%%%s%%</div> <div class='gaplus_app_cf_value'>%s</div> </div>", $fld_name, $shortcode, $fld_value);
		}
		$out .= '</div>';
		return $out;
	}

/*
 * Prepares form of fields set to let user fill this form
 * to create new custom field template of current service
 *
 * @param	int	$service_id		id of service post
 */
	public function show_cf_form($service_id) {
		$out = '';
		$templates = $this->get_service_stored_templates($service_id);
		if ( ! $templates ) return $out;
		$out = '<div class="gaplus_cf_wrapper_label">Preconfirm action</div><div class="gaplus_cf_wrapper">';
		foreach ( $templates as $tpl ) {
			if ( 'yes' === strtolower( $tpl['required'] ) ) {
				$class_list = ' gaplus_required ';
			} else {
				$class_list = '';
			}
			switch ( $tpl['type'] ) {
				case 'text':
					$out .= $this->field_text_generator($tpl['shortcode'], $tpl['label'], $class_list);
					break;
				case 'textarea':
					$out .= $this->field_textarea_generator($tpl['shortcode'], $tpl['label'], $class_list);
					break;
				case 'select':
					$out .= $this->field_select_generator($tpl['shortcode'], $tpl['options'], $tpl['label'], $class_list);
					break;
				case 'radio':
					$out .= $this->field_radio_generator($tpl['shortcode'], $tpl['options'], $tpl['label'], $class_list);
					break;
				case 'checkbox':
					$out .= $this->field_checkbox_generator($tpl['shortcode'], $tpl['options'], $tpl['label'], $class_list);
					break;
				default:
					break;
			}
		}
		$out .= '<input type="hidden" id="app-nonce" value="' . wp_create_nonce('gaplus_app_cf_nonce') . '">';
		$out .= '<div class="gaplus_preconfirm_buttons"><div name="hide_button">Hide</div><div name="save_button">Save</div></div>';
		$out .= '</div>';

		return $out;
	}

/*
 * Creates text element, based on template attributes
 *
 * @param	string	$shortcode		name of element
 * @param	string	$label			label of element
 * @param	string	$class_list		set of CSS class names
 */
	public function field_text_generator($shortcode, $label = '', $class_list = '') {
		$out = '';
		if ( $shortcode ) {
			$out = '<div class="gaplus_app_field'. $class_list . '">';
			$out .= '<label for="gaplus_' . get_the_ID() . '_' . $shortcode . '">' . $label . ': ' . ($class_list == " gaplus_required" ? "<span style='color: red'>*</span>" : "") . '</label>';
			$out .= '<input type="text" name="' . $shortcode . '" id="gaplus_' . get_the_ID() . '_' . $shortcode . '" value="">';
			$out .='</div>';
		}
		return $out;
	}

/*
 * Creates textarea element, based on template attributes
 *
 * @param	string	$shortcode		name of element
 * @param	string	$label			label of element
 * @param	string	$class_list		set of CSS class names
 */
	public function field_textarea_generator($shortcode, $label = '', $class_list = '') {
		$out = '';
		if ( $shortcode ) {
			$out = '<div class="gaplus_app_field'. $class_list . '">';
			$out .= '<label for="gaplus_' . get_the_ID() . '_' . $shortcode . '">' . $label . ': ' . ($class_list == " gaplus_required" ? "<span style='color: red'>*</span>" : "") . '</label>';
			$out .= '<textarea rows="4" cols="20" name="' . $shortcode . '" id="gaplus_' . get_the_ID() . '_' . $shortcode . '"></textarea></div>';
		}
		return $out;
	}

/*
 * Creates select element, based on template attributes
 *
 * @param	string	$shortcode		name of element
 * @param	string	$options		options values
 * @param	string	$label			label of element
 * @param	string	$class_list		set of CSS class names
 */
	public function field_select_generator($shortcode, $options = '', $label = '', $class_list = '') {
		$out = '';
		if ( $shortcode ) {
			if ( $options ) {
				$options = explode("\n", $options);
			}
			$out = '<div class="gaplus_app_field'. $class_list . '">';
			$out .= '<label for="gaplus_' . get_the_ID() . '_' . $shortcode . '">' . $label . ': ' . ($class_list == " gaplus_required" ? "<span style='color: red'>*</span>" : "") . '</label>';
			$out .= '<select name="' . $shortcode . '" id="gaplus_' . get_the_ID() . '_' . $shortcode . '">';
				$out .= '<option disabled selected value="">Select...</option>';
			foreach ($options as $option) {
				$out .= '<option value="' . $option .'">'. $option .'</option>';
			 }
			$out .= '</select></div>';
		}
		return $out;
	}

/*
 * Creates group of radio type elements, based on template attributes
 *
 * @param	string	$shortcode		name of radio group
 * @param	string	$options		radio values
 * @param	string	$label			label of radio group
 * @param	string	$class_list		set of CSS class names
 */
	public function field_radio_generator($shortcode, $options = '', $label = '', $class_list = '') {
		$out = '';
		if ( $shortcode ) {
			if ( $options ) {
				$options = explode("\n", $options);
			}
			$out = '<div class="gaplus_app_field'. $class_list . '">';
			$out .= '<label for="' . $shortcode . '">' . $label . ': ' . ($class_list == " gaplus_required" ? "<span style='color: red'>*</span>" : "") . '</label><div class="gaplus_cf_radio_group" data-name="' . $shortcode . '">';
			foreach ($options as $option) {
				$out .= '<input type= "radio" name="' . $shortcode . '" value="' . $option .'">' . $option;
			 }
			$out .= '</div></div>';
		}
		return $out;
	}

/*
 * Creates group of checkbox type elements, based on template attributes
 *
 * @param	string	$shortcode		name of checkbox group
 * @param	string	$options		checkboxes values
 * @param	string	$label			label of checkbox group
 * @param	string	$class_list		set of CSS class names
 */
	public function field_checkbox_generator($shortcode, $options = '', $label = '', $class_list = '') {
		$out = '';
		if ( $shortcode ) {
			if ( $options ) {
				$options = explode("\n", $options);
			}
			$out = '<div class="gaplus_app_field'. $class_list . '">';
			$out .= '<label for="' . $shortcode . '">' . $label . ': ' . ($class_list == " gaplus_required" ? "<span style='color: red'>*</span>" : "") . '</label><div class="gaplus_cf_checkbox_group" data-name="' . $shortcode . '">';
			foreach ($options as $option) {
				$out .= '<input type= "checkbox" name="' . $shortcode . '" value="' . $option .'">' . $option;
			 }
			$out .= '</div></div>';
		}
		return $out;
	}

/*
 * Returns appointment status value. Bound to filter 'gappointments/appointments/app_status_name'
 *
 * @param	string	$app_status_name	Value of current appointment (from parent plugin) status
 */
	public function get_app_status($app_status_name) {
		if ( !empty($app_status_name) ) {
			$this->app_status = $app_status_name;
		} else {
			$this->app_status = '';
		}
		return $this->app_status;
	}

}
