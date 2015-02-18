<?php
/**
 * Plugin Name: BuddyPress Edit Activity
 * Plugin URI:  http://www.buddyboss.com/product/buddypress-edit-activity/
 * Description: Edit BuddyPress activity posts from the front-end
 * Author:      BuddyBoss
 * Author URI:  http://buddyboss.com
 * Version:     1.0.3
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
  exit;

/**
 * ========================================================================
 * CONSTANTS
 * ========================================================================
 */
// Codebase version
if (!defined( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_VERSION' ) ) {
  define( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_VERSION', '1.0.3' );
}

// Database version
if (!defined( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_DB_VERSION' ) ) {
  define( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_DB_VERSION', 2 );
}

// Directory
if (!defined( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_DIR' ) ) {
  define( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

// Url
if (!defined( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_URL' ) ) {
  $plugin_url = plugin_dir_url( __FILE__ );

  // If we're using https, update the protocol. Workaround for WP13941, WP15928, WP19037.
  if ( is_ssl() )
    $plugin_url = str_replace( 'http://', 'https://', $plugin_url );

  define( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_URL', $plugin_url );
}

// File
if (!defined( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_FILE' ) ) {
  define( 'BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_FILE', __FILE__ );
}

/**
 * ========================================================================
 * MAIN FUNCTIONS
 * ========================================================================
 */

/**
 * Main
 *
 * @return void
 */
function buddyboss_edit_activity_init()
{
  global $bp, $BUDDYBOSS_EDIT_ACTIVITY;

  $main_include  = BUDDYBOSS_EDIT_ACTIVITY_PLUGIN_DIR  . 'includes/main-class.php';

  try
  {
    if ( file_exists( $main_include ) )
    {
      require( $main_include );
    }
    else{
      $msg = sprintf( __( "Couldn't load main class at:<br/>%s", 'buddypress-edit-activity' ), $main_include );
      throw new Exception( $msg, 404 );
    }
  }
  catch( Exception $e )
  {
    $msg = sprintf( __( "<h1>Fatal error:</h1><hr/><pre>%s</pre>", 'buddypress-edit-activity' ), $e->getMessage() );
    echo $msg;
  }

  $BUDDYBOSS_EDIT_ACTIVITY = BuddyBoss_Edit_Activity::instance();
  
}
add_action( 'plugins_loaded', 'buddyboss_edit_activity_init' );

/**
 * Must be called after hook 'plugins_loaded'
 * @return BuddyPress Edit Activity Plugin main controller object
 */
function buddyboss_edit_activity()
{
  global $BUDDYBOSS_EDIT_ACTIVITY;

  return $BUDDYBOSS_EDIT_ACTIVITY;
}