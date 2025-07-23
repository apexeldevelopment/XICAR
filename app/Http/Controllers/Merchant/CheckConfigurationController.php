<?php

namespace App\Http\Controllers\Merchant;

use App\Models\ApplicationConfiguration;
use App\Models\ApplicationTheme;
use App\Models\BookingConfiguration;
use App\Models\CarpoolingConfiguration;
use App\Http\Controllers\Helper\Merchant as helperMerchant;
use App\Models\Country;
use App\Models\InfoSetting;
use App\Models\LanguageApplicationTheme;
use App\Models\MerchantFarePolicy;
use App\Models\PaymentConfiguration;
use App\Models\Configuration;
use App\Models\DriverConfiguration;
use App\Models\Merchant;
use App\Models\VersionManagement;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use App\Models\MerchantStripeConnect;
use App\Models\Document;
use Illuminate\Support\Facades\App;
use App\Models\BonsBankToBankQrGateway;
use App\Models\BonsBankToBankQrGatewayLanguage;

class CheckConfigurationController extends Controller
{
    use ImageTrait,MerchantTrait;
    
    public function index(Request $request){
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $app_config = $merchant->ApplicationConfiguration;
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $arr_segment_group =   $this->segmentGroup($merchant_id,$return_type = "drop_down","");
        $merchant_segment_group = isset($arr_segment_group['arr_group']) ?  array_keys($arr_segment_group['arr_group']) : [];
        $segment_group_vehicle = false;
        $segment_group_handyman = false;
        $category_vehicle_type_module = $merchant->ApplicationConfiguration->home_screen_view;
        if (in_array(1, $merchant_segment_group) || in_array(3, $merchant_segment_group) || in_array(4, $merchant_segment_group)) {
            $segment_group_vehicle = true;
        }
        if (in_array(2, $merchant_segment_group)) {
            $segment_group_handyman = true;
        }
        $mcht_config_array = getMerchantConfigurationDetails();
        
        $bonsQrPayment = BonsBankToBankQrGateway::where('merchant_id',$merchant_id)->first();
        
        return view('merchant.check_configuration.check_configuration',compact('merchant','mcht_config_array','merchant_segment','merchant_segment_group','app_config','segment_group_vehicle','segment_group_handyman','category_vehicle_type_module','bonsQrPayment'));
    }
    
    public function saveBonsBankQr(Request $request,$id = null){
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $app_config = $merchant->ApplicationConfiguration;
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $arr_segment_group =   $this->segmentGroup($merchant_id,$return_type = "drop_down","");
        $merchant_segment_group = isset($arr_segment_group['arr_group']) ?  array_keys($arr_segment_group['arr_group']) : [];
        $segment_group_vehicle = false;
        $segment_group_handyman = false;
        $category_vehicle_type_module = $merchant->ApplicationConfiguration->home_screen_view;
        if (in_array(1, $merchant_segment_group) || in_array(3, $merchant_segment_group) || in_array(4, $merchant_segment_group)) {
            $segment_group_vehicle = true;
        }
        if (in_array(2, $merchant_segment_group)) {
            $segment_group_handyman = true;
        }
        $mcht_config_array = getMerchantConfigurationDetails();
        
    
        if(!empty($id)){
            $bonsQrPayment = BonsBankToBankQrGateway::where('merchant_id',$merchant_id)->first();  
        }else{
            $bonsQrPayment = new BonsBankToBankQrGateway;
            $bonsQrPayment->merchant_id = $merchant_id;
        }
        
        if ($request->hasFile('bank_qr_document')) {
            $bonsQrPayment->qr_image = $this->uploadImage('bank_qr_document', 'bons_qr_image', $merchant_id);
        }
            
        $bonsQrPayment->save();
        
        $this->bonsQrPaymentLanguage($merchant_id,$request->bank_name,$request->account_name,$bonsQrPayment->id);
        
        return view('merchant.check_configuration.check_configuration',compact('merchant','mcht_config_array','merchant_segment','merchant_segment_group','app_config','segment_group_vehicle','segment_group_handyman','category_vehicle_type_module','bonsQrPayment'));
    }
    
    public function bonsQrPaymentLanguage($merchant_id,$bankName,$accountName,$bonsQrPaymentId){
        BonsBankToBankQrGatewayLanguage::updateOrCreate([
            'merchant_id' => $merchant_id,'locale' => App::getLocale(), 'bons_bank_to_bank_qr_gateway_id' => $bonsQrPaymentId
        ], [
            'bank_name' => $bankName,
            'account_name' => $accountName,
        ]);
    }
}