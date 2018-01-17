<?php
    if(!isset($_SESSION)) { session_start(); }
    $doc_level_1 = $_SERVER['DOCUMENT_ROOT'];
    $doc_level_0 = dirname($_SERVER['DOCUMENT_ROOT']);
    $web_settings = $doc_level_0.'/web_settings.json';
    if(file_exists($web_settings)) {
        $web_settings = json_decode_check(file_get_contents($web_settings));
        if(isset($web_settings['error'])) {
            //cannot decode $web_settings
            //display error or config setup from here
        }
    }

/* FUNCTIONS -------------------------------------------------*/
    function json_decode_check($string) {
        $error = 'Decoding: ' . $string;
        $json = json_decode($string);
    
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $json;
            break;
            case JSON_ERROR_DEPTH:
                return array("error" => $error . ' - Maximum stack depth exceeded');
            break;
            case JSON_ERROR_STATE_MISMATCH:
                return array("error" => $error . ' - Underflow or the modes mismatch');
            break;
            case JSON_ERROR_CTRL_CHAR:
                return array("error" => $error . ' - Unexpected control character found');
            break;
            case JSON_ERROR_SYNTAX:
                return array("error" => $error . ' - Syntax error, malformed JSON');
            break;
            case JSON_ERROR_UTF8:
                return array("error" => $error . ' - Malformed UTF-8 characters, possibly incorrectly encoded');
            break;
            default:
                return array("error" => $error . ' - Unknown error');
            break;
        }
    }
?>
