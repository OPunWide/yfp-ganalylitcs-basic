<?php
/**
* This needs to be cleaned up from the related post code it was taken from.
* It is overkill for the simple options used.
*/
/**
* A few things are needed in admin as well as outside of it;
* constants and database retrieval, so put them in a separate class.
*
* This will tend to have the defaults for when nothing has been saved
* and the retrieval methods because they are needed in both modes.
*
*/
class Yfp_Ganalytics_Basic_Common
{
    const COMMON_PLUGIN_NAME = 'YFP Google Analytics Basic';
    // This is the key used in the wp_options table to store everything. WPOT_ = WP Option Table
    const WPOT_KEY_OPTIONS = 'yfp_ganalytics_basic_options';

    // Keys used in WPOT_KEY_OPTIONS array, OK_* = Option Key
    const OK_GID = 'ga_id';
    const OK_ENABLED = 'is_enabled';
    const OK_IN_HEAD = 'is_in_head';
    const OK_LOG_ON = 'is_logging_on';
    const OK_SERVER_EXCLUDES = 'local_server_tails';

    // Values used in the keys
    const OKV_IS_TRUE = '1';
    const OKV_IS_FALSE = '0';

    // An array of all of the defaults for the OK_* keys.
    protected $optDefaults = null;
    protected $optCurrent = null;

    // Use the same starting string to make the keys easier to find. These will be in the post_meta table.
    const KEY_CMB_GROUP_SELECT = '_yfp_mb_group_select';

    protected $groupKeyNameMap = false;

    public function __construct() {
        // Set all of the defaults
        $this->optDefaults = array();
        $this->optDefaults[self::OK_ENABLED] = '0';
        $this->optDefaults[self::OK_GID] = '';
        $this->optDefaults[self::OK_IN_HEAD] = '0';
        $this->optDefaults[self::OK_LOG_ON] = self::OKV_IS_FALSE;
        $this->optDefaults[self::OK_SERVER_EXCLUDES] = '.local; .loc';
        $this->buildCurrentOptions();
    }

    public function getDefaultOptions() {
        return $this->optDefaults;
    }
    /**
    * If saved options were found, they are used. Otherwise the default
    * values will be returned.
    *
    */
    public function getCurrentOptions() {
        return $this->optCurrent;
    }
    // For debug, just pulls the data from storage.
    public function getRawOptions($safe=false) {
        return $safe ? get_option(self::WPOT_KEY_OPTIONS, array()) : get_option(self::WPOT_KEY_OPTIONS);
    }

    /**
    * Force override the defaults for testing.
    * Use it to prove that everything works outside of using admin.
    */
    protected function forceTestData() {
        $this->optCurrent[self::OK_ENABLED] = '1';
        $this->optCurrent[self::OK_GID] = 'UA-26307840-1';
        //$this->optCurrent[self::OK_IN_HEAD] = '1';
        $this->optCurrent[self::OK_LOG_ON] = '1';
        //$this->optCurrent[self::OK_SERVER_EXCLUDES] = 'one; two';
        //$this->optCurrent[self::OK_SERVER_EXCLUDES] = '.loc';
    }

    /**
    * Gets the saved options. Uses defaults for options the don't exist,
    * to make it backwards compatible for future options.
    *
    */
    protected function buildCurrentOptions() {
        $saved = get_option( self::WPOT_KEY_OPTIONS );
        if (!is_array($saved)) {$saved = array(); }
        $saved = $this->getRawOptions(true);
        $this->optCurrent = array_merge($this->optDefaults, $saved);
        //////////////// TEST CODE TEST CODE TEST CODE TEST CODE /////////////////
        //$this->forceTestData();
    }

    /**
    * Calculations belong here so they all change in one place.
    */
    public function optIsEnabled() {
        return intval($this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_ENABLED]) > 0;
    }
    public function optInHead() {
        return intval($this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_IN_HEAD]) > 0;
    }
    public function optUseLogging() {
        return intval($this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_LOG_ON]) > 0;
    }
    public function optGid() {
        return $this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_GID];
    }
    public function optLocalList() {
        $list = $this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_SERVER_EXCLUDES];
        $items = explode(';', $list);
        $items = array_map('trim', $items);
        $items = array_filter($items);
        return $items;
    }
}


