<?php
global $wpdb;

$baseUrl = "";
$sql     = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_content like '%[dealercloud]%' AND post_status='publish' LIMIT 1";
$results = $wpdb->get_results($sql);
if (count($results) == 1) {
    $baseUrl = get_permalink($results[0]->ID);
}
$baseUrl .= "?";

$permalink_structure = get_option('permalink_structure');
if ($permalink_structure == '') {
    $p       = isset($_REQUEST['p']) ? InputHelper::get($_REQUEST,'p') : "";
    $m       = isset($_REQUEST['m']) ? InputHelper::get($_REQUEST,'m') : "";
    $page_id = isset($_REQUEST['page_id']) ? InputHelper::get($_REQUEST,'page_id') : "";
    $cat     = isset($_REQUEST['cat']) ? InputHelper::get($_REQUEST,'cat') : "";
    $baseUrl .= "page_id=" . $page_id . "&cat=" . $cat . "&p=" . $p . "&m=" . $m . "&";
}

$options = get_option("DealerCloud_Widget");
$number  = isset($options['number']) ? $options['number']: 3;
$ids     = isset($options['ids']) ? $options['ids']: '';
$make    = isset($options['make']) ? $options['make']: '';
$model   = isset($options['model']) ? $options['model']: '';

$folder = WP_PLUGIN_URL . '/DealerCloud';
$client = new Client();

if ($ids != '') {
    $_ids     = explode(",", $ids);
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
            "page_size"  => (string)$number,
            "sort_by"    => 'year',
            "sort_type"  => 'desc'
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
?>