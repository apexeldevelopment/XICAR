<?php

namespace App\Http\Controllers\Merchant;

use App;
use Auth;
use App\Models\Country;
use App\Models\Language;
use App\Models\InfoSetting;
use App\Traits\ContentTrait;
use Illuminate\Http\Request;
use App\Traits\MerchantTrait;
use App\Models\CountryDocument;
use App\Models\LanguageCountry;
use Illuminate\Validation\Rule;
use App\Models\VersionManagement;
use App\Http\Controllers\Controller;
use App\Http\Requests\CountryRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Helper\Merchant as helperMerchant;

class CountryController extends Controller
{
    use ContentTrait, MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'COUNTRY')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index(Request $request)
    {
        $checkPermission = check_permission(1, 'view_countries');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
//        $countries = Country::where('merchant_id', $merchant_id)->latest()->paginate(25);
        $query = Country::where('merchant_id', $merchant_id)->latest();
        if (!empty($request->country_id)) {
            $query->where('id', $request->country_id);
        }
        if (!empty($request->phonecode)) {
            $query->where('phonecode', $request->phonecode);
        }
        if (!empty($request->isoCode)) {
            $query->where('isoCode', $request->isoCode);
        }
        $countries = $query->paginate(25);
        $search_countries = Country::where('merchant_id', $merchant_id)->latest()->get();
        $search_data = $request->all();
        $string_file = $this->getStringFile(NULL, $merchant);
        $distance_units = get_distance_units($string_file);
        return view('merchant.country.index', compact('countries', 'search_countries', 'search_data', 'distance_units'));
    }

    public function create()
    {
        $checkPermission = check_permission(1, 'create_countries');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $applicationConfig = $merchant->ApplicationConfiguration;
        $configurations = $merchant->Configuration;
        $languages = $merchant->Language;
        $documents = $merchant->Document;
        $default_country_list = $this->countryListNew();
        $country_wise_payment_gateway_list = [];
        if(isset($configurations->country_wise_payment_gateway) && $configurations->country_wise_payment_gateway == 1){
            $having_payment_methods = $merchant->PaymentMethod->whereIn("id",[2,4]);
            if(count($having_payment_methods) >= 1){
                foreach($merchant->PaymentOption as $payment_option){
                    $country_wise_payment_gateway_list[$payment_option->id] = $payment_option->name;
                }
            }
        }

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $payment_options = $merchant->PaymentOptionsConfiguration;
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);
        $distance_units = get_distance_units($string_file);
        $driver_address_fields = Config::get("custom.all_driver_address_fields");
        return view('merchant.country.create', compact('languages', 'documents', 'applicationConfig', 'configurations', 'default_country_list', 'carpooling_enable', 'payment_options', 'country_wise_payment_gateway_list', 'distance_units', 'driver_address_fields'));
    }

    public function store(CountryRequest $request)
    {
        $checkPermission = check_permission(1, 'create_countries');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
//        $merchant = App\Models\Merchant::find($merchant_id);
        $applicationConfig = $merchant->ApplicationConfiguration;
        $config = $merchant->Configuration;
        if ($applicationConfig->user_document == 1) {

            if ($applicationConfig->local_citizen_foreigner_documents == 1) {
                $request->validate([
                'local_citizen_documents' => 'required|array|min:1',
                'foreigner_documents' => 'required|array|min:1',
            ]);
            } else {
                $request->validate([
                'document' => 'required|array|min:1'
            ]);
            }
        }

        $tipAmount = array();
        if (!empty($request->tip_short_amount)) {
            foreach ($request->tip_short_amount as $value) {
                $tipAmount[] = array('amount' => $value);
            }
        }
//p($request->all());
        $countries = Country::where('merchant_id', $merchant->id)->get();
        foreach($countries as $country){
            if($country->country_code == $request->country_code && $country->isoCode == $request->isocode && $country->phonecode == "+" . $request->phonecode){
                return redirect()->back()->withErrors("Country already exist in " . $country->LanguageCountryAny->locale . " language" );
            }
        }
        $country = Country::create([
            'merchant_id' => $merchant->id,
            'country_code' => $request->country_code,
            'isoCode' => $request->isocode,
            'phonecode' => "+" . $request->phonecode,
            'maxNumPhone' => $request->maxNumPhone,
            'minNumPhone' => $request->minNumPhone,
//            'default_language' => $request->default_language,
            'distance_unit' => $request->distance_unit,
            'sequance' => $request->sequance,
            'transaction_code' => $request->online_transaction,
//            'additional_details' => ($request->has('additional_details')) ? $request->additional_details : 0,
            //'parameter_name' => $request->parameter_name,
            //'placeholder' => $request->placeholder,
            'payment_option_ids' => isset($request->country_wise_payment_gateway) ? $request->country_wise_payment_gateway : NULL,
            'tip_short_amount' => json_encode($tipAmount, true),
            'driver_address_fields' => isset($request->driver_address_fields) ? json_encode($request->driver_address_fields) : NULL,
            'sub_area_codes' => isset($request->sub_area_codes) ? $request->sub_area_codes : NULL,
        ]);
        $this->SaveLanguageCountry($merchant_id, $country->id, $request->name, $request->currency, $request->parameter_name, $request->placeholder);

//        $merchant_segment = helperMerchant::MerchantSegments(1);
//        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
//        $value=$request->check_user == null ? 2 : 1;
//        $value1=$request->check_offer_user == null ? 2 : 1;

        if ($applicationConfig->user_document == 1) {
             if ($applicationConfig->local_citizen_foreigner_documents == 1) {
                $localCitizenDocuments = $request->input('local_citizen_documents', []);
                $foreignerDocuments = $request->input('foreigner_documents', []);
                
                $documentsToSync = [];
                
                foreach ($localCitizenDocuments as $docId) {
                    $documentsToSync[] = ['document_id' => $docId, 'document_type' => 1];
                }
                
                foreach ($foreignerDocuments as $docId) {
                    $documentsToSync[] = ['document_id' => $docId, 'document_type' => 2];
                }
                
                // Sync logic
                $keep = [];
              
                    foreach ($documentsToSync as $item) {
                        $countryAreaDocument = CountryDocument::updateOrCreate(
                            [
                                'country_id' => $country->id,
                                'document_id'     => $item['document_id'],
                                'document_type'   => $item['document_type'],
                            ],
                            []
                        );
                        $keep[] = $countryAreaDocument->id;
                    }
               
                // Delete old records
                CountryDocument::where('country_id', $country->id)
                    ->whereNotIn('id', $keep)
                    ->delete();
                
            } else {
                $Documents = $request->input('document', []);
                $documentsToSync = [];

                foreach ($Documents as $docId) {
                    $documentsToSync[] = ['document_id'=>  $docId ,'document_type' => 1];
                }

                  $country->documents()->sync($documentsToSync);
            } 

            $country->document_auto_verify = $request->document_auto_verify;
            $country->save();
        }
//        if ($config->countrywise_payment_gateway == 1) {
//            $country->paymentoption()->sync($request->input('payment_option'));
//            $country->paymentcashout()->sync($request->input('payin_option'));
//        }
        VersionManagement::updateVersion($merchant_id);
        $string_file = $this->getStringFile(NULL, $merchant);
        return redirect()->route('country.index')->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function edit($id)
    {
        $checkPermission = check_permission(1, 'edit_countries');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $country = Country::where('merchant_id', $merchant_id)->findorFail($id);
        $merchant = App\Models\Merchant::find($merchant_id);
        $applicationConfig = $merchant->ApplicationConfiguration;
        $configurations = $merchant->Configuration;
        $languages = $merchant->Language;
        $documents = $merchant->Document;
        $payment_options = $merchant->PaymentOptionsConfiguration;
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;

//        $default_country_list = $this->countryList();
        $country_wise_payment_gateway_list = [];
        if(isset($configurations->countrywise_payment_gateway) && $configurations->countrywise_payment_gateway == 1){
            $having_payment_methods = $merchant->PaymentMethod->whereIn("id",[2,4]);
            if(count($having_payment_methods) >= 1){
                foreach($merchant->PaymentOptionsConfiguration as $payment_option){
                    $country_wise_payment_gateway_list[$payment_option->id] = $payment_option->PaymentOption->name;
                }
            }
        }
        $string_file = $this->getStringFile(NULL, $merchant);
        $distance_units = get_distance_units($string_file);
        $driver_address_fields = Config::get("custom.all_driver_address_fields");


       
         $CitizenDocuments = [];
          if ($applicationConfig->local_citizen_foreigner_documents == 1) {
            $CitizenDocuments['localCitizenDocuments'] = [];
            $CitizenDocuments['foreignerDocuments'] = [];
                foreach($country->Documents as $document) {
                    if($document->pivot->document_type == 1){
                        $CitizenDocuments['localCitizenDocuments'][] = $document->id;
                    }else{
                        $CitizenDocuments['foreignerDocuments'][] = $document->id;
                    }
                 }
          }
        //   dd( $CitizenDocuments );

        return view('merchant.country.edit', compact('country', 'languages', 'applicationConfig', 'configurations', 'CitizenDocuments', 'documents', 'payment_options','carpooling_enable', 'country_wise_payment_gateway_list', 'distance_units', 'driver_address_fields'));
    }

    public function update(Request $request, $id)
    {
        $locale = App::getLocale();
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $distance_units = get_distance_units(null,true);
        $distance_units = implode(",",$distance_units);
        $request->validate([
            'name' => ['required', 'max:255',
                Rule::unique('language_countries')->where(function ($query) use ($merchant_id, &$locale, &$id) {
                    $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', $locale], ['country_id', '!=', $id]]);
                })],
//            'isoCode' => 'required',
            'country_code' => 'required',
//            'phonecode' => 'required|integer',
            'maxNumPhone' => 'required|integer|gte:maxNumPhone',
            'distance_unit' => 'required|integer|in:'.$distance_units,
//            'default_language' => 'required',
//            'currency' => 'required',
            'minNumPhone' => 'required|integer|lte:minNumPhone',
//            'additional_details'=>'integer|between:0,1',
//            'parameter_name' => 'required_if:additional_details,1',
//            'placeholder' => 'required_if:additional_details,1',
//            'isoCode' => ['required',
//                Rule::unique('countries','isoCode')->where(function ($query) use ($merchant_id) {
//                    return $query->where([['merchant_id', '=', $merchant_id]]);
//                })->ignore($id)],
            'phonecode' => ['required', 'integer',
                Rule::unique('countries', 'phonecode')->where(function ($query) use ($merchant_id) {
                    return $query->where([['merchant_id', '=', $merchant_id]]);
                })->ignore($id)],
        ], ['name.unique' => 'This country already exists in database.']);
//        $merchant = App\Models\Merchant::find($merchant_id);
        $applicationConfig = $merchant->ApplicationConfiguration;
        $configuration = $merchant->Configuration;
        if ($applicationConfig->user_document == 1) {

            if ($applicationConfig->local_citizen_foreigner_documents == 1) {
                $request->validate([
                'local_citizen_documents' => 'required|array|min:1',
                'foreigner_documents' => 'required|array|min:1',
            ]);
            } else {
                $request->validate([
                'document' => 'required|array|min:1'
            ]);
            }
           
        }

        $tipAmount = array();
        if (!empty($request->tip_short_amount)) {
            foreach ($request->tip_short_amount as $value) {
                $tipAmount[] = array('amount' => $value);
            }
        }

        $country = Country::where([['merchant_id', '=', $merchant_id]])->findorFail($id);
        $country->isoCode = $request->isoCode;
        $country->phonecode = "+" . $request->phonecode;
        $country->country_code = $request->country_code;
        $country->minNumPhone = $request->minNumPhone;
        $country->maxNumPhone = $request->maxNumPhone;
//        $country->default_language = $request->default_language;
        $country->distance_unit = $request->distance_unit;
        $country->sequance = $request->sequance;
//        $country->additional_details = $request->additional_details;
        $country->transaction_code = $request->online_transaction;
        //$country->parameter_name = $request->parameter_name;
        //$country->placeholder = $request->placeholder;
        $country->payment_option_ids = isset($request->country_wise_payment_gateway) ? $request->country_wise_payment_gateway : NULL;
        $country->tip_short_amount = json_encode($tipAmount, true);
        $country->driver_address_fields = isset($request->driver_address_fields) ? json_encode($request->driver_address_fields) : NULL;
        $country->sub_area_codes = isset($request->sub_area_codes) ? $request->sub_area_codes : NULL;
        $country->save();
        $this->SaveLanguageCountry($merchant_id, $id, $request->name, $request->currency, $request->parameter_name, $request->placeholder);

//        $merchant_segment = helperMerchant::MerchantSegments(1);
//        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
//        $value=$request->check_user == null ? 2 : 1;
//        $value1=$request->check_offer_user == null ? 2 : 1;

        if ($applicationConfig->user_document == 1) {
             if ($applicationConfig->local_citizen_foreigner_documents == 1) {
                $localCitizenDocuments = $request->input('local_citizen_documents', []);
                $foreignerDocuments = $request->input('foreigner_documents', []);
                
                $documentsToSync = [];
                
                foreach ($localCitizenDocuments as $docId) {
                    $documentsToSync[] = ['document_id' => $docId, 'document_type' => 1];
                }
                
                foreach ($foreignerDocuments as $docId) {
                    $documentsToSync[] = ['document_id' => $docId, 'document_type' => 2];
                }
                
                // Sync logic
                $keep = [];
              
                    foreach ($documentsToSync as $item) {
                        $countryAreaDocument = CountryDocument::updateOrCreate(
                            [
                                'country_id' => $country->id,
                                'document_id'     => $item['document_id'],
                                'document_type'   => $item['document_type'],
                            ],
                            []
                        );
                        $keep[] = $countryAreaDocument->id;
                    }
               
                // Delete old records
                CountryDocument::where('country_id', $country->id)
                    ->whereNotIn('id', $keep)
                    ->delete();
                
            } else {
                $Documents = $request->input('document', []);
                $documentsToSync = [];

                foreach ($Documents as $docId) {
                    $documentsToSync[] = ['document_id'=>  $docId ,'document_type' => 1];
                }

                  $country->documents()->sync($documentsToSync);
            }          
            $country->document_auto_verify = $request->document_auto_verify;
            $country->save();
        }
        if ($configuration->countrywise_payment_gateway == 1) {
            $country->paymentoption()->sync($request->input('country_wise_payment_gateway'));
            $country->paymentcashout()->sync($request->input('country_wise_payment_gateway'));
        }
        $string_file = $this->getStringFile(NULL, $merchant);
        return redirect()->route('country.index')->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function destroy($id)
    {
        //
    }

    public function SaveLanguageCountry($merchant_id, $country_id, $name, $currency, $parameter_name = null, $placeholder = null)
    {
        LanguageCountry::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'country_id' => $country_id
        ], [
            'name' => $name,
            'currency' => $currency,
            'parameter_name' => $parameter_name,
            'placeholder' => $placeholder,
        ]);
    }

    public function ChangeStatus($id, $status)
    {
        $validator = Validator::make(
            [
                'id' => $id,
                'status' => $status,
            ],
            [
                'id' => ['required'],
                'status' => ['required', 'integer', 'between:1,2'],
            ]);
        if ($validator->fails()) {
            return redirect()->back();
        }

        $country = Country::findOrFail($id);
        $country->country_status = $status;
        $string_file = $this->getStringFile(NULL, $country->Merchant);
        $country->save();
        $body = $status == 1 ? trans("$string_file.activated") : trans("$string_file.deactivated");
        $message = $body;
        return redirect()->back()->withSuccess($message);
    }

//    public function SearchCountry(Request $request){
//        $checkPermission =  check_permission(1,'view_countries');
//        if ($checkPermission['isRedirect']){
//            return  $checkPermission['redirectBack'];
//        }
//        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
//        $query = Country::where('merchant_id', $merchant_id)->latest();
//        if (!empty($request->country_id)) {
//            $query->where('id', $request->country_id);
//        }
//        $countries = $query->paginate(25);
//        $search_countries = Country::where('merchant_id', $merchant_id)->latest()->get();
//        return view('merchant.country.index', compact('countries','search_countries'));
//    }
}
