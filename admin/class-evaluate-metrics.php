<?php

/**
 * This class registers and renders the "Manage Metrics" page.
 */
class Evaluate_Metrics {
	// This slug is used for the metrics admin page.
	public static $page_key = 'evaluate_metrics';
	// These are special usage scenarios for the metrics.
	private static $special_cases = array(
		'shortcodes' => "Available as a shortcode",
		'anonymous' => "Allow anonymous voters",
		'admins_only' => "Only visible to admins",
		'comment' => "Visible on Comments",
		'comment_attached' => "Attached to Comments <span class=\"info\" title=\"If enabled, the user's vote will be shown alongside their comment.\">[?]</span>",
	);

	/**
	 * @filter init
	 */
	public static function init() {
		add_action( 'wp_ajax_evaluate_set_usage', array( __CLASS__, 'ajax_set_usage' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts_and_styles' ), 5 );

		// Check that either of the required permissions are satisfied.
		if ( current_user_can( 'evaluate_display_metrics' ) || current_user_can( 'evaluate_edit_metrics' ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		}
	}

	/**
	 * Register the JS script and CSS style that are necessary for this page.
	 * @filter admin_enqueue_scripts
	 */
	public static function register_scripts_and_styles() {
		wp_register_style( 'evaluate-metrics', Evaluate::$directory_url . 'admin/css/evaluate-metrics.css' );
		wp_register_script( 'evaluate-metrics', Evaluate::$directory_url . 'admin/js/evaluate-metrics.js', array( 'jquery' ) );
	}

	/**
	 * Define the admin page.
	 * @filter admin_menu
	 */
	public static function add_page() {
		add_submenu_page(
			Evaluate_Manage::$page_key, // Parent slug
			"Evaluate Metrics", // Page title
			"Manage Metrics", // Menu title
			'read', // Capability required to view this page.
			self::$page_key, // Page slug
			array( __CLASS__, 'render_page' ) // Rendering callback.
		);
	}

	/**
	 * An ajax callback which is used to save the forms rendered on this page.
	 */
	public static function ajax_set_usage() {
		$usage = empty( $_POST['usage'] ) ? null : $_POST['usage'];
		$metric_id = empty( $_POST['metric_id'] ) ? null : $_POST['metric_id'];

		if ( empty( $metric_id ) || ! current_user_can( 'evaluate_display_metrics' ) ) {
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
			if ( isset( $_GET['metric_id'] ) && current_user_can( 'evaluate_edit_metrics' ) ) {
				// IF the metric_id has been provided then we embed the Evaluate Server's built-in editor.
				?>
				<h1>
					<?php echo empty( $_GET['metric_id'] ) ? "Create" : "Edit"; ?> Metric
					<a href="?page=<?php echo self::$page_key; ?>" class="page-title-action">Go Back</a>
				</h1>
				<?php
				// This function handles the authentication and embedding.
				Evaluate_Connector::print_frame( "/metrics/edit", array(
					'metric_id' => $_GET['metric_id'],
				) );
			} else {
				// Set our style and script to be included.
				wp_enqueue_style( 'evaluate-metrics' );
				wp_enqueue_script( 'evaluate-metrics' );

				// Retrieve a list of metrics from the Evaluate Server.
				$metrics = Evaluate_Connector::get_data( "/metrics/list" );
				$metrics = json_decode( $metrics );

				// Get the saved usage options.
				$usage = get_option( 'evaluate_usage', array() );

				// Construct the list of usage cases, from various post types.
				$cases = array();
				foreach ( get_post_types( array( 'public' => true, ), 'objects' ) as $slug => $object ) {
					$cases[ $slug ] = "Visible on " . $object->labels->name;
				}

				// Render the page.
				?>
				<h1>
					Manage Metrics
					<a href="?page=<?php echo self::$page_key; ?>&metric_id" class="page-title-action">Add New</a>
				</h1>
				<?php
				if ( empty( $metrics ) ) {
					// If we receive no metrics, display a warning.
					?>
					<div class="notice notice-warning">
						<p>No Metrics Received from the Server.</p>
					</div>
					<?php
				} else {
					// Render each metric in a list.
					?>
					<ul>
						<?php
						foreach ( $metrics as $index => $metric ) {
							$metric_usage = empty ( $usage[ $metric->metric_id ] ) ? array() : $usage[ $metric->metric_id ];
							self::render_metric( $metric, $cases, $metric_usage );
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
	 * Render a single metric.
	 * @param $metric the attributes of a metric.
	 * @param $cases an array of usage cases to render.
	 * @param $usage an array of saved usage settings.
	 */
	private static function render_metric( $metric, $cases, $usage ) {
		?>
		<li>
			<form class="metric">
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
				<div class="column">
					<?php echo self::render_usage_cases( $cases, $usage ); ?>
				</div>
				<div class="column">
					<?php echo self::render_usage_cases( self::$special_cases, $usage ); ?>
				</div>
				<?php
				if ( current_user_can( 'evaluate_display_metrics' ) ) {
					?>
					<div>
						<label>
							Shortcode
							<input class="shortcode-box" type="text" value="[evaluate metric='<?php echo $metric->metric_id; ?>']" <?php echo in_array( 'shortcodes', $usage ) ? '' : 'disabled="disabled"'; ?>></input>
						</label>
					</div>
					<?php
				}
				?>
				<div class="actions">
					<?php
					if ( current_user_can( 'evaluate_display_metrics' ) ) {
						?>
						<input type="hidden" name="action" value="evaluate_set_usage"></input>
						<input type="hidden" name="metric_id" value="<?php echo $metric->metric_id; ?>"></input>
						<input type="button" class="save-button button button-primary" disabled="disabled" value="Saved"></input>
						<?php
					}

					if ( current_user_can( 'evaluate_edit_metrics' ) ) {
						?>
						<a href="?page=<?php echo self::$page_key; ?>&metric_id=<?php echo $metric->metric_id; ?>" class="edit-button button">Edit Metric</a>
						<?php
					}
					?>
				</div>
				<br class="clear">
			</form>
		</li>
		<?php
	}

	/**
	 * Render the form elements for controlling the usage settings for a particular metric.
	 * @param $cases an array of usage cases to render.
	 * @param $usage an array of saved usage settings.
	 */
	private static function render_usage_cases( $cases, $usage ) {
		foreach ( $cases as $slug => $text ) {
			?>
			<label>
				<input class="<?php echo $slug; ?>" type="checkbox" name="usage[<?php echo $slug; ?>]" value="<?php echo $slug; ?>" <?php checked( in_array( $slug, $usage ) ); ?> <?php disabled( ! current_user_can( 'evaluate_display_metrics' ) ); ?>></input>
				<?php echo $text; ?>
			</label>
			<br>
			<?php
		}
	}

}

add_action( 'init', array( 'Evaluate_Metrics', 'init' ) );
