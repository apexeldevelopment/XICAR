<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 18/7/22
 * Time: 1:05 PM
 */

namespace App\Http\Controllers\PaymentMethods\Kbzpay;

use Illuminate\Http\Request;


class KbzpayController
{
    public function notify(Request $request){
        $request_response = $request->all();
        $data = [
            'type'=>'Kbzpay - Notify',
            'data'=>$request_response
        ];
        \Log::channel('kbzpay_api')->emergency($data);
    }

    public function referer(Request $request){
        $request_response = $request->all();
        $data = [
            'type'=>'Kbzpay - Referer',
            'data'=>$request_response
        ];
        \Log::channel('kbzpay_api')->emergency($data);
    }

    public function callback(Request $request){
        $request_response = $request->all();
        $data = [
            'type'=>'Kbzpay - Callback',
            'data'=>$request_response
        ];
        \Log::channel('kbzpay_api')->emergency($data);
    }
}