@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('merchant.offer.rides') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="far fa-car" aria-hidden="true"></i>
                        @lang("$string_file.ride") @lang("$string_file.details")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                @if(!empty($offer_ride_user_details))
                                    <div class="col-md-6">
                                        <div class="card " style="width: auto; ">
                                            <h3 class="panel-title">
                                                <span>@lang("$string_file.offer") @lang("$string_file.user") @lang("$string_file.details")</span>
                                            </h3>

                                            <div class="card-body shadow">

                                                <div class="card-block text-center">
                                                    <img src="{{ get_image($carpooling->User->UserProfileImage,'user') }}"
                                                         class="rounded-circle" width="120" height="120">

                                                    <h5 class="user-name mb-3">{{ $carpooling->User->first_name." ".$carpooling->User->last_name }}</h5>
                                                    <p class="user-job mb-3">{{ $carpooling->User->UserPhone }}</p>
                                                    <p class="user-info mb-3">{{ $carpooling->User->email }}</p>


                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                  
                                @endif
                                <div class="col-md-6">
                                    <h3 class="panel-title">@lang("$string_file.basic") @lang("$string_file.details")</h3>
                                    <div class="card shadow">
                                        <table class="display nowrap table table-hover table-striped w-full"
                                               style="width:100%">
                                            <thead>
                                            <tr>
                                                <th>@lang("$string_file.sn")</th>
                                                <th>@lang("$string_file.booked") @lang("$string_file.seat")</th>
                                                <th>@lang("$string_file.is_return")</th>
                                                <th>@lang("$string_file.map") @lang("$string_file.image")</th>

                                            </tr>
                                            </thead>
                                            <tbody>
                                            @php $sr = $offer_ride_details->firstItem() @endphp
                                            @foreach($offer_ride_details as $ride_details)
                                                <tr>
                                                    <td>{{$sr}}</td>
                                                    <td>{{$carpooling->booked_seats}}</td>
                                                    <td>
                                                        @if($ride_details->is_return == 1)
                                                            <span>@lang("$string_file.yes")</span>
                                                        @else
                                                            <span>@lang("$string_file.no")</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{!! $ride_details->map_image !!}"
                                                           target="_blank">@lang("$string_file.view") @lang("$string_file.map")</a>
                                                    </td>
{{--                                                    <td><span> @lang("$string_file.price") @lang("$string_file.card") @lang("$string_file.id") :- </span>{{$ride_details->bill_details[0]}}--}}
{{--                                                        <br>--}}

