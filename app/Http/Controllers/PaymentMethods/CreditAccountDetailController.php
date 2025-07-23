<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 30/1/23
 * Time: 3:50 PM
 */

namespace App\Http\Controllers\PaymentMethods;


use App\Http\Controllers\PaymentMethods\OneVision\OneVisionController;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\CreditAccountDetail;
use App\Http\Controllers\PaymentMethods\GlomoMoney\GlomoMoney;

class CreditAccountDetailController
{
    use ApiResponseTrait, MerchantTrait;

    public function makeTransfer(CreditAccountDetail $creditAccountDetail, $amount) : array
    {
        try{
            $string_file = $this->getStringFile($creditAccountDetail->merchant_id);
            $slug = $creditAccountDetail->PaymentOption->slug;
            $flag = false;
            $transaction_id = "";
            switch ($slug) {
                case "GLOMO_MONEY":
                    $params = array(
                        "merchant_id" => $creditAccountDetail->merchant_id,
                        "amount" => $amount,
                        "phone_number" => $creditAccountDetail->phone_number,
                        "string_file" => $string_file,
                    );
                    $glomo = new GlomoMoney();
                    $flag = true;
                    $transaction_id = $glomo->makeTransfer($params);
                    break;
                case "ONE_VISION_CASHOUT":
                    $params = array(
                        "id" => !empty($creditAccountDetail->driver_id) ? $creditAccountDetail->driver_id : $creditAccountDetail->user_id,
                        "merchant_id" => $creditAccountDetail->merchant_id,
                        "amount" => $amount,
                        "cardholder" => $creditAccountDetail->name,
                        "card_no" => $creditAccountDetail->card_no,
                        "string_file" => $string_file,
                        "business_name" => $creditAccountDetail->Merchant->BusinessName,
                        "email" => !empty($creditAccountDetail->driver_id) ? $creditAccountDetail->Driver->email : $creditAccountDetail->User->email,
                        "phone" => !empty($creditAccountDetail->driver_id) ? $creditAccountDetail->Driver->phoneNumber : $creditAccountDetail->User->UserPhone
                    );
                    $oneVision = new OneVisionController();
                    $flag = true;
                    $transaction_id = $oneVision->makePayoutTransfer($params);
                    break;
                default:
                    throw new \Exception("Invalid Payment Option");
            }
            return array("flag" => $flag, "transaction_id" => $transaction_id);
        }catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
}
