@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if($export_permission)
                            <a href="{{route('excel.complete',$arr_search)}}" data-toggle="tooltip">
                                <button type="button" class="btn btn-icon btn-primary float-right"
                                        style="margin: 10px;">
                                    <i class="wb-download" title="@lang("$string_file.export_excel")"></i>
                                </button>
                            </a>
                            <a href="{{route('merchant.master-invoice',$arr_search)}}" data-toggle="tooltip">
                                <button type="button" class="btn btn-icon btn-warning float-right" style="margin: 10px;"
                                        title="@lang("$string_file.master_invoice")">
                                    <i class="fa fa-print"></i>
                                </button>
                            </a>
                            <a href="{{route('merchant.multiple-invoice',$arr_search)}}" data-toggle="tooltip">
                                <button type="button" class="btn btn-icon btn-info float-right" style="margin: 10px;"
                                        title="@lang("$string_file.multiple_invoice")">
                                    <i class="fa fa-list"></i>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="fa-taxi" aria-hidden="true"></i>
                        @lang("$string_file.completed_rides")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! $search_view !!}
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.ride_id")</th>
                            <th>@lang("$string_file.ride_type")</th>
                            <th>@lang("$string_file.user_details")</th>
                            <th>@lang("$string_file.driver_details")</th>
                            <th>@lang("$string_file.request_from")</th>
                            <th>@lang("$string_file.ride_details")</th>
                            <th>@lang("$string_file.pickup_drop")</th>
                            <th>@lang("$string_file.payment_method")</th>
                            <th>@lang("$string_file.bill_amount")</th>
                            <th>@lang("$string_file.date")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $bookings->firstItem() @endphp
                        @foreach($bookings as $booking)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{ $booking->merchant_booking_id }}</td>
                                <td>
                                    @if($booking->booking_type == 1)
                                        @lang("$string_file.ride_now")
                                    @else
                                        @lang("$string_file.ride_later") <br>(
                                        {!! date(getDateTimeFormat($booking->Merchant->datetime_format,2),strtotime($booking->later_booking_date)) !!}
                                        {{$booking->later_booking_time }} )
                                    @endif
                                </td>

                                <td>
                                     <span class="long_text">
                                         {{ is_demo_data($booking->User->UserName, $booking->Merchant) }}<br>
                                         {{ is_demo_data($booking->User->UserPhone, $booking->Merchant) }}<br>
                                         {{ is_demo_data($booking->User->email, $booking->Merchant) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="long_text">
                                        {{ is_demo_data($booking->Driver->fullName, $booking->Merchant) }}<br>
                                        {{ is_demo_data($booking->Driver->phoneNumber, $booking->Merchant) }}<br>
                                        {{ is_demo_data($booking->Driver->email, $booking->Merchant) }}
                                    </span>
                                </td>
                                <td>
                                    @switch($booking->platform)
                                        @case(1)
                                        @lang("$string_file.application")
                                        @break
                                        @case(2)
                                        @lang("$string_file.admin")
                                        @break
                                        @case(3)
                                        @lang("$string_file.web")
                                        @break
                                    @endswitch
                                </td>
                                @php
                                    $package_name = ($booking->service_type_id == 2) && !empty($booking->service_package_id) ? ' ('.$booking->ServicePackage->PackageName.')' : '';
                                    $service_text = ($booking->ServiceType) ? $booking->ServiceType->serviceName($booking->merchant_id).$package_name : ($booking->deliveryType ? $booking->deliveryType->name : '---' );
                                @endphp
                                <td>{!! nl2br($booking->CountryArea->CountryAreaName ."\n". $service_text."\n".$booking->VehicleType->VehicleTypeName) !!}</td>

                                <td>
                                    @if(!empty($booking->BookingDetail->start_location))
                                        <a title="{{ $booking->BookingDetail->start_location }}"
                                           target="_blank"
                                           href="https://www.google.com/maps/place/{{ $booking->BookingDetail->start_latitude}},{{ $booking->BookingDetail->start_longitude}}"
                                           class="btn btn-icon btn-success ml-2  0"><i class="icon wb-map"></i></a>
                                    @endif
                                    @if(!empty($booking->BookingDetail->end_location))
                                        <a title="{{ $booking->BookingDetail->end_location }}"
                                           target="_blank"
                                           href="https://www.google.com/maps/place/{{ $booking->BookingDetail->end_latitude}},{{ $booking->BookingDetail->end_longitude}}"
                                           class="btn btn-icon btn-danger ml-20"><i class="icon fa-tint"></i></a>
                                    @endif
                                </td>
                                <td>
                                    {{ $booking->PaymentMethod->payment_method }}
                                </td>
                                <td>
                                    {{ $booking->CountryArea->Country->isoCode . $booking->final_amount_paid }}
                                </td>
                                <td>
                                    {!! convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone,null,$booking->Merchant) !!}

                                </td>
                                <td>
                                    <a target="_blank" title="@lang("$string_file.requested_drivers")"
                                       href="{{ route('merchant.ride-requests',$booking->id) }}"
                                       class="btn btn-sm btn-success menu-icon btn_detail action_btn"><span
                                                class="fa fa-list-alt"></span></a>

                                    <a target="_blank" title="@lang("$string_file.ride_details")"
                                       href="{{ route('merchant.booking.details',$booking->id) }}"
                                       class="btn btn-sm btn-info menu-icon btn_money action_btn"><span
                                                class="fa fa-info-circle"
                                                title=""></span></a>

                                    <a target="_blank" title="@lang("$string_file.invoice")"
                                       href="{{ route('merchant.booking.invoice',$booking->id) }}"
                                       class="btn btn-sm btn-warning menu-icon btn_eye action_btn"><span
                                                class="fa fa-print"></span></a>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $bookings, 'data' => $arr_search])
                </div>
            </div>
        </div>
    </div>
@endsection
