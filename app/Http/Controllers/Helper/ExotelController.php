<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;

class ExotelController extends Controller
{
    public function ConnectCalls($api_key,$api_token,$exotel_sid,$caller_id,$from,$to){
        $post_data = array(
            'From' => $from,
            'To' => $to,
            'CallerId' => $caller_id,
            'CallType' => "trans"
        );
        #Replace <subdomain> with the region of your account
        #<subdomain> of Singapore cluster is @api.exotel.com
        #<subdomain> of Mumbai cluster is @api.in.exotel.com
        $url = "https://" . $api_key . ":" . $api_token . "@api.exotel.com/v1/Accounts/" . $exotel_sid . "/Calls/connect";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        $http_result = curl_exec($ch);
        curl_close($ch);
        print "Response = ".print_r($http_result);
    }
}
