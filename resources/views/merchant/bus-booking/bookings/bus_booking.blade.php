@extends('merchant.layouts.main')
@section('content')
    @php
        $booking_id = isset($arr_search['booking_id']) ? $arr_search['booking_id'] : "";
        $first_name = isset($arr_search['first_name']) ? $arr_search['first_name'] : "";
        $last_name = isset($arr_search['last_name']) ? $arr_search['last_name'] : "";
        $phone = isset($arr_search['last_name']) ? $arr_search['last_name'] : "";
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
                        @lang("$string_file.bus_bookings")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('bus_booking_master') }}" method="get">
                        <div class="table_search row">
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="booking_id"
                                           placeholder="@lang("$string_file.booking") @lang("$string_file.id")"
                                           class="form-control col-md-12 col-xs-12" value="{{$booking_id}}">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="last_name"
                                           placeholder="@lang("$string_file.user") @lang("$string_file.first") @lang("$string_file.name") "
                                           class="form-control col-md-12 col-xs-12" value="{{$first_name}}">
                                </div>
                            </div>

                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="last_name"
                                           placeholder="@lang("$string_file.user") @lang("$string_file.last") @lang("$string_file.name") "
                                           class="form-control col-md-12 col-xs-12" value="{{$last_name}}">
                                </div>
                            </div>

                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="phone"
                                           placeholder="@lang("$string_file.user") @lang("$string_file.phone") "
                                           class="form-control col-md-12 col-xs-12" value="{{$phone}}">
                                </div>
                            </div>

                            <div class="col-sm-2  col-xs-12 form-group ">
                                <button class="btn btn-primary" type="submit" name="seabt12"><i
                                            class="fa fa-search" aria-hidden="true"></i>
                                </button>
                                <a href="{{route('bus_booking_master')}}" ><button class="btn btn-success" type="button"><i class="fa fa-refresh" aria-hidden="true"></i></button></a>
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
                                    <th>@lang("$string_file.user_details")</th>
                                    <th>@lang("$string_file.service_area")</th>
                                    <th>@lang("$string_file.from")</th>
                                    <th>@lang("$string_file.to")</th>
                                    <th>@lang("$string_file.pickup") @lang("$string_file.point") </th>
                                    <th>@lang("$string_file.drop") @lang("$string_file.point")</th>
                                    <th>@lang("$string_file.bookings") @lang("$string_file.type")</th>
                                    <th>@lang("$string_file.total") @lang("$string_file.seat")</th>
                                    <th>@lang("$string_file.total_amount")</th>
                                    <th>@lang("$string_file.payment_details")</th>
                                    <th>@lang("$string_file.created_at")</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $sr = $paginated_bookings->firstItem() @endphp
                                @foreach($paginated_bookings as $booking)
                                    <tr>
                                        <td>
                                            {{ $sr }}
                                        </td>
                                        <td>
                                            {{ "#".$booking->merchant_bus_booking_id }}
                                        </td>
                                        <td>
                                                {{ is_demo_data($booking->User->first_name, $booking->Merchant) }}<br>
                                                {{ is_demo_data($booking->User->userPhone, $booking->Merchant) }}<br>
                                                {{ is_demo_data($booking->User->email, $booking->Merchant) }}

                                        </td>
                                        <td>
                                            {{ $booking->CountryArea->getCountryAreaNameAttribute() }}
                                        </td>
                                        <td>

                                            <span style="word-wrap: break-word; word-break: break-all; white-space:normal;display: inline-block;width:150px">{{ $booking->BusStop->address }}</span>
                                        </td>
                                        <td>

                                            <span style="word-wrap: break-word; word-break: break-all; white-space:normal;display: inline-block;width:150px">{{ $booking->EndBusStop->address }}</span>

                                        </td>
                                        <td>

                                            <span style="word-wrap: break-word; word-break: break-all; white-space:normal;display: inline-block;width:150px">{{ isset($booking->PickupPoint)? $booking->PickupPoint->address : ""}}</span>

                                        </td>
                                        <td>

                                            <span style="word-wrap: break-word; word-break: break-all; white-space:normal;display: inline-block;width:150px">{{ isset($booking->DropPoint)? $booking->DropPoint->address : ""}}</span>

                                        </td>
                                        <td>
                                            @if($booking->booking_for == 1)
                                                <span class="badge badge-primary"> @lang("$string_file.passenger") </span>
                                            @else
                                                <span class="badge badge-warning">@lang("$string_file.package_delivery")</span>
                                            @endif

                                        </td>
                                        <td>
                                            <span class="badge badge-secondary"> {{$booking->total_seats}} </span>

                                        </td>
                                        <td>
                                            {{$booking->CountryArea->Country->isoCode}}  {{$booking->total_amount}}

                                        </td>
                                        <td>
                                            {{$booking->PaymentMethod->payment_method}}<br>
                                        </td>

                                        <td>
                                            {{$booking->created_at}}<br>
                                        </td>
                                    </tr>
                                    @php $sr++ @endphp
                                @endforeach
                                </tbody>
                            </table>
                            @include('merchant.shared.table-footer', ['table_data' => $paginated_bookings, 'data' => $data])
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
