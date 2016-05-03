<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Evaluate Plugin
 * Plugin URI:        http://ctlt.ubc.ca
 * Description:       Interfaces with the Evaluate NodeJS App. http://www.github.com/ubc/EvaluateApp
 * Version:           1.0.0
 * Author:            CTLT, Devindra Payment
 * Text Domain:       evaluate
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: www.github.com/ubc/EvaluatePlugin
 */

class Evaluate {
	public static $directory_path = '';
	public static $directory_url = '';
	// The version number of this plugin. Used to track upgrades.
	public static $version = "1.0.0";
	
	public static function init() {
		self::$directory_path = plugin_dir_path( __FILE__ );
		self::$directory_url = plugin_dir_url( __FILE__ );
		
		add_action( 'plugins_loaded', array( __CLASS__, 'load' ), 11 );
		register_activation_hook( __FILE__, array( __CLASS__, 'install' ) );
	}

	/**
	 * Load the plugin, if we meet requirements.
	 * @filter plugins_loaded
	 */
	public static function load() {
		require_once( self::$directory_path . '/includes/class-evaluate-connector.php' );
		require_once( self::$directory_path . '/includes/class-evaluate-settings.php' );

		if ( is_admin() ) {
			require_once( self::$directory_path . '/admin/class-evaluate-manage.php' );
			require_once( self::$directory_path . '/admin/class-evaluate-metrics.php' );
			require_once( self::$directory_path . '/admin/class-evaluate-rubrics.php' );
			require_once( self::$directory_path . '/admin/class-evaluate-data.php' );
		} else {
			require_once( self::$directory_path . '/public/class-evaluate-display.php' );
		}
	}

	/**
	 * This is the activation hook used to set defaults and perform upgrades when necessary.
	 */
	public static function install() {
		require_once( self::$directory_path . '/includes/class-evaluate-settings.php' );
		$version = get_site_option( 'evaluate_version', "0" );

		// If the user has a version number less than 1.0.0 that means their installation has never been initialized.
		if ( version_compare( $version, "1.0.0" ) < 0 ) {
			// Set default permissions.
			Evaluate_Settings::set_permissions( array(
				'administrator' => array(
					'evaluate_vote' => true,
					'evaluate_display_metrics' => true,
					'evaluate_vote_everywhere' => true,
					'evaluate_edit_metrics' => true,
					'evaluate_edit_rubrics' => true,
				),
				'editor'        => array(
					'evaluate_vote' => true,
					'evaluate_display_metrics' => true,
					'evaluate_vote_everywhere' => true,
					'evaluate_edit_metrics' => true,
				),
				'author'        => array(
					'evaluate_vote' => true,
					'evaluate_display_metrics' => true,
					'evaluate_vote_everywhere' => true,
				),
				'contributor'   => array(
					'evaluate_vote_metrics' => true,
					'evaluate_display' => true,
				),
				'subscriber'    => array(
					'evaluate_vote' => true,
				),
			) );
		}

		// Update the site's version number.
		update_site_option( 'evaluate_version', self::$version );
	}
}

Evaluate::init();
