@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route("merchant.bus_booking.active.index")}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="fa fa-reply"></i>
                            </button>
                        </a>
                        @if(empty($bus_booking->driver_id))
                            <button type="button" data-toggle="modal" data-target="#assign_booking"
                                    class="btn btn-icon btn-info float-right" style="margin:10px">
                                @lang("$string_file.manual_booking_assign")
                            </button>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="fa-info-circle" aria-hidden="true"></i>
                        @lang("$string_file.ride_details")
                        #{{$bus_booking->id}}
                    </h3>
                </header>
                <div class="panel-body">
                    <div id="user-profile">
                        <div class="row">
                            <div class="col-md-4 col-xs-12">
                                <div class="card my-2 shadow">
                                    <div class="card-body ">
                                        <div class="col-md-4 col-sm-4" style="float:left;">
                                            <img height="80" width="80" class="rounded-circle"
                                                 src="@if(!empty($bus_booking->Driver) && $bus_booking->Driver->profile_image) {{ get_image($bus_booking->Driver->profile_image,'driver') }} @else {{ get_image(null,'driver') }} @endif"
                                                 alt="img">
                                        </div>
                                        <div class="card-text col-md-8 col-sm-8 py-2" style="float:left;">
                                            @if(!empty($bus_booking->Driver))
                                                <h5 class="user-name">{{ is_demo_data($bus_booking->Driver->fullName, $bus_booking->Merchant) }}</h5>
                                                <h6 class="user-job">{{ is_demo_data($bus_booking->Driver->phoneNumber, $bus_booking->Merchant) }}</h6>
                                                <h6 class="user-location">{{ is_demo_data($bus_booking->Driver->email, $bus_booking->Merchant) }}</h6>
                                            @else
                                                <h5 class="user-name">@lang("$string_file.not_assigned_yet")</h5>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card my-2 shadow bg-white h-280">
                                    <div class="justify-content-center p-3">
                                        <div class="row">
                                            <div class="col-md-6 col-xs-6 mt-10"
                                                 style="text-align:center;justify-content:center">
                                                <img height="80" width="80" class="rounded-circle"
                                                     src="{{ get_image($bus_booking->Bus->vehicle_image,'vehicle_document') }}">
                                            </div>
                                            <div class="col-md-6 col-xs-6 mt-10"
                                                 style="text-align:center;justify-content:center">
                                                <img height="80" width="80" class="rounded-circle"
                                                     src="{{ get_image($bus_booking->Bus->vehicle_number_plate_image,'vehicle_document') }}">
                                            </div>
                                        </div>
                                        <hr/>
                                        <div class="clear"></div>
                                        <div class="user-btm-box">
                                            <div class="col-md-4 col-sm-4" style="float:left;">
                                                <a class="avatar img-bordered avatar-100" href="javascript:void(0)">
                                                    <img src="{{ get_image($bus_booking->Bus->VehicleType->vehicleTypeImage,'vehicle') }}"/>
                                                </a>
                                            </div>
                                            <div class="col-md-8 col-sm-8" style="float:left;">
                                                <h5 class="user-name">@lang("$string_file.service_type")
                                                    : {{ $bus_booking->ServiceType->ServiceName($bus_booking->merchant_id) }}</h5>
                                                <h6 class="user-job">@lang("$string_file.vehicle_type")
                                                    : {{ $bus_booking->Bus->VehicleType->VehicleTypeName }}</h6>
                                                <h6 class="user-location">
                                                    @lang("$string_file.vehicle_make")
                                                    : {{ $bus_booking->Bus->VehicleMake->VehicleMakeName }}<br>
                                                    @lang("$string_file.vehicle_model")
                                                    : {{  $bus_booking->Bus->VehicleModel->VehicleModelName }}<br>
                                                    @lang("$string_file.bus_details")
                                                    : {{ $bus_booking->Bus->busName($bus_booking->Bus) }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8 col-xs-12 mt-20">
                                <div class="row">
                                    <div class="col-md-6 col-sm-6 col-xs-12 mb-5 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="font-weight-500 text-success text-uppercase mb-1">
                                                    <i class="icon fa-map-marker fa-2x text-gray-300"></i>
                                                    @lang("$string_file.pickup_location")</div>
                                                <div class="mb-0">{{ $bus_booking->BusRoute->StartPoint->Name }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12 mb-5 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="font-weight-500 text-danger text-uppercase mb-1">
                                                    <i class="icon fa-tint fa-2x text-gray-300"></i>
                                                    @lang("$string_file.drop_location")</div>
                                                <div class="mb-0">{{ $bus_booking->BusRoute->EndPoint->Name }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 col-sm-6 mb-5 col-xs-12 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="font-weight-500 text-success text-uppercase mb-1">
                                                    <i class="icon fa-calendar fa-2x text-gray-300"></i>
                                                    @lang("$string_file.date")</div>
                                                <div class="mb-0">
                                                    {{ $bus_booking->booking_date." ".$bus_booking->ServiceTimeSlotDetail->slot_time_text }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-50">
                                    <div class="col-md-12 col-xs-12">
                                        <h4>@lang("$string_file.stop_points")</h4>
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="dataTable">
                                                <thead>
                                                    <tr>
                                                        <th>@lang("$string_file.sr")</th>
                                                        <th>@lang("$string_file.stop_point")</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $sr = 1; @endphp
                                                    @foreach($bus_booking->BusRoute->StopPoints as $stop_point)
                                                        <tr>
                                                            <td>{{$sr++}}</td>
                                                            <td>{{$stop_point->Name}}</td>
                                                        </tr>
                                                    @endforeach
                                                    <tr>
                                                        <td>{{$sr++}}</td>
                                                        <td>{{$bus_booking->BusRoute->EndPoint->Name}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12 col-xs-12">
                                <div class="tab-content pt-20">
                                    <h4>@lang("$string_file.bus_bookings")</h4>
                                    <div class="tab-pane active" id="exampleTabsLineTopOne" role="tabpanel">
                                        <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                                               style="width:100%">
                                            <thead>
                                            <tr>
                                                <th>@lang("$string_file.sn")</th>
                                                <th>@lang("$string_file.user_details")</th>
                                                <th>@lang("$string_file.start_and_end_point")</th>
                                                <th>@lang("$string_file.no_of_bookings")</th>
                                                <th>@lang("$string_file.total_amount")</th>
                                                <th>@lang("$string_file.details")</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @php $sr = 1; @endphp
                                            @foreach($bus_booking->BusBooking as $booking)
                                                <tr>
                                                    <td>
                                                        {{ $sr++ }}
                                                    </td>
                                                    <td>
                                                        <span class="long_text">
                                                            {{ is_demo_data($booking->User->UserName, $booking->Merchant) }}<br>
                                                            {{ is_demo_data($booking->User->UserPhone, $booking->Merchant) }}<br>
                                                            {{ is_demo_data($booking->User->email, $booking->Merchant) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @lang("$string_file.start") : {{ $booking->BusStop->Name }} <br>
                                                        @lang("$string_file.end") : {{ $booking->EndBusStop->Name }}
                                                    </td>
                                                    <td>{{ $booking->total_seats }}</td>
                                                    <td>{{ $booking->total_amount }}</td>
                                                    <td>
                                                        <a data-toggle="modal" data-target="#booking_detail_{{$booking->id}}">
                                                            <i class="icon wb-users" aria-hidden="true"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @foreach($bus_booking->BusBooking as $booking)
        <div class="modal fade text-center" id="booking_detail_{{$booking->id}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
             aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <label class="modal-title text-text-bold-600"
                               id="myModalLabel33"><b> @lang("$string_file.booking_detail") : {{ $booking->User->UserName }}</b></label>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body col-md-12">
                        <table class="table table-striped">
                            <tr>
                                <th>@lang("$string_file.sn")</th>
                                <th>@lang("$string_file.booking_id")</th>
                                <th>@lang("$string_file.seat_detail")</th>
                                <th>@lang("$string_file.name")</th>
                                <th>@lang("$string_file.age")</th>
                                <th>@lang("$string_file.gender")</th>
                                <th>@lang("$string_file.amount")</th>
                            </tr>
                            @php $sr = 1; @endphp
                            @foreach($booking->BusBookingDetail as $booking_detail)
                                <tr>
                                    <td>{{$sr++}}</td>
                                    <td>{{$booking_detail->bus_booking_id}}</td>
                                    <td>{{$booking_detail->BusSeatDetail->seat_no." / ".$bus_type_show[$booking_detail->BusSeatDetail->type]}}</td>
                                    <td>{{$booking_detail->name}}</td>
                                    <td>{{$booking_detail->age}}</td>
                                    <td>@if($booking_detail->gender == 1) @lang("$string_file.male") @else @lang("$string_file.female") @endif</td>
                                    <td>{{$booking_detail->amount}}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <div class="modal fade text-center" id="assign_booking" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33"><b> @lang("$string_file.manual_booking_assign")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body col-md-12">
                    <form method="post" id="vehicle-make-add" name="bus_booking_driver_manual_assign" action="{{ route('merchant.bus_booking.active.manual-assign') }}">
                        @csrf
                        <div class="modal-body">
                            <label>
                                @lang("$string_file.driver")  <span class="text-danger">*</span>
                            </label>
                            <div class="form-group">
                                {{ Form::select("driver_id", $drivers, old("driver_id"), array("required" => true, "class" => "form-control")) }}
                            </div>
                            {{ Form::hidden("bus_booking_id", $bus_booking->id) }}
                        </div>
                        <div class="modal-footer">
                            <input type="reset" class="btn btn-outline-secondary btn" data-dismiss="modal" value="@lang("$string_file.close")">
                            <input type="submit" class="btn btn-outline-primary btn" value="@lang("$string_file.submit")">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
