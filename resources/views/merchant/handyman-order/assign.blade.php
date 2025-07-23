@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                    </div>
                    <h3 class="panel-title"><i class="fa-user" aria-hidden="true"></i>

                        @lang("$string_file.manual") @lang("$string_file.assign")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','id'=>'driver_assign','url'=>route('handyman-store.order.assign-to-driver')]) !!}
                    {!! Form::hidden('order_id',$order->id) !!}
                    {!! Form::hidden('order_status',$order->order_status) !!}
                    {!! Form::hidden('driver_id', '', ['id' => 'driver_id']) !!}
                    {!! Form::hidden('distance', '', ['id' => 'distance']) !!}
                    {!! Form::hidden('bid_amount', '', ['id' => 'bid_amount']) !!}
                    {!! Form::close() !!}
                    <h5>@lang("$string_file.order") : - </h5>

                    <div class="row p-4 mb-2 bg-blue-grey-100 ml-15 mr-15">
                        <div class="col-md-3">
                            <strong>  @lang("$string_file.order") @lang("$string_file.id"): </strong> :
                            #{{ $order->merchant_order_id }}<br>
                            <br>

                        </div>
                        <div class="col-md-3">
                            <strong>@lang("$string_file.payment") @lang("$string_file.mode")</strong>
                            : {{$order->PaymentMethod->payment_method}}

                            <br>
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
                            <strong>@lang("$string_file.slot") @lang("$string_file.details")
                                : </strong> {{$day}} {{$order->ServiceTimeSlotDetail->slot_time_text}} <br>
                            <strong>@lang("$string_file.booking_date") : </strong> {!! $order->booking_date !!}

                            <br>
                        </div>
                        <div class="col-md-5">
                            <strong> @lang("$string_file.user_details") </strong>
                            : {!! is_demo_data($order->User->first_name,$order->Merchant).' '.is_demo_data($order->User->last_name,$order->Merchant)!!}
                            <br>
                            <strong> @lang("$string_file.location") </strong>
                            : {!! $order->drop_location!!}
                            <br><br>
                            <button class="btn btn-info" id="order_status"></button>
                        </div>


                    </div>
                    <h5>@lang("$string_file.drivers") : - </h5>
                    <table class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.assign")</th>
                            <th>@lang("$string_file.amount")</th>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.estimate_distance")</th>
                            <th>@lang("$string_file.rating")</th>
                        </tr>
                        </thead>
                        <tbody id="assign_table">

                        </tbody>

                    </table>

                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        getNearestOnlineDriver("{{$order->id}}", "{{$order->merchant_id}}")
        function assignDriver(driver_id, distance){
            $('#driver_id').val(driver_id);
            $('#distance').val(distance);
            $('#driver_assign').submit();
        }

        function getNearestOnlineDriver(id, merchant_id){
            var token = $('[name="_token"]').val();
            $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    type: "POST",
                    data: {
                        id: id,
                        merchant_id: merchant_id
                    },
                    url: "{{ route('handyman.get.nearest.provider') }}",
                }).done(function (data) {
                    document.getElementById('order_status').innerHTML=data.order_status;
                    if(data.status == 2){
                        document.getElementById('order_status').className = 'btn btn-success';
                    }
                    if(data.status == 3){
                        document.getElementById('order_status').className = 'btn btn-danger';
                    }
                    if(data.status == 1){
                        let tbody = document.getElementById('assign_table');
                        tbody.innerHTML = "";
                        data.arr_drivers.forEach(driver =>{
                            let row = document.createElement('tr');

                            let assign_btn = document.createElement('td');
                            assign_btn.innerHTML = `<input type="button" value='assign' class='btn btn-primary' onclick='assignDriver("${driver.id}", "${driver.distance}")'>`;
                            row.appendChild(assign_btn);

                            let bidding = document.createElement('td');
                            bidding.innerHTML = `<input type="number" class="form-control" value='0.00' class='btn btn-primary' name="bidding_amount_${driver.id}" id="bidding_amount_${driver.id}" >`;
                            row.appendChild(bidding);

                            let name_cell = document.createElement('td');
                            name_cell.textContent = driver.first_name+" "+driver.last_name;
                            row.appendChild(name_cell);

                            let estimate_distance = document.createElement('td');
                            estimate_distance.textContent = driver.distance + " " + '@lang("$string_file.km")';
                            row.appendChild(estimate_distance);

                            let rating = document.createElement('td');
                            rating.textContent = (driver.rating) ? driver.rating : " "+ '@lang("$string_file.not_rated_yet")';
                            row.appendChild(rating);

                            tbody.appendChild(row);

                        });

                    }

                }).fail(function (jqXHR, textStatus, errorThrown) {
                    swal({
                        title: "Error",
                        text: data.message,
                        type: "error",
                    });
                });
        }
        setInterval(function() {
            getNearestOnlineDriver("{{$order->id}}", "{{$order->merchant_id}}");
        }, 30000);

        function assignDriver(driver_id, distance){
            $('#driver_id').val(driver_id);
            $('#distance').val(distance);
            let amount = $("#bidding_amount_"+driver_id).val()
            $('#bid_amount').val(amount);
            $('#driver_assign').submit();
        }

    </script>
@endsection
