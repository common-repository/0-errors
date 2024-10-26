<?php

/**
 * 
 * The plugin base class - the plugin root!
 * 
 * @author Ayebare Mucunguzi
 *
 */
class Zero_Error_Base {

    /**
     * 
     * Assign everything as a call from within the constructor
     */
    public function __construct() {
        $this->registerHookCallbacks();
    }

    /**
     * Register callbacks for actions and filters
     * @mvc Controller
     * @author Zanto Translate
     */
    public function registerHookCallbacks() {


        add_action('shutdown', array($this, 'shutdown'));


        // register admin pages for the plugin
        add_action('admin_menu', array($this, 'ze_admin_pages_callback'));

        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, 'ze_on_activate_callback');
        register_deactivation_hook(__FILE__, 'ze_on_deactivate_callback');

        // Translation-ready
        add_action('wp_loaded', array($this, 'wp_loaded'));

        // Add earlier execution as it needs to occur before admin page display
        add_action('init', array($this, 'init'));
    }

    public function error_js() {
        if (!empty($GLOBALS['_ze_errors'])) {
            $errors = $GLOBALS['_ze_errors'];
            $errtext = '<ul>';
            ?>
            <!-- jbar -->

            <?php
            foreach ($errors as $index => $error) {
                $errtext.='<li> <b>' . $error['errtype'] . ' :</b>' . $error['errmsg'] . '</li>';
                //$errtype= $error['type'];
            }
            $errtext.='</ul>';
            ?>		
			
            <script type="text/javascript">
			    var ZeErrMasg = '<?php echo json_encode(strtr($errtext, "\"", "'")) ?>';
			</script>
			
            <div class="jbar" data-init="jbar" data-jbar='{
                 "button"  : "<?php _e('Get Help','0e') ?>",
                 "url"     : "https://wordpress.org/support/plugin/0-errors/",
                 "errNo"   : <?php echo count($errors) ?>,
                 "state"   : "closed"
                 }'></div>

            <!-- /jbar -->

            <?php
        }
    }

    public function init() {
        global $ZE_Settings;
        $this->ze_register_settings();

        add_action('admin_enqueue_scripts', array($this, 'ze_admin_CSS'));
        $settings = $ZE_Settings::get_settings();

        if ($settings['ze_bar_enable'] && current_user_can('manage_optoins')) {
            // add scripts and styles only available in admin
            add_action('admin_enqueue_scripts', array($this, 'ze_add_CSS'));
            add_action('admin_enqueue_scripts', array($this, 'ze_add_JS'));
            // add scripts and styles to the front end
            add_action('wp_enqueue_scripts', array($this, 'ze_add_JS'));
            add_action('wp_enqueue_scripts', array($this, 'ze_add_CSS'));
        }

        if (is_admin()) {
            add_action('admin_print_footer_scripts', array($this, 'error_js'), 50);
        } else {
            add_action('wp_print_footer_scripts', array($this, 'error_js'), 50);
        }
    }

    public function get_admins() {
        $users = get_users();
        $recepients = array();
        foreach ($users as $user) {
            if (in_array('administrator', $user->roles))
                $recepients[] = array('email' => $user->user_email, 'name' => $user->display_name);
        }
        return $recepients;
    }

    public function shutdown() {

        if (!empty($GLOBALS['_ze_errors'])) {
            global $ze_error_handler, $ZE_Settings;

            $settings = $ZE_Settings::get_settings();

            if ($settings['ze_err_log']) {
                if (defined('DOING_AJAX') && DOING_AJAX) {
                    update_option('ze_hxr_errors', $ze_error_handler::get_ajax_errs());
                }
            }

            if ($settings['ze_email_alerts']['enable']) {

                if (!get_transient('ze_error_alert')) {
                    $headers = '';
                    $site_name = get_site_option('site_name');
                    $subject = sprintf(__('%s Error Alert!', '0e'), $site_name);
                    $message = __('Errors have been recorded on your website!:') . "\r\n\r\n";
                    $message .= network_home_url('/') . "\r\n\r\n";
                    $message .= sprintf(__('This message was sent by 0Errors plugin installed on %s:'), $site_name) . "\r\n\r\n";

                    if ($settings['ze_email_alerts']['cc_admins']) {
                        $admins = $this->get_admins();
                        foreach ($admins as $admin) {
                            $headers[] = sprintf('Cc: %s <%s>', $admin['display_name'], $admin['email']);
                        }
                    }

                    $to_mail = $settings['ze_email_alerts']['email_addrs'];
                    wp_mail($to_mail, $subject, $message, $headers);
                    set_transient('ze_error_alert', true, 60 * 1440); //24 hour transient
                }
            }
        }
    }

    /**
     *
     * Adding JavaScript scripts for the admin pages only
     *
     * Loading existing scripts from wp-includes or adding custom ones
     *
     */
    public function ze_add_JS() {
        wp_register_script('j-bar', ZEP_URL . '/js/jbar.js', array('jquery'), '1.0', true);
        wp_enqueue_script('j-bar');
    }

    /**
     * 
     * Add CSS styles
     * 
     */
    public function ze_add_CSS() {
        wp_register_style('j-bar-css', ZEP_URL . '/css/jbar.css', array(), '1.0', 'screen');
        wp_enqueue_style('j-bar-css');
    }

    /**
     * 
     * Add CSS styles
     * 
     */
    public function ze_admin_CSS($hook) {

        if ('toplevel_page_zero-errors' === $hook) {
            wp_register_style('ze_stgs_page', ZEP_URL . '/css/stgs-page.css', array(), '1.0', 'screen');
            wp_enqueue_style('ze_stgs_page');
        }
    }

    /**
     * 
     * Callback for registering pages
     * 
     * This demo registers a custom page for the plugin and a subpage
     *  
     */
    public function ze_admin_pages_callback() {
        add_menu_page(__("Zero Errors", '0e'), __("Zero Errors", '0e'), 'edit_themes', 'zero-errors', array($this, 'Zero_Error_Stgs'), 'dashicons-flag');
    }

    /**
     * 
     * The content of the settings page
     * 
     */
    public function Zero_Error_Stgs() {
        include_once( ZEP_PATH_INCLUDES . '/stgs-page.php' );
    }

    /**
     * Initialize the Settings class
     * 
     * Register a settings section with a field for a secure WordPress admin option creation.
     * 
     */
    public function ze_register_settings() {
        global $ZE_Settings;
        require_once( ZEP_PATH_CLASS . '/ze-settings.class.php' );
        $ZE_Settings = new ZE_Settings();
    }

    /**
     * Add textdomain for plugin
     */
    public function wp_loaded() {
        load_plugin_textdomain('0e', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

}