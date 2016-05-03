<?php

/**
 * This class is responsible for connecting to the Evaluate Server.
 * It abstracts the process of authentication and embedding.
 */

class Evaluate_Connector {

	/**
	 * A generic request to the Evaluate Server
	 * @param $path the relative path at which to make the request.
	 * @param $data the data which should be sent to the server.
	 * @param $type either "POST" or "GET"
	 * @return the server's response
	 */
	private static function request( $path, $data, $type = "POST" ) {
		// Construct the request url.
		$url = Evaluate_Settings::get_settings( 'server' ) . $path;
		// Build an http query from the data.
		$query = http_build_query( $data );
		// Response is null by default
		$response = null;

		if ( $type == "POST" ) {
			// Create the context for the POST request, including embedded data.
			$context = stream_context_create( array(
				'http' => array(
					'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
					'method'  => "POST",
					'content' => $query,
				),
			) );

			// Open a connection to the server. Errors are suppressed using '@'
			$file = @fopen( $url, 'rb', false, $context );

			if ( $file ) {
				// Save the server's response.
				$response = stream_get_contents( $file );
			} else {
				// If the connection is null, then display an error.
				?>
				<div class="notice notice-error">
					<p>Could not contact server: <a href="<?php echo $url; ?>"><?php echo $url; ?></a></p>
				</div>
				<?php
			}
		} else if ( $type == "GET" ) {
			// A simple get request to the server. Errors are suppressed using '@'
			$response = @file_get_contents( $url . "?" . $query );
		}

		return $response;
	}

	/**
	 * Retrieve data from the server using a GET request.
	 * @param $path the relative path which should be requested from the server.
	 * @param $data the parameters to pass to the server - if any.
	 * @return the server's response.
	 */
	public static function get_data( $path, $data = array() ) {
		$path .= "/" . Evaluate_Settings::get_settings( 'api_key' );
		return self::request( $path, $data, "GET" );
	}

	/**
	 * Request authority from the server to perform a specific action.
	 * Confirmation of that authority is returned as a Transaction ID.
	 * @param $path the relative path which should be requested from the server.
	 * @param $data the parameters to pass to the server - if any.
	 * @return a transaction ID corresponding to the given path.
	 */
	private static function generate_transaction_id( $path, $data ) {
		// Make a request to Evaluate's /auth path to get the Transaction ID.
		return self::get_data( "/auth", array(
			"path"    => $path,
			"payload" => $data,
		) );
	}

	/**
	 * Perform an action on the server using a POST request.
	 * @param $path the relative path which should be requested from the server.
	 * @param $data the parameters to pass to the server.
	 */
	public static function do_action( $path, $data ) {
		$transaction_id = self::generate_transaction_id( $path, $data );
		$path .= "/" . $transaction_id;
		return self::request( $path, $data, "POST" );
	}

	/**
	 * Print an iframe of a page on the Evaluate Server.
	 * This is typically used to Render a metric, or Evaluate's built-in editors.
	 * @param $path the relative path which should be requested from the server.
	 * @param $data the parameters to pass to the server.
	 */
	public static function print_frame( $path, $data ) {
		// Get the transaction ID for this iframe.
		$transaction_id = self::generate_transaction_id( $path, $data );
		// Construct the URL.
		$url = Evaluate_Settings::get_settings( 'server' ) . $path . "/" . $transaction_id;

		if ( is_admin() ) {
			// For admin pages, make the iframe have full width.
			// TODO: Probably this should be moved into a CSS file.
			?>
			<iframe src="<?php echo $url; ?>" style="width: 100%; min-height: 600px;"></iframe>
			<?php
		} else {
			?>
			<iframe src="<?php echo $url; ?>"></iframe>
			<?php
		}
	}
}
