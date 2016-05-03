<?php

/**
 * This class registers and renders the "Manage Rubrics" page.
 */
class Evaluate_Rubrics {
	// This slug is used for the rubrics admin page.
	public static $page_key = 'evaluate_rubrics';

	/**
	 * @filter init
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts_and_styles' ), 5 );
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
	}

	/**
	 * Register the JS script and CSS style that are necessary for this page.
	 * @filter admin_enqueue_scripts
	 */
	public static function register_scripts_and_styles() {
		wp_register_style( 'evaluate-rubrics', Evaluate::$directory_url . 'admin/css/evaluate-rubrics.css' );
	}

	/**
	 * Define the admin pages.
	 * @filter admin_menu
	 */
	public static function add_page() {
		add_submenu_page(
			Evaluate_Manage::$page_key, // Parent slug
			"Evaluate Rubrics", // Page title
			"Manage Rubrics", // Menu title
			'evaluate_edit_rubrics', // Capability required to view this page.
			self::$page_key, // Page slug
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the rubrics page.
	 */
	public static function render_page() {
		?>
		<div class="wrap">
			<?php
			if ( isset( $_GET['blueprint_id'] ) ) {
				// If a blueprint_id has been defined, then render Evaluate's built-in editor.
				?>
				<h1>
					<?php echo empty( $_GET['blueprint_id'] ) ? "Create" : "Edit"; ?> Rubric
					<a href="?page=<?php echo self::$page_key; ?>" class="page-title-action">Go Back</a>
				</h1>
				<?php
				// This function handles the authentication and embedding.
				Evaluate_Connector::print_frame( "/blueprints/edit", array(
					'blueprint_id' => $_GET['blueprint_id'],
				) );
			} else {
				// Set our style to be included.
				wp_enqueue_style( 'evaluate-rubrics' );

				// Retrieve a list of blueprints (aka rubrics) from the Evaluate Servers.
				$blueprints = Evaluate_Connector::get_data( "/blueprints/list" );
				$blueprints = json_decode( $blueprints );

				// Render the list of blueprints.
				?>
				<h1>
					Manage Rubrics
					<a href="?page=<?php echo self::$page_key; ?>&blueprint_id" class="page-title-action">Add Rubric</a>
				</h1>
				<?php
				if ( empty( $blueprints ) ) {
					// If no blueprints were received, display a warning.
					?>
					<div class="notice notice-warning">
						<p>No Rubrics Received from the Server.</p>
					</div>
					<?php
				} else {
					// Render the blueprints in a list.
					?>
					<ul>
						<?php
						foreach ( $blueprints as $index => $blueprint ) {
							self::render_blueprint( $blueprint );
						}
						?>
					</ul>
					<?php
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render a blueprint as a list item.
	 * @param $blueprint the attributes of a blueprint to render.
	 */
	private static function render_blueprint( $blueprint ) {
		?>
		<li>
			<strong class="title"><?php echo $blueprint->name; ?></strong> <a href="?page=<?php echo self::$page_key; ?>&blueprint_id=<?php echo $blueprint->blueprint_id; ?>">[Edit]</a>
			<div><?php echo $blueprint->description; ?></div>
		</li>
		<?php
	}

}

add_action( 'init', array( 'Evaluate_Rubrics', 'init' ) );
