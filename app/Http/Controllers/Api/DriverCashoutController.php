<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Controllers\Helper\Merchant;
use App\Models\Configuration;
use App\Models\DriverCashout;
use App\Models\DriverConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Onesignal;

class DriverCashoutController extends Controller
{
    use ApiResponseTrait,MerchantTrait;
    public function index(Request $request){
        try{
            $driver = $request->user('api-driver');
            $merchant_helper = new Merchant();
            $string_file = $this->getStringFile(NULL,$driver->Merchant);
            $configuration = Configuration::where('merchant_id',$driver->merchant_id)->first();
            $driver_config = DriverConfiguration::where('merchant_id',$driver->merchant_id)->first();
            if(isset($configuration->driver_cashout_module) && $configuration->driver_cashout_module != 1 && isset($driver_config->driver_cashout_min_amount) && $driver_config->driver_cashout_min_amount != 1){
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }else{
                $data = [];
                $cashout_requests = DriverCashout::where([['merchant_id','=',$driver->merchant_id],['driver_id',$driver->id]])->orderBy('created_at','DESC')->get();
                if($cashout_requests->count() > 0){
                    foreach ($cashout_requests as $cashout_request){
                        // p($cashout_request->cashout_status);
                        $cashout_status = '';
                        switch ($cashout_request->cashout_status){
                            case "0":
                                // p($cashout_status);
                                $cashout_status = trans("$string_file.pending");
                                // p($cashout_status);
                                break;
                            case "1":
                                $cashout_status = trans("$string_file.success");
                                break;
                            case "2":
                                $cashout_status = trans("$string_file.rejected");
                                break;
                            default:
                                $cashout_status ="";

                        }
                        // p($cashout_status);
                        array_push($data,array(
                            'id' => $cashout_request->id,
                            // 'amount' => $driver->CountryArea->Country->isoCode .' '.$cashout_request->amount,
                            'amount' => $driver->CountryArea->Country->isoCode . ' ' . $merchant_helper->PriceFormat($cashout_request->amount, $driver->merchant_id),
                            'cashout_status' => $cashout_status,
                            'action_by' => $cashout_request->action_by,
                            'transaction_id' => $cashout_request->transaction_id,
                            'comment' => $cashout_request->comment,
                            'created_at' =>  strtotime($cashout_request->created_at),
                            'updated_at' =>  strtotime($cashout_request->updated_at),
                        ));
                    }
                }
                return $this->successResponse(trans("$string_file.cash_out_request_driver"),$data);
            }
        }catch (\Exception $e){
            return $this->failedResponse($e->getMessage());
        }
    }

    public function request(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL,$driver->Merchant);
            $configuration = Configuration::where('merchant_id',$driver->merchant_id)->first();
            $driver_config = DriverConfiguration::where('merchant_id',$driver->merchant_id)->first();
            $driverCashout = DriverCashout::where(['driver_id'=>$driver->id,'cashout_status'=>0])->get();
            $driver_cashout_amount_requested = 0;
            foreach($driverCashout as $cashout){
                $driver_cashout_amount_requested += $cashout->amount;
            }

            $driver_remaining_amount = $driver->wallet_money - $driver_cashout_amount_requested;
            if($request->amount > $driver_remaining_amount){
                return $this->failedResponse(trans("$string_file.some_pending_amount_which_already_exceeded_from_wallet_amount"));
            }

            if(isset($configuration->driver_cashout_module) && $configuration->driver_cashout_module != 1 && isset($driver_config->driver_cashout_min_amount) && $driver_config->driver_cashout_min_amount != 1){
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }else{
                if($request->amount < $driver_config->driver_cashout_min_amount){
                    $amount = $driver->CountryArea->Country->isoCode .' '.$driver_config->driver_cashout_min_amount;
                    return $this->failedResponse(trans("$string_file.cash_out_min_amount_requested").' '.$amount);
                }
                if($driver->wallet_money < $request->amount){
                    return $this->failedResponse(trans("$string_file.low_wallet_warning"));
                }


                if(!empty($request->credit_account_detail_id) || !empty($request->glomo_money_transaction_id)){
                    $paramArray = array(
                        'driver_id' => $driver->id,
                        'booking_id' => null,
                        'amount' => $request->amount,
                        'narration' => 10,
                    );
                    WalletTransaction::WalletDeduct($paramArray);
                    DriverCashout::create([
                        'driver_id' => $driver->id,
                        'merchant_id' => $driver->merchant_id,
                        'amount' => $request->amount,
                        'credit_account_detail_id' => isset($request->credit_account_detail_id) && !empty($request->credit_account_detail_id) ? $request->credit_account_detail_id : NULL,
                        'transfer_transaction_id' => isset($request->glomo_money_transaction_id) && !empty($request->glomo_money_transaction_id) ? $request->glomo_money_transaction_id : NULL,
                    ]);
                }
                else{
                    $cashout_id= DriverCashout::insertGetId([
                        'driver_id' => $driver->id,
                        'merchant_id' => $driver->merchant_id,
                        'amount' => $request->amount,
                    ]);

                    $notification_data['notification_type'] = "CASHOUT_REQUEST_RAISED";
                    $notification_data['segment_data'] = ['cashout_id'=>$cashout_id];
                    $notification_data['segment_sub_group'] = null;
                    $notification_data['segment_group_id'] = $driver->segment_group_id;
                    $arr_param = ['driver_id' => $driver->id, 'data' => $notification_data, 'message' => trans("$string_file.cashout_request_raised"), 'merchant_id' => $driver->merchant_id, 'title' => trans("$string_file.cashout_request"), 'large_icon' => ""];
                    Onesignal::DriverPushMessage($arr_param);
                }

                DB::commit();
                return $this->successResponse(trans("$string_file.cash_out_request_driver_successfully"));
            }
        }catch (\Exception $e){
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
    }
}
