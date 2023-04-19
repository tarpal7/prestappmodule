<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
require_once(dirname(__FILE__) . '/PrestappApi.php');

class PrestappConnect
{
    function __construct($url, $key) {
    }

    public static function sendTo($url, $data)
    {
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n"."Authorization: Bearer".strval(Configuration::get('PRESTAPP_V2_MODULE_TOKEN'))."\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            ),
            "ssl" => array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */ }

        return $result;
    }
}