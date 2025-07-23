@extends('merchant.layouts.main')
@section('content')
@php
    $merchant = get_merchant_id(false);
    $merchant_id = $merchant->id;
    $payment_config = \App\Models\PaymentConfiguration::firstOrCreate(['merchant_id' => $merchant_id])->first();
    $all_grocery_clone = \App\Models\Segment::where("sub_group_for_app",2)->get()->pluck("slag")->toArray();
    $all_food_clone = \App\Models\Segment::where("sub_group_for_app",1)->get()->pluck("slag")->toArray();
    $all_food_grocery_clone = \App\Models\Segment::whereIn("sub_group_for_app",[1,2])->get()->pluck("slag")->toArray();
    $grocery_clone = (count(array_intersect($merchant_segment, $all_grocery_clone)) > 0) ? true :false;
    $grocery_food_exist = (count(array_intersect($merchant_segment, $all_food_grocery_clone)) > 0) ? true :false;
    $food_clone = (count(array_intersect($merchant_segment, $all_food_clone)) > 0) ? true :false;
    // $grocery_clone = (in_array('GROCERY',$merchant_segment) || in_array('PHARMACY',$merchant_segment) || in_array('GAS_DELIVERY',$merchant_segment)|| in_array('WATER_TANK_DELIVERY',$merchant_segment)|| in_array('MEAT_SHOP',$merchant_segment)|| in_array('SWEET_SHOP',$merchant_segment)|| in_array('PAAN_SHOP',$merchant_segment)|| in_array('ARTIFICIAL_JEWELLERY',$merchant_segment) || in_array('GIFT_SHOP',$merchant_segment)|| in_array('CONVENIENCE_SHOP',$merchant_segment) || in_array('ELECTRONIC_SHOP',$merchant_segment) || in_array('WINE_DELIVERY',$merchant_segment) || in_array('FLOWER_DELIVERY',$merchant_segment) || in_array('PET_SHOP',$merchant_segment));
