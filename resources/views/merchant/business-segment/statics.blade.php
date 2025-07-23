@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" >
                            <a href="{{ route('merchant.business-segment',['slug'=>$slug]) }}">
                                <button type="button" class="btn btn-icon btn-success"style="margin:10px">
                                    <i class="wb-reply"></i>
                                </button>
                            </a>
                            @if(!empty($info_setting) && $info_setting->view_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                        data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title">
                        <i class="icon wb-list" aria-hidden="true"></i>
                        @lang("$string_file.product") & @lang("$string_file.order") @lang("common.statics")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <div class="row">
                        <!-- First Row -->
                        <div class="col-md-12"><u> @lang("$string_file.product") @lang("$string_file.order") @lang("common.statics") @lang('common.of') <strong>{{ $merchant_name }} </strong></u></div>
                        <div class="col-xl-3 col-md-6 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-success">
                                        <i class="icon wb-info"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400" >@lang("$string_file.products")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class=" font-weight-100">{{$summary['products']}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-warning">
                                        <i class="icon wb-shopping-cart"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400">@lang("$string_file.orders")</span>
                                    <div class="content-text text-center mb-0">
                                        {{--                                        <i class="text-danger icon wb-triangle-up font-size-20">--}}
                                        {{--                                        </i>--}}
                                        <span class="font-size-20 font-weight-100">{{$summary['orders']}}</span>
                                        {{--                                        <p class="blue-grey-400 font-weight-100 m-0">+45% From previous month</p>--}}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-danger">
                                        <i class="icon fa-dollar"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400 p-lg-60">@lang("$string_file.orders") @lang('common.amount') : {{isset($summary['income']['order_amount']) ? $summary['income']['order_amount'] : 0}}</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-14 font-weight-100 green-800">
                                            {{trans($string_file.'.business_segment').' '.trans("common.earning").':'}} {{!empty($summary['income']['store_earning']) ? $summary['income']['store_earning'] : 0 }} | </span>
                                        <span class="font-size-14 font-weight-100 blue-800">
                                            {{$merchant_name.' '.trans("common.earning").': '}} {{ !empty($summary['income']['merchant_earning']) ? $summary['income']['merchant_earning'] : 0}}  <br></span>
                                        <span class="font-size-14 font-weight-100 brown-800">
                                            {{trans($string_file.'.driver').' '.trans("common.earning").": "}}  {{ !empty($summary['income']['driver_earning']) ? $summary['income']['driver_earning'] : 0}}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <!-- End First Row -->

                    <!-- second Row -->
                    <div class="row">
                        <!-- First Row -->
                        <div class="col-md-12">
                            <div class="row">
                            <div class="col-md-5"><u>@lang("$string_file.product") @lang("$string_file.order") @lang("common.statics") @lang('common.of') <strong>{{ $title }} </strong></u></div>
                            <div class="col-md-6">
                                <form class="form-horizontal">
                                    <div class="form-group row">
                                        <label class="col-md-4 form-control-label">@lang("$string_file.business_segment"): </label>
                                        <div class="col-md-8">
                                            {!! Form::select('business_segment_id',add_blank_option($business_seg_list,trans("common.select")),old('business_segment_id',isset($id) ? $id : NULL),['id'=>'business_segment_id','class'=>'form-control','required'=>true]) !!}
                                        </div>
                                    </div>
                                </form>
                            </div>
                            </div>
                        </div>
                        @if(!empty($id))
                        <div class="col-xl-3 col-md-6 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-success">
                                        <i class="icon wb-info"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400">@lang("$string_file.products")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-20 font-weight-100">{{$business_summary['products']}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-warning">
                                        <i class="icon wb-shopping-cart"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400">@lang("$string_file.orders")</span>
                                    <div class="content-text text-center mb-0">
                                        {{--                                        <i class="text-danger icon wb-triangle-up font-size-20">--}}
                                        {{--                                        </i>--}}
                                        <span class="font-size-20 font-weight-100">{{$business_summary['orders']}}</span>
                                        {{--                                        <p class="blue-grey-400 font-weight-100 m-0">+45% From previous month</p>--}}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-danger">
                                        <i class="icon fa-dollar"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400 p-lg-60">@lang("$string_file.orders") @lang("common.amount") : {{isset($business_summary['income']['order_amount']) ? $currency.$business_summary['income']['order_amount'] : 0}}</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-14 font-weight-100 green-800">
                                            {{$title.' '.trans("common.earning").': '}} {{ !empty($business_summary['income']['store_earning']) ? $currency.$business_summary['income']['store_earning'] : 0 }} | </span>
                                        <span class="font-size-14 font-weight-100 blue-800">
                                            {{$merchant_name.' '.trans("common.earning").': '}} {{ !empty($business_summary['income']['store_earning']) ? $currency.$business_summary['income']['merchant_earning'] : 0}}  <br></span>
                                        <span class="font-size-14 font-weight-100 brown-800">
                                            {{trans($string_file.'.driver').' '.trans("common.earning").": "}} {{ !empty($business_summary['income']['driver_earning']) ? $currency.$business_summary['income']['driver_earning'] : 0}}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <hr>

                    <!-- Third Row -->
                    <!-- Third Left -->
                    <div class="row">
                        <div class="col-lg-12" id="ecommerceRecentOrder">
                            <div class="card card-shadow table-row">
                                {{--                                            <div class="card-header card-header-transparent py-20">--}}
                                {{--                                                <div class="btn-group dropdown">--}}
                                {{--                                                    <a href="#" class="text-body dropdown-toggle blue-grey-700" data-toggle="dropdown">@lang("common.all") @lang("$string_file.orders") </a>--}}
                                {{--                                                    <div class="dropdown-menu animate" role="menu">--}}
                                {{--                                                        <a class="dropdown-item" href="#" role="menuitem">Sales</a>--}}
                                {{--                                                        <a class="dropdown-item" href="#" role="menuitem">Total sales</a>--}}
                                {{--                                                        <a class="dropdown-item" href="#" role="menuitem">profit</a>--}}
                                {{--                                                    </div>--}}
                                {{--                                                </div>--}}
                                {{--                                            </div>--}}
                                <h4 class="example-title">@lang("common.all") @lang("$string_file.orders")</h4>
                                <div class="card-block bg-white table-responsive">
                                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>@lang("common.sn")</th>
                                            <th>@lang("$string_file.order") @lang("common.id")</th>
                                            <th>@lang("common.earning") @lang("common.details")</th>
                                            <th>@lang("common.payment") @lang("common.details")</th>
                                            <th>@lang("$string_file.product") @lang("common.details")</th>
                                            <th>@lang("common.user") @lang("common.details")</th>
                                            <th>@lang("common.created") @lang("common.at")</th>
                                            <th>@lang("$string_file.deliver") @lang("common.on")</th>
                                            <th>@lang("common.current") @lang("common.status")</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(!empty($arr_orders))
                                            @php $sr = $arr_orders->firstItem(); $user_name = ''; $user_phone = ''; $user_email = '';
                                                        $driver_name = '';$driver_email = '';
                                            @endphp
                                            @foreach($arr_orders as $order)
                                                @php
                                                     $user_name = is_demo_data($order->User->UserName,$order->Merchant);
                                                     $user_phone = is_demo_data($order->User->UserPhone,$order->Merchant);
                                                     $user_email = is_demo_data($order->User->email,$order->Merchant);
                                                     $currency = $order->CountryArea->Country->isoCode;
                                                     $tax_amount =    !empty($order->tax) ? $order->tax : 0;
                                                @endphp
                                                <tr>
                                                    <td>{{$sr}}</td>
                                                    <td>
                                                        <a href="{{route('driver.order.detail',$order->id)}}">{{ $order->merchant_order_id }}</a>
                                                    </td>
                                                    <td>
                                                        @if(!empty($order->OrderTransaction))
                                                            @php $transaction = $order->OrderTransaction;

                                                            @endphp
                                                            @lang("common.grand") @lang("common.total") :  {{ $currency.$order->final_amount_paid}} <br>
                                                            {{trans("$string_file.business_segment").' '.trans("common.earning").': '.$currency.$transaction->business_segment_earning }} <br>
                                                            {{trans("common.merchant").' '.trans("common.earning").': '.$currency.$transaction->company_earning}} <br>
                                                            {{trans("$string_file.driver").' '.trans("common.earning").": ". $currency.$transaction->driver_earning}}<br>
                                                        @endif
                                                    </td>
                                                    <td>

                                                        {{trans("common.mode").": ". $order->PaymentMethod->payment_method}}<br>
                                                        {{trans($string_file.".cart").' '.trans("common.amount"). ': '.$currency.$order->cart_amount}} <br>
                                                        {{trans("$string_file.delivery").' '.trans("common.charge").': '. $currency.$order->delivery_amount }} <br>
                                                        {{trans("common.tax").': '.$currency.$tax_amount  }} <br>
                                                        {{trans("common.tip").' '.trans("common.amount").': '.$currency.$order->tip_amount }} <br>
                                                        @lang("common.grand") @lang("common.total") :  {{ $currency.$order->final_amount_paid}}
                                                        <br>
                                                    </td>
                                                    <td>
                                                        @php $product_detail = $order->OrderDetail; $products = "";@endphp
                                                        @foreach($product_detail as $product)
                                                            @php $unit = isset($product->weight_unit_id) ? $product->WeightUnit->WeightUnitName : ""; @endphp
                                                            {{ $product->quantity.' '.$unit.' '.$product->Product->Name($order->merchant_id)}},<br>
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        {{$user_name}} <br>
                                                        {{$user_phone}} <br>
                                                        {{$user_email}} <br>
                                                    </td>

                                                    <td>
                                                        @lang("common.at") {{date('H:i a',strtotime($order->created_at))}}
                                                        <br>
                                                        {{date_format($order->created_at,'D, M d, Y')}}
                                                    </td>
                                                    <td> {{!empty($order->order_date) ? date('D, M d, Y',strtotime($order->order_date)) : ""}}</td>
                                                    <td style="text-align: center">
                                                        @if($order->order_status == 11)
                                                            <span class="badge badge-success font-weight-100">{{ $arr_status[$order->order_status] }}</span>
                                                        @elseif(in_array($order->order_status,[1,6,7,9,10]))
                                                            <span class="badge btn-info font-weight-100">{{ $arr_status[$order->order_status] }}</span>
                                                        @else
                                                            <span class="badge badge-danger font-weight-100">{{ $arr_status[$order->order_status] }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @php $sr++  @endphp
                                            @endforeach
                                        </tbody>
                                        @endif
                                    </table>
                                        @include('merchant.shared.table-footer', ['table_data' => $arr_orders, 'data' => []])
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#business_segment_id').change(function() {
                window.location ="{{route('merchant.business-segment.statics',[$slug])}}"+"/"+$(this).val();
            });
        });
    </script>
@endsection