<?php

$client = new Client();

if (isset($_POST['submit'])) {
    if (function_exists('current_user_can') && !current_user_can('manage_options')) {
        die(__('Cheatin&#8217; uh?'));
    }

    // Get post variables here
    // TODO:
    $params          = array();
    $params['vin']   = isset($_POST['vin']) ? InputHelper::get($_POST, 'vin') : "";
    $params['stock'] = isset($_POST['stock']) ? InputHelper::get($_POST, 'stock') : "";
    $params['year']  = isset($_POST['year']) ? InputHelper::get($_POST, 'year') : "";
    $params['make']  = isset($_POST['make']) ? InputHelper::get($_POST, 'make') : "";
    $params['model'] = isset($_POST['model']) ? InputHelper::get($_POST, 'model') : "";
    $params['price'] = isset($_POST['price']) ? InputHelper::get($_POST, 'price') : "";

    // See options submit above for indication to interact with Dealercloud
    // TODO:
    $client->AddVehicle($params);
}

$key                   = get_option('awe_api_key');
$recaptcha_public_key  = get_option('recaptcha_public_key');
$recaptcha_private_key = get_option('recaptcha_private_key');
$google_maps_key       = get_option('google_maps_key');
$dealers_domain        = get_option('dealers_domain');
?>

<div class="wrap">
    <h2><?= _e('DealerCloud | Add New Inventory') ?></h2>
    <p>Add a new vehicle to your inventory here:</p>

    <div class="narrow" style="margin-left:100px;">
        <form method="post" id="DealerCloud-conf">
            <p>
            <h3><label for="vin"><?= _e('VIN') ?></label></h3>
            <input id="vin" name="vin" type="text" value=""/></p>

            <p>
            <h3><label for="stock"><?= _e('stock') ?></label></h3>
            <input id="stock" name="stock" type="text" value=""/></p>

            <p>
            <h3><label for="year"><?= _e('year') ?></label></h3>
            <input id="year" name="year" type="text" value=""/></p>

            <p>
            <h3><label for="make"><?= _e('make') ?></label></h3>
            <input id="make" name="make" type="text" value=""/></p>

            <p>
            <h3><label for="model"><?= _e('model') ?></label></h3>
            <input id="model" name="model" type="text" value=""/></p>

            <p>
            <h3><label for="price"><?= _e('price') ?></label></h3>
            <input id="price" name="price" type="text" value=""/></p>

            <p>The following fields are optional...</p>

            <p>
            <h3><label for="trim"><?= _e('trim') ?></label></h3>
            <input id="trim" name="trim" type="text" value=""/></p>

            <p>
            <h3><label for="mileage"><?= _e('mileage') ?></label></h3>
            <input id="mileage" name="mileage" type="text" value=""/></p>

            <p>
            <h3><label for="exterior_color"><?= _e('exterior color') ?></label></h3>
            <input id="exterior_color" name="exterior_color" type="text" value=""/></p>

            <p>
            <h3><label for="interior_color"><?= _e('interior color') ?></label></h3>
            <input id="interior_color" name="interior_color" type="text" value=""/></p>

            <p>
            <h3><label for="comments"><?= _e('comments') ?></label></h3>
            <input id="comments" name="comments" type="text" value=""/></p>

            <p>
            <h3><label for="standard_features"><?= _e('standard features') ?></label></h3>
            <input id="standard_features" name="standard_features" type="text" value=""/></p>

            <p>
            <h3><label for="features"><?= _e('features') ?></label></h3>
            <input id="features" name="features" type="text" value=""/></p>

            <p>
            <h3><label for="cmpg"><?= _e('cmpg') ?></label></h3>
            <input id="cmpg" name="cmpg" type="text" value=""/></p>

            <p>
            <h3><label for="hmpg"><?= _e('hmpg') ?></label></h3>
            <input id="hmpg" name="hmpg" type="text" value=""/></p>

            <p>
            <h3><label for="engine"><?= _e('engine') ?></label></h3>
            <input id="engine" name="engine" type="text" value=""/></p>

            <p>
            <h3><label for="drive"><?= _e('drive') ?></label></h3>
            <input id="drive" name="drive" type="text" value=""/></p>

            <p>
            <h3><label for="trans"><?= _e('trans') ?></label></h3>
            <input id="trans" name="trans" type="text" value=""/></p>

            <p>
            <h3><label for="stock_type"><?= _e('stock type') ?></label></h3>
            <input id="stock_type" name="stock_type" type="text" value=""/></p>

            <p>
            <h3><label for="payment"><?= _e('payment') ?></label></h3>
            <input id="payment" name="payment" type="text" value=""/></p>

            <p>
            <h3><label for="blue_book_high"><?= _e('blue book high') ?></label></h3>
            <input id="blue_book_high" name="blue_book_high" type="text" value=""/></p>

            <p>
            <h3><label for="blue_book_low"><?= _e('blue book low') ?></label></h3>
            <input id="blue_book_low" name="blue_book_low" type="text" value=""/></p>

            <p>
            <h3><label for="type_code"><?= _e('type code') ?></label></h3>
            <input id="type_code" name="type_code" type="text" value=""/></p>

            <p>
            <h3><label for="body_door_count"><?= _e('body door count') ?></label></h3>
            <input id="body_door_count" name="body_door_count" type="text" value=""/></p>

            <p>
            <h3><label for="seating_capacity"><?= _e('seating capacity') ?></label></h3>
            <input id="seating_capacity" name="seating_capacity" type="text" value=""/></p>

            <p>
            <h3><label for="classification"><?= _e('classification') ?></label></h3>
            <input id="classification" name="classification" type="text" value=""/></p>

            <p class="submit"><input type="submit" name="submit" value="<?= _e('Add New Vehicle') ?>"/></p>
        </form>
    </div>
</div>
