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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts_and_styles' ), 5 );

		if ( current_user_can( 'evaluate_rubrics' ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		}
	}

	public static function register_scripts_and_styles() {
		wp_register_style( 'evaluate-rubrics', Evaluate::$directory_url . 'admin/css/evaluate-rubrics.css' );
	}

	/**
	 * Define the admin pages.
	 * @filter network_admin_menu
	 */
	public static function add_page() {
		add_submenu_page(
			Evaluate_Manage::$page_key, // Parent slug
			"Evaluate Rubrics", // Page title
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
		<div class="wrap">
			<?php
			if ( isset( $_GET['blueprint_id'] ) ) {
				?>
				<h1>
					<?php echo empty( $_GET['blueprint_id'] ) ? "Create" : "Edit"; ?> Rubric
					<a href="?page=<?php echo self::$page_key; ?>" class="page-title-action">Go Back</a>
				</h1>
				<?php
				Evaluate_Connector::print_frame( "/blueprints/edit", array(
					'blueprint_id' => $_GET['blueprint_id'],
				) );
			} else {
				wp_enqueue_style( 'evaluate-rubrics' );
				$blueprints = Evaluate_Connector::get_data( "/blueprints/list" );
				$blueprints = json_decode( $blueprints );

				?>
				<h1>
					Manage Rubrics
					<a href="?page=<?php echo self::$page_key; ?>&blueprint_id" class="page-title-action">Add Rubric</a>
				</h1>
				<?php
				if ( empty( $blueprints ) ) {
					?>
					<div class="notice notice-warning">
						<p>No Blueprints Received from the Server.</p>
					</div>
					<?php
				} else {
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
