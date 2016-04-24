<?php

/**
 * This class registers and renders the form on each subsite,
 * where administrators can fill out the information that this plugin asks for.
 */
class Evaluate_Metrics {
	// This slug is used for the metrics admin page.
	public static $page_key = 'evaluate_metrics';

	private static $special_cases = array(
		'shortcodes' => "Available as a shortcode",
		'anonymous' => "Allow anonymous voters",
		'admins_only' => "Only visible to admins",
		'comments' => "Visible on Comments",
		'comments_attached' => "Attached to Comments [i]",
	);

	/**
	 * @filter init
	 */
	public static function init() {
		add_action( 'wp_ajax_evaluate_set_usage', array( __CLASS__, 'ajax_set_usage' ) );

		if ( current_user_can( 'evaluate_display' ) || current_user_can( 'evaluate_metrics' ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] == self::$page_key ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts_and_styles' ), 5 );
		}
	}

	public static function register_scripts_and_styles() {
		wp_register_style( 'evaluate-metrics', Evaluate::$directory_url . 'admin/css/evaluate-metrics.css' );
		wp_register_script( 'evaluate-metrics', Evaluate::$directory_url . 'admin/js/evaluate-metrics.js', array( 'jquery' ) );
	}
	/**
	 * Define the admin pages.
	 * @filter network_admin_menu
	 */
	public static function add_page() {
		add_submenu_page(
			Evaluate_Manage::$page_key, // Parent slug
			"Metrics", // Page title
			"Manage Metrics", // Menu title
			Evaluate_Manage::$required_capability, // Capability required to view this page.
			self::$page_key, // Page slug
			array( __CLASS__, 'render_page' )
		);
	}

	public static function ajax_set_usage() {
		$metric_id = $_POST['metric_id'];
		$usage = get_option( 'evaluate_usage', array() );

		if ( empty( $_POST['usage'] ) ) {
			unset( $usage[ $metric_id ] );
		} else {
			$usage[ $metric_id ] = $_POST['usage'];
		}

		update_option( 'evaluate_usage', $usage );
		echo 'success';
		wp_die();
	}

	/**
	 * Render the metrics page.
	 */
	public static function render_page() {
		wp_enqueue_style( 'evaluate-metrics' );
		wp_enqueue_script( 'evaluate-metrics' );

		$metrics = Evaluate_Connector::request( "/metrics/list", array(), "POST", true );
		$metrics = json_decode( $metrics );

		$usage = get_option( 'evaluate_usage', array() );

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
			if ( empty( $metrics ) ) {
				?>
				<div class="notice notice-warning">
					<p>No Metrics Received from the Server.</p>
				</div>
				<?php
			} else {
				foreach ( $metrics as $index => $metric ) {
					$metric_usage = empty ( $usage[ $metric->metric_id ] ) ? array() : $usage[ $metric->metric_id ];

					self::render_metric( $metric, $cases, $metric_usage );
				}
			}
			?>
		</div>
		<?php
	}

	private static function render_metric( $metric, $cases, $usage ) {
		//$usage = get_option( self::get_metric_data_slug( $metric->metric_id ), array() );

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

}

add_action( 'init', array( 'Evaluate_Metrics', 'init' ) );
