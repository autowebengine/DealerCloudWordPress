<?php
require_once("Cache.php");
require_once("Config.php");

/*************************************************************************************************/
// DealerCloud API Client Class
/*************************************************************************************************/

class Client {
    
    private $enableCache = false;
    private $debug = false;
    private $config = false;
    
    public $hasError;
    public $error;
    private $data;
    
/*************************************************************************************************/

    function __construct() {
        $this->config = new Config();
    }
    
/*************************************************************************************************/

    public function GetMakes() {
        
        if($this->enableCache && Cache::hasMakes()) {
            return Cache::getMakes();
        }
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="vehicle.list_makes">';
        $xml .= '</request>';
        $this->handleResponse($this->sendRequest($xml));
        
        $makes = array();
        foreach($this->data->makes->make as $make) {
            $makes[] = (string)$make;
        }
        
        if($this->enableCache) {
            Cache::setMakes($makes);
        }
        
        return $makes;
    }
    
/*************************************************************************************************/

    public function GetModels($make) {
        
        if($this->enableCache && Cache::hasModels()) {
            return Cache::getModels();
        }
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="vehicle.list_models">';
        $xml .= "<make>$make</make>";
        $xml .= '</request>';
        $this->handleResponse($this->sendRequest($xml));
        
        $models = array();
        foreach($this->data->models->model as $model) {
            $models[] = (string)$model;
        }
        
        if($this->enableCache) {
            Cache::setModels($models);
        }
        
        return $models;
    }
    
    
/*************************************************************************************************/

    public function sendMessage($params) {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="contact.add">';
        foreach($params as $key=>$val) {
            $xml .= "<$key>$val</$key>";
        }
        $xml .= '</request>';
        $this->handleResponse($this->sendRequest($xml));
        
        return !$this->hasError;
    }
    
/*************************************************************************************************/

    public function GetVehicles($params) {
    
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request method="vehicle.list">';
        $xml .= '<query_db>0</query_db>';
        foreach($params as $key=>$val) {
            $xml .= "<$key>$val</$key>";
        }
        $xml .= '</request>';
        $this->handleResponse($this->sendRequest($xml));
        
        $vehicles = array();
        $vehCount = 0;
        
        foreach($this->data->vehicles[0]->vehicle as $veh) {
            $vehicle = array();
            $vehicle["photos"] = array();
            $veihcle["dealer"] = array();
            foreach($veh as $key=>$val) {
                $key = (string)$key;
                if($key != "dealer" && $key != "photos") {
                    $vehicle[$key] = (string)$val;
                }
            }
            foreach($veh->photos[0] as $key=>$val) {
                $vehicle["photos"][] = (string)$val;
            }
            
            foreach($veh->dealer[0] as $key=>$val) {
                $vehicle["dealer"][(string)$key] = (string)$val;
            }
            
            $vehicles["list"][] = $vehicle;
            $vehCount += 1;
        }
        //$vehicles["total_count"] = (string)$vehCount;
        $vehicles["total_count"] = (string)$this->data->meta[0]->total;
        //$vehicles["total_count"] = count($vehicles["list"]);
        
        return $vehicles;
    }
    
/*************************************************************************************************/

    public function GetVehicle($id) {
        $xml = "<?xml version='1.0' encoding='utf-8'?>";
        $xml .= "<request method='vehicle.get'>";
        $xml .= "<veh_id>$id</veh_id>";
        $xml .= "<track_stats>1</track_stats>";
        $xml .= "<remote_ip>" . $_SERVER["REMOTE_ADDR"] . "</remote_ip>";
        $xml .= "</request>";
        $this->handleResponse($this->sendRequest($xml));
        
        $vehicle = array();
        foreach($this->data->vehicle[0] as $key=>$val) {
            $vehicle[(string)$key] = (string)$val;
        }
        
        foreach($this->data->vehicle[0]->dealer[0] as $key=>$val) {
            $vehicle["dealer"][(string)$key] = (string)$val;
        }
        
        foreach($this->data->vehicle[0]->carfax[0] as $key=>$val) {
            $vehicle["carfax"][(string)$key] = (string)$val;
        }
        
        $count = 0;
        foreach($this->data->vehicle[0]->photos[0] as $key=>$val) {
            $vehicle["photos"][$count++] = (string)$val;
        }
        
        return $vehicle;
    }
    
/*************************************************************************************************/

    public function GetRelatedVehicles($relVehId) {
        $xml = "<?xml version='1.0' encoding='utf-8'?>";
        $xml .= "<request method='vehicle.list_related'>";
        $xml .= "<veh_id>$relVehId</veh_id>";
        $xml .= "</request>";
        $this->handleResponse($this->sendRequest($xml));
        
        $vehicles = array();
        
        foreach($this->data->vehicles[0]->vehicle as $veh) {
            $vehicle = array();
            $vehicle["photos"] = array();
            $veihcle["dealer"] = array();
            foreach($veh as $key=>$val) {
                $key = (string)$key;
                if($key != "dealer" && $key != "photos") {
                    $vehicle[$key] = (string)$val;
                }
            }
            
            foreach($veh->photos[0] as $key=>$val) {
                $vehicle["photos"][] = (string)$val;
            }
            
            foreach($veh->dealer[0] as $key=>$val) {
                $vehicle["dealer"][(string)$key] = (string)$val;
            }
            
            $vehicles["list"][] = $vehicle;
        }
        
        return $vehicles;
    }
    
/*************************************************************************************************/

    private function handleResponse($resp) {
        $this->hasError = false;
        $this->error = "";
        if($this->debug) {
            echo "<textarea rows='10' cols='100'>$resp</textarea><br /><br />";
        }
        if($resp != "") {
            $i = strpos($resp, "<?xml");
            if ($i !== false) {
                $resp = substr($resp, $i);
            }
            $xml = simplexml_load_string($resp);
            $attr = $xml->attributes();
            $status = $attr["status"];
            if($status == "fail") {
                $this->hasError = true;
                $this->error = $xml->error;
            } else if ($status == "ok") {
                $this->data = $xml;
            }
        }
    }
    
/*************************************************************************************************/

    private function sendRequest($xml) {
        $options = array(
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CONNECTTIMEOUT  => 120,
            CURLOPT_TIMEOUT         => 120,
            CURLOPT_POST            => true,
            CURLOPT_USERAGENT       => $this->config->appName,
            CURLOPT_USERPWD         => $this->config->aweAPIKey,
            CURLOPT_URL             => $this->config->aweAPIURL,
            CURLOPT_POSTFIELDS      => $xml
        );
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }

/*************************************************************************************************/

}

/*************************************************************************************************/
?>
