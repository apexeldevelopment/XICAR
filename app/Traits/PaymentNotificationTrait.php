<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 9/6/23
 * Time: 4:10 PM
 */

namespace App\Traits;


use App\Models\Onesignal;

trait PaymentNotificationTrait
{
    public function paymentSuccessNotification($for, $receiver_id, $merchant_id, $title = "Payment Success",$description=""){
        if($for == "USER"){
            $data = array('notification_type' => 'PAYMENT_SUCCESS','segment_type' => 'PAYMENT_SUCCESS','segment_data'=>[]);
            $arr_param = array(
                'user_id' => $receiver_id,
                'data'=>$data,
                'message'=>$description,
                'merchant_id'=>$merchant_id,
                'title' => $title
            );
            Onesignal::UserPushMessage($arr_param);
        }else{
            $data = array('notification_type' => 'PAYMENT_SUCCESS', 'segment_type' => "PAYMENT_SUCCESS",'segment_data' => [],'notification_gen_time' => time());
            $arr_param = array(
                'driver_id' => $receiver_id,
                'large_icon' => "",
                'data'=>$data,
                'message'=>$description,
                'merchant_id'=>$merchant_id,
                'title' => $title,
                'description' => $description
            );
            Onesignal::DriverPushMessage($arr_param);
        }
    }

    public function paymentFailedNotification($for, $receiver_id, $merchant_id, $title = "Payment Failed",$description=""){
        if($for == "USER"){
            $data = array('notification_type' => 'PAYMENT_FAILED','segment_type' => 'PAYMENT_FAILED','segment_data'=>[]);
            $arr_param = array(
                'user_id' => $receiver_id,
                'data'=>$data,
                'message'=>$description,
                'merchant_id'=>$merchant_id,
                'title' => $title
            );
            Onesignal::UserPushMessage($arr_param);
        }else{
            $data = array('notification_type' => 'PAYMENT_FAILED', 'segment_type' => "PAYMENT_FAILED",'segment_data' => [],'notification_gen_time' => time());
            $arr_param = array(
                'driver_id' => $receiver_id,
                'large_icon' => "",
                'data'=>$data,
                'message'=>$description,
                'merchant_id'=>$merchant_id,
                'title' => $title,
                'description' => $description
            );
            Onesignal::DriverPushMessage($arr_param);
        }
    }
}
