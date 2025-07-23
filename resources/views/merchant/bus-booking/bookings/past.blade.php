@extends('merchant.layouts.main')
@section('content')
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
                        @lang("$string_file.past_bus_bookings")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <div class="tab-content pt-20">
                        <div class="tab-pane active" id="exampleTabsLineTopOne" role="tabpanel">
                            <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                                   style="width:100%">
                                <thead>
                                <tr>
                                    <th>@lang("$string_file.sn")</th>
                                    <th>@lang("$string_file.segment")</th>
                                    <th>@lang("$string_file.service_type")</th>
                                    <th>@lang("$string_file.bus_route")</th>
                                    <th>@lang("$string_file.bus_details")</th>
                                    <th>@lang("$string_file.date") & @lang("$string_file.start_time")</th>
                                    <th>@lang("$string_file.driver_details")</th>
                                    <th>@lang("$string_file.no_of_bookings")</th>
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
                                            {{ $bus_booking_master->Segment->Name($bus_booking_master->merchant_id) }}
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
                                            {{ count($bus_booking_master->BusBooking) }}
                                        </td>
                                        <td>
                                            {{ $bus_booking_status[$bus_booking_master->status] }}
                                        </td>
                                        <td>
                                            <a target="_blank"
                                               title=""
                                               href="{{ route('merchant.bus_booking.detail',$bus_booking_master->id) }}"
                                               class="btn btn-sm btn-info menu-icon btn_money action_btn"><span
                                                        class="fa fa-info-circle"
                                                        data-original-title="@lang("$string_file.ride_details")"
                                                        data-toggle="tooltip"
                                                        data-placement="top"></span></a>
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
