@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" >
{{--                            <a href="{{ route('merchant.business-segment',['slug'=>$slug]) }}">--}}
{{--                                <button type="button" class="btn btn-icon btn-success"style="margin:10px">--}}
{{--                                    <i class="wb-reply"></i>--}}
{{--                                </button>--}}
{{--                            </a>--}}
                            @if(Auth::user('merchant')->demo != 1)
                            <a href="{{route('excel.merchant.orders',$arr_search)}}">
                                <button type="button" title="@lang("$string_file.export_excel")"
                                        class="btn btn-icon btn-success float-right"  style="margin:10px"><i class="fa fa-download"></i>
                                </button>
                            </a>
                            @endif
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
                        @lang("$string_file.all_orders")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <!-- End First Row -->

                    <!-- second Row -->
                    <div class="row">
                        <!-- First Row -->
                    </div>
                         {!! $search_view !!}
                    <hr>

                    <!-- Third Row -->
                    <!-- Third Left -->
                    <div class="row">
                        <div class="col-lg-12" id="ecommerceRecentOrder">
                            <div class="card card-shadow table-row">
                                <div class="card-block bg-white table-responsive">
                                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>@lang("$string_file.sn")</th>
                                            <th>@lang("$string_file.order_id")</th>
                                            <th>@lang("$string_file.earning_details")</th>
                                            <th>@lang("$string_file.payment_details")</th>
                                            <th>@lang("$string_file.product_details")</th>
                                            <th>@lang("$string_file.store_details")</th>
                                            <th>@lang("$string_file.user_details")</th>
                                            <th>@lang("$string_file.deliver_on")</th>
                                            <th>@lang("$string_file.current_status")</th>
                                            <th>@lang("$string_file.created_at")</th>
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

                                                     $store_name = is_demo_data($order->BusinessSegment->full_name,$order->Merchant);
                                                     $store_phone = is_demo_data($order->BusinessSegment->phone_number,$order->Merchant);
                                                     $store_email = is_demo_data($order->BusinessSegment->email,$order->Merchant);
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
                                                            @lang("$string_file.grand_total") :  {{ $currency.$order->final_amount_paid}} <br>
                                                            {{trans("$string_file.store_earning").': '.$currency.$transaction->business_segment_earning }} <br>
                                                            {{trans("$string_file.merchant_earning").': '.$currency.$transaction->company_earning}} <br>
                                                            {{trans("$string_file.driver_earning").": ". $currency.$transaction->driver_earning}}<br>
                                                        @endif
                                                    </td>
                                                    <td>

                                                        {{trans("$string_file.mode").": ". $order->PaymentMethod->payment_method}}<br>
                                                        {{trans($string_file.".cart_amount"). ': '.$currency.$order->cart_amount}} <br>
                                                        {{trans("$string_file.delivery_charge").': '. $currency.$order->delivery_amount }} <br>
                                                        {{trans("$string_file.tax").': '.$currency.$tax_amount  }} <br>
                                                        {{trans("$string_file.tip").': '.$currency.$order->tip_amount }} <br>
                                                        {{trans("$string_file.discount").': '.$currency.$order->discount_amount }} <br>
                                                        @lang("$string_file.grand_total") :  {{ $currency.$order->final_amount_paid}}
                                                        <br>
                                                    </td>
                                                    <td>
                                                        @php $product_detail = $order->OrderDetail; $products = "";@endphp
                                                        @foreach($product_detail as $product)
                                                             @php $weight =  isset($product->ProductVariant->weight) ? $product->ProductVariant->weight : "";
                                                             $unit = isset($product->weight_unit_id) ? $product->WeightUnit->WeightUnitName : "";
                                                             $unit = !empty($weight)  ? $product->quantity.' x '.$weight.' '.$unit : $product->quantity.$unit;
                                                         @endphp
                                                            {{ $product->quantity.' '.$unit.' '.$product->Product->Name($order->merchant_id)}},<br>
                                                            @if($product->empty_bottle_quantity > 0)
                                                            <strong>Empty Bottle : </strong><br>
                                                            {{$product->empty_bottle_quantity}} x {{$currency.$product->empty_bottle_price}}
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        {{$store_name}} <br>
                                                        {{$store_phone}} <br>
                                                        {{$store_email}} <br>
                                                    </td>
                                                    <td>
                                                        {{$user_name}} <br>
                                                        {{$user_phone}} <br>
                                                        {{$user_email}} <br>
                                                    </td>
                                                    <td>
                                                        {!! $order->order_date !!}
                                                    </td>
                                                    <td style="text-align: center">
                                                        @if($order->order_status == 11)
                                                            <span class="badge badge-success font-weight-100">{{ $arr_status[$order->order_status] }}</span>
                                                        @elseif(in_array($order->order_status,[1,6,7,9,10]))
                                                            <span class="badge btn-info font-weight-100">{{ $arr_status[$order->order_status] }}</span>
                                                        @else
                                                            <span class="badge badge-danger font-weight-100">{{ $arr_status[$order->order_status] }}</span>
                                                        @endif

                                                            @if($order->delivery_mode == 1  &&  !empty($order->delivery_image) )
                                                                <button class='btn btn-primary' onclick='openWindow("{{ get_image($order->delivery_image, 'booking_images', $order->merchant_id) }}")'>View Deliverd Image</button>
                                                            @endif
                                                    </td>
                                                    <td>
                                                        {!! $order->created_at !!}
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
{{--        $(document).ready(function () {--}}
{{--            $('#business_segment_id').change(function() {--}}
{{--                window.location ="{{route('merchant.business-segment.statistics',[$slug])}}"+"/"+$(this).val();--}}
{{--            });--}}
{{--        });--}}


function openWindow(url) {
    var imageSrc = 'https://static5.cargurus.com/images/site/2009/10/24/14/42/2004_suzuki_vitara_4_dr_lx_4wd_suv-pic-8731393806365188898-640x480.jpeg';

    // Create a temporary image element to get the actual dimensions of the image
    var tempImg = new Image();
    tempImg.src = imageSrc;

    // Once the image loads, get its dimensions and open the popup window
    tempImg.onload = function() {
        var imageWidth = this.width;
        var imageHeight = this.height;

        // Calculate popup window size based on image dimensions
        var popW = imageWidth + 50; // Add some padding for scrollbars, etc.
        var popH = imageHeight + 50; // Add some padding

        // Center the popup window on the screen
        var w = window.innerWidth;
        var h = window.innerHeight;
        var leftPos = (w - popW) / 2;
        var topPos = (h - popH) / 2;

        // Open the popup window and load the image
        var msgWindow = window.open('', 'popup', 'width=' + popW + ',height=' + popH + ',top=' + topPos + ',left=' + leftPos + ',scrollbars=yes');
        msgWindow.document.body.innerHTML = '<img src="' + imageSrc + '" style="max-width: 100%; max-height: 100%; display: block; margin: auto;">';
    };
}

    </script>
@endsection