<?php
namespace App\Http\Controllers\Helper;
use App\Models\ApplicationString;
class GetStringCommon{
public static function getStringFromVersion($merchant_id,$locale,$version,$platform,$application){
        $data = ApplicationString::with(['ApplicationMerchantString'=>function($q) use ($merchant_id,$locale,$version){
            $q->where([['merchant_id','=',$merchant_id],['locale','=',$locale],['version','>',$version]]);
        }])
            ->whereHas('ApplicationMerchantString', function ($query) use ($merchant_id,$locale,$version){
            $query->where([['merchant_id','=',$merchant_id],['locale','=',$locale],['version','>',$version]]);
        })
            ->where([['platform','=',$platform],['application','=',$application]])->latest();
        return $data;
    }
}