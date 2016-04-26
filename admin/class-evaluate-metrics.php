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
		$usage = empty( $_POST['usage'] ) ? null : $_POST['usage'];
		$metric_id = empty( $_POST['metric_id'] ) ? null : $_POST['metric_id'];

		if ( empty( $metric_id ) ) {
			echo 'failure';
		} else {
			Evaluate_Settings::set_usage( $usage, $metric_id );
			echo 'success';
		}
		wp_die();
	}

	/**
	 * Render the metrics page.
	 */
	public static function render_page() {
		?>
		<div class="wrap">
			<?php
			if ( isset( $_GET['metric_id'] ) ) {
				?>
				<h1><?php echo empty( $_GET['metric_id'] ) ? "Create" : "Edit"; ?> Metric</h1>
				<?php
				Evaluate_Connector::print_frame( "/metrics/edit", array(
					'metric_id' => $_GET['metric_id'],
				) );
			} else {
				wp_enqueue_style( 'evaluate-metrics' );
				wp_enqueue_script( 'evaluate-metrics' );

				$metrics = Evaluate_Connector::get_data( "/metrics/list" );
				$metrics = json_decode( $metrics );

				$usage = get_option( 'evaluate_usage', array() );

				$cases = array();
				foreach ( get_post_types( array( 'public' => true, ), 'objects' ) as $slug => $object ) {
					$cases[ $slug ] = "Visible on " . $object->labels->name;
				}

				?>
				<h1>
					Manage Metrics
					<a href="?page=<?php echo self::$page_key; ?>&metric_id=" class="page-title-action">Add New</a>
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
			}
			?>
		</div>
		<?php
	}

	private static function render_metric( $metric, $cases, $usage ) {
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
			<div>
				<label>
					Shortcode
					<input class="shortcode-box" type="text" value="[evaluate metric='<?php echo $metric->metric_id; ?>']" <?php echo in_array( 'shortcodes', $usage ) ? '' : 'disabled="disabled"'; ?>></input>
				</label>
			</div>
			<div class="actions">
				<input type="hidden" name="action" value="evaluate_set_usage"></input>
				<input type="hidden" name="metric_id" value="<?php echo $metric->metric_id; ?>"></input>
				<input type="button" class="save-button button button-primary" disabled="disabled" value="Saved"></input>
				<a href="?page=<?php echo self::$page_key; ?>&metric_id=<?php echo $metric->metric_id; ?>" class="edit-button button">Edit Metric</a>
			</div>
			<br class="clear">
		</form>
		<?php
	}

	private static function render_usage_cases( $cases, $usage ) {
		foreach ( $cases as $slug => $text ) {
			?>
			<label>
				<input class="<?php echo $slug; ?>" type="checkbox" name="usage[<?php echo $slug; ?>]" value="<?php echo $slug; ?>" <?php checked( in_array( $slug, $usage ) ); ?>></input>
				<?php echo $text; ?>
			</label>
			<br>
			<?php
		}
	}

}

add_action( 'init', array( 'Evaluate_Metrics', 'init' ) );
