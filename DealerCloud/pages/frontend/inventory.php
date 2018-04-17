<?php

function DealerCloudInventory($atts, $content = null)
{
    if (isset($_REQUEST['p'])) {
        $p = InputHelper::get($_REQUEST, 'p');
    } else {
        $p = "";
    }
    if (isset($_REQUEST['m'])) {
        $m = InputHelper::get($_REQUEST, 'm');
    } else {
        $m = "";
    }
    if (isset($_REQUEST['page_id'])) {
        $page_id = InputHelper::get($_REQUEST, 'page_id');
    } else {
        $page_id = "";
    }
    if (isset($_REQUEST['cat'])) {
        $cat = InputHelper::get($_REQUEST, 'cat');
    } else {
        $cat = "";
    }

    $permalink_structure = get_option('permalink_structure');
    if ($permalink_structure == '') {
        $baseUrl = "?page_id=" . $page_id . "&cat=" . $cat . "&p=" . $p . "&m=" . $m . "&";
    } else {
        $baseUrl = "?";
    }

    $ru = $_SERVER['REQUEST_URI'];
    $i1 = strpos($ru, 'action/view_details/');
    if ($i1 !== false) {
        $i1 += 20;
        $i2 = strpos($ru, '/', $i1);
        if ($i2 === false) {
            $_REQUEST['id'] = substr($ru, $i1);
        } else {
            $_REQUEST['id'] = substr($ru, $i1, $i2 - $i1);
        }
        $_REQUEST['action'] = 'view_details';
    }

    $folder = WP_PLUGIN_URL . '/DealerCloud';

    $client = new Client();

    $rest = '<script type="text/javascript" src="' . $folder . '/res/js/jquery-1.3.min.js"></script>
	<script type="text/javascript" src="' . $folder . '/res/js/jquery.corner.js"></script>
	<script type="text/javascript" src="' . $folder . '/res/js/awe.js"></script>';

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
        "sortBy"    => InputHelper::get($_REQUEST, "sortBy", "year"),
        "sortType"  => InputHelper::get($_REQUEST, "sortType", "desc"),
        "display"   => InputHelper::get($_REQUEST, "disp", "L"),
        "page"      => InputHelper::get($_REQUEST, "page_num", "1")
    );

    if (isset($_REQUEST['action']) && InputHelper::get($_REQUEST, 'action') == 'view_details') {
        // Show vehicle details page
        include "details.php";

    } else if (isset($_REQUEST['action']) && InputHelper::get($_REQUEST, 'action') == 'clear_recent') {
        // Clear recent cookies

        //Killing the cookie:
        $cookie_name = "awerecent_" . $_GET["id"];

        //here we assign a "0" value to the cookie, i.e. disabling the cookie:
        $cookie_value = "";

        //When deleting a cookie you should assure that the expiration date is in the past,
        //to trigger the removal mechanism in your browser.
        $cookie_expire = time() - 60;

        $cookie_domain = "";
        setcookie($cookie_name, $cookie_value, $cookie_expire, "/", $cookie_domain, 0);

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
        header("Location:$backUrl");
        exit;

    } else {
        // Main homepage: Inventory page (vehicle search page)

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
            "page_size"  => (string)$pageSize,
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

        // RECENTLY VIEWED
        $recent = array();
        foreach ($_COOKIE as $k => $v) {
            if (substr($k, 0, 10) == 'awerecent_') {
                $vPart = explode("||", $v);
                $kPart = explode("_", $k);
                $id    = $kPart[1];
                if ($vPart[1] != "") {
                    $recent[] = array("time" => $vPart[0], "veh" => $vPart[1], "id" => $id);
                }
            }
        }

        foreach ($recent as $key => $row) {
            $time[$key] = $row['time'];
            $veh[$key]  = $row['veh'];
            $id[$key]   = $row['id'];
        }

        if (count($recent) > 0) {
            array_multisort($time, SORT_DESC, $recent);
        }

        // SEARCH FORM

        // Makes
        $rest .= '
	<script type="text/javascript">
		var SITE_FOLDER = "' . $folder . '";
	</script>

<script type="text/javascript" src="' . $folder . '/res/js/make-model.js"></script>
<form name="frmFilter" action="' . $baseUrl . '" method="post">
	<div>
		<div class="blueRfg" id="search_Round">

	<div class="formdiv">
		<div class="form1">
			<label class="desc">Make</label>
                <select name="make" id="make" style="font-size:16px;width:100%" onChange="getModels();">
                <option value=""></option>';
//                <option value="">-- Any --</option>';
        $makes = $client->GetMakes();
        foreach ($makes as $m) {
            $selected = "";
            if ($vars->make == $m) {
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
        if ($vars->make != "" && $vars->model != "") {
            $models = $client->GetModels($vars->make);
            foreach ($models as $m) {
                $selected = "";
                if ($vars->model == $m) {
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
        for ($minp = 0; $minp <= 100000; $minp += 5000) {
            $selected = "";
            if ($minp == $vars->minPrice and $minp > 0) {
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
        for ($maxp = 100000; $maxp > 0; $maxp -= 5000) {
            $selected = "";
            if ($maxp == $vars->maxPrice) {
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
        for ($yearCnt = date("Y") + 1; $yearCnt >= date("Y") - 25; $yearCnt--) {
            $selected = "";
            if ($yearCnt == $vars->minYear) {
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
        for ($yearCnt = date("Y") + 1; $yearCnt >= date("Y") - 25; $yearCnt--) {
            $selected = "";
            if ($yearCnt == $vars->maxYear) {
                $selected = "selected";
            }
            $rest .= "<option $selected value='$yearCnt'>$yearCnt</option>";
        }
        // Keyword and Submit
        $rest .= '
			</select>
		</div>

		<div class="form4">
                Keyword <input name="keyword" type="text" value="' . $vars->keyword . '" style="font-size:14px;width:74%" /> <input name="submit" type="submit" value="search" class="submit" style="font-size:12px;" />
		</div>

	</div>
</div>
	</div>
</form>';

        // TOTAL MATCHES BAR
        if ($vehicles["total_count"] > 0) {
            $rest .= '
                <div class="infomessage" id="info_Round">
                <span><span style="font-family:Georgia; font-size:16px;">' . $vehicles["total_count"] . '</span> vehicles match your search!</span>
                </div>';
        } else {
            $rest .= '
                <div class="nocarsinfomessage id="info_Round"">
                    We could not find any matches for that search.
                </div>';
        }

        // SORT CONTROL
        $rest .= '
            <div class="resultList">
            <div class="sortBy">Sort By:</div>
            <div class="sortLink">
                <div class="sorter">
                    <div style="width:65px;float:left;">' . ($vars->sortBy == "year" ? '<img src="' . $folder . '/res/img/' . $vars->sortType . '.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ' : '') . '<a href="' . $client->GetSortingUrl($baseUrl, $vars, 'year') . '">Year</a></div>
                    <div style="width:65px;float:left;">' . ($vars->sortBy == "make" ? '<img src="' . $folder . '/res/img/' . $vars->sortType . '.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ' : '') . '<a href="' . $client->GetSortingUrl($baseUrl, $vars, 'make') . '">Make</a></div>
                    <div style="width:68px;float:left;">' . ($vars->sortBy == "model" ? '<img src="' . $folder . '/res/img/' . $vars->sortType . '.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ' : '') . '<a href="' . $client->GetSortingUrl($baseUrl, $vars, 'model') . '">Model</a></div>
                    <div style="width:65px;float:left;">' . ($vars->sortBy == "price" ? '<img src="' . $folder . '/res/img/' . $vars->sortType . '.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ' : '') . '<a href="' . $client->GetSortingUrl($baseUrl, $vars, 'price') . '">Price</a></div>
                    <div style="width:65px;float:left;">' . ($vars->sortBy == "mileage" ? '<img src="' . $folder . '/res/img/' . $vars->sortType . '.png" alt="Sort" align="absmiddle" style="float:left;padding-top:5px;" /> ' : '') . '<a href="' . $client->GetSortingUrl($baseUrl, $vars, 'mileage') . '">Miles</a></div>
                </div>
            </div>

            <div class="viewLink">
                <div class="sorter">';
        // Gallery or List
        if ($vars->display != "G") {
            $rest .= '<div style="width:105px;float:left;"><a href="' . $client->GetDisplayUrl($baseUrl, $vars) . '">Gallery View</a><img src="' . $folder . '/res/img/gallery.png" alt="Gallery" align="absmiddle" style="float:right;padding-top:5px;" /></div>';
        } else {
            $rest .= '<div style="width:90px;float:left;"><a href="' . $client->GetDisplayUrl($baseUrl, $vars) . '">List View</a><img src="' . $folder . '/res/img/list.png" alt="List" align="absmiddle" style="float:right;padding-top:5px;" /></div>';
        }
        $rest .= '
                    </div>
                </div>
            </div>';

        // GALLERY VIEW
        if ($vars->display == "G" && $vehicles["total_count"] > 0) {
            $pageCols = 3;
            $rest     .= '<div class="vresultsg">';
            $i        = 0;
            foreach ($vehicles["list"] as $veh) {
                $rest .= '<div class="glayout">';
                if (count($veh["photos"]) > 0) {
                    $firstImage = $veh["photos"][0];
                } else {
                    $firstImage = $folder . "/res/img/noimage3.gif";
                }
                $rest .= '
							<div class="gallery">
								<a href="' . $client->GetVehicleUrl($baseUrl, $veh) . '"><img src="' . $firstImage . '" width="85" alt="' . $veh["year"] . " " . $veh["make"] . " " . $veh["model"] . '" /></a>
							</div>
							<div  class="makes">
								<a href="' . $client->GetVehicleUrl($baseUrl, $veh) . '">' . ($vars->sortBy == "year" ? '<span class="sorthighlight">' : '') . $veh["year"] . ($vars->sortBy == "year" ? '</span>' : '') . ($vars->sortBy == "make" ? '<span class="sorthighlight">' : '') . $veh["make"] . ($vars->sortBy == "make" ? '</span>' : '') . ($vars->sortBy == "model" ? '<span class="sorthighlight">' : '') . $veh["model"] . ($vars->sortBy == "model" ? '</span>' : '') . '
								</a>
							</div>
						</div>';

                $i++;
                if ($i % $pageCols == 0) {
                    $rest .= '<br style="clear:both" />';
                }
            }
            $rest .= '</div>';

            // LIST VIEW
        } else if ($vars->display == "L" && $vehicles["total_count"] > 0) {
            $rest .= '
			<div>';
            $i    = 1;
            foreach ($vehicles["list"] as $veh) {
                if (!isset($veh["interior_color"])) {
                    $veh["interior_color"] = "";
                }
                $cssClass = "resultstable" . $i;
                $rest     .= '
					<div class="' . $cssClass . '">
						<div class="resultstablerow">
							<div class="photocell">';

                $dealer_company = (isset($veh["dealer"]) && isset($veh["dealer"]["company"])) ? $veh["dealer"]["company"] : "";

                if (count($veh["photos"]) > 0) {
                    $firstImage = $veh["photos"][0];
                } else {
                    $firstImage = $folder . "/res/img/noimage3.gif";
                }
                $rest .= '
					  <div class="gallery">
                          <a href="' . $client->GetVehicleUrl($baseUrl, $veh) . '"><img src="' . $firstImage . '" width="85" style="border:none;" alt="' . $veh["year"] . " " . $veh["make"] . " " . $veh["model"] . (!empty($dealer_company) ? ' / ' . $dealer_company : '') . ' " title="' . $veh["year"] . " " . $veh["make"] . " " . $veh["model"] . (!empty($dealer_company) ? ' / ' . $dealer_company : '') . ' " /></a>
								</div>
							</div>
							<div class="desccell">
								<div class="descfont">
									<div class="descfont1">
										<a href="' . $client->GetVehicleUrl($baseUrl, $veh) . '">
										' . $veh["year"] . ' ' . $veh["make"] . ' ' . $veh["model"] . ' ' . $veh["trim"] . '</a>' . ($veh["cmpg"] >= "21" ? '<img src="' . $folder . '/res/img/leaf1.png">' : '') . '
									</div>
								</div>

                          <div class="descfont2">';
                if (number_format(floatval($veh["price"])) != 0) {
                    $rest .= "$" . number_format(floatval($veh["price"]));
                } else {
                    $rest .= "<span style='color:#ff0000'>****</span>";
                }
                $rest      .= ' | ' . number_format(floatval($veh["mileage"])) . ' miles
								</div>
                          <div class="summ">#' . $veh["stock"]
                    . ($veh["exterior_color"] != "" ? ", " . $veh["exterior_color"] : "")
                    . ($veh["interior_color"] != "" ? " | " . $veh["interior_color"] : "")
                    . ($veh["cmpg"] != "" ? ", " . $veh["cmpg"] . " mpg" : "");
                $maxLength = 25;
                $features  = [];
                if (array_key_exists("features", $veh) && $veh["features"] != "") {
                    $features = array_merge($features, explode("|", $veh["features"]));
                }
                $temp = "";
                foreach ($features as $feature) {
                    if ($temp != "") {
                        $temp .= ", ";
                    }
                    $temp .= $feature;
                }
                if (strlen($temp) > 5) {
                    $rest .= " | " . substr($temp, 0, $maxLength);
                }
                if (strlen($temp) > $maxLength) {
                    $rest .= " ...";
                }
                $rest .= '
								</div>
								<div class="moredetails">
								</div>
							</div>
						</div>
					</div>';
                if ($i == 1) {
                    $i++;
                } else {
                    $i = 1;
                }
            }
            $rest .= '</div>';

        }
        // PAGE NAV
        $rest .= '
			 <br style="clear:both" />
			 <div class="pagination">
					<ul>';
        if ($firstPage >= 10) {
            $rest .= '<li><a href="' . $client->GetPagingUrl($baseUrl, $vars, $firstPage) . '"><< Prev 10</a></li>';
        }
        for ($i = $firstPage + 1; $i <= $lastPage; $i++) {
            $rest .= '<li ' . ($i == $vars->page ? 'class="currentpage"' : '') . '><a href="' . $client->GetPagingUrl($baseUrl, $vars, $i) . '">' . $i . '</a></li>';
        }
        if ($lastPage != $totalPages) {
            $rest .= '<li><a href="' . $client->GetPagingUrl($baseUrl, $vars, $lastPage + 1) . '">Next 10 >></a></li>';
        }
        $rest .= '
					</ul>
			</div>
			<br />';

        // RECENTLY VIEWED
        if (count($recent) > 0) {
            $rest  .= '
			   <div style="width:100%; text-align:left; margin-right:20px;">
				Previously Viewed Vehicles...&nbsp;&nbsp;&nbsp;
				</div>
			   <div class="recentviewfg" id="recent_Round">
					<div class="recentList">';
            $count = 0;
            foreach ($recent as $rec) {
                $count++;
                if ($count > 10) {
                    break;
                }
                $rest .= '<span class="recentSpan">&#8226; <a href="' . $baseUrl . 'action=view_details&id=' . $rec["id"] . '">' . $rec["veh"] . '</a>
									</span>
									(<a href="' . $baseUrl . 'action=clear_recent&id=' . $rec["id"] . '" class="cleared"><span class="cleared">X</span></a>)
									<br />';
            }
            $rest .= '
					</div>
				</div>';
        }
    }

    return $rest;
}

?>