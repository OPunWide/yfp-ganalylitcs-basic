<?php
/**
* Delete the key used to save options for the plugin.
* The existance of a file named uninstall.php is all that is required to do the uninstall.
*/
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	exit();

if (is_admin() && current_user_can('activate_plugins')) {
    // Need to get the defined constants. The class won't be instanciated because of the defined WP_UNINSTALL_PLUGIN.
    include_once(dirname(__FILE__) . '/yfp-ganalytics-common.php');
    // All of the options are in one location.
    $optKey = Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS;
    delete_option($optKey);
    // For site options in multisite
    delete_site_option( $optKey );
}