@endphp
<style>
        .config_size {
            font-size: 25px;
        }
        
        ul {
          list-style-type: none;
        }
    </style>
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                    </div>
                    <h3 class="panel-title">
                        <i class="icon wb-divst" aria-hidden="true"></i>
                        @lang("$string_file.check_merchant_configuration")
                    </h3>
                </header>
            </div>
        </div>
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="mr--10 ml--10">
                <div class="row" style="margin-right: 0rem;margin-left: 0rem">
                        @if(Auth::user('merchant')->hasAnyPermission('view_pricing_parameter','view_documents','view_vehicle_model','view_vehicle_make','view_countries','view_area','view_vehicle_type'))
                            <div class="col-12 col-md-12 col-sm-12">
                                <div class="panel panel-bordered">
                                    <div class="panel-heading">
                                        <div class="panel-actions">
                                        </div>
                                        <h3 class="panel-title">@lang("$string_file.mandatory_setup")</h3>
                                        <div class="float-right">
                                            <span class="badge badge-warning">@lang("$string_file.optional")</span>
                                            <span class="badge badge-danger">@lang("$string_file.not_required")</span>
                                            <span class="badge badge-success">@lang("$string_file.required")</span>
                                        </div>
                                    </div>
                                    <div class="panel-body">
                                        @foreach($mcht_config_array as $mcht_array)
                                            @php $check_mcht_priority = checkMerchantPriority($app_config,$grocery_clone,$food_clone,$merchant,$mcht_array,$merchant_segment_group,$merchant_segment) @endphp
                                            <ul>
                                                <li>
                                                    @if($mcht_array['slug'] == "service_area")
                                                        <div class="row col-md">
                                                            <a class="animsition-divnk" href="">
                                                                <span class="site-menu-title">{{$mcht_array['name']}}</span>
                                                            </a>
                                                            <div class="ml-3">
                                                                @if($check_mcht_priority == true)
                                                                    @if($mcht_array['priority'] == 'required')
                                                                        <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                    @elseif($mcht_array['priority'] == 'optional')
                                                                        <span class="badge badge-warning">@lang("$string_file.added")</span>
                                                                    @else
                                                                        <span class="badge badge-danger">@lang("$string_file.added")</span>
                                                                    @endif
                                                                @else
                                                                        <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <ul>
                                                            <li>
                                                                @foreach($merchant->CountryArea as $countryArea)
                                                                     <span>{{$countryArea->CountryAreaName}}</span>
                                                                     <ul>
                                                                        @if($countryArea->AreaCoordinates)
                                                                            <li>
                                                                                <div class="row">
                                                                                    <a href="" class="">@lang("$string_file.draw_map")</a>
                                                                                    <div class="ml-3">
                                                                                        @if(!empty($countryArea->AreaCoordinates))
                                                                                        <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                                        @else
                                                                                        <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                                        @endif
                                                                                        
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                            </li>
                                                                        @endif
                                                                        @if($segment_group_vehicle == true)
                                                                            <li>
                                                                                <div class="row">
                                                                                    <a href="" class="">@lang("$string_file.vehicle_configuration")</a>
                                                                                    <div class="ml-3">
                                                                                        @if(!empty($countryArea->VehicleType) && !empty($countryArea->VehicleDocuments) && !empty($countryArea->ServiceTypes))
                                                                                        <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                                        @else
                                                                                        <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                                        @endif
                                                                                        
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                            </li>
                                                                        @endif
                                                                        @if($segment_group_handyman == true)
                                                                            <li>
                                                                                <div class="row">
                                                                                    <a href="" class="">@lang("$string_file.handyman_configuration")</a>
                                                                                    @php $segmentGroupIds = array_column(Auth::user('merchant')->Segment->toArray(), 'segment_group_id'); @endphp
                                                                                    <div class="ml-3">
                                                                                        @if(!empty(in_array(2, $segmentGroupIds)) && !empty($countryArea->Documents))
                                                                                        <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                                        @else
                                                                                        <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                                        @endif
                                                                                        
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                            </li>
                                                                        @endif
                                                                        @if($category_vehicle_type_module == 1 && in_array('TAXI',$merchant_segment))
                                                                            <li>
                                                                                <div class="row">
                                                                                    <a href="" class="">@lang("$string_file.vehicle_type_categorization")</a>
                                                                                    <div class="ml-3">
                                                                                        @if(!empty($countryArea->Documents))
                                                                                        <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                                        @else
                                                                                        <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                                        @endif
                                                                                        
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                            </li>
                                                                        @endif
                                                                     </ul>
                                                                @endforeach
                                                            </li>
                                                        </ul>
                                                    @elseif($mcht_array['slug'] == "price_card")
                                                        <div class="row col-md">
                                                            <a class="animsition-divnk" href="">
                                                                <span class="site-menu-title">{{$mcht_array['name']}}</span>
                                                            </a>
                                                            <div class="ml-3">
                                                                @if($check_mcht_priority == true)
                                                                    @if($mcht_array['priority'] == 'required')
                                                                        <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                    @elseif($mcht_array['priority'] == 'optional')
                                                                        <span class="badge badge-warning">@lang("$string_file.added")</span>
                                                                    @else
                                                                        <span class="badge badge-danger">@lang("$string_file.added")</span>
                                                                    @endif
                                                                @else
                                                                        <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @if(in_array(1,$merchant_segment_group) || in_array(3,$merchant_segment_group))
                                                            <ul>
                                                                @if((in_array('TAXI',$merchant_segment) || in_array('DELIVERY',$merchant_segment) && Auth::user('merchant')->hasAnyPermission(['price_card_TAXI','price_card_DELIVERY'])))
                                                                        <li>
                                                                            <span>@lang("$string_file.taxi_and_logistics_services")</span>
                                                                            <ul>
                                                                                <li>
                                                                                    @if(count($merchant->Segment) > 0 && count($merchant->GetCountryArea) > 0 && count($merchant->VehicleType) > 0)
                                                                                        <div class="row">
                                                                                            <a class="animsition-link" href="">@lang("$string_file.for_user") & @lang("$string_file.driver")</a>
                                                                                            @if($check_mcht_priority == true)
                                                                                                <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                                            @else
                                                                                                <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                                            @endif
                                                                                        </div>
                                                                                    @endif
                                                                                </li>
                                                                            </ul>
                                                                        </li>
                                                                @endif
                                                                @if($grocery_food_exist && Auth::user('merchant')->hasAnyPermission($all_food_grocery_clone))
                                                                    <li>
                                                                        <span>@lang("$string_file.delivery_services")</span>
                                                                        <ul>
                                                                            <li>
                                                                                @if(count($merchant->Segment) > 0 && count($merchant->GetCountryArea) > 0 && count($merchant->VehicleType) > 0)
                                                                                    <li>
                                                                                        <div class="row">
                                                                                        <a class="animsition-link" href="">@lang("$string_file.for_driver")</a>
                                                                                        @if($check_mcht_priority == true)
                                                                                            <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                                        @else
                                                                                            <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                                        @endif
                                                                                        </div>
                                                                                    </li>
                                                                                    <li>
                                                                                        <div class="row">
                                                                                        <a class="animsition-link" href="">@lang("$string_file.for_user")</a>
                                                                                        @if($check_mcht_priority == true)
                                                                                            <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                                        @else
                                                                                            <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                                        @endif
                                                                                        </div>
                                                                                    </li>
                                                                                    
                                                                                @endif
                                                                            </li>
                                                                        </ul>
                                                                            
                                                                                
                                                                    </li>
                                                                    
                                                                @endif
                                                            </ul>
                                                            
                                                        @endif
                                                        
                                                    @else
                                                        @if($check_mcht_priority != null)
                                                            <div class="row col-md">
                                                                    <a class="animsition-divnk" href="">
                                                                        <span class="site-menu-title">{{$mcht_array['name']}}</span>
                                                                    </a>
                                                                    <div class="ml-3">
                                                                        @if($check_mcht_priority == true)
                                                                            @if($mcht_array['priority'] == 'required')
                                                                                <span class="badge badge-success">@lang("$string_file.added")</span>
                                                                            @elseif($mcht_array['priority'] == 'optional')
                                                                                <span class="badge badge-warning">@lang("$string_file.added")</span>
                                                                            @else
                                                                                <span class="badge badge-danger">@lang("$string_file.added")</span>
                                                                            @endif
                                                                        @else
                                                                                <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                                                        @endif
                                                                    </div>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </li>
                                            </ul>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                </div>
            </div>
        </div>
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="mr--10 ml--10">
                <div class="row" style="margin-right: 0rem;margin-left: 0rem">
                    @if($app_config->bons_bank_to_bank_pg_enable == 1)
                    <div class="col-12 col-md-12 col-sm-12">
                        <div class="panel panel-bordered">
                                    <div class="panel-heading">
                                        <div class="panel-actions">
                                        </div>
                                        <h3 class="panel-title">@lang("$string_file.bons_bank_to_bank_payment")</h3>
                                    </div>
                                    <div class="panel-body">
                        <form action="{{route('merchant.saveBonsBankQr',!empty($bonsQrPayment) ? $bonsQrPayment->id : "")}}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <!-- Bank Name -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="bank_name">@lang('bank_name') <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name" value="{{!empty($bonsQrPayment) ? $bonsQrPayment->BankName : old('bank_name') }}" required>
                                        @if ($errors->has('bank_name'))
                                            <span class="text-danger">{{ $errors->first('bank_name') }}</span>
                                        @endif
                                    </div>
                                </div>
                    
                                <!-- Account Name -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="account_name">@lang('account_name') <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="account_name" name="account_name" value="{{!empty($bonsQrPayment) ? $bonsQrPayment->AccountName : old('account_name') }}" required>
                                        @if ($errors->has('account_name'))
                                            <span class="text-danger">{{ $errors->first('account_name') }}</span>
                                        @endif
                                    </div>
                                </div>
                    
                                <!-- Upload Image -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="bank_qr_document">@lang("$string_file.upload_qr_image") <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control" id="bank_qr_document" name="bank_qr_document" @if(!empty($bonsQrPayment)) ? "" : required @endif>
                                        @if ($errors->has('bank_qr_document'))
                                            <span class="text-danger">{{ $errors->first('bank_qr_document') }}</span>
                                        @endif
                                    </div>
                                    
                                    @if(!empty($bonsQrPayment))
                                    <img src="{{ get_image($bonsQrPayment->qr_image, 'bons_qr_image',$merchant_id)  }}" style="width:50%; height:50%; ">
                                    @endif
                                </div>
                            </div>
                    
                            <!-- Submit Button -->
                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">@lang("$string_file.save")</button>
                            </div>
                        </form>
                        </div>
                        </div>
                </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection