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
        add_action('init', array(&$this, 'DealerCloud_init'));
        add_action('wp_print_styles', array(&$this, 'DealerCloud_loadcss_head'));
        add_action('wp_enqueue_scripts', array(&$this, 'DealerCloud_enqueue_scripts'));
        add_shortcode('dealercloud', array(&$this, '_dealercloud'));
    }

    /*************************************************************************************************/
    // Initialization
    function DealerCloud_init()
    {
        global $wpdb;

        add_action('admin_menu', array(&$this, 'DealerCloud_config_page'));
        wp_register_sidebar_widget('DealerCloudSidebarID', __('DealerCloud'), array(&$this, 'DealerCloud_widget'));
        wp_register_widget_control('DealerCloudWidgetID', 'DealerCloud', array(&$this, 'DealerCloud_control'));
    }

    /*************************************************************************************************/
    // Pass Config to JavaScript
    function DealerCloud_enqueue_scripts()
    {
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

    /*************************************************************************************************/
    // Main Plugin (Inventory & Details Pages loader)
    function _DealerCloud($atts, $content = null)
    {
        require_once("lib/Client.php");
        require_once("lib/InputHelper.php");
        require_once("lib/ReCaptcha.php");
        require_once("pages/frontend/inventory.php");
        return _DealerCloudImpl($atts, $content);
    }

    /*************************************************************************************************/
    // CSS Loader
    function DealerCloud_loadcss_head()
    {
        $styles = WP_PLUGIN_URL . '/DealerCloud/res/css/styles.css';

        wp_register_style('DealerCloud_styles', $styles);
        wp_enqueue_style('DealerCloud_styles');
    }

    /* Plugin Configuration Pages */

    /*************************************************************************************************/
    // Configuration page definition
    function DealerCloud_config_page()
    {
        if (function_exists('add_menu_page') && function_exists('add_submenu_page')) {
            add_menu_page(__('DealerCloud'), __('DealerCloud Configuration'), 'manage_options', 'DealerCloud-key', array(&$this, 'DealerCloud_conf'));
            add_submenu_page(__('DealerCloud-key'), __('Add Inventory'), __('Add Inventory'), 'manage_options', 'DealerCloud-key-add', array(&$this, 'DealerCloud_addInventory'));
            add_submenu_page(__('DealerCloud-key'), __('Manage Inventory'), __('Manage Inventory'), 'manage_options', 'DealerCloud-key-manage', array(&$this, 'DealerCloud_inventoryManagement'));
        }
    }

    /*************************************************************************************************/
    // Configuration page - Main
    function DealerCloud_conf()
    {
        require_once("lib/InputHelper.php");
        include "pages/admin/configure.php";
    }

    /*************************************************************************************************/
    // Configuration page - Add Inventory
    function DealerCloud_addInventory()
    {
        require_once("lib/Client.php");
        require_once("lib/InputHelper.php");
        include "pages/admin/addInventory.php";
    }

    /*************************************************************************************************/
    // Configuration page - Manage Inventory
    function DealerCloud_inventoryManagement()
    {
        require_once("lib/Client.php");
        require_once("lib/InputHelper.php");
        include "pages/admin/manageInventory.php";
    }

    /* Plugin SideBar Widget */

    /*************************************************************************************************/
    // Widget loader
    function DealerCloud_widget($args)
    {
        extract($args);

        $options = get_option("widget_DealerCloud");

        echo $before_widget;
        echo $before_title;
        echo $options['title'];
        echo $after_title;
        $this->widget_DealerCloud();
        echo $after_widget;
    }

    /*************************************************************************************************/
    // Widget definition
    function widget_DealerCloud()
    {
        global $wpdb;

        $baseUrl = "";
        $sql     = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_content like '%[dealercloud]%' AND post_status='publish' LIMIT 1";
        $results = $wpdb->get_results($sql);
        if (count($results) == 1) {
            $baseUrl = get_permalink($results[0]->ID);
        }

        $options = get_option("widget_DealerCloud");
        $number  = $options['number'];
        $make    = $options['make'];
        $model   = $options['model'];
        $ids     = $options['ids'];

        $p       = isset($_REQUEST['p']) ? $_REQUEST['p'] : "";
        $m       = isset($_REQUEST['m']) ? $_REQUEST['m'] : "";
        $page_id = isset($_REQUEST['page_id']) ? $_REQUEST['page_id'] : "";
        $cat     = isset($_REQUEST['cat']) ? $_REQUEST['cat'] : "";

        $permalink_structure = get_option('permalink_structure');
        if ($permalink_structure == '') {
            $baseUrl .= "?page_id=" . $page_id . "&cat=" . $cat . "&p=" . $p . "&m=" . $m . "&";
        } else {
            $baseUrl .= "?";
        }
        $folder = WP_PLUGIN_URL . '/DealerCloud';
        $client = new Client();

        if ($ids != '') {
            $_ids     = split(",", $ids);
            $vehicles = array('total_count' => 0, 'list' => array());
            $total    = 0;
            for ($i = 0; $i < count($_ids); $i++) {
                $vehicle = $client->getVehicle($_ids[$i]);
                if ($vehicle) {
                    $vehicles['list'][] = $vehicle;
                    $total++;
                    if ($total == $number) {
                        break;
                    }
                }
            }
            $vehicles['total_count'] = $total;
        } else {
            $vehicles = $client->GetVehicles(
                array(
                    "make"       => $make,
                    "model"      => $model,
                    "min_price"  => '',
                    "max_price"  => '',
                    "min_year"   => '',
                    "max_year"   => '',
                    "keyword"    => '',
                    "stock_type" => '',
                    "featured"   => '',
                    "special"    => '',
                    "class_code" => '',
                    "page"       => '1',
                    "page_size"  => $number,
                    "sort_by"    => 'make',
                    "sort_type"  => 'asc'
                ));
        }

        if ($vehicles["total_count"] > 0 && array_key_exists("list", $vehicles)) {
            foreach ($vehicles["list"] as $veh) {
                if (count($veh["photos"]) > 0) {
                    $firstImage = $veh["photos"][0];
                } else {
                    $firstImage = $folder . "/res/img/noimage3.gif";
                }
                ?>
                <div class="Wgallery">
                    <a href="<?= $client->GetVehicleUrl($baseUrl, $veh) ?>"><img src="<?= $firstImage ?>" width="125" alt="<?= $veh["year"] . " " . $veh["make"] . " " . $veh["model"] ?>"/></a>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="nocarsinfomessage">
                We could not find any vehicles
            </div>
            <?php
        }
    }

    /*************************************************************************************************/
    // Widget configuration sidebar control
    function DealerCloud_control()
    {
        $folder  = WP_PLUGIN_URL . '/DealerCloud';
        $options = get_option("widget_DealerCloud");

        if (!is_array($options)) {
            $options = array(
                'title'  => 'DealerCloud',
                'make'   => '',
                'model'  => '',
                'ids'    => '',
                'random' => '0',
                'number' => '3',
            );
        }

        if (isset($_POST['sideDealerCloud-Submit']) && $_POST['sideDealerCloud-Submit']) {
            $options['title']  = htmlspecialchars($_POST['sideDealerCloud-WidgetTitle']);
            $options['make']   = htmlspecialchars($_POST['sideDealerCloud-VehiclesMake']);
            $options['model']  = htmlspecialchars($_POST['sideDealerCloud-VehiclesModel']);
            $options['ids']    = htmlspecialchars($_POST['sideDealerCloud-VehiclesIDS']);
            $options['random'] = isset($_POST['sideDealerCloud-random']) ? '1' : '0';
            $options['number'] = htmlspecialchars($_POST['sideDealerCloud-VehiclesNumber']);
            if ($options['number'] > 5) {
                $options['number'] = 5;
            }

            update_option("widget_DealerCloud", $options);
        }

        $client = new Client();
        $makes  = $client->GetMakes();
        ?>
        <script src="<?= $folder ?>/res/js/jquery-1.3.min.js"></script>
        <script src="<?= $folder ?>/res/js/jquery.corner.js"></script>
        <script src="<?= $folder ?>/res/js/awe.js"></script>
        <script src="<?= $folder ?>/res/js/make-model.js"></script>
        <script type="text/javascript">
            var SITE_FOLDER = "<?=$folder?>";
        </script>

        <p>
            <label for="sideFeature-WidgetTitle">
                <b>Title:</b>
            </label><br/>
            Title of sidebar showing vehicles<br/>
            <input class="widefat" type="text" id="sideDealerCloud-WidgetTitle" name="sideDealerCloud-WidgetTitle" value="<?= $options['title'] ?>"/>
            <br/><br/>
            <label for="sideDealerCloud-VehiclesNumber">
                <b>Number of vehicles to show:</b>
            </label>
            <input type="text" id="sideDealerCloud-VehiclesNumber" name="sideDealerCloud-VehiclesNumber" style="width: 25px; text-align: center;" maxlength="1" value="<?= $options['number'] ?>"/><br/>
            <small>
                <em>(max 5)</em>
            </small>
            <br/><br/>
            You can set to have specific make or model show<br/>
            <label for="sideDealerCloud-VehiclesMake">
                <b>Make:</b>
            </label><br/>
            <select name="sideDealerCloud-VehiclesMake" id="sideDealerCloud-VehiclesMake" style="width:125px" onChange="getModels();">
                <option value=""></option>
                <!--<option value="">-- Any --</option>-->
                <?php
                foreach ($makes as $m) {
                    $selected = "";
                    if ($options['make'] == $m) {
                        $selected = "selected";
                    }
                    ?>
                    <option
                        <?= $selected ?> value='<?= $m ?>'><?= $m ?>
                    </option>
                    <?php
                }
                ?>
            </select>
            <br/><br/>
            <label for="sideDealerCloud-VehiclesModel">
                <b>Model:</b>
            </label><br/>
            <select name="sideDealerCloud-VehiclesModel" id="sideDealerCloud-VehiclesModel" style="width: 125px;">
                <option value=""></option>
                <!--<option value="">-- Any --</option>-->
                <?php
                if ($options['make'] != "" && isset($_SESSION[$options['make'] . "_models"])) {
                    $models = $_SESSION[$options['make'] . "_models"];
                    foreach ($models as $m) {
                        $selected = "";
                        if ($options['make'] == $m) {
                            $selected = "selected";
                        }
                        ?>
                        <option <?= $selected ?> value='<?= $m ?>'><?= $m ?></option>
                        <?php
                    }
                }
                ?>
            </select>
            <br/><br/>
            <label for="sideDealerCloud-VehiclesIDS">
                <b>ID's:</b>
            </label><br/>
            Only show specific vehicles<br/>
            <input class="widefat" type="text" id="sideDealerCloud-VehiclesIDS" name="sideDealerCloud-VehiclesIDS" value="<?= $options['ids'] ?>"/><br/>
            <small>
                <em>(comma separated vehicle ids)</em>
            </small>

            <input type="hidden" id="sideDealerCloud-Submit" name="sideDealerCloud-Submit" value="1"/>
        </p>
        <?php
    }
}

// Create the DealerCloud PlugIn object
$dc_plugin = new DealerCloud_plugin();

?>