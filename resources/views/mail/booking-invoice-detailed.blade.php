<html><head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <title></title>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css">
</head>
<body style="background-color: #f6f6f6;  padding:20px">
<div class="container content-width" style=" margin: 20px auto;background-repeat: no-repeat; background-size: cover; background: url(images/bg2.png); max-width: 700px;min-width:300px; font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;">
    <div style="background-color:#ffffffe8; ">
        <table style="margin:0;border-collapse: collapse;width: 100%;">
            <tbody>
            <tr>
                <td style="float: left"><div class="logo" style="text-align:center; padding:10px; ">
                        <img align="center" width="100" height="100" src="{{get_image($booking->Merchant->BusinessLogo,'business_logo',$booking->merchant_id,true,true,'email')}}">
                    </div></td>
                <td><div class="logo" style="text-align:right; padding:10px; ">
                        <p style="margin: 0;">@lang("$s_string_file.service_provider_mail_heading")</p>
                        <p style="margin: 0;">{{$booking->Driver->first_name.' '.$booking->Driver->last_name}}</p>
                        <p style="margin: 0;">@lang("$s_string_file.id"): {{$booking->Driver->id}}</p>
                    </div></td>
            </tr>
            </tbody>
        </table>
        <div class="user-details" style="font-size: 13px; font-weight: 400;text-align: left;padding-left:25px; padding-right:25px;">
            <p style="border-bottom: 1px solid #ddd;"></p>
            <p>{{$booking->User->first_name.' '.$booking->User->last_name}},</p>
            <p>@lang("$s_string_file.id"): {{$booking->User->id}}</p>
            <p>@lang("$s_string_file.mail_content_6")</p>
            <p>@lang("$s_string_file.mail_content_1") {{$booking->Merchant->BusinessName}}! .@lang("$s_string_file.mail_content_4")</p>
            <p>@lang("$s_string_file.mail_content_3")</p>
        </div>
        <div class="user-details" style="padding-left:25px;display: block;margin-right:25px;">
            <table style="margin:0;border-collapse: collapse;width: 100%;">
                <tbody>
                <tr>
                    <td style="border-bottom: none;padding:0;text-align: left;">
                        <table align="left" style="margin:0;width: auto;max-width:100%;padding-bottom:10px;">
                            <tbody>
                            <tr>
                                <td style="border-bottom: none; padding:0;">
                                    <strong><p style="font-size: 16px;  margin-bottom: 5px;">@lang("$s_string_file.mail_content_8") {{date_format($booking->created_at,'Y')}}/{{$booking->merchant_order_id}}</p></strong>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <table align="right" style="margin:0;width: auto;max-width:100%;padding-bottom:10px;text-align: left;">
                            <tbody>
                            <tr>
                                <td style="border-bottom: none;padding:0; text-align: right;">
                                    <p style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">@lang("$s_string_file.date") & @lang("$s_string_file.time")</p>
                                    <h6 style="font-weight:900;font-size:14px; margin:0;">{{date_format($booking->created_at,'Y-m-d h:i a')}}</h6>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="details" style="margin-left:8px; margin-right: 20px;margin-top:20px;font-size: 12px; font-weight: 400;">
            <table style="border-collapse: collapse;width: 100%;">
                <thead style="background: #071092a9;">
                <tr>
                    <th style="text-align: left;padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 13%">@lang("$s_string_file.sn")</th>
                    <th style="text-align: left;padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 33.33%">@lang("$s_string_file.service_type")</th>
                    <th style="text-align: left;padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 25%">@lang("$s_string_file.type")</th>
                    <th style="text-align: left;padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 13%">@lang("$s_string_file.quantity")</th>
                    <th style="text-align: left;padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 23%">@lang("$s_string_file.price")</th>
                    <th style="text-align: right;padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 23%">@lang("$s_string_file.total")</th>
                </tr>
                </thead>
                <tbody>
                @php $sn = 1;
                    $tax_amount = 0;
                    if(!empty($booking->tax_after_dispute)){
                        $tax_amount = $booking->tax_after_dispute;
                    }
                    else{
                        if(!empty($booking->tax)){
                            $tax_amount = $booking->tax;
                        }
                    }
                    $final_amount = $booking->final_amount_paid;
                    $service_amount = ($final_amount - $tax_amount);
                    $currency = $booking->CountryArea->Country->isoCode;
                    $bidding_amount = $booking->bidding_amount;

                @endphp
                @if(isset($booking->HandymanOrderDetail) && $booking->HandymanOrderDetail->count() > 0)
                    @foreach($booking->HandymanOrderDetail as $service)
                        <tr style="border-bottom: 2px solid #ddd;">
                            <td style="text-align: left; padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 13%">{{$sn}}</td>
                            <td style="text-align: left; padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 33.33%">{{$service->ServiceType->ServiceName($booking->merchant_id)}}</td>
                            <td style="text-align: left; padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 25%">{{$booking->price_type == 1 ? trans("$s_string_file.fixed") : trans("$s_string_file.hourly")}}</td>
                            <td style="text-align: left; padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 13%">{{$service->quantity}}</td>
                            <td style="text-align: left; padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 25%">@if(!empty($bidding_amount)) {{$bidding_amount}} @else {{$service->price}} @endif</td>
                            <td style="text-align: right; padding-top: 5px; padding-bottom: 5px; padding-left: 2px; padding-right: 2px; width: 25%">@if(!empty($bidding_amount)) {{$bidding_amount}} @else {{$service->total_amount}} @endif</td>
                        </tr>
                    @endforeach
                @endif
                <tr>
                    <td colspan="2" style="text-align: left !important; border-bottom: none; padding-top: 5px; padding-bottom: 5px;padding-left: 2px; padding-right: 2px;">@lang("$s_string_file.service_amount")</td>
                    <td colspan="3" style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;"></td>
                    <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;padding-left: 2px; padding-right: 2px;">{{$service_amount}}</td>
                </tr>
                <tr>
                    <td style="text-align: left !important; border-bottom: none; padding-top: 5px; padding-bottom: 5px;padding-left: 2px; padding-right: 2px;">@lang("$s_string_file.tax")</td>
                    <td colspan="4" style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;"></td>
                    <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;padding-left: 2px; padding-right: 2px;">{{$tax_amount}}</td>
                </tr>
                </tbody>
            </table>
            <table style="border-collapse: collapse;width: 100%;">
                <tbody><tr style="background: #f9f9f9;">
                    <td style="float:left !important; font-size: 15px; border-bottom: none; padding-top: 5px; padding-bottom: 5px; color: #071192;">@lang("$s_string_file.grand_total")</td>
                    <td colspan="4"></td>
                    <td style="float: right; border-bottom: none; font-size: 15px; padding-top: 5px; padding-bottom: 5px; color: #071192;">{{$currency.' '.$final_amount}}</td>
                </tr>
                </tbody></table>
        </div>
        <div class="details" style="margin-left:25px; margin-right: 25px; font-size: 12px;">
            <p>@lang("$s_string_file.mail_content_7") {{date_format($booking->created_at,'Y-m-d')}}</p>
        </div>
        <div class="details" style="margin:25px; font-size: 12px; display: flex; justify-content: space-between;">
            <div style="flex: 1; margin-right: 10px;">
                <table class="table" style="width: 100%; border-collapse: collapse;" border="2px">
                    <thead>
                    <tr style="border-bottom: 1px solid black;">
                        <th scope="col" style="border: none;">@lang("$s_string_file.tax_summary")</th>
                        <th scope="col" style="border: none;"></th>
                        <th scope="col" style="border: none;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr style="border-bottom: 1px solid black;">
                        <td style="border: none;">@lang("$s_string_file.tax_name1")</td>
                        <td style="border: none;">@lang("$s_string_file.tax_percentage")</td>
                        <td style="border: none;">@lang("$s_string_file.tax_deduction")</td>
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                        <td style="border: none;">@lang("$s_string_file.tax_name")</td>
                        <td style="border: none;">{{$booking->tax_per}}%</td>
                        <td style="border: none;">{{$tax_amount}}</td>
                    </tr>
                    </tbody>
                </table>
                <hr>
                <table width="100%" style="padding: 0px 15px;margin:0;border-collapse: collapse;width: 100%;height: 50px;">
                    <tbody>

                    <tr>

                        <td style="padding:0;border-bottom: 2px solid #ddd;">

                            <table align="left" style="margin:0;max-width:100%">

                                <tbody>

                                <tr>

                                    <td style="border-bottom: none;padding:0">

                                        <table>

                                            <tbody>

                                            <tr>

                                                <td style="border-bottom: none;margin-right: 60px !important;padding:0px;"><p style="font-family: normal;">@lang("$s_string_file.get_app"):</p></td>

                                                <td style="border-bottom: none; word-break: break-word; padding-right: 1px; padding-left: 1px;"><a href="https://play.google.com/store/apps" target="_blank"><img alt="App Store" height="20" src="{{asset('/basic-images/android.png')}}" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="App Store" width="20"/></a></td>
                                                <td style="border-bottom: none; word-break: break-word; padding-right: 1px; padding-left: 1px;"><a href="https://appstoreconnect.apple.com/login" target="_blank"><img alt="Play Store" height="20" src="{{asset('/basic-images/ios.png')}}" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="Play Store" width="20"/></a></td>

                                            </tr>

                                            </tbody>

                                        </table>
                                        @if (!empty($temp->social_links))
                                            @php
                                                $social_links = get_object_vars(json_decode($temp->social_links));
                                                $social_links = $social_links['links'];
                                            @endphp
                                            <table align="right" style="margin:0; max-width:142px">
                                                <tbody>
                                                <tr>
                                                    <td style="border-bottom: none; padding:0">
                                                        <table>
                                                            <tbody>
                                                            <tr align="center" style="display: inline-block;">
                                                                @if(isset($social_links->facebook) && !empty($social_links->facebook))
                                                                    <td style="border-bottom: none; word-break: break-word; padding-right: 1px; padding-left: 1px;">
                                                                        <a class="text-dark" href="{{$social_links->facebook}}" target="_blank">
                                                                            <img alt="LinkedIn" height="20" src="{{asset('basic-images/facebook2x.png')}}" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="Facebook" width="24"/>
                                                                        </a>
                                                                    {{--                                                <a href="https://www.facebook.com/" target="_blank"><img alt="Facebook" height="20" src="https://delhitrial.apporioproducts.com/email/images/facebook2x.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="Facebook" width="24"/></a></td>--}}
                                                                @endif
                                                                @if(isset($social_links->twitter) && !empty($social_links->twitter))
                                                                    <td style="border-bottom: none; word-break: break-word; padding-right: 1px; padding-left: 1px;">
                                                                        <a class="text-dark" href="{{$social_links->twitter}}" target="_blank">
                                                                            <img alt="LinkedIn" height="20" src="{{asset('basic-images/twitter2x.png')}}" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="Twitter" width="24"/>
                                                                        </a>
                                                                        {{--                                                <a href="https://twitter.com/" target="_blank"><img alt="Twitter" height="20" src="https://delhitrial.apporioproducts.com/email/images/twitter2x.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="Twitter" width="24"/></a>--}}
                                                                    </td>
                                                                @endif
                                                                @if(isset($social_links->instagram) && !empty($social_links->instagram))
                                                                    <td style="border-bottom: none; word-break: break-word; padding-right: 1px; padding-left: 1px;">
                                                                        {{--                                                <a href="https://instagram.com/" target="_blank"><img alt="Instagram" height="20" src="https://delhitrial.apporioproducts.com/email/images/instagram2x.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="Instagram" width="24"/></a>--}}
                                                                        <a class="text-dark" href="{{$social_links->instagram}}" target="_blank">
                                                                            <img alt="LinkedIn" height="20" src="{{asset('basic-images/instagram2x.png')}}" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="Instagram" width="24"/>
                                                                        </a>
                                                                    </td>
                                                                @endif
                                                                @if(isset($social_links->linkedin) && !empty($social_links->linkedin))
                                                                    <td style="border-bottom: none; word-break: break-word; padding-right: 1px; padding-left: 1px;">
                                                                        <a class="text-dark" href="{{$social_links->linkedin}}" target="_blank">
                                                                            <img alt="LinkedIn" height="20" src="{{asset('basic-images/linkedin2x.png')}}" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="LinkedIn" width="24"/>
                                                                        </a>
                                                                        {{--                                                <a href="https://www.linkedin.com/" target="_blank"><img alt="LinkedIn" height="20" src="https://delhitrial.apporioproducts.com/email/images/linkedin2x.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="LinkedIn" width="24"/></a>--}}
                                                                    </td>
                                                                @endif
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        @endif
                                    </td>

                                </tr>

                                </tbody>

                            </table>

                        </td>

                    </tr>

                    </tbody>

                </table>

            </div>
            <div style="flex: 1;">
                <table class="table" style="width: 100%; border-collapse: collapse;" border="2px">
                    <thead>
                    <tr style="border-bottom: 1px solid black;">
                        <th scope="col" style="border: none;">@lang("$s_string_file.invoice_summary")</th>
                        <th scope="col" style="border: none;"></th>
                        <th scope="col" style="border: none;"></th>
                    </tr>
                    </thead>
                    @php
                        $tax_amount = 0;
                        if(!empty($booking->tax_after_dispute)){
                            $tax_amount = $booking->tax_after_dispute;
                        }
                        else{
                            if(!empty($booking->tax)){
                                $tax_amount = $booking->tax;
                            }
                        }
                        $final_amount = $booking->final_amount_paid;
                        $service_amount = ($final_amount - $tax_amount);
                        $currency = $booking->CountryArea->Country->isoCode;
                        $bidding_amount = $booking->bidding_amount;

                    @endphp
                    <tbody>
                    <tr style="border-bottom: 1px solid black;">
                        <td style="border: none;">@lang("$s_string_file.total")</td>
                        <td style="border: none;"></td>
                        <td style="border: none;">@if(!empty($bidding_amount)) {{$bidding_amount}} @else {{$service->total_amount}} @endif</td>
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                        <td style="border: none;">@lang("$s_string_file.total") @lang("$s_string_file.discount")</td>
                        <td style="border: none;"></td>
                        <td style="border: none;">{{!empty($booking->discount_amount) ? $booking->discount_amount : 0.00}}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                        <td style="border: none;">@lang("$s_string_file.total") @lang("$s_string_file.tax")</td>
                        <td style="border: none;"></td>
                        <td style="border: none;">{{$tax_amount}}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                        <td style="border: none;">@lang("$s_string_file.total") @lang("$s_string_file.Withheld")</td>
                        <td style="border: none;"></td>
                        <td style="border: none;">{{$tax_amount}}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                        <td style="border: none;">@lang("$s_string_file.grand_total")</td>
                        <td style="border: none;"></td>
                        <td style="border: none;">{{$final_amount}}</td>
                    </tr>

                    </tbody>
                </table>
            </div>
        </div>
        @php
            $email_var_1 = isset($additional_email_variables->email_variable_1) ? $additional_email_variables->email_variable_1 : "xx";
            $email_var_2 = isset($additional_email_variables->email_variable_2) ? $additional_email_variables->email_variable_2 : "2024";
        @endphp
        <div class="details"style="margin-left:25px; margin-right: 25px; vertical-align: middle; margin:0; text-align:center;font-weight:normal;">
            <p style="font-size:15px;padding-top:15px; padding-bottom:5px;color:#000000;margin:0">Processado por programa Certificado {{$email_var_1}}/AGT/{{$email_var_2}} AppContrato</p>
        </div>
        <div class="details"style="margin-left:25px; margin-right: 25px; vertical-align: middle; margin:0; text-align:center;font-weight:normal;">
            <p style="font-size:10px;padding-top:15px; padding-bottom:5px;color:#9b9b9b;margin:0">ﾃつｩ {{$booking->Merchant->BusinessName}}! . @lang("$s_string_file.all_right_reserved")</p>
            <p style="font-size:10px;padding-bottom:20px; color:#9b9b9b;margin:0">@lang("$s_string_file.terms_conditions") | @lang("$s_string_file.privacy_policy")</p>
        </div>
    </div>
</div>
</body></html>