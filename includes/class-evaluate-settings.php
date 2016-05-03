<?php
/**
 * This class manages our plugin's settings.
 */

class Evaluate_Settings {
	// Used to store our plugin settings
	public static $settings_key = 'evaluate_settings';
	// Used to store our network-wide plugin settings
	public static $network_settings_key = 'evaluate_network_settings';
	// Used to store metric usage
	private static $usage_key = 'evaluate_usage';
	// The list of permissions used by this plugin.
	public static $permissions = array(
		'evaluate_vote' => "Vote",
		'evaluate_display_metrics' => "Display Metrics",
		'evaluate_edit_metrics' => "Edit Metrics",
		'evaluate_edit_rubrics' => "Edit Rubrics",
		'evaluate_vote_everywhere' => "See Admin Only Metrics",
	);

	/**
	 * Save usage settings for one or all metrics.
	 * @param $data an array of usage cases which should be enabled. Or an array of arrays if $metric_id is not specified.
	 * @param $metric_id the id of a metric that this data should be applied to.
	 */
	public static function set_usage( $data, $metric_id = null ) {
		if ( ! empty( $metric_id ) ) {
			// Change the usage settings for just one metric.

			// Get the existing usage settings.
			$usage = get_option( self::$usage_key, array() );

			if ( empty( $data ) ) {
				// If there are no usage cases enabled, then remove this from the list.
				unset( $usage[ $metric_id ] );
			} else {
				// Otherwise, overwrite this metric's settings.
				$usage[ $metric_id ] = $data;
			}
		} else {
			// If a metric id was not specified, then we should overwrite all settings.
			$usage = $data;
		}

		// Save the new usage settings.
		update_option( self::$usage_key, $usage );
	}

	/**
	 * Get usage settings for one or all metrics.
	 * @param $metric_id the id of a metric that this data should be retrieved for - or all metrics if null.
	 */
	public static function get_usage( $metric_id = null ) {
		$usage = get_option( self::$usage_key, array() );

		if ( empty( $metric_id ) ) {
			// If no metric id was specified, return the entire usage array.
			return $usage;
		} else {
			// If the metric id was specified, return just the usage settings for that metric.
			return empty( $usage[ $metric_id ] ) ? array() : $usage[ $metric_id ];
		}
	}

	/**
	 * Get one of the plugin's settings.
	 * @param $slug the setting to retrieve.
	 * @param $default the value to return if the setting is not defined.
	 */
	public static function get_settings( $slug, $default = "" ) {
		if ( ( $slug == 'server' || $slug == 'api_key' ) && self::get_network_settings( 'network_toggle') == 'on' ) {
			// If the network toggle is on, and the setting is 'server' or 'api_key' then get the network-wide option.
			return self::get_network_settings( $slug );
		} else {
			// Get all the settings.
			$settings = get_option( self::$settings_key, array() );

			// Return the one we want, or the default.
			return empty( $settings[ $slug ] ) ? $default : $settings[ $slug ];
		}
	}

	/**
	 * Get one of the plugin's network-wide settings.
	 * @param $slug the setting to retrieve.
	 * @param $default the value to return if the setting is not defined.
	 */
	public static function get_network_settings( $slug = null, $default = "" ) {
		// Get all the settings.
		$settings = get_site_option( self::$network_settings_key, array() );

		// Return the one we want, or the default.
		return empty( $settings[ $slug ] ) ? $default : $settings[ $slug ];
	}

	/**
	 * Save a set of permissions
	 * @param $permissions an array of roles mapped to arrays of enabled permissions.
	 */
	public static function set_permissions( $permissions ) {
		// Get a list of valid roles.
		$roles = get_editable_roles();

		// Loop through each one.
		foreach ( $roles as $slug => $info ) {
			$role = get_role( $slug );

			// Loop through the permissions that are managed by this plugin.
			foreach ( self::$permissions as $permission => $name ) {
				// Either remove or add the permission, as appropriate.
				if ( empty( $permissions[ $slug ][ $permission ] ) ) {
					$role->remove_cap( $permission );
				} else {
					$role->add_cap( $permission );
				}
			}
		}
	}
}
