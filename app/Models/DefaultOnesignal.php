<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultOnesignal extends Model
{
    protected $guarded = [];

    /*OneSignal Android+ iOS notification*/
    public static function oneSignalCurl($arr_param)
    {
        $default_onesignal = self::whereNull("merchant_id")->whereNull("package_name")->first();
        if(!empty($default_onesignal)){
            $playerid = $arr_param['playerid'];
            $data = $arr_param['data'];
            $message = $arr_param['message'];
            $notification_for = $arr_param['notification_for'];
            $title = $arr_param['title'];
            $large_icon = isset($arr_param['large_icon']) ? $arr_param['large_icon'] : NULL;

            if($notification_for == "user") {
                $application_key = $default_onesignal->user_application_key;
                $rest_key = $default_onesignal->user_rest_key;
                $channel_id = $default_onesignal->user_channel_id;
                $data['segment_sub_group'] = isset($data['segment_sub_group']) ? $data['segment_sub_group'] :  NULL;
                $data['segment_group_id'] = isset($data['segment_group_id']) ? $data['segment_group_id'] :  NULL;
            } elseif($notification_for == "driver") {
                $application_key = $default_onesignal->driver_application_key;
                $rest_key = $default_onesignal->driver_rest_key;
                $channel_id = $default_onesignal->driver_channel_id;
                $data['segment_sub_group'] = isset($data['segment_sub_group']) ? $data['segment_sub_group'] :  "NA";
                $data['segment_group_id'] = isset($data['segment_group_id']) ? $data['segment_group_id'] :  "NA";
            } elseif($notification_for == "business_segment") {
                $application_key = $default_onesignal->business_segment_application_key;
                $rest_key = $default_onesignal->business_segment_rest_key;
                $channel_id = $default_onesignal->business_segment_channel_id;
                $data['segment_sub_group'] = isset($data['segment_sub_group']) ? $data['segment_sub_group'] :  "NA";
                $data['segment_group_id'] = isset($data['segment_group_id']) ? $data['segment_group_id'] :  "NA";
            }
            $playerid = array_filter($playerid);
            $content = array(
                "en" => $message,
            );
            $heading_content = array(
                "en" => $title,
            );
            // adding new key to data
            $data['notification_gen_time'] = time();

            $sendField = "include_player_ids";
            $fields = array(
                'app_id' => $application_key,
                $sendField => $playerid,
                'contents' => $content,
                'content_available' => true,
                'mutable_content' => true,
//            'android_channel_id' => $channel_id,
                'headings' => $heading_content,
                'data' => $data,
                'large_icon'=>"",
                'priority' => 10,
//                "https://i.ibb.co/6R3c78y/logo-circular-lack.png",//$large_icon // Dump icon
            );

            $fields = json_encode($fields);
            // p($fields);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                'Authorization: Basic ' . $rest_key));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $response = curl_exec($ch);
            // p($response);
            curl_close($ch);
            return $response;
        }
    }

    /*OneSignal Package wise Android+ iOS notification*/
    public static function packageWiseOneSignalCurl($merchant, $onesignal_player_data, $arr_param)
    {
        $filtered_data = [];
        foreach($onesignal_player_data as $onesignal_player_datum){
            $filtered_data[$onesignal_player_datum['device']][$onesignal_player_datum['package_name']][] = $onesignal_player_datum;
        }
        $ios_player_data = isset($filtered_data[2]) ? $filtered_data[2] : [];
        $android_player_data = isset($filtered_data[1]) ? $filtered_data[1] : [];

        foreach($ios_player_data as $package_name => $datum){
            $default_onesignal = self::where("merchant_id", $merchant->id)->where("package_name", $package_name)->first();
            $arr_param['playerid'] = array_pluck($datum, 'player_id');
            if(!empty($default_onesignal)){
                $playerid = $arr_param['playerid'];
                $data = $arr_param['data'];
                $message = $arr_param['message'];
                $notification_for = $arr_param['notification_for'];
                $title = $arr_param['title'];
                $large_icon = isset($arr_param['large_icon']) ? $arr_param['large_icon'] : NULL;

                if($notification_for == "user") {
                    $application_key = $default_onesignal->user_application_key;
                    $rest_key = $default_onesignal->user_rest_key;
                    $channel_id = $default_onesignal->user_channel_id;
                    $data['segment_sub_group'] = isset($data['segment_sub_group']) ? $data['segment_sub_group'] :  NULL;
                    $data['segment_group_id'] = isset($data['segment_group_id']) ? $data['segment_group_id'] :  NULL;
                } elseif($notification_for == "driver") {
                    $application_key = $default_onesignal->driver_application_key;
                    $rest_key = $default_onesignal->driver_rest_key;
                    $channel_id = $default_onesignal->driver_channel_id;
                    $data['segment_sub_group'] = isset($data['segment_sub_group']) ? $data['segment_sub_group'] :  "NA";
                    $data['segment_group_id'] = isset($data['segment_group_id']) ? $data['segment_group_id'] :  "NA";
                } elseif($notification_for == "business_segment") {
                    $application_key = $default_onesignal->business_segment_application_key;
                    $rest_key = $default_onesignal->business_segment_rest_key;
                    $channel_id = $default_onesignal->business_segment_channel_id;
                    $data['segment_sub_group'] = isset($data['segment_sub_group']) ? $data['segment_sub_group'] :  "NA";
                    $data['segment_group_id'] = isset($data['segment_group_id']) ? $data['segment_group_id'] :  "NA";
                }
                $playerid = array_filter($playerid);
                $content = array(
                    "en" => $message,
                );
                $heading_content = array(
                    "en" => $title,
                );
                // adding new key to data
                $data['notification_gen_time'] = time();

                $sendField = "include_player_ids";
                $fields = array(
                    'app_id' => $application_key,
                    $sendField => $playerid,
                    'contents' => $content,
                    'content_available' => true,
                    'mutable_content' => true,
//            'android_channel_id' => $channel_id,
                    'headings' => $heading_content,
                    'data' => $data,
                    'large_icon'=>"",
                    'priority' => 10,
//                "https://i.ibb.co/6R3c78y/logo-circular-lack.png",//$large_icon // Dump icon
                );

                $fields = json_encode($fields);
                // p($fields);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                    'Authorization: Basic ' . $rest_key));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                $response = curl_exec($ch);
                // p($response);
                curl_close($ch);
                return $response;
            }
        }

        foreach($android_player_data as $package_name => $datum){
            $default_onesignal = self::where("merchant_id", $merchant->id)->where("package_name", $package_name)->first();
            $arr_param['playerid'] = array_pluck($datum, 'player_id');
            if(!empty($default_onesignal)){
                $playerid = $arr_param['playerid'];
                $data = $arr_param['data'];
                $message = $arr_param['message'];
                $notification_for = $arr_param['notification_for'];
                $title = $arr_param['title'];
                $large_icon = isset($arr_param['large_icon']) ? $arr_param['large_icon'] : NULL;

                if($notification_for == "user") {
                    $application_key = $default_onesignal->user_application_key;
                    $rest_key = $default_onesignal->user_rest_key;
                    $channel_id = $default_onesignal->user_channel_id;
                    $data['segment_sub_group'] = isset($data['segment_sub_group']) ? $data['segment_sub_group'] :  NULL;
                    $data['segment_group_id'] = isset($data['segment_group_id']) ? $data['segment_group_id'] :  NULL;
                } elseif($notification_for == "driver") {
                    $application_key = $default_onesignal->driver_application_key;
                    $rest_key = $default_onesignal->driver_rest_key;
                    $channel_id = $default_onesignal->driver_channel_id;
                    $data['segment_sub_group'] = isset($data['segment_sub_group']) ? $data['segment_sub_group'] :  "NA";
                    $data['segment_group_id'] = isset($data['segment_group_id']) ? $data['segment_group_id'] :  "NA";
                } elseif($notification_for == "business_segment") {
                    $application_key = $default_onesignal->business_segment_application_key;
                    $rest_key = $default_onesignal->business_segment_rest_key;
                    $channel_id = $default_onesignal->business_segment_channel_id;
                    $data['segment_sub_group'] = isset($data['segment_sub_group']) ? $data['segment_sub_group'] :  "NA";
                    $data['segment_group_id'] = isset($data['segment_group_id']) ? $data['segment_group_id'] :  "NA";
                }
                $playerid = array_filter($playerid);
                $content = array(
                    "en" => $message,
                );
                $heading_content = array(
                    "en" => $title,
                );
                // adding new key to data
                $data['notification_gen_time'] = time();

                $sendField = "include_player_ids";
                $fields = array(
                    'app_id' => $application_key,
                    $sendField => $playerid,
                    'contents' => $content,
                    'content_available' => true,
                    'mutable_content' => true,
//            'android_channel_id' => $channel_id,
                    'headings' => $heading_content,
                    'data' => $data,
                    'large_icon'=>"",
                    'priority' => 10,
//                "https://i.ibb.co/6R3c78y/logo-circular-lack.png",//$large_icon // Dump icon
                );

                $fields = json_encode($fields);
                // p($fields);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                    'Authorization: Basic ' . $rest_key));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                $response = curl_exec($ch);
                // p($response);
                curl_close($ch);
                return $response;
            }
        }
    }
}
