<?php
/*
Plugin Name: DealerCloud
Description: DealerCloud WordPress Plugin
Version: 1.0
Author: Auto Web Engine LLC
Author URI: http://www.autowebengine.com
*/
require_once("lib/Client.php");
require_once("lib/InputHelper.php");
require_once("lib/ReCaptcha.php");

/*************************************************************************************************/
// DealerCloud Plugin Class for WordPress sites using DealerCloud accounts
/*************************************************************************************************/

class DealerCloud_plugin {

/*************************************************************************************************/
// Constructor
    function __construct() {
        add_action('init', array(&$this, 'DealerCloud_init'));
        add_action('wp_print_styles', array(&$this, 'DealerCloud_loadcss_head'));
        add_action( 'wp_enqueue_scripts', array(&$this, 'DealerCloud_enqueue_scripts' ));
        add_shortcode('dealercloud', array(&$this, '_dealercloud'));
    }

/*************************************************************************************************/
// Initialization
    function DealerCloud_init() {
        global $wpdb;

        add_action('admin_menu', array(&$this, 'DealerCloud_config_page'));
//        wp_register_sidebar_widget(__('DealerCloud'), __('DealerCloud'), array(&$this, 'DealerCloud_widget'));
//        wp_register_widget_control('DealerCloud', 'DealerCloud', array(&$this, 'DealerCloud_control'));
    }

/*************************************************************************************************/
// Configuration page definition
    function DealerCloud_config_page() {
        add_submenu_page('plugins.php', __('DealerCloud Configuration'), __('DealerCloud Configuration'), 'manage_options', 'DealerCloud-key-config', array(&$this, 'DealerCloud_conf'));
    }

/*************************************************************************************************/
// CSS loader
    function DealerCloud_loadcss_head() {
        $styles = WP_PLUGIN_URL . '/DealerCloud/res/css/styles.css?ver=1.04';

        wp_register_style('DealerCloud_styles', $styles);
        wp_enqueue_style('DealerCloud_styles');
    }

/*************************************************************************************************/
// Pass Config to JS
    function DealerCloud_enqueue_scripts() {
        wp_enqueue_script(
            'modeljs',
            plugins_url('res/js/make-model.js', __FILE__)
        );

        $config = new Config();

        $scriptData = array(
            'aweAPIURL' => $config->aweAPIURL,
            'aweAPIKey' => $config->aweAPIKey,
            'appName' => $config->appName,
            'recaptchaPublicKey' => $config->recaptchaPublicKey,
            'recaptchaPrivateKey' => $config->recaptchaPrivateKey,
            'model' => "",
        );

        wp_localize_script('modeljs', 'awe_options', $scriptData);
    }

/*************************************************************************************************/
// Widget primer
    function DealerCloud_widget($args) {
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
// Widget
    function widget_DealerCloud() {
        global $wpdb;

        $baseUrl = "";
        $sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_content like '%[dealercloud]%' AND post_status='publish' LIMIT 1";
        $results = $wpdb->get_results($sql);
        if (count($results) == 1) {
            $baseUrl = get_permalink($results[0]->ID);
        }

        $options = get_option("widget_DealerCloud");
        $number = $options['number'];
        $make = $options['make'];
        $model = $options['model'];
        $ids = $options['ids'];

        $p = isset($_REQUEST['p']) ? $_REQUEST['p'] : "";
        $m = isset($_REQUEST['m']) ? $_REQUEST['m'] : "";
        $page_id = isset($_REQUEST['page_id']) ? $_REQUEST['page_id'] : "";
        $cat = isset($_REQUEST['cat']) ? $_REQUEST['cat'] : "";

        $permalink_structure = get_option('permalink_structure');
        if ($permalink_structure == '') {
            $baseUrl .= "?page_id=".$page_id."&cat=".$cat."&p=".$p."&m=".$m."&";
        } else {
            $baseUrl .= "?";
        }
        $folder = WP_PLUGIN_URL.'/DealerCloud';

        $client = new Client();

        if ($ids != '') {
            $_ids = split(",", $ids);
            $vehicles = array('total_count'=>0, 'list'=>array());
            $total = 0;
            for($i=0; $i<count($_ids); $i++) {
                $vehicle = $client->getVehicle($_ids[$i]);
                if ($vehicle) {
                    $vehicles['list'][] = $vehicle;
                    $total++;
                    if ($total == $number)
                        break;
                }
            }
            $vehicles['total_count'] = $total;
        } else {
            $vehicles = $client->GetVehicles(
                array(
                    "make" => $make,
                    "model" => $model,
                    "min_price" => '',
                    "max_price" => '',
                    "min_year" => '',
                    "max_year" => '',
                    "keyword" => '',
                    "stock_type" => '',
                    "featured" => '',
                    "special" => '',
                    "class_code" => '',
                    "page" => '1',
                    "page_size" => $number,
                    "sort_by" => 'make',
                    "sort_type" => 'asc'
                ));
        }

        if ($vehicles["total_count"] > 0 && array_key_exists("list", $vehicles)) {
            foreach($vehicles["list"] as $veh) {
                if(count($veh["photos"]) > 0) {
                    $firstImage = $veh["photos"][0];
                } else {
                    $firstImage = $folder."/res/img/noimage3.gif";
                }
?>
<div class="Wgallery">
  <a href="<?=getSEOUrl($baseUrl, $veh)?>"><img src="<?=$firstImage?>" width="125" alt="<?=$veh["year"]." ".$veh["make"]." ".$veh["model"]?>" /></a>
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
    function DealerCloud_control() {
        $folder = WP_PLUGIN_URL.'/DealerCloud';
        $options = get_option("widget_DealerCloud");

        if (!is_array($options)) {
            $options = array(
                'title' => 'DealerCloud',
                'make' => '',
                'model' => '',
                'ids' => '',
                'random' => '0',
                'number' => '3',
            );
        }

        if (isset($_POST['sideDealerCloud-Submit']) && $_POST['sideDealerCloud-Submit']) {
            $options['title'] = htmlspecialchars($_POST['sideDealerCloud-WidgetTitle']);
            $options['make'] = htmlspecialchars($_POST['sideDealerCloud-VehiclesMake']);
            $options['model'] = htmlspecialchars($_POST['sideDealerCloud-VehiclesModel']);
            $options['ids'] = htmlspecialchars($_POST['sideDealerCloud-VehiclesIDS']);
            $options['random'] = isset($_POST['sideDealerCloud-random'])?'1':'0';
            $options['number'] = htmlspecialchars($_POST['sideDealerCloud-VehiclesNumber']);
            if ($options['number'] > 5) {
                $options['number'] = 5;
            }

            update_option("widget_DealerCloud", $options);
        }

        $client = new Client();
        $makes = $client->GetMakes();
?>
<script src="<?=$folder?>/res/js/jquery-1.3.min.js"></script>
<script src="<?=$folder?>/res/js/jquery.corner.js"></script>
<script src="<?=$folder?>/res/js/awe.js"></script>
<script src="<?=$folder?>/res/js/make-model.js"></script>
<script type="text/javascript">
  var SITE_FOLDER = "<?=$folder?>";
</script>

<p>
  <label for="sideFeature-WidgetTitle">
    <b>Title:</b>
  </label><br />
  Title of sidebar showing vehicles<br />
  <input class="widefat" type="text" id="sideDealerCloud-WidgetTitle" name="sideDealerCloud-WidgetTitle" value="<?=$options['title']?>" />
  <br /><br />
  <label for="sideDealerCloud-VehiclesNumber">
    <b>Number of vehicles to show:</b>
  </label>
  <input type="text" id="sideDealerCloud-VehiclesNumber" name="sideDealerCloud-VehiclesNumber" style="width: 25px; text-align: center;" maxlength="1" value="<?=$options['number']?>" /><br />
  <small>
    <em>(max 5)</em>
  </small>
  <br /><br />
  You can set to have specific make or model show<br />
  <label for="sideDealerCloud-VehiclesMake">
    <b>Make:</b>
  </label><br />
  <select name="sideDealerCloud-VehiclesMake" id="sideDealerCloud-VehiclesMake" style="width:125px" onChange="getModels();">
    <option value=""></option>
    <!--<option value="">-- Any --</option>-->
<?php
    foreach($makes as $m) {
        $selected = "";
        if($options['make'] == $m) {
            $selected = "selected";
        }
?>
    <option
      <?=$selected?> value='<?=$m?>'><?=$m?>
    </option>
<?php
    }
?>
  </select>
  <br /><br />
  <label for="sideDealerCloud-VehiclesModel">
    <b>Model:</b>
  </label><br />
  <select name="sideDealerCloud-VehiclesModel" id="sideDealerCloud-VehiclesModel" style="width: 125px;">
    <option value=""></option>
    <!--<option value="">-- Any --</option>-->
<?php
    if($options['make'] != "" && isset($_SESSION[$options['make'] . "_models"])) {
        $models = $_SESSION[$options['make'] . "_models"];
        foreach($models as $m) {
            $selected = "";
            if($options['make'] == $m) {
                $selected = "selected";
            }
?>
    <option <?=$selected?> value='<?=$m?>'><?=$m?></option>
<?php
        }
    }
?>
  </select>
  <br /><br />
  <label for="sideDealerCloud-VehiclesIDS">
    <b>ID's:</b>
  </label><br />
  Only show specific vehicles<br />
  <input class="widefat"  type="text" id="sideDealerCloud-VehiclesIDS" name="sideDealerCloud-VehiclesIDS" value="<?=$options['ids']?>" /><br />
  <small>
    <em>(comma separated vehicle ids)</em>
  </small>

  <input type="hidden" id="sideDealerCloud-Submit" name="sideDealerCloud-Submit" value="1" />
</p>
<?php
    }

/*************************************************************************************************/
// Plugin configuration page
    function DealerCloud_conf() {
        if ( isset($_POST['submit']) ) {
            if ( function_exists('current_user_can') && !current_user_can('manage_options') )
                die(__('Cheatin&#8217; uh?'));

            //check_admin_referer( $akismet_nonce );
            $key = $_POST['key'];
            $recaptcha_public_key = $_POST['recaptcha_public_key'];
            $recaptcha_private_key = $_POST['recaptcha_private_key'];
//            $google_maps_key = $_POST['google_maps_key'];
            $dealers_domain = $_POST['dealers_domain'];

            if (empty($key)) {
                delete_option('awe_api_key');
            } else {
                update_option('awe_api_key', $key);
            }
            if (empty($recaptcha_public_key)) {
                delete_option('recaptcha_public_key');
            } else {
                update_option('recaptcha_public_key', $recaptcha_public_key);
            }
            if (empty($recaptcha_private_key)) {
                delete_option('recaptcha_private_key');
            } else {
                update_option('recaptcha_private_key', $recaptcha_private_key);
            }
//            if (empty($google_maps_key)) {
//                delete_option('google_maps_key');
//            } else {
//                update_option('google_maps_key', $google_maps_key);
//            }
            if (empty($dealers_domain)) {
                delete_option('dealers_domain');
            } else {
                update_option('dealers_domain', $dealers_domain);
            }
        }

        $key = get_option('awe_api_key');
        $recaptcha_public_key = get_option('recaptcha_public_key');
        $recaptcha_private_key = get_option('recaptcha_private_key');
//        $google_maps_key = get_option('google_maps_key');
        $dealers_domain = get_option('dealers_domain');

        if ( !empty($_POST ) ) {
?>
<div id="message" class="updated fade">
  <p>
    <strong>
      <?=_e('Options saved.')?>
    </strong>
  </p>
</div>
<?php
        }
?>
<div class="wrap">
  <h2>
    <?=_e('DealerCloud Configuration')?>
  </h2>
  <br />A DealerCloud account at <a href="http://www.dealercloud.com" target="_blank">www.dealercloud.com</a> is required in order to use this plugin.
  <p>
    To have your inventory show up:
    <ul style="margin-left:50px;list-style-type:disc;">
      <li>Create a new WordPress Page</li>
      <li>
        Add <b>[dealercloud]</b> to the html of that page
      </li>
      <li>Save and publish the page</li>
    </ul>
  </p>
  <div class="narrow" style="margin-top:50px; margin-left:50px;">
    <form method="post" id="DealerCloud-conf">
      <p>
        <h3>
          <label for="key">
            <?=_e("Dealer's Website Token")?>
          </label>
        </h3>
        Found in the DealerCloud dealer account info.<br />
        <input id="key" name="key" type="text" value="<?=$key?>" style="width:400px;" />
      </p>
      <p>
        <h3>
          <label for="dealers_domain">
            <?=_e("Dealer's User Name")?>
          </label>
        </h3>
        Found in the DealerCloud dealer account info.<br />
        <input id="dealers_domain" name="dealers_domain" type="text" value="<?=$dealers_domain?>" style="width:400px;" />
      </p>
      <p>
        <h3>
          <label for="recaptcha_public_key">
            <?=_e('reCAPTCHA Site Key')?>
          </label>
        </h3>
        Can be acquired at <a href="http://www.recaptcha.net" target="_blank">https://www.google.com/recaptcha/admin</a><br />
        <input id="recaptcha_public_key" name="recaptcha_public_key" type="text" value="<?=$recaptcha_public_key?>" style="width:400px;" />
      </p>
      <p>
        <h3>
          <label for="recaptcha_private_key">
            <?=_e('reCAPTCHA Secret Key')?>
          </label>
        </h3>
        Found in the recaptcha site admin info.<br />
        <input id="recaptcha_private_key" name="recaptcha_private_key" type="text" value="<?=$recaptcha_private_key?>" style="width:400px;" />
      </p>
      <p class="submit">
        <input type="submit" name="submit" value="<?=_e('Save settings')?>" />
      </p>
      <!--    <p><h3><label for="google_maps_key"><?=_e('Google Maps API Key')?></label></h3>
    Can be acquired at <a href="http://code.google.com/apis/maps/signup.html" target="_blank">Google.com</a><br />
    <input id="google_maps_key" name="google_maps_key" type="text" value="<?=$google_maps_key?>" style="width:400px;" /></p>-->
    </form>
  </div>
</div>
<?php
    }

/*************************************************************************************************/
// Sort handler
    function getSortingUrl($baseUrl, $vars, $sortColumn) {
        if($sortColumn == $vars->sortBy) {
            $newSortType = $vars->sortType == "asc" ? "desc" : "asc";
        } else {
            $newSortType = "asc";
        }
        $link = "";
        $link .="page_num=" . $vars->page;
        $link .= "&sortBy=" .$sortColumn;
        $link .= "&sortType=" .$newSortType;
        $link .= "&disp=" .$vars->display;
        $link .= "&make=" .$vars->make;
        $link .= "&model=" .$vars->model;
        $link .= "&minYear=" .$vars->minYear;
        $link .= "&maxYear=" .$vars->maxYear;
        $link .= "&minPrice=" .$vars->minPrice;
        $link .= "&maxPrice=" .$vars->maxPrice;
        $link .= "&keyword=" .$vars->keyword;
        $link .= "&stockType=" .$vars->stockType;
        $link .= "&feat=" .$vars->feat;
        $link .= "&spec=" .$vars->spec;
        $link .= "&classCode=" .$vars->classCode;

        return $baseUrl.$link;
    }

/*************************************************************************************************/
// List / Gallery handler
    function getDisplayUrl($baseUrl, $vars) {

        if($vars->display == "" || $vars->display == "L") {
            $disp = "G";
        } else {
            $disp = "L";
        }

        $link = "";
        $link .="page_num=" . $vars->page;
        $link .= "&sortBy=" . $vars->sortBy;
        $link .= "&sortType=" . $vars->sortType;
        $link .= "&disp=" . $disp;
        $link .= "&make=" . $vars->make;
        $link .= "&model=" . $vars->model;
        $link .= "&minYear=" . $vars->minYear;
        $link .= "&maxYear=" . $vars->maxYear;
        $link .= "&minPrice=" . $vars->minPrice;
        $link .= "&maxPrice=" . $vars->maxPrice;
        $link .= "&keyword=" . $vars->keyword;
        $link .= "&stockType=" . $vars->stockType;
        $link .= "&feat=" . $vars->feat;
        $link .= "&spec=" . $vars->spec;
        $link .= "&classCode=" . $vars->classCode;

        return $baseUrl.$link;
    }

/*************************************************************************************************/
// Page handler
    function getPagingUrl($baseUrl, $vars, $page) {
        $link = "";
        $link .="page_num=" . $page;
        $link .="&sortBy=" . $vars->sortBy;
        $link .="&sortType=" . $vars->sortType;
        $link .="&disp=" . $vars->display;
        $link .="&make=" . $vars->make;
        $link .="&model=" . $vars->model;
        $link .="&minYear=" . $vars->minYear;
        $link .="&maxYear=" . $vars->maxYear;
        $link .="&minPrice=" . $vars->minPrice;
        $link .="&maxPrice=" . $vars->maxPrice;
        $link .="&keyword=" . $vars->keyword;
        $link .="&stockType=" . $vars->stockType;
        $link .="&feat=" . $vars->feat;
        $link .="&spec=" . $vars->spec;
        $link .="&classCode=" . $vars->classCode;

        return $baseUrl.$link;
    }

/*************************************************************************************************/
// Details page loader
    function getSEOUrl($baseUrl, $veh) {
        if (substr($baseUrl, strlen($baseUrl)-1, 1) == "?") {
            return $baseUrl."action/view_details/".$veh["id"]."/".$veh["year"]."-".$veh["make"]."-".$veh["model"]."/".str_replace(" ", "-", trim($veh["dealer"]["city"]))."/".str_replace(" ", "-", $veh["dealer"]["company"]);
        }
        return $baseUrl.'action=view_details&id='.$veh["id"];
    }

/*************************************************************************************************/
/*************************************************************************************************/
// Main Plugin
    function _dealercloud($atts, $content=null) {
        $p = InputHelper::get($_REQUEST,'p');
        $m = InputHelper::get($_REQUEST,'m');
        $page_id = InputHelper::get($_REQUEST,'page_id');
        $cat = InputHelper::get($_REQUEST,'cat');

        $permalink_structure = get_option('permalink_structure');
        if ($permalink_structure == '') {
            $baseUrl = "?page_id=".$page_id."&cat=".$cat."&p=".$p."&m=".$m."&";
        } else {
            $baseUrl = "?";
        }

        $ru = $_SERVER['REQUEST_URI'];
        $i1 = strpos($ru, 'action/view_details/');
        if ($i1 !== false) {
            $i1 += 20;
            $i2 = strpos($ru, '/', $i1);
            if ($i2 === false)
                $_REQUEST['id'] = substr($ru, $i1);
            else
                $_REQUEST['id'] = substr($ru, $i1, $i2-$i1);
            $_REQUEST['action'] = 'view_details';
        }

        $folder = WP_PLUGIN_URL.'/DealerCloud';

        $client = new Client();

        $rest = '<script type="text/javascript" src="'.$folder.'/res/js/jquery-1.3.min.js"></script>
        <script type="text/javascript" src="'.$folder.'/res/js/jquery.corner.js"></script>
        <script type="text/javascript" src="'.$folder.'/res/js/awe.js"></script>';

        $vars = (object)array(
            "make" => InputHelper::get($_REQUEST, "make"),
            "model" => InputHelper::get($_REQUEST, "model"),
            "minPrice" => InputHelper::get($_REQUEST, "minPrice"),
            "maxPrice" => InputHelper::get($_REQUEST, "maxPrice"),
            "minYear" => InputHelper::get($_REQUEST, "minYear"),
            "maxYear" => InputHelper::get($_REQUEST, "maxYear"),
            "keyword" => InputHelper::get($_REQUEST, "keyword"),
            "stockType" => InputHelper::get($_REQUEST, "stockType"),
            "feat" => InputHelper::get($_REQUEST, "feat"),
            "spec" => InputHelper::get($_REQUEST, "spec"),
            "classCode" => InputHelper::get($_REQUEST, "classCode"),
            "sortBy" => InputHelper::get($_REQUEST, "sortBy", "make"),
            "sortType" => InputHelper::get($_REQUEST, "sortType", "asc"),
            "display" => InputHelper::get($_REQUEST, "disp", "L"),
            "page" => InputHelper::get($_REQUEST, "page_num", "1") );

        if (InputHelper::get($_REQUEST, 'action') == 'view_details') {

            // Vehicle details page

            $vehicle = $client->getVehicle($_REQUEST["id"]);

            $dealer = $vehicle["dealer"];
            $photos = $vehicle["photos"];

            // CONTACT FORM SUBMIT HANDLER
            $firstName = "";
            $lastName = "";
            $phone = "";
            $email = "";
            $address = "";
            $city = "";
            $state = "";
            $zip = "";
            $message = "";
            if(isset($_POST["submitContact"]) && isset($_POST['g-recaptcha-response'])) {
                $recaptcha = new \ReCaptcha\ReCaptcha(get_option('recaptcha_private_key'));
                $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

                $firstName = InputHelper::get($_REQUEST, "firstName");
                $lastName = InputHelper::get($_REQUEST, "lastName");
                $phone = InputHelper::get($_REQUEST, "phone");
                $email = InputHelper::get($_REQUEST, "email");
                $address = InputHelper::get($_REQUEST, "address");
                $city = InputHelper::get($_REQUEST, "city");
                $state = InputHelper::get($_REQUEST, "state");
                $zip = InputHelper::get($_REQUEST, "zip");
                $message = InputHelper::get($_REQUEST, "message");

                if ($resp->isSuccess()) {
                    $err = "Error:<br />";
                    if($firstName == "" || $lastName == "") {
                        $err .= "First and Last name are required<br />";
                    }
                    if($email == "") {
                        $err .= "Email is required";
                    }
                    if($err != "Error:<br />") {
                        $sendMessageRes = "<div class='nosentMessage'>$err</div";
                    } else {
                        //submit message
                        $retVal = $client->SendMessage(
                            array(
                                "veh_id" => $vehicle["id"],
                                "first_name" => $firstName,
                                "last_name" => $lastName,
                                "phone" => $phone,
                                "email" => $email,
                                "address" => $address,
                                "city" => $city,
                                "state" => $state,
                                "zip_code" => $zip,
                                "message" => $message
                            ));
                        if($retVal) {
                            $sendMessageRes = "<div class='sentMessage'>Message sent successfully!</div>";
                        } else {
                            $sendMessageRes = "<div class='nosentMessage'>$err Failed to send message!<br />Please try again.</div>";
//                            $sendMessageRes = "<div class='nosentMessage'>"$err . $client->error . "</div>";
                        }
                    }
                } else {
                    # set the error code so that we can display it
//                    $sendMessageRes = "<div class='nosentMessage'><p>The following error was returned:";
//                    foreach ($resp->getErrorCodes() as $code) {
//                        $sendMessageRes .= "'<tt>' , $code , '</tt> '";
//                    }
//                    $sendMessageRes .= "</p></div>";
                    $sendMessageRes = "<div class='nosentMessage'>$err reCAPTCHA verification failed!</div>";
                }
            }

            // RECENTLY VIEWED ITEMS ADD COOKIE
//            $cookieName = "awerecent_" . $vehicle["id"];
//            setcookie($cookieName, time() . "||" . $vehicle["year"] . " " . $vehicle["make"] . " " . $vehicle["model"], time() + 604800, "/");

            if(isset($sendMessageRes)) {
                $rest .= $sendMessageRes;
            }

            // BEGIN PAGE HTML

            $rest .= '
    <div class="vehicleBackfg" id="vehBack_Round">
        <div class="detailsContainer">
            <div class="detailNavfg" id="vehNav_Round">
                <div class="detailsDealerHeader">';
            // Back to search
            $vars = isset($_SESSION["vars"]) ? $_SESSION["vars"] : null;
            $backUrl = $baseUrl;
            if($vars != null) {
                $backUrl .= "make=" . $vars->make;
                $backUrl .= "&model=" . $vars->model;
                $backUrl .= "&minYear=" . $vars->minYear;
                $backUrl .= "&maxYear=" . $vars->maxYear;
                $backUrl .= "&minPrice=" . $vars->minPrice;
                $backUrl .= "&maxPrice=" . $vars->maxPrice;
                $backUrl .= "&keyword=" . $vars->keyword;
                $backUrl .= "&sortBy=" . $vars->sortBy;
                $backUrl .= "&sortType=" . $vars->sortType;
                $backUrl .= "&feat=" . $vars->feat;
                $backUrl .= "&spec=" . $vars->spec;
                $backUrl .= "&classCode=" . $vars->classCode;
                $backUrl .= "&disp=" . $vars->display;
                $backUrl .= "&p=" . $vars->page;
            }
            $rest .= '<a href="'.$backUrl.'" class="backSearch">Back to search</a></div>
            </div>
            <div class="detailsHeader">
            <h2>';
            // Title
            if ($vehicle["cmpg"] >= "21") {
                $rest .= "<img src='$folder/res/img/leaf1.png' align='absmiddle'>";
            }
            $rest .= $vehicle["year"] . " " . $vehicle["make"] . " " . $vehicle["model"] . " " . $vehicle["trim"];
            $rest .= '<span style="text-transform:none; font-size:16px; line-height:26px;">';
            // Price
            $price = number_format(floatval($vehicle["price"]));
            if($price != 0) {
                $rest .= "<br />Your Price: <span style=\"font-size:16px; font-weight:bolder; color:#FF0000;\">$".$price."</span>";
            } else {
                $rest .= "[Contact for Price]";
            }

            // Photos
            $rest .= '</span>
               </h2>
            </div>

            <div class="photoDiv">
                <div class="photoRow">
                    <div class="mainPhoto">';
            $firstPhoto = "";
            if(is_array($photos) && count($photos) > 0) {
                $firstPhoto = $photos[0];
            }

            if( $firstPhoto == "" ) {
                $firstPhoto = $folder."/res/img/noimage.gif";
            }

            $photoString = $vehicle["year"] . " " . $vehicle["make"] . " " . $vehicle["model"] . " " . $vehicle["trim"] . " / " . $dealer["company"];
            if($dealer["city"] != "") {
                $photoString .= " / " . $dealer["city"];
            }
            if($dealer["state"] != "") {
                $photoString .= " / " . $dealer["state"];
            }
            if($dealer["zip_code"] != "") {
                $photoString .= " / " . $dealer["zip_code"];
            }
            $rest .= '
                <div class="gallery">
                    <img id="mainPhoto" src="'.$firstPhoto.'" class="pborder" border="0" title="Cars for sale / '.$photoString.'" alt="cars for sale / '.$photoString.'" width="400">
                </div>
                <div class="photoThumbs">';
            $photosShown = 0;
            if(is_array($photos) && count($photos) > 0) {
                foreach($photos as $photo) {
                    if($photosShown++ % 6 == 0) {
                        $rest .= "<br style='clear:both'/>";
                    }
                    $rest .= '<a href="javascript:swapPhoto(\'mainPhoto\', \''.$photo.'\');"><img src="'.$photo.'" class="pborder2" align="middle" border="0" width="70"></a>';
                }
            }
            // Details
            $rest .= '
                </div>
                <br />
                <div class="clear"></div>
                </div>
                <div class="detailsdiv2" id="details_Round">';

            if($vehicle["price"] != "") {
                $rest .= '<div class="detailsdiv" style="border-top:none;">Price: &nbsp;';
            if($price != 0) {
                $rest .= "$" . $price;
            } else {
                $rest .= " [Contact for Price]";
            }
            $rest .= '
                </div>';
            }
            if(array_key_exists("vin", $vehicle) && $vehicle["vin"] != "") {
                $rest .= '<div class="detailsdiv">Vin: &nbsp;'.$vehicle["vin"].'</div>';
            }
            if(array_key_exists("stock", $vehicle) && $vehicle["stock"] != "") {
                $rest .= '<div class="detailsdiv">Stock #: &nbsp;'.$vehicle["stock"].'</div>';
            }
            if(array_key_exists("mileage", $vehicle) && $vehicle["mileage"] > 0) {
                $rest .= '<div class="detailsdiv">Mileage: &nbsp;'.number_format(floatval($vehicle["mileage"])).'</div>';
            }
            if(array_key_exists("exterior_color", $vehicle) && $vehicle["exterior_color"] != "") {
                $rest .= '<div class="detailsdiv">Exterior Color: &nbsp;'.$vehicle["exterior_color"].'</div>';
            }
            if(array_key_exists("interior_color", $vehicle) && $vehicle["interior_color"] != "") {
                $rest .= '<div class="detailsdiv">Interior Color: &nbsp;'.$vehicle["interior_color"].'</div>';
            }
            if(array_key_exists("body_door_count", $vehicle) && $vehicle["body_door_count"] > 0) {
                $rest .= '<div class="detailsdiv"># of Doors: &nbsp;'.$vehicle["body_door_count"].'</div>';
            }
            if(array_key_exists("engine", $vehicle) && $vehicle["engine"] != "") {
                $rest .= '<div class="detailsdiv">Engine: &nbsp;'.$vehicle["engine"].'</div>';
            }
            if(array_key_exists("trans", $vehicle) && $vehicle["trans"] != "") {
                $rest .= '<div class="detailsdiv">Trans: &nbsp;'.$vehicle["trans"].'</div>';
            }
            if(array_key_exists("drive", $vehicle) && $vehicle["drive"] != "") {
                $rest .= '<div class="detailsdiv">Drive: &nbsp;'.$vehicle["drive"].'</div>';
            }
            if(array_key_exists("classification", $vehicle) && $vehicle["classification"] != "") {
                $rest .= '<div class="detailsdiv">Class: &nbsp;'.$vehicle["classification"].'</div>';
            }
            if(array_key_exists("cmpg", $vehicle) && $vehicle["cmpg"] > 0) {
                $rest .= '<div class="detailsdiv">City / Hwy (mpg): &nbsp;'.$vehicle["cmpg"].' / '.$vehicle["hmpg"].'</div>';
            }

            // Buttons - Financing and CarFax
            if(true) {
                $rest .= '<br />
                <div class="buttons" id="buttons_Round">
                        <a href="#" style="border:none;">
                            <img src="'.$folder.'/res/img/applyfin.gif" alt="financing" style="border:none;" /></a><br />
                        <a style="border:none;" href="'.$vehicle["carfax"]["report_url"].'">
                            <img src="'.$vehicle["carfax"]["report_image"].'" style="border:none;" />
                        </a>';
                if($vehicle["carfax"]["one_owner"]) {
                    $rest .= '<br /><img src="'.$vehicle["carfax"]["one_owner_image"].'" />';
                }
                $rest .= '</div>';
            }
            $rest .= '</div>
            </div>';

            // Features
            if((array_key_exists("standard_features", $vehicle) && $vehicle["standard_features"] != "")||
               (array_key_exists("features", $vehicle) && $vehicle["features"] != "")) {
                $rest .= '
                <div class="featurestitle">
                    Features / Options
                </div>

                <div class="detailscontainer2">
                    <div style="clear:both" />
                    <div class="standardFeatures">
                        <ul>';
                $features = [];
                if(array_key_exists("features", $vehicle) && $vehicle["features"] != "") {
                    $features = array_merge($features, explode("|", $vehicle["features"]));
                }
                if(array_key_exists("standard_features", $vehicle) && $vehicle["standard_features"] != "") {
                    $features = array_merge($features, explode("|", $vehicle["standard_features"]));
                }
                foreach($features as $feat) {
                    if($feat != "") {
                        $features2 = explode( ",", $feat );
                        foreach( $features2 as $feat2 ) {
                            if( $feat2 != "" ) {
                                $rest .= '<li>'.trim($feat2).'</li>';
                            }
                        }
                    }
                }
                $rest .= '</ul>
                        </div>
                        <div class="featureClick">
                            Standard equipment and options shown. Some features may not be available.  Contact us for details.
                        </div>
                    </div>
                    <br />';
            }

            // Notes
            if(array_key_exists("comments", $vehicle) && $vehicle["comments"] != "") {

                $rest .= '
                <div class="featurestitle">
                    Notes
                </div>
                <div class="detailscontainer2">
                    <div>'.$vehicle["comments"].'</div>
                </div>
                <br />';
            }

            // Contact form
            $rest .= '';
            $rest .= '
            <div class="featurestitle">
                Contact Us About This Vehicle
            </div>
            <div class="detailscontainer2">
                <form id="f" name="f" method="post" action="'.$_SERVER["REQUEST_URI"].'">
                    <div>
                        Questions about this '.$vehicle["make"].'?  Please fill out the quick form below.
                    </div>
                    <br />
                    <div class="contactform">
                        <div class="contactformrow">
                            <div class="contactformcell">
                                First Name *
                                <br />
                                <input type="text" name="firstName" value="'.$firstName.'"/>
                            </div>
                            <div class="contactformcell">
                                Last Name *
                                <br />
                                <input type="text" name="lastName" value="'.$lastName.'"/>
                            </div>
                            <br style="clear:both" />
                            <div class="contactformcell">
                                Email *
                                <br />
                                <input type="text" name="email" value="'.$email.'"/>
                            </div>
                            <div class="contactformcell">
                                Phone
                                <br />
                                <input type="text" name="phone" value="'.$phone.'"/>
                            </div>
                        </div>
                    </div>
                    <br style="clear:both" />
                    <div class="contactform">
                            <div class="contactformcell2">
                                Comments
                                <br />
                                <textarea name="message" cols="40" rows="6">'.$message.'</textarea>
                                <br style="clear:both" />
                                <script src="https://www.google.com/recaptcha/api.js"></script>
                                <div class="verification">
                                    Verification *
                                    <br />
                                    <div class="g-recaptcha" data-sitekey="'.get_option('recaptcha_public_key').'"></div>
                                </div>
                                <br style="clear:both" />
                                <input name="submitContact" type="submit" value="submit" class="submit" />
                            </div>
                    </div>
                </form>
            </div>';

            $rest .= '<div>';
            /* Only show map if this is not a dealix vehicle */
            // Map
//            if(false && array_key_exists("dealix_vehicle_id", $vehicle) && $vehicle['dealix_vehicle_id'] == 0) {
//                $map = new PhoogleMap();
//                /**** Google Maps API Key Needs to be placed here****/
//                $map->setAPIKey($config->GoogleMapsAPIKey);
//                ob_start();
//                $map->printGoogleJS();
//                $map->addAddress($dealer["address1"] . ", " . $dealer["city"] . ", " . $dealer["state"] . " " . $dealer["zip_code"]);
//                $map->showMap();
//                $rest .= ob_get_clean();
//            }
            $rest .= '</div>
                  </div>
                </div>
              </div>
            </div>';
        } else if (InputHelper::get($_REQUEST, 'action') == 'clear_recent') {

            // Clear recent cookies

            //Killing the cookie:
            $cookie_name= "awerecent_" . $_GET["id"];

            //here we assign a "0" value to the cookie, i.e. disabling the cookie:
            $cookie_value="";

            //When deleting a cookie you should assure that the expiration date is in the past,
            //to trigger the removal mechanism in your browser.
            $cookie_expire = time() - 60;

            $cookie_domain="";
            setcookie($cookie_name, $cookie_value, $cookie_expire, "/", $cookie_domain,0);

            $backUrl = $baseUrl;
            if($vars != null) {
                $backUrl .= "make=" . $vars->make;
                $backUrl .= "&model=" . $vars->model;
                $backUrl .= "&minYear=" . $vars->minYear;
                $backUrl .= "&maxYear=" . $vars->maxYear;
                $backUrl .= "&minPrice=" . $vars->minPrice;
                $backUrl .= "&maxPrice=" . $vars->maxPrice;
                $backUrl .= "&keyword=" . $vars->keyword;
                $backUrl .= "&sortBy=" . $vars->sortBy;
                $backUrl .= "&sortType=" . $vars->sortType;
                $backUrl .= "&feat=" . $vars->feat;
                $backUrl .= "&spec=" . $vars->spec;
                $backUrl .= "&classCode=" . $vars->classCode;
                $backUrl .= "&disp=" . $vars->display;
                $backUrl .= "&p=" . $vars->page;
            }
            header("Location:$backUrl");
            exit;

        } else {

            // Main homepage / inventory page / search page

            //set some default values
            if($vars->sortType != "asc" && $vars->sortType != "desc") {
                $vars->sortType = "asc";
            }
            if($vars->page == "" || $vars->page < 1) {
                $vars->page = 1;
            }

            $_SESSION["vars"] = $vars;

            $pagerSize = 10;
            $pageSize = 10;

            $vehicles = $client->GetVehicles(
                array(
                    "make" => $vars->make,
                    "model" => $vars->model,
                    "min_price" => $vars->minPrice,
                    "max_price" => $vars->maxPrice,
                    "min_year" => $vars->minYear,
                    "max_year" => $vars->maxYear,
                    "keyword" => $vars->keyword,
                    "stock_type" => $vars->stockType,
                    "featured" => $vars->feat,
                    "special" => $vars->spec,
                    "class_code" => $vars->classCode,
                    "page" => $vars->page,
                    "page_size" => $pageSize,
                    "sort_by" => $vars->sortBy,
                    "sort_type" => $vars->sortType
                ));

            // PAGING
            $totalPages = ceil($vehicles["total_count"] / $pageSize);

            $offset = $vars->page % 10;

            if($offset == 0) {
                $firstPage = $vars->page - $pagerSize;
            } else {
                $firstPage = $vars->page - $offset;
            }

            $lastPage = $firstPage + $pagerSize;

            if($firstPage > $totalPages) {
                $firstPage = $totalPages;
            }

            if($lastPage > $totalPages) {
                $lastPage = $totalPages;
            }

            // RECENTLY VIEWED
            $recent = array();
            foreach($_COOKIE as $k => $v) {
                if(substr($k, 0, 10) == 'awerecent_') {
                    $vPart = explode("||", $v);
                    $kPart = explode("_", $k);
                    $id = $kPart[1];
                    if($vPart[1] != "") {
                        $recent[] = array("time" =>$vPart[0], "veh" =>$vPart[1], "id" => $id);
                    }
                }
            }

            foreach($recent as $key => $row) {
                $time[$key]  = $row['time'];
                $veh[$key] = $row['veh'];
                $id[$key] = $row['id'];
            }

            if(count($recent) > 0) {
                array_multisort($time, SORT_DESC, $recent);
            }

            // SEARCH FORM

            // Makes
            $rest .= '
        <script type="text/javascript">
            var SITE_FOLDER = "'.$folder.'";
        </script>

        <script type="text/javascript" src="'.$folder.'/res/js/make-model.js"></script>
        <form name="frmFilter" action="'.$baseUrl.'" method="post">
        <div>
            <div class="blueRfg" id="search_Round">

        <div class="formdiv">
            <div class="form1">
                <label class="desc">Make</label>
                <select name="make" id="make" style="font-size:16px;width:100%" onChange="getModels();">
                <option value=""></option>';
//                <option value="">-- Any --</option>';
            $makes = $client->GetMakes();
            foreach($makes as $m) {
                $selected = "";
                if($vars->make == $m) {
                    $selected = "selected";
                }
                $rest .= "<option $selected value='$m'>$m</option>";
            }
            // Models
            $rest .= '
                </select>

                <label class="desc">Model</label>
                <select name="model" id="model" style="font-size:16px;width:100%">';
//                    <option value="">-- Any --</option>';
            if($vars->make != "" && $vars->model != "") {
                $models = $client->GetModels($vars->make);
                foreach($models as $m) {
                    $selected = "";
                    if($vars->model == $m) {
                        $selected = "selected";
                    }
                    $rest .= "<option $selected value='$m'>$m</option>";
                }
            }
            $rest .= '
                </select>
            </div>

            <div class="form2">
                <label class="desc">Min Price</label>
                <select name="minPrice" style="font-size:16px;width:100%">
                    <option value=""></option>';
            // Min Price
            for($minp = 0; $minp <= 100000; $minp += 5000) {
                $selected = "";
                if($minp == $vars->minPrice and $minp > 0) {
                    $selected = "selected";
                }
                $rest .= "<option $selected value='$minp'>\$$minp</option>";
            }
            // Max Price
            $rest .= '
            </select>

            <label class="desc">Max Price</label>
            <select name="maxPrice" style="font-size:16px;width:100%">
                <option value=""></option>';
            for($maxp = 100000; $maxp > 0; $maxp -= 5000) {
                $selected = "";
                if($maxp == $vars->maxPrice) {
                    $selected = "selected";
                }
                $rest .= "<option $selected value='$maxp'>\$$maxp</option>";
            }
            // Min Year
            $rest .= '
                </select>
            </div>
            <div class="form3">
                <label class="desc">Min Year</label>
                <select name="minYear" style="font-size:16px;width:98%">
                    <option value=""></option>';
            for($yearCnt = date("Y") + 1; $yearCnt >= date("Y") - 25; $yearCnt--){
                $selected = "";
                if($yearCnt == $vars->minYear) {
                    $selected = "selected";
                }
                $rest .= "<option $selected value='$yearCnt'>$yearCnt</option>";
            }
            // Max Year
            $rest .= '
                </select>

                <label class="desc">Max Year</label>
                <select name="maxYear" style="font-size:16px;width:98%">
                    <option value=""></option>';
            for($yearCnt = date("Y") + 1; $yearCnt >= date("Y") - 25; $yearCnt--){
                $selected = "";
                if($yearCnt == $vars->maxYear) {
                    $selected = "selected";
                }
                $rest .= "<option $selected value='$yearCnt'>$yearCnt</option>";
            }
            // Keyword and Submit
            $rest .= '
                </select>
            </div>

            <div class="form4">
                Keyword <input name="keyword" type="text" value="'.$vars->keyword.'" style="font-size:14px;width:74%" /> <input name="submit" type="submit" value="search" class="submit" style="font-size:12px;" />
            </div>

        </div>
    </div>
        </div>
    </form>';

            // TOTAL MATCHES BAR
            if($vehicles["total_count"] > 0) {
                $rest .= '
                <div class="infomessage" id="info_Round">
                <span><span style="font-family:Georgia; font-size:16px;">'.$vehicles["total_count"].'</span> vehicles match your search!</span>
                </div>';
            } else  {
                $rest .= '
                <div class="nocarsinfomessage id="info_Round"">
                    We could not find any matches, please search again.
                </div>';
            }

            // SORT CONTROL
            $rest .= '
            <div class="resultList">
            <div class="sortBy">Sort By:</div>
            <div class="sortLink">
                <div class="sorter">
                    <div style="width:65px;float:left;">'.($vars->sortBy == "year"?'<img src="'.$folder.'/res/img/'.$vars->sortType.'.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ':'').'<a href="'.$this->getSortingUrl($baseUrl, $vars, 'year').'">Year</a></div>
                    <div style="width:65px;float:left;">'.($vars->sortBy == "make"?'<img src="'.$folder.'/res/img/'.$vars->sortType.'.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ':'').'<a href="'.$this->getSortingUrl($baseUrl, $vars, 'make').'">Make</a></div>
                    <div style="width:68px;float:left;">'.($vars->sortBy == "model"?'<img src="'.$folder.'/res/img/'.$vars->sortType.'.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ':'').'<a href="'.$this->getSortingUrl($baseUrl, $vars, 'model').'">Model</a></div>
                    <div style="width:65px;float:left;">'.($vars->sortBy == "price"?'<img src="'.$folder.'/res/img/'.$vars->sortType.'.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ':'').'<a href="'.$this->getSortingUrl($baseUrl, $vars, 'price').'">Price</a></div>
                    <div style="width:65px;float:left;">'.($vars->sortBy == "mileage"?'<img src="'.$folder.'/res/img/'.$vars->sortType.'.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ':'').'<a href="'.$this->getSortingUrl($baseUrl, $vars, 'mileage').'">Miles</a></div>
                </div>
            </div>

            <div class="viewLink">
                <div class="sorter">';
            // Gallery or List
            if ($vars->display != "G") {
                $rest .= '<div style="width:105px;float:left;"><a href="'.$this->getDisplayUrl($baseUrl, $vars).'">Gallery View</a><img src="'.$folder.'/res/img/gallery.png" alt="Gallery" align="absmiddle" style="float:right;padding-top:5px;" /></div>';
            } else  {
                $rest .= '<div style="width:90px;float:left;"><a href="'.$this->getDisplayUrl($baseUrl, $vars).'">List View</a><img src="'.$folder.'/res/img/list.png" alt="List" align="absmiddle" style="float:right;padding-top:5px;" /></div>';
            }
            $rest .= '
                    </div>
                </div>
            </div>';

            // GALLERY VIEW
            if($vars->display == "G" && $vehicles["total_count"] > 0) {
                $pageCols=3;
                $rest .= '<div class="vresultsg">';
                $i = 0;
                foreach($vehicles["list"] as $veh) {
                    $rest .= '<div class="glayout">';
                    if(count($veh["photos"]) > 0) {
                        $firstImage = $veh["photos"][0];
                    } else {
                        $firstImage = $folder."/res/img/noimage3.gif";
                    }
                    $rest .= '
                        <div class="gallery">
                            <a href="'.$this->getSEOUrl($baseUrl, $veh).'"><img src="'.$firstImage.'" width="85" alt="'.$veh["year"] . " " . $veh["make"] . " " . $veh["model"].'" /></a>
                        </div>
                        <div  class="makes">
                            <a href="'.$this->getSEOUrl($baseUrl, $veh).'">'.($vars->sortBy == "year"?'<span class="sorthighlight">':'').$veh["year"].($vars->sortBy == "year"?'</span>':'').($vars->sortBy == "make"?'<span class="sorthighlight">':'').$veh["make"].($vars->sortBy == "make"?'</span>':'').($vars->sortBy == "model"?'<span class="sorthighlight">':'').$veh["model"].($vars->sortBy == "model"?'</span>':'').'
                            </a>
                        </div>
                    </div>';

                    $i++;
                    if($i % $pageCols == 0 ) {
                        $rest .= '<br style="clear:both" />';
                    }
                }
                $rest .= '</div>';

            // LIST VIEW
            } else if($vars->display == "L" && $vehicles["total_count"] > 0){
                $rest .= '
                <div>';
                $i = 1;
                foreach($vehicles["list"] as $veh) {
                    $cssClass = "resultstable" . $i;
                    $rest .='
                        <div class="'.$cssClass.'">
                            <div class="resultstablerow">
                                <div class="photocell">';
                    if(count($veh["photos"]) > 0) {
                        $firstImage = $veh["photos"][0];
                    } else {
                        $firstImage = $folder."/res/img/noimage3.gif";
                    }
                    $rest .= '
                          <div class="gallery">
                          <a href="'.$this->getSEOUrl($baseUrl, $veh).'"><img src="'.$firstImage.'" width="85" style="border:none;" alt="'.$veh["year"] . " " . $veh["make"] . " " . $veh["model"].' / '.$veh["dealer"]["company"].' " title="'.$veh["year"] . " " . $veh["make"] . " " . $veh["model"].' / '.$veh["dealer"]["company"].' " /></a>
                          </div>
                      </div>
                      <div class="desccell">
                          <div class="descfont">
                              <div class="descfont1">
                                  <a href="'.$this->getSEOUrl($baseUrl, $veh).'">
                                  '.$veh["year"].' '.$veh["make"].' '.$veh["model"].' '.$veh["trim"].'</a>'.($veh["cmpg"] >= "21"?'<img src="'.$folder.'/res/img/leaf1.png">':'').'
                              </div>
                          </div>
                          <div class="descfont2">';
                    if(number_format(floatval($veh["price"])) != 0) {
                        $rest .= "$" . number_format(floatval($veh["price"]));
                    } else {
                        $rest .= "<span style='color:#ff0000'>****</span>";
                    }
                    $rest .= ' | '.number_format(floatval($veh["mileage"])).' miles
                          </div>
                          <div class="summ">#' . $veh["stock"]
                    . ($veh["exterior_color"] != "" ? ", " . $veh["exterior_color"] : "")
                    . ($veh["interior_color"] != "" ? " | " . $veh["interior_color"] : "")
                    . ($veh["cmpg"] != "" ? ", " . $veh["cmpg"] . " mpg" : "");
                    $maxLength = 55;
                    $feat = explode("|", (array_key_exists("standard_features", $veh) ? $veh["standard_features"] : '') . "|" . (array_key_exists("features", $veh) ? $veh["features"] : ''));
                    $temp = "";
                    foreach($feat as $t) {
                        if($temp != "") {
                            $temp .= ", ";
                        }
                        $temp .= $t;
                    }
                    $rest .= substr($temp, 0, $maxLength);
                    if(strlen($temp) > $maxLength) {
                        $rest .= " ...";
                    }
                    $rest .= '
                          </div>
                          <div class="moredetails">';
//                                    $rest .= '<a href="'.$this->getSEOUrl($baseUrl, $veh).'">View Details</a> | <a href="'.$this->getSEOUrl($baseUrl, $veh).'">More Photos</a>';
                    $rest .= '</div>
                          </div>
                      </div>
                  </div>';
                  if( $i == 1 ){ $i++; } else { $i = 1; }
              }
              $rest .= '</div>';
            }

            // PAGE NAV
            $rest .= '
                 <br style="clear:both" />
                 <div class="pagination">
                     <ul>';
            if($firstPage >= 10) {
                $rest .= '<li><a href="'.$this->getPagingUrl($baseUrl, $vars, $firstPage).'"><< Prev 10</a></li>';
            }
            for($i = $firstPage + 1; $i <= $lastPage; $i++) {
                $rest .= '<li '.($i == $vars->page?'class="currentpage"':'').'><a href="'.$this->getPagingUrl($baseUrl, $vars, $i).'">'.$i.'</a></li>';
            }
            if($lastPage != $totalPages) {
                $rest .= '<li><a href="'.$this->getPagingUrl($baseUrl, $vars, $lastPage + 1).'">Next 10 >></a></li>';
            }
            $rest .= '
                    </ul>
                 </div>
                 <br />';

            // RECENTLY VIEWED
            if(count($recent) > 0) {
                $rest .= '
                 <div style="width:100%; text-align:left; margin-right:20px;">
                 Previously Viewed Vehicles...&nbsp;&nbsp;&nbsp;
                 </div>
                 <div class="recentviewfg" id="recent_Round">
                      <div class="recentList">';
                $count = 0;
                foreach($recent as $rec) {
                    $count++;
                    if($count > 10) {
                        break;
                    }
                $rest .= '<span class="recentSpan">&#8226; <a href="'.$baseUrl.'action=view_details&id='.$rec["id"].'">'.$rec["veh"].'</a>
                          </span>
                          (<a href="'.$baseUrl.'action=clear_recent&id='.$rec["id"].'" class="cleared"><span class="cleared">X</span></a>)
                          <br />';
                }
                $rest .= '
                        </div>
                    </div>';
            }
        }

        return $rest;
    }

/*************************************************************************************************/

}

// Create the PlugIn object
$dc_plugin = new DealerCloud_plugin();

?>