{{--                                                    </td>--}}
                                                </tr>
                                                @php $sr++  @endphp
                                            @endforeach
                                            </tbody>

                                        </table>
                                    </div>
                                </div>

                            </div>
                            @if(!empty($carpooling))
                                <div class="row">
                                    <div class="col-md-12">
                                        <h3 class="panel-title">
                                            <span>@lang("$string_file.vehicle") @lang("$string_file.details")</span></h3>
                                        <div class="user-btm-box">
                                            <div class="col-md-4 col-sm-4" style="float:left;">
                                                <a class="avatar img-bordered avatar-100" href="javascript:void(0)">
                                                    <img src="{{ get_image($carpooling->UserVehicle->vehicle_image,'user_vehicle_document') }}"
                                                    /></a>
                                            </div>
                                            <div class="col-md-8 col-sm-8" style="float:left;">
                                                <h5 class="user-name"></h5>
                                                <h6 class="user-job">{{ $carpooling->UserVehicle->VehicleType->vehicleTypeName }}</h6>
                                                <p class="user-job mb-3">{{ $carpooling->UserVehicle->vehicle_number }}</p>
                                                <p class="user-info mb-3">{{ $carpooling->UserVehicle->vehicle_color }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <hr>
                            @if(!empty($offer_ride_user_details))
                             <div class="col-md-6">
                                        <div class="card " style="width: auto; ">
                                            <h3 class="panel-title">
                                                <span>@lang("$string_file.taken") @lang("$string_file.user") @lang("$string_file.details")</span>
                                            </h3>

                                            <div class="card-body shadow">

                                                <div class="card-block text-center">
                                                    <img src="{{ get_image($offer_ride_user_details->User->UserProfileImage,'user') }}"
                                                         class="rounded-circle" width="120" height="120">

                                                    <h5 class="user-name mb-3">{{ $offer_ride_user_details->User->first_name." ".$offer_ride_user_details->User->last_name }}</h5>
                                                    <p class="user-job mb-3">{{ $offer_ride_user_details->User->UserPhone }}</p>
                                                    <p class="user-info mb-3">{{ $offer_ride_user_details->User->email }}</p>


                                                </div>
                                            </div>

                                        </div>
                                    </div>
                            @endif
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card shadow">
                                        <div class="card-header">
                                            <h3 class="panel-title">
                                                <span>@lang("$string_file.ride") @lang("$string_file.other") @lang("$string_file.details")</span>
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="card ">
                                                <table class="display nowrap table table-hover table-striped w-full"
                                                       style="width:100%">
                                                    <thead>
                                                    <tr>
                                                        <th>@lang("$string_file.sn")</th>
                                                        <th>@lang("$string_file.estimate") @lang("$string_file.distance")</th>
                                                        <th>@lang("$string_file.total") @lang("$string_file.charges")</th>
                                                        <th>@lang("$string_file.ride") @lang("$string_file.status")</th>
                                                    </tr>
                                                    </thead>
                                                    <tboday>
                                                        @php $sr = $offer_ride_details->firstItem() @endphp
                                                        @foreach($offer_ride_details as $ride_details)
                                                            <tr>
                                                                <td>{{$sr}}</td>
                                                                <td>{{$ride_details->estimate_distance_text}}</td>
                                                                <td>{{$ride_details->CarpoolingRide->total_amount}}</td>
                                                                <td>
                                                                    @switch($ride_details->ride_status)
                                                                        @case(1)
                                                                        <span>@lang("$string_file.booked")</span>
                                                                        @break
                                                                        @case(2)
                                                                        <span>@lang("$string_file.booked") @lang("$string_file.seat")</span>
                                                                        @break
                                                                        @case(3)
                                                                        <span>  @lang("$string_file.active")@lang("$string_file.ride")</span>
                                                                        @break
                                                                        @case(4)
                                                                        <span>@lang("$string_file.complete") @lang("$string_file.ride")</span>
                                                                        @break
                                                                        @case(5)
                                                                        <span>@lang("$string_file.cancel") @lang("$string_file.ride")</span>
                                                                        @break
                                                                        @case(6)
                                                                        <span>@lang("$string_file.auto") @lang("$string_file.cancel")  @lang("$string_file.ride")</span>
                                                                        @break

                                                                        @default
                                                                        <span>Something went wrong, please try again</span>
                                                                    @endswitch
                                                                </td>

                                                            </tr>
                                                            @php $sr++  @endphp
                                                        @endforeach
                                                    </tboday>

                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>


                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6  float-left">
                                    <h3 class="panel-title">@lang("$string_file.pickup") @lang("$string_file.location")</h3>
                                </div>
                                <div class="col-md-6 float-right ">
                                    <h3 class="panel-title">@lang("$string_file.drop") @lang("$string_file.location")</h3>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-12">
                                    <ul class="timeline timeline-simple">
                                        @foreach($offer_ride_details as $ride_details)
                                            <li class="timeline-item">
                                                <div class="timeline-dot bg-success" data-placement="right"
                                                     data-toggle="tooltip" data-trigger="hover"
                                                     data-original-title=""></div>
                                                <div class="timeline-content">
                                                    <div class="card card-inverse border border-success card-shadow">
                                                        <div class="card-block">
                                                            <p class="card-text"
                                                               style="color:#000000">{{$ride_details->from_location}} </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="timeline-item timeline-reverse">
                                                <div class="timeline-dot bg-danger" data-placement="left"
                                                     data-toggle="tooltip"
                                                     data-trigger="hover" data-original-title=""></div>
                                                <div class="timeline-content">
                                                    <div class="card card-inverse border border-danger card-shadow">
                                                        <div class="card-block">
                                                            <p class="card-text"
                                                               style="color:#000000">{{$ride_details->to_location}} </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>

                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
