<?php

class Evaluate_Connector {

	private static function request( $path, $data, $type = "POST" ) {
		$url = Evaluate_Settings::get_settings( 'server' ) . $path;
		$query = http_build_query( $data );

		if ( $type == "POST" ) {
			$context = stream_context_create( array(
				'http' => array(
					'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
					'method'  => "POST",
					'content' => $query,
				),
			) );

			$file = @fopen( $url, 'rb', false, $context );

			if ( $file ) {
				$response = stream_get_contents( $file );
			} else {
				?>
				<div class="notice notice-error">
					<p>Could not contact server: <a href="<?php echo $url; ?>"><?php echo $url; ?></a></p>
				</div>
				<?php
				$response = null;
			}
		} else {
			$response = @file_get_contents( $url . "?" . $query );
		}

		return $response;
	}

	public static function get_data( $path, $data = array() ) {
		$path .= "/" . Evaluate_Settings::get_settings( 'api_key' );
		return self::request( $path, $data, "GET" );
	}

	public static function do_action( $path, $data ) {
		$transaction_id = self::generate_transaction_id( $path, $data );
		$path .= "/" . $transaction_id;
		return self::request( $path, $data, "POST" );
	}

	private static function generate_transaction_id( $path, $data ) {
		return self::get_data( "/auth", array(
			"path"    => $path,
			"payload" => $data,
		) );
	}

	public static function print_frame( $path, $data ) {
		$transaction_id = self::generate_transaction_id( $path, $data );
		$url = Evaluate_Settings::get_settings( 'server' ) . $path . "/" . $transaction_id;

		if ( is_admin() ) {
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
