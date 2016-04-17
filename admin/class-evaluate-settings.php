<?php

/**
 * This class registers and renders the form on each subsite,
 * where administrators can fill out the information that this plugin asks for.
 */

// TODO: Support Custom CSS
class Evaluate_Settings {
	// This slug is used for the admin page.
	public static $page_key = 'evaluate';
	// This slug is used for the settings group.
	public static $section_key = 'evaluate';
	// Used to store our plugin settings
	public static $settings_key = 'evaluate_settings';
	// The capability that a user needs to edit the form.
	public static $required_capability = 'manage_options';

	public static $api_key = 'api_key';
	public static $server = 'server';

	private static $permissions = array(
		'evaluate_display' => "Display Metrics",
		'evaluate_metrics' => "Edit Metrics",
		'evaluate_rubrics' => "Manage Rubrics",
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
		wp_register_style( 'evaluate-admin', Evaluate::$directory_url . 'admin/css/evaluate-admin.css' );
	}

	public static function register_setting() {
		register_setting( self::$section_key, self::$settings_key, array( __CLASS__, 'validate_settings' ) );
		add_settings_section( self::$section_key, 'Settings', array( __CLASS__, 'render_settings_description' ), self::$page_key );
		add_settings_field( "evaluate_server", "Server", array( __CLASS__, 'render_server' ), self::$page_key, self::$section_key );
		// TODO: Retrieve API Key automatically, using LTI and Server url.
		add_settings_field( "evaluate_api_key", "API Key", array( __CLASS__, 'render_api_key' ), self::$page_key, self::$section_key );
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
		wp_enqueue_style( 'evaluate-admin' );

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
		$options = get_option( self::$settings_key );
		$value = key_exists( self::$server, $options ) ? $options[ self::$server ] : "";

		ob_start();
		?>
		<input id="<?php echo self::$server; ?>" name="<?php echo self::$settings_key; ?>[<?php echo self::$server; ?>]" type="text" size="40" value="<?php echo $value; ?>"></input>
		<?php
		echo ob_get_clean();
	}

	public static function render_api_key() {
		$options = get_option( self::$settings_key );
		$value = key_exists( self::$api_key, $options ) ? $options[ self::$api_key ] : "";

		ob_start();
		?>
		<input id="<?php echo self::$api_key; ?>" type="text" size="40" disabled="disabled" value="<?php echo $value; ?>" placeholder="TODO: Implement Automatic API Key fetch."></input>
		<?php
		echo ob_get_clean();
	}

	public static function render_permissions() {
		$roles = get_editable_roles();

		ob_start();
		?>
		<table>
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
							$name = self::$settings_key . "[permissions][" . $slug . "][" . $permission . "]";
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
		echo ob_get_clean();
	}

	public static function validate_settings( $input ) {
		$result[ self::$server ] = untrailingslashit( trim( $input[ self::$server ] ) );

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

add_action( 'init', array( 'Evaluate_Settings', 'init' ) );
