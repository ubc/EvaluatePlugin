<?php

/**
 * This class is responsible for determining when to render metrics, as well as rendering them.
 */
class Evaluate_Display {

	private static $user_id = false;
	
	/**
	 * @filter init
	 */
	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'render_metrics' ), 100 );
		add_filter( 'comment_text', array( __CLASS__, 'render_metrics' ), 100 );
		add_shortcode( 'evaluate', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * @return the user's Wordpress ID, or their IP if they are anonymous.
	 */
	public static function get_user_id() {
		// Check if the user_id is already cached.
		if ( self::$user_id === false ) {
			// If not, then set it.
			if ( is_user_logged_in() ) {
				// If the user is logged in, then use their Wordpress ID.
				self::$user_id = get_current_user_id();
			} else if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				// Check for IP from shared internet
				self::$user_id = $_SERVER['HTTP_CLIENT_IP'];
			} else if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				// Check for IP from a proxy
				self::$user_id = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				// Default IP check.
				self::$user_id = $_SERVER['REMOTE_ADDR'];
			}
		}

		return self::$user_id;
	}

	/**
	 * Get the context in which a metric should be rendered.
	 * @param $filter the Wordpress filter which is currently being run - or the filter you want to emulate.
	 * @param $force_type if set a certain context type will be used.
	 * @param $force_id if set a certain post/comment id will be used.
	 */
	public static function get_context( $filter, $force_type = null, $force_id = null ) {
		if ( ( 'comment_text' === $filter && $force_type == null ) || $force_type == 'comment' ) {
			// The 'comment' context
			$id    = empty( $force_id ) ? get_comment_ID() : $force_id;
			$type  = 'comment';
			$title = get_comment( $id )->comment_author . "'s comment";
			$url   = get_comment_link();
		} else if ( ( 'comment_text' !== $filter && get_the_ID() != false && $force_type == null ) || $force_type == 'post' ) {
			// The 'post' context
			$id    = empty( $force_id ) ? get_the_ID() : $force_id;
			$type  = get_post_type( $id );
			$title = get_the_title( $id );
			$url   = get_the_permalink( $id );
		} else {
			// The default context
			$type  = empty( $force_type ) ? 'webpage' : $force_type;
			$title = wp_title( "", false );
			$url   = empty( $force_id ) ? $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : $force_id;
		}

		// Run the results through a filter so that other plugins can modify it.
		return apply_filters( 'evaluate_get_context', array(
			'type'  => $type,  // This corresponds to metric usage settings.
			'title' => $title, // The title of this page, to be passed to Evaluate for logging.
			'url'   => $url,   // The url which will be used to identify this context
			'id'    => $id,    // The wordpress ID associated with this context, for more information.
		) );
	}

	/**
	 * Get the context in which a metric should be rendered.
	 * @filter the_content, comment_text
	 * @param $content existing rendered content from the_content or comment_text filters.
	 */
	public static function render_metrics( $content ) {
		// Get the usage settings.
		$usage_settings = Evaluate_Settings::get_usage();
		// Get the current context.
		$context = self::get_context( current_filter() );

		// Loop through the usage settings metric by metric.
		foreach ( $usage_settings as $metric_id => $usage ) {
			// If the context is 'comment', then we can consider the 'comment_attached' special case.
			if ( $context['type'] == 'comment' && in_array( 'comment_attached', $usage ) ) {
				// If this special case is valid, then we want to embed a preview of the user's vote on the comment's parent context.

				// Retrieve the parent context.
				$comment = get_comment( $context['id'] );
				$parent_id = $comment->comment_post_ID;
				$parent_context = self::get_context( 'the_content', null, $parent_id );

				// Check if the metric is valid in the parent context.
				if ( in_array( $parent_context['type'], $usage ) ) {
					// If so render it.
					$content .= self::render_metric( $metric_id, $parent_context, true );
					continue; // If we success, skip to the next metric, so that we don't render the same metric twice.
				}
			}

			// Check if the metric is valid in the current context.
			if ( in_array( $context['type'], $usage ) ) {
				// If so render it.
				$content .= self::render_metric( $metric_id, $context );
			}
		}

		return $content;	
	}

	/**
	 * Render an individual metric.
	 * @param $metric_id the id of the metric to render
	 * @param $context the context in which we are rendering the metric.
	 * @param $preview if true, the user will only be able to see, but not vote on this metric.
	 */
	public static function render_metric( $metric_id, $context, $preview = false ) {
		// Check if the metric_id is valid.
		if ( empty( $metric_id ) ) {
			return;
		}

		// Get the user identifier
		$user_id = self::get_user_id();

		// Get the usage settings for this metric.
		$usage = Evaluate_Settings::get_usage( $metric_id );

		// Check if the user is allowed to see this metric.
		if ( in_array( 'admins_only', $usage ) && ! current_user_can( 'evaluate_vote_everywhere' ) ) {
			return;
		}

		// Check if the user is allowed to vote
		if ( ! ( is_user_logged_in() && current_user_can( 'evaluate_vote' ) ) && ! in_array( 'anonymous', $usage ) ) {
			$preview = true;
		}

		ob_start();

		// Print out this metric.
		Evaluate_Connector::print_frame( "/embed", array(
			'metric_id'  => $metric_id,
			'user_id'    => $user_id,
			'context_id' => $context['url'],
			'stylesheet' => Evaluate_Settings::get_settings( 'stylesheet_url' ),
			'preview'    => $preview ? 'preview' : '',
			'lrs'        => array( // This data is sent to Evaluate's LRS if one is configured.
				'username' => is_user_logged_in() ? wp_get_current_user()->display_name : $user_id,
				'homeurl'  => get_site_url(),
				'activity_name' => $context['title'],
				'activity_description' => $context['type'],
			),
		) );

		return ob_get_clean();
	}

	/**
	 * Render the 'evaluate' shortcode
	 * @shortcode evaluate
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'metric'  => null,
			'type'    => null,
			'context' => null,
		), $atts, 'evaluate' );

		// Get the usage for this metric.
		$usage = Evaluate_Settings::get_usage( $atts['metric'] );

		ob_start();

		if ( empty( $atts['metric'] ) ) {
			// If the metric id is not defined, render an error.
			?>
			<div class="notice notice-error">
				<p>No metric ID was supplied for this shortcode!</p>
			</div>
			<?php
		} else if ( ! in_array( 'shortcodes', $usage ) ) {
			// If this metric is not allowed as a shortcode, render a warning.
			?>
			<div class="notice notice-warning">
				<p>Metric #<?php echo $metric_id; ?> cannot be rendered as a shortcode.</p>
				<p>You can edit this setting <a href="/wp-admin/admin.php?page=evaluate_metrics">here</a>.</p>
			</div>
			<?php
		}

		$error = ob_get_clean();

		if ( empty( $error ) ) {
			// If all went well, render the metric.
			$context = self::get_context( current_filter(), $atts['type'], $atts['context'] );
			return self::render_metric( $atts['metric'], $context );
		} else {
			// Otherwise an error occurred.
			// If the user can control rendering of metrics, show them the error, otherwise fail silently.
			return current_user_can( 'evaluate_display_metrics' ) ? $error : "";
		}
	}

}

add_action( 'init', array( 'Evaluate_Display', 'init' ) );
