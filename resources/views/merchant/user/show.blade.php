@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('users.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                        <a href="{{route('excel.userRides',$user->id)}}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                <i class="wb-download" title="@lang("$string_file.export_excel")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa-info-circle" aria-hidden="true"></i>
                        @lang("$string_file.user_details")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <div id="user-profile">
                        <div class="row">
                            <!-- Column -->
                            <div class="col-md-4 col-xs-12">
                                <div class="card shadow">
                                    <div class="card-block text-center">
                                        <img src="{{ get_image($user->UserProfileImage,'user') }}"
                                             class="rounded-circle" width="120" height="120">
                                        <h5 class="user-name mb-3">{{ is_demo_data($user->UserName, $user->Merchant) }}</h5>
                                        <p class="user-job mb-3">{{ is_demo_data($user->UserPhone, $user->Merchant) }}</p>
                                        <p class="user-info mb-3">{{ is_demo_data($user->email, $user->Merchant) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8 col-xs-12 mt-20">
                                <div class="row mb-5">
                                    <div class="col-md-6 col-sm-6 col-xs-12 py-2 ">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="h5 text-uppercase mb-1">
                                                    <i class="icon fa-car fa-2x text-gray-300"></i>
                                                    @lang("$string_file.user_type")
                                                </div>
                                                <div class="mb-0">
                                                    @if($user->user_type == 1)
                                                        @lang("$string_file.corporate_user")
                                                    @else
                                                        @lang("$string_file.retail")
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="h5 text-uppercase mb-1">
                                                    <i class="icon fa-tag fa-2x text-gray-300"></i>
                                                    @lang("$string_file.referral_code")
                                                </div>
                                                <div class="mb-0 text-gray-800">{{
                                                        $user->ReferralCode }}
                                                </div>
                                            </div>
                                        </div>
                                        <!-- <div class="white-box"> -->
                                        <!-- <ul class="book_details"> -->
                                        <!-- <li> -->
                                    <!-- <h4>@lang("$string_file.referral_code")</h4> -->
                                    <!-- <p>{{ $user->ReferralCode }}</p> -->
                                        <!-- </li> -->
                                        <!-- </ul> -->
                                        <!-- </div> -->
                                    </div>
                                </div>
                                <div class="row mb-5">
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="h5 text-uppercase mb-1">
                                                    <i class="icon fa-signing fa-2x text-gray-300"></i>
                                                    @lang("$string_file.signup_type")
                                                </div>
                                                <div class="mb-0 text-gray-800">
                                                    @switch($user->UserSignupType)
                                                        @case(1)
                                                        @lang("$string_file.normal")
                                                        @break
                                                        @case(2)
                                                        @lang("$string_file.google")
                                                        @break
                                                        @case(3)
                                                        @lang("$string_file.facebook")
                                                        @break
                                                    @endswitch
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="h5 text-uppercase mb-1">
                                                    <i class="icon fa-mobile fa-2x text-gray-300"></i>
                                                    @lang("$string_file.signup_plateform")
                                                </div>
                                                <div class="mb-0 text-gray-800">
                                                    @switch($user->UserSignupFrom)
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-5">
                                    <div class="col-md-6 col-sm-6 col-xs-12 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="h5 text-uppercase mb-1">
                                                    <i class="icon fa-calendar fa-2x text-gray-300"></i>
                                                    @lang("$string_file.registered_date")
                                                </div>
                                                <div class="mb-0 text-gray-800">
                                                    @if(isset($user->CountryArea->timezone))
                                                        {!! convertTimeToUSERzone($user->created_at, $user->CountryArea->timezone, null, $user->Merchant) !!}
                                                    @else
                                                        {!! convertTimeToUSERzone($user->created_at, null, null, $user->Merchant) !!}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="h5 text-uppercase mb-1">
                                                    <i class="icon fa-calendar fa-2x text-gray-300"></i>
                                                    @lang("$string_file.updated_at")
                                                </div>
                                                <div class="mb-0 text-gray-800">
                                                    @if(isset($user->CountryArea->timezone))
                                                        {!! convertTimeToUSERzone($user->updated_at, $user->CountryArea->timezone, null, $user->Merchant) !!}
                                                    @else
                                                        {!! convertTimeToUSERzone($user->updated_at, null, null, $user->Merchant) !!}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="row mb-5">
                                    @if($appConfig->gender == 1)
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="h5 text-uppercase mb-1">
                                                        <i class="icon fa-sign-in fa-2x text-gray-300"></i>
                                                        @lang("$string_file.gender")
                                                    </div>
                                                    <div class="mb-0 text-gray-800">
                                                        @if($user->user_gender == 1) @lang("$string_file.male") @else @lang("$string_file.female")  @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if($appConfig->smoker == 1)
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="h5 text-uppercase mb-1">
                                                        <i class="icon wi-smoke text-gray-300" area-hidden="true"></i>
                                                        @lang("$string_file.smoke")
                                                    </div>
                                                    <div class="mb-0 text-gray-800">
                                                    </div>
                                                    @if($user->smoker_type == 1)  @lang("$string_file.smoker") @else  @lang("$string_file.non_smoker") @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @if(isset($config->user_bank_details_enable) && $config->user_bank_details_enable == 1)
                                <div class="col-md-12 mt-20 mb-10">
                                    <h5>@lang("common.bank") @lang("common.details")</h5>
                                    <hr>
                                    <div class="card shadow">
                                        <div class="card-block">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <span class="h5 text-uppercase mb-1"> @lang("common.bank") @lang("common.name") :</span>
                                                    {{ is_demo_data($user->bank_name, $user->Merchant) }}
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="h5 text-uppercase mb-1"> @lang("common.account") @lang("common.holder") @lang("common.name") :</span>
                                                    {{ is_demo_data($user->account_holder_name, $user->Merchant) }}
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="h5 text-uppercase mb-1"> @lang("common.account") @lang("common.number") :</span>
                                                    {{ is_demo_data($user->account_number, $user->Merchant) }}
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="h5 text-uppercase mb-1"> @lang("common.online") @lang("common.transaction") @lang("common.code") :</span>
                                                    {{ is_demo_data($user->online_code, $user->Merchant) }}
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="h5 text-uppercase mb-1"> @lang("common.account") @lang("common.type") :</span>
                                                    @if(!empty($user->AccountType))
                                                        {{ is_demo_data($user->AccountType->Name, $user->Merchant) }}
                                                    @else
                                                        ---
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(in_array('CARPOOLING',$merchant_segment))
                                <div class="col-md-12 mt-20 mb-10">
                                    @if(!empty($user->OwnerVehicle) && $user->OwnerVehicle->count() > 0)
                                        <h5>@lang("$string_file.self") @lang("$string_file.vehicle")</h5>
                                        <hr>
                                    @endif
                                    @foreach($user->OwnerVehicle as $owner_vehicle)
                                        <div style="border:1px solid #e4eaec; border-radius: 5px;" class="mb-2 p-2">
                                            <div class="row mt-20 mb-5">
                                                <div class="col-md-6">
                                                    <h5>@lang("$string_file.vehicle") @lang("common.no")
                                                        : {{$owner_vehicle->vehicle_number}}</h5>
                                                    <span class=""><b>@lang("$string_file.vehicle") @lang("common.type") </b></span>
                                                    : {{ isset($owner_vehicle->VehicleType->VehicleTypeName) ? $owner_vehicle->VehicleType->VehicleTypeName : "---"}}
                                                    |
                                                    <span class=""><b>@lang("$string_file.vehicle") @lang("common.model")  </b></span>
                                                    : {{ isset($owner_vehicle->VehicleModel->VehicleModelName) ? $owner_vehicle->VehicleModel->VehicleModelName : "---"}}
                                                    |
                                                    <span class=""><b>@lang("$string_file.vehicle") @lang("common.make")  </b></span>
                                                    : {{ isset($owner_vehicle->VehicleMake->VehicleMakeName) ? $owner_vehicle->VehicleMake->VehicleMakeName : "---"}}
                                                    |<br>
                                                    {{--                                                <span class=""><b>@lang("$string_file.vehicle") @lang("common.number") </b></span>--}}
                                                    {{--                                                : {{ isset($owner_vehicle->vehicle_number) ? $owner_vehicle->vehicle_number : "---"}}--}}
                                                    {{--                                                |--}}
                                                    <span class=""><b>@lang("$string_file.vehicle") @lang("common.registered") @lang("common.date")</b></span>
                                                    : {{ isset($owner_vehicle->vehicle_register_date) ? $owner_vehicle->vehicle_register_date : "---"}}
                                                    |
                                                    <br>
                                                </div>
                                                <div class="col-md-3">
                                                    <h6>@lang("$string_file.vehicle") @lang("common.image") </h6>
                                                    <div class="" style="width: 6.5rem;">
                                                        <div class=" bg-light">
                                                            @php $vehicle_image = get_image($owner_vehicle->vehicle_image,'user_vehicle_document'); @endphp
                                                            <a href="{{$vehicle_image}}" target="_blank"><img
                                                                        src="{{ $vehicle_image }}"
                                                                        style="width:100%;height:80px;"></a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <h6>@lang("$string_file.vehicle")  @lang("common.number") @lang("$string_file.plate")  @lang("common.image") </h6>
                                                    <div class="" style="width: 6.5rem;">
                                                        <div class=" bg-light">
                                                            @php $vehicle_number_plate_image = get_image($owner_vehicle->vehicle_number_plate_image,'user_vehicle_document'); @endphp
                                                            <a href="{{ $vehicle_number_plate_image }}" target="_blank"><img
                                                                        src="{{ $vehicle_number_plate_image }}"
                                                                        style="width:100%;height:80px;"></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered" id="dataTable">
                                                            <thead>
                                                            <tr>
                                                                <th>@lang("common.document") @lang("common.name")</th>
                                                                <th>@lang('common.document')</th>
                                                                <th>@lang('common.status')</th>
                                                                <th>@lang("common.expire") @lang("common.date")</th>
                                                                <th>@lang("common.uploaded") @lang("common.time")</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            @isset($owner_vehicle->UserVehicleDocument)
                                                                @foreach($owner_vehicle->UserVehicleDocument as $document)
                                                                    <tr>
                                                                        <td> {{ $document->Document->documentname }}</td>
                                                                        <td>
                                                                            <a href="{{ get_image($document->document,'user_vehicle_document') }}"
                                                                               target="_blank"><img
                                                                                        src="{{ get_image($document->document,'user_vehicle_document') }}"
                                                                                        style="width:60px;height:60px;border-radius: 10px"></a>
                                                                        </td>
                                                                        <td>
                                                                            @switch($document->document_verification_status)
                                                                                @case(1)
                                                                                @lang("common.pending") @lang("common.for") @lang("common.verification")
                                                                                @break
                                                                                @case(2)
                                                                                @lang("common.verified")
                                                                                @break
                                                                                @case(3)
                                                                                @lang("common.rejected")
                                                                                @break
                                                                            @endswitch
                                                                        </td>
                                                                        <td>
                                                                            {{ $document->expire_date  }}
                                                                        </td>
                                                                        <td>
                                                                            {{ $document->created_at }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            @endisset
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            @if($owner_vehicle->vehicle_verification_status == 1 || $owner_vehicle->vehicle_verification_status == 0)
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="float-right mt-10">
                                                            <a href="{{ route('merchant.uservehicles-vehicle-verify',[$owner_vehicle->id,2]) }}">
                                                                <button class="btn btn-success float-right">@lang("common.approve")</button>
                                                            </a>
                                                            <button class="btn btn-danger float-right mr-2"
                                                                    onclick="rejectVehicle({{$owner_vehicle->id}})"
                                                            >@lang("common.reject")
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if(!empty($sharing_vehicles) && $sharing_vehicles->count() > 0)
                                        <h5>@lang("$string_file.sharing") @lang("$string_file.vehicle")</h5>
                                        <hr>
                                    @endif
                                    @foreach($sharing_vehicles as $sharing_vehicle)
                                        <div class="row mt-20 mb-5">
                                            <div class="col-md-6">
                                                <h5>@lang("$string_file.vehicle") @lang("common.no")
                                                    : {{$sharing_vehicle->vehicle_number}}</h5>
                                                <span class=""><b>@lang("$string_file.vehicle") @lang("common.type") </b></span>
                                                : {{ isset($sharing_vehicle->VehicleType->VehicleTypeName) ? $sharing_vehicle->VehicleType->VehicleTypeName : "---"}}
                                                |
                                                <span class=""><b>@lang("$string_file.vehicle") @lang("common.model")  </b></span>
                                                : {{ isset($sharing_vehicle->VehicleModel->VehicleModelName) ? $sharing_vehicle->VehicleModel->VehicleModelName : "---"}}
                                                |
                                                <span class=""><b>@lang("$string_file.vehicle") @lang("common.make")  </b></span>
                                                : {{ isset($sharing_vehicle->VehicleMake->VehicleMakeName) ? $sharing_vehicle->VehicleMake->VehicleMakeName : "---"}}
                                                |<br>
                                                <span class=""><b>@lang("$string_file.vehicle") @lang("common.registered") @lang("common.date")</b></span>
                                                : {{ isset($sharing_vehicle->vehicle_register_date) ? $sharing_vehicle->vehicle_register_date : "---"}}
                                                |
                                                <br>
                                            </div>
                                            <div class="col-md-3">
                                                <h6>@lang("$string_file.vehicle") @lang("common.image") </h6>
                                                <div class="" style="width: 6.5rem;">
                                                    <div class=" bg-light">
                                                        @php $vehicle_image = get_image($sharing_vehicle->vehicle_image,'user_vehicle_document'); @endphp
                                                        <a href="{{$vehicle_image}}" target="_blank"><img
                                                                    src="{{ $vehicle_image }}"
                                                                    style="width:100%;height:80px;"></a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <h6>@lang("$string_file.vehicle")  @lang("common.number") @lang("$string_file.plate")  @lang("common.image") </h6>
                                                <div class="" style="width: 6.5rem;">
                                                    <div class=" bg-light">
                                                        @php $vehicle_number_plate_image = get_image($sharing_vehicle->vehicle_number_plate_image,'user_vehicle_document'); @endphp
                                                        <a href="{{ $vehicle_number_plate_image }}" target="_blank"><img
                                                                    src="{{ $vehicle_number_plate_image }}"
                                                                    style="width:100%;height:80px;"></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered" id="dataTable">
                                                        <thead>
                                                        <tr>
                                                            <th>@lang("common.document") @lang("common.name")</th>
                                                            <th>@lang('common.document')</th>
                                                            <th>@lang('common.status')</th>
                                                            <th>@lang("common.expire") @lang("common.date")</th>
                                                            <th>@lang("common.uploaded") @lang("common.time")</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @isset($sharing_vehicle->UserVehicleDocument)
                                                            @foreach($sharing_vehicle->UserVehicleDocument as $document)
                                                                <tr>
                                                                    <td> {{ $document->Document->documentname }}</td>
                                                                    <td>
                                                                        <a href="{{ get_image($document->document,'user_vehicle_document') }}"
                                                                           target="_blank"><img
                                                                                    src="{{ get_image($document->document,'user_vehicle_document') }}"
                                                                                    style="width:60px;height:60px;border-radius: 10px"></a>
                                                                    </td>
                                                                    <td>
                                                                        @switch($document->document_verification_status)
                                                                            @case(1)
                                                                            @lang("common.pending") @lang("common.for") @lang("common.verification")
                                                                            @break
                                                                            @case(2)
                                                                            @lang("common.verified")
                                                                            @break
                                                                            @case(3)
                                                                            @lang("common.rejected")
                                                                            @break
                                                                        @endswitch
                                                                    </td>
                                                                    <td>
                                                                        {{ $document->expire_date  }}
                                                                    </td>
                                                                    <td>
                                                                        {{ $document->created_at }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @endisset
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <div class="col-md-12 mt-30">
                                <table id="customDataTable"
                                       class="display nowrap table table-hover table-striped w-full" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>@lang("$string_file.sn")</th>
                                        <th>@lang("$string_file.ride_type")</th>
                                        <th>@lang("$string_file.driver_details")</th>
                                        <th>@lang("$string_file.service_details")</th>
                                        <th>@lang("$string_file.service_area") </th>
                                        <th>@lang("$string_file.pickup_drop")</th>
                                        <th>@lang("$string_file.current_status")</th>
                                        <th>@lang("$string_file.payment")</th>
                                        <th>@lang("$string_file.created_at")</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php $sr = $bookings->firstItem() @endphp
                                    @foreach($bookings as $booking)
                                        <tr>
                                            <td>{{ $sr }}</td>
                                            <td>
                                                @if($booking->booking_type == 1)
                                                    @lang("$string_file.ride_now")
                                                @else
                                                    @lang("$string_file.ride_later")<br>(
                                                    {!! convertTimeToUSERzone($booking->later_booking_date, $booking->CountryArea->timezone,null,$booking->Merchant, 2) !!}
                                                    <br>
                                                    {{$booking->later_booking_time }} )
                                                @endif
                                            </td>
                                            <td>
                                                <span class="long_text">
                                                    @if($booking->Driver)
                                                         {{ is_demo_data($booking->Driver->fullName, $booking->Merchant) }}
                                                         <br>
                                                         {{ is_demo_data($booking->Driver->phoneNumber, $booking->Merchant) }}
                                                         <br>
                                                         {{ is_demo_data($booking->Driver->email, $booking->Merchant) }}
                                                     @else
                                                         @lang("$string_file.not_assigned_yet")
                                                     @endif
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
                                                <br>
                                                @php
                                                    $service_text = ($booking->ServiceType) ? $booking->ServiceType->serviceName : $booking->deliveryType->name ;
                                                @endphp
                                                {{ $service_text }} <br>
                                                {{ $booking->VehicleType->VehicleTypeName }}
                                            </td>
                                            <td> {{ $booking->CountryArea->CountryAreaName }}</td>
                                            <td>
                                                <a title="{{ $booking->pickup_location }}"
                                                   href="https://www.google.com/maps/place/{{ $booking->pickup_location }}" class="btn btn-icon btn-success ml-20"><i class="icon wb-map"></i></a>
                                                <a title="{{ $booking->drop_location }}"
                                                   href="https://www.google.com/maps/place/{{ $booking->drop_location }}" class="btn btn-icon btn-danger ml-20"><i class="icon fa-tint"></i></a>
                                            </td>
                                            <td style="text-align: center">
                                                @if(!empty($arr_booking_status))
                                                    {!! isset($arr_booking_status[$booking->booking_status]) ? $arr_booking_status[$booking->booking_status] : ""  !!}
                                                    <br>
                                                    @lang("$string_file.at") {!! convertTimeToUSERzone($booking->updated_at, $booking->CountryArea->timezone,null,$booking->Merchant, 3) !!}
                                                @endif
                                            </td>
                                            <td>
                                                {{ $booking->PaymentMethod->payment_method }}
                                            </td>
                                            <td>
                                                {!! convertTimeToUSERzone($booking->created_at, $user->CountryArea->timezone, null, $booking->Merchant) !!}
                                            </td>
                                        </tr>
                                        @php $sr++ @endphp
                                    @endforeach
                                    </tbody>
                                </table>
                                <div class="pagination1 float-right">{{ $bookings->links() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form class="form-group" action="{{ route('merchant.user-vehicle-reject') }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"
                            id="exampleModalCenterTitle">@lang("common.reject") @lang("common.user") @lang("$string_file.vehicle")</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" value="{{ $user->id }}" name="user_id">
                            <hr>
                            <div class="col-md-12">
                                <h5>@lang("$string_file.vehicle") @lang("common.documents")</h5>
                            </div>
                            <input type="hidden" value=""
                                   name="user_vehicle_id" id="user_vehicle_id">
                            <div id="doc_check_list"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <textarea class="form-control" placeholder="@lang("common.comment")" name="comment"
                                          required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">@lang("common.close")</button>
                        <button type="submit" class="btn btn-primary">@lang("common.reject") </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $('#sub').on('click', function () {
            $('#myLoader').removeClass('d-none');
            $('#myLoader').addClass('d-flex');
        });
    </script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        function rejectVehicle(id) {
            $.ajax({
                type: "GET",
                data: {user_vehicle_id: id},
                url: "{{ route('merchant.user-vehicle-document') }}",
            }).done(function (data) {
                $("#doc_check_list").html(data);
                $("#user_vehicle_id").val(id);
                $("#exampleModalCenter").modal('show');
            });
        }
    </script>
@endsection
