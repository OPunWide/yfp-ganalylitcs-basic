<?php
/**
* This is only a class to make it easier to remember how the parts work together.
* It wraps the two functions involved in making a settings section.
*
* To empahsize that there is only one add_settings_section() call per
* settings sections, it is part of the constructor.
*
* Additional tests are included to help catch errors early. They can be disabled
* on any instance by using the methods:
*
* $obj->callback_prefix(false);
* $obj->disable_callback_verification(true);
*
* Callback Verification makes sure that the function of method defined exists.
* Callback prefix checking is usefull if you want all of the form callback
* method or function names to start with the same text.
*
*
* Why...
*
* The function add_settings_section is more of an organizational tool than anything
* else. It does not have to display any html, but provides information about how to
* display the various add_settings_field calls that are made in the admin section.
*/
class Yfp_Plugin_Settings_Section
{
    const FIELD_ID_PREFIX = 'autoid';
    const CALLBACK_PREFIX = 'form_cb_';
    protected $uid;
    protected $page;
    // Incremented to create a unique id within the section.
    protected $fieldId = 0;
    protected $namePrefix;
    protected $verifyOn = true;

    /**
    * There are developer warnings. Once the plug in working these should never
    * be able to happen.
    *
    * The reason for adding the checks is that unless a function is called, its
    * errors will never be reported. Using this does a check check on the
    * methods even if they are not being called during executiono of that page.
    * For example, if setting are put on more than one page.
    *
    * @param mixed $cb
    */
    protected function verify_callback($cb) {
        $methodName = '';
        if (is_string($cb)) {$methodName = $cb;}
        // Okay to cause an error, because that's what will happen if it isn't valid.
        else if (is_array($cb)) {$methodName = $cb[1];}

        if ($this->namePrefix) {
            $loc = strpos($methodName, $this->namePrefix);
            if (0 !== $loc) {
                trigger_error('Settings callback prefix is not following standards, was ' . $methodName, E_USER_WARNING);
            }
        }
        if ($this->verifyOn) {
            if (is_string($cb)) {
                assert(function_exists($methodName));
            }
            else if (is_array($cb)) {
                assert(method_exists($cb[0], $methodName));
            }
        }
    }

    /**
    * To empahsize that there is only one add_settings_section() call per
    * settings sections, it is part of the constructor.
    *
    * Use array( $this, 'method_name' ) to provide a method callback.
    *
    * @param mixed $uid - any plug-in wide unique id string.
    * @param mixed $page_slug - where this content should be displayed.
    * @param mixed $title - the string title of this section. Will be wrapped in a H3 tag.
    * @param mixed $html_callback - a function that will display the html for the start of the section.
    */
    public function __construct($uid, $page_slug, $title, $html_callback) {
        $this->uid = $uid;
        $this->page = $page_slug;
        $this->namePrefix = self::CALLBACK_PREFIX;

        add_settings_section($this->uid, $title, $html_callback, //array( $this, 'section_1_html' )
            $this->page // Options page slug, the page where this section will be displayed.
        );
    }

    /**
    * Adds a call to WP's add_settings_field().
    * Per the codex the $id in add_settings_field should match the name attribute of the
    * forn element that will be created by the html_callback.
    * Per the WP code, the id is a: Slug-name to identify the field. Used in the 'id' attribute of tags.
    *
    * But it is not actually used that way. It only needs
    * to be unique within a settings section. It does not show up in the HTML.
    *
    * @param mixed $id - not used. (Use add_field_with_id if you want to control this)
    * @param mixed $label - The HTML for the field. It will be displayed in a label tag, that is wrapped in TH.
    * @param mixed $html_callback - a function that will display the html for the form element and any other HTML.
    * @param mixed $args - optional arguments needed by the callback.
    */
    public function add_field($label, $html_callback, $args = array()) {
        // Create a unique id within this section.
        $rid = self::FIELD_ID_PREFIX . $this->fieldId++;
        $this->add_field_with_id($rid, $label, $html_callback, $args);
    }
    public function add_field_with_id($id, $label, $html_callback, $args = array()) {
        $this->verify_callback($html_callback);
        add_settings_field($id, $label, $html_callback, $this->page, $this->uid, $args);
    }

    /**
    * A lot of a plugin's functions are callbacks, most are for field output.
    * By using a consistant naming convention it is easer to find (or ignore)
    * all of those functions/methods.
    * Enabling name warnings causes warnings to be given if a callback does not
    * follow the convention.
    *
    * @param string $str - Any string is valid.
    *       An empty string or bool false turns it off
    *       bool true sets it to the default.
    */
    public function callback_prefix($str) {
        // Any string is valid. An empty string or bool false turns it off
        if (is_string($str) || false === $str) {
            $this->namePrefix = $str;
        }
        else if (true === $str) {
            $this->namePrefix = self::CALLBACK_PREFIX;
        }
    }

    public function disable_callback_verification($yes) {
        // This will turn off the check that a method or function exists.
        $this->verifyOn = !$yes;
    }
}
