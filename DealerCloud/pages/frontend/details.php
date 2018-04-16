<?php
// Vehicle details page
if (isset($_REQUEST["id"])) {
// GET NEEDED DATA
    $vehicle = $client->getVehicle($_REQUEST["id"]);
    if (isset($vehicle["dealer"])) {
        $dealer = $vehicle["dealer"];
    } else {
        $dealer = array(
            'company'  => "",
            'city'     => "",
            'state'    => "",
            'zip_code' => "",
            'address1' => ""
        );
    }
    if (isset($vehicle["photos"])) {
        $photos = $vehicle["photos"];
    } else {
        $photos = "";
    }

// CONTACT FORM SUBMIT HANDLER
    $firstName = "";
    $lastName  = "";
    $phone     = "";
    $email     = "";
    $address   = "";
    $city      = "";
    $state     = "";
    $zip       = "";
    $message   = "";
    if (isset($_POST["submitContact"]) && isset($_POST['g-recaptcha-response'])) {
        $recaptcha = new \ReCaptcha\ReCaptcha(get_option('recaptcha_private_key'));
        $resp      = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
        $firstName = InputHelper::get($_REQUEST, "firstName");
        $lastName  = InputHelper::get($_REQUEST, "lastName");
        $phone     = InputHelper::get($_REQUEST, "phone");
        $email     = InputHelper::get($_REQUEST, "email");
        $address   = InputHelper::get($_REQUEST, "address");
        $city      = InputHelper::get($_REQUEST, "city");
        $state     = InputHelper::get($_REQUEST, "state");
        $zip       = InputHelper::get($_REQUEST, "zip");
        $message   = InputHelper::get($_REQUEST, "message");
        if ($resp->isSuccess()) {
        $err = "Error:<br />";
        if ($firstName == "" || $lastName == "") {
            $err .= "First and Last name are required<br />";
        }
        if ($email == "") {
            $err .= "Email is required";
        }
        if ($err != "Error:<br />") {
            $sendMessageRes = "<div class='nosentMessage'>$err</div";
        } else {
            //submit message
            $retVal = $client->SendMessage(
                array(
                    "veh_id"     => $vehicle["id"],
                    "first_name" => $firstName,
                    "last_name"  => $lastName,
                    "phone"      => $phone,
                    "email"      => $email,
                    "address"    => $address,
                    "city"       => $city,
                    "state"      => $state,
                    "zip_code"   => $zip,
                    "message"    => $message
                ));
            if ($retVal) {
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
    if (isset($sendMessageRes)) {
        $rest .= $sendMessageRes;
    }
// RECENTLY VIEWED ITEMS ADD COOKIE
//            $cookieName = "awerecent_" . $vehicle["id"];
//            setcookie($cookieName, time() . "||" . $vehicle["year"] . " " . $vehicle["make"] . " " . $vehicle["model"], time() + 604800, "/");

// BEGIN PAGE HTML
    $rest .= '
    <div class="vehicleBackfg" id="vehBack_Round">
        <div class="detailsContainer">
            <div class="detailNavfg" id="vehNav_Round">
                <div class="detailsDealerHeader">';
// Back to search
    $vars    = isset($_SESSION["vars"]) ? $_SESSION["vars"] : null;
    $backUrl = $baseUrl;
    if ($vars != null) {
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
    $rest .= '<a href="' . $backUrl . '" class="backSearch">Back to search</a></div>
            </div>
            <div class="detailsHeader">
            <h2>';
// Title
    $rest .= $vehicle["year"] . " " . $vehicle["make"] . " " . $vehicle["model"] . " " . $vehicle["trim"];
    if ($vehicle["cmpg"] >= "21") {
        $rest .= "<img src='$folder/res/img/leaf1.png' align='absmiddle'>";
    }
    $rest .= '<span style="text-transform:none; font-size:16px; line-height:26px;">';
// Price
    $price = number_format(floatval($vehicle["price"]));
    if ($price != 0) {
        $rest .= "<br />Your Price: <span style=\"font-size:16px; font-weight:bolder; color:#FF0000;\">$" . $price . "</span>";
    } else {
        $rest .= "[Contact for Price]";
    }
// Photos
    $rest       .= '</span>
               </h2>
            </div>
            <div class="photoDiv">
                <div class="photoRow">
                    <div class="mainPhoto">';
    $firstPhoto = "";
    if (is_array($photos) && count($photos) > 0) {
        $firstPhoto = $photos[0];
    }
    if ($firstPhoto == "") {
        $firstPhoto = $folder . "/res/img/noimage.gif";
    }
    $photoString = $vehicle["year"] . " " . $vehicle["make"] . " " . $vehicle["model"] . " " . $vehicle["trim"];
    if ($dealer["company"] != "") {
        $photoString .= " / " . $dealer["company"];
    }
    if ($dealer["city"] != "") {
        $photoString .= " / " . $dealer["city"];
    }
    if ($dealer["state"] != "") {
        $photoString .= " / " . $dealer["state"];
    }
    if ($dealer["zip_code"] != "") {
        $photoString .= " / " . $dealer["zip_code"];
    }
    $rest        .= '
                <div class="gallery">
                    <img id="mainPhoto" src="' . $firstPhoto . '" class="pborder" border="0" title="Cars for sale / ' . $photoString . '" alt="cars for sale / ' . $photoString . '" width="400">
                </div>
                <div class="photoThumbs">';
    $photosShown = 0;
    if (is_array($photos) && count($photos) > 0) {
        foreach ($photos as $photo) {
            if ($photosShown++ % 6 == 0) {
                $rest .= "<br style='clear:both'/>";
            }
            $rest .= '<a href="javascript:swapPhoto(\'mainPhoto\', \'' . $photo . '\');"><img src="' . $photo . '" class="pborder2" align="middle" border="0" width="70"></a>';
        }
    }
// Details
    $rest .= '
                </div>
                <br />
                <div class="clear"></div>
                </div>
                <div class="detailsdiv2" id="details_Round">';
    if ($vehicle["price"] != "") {
        $rest .= '<div class="detailsdiv" style="border-top:none;">Price: &nbsp;';
        if ($price != 0) {
            $rest .= "$" . $price;
        } else {
            $rest .= " [Contact for Price]";
        }
        $rest .= '
                </div>';
    }
    if (array_key_exists("vin", $vehicle) && $vehicle["vin"] != "") {
        $rest .= '<div class="detailsdiv">Vin: &nbsp;' . $vehicle["vin"] . '</div>';
    }
    if (array_key_exists("stock", $vehicle) && $vehicle["stock"] != "") {
        $rest .= '<div class="detailsdiv">Stock #: &nbsp;' . $vehicle["stock"] . '</div>';
    }
    if (array_key_exists("mileage", $vehicle) && $vehicle["mileage"] > 0) {
        $rest .= '<div class="detailsdiv">Mileage: &nbsp;' . number_format(floatval($vehicle["mileage"])) . '</div>';
    }
    if (array_key_exists("exterior_color", $vehicle) && $vehicle["exterior_color"] != "") {
        $rest .= '<div class="detailsdiv">Exterior Color: &nbsp;' . $vehicle["exterior_color"] . '</div>';
    }
    if (array_key_exists("interior_color", $vehicle) && $vehicle["interior_color"] != "") {
        $rest .= '<div class="detailsdiv">Interior Color: &nbsp;' . $vehicle["interior_color"] . '</div>';
    }
    if (array_key_exists("body_door_count", $vehicle) && $vehicle["body_door_count"] > 0) {
        $rest .= '<div class="detailsdiv"># of Doors: &nbsp;' . $vehicle["body_door_count"] . '</div>';
    }
    if (array_key_exists("engine", $vehicle) && $vehicle["engine"] != "") {
        $rest .= '<div class="detailsdiv">Engine: &nbsp;' . $vehicle["engine"] . '</div>';
    }
    if (array_key_exists("trans", $vehicle) && $vehicle["trans"] != "") {
        $rest .= '<div class="detailsdiv">Trans: &nbsp;' . $vehicle["trans"] . '</div>';
    }
    if (array_key_exists("drive", $vehicle) && $vehicle["drive"] != "") {
        $rest .= '<div class="detailsdiv">Drive: &nbsp;' . $vehicle["drive"] . '</div>';
    }
    if (array_key_exists("classification", $vehicle) && $vehicle["classification"] != "") {
        $rest .= '<div class="detailsdiv">Class: &nbsp;' . $vehicle["classification"] . '</div>';
    }
    if (array_key_exists("cmpg", $vehicle) && $vehicle["cmpg"] > 0) {
        $rest .= '<div class="detailsdiv">City / Hwy (mpg): &nbsp;' . $vehicle["cmpg"] . ' / ' . $vehicle["hmpg"] . '</div>';
    }
// Buttons - Financing and CarFax
    if (true) {
        $rest .= '<br />
                <div class="buttons" id="buttons_Round">
                        <a href="#" style="border:none;">
                            <img src="' . $folder . '/res/img/applyfin.gif" alt="financing" style="border:none;" /></a><br />
                        <a style="border:none;" href="' . $vehicle["carfax"]["report_url"] . '">
                            <img src="' . $vehicle["carfax"]["report_image"] . '" style="border:none;" />
                        </a>';
        if ($vehicle["carfax"]["one_owner"]) {
            $rest .= '<br /><img src="' . $vehicle["carfax"]["one_owner_image"] . '" />';
        }
        $rest .= '</div>';
    }
    $rest .= '</div>
            </div>';
// Features
    if ((array_key_exists("standard_features", $vehicle) && $vehicle["standard_features"] != "") ||
        (array_key_exists("features", $vehicle) && $vehicle["features"] != "")) {
        $rest     .= '
                <div class="featurestitle">
                    Features / Options
                </div>
                <div class="detailscontainer2">
                    <div style="clear:both" />
                    <div class="standardFeatures">
                        <ul>';
        $features = [];
        if (array_key_exists("features", $vehicle) && $vehicle["features"] != "") {
            $features = array_merge($features, explode("|", $vehicle["features"]));
        }
        if (array_key_exists("standard_features", $vehicle) && $vehicle["standard_features"] != "") {
            $features = array_merge($features, explode("|", $vehicle["standard_features"]));
        }
        foreach ($features as $feat) {
            if ($feat != "") {
                $features2 = explode(",", $feat);
                foreach ($features2 as $feat2) {
                    if ($feat2 != "") {
                        $rest .= '<li>' . trim($feat2) . '</li>';
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
    if (array_key_exists("comments", $vehicle) && $vehicle["comments"] != "") {
        $rest .= '
                <div class="featurestitle">
                    Notes
                </div>
                <div class="detailscontainer2">
                    <div>' . $vehicle["comments"] . '</div>
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
                <form id="f" name="f" method="post" action="' . $_SERVER["REQUEST_URI"] . '">
                    <div>
                        Questions about this ' . $vehicle["make"] . '?  Please fill out the quick form below.
                    </div>
                    <br />
                    <div class="contactform">
                        <div class="contactformrow">
                            <div class="contactformcell">
                                First Name *
                                <br />
                                <input type="text" name="firstName" value="' . $firstName . '"/>
                            </div>
                            <div class="contactformcell">
                                Last Name *
                                <br />
                                <input type="text" name="lastName" value="' . $lastName . '"/>
                            </div>
                            <br style="clear:both" />
                            <div class="contactformcell">
                                Email *
                                <br />
                                <input type="text" name="email" value="' . $email . '"/>
                            </div>
                            <div class="contactformcell">
                                Phone
                                <br />
                                <input type="text" name="phone" value="' . $phone . '"/>
                            </div>
                        </div>
                    </div>
                    <br style="clear:both" />
                    <div class="contactform">
                            <div class="contactformcell2">
                                Comments
                                <br />
                                <textarea name="message" cols="40" rows="6">' . $message . '</textarea>
                                <br style="clear:both" />
                                <script src="https://www.google.com/recaptcha/api.js"></script>
                                <div class="verification">
                                    Verification *
                                    <br />
                                    <div class="g-recaptcha" data-sitekey="' . get_option('recaptcha_public_key') . '"></div>
                                </div>
                                <br style="clear:both" />
                                <input name="submitContact" type="submit" value="Get More Info!" class="submit" />
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
}
?>