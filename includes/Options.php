/*
 * Options class
 * since: 1.0.0
 */

<?php
namespace sf\gaplus\includes;

class Options {
	private static $default_options = array(
		'cft_metafield_name' => 'ga_service_cf_template',
		'appointment_cf_name' => 'gaplus_appointment_cf',
	);

	public static function get_gaplus_option($name) {
		$result = get_option($name, '');
		if ( !$result ) {
			if ( static::$default_options[ $name ] ) {
				$result = static::$default_options[ $name ];
				$option_saved = static::set_gaplus_option( $name, $result );
			}
		}
		return $result;
	}
	public function set_gaplus_option($name, $value) {
		$result = update_option($name, $value);
		return (bool) $result;
	}
}
//new Options();

?>
