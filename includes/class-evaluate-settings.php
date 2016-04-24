<?php

/**
 * This class registers and renders the form on each subsite,
 * where administrators can fill out the information that this plugin asks for.
 */

// TODO: Support Custom CSS
class Evaluate_Settings {
	// Used to store our plugin settings
	public static $settings_key = 'evaluate_settings';
	// Used to store metric usage
	private static $usage_key = 'evaluate_usage';
	

	public static function set_usage( $data, $metric_id = null ) {
		if ( ! empty( $metric_id ) ) {
			$usage = get_option( self::$usage_key, array() );

			if ( empty( $data ) ) {
				unset( $usage[ $metric_id ] );
			} else {
				$usage[ $metric_id ] = $data;
			}
		} else {
			$usage = $data;
		}

		update_option( self::$usage_key, $usage );
	}

	public static function get_usage( $metric_id = null ) {
		$usage = get_option( self::$usage_key, array() );

		if ( empty( $metric_id ) ) {
			return $usage;
		} else {
			return empty( $usage[ $metric_id ] ) ? array() : $usage[ $metric_id ];
		}
	}

	public static function get_settings( $slug = null, $default = "" ) {
		$settings = get_option( self::$settings_key, array() );

		if ( empty( $slug ) ) {
			return $settings;
		} else {
			return empty( $settings[ $slug ] ) ? $default : $settings[ $slug ];
		}
	}
}
