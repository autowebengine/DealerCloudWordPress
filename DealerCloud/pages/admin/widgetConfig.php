<?php

if (isset($_POST['DealerCloud-Submit']) && $_POST['DealerCloud-Submit']) {
    $options['title']    = htmlspecialchars($_POST['title']);
    $options['number']   = htmlspecialchars($_POST['number']);
    $options['ids']      = htmlspecialchars($_POST['ids']);
    $options['featured'] = isset($_POST['featured']) ? '1' : '0';
    $options['sortby']   = htmlspecialchars($_POST['sortby']);
    $options['sortdir']  = htmlspecialchars($_POST['sortdir']);
    if ($options['number'] > 6) {
        $options['number'] = 6;
    }

    update_option("DealerCloud_Widget", $options);
}

$options  = get_option("DealerCloud_Widget");
$title    = isset($options['title']) ? $options['title'] : '';
$number   = isset($options['number']) ? $options['number'] : '';
$ids      = isset($options['ids']) ? $options['ids'] : '';
$featured = isset($options['featured']) ? $options['featured'] : '0';
$sortby   = isset($options['sortby']) ? $options['sortby'] : '';
$sortdir  = isset($options['sortdir']) ? $options['sortdir'] : '';

?>
<p>
    <label for="title">
        <b>Title:</b>
    </label><br/>
    Title of widget showing vehicles<br/>
    <input class="widefat" type="text" id="title" name="title" value="<?= $title ?>"/>
    <br/><br/>

    <label for="number">
        <b>Number of vehicles to show:</b>
    </label>
    <input type="text" id="number" name="number" style="width: 25px; text-align: center;" maxlength="1" value="<?= $number ?>"/>
    <small>
        <em>(max 6)</em>
    </small>
    <br/><br/>

    <label for="ids">
        <b>ID's:</b>
    </label>
    <br/>Only show specific vehicles<br/>
    <input class="widefat" type="text" id="ids" name="ids" value="<?= $ids ?>"/><br/>
    <small>
        <em>(comma separated vehicle id's)</em>
    </small>
    <br/><br/>

    <label for="featured">
        <b>Featured:</b>
    </label>
    <br/>Show featured vehicles first?<br/>
    <input type="checkbox" id="featured" name="featured" <?= ($featured == '1' ? 'checked' : '') ?>>
    <br/><br/>

    <label for="sortby">
        <b>Sort By:</b>
    </label>
    <br/>Sort by which vehicle characteristic?<br/>
    <select name="sortby" id="sortby" style="font-size:16px;width:100%">
        <option value=""></option>
        <option <?= ($sortby == 'year' ? 'selected' : '') ?> value='year'>Year</option>
        <option <?= ($sortby == 'make' ? 'selected' : '') ?> value='make'>Make</option>
        <option <?= ($sortby == 'model' ? 'selected' : '') ?> value='model'>Model</option>
        <option <?= ($sortby == 'trim' ? 'selected' : '') ?> value='trim'>Trim</option>
        <option <?= ($sortby == 'engine' ? 'selected' : '') ?> value='engine'>Engine</option>
        <option <?= ($sortby == 'mileage' ? 'selected' : '') ?> value='mileage'>Mileage</option>
        <option <?= ($sortby == 'price' ? 'selected' : '') ?> value='price'>Price</option>
        <option <?= ($sortby == 'condition' ? 'selected' : '') ?> value='condition'>Condition</option>
        <option <?= ($sortby == 'typecode' ? 'selected' : '') ?> value='typecode'>TypeCode</option>
        <option <?= ($sortby == 'cmpg' ? 'selected' : '') ?> value='cmpg'>City MPG</option>
    </select>
    <br/><br/>

    <label for="sortdir">
        <b>Sort Type:</b>
    </label>
    <br/>Sort vehicles in which order?<br/>
    <select name="sortdir" id="sortdir" style="font-size:16px;width:100%">
        <option value=""></option>
        <option <?= ($sortdir == 'asc' ? 'selected' : '') ?> value='asc'>Ascending</option>
        <option <?= ($sortdir == 'desc' ? 'selected' : '') ?> value='desc'>Descending</option>
    </select>

    <input type="hidden" id="DealerCloud-Submit" name="DealerCloud-Submit" value="1"/>
</p>
