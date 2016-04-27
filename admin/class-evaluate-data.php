<?php

/**
 * This class registers and renders the form on each subsite,
 * where administrators can fill out the information that this plugin asks for.
 */
class Evaluate_Data {
	// This slug is used for the metrics admin page.
	public static $page_key = 'evaluate_data';

	/**
	 * @filter init
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts_and_styles' ), 5 );

		if ( current_user_can( 'evaluate_display' ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		}
	}

	public static function register_scripts_and_styles() {
		wp_register_style( 'evaluate-data', Evaluate::$directory_url . 'admin/css/evaluate-data.css' );
		//wp_register_script( 'evaluate-data', Evaluate::$directory_url . 'admin/js/evaluate-data.js', array( 'jquery' ) );
	}

	/**
	 * Define the admin pages.
	 * @filter network_admin_menu
	 */
	public static function add_page() {
		add_submenu_page(
			Evaluate_Manage::$page_key, // Parent slug
			"Evaluate Data", // Page title
			"Data", // Menu title
			Evaluate_Manage::$required_capability, // Capability required to view this page.
			self::$page_key, // Page slug
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the metrics page.
	 */
	public static function render_page() {
		?>
		<div class="wrap">
			<?php
			if ( ! empty( $_GET['metric_id'] ) ) {
				/*?>
				<h1>
					<?php echo empty( $_GET['metric_id'] ) ? "Create" : "Edit"; ?> Metric
					<a href="?page=<?php echo self::$page_key; ?>" class="page-title-action">Go Back</a>
				</h1>
				<?php
				Evaluate_Connector::print_frame( "/metrics/edit", array(
					'metric_id' => $_GET['metric_id'],
				) );*/
			} else if ( ! empty( $_GET['context_id'] ) ) {
				/*?>
				<h1>
					<?php echo empty( $_GET['metric_id'] ) ? "Create" : "Edit"; ?> Metric
					<a href="?page=<?php echo self::$page_key; ?>" class="page-title-action">Go Back</a>
				</h1>
				<?php
				Evaluate_Connector::print_frame( "/metrics/edit", array(
					'metric_id' => $_GET['metric_id'],
				) );*/
			} else {
				wp_enqueue_style( 'evaluate-data' );
				//wp_enqueue_script( 'evaluate-data' );

				$data = Evaluate_Connector::get_data( "/data" );
				$data = json_decode( $data );

				?>
				<h1>Voting Data</h1>
				<?php
				if ( empty( $data ) ) {
					?>
					<div class="notice notice-warning">
						<p>No Data Received from the Server.</p>
					</div>
					<?php
				} else {
					?>
					<ul>
						<?php
						foreach ( $data as $index => $data ) {
							self::render_metric( $data );
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

	private static function render_metric( $data ) {
		?>
		<li>
			<strong><?php echo $data->name; ?></strong>
			<div>Vote Count: <?php echo count( $data->votes ); ?></div>
			<div>Context Count: <?php echo count( $data->scores ); ?></div>
			<details>
				<summary>Data</summary>
				<pre><?php print_r( $data ); ?></pre>
			</details>
		</li>
		<?php
	}
}

add_action( 'init', array( 'Evaluate_Data', 'init' ) );
