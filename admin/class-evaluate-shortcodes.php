<?php

/**
 * This class forces the user to fill out the form, by redirecting them if they haven't already filled it out.
 */
class Evaluate_Shortcodes {
	
	/**
	 * @filter init
	 */
	public static function init() {
		add_shortcode( 'evaluate', array( __CLASS__, 'shortcode' ) );
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'metric' => null,
			'user'   => null,
			'domain' => null,
		), $atts, 'evaluate' );

		$options = get_option( Evaluate_Settings::$settings_key );
		$server = $options[ Evaluate_Settings::$server ];
		$api_key = $options[ Evaluate_Settings::$api_key ];

		$path = "/api/auth/" . $api_key;
		$data = array(
			'metric_id' => $atts['metric'],
			'user_id'   => $atts['user'],
			'domain_id' => $atts['domain'],
		);

		$transaction_id = Evaluate_Connector::post( $path, $data );

		if ( $transaction_id === FALSE ) {
			// TODO: Handle Error
			return "ERROR ON EVALUATE AUTH REQUEST";
		} else {
			ob_start();
			?>
			<iframe href="<?php echo $server . "/api/embed/" . $transaction_id; ?>"></iframe>
			<?php
			return ob_get_clean();
		}		
	}

}

add_action( 'init', array( 'Evaluate_Shortcodes', 'init' ) );
