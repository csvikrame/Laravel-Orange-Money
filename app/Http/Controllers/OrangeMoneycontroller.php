<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class OrangeMoneyController extends Controller
{

    /**
     * Url lorsqu'il annule le paiement
     */
    private $cancel_url = "www.google.com";
    /**
     * Url lorsqu'il ya des notifications consernant le paiement
     */
    private $notif_url = "www.google.com";
    /**
     * Le Devise que vous voulez utilisé
     */
    private $currency = "GNF";
    /**
     * Url de retour apres le paiement
     */
    private $return_url = "www.google.com";
    /**
     * La cle marchant qui sera generer apres la creation de 
     */
    private $merchant_key = "ea0dd6lllk457ab";
    /**
     * Url lorsqu'il annule le paiement
     */
    private $montant = 100000;
    /**
     * Url lorsqu'il annule le paiement
     */
    private $reference = 'PAIEMENT ORANGE MONEY';
    /**
     * Le token pour autoriser la generation du token
     */
    public $token_autorization = "Basic V3ZYS6540FtYnBGGHmNzVxeUFpbGRwcTliMUxmZljklV3bEV2OG06MnB0Qkd3ZTcxUFVmeUFkeA==";
    /**
     * API ORANGE URL
     */
    private $url = 'https://api.orange.com/orange-money-webpay/gn/v1/webpayment';
    /**
     * Token api
     */
    private $token_api = "uB5Upe6Ej4fTJCAL2As6652ojhjhGHgghRUlKV00";

    /**
     * Methode pour generer le token
     */
    public function generateToken() {
        $url = "https://api.orange.com/oauth/v2/token";

        $curls = curl_init();
        curl_setopt($curls, CURLOPT_URL, $url);
        curl_setopt($curls, CURLOPT_POST, true);
        curl_setopt($curls, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($curls, CURLOPT_HTTPHEADER, array(
            'Authorization: '.$this->token_autorization,
        ));
        $token = curl_exec($curls);
        curl_close($curls);
    }
    public function initPayement(Request $request) {
        $order_id = $request->order_id; 
        $montant = $request->montant; 

        $data = array(
            "merchant_key" => $this->merchant_key,
            "currency" => $this->currency,
            "order_id" => $order_id,
            "amount" => $montant ? $ $montant : $this->montant,
            "return_url" => $this->return_url,
            "cancel_url" => $this->cancel_url,
            "notif_url" => $this->notif_url,
            "lang" => "fr",
            "reference" => $this->reference
        );
        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '.$this->token_api
        );
        $makeUrl =  $this->callApi($headers, $data, $this->url, 'POST', 201, true);
        if ($makeUrl) {
            // dd($makeUrl['payment_url']);
            $url_ = $makeUrl['payment_url'];
            // dd($url_);
            return Redirect::to($url_);
        }
    }
    public function callApi($headers, $args, $url, $method, $successCode, $jsonEncodeArgs = false) {
        $ch = curl_init();
    
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($args)) {
                if ($jsonEncodeArgs === true) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
                }
            }
        } else /* $method === 'GET' */ {
            if (!empty($args)) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($args));
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // if ($this->getVerifyPeerSSL() === false) {
        //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // }
        // Make sure we can access the response when we execute the call
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($ch);

        if ($data === false) {
            return array('error' => 'API call failed with cURL error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
        curl_close($ch);

        $response = json_decode($data, true);

        $jsonErrorCode = json_last_error();
        if ($jsonErrorCode !== JSON_ERROR_NONE) {
            return array(
                'error' => 'API response not well-formed (json error code: '
                    . $jsonErrorCode . ')'
            );
        }

        if ($httpCode !== $successCode) {
            $errorMessage = '';

            if (!empty($response['error_description'])) {
                $errorMessage = $response['error_description'];
            } elseif (!empty($response['error'])) {
                $errorMessage = $response['error'];
            } elseif (!empty($response['description'])) {
                $errorMessage = $response['description'];
            } elseif (!empty($response['message'])) {
                $errorMessage = $response['message'];
            } elseif (!empty($response['requestError']['serviceException'])) {
                $errorMessage = $response['requestError']['serviceException']['text']
                    . ' ' . $response['requestError']['serviceException']['variables'];
            } elseif (!empty($response['requestError']['policyException'])) {
                $errorMessage = $response['requestError']['policyException']['text']
                    . ' ' . $response['requestError']['policyException']['variables'];
            }

            return array('error' => $errorMessage);
        }

        return $response;
    }
}

