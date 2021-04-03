<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Routing\Controller as BaseController;
use Zoho\CRM\ZohoClient;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function joinDealAndTask()
    {
        $deal = $this->makeDeal();
        $dealId = $deal[0]['details']['id'];

        $task = $this->makeTask();
        $taskId = $task[0]['details']['id'];

        $response = $this->putRequest('Tasks/' . $taskId, [
            '$se_module' => 'Deals',
            'What_Id'    => $dealId
        ]);

        return Response::json($response);
    }


    public function makeDeal()
    {
        $data = [
            'Deal_Name' => 'Test DEAL',
            'Amount'    => 1200
        ];
        $response = $this->postRequest('Deals', $data);

        return $response['data'];
    }

    public function makeTask()
    {
        $data = [
            'Subject'  => 'Test Task',
            'Due_Date' => '2021-01-01'
        ];
        $response = $this->postRequest('Tasks', $data);

        return $response['data'];
    }

    public function oauthGetAccessToken()
    {
        $result = $this->makePostForToken([
            'grant_type'    => "refresh_token",
            'client_id'     => env('ZOHO_CLIENT_ID'),
            'client_secret' => env('ZOHO_SECRET'),
            'refresh_token' => env('ZOHO_REFRESH_TOKEN'),
        ]);

        return $result->access_token;
    }

    public function makePostForToken($data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://accounts.zoho.com/oauth/v2/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);

        return json_decode($server_output);
    }

    public function getRequest($to, $data)
    {
        $ch = curl_init();

        $to .= '?' . http_build_query($data);
        $url = "https://www.zohoapis.com/crm/v2/" . $to;
        curl_setopt($ch, CURLOPT_URL, $url);

        $headersArray = [];
        $headersArray[] = "Authorization" . ":" . "Zoho-oauthtoken " . $this->oauthGetAccessToken();

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArray);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);

        return json_decode($server_output, true);
    }

    public function postRequest($to, $data)
    {
        return $this->otherRequest($to, $data, 'POST');
    }

    public function putRequest($to, $data)
    {
        return $this->otherRequest($to, $data, 'PUT');
    }

    public function otherRequest($to, $data, $type)
    {
        $curl_pointer = curl_init();

        $curl_options = [];
        $url = "https://www.zohoapis.com/crm/v2/" . $to;

        $curl_options[CURLOPT_URL] = $url;
        $curl_options[CURLOPT_RETURNTRANSFER] = true;
        $curl_options[CURLOPT_HEADER] = 1;
        $curl_options[CURLOPT_CUSTOMREQUEST] = $type;
        $requestBody = [];
        $recordArray = [];
        $recordObject = $data;

        $recordArray[] = $recordObject;
        $requestBody["data"] = $recordArray;
        $curl_options[CURLOPT_POSTFIELDS] = json_encode($requestBody);
        $headersArray = [];

        $headersArray[] = "Authorization" . ":" . "Zoho-oauthtoken " . $this->oauthGetAccessToken();

        $curl_options[CURLOPT_HTTPHEADER] = $headersArray;

        curl_setopt_array($curl_pointer, $curl_options);

        $result = curl_exec($curl_pointer);
        $responseInfo = curl_getinfo($curl_pointer);
        curl_close($curl_pointer);
        [$headers, $content] = explode("\r\n\r\n", $result, 2);
        if (strpos($headers, " 100 Continue") !== false) {
            [$headers, $content] = explode("\r\n\r\n", $content, 2);
        }
        $headerArray = (explode("\r\n", $headers, 50));
        $headerMap = [];
        foreach ($headerArray as $key) {
            if (strpos($key, ":") != false) {
                $firstHalf = substr($key, 0, strpos($key, ":"));
                $secondHalf = substr($key, strpos($key, ":") + 1);
                $headerMap[$firstHalf] = trim($secondHalf);
            }
        }
        $jsonResponse = json_decode($content, true);
        if ($jsonResponse == null && $responseInfo['http_code'] != 204) {
            [$headers, $content] = explode("\r\n\r\n", $content, 2);
            $jsonResponse = json_decode($content, true);
        }

        return $jsonResponse;
    }


    //only for get refresh toket
    public function makeAuth()
    {
        return ('https://accounts.zoho.com/oauth/v2/auth?' . http_build_query([
                'scope'         => "ZohoCRM.modules.ALL,ZohoCRM.settings.ALL",
                'client_id'     => env('ZOHO_CLIENT_ID'),
                'response_type' => 'code',
                'access_type'   => 'offline',
                'redirect_uri'  => 'https://localhost/refresh-token'
            ]));
    }

    public function oauthGetRefreshToken()
    {
        $code = Request::get('code');

        $result = $this->makePostForToken([
            'grant_type'    => "authorization_code",
            'client_id'     => env('ZOHO_CLIENT_ID'),
            'client_secret' => env('ZOHO_SECRET'),
            'redirect_uri'  => 'https://localhost/refresh-token',
            'code'          => $code
        ]);

        return Response::json($result);
    }
}
