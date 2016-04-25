<?php

/**
 * This class registers and renders the form on each subsite,
 * where administrators can fill out the information that this plugin asks for.
 */

class Evaluate_Manage {
	// This slug is used for the admin page.
	public static $page_key = 'evaluate';
	// This slug is used for the settings group.
	public static $section_key = 'evaluate';
	// The capability that a user needs to edit the form.
	public static $required_capability = 'manage_options';

	private static $permissions = array(
		'evaluate_display' => "Display Metrics",
		'evaluate_metrics' => "Edit Metrics",
		'evaluate_rubrics' => "Manage Rubrics",
		'evaluate_vote_everywhere' => "See Admin Only Metrics",
	);

	/**
	 * @filter init
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );

		if ( isset( $_GET['page'] ) && $_GET['page'] == self::$page_key ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts_and_styles' ), 5 );
		}
	}

	/**
	 * Register the JS script and CSS style that are necessary for this page.
	 * @filter admin_enqueue_scripts
	 */
	public static function register_scripts_and_styles() {
		wp_register_style( 'evaluate-manage', Evaluate::$directory_url . 'admin/css/evaluate-manage.css' );
	}

	public static function register_setting() {
		register_setting( self::$section_key, Evaluate_Settings::$settings_key, array( __CLASS__, 'validate_settings' ) );
		add_settings_section( self::$section_key, 'Settings', array( __CLASS__, 'render_settings_description' ), self::$page_key );
		
		add_settings_field( "evaluate_server", "Server", array( __CLASS__, 'render_server' ), self::$page_key, self::$section_key );
		// TODO: Retrieve API Key automatically, using LTI and Server url.
		add_settings_field( "evaluate_api_key", "API Key", array( __CLASS__, 'render_api_key' ), self::$page_key, self::$section_key );
		// TODO: These settings must be network admin only.
		add_settings_field( "evaluate_consumer_key", "Consumer Key", array( __CLASS__, 'render_consumer_key' ), self::$page_key, self::$section_key );
		add_settings_field( "evaluate_consumer_secret", "Consumer Secret", array( __CLASS__, 'render_consumer_secret' ), self::$page_key, self::$section_key );
		add_settings_field( "evaluate_stylesheet_url", "Stylesheet URL", array( __CLASS__, 'render_stylesheet_url' ), self::$page_key, self::$section_key );
		add_settings_field( "evaluate_anonymous", "Anonymous Voters", array( __CLASS__, 'render_allow_anonymous' ), self::$page_key, self::$section_key );
		add_settings_field( "evaluate_permissions", "Permissions", array( __CLASS__, 'render_permissions' ), self::$page_key, self::$section_key );
	}

	/**
	 * Define the network admin page.
	 * @filter network_admin_menu
	 */
	public static function add_page() {
		add_menu_page(
			"Evaluate", // Page title
			"Evaluate", // Menu title
			self::$required_capability, // Capability required to view this page.
			self::$page_key, // Page slug
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the form page.
	 */
	public static function render_page() {
		wp_enqueue_style( 'evaluate-manage' );

		?>
		<div class="wrap">
			<h2>Evaluate</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::$section_key );
				do_settings_sections( self::$section_key );
				submit_button( "Save" );
				?>
			</form>
		</div>
		<?php
	}

	public static function render_settings_description() {
		// Do Nothing
	}

	public static function render_server() {
		$value = Evaluate_Settings::get_settings( 'server' );
		?>
		<input id="server" name="<?php echo Evaluate_Settings::$settings_key; ?>[server]" type="text" size="40" value="<?php echo $value; ?>"></input>
		<?php
	}

	public static function render_api_key() {
		$value = Evaluate_Settings::get_settings( 'api_key' );
		?>
		<input id="api_key" type="text" size="40" disabled="disabled" value="<?php echo $value; ?>" placeholder="TODO: Implement Automatic API Key fetch."></input>
		<?php
	}

	public static function render_consumer_key() {
		$value = Evaluate_Settings::get_settings( 'consumer_key' );
		?>
		<input id="consumer_key" name="<?php echo Evaluate_Settings::$settings_key; ?>[consumer_key]" type="text" size="40" value="<?php echo $value; ?>"></input>
		<?php
	}

	public static function render_consumer_secret() {
		$value = Evaluate_Settings::get_settings( 'consumer_secret' );
		?>
		<input id="consumer_secret" name="<?php echo Evaluate_Settings::$settings_key; ?>[consumer_secret]" type="text" size="40" value="<?php echo $value; ?>"></input>
		<?php
	}

	public static function render_stylesheet_url() {
		$value = Evaluate_Settings::get_settings( 'stylesheet_url' );
		?>
		<input id="stylesheet_url" name="<?php echo Evaluate_Settings::$settings_key; ?>[stylesheet_url]" type="text" size="40" value="<?php echo $value; ?>"></input>
		<?php
	}

	public static function render_allow_anonymous() {
		$value = Evaluate_Settings::get_settings( 'allow_anonymous' );
		?>
		<input id="allow_anonymous" name="<?php echo Evaluate_Settings::$settings_key; ?>[allow_anonymous]" type="checkbox" value="on" <?php checked( $value, "on" ); ?>></input> Allowed By Default
		<?php
	}

	public static function render_permissions() {
		$roles = get_editable_roles();

		?>
		<table id="permissions">
			<thead>
				<tr>
					<th></th>
					<?php
					foreach ( self::$permissions as $value => $title ) {
						?>
						<th><?php echo $title; ?></th>
						<?php
					}
					?>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $roles as $slug => $info ) {
					?>
					<tr>
						<th><?php echo $info['name']; ?></th>
						<?php
						foreach ( self::$permissions as $permission => $title ) {
							$name = Evaluate_Settings::$settings_key . "[permissions][" . $slug . "][" . $permission . "]";
							?>
							<td>
								<input type="checkbox" name="<?php echo $name; ?>" value="on" <?php checked( ! empty( $info['capabilities'][ $permission ] ) ); ?>>
							</td>
							<?php
						}
						?>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}

	public static function validate_settings( $input ) {
		$result = shortcode_atts( array(
			'server'          => "",
			'api_key'         => "",
			'consumer_key'    => "",
			'consumer_secret' => "",
			'stylesheet_url'  => "",
			'allow_anonymous' => "",
		), $input );

		$result[ 'server' ] = untrailingslashit( trim( $input[ 'server' ] ) );

		$permissions = empty ( $input['permissions'] ) ? array() : $input['permissions'];
		self::set_permissions( $permissions );		

		return $result;
	}

	public static function set_permissions( $permissions ) {
		$roles = get_editable_roles();

		foreach ( $roles as $slug => $info ) {
			$role = get_role( $slug );

			foreach ( self::$permissions as $permission => $name ) {
				$role->add_cap( $permission, ! empty( $permissions[ $slug ][ $permission ] ) );
			}
		}
	}

}

add_action( 'init', array( 'Evaluate_Manage', 'init' ) );
