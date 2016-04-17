<?php

/**
 * This class registers and renders the form on each subsite,
 * where administrators can fill out the information that this plugin asks for.
 */
class Evaluate_Admin {
	// This slug is used for the metrics admin page.
	public static $metrics_page_key = 'evaluate_metrics';
	// This slug is used for the rubrics admin page.
	public static $rubrics_page_key = 'evaluate_rubrics';

	/**
	 * @filter init
	 */
	public static function init() {
		$options = get_option( Evaluate_Settings::$settings_key );
		$server = key_exists( Evaluate_Settings::$server, $options ) ? $options[ Evaluate_Settings::$server ] : null;

		// TODO: Remove this test code.
		$server = "localhost:3000";

		if ( ! empty( $server ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_pages' ) );
		}
	}

	/**
	 * Define the admin pages.
	 * @filter network_admin_menu
	 */
	public static function add_pages() {
		error_log("Add pages");

		add_submenu_page(
			Evaluate_Settings::$page_key, // Parent slug
			"Metrics", // Page title
			"Manage Metrics", // Menu title
			Evaluate_Settings::$required_capability, // Capability required to view this page.
			self::$metrics_page_key, // Page slug
			array( __CLASS__, 'render_metrics_page' )
		);

		add_submenu_page(
			Evaluate_Settings::$page_key, // Parent slug
			"Rubrics", // Page title
			"Manage Rubrics", // Menu title
			Evaluate_Settings::$required_capability, // Capability required to view this page.
			self::$rubrics_page_key, // Page slug
			array( __CLASS__, 'render_rubrics_page' )
		);
	}

	/**
	 * Render the metrics page.
	 */
	public static function render_metrics_page() {
		?>
		<h1>Manage Metrics</h1>
		<?php
		Evaluate_Connector::print_frame( "/metrics", true );
	}

	/**
	 * Render the metrics page.
	 */
	public static function render_rubrics_page() {
		?>
		<h1>Manage Rubrics</h1>
		<?php
		Evaluate_Connector::print_frame( "/blueprints", true );
	}

}

add_action( 'init', array( 'Evaluate_Admin', 'init' ) );
