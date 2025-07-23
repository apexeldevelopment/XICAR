    @extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
                        @include('merchant.shared.errors-and-messages')

            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        @if($export_permission)
                        <a href="{{route('merchant.handyman-booking-export',$arr_search)}}">
                            <button type="button" title="@lang("$string_file.export_bookings")"
                                    class="btn btn-icon btn-success" style="margin:10px">@lang("$string_file.export_bookings")
                                <i class="wb-download"></i>
                            </button>
                        </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="fa-user" aria-hidden="true"></i>
                        {{trans($string_file.'.handyman').' '.trans($string_file.'.booking').' '.trans("$string_file.management")}}
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! $search_view !!}
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.booking_id")</th>
                            <th>@lang("$string_file.user_details")</th>
                            <th>@lang("$string_file.driver_details")</th>
                            <th>@lang("$string_file.service_details")</th>
                            <th>@lang("$string_file.payment_details")</th>
                            <th>@lang("$string_file.earning_details")</th>
                            <th>@lang("$string_file.comments")</th>
                            <th>@lang("$string_file.service_area")</th>
                            <th>@lang("$string_file.current_status")</th>
                            <th>@lang("$string_file.service_date")</th>
                            <th>@lang("$string_file.booking_date")</th>
                            <th>@lang("$string_file.pickup")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $arr_orders->firstItem();
                        $user_name = ''; $user_phone = ''; $user_email = '';
                        $driver_name = '';$driver_email = '';
                        $arr_price_type = get_price_card_type("web","BOTH",$string_file);
                        @endphp
                        @foreach($arr_orders as $order)
                            @php
                                $currency = $order->CountryArea->Country->isoCode;
                            @endphp
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{ $order->merchant_order_id }}</td>
                                <td>
                                    {{ is_demo_data($order->User->UserName, $order->Merchant) }} <br>
                                    {{ is_demo_data($order->User->UserPhone, $order->Merchant) }} <br>
                                    {{ is_demo_data($order->User->email, $order->Merchant) }} <br>
                                </td>
                                <td>

                                    @if(!empty($order->Driver->id))
                                        {{ is_demo_data($order->Driver->first_name, $order->Merchant) }} 
                                        {{ is_demo_data($order->Driver->last_name, $order->Merchant) }} <br>
                                        {{ is_demo_data($order->Driver->phoneNumber, $order->Merchant) }} <br>
                                        {{ is_demo_data($order->Driver->email, $order->Merchant) }} <br>
                                    @else
                                        @lang("$string_file.not_assigned_yet")
                                    @endif
                                </td>
                                <td>
                                    @php $arr_services = []; $order_details = $order->HandymanOrderDetail;
                                        foreach($order_details as $details){
                                            $arr_services[] = $details->ServiceType->serviceName;
                                        }
                                    @endphp
                                    {{trans($string_file.".date").' : '.$order->booking_date}}
                                    <br>
                                    {{trans("$string_file.price_type").' : '}}
                                    {{isset($arr_price_type[$order->price_type]) ? $arr_price_type[$order->price_type] : ""}}
                                    <br>
                                    @if($order->price_type == 2)
                                        {{trans("$string_file.service_time").' : '}} {{ $order->total_service_hours}} @lang("$string_file.hour")
                                        <br>
                                    @endif
                                    {{trans("$string_file.service_type").' : '}}
                                    @foreach($order_details as $details)
                                        {{$details->ServiceType->serviceName}}, <br>
                                    @endforeach
                                    <br>
                                    @lang("$string_file.segment") : <strong>{{$order->Segment->name}}</strong>
                                    <br>
                                </td>
                                <td>
                                                                        {{trans("$string_file.mode").': '.$order->PaymentMethod->payment_method}} <br>
                                    @if($order->price_type == 2 && $order->order_status != 7 && $order->is_order_completed !=1)
                                        @php
                                            $cart_amount =  $order->hourly_amount.' '.trans("$string_file.hourly");
                                            $payment_message = trans("$string_file.handyman_order_payment");
                                        @endphp
                                        {{$currency.$cart_amount}}
                                        <br>
                                        <b>@lang("$string_file.note"):</b> {{$payment_message}}
                                    @else
                                        {{trans("$string_file.tax_percentage").': '}} {{$order->tax_per}} % <br>
                                        (@lang("$string_file.tax_included"))<br>
                                        
                                        @if(!isset($order->dispute_settled_amount))

                                            {{trans("$string_file.cart_amount").': '.$currency.' '.$order->cart_amount}}
                                            <br>
                                             {{trans("$string_file.discount").': '.$currency.' '.$order->discount_amount}}
                                            <br>
                                             {{trans("$string_file.tax").': '.$currency.' '.$order->tax}}
                                            <br>
                                            {{trans("$string_file.total_amount").': '.$currency.' '.$order->total_booking_amount}}
                                            <br>
                                            
                                            
                                            <b>{{trans("$string_file.final_amount_paid").': '.$currency.' '.$order->final_amount_paid}}</b>
                                            <br>
                                        
                                        @else
                                            {{trans("$string_file.previous_cart_amount").': '.$currency.' '.$order->cart_amount}}
                                            <br>
                                            {{trans("$string_file.discount").': '.$currency.' '.$order->discount_amount}}
                                            <br>
                                            {{trans("$string_file.dispute_settled_amount").': '.$currency.' '.$order->dispute_settled_amount}}
                                            <br> 
                                            {{trans("$string_file.tax").': '.$currency.' '.$order->tax_after_dispute}}
                                            <br>
                                            {{trans("$string_file.total_amount").': '.$currency.' '.$order->final_amount_paid}}
                                            <br>
                                            <b>{{trans("$string_file.final_amount_paid").': '.$currency.' '.$order->final_amount_paid}}</b>
                                            <br>
                                            
                                        @endif
                                        
                                        
                                    @endif
                                </td>
                                <td>
                                    @if($order->is_order_completed == 1 && !empty($order->HandymanOrderTransaction->id))
                                        @lang("$string_file.driver")
                                        : {{ $currency.$order->HandymanOrderTransaction->driver_earning }}
                                        <br>
                                        @lang("$string_file.merchant")
                                        : {{ $currency.$order->HandymanOrderTransaction->company_earning }}
                                    @endif
                                </td>
                                <td>
                                    <div class="row">
                                        {{$order->dispute_message}}
                                    </div>
                                    @php
                                    $images = [];
                                    if (!empty($order->dispute_images)) {
                                        if (is_string($order->dispute_images)) {
                                            $images = json_decode($order->dispute_images, true);
                                        } else if (is_array($order->dispute_images)) {
                                            $images = $order->dispute_images;
                                        }
                                    }
                                    @endphp
                                    <div class="container">
                                      <div class="row">
                                          @foreach($images as $image)
                                            <div class="col-md-4">
                                                <a href="{{get_image($image, 'booking_images', $order->merchant_id)}}" target="_blank"</a><i class="fa fa-picture-o" aria-hidden="true"></i></a>
                                            </div>
                                          @endforeach

                                      </div>
                                    </div>
                                </td>
                                <td> {{ $order->CountryArea->CountryAreaName }}</td>
                                <td style="text-align: center">
                                    {{ $arr_status[$order->order_status] }}<br>
                                    @if(!empty($order->order_otp)) <span class="badge badge-info">OTP: {{$order->order_otp}}</span> @endif
                                </td>
                                <td>{!! $order->booking_date !!}</td>
                                @php $created_at = convertTimeToUSERzone($order->created_at, $order->CountryArea->timezone,null, $order->Merchant); @endphp
                                <td>{!! $created_at !!}</td>
                                <td>
                                    <a title="{{ $order->drop_location }}" target="_blank"
                                       href="https://www.google.com/maps/place/{{ $order->drop_latitude}},{{$order->drop_longitude}}"
                                       class="btn btn-icon btn-danger ml-20">
                                        <i class="icon fa-tint"></i>
                                    </a>
                                </td>
                                <td>
                                    <a target="_blank" title="@lang("$string_file.order_details")"
                                       href="{{route('merchant.handyman.order.detail',$order->id)}}"
                                       class="btn btn-sm btn-info menu-icon btn_money action_btn"><span
                                                class="fa fa-info-circle"
                                                title="{{trans("$string_file.booking_details")}}"></span></a>
                                    @if($booking_config->handyman_booking_dispute == 1 && $order->order_status == 10 && empty($order->is_dispute))
                                        <button type="button" class="btn btn-sm btn-warning" id="dispute_action"
                                                data-toggle="modal" data-target="#disputeActionModal"  onclick="setOrderId('{{(float)$order->final_amount_paid-(float)$order->tax}}')"
                                                data-id="{{$order->id}}" title="@lang("$string_file.action")">
                                            <i class="fa fa-check"></i>
                                        </button>
                                    @endif

                                    @if(in_array($order->order_status, [4,6]) && $booking_config->handyman_admin_can_complete_booking == 1)
                                        <button type="button" class="btn btn-sm btn-success" id="complete_action"
                                                data-toggle="modal" data-target="#completActionModal"  onclick="setCompleteModalOrderId()"
                                                data-id="{{$order->id}}" title="@lang("$string_file.action")">
                                            <i class="fa fa-check"></i> <span>@lang("$string_file.complete") @lang("$string_file.order")</span>
                                        </button>
                                    @endif

                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $arr_orders, 'data' => $arr_search])
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="disputeActionModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">@lang("$string_file.dispute") @lang("$string_file.action")</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('order.dispute.action')}}" method="post">
                    @csrf
                    <div class="modal-body text-center">
                        <h3>@lang("$string_file.are_you_sure")</h3>
                        <h5>@lang("$string_file.dispute_action_warning")</h5>
                        <input type="hidden" name="order_id" id="order_id">
                        <div class="row" id="agreed_booking_amount_div" style="display: none;">
                        <div class="col-md-12" style="text-align: left;">
                            <label for="agreed_booking_amount">Agreed Booking Amount</label>
                            <input type="text" class="form-control" name="agreed_booking_amount" id="agreed_booking_amount" required>
                        </div>
                        </div>
                    
                        <br>
                        <div class="row">
                            <div class="col-md-12">
                                <select class="form-control" name="status" id="status" required  onchange="showBookingAmount()">
                                    <option value="">@lang("$string_file.select")</option>
                                    <option value="1">@lang("$string_file.approve")</option>
                                    <option value="2">@lang("$string_file.reject")</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang("$string_file.close")</button>
                        <button type="submit" class="btn btn-success">@lang("$string_file.submit")</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="completActionModal" tabindex="-1" role="dialog" aria-labelledby="completActionModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="completActionModalLongTitle">@lang("$string_file.complete")  @lang("$string_file.order")</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('handyman.order.mark-as-complete')}}" method="post">
                    @csrf
                    <div class="modal-body ">
                        <div class="text-center">
                            <h3>@lang("$string_file.are_you_sure")</h3>
                            <h5>@lang("$string_file.complete") @lang("$string_file.order")</h5>
                        </div>
                        <input type="hidden" name="order_id" id="order_id">
                        <br>

                        <div class="form-group">
                            <label for="exampleInputEmail1">@lang("$string_file.rate") @lang("$string_file.user") </label>
                            <select class="form-control" name="rating" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">@lang("$string_file.remarks")</label>
                            <textarea class="form-control" name="remarks" required></textarea>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="maintain_transaction" name="maintain_transaction">
                                <label class="form-check-label" for="maintain_transaction">
                                    @lang("$string_file.maintain_transaction")
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang("$string_file.close")</button>
                            <button type="submit" class="btn btn-success">@lang("$string_file.submit")</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script>
        // function setOrderId(){
        //     var order_id = $('#dispute_action').attr('data-id');
        //     console.log('order_id:' +order_id);
        //     $('#disputeActionModal #order_id').val(order_id);
        // }
        function setCompleteModalOrderId(){
            var order_id = $('#complete_action').attr('data-id');
            $('#completActionModal #order_id').val(order_id);
        }
        
        function setOrderId(total_amount){
            var order_id = $('#dispute_action').attr('data-id');
            console.log('order_id:' +order_id);
            $('#disputeActionModal #order_id').val(order_id);
            $('#agreed_booking_amount').val(total_amount);
        }
        
        function showBookingAmount(){
            var status = $('#status').val();
            console.log("status: "+status);
            if(status == "1"){
                $('#agreed_booking_amount_div').css('display', 'block')
            }
            else{
                $('#agreed_booking_amount_div').css('display', 'none')
            }
        }
        
    </script>
@endsection