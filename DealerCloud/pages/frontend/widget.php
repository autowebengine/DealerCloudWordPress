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
    $p       = isset($_REQUEST['p']) ? InputHelper::get($_REQUEST, 'p') : "";
    $m       = isset($_REQUEST['m']) ? InputHelper::get($_REQUEST, 'm') : "";
    $page_id = isset($_REQUEST['page_id']) ? InputHelper::get($_REQUEST, 'page_id') : "";
    $cat     = isset($_REQUEST['cat']) ? InputHelper::get($_REQUEST, 'cat') : "";
    $baseUrl .= "page_id=" . $page_id . "&cat=" . $cat . "&p=" . $p . "&m=" . $m . "&";
}

$options  = get_option("DealerCloud_Widget");
$number   = isset($options['number']) ? $options['number'] : '3';
$ids      = isset($options['ids']) ? $options['ids'] : '';
$featured = isset($options['featured']) ? $options['featured'] : '0';
$sortby   = isset($options['sortby']) ? $options['sortby'] : 'year';
$sortdir  = isset($options['sortdir']) ? $options['sortdir'] : 'desc';
$make     = isset($options['make']) ? $options['make'] : '';
$model    = isset($options['model']) ? $options['model'] : '';

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
            "make"           => $make,
            "model"          => $model,
            "min_price"      => '',
            "max_price"      => '',
            "min_year"       => '',
            "max_year"       => '',
            "keyword"        => '',
            "featured_first" => $featured,
            "page"           => '1',
            "page_size"      => '10',
            "sort_by"        => $sortby,
            "sort_type"      => $sortdir
        ));
}

if ($vehicles["total_count"] > 0 && array_key_exists("list", $vehicles)) {
    $vehCount = 0;
    ?>
    <div class="photoDiv">
        <div class="photoRow">
            <div class="mainPhoto">
                <div class="photoThumbs" style="width: 320px;">
                    <?php
                    foreach ($vehicles["list"] as $veh) {
                        $vehCount++;
                        if (count($veh["photos"]) > 0) {
                            $firstImage = $veh["photos"][0];
                        } else {
                            $firstImage = $folder . "/res/img/noimage3.gif";
                        }
                        ?>
                        <a href="<?= $client->GetVehicleUrl($baseUrl, $veh) ?>"><img src="<?= $firstImage ?>" width="100" alt="<?= $veh["year"] . " " . $veh["make"] . " " . $veh["model"] ?>"/></a>
                        <?php
                        if ($vehCount == $number) {
                            break;
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="nocarsinfomessage">
        We could not find any vehicles
    </div>
    <?php
}
?>