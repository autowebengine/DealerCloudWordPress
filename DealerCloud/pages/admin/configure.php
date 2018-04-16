<?php
if (isset($_POST['submit'])) {
    if (function_exists('current_user_can') && !current_user_can('manage_options')) {
        die(__('Cheatin&#8217; uh?'));
    }

    $key                   = isset($_POST['key']) ? InputHelper::get($_POST, 'key') : "";
    $dealers_domain        = isset($_POST['dealers_domain']) ? InputHelper::get($_POST, 'dealers_domain') : "";
    $recaptcha_public_key  = isset($_POST['recaptcha_public_key']) ? InputHelper::get($_POST, 'recaptcha_public_key') : "";
    $recaptcha_private_key = isset($_POST['recaptcha_private_key']) ? InputHelper::get($_POST, 'recaptcha_private_key') : "";
    $google_maps_key       = isset($_POST['google_maps_key']) ? InputHelper::get($_POST, 'google_maps_key') : "";

    if (empty($key)) {
        delete_option('awe_api_key');
    } else {
        update_option('awe_api_key', $key);
    }
    if (empty($dealers_domain)) {
        delete_option('dealers_domain');
    } else {
        update_option('dealers_domain', $dealers_domain);
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
    if (empty($google_maps_key)) {
        delete_option('google_maps_key');
    } else {
        update_option('google_maps_key', $google_maps_key);
    }
}

$key                   = get_option('awe_api_key');
$dealers_domain        = get_option('dealers_domain');
$recaptcha_public_key  = get_option('recaptcha_public_key');
$recaptcha_private_key = get_option('recaptcha_private_key');
$google_maps_key       = get_option('google_maps_key');

if (!empty($_POST)) { ?>
    <div id="message" class="updated fade"><p><strong><?= _e('Options saved.') ?></strong></p></div>
    <?php } ?>
<div class="wrap">
    <h2>
        <?= _e('DealerCloud | Configuration') ?>
    </h2>
    <br/>A DealerCloud account at
    <a href="http://www.dealercloud.com" target="_blank">www.dealercloud.com</a> is required in order to use this plugin.
    <p>
        To have your inventory show up:
    <ul style="margin-left:50px;list-style-type:disc;">
        <li>Create a new WordPress Page</li>
        <li>
            Add <b>[DealerCloud]</b> to the html of that page
        </li>
        <li>Save and publish the page</li>
    </ul>
    </p>

    <div class="narrow" style="margin-top:50px; margin-left:50px;">
        <form method="post" id="DealerCloud-conf">
            <p>
            <h3>
                <label for="key">
                    <?= _e("Dealer's Website Token") ?>
                </label>
            </h3>
            Found in the DealerCloud dealer account info.<br/>
            <input id="key" name="key" type="text" value="<?= $key ?>" style="width:400px;"/>
            </p>
            <p>
            <h3>
                <label for="dealers_domain">
                    <?= _e("Dealer's User Name") ?>
                </label>
            </h3>
            Found in the DealerCloud dealer account info.<br/>
            <input id="dealers_domain" name="dealers_domain" type="text" value="<?= $dealers_domain ?>" style="width:400px;"/>
            </p>
            <p>
            <h3>
                <label for="recaptcha_public_key">
                    <?= _e('reCAPTCHA Site Key') ?>
                </label>
            </h3>
            Can be acquired at
            <a href="http://www.recaptcha.net" target="_blank">https://www.google.com/recaptcha/admin</a><br/>
            <input id="recaptcha_public_key" name="recaptcha_public_key" type="text" value="<?= $recaptcha_public_key ?>" style="width:400px;"/>
            </p>
            <p>
            <h3>
                <label for="recaptcha_private_key">
                    <?= _e('reCAPTCHA Secret Key') ?>
                </label>
            </h3>
            Found in the recaptcha site admin info.<br/>
            <input id="recaptcha_private_key" name="recaptcha_private_key" type="text" value="<?= $recaptcha_private_key ?>" style="width:400px;"/>
            </p>
            <p class="submit">
                <input type="submit" name="submit" value="<?= _e('Save settings') ?>"/>
            </p>
            <!--    <p><h3><label for="google_maps_key"><?= _e('Google Maps API Key') ?></label></h3>
    Can be acquired at <a href="http://code.google.com/apis/maps/signup.html" target="_blank">Google.com</a><br />
    <input id="google_maps_key" name="google_maps_key" type="text" value="<?= $google_maps_key ?>" style="width:400px;" /></p>-->
        </form>
    </div>
</div>