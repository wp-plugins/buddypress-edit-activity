<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'BuddyBoss_Edit_Activity' ) ):
/**
 *
 * BuddyBoss Edit Activity Plugin Main Controller
 * **************************************
 *
 *
 */
class BuddyBoss_Edit_Activity {
	/**
	 * Default options for the plugin, the strings are
	 * run through localization functions during instantiation,
	 * and after the user saves options the first time they
	 * are loaded from the DB.
	 *
	 * @var array
	 */
	private $default_options = array(
		'user_access'		=> 'author',//whether only admin can edit an activity or the activity's original author as well
		'editable_types'	=> array( 'activity_comment', 'activity_update' ),//what can be edited
		'editable_timeout'	=> false,//how long after posting, the activity is editable? always editable by default
		'exclude_admins'	=> 'yes',//whether admins are excluded from timeout limitation and can always edit activity.
	);
	
	/**
	 * This options array is setup during class instantiation, holds
	 * default and saved options for the plugin.
	 *
	 * @var array
	 */
	public $options = array();
	
	/**
	 * Main BuddyBoss Edit Activity Instance.
	 *
	 * Insures that only one instance of this class exists in memory at any
	 * one time. Also prevents needing to define globals all over the place.
	 *
	 * @since BuddyBoss Edit Activity (1.0.0)
	 *
	 * @static object $instance
	 * @uses BuddyBoss_Edit_Activity::setup_actions() Setup the hooks and actions.
	 * @uses BuddyBoss_Edit_Activity::setup_textdomain() Setup the plugin's language file.
	 * @see buddyboss_edit_activity()
	 *
	 * @return object BuddyBoss_Edit_Activity
	 */
	public static function instance(){
		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been run previously
		if ( null === $instance )
		{
			$instance = new BuddyBoss_Edit_Activity();
			$instance->setup_globals();
			$instance->setup_actions();
			$instance->setup_textdomain();
		}

		// Always return the instance
		return $instance;
	}
	
