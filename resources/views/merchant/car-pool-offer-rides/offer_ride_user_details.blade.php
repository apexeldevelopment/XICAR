@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ URL::previous() }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="fa fa-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa-info-circle" aria-hidden="true"></i>
                        @lang("$string_file.ride") @lang("$string_file.details") @lang("$string_file.of") #{{$user_ride_details->carpooling_ride_detail_id}}
                    </h3>
                </header>
                <div class="panel-body">
                    <div id="user-profile">
                        <div class="row">
                            <div class="col-md-4 col-xs-12">
                                <div class="card my-2 shadow">
                                    <div class="">
                                        <img class="card-img-top" width="100%" style="height:22vh"
                                             alt="google image" src="{!! $user_ride_details->CarpoolingRideDetail->map_image !!}">
                                    </div>
                                    <div class="card-body ">
                                        <div class="col-md-4 col-sm-4"  style="float:left;">
                                            <img  height="80" width="80"  class="rounded-circle" src="@if ($user_ride_details->User->UserProfileImage) {{ get_image($user_ride_details->User->UserProfileImage,'user') }} @else {{ get_image(null,'user') }} @endif"
                                                  alt="img">

                                        </div>
                                        <div class="card-text col-md-8 col-sm-8 py-2" style="float:left;">
                                            @if(Auth::user()->demo == 1)
                                                <h4 class="user-name">{{ "********".substr($user_ride_details->User->first_name, -2) }}</h4>
                                                <p class="user-job">{{ "********".substr($user_ride_details->User->UserPhone, -2) }}</p>
                                                <p class="user-location">{{ "********".substr($user_ride_details->User->email, -2) }}</p>
                                            @else
                                                <h5 class="user-name">{{ $user_ride_details->User->first_name }}</h5>
                                                <h6 class="user-job">{{ $user_ride_details->User->UserPhone }}</h6>
                                                <h6 class="user-location">{{ $user_ride_details->User->email }}</h6>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card my-2 shadow bg-white h-280">
                                    <div class="justify-content-center p-3">
                                        <div class="col-md-12 col-xs-12 mt-10"
                                             style="text-align:center;justify-content:center">

                                            <img height="80" width="80" class="rounded-circle" src="@if ($user_ride_details->CarpoolingRide->User) {{ get_image($user_ride_details->CarpoolingRide->User->UserProfileImage,'user') }} @else {{ get_image(null,'user') }} @endif">

                                        </div>
                                        <div class="overlay-box">
                                            <div class="user-content " style="text-align:center">
                                                <!-- <a href="javascript:void(0)"> -->
                                            <!-- <img src="@if ($user_ride_details->CarpoolingRide->User) {{ asset($user_ride_details->CarpoolingRide->User->UserProfileImage) }} @else {{ asset("user.png") }} @endif" -->
                                                <!-- class="thumb-lg img-circle" alt="img"> -->
                                                <!-- </a> -->
                                                @if(!empty($user_ride_details->CarpoolingRide->User->id))
                                                    @if(Auth::user()->demo == 1)
                                                        <h5 class="user-name mt-5 mb-5">@if ($user_ride_details->CarpoolingRide->User) {{ "********".substr($booking->Driver->fullName, -2) }} @else  @endif</h5>
                                                        <p class="user-job mb-1 ">@if ($booking->Driver) {{ "********".substr($booking->Driver->email, 2) }} @else  @endif</p>
                                                        <p class="user-location mb-2">@if ($booking->Driver) {{ "********".substr($booking->Driver->phoneNumber, -2) }} @else  @endif</p>
                                                    @else
                                                        <h5 class="user-name mt-5 mb-1">@if ($user_ride_details->CarpoolingRide->User) {{ $user_ride_details->CarpoolingRide->User->first_name }} @else  @endif</h5>
                                                        <p class="user-job mb-1">@if ($user_ride_details->CarpoolingRide->User) {{ $user_ride_details->CarpoolingRide->User->email }} @else  @endif</p>
                                                        <p class="user-location mb-2">@if ($user_ride_details->CarpoolingRide->User) {{ $user_ride_details->CarpoolingRide->User->phoneNumber }} @else  @endif</p>
                                                    @endif
                                                @else
                                                    @lang("$string_file.not") @lang("$string_file.accepted")
                                                @endif
                                            </div>
                                        </div>
                                        <hr/>
                                        <div class="clear"></div>
                                        <div class="user-btm-box">
                                            <div class="col-md-4 col-sm-4" style="float:left;">
                                                <a class="avatar img-bordered avatar-100" href="javascript:void(0)">
                                                    <img src="@if ($user_ride_details->CarpoolingRide->UserVehicle->VehicleType) {{ get_image($user_ride_details->CarpoolingRide->UserVehicle->VehicleType->vehicleTypeImage,'vehicle') }}@endif"
                                                    /></a>
                                            </div>
                                            <div class="col-md-8 col-sm-8" style="float:left;">
                                                <h6 class="user-job">{{ $user_ride_details->CarpoolingRide->UserVehicle->VehicleType->VehicleTypeName }}</h6>
                                                <h6 class="user-location">@if ($user_ride_details->CarpoolingRide->UserVehicle) {{ $user_ride_details->CarpoolingRide->UserVehicle->VehicleMake->VehicleMakeName }}
                                                    :{{  $user_ride_details->CarpoolingRide->UserVehicle->VehicleModel->VehicleModelName }}
                                                    -{{ $user_ride_details->CarpoolingRide->UserVehicle->vehicle_number }} @else  @endif</h6>
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
                                                    @lang("$string_file.pickup") @lang("$string_file.location")</div>
                                                <div class="mb-0">{{ $user_ride_details->pickup_location }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12 mb-5 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="font-weight-500 text-danger text-uppercase mb-1">
                                                    <i class="icon fa-tint fa-2x text-gray-300"></i>
                                                    @lang("$string_file.drop_off") @lang("$string_file.location")</div>
                                                <div class="mb-0">{{ $user_ride_details->drop_location }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 col-sm-6 col-xs-12 mb-5 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="font-weight-500 text-info text-uppercase mb-1">
                                                    <i class="icon fa-money"></i>
                                                    @lang("$string_file.payment")</div>
                                                <div class="mb-0">
                                                    @if($user_ride_details->payment_status = 1)
                                                        <span>{{trans("$string_file.pending")}}</span>b
                                                    @else
                                                    <span>{{trans("$string_file.success")}}</span>
                                                    @endif
                                                </div>
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
                                                    @lang("$string_file.created") @lang("$string_file.at")</div>
                                                <div class="mb-0">
                                                    {{ $user_ride_details->created_at }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection