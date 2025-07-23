<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 20/3/23
 * Time: 3:18 PM
 */

namespace App\Http\Controllers\PaymentMethods\Midtrans;


class MidtransLib
{
    var $server_key = "";
    var $is_production = false;
    var $basepath = "";


    function __construct($is_production, $server_key){
        $this->basepath = $is_production ? 'https://app.midtrans.com/' : 'https://app.sandbox.midtrans.com/';
        $this->server_key = $server_key;
    }

    function charge($param)
    {
        $url = $this->basepath . 'snap/v1/transactions';
        $data = $this->fetch($url, null, true, $param);
        if ($this->isJson($data))
            return json_decode($data);
        else
            return false;
    }

    function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    function fetch($url, $header, $is_post = FALSE, $data)
    {
        if ($header == null) {
            $header = array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($this->server_key . ':')
            );
        };

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        if ($is_post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $data = curl_exec($ch);

        if (curl_errno($ch)) {
            return curl_error($ch);
        }

        $result = array(
            'body' => json_decode($data),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        );

        return json_encode($result);
    }

    function convert_response_status($data)
    {
        $payment_type = $data->payment_type;

        $result = new stdClass();
        $result->data = new stdClass();
        if ($payment_type == "credit_card") {
            $result->payment_type = "Credit / Debit Card";
            $result->data->card_number = $data->masked_card;
            //$result->data->eci = $data->eci;
            //$result->data->response_message = $data->channel_response_message;
            //$result->data->response_code = $data->channel_response_code;
            $result->data->card_type = $data->card_type;
            $result->data->bank = $data->bank;
            $result->data->approval_code = $data->approval_code;
        } else if ($payment_type == "gopay") {
            $result->payment_type = "GOPAY";
        } else if ($payment_type == "bank_transfer") {
            if (isset($data->permata_va_number)) {
                $result->payment_type = "Permata Virtual Account";
                $result->data->virtual_account_number = $data->permata_va_number;
                $result->data->bank = "permata";
            } else if (isset($data->va_numbers)) {
                if ($data->va_numbers[0]->bank == "bca") {
                    $result->payment_type = "BCA Virtual Account";
                    $result->data->virtual_account_number = $data->va_numbers[0]->va_number;
                    $result->data->bank = $data->va_numbers[0]->bank;
                } else if ($data->va_numbers[0]->bank == "bni") {
                    $result->payment_type = "BNI Virtual Account";
                    $result->data->virtual_account_number = $data->va_numbers[0]->va_number;
                    $result->data->bank = $data->va_numbers[0]->bank;
                }
            }
        } else if ($payment_type == "echannel") {
            $result->payment_type = "Mandiri Bill";
            $result->data->company_code = $data->biller_code;
            $result->data->billpay_code = $data->bill_key;
            $result->data->approval_code = $data->approval_code;
        } else if ($payment_type == "bca_klikpay") {
            $result->payment_type = "BCA Klikpay";
            $result->data->approval_code = $data->approval_code;
        } else if ($payment_type == "bca_klikbca") {
            $result->payment_type = "KlikBCA";
            $result->data->approval_code = $data->approval_code;
        } else if ($payment_type == "mandiri_clickpay") {
            $result->payment_type = "Mandiri ClickPay";
            $result->data->approval_code = $data->approval_code;
        } else if ($payment_type == "cimb_clicks") {
            $result->payment_type = "CIMB Clicks";
            $result->data->approval_code = $data->approval_code;
        } else if ($payment_type == "danamon_online") {
            $result->payment_type = "Danamon Online";
            $result->data->approval_code = $data->approval_code;
        } else if ($payment_type == "cstore") {
            if ($data->store == "indomaret") {
                $result->payment_type = "Indomaret";
                $result->data->payment_code = $data->payment_code;
                $result->data->approval_code = $data->approval_code;
            } else if ($data->store == "alfamart") {
                $result->payment_type = "Alfamart";
                $result->data->payment_code = $data->payment_code;
                $result->data->approval_code = $data->approval_code;
            }
        } else if ($payment_type == "akulaku") {
            $result->payment_type = "Akulaku";
        } else if ($payment_type == "bri_epay") {
            $result->payment_type = "BRI Epay";
            $result->data->approval_code = $data->approval_code;
        }
        return $result;
    }
}
