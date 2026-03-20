<?php

namespace Module\Server\emailSolution\ApiCall;

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\emailSolution\Helper;

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
class ApiCall
{
    public $url = '';
    public $username = '';
    public $password = '';
    public $apiUrl = '';
    
    public function __construct()
    {
        $data = Capsule::table('tblservers')->where('type', 'email_solution')->first();
        $this->url = $data->hostname;
        $this->username = $data->username;
        $this->password = decrypt($data->password);
    }

    public function generateToken()
    {
        $data = array(
            'username' => $this->username,
            'password' => $this->password,
        );
        $this->apiUrl = 'https://' . $this->url . '/v1beta/auth/login';
        $header = array(
            'Content-Type: application/json',
        );
        $res =  $this->__curlCall("POST", $data, $this->apiUrl, $header, "Generate Token");

        Capsule::table('mod_email_solution_token')->delete();
        Capsule::table('mod_email_solution_token')->insert([
            'response' => json_encode($res),
            'datetime' => date('Y-m-d H:i:s'),
        ]);

        return json_encode($res);
    }

    public function __curlCall($method, $data = null, $apiUrl = null, $header  = "", $action = '')
    {
        $helper = new Helper();
        $curl = curl_init();
        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count($data) ? json_encode($data) : ""));
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count($data) ? json_encode($data) : ""));
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count($data) ? json_encode($data) : ""));
                break;
            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        
        curl_setopt($curl, CURLOPT_URL, $apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($curl);
       
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl)) {
            throw new \Exception(curl_error($curl));
        }
        curl_close($curl);

        if(isset($data['password'])){
            $data['password'] = '********';
        }

        logModuleCall("Email Solution", $action, $data, json_decode($response));
        return ['httpcode' => $httpCode, 'result' => json_decode($response)];
    }

    public function postCurl($data,$token, $baseUrl,$action ){
        $this->apiUrl = 'https://' . $this->url .$baseUrl;

        $header = array(
            'Content-Type: application/json',
            "Authorization: Bearer $token"
        );

        $res =  $this->__curlCall("POST", $data, $this->apiUrl, $header, $action);
        return $res;
    }

    public function deleteCurl($token, $baseUrl,$action ){
        $this->apiUrl = 'https://' . $this->url .$baseUrl;

        $header = array(
            'Content-Type: application/json',
            "Authorization: Bearer $token"
        );

        $res =  $this->__curlCall("DELETE",[], $this->apiUrl, $header, $action);
        return $res;
    }

    public function getCurl($token, $baseUrl,$action ){
        $this->apiUrl = 'https://' . $this->url .$baseUrl;

        $header = array(
            'Content-Type: application/json',
            "Authorization: Bearer $token"
        );

        $res =  $this->__curlCall("GET",[], $this->apiUrl, $header, $action);
        return $res;
    }

}
