<?php

namespace App\Http\Resources;

use App\Http\Controllers\Helper\Merchant;
use App\Models\Configuration;
use App\Models\UserCard;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CmsPage;

class UserResource extends JsonResource
{
    public function toArray($data)
    {
        $countryCode = "";
        $phonenumber = "";
        $isoCode = "";

        //Encrypt Decrypt
        $fname = "";
        $lname = "";
        $email = "";
        $userProfileImage = "";
        $bankName = "";
        $bankAddress = "";
        $accountHolderName="";
        $accountNumber="";
        $routingNumber = "";
        $ibanNumber = "";
        if($this->Merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];

                if($this->Country && $this->Country->phonecode){
                    $countryCode = encryptText($this->Country->phonecode,$secret,$iv);
                    $phonenumber = encryptText(substr($this->UserPhone, strlen($this->Country->phonecode)),$secret,$iv);

                }

                if($this->Country && $this->Country->isoCode){
                    $isoCode = encryptText($this->Country->isoCode,$secret,$iv);
                }

                if($this->first_name){
                    $fname = encryptText($this->first_name,$secret,$iv);
                }

                if($this->last_name){
                    $lname = encryptText($this->last_name,$secret,$iv);
                }

                if($this->email){
                    $email = encryptText($this->email,$secret,$iv);
                }

                if($this->UserProfileImage){
                    $userProfileImage = encryptText(get_image($this->UserProfileImage,'user',$this->merchant_id,true,false),$secret,$iv);
                }else{
                    $userProfileImage = encryptText(get_image(),$secret,$iv);
                }

                if(isset($this->Merchant->Configuration->user_bank_details_enable) && $this->Merchant->Configuration->user_bank_details_enable == 1){
                    if($this->bank_name){
                        $bankName = encryptText($this->bank_name,$secret,$iv);
                    }
                    if($this->account_holder_name){
                        $accountHolderName = $this->account_holder_name;
                    }
                    if($this->account_number){
                        $accountNumber = $this->account_number;
                    }
                    if($this->routing_number){
                        $routingNumber = $this->routing_number;
                    }
                    if($this->iban_number){
                        $ibanNumber = $this->iban_number;
                    }
                    if($this->bank_address){
                        $bankAddress = $this->bank_address;
                    }
                }
                
                
            }catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        if ($this->Country) {
            $phonenumber = $phonenumber ? $phonenumber : substr($this->UserPhone, strlen($this->Country->phonecode));
            $countryCode = $countryCode ? $countryCode : $this->Country->phonecode;
            $isoCode = $isoCode ? $isoCode : $this->Country->isoCode;
        }else{
            $phonenumber = $this->UserPhone;
        }

        $user_card = true;
        $user_signup_card_store = true;
        $config = Configuration::where('merchant_id', $this->merchant_id)->first();
        if(isset($config->user_signup_card_store_enable) && $config->user_signup_card_store_enable == 1){
            $user_signup_card_store = true;
            $cardList = UserCard::where([['user_id', '=', $this->id]])->get();
            if(count($cardList) > 0){
                $user_card = false;
            }
        }
        // need country id in case of demo user
//        p($this->id);
        $home = $this->UserAddress->where('category',1);
        $work = $this->UserAddress->where('category',2);

