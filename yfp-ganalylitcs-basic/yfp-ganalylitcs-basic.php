<?php
/*
Plugin Name: YFP Google Analytics Basic
Plugin URI: https://SplendidSpider.com
Description: Adds Google Analytics in its (almost) most basic form. Add your tracking ID and the Univeral Analytics script will be inserted on your page.
Version: 0.1.0
Author: Paul Blakelock, Splendid Spider Web Design.
Author URI: http://SplendidSpider.com
License: GPL2
*/

// Methods and constants needed for normal and admin modes.
if ( ! class_exists( 'Yfp_Ganalytics_Basic_Common'  ) ) {
    require_once  plugin_dir_path( __FILE__ ) . 'yfp-ganalytics-common.php';
}

// We need the class even if it is never instanciated.
if ( ! class_exists( 'Yfp_Ganalytics_Basic_Admin' ) ) {
    require_once(plugin_dir_path( __FILE__ ) . 'yfp-ganalytics-admin.php');
}


class Yfp_Ganalytics_Basic
{
    // Name of the possible hooks to place the JavaScript code.
    const TOP_HOOK = 'wp_head';
    const BOTTOM_HOOK = 'wp_footer';

    private static $instance = null;
    // This holds all of the current options in one array, defined in the common class.
    protected $pluginCommon;

    // debug
    protected $foundHost = '';

    /**
    * Determines if this is a local host based on the server name.
    * Compares the server name to a list of server endings to support .local,
    * but a full domain could be used as an ignore.
    * @return bool
    */
    protected function isIgnoredServer() {

        $isValidHost = true;
        $ignoreList = $this->pluginCommon->optServerExludeList();
        if (count($ignoreList)) {
            // Make sure the match is at the end.
            $adder = '_xzx_';
            $serverTest = $_SERVER['SERVER_NAME'] . $adder;
            // If the current server matches any of the ignored list, then get out.
            foreach ($ignoreList as $cur) {
                $isValidHost = (false === stripos( $serverTest, $cur . $adder ) ? true : false);
                if (!$isValidHost) {
                    // debug
                    $this->foundHost = $cur;
                    break;
                }
            }
        }
        return !$isValidHost;
    }


    /**
    * Add the hooks that make this run. Or do admin.
    */
    protected function init() {
        // Provides info to parent class, so do it early.
        if (is_admin() ) {
            $this->pluginAdmin = new Yfp_Ganalytics_Basic_Admin(__FILE__);
        }
        else {
            $this->pluginCommon = new Yfp_Ganalytics_Basic_Common();

            // Don't track if we aren't enabled.
            if ($this->pluginCommon->optIsEnabled()) {
                // The location of the code depends on the inHead option.
                add_action(
                    $this->pluginCommon->optInHead() ? self::TOP_HOOK : self::BOTTOM_HOOK,
                    array($this, 'insert_js_code')
                );
            }
            else {
                // Add a comment so we can tell the plugin is functioning, but disabled.
                add_action(
                    $this->pluginCommon->optInHead() ? self::TOP_HOOK : self::BOTTOM_HOOK,
                    array($this, 'disabled_message')
                );
            }
        }
    }


    /**
    * A string of JS code that does not depend on any options to build,
    * but may not be used. It runs an optional Analytics callback
    * feature and will log a message to the console if logging is possible.
    *
    * @return string
    */
    protected function getTrackerString() {
        ob_start();
?>
ga(function(tracker) {
    var url = tracker.get('location');
    var message = 'Analytics page is: '+ url;
    if(window.console && console.log) console.log(message);
});
<?php
        $jsLog = ob_get_clean();
        return $jsLog;
    }


