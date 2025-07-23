<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\PaymentSplit\StripeConnect;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\MerchantStripeConnect;
use App\Traits\ApiResponseTrait;
use App\Traits\DriverTrait;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    use ImageTrait, MerchantTrait, ApiResponseTrait, DriverTrait;

    public function RegisterToStripeConnect(Request $request)
    {
        $validator_array = array(
            'driver_id' => 'required|exists:drivers,id',
            'ip_address' => 'required',
            'dob' => 'required',
            //            'identity_document' => 'required|file',
            'account_number' => 'required',
            'postal_code' => 'required',
            'address_line_1' => 'required',
            'city' => 'required',
        );
        $driver = Driver::find($request->driver_id);
        $string_file = $this->getStringFile(null, $driver->Merchant);
        $short_code = $driver->CountryArea->Country->short_code;
        switch ($short_code) {
            case 'US':
                $validator_array = array_merge($validator_array, array(
                    'routing_number' => 'required',
                    'state' => 'required|alpha|size:2',
                    'address_line_2' => 'required',
                    'ssn' => 'required|unique:drivers,ssn',
                ));
                break;
            case 'AU': // If contry is Australia
                $validator_array = array_merge($validator_array, array(
                    // 'routing_number' => 'required',
                    'account_holder_name' => 'required',
                    'bsb_number' => 'required',
                    'abn' => 'required',
                    'state' => 'required|alpha',
                    'ssn' => 'required|unique:drivers,ssn',
                ));
                break;
            case 'LU': // If contry is Luxembourg
                $validator_array = array_merge($validator_array, array(
                    'bsb_number' => 'required'
                ));
                break;
            case 'GB': // if country is the United Kingdom
                $validator_array = array_merge($validator_array, array(
                    'account_holder_name' => 'required',
                    'sort_code' => 'required|digits:6', // UK Sort Code (6 digits)
                    'ssn' => 'required|unique:drivers,ssn', // Optional if needed
                ));
                break;
        }
        $valid = validator($request->all(), $validator_array);
        if ($valid->fails()) {
            return $this->failedResponse($valid->errors()->first());
        }

        if ($driver->sc_account_id) {
            return $this->failedResponse(trans("$string_file.driver_already_registered_for_stripe_connect"));
        }

        DB::beginTransaction();
        $driver_additional_data = array(
            "pincode" => $request->postal_code,
            "postal_code" => $request->postal_code,
            "address_line_1" => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            "province" => $request->state,
            'city_name' => $request->city,
        );
        $driver_additional_data = json_encode($driver_additional_data, true);
        try {
            $driver->device_ip = $request->ip_address;
            $driver->dob = formatted_date($request->dob);

            //            $photo = $this->uploadImage('identity_document' , 'driver' , $driver->merchant_id);
            //            $driver->sc_identity_photo = $photo;
            //            $driver->sc_identity_photo_status = 'pending';
            //            $driver->ssn = $request->ssn; // For Australia it will be unique id number

            $driver->account_number = $request->account_number;

            if (isset($request->routing_number) && !empty($request->routing_number)) {
                $driver->routing_number = $request->routing_number;
            } elseif (isset($request->sort_code)) {
                $driver->routing_number = $request->sort_code;
            }

            $driver->driver_additional_data = $driver_additional_data;
            $driver->account_holder_name = isset($request->account_holder_name) ? $request->account_holder_name : null;
            $driver->bsb_number = isset($request->bsb_number) ? $request->bsb_number : null;
            $driver->abn_number = isset($request->abn) ? $request->abn : $request->ssn;
            $driver->sort_code = isset($request->sort_code) ? $request->sort_code : null;
            $driver->save();
            // upload image to stripe connect
            //            $stripe_file = StripeConnect::upload_file($request->identity_document, $driver->merchant_id, 'identity_document');
            //            $verification_details = [
            //                'photo_id_front' => $stripe_file->id,
            //                'additional_document_front' => $stripe_file->id
            //            ];

            $merchant_stripe_config = MerchantStripeConnect::where('merchant_id', $driver->merchant_id)->first();
            $stripe_docs_list = self::getStripeRelatedDocuments($merchant_stripe_config, $driver);

            $driver->sc_identity_photo = $stripe_docs_list['personal_document']['image_name'];
            $driver->sc_identity_photo_status = 'pending';
            $driver->ssn = $stripe_docs_list['personal_document']['doc_number'];
            $driver->save();

            // if ($request->has('debit_card')) {
            //     if ($driver->DriverDetail != null) {
            //         $driver->DriverDetail->card_token = $request->debit_card;
            //         $driver->DriverDetail->save();
            //     } else {
            //         $driver_detail = new \App\Models\DriverDetail();
            //         $driver_detail->driver_id = $driver->id;
            //         $driver_detail->card_token = $request->debit_card;
            //         $driver_detail->save();
            //     }
            // }


            $personal_id = StripeConnect::upload_file($stripe_docs_list['personal_document']['image'], $driver->merchant_id, 'customer_signature');
            $photo_front_id = StripeConnect::upload_file($stripe_docs_list['photo_front_document']['image'], $driver->merchant_id, 'identity_document');
            $photo_back_id = StripeConnect::upload_file($stripe_docs_list['photo_back_document']['image'], $driver->merchant_id, 'identity_document');
            $additional_id = StripeConnect::upload_file($stripe_docs_list['additional_document']['image'], $driver->merchant_id, 'additional_verification');
            $verification_details = [
                'personal_id' => $personal_id->id,
                'photo_front_id' => $photo_front_id->id,
                'photo_back_id' => $photo_back_id->id,
                'additional_id' => $additional_id->id
            ];

            $driver = StripeConnect::create_driver_account($driver, $verification_details);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse('Success', $driver);
    }

    public function CheckStripeConnect(Request $request)
    {
        $valid = validator($request->all(), [
            'driver_id' => 'required|exists:drivers,id',
        ]);
        if ($valid->fails()) {
            return $this->failedResponse($valid->errors()->first());
        }
        try {
            $driver = Driver::findOrFail($request->driver_id);
            $string_file = $this->getStringFile($driver->merchant_id);
            $config = Configuration::where('merchant_id', $driver->merchant_id)->first();
            if (isset($config->stripe_connect_enable) && $config->stripe_connect_enable != 1) {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }
            $sc_account_status = $driver->sc_account_status == 'active' ? '1' : ($driver->sc_account_status == null ? '3' : '2');
            $sc_account_text = $driver->sc_account_status == 'active' ? '' : ($driver->sc_account_status == null ? '3' : __('api.stripe_account_pending'));
            if ($driver->sc_account_id) {
                return response()->json(['result' => $sc_account_status, 'message' => $sc_account_text]);
            } else {
                return response()->json(['result' => "4", 'message' => trans("$string_file.driver_not_registered_for_stripe_connect")]);
            }
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getStripeConnectRequireDetails(Request $request)
    {
        $validator_array = array(
            'driver_id' => 'required|exists:drivers,id',
        );
        $valid = validator($request->all(), $validator_array);
        if ($valid->fails()) {
            return error_response($valid->errors()->first());
        }
        $driver = Driver::find($request->driver_id);
        $string_file = $this->getStringFile(null, $driver->Merchant);
        $short_code = $driver->CountryArea->Country->short_code;
        $required_array = array(
            array("key" => "ip_address", "display_text" => "IP Address", "display" => false, "type" => "text"),
            array("key" => "dob", "display_text" => "DOB", "display" => true, "type" => "select_dob"),
            //            array("key" => "identity_document", "display_text" => "Identity Document", "display" => true, "type" => "file"),
            array("key" => "postal_code", "display_text" => "Postal Code", "display" => true, "type" => "text"),
            array("key" => "address_line_1", "display_text" => "Address Line 1", "display" => true, "type" => "text"),
            array("key" => "city", "display_text" => "City", "display" => true, "type" => "text"),
        );
        switch ($short_code) {
            case 'US':
                array_push($required_array, array("key" => "account_number", "display_text" => "Account Number", "display" => true, "type" => "text"));
                array_push($required_array, array('key' => 'routing_number', "display_text" => "Routing Number", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'state', "display_text" => "State", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'address_line_2', "display_text" => "Address Line 2", 'display' => true, 'type' => 'text'));
                array_push($required_array, array("key" => "ssn", "display_text" => "SSN", "display" => true, "type" => "text"));
                break;
            case 'AU': // If contry is Australia
                array_push($required_array, array("key" => "account_number", "display_text" => "Account Number", "display" => true, "type" => "text"));
                array_push($required_array, array('key' => 'account_holder_name', "display_text" => "Account Holder Name", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'bsb_number', "display_text" => "BSB Number", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'abn', "display_text" => "ABN", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'state', "display_text" => "State", 'display' => true, 'type' => 'text'));
                array_push($required_array, array("key" => "ssn", "display_text" => "SSN", "display" => true, "type" => "text"));
                break;
            case 'LU':
                array_push($required_array, array("key" => "account_number", "display_text" => "Account Number (IBAN)", "display" => true, "type" => "text"));
                array_push($required_array, array("key" => "bsb_number", "display_text" => "BIC/Swift Code", "display" => true, "type" => "text"));
                break;
            case 'GB':
                array_push($required_array, array("key" => "account_number", "display_text" => "Account Number", "display" => true, "type" => "text"));
                array_push($required_array, array('key' => 'account_holder_name', "display_text" => "Account Holder Name", 'display' => true, 'type' => 'text'));
                array_push($required_array, array("key" => "ssn", "display_text" => "SSN", "display" => true, "type" => "text"));
                array_push($required_array, array("key" => "sort_code", "display_text" => "Sort Number", "display" => true, "type" => "number", "max" => 6));
                break;
            default:
                return $this->successResponse(trans("$string_file.stripe_not_support_in_your_country"));
        }
        return $this->successResponse("Success", $required_array);
    }
}
