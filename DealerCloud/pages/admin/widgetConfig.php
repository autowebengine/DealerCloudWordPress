<?php

if (isset($_POST['sideDealerCloud-Submit']) && $_POST['sideDealerCloud-Submit']) {
    $options['title']  = htmlspecialchars($_POST['sideDealerCloud-WidgetTitle']);
    $options['make']   = htmlspecialchars($_POST['make']);
    $options['model']  = htmlspecialchars($_POST['model']);
    $options['ids']    = htmlspecialchars($_POST['sideDealerCloud-VehiclesIDS']);
    $options['random'] = isset($_POST['sideDealerCloud-random']) ? '1' : '0';
    $options['number'] = htmlspecialchars($_POST['sideDealerCloud-VehiclesNumber']);
    if ($options['number'] > 5) {
        $options['number'] = 5;
    }

    update_option("DealerCloud_Widget", $options);
}

$client  = new Client();
$folder  = WP_PLUGIN_URL . '/DealerCloud';

$options = get_option("DealerCloud_Widget");
$title  = isset($options['title']) ? $options['title']: '';
$random  = isset($options['random']) ? $options['random']: '0';
$number  = isset($options['number']) ? $options['number']: 3;
$ids     = isset($options['ids']) ? $options['ids']: '';
$make    = isset($options['make']) ? $options['make']: '';
$model   = isset($options['model']) ? $options['model']: '';

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
        Title of widget showing vehicles<br/>
        <input class="widefat" type="text" id="sideDealerCloud-WidgetTitle" name="sideDealerCloud-WidgetTitle" value="<?= $title ?>"/>
        <br/><br/>
        <label for="sideDealerCloud-VehiclesNumber">
            <b>Number of vehicles to show:</b>
        </label>
        <input type="text" id="sideDealerCloud-VehiclesNumber" name="sideDealerCloud-VehiclesNumber" style="width: 25px; text-align: center;" maxlength="1" value="<?= $number ?>"/><br/>
        <small>
            <em>(max 5)</em>
        </small>
        <br/><br/>
        Only show specific make and/or model<br/>
        <label for="make">
            <b>Make:</b>
        </label><br/>
        <select name="make" id="make" style="width:125px" onChange="getModels();">
            <option value=""></option>
            <!--<option value="">-- Any --</option>-->
            <?php
            $makes  = $client->GetMakes();
            foreach ($makes as $m) {
                $selected = "";
                if ($make == $m) {
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
        <label for="model">
            <b>Model:</b>
        </label><br/>
        <select name="model" id="model" style="width: 125px;">
            <option value=""></option>
            <!--<option value="">-- Any --</option>-->
            <?php
            if ($make != "") {
                $models = $client->GetModels($make);
                foreach ($models as $m) {
                    $selected = "";
                    if ($model == $m) {
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
        <input class="widefat" type="text" id="sideDealerCloud-VehiclesIDS" name="sideDealerCloud-VehiclesIDS" value="<?= $ids ?>"/><br/>
        <small>
            <em>(comma separated vehicle id's)</em>
        </small>

        <input type="hidden" id="sideDealerCloud-Submit" name="sideDealerCloud-Submit" value="1"/>
    </p>
