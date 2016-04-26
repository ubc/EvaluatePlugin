<?php

/**
 * This class forces the user to fill out the form, by redirecting them if they haven't already filled it out.
 */
class Evaluate_Display {

	private static $usage_settings = false;
	private static $is_user_anonymous = null;
	private static $user_id = false;
	private static $context = false;
	
	/**
	 * @filter init
	 */
	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'render_metrics' ), 100 );
		add_filter( 'comment_text', array( __CLASS__, 'render_metrics' ), 100 );
		add_shortcode( 'evaluate', array( __CLASS__, 'shortcode' ) );
	}

	public static function get_usage_settings( $metric_id = null ) {
		if ( self::$usage_settings === false ) {
			self::$usage_settings = get_option( 'evaluate_usage', array() );
		}

		if ( $metric_id == null ) {
			return self::$usage_settings;
		} else {
			return self::$usage_settings[ $metric_id ];
		}
	}

	public static function get_user_id() {
		if ( self::$user_id === false ) {
			self::$user_id = get_current_user_id();
			self::$is_user_anonymous = empty( self::$user_id );

			if ( empty( self::$user_id ) ) {
				if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
					//check ip from share internet
					self::$user_id = $_SERVER['HTTP_CLIENT_IP'];
				} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
					//to check ip is pass from proxy
					self::$user_id = $_SERVER['HTTP_X_FORWARDED_FOR'];
				} else {
					self::$user_id = $_SERVER['REMOTE_ADDR'];
				}
			}
		}

		return self::$user_id;
	}

	public static function get_context() {
		if ( self::$context === false ) {
			if ( 'comment_text' === current_filter() ) {
				// TODO: Support the 'comments_attached' usage setting.
				$context_type = 'comments';
				$context_id = get_comment_ID();
			} else {
				$context_type = get_post_type();
				$context_id = get_the_ID();
			}

			self::$context = apply_filters( 'evaluate_get_context', array(
				'type' => $context_type,
				'id'   => $context_id,
			) );
		}

		return self::$context;
	}

	public static function render_metrics( $content ) {
		$usage_settings = self::get_usage_settings();
		$context = self::get_context();

		foreach ( $usage_settings as $metric_id => $usage ) {
			if ( in_array( $context['type'], $usage ) ) {
				$content .= self::render_metric( $metric_id );
			}
		}

		return $content;	
	}

	public static function render_metric( $metric_id, $context_id = null ) {
		$user_id = self::get_user_id();
		$usage = self::get_usage_settings( $metric_id );

		if ( self::$is_user_anonymous && ! in_array( 'anonymous', $usage ) ) {
			return "anon<br>";
		}

		if ( in_array( 'admin_only', $usage ) && ! current_user_can( 'evaluate_vote_everywhere' ) ) {
			return "admin<br>";
		}

		if ( empty( $metric_id ) ) {
			return 'no ID<br>';
		}

		if ( empty( $context_id ) ) {
			$context_id = self::get_context()['id'];
		}

		ob_start();

		Evaluate_Connector::print_frame( "/embed", array(
			'metric_id'  => $metric_id,
			'user_id'    => $user_id,
			'context_id' => $context_id,
			'stylesheet' => Evaluate_Settings::get_settings( 'stylesheet_url' ),
		) );

		return ob_get_clean();
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'metric'  => null,
			'context' => null,
		), $atts, 'evaluate' );

		$usage = self::get_usage_settings( $atts['metric'] );

		if ( empty( $atts['metric'] ) ) {
			ob_start();
			?>
			<div class="notice notice-error">
				<p>No metric ID was supplied for this shortcode!</p>
			</div>
			<?php
			$error = ob_get_clean();
		} else if ( ! in_array( 'shortcodes', $usage ) ) {
			ob_start();
			?>
			<div class="notice notice-warning">
				<p>Metric #<?php echo $metric_id; ?> cannot be rendered as a shortcode.</p>
				<p>You can edit this setting <a href="/wp-admin/admin.php?page=evaluate_metrics">here</a>.</p>
			</div>
			<?php
			$error = ob_get_clean();
		}

		if ( ! empty( $error ) ) {
			return current_user_can( 'evaluate_display' ) ? $error : "";
		} else {
			return self::render_metric( $atts['metric'], $atts['context'] );
		}
	}

}

add_action( 'init', array( 'Evaluate_Display', 'init' ) );
