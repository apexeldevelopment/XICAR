<?php

namespace App\Http\Resources;

use App\Models\UserVehicle;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserVehicleDocument;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CountryArea;
use App\Models\Country;
use App\Models\Configuration;
use App\Traits\MerchantTrait;

class CarpoolingResourceBackup extends JsonResource
{
    use MerchantTrait;

    public function toArray($data)
    {
        $is_user_doc = false;
        $is_user_offer_ride = false;
        $is_veh_upload = false;
        $is_veh_doc_upload = false;
        $is_user_minimum_balance = false;
        $is_user_vehicle_document_expire = false;
        $is_user_vehicle_document_expired = false;
        $is_user_vehicle_document_expire_text = "";
        $is_user_vehicle_document_expired_text = "";
        $is_user_personal_document_expired = false;
        $is_user_personal_document_expire = false;
        $is_user_personal_document_expired_text = "";
        $is_user_personal_document_expire_text = "";
        $is_user_minimum_balance_text = "";

        $string_file = $this->getStringFile($this->merchant_id);

        $vehicle_list = UserVehicle::whereHas('Users', function ($query) {
            $query->where([['user_id', '=', $this->id], ['user_default_vehicle', '=', 1]]);
        })->with(['Users' => function ($query) {
            $query->where([['user_id', '=', $this->id]]);
        }])->where([['vehicle_verification_status', '=', 2], ['vehicle_delete', '=', NULL]])->get();
        //p( $vehicle_list);
        if (count($vehicle_list) > 0) {
            $is_user_offer_ride = true;
        }

        $upload_vehicle = UserVehicle::whereHas('Users', function ($query) {
            $query->where([['user_id', '=', $this->id], ['user_default_vehicle', '=', 1]]);
        })->where([['vehicle_delete', '=', NULL]])->get(); // ,['active_default_vehicle','=',1]
        // p($upload_vehicle);
        if (count($upload_vehicle) > 0) {
            $is_veh_upload = true;
        }
        $upload_vehicle_document = UserVehicleDocument::whereHas('UserVehicle', function ($query) {
            $query->where([['user_id', '=', $this->id], ['vehicle_delete', '=', NULL]]); // ,['active_default_vehicle','=',1]
        })->get();

        $vehicle_document_count = 0;
        if(!empty($this->country_area_id)){
            $query = CountryArea::where('id', $this->country_area_id);
            $query->with(['VehicleDocuments' => function ($q) {
                $q->addSelect('documents.id', 'expire_date as expire_status', 'documentNeed as document_mandatory', 'document_number_required');
                $q->where('documentStatus', 1);
            }]);
            $area = $query->first();
            $vehicle_document_count = $area->VehicleDocuments->where("documentNeed",1)->count();
        }


        // if ($vehicle_document_count == 0 || count($upload_vehicle_document) > 0) {
        if ($vehicle_document_count != 0 && count($upload_vehicle_document) >= $vehicle_document_count) {
            $is_veh_doc_upload = true;
        }
        $user_doc = UserDocument::where([['user_id', '=', $this->id]])->get();
        $country = Country::find($this->country_id);
        $user_doc_mandatory_count = $country->documents->where("documentNeed",1)->count();
        if ($user_doc_mandatory_count == 0 || count($user_doc) > 0) {
            $is_user_doc = true;
        }

        $user_balance = CountryArea::where([['id', '=', $this->country_area_id]])->first();
        if($this->Merchant->demo != 1){
            if (empty($user_balance)) {
                $is_user_minimum_balance = false;
            } elseif ($this->wallet_balance >= $user_balance->minimum_wallet_amount) {
                $is_user_minimum_balance = true;
            } else {
                $is_user_minimum_balance = false;
                $is_user_minimum_balance_text = trans("$string_file.maintain_wallet_balance") . " " . $this->Country->isoCode . " " . $user_balance->minimum_wallet_amount;
            }
        }else{
            $is_user_minimum_balance = true;
        }
        $currentDate = date('Y-m-d');

        $reminder_days = Configuration::where('merchant_id', '=', $this->merchant_id)->select('reminder_doc_expire')->first();
        $reminder_last_date = date('Y-m-d', strtotime('+' . $reminder_days->reminder_doc_expire . ' days'));
        $dateBetween = array($currentDate, $reminder_last_date);
        $raw_query = User::select('id', 'first_name', 'last_name', 'email', 'UserPhone', 'country_id', 'country_area_id')->whereHas('UserVehicles', function ($q) use ($dateBetween) {
            $q->whereHas('UserVehicleDocument', function ($o) use ($dateBetween) {
                $o->whereHas('Document', function ($s) {
                    $s->where('expire_date', 1);
                });
                $o->where('document_verification_status', 2);
                $o->whereBetween('expire_date', $dateBetween);

            });
            $q->where('vehicle_verification_status', 2);
            $q->where('active_default_vehicle', 1);
            $q->where('vehicle_delete', NULL);

        })->where('id', $this->id)->get();

        if ($raw_query->isNotEmpty()) {

            $user_vehicle = UserVehicle::where([['user_id', $this->id], ['active_default_vehicle', 1]])->first();

            if(!empty($user_vehicle)){
                $user_vehicle_document = UserVehicleDocument::where('user_vehicle_id', $user_vehicle->id)->get();
                foreach ($user_vehicle_document as $vehicle_document) {
                    if ($vehicle_document->expire_date <= $currentDate) {
                        $is_user_vehicle_document_expired = true;
                        $is_user_vehicle_document_expired_text = trans("$string_file.vehicle_document_expired_warning");
                    } elseif(date("Y-m-d", strtotime($vehicle_document->expire_date. ' - 10 day')) <= $currentDate) {
                        $is_user_vehicle_document_expire = true;
                        $is_user_vehicle_document_expire_text = trans("$string_file.vehicle_document_expire_warning");
                    }
                }
            }

            // if ($user_vehicle->expire_date <= $currentDate) {
            //     $is_user_vehicle_document_expired = true;
            //     $is_user_vehicle_document_expired_text = trans("$string_file.vehicle_document_expired_warning");
            // } elseif(date("Y-m-d", strtotime($user_vehicle->expire_date. ' - 10 day')) <= $currentDate) {
            //     $is_user_vehicle_document_expire = true;
            //     $is_user_vehicle_document_expire_text = trans("$string_file.vehicle_document_expire_warning");
            // }


        }
        $query = User::select('id', 'first_name', 'last_name', 'email', 'UserPhone', 'country_id')->whereHas('UserDocument', function ($q) use ($dateBetween) {
            $q->whereHas('Document', function ($o) {
                $o->where('expire_date', 1);
            });
            $q->where('document_verification_status', 2);
            $q->whereBetween('expire_date', $dateBetween);
        })->where('id', $this->id)->get();

        if ($query->isNotEmpty()) {
            $user_document = UserDocument::where('user_id', $this->id)->get();
            foreach ($user_document as $v) {
                if ($v->expire_date <= $currentDate) {
                    $is_user_personal_document_expired = true;
                    $is_user_personal_document_expired_text = trans("$string_file.personal_document_expired_error");
                } elseif(date("Y-m-d", strtotime($v->expire_date. ' - 10 day')) <= $currentDate) {
                    $is_user_personal_document_expire = true;
                    $is_user_personal_document_expire_text = trans("$string_file.personal_document_expire_error");
                }
            }
        }
        return [
            'is_user_doc_upload' => $is_user_doc,
            'is_veh_upload' => $is_veh_upload,
            'is_veh_doc_upload' => $is_veh_doc_upload,
            'is_user_offer_ride' => $is_user_offer_ride,
            'is_user_minimum_balance' => $is_user_minimum_balance,
            'offer_ride_text' => trans("$string_file.pending_vehicle_document"),
            'upload_document' => trans("$string_file.driver_upload_document"),
            'upload_vehicle' => trans("$string_file.upload_vehicle_document"),
            'user_doc_upload' => trans("$string_file.upload_user_document"),
            'is_user_minimum_balance_text' => $is_user_minimum_balance_text,
            'is_user_vehicle_document_expired' => $is_user_vehicle_document_expired,
            'is_user_vehicle_document_expired_text' => $is_user_vehicle_document_expired_text,
            'is_user_personal_document_expired' => $is_user_personal_document_expired,
            'is_user_personal_document_expired_text' => $is_user_personal_document_expired_text,
            'is_user_vehicle_document_expire' => $is_user_vehicle_document_expire,
            'is_user_vehicle_document_expire_text' => $is_user_vehicle_document_expire_text,
            'is_user_personal_document_expire' => $is_user_personal_document_expire,
            'is_user_personal_document_expire_text' => $is_user_personal_document_expire_text,
        ];
    }
}
