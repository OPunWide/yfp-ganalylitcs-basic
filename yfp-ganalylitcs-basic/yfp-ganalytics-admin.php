<?php
/**
* Simple plugin for GA. Admin is separate because it does so much more
* that the actual worker-code.
*/

// This file is included by the main plugin, so the common class should already be loaded.
if ( ! class_exists( 'Yfp_Ganalytics_Basic_Common'  ) ) {
    throw new Yfp_Plugin_Base_Exception('Required class was not defined: ' . 'Yfp_Ganalytics_Basic_Common');
}

// This should be renamed to something like plugin-admin base.
if ( ! class_exists( 'Yfp_Plugin_Base' ) ) {
    require_once(plugin_dir_path( __FILE__ ) . 'includes/Yfp_Plugin_Base.php');
}

if ( ! class_exists( 'Yfp_Plugin_Settings_Section' ) ) {
    require_once(plugin_dir_path( __FILE__ ) . 'includes/Yfp_Plugin_Settings_Section.php');
}

class Yfp_Ganalytics_Basic_Admin extends Yfp_Plugin_Base
{
    const ADMIN_MENU_SLUG = 'yfp-ganalytics-basic-admin';

    // Additions for the admin interface.
    const STR_PLUGIN_TITLE = 'YFP Google Analytics';
    // The menu text in the settings menu, so keep it short.
    const STR_SETTINGS_MENU_TEXT = 'YFP G. Analytics';

    const TPL_INPUT_ELEM = '<input type="text" id="%s" name="%s" value="%s" />';
    // Lazy select... the last %s is to allow a "checked" option.
    const TPL_RADIO_ELEM = '<input type="radio" id="%s" name="%s" value="%s"%s />';
    const INPUT_VALUE_NO = 'no';
    const INPUT_VALUE_YES = 'yes';

    // Access the the common class within this object.
    protected $pluginCommon;
    protected $fqPluginFile;


    function __construct($fqPluginFile) {
        //$fqFile = __FILE__;
        $this->fqPluginFile = $fqPluginFile;
        // Provides info to parent class, so do it early.
        $this->initializePluginSettings();
        $this->initAdmin();
    }

    /**
    * All YFP plugins use this.
    */
    protected function initializePluginSettings() {
        //$fqFile = __FILE__;
        $fqFile = $this->fqPluginFile;
        $this->plugin_basename = plugin_basename($fqFile);
        $this->plugin_path = dirname($fqFile);
        $this->plugin_url_dir = plugins_url('', $fqFile);
        // Needed by the parent class to create a link.
        $this->plugin_menu_slug = self::ADMIN_MENU_SLUG;
        // Used on input forms for this plugin.
        $this->pluginSettingsGroup = $this->keySanitize($this->plugin_basename) . '_settings_group';
        parent::__construct();
    }


    protected function initAdmin() {
        // Get constants and common retrieval methods.
        $this->pluginCommon = new Yfp_Ganalytics_Basic_Common();
        // Add a link in the Admin Settings section to the settins page.
        $this->hook('admin_menu', 'cb_create_menu_link_to_settings_page');
        $this->hook('admin_init', 'cb_settings_api_init');
        // Add an Settings link to the Plugin page.
        $this->addPluginSettingsLink();
    }


    /**
     * Register and add settings. There is only one group.
     */
    public function cb_settings_api_init() {
        // This does registration, sections and settings, when I decide what that is.
        $this->addSettingsSection1('yfp_section_1', self::ADMIN_MENU_SLUG);
        register_setting(
            $this->pluginSettingsGroup,
            Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS,
            array( $this, 'sanitize_wpot_options' )
        );
    }


    /**
     * Add the settings page to the Admin Settings menu.
     */
    public function cb_create_menu_link_to_settings_page() {

        // This page will be under "Settings"  in the admin area.
        // This function is a simple wrapper for a call to add_submenu_page().
        add_options_page(
            self::STR_PLUGIN_TITLE . ' Settings', // Title for the browser's title tag.
            self::STR_SETTINGS_MENU_TEXT, // Menu text, show under Settings.
            'manage_options', // Which users can use this.
            self::ADMIN_MENU_SLUG, // Menu slug
            array( $this, 'cb_build_settings_page' )
        );
    }

