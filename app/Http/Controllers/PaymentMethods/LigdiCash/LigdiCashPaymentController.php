<?php

namespace App\Http\Controllers\PaymentMethods\LigdiCash;

use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use DB;
use Exception;
use Illuminate\Http\Request;

class LigdiCashPaymentController extends Controller
{
    //
    use MerchantTrait;

    public function initiatePayment($request, $payment_option_config, $calling_from): array
    {
        DB::BeginTransaction();
        try {
            if ($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
            } else {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
            }
            $string_file = $this->getStringFile($user->merchant_id);
            $ord_ref = 'ORD' . "-" . uniqid() . time();
            $tax_ref = 'TRANS' . "-" . uniqid() . time();
            $amount = $request->amount;

            $data = [
                "commande" => [
                    "invoice" => [
                        "items" => [
                            [
                                "name" => "Watilow" . " " . trans("$string_file.wallet") . " " . trans("$string_file.transaction"),
                                "description" => "__watilow_wallet_payments__",
                                "quantity" => 1,
                                "unit_price" => $amount,
                                "total_price" => $amount
                            ]
                        ],
                        "total_amount" => $amount,
                        "devise" => "XOF",
                        "description" => "Watilow " . trans("$string_file.wallet") . " " . trans("$string_file.transaction"),
                        "customer" => "",
                        "customer_firstname" => $user->first_name ?? "",
                        "customer_lastname" => $user->last_name ?? "",
                        "customer_email" => $user->email ?? "",
                        "external_id" => "",
                        "otp" => ""
                    ],
                    "store" => [
                        "name" => "Watilow",
                        "website_url" => "https://msprojects.apporioproducts.com/multi-service-v3/public/"
                    ],
                    "actions" => [
                        "cancel_url" => route("ligdicash-cancel"),
                        "return_url" => route("ligdicash-success"),
                        "callback_url" => route("ligdicash-callback")
                    ],
                    "custom_data" => [
                        "order_id" => $ord_ref,
                        "transaction_id" => $tax_ref
                    ]
                ]
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://app.ligdicash.com/pay/v01/redirect/checkout-invoice/create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Apikey: ' . $payment_option_config->api_secret_key,
                    'Authorization: Bearer ' . $payment_option_config->auth_token,
                    'Accept: application/json',
                    'Content-Type: application/json',
                ),
            ));

            $resp = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($resp);
            \Log::channel('ligidcash')->emergency(["initiate_response"=>$response]);

            if (isset($response->response_code) && $response->response_code == "00") {
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => $tax_ref,
                    'reference_id' => $ord_ref,
                    'amount' => $amount,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'request_status' => 1,
                    'status_message' => 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw new Exception('Payment Url not generated');
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        DB::commit();
        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $response->response_text ?? '',
            'transaction_id' => $response->custom_data->transaction_id ?? '',
            'reference_id' => $response->custom_data->order_id ?? '',
            'success_url' => route('ligdicash-success'),
            'fail_url' => route('ligdicash-cancel'),
            'token' => $response->token ?? '',
        ];
    }


    public function handleCallback(Request $request): void
    {
        \Log::channel('ligidcash')->emergency(["callback_request"=>$request->all()]);
        $event = $request->all();

        // $refrences_arr =  array_map('trim', explode(";", $event->transaction_id));
        // $transaction_id = $refrences_arr[1];
        // $status = $event->status;

        $refrences_arr =  array_map('trim', explode(";", $event['transaction_id']));
        $transaction_id = $refrences_arr[1];
        $status = $event['status'];


        if ($status === "completed") {
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                'request_status' => 2,
                'status_message' => 'SUCCESS',
                'payment_transaction' => json_encode($event),
            ]);
        } else {
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                'request_status' => 3,
                'status_message' => 'FAIL',
                'payment_transaction' => json_encode($event),
            ]);
        }
    }

    public function successCallBack(Request $request){
        \Log::channel('ligidcash')->emergency(["successCallBack"=>$request->all()]);
        echo "Success";
    }

    public function CancelCallBack(Request $request){
        \Log::channel('ligidcash')->emergency(["CancelCallBack"=>$request->all()]);
        echo "Failed";
    }
}
