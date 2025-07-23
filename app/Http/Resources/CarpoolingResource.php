<?php

namespace App\Http\Resources;

use App\Models\CarpoolingConfiguration;
use App\Models\UserVehicle;
use App\Models\User;
use App\Models\UserVehicleDocument;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CountryArea;
use App\Traits\MerchantTrait;

class CarpoolingResource extends JsonResource
{
    use MerchantTrait;

    public function toArray($data)
    {
        $is_vehicle_uploaded = false;
        $is_vehicle_document_upload = false;
        $can_user_offer_ride = false;

        $is_user_vehicle_document_going_to_expire = false;
        $is_user_vehicle_document_expired = false;
        $is_user_vehicle_document_going_to_expire_text = "";
        $is_user_vehicle_document_expired_text = "";
        $is_user_minimum_balance_text = "";

        $string_file = $this->getStringFile($this->merchant_id);

        $upload_vehicles = UserVehicle::whereHas('Users', function ($query) {
            $query->where([['id', '=', $this->id]]);
             // $query->where([['id', '=', $this->id], ['user_default_vehicle', '=', 1]]);
        })->where([['vehicle_verification_status', '=', 2], ['vehicle_delete', '=', NULL]])->get();
        
        $active_user_vehicle = [];
        if (count($upload_vehicles) > 0) {
            $is_vehicle_uploaded = true;
            $can_user_offer_ride = true;
            $active_user_vehicle = $upload_vehicles->first();
        }

        if(!empty($active_user_vehicle)){
            $upload_vehicle_document = UserVehicleDocument::whereHas('UserVehicle', function ($query) use($active_user_vehicle) {
                $query->where([['id', '=', $active_user_vehicle->id], ['user_id', '=', $this->id], ['vehicle_delete', '=', NULL]]);
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
            if ($vehicle_document_count == 0 || ($vehicle_document_count != 0 && count($upload_vehicle_document) >= $vehicle_document_count)) {
                $is_vehicle_document_upload = true;
            }

            $currentDate = date('Y-m-d');
            $carpooling_configuration = CarpoolingConfiguration::where('merchant_id', '=', $this->merchant_id)->select('user_document_reminder_time')->first();

            $reminder_last_date = !empty($carpooling_configuration) ? date('Y-m-d', strtotime('+' . $carpooling_configuration->user_document_reminder_time . ' days')) : date('Y-m-d', strtotime('+10 days'));
            $dateBetween = array($currentDate, $reminder_last_date);
            $raw_query = User::select('id', 'first_name', 'last_name', 'email', 'UserPhone', 'country_id', 'country_area_id')->whereHas('UserVehicles', function ($q) use ($dateBetween, $active_user_vehicle) {
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
                $q->where('id', $active_user_vehicle->id);
            })->where('id', $this->id)->get();

            if ($raw_query->isNotEmpty()) {
                $user_vehicle_document = UserVehicleDocument::where('user_vehicle_id', $active_user_vehicle->id)->get();
                foreach ($user_vehicle_document as $vehicle_document) {
                    if ($vehicle_document->expire_date <= $currentDate) {
                        $is_user_vehicle_document_expired = true;
                        $is_user_vehicle_document_expired_text = trans("$string_file.vehicle_document_expired_warning");
                    } elseif(date("Y-m-d", strtotime($vehicle_document->expire_date. " - $carpooling_configuration->user_document_reminder_time day")) <= $currentDate) {
                        $is_user_vehicle_document_going_to_expire = true;
                        $is_user_vehicle_document_going_to_expire_text = trans("$string_file.vehicle_document_expire_warning");
                    }
                }
            }
        }

        $country_area = CountryArea::where([['id', '=', $this->country_area_id]])->first();
        if($this->Merchant->demo != 1){
            if (empty($country_area)) {
                $is_user_minimum_balance = false;
            } elseif ($this->wallet_balance >= $country_area->minimum_wallet_amount) {
                $is_user_minimum_balance = true;
            } else {
                $is_user_minimum_balance = false;
                $is_user_minimum_balance_text = trans("$string_file.maintain_wallet_balance") . " " . $this->Country->isoCode . " " . $country_area->minimum_wallet_amount;
            }
        }else{
            $is_user_minimum_balance = true;
        }

        return [
            'is_vehicle_uploaded' => $is_vehicle_uploaded,
            'is_vehicle_document_upload' => $is_vehicle_document_upload,
            'can_user_offer_ride' => $can_user_offer_ride,
            'is_user_minimum_balance' => $is_user_minimum_balance,
            'is_user_minimum_balance_text' => $is_user_minimum_balance_text,

//            'offer_ride_text' => trans("$string_file.pending_vehicle_document"),
//            'upload_document' => trans("$string_file.driver_upload_document"),
//            'upload_vehicle' => trans("$string_file.upload_vehicle_document"),
//            'user_doc_upload' => trans("$string_file.upload_user_document"),

            'is_user_vehicle_document_going_to_expire' => $is_user_vehicle_document_going_to_expire,
            'is_user_vehicle_document_going_to_expire_text' => $is_user_vehicle_document_going_to_expire_text,

            'is_user_vehicle_document_expired' => $is_user_vehicle_document_expired,
            'is_user_vehicle_document_expired_text' => $is_user_vehicle_document_expired_text,
        ];
    }
}
