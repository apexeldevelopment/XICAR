<?php

namespace App\Models;
use App;

use App\Models\BusinessSegment\BusinessSegment;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusinessSegment\Order;
use App\Models\BusinessSegment\Product;

class Segment extends Model
{
    protected $guarded = [];

    protected $hidden = array('pivot');

    public function Driver()
    {
        return $this->belongsToMany(Driver::class);
    }
    public function Category()
    {
        return $this->belongsToMany(Category::class);
    }
    public function Product()
    {
        return $this->hasMany(Product::class);
    }
    public function Merchant()
    {
        return $this->belongsToMany(Merchant::class,'merchant_segment','segment_id')->withPivot('segment_icon','sequence','price_card_owner','is_coming_soon','dynamic_url','segment_background_gradient_1','segment_background_gradient_2','segment_home_screen_image');
    }
    public function MerchantSegment($id)
    {
        return $this->belongsToMany(Merchant::class,'merchant_segment','segment_id')->withPivot('segment_icon','sequence','price_card_owner','is_coming_soon','dynamic_url')->wherePivot('merchant_id',$id)->first();
    }
    public function ServiceType()
    {
        return $this->hasMany(ServiceType::class);
    }
    public function SegmentTranslation()
    {
        return $this->hasMany(SegmentTranslation::class);
    }
    public function LanguageAny()
    {
        //$merchant_id = get_merchant_id();
        return $this->hasOne(SegmentTranslation::class);
    }
    public function LanguageSingle()
    {
        //$merchant_id = get_merchant_id();
        return $this->hasOne(SegmentTranslation::class)->where([['locale', '=', App::getLocale()]]);
    }
//     public function getNameAttribute()
//     {
//         if (empty($this->LanguageSingle)) {
//             if(!empty($this->LanguageAny))
//             {
//             return !empty($this->LanguageAny) ?  $this->LanguageAny->name : "";
//
//             }
//         }
//         return !empty($this->LanguageSingle) ?  $this->LanguageSingle->name : "";
//     }

    public function Name($merchant_id = NULL)
    {
        $merchant_id = empty($merchant_id) ? get_merchant_id() :$merchant_id ;
        $locale = App::getLocale();

        $service = $this->hasOne(SegmentTranslation::class, 'segment_id')->where(function($q) use ($locale){
            $q->where("locale", $locale)->orWhere("locale","en");
        })->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '".$locale."' THEN 1 ELSE 2 END")->first();
//        p($service);

//        $service = $this->hasOne(SegmentTranslation::class, 'segment_id')
//            ->where(function ($q) use ($locale) {
//                $q->where('locale', $locale);
//            })
//            ->where([['merchant_id', '=', $merchant]])->first();
//        if(empty($service))
//        {
//            $service = $this->hasOne(SegmentTranslation::class, 'segment_id')
//                ->where(function ($q) use ($locale) {
//                $q->where('locale', '!=', NULL);
//                })
//                ->where([['merchant_id', '=', $merchant]])->first();
//        }
        if (!empty($service)) {
//            p($service->name);
            return $service->name;
        }
//        p($this->name);
        return $this->name;
    }

    public function ServiceTimeSlot()
    {
        return $this->hasMany(ServiceTimeSlot::class);
    }
    public function SegmentGroup()
    {
        return $this->belongsTo(SegmentGroup::class);
    }
    // document of segment specially for group 2 segments
    public function CountryAreaDocument()
    {
        return $this->belongsToMany(Document::class, 'country_area_segment_document','segment_id')->withPivot('segment_id');
    }

    public function BusinessSegment(){
        return $this->hasMany(BusinessSegment::class );
    }

    public function CountryArea()
    {
        return $this->belongsToMany(CountryArea::class);
    }

    public function DriverGallery(){
        return $this->hasMany(DriverGallery::class ,'segment_id');
    }

    public function DriverSegmentDocument(){
        return $this->hasMany(DriverSegmentDocument::class );
    }
    // orders of all segments
    public function HandymanOrder(){
        return $this->hasMany(HandymanOrder::class );
    }
    // orders of all segments
    public function Booking(){
        return $this->hasMany(Booking::class,'segment_id');
    }
    // orders of all segments
    public function Order(){
        return $this->hasMany(Order::class );
    }
    public function SegmentPriceCard(){
        return $this->hasOne(SegmentPriceCard::class );
    }

    public function SubscriptionPackage(){
        return $this->hasMany(SubscriptionPackage::class );
    }

    public function CarpoolingRide(){
        return $this->hasMany(CarpoolingRide::class);
    }
    public function BusSegment(){
        return Segment::where('slag','BUS_BOOKING')->first();
    }

}
