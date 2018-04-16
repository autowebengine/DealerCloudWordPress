<?php

$client = new Client();

if (isset($_POST['Delete'])) {
    // Delete all selected vehicles
    $vehCount = $_POST['vehCount'];
    for ($i = 1; $i <= $vehCount; $i++) {
        $checkboxName = "vehicle" . $i;
        if (isset($_POST[$checkboxName])) {
            ?>
            <script>
                alert('<?= "Deleting selected vehicle: " . $_POST[$checkboxName] ?>');
            </script>
            <?php
            // TODO: Call to delete each vehicle
            $client->DeleteVehicle($_POST[$checkboxName]);
        }
    }
}

$folder = WP_PLUGIN_URL . '/DealerCloud';
echo '<script type="text/javascript" src="' . $folder . '/res/js/jquery-1.3.min.js"></script>';
echo '<script type="text/javascript" src="' . $folder . '/res/js/jquery.corner.js"></script>';
echo '<script type="text/javascript" src="' . $folder . '/res/js/awe.js"></script>';

$vars = (object)array(
    "make"      => InputHelper::get($_REQUEST, "make"),
    "model"     => InputHelper::get($_REQUEST, "model"),
    "minPrice"  => InputHelper::get($_REQUEST, "minPrice"),
    "maxPrice"  => InputHelper::get($_REQUEST, "maxPrice"),
    "minYear"   => InputHelper::get($_REQUEST, "minYear"),
    "maxYear"   => InputHelper::get($_REQUEST, "maxYear"),
    "keyword"   => InputHelper::get($_REQUEST, "keyword"),
    "stockType" => InputHelper::get($_REQUEST, "stockType"),
    "feat"      => InputHelper::get($_REQUEST, "feat"),
    "spec"      => InputHelper::get($_REQUEST, "spec"),
    "classCode" => InputHelper::get($_REQUEST, "classCode"),
    "sortBy"    => InputHelper::get($_REQUEST, "sortBy", "make"),
    "sortType"  => InputHelper::get($_REQUEST, "sortType", "asc"),
    "display"   => InputHelper::get($_REQUEST, "disp", "L"),
    "page"      => InputHelper::get($_REQUEST, "page_num", "1")
);

//set some default values
if ($vars->sortType != "asc" && $vars->sortType != "desc") {
    $vars->sortType = "asc";
}

if ($vars->page == "" || $vars->page < 1) {
    $vars->page = 1;
}

$_SESSION["vars"] = $vars;

$pagerSize = 10;
$pageSize  = 10;

// GET ALL DATA
$makes = $client->GetMakes();

$vehicles = $client->GetVehicles(array(
    "make"       => $vars->make,
    "model"      => $vars->model,
    "min_price"  => $vars->minPrice,
    "max_price"  => $vars->maxPrice,
    "min_year"   => $vars->minYear,
    "max_year"   => $vars->maxYear,
    "keyword"    => $vars->keyword,
    "stock_type" => $vars->stockType,
    "featured"   => $vars->feat,
    "special"    => $vars->spec,
    "class_code" => $vars->classCode,
    "page"       => $vars->page,
    "page_size"  => $pageSize,
    "sort_by"    => $vars->sortBy,
    "sort_type"  => $vars->sortType
));

// PAGING
$totalPages = ceil($vehicles["total_count"] / $pageSize);

$offset = $vars->page % 10;

if ($offset == 0) {
    $firstPage = $vars->page - $pagerSize;
} else {
    $firstPage = $vars->page - $offset;
}

$lastPage = $firstPage + $pagerSize;

if ($firstPage > $totalPages) {
    $firstPage = $totalPages;
}

if ($lastPage > $totalPages) {
    $lastPage = $totalPages;
}

$key                   = get_option('awe_api_key');
$recaptcha_public_key  = get_option('recaptcha_public_key');
$recaptcha_private_key = get_option('recaptcha_private_key');
$google_maps_key       = get_option('google_maps_key');
$dealers_domain        = get_option('dealers_domain');
?>

<div class="wrap">
    <h2><?= _e('DealerCloud | Manage Inventory') ?></h2>
    <p>Remove existing vehicles here:</p>

    <div class="narrow" style="margin-left:100px;">
        <form method="post">
            <table class="table">
                <thead>
                <th>
                <td></td>
                <td>VIN</td>
                <td>Stock</td>
                <td>Year</td>
                <td>Make</td>
                <td>Model</td>
                </th>
                </thead>
                <tbody>
                <?php
                $i        = 0;
                $pageCols = 1;
                foreach ($vehicles["list"] as $veh) {
                    $i++;
                    if (count($veh["photos"]) > 0) {
                        $firstImage = $veh["photos"][0];
                    } else {
                        $firstImage = $folder . "/res/img/noimage3.gif";
                    }
                    $image = '<img src="' . $firstImage . '" width="85" alt="' . $veh["year"] . " " . $veh["make"] . " " . $veh["model"] . '" />';
                    $year  = $veh["year"];
                    $make  = $veh["make"];
                    $model = $veh["model"];
                    $stock = $veh["stock"];
                    $vin   = $veh["vin"];
                    $id    = $veh["id"];
                    ?>
                    <tr>
                        <td><input type="checkbox" id="vehicle<?= $i; ?>" name="vehicle<?= $i; ?>" value="<?= $id; ?>"></td>
                        <td><?= $image ?></td>
                        <td><?= $vin ?></td>
                        <td><?= $stock ?></td>
                        <td><?= $year ?></td>
                        <td><?= $make ?></td>
                        <td><?= $model ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <input type="hidden" name="vehCount" value="<?= $i; ?>">
            <input type="submit" value="Delete" name="Delete">
        </form>
    </div>
</div>