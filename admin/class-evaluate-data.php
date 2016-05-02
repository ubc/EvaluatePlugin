<?php

/**
 * This class registers and renders the form on each subsite,
 * where administrators can fill out the information that this plugin asks for.
 */
class Evaluate_Data {
	// This slug is used for the metrics admin page.
	public static $page_key = 'evaluate_data';
	// The format for displaying date/time.
	public static $datetime_format = "D, M j Y";

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
		wp_enqueue_style( 'evaluate-data' );
		//wp_enqueue_script( 'evaluate-data' );

		$data = Evaluate_Connector::get_data( "/data" );
		$data = json_decode( $data );

		$regular_voters = 0;
		$admin_voters = 0;
		$users = count_users();

		foreach ( $users['avail_roles'] as $slug => $count ) {
			$role = get_role( $slug );

			if ( ! empty( $role->capabilities['evaluate_vote'] ) ) {
				$regular_voters += $count;
				
				if ( ! empty( $role->capabilities['evaluate_vote_everywhere'] ) ) {
					$admin_voters += $count;
				}
			}
		}

		?>
		<div class="wrap">
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
					foreach ( $data as $index => $metric ) {
						$usage = Evaluate_Settings::get_usage( $metric->metric_id );
						$eligible_voters = in_array( 'admin_only', $usage ) ? $admin_voters : $regular_voters;

						self::render_metric( $metric, $eligible_voters );
					}
					?>
				</ul>
				<?php
			}
			?>
		</div>
		<?php
	}

	private static function render_metric( $data, $eligible_voters = 0 ) {
		$total_contexts = count( $data->scores );
		$unique_voters = 0;
		$voters = array();

		foreach ( $data->votes as $index => $vote ) {
			$user_id = $vote->user_id;
			$value = $vote->value;

			if ( ! array_key_exists( $user_id, $voters ) ) {
				$voters[ $user_id ] = array(
					'total_votes' => 0,
					'last_vote' => $vote->modified,
				);

				$unique_voters++;
			} else if ( $vote->modified > $voters[ $user_id ]) {
				$voters[ $user_id ] = $vote->modified;
			}

			$voters[ $user_id ]['total_votes']++;
		}

		?>
		<li>
			<strong><a href="?page=<?php echo Evaluate_Metrics::$page_key; ?>&metric_id=<?php echo $data->metric_id; ?>"><?php echo $data->name; ?></a></strong>
			<details>
				<summary>Unique Voters: <?php echo $unique_voters; ?> of <?php echo $eligible_voters; ?> (<?php echo $unique_voters / $eligible_voters * 100; ?>%)</summary>
				<ul>
					<?php
					foreach ( $voters as $user_id => $voter ) {
						self::render_voter( $user_id, $voter, $total_contexts );
					}
					?>
				</ul>
			</details>
			<details>
				<summary>Total Contexts: <?php echo $total_contexts; ?></summary>
				<ul>
					<?php
					foreach ( $data->scores as $index => $score ) {
						self::render_context( $score, $eligible_voters );
					}
					?>
				</ul>
			</details>
		</li>
		<?php
	}

	private static function render_context( $data, $eligible_voters ) {
		$date = new DateTime( $data->modified );
		$date = $date->format( self::$datetime_format );

		?>
		<li>
			<strong><a href="<?php echo $data->context_id; ?>"><?php echo $data->context_id; ?></a></strong>
			<div>Score: <?php echo $data->average; ?></div>
			<div>Voters: <?php echo $data->count; ?> (<?php echo $data->count / $eligible_voters * 100; ?>%)</div>
			<div>Latest Vote: <?php echo $date; ?></div>
		</li>
		<?php
	}

	private static function render_voter( $user_id, $data, $total_contexts ) {
		$date = new DateTime( $data['last_vote'] );
		$date = $date->format( self::$datetime_format );
		$user = get_userdata( $user_id );
		$name = empty( $user ) ? $user_id : $user->display_name;
		$link = empty( $user ) ? "#" : get_author_posts_url( $user_id, $name );

		?>
		<li>
			<strong><a href="<?php echo $link; ?>"><?php echo $name; ?></a></strong>
			<div>Vote Count: <?php echo $data['total_votes']; ?> (<?php echo $data['total_votes'] / $total_contexts * 100; ?>%)</div>
			<div>Latest Vote: <?php echo $date; ?></div>
		</li>
		<?php
	}
}

add_action( 'init', array( 'Evaluate_Data', 'init' ) );