    /**
    *
    * Based on Nov. 2014 Google documents version.
    *
    * This code was originally used in another project. The existing interface
    * is used and a few changes were added for flexibility and status improvements.
    *
    * Basic usage: getJsCodeToEcho($gaUaId, array(
    *        'log' => true,
    *        'localserver' => \Yfp\isLocalServer(),
    *    ));
    *
    * @param string $gaUaId - GA web property ID, looks like: UA-XXXX-Y
    * @param array $options -
    *       'localserver' - trueish to add 'cookieDomain': 'none' to the create command.
    *       'log' - trueish to add a console message. This is to show that GA is working.
    * @return string - everything ready to be wrapped in script tags.
    */
    protected function getJsCodeToEcho($gaUaId, $options=array()) {

        $createOpts = "'auto'";
        $optKeyLog = 'log';
        $optKeyLocal = 'localserver';
        $useConsole = false;
        $jsMsgs = array();

        if (count($options)) {
            $jsMsgs[] = 'Options array input keys: "' . implode(', ', array_keys($options)) . '"';

            // Check logging option
            if (array_key_exists($optKeyLog, $options) && $options[$optKeyLog]){
                $useConsole = true;
                $jsMsgs[] = 'logging is on';
            }

            // For building the options array that is sent in the JS.
            $coList = array();
            // Used for testing the code without Google logging the event.
            if (array_key_exists($optKeyLocal, $options) && $options[$optKeyLocal]){
                $coList[] = "'cookieDomain': 'none'";
                // foundHost was saved when this mode was set.
                $jsMsgs[] = 'ignore domain is on, found: -' . $this->foundHost . '-';
            }

            // If any options were added, create the string that will be used in the JS.
            if (count($coList)) {
                $createOpts = "{\n" . implode(', ', $coList) . "}\n";
            }
        }

        // Get the main analytics code. Start with some identifying comments.
        ob_start();
        echo '// This script block is from the ' . Yfp_Ganalytics_Basic_Common::COMMON_PLUGIN_NAME . " plugin.\n";
        echo '// Options used info: ' . implode(', ', $jsMsgs) . ".\n";

        ?>
// The analytics.js loaded is part of Google's Universal Analytics.
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

// Adding this will make sure that the ga commands can be queued up even if it has not loaded.
window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)}; ga.l=+new Date;
ga('create', '<?php echo $gaUaId; ?>', <?php echo $createOpts; ?>);
// Not required, but doesn't hurt to add it. Rethink this for high-traffic sites.
ga('require', 'displayfeatures');
ga('send', 'pageview');
<?php
        // Only include the logging code if it is enabled.
        if ($useConsole) {
            // Echo the JS debug code for creating a console log.
            echo $this->getTrackerString();
        }
        echo '// End of ' . Yfp_Ganalytics_Basic_Common::COMMON_PLUGIN_NAME . " plugin\n";
        $scr = ob_get_clean();
        return $scr;
    }


    /**
    * This is public so it can be reached by a hook. The string it returns
    * will be placed into either the head or tail, using either the wp_head
    * or wp_footer hook.
    * @return string
    */
    public function insert_js_code() {
        echo "\n<script>\n" . $this->getJsCodeToEcho($this->pluginCommon->optGid(), array(
            'log' => $this->pluginCommon->optUseLogging(),
            'localserver' => $this->isIgnoredServer(),
        )) . '</script>' . "\n";
    }


    /**
    * An FYI message that indicates that the plugin is enabled, but the admin
    * settings are keeping it from generating any analytics code.
    */
    public function disabled_message() {
        echo '<!-- ' . Yfp_Ganalytics_Basic_Common::COMMON_PLUGIN_NAME . ' is disabled in the admin panel. -->' . "\n";
    }


    /**
    * The trend seems to be to make a singleton so the object can be
    * accessed once it is built.
    */
    protected function __construct(){}

    public static function instance(){
        if (!isset(self::$instance)) {
            self::$instance = new self;
            self::$instance->init();
        }
        return self::$instance;
    }

}

// Create an instance of the class if this isn't an uninstall.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    // Launch the plugin. There are no hooks needed
    Yfp_Ganalytics_Basic::instance();
}

