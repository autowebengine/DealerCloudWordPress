<?php
require_once("Cache.php");
require_once("Config.php");

/*************************************************************************************************/
// DealerCloud API Client Class
/*************************************************************************************************/

class Client
{
    private $enableCache = true;
    private $debug       = false;
    private $config      = false;

    public  $hasError;
    public  $error;
    private $data;

    /*************************************************************************************************/

    function __construct()
    {
        $this->config = new Config();
    }

    /*************************************************************************************************/

    public function GetMakes()
    {
        if ($this->enableCache && Cache::hasMakes()) {
            return Cache::getMakes();
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="vehicle.list_makes">';
        $xml .= '</request>';
        $this->handleResponse($this->sendRequest($xml));

        $makes = array();
        foreach ($this->data->makes->make as $make) {
            $makes[] = (string)$make;
        }

        if ($this->enableCache) {
            Cache::setMakes($makes);
        }

        return $makes;
    }

    /*************************************************************************************************/

    public function GetModels($make)
    {
        if ($this->enableCache && Cache::hasModels()) {
            return Cache::getModels();
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="vehicle.list_models">';
        $xml .= "<make>$make</make>";
        $xml .= '</request>';
        $this->handleResponse($this->sendRequest($xml));

        $models = array();
        foreach ($this->data->models->model as $model) {
            $models[] = (string)$model;
        }

        if ($this->enableCache) {
            Cache::setModels($models);
        }

        return $models;
    }

    /*************************************************************************************************/

    public function GetVehicle($id)
    {
        $xml = "<?xml version='1.0' encoding='utf-8'?>";
        $xml .= "<request method='vehicle.get'>";
        $xml .= "<veh_id>$id</veh_id>";
        $xml .= "<track_stats>1</track_stats>";
        $xml .= "<remote_ip>" . $_SERVER["REMOTE_ADDR"] . "</remote_ip>";
        $xml .= "</request>";
        $this->handleResponse($this->sendRequest($xml));

        $vehicle = array();

        if (isset($this->data->vehicle[0])) {
            $vehicle = array();
            foreach ($this->data->vehicle[0] as $key => $val) {
                $vehicle[(string)$key] = (string)$val;
            }

            if (isset($this->data->vehicle[0]->dealer[0])) {
                foreach ($this->data->vehicle[0]->dealer[0] as $key => $val) {
                    $vehicle["dealer"][(string)$key] = (string)$val;
                }
            }

            if (isset($this->data->vehicle[0]->carfax[0])) {
                foreach ($this->data->vehicle[0]->carfax[0] as $key => $val) {
                    $vehicle["carfax"][(string)$key] = (string)$val;
                }
            }

            if (isset($this->data->vehicle[0]->photos[0])) {
                $count = 0;
                foreach ($this->data->vehicle[0]->photos[0] as $key => $val) {
                    $vehicle["photos"][$count++] = (string)$val;
                }
            }
        }

        return $vehicle;
    }

    /*************************************************************************************************/

    public function GetVehicles($params)
    {
        $xml = "<?xml version='1.0' encoding='utf-8'?>";
        $xml .= "<request method='vehicle.list'>";
        foreach ($params as $key => $val) {
            $xml .= "<$key>$val</$key>";
        }
        $xml .= "<extra_columns>features</extra_columns>";
        $xml .= "</request>";
        $this->handleResponse($this->sendRequest($xml));

        $vehicles = array();

        if (isset($this->data->vehicles[0])) {
            foreach ($this->data->vehicles[0]->vehicle as $veh) {
                $vehicle           = array();
                $vehicle["photos"] = array();
                $veihcle["dealer"] = array();
                foreach ($veh as $key => $val) {
                    $key = (string)$key;
                    if ($key != "dealer" && $key != "photos") {
                        $vehicle[$key] = (string)$val;
                    }
                }

                if (isset($veh->photos[0])) {
                    foreach ($veh->photos[0] as $key => $val) {
                        $vehicle["photos"][] = (string)$val;
                    }
                }

                if (isset($veh->dealer[0])) {
                    foreach ($veh->dealer[0] as $key => $val) {
                        $vehicle["dealer"][(string)$key] = (string)$val;
                    }
                }

                $vehicles["list"][] = $vehicle;
            }
            $vehicles["total_count"] = (string)$this->data->meta[0]->total;
        }

        return $vehicles;
    }

    /*************************************************************************************************/

    public function GetRelatedVehicles($relVehId)
    {
        $xml = "<?xml version='1.0' encoding='utf-8'?>";
        $xml .= "<request method='vehicle.list_related'>";
        $xml .= "<veh_id>$relVehId</veh_id>";
        $xml .= "</request>";
        $this->handleResponse($this->sendRequest($xml));

        $vehicles = array();

        if (isset($this->data->vehicles[0])) {
            foreach ($this->data->vehicles[0]->vehicle as $veh) {
                $vehicle           = array();
                $vehicle["photos"] = array();
                $veihcle["dealer"] = array();
                foreach ($veh as $key => $val) {
                    $key = (string)$key;
                    if ($key != "dealer" && $key != "photos") {
                        $vehicle[$key] = (string)$val;
                    }
                }

                if (isset($veh->photos[0])) {
                    foreach ($veh->photos[0] as $key => $val) {
                        $vehicle["photos"][] = (string)$val;
                    }
                }

                if (isset($veh->dealer[0])) {
                    foreach ($veh->dealer[0] as $key => $val) {
                        $vehicle["dealer"][(string)$key] = (string)$val;
                    }
                }

                $vehicles["list"][] = $vehicle;
            }
            $vehicles["total_count"] = (string)$this->data->meta[0]->total;
        }

        return $vehicles;
    }

    /*************************************************************************************************/
    // Direct Vehicle link handler
    public function GetVehicleUrl($baseUrl, $veh)
    {
        $id             = isset($veh["id"]) ? $veh["id"] : "";
        $year           = isset($veh["year"]) ? $veh["year"] : "";
        $make           = isset($veh["make"]) ? $veh["make"] : "";
        $model          = isset($veh["model"]) ? $veh["model"] : "";
        $dealer_city    = (isset($veh["dealer"]) && isset($veh["dealer"]["city"])) ? $veh["dealer"]["city"] : "";
        $dealer_company = (isset($veh["dealer"]) && isset($veh["dealer"]["company"])) ? $veh["dealer"]["company"] : "";

        if (substr($baseUrl, strlen($baseUrl) - 1, 1) == "?") {
            $baseUrl .= "action/view_details/" . $id . "/" . $year . "-" . $make . "-" . $model;
            if (!empty($dealer_city)) {
                $baseUrl .= "/" . str_replace(" ", "-", trim($dealer_city));
            }
            if (!empty($dealer_company)) {
                $baseUrl .= "/" . str_replace(" ", "-", $dealer_company);
            }
        } else {
            $baseUrl .= 'action=view_details&id=' . $veh["id"];
        }

//        $siteUrl = sprintf( "%s://%s%s",
//            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '' && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http'),
//            $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);

        return $baseUrl;
    }

    /*************************************************************************************************/
    // List / Gallery handler
    public function GetDisplayUrl($baseUrl, $vars)
    {
        if ($vars->display == "" || $vars->display == "L") {
            $disp = "G";
        } else {
            $disp = "L";
        }

        $link = "";
        $link .= "page_num=" . $vars->page;
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

        return $baseUrl . $link;
    }

    /*************************************************************************************************/
    // Paging handler
    public function GetPagingUrl($baseUrl, $vars, $page)
    {
        $link = "";
        $link .= "page_num=" . $page;
        $link .= "&sortBy=" . $vars->sortBy;
        $link .= "&sortType=" . $vars->sortType;
        $link .= "&disp=" . $vars->display;
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

        return $baseUrl . $link;
    }

    /*************************************************************************************************/
    // Sorting handler
    public function GetSortingUrl($baseUrl, $vars, $sortColumn)
    {
        if ($sortColumn == $vars->sortBy) {
            $newSortType = $vars->sortType == "asc" ? "desc" : "asc";
        } else {
            $newSortType = "asc";
        }
        $link = "";
        $link .= "page_num=" . $vars->page;
        $link .= "&sortBy=" . $sortColumn;
        $link .= "&sortType=" . $newSortType;
        $link .= "&disp=" . $vars->display;
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

        return $baseUrl . $link;
    }

    /*************************************************************************************************/

    public function UpdateStat($vehicleId, $source, $ip)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="vehicle.update_stat">';
        $xml .= "<vehicle_id>$vehicleId</vehicle_id>";
        $xml .= "<source>$source</source>";
        $xml .= "<remote_ip>$ip</remote_ip>";
        $xml .= '</request>';

        $this->handleResponse($this->sendRequest($xml));

        return !$this->hasError;
    }

    /*************************************************************************************************/

    public function AddVehicleSearch($params)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="vsearch.add">';
        foreach ($params as $key => $val) {
            $xml .= "<$key>$val</$key>";
        }
        $xml .= '</request>';

        $this->handleResponse($this->sendRequest($xml));

        return !$this->hasError;
    }

