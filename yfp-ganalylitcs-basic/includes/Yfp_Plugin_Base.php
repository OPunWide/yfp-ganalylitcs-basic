<?php
/**
* @author  Paul Blakelock
* @version 1.0.0
*
*/
class Yfp_Plugin_Base_Exception extends Exception {};

abstract class Yfp_Plugin_Base
{
    protected $plugin_basename;
    // These have no trailing slashes.
    protected $plugin_path;
    protected $plugin_url_dir;
    // The slug for the main menu page. Needed here to make the settings page link.
    protected $plugin_menu_slug;

    /**
    * Looks sloppy to me. I cleaned up what was used in Konstantin's obenland-wp-plugins.php
    * I don't see a reason to use the sanitize function, so removed it. Watch for errors
    * caused by actions that have the chars . or -.
    *
    * All this does is look for an optional priority param, before calling an action
    * of the same name as sent. So it hooks admin_init by adding this class' method
    * of the same naem.
    *
    * @param mixed $hook
    */
    protected function hook() {
        $priority = 10;
        //$method   = $this->sanitize_method( $hook );
        $args     = func_get_args();
        $hook = array_shift( $args );
        $method = $hook;
        //
        foreach ( (array) $args as $arg ) {
            if ( is_int( $arg ) )
                $priority = $arg;
            else
                // Hook to a different method name.
                $method = $arg;
        }
        return add_action( $hook, array( $this, $method ), $priority , 999 );
    }

    /**
    * Created to convert basename to a variable, but useful.
    *
    * @param string $key
    * @return string
    */
    protected function keySanitize($key) {
        return str_replace( array( '.', '-' ), '_', $key );
    }


    /**
    * Conversion to ID and name attributes for and option key.
    *
    * Keep the order the same as other array_* functions
    * @param string $key_name
    * @param string $array_name
    */
    protected function attrToArray($key_name, $array_name) {
        return $array_name . '[' . $key_name . ']';
    }
    protected function attrToString($key_name, $array_name) {
        return $array_name . '_' . $key_name;
    }


    /**
    * HTML for a link on the plugin page of the Dashboard.
    * Assumes that a menu page will be added. If not, don't call this.
    * @param arrat $links
    */
    public function plugin_settings_page_link($links) {
        $settings_link = '<a href="options-general.php?page=' . $this->plugin_menu_slug . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    // Called to add the above link to the plugins menu.
    protected function addPluginSettingsLink() {
        if (is_string($this->plugin_menu_slug))
        add_filter('plugin_action_links_' . $this->plugin_basename, array(&$this, 'plugin_settings_page_link'));
    }

    /**
    * Always call this at the END of the child constructor.
    * Check that the expected variables were initialized. It's a helper when new classes
    * are derived to make sure that step wasn't forgotten.
    */
    protected function __construct() {
        // These should have been initiated. the page slug is optional.
        $props = array('plugin_basename', 'plugin_path', 'plugin_menu_slug', );
        foreach ($props as $prop) {
            assert(is_string($this->{$prop}));
        }
    }

}

