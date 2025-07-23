@extends('merchant.layouts.main')
@section('content')
    <div class="modal fade bd-example-modal-lg " id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="exampleModalLabel">{{trans($string_file.'.handyman').' '.trans($string_file.'.bidding').' '.trans("$string_file.management")}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.phone")</th>
                            <th>@lang("$string_file.amount")</th>
                            <th>@lang("$string_file.description")</th>
                            <th>@lang("$string_file.status")</th>
                        </tr>
                        </thead>
                        <tbody id="modal_table_body">

                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">@lang("$string_file.close")</button>
                    <!--<button type="button" class="btn btn-primary">@lang("$string_file.close")</button>-->
                </div>
            </div>
        </div>
    </div>

    <div class="page">
        <div class="page-content">
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
                    <h3 class="panel-title"><i class="fa-user" aria-hidden="true"></i>
                        {{trans($string_file.'.handyman').' '.trans($string_file.'.bidding').' '.trans("$string_file.management")}}
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! $search_view !!}
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.order") @lang("$string_file.id")</th>
                            <th>@lang("$string_file.user_details")</th>
                            <th>@lang("$string_file.images")</th>
                            <th>@lang("$string_file.service_details")</th>
                            <th>@lang("$string_file.bidding") @lang("$string_file.details")</th>

                            <th>@lang("$string_file.booking")</th>
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

                                    @if(!empty($order->upload_images))
                                        @php
                                            $arr_image = json_decode($order->upload_images);
                                        @endphp

                                        @foreach($arr_image as $image)
                                            <a href="{{get_image($image,"booking_images",$order->merchant_id)}}"
                                               target="_blank">
                                                <button class="btn btn-icon btn-warning"
                                                        style="margin:10px" type="button">
                                                    <i class="icon fa fa-picture-o ml-1 mr-1" title="Info" style=""></i>
                                                </button>
                                            </a>


                    @endforeach
                </div>
                @endif


                </td>
                <td>
                    <!--segments-->
                    @inject('service_type', 'App\Models\ServiceType')
                    @php $arr_services = json_decode($order->ordered_services);
                                        $service_type_ids = [];
                                        $day = "";

                                        if(!empty($order->ServiceTimeSlotDetail)){
                                            switch($order->ServiceTimeSlotDetail->ServiceTimeSlot->day){
                                                case 0:
                                                    $day = "Sunday";
                                                    break;
                                                case 1:
                                                    $day = "Monday";
                                                    break;
                                                case 2:
                                                    $day = "Tuesday";
                                                    break;
                                                case 3:
                                                    $day = "Wednesday";
                                                    break;
                                                case 4:
                                                    $day = "Thrusday";
                                                    break;
                                                case 5:
                                                    $day = "Friday";
                                                    break;
                                                case 6:
                                                    $day = "Saturday";
                                                    break;
                                                default:
                                                    $day = "";
                                            }
                                        }

                                        foreach($arr_services as $item){
                                            array_push($service_type_ids, $item->service_type_id);
                                        }

                                        $services= $service_type->whereIn('id',$service_type_ids)->get();
                    @endphp

                    <strong>@lang("$string_file.service_type") :</strong>
                    @foreach($services as $details)
                        {{$details->serviceName}}, <br>
                    @endforeach
                    <br>
                    <strong>@lang("$string_file.segment") : </strong>{{$order->Segment->name}}
                    <br>
                    <strong>@lang("$string_file.description") :</strong> <span
                            style="text-wrap:wrap; word-wrap: break-word; overflow-wrap: break-word;">{{$order->description}}</span>
                    <br>
                    <strong>@lang("$string_file.payment") @lang("$string_file.mode")
                        :</strong> {{$order->PaymentMethod->payment_method}}
                    <br>
                    <strong>@lang("$string_file.slot") @lang("$string_file.details")
                        : </strong> {{$day}} {{$order->ServiceTimeSlotDetail->slot_time_text}} <br>
                    <strong>@lang("$string_file.booking_date") : </strong> {!! $order->booking_date !!}
                </td>


                <td>
                    @php
                        $drivers = $order->AllReceivedDrivers;
                        $data = [];

                        foreach ($drivers as $driver) {
                            $driverData = [
                                'driver_id'=>$driver->id,
                                'bidding_order_id'=>$order->id,
                                'first_name' => $driver->first_name,
                                'last_name' => $driver->last_name,
                                'phone'=>$driver->phoneNumber,
                                'amount_str' => trans("$string_file.amount"),
                                'amount' => $driver->pivot->amount,
                                'status' => $driver->pivot->status,
                                'description' => isset($driver->pivot->description)? $driver->pivot->description : "----------",
                            ];

                            $data[] = $driverData;
                        }

                        $data = json_encode([$data, $order->order_status]);
                    @endphp


                    <button class="btn btn-icon btn-primary"
                            style="margin:10px" data-target="#exampleModal"
                            onclick='showDetails({{$data}} )' data-toggle="modal" type="button">
                        <i class="wb-users ml-1 mr-1" title="Info" style=""></i>
                    </button>

                    <a href="https://www.google.com/maps/place/{{$order->drop_latitude}},{{$order->drop_longitude}}"
                       target="_blank">
                        <button class="btn btn-icon btn-danger"
                                style="margin:10px" type="button">
                            <i class="icon wb-map ml-1 mr-1" title="Info" style=""></i>
                        </button>
                    </a>


                </td>


                <td>
                    @if(!empty($order->handyman_order_id))

                        <a target="_blank" title="@lang("$string_file.order_details")"
                           href="{{route('merchant.handyman.order.detail',$order->handyman_order_id)}}"
                           class="">
                            <button class="btn btn-icon btn-success"
                                    style="margin:10px" type="button">
                                <i class="wb-users ml-1 mr-1" title="Info"
                                   style=""> @lang("$string_file.booking_details")</i>
                            </button>
                        </a>
                    @elseif(!empty($order->cancel_reason_id))
                        <a title=""
                           href="#"
                           class="">
                            <span class="badge badge-danger"
                                  style="margin:10px; padding: 0.6rem; ">@lang("$string_file.cancelled") : {{$order->CancelReason->ReasonName}}</span><br>
                            {{--                                            <button type="button" class="btn btn-danger"--}}
                            {{--                                                    style="margin:10px; border-radius: 20px;"></button>--}}
                        </a>
                    @else
                        <a title=""
                           href="{{route('handyman.bidding.manual.assign', $order->id)}}"
                           class="">
                            <span class="badge badge-info"
                                  style="margin:10px; padding: 0.7rem; ">@lang("$string_file.manual") @lang("$string_file.assign")</span>

                        </a>
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

    <div class="modal fade" id="disputeActionModal" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="exampleModalLongTitle">@lang("$string_file.dispute") @lang("$string_file.action")</h5>
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
                                <input type="text" class="form-control" name="agreed_booking_amount"
                                       id="agreed_booking_amount" required>
                            </div>
                        </div>

                        <br>
                        <div class="row">
                            <div class="col-md-12">
                                <select class="form-control" name="status" id="status" required
                                        onchange="showBookingAmount()">
                                    <option value="">@lang("$string_file.select")</option>
                                    <option value="1">@lang("$string_file.approve")</option>
                                    <option value="2">@lang("$string_file.reject")</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">@lang("$string_file.close")</button>
                        <button type="submit" class="btn btn-success">@lang("$string_file.submit")</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>



    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        // function setOrderId(){
        //     var order_id = $('#dispute_action').attr('data-id');
        //     console.log('order_id:' +order_id);
        //     $('#disputeActionModal #order_id').val(order_id);
        // }


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

        function showDetails(data){
            // console.log(data);
            let tr= "";


            let order_status = data[1];
            data[0].forEach(function(d){

                let status = ""
                if(d.status == "0"){
                     status = '<button type="button" class="btn btn-warning">Pending</button>';
                }
                else if(d.status == "1"){
                     status = '<button type="button" class="btn btn-primary">Created</button>';
                }
                else if(d.status == "2"){
                    status = '<button type="button" class="btn btn-info">Processed</button>';
                }
                else if(d.status == "3"){
                     status = '<button type="button" class="btn btn-danger">Cancelled</button>';
                }
                else if(d.status == "4"){
                    status = '<button type="button" class="btn btn-success">Finalized</button>';
                }

                let amount = "";
                if(d.amount && (order_status == "1") ){

                    amount = d.amount_str+" "+"<input type='number' class='form-control' name='bidding_"+d.id+"' id='bidding_"+d.id+"' value='"+d.amount+"'  onblur='setBiddingQuote(this, "+d.bidding_order_id+", "+d.driver_id+")' >";
                }
                else if(d.amount && (order_status != "1")){
                     amount = d.amount_str +" "+d.amount;
                }


                let str = `
                              <tr>
                                <td>${d.first_name}  ${d.last_name}</td>
                                <td>
                                    ${d.phone}
                                </td>
                                <td>
                                    ${amount}
                                </td>
                                <td>${d.description}</td>
                                <td>${status}</td>
                              </tr>
                        `;
                tr+=str;
            })
            document.getElementById("modal_table_body").innerHTML= tr;
        }



        function setBiddingQuote(elem, id, driver){
        console.log(elem.value, id)
            var token = $('[name="_token"]').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                type: "POST",
                data: {
                    id: id,
                    price: elem.value,
                    driver_id: driver
                },
                url: "{{ route('handyman.bidding.update.quoted.price') }}",
            }).done(function (data) {
                // console.log(data);
                swal({
                    title: "@lang("$string_file.success")",
                    text: "@lang("$string_file.amount") @lang("$string_file.successfully") @lang("$string_file.updated")",
                    type: "success",
                });
                location.reload();
            }).fail(function (jqXHR, textStatus, errorThrown) {
                swal({
                    title: "Error",
                    text: data.message,
                    type: "error",
                });
            });

    }

    </script>
@endsection