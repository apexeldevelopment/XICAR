<?php

namespace App\Http\Controllers\Helper;
use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FireBaseController extends Controller
{
    //@ayush
    //Firebase v2 update

    public function getFireBaseAuthorization($merchant){
        $file_path = "firebase/".$merchant->OneSignal->firebase_project_file;

        try{
            $credentialsFilePath = storage_path($file_path);
            $client = new \Google_Client();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken();
            return $token['access_token'];
        }
        catch(\Exception $e){
            return $e->getMessage();
        }
    }


    public function sendFireBaseNotifications($merchant_id, $player_ids, $fields){

        try{

            $merchant= Merchant::find($merchant_id);
            $authorization  = "Bearer ".$this->getFireBaseAuthorization($merchant);

            $topic = $merchant->alias_name.time();
            $payload = [
                "to" => "/topics/".$topic,
                "registration_tokens" => $player_ids
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://iid.googleapis.com/iid/v1:batchAdd',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode($payload),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: '.$authorization,
                    'access_token_auth: true'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            return $this->initiateNotifications($merchant,$topic, $fields, $authorization);
        }
        catch(\Exception $e){
            return $e->getMessage();
        }

    }


    public function initiateNotifications($merchant, $topic, $feilds, $authorization){
        try{
            $data = [
                "message" => [
                    "topic" => $topic,
                    "notification"=>[
                        'title'=>$feilds['title'],
                        'body'=>$feilds['message'],
                    ],
                    'data' => [
                        'title'=>$feilds['title'], //for android
                        'data' => json_encode($feilds['body']), //for ios
                        'body' => json_encode($feilds['body']), //for android
                    ],
                    "webpush" => [
                        "headers" => [
                            "Urgency" => "high"
                        ],
                        // "notification" => [
                        //     "body" => $feilds['body'],
                        //     "requireInteraction" => "true",
                        //     "badge" => "/badge-icon.png"
                        // ]
                    ],
                    'android' => [
                        'notification' => [
                            'click_action' => 'TOP_STORY_ACTIVITY',
                        ]
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'category' => 'NEW_MESSAGE_CATEGORY'
                            ]
                        ]
                    ]
                ]
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://fcm.googleapis.com/v1/projects/previewappallinone/messages:send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: '.$authorization,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return $response;
        }
        catch(\Exception $e){
            return $e->getMessage();
        }

    }
}
