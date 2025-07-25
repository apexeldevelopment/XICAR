<!DOCTYPE html>

<html>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
    <meta content="width=device-width" name="viewport"/>
    <meta content="IE=edge" http-equiv="X-UA-Compatible"/>
    <title></title>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css"/>

</head>
<body style="background-color: #f6f6f6; padding:20px">
<div class="container content-width" style="background-color: #ffffff;max-width: 700px;min-width:300px;margin: 20px auto;font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;">
    <div class="logo" style="margin-top:30px;text-align:center; padding:10px 0; ">
        <img align="center" width="100" height="100" src="{{get_image($order->Merchant->BusinessLogo,'business_logo',$order->merchant_id,true,true,"email")}}"/>
    </div>
    <div class="user-details" style="font-size: 13px; font-weight: 400;text-align: left;padding-left:25px; padding-right:25px;">
        <p style="border-bottom: 1px solid #ddd;"></p>
        <p>{{$order->User->first_name.' '.$order->User->last_name}},</p>
        <p>@lang("$string_file.mail_content_1") {{$order->Merchant->BusinessName}}! .@lang("$string_file.mail_content_2_mail_content_3")</p>
    </div>
    <div class="user-details" style="padding-left:25px; margin-right:25px;">
        <table style="margin:0;border-collapse: collapse;width: 100%;">
            <tbody>
            <tr>
                <td style="border-bottom: none;padding:0;">
                    <table align="left" style="margin:0;width:190px;max-width:100%;padding-bottom:10px;">
                        <tbody>
                        <tr>
                            <td style="border-bottom: none; padding:0;">
                                <p style="font-size: 13px; font-weight: 400; margin-bottom: 5px;">@lang("$string_file.order_no"):</p>
                                <h6 style="font-weight:900;font-size:14px;margin:0;">{{$order->merchant_order_id}}</h6>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <table align="right" style="margin:0;width:190px;max-width:100%;padding-bottom:10px;">
                        <tbody>
                        <tr>
                            <td style="border-bottom: none;padding:0; text-align: right;">
                                <p style="font-size: 13px; font-weight: 400; margin-bottom: 5px;">@lang("$string_file.f_cap_from"):</p>
                                <h6 style="font-weight:900;font-size:14px; margin:0;">{{$order->BusinessSegment->full_name}}</h6>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="user-details" style="padding-left:25px; margin-right:25px;">
        <table style="margin:0;width: 100%;">
            <tbody>
            <tr>
                <td style="border-bottom: none; padding:0;">
                    <p style="font-size: 13px; font-weight: 400; margin-bottom: 5px;">@lang("$string_file.order_placed_at"):</p>
                    <h6 style="font-weight:900;font-size:14px;margin:0;">
                        {{--{{convertTimeToUSERzone($order->created_at,$order->CountryArea->timezone,null,$order->Merchant)}}--}}
                        {{convertTimeToUSERzone($order->created_at,$order->CountryArea->timezone,null,
                        $order->Merchant)}}
                    </h6>
                </td>
                <td style="border-bottom: none; padding:0;">
{{--                    <p style="font-size: 13px; font-weight: 400; margin-bottom: 5px;">@lang('api.order_delivered_at'):</p>--}}
{{--                    <h6 style="font-weight:900;font-size:14px;margin:0;">Sunday, June 21, 2020 8:39 PM</h6>--}}
                </td>
                <td style="border-bottom: none;padding:0; text-align: right;">
                    <p style="font-size: 13px; font-weight: 400; margin-bottom: 5px;">@lang("$string_file.order_status"):</p>
                    <h6 style="font-weight:900;font-size:14px; margin:0;color: #79b33b;">@lang("$string_file.delivered")</h6>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="details" style="margin-left:25px; margin-right: 25px;margin-top:25px;font-size: 12px; font-weight: bold;">
        <table style="border-collapse: collapse;width: 100%;">
            <thead style="background: #e9e9e9; line-height: 1.2em;">
            <tr>
                <th style="text-align: left;padding: 15px;">@lang("$string_file.sn")</th>
                <th style="text-align: left;padding: 15px;">@lang("$string_file.item_name")</th>
                <th style="text-align: left;padding: 15px;">@lang("$string_file.quantity")</th>
                <th style="text-align: right;padding: 15px;">@lang("$string_file.price")</th>
                <th style="text-align: right;padding: 15px;">@lang("$string_file.total")</th>
            </tr>
            </thead>
            <tbody>
            @php $sn = 1; $currency = $order->CountryArea->Country->isoCode; @endphp
            @foreach($order->OrderDetail as $product)
            <tr style="border-bottom: 2px solid #ddd;">
                <td style="padding: 15px;">{{$sn}}</td>
                <td style="padding: 15px;">{{$product->Product->Name($order->merchant_id)}}</td>
                <td style="padding: 15px;">{{$product->quantity}}</td>
                <td style="text-align: right; padding: 15px;">{{$currency.$product->price}}</td>
                <td style="text-align: right; padding: 15px;">{{$currency.$product->total_amount}}</td>
            </tr>
            @php $sn++; @endphp
            @endforeach
            <tr>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;"  colspan="3"></td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;">@lang("$string_file.sub_total") </td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;padding-right:15px;">{{$currency.$order->cart_amount}}</td>
            </tr>
            <tr>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;" colspan="3"></td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;">@lang("$string_file.discount")</td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;padding-right:15px;">{{$currency.$order->discount_amount}}</td>
            </tr>
            <tr>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;" colspan="3"></td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;">@lang("$string_file.delivery_charge") </td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;padding-right:15px;">{{$currency.$order->delivery_amount}}</td>
            </tr>
            <tr>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;" colspan="3"></td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;">@lang("$string_file.tax")</td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;padding-right:15px;">{{$currency.$order->tax}}</td>
            </tr>
            <tr style="background: #f9f9f9;">
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px;" colspan="3"></td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px; color: #79b33b;">@lang("$string_file.grand_total")</td>
                <td style="text-align: right; border-bottom: none; padding-top: 5px; padding-bottom: 5px; color: #79b33b;padding-right:15px;">{{$currency.$order->final_amount_paid}}</td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="details" style="margin-left:25px; margin-right: 25px; font-size: 12px;">
        <table style="margin:0;border-collapse: collapse;width: 100%;">
            <tbody>
            <tr>
                <td style="padding-top:15px; padding-left: 0; border-bottom: 2px solid #ddd;">
                    <table align="left" style="margin:0;width:190px;max-width:100%;padding-bottom:10px;">
                        <tbody>
                        <tr>
                            <td style="border-bottom: none; padding:0;">
                                <h6 style="margin:0;margin-bottom:5px;font-weight:900;font-size:14px;">@lang("$string_file.delivery_address"):</h6>
                                <p style="margin:0;font-weight:normal;line-height:1.6">{{$order->drop_location}}
{{--                                    772, Sector 31, Gurugram,<br> Haryana 122001, India<br> Gurugram--}}
                                </p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
{{--                    <table align="left" style="margin:0;width:190px;max-width:100%;padding-bottom:10px;">--}}
{{--                        <tbody>--}}
{{--                        <tr>--}}
{{--                            <td style="border-bottom: none;padding:0;">--}}
{{--                                <h6 style="margin:0;margin-bottom:5px;font-weight:900;font-size:14px;">Landmark:</h6>--}}
{{--                                <p style="margin:0;font-weight:normal;line-height:1.6">Near Mother Dairy</p>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="details" style="margin-left:25px; margin-right: 25px;vertical-align: middle;font-weight:normal;">
        <table width="100%" style="padding:0 15px;margin:0; border-collapse: collapse;width: 100%;">
            <tbody>
            <tr>
                <td style="padding:0;border-bottom: 2px solid #ddd;">
                    <table align="left" style="margin:0;max-width:140px">
                        <tbody>
                        <tr>
                            <td style="border-bottom: none;padding:0">
                                <table>
                                    <tbody>
                                    <tr>
                                        <td style="border-bottom: none; padding:0px;"><p style="font-family: normal;">@lang("$string_file.get_app"):</p></td>
                                        <td style="border-bottom: none; word-break: break-word; padding-right: 1px; padding-left: 1px;"><a href="#"><img alt="App Store" height="20" src="{{asset('basic-images/android.png')}}" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="App Store" width="20"/></a></td>
                                        <td style="border-bottom: none; word-break: break-word; padding-right: 1px; padding-left: 1px;"><a href="#"><img alt="Play Store" height="20" src="{{asset('basic-images/ios.png')}}" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: 0; display: block;" title="Play Store" width="20"/></a></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
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
    </div>
    <div class="details"style="margin-left:25px; margin-right: 25px; background-color:#fbfbfb;vertical-align: middle; margin:0; text-align:center;font-weight:normal;">
        <p style="font-size:10px;padding-top:15px; padding-bottom:5px;color:#9b9b9b;margin:0">Â© {{$order->Merchant->BusinessName}}! . @lang("$string_file.all_right_reserved")</p>
        <p style="font-size:10px;padding-bottom:20px; color:#9b9b9b;margin:0">@lang("$string_file.terms_conditions") | @lang("$string_file.privacy_policy")</p>
    </div>
</div>
</body>
</html>