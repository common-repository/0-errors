<?php

class ZE_Settings {

    private static $ze_setting;
    protected static $default_settings;

    /**
     * Construct the settings class
     */
    public function __construct() {
        self::$default_settings = self::get_default_settings();
        self::$ze_setting = self::get_settings();

        // register the settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Setup the settings
     * 
     */
    public function register_settings() {
        register_setting('ze_setting', 'ze_setting', array($this, 'ze_validate_settings'));

        add_settings_section(
                'ze_settings_section', // ID used to identify this section and with which to register options
                __("PHP Errors", '0e'), // Title to be displayed on the administration page
                array($this, 'ze_section_header'), // Callback used to render the description of the section
                '0e-plugin-base'                           // Page on which to add this section of options
        );

        add_settings_field(
                'ze_bar_enable', // ID used to identify the field throughout the theme
                __("Notifications: ", '0e'), // The label to the left of the option interface element
                array($this, 'ze_bar_enable_callback'), // The name of the function responsible for rendering the option interface
                '0e-plugin-base', // The page on which this option will be displayed
                'ze_settings_section'         // The name of the section to which this field belongs
        );

        add_settings_field(
                'ze_err_log', // ID used to identify the field throughout the theme
                __("Log Errors: ", '0e'), // The label to the left of the option interface element
                array($this, 'ze_errl_callback'), // The name of the function responsible for rendering the option interface
                '0e-plugin-base', // The page on which this option will be displayed
                'ze_settings_section'         // The name of the section to which this field belongs
        );


        add_settings_field(
                'ze_email_alerts', // ID used to identify the field throughout the theme
                __("Email Error Alerts: ", '0e'), // The label to the left of the option interface element
                array($this, 'ze_email_alerts_callback'), // The name of the function responsible for rendering the option interface
                '0e-plugin-base', // The page on which this option will be displayed
                'ze_settings_section'         // The name of the section to which this field belongs
        );
    }


    /**
     * Establishes initial values for all settings
     * @return array
     */
    protected static function get_default_settings() {
        $email_alerts = array(
            'enable' => 1,
            'email_addrs' => get_option('admin_email'), // Mail User - Default mail format (html or plain text)
            'cc_admins' => 1, // Whether to CC all administrators when errors are registered.
        );


        return array(
            'db-version' => '0.1',
            'ze_email_alerts' => $email_alerts,
            'ze_err_log' => 1,
            'ze_bar_enable' => 1
        );
    }

    /**
     * Returns settings 
     * 
     * @param array $db, retrieve settings from the database if true	 
     * @return array
     */
    public static function get_settings($db=false) {

        if (!$db && isset(self::$ze_setting)) {
            return self::$ze_setting;
        }

        $settings = shortcode_atts(
                self::$default_settings, get_option('ze_setting', array())
        );

        return $settings;
    }

    public function ze_section_header() {
         echo '<p>',__('<b>Note:</b> Parse Errors are not subject to custom PHP error handleling.','0e'),'</p>';
                    
    }

    /**
     * Error bar Settings
     * 
     * outputs error notice bar options fields 
     * uses @ze_validate_settings to sanitize the fields
     * 
     * @param array $input
     */
    public function ze_bar_enable_callback() {
        $val = self::$ze_setting['ze_bar_enable'];
        ?>
        <p><label><input autocomplete="off" type="checkbox" value="1" name="ze_setting[ze_bar_enable]" <?php checked($val, 1) ?>><?php _e('Enable Notifications bar', '0e') ?></label></p>
        <p class="description"><?php _e('The bar shows for admins and when errors are caught', '0e') ?> </p>

        <?php
    }

    /**
     * Error log Settings
     * 
     * out puts the error log option fields i.e save arrors, save only ajax or not to save any errors
     * uses @ze_validate_settings to sanitize the fields
     * 
     * @param array $input
     */
    public function ze_errl_callback() {

        $val = self::$ze_setting['ze_err_log'];
        ?>
        <label><input type="checkbox" value="1" id="ze_err_log" name="ze_setting[ze_err_log]" <?php checked($val, 1, 1) ?>/> Save Ajax log</label><br/>
        <p class="description"> Up to 10 latest unique ajax errors will be saved </p>
        <?php
    }

    /**
     * Email Settings
     * 
     * out puts the email alert fields i.e enable checkbox, email address field and CC admins checkbox
     * uses @ze_validate_settings to sanitize the fields
     * 
     * @param array $input
     */
    public function ze_email_alerts_callback() {

        $enabled = self::$ze_setting['ze_email_alerts']['enable'];
        $email_addrs = self::$ze_setting['ze_email_alerts']['email_addrs'];
        $cc_admins = self::$ze_setting['ze_email_alerts']['cc_admins'];
        ?>

        <p><label><input autocomplete="off" type="checkbox" value="1" name="ze_setting[ze_email_alerts][enable]" <?php checked($enabled, 1) ?>>Enable Email Error Alerts</label></p>
        <p><input autocomplete="off" type="text" name="ze_setting[ze_email_alerts][email_addrs]" placeholder="email@examplesite.com" value="<?php echo $email_addrs ?>"> </p>
        <p class="description">Email address to send Email Error notifications to</p>
        <p><label><input autocomplete="off" type="checkbox" value="1" name="ze_setting[ze_email_alerts][cc_admins]" <?php checked($cc_admins, 1) ?>>CC: All Admins</label></p>

        <?php
    }

    /**
     * Validate Settings
     * 
     * Filter input data and sanitize it
     * 
     * @param array $input
     */
    public function ze_validate_settings($input) {
        $input['ze_bar_enable'] = isset($input['ze_bar_enable']) ? 1 : 0;
        $input['ze_email_alerts']['enable'] = isset($input['ze_email_alerts']['enable']) ? 1 : 0;
        $input['ze_email_alerts']['cc_admins'] = isset($input['ze_email_alerts']['cc_admins']) ? 1 : 0;
		$input['ze_err_log'] = isset($input['ze_err_log']) ? 1 : 0;

        if ($input['ze_email_alerts']['enable']) {
            if (!is_email($input['ze_email_alerts']['email_addrs'])) {
			    $err_msg=__('Invalid Email provided','0e');
                add_settings_error( 'ze_setting', 'email_error', $err_msg, 'error' );
                $input['ze_email_alerts']['enable'] = 0;
                $input['ze_email_alerts']['email_addrs'] = '';
            }
        }

        return $input;
    }
	

}