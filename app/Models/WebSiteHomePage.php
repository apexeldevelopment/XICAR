<?php

namespace App\Models;

use App;
use App\Models\WebSiteHomePageTranslation;
use Illuminate\Database\Eloquent\Model;

class WebSiteHomePage extends Model
{
    protected $guarded = [];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function LanguageAny()
    {
        return $this->hasOne(WebSiteHomePageTranslation::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(WebSiteHomePageTranslation::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getStartAddressAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->start_address_hint;
        }
        return $this->LanguageSingle->start_address_hint;
    }

    public function getEndAddressAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->end_address_hint;
        }
        return $this->LanguageSingle->end_address_hint;
    }

    public function getBookingButtonAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->book_btn_title;
        }
        return $this->LanguageSingle->book_btn_title;
    }
    //estimate_btn_title

    public function getEstimateButtonAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->estimate_btn_title;
        }
        return $this->LanguageSingle->estimate_btn_title;
    }

    public function getEstimateDescriptionAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->estimate_description;
        }
        return $this->LanguageSingle->estimate_description;
    }

    //driver_heading
    public function getDriverHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->driver_heading;
        }
        return $this->LanguageSingle->driver_heading;
    }

    //subHeading
    public function getsubHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->driver_sub_heading;
        }
        return $this->LanguageSingle->driver_sub_heading;
    }
    //driver_buttonText

    public function getdriverButtonTextAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->driver_buttonText;
        }
        return $this->LanguageSingle->driver_buttonText;
    }
    //

    public function getFooterHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->footer_heading;
        }
        return $this->LanguageSingle->footer_heading;
    }

    //subHeading

    public function getFooterSubHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->footer_sub_heading;
        }
        return $this->LanguageSingle->footer_sub_heading;
    }
    
    //headings
    public function getHomePageIconHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->home_page_icon_heading : null;
        }
        return $this->LanguageSingle->home_page_icon_heading;
    }

    public function getHomePageAdvertHeaderAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->home_page_advert_header : null;
        }
        return $this->LanguageSingle->home_page_advert_header;
    }

    public function getHomePageAdvertContentAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->home_page_advert_content : null;
        }
        return $this->LanguageSingle->home_page_advert_content;
    }


    public function getHomePageIconContent1Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->home_page_icon_content_1 : null;
        }
        return $this->LanguageSingle->home_page_icon_content_1;
    }

    public function getHomePageIconContent2Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->home_page_icon_content_2 : null;
        }
        return $this->LanguageSingle->home_page_icon_content_2;
    }

    public function getHomePageIconContent3Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->home_page_icon_content_3 : null;
        }
        return $this->LanguageSingle->home_page_icon_content_3;
    }

    public function getHomePageIconContent4Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->home_page_icon_content_4 : null;
        }
        return $this->LanguageSingle->home_page_icon_content_4;
    }
    
    
    public function getFeatureDetailsAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->feature_details : null;
        }
        return $this->LanguageSingle->feature_details;
    }

    public function getServiceHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->service_heading : null;
        }
        return $this->LanguageSingle->service_heading;
    }

    public function getFeaturesHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->features_heading : null;
        }
        return $this->LanguageSingle->features_heading;
    }


    public function getBottomAboutUsHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->bottom_about_us_heading : null;
        }
        return $this->LanguageSingle->bottom_about_us_heading;
    }

    public function getBottomServicesHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->bottom_services_heading : null;
        }
        return $this->LanguageSingle->bottom_services_heading;
    }

    public function getBottomPrivacyPolicyHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->bottom_privacy_policy_heading : null;
        }
        return $this->LanguageSingle->bottom_privacy_policy_heading;
    }

    public function getBottomContactUsHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->bottom_contact_us_heading : null;
        }
        return $this->LanguageSingle->bottom_contact_us_heading;
    }

    public function getBottomTermsAndSerHeadingAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->bottom_terms_and_ser_heading : null;
        }
        return $this->LanguageSingle->bottom_terms_and_ser_heading;
    }

    public function getLoginTextAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->login_text : null;
        }
        return $this->LanguageSingle->login_text;
    }

    public function getSignupTextAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->signup_text : null;
        }
        return $this->LanguageSingle->signup_text;
    }
    
        public function getAndroidUserLinkTextAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->android_user_link_text : null;
        }
        return $this->LanguageSingle->android_user_link_text;
    }

    public function getAndroidDriverLinkTextAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->android_driver_link_text : null;
        }
        return $this->LanguageSingle->android_driver_link_text;
    }

    public function getIosUserLinkTextAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->ios_user_link_text : null;
        }
        return $this->LanguageSingle->ios_user_link_text;
    }

    public function getIosDriverLinkTextAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->ios_driver_link_text : null;
        }
        return $this->LanguageSingle->ios_driver_link_text;
    }
    

    public function getAdditionalHeader1Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->additional_header_1 : null;
        }
        return $this->LanguageSingle->additional_header_1;
    }

    public function getAdditionalHeaderContent1Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->additional_header_content_1 : null;
        }
        return $this->LanguageSingle->additional_header_content_1;
    }

    public function getAdditionalHeader2Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->additional_header_2 : null;
        }
        return $this->LanguageSingle->additional_header_2;
    }

    public function getAdditionalHeaderContent2Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->additional_header_content_2 : null;
        }
        return $this->LanguageSingle->additional_header_content_2;
    }

    public function getExtraContent1Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->extra_content_1 : null;
        }
        return $this->LanguageSingle->extra_content_1;
    }


    public function getExtraContent2Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->extra_content_2 : null;
        }
        return $this->LanguageSingle->extra_content_2;
    }

    public function getExtraContent3Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->extra_content_3 : null;
        }
        return $this->LanguageSingle->extra_content_3;
    }

    public function getExtraContent4Attribute()
    {
        if (empty($this->LanguageSingle)) {
            return !empty($this->LanguageAny) ? $this->LanguageAny->extra_content_4 : null;
        }
        return $this->LanguageSingle->extra_content_4;
    }


    public function WebsiteFeature()
    {
        return $this->hasOne(WebsiteFeature::class,'web_site_home_page_id');
    }

    public function WebsiteFeaturesComponents()
    {
        return $this->hasOne(WebsiteFeaturesComponents::class,'web_site_home_page_id');
    }
}
