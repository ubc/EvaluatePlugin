<?php

class Evaluate_Connector {

	private static $server;
	private static $lti_key;
	private static $lti_secret;
	private static $launch_data = array(
		"user_id" => "292832126",
		"roles" => "Instructor",
		"resource_link_id" => "120988f929-274612",
		"resource_link_title" => "Weekly Blog",
		"resource_link_description" => "A weekly blog.",
		"lis_person_name_full" => "Jane Q. Public",
		"lis_person_name_family" => "Public",
		"lis_person_name_given" => "Given",
		"lis_person_contact_email_primary" => "user@school.edu",
		"lis_person_sourcedid" => "school.edu:user",
		"context_id" => "456434513",
		"context_title" => "Design of Personal Environments",
		"context_label" => "SI182",
		"tool_consumer_instance_guid" => "lmsng.school.edu",
		"tool_consumer_instance_description" => "University of School (LMSng)",
		"launch_presentation_document_target" => "iframe",
	);

	public static function init() {
		$options = get_option( Evaluate_Settings::$settings_key );
		self::$server = $options[ Evaluate_Settings::$server ];
		self::$lti_key = "testconsumerkey";
		self::$lti_secret = "testconsumersecret";
	}

	private static function get_launch_data( $launch_url ) {
		$now = new DateTime();
		$launch_data = self::$launch_data;
		$launch_data["lti_version"] = "LTI-1p0";
		$launch_data["lti_message_type"] = "basic-lti-launch-request";

		# Basic LTI uses OAuth to sign requests
		# OAuth Core 1.0 spec: http://oauth.net/core/1.0/
		$launch_data["oauth_callback"] = "about:blank";
		$launch_data["oauth_consumer_key"] = self::$lti_key;
		$launch_data["oauth_version"] = "1.0";
		$launch_data["oauth_nonce"] = uniqid('', true);
		$launch_data["oauth_timestamp"] = $now->getTimestamp();
		$launch_data["oauth_signature_method"] = "HMAC-SHA1";

		# In OAuth, request parameters must be sorted by name
		$launch_data_keys = array_keys( $launch_data );
		$launch_params = array();

		sort( $launch_data_keys );

		foreach ( $launch_data_keys as $key ) {
			array_push( $launch_params, $key . "=" . rawurlencode( $launch_data[ $key ] ) );
		}

		$base_string = "POST&" . urlencode( $launch_url ) . "&" . rawurlencode( implode( "&", $launch_params ) );
		$secret = urlencode( self::$lti_secret ) . "&";
		$launch_data['oauth_signature'] = base64_encode( hash_hmac( "sha1", $base_string, $secret, true ) );

		return $launch_data;
	}

	public static function print_frame( $path, $auth = false ) {
		$url = self::$server . $path;

		if ( $auth ) {
			$launch_data = self::get_launch_data( $url );

			?>
			<div id="ltiLaunchDiv">
				<form id="ltiLaunchForm" target="ltiLaunch" method="POST" action="<?php echo $url; ?>">
					<?php
					foreach ( $launch_data as $k => $v ) {
						?>
						<input type="hidden" name="<?php echo $k ?>" value="<?php echo $v ?>"></input>
						<?php
					}
					?>
				</form>
				<script>
					window.onload = function() {
						console.log('submit');
						document.getElementById("ltiLaunchForm").submit();
						document.getElementById("ltiLaunchDiv").remove();
					};
				</script>
			</div>

			<iframe name="ltiLaunch" src="" style="width: 100%; height: 400px;"></iframe>
			<?php
		} else {
			?>
			<iframe src="<?php echo $url; ?>" style="width: 100%; height: 400px;"></iframe>
			<?php
		}
	}

	public static function post( $path, $data ) {
		$url = self::$server . $path;

		$context = stream_context_create( array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query( $data ),
			),
		) );

		//return file_get_contents( $url, false, $context );

		$file = fopen( $url, 'rb', false, $context );
		$response = stream_get_contents( $file );
		return $response;
	}
}

add_action( 'init', array( 'Evaluate_Connector', 'init' ) );
