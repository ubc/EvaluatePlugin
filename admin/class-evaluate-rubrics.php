<?php

/**
 * This class registers and renders the form on each subsite,
 * where administrators can fill out the information that this plugin asks for.
 */
class Evaluate_Rubrics {
	// This slug is used for the rubrics admin page.
	public static $page_key = 'evaluate_rubrics';

	/**
	 * @filter init
	 */
	public static function init() {
		if ( current_user_can( 'evaluate_rubrics' ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		}
	}

	/**
	 * Define the admin pages.
	 * @filter network_admin_menu
	 */
	public static function add_page() {
		add_submenu_page(
			Evaluate_Manage::$page_key, // Parent slug
			"Rubrics", // Page title
			"Manage Rubrics", // Menu title
			Evaluate_Manage::$required_capability, // Capability required to view this page.
			self::$page_key, // Page slug
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the rubrics page.
	 */
	public static function render_page() {
		?>
		<h1>Manage Rubrics</h1>
		<?php
		Evaluate_Connector::print_frame( "/blueprints", true );
	}

}

add_action( 'init', array( 'Evaluate_Rubrics', 'init' ) );
