<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App;
class MerchantMembershipPlan extends Model
{
    protected $guarded = [];
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function LanguageMerchantMembershipPlanAny()
    {
        return $this->hasOne(LanguageMerchantMembershipPlan::class,'merchant_membership_plan_id');
    }
    public function LanguageMerchantMembershipPlanSingle()
    {
        return $this->hasOne(LanguageMerchantMembershipPlan::class,'merchant_membership_plan_id')->where([['locale', '=', App::getLocale()]]);
    }
    public function getPlanTitleAttribute()
    {
        if (empty($this->LanguageMerchantMembershipPlanSingle)) {
            return !empty($this->LanguageMerchantMembershipPlanAny) ? $this->LanguageMerchantMembershipPlanAny->plan_title : "";
        }
        return $this->LanguageMerchantMembershipPlanSingle->plan_title;
    }
    public function getPlanNameAttribute()
    {
        if (empty($this->LanguageMerchantMembershipPlanSingle)) {
            return !empty($this->LanguageMerchantMembershipPlanAny) ? $this->LanguageMerchantMembershipPlanAny->plan_name : "";
        }
        return $this->LanguageMerchantMembershipPlanSingle->plan_name;
    }
    public function getDescriptionAttribute()
    {
        if (empty($this->LanguageMerchantMembershipPlanSingle)) {
            return !empty($this->LanguageMerchantMembershipPlanAny) ? $this->LanguageMerchantMembershipPlanAny->description : "";
        }
        return $this->LanguageMerchantMembershipPlanSingle->description;
    }
}