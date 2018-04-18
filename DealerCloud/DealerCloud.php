<?php
/*
Plugin Name: DealerCloud
Description: DealerCloud WordPress Plugin
Version: 2.0
Author: Auto Web Engine LLC
Author URI: http://www.autowebengine.com
*/
require_once("lib/Client.php");

/*************************************************************************************************/
// DealerCloud Plugin Class for WordPress sites using DealerCloud accounts
/*************************************************************************************************/

class DealerCloud_plugin
{
    /*************************************************************************************************/
    // Constructor
    function __construct()
    {
        add_action('init', array(&$this, 'DealerCloud_Initialize'));
        add_action('wp_print_styles', array(&$this, 'DealerCloud_LoadStyles'));
        add_action('wp_enqueue_scripts', array(&$this, 'DealerCloud_LoadScripts'));
        add_shortcode('dealercloud', array(&$this, 'DealerCloud_Inventory'));
    }

    /*************************************************************************************************/
    // Initialization
    function DealerCloud_Initialize()
    {
        global $wpdb;

        add_action('admin_menu', array(&$this, 'DealerCloud_Config_Initialize'));
        wp_register_sidebar_widget('DealerCloud_Widget', 'DealerCloud Widget', array(&$this, 'DealerCloud_Widget_Initialize'));
        wp_register_widget_control('DealerCloud_Widget', 'DealerCloud Widget', array(&$this, 'DealerCloud_Widget_Config'));
    }

    /*************************************************************************************************/
    // Main Plugin (Inventory & Details Pages loader)
    function DealerCloud_Inventory($atts, $content = null)
    {
        require_once("lib/Client.php");
        require_once("lib/InputHelper.php");
        require_once("lib/ReCaptcha.php");
        require_once("pages/frontend/inventory.php");
        return DealerCloudInventory($atts, $content);
    }

    /*************************************************************************************************/
    // CSS Loader
    function DealerCloud_LoadStyles()
    {
        $styles = WP_PLUGIN_URL . '/DealerCloud/res/css/styles.css';
        wp_register_style('DealerCloud_Styles', $styles);
        wp_enqueue_style('DealerCloud_Styles');
    }

    /*************************************************************************************************/
    // JavaScript Loader
    function DealerCloud_LoadScripts()
    {
        // Pass Config to JavaScript
        wp_enqueue_script(
            'modeljs',
            plugins_url('res/js/make-model.js', __FILE__)
        );
        $config = new Config();
        $scriptData = array(
            'aweAPIURL'           => $config->aweAPIURL,
            'aweAPIKey'           => $config->aweAPIKey,
            'appName'             => $config->appName,
            'recaptchaPublicKey'  => $config->recaptchaPublicKey,
            'recaptchaPrivateKey' => $config->recaptchaPrivateKey,
            'model'               => "",
        );
        wp_localize_script('modeljs', 'awe_options', $scriptData);
    }

    /* Plugin Configuration Pages */

    /*************************************************************************************************/
    // Configuration Initialization
    function DealerCloud_Config_Initialize()
    {
        if (function_exists('add_menu_page')) {
            add_menu_page('DealerCloud Configuration', 'DealerCloud Configuration', 'manage_options', 'dc_wp_config', array(&$this, 'DealerCloud_Config_Main'));
            if (function_exists('add_submenu_page')) {
                add_submenu_page('dc_wp_config', 'Add Inventory', 'Add Inventory', 'manage_options', 'dc_wp_add', array(&$this, 'DealerCloud_Config_Add'));
                add_submenu_page('dc_wp_config', 'Manage Inventory', 'Manage Inventory', 'manage_options', 'dc_wp_manage', array(&$this, 'DealerCloud_Config_Manage'));
            }
        }
    }

    /*************************************************************************************************/
    // Configuration page - Main
    function DealerCloud_Config_Main()
    {
        require_once("lib/InputHelper.php");
        include "pages/admin/configure.php";
    }

    /*************************************************************************************************/
    // Configuration page - Add Inventory
    function DealerCloud_Config_Add()
    {
        require_once("lib/Client.php");
        require_once("lib/InputHelper.php");
        include "pages/admin/addInventory.php";
    }

    /*************************************************************************************************/
    // Configuration page - Manage Inventory
    function DealerCloud_Config_Manage()
    {
        require_once("lib/Client.php");
        require_once("lib/InputHelper.php");
        include "pages/admin/manageInventory.php";
    }

    /* Plugin SideBar Widget */

    /*************************************************************************************************/
    // Widget Initialization
    function DealerCloud_Widget_Initialize($args)
    {
        extract($args);

        $options = get_option("DealerCloud_Widget");
        $title = (isset($options['title']) ? $options['title'] : "New Models !!!");

        echo $before_widget;
        echo $before_title;
        echo $title;
        echo $after_title;
        $this->DealerCloud_Widget();
        echo $after_widget;
    }

    /*************************************************************************************************/
    // Widget
    function DealerCloud_Widget()
    {
        require_once("lib/Client.php");
        require_once("lib/InputHelper.php");
        include "pages/frontend/widget.php";
    }

    /*************************************************************************************************/
    // Widget Configuration
    function DealerCloud_Widget_Config()
    {
        require_once("lib/Client.php");
        require_once("lib/InputHelper.php");
        include "pages/admin/widgetConfig.php";
    }
}

// Create the DealerCloud PlugIn object
$dc_plugin = new DealerCloud_plugin();

?>