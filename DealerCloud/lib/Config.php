<?php
/*************************************************************************************************/
//  DealerCloud Configuration Settings Class
/*************************************************************************************************/

class Config {
    public $aweAPIURL;
    public $aweAPIKey;
    public $appName;
    public $recaptchaPublicKey;
    public $recaptchaPrivateKey;
    public $googleMapsAPIKey;

/*************************************************************************************************/

    function __construct() {
        $this->aweAPIURL = "http://dealerapi.listingkit.com/api/1.0/xml-in.php";
        $this->aweAPIKey = get_option('awe_api_key');
        $this->appName   = get_option('dealers_domain');
        $this->recaptchaPublicKey  = get_option('recaptcha_public_key');
        $this->recaptchaPrivateKey = get_option('recaptcha_private_key');
        $this->googleMapsAPIKey = get_option('google_maps_key');
    }

/*************************************************************************************************/

}

?>