	/* Magic Methods
	 * ===================================================================
	 */
	private function __construct() { /* Do nothing here */ }

	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddyboss-media' ), '1.7' ); }

	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'buddyboss-media' ), '1.7' ); }

	public function __isset( $key ) { return isset( $this->data[$key] ); }

	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	public function __set( $key, $value ) { $this->data[$key] = $value; }

	public function __unset( $key ) { if ( isset( $this->data[$key] ) ) unset( $this->data[$key] ); }

	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }
	
	/**
	 * Setup BuddyBoss Edit Activity plugin global variables.
	 *
	 * @since BuddyBoss Edit Activity (1.0.0)
	 * @access private
	 */
	private function setup_globals(){
		// DEFAULT CONFIGURATION OPTIONS
		$default_options = $this->default_options;

		$saved_options = get_option( 'b_e_a_plugin_options' );
		$saved_options = maybe_unserialize( $saved_options );

		$this->options = wp_parse_args( $saved_options, $default_options );
	}
	
	private function setup_actions(){
		if ( ( is_admin() || is_network_admin() ) && current_user_can( 'manage_options' ) ){
			$this->load_admin();
		}
		
		// Hook into BuddyPress init
		add_action( 'bp_loaded', array( $this, 'bp_loaded' ) );
	}
	
	/**
	 * Include required admin files.
	 *
	 * @since BuddyBoss Edit Activity (1.0.0)
	 * @access private
	 */
	private function load_admin(){
		require_once( BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_DIR . 'includes/admin.php' );

		$this->admin = BuddyBoss_Edit_Activity_Admin::instance();
	}
	
	/**
	 * Load plugin text domain
	 *
	 * @since BuddyBoss Edit Activity (1.0.0)
	 *
	 * @uses sprintf() Format .mo file
	 * @uses get_locale() Get language
	 * @uses file_exists() Check for language file
	 * @uses load_textdomain() Load language file
	 */
	public function setup_textdomain(){
		$domain = 'buddypress-edit-activity';
		$locale = apply_filters('plugin_locale', get_locale(), $domain);
		
		//first try to load from wp-contents/languages/plugins/ directory
		load_textdomain($domain, WP_LANG_DIR.'/plugins/'.$domain.'-'.$locale.'.mo');
		
		//if not found, then load from buddypress-edit-activity/languages/ directory
		load_plugin_textdomain( $domain, false, 'buddypress-edit-activity/languages' );
	}
	
	/**
	 * We require BuddyPress to run the main components, so we attach
	 * to the 'bp_loaded' action which BuddyPress calls after it's started
	 * up. This ensures any BuddyPress related code is only loaded
	 * when BuddyPress is active.
	 *
	 * @since BuddyBoss Edit Activity (1.0.0)
	 *
	 * @return void
	 */
	public function bp_loaded(){
		add_action( 'bp_activity_entry_meta',		array( $this, 'btn_edit_activity' ) );
		add_action( 'bp_activity_comment_options',	array( $this, 'btn_edit_activity_comment' ) );
		
		if ( ! is_admin() && ! is_network_admin() ){
			add_action( 'wp_enqueue_scripts',	array( $this, 'assets' ) );
			add_action( 'wp_footer',			array( $this, 'print_edit_activity_template' ) );
		}
		
		add_action( 'wp_ajax_buddypress-edit-activity-get', array( $this, 'ajax_get_activity_content' ) );
		add_action( 'wp_ajax_buddypress-edit-activity-save', array( $this, 'ajax_save_activity_content' ) );
	}
	
	public function assets(){
		$assets_url = trailingslashit( BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_URL ) . 'assets/';
		//wp_enqueue_script( 'buddyboss-edit-activity', $assets_url . 'js/buddypress-edit-activity.js', array('jquery'), '1.0.0', true );
		wp_enqueue_script( 'buddyboss-edit-activity', $assets_url . 'js/buddypress-edit-activity.min.js', array('jquery'), '1.0.0', true );

		add_action('wp_head', 'b_e_a_inline_styles');
		function b_e_a_inline_styles() {
		    echo '
		        <style>
					#buddypress div.activity-comments form#frm_buddypress-edit-activity .ac-textarea {
						margin: 20px 10px 5px;
					}
		        </style>';        
		}
		
		$data = array(
			'loading_bar_url'	=> home_url( '/wp-includes/js/thickbox/loadingAnimation.gif' ),
			'button_text'		=> array(
				'edit'			=> __( 'Edit', 'buddypress-edit-activity' ),
				'save'			=> __( 'Save', 'buddypress-edit-activity' ),
			),
		);
		
		wp_localize_script( 'buddyboss-edit-activity', 'B_E_A_', $data );
	}
	
	/**
	 * Convenience function to access plugin options, returns false by default
	 */
	public function option( $key ){
		$key    = strtolower( $key );
		$option = isset( $this->options[$key] )
		        ? $this->options[$key]
		        : null;

		// Apply filters on options as they're called for maximum
		// flexibility. Options are are also run through a filter on
		// class instatiation/load.
		// ------------------------

		// This filter is run for every option
		$option = apply_filters( 'b_e_a_plugin_option', $option );

		// Option specific filter name is converted to lowercase
		$filter_name = sprintf( 'b_e_a_plugin_option_%s', strtolower( $key  ) );
		$option = apply_filters( $filter_name,  $option );

		return $option;
	}
	
	public function print_edit_activity_template(){
		?>
		<div id="buddypress-edit-activity-wrapper" style="display:none">
			<form id="frm_buddypress-edit-activity" method="POST" onsubmit="return false;">
				<input type="hidden" name="action_get" value="buddypress-edit-activity-get" >
				<input type="hidden" name="action_save" value="buddypress-edit-activity-save" >
				<input type="hidden" name="buddypress_edit_activity_nonce" value="<?php echo wp_create_nonce( 'buddypress-edit-activity');?>" >
				<input type="hidden" name="activity_id" value="">
				<div class="field ac-textarea">
					<textarea name="activity_content"></textarea>
				</div>
			</form>
		</div>
		<?php 
	}
	
	public function btn_edit_activity(){
		if( $this->can_edit_activity() ){
			?>
			<a href="#" class="button bp-secondary-action buddyboss_edit_activity" onclick="return buddypress_edit_activity_initiate(this);" data-activity_id="<?php bp_activity_id() ;?>">
				<?php _e( 'Edit', 'buddyboss-edit-activity' ); ?>
			</a>
			<?php 
		}
	}
	
	public function btn_edit_activity_comment(){
		global $activities_template;
		$activity = $activities_template->activity->current_comment;
		if( $this->can_edit_activity( $activity ) ){
			?>
			<a href="#" class="bp-secondary-action buddyboss_edit_activity_comment" onclick="return buddypress_edit_activity_initiate(this);" data-activity_id="<?php echo $activity->id;?>">
				<?php _e( 'Edit', 'buddyboss-edit-activity' ); ?>
			</a>
			<?php 
		}
	}
	
	/**
	 * Check if current user can edit given activity.
	 * 
	 * @global type $activities_template
	 * @param object $activity
	 * @return boolean
	 */
	private function can_edit_activity( $activity=false ){
		if( !is_user_logged_in() )
			return false;
		
		global $activities_template;

		// Try to use current activity if none was passed
		if ( empty( $activity ) && ! empty( $activities_template->activity ) ) {
			$activity = $activities_template->activity;
		}
			
		$can_edit = false;
		/**
		 * User must be either an admin or the author of activity himself/herself, to be adle to edit it.
		 */
		if( current_user_can( 'level_10' ) ){
			$can_edit = true;
		} else {
			if( $this->option( 'user_access' )=='author' ){
				if ( isset( $activity->user_id ) && ( (int) $activity->user_id === bp_loggedin_user_id() ) ) {
					$can_edit = true;
				}
			}
		}
		
		/**
		 * Activity must be of type 'activity_update', 'activity_comment', 
		 * whatever is selected in plugin settings.
		 */
		if( $can_edit===true ){
			if( !in_array( $activity->type, $this->option( 'editable_types' ) ) ){
				$can_edit = false;
			}
		}
		
		/**
		 * is a timeout defined and has the current activity passed the timeout?
		 * Timeout is not applicable for admins by default ( unless overridden in settings)
		 */
		if( $can_edit===true && ( !current_user_can( 'level_10' ) || $this->option( 'exclude_admins' ) != 'yes' ) ){
			if( ( $timeout = (int)$this->option( 'editable_timeout' ) ) != 0 ){
				$activity_time = strtotime( $activity->date_recorded );
				$current_time = time();
				
				$diff = (int) abs( $current_time - $activity_time );
				if( floor( $diff/60 ) >= $timeout ){
					//timeout must be in minutes!
					$can_edit = false;
				}
			}
		}
		
		return apply_filters( 'b_e_a_can_edit_activity', $can_edit, $activity );
	}
	
	public function ajax_get_activity_content(){
		check_ajax_referer( 'buddypress-edit-activity', 'buddypress_edit_activity_nonce' );
		$retval = array(
			'status'	=> false,
			'content'	=> __( 'Nothing found!', 'buddypress-edit-activity' ),
		);
		
		$activity_id = isset( $_POST['activity_id'] ) ? (int)$_POST['activity_id'] : false;
		if( !$activity_id ){
			die( json_encode( $retval ) );
		}
		
		$activity_content = $this->get_activity_content( $activity_id );
		if( $activity_content ){
			$retval['status'] = true;
			$retval['content'] = $activity_content;
		}
		
		die( json_encode( $retval ) );
	}
	
	private function get_activity_content( $activity_id ){
		$activity = new BP_Activity_Activity( $activity_id );
		if( !$activity || is_wp_error( $activity ) )
			return false;
		
		if( !$this->can_edit_activity( $activity ) )
			return false;
		
		$content = stripslashes( $activity->content );
		
		//convert @mention anchor tags into plain text
		$content = $this->strip_mention_tags( $content );
		return $content;
	}
	
	public function ajax_save_activity_content(){
		check_ajax_referer( 'buddypress-edit-activity', 'buddypress_edit_activity_nonce' );
		$retval = array(
			'status'	=> false,
			'content'	=> __( 'Error!', 'buddypress-edit-activity' ),
		);
		
		$activity_id = isset( $_POST['activity_id'] ) ? (int)$_POST['activity_id'] : false;
		if( !$activity_id ){
			die( json_encode( $retval ) );
		}
		
		$args = array(
			'activity_id'	=> $activity_id,
			'content'		=> isset( $_POST['content'] ) ? $_POST['content'] : '',
		);
		$retval['content'] = $this->save_activity_content( $args );
		$retval['status'] = true;
		
		die( json_encode( $retval ) );
	}
	
	private function save_activity_content( $args ){
		$activity = new BP_Activity_Activity( $args['activity_id'] );
		if( !$activity || is_wp_error( $activity ) )
			return false;
		
		if( !$this->can_edit_activity( $activity ) )
			return false;
		
		$activity->content = $args['content'];
		$activity->save();
		
		$activity_updated_html_content = '';
		
		if( $activity->type == 'activity_update' ){
			if( bp_has_activities( array( 'include' => $args['activity_id'] ) ) ){
				while ( bp_activities() ) { 
					bp_the_activity();
					ob_start();
					bp_activity_content_body();
					$activity_updated_html_content = ob_get_clean();
				}
			}
		}
		
		if( $activity->type == 'activity_comment' ){
			$content = apply_filters( 'bp_get_activity_content', $activity->content );

			$activity_updated_html_content = apply_filters( 'bp_activity_comment_content', $content );
		}
		
		return $activity_updated_html_content;
	}
	
	public function strip_mention_tags( $content ){
		if( empty( $content ) )
			return '';
		
		$dom = new DOMDocument();
		$dom->loadHTML($content);

		$anchors = $dom->getElementsByTagName('a');
		$len = $anchors->length;

		if ( $len > 0 ) {
			$i = $len-1;
			while ( $i > -1 ) {
				$anchor = $anchors->item( $i );

				if ( $anchor->hasAttribute('href') ) {
					$href = $anchor->getAttribute('href');
					$regex = '/^http/';

					if ( !preg_match ( $regex, $href ) ) { 
						$i--;
						continue;
					}

					$text = $anchor->nodeValue;
					$pos_attherate = strpos( $text, '@' );
					if( $pos_attherate===0 ){
						$textNode = $dom->createTextNode( $text );
						$anchor->parentNode->replaceChild( $textNode, $anchor );
					}
				}
				$i--;
			}
		}

		$html_fragment = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $dom->saveHTML()));
		return trim( $html_fragment );
	}
}

endif;