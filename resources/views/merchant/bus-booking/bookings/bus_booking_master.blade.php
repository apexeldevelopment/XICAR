@extends('merchant.layouts.main')
@section('content')
    @php
        $bus_id = isset($arr_search['bus_id']) ? $arr_search['bus_id'] : "";
        $booking_id = isset($arr_search['booking_id']) ? $arr_search['booking_id'] : "";
        $booking_master_id = isset($arr_search['booking_master_id']) ? $arr_search['booking_master_id'] : "";
    @endphp
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.master_bus_bookings")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('bus_booking_master') }}" method="get">
                        <div class="table_search row">
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="bus_id"
                                           placeholder="@lang("$string_file.bus") @lang("$string_file.id")"
                                           class="form-control col-md-12 col-xs-12" value="{{$bus_id}}">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="booking_id"
                                           placeholder="@lang("$string_file.booking") @lang("$string_file.id")"
                                           class="form-control col-md-12 col-xs-12" value="{{$booking_id}}">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="booking_master_id"
                                           placeholder="@lang("$string_file.master") @lang("$string_file.booking") @lang("$string_file.id")"
                                           class="form-control col-md-12 col-xs-12" value="{{$booking_master_id}}">
                                </div>
                            </div>

                            <div class="col-sm-2  col-xs-12 form-group ">
                                <button class="btn btn-primary" type="submit" name="seabt12"><i
                                            class="fa fa-search" aria-hidden="true"></i>
                                </button>
                                <a href="{{route('merchant.bus_booking.rating.index')}}" ><button class="btn btn-success" type="button"><i class="fa fa-refresh" aria-hidden="true"></i></button></a>
                            </div>
                            <div class="col-sm-4 float-right form-group">

                            </div>
                        </div>
                    </form>
                    <div class="tab-content pt-20">
                        <div class="tab-pane active" id="exampleTabsLineTopOne" role="tabpanel">
                            <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                                   style="width:100%">
                                <thead>
                                <tr>
                                    <th>@lang("$string_file.sn")</th>
                                    <th>@lang("$string_file.id")</th>
                                    <th>@lang("$string_file.service_type")</th>
                                    <th>@lang("$string_file.bus_route")</th>
                                    <th>@lang("$string_file.bus_details")</th>
                                    <th>@lang("$string_file.date") & @lang("$string_file.start_time")</th>
                                    <th>@lang("$string_file.driver_details")</th>
                                    <th>@lang("$string_file.seats") @lang("$string_file.booked")</th>
                                    <th>@lang("$string_file.bookings")</th>
                                    <th>@lang("$string_file.status")</th>
                                    <th>@lang("$string_file.action")</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $sr = $bus_booking_masters->firstItem() @endphp
                                @foreach($bus_booking_masters as $bus_booking_master)
                                    <tr>
                                        <td>
                                            {{ $sr }}
                                        </td>
                                        <td>
                                            {{ "#".$bus_booking_master->id }}
                                        </td>

                                        <td>
                                            {{ $bus_booking_master->ServiceType->ServiceName($bus_booking_master->merchant_id) }}
                                        </td>
                                        <td>
                                            {{ $bus_booking_master->BusRoute->Name }}
                                        </td>
                                        <td>
                                            {{ $bus_booking_master->Bus->busName($bus_booking_master->Bus) }}
                                        </td>
                                        <td>
                                            {{ $bus_booking_master->booking_date }}<br>
                                            {{ $bus_booking_master->ServiceTimeSlotDetail->slot_time_text }}
                                        </td>
                                        <td>
                                            <span class="long_text">
                                                @if($bus_booking_master->Driver)
                                                    {{ is_demo_data($bus_booking_master->Driver->fullName, $bus_booking_master->Merchant) }}<br>
                                                    {{ is_demo_data($bus_booking_master->Driver->phoneNumber, $bus_booking_master->Merchant) }}<br>
                                                    {{ is_demo_data($bus_booking_master->Driver->email, $bus_booking_master->Merchant) }}
                                                @else
                                                    @lang("$string_file.not_assigned_yet")
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                           @php
                                               $total_booked_seats = 0;
                                               foreach($bus_booking_master->BusBooking as $booking)
                                                   $total_booked_seats += $booking->total_seats ?? 0;
                                           @endphp

                                            <span class="badge badge-secondary">{{$bus_booking_master->Bus->BusSeatDetail->Count() - $total_booked_seats }} /
                                            {{$bus_booking_master->Bus->BusSeatDetail->Count()}}</span>

                                        </td>
                                        <td>
                                            <a href="{{route("bus_booking_booking", ['id'=>$bus_booking_master->id])}}">
                                                <button type="button" class="btn btn-sm btn-primary">
                                                    @lang("$string_file.view") @lang("$string_file.bookings") <span class="badge badge-light">{{ count($bus_booking_master->BusBooking) }}</span>
                                                </button>
                                            </a>
                                        </td>
                                        <td>

                                            @if($bus_booking_master->status == 1 || $bus_booking_master->status == 2)
                                                <span class="badge badge-primary">{{ $bus_booking_status[$bus_booking_master->status] }}</span>
                                            @elseif($bus_booking_master->status == 3)
                                                <span class="badge badge-success">{{ $bus_booking_status[$bus_booking_master->status] }}</span>
                                            @elseif($bus_booking_master->status == 4)
                                                <span class="badge badge-warning">{{ $bus_booking_status[$bus_booking_master->status] }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ $bus_booking_status[$bus_booking_master->status] }}</span>
                                            @endif

                                        </td>
                                        <td>
                                            <a target="_blank"
                                               title=""
                                               href="{{ route('merchant.bus_booking.detail',$bus_booking_master->id) }}"
                                               class="btn btn-sm btn-info menu-icon btn_money action_btn"><span
                                                        class="fa fa-info-circle"
                                                        data-original-title="@lang("$string_file.ride_detail")"
                                                        data-toggle="tooltip"
                                                        data-placement="top"></span></a>
                                            @if($bus_booking_master->status == 1)
                                                <span data-target="#cancelbooking"
                                                      data-toggle="modal"
                                                      id="{{ $bus_booking_master->id }}"><a
                                                            data-original-title="@lang("$string_file.cancel_ride")"
                                                            data-toggle="tooltip"
                                                            id="{{ $bus_booking_master->id }}"
                                                            data-placement="top"
                                                            class="btn btn-sm btn-danger menu-icon btn_delete action_btn"> <i
                                                                class="fa fa-times"></i> </a></span>
                                            @endif
                                        </td>
                                    </tr>
                                    @php $sr++ @endphp
                                @endforeach
                                </tbody>
                            </table>
                            @include('merchant.shared.table-footer', ['table_data' => $bus_booking_masters, 'data' => $data])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#dataTable2').DataTable({
                searching: false,
                paging: false,
                info: false,
                "bSort": false,
            });
        });
    </script>
@endsection
