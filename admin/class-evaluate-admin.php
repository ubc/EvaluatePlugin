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

	private static $special_cases = array(
		'shortcodes' => "Available as a shortcode",
		'admins_only' => "Only visible to admins",
		'comments' => "Visible on Comments",
		'comments_attached' => "Attached to Comments [i]",
	);

	/**
	 * @filter init
	 */
	public static function init() {
		$options = get_option( Evaluate_Settings::$settings_key );
		$server = key_exists( Evaluate_Settings::$server, $options ) ? $options[ Evaluate_Settings::$server ] : null;

		add_action( 'wp_ajax_evaluate_set_usage', array( __CLASS__, 'ajax_set_usage' ) );

		// TODO: Remove this test code.
		$server = "localhost:3000";

		if ( ! empty( $server ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_pages' ) );
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] == self::$metrics_page_key ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts_and_styles' ), 5 );
		}
	}

	public static function register_scripts_and_styles() {
		wp_register_style( 'evaluate-admin', Evaluate::$directory_url . 'admin/css/evaluate-admin.css' );
		wp_register_script( 'evaluate-admin', Evaluate::$directory_url . 'admin/js/evaluate-admin.js', array( 'jquery' ) );
	}

	/**
	 * Define the admin pages.
	 * @filter network_admin_menu
	 */
	public static function add_pages() {
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

	public static function ajax_set_usage() {
		$slug = self::get_metric_data_slug( $_POST['metric_id'] );

		if ( empty( $_POST['usage'] ) ) {
			delete_option( $slug );
		} else {
			update_option( $slug, $_POST['usage'] );
		}

		echo 'success';
		wp_die();
	}

	public static function get_metric_data_slug( $metric_id ) {
		return 'evaluate_usage_' . $metric_id;
	}

	/**
	 * Render the metrics page.
	 */
	public static function render_metrics_page() {
		wp_enqueue_style( 'evaluate-admin' );
		wp_enqueue_script( 'evaluate-admin' );

		$metrics = Evaluate_Connector::post( "/metrics/list", array(), true );
		$metrics = json_decode( $metrics );

		$cases = array();
		foreach ( get_post_types( array( 'public' => true, ), 'objects' ) as $slug => $object ) {
			$cases[ $slug ] = "Visible on " . $object->labels->name;
		}

		?>
		<div class="wrap">
			<h1>
				Manage Metrics
				<a href="test" class="page-title-action">Add New</a>
			</h1>
			<?php
			foreach ( $metrics as $index => $metric ) {
				self::render_metric( $metric, $cases );
			}
			?>
		</div>
		<?php
	}

	private static function render_metric( $metric, $cases ) {
		$usage = get_option( self::get_metric_data_slug( $metric->metric_id ), array() );

		?>
		<form>
			<strong class="title"><?php echo $metric->name; ?></strong>
			<details>
				<summary>Attributes</summary>
				<pre><?php
					echo "type: \"" . $metric->type->title . "\"\r\n";
					foreach ( $metric->options as $key => $value ) {
						echo $key . ": " . json_encode( $value ) . "\r\n";
					}
				?></pre>
			</details>
			<hr>
			<div class="column">
				<?php echo self::render_usage_cases( $cases, $usage ); ?>
			</div>
			<div class="column">
				<?php echo self::render_usage_cases( self::$special_cases, $usage ); ?>
			</div>
			<div class="actions">
				<input type="hidden" name="action" value="evaluate_set_usage"></input>
				<input type="hidden" name="metric_id" value="<?php echo $metric->metric_id; ?>"></input>
				<input type="button" class="save-button button button-primary" disabled="disabled" value="Saved"></input>
				<input type="button" class="edit-button button" value="Edit Metric"></input>
			</div>
			<br class="clear">
		</form>
		<?php
	}

	private static function render_usage_cases( $cases, $usage ) {
		foreach ( $cases as $slug => $text ) {
			?>
			<label>
				<input type="checkbox" name="usage[<?php echo $slug; ?>]" value="<?php echo $slug; ?>" <?php checked( in_array( $slug, $usage ) ); ?>></input>
				<?php echo $text; ?>
			</label>
			<br>
			<?php
		}
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