        $newMerchant = new Merchant();
        $country_areas = $newMerchant->CountrywithAreaList($this->Merchant,$this->country_id);
        $bank_details = [];
        if(isset($this->Merchant->Configuration->user_bank_details_enable) && $this->Merchant->Configuration->user_bank_details_enable == 1){
            $account_type = "";
            if(!empty($this->account_type_id)){
                $account_type = $this->AccountType->Name;
            }
            $bank_details = array(
                'bank_name' => $bankName ? $bankName : (isset($this->bank_name) ? $this->bank_name : ""),
                'account_type' => $account_type,
                'account_type_id' => isset($this->account_type_id) ? $this->account_type_id : 0,
                'online_code' => isset($this->online_code) ? $this->online_code : "",
                'account_holder_name' => $accountHolderName ? $accountHolderName : (isset($this->account_holder_name) ? $this->account_holder_name : ""),
                'account_number' => $accountNumber ? $accountNumber : (isset($this->account_number) ? $this->account_number : ""),
                'transaction_code_text' => isset($this->Country) ? $this->Country->transaction_code : "Transaction Code",
                'bank_institution_number'=>isset($this->bank_institution_number) ? $this->bank_institution_number: "",
                'routing_number'=>$routingNumber ? $routingNumber : (isset($this->routing_number) ? $this->routing_number: ""),
                'iban_number'=>$ibanNumber ? $ibanNumber : (isset($this->iban_number) ? $this->iban_number : ""),
                'swift_bic_code'=>isset($this->swift_bic_code) ? $this->swift_bic_code : "",
                'bank_address'=>$bankAddress ? $bankAddress : (isset($this->bank_address) ? $this->bank_address : ""),
            );
        }
        else
        {
            $bank_details = array(
                'bank_name' =>  "",
                'account_type' => "",
                'account_type_id' =>  "",
                'online_code' =>  "",
                'account_holder_name' =>  "",
                'account_number' => "",
                'transaction_code_text' => "",
                'bank_institution_number'=>"",
                'routing_number'=>"",
                'iban_number'=>"",
                'swift_bic_code'=>"",
                'bank_address'=>"",
            );
        }

        $cms_pages = CmsPage::where([
            ['merchant_id', '=', $this->Merchant->id],
            ['application', '=', 1]
        ])->where(function ($query) {
            $query->where('country_id', $this->country_id)
                ->orWhereNull('country_id');
        })->pluck('slug')
            ->toArray();

        return [
            'country_id' => !empty($this->country_id) ? $this->country_id : $this->CountryArea->Country->id,
            'phone_code' => (string)$countryCode,
            'isoCode' => $isoCode,
            'first_name' => $fname ? $fname : (string)$this->first_name,
            'last_name' => $lname ? $lname : ($this->last_name ? (string)$this->last_name : ""),
            'email' => $email ? $email : (string)$this->email,
            'rating' => (string)$this->rating,
            'merchant_id' => (string)$this->merchant_id,
            'PhoneVerified' => (string)$this->PhoneVerified,
            'id' => (string)$this->id,
            'ReferralCode' => (string)$this->ReferralCode,
            'password' => (string)$this->password,
            'UserPhone' => (string)$phonenumber,
            'wallet_balance' => (string)$this->wallet_balance,
            'UserProfileImage' => $userProfileImage ? $userProfileImage : ((string)$this->UserProfileImage ? get_image($this->UserProfileImage,'user',$this->merchant_id,true,false) :
                get_image()),
            'user_gender' => (string)$this->user_gender,
            'dob'=>(string)$this->dob,
            'signup_status' => (string)$this->signup_status,
            'outstanding_amount' => (string)$this->outstanding_amount ? $this->outstanding_amount : "",
            'smoker_type' => (string)$this->smoker_type,
            'allow_other_smoker' => (string)$this->allow_other_smoker,
            'UserSignupType' => (string)$this->UserSignupType,
            'login_via' => (string)$this->login_via,
            'user_card' => $user_card,
            'user_signup_card_store' => $user_signup_card_store,
            'no_of_favorite_drivers' => !empty($this->FavouriteDriver) ? $this->FavouriteDriver->count(): 0,
            'home_location' => isset($home[0]) ? $home[0]->address : '',
            'work_location' => isset($work[0]) ? $work[0]->address : '',
            'no_of_bookings_done' => !empty($this->Booking) ? $this->Booking->count(): 0,
            'no_of_emergency_contacts' => !empty($this->Sos) ? $this->Sos->count(): 0,
            "country_areas"=>$country_areas,
            "reward_points" => !empty($this->reward_points) ? (string)$this->reward_points : "0",
            'bank_details' => $bank_details,
            'is_corporate_user' => !empty($this->corporate_id) ? true : false,
            'country_code'=> (isset($this->Country) && $this->Country->country_code) ? $this->Country->country_code : "",
            'cms_pages'=>$cms_pages,
            'tap_customer_token'=>isset($this->tap_user_customer_token) ? (string) $this->tap_user_customer_token : ""
        ];
    }

    public function with($data)
    {
        return [
            'result' => "1",
            'message' => "success",
        ];
    }
}
