@extends('merchant.layouts.main')
@section('content')
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
                        @if(Auth::user('merchant')->can('view_sos_request'))
                            <a href="{{route('excel.sosrequests')}}?version=2">
                                <button type="button" class="btn btn-icon btn-primary mr-1 float-right"
                                        style="margin:10px"
                                        data-original-title="@lang("$string_file.export_excel")"
                                        data-toggle="tooltip"><i
                                            class="fa fa-download"></i>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title">
                        <i class=" wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.sos_request")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('merchant.all.sos.search') }}">
                        <div class="table_search">
                            <div class="row">
                                <div class="col-md-2 form-group ">
                                    <div class="input-group">
                                        <select class="form-control" name="application"
                                                id="application">
                                            <option value="">--@lang("$string_file.application")--</option>
                                            <option value="2">@lang("$string_file.driver")</option>
                                            <option value="1">@lang("$string_file.user")</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2 form-group ">
                                    <div class="input-group">
                                        <input type="text" id="" name="rider"
                                               placeholder="@lang("$string_file.user_details")"
                                               class="form-control col-md-12 col-xs-12">
                                    </div>
                                </div>
                                <div class="col-md-2 form-group ">
                                    <div class="input-group">
                                        <input type="text" id="" name="driver"
                                               placeholder="@lang("$string_file.driver_details")"
                                               class="form-control col-md-12 col-xs-12">
                                    </div>
                                </div>
                                <div class="col-md-2 form-group ">
                                    <div class="input-group">
                                        <input type="text" id="" name="date"
                                               placeholder="@lang("$string_file.date")"
                                               class="form-control col-md-12 col-xs-12 datepickersearch"
                                               id="datepickersearch">
                                    </div>
                                </div>
                                <div class="col-sm-2 form-group ">
                                    <button class="btn btn-primary" type="submit" name="seabt12"><i
                                                class="fa fa-search" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.application")</th>
                            <th>@lang("$string_file.user_details")</th>
                            <th>@lang("$string_file.driver_details")</th>
                            <th>@lang("$string_file.service_area")</th>
                            <th>@lang("$string_file.services")</th>
                            <th>@lang("$string_file.vehicle") @lang("$string_file.details")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.request") @lang("$string_file.time")</th>
                            <th>@lang("$string_file.date")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $sosRequests->firstItem() @endphp
                        @foreach($sosRequests as $sosRequest)
                            <tr>
                                <td>#{{ $sr }}
                                </td>
                                @switch($sosRequest->application)
                                    @case(1)
                                    <td>@lang("$string_file.user")</td>
                                    @break
                                    @case(2)
                                    <td>@lang("$string_file.driver")</td>
                                    @break
                                @endswitch
                                <td>
                                    {{ is_demo_data($sosRequest->Booking->User->UserName, $sosRequest->Merchant) }}<br>
                                    {{ is_demo_data($sosRequest->Booking->User->UserPhone, $sosRequest->Merchant) }}<br>
                                    {{ is_demo_data($sosRequest->Booking->User->email, $sosRequest->Merchant) }}
                                </td>
                                <td>
                                    @if($sosRequest->Booking->driver_id)
                                        {{ is_demo_data($sosRequest->Booking->Driver->fullName, $sosRequest->Merchant) }}<br>
                                        {{ is_demo_data($sosRequest->Booking->Driver->phoneNumber, $sosRequest->Merchant) }}<br>
                                        {{ is_demo_data($sosRequest->Booking->Driver->email, $sosRequest->Merchant) }}
                                    @else
                                        No Driver
                                    @endif
                                </td>
                                <td> {{ $sosRequest->Booking->CountryArea->LanguageSingle == "" ? $sosRequest->Booking->CountryArea->LanguageAny->AreaName : $sosRequest->Booking->CountryArea->LanguageSingle->AreaName }}</td>
                                <td> {{ $sosRequest->Booking->ServiceType->serviceName }}</td>
                                <td> @lang("$string_file.type") : {{ $sosRequest->Booking->VehicleType->LanguageVehicleTypeSingle == "" ? $sosRequest->Booking->VehicleType->LanguageVehicleTypeAny->vehicleTypeName : $sosRequest->Booking->VehicleType->LanguageVehicleTypeSingle->vehicleTypeName }}<br>
                                    @lang("$string_file.model") :{{$sosRequest->Booking->DriverVehicle ? $sosRequest->Booking->DriverVehicle->VehicleModel->VehicleModelName: ""}}<br>
                                    @lang("$string_file.color") :{{$sosRequest->Booking->DriverVehicle ? $sosRequest->Booking->DriverVehicle->vehicle_color : ""}}<br>
                                    @lang("$string_file.number") :{{$sosRequest->Booking->DriverVehicle ? $sosRequest->Booking->DriverVehicle->vehicle_number : ""}}<br>

                                </td>
                                <td>
                                    @if($sosRequest->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.open")</span>
                                    @else
                                        <span class="badge badge-secondary">@lang("$string_file.close")</span>
                                    @endif
                                </td>

                                <td>{!! convertTimeToUSERzone($sosRequest->created_at, $sosRequest->Booking->CountryArea->timezone, null, $sosRequest->Merchant) !!}</td>
                                <td>{!! convertTimeToUSERzone($sosRequest->Booking->created_at, $sosRequest->Booking->CountryArea->timezone, null, $sosRequest->Merchant, 2) !!}</td>
                                <td>
                                    <div class="button-margin">
                                        @if($sosRequest->status == 1)
                                            <a href="{{route('merchant.sos.v2.status', ['id'=>$sosRequest->id, 2])}}" target="_blank" title="Close Sos Request" data-placement="top" class="btn text-white btn-sm btn-success menu-icon btn_eye action_btn" role="menuitem">
                                                <i class="fa fa-check" aria-hidden="true"></i>
                                            </a>
                                            @endif
                                        <a href="{{ route('merchant.booking.details',$sosRequest->booking_id) }}" target="_blank" title="Booking Details" data-placement="top" class="btn text-white btn-sm btn-info menu-icon btn_eye action_btn" role="menuitem">
                                            <i class="icon fa-window-maximize"></i>
                                        </a>
                                            <a href="https://www.google.com/maps/place/{{ $sosRequest->sos_longitude }},{{ $sosRequest->sos_latitude }}" target="_blank" title="Sos Location" data-placement="top" class="btn text-white btn-sm btn-danger menu-icon btn_eye action_btn" role="menuitem">
                                                <i class="fa fa-map-marker" aria-hidden="true"></i>

                                            </a>


                                    </div>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    <div class="pagination1 float-right">{{ $sosRequests->appends($data)->links() }}</div>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
