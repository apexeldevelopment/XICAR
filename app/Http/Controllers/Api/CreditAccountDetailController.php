<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 20/1/23
 * Time: 1:19 PM
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentMethods\GlomoMoney\GlomoMoney;
use App\Models\CreditAccountDetail;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class CreditAccountDetailController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function storeDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'payment_option' => 'required|exists:payment_options,slug',
            'phone_number' => 'required_if:payment_option,GLOMO_MONEY',
            'name' => 'required_if:payment_option,GLOMO_MONEY,ONE_VISION_CASHOUT',
            'card_no' => 'required_if:payment_option,ONE_VISION_CASHOUT'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user = ($request->for == "USER") ? $request->user('api') : $request->user('api-driver');
            $string_file = $this->getStringFile(NULL,$user->Merchant);

            $payment_option = PaymentOption::where('slug', $request->payment_option)->first();
            // Get payment option config which is for credit or both.
            $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id',"=", $user->merchant_id],['payment_option_id','=',$payment_option->id]])->whereIn("payment_option_for",["2","3"])->first();
            if(empty($payment_option_config)){
                return $this->failedResponse(trans("$string_file.payment_configuration_not_found"));
            }
            $status = false;
            $message = "No Message";
            switch ($request->payment_option) {
                case "GLOMO_MONEY":
                    CreditAccountDetail::create([
                        "merchant_id" => $user->merchant_id,
                        "user_id" => ($request->for == "USER") ? $user->id : NULL,
                        "driver_id" => ($request->for == "DRIVER") ? $user->id : NULL,
                        "payment_option_id" => $payment_option->id,
                        "payment_options_configuration_id" => $payment_option_config->id,
                        "name" => $request->name,
                        "phone_number" => $request->phone_number
                    ]);
                    $status = true;
                    break;
                case "ONE_VISION_CASHOUT":
                    CreditAccountDetail::create([
                        "merchant_id" => $user->merchant_id,
                        "user_id" => ($request->for == "USER") ? $user->id : NULL,
                        "driver_id" => ($request->for == "DRIVER") ? $user->id : NULL,
                        "payment_option_id" => $payment_option->id,
                        "payment_options_configuration_id" => $payment_option_config->id,
                        "name" => $request->name,
                        "card_no" => $request->card_no
                    ]);
                    $status = true;
                    break;
            }
            DB::commit();
            if($status){
                return $this->successResponse(trans("$string_file.success"));
            }else{
                return $this->failedResponse($message);
            }
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function getDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'payment_option' => 'nullable|exists:payment_options,slug',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $user = ($request->for == "USER") ? $request->user('api') : $request->user('api-driver');
            $string_file = $this->getStringFile(NULL,$user->Merchant);

            $currency = $user->Country->isoCode;

            $query = CreditAccountDetail::with(["PaymentOption" => function ($query) use ($request) {
                if (isset($request->payment_option) && !empty($request->payment_option)) {
                    $query->where("slug", $request->payment_option);
                }
            }])->whereHas("PaymentOption", function ($query) use ($request) {
                if (isset($request->payment_option) && !empty($request->payment_option)) {
                    $query->where("slug", $request->payment_option);
                }
            })->whereHas("PaymentOptionsConfiguration", function ($query) {
                $query->whereIn("payment_option_for", [2,3]);
            });

            if($request->for == "USER"){
                $query->where("user_id",$user->id);
            }else{
                $query->where("driver_id",$user->id);
            }
            $credit_accounts = $query->get();

            $accounts = [];
            foreach($credit_accounts as $account){
                switch ($account->PaymentOption->slug){
                    case "GLOMO_MONEY":
                        array_push($accounts,array(
                            "id" => $account->id,
                            "name" => $account->name,
                            "phone_number" => $account->phone_number,
                        ));
                        break;
                    case "ONE_VISION_CASHOUT":
                        array_push($accounts,array(
                            "id" => $account->id,
                            "name" => $account->name,
                            "card_no" => $account->card_no,
                        ));
                        break;
                }
            }

            $return_data = [
                'account_data' => $accounts,
                'currency' => $currency,
                'payment_option' => $request->payment_option,
            ];
            return $this->successResponse(trans("$string_file.success"), $return_data);
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }
}
