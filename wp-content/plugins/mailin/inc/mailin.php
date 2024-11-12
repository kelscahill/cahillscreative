<?php

/**
 * Sendinblue REST client
 */

class Mailin
{
    public $api_key;
    public $base_url;
    public function __construct($base_url,$api_key)
    {
        if(!function_exists('curl_init'))
        {
            throw new Exception('Mailin requires CURL module');
        }
        $this->base_url = $base_url;
        $this->api_key = $api_key;
    }
    /**
     * Do CURL request with authorization
     */
    private function do_request($resource,$method,$input)
    {
        $called_url = $this->base_url."/".$resource;
        $ssl_verify = true;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows only over-ride
            $ssl_verify = false;
        }

         $args = array(
             'method'       => $method,
             'sslverify'    => $ssl_verify,
             'headers'      => array(
                 'api-key' => $this->api_key,
                 'Content-Type'=> 'application/json',
                 'User-Agent' => 'sendinblue_plugins/wordpress',
                 ),
           );
         $args['body'] = $input;
         
		 $response = wp_remote_request($called_url, $args);
         $data = wp_remote_retrieve_body($response);

        return json_decode($data,true);
    }
    public function post($resource,$input)
    {
        return $this->do_request($resource,"POST",json_encode($input));
    }
}
?>