    /*************************************************************************************************
     *
     * Function:
     *   AddVehicle()
     * Description:
     *   Add vehicle.
     * Input:
     *   Array of vehicle settings:
     *       dealer_id (AWE dealer id; only required if BROKER account)
     *           vin (required)
     *           stock (required)
     *           year (required)
     *           make (required)
     *           model (required)
     *           price (required)
     *           trim
     *           mileage
     *           exterior_color
     *           interior_color
     *           comments
     *           standard_features
     *           features
     *           cmpg
     *           hmpg
     *           engine
     *           drive
     *           trans
     *           stock_type
     *           payment
     *           blue_book_high
     *           blue_book_low
     *           type_code
     *           body_door_count
     *           seating_capacity
     *           classification
     *
     * Output:
     *   boolean; true if successful, false if not
     */
    public function AddVehicle($params)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="vehicle.add">';
        foreach ($params as $key => $val) {
            $xml .= "<$key>" . htmlspecialchars(utf8_encode($val)) . "</$key>";
        }
        $xml .= '</request>';

        $this->handleResponse($this->sendRequest($xml));

        return !$this->hasError;
    }

    /*************************************************************************************************
     *
     * Function:
     *   DeleteVehicle()
     * Description:
     *   Delte vehicle.
     * Input:
     *   id: vehicle id to delete
     * Output:
     *   boolean; true if successful, false if not
     */
    public function DeleteVehicle($id)
    {
        $xml = "<?xml version='1.0' encoding='utf-8'?>";
        $xml .= "<request method='vehicle.delete'>";
        $xml .= "<veh_id>$id</veh_id>";
        $xml .= "</request>";

        $this->handleResponse($this->sendRequest($xml));

        return !$this->hasError;
    }

    /*************************************************************************************************/

    public function sendMessage($params)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="contact.add">';
        foreach ($params as $key => $val) {
            $xml .= "<$key>$val</$key>";
        }
        $xml .= '</request>';

        $this->handleResponse($this->sendRequest($xml));

        return !$this->hasError;
    }

    /*************************************************************************************************/

    private function sendRequest($xml)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_POST           => true,
            CURLOPT_USERAGENT      => $this->config->appName,
            CURLOPT_USERPWD        => $this->config->aweAPIKey,
            CURLOPT_URL            => $this->config->aweAPIURL,
            CURLOPT_POSTFIELDS     => $xml
        );
        $ch      = curl_init();
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }

    /*************************************************************************************************/

    private function handleResponse($resp)
    {
        $this->hasError = false;
        $this->error    = "";
        if ($this->debug) {
            echo "<textarea rows='10' cols='100'>$resp</textarea><br /><br />";
        }
        if ($resp != "") {
            $i = strpos($resp, "<?xml");
            if ($i !== false) {
                $resp = substr($resp, $i);
            }
            $xml    = simplexml_load_string($resp);
            $attr   = $xml->attributes();
            $status = $attr["status"];
            if ($status == "fail") {
                $this->hasError = true;
                $this->error    = $xml->error;
            } else if ($status == "ok") {
                $this->data = $xml;
            }
        }
    }

    /*************************************************************************************************/
}
