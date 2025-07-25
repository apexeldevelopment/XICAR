@extends('merchant.layouts.main')
@section('content')
    @php
        $tdt_segment_condition = is_merchant_segment_exist(['TAXI','DELIVERY']);
        $food_grocery = is_merchant_segment_exist(['FOOD','GROCERY','PHARMACY']);
       // $all_food_grocery_clone = \App\Models\Segment::whereIn("sub_group_for_app",[1,2])->get()->pluck("slag")->toArray();
      //  $merchant_segment_group = get_merchant_segment_group();
    @endphp
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                    </div>
                    <h1 class="panel-title">
                        <i class="icon fa-gears" aria-hidden="true"></i>
                        @lang("$string_file.request_configuration")
                    </h1>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              action="{{ route('merchant.booking_configuration.store') }}">
                            @csrf
                            @if(Auth::user()->demo != 1)
                                <h5 class="form-section"><i
                                            class="fa fa-key"></i> @lang("$string_file.google_key_configuration")</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="android_user_key">
                                                @lang("$string_file.android_user_key")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="android_user_key"
                                                   name="android_user_key"
                                                   value="{{ $configuration->android_user_key }}"
                                                   required>
                                            @if ($errors->has('android_user_key'))
                                                <label class="danger">{{ $errors->first('android_user_key') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="android_driver_key">
                                                @lang("$string_file.android_driver_key")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="android_driver_key"
                                                   name="android_driver_key"
                                                   value="{{ $configuration->android_driver_key }}"
                                                   required>
                                            @if ($errors->has('android_driver_key'))
                                                <label class="danger">{{ $errors->first('android_driver_key') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="ios_user_key">
                                                @lang("$string_file.ios_user_key")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="ios_user_key"
                                                   name="ios_user_key"
                                                   value="{{ $configuration->ios_user_key }}"
                                                   required>
                                            @if ($errors->has('ios_user_key'))
                                                <label class="danger">{{ $errors->first('ios_user_key') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="ios_driver_key">
                                                @lang("$string_file.ios_driver_key")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="ios_driver_key"
                                                   name="ios_driver_key"
                                                   value="{{ $configuration->ios_driver_key }}"
                                                   required>
                                            @if ($errors->has('ios_driver_key'))
                                                <label class="danger">{{ $errors->first('ios_driver_key') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="ios_map_load_from">@lang("$string_file.ios_map_load_from")</label>
                                            <select class="form-control" name="ios_map_load_from"
                                                    id="ios_map_load_from"
                                                    required>
                                                <option vlaue="">@lang("$string_file.select")</option>
                                                <option value="1"
                                                        @if($configuration->ios_map_load_from == 1) selected @endif>@lang("$string_file.apple_map")</option>
                                                <option value="2"
                                                        @if($configuration->ios_map_load_from == 2) selected @endif>@lang("$string_file.google_map")</option>
                                            </select>
                                            @if ($errors->has('ios_map_load_from'))
                                                <label class="danger">{{ $errors->first('ios_map_load_from') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="google_key">
                                                @lang("$string_file.google_key_for_api")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="google_key"
                                                   name="google_key"
                                                   placeholder="@lang('admin_x.message154')"
                                                   value="{{ $configuration->google_key }}"
                                                   required>
                                            @if ($errors->has('google_key'))
                                                <label class="danger">{{ $errors->first('google_key') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="google_key_admin">
                                                @lang("$string_file.google_key_for_admin")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="google_key_admin"
                                                   name="google_key_admin"
                                                   placeholder=""
                                                   value="{{ $configuration->google_key_admin }}"
                                                   required>
                                            @if ($errors->has('google_key_admin'))
                                                <label class="danger">{{ $errors->first('admin_x.message892') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    @if($merchant->ApplicationConfiguration->map_box_enable == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="map_box_key">
                                                Map box Key
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="map_box_key"
                                                   name="map_box_key"
                                                   placeholder=""
                                                   value="{{ $configuration->map_box_key }}"
                                                   >
                                            @if ($errors->has('map_box_key'))
                                                <label class="danger">{{ $errors->first('admin_x.message892') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                    @if($merchant->ApplicationConfiguration->here_map_enable == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="here_map_key">
                                                Here Map Key
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="here_map_key"
                                                   name="here_map_key"
                                                   placeholder=""
                                                   value="{{ $configuration->here_map_key }}"
                                                   >
                                            @if ($errors->has('here_map_key'))
                                                <label class="danger">{{ $errors->first('admin_x.message892') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                     @endif
                                </div>
                            @endif
                             <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="slide_button">
                                                @lang("$string_file.slide_button")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="slide_button"
                                                    id="slide_button" required>
                                                <option value="1" {{ $configuration->slide_button == 1 ? 'selected' : ''}}>@lang("$string_file.yes")</option>
                                                <option value="2" {{ $configuration->slide_button == 2 ? 'selected' : ''}}>@lang("$string_file.no")</option>
                                            </select>
                                            @if ($errors->has('slide_button'))
                                                <label class="danger">{{ $errors->first('slide_button') }}</label>
                                            @endif
                                        </div>
                                    </div>
                            <h5 class="form-section"><i
                                        class="fa fa-taxi"></i> @lang("$string_file.general_configuration")</h5>
                            <hr>
                            <div class="row">
                                @if(in_array(1,$merchant_segment_group_config))
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="driver_request_timeout">
                                                @lang("$string_file.driver_request_time_out")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="driver_request_timeout"
                                                   name="driver_request_timeout"
                                                   placeholder="@lang("$string_file.driver_request_time_out")"
                                                   value="{{ $configuration->driver_request_timeout }}"
                                                   required>
                                            @if ($errors->has('driver_request_timeout'))
                                                <label class="danger">{{ $errors->first('driver_request_timeout') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                @if($tdt_segment_condition)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="user_request_timeout">
                                                @lang("$string_file.user_request_time_out")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="user_request_timeout"
                                                   name="user_request_timeout"
                                                   placeholder="@lang("$string_file.user_request_time_out")"
                                                   value="{{ $configuration->user_request_timeout }}"
                                                   required>
                                            @if ($errors->has('user_request_timeout'))
                                                <label class="danger">{{ $errors->first('user_request_timeout') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tracking_screen_refresh_timeband">
                                                @lang("$string_file.tracking_screen_time_band")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="tracking_screen_refresh_timeband"
                                                   name="tracking_screen_refresh_timeband"
                                                   placeholder="@lang("$string_file.tracking_screen_time_band")"
                                                   value="{{ $configuration->tracking_screen_refresh_timeband }}"
                                                   required>
                                            @if ($errors->has('tracking_screen_refresh_timeband'))
                                                <label class="danger">{{ $errors->first('tracking_screen_refresh_timeband') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <!-- <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="slide_button">
                                                @lang("$string_file.slide_button")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="slide_button"
                                                    id="slide_button" required>
                                                <option value="1" {{ $configuration->slide_button == 1 ? 'selected' : ''}}>@lang("$string_file.yes")</option>
                                                <option value="2" {{ $configuration->slide_button == 2 ? 'selected' : ''}}>@lang("$string_file.no")</option>
                                            </select>
                                            @if ($errors->has('slide_button'))
                                                <label class="danger">{{ $errors->first('slide_button') }}</label>
                                            @endif
                                        </div>
                                    </div> -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="drop_location_request">@lang("$string_file.driver_drop_location_visibility_on_request")</label>
                                            <select class="form-control"
                                                    name="drop_location_request"
                                                    id="drop_location_request"
                                                    required>
                                                <option value="1"
                                                        @if($configuration->drop_location_request == 1) selected @endif>@lang("$string_file.yes")</option>
                                                <option value="2"
                                                        @if($configuration->drop_location_request == 2) selected @endif>@lang("$string_file.no")</option>
                                            </select>
                                            @if ($errors->has('drop_location_request'))
                                                <label class="danger">{{ $errors->first('drop_location_request') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="estimate_fare_request">@lang("$string_file.estimate_fare_request")</label>
                                            <select class="form-control"
                                                    name="estimate_fare_request"
                                                    id="estimate_fare_request"
                                                    required>
                                                <option value="1"
                                                        @if($configuration->estimate_fare_request == 1) selected @endif>@lang("$string_file.yes")</option>
                                                <option value="2"
                                                        @if($configuration->estimate_fare_request == 2) selected @endif>@lang("$string_file.no")</option>
                                            </select>
                                            @if ($errors->has('estimate_fare_request'))
                                                <label class="danger">{{ $errors->first('estimate_fare_request') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="number_of_driver_user_map">
                                                @lang("$string_file.no_of_drivers_on_user_map")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="number_of_driver_user_map"
                                                   name="number_of_driver_user_map"
                                                   placeholder="@lang('admin_x.message149')"
                                                   value="{{ $configuration->number_of_driver_user_map }}"
                                                   required>
                                            @if ($errors->has('number_of_driver_user_map'))
                                                <label class="danger">{{ $errors->first('number_of_driver_user_map') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="booking_eta">@lang("$string_file.booking_eta")</label>
                                            <select class="form-control" name="booking_eta"
                                                    id="booking_eta"
                                                    required>
                                                <option value="1"
                                                        @if($configuration->booking_eta == 1) selected @endif>@lang("$string_file.yes")</option>
                                                <option value="2"
                                                        @if($configuration->booking_eta == 2) selected @endif>@lang("$string_file.no")</option>
                                            </select>
                                            @if ($errors->has('booking_eta'))
                                                <label class="danger">{{ $errors->first('booking_eta') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="ride_later_cancel_hour">
                                                @lang("$string_file.time_gap")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="partial_accept_hours"
                                                   name="partial_accept_hours"
                                                   placeholder=""
                                                   value="{{ $configuration->partial_accept_hours }}"
                                                   required>
                                            @if ($errors->has('partial_accept_hours'))
                                                <label class="danger">{{ $errors->first('partial_accept_hours') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="partial_accept_before_hours">
                                                @lang("$string_file.partial_accept_before_hours")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="partial_accept_before_hours"
                                                   name="partial_accept_before_hours"
                                                   placeholder=""
                                                   value="{{ $configuration->partial_accept_before_hours }}"
                                                   required>
                                            @if ($errors->has('partial_accept_before_hours'))
                                                <label class="danger">{{ $errors->first('partial_accept_before_hours') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.auto_cancel_expired_rides")</label>
                                            <select class="form-control"
                                                    name="auto_cancel_expired_rides"
                                                    id="auto_cancel_expired_rides"
                                                    required>
                                                <option value="1"
                                                        @if($configuration->auto_cancel_expired_rides == 1) selected @endif>@lang("$string_file.yes")</option>
                                                <option value="0"
                                                        @if($configuration->auto_cancel_expired_rides == 0) selected @endif>@lang("$string_file.no")</option>
                                            </select>
                                            @if ($errors->has('auto_cancel_expired_rides'))
                                                <label class="danger">{{ $errors->first('auto_cancel_expired_rides') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.ride_later_max_num_days")</label>
                                            <input type="number" class="form-control"
                                                   name="ride_later_max_num_days"
                                                   value="{{$configuration->ride_later_max_num_days}}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">
                                                @lang("$string_file.ride_later_cancel_hour")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="ride_later_cancel_hour"
                                                   name="ride_later_cancel_hour"
                                                   placeholder=""
                                                   value="{{ $configuration->ride_later_cancel_hour }}"
                                                   step="any"
                                                   min="0" max="10"
                                                   required>
                                            @if ($errors->has('ride_later_cancel_hour'))
                                                <label class="danger">{{ $errors->first('ride_later_cancel_hour') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.location_update_timeband")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="location_update_timeband"
                                                   name="location_update_timeband"
                                                   placeholder=""
                                                   value="{{ $configuration->location_update_timeband }}"
                                                   required>
                                            @if ($errors->has('location_update_timeband'))
                                                <label class="danger">{{ $errors->first('location_update_timeband') }}</label>
                                            @endif
                                        </div>
                                    </div> --}}
                                @endif
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="additional_note">@lang("$string_file.additional_notes") </label>
                                        <select class="form-control" name="additional_note"
                                                id="additional_note"
                                                required>
                                            <option value="1"
                                                    @if($configuration->additional_note == 1) selected @endif>@lang("$string_file.yes")</option>
                                            <option value="2"
                                                    @if($configuration->additional_note == 2) selected @endif>@lang("$string_file.no")</option>
                                        </select>
                                        @if ($errors->has('additional_note'))
                                            <label class="danger">{{ $errors->first('additional_note') }}</label>
                                        @endif
                                    </div>
                                </div>
                                @if($configuration->sos_feature_enable == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="sos_driver_count">@lang("$string_file.sos")  @lang("$string_file.drivers") @lang("$string_file.count") </label>
                                            <input type="text" class="form-control" name="sos_driver_count" id="sos_driver_count" value="{{ $configuration->sos_driver_count }}" required>
                                            @if ($errors->has('sos_driver_count'))
                                                <label class="danger">{{ $errors->first('sos_driver_count') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="sos_driver_count">@lang("$string_file.sos") @lang("$string_file.radius") @lang("$string_file.for")  @lang("$string_file.drivers")  </label>
                                                <input type="text" class="form-control" name="sos_driver_radius" id="sos_driver_radius" value="{{ $configuration->sos_driver_radius }}" required>
                                                @if ($errors->has('sos_driver_radius'))
                                                    <label class="danger">{{ $errors->first('sos_driver_radius') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                @endif
                                @if($merchant->Configuration->ride_later_cancel_in_cancel_hour_enable == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.ride_later_cancel_enable_in_cancel_hour")</label>
                                            <select name="ride_later_cancel_enable_in_cancel_hour"
                                                    class="form-control">
                                                <option value="2">@lang("$string_file.disable")</option>
                                                <option value="1" {{($configuration->ride_later_cancel_enable_in_cancel_hour == 1) ? 'selected' : ''}}>
                                                    @lang("$string_file.enable")
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.ride_later_cancel_charge_in_cancel_hour")</label>
                                            <input name="ride_later_cancel_charge_in_cancel_hour"
                                                   value="{{$configuration->ride_later_cancel_charge_in_cancel_hour}}"
                                                   class="form-control"/>
                                        </div>
                                    </div>
                                @endif
                                @if ($configuration->ride_later_payment_types_enable == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.ride_later_payment_types")</label>
                                            <select class="form-control select2"
                                                    name="ride_later_payment_types[]" multiple>
                                                @foreach ($paymentmethods as $payment)
                                                    <option value="{{$payment->id}}"
                                                            {{($configuration->ride_later_payment_types != null && in_array($payment->id , json_decode($configuration->ride_later_payment_types))) ? 'selected' : '' }}
                                                    >
                                                        {{ $payment->payment_method }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif
                                @if(isset($merchant->ApplicationConfiguration->delivery_app_theme) && $merchant->ApplicationConfiguration->delivery_app_theme == 3)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="volumetric_capacity_calculation">@lang("$string_file.volumetric_capacity_calculation")</label>
                                        <input type="number" class="form-control" name="volumetric_capacity_calculation" id="volumetric_capacity_calculation" step="0.02" value="{{ number_format((float) $merchant->BookingConfiguration->volumetric_capacity_calculation, 2, '.', '') }}" required>
                                        @if ($errors->has('volumetric_capacity_calculation'))
                                            <label class="danger">{{ $errors->first('volumetric_capacity_calculation') }}</label>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            {{-- Taxi Configuration --}}
                            <h5 class="form-section"><i
                                        class="fa fa-taxi"></i> @lang("$string_file.ride_notification_config")</h5>
                            <hr>
                            @if($tdt_segment_condition)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="request_show_price">@lang("$string_file.request_show_price") </label>
                                        <select class="form-control" name="request_show_price"
                                                id="request_show_price"
                                                required>
                                            <option value="1"
                                                    @if($configuration->request_show_price == 1) selected @endif>@lang("$string_file.yes")</option>
                                            <option value="2"
                                                    @if($configuration->request_show_price == 2) selected @endif>@lang("$string_file.no")</option>
                                        </select>
                                        @if ($errors->has('request_show_price'))
                                            <label class="danger">{{ $errors->first('request_show_price') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="request_customer_details">@lang("$string_file.request_customer_details") </label>
                                        <select class="form-control" name="request_customer_details"
                                                id="request_customer_details"
                                                required>
                                            <option value="1"
                                                    @if($configuration->request_customer_details == 1) selected @endif>@lang("$string_file.yes")</option>
                                            <option value="2"
                                                    @if($configuration->request_customer_details == 2) selected @endif>@lang("$string_file.no")</option>
                                        </select>
                                        @if ($errors->has('request_customer_details'))
                                            <label class="danger">{{ $errors->first('request_customer_details') }}</label>
                                        @endif
                                    </div>
                                </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="request_distance">@lang("$string_file.request_distance") </label>
                                            <select class="form-control" name="request_distance"
                                                    id="request_distance"
                                                    required>
                                                <option value="1"
                                                        @if($configuration->request_distance == 1) selected @endif>@lang("$string_file.yes")</option>
                                                <option value="2"
                                                        @if($configuration->request_distance == 2) selected @endif>@lang("$string_file.no")</option>
                                            </select>
                                            @if ($errors->has('request_distance'))
                                                <label class="danger">{{ $errors->first('request_distance') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="request_payment_method">@lang("$string_file.request_payment_method") </label>
                                    <select class="form-control" name="request_payment_method"
                                            id="request_payment_method"
                                            required>
                                        <option value="1"
                                                @if($configuration->request_payment_method == 1) selected @endif>@lang("$string_file.yes")</option>
                                        <option value="2"
                                                @if($configuration->request_payment_method == 2) selected @endif>@lang("$string_file.no")</option>
                                    </select>
                                    @if ($errors->has('request_payment_method'))
                                        <label class="danger">{{ $errors->first('request_payment_method') }}</label>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if(in_array('CARPOOLING',$merchant_segment))
                                <h5 class="form-section"><i
                                            class="fa fa-truck"></i> @lang("carpooling.carpooling") @lang("common.configuration")
                                </h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize"> @lang("$string_file.drop") @lang("common.location") @lang("common.radius")</label>
                                            <input name="drop_location_radius" class="form-control"
                                                   value="{{ $car_pooling_Configuration ? $car_pooling_Configuration->drop_location_radius  : ''}}"
                                                   placeholder="@lang('common.enter') @lang("$string_file.drop") @lang("common.location") @lang("common.radius")  @lang("common.in")  @lang("common.km")">
                                            @if($errors->first('drop_location_radius'))
                                                <span class="text-danger">{{$errors->first('drop_location_radius')}}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize"> @lang("common.number") @lang("common.of") @lang("$string_file.drop")</label>
                                            <input name="number_of_drops" class="form-control"
                                                   value="{{ $car_pooling_Configuration ? $car_pooling_Configuration->number_of_drops  : ''}}"
                                                   placeholder="@lang('common.enter') @lang("common.number") @lang("common.of") @lang("$string_file.drop")">
                                            @if($errors->first('number_of_drops'))
                                                <span class="text-danger">{{$errors->first('number_of_drops')}}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize"> @lang("common.number") @lang("common.of") @lang("$string_file.ride") @lang("common.to") @lang("common.show") @lang("common.user")</label>
                                            <input name="no_of_rides_to_show_user" class="form-control"
                                                   value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->no_of_rides_to_show_user  : ''}}"
                                                   placeholder="@lang('common.enter') @lang("common.number") @lang("common.of") @lang("$string_file.rides") @lang("common.to") @lang("common.show") @lang("common.user")">
                                            @if($errors->first('no_of_rides_to_show_user'))
                                                <span class="text-danger">{{$errors->first('no_of_rides_to_show_user')}}</span>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="text-capitalize"> @lang("common.user") @lang("$string_file.ride") @lang("common.start") @lang("common.time")
                                            <span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">
                                            <input type="text" name="user_ride_start_time" id="ride"
                                                   class="form-control" aria-label="Text input with checkbox"
                                                   value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->user_ride_start_time  : ''}}"
                                                   placeholder="@lang('common.enter') @lang("common.time") @lang("common.in") @lang("common.minute")">
                                            @if($errors->first('user_ride_start_time'))
                                                <span class="text-danger">{{$errors->first('user_ride_start_time')}}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-capitalize"> @lang("common.user") @lang("common.document") @lang("common.reminder_expire_doc")
                                            <span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">

                                            <input type="text" name="user_document_reminder_time" id="document"
                                                   class="form-control" aria-label="Text input with checkbox"
                                                   placeholder="@lang('common.enter') @lang("common.time") @lang("common.in") @lang("common.days")"
                                                   value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->user_document_reminder_time  : ''}}">
                                            @if($errors->first('user_document_reminder_time'))
                                                <span class="text-danger">{{$errors->first('user_document_reminder_time')}}</span>
                                            @endif
                                        </div>
                                    </div>
                                    {{--                                    <div class="col-md-4">--}}
                                    {{--                                        <label class="text-capitalize"> @lang("common.offer") @lang("$string_file.ride") @lang("common.cancel") @lang("common.time")--}}
                                    {{--                                            <span class="text-danger">*</span></label>--}}
                                    {{--                                        <div class="input-group mb-3">--}}
                                    {{--                                            <input type="text" name="offer_ride_cancel_time" id="cancel"--}}
                                    {{--                                                   class="form-control" aria-label="Text input with checkbox"--}}
                                    {{--                                                   placeholder="@lang('common.enter') @lang("common.time") @lang("common.in") @lang("common.hour")" value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->offer_ride_cancel_time  : ''}}">--}}
                                    {{--                                            @if($errors->first('offer_ride_cancel_time'))--}}
                                    {{--                                                <span class="text-danger">{{$errors->first('offer_ride_cancel_time')}}</span>--}}
                                    {{--                                            @endif--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                </div>
                                {{--                                <div class="row">--}}
                                {{--                                    <div class="col-md-4">--}}
                                {{--                                        <label class="text-capitalize"> @lang("common.offer") @lang("$string_file.ride") @lang("common.cancel") @lang("common.radius")--}}
                                {{--                                            <span class="text-danger">*</span></label>--}}
                                {{--                                        <div class="input-group mb-3">--}}
                                {{--                                            <input type="text" name="offer_ride_cancel_radius" id="cancel"--}}
                                {{--                                                   class="form-control" aria-label="Text input with checkbox"--}}
                                {{--                                                   placeholder="@lang('common.enter') @lang("common.radius") @lang("common.in") @lang("common.km")" value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->offer_ride_cancel_radius  : ''}}">--}}
                                {{--                                            @if($errors->first('offer_ride_cancel_radius'))--}}
                                {{--                                                <span class="text-danger">{{$errors->first('offer_ride_cancel_radius')}}</span>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="col-md-4">--}}
                                {{--                                        <label class="text-capitalize"> @lang("common.offer") @lang("$string_file.ride") @lang("common.cancel") @lang("common.amount")--}}
                                {{--                                            <span class="text-danger">*</span></label>--}}
                                {{--                                        <div class="input-group mb-3">--}}
                                {{--                                            <input type="text" name="amount_deduct_in_cancel_offer_ride" id="cancel"--}}
                                {{--                                                   class="form-control" aria-label="Text input with checkbox"--}}
                                {{--                                                   placeholder="@lang('common.enter') @lang("common.amount") @lang("common.offer") @lang("$string_file.ride") @lang("common.cancel")" value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->amount_deduct_in_cancel_offer_ride  : ''}}">--}}
                                {{--                                            @if($errors->first('amount_deduct_in_cancel_offer_ride'))--}}
                                {{--                                                <span class="text-danger">{{$errors->first('amount_deduct_in_cancel_offer_ride')}}</span>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="col-md-4">--}}
                                {{--                                        <label class="text-capitalize"> @lang("common.taken") @lang("$string_file.ride") @lang("common.cancel") @lang("common.time")--}}
                                {{--                                            <span class="text-danger">*</span></label>--}}
                                {{--                                        <div class="input-group mb-3">--}}
                                {{--                                            <input type="text" name="taken_ride_cancel_time" id="taken_ride_cancel_time"--}}
                                {{--                                                   class="form-control" aria-label="Text input with checkbox"--}}
                                {{--                                                   placeholder="@lang('common.enter') @lang("common.time") @lang("common.in") @lang("common.hour")" value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->taken_ride_cancel_time  : ''}}">--}}
                                {{--                                            @if($errors->first('taken_ride_cancel_time'))--}}
                                {{--                                                <span class="text-danger">{{$errors->first('taken_ride_cancel_time')}}</span>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="row">--}}
                                {{--                                    <div class="col-md-4">--}}
                                {{--                                        <label class="text-capitalize"> @lang("common.taken") @lang("$string_file.ride") @lang("common.cancel") @lang("common.radius")--}}
                                {{--                                            <span class="text-danger">*</span></label>--}}
                                {{--                                        <div class="input-group mb-3">--}}
                                {{--                                            <input type="text" name="taken_ride_cancel_radius" id="taken_ride_cancel_time"--}}
                                {{--                                                   class="form-control" aria-label="Text input with checkbox"--}}
                                {{--                                                   placeholder="@lang('common.enter') @lang("common.radius") @lang("common.in") @lang("common.km")" value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->taken_ride_cancel_radius  : ''}}">--}}
                                {{--                                            @if($errors->first('taken_ride_cancel_radius'))--}}
                                {{--                                                <span class="text-danger">{{$errors->first('taken_ride_cancel_radius')}}</span>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="col-md-4">--}}
                                {{--                                        <label class="text-capitalize"> @lang("common.taken") @lang("$string_file.ride") @lang("common.cancel") @lang("common.company") @lang("common.cut")--}}
                                {{--                                            <span class="text-danger">*</span></label>--}}
                                {{--                                        <div class="input-group mb-3">--}}
                                {{--                                            <input type="text" name="taken_ride_cancel_company_cut" id="taken_ride_cancel_company_cut"--}}
                                {{--                                                   class="form-control" aria-label="Text input with checkbox"--}}
                                {{--                                                   placeholder="@lang('common.enter') @lang("common.amount") @lang("common.of") @lang("common.company") @lang("common.cut")" value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->taken_ride_cancel_company_cut  : ''}}">--}}
                                {{--                                            @if($errors->first('taken_ride_cancel_company_cut'))--}}
                                {{--                                                <span class="text-danger">{{$errors->first('taken_ride_cancel_company_cut')}}</span>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="col-md-4">--}}
                                {{--                                        <label class="text-capitalize"> @lang("common.taken") @lang("$string_file.ride") @lang("common.cancel") @lang("common.user") @lang("common.cut")--}}
                                {{--                                            <span class="text-danger">*</span></label>--}}
                                {{--                                        <div class="input-group mb-3">--}}
                                {{--                                            <input type="text" name="taken_ride_cancel_user_cut" id="taken_ride_cancel_user_cut"--}}
                                {{--                                                   class="form-control" aria-label="Text input with checkbox"--}}
                                {{--                                                   placeholder="@lang('common.enter') @lang("common.amount") @lang("common.of") @lang("common.user") @lang("common.cut")" value="{{$car_pooling_Configuration ?  $car_pooling_Configuration->taken_ride_cancel_user_cut  : ''}}">--}}
                                {{--                                            @if($errors->first('taken_ride_cancel_user_cut'))--}}
                                {{--                                                <span class="text-danger">{{$errors->first('taken_ride_cancel_user_cut')}}</span>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                            @endif
                            @if($tdt_segment_condition)
                            <div class="col-md-12">
                                @php $a = isset($configuration->driver_ride_radius_request) ? json_decode($configuration->driver_ride_radius_request,true) : [];  @endphp
                                <label for="driver_ride_radius_request">
                                    @lang("$string_file.driver_ride_radius_request")<span
                                            class="text-danger">*</span>
                                </label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input type="number" class="form-control"
                                                   name="driver_ride_radius_request[]"
                                                   value="@if(array_key_exists(0, $a)){{ $a[0]}}@endif"
                                                   placeholder="@lang('admin_x.driver_ride_radius_request')">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input type="number" class="form-control"
                                                   name="driver_ride_radius_request[]"
                                                   value="@if(array_key_exists(1, $a)){{ $a[1]}}@endif"
                                                   placeholder="@lang('admin_x.driver_ride_radius_request')">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input type="number" class="form-control"
                                                   name="driver_ride_radius_request[]"
                                                   value="@if(array_key_exists(2, $a)){{ $a[2]}}@endif"
                                                   placeholder="@lang('admin_x.driver_ride_radius_request')"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($configuration->driver_cancel_ride_after_time == 1)
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="driver_cancel_after_time">
                                                @lang("$string_file.driver") @lang("$string_file.booking") @lang("$string_file.cancel") @lang("$string_file.after") @lang("$string_file.time")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="driver_cancel_after_time"
                                                   name="driver_cancel_after_time"
                                                   placeholder=""
                                                   value="{{ isset($configuration->driver_cancel_after_time) ? $configuration->driver_cancel_after_time :0 }}"
                                                   required>
                                            @if ($errors->has('driver_cancel_after_time'))
                                                <label class="danger">{{ $errors->first('driver_cancel_after_time') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <br>
                           @if($all_food_grocery_clone)
                            <h5 class="form-section"><i
                                        class="fa fa-home"></i> @lang("$string_file.restaurant") / @lang("$string_file.store_configuration")
                            </h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="store_radius_from_user">
                                            @lang("$string_file.restaurant") / @lang("$string_file.store_radius_from_user") (@lang("$string_file.km"))
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control"
                                               id="store_radius_from_user"
                                               name="store_radius_from_user"
                                               placeholder=""
                                               value="{{ isset($configuration->store_radius_from_user) ? $configuration->store_radius_from_user :0 }}"
                                               required>
                                        @if ($errors->has('store_radius_from_user'))
                                            <label class="danger">{{ $errors->first('store_radius_from_user') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($tdt_segment_condition)
                                @if(in_array(1,$service_types) || $tdt_segment_condition)
                                    <br>
                                    <h5 class="form-section"><i
                                                class="fa fa-taxi"></i> @lang("$string_file.ride_allocation_setting")
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="normal_ride_now_request_driver">
                                                    @lang("$string_file.normal_request_driver")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="normal_ride_now_request_driver"
                                                       name="normal_ride_now_request_driver"
                                                       placeholder="@lang('admin_x.message156')"
                                                       value="{{ $configuration->normal_ride_now_request_driver }}"
                                                       required>
                                                @if ($errors->has('normal_ride_now_request_driver'))
                                                    <label class="danger">{{ $errors->first('normal_ride_now_request_driver') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        @if(in_array(1,$service_types))
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="normal_ride_now_drop_location">
                                                        @lang("$string_file.normal_ride_now_drop_location")
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-control"
                                                            name="normal_ride_now_drop_location"
                                                            id="normal_ride_now_drop_location" required>
                                                        <option value="1"
                                                                @if($configuration->normal_ride_now_drop_location == 1) selected @endif>@lang("$string_file.yes")</option>
                                                        <option value="2"
                                                                @if($configuration->normal_ride_now_drop_location == 2) selected @endif>@lang("$string_file.no")</option>
                                                    </select>
                                                    @if ($errors->has('normal_ride_now_drop_location'))
                                                        <label class="danger">{{ $errors->first('normal_ride_now_drop_location') }}</label>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="normal_ride_later_request_type">
                                                    @lang("$string_file.ride_later_request")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control"
                                                        name="normal_ride_later_request_type"
                                                        id="normal_ride_later_request_type"
                                                        onchange="cronJob(this.value)" required>
                                                    <option value="1"
                                                            @if($configuration->normal_ride_later_request_type == 1) selected @endif>@lang("$string_file.all_drivers")</option>
                                                    <option value="2"
                                                            @if($configuration->normal_ride_later_request_type == 2) selected @endif>@lang("$string_file.cron_job")</option>
                                                </select>
                                                @if ($errors->has('normal_ride_later_request_type'))
                                                    <label class="danger">{{ $errors->first('normal_ride_later_request_type') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="normal_ride_later_booking_hours">
                                                    @lang("$string_file.ride_later_booking_from_current")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="normal_ride_later_booking_hours"
                                                       name="normal_ride_later_booking_hours"
                                                       placeholder=""
                                                       value="{{ $configuration->normal_ride_later_booking_hours }}"
                                                       required>
                                                @if ($errors->has('normal_ride_later_booking_hours'))
                                                    <label class="danger">{{ $errors->first('normal_ride_later_booking_hours') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="firstName3">
                                                    @lang("$string_file.distance_radius_for_ride_later")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="normal_ride_later_radius"
                                                       name="normal_ride_later_radius"
                                                       placeholder="@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )"
                                                       value="{{ $configuration->normal_ride_later_radius }}"
                                                       required>
                                                @if ($errors->has('normal_ride_later_radius'))
                                                    <label class="danger">{{ $errors->first('normal_ride_later_radius') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        @if(in_array(1,$service_types))
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="normal_ride_later_drop_location">
                                                    @lang("$string_file.normal_ride_later_drop_location")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control"
                                                        name="normal_ride_later_drop_location"
                                                        id="normal_ride_later_drop_location"
                                                        required>
                                                    <option value="1"
                                                            @if($configuration->normal_ride_later_drop_location == 1) selected @endif>@lang("$string_file.yes")</option>
                                                    <option value="2"
                                                            @if($configuration->normal_ride_later_drop_location == 2) selected @endif>@lang("$string_file.no")</option>
                                                </select>
                                                @if ($errors->has('normal_ride_later_drop_location'))
                                                    <label class="danger">{{ $errors->first('normal_ride_later_drop_location') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="normal_ride_later_request_driver">
                                                    @lang("$string_file.ride_later_to_number_of_drivers")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="normal_ride_later_request_driver"
                                                       name="normal_ride_later_request_driver"
                                                       placeholder="@lang("$string_file.no_of_drivers")"
                                                       value="{{ $configuration->normal_ride_later_request_driver }}"
                                                       required>
                                                @if ($errors->has('normal_ride_later_request_driver'))
                                                    <label class="danger">{{ $errors->first('normal_ride_later_request_driver') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="normal_ride_later_time_before">
                                                    @lang("$string_file.ride_later_start_time_before")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="normal_ride_later_time_before"
                                                       name="normal_ride_later_time_before"
                                                       placeholder=""
                                                       value="{{ $configuration->normal_ride_later_time_before }}"
                                                       required>
                                                @if ($errors->has('normal_ride_later_time_before'))
                                                    <label class="danger">{{ $errors->first('normal_ride_later_time_before') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 custom-hidden"
                                             id="normal_ride_later_cron_hour">
                                            <div class="form-group">
                                                <label for="normal_ride_later_cron_hour">
                                                    @lang("$string_file.cronJob")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="normal_ride_later_cron_hour"
                                                       name="normal_ride_later_cron_hour"
                                                       placeholder=""
                                                       value="{{ $configuration->normal_ride_later_cron_hour }}">
                                                @if ($errors->has('normal_ride_later_cron_hour'))
                                                    <label class="danger">{{ $errors->first('normal_ride_later_cron_hour') }}</label>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                @endif
                                @if(in_array(2,$service_types))
                                    <br>
                                    <h5 class="form-section"><i
                                                class="fa fa-taxi"></i> @lang("$string_file.rental_configuration")
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="riderentaldistance">
                                                    @lang("$string_file.rental_distance_radius")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="riderentaldistance"
                                                       name="rental_ride_now_radius"
                                                       placeholder="@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )"
                                                       value="{{ $configuration->rental_ride_now_radius }}"
                                                       required>
                                                @if ($errors->has('rental_ride_now_radius'))
                                                    <label class="danger">{{ $errors->first('rental_ride_now_radius') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_ride_now_request_driver">
                                                    @lang("$string_file.rental_ride_request_drivers") <span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="rental_ride_now_request_driver"
                                                       name="rental_ride_now_request_driver"
                                                       placeholder=""
                                                       value="{{ $configuration->rental_ride_now_request_driver }}"
                                                       required>
                                                @if ($errors->has('rental_ride_now_request_driver'))
                                                    <label class="danger">{{ $errors->first('rental_ride_now_request_driver') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_ride_now_drop_location">
                                                    @lang("$string_file.rental_ride_now_drop_location")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control"
                                                        name="rental_ride_now_drop_location"
                                                        id="rental_ride_now_drop_location" required>
                                                    <option value="1"
                                                            @if($configuration->rental_ride_now_drop_location == 1) selected @endif>@lang("$string_file.yes")</option>
                                                    <option value="2"
                                                            @if($configuration->rental_ride_now_drop_location == 2) selected @endif>@lang("$string_file.no")</option>
                                                </select>
                                                @if ($errors->has('rental_ride_now_drop_location'))
                                                    <label class="danger">{{ $errors->first('rental_ride_now_drop_location') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_ride_later_request_type">
                                                    @lang("$string_file.rental_ride_later_request_type")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control"
                                                        name="rental_ride_later_request_type"
                                                        id="rental_ride_later_request_type"
                                                        required>
                                                    <option value="1"
                                                            @if($configuration->rental_ride_later_request_type == 1) selected @endif>@lang("$string_file.all_drivers")</option>
                                                    <option value="2"
                                                            @if($configuration->rental_ride_later_request_type == 2) selected @endif>@lang("$string_file.cron_job")</option>
                                                </select>
                                                @if ($errors->has('rental_ride_later_request_type'))
                                                    <label class="danger">{{ $errors->first('rental_ride_later_request_type') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_ride_later_booking_hours">
                                                    @lang("$string_file.rental_ride_later_booking_hours")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="rental_ride_later_booking_hours"
                                                       name="rental_ride_later_booking_hours"
                                                       placeholder=""
                                                       value="{{ $configuration->rental_ride_later_booking_hours }}"
                                                       required>
                                                @if ($errors->has('rental_ride_later_booking_hours'))
                                                    <label class="danger">{{ $errors->first('rental_ride_later_booking_hours') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_ride_later_radius">
                                                    @lang("$string_file.rental_ride_later_radius")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="rental_ride_later_radius"
                                                       name="rental_ride_later_radius"
                                                       placeholder="@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )"
                                                       value="{{ $configuration->rental_ride_later_radius }}"
                                                       required>
                                                @if ($errors->has('rental_ride_later_radius'))
                                                    <label class="danger">{{ $errors->first('rental_ride_later_radius') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_ride_later_drop_location">
                                                    @lang("$string_file.rental_ride_later_drop_location")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control"
                                                        name="rental_ride_later_drop_location"
                                                        id="rental_ride_later_drop_location"
                                                        required>
                                                    <option value="1"
                                                            @if($configuration->rental_ride_later_drop_location == 1) selected @endif>@lang("$string_file.yes")</option>
                                                    <option value="2"
                                                            @if($configuration->rental_ride_later_drop_location == 2) selected @endif>@lang("$string_file.no")</option>
                                                </select>
                                                @if ($errors->has('rental_ride_later_drop_location'))
                                                    <label class="danger">{{ $errors->first('rental_ride_later_drop_location') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_ride_later_request_driver">
                                                    @lang("$string_file.rental_ride_later_request_driver")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="rental_ride_later_request_driver"
                                                       name="rental_ride_later_request_driver"
                                                       placeholder=""
                                                       value="{{ $configuration->rental_ride_later_request_driver }}"
                                                       required>
                                                @if ($errors->has('rental_ride_later_request_driver'))
                                                    <label class="danger">{{ $errors->first('rental_ride_later_request_driver') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rental_ride_later_time_before">
                                                    @lang("$string_file.rental_ride_later_time_before")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="rental_ride_later_time_before"
                                                       name="rental_ride_later_time_before"
                                                       placeholder=""
                                                       value="{{ $configuration->rental_ride_later_time_before }}"
                                                       required>
                                                @if ($errors->has('rental_ride_later_time_before'))
                                                    <label class="danger">{{ $errors->first('rental_ride_later_time_before') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 custom-hidden"
                                             id="rental_ride_later_cron_hour">
                                            <div class="form-group">
                                                <label for="rental_ride_later_cron_hour">
                                                    @lang("$string_file.cronJob")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="rental_ride_later_cron_hour"
                                                       name="rental_ride_later_cron_hour"
                                                       placeholder=""
                                                       value="{{ $configuration->rental_ride_later_cron_hour }}"
                                                >
                                                @if ($errors->has('rental_ride_later_cron_hour'))
                                                    <label class="danger">{{ $errors->first('rental_ride_later_cron_hour') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if(in_array(3,$service_types))
                                    <br>
                                    <h5 class="form-section"><i
                                                class="fa fa-taxi"></i> @lang('admin_x.transfer_config')
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="ridetransferdistance">
                                                    @lang('admin_x.transfer_distance_radius')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="ridetransferdistance"
                                                       name="transfer_ride_now_radius"
                                                       placeholder="@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )"
                                                       value="{{ $configuration->transfer_ride_now_radius }}"
                                                       required>
                                                @if ($errors->has('transfer_ride_now_radius'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_now_radius') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transfer_ride_now_request_driver">
                                                    @lang('admin_x.transfer_ride_request_drivers')
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="transfer_ride_now_request_driver"
                                                       name="transfer_ride_now_request_driver"
                                                       placeholder="@lang('admin_x.message156')"
                                                       value="{{ $configuration->transfer_ride_now_request_driver }}"
                                                       required>
                                                @if ($errors->has('transfer_ride_now_request_driver'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_now_request_driver') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transfer_ride_now_drop_location">
                                                    @lang('admin_x.transfer_ride_now_drop_location')
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control"
                                                        name="transfer_ride_now_drop_location"
                                                        id="transfer_ride_now_drop_location"
                                                        required>
                                                    <option value="1"
                                                            @if($configuration->transfer_ride_now_drop_location == 1) selected @endif>@lang("$string_file.yes")</option>
                                                    <option value="2"
                                                            @if($configuration->transfer_ride_now_drop_location == 2) selected @endif>@lang("$string_file.no")</option>
                                                </select>
                                                @if ($errors->has('transfer_ride_now_drop_location'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_now_drop_location') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transfer_ride_later_request_type">
                                                    @lang('admin_x.transfer_ride_later_request_type')
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control"
                                                        name="transfer_ride_later_request_type"
                                                        id="transfer_ride_later_request_type"
                                                        required>
                                                    <option value="1"
                                                            @if($configuration->transfer_ride_later_request_type == 1) selected @endif>@lang("$string_file.all_drivers")</option>
                                                    <option value="2"
                                                            @if($configuration->transfer_ride_later_request_type == 2) selected @endif>@lang("$string_file.cron_job")</option>
                                                </select>
                                                @if ($errors->has('transfer_ride_later_request_type'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_later_request_type') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transfer_ride_later_booking_hours">
                                                    @lang('admin_x.transfer_ride_later_booking_hours')
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="transfer_ride_later_booking_hours"
                                                       name="transfer_ride_later_booking_hours"
                                                       placeholder="@lang('admin_x.message168')"
                                                       value="{{ $configuration->transfer_ride_later_booking_hours }}"
                                                       required>
                                                @if ($errors->has('transfer_ride_later_booking_hours'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_later_booking_hours') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transfer_ride_later_radius">
                                                    @lang('admin_x.transfer_ride_later_radius')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="transfer_ride_later_radius"
                                                       name="transfer_ride_later_radius"
                                                       placeholder="@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )"
                                                       value="{{ $configuration->transfer_ride_later_radius }}"
                                                       required>
                                                @if ($errors->has('transfer_ride_later_radius'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_later_radius') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transfer_ride_later_drop_location">
                                                    @lang('admin_x.transfer_ride_later_drop_location')
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control"
                                                        name="transfer_ride_later_drop_location"
                                                        id="transfer_ride_later_drop_location"
                                                        required>
                                                    <option value="1"
                                                            @if($configuration->transfer_ride_later_drop_location == 1) selected @endif>@lang("$string_file.yes")</option>
                                                    <option value="2"
                                                            @if($configuration->transfer_ride_later_drop_location == 2) selected @endif>@lang("$string_file.no")</option>
                                                </select>
                                                @if ($errors->has('transfer_ride_later_drop_location'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_later_drop_location') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transfer_ride_later_request_driver">
                                                    @lang('admin_x.transfer_ride_later_request_driver')
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="transfer_ride_later_request_driver"
                                                       name="transfer_ride_later_request_driver"
                                                       placeholder="@lang('admin_x.message156')"
                                                       value="{{ $configuration->transfer_ride_later_request_driver }}"
                                                       required>
                                                @if ($errors->has('transfer_ride_later_request_driver'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_later_request_driver') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transfer_ride_later_time_before">
                                                    @lang('admin_x.transfer_ride_later_time_before')
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="transfer_ride_later_time_before"
                                                       name="transfer_ride_later_time_before"
                                                       placeholder="@lang('admin_x.message148')"
                                                       value="{{ $configuration->rental_ride_later_time_before }}"
                                                       required>
                                                @if ($errors->has('transfer_ride_later_time_before'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_later_time_before') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4 custom-hidden"
                                             id="transfer_ride_later_cron_hour">
                                            <div class="form-group">
                                                <label for="transfer_ride_later_cron_hour">
                                                    @lang('admin_x.cronJob')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="transfer_ride_later_cron_hour"
                                                       name="transfer_ride_later_cron_hour"
                                                       placeholder="@lang('admin_x.message148')"
                                                       value="{{ $configuration->transfer_ride_later_cron_hour }}"
                                                >
                                                @if ($errors->has('transfer_ride_later_cron_hour'))
                                                    <label class="danger">{{ $errors->first('transfer_ride_later_cron_hour') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if(in_array(5,$service_types))
                                    <br>
                                    <h5 class="form-section"><i
                                                class="fa fa-taxi"></i> @lang("$string_file.pool_configuration")
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="pool_radius">
                                                    @lang("$string_file.pool_ride_request")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="pool_radius"
                                                       name="pool_radius"
                                                       placeholder="@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )"
                                                       value="{{ $configuration->pool_radius }}"
                                                       required>
                                                @if ($errors->has('pool_radius'))
                                                    <label class="danger">{{ $errors->first('pool_radius') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="pool_drop_radius">
                                                    @lang("$string_file.pool_radius")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="pool_drop_radius"
                                                       name="pool_drop_radius"
                                                       placeholder="@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )"
                                                       value="{{ $configuration->pool_drop_radius }}"
                                                       required>
                                                @if ($errors->has('pool_drop_radius'))
                                                    <label class="danger">{{ $errors->first('pool_drop_radius') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="pool_now_request_driver">
                                                    @lang("$string_file.pool_request_driver")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="pool_now_request_driver"
                                                       name="pool_now_request_driver"
                                                       placeholder=""
                                                       value="{{ $configuration->pool_now_request_driver }}"
                                                       required>
                                                @if ($errors->has('pool_now_request_driver'))
                                                    <label class="danger">{{ $errors->first('pool_now_request_driver') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="pool_maximum_exceed">
                                                    @lang("$string_file.pool_max_user")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="pool_maximum_exceed"
                                                       name="pool_maximum_exceed"
                                                       placeholder=""
                                                       value="{{ $configuration->pool_maximum_exceed }}"
                                                       required>
                                                @if ($errors->has('pool_maximum_exceed'))
                                                    <label class="danger">{{ $errors->first('pool_maximum_exceed') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if(in_array(4,$service_types))
                                <br>
                                <h5 class="form-section"><i
                                            class="fa fa-taxi"></i> @lang("$string_file.outstation_configuration")
                                </h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="outstation_request_type">
                                                @lang("$string_file.outstation_request")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="outstation_request_type"
                                                    id="outstation_request_type"
                                                    onchange="outstation(obj)" required>
                                                <option value="1"
                                                        @if($configuration->outstation_request_type == 1) selected @endif>@lang("$string_file.all_drivers")</option>
                                                <option value="2"
                                                        @if($configuration->outstation_request_type == 2) selected @endif>@lang("$string_file.cron_job")</option>
                                            </select>
                                            @if ($errors->has('outstation_request_type'))
                                                <label class="danger">{{ $errors->first('outstation_request_type') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="outstation_booking_hours">
                                                @lang("$string_file.outstation_booking_time_from_current")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="outstation_booking_hours"
                                                   name="outstation_booking_hours"
                                                   placeholder=""
                                                   value="{{ $configuration->outstation_booking_hours }}"
                                                   required>
                                            @if ($errors->has('outstation_booking_hours'))
                                                <label class="danger">{{ $errors->first('outstation_booking_hours') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="outstation_radius">
                                                @lang("$string_file.outstation_distance_radius")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="outstation_radius"
                                                   name="outstation_radius"
                                                   placeholder="@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )"
                                                   value="{{ $configuration->outstation_radius }}"
                                                   required>
                                            @if ($errors->has('outstation_radius'))
                                                <label class="danger">{{ $errors->first('outstation_radius') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="outstation_request_driver">
                                                @lang("$string_file.outstation_request_driver")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="outstation_request_driver"
                                                   name="outstation_request_driver"
                                                   placeholder=""
                                                   value="{{ $configuration->outstation_request_driver }}"
                                                   required>
                                            @if ($errors->has('outstation_request_driver'))
                                                <label class="danger">{{ $errors->first('outstation_request_driver') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="outstation_time_before">
                                                @lang("$string_file.outstation_time_before")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="outstation_time_before"
                                                   name="outstation_time_before"
                                                   placeholder=""
                                                   value="{{ $configuration->outstation_time_before }}"
                                                   required>
                                            @if ($errors->has('outstation_time_before'))
                                                <label class="danger">{{ $errors->first('outstation_time_before') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 custom-hidden"
                                         id="outstation_ride_later_cron_hour">
                                        <div class="form-group">
                                            <label for="outstation_ride_later_cron_hour">
                                                @lang("$string_file.cronJob")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control"
                                                   id="outstation_ride_later_cron_hour"
                                                   name="outstation_ride_later_cron_hour"
                                                   placeholder=""
                                                   value="{{ $configuration->outstation_ride_later_cron_hour }}"
                                            >
                                            @if ($errors->has('outstation_ride_later_cron_hour'))
                                                <label class="danger">{{ $errors->first('outstation_ride_later_cron_hour') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($configuration->outstation_ride_now_enabled == 1)
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="outstation_ride_now_radius">
                                                    @lang("$string_file.outstation_ride_now_radius")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="ridetransferdistance"
                                                       name="outstation_ride_now_radius"
                                                       placeholder="@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )"
                                                       value="{{ $configuration->outstation_ride_now_radius }}"
                                                       required>
                                                @if ($errors->has('outstation_ride_now_radius'))
                                                    <label class="danger">{{ $errors->first('outstation_ride_now_radius') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="outstation_ride_now_request_driver">
                                                    @lang("$string_file.outstation_ride_now_request_driver")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="outstation_ride_now_request_driver"
                                                       name="outstaion_ride_now_request_driver"
                                                       placeholder=""
                                                       value="{{ $configuration->outstaion_ride_now_request_driver }}"
                                                       required>
                                                @if ($errors->has('outstaion_ride_now_request_driver'))
                                                    <label class="danger">{{ $errors->first('outstaion_ride_now_request_driver') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="upcoming_notification_time">
                                            @lang("$string_file.upcoming") @lang("$string_file.ride") @lang("$string_file.notification")  @lang("$string_file.time") @lang("$string_file.diffrence") @lang("$string_file.in") @lang("$string_file.minutes")
                                        </label>
                                        <input type="number" class="form-control"
                                               id="upcoming_notification_time"
                                               name="upcoming_notification_time"
                                               value="@if(isset($configuration->upcoming_notification_time)){{$configuration->upcoming_notification_time}}@endif"
                                               step=".01"
                                               required>
                                        @if ($errors->has('upcoming_notification_time'))
                                            <label class="danger">{{ $errors->first('upcoming_notification_time') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @endif
                            <div class="form-actions right" style="margin-bottom: 3%">
                                @if(Auth::user('merchant')->can('edit_configuration'))
                                    @if($edit_permission)
                                    <button type="submit" class="btn btn-primary float-right">
                                        <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                                    </button>
                                    @else
                                        <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                                    @endif
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script>
        function cronJob(obj) {
            $("#loader1").show();
            if (obj == 2) {
                document.getElementById('normal_ride_later_cron_hour').style.display = 'block';
            } else {
                document.getElementById('normal_ride_later_cron_hour').style.display = 'none';
            }
            $("#loader1").hide();
        }

        function rental(obj) {
            $("#loader1").show();
            if (obj == 2) {
                document.getElementById('rental_ride_later_cron_hour').style.display = 'block';
            } else {
                document.getElementById('rental_ride_later_cron_hour').style.display = 'none';
            }
            $("#loader1").hide();
        }

        function outstation(obj) {
            $("#loader1").show();
            if (obj == 2) {
                document.getElementById('outstation_ride_later_cron_hour').style.display = 'block';
            } else {
                document.getElementById('outstation_ride_later_cron_hour').style.display = 'none';
            }
            $("#loader1").hide();
        }

        function transfer(obj) {
            $("#loader1").show();
            if (obj == 2) {
                document.getElementById('transfer_ride_later_cron_hour').style.display = 'block';
            } else {
                document.getElementById('transfer_ride_later_cron_hour').style.display = 'none';
            }
            $("#loader1").hide();
        }

        $(document).ready(function () {
            $('#start_time').change(function () {

                if (this.checked == false) {
                    $('#ride').prop("disabled", true);
                } else {
                    $('#ride').prop("disabled", false);
                }
            });
        });
        $(document).ready(function () {
            $('#start').change(function () {

                if (this.checked == false) {
                    $('#document').prop("disabled", true);
                } else {
                    $('#document').prop("disabled", false);
                }
            });
        });
        $(document).ready(function () {

            $('#offer').change(function () {

                if (this.checked == true || this.checked == null) {
                    $('#cancel').prop("disabled", false);
                } else {
                    $('#cancel').prop("disabled", true);
                }
            });
        });
    </script>
@endsection

