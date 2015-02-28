<?php
/**
* A few things are needed in admin as well as outside of it;
* constants and database retrieval, so put them in a separate class.
*
* This has the defaults for when nothing has been saved
* and it has the retrieval methods because they are needed in both modes.
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

    public function __construct() {
        // Set all of the defaults
        $this->optDefaults = array();
        $this->optDefaults[self::OK_ENABLED] = '0';
        $this->optDefaults[self::OK_GID] = '';
        $this->optDefaults[self::OK_IN_HEAD] = '0';
        $this->optDefaults[self::OK_LOG_ON] = self::OKV_IS_FALSE;
        $this->optDefaults[self::OK_SERVER_EXCLUDES] = '.local; .loc';
        // Get the saved options, if any.
        $saved = $this->getRawOptions(true);
        // Make the current options available, using defaults for non-saved items.
        $this->optCurrent = array_merge($this->optDefaults, $saved);
    }

    public function getDefaultOptions() {
        return $this->optDefaults;
    }
    /**
    * If saved options were found, they are used. Otherwise the default
    * values will be returned.
    */
    public function getCurrentOptions() {
        return $this->optCurrent;
    }
    /**
    * Pulls the data from storage, which will be FALSE if nothing is found.
    *
    * @param bool $safe - will always return an array if true
    */
    public function getRawOptions($safe=false) {
        return $safe ? get_option(self::WPOT_KEY_OPTIONS, array()) : get_option(self::WPOT_KEY_OPTIONS);
    }


    /**
    * Want to be able to process the array before it is saved, so this
    * needs to be public. Needs to be consistent so non-functional
    * changes to the string do not cause it to be saved.
    */
    public function serverStringToArray($inpstr) {
        $items = array();
        if (strlen($inpstr)) {
            $items = explode(';', $inpstr);
            $items = array_map('trim', $items);
            $items = array_unique($items);
            $items = array_filter($items);
        }
        return $items;

    }
    public function serverArrayToString($inpArr) {
        return implode('; ', $inpArr);
    }

    /**
    * Calculations belong here so they all change in one place.
    * These do not return the raw values, but the computed ones that are usually needed.
    */
    /**
    * @return bool
    */
    public function optIsEnabled() {
        return intval($this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_ENABLED]) > 0;
    }
    /**
    * @return bool
    */
    public function optInHead() {
        return intval($this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_IN_HEAD]) > 0;
    }
    /**
    * @return bool
    */
    public function optUseLogging() {
        return intval($this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_LOG_ON]) > 0;
    }
    /**
    * @return string
    */
    public function optGid() {
        return $this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_GID];
    }
    /**
    * @return array of strings
    */
    public function optServerExludeList() {
        return $this->serverStringToArray($this->optCurrent[Yfp_Ganalytics_Basic_Common::OK_SERVER_EXCLUDES]);
    }
}


