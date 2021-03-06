<?php
/*************************************************************************************************/
// Session Helper Class
/*************************************************************************************************/

class Session {

/*************************************************************************************************/

    public static function get($key) {
        if(isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } else {
            return null;
        }
    }
    
/*************************************************************************************************/

    public static function set($key, $val) {
        $_SESSION[$key] = $val;
    }

/*************************************************************************************************/

}

/*************************************************************************************************/
?>
