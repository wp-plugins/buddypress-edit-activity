<?php
/**
 * @package WordPress
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BuddyBoss_Edit_Activity_Admin' ) ):
	
/**
 *
 * BuddyBoss Edit Activity Admin
 * ********************
 *
 *
 */
class BuddyBoss_Edit_Activity_Admin{
	/**
	 * Plugin options
	 *
	 * @var array
	 */
	public $options = array();
	
	/**
	 * Empty constructor function to ensure a single instance
	 */
	public function __construct(){
		// ... leave empty, see Singleton below
	}


	/* Singleton
	 * ===================================================================
	 */

	/**
	 * Admin singleton
	 *
	 * @param  array  $options [description]
	 *
	 * @return object Admin class
	 */
	public static function instance(){
		static $instance = null;

		if ( null === $instance )
		{
			$instance = new BuddyBoss_Edit_Activity_Admin();
			$instance->setup();
		}

		return $instance;
	}
	
	/**
	 * Get option
	 *
	 * @param  string $key Option key
	 *
	 * @return mixed      Option value
	 */
	public function option( $key ){
		$value = buddyboss_edit_activity()->option( $key );
		return $value;
	}
	
	/**
	 * Setup admin class
	 */
	public function setup(){
		if ( ( ! is_admin() && ! is_network_admin() ) || ! current_user_can( 'manage_options' ) ){
			return;
		}

		$actions = array(
			'admin_init',
			'admin_menu',
			'network_admin_menu'
		);

		foreach( $actions as $action ){
			add_action( $action, array( $this, $action ) );
		}
	}
	
	/**
	 * Register admin settings
	 */
	public function admin_init(){
		register_setting( 'b_e_a_plugin_options', 'b_e_a_plugin_options', array( $this, 'plugin_options_validate' ) );
		add_settings_section( 'general_section', __( 'Front-end Editing Settings', 'buddypress-edit-activity' ), array( $this, 'section_general' ), __FILE__ );

		add_settings_field( 'user_access', __( 'Who can edit activity', 'buddypress-edit-activity' ), array( $this, 'setting_user_access' ), __FILE__, 'general_section');
		add_settings_field( 'editable_types', __( 'Editable on front-end', 'buddypress-edit-activity' ), array( $this, 'setting_editable_types' ), __FILE__, 'general_section');
		add_settings_field( 'editable_timeout', __( 'Disallow editing after', 'buddypress-edit-activity' ), array( $this, 'setting_editable_timeout' ), __FILE__, 'general_section');
		add_settings_field( 'exclude_admins', '', array( $this, 'setting_exclude_admins' ), __FILE__, 'general_section');
	}
	
	/**
	 * General settings section
	 */
	public function section_general(){

	}
	
	/**
	 * Add plugin settings page
	 */
	public function admin_menu(){
		add_options_page( 'BP Edit Activity', 'BP Edit Activity', 'manage_options', __FILE__, array( $this, 'options_page' ) );
	}
	
	/**
	 * Add plugin settings page
	 */
	public function network_admin_menu(){
		return $this->admin_menu();
	}
	
	/**
	 * Render settings page
	 */
	public function options_page(){
		?>
		<div class="wrap">
			<h2><?php _e( 'Buddypress Edit Activity' , 'buddypress-edit-activity' ) ; ?></h2>
				<div class="updated fade">
					<p><?php _e( 'Need BuddyPress customizations?', 'buddypress-edit-activity' ); ?>  &nbsp;<a href="http://buddyboss.com/buddypress-developers/" target="_blank"><?php _e( 'Say hello.', 'buddypress-edit-activity' ); ?></a></p>
				</div>
			<form action="options.php" method="post">
			<?php settings_fields( 'b_e_a_plugin_options' ); ?>
			<?php do_settings_sections( __FILE__ ); ?>

			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
			</p>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Validate plugin option
	 */
	public function plugin_options_validate( $input ){
		$editable_timeout = (int)sanitize_text_field( $input['editable_timeout'] );
		$input['editable_timeout'] = $editable_timeout;
		
		if( !isset( $input['exclude_admins'] ) || !$input['exclude_admins'] )
			$input['exclude_admins'] = 'no';

		return $input; // return validated input
	}
	
	/**
	 * Setting > user_access
	 */
	public function setting_user_access(){
		$user_access = $this->option( 'user_access' );
		if( !$user_access ){
			$user_access = 'author';
		}
		
		$options = array(
			'admin'		=> __( 'Admin only', 'buddypress-edit-activity' ),
			'author'	=> __( 'Admin and user who created the post', 'buddypress-edit-activity' )
		);
		foreach( $options as $option=>$label ){
			$checked = $user_access == $option ? ' checked' : '';
			echo '<label><input type="radio" name="b_e_a_plugin_options[user_access]" value="'. $option . '" '. $checked . '>' . $label . '</label>&nbsp;&nbsp;';
		}
	}
	
	/**
	 * Setting > editable_types
	 */
	public function setting_editable_types(){
		$editable_types = $this->option( 'editable_types' );
		if( !$editable_types ){
			$editable_types = array( 'activity_update' );
		}
		
		$options = array(
			'activity_update'	=> __( 'Activity Posts', 'buddypress-edit-activity' ),
			'activity_comment'	=> __( 'Activity Replies', 'buddypress-edit-activity' )
		);
		foreach( $options as $option=>$label ){
			$checked = in_array( $option, $editable_types ) ? ' checked' : '';
			echo '<label><input type="checkbox" name="b_e_a_plugin_options[editable_types][]" value="'. $option . '" '. $checked . '>' . $label . '</label>&nbsp;&nbsp;';
		}
	}
	
	/**
	 * Setting > editable_timeout
	 */
	public function setting_editable_timeout(){
		$editable_timeout = $this->option( 'editable_timeout' );

		echo "<input id='editable_timeout' name='b_e_a_plugin_options[editable_timeout]' type='text' class='small-text' value='" . esc_attr( $editable_timeout ) . "' />";
		echo '<label for="b_e_a_plugin_options[editable_timeout]">' . __( ' minutes', 'buddypress-edit-activity' ) . '</label>';
		echo '<p class="description">' . __( 'Leave at 0 to set no time limit ', 'buddypress-edit-activity' ) . '</p>';
	}
	
	/**
	 * Setting > exclude_admins
	 */
	public function setting_exclude_admins(){
		$exclude_admins = $this->option( 'exclude_admins' );
		$checked = $exclude_admins=='yes' ? ' checked' : '';
		echo '<label><input type="checkbox" name="b_e_a_plugin_options[exclude_admins]" value="yes" '. $checked . '>' . __( 'Exclude admins from time limit.', 'buddypress-edit-activity' ) . '</label>';
	}
}

endif;