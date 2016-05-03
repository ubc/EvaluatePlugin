<?php

/**
 * This class forces the user to fill out the form, by redirecting them if they haven't already filled it out.
 */
class Evaluate_Display {

	private static $usage_settings = false;
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
			if ( is_user_logged_in() ) {
				self::$user_id = get_current_user_id();
			} else if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				//check ip from share internet
				self::$user_id = $_SERVER['HTTP_CLIENT_IP'];
			} else if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				//to check ip is pass from proxy
				self::$user_id = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				self::$user_id = $_SERVER['REMOTE_ADDR'];
			}
		}

		return self::$user_id;
	}

	public static function get_context( $filter, $force_type = null, $force_id = null ) {
		if ( ( 'comment_text' === $filter && $force_type == null ) || $force_type == 'comment' ) {
			$id    = empty( $force_id ) ? get_comment_ID() : $force_id;
			$type  = 'comment';
			$title = get_comment( $id )->comment_author . "'s comment";
			$url   = get_comment_link();
		} else if ( ( 'comment_text' !== $filter && get_the_ID() != false && $force_type == null ) || $force_type == 'post' ) {
			$id    = empty( $force_id ) ? get_the_ID() : $force_id;
			$type  = get_post_type( $id );
			$title = get_the_title( $id );
			$url   = get_the_permalink( $id );
		} else {
			$type  = empty( $force_type ) ? 'webpage' : $force_type;
			$title = wp_title( "", false );
			$url   = empty( $force_id ) ? $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : $force_id;
		}

		return apply_filters( 'evaluate_get_context', array(
			'type'  => $type,
			'title' => $title,
			'url'   => $url,
			'id'    => $id,
		) );
	}

	public static function render_metrics( $content ) {
		$usage_settings = self::get_usage_settings();
		$context = self::get_context( current_filter() );

		foreach ( $usage_settings as $metric_id => $usage ) {
			if ( $context['type'] == 'comment' && in_array( 'comment_attached', $usage ) ) {
				$comment = get_comment( $context['id'] );
				$parent_id = $comment->comment_post_ID;
				$parent_context = self::get_context( 'the_content', null, $parent_id );

				if ( in_array( $parent_context['type'], $usage ) ) {
					$content .= self::render_metric( $metric_id, $parent_context, true );
					continue;
				}
			}

			if ( in_array( $context['type'], $usage ) ) {
				$content .= self::render_metric( $metric_id, $context );
			}
		}

		return $content;	
	}

	public static function render_metric( $metric_id, $context, $preview = false ) {
		$user_id = self::get_user_id();
		$usage = self::get_usage_settings( $metric_id );

		if ( ! is_user_logged_in() && ! in_array( 'anonymous', $usage ) ) {
			return;
		}

		if ( in_array( 'admin_only', $usage ) && ! current_user_can( 'evaluate_vote_everywhere' ) ) {
			return;
		}

		if ( empty( $metric_id ) ) {
			return;
		}

		ob_start();

		Evaluate_Connector::print_frame( "/embed", array(
			'metric_id'  => $metric_id,
			'user_id'    => $user_id,
			'context_id' => $context['url'],
			'stylesheet' => Evaluate_Settings::get_settings( 'stylesheet_url' ),
			'preview'    => $preview ? 'preview' : '',
			'lrs'        => array(
				'username' => is_user_logged_in() ? wp_get_current_user()->display_name : $user_id,
				'homeurl'  => get_site_url(),
				'activity_name' => $context['title'],
				'activity_description' => $context['type'],
			),
		) );

		return ob_get_clean();
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'metric'  => null,
			'type'    => null,
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
			$context = self::get_context( current_filter(), $atts['type'], $atts['context'] );
			return self::render_metric( $atts['metric'], $context );
		}
	}

}

add_action( 'init', array( 'Evaluate_Display', 'init' ) );