    /**
     * A callback that creates the admin settings page content. Most of
     * the content is generated by the data saved by cb_settings_api_init.
     * This (internally) goes through the list of possible callbacks
     * and displays ones associated with this page.
     */
    public function cb_build_settings_page() {

        $htm = '';
        //$htm .= $this->postListDebug();

        ?>
        <div class="wrap yfp-plugin-admin">
            <h2><?php echo self::STR_PLUGIN_TITLE; ?> Settings</h2>
            <p>All setting for the plugin are on this page.</p>
            <?php echo $htm; ?>
            <form method="post" class="yfp-plugin-admin-form" action="options.php">
            <?php
                echo '<!-- settings_fields of "' . $this->pluginSettingsGroup . '" -->' . "\n";
                settings_fields($this->pluginSettingsGroup); // Demo says otherwise
                echo "\n\n" . '<!-- do_settings_sections of "' . self::ADMIN_MENU_SLUG . '" -->' . "\n";
                do_settings_sections(self::ADMIN_MENU_SLUG);
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Callback function that prints the section's introductory html.
     * This is (but does not have to be) a summary of the current saved values.
     */
    public function form_cb_section_1_html() {
        _e('<p>A simple way to include Google\'s Universal Analytics code. Add the ID you wish to track and then select the enable option.</p>');
    }


    /**
    * Separated to give more structure to how the parts work together.
    * Fields are displayed in the order added.
    * The section info is displayed first using the callback.
    * Only one add_settings_section() per sectionName.
    *
    * @param string $secName - Links section fields together, along with any settings
    *                   intro text. Not used in any other way.
    * @param string $pageSlug - Determines which admin page it will display on.
    */
    protected function addSettingsSection1($secName, $pageSlug) {
        $ss1 = new Yfp_Plugin_Settings_Section(
                $secName, $pageSlug, '', array( $this, 'form_cb_section_1_html' ));
        // Each of these create a row in the form table: title and an option (radio, input, etc.).
        $ss1->add_field('Analytics ID', array( $this, 'form_cb_analytics_id' ));
        $ss1->add_field('Enable tracking', array( $this, 'form_cb_radio_enable' ));
        $ss1->add_field('Add to head or tail', array( $this, 'form_cb_radio_head' ));
        $ss1->add_field('Use console logging', array( $this, 'form_cb_radio_logging' ));
        $ss1->add_field('Servers to not track', array( $this, 'form_cb_seerver_excludes' ));
    }

    /**
    * Makes a single button for a radio selector.
    *
    * @param mixed $id
    * @param mixed $name
    * @param mixed $value
    * @param mixed $labelText
    * @param mixed $checked
    * @return string - a button with a label tag around it
    */
    protected function makeRadioButton($id, $name, $value, $labelText, $checked='') {
        $htmRadio = sprintf(self::TPL_RADIO_ELEM, $id, $name, $value, $checked);
        $htmLabel = '<label for="' . $id . '">' . $labelText . '</label>';
        return $htmRadio . $htmLabel;
    }

    /**
    * This determines what will be submitted by the form. WP's filtering limits
    * what values will make it to the sanitize functino.
    * The work-around is to add a key that won't be used and merge the data in
    * the sanitize.'
    *
    */
    public function form_cb_radio_logging() {
        $isOn = $this->pluginCommon->optUseLogging();

        // Radio button to select which action to perform.
        $buttons = array();
        // $id, $name, $value, $labelText, $checked=''
        $buttons[] = $this->makeRadioButton(
            $this->attrToString(Yfp_Ganalytics_Basic_Common::OK_LOG_ON . '_no', Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            $this->attrToArray(Yfp_Ganalytics_Basic_Common::OK_LOG_ON, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            Yfp_Ganalytics_Basic_Common::OKV_IS_FALSE,
            'No',
            $isOn ? '' : ' checked'
        );
        $buttons[] = $this->makeRadioButton(
            $this->attrToString(Yfp_Ganalytics_Basic_Common::OK_LOG_ON . '_yes', Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            $this->attrToArray(Yfp_Ganalytics_Basic_Common::OK_LOG_ON, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            Yfp_Ganalytics_Basic_Common::OKV_IS_TRUE,
            'Yes',
            $isOn ? ' checked' : ''
        );
        echo implode("\n\t\t", $buttons);
        echo '<br />Use debug mode. Logs an entry to the console. Rarely used.';
        //var_dump(get_option( Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS ));
    }

    public function form_cb_radio_head() {
        $isOn = $this->pluginCommon->optInHead();
        // Radio button to select which action to perform.
        $buttons = array();
        // $id, $name, $value, $labelText, $checked=''
        $buttons[] = $this->makeRadioButton(
            $this->attrToString(Yfp_Ganalytics_Basic_Common::OK_IN_HEAD . '_yes', Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            $this->attrToArray(Yfp_Ganalytics_Basic_Common::OK_IN_HEAD, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            Yfp_Ganalytics_Basic_Common::OKV_IS_TRUE,
            'Head',
            $isOn ? ' checked' : ''
        );
        $buttons[] = $this->makeRadioButton(
            $this->attrToString(Yfp_Ganalytics_Basic_Common::OK_IN_HEAD . '_no', Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            $this->attrToArray(Yfp_Ganalytics_Basic_Common::OK_IN_HEAD, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            Yfp_Ganalytics_Basic_Common::OKV_IS_FALSE,
            'Tail',
            $isOn ? '' : ' checked'
        );
        echo implode("\n\t\t", $buttons);
        echo '<br />Place JavaScript in the head section or at the end. Google suggests head.';
        //var_dump(get_option( Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS ));
    }

    public function form_cb_radio_enable() {
        $isOn = $this->pluginCommon->optIsEnabled();
        // Radio button to select which action to perform.
        $buttons = array();
        // $id, $name, $value, $labelText, $checked=''
        $buttons[] = $this->makeRadioButton(
            $this->attrToString(Yfp_Ganalytics_Basic_Common::OK_ENABLED . '_no', Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            $this->attrToArray(Yfp_Ganalytics_Basic_Common::OK_ENABLED, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            Yfp_Ganalytics_Basic_Common::OKV_IS_FALSE,
            $isOn ? 'No' : '<span style="color: #FF7400">NO</span>',
            $isOn ? '' : ' checked'
        );
        $buttons[] = $this->makeRadioButton(
            $this->attrToString(Yfp_Ganalytics_Basic_Common::OK_ENABLED . '_yes', Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            $this->attrToArray(Yfp_Ganalytics_Basic_Common::OK_ENABLED, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            Yfp_Ganalytics_Basic_Common::OKV_IS_TRUE,
            'Yes',
            $isOn ? ' checked' : ''
        );
        //echo '<br>This is form_cb_radio_enable, a call back that displays this first item.<br>';
        echo implode("\n\t\t", $buttons);
        echo '<br />Enable tracking. The ID must be set before tracking can be enabled.';
        //var_dump(get_option( Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS ));
    }

    /**
    * Display the analytics ID input box
    */
    public function form_cb_analytics_id() {
        $curArr = $this->pluginCommon->getCurrentOptions();

        // params are: id, name, value
        printf(self::TPL_INPUT_ELEM,
            $this->attrToString(Yfp_Ganalytics_Basic_Common::OK_GID, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            $this->attrToArray(Yfp_Ganalytics_Basic_Common::OK_GID, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            esc_html__($curArr[Yfp_Ganalytics_Basic_Common::OK_GID])
        );
        echo '<br />This is the Tracking ID provided by Google for your site.';
        //var_dump(get_option( Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS ));
    }

    /**
    * Display the analytics ID input box
    */
    public function form_cb_seerver_excludes() {
        $list = $this->pluginCommon->optServerExludeList();
        $str = $this->pluginCommon->serverArrayToString($list);
        // params are: id, name, value
        printf(self::TPL_INPUT_ELEM,
            $this->attrToString(Yfp_Ganalytics_Basic_Common::OK_SERVER_EXCLUDES, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            $this->attrToArray(Yfp_Ganalytics_Basic_Common::OK_SERVER_EXCLUDES, Yfp_Ganalytics_Basic_Common::WPOT_KEY_OPTIONS),
            esc_html__($str)
        );
        echo '<br />Add servers (domains) that you do not want to track in a semicolon separated list. This uses Google\'s test mode, so logging can still occur. Please see the README for details.';
    }


    /**
    * Massage the inputs that go into the WPOT_KEY_OPTIONS storage location. This
    * is the chance to fix any badness that was entered into the form when it
    * was submitted.
    *
    * Add back current or default values in the array key that is being processed
    * because the results of this determine everything that will be stored for
    * this key.
    *
    * @param mixed $input
    * @return array
    */
    public function sanitize_wpot_options( $input ) {
        $message = array();
        $changedList = array();
        $showProgress = false;

        //$message .= __METHOD__ . '<br />';
        $type = 'updated';
        $wasChanged = false;

        $raw = $this->pluginCommon->getRawOptions();
        // This gets all keys and values, using their defaults if they have not been saved.
        $saved = $this->pluginCommon->getCurrentOptions();
        if ($showProgress) {
            $message[] = 'New (inp): '. print_r($input, true) . '<br />';
            $message[] = is_array($raw) ? 'sav (raw): '. print_r($raw, true) . '<br />' : 'key does not exist<br />';
            $message[] = 'Cur (sav): '. print_r($saved, true) . '<br />';
        }

        $key = Yfp_Ganalytics_Basic_Common::OK_GID;
        if (array_key_exists($key, $input)) {

            if (is_string($input[$key])) {
                $val = sanitize_text_field($input[$key]);
                // empty is okay ... if ('' !== $val)
                if ($saved[$key] != $val) {
                    $saved[$key] = $val;
                    $wasChanged |= true;
                    $changedList[] = $key;
                }
            }
        }

        $key = Yfp_Ganalytics_Basic_Common::OK_SERVER_EXCLUDES;
        if (array_key_exists($key, $input)) {

            if (is_string($input[$key])) {
                $val = sanitize_text_field($input[$key]);
                // Make it look like it will when the form is next shown to stop false saves.
                $arr = $this->pluginCommon->serverStringToArray($val);
                $val = $this->pluginCommon->serverArrayToString($arr);
                // empty is okay ... if ('' !== $val)
                if ($saved[$key] != $val) {
                    $saved[$key] = $val;
                    $wasChanged |= true;
                    $changedList[] = $key;
                }
            }
        }

        $key = Yfp_Ganalytics_Basic_Common::OK_ENABLED;
        // Disable if the GA ID field is blank.
        if ('' == $saved[Yfp_Ganalytics_Basic_Common::OK_GID]) {
            $saved[$key] = Yfp_Ganalytics_Basic_Common::OKV_IS_FALSE;

        }
        else {
            if (array_key_exists($key, $input)) {
                if (Yfp_Ganalytics_Basic_Common::OKV_IS_FALSE === $input[$key] ||
                    Yfp_Ganalytics_Basic_Common::OKV_IS_TRUE === $input[$key]) {
                    if ($saved[$key] != $input[$key]) {
                        $saved[$key] = $input[$key];
                        $wasChanged |= true;
                        $changedList[] = $key;
                    }
                }
            }
        }

        $key = Yfp_Ganalytics_Basic_Common::OK_IN_HEAD;
        if (array_key_exists($key, $input)) {
            if (Yfp_Ganalytics_Basic_Common::OKV_IS_FALSE === $input[$key] ||
                Yfp_Ganalytics_Basic_Common::OKV_IS_TRUE === $input[$key]) {
                if ($saved[$key] != $input[$key]) {
                    $saved[$key] = $input[$key];
                    $wasChanged |= true;
                    $changedList[] = $key;
                }
            }
        }
        $key = Yfp_Ganalytics_Basic_Common::OK_LOG_ON;
        if (array_key_exists($key, $input)) {
            if (Yfp_Ganalytics_Basic_Common::OKV_IS_FALSE === $input[$key] ||
                Yfp_Ganalytics_Basic_Common::OKV_IS_TRUE === $input[$key]) {
                if ($saved[$key] != $input[$key]) {
                    $saved[$key] = $input[$key];
                    $wasChanged |= true;
                    $changedList[] = $key;
                }
            }
        }

        $hadError = false;
        /*
        if ($hadError) {
                $type = 'error';
        }
        */
        if ($showProgress) {
            $message[] = 'Cur (end): '. print_r($saved, true) . '<br />';
        }
        $message[] = 'There were ' . ($wasChanged ? '' : 'not any ') . 'changes made.<br />';
        if (count($changedList)) {
            $message[] = 'Keys changed: ' . implode(', ', $changedList);
        }
        // Send a message or an error if the data exists.
        // $type = 'updated' will cause a status message to be sent rather than an error.
        if (count($message)) {
            add_settings_error(
                'unusedUniqueIdentifyer2',
                esc_attr( 'settings_updated' ),
                implode("\n", $message),
                $type
            );
        }
        // Don't save anything until this is working
        //return array();
        return $saved;
    }

}

