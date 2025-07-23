<?php

namespace App\Http\Controllers\PaymentMethods\Binance;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\PaymentOptionConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class BinanceController extends Controller
{
    use ApiResponseTrait, MerchantTrait;


    public function authToken($request)
    {
      try
      {
          
            $data = array(
                "Dni"  =>$request->api_public_key,
                "Pass" =>$request->api_secret_key
            );
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://adminp2p.sitca-ve.com/public/auth/security/users/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE4MDYyNDE4MjcsImlhdCI6MTc0NDAzMzgyN30.tCe4aaOapv43ni5K1KfR9qPLajYl0_dxwYQlF9KEwOg',
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);
            
            if($response->code == 200)
            {
                $res = array(
                    "Token"   => $response->token,
                    "Message" => $response->mensaje
                );
                return $res;
            }
        }
        catch (\Exception $e) 
        {
            throw $e;
        }
    

    }

    public function initiatePayment($request, $payment_option_config,$calling_from)
    {
        DB::beginTransaction();
        $token = $this->authToken($request,$payment_option_config,$calling_from);
        if(empty($token))
        {
            return $token;
        }
        if ($calling_from == "DRIVER") {
            $driver = $request->user('api-driver');
            // $currency = $driver->Country->isoCode;
            $accountno = $driver->phoneNumber;
            $id = $driver->id;
            $merchant_id = $driver->merchant_id;
            $description = "driver wallet topup";
            $country_code = $driver->Country->country_code;
        } else {
            $user = $request->user('api');
            // $currency = $user->Country->isoCode;
            $id = $user->id;
            $accountno = $user->UserNumber;
            $merchant_id = $user->merchant_id;
            $description = "payment from user";
            $country_code = $user->Country->country_code;
        }

        $reqData = array(
            "Banco_origen" =>$request->Bank_origin,
            "Telf_origen"  =>$request->Tel_origin,
            "Dni_origen"   =>$request->Dni_origin,
            "Monto"        =>$request->amount,
            "Motivo"       =>$request->Reason,
            "Otp"          =>str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)
           );
           
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://adminp2p.sitca-ve.com/public/protected/c2p/payments/v2',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>json_encode($reqData),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token['Token'],
            'Cookie: __cf_bm=IwnUYpfzdHTeMqyydtflqOznDB01e7udeJSzXXLY8.o-1722928754-1.0.1.1-ic.WwzzEIHugxfNWuFN8r1q7dxAGQe9GiRlcMtpc43m9o3Zxj82JE8tS36e5FJfiHWttrwMkpfHclDiEY79S9A',
            'Content-Type: application/json',
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response);
        if($response['code'] == 200)
        {
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_from,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => "",
                'amount' => $request->amount,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'request_status' => 1,
                "payment_mode" => "Third-party App",
                'status_message' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

    }

    public function callBackurl($request)
    {
    }

    public function bankList(Request $request)
    {   
        $payment_option_config = DB::table('payment_options_configurations')
  				->where('payment_option_id', '=', $request->payment_option_id)->first();
  				
        $token = $this->authToken($payment_option_config);
        
        if(empty($token))
        {
            return $token->mensaje;
        }

        $data = array(
            "Dni"  =>$payment_option_config->api_public_key,
            "Pass" =>$payment_option_config->api_secret_key
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://adminp2p.sitca-ve.com/public/protected/c2p/banks',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_POSTFIELDS =>json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token['Token'],
            'Content-Type: application/json',
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response);
        $curr_response = $this->conversionCurrency();
        if($response->code==200 && !empty($curr_response))
        {
            $respon = array(
                "result"   => 1,
                "message"  => "success",
                "data"     => $response->data,
                "version"=> $request->version,
                "currency" => "Bs",
                "currency_converted" => number_format($request->amount * $curr_response,2)
                );
            return $respon;
        }
        else{
            $respon = array(
                "result"   => 0,
                "message"  => "fail",
                "data"     => $response->data,
                "version"=> $request->version,
                "currency" => "",
                "currency_converted" => ""
                );
                return $respon;
        }


    }

    public function sendPaymentImmediate($request, $payment_option_config,$calling_from)
    {
        $token = $this->authToken($request,$payment_option_config,$calling_from);
        if(empty($token))
        {
            return $token;
        }
        if ($calling_from == "DRIVER") {
            $driver = $request->user('api-driver');
            // $currency = $driver->Country->isoCode;
            $accountno = $driver->phoneNumber;
            $id = $driver->id;
            $merchant_id = $driver->merchant_id;
            $description = "driver wallet topup";
            $country_code = $driver->Country->country_code;
        } else {
            $user = $request->user('api');
            // $currency = $user->Country->isoCode;
            $id = $user->id;
            $accountno = $user->UserNumber;
            $merchant_id = $user->merchant_id;
            $description = "payment from user";
            $country_code = $user->Country->country_code;
        }
        $curl = curl_init();
        
        $reqData = array(
            "Documento"  =>$requet->Document,
            "Nombre"     =>$request->Name,
            "Cuenta"     =>$request->AccountNO,
            "Banco"      =>$request->Bank,
            "Concepto"   =>$request->Reason,
            "Monto"      =>$request->amount,
            "Referencia" =>str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)
           );
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://adminp2p.sitca-ve.com/public/protected/creditoinmediato/send/new',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>json_encode($reqData),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token['Token'],
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response);
        if($response['code'] == 200)
        {
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_from,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => $response->referencia,
                'amount' => $request->amount,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'request_status' => 1,
                "payment_mode" => "Third-party App",
                'status_message' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $this->manualcallback($request, $payment_option_config,$calling_from,$response->referencia);
        }
        

    }

    public function manualcallback($request, $payment_option_config,$calling_from,$ref)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://adminp2p.sitca-ve.com/public/protected/creditoinme diato/status/new/'.$ref,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token['Token'],
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);
        if($response['code']==200 && $response['estatus']=="Operaci贸n Exitosa")
        {
            $transaction_id = $ref;
            $arr = array(
                "request_status" => 2,
                "status_message" => "SUCCESS"
            );
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update($arr);
        }
        

    }

    public function paymentHistory($request,$payment_option_config,$calling_from)
    {
       
        $token = $this->authToken($payment_option_config);
        
        if(empty($token))
        {
            return $token;
        }
      
        if ($calling_from == "DRIVER") {
            $driver = $request->user('api-driver');
            // $currency = $driver->Country->isoCode;
            $accountno = $driver->phoneNumber;
            $id = $driver->id;
            $merchant_id = $driver->merchant_id;
            $description = "driver wallet topup";
            $country_code = $driver->Country->country_code;
        } else {
            $user = $request->user('api');
            // $currency = $user->Country->isoCode;
            $id = $user->id;
            $accountno = $user->UserNumber;
            $merchant_id = $user->merchant_id;
            $description = "payment from user";
            $country_code = $user->Country->country_code;
        }
        
        $string_file = $this->getStringFile($merchant_id);

        $reqData = array(
            'Phone' => "58" . $request->Phone,
            'Bank'  => $request->Bank,
            'Date'  => \DateTime::createFromFormat('d/m/Y', $request->Date)->format('Y-m-d') 
        );
       
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://adminp2p.sitca-ve.com/public/protected/pm/find',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>json_encode($reqData),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: text/plain',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token['Token'],
        ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response);
        if(isset($response->code) && $response->code == 200 && !empty($response->lista))
        {
            foreach($response->lista as $item)
            {
                $lastFourDigits = substr($item->NroReferenciaCorto, -4);
              $dataArray = [];
               if($lastFourDigits == (string)$request['Reference']){
                $dataArray = [
                    "ID"=>$item->ID,
                    "created_at"=>$item->created_at,
                    "update_at"=>$item->update_at,
                    "Dni"=>$item->Dni,
                    "PhoneDest"=>$item->PhoneDest,
                    "PhoneOrig"=>$item->PhoneOrig,
                    "Amount"=>$item->Amount,
                    "BancoOrig"=>$item->BancoOrig,
                    "NroReferenciaCorto"=>$item->NroReferenciaCorto,
                    "NroReferencia"=>$item->NroReferencia,
                    "HoraMovimiento"=>$item->HoraMovimiento,
                    "FechaMovimiento"=>$item->FechaMovimiento,
                    "Descripcion"=>$item->Descripcion,
                    "Status"=>$item->Status,
                    "Refpk"=>$item->Refpk,
                    "Ref"=>$item->Ref
                ];
                
               }else{
                   continue;
               }
               
               if($dataArray){
                   $lastFourDigits = substr($dataArray['NroReferenciaCorto'], -4);
                //   dd($lastFourDigits);
                   $transaction_table = DB::table("transactions")
                                    ->where('payment_transaction_id', $dataArray['NroReferenciaCorto'])
                                    ->first();
                    $curr_response = $this->conversionCurrency();
                    $amount = number_format($request->amount * $curr_response,2);
                    
                if(empty($transaction_table)){
                    
                     if((string)$request['Reference'] !== $lastFourDigits)
                       {
                        // dd('ref1');
                         $request_status_text = trans("$string_file.reference_code_invalid");
                            $data = ['payment_status' => 3,'request_status' => $request_status_text,'transaction_status' => 3];
                            return $data;
                       }
                   
                   
                   if($lastFourDigits == (string)$request['Reference'] && $dataArray['Amount'] != round($amount,2) && $dataArray['PhoneOrig'] == "58" . $request->Phone && $dataArray['FechaMovimiento'] == $reqData['Date']){
                    //   dd('amount1');
                        // $request_status_text = "The amount does not match, please check the amount";
                        $request_status_text = trans("$string_file.amount_not_match");
                        $transaction_status = 3;
                        $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                        return $data;
                    }
                    
                    if($lastFourDigits == (string)$request['Reference'] && empty($transaction_table) && $dataArray['Amount'] == round($amount,2) && $dataArray['PhoneOrig'] == "58" . $request->Phone && $dataArray['FechaMovimiento'] == $reqData['Date'])
                    {
                        // dd('all1');
                        DB::table('transactions')->insert([
                            'user_id' => $request->calling_from == "USER" ? $id : NULL,
                            'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                            'status' => 1,
                            'merchant_id' => $merchant_id,
                            'payment_transaction_id' => $dataArray['NroReferenciaCorto'],
                            'payment_transaction'  => json_encode($response),
                            'amount' => $request->amount,
                            'payment_option_id' => $payment_option_config->payment_option_id,
                            'request_status' => 1,
                            "payment_mode" => "Third-party App",
                            'status_message' => 'SUCCESS',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        $request_status_text = "SUCCESS";
                        $transaction_status = 1;
                        $message = trans("$string_file.recharge_successfull",['amount' => $amount]);
                        // $data = ['payment_status' => 1, 'request_status' => "Your recharge was successfully proceeded - Amount reloaded Bs $amount", 'transaction_status' => $transaction_status];
                        $data = ['payment_status' => 1, 'request_status' => $message, 'transaction_status' => $transaction_status];
                        return $data;
                    }
                    else
                    {
                        DB::table('transactions')->insert([
                        'user_id' => $request->calling_from == "USER" ? $id : NULL,
                        'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                        'status' => $calling_from,
                        'merchant_id' => $merchant_id,
                        'payment_transaction_id' => "",
                        'amount' => $request->amount,
                        'payment_option_id' => $payment_option_config->payment_option_id,
                        'request_status' => 3,
                        'payment_transaction'  => json_encode($response),
                        "payment_mode" => "Third-party App",
                        'status_message' => 'FAILED',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                         ]);
    
                        // $request_status_text = "This Code has already been credited";
                        $request_status_text = trans("$string_file.code_taken");
                        $transaction_status = 3;
                        $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                        return $data;
                    }
                }else{
                    if(isset($transaction_table) && !empty($transaction_table) && (string)$request['Reference'] !== $lastFourDigits)
                   {
                        $data = ['payment_status' => 3,'request_status' => "This Refrence Code is invalid",'transaction_status' => 3];
                        return $data;
                   }
            //   $curr_response = $this->conversionCurrency();
            //   $amount = number_format($request->amount * $curr_response,2);
               
                if(isset($transaction_table) && !empty($transaction_table) && $transaction_table->request_status == 2 && (string)$request['Reference'] == $lastFourDigits)
                {
                    $data = ['payment_status' => 3, 'request_status' => "This code has alredy been credited", 'transaction_status' => $transaction_table->request_status];
                    return $data;
                }
                
                if($lastFourDigits == (string)$request['Reference'] && $dataArray['Amount'] != round($amount,2) && $dataArray['PhoneOrig'] == "58" . $request->Phone && $dataArray['FechaMovimiento'] == $reqData['Date']){
                    // $request_status_text = "The amount does not match, please check the amount";
                    $request_status_text = trans("$string_file.amount_not_match");
                    $transaction_status = 3;
                    $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                    return $data;
                }
                // $lastFourDigits = substr($dataArray['NroReferenciaCorto, -4);
                // $amount = number_format($request->amount * $curr_response,2);
                // dd($lastFourDigits == (string)$request['Reference'] && empty($transaction_table) && $dataArray['Amount == round($amount,2) && $dataArray['PhoneOrig == "58" . $request->Phone && $dataArray['FechaMovimiento == $reqData['Date']);
                if($lastFourDigits == (string)$request['Reference'] && empty($transaction_table) && $dataArray['Amount'] == round($amount,2) && $dataArray['PhoneOrig'] == "58" . $request->Phone && $dataArray['FechaMovimiento'] == $reqData['Date'])
                {

                    DB::table('transactions')->insert([
                        'user_id' => $request->calling_from == "USER" ? $id : NULL,
                        'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                        'status' => 1,
                        'merchant_id' => $merchant_id,
                        'payment_transaction_id' => $dataArray['NroReferenciaCorto'],
                        'payment_transaction'  => json_encode($response),
                        'amount' => $request->amount,
                        'payment_option_id' => $payment_option_config->payment_option_id,
                        'request_status' => 1,
                        "payment_mode" => "Third-party App",
                        'status_message' => 'SUCCESS',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $request_status_text = "SUCCESS";
                    $transaction_status = 1;
                    $message = trans("$string_file.recharge_successfull",['amount' => $amount]);
                    // $data = ['payment_status' => 1, 'request_status' => "Your recharge was successfully proceeded - Amount reloaded Bs $amount", 'transaction_status' => $transaction_status];
                    $data = ['payment_status' => 1, 'request_status' => $message, 'transaction_status' => $transaction_status];
                    return $data;
                }
                else
                {
        
                    DB::table('transactions')->insert([
                    'user_id' => $request->calling_from == "USER" ? $id : NULL,
                    'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                    'status' => $calling_from,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => "",
                    'amount' => $request->amount,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'request_status' => 3,
                    'payment_transaction'  => json_encode($response),
                    "payment_mode" => "Third-party App",
                    'status_message' => 'FAILED',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                     ]);

                    // $request_status_text = "This Code has already been credited";
                    $request_status_text = trans("$string_file.code_taken");
                    $transaction_status = 3;
                    $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                    return $data;
                }
                }
                  
               
               }
               
            }
        }
        elseif(isset($response->codigo) && $response->codigo == 511)
        {
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_from,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => "",
                'amount' => $request->amount,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'request_status' => 3,
                "payment_mode" => "Third-party App",
                'status_message' => 'FAILED',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $request_status_text = trans("$string_file.something_went_wrong_data_payload");
            $transaction_status = 3;
            $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            return $data;
            
        }
        elseif(isset($response->codigo) && $response->codigo == 551)
        {
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_from,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => "",
                'amount' => $request->amount,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'request_status' => 3,
                "payment_mode" => "Third-party App",
                'status_message' => 'FAILED',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $request_status_text = trans("$string_file.something_went_wrong_bank_data");
            $transaction_status = 3;
            $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            return $data;
            
        }
        elseif(isset($response->codigo) && $response->codigo == 552)
        {
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_from,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => "",
                'amount' => $request->amount,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'request_status' => 3,
                "payment_mode" => "Third-party App",
                'status_message' => 'FAILED',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $request_status_text = trans("$string_file.something_went_wrong_phone_number");
            $transaction_status = 3;
            $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            return $data;
            
        }
        elseif(isset($response->codigo) && $response->codigo == 553)
        {
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_from,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => "",
                'amount' => $request->amount,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'request_status' => 3,
                "payment_mode" => "Third-party App",
                'status_message' => 'FAILED',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $request_status_text = "Something went wrong with Date format";
            $transaction_status = 3;
            $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            return $data;
            
        }
        elseif(isset($response->codigo) && $response->codigo == 503)
        {
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_from,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => "",
                'amount' => $request->amount,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'request_status' => 3,
                "payment_mode" => "Third-party App",
                'status_message' => 'FAILED',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $request_status_text = "Unauthorized or expired token error";
            $transaction_status = 3;
            $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            return $data;
            
        }
        else
        {
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_from,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => "",
                'amount' => $request->amount,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'request_status' => 3,
                "payment_mode" => "Third-party App",
                'status_message' => 'FAILED',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // $request_status_text = "No payment records found. Please check back later";
            $request_status_text = trans("$string_file.no_payment_record_found");
            $transaction_status = 3;
            $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            return $data;
            
        }
        
        $request_status_text = trans("$string_file.reference_code_invalid");
        $data = ['payment_status' => 3,'request_status' => $request_status_text,'transaction_status' => 3];
        return $data;
        
        
        

    }
    
    public function conversionCurrency()
    {
       
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://pydolarve.org/api/v2/tipo-cambio?currency=usd',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        
        $response = curl_exec($curl);
        $response = json_decode($response,true);
        
        if(!empty($response['price']))
        {
            $value = $response['price'];
        }
        else
        {
            $value = "";
        }
        
       return  $value;
    }
    
    
   

}