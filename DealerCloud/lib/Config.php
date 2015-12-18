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
//    public $GoogleMapsAPIKey;
//    public $YahooMapsAppId;
    
/*************************************************************************************************/

    function __construct() {
        $this->aweAPIURL = "http://www.dealercloud.com/api/1.0/xml-in";
        $this->aweAPIKey = get_option('awe_api_key');
        $this->appName   = get_option('dealers_domain');
        $this->recaptchaPublicKey  = get_option('recaptcha_public_key');
        $this->recaptchaPrivateKey = get_option('recaptcha_private_key');
//        $this->GoogleMapsAPIKey = get_option('google_maps_key');
//        $this->YahooMapsAppId   = "1XplFcXV34Gp3hpTKS.VphQUS9uLsd2aiElq2YBNv_hlOWAJcid7DKOdqalgtJyG4w--";
    }

/*************************************************************************************************/

}

/*************************************************************************************************/
?>
