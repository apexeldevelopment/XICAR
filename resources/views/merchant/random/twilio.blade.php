@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title"><i class="wb-earning" aria-hidden="true"></i>
                        @lang("$string_file.twilio")   @lang("common.configuration") </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              action="{{ route('merchant.gateway.twilio.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.twilio") @lang("$string_file.sid")  <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="api _key" name="api_key"
                                               placeholder=" @lang("$string_file.twilio") @lang("common.key") "
                                               value="@if(!empty($twilio_config)) {{ $twilio_config->api_key}} @endif"
                                               required>
                                        @if ($errors->has('api_key'))
                                            <label class="danger">{{ $errors->first('api_key') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                           @lang("$string_file.twilio") @lang("$string_file.auth") @lang("common.key") <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="auth_token"
                                               name="auth_token"
                                               placeholder="@lang("common.auth") @lang("common.key") "
                                               value="@if(!empty($twilio_config)) {{ $twilio_config->auth_token }} @endif"
                                               required>
                                        @if ($errors->has('auth_token'))
                                            <label class="danger">{{ $errors->first('auth_token') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                           @lang("$string_file.twilio") @lang("common.phone") @lang("common.number") <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="sender_number"
                                               name="sender_number"
                                               placeholder="@lang("common.phone") @lang("common.number") "
                                               value="@if(!empty($twilio_config)) {{ $twilio_config->sender_number }} @endif"
                                               required>
                                        @if ($errors->has('sender_number'))
                                            <label class="danger">{{ $errors->first('sender_number') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if(isset($config->driver_enable) && $config->driver_enable == 1)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("common.ios") @lang("$string_file.driver") @lang("common.app") @lang("common.url")<span class="text-danger">*</span>
                                        </label>
                                        <input type="url" class="form-control" id="ios_driver_link"
                                               name="ios_driver_link"
                                               placeholder="@lang("common.ios") @lang("$string_file.driver") @lang("common.app") @lang("common.url")"
                                               value="@if(!empty($application)) {{ $application->ios_driver_link }} @endif"
                                               required>
                                        @if ($errors->has('ios_driver_link'))
                                            <label class="danger">{{ $errors->first('ios_driver_link') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("common.android") @lang("$string_file.driver") @lang("common.app") @lang("common.url")<span class="text-danger">*</span>
                                        </label>
                                        <input type="url" class="form-control"
                                               id="android_driver_link"
                                               name="android_driver_link"
                                               placeholder="@lang("common.android") @lang("$string_file.driver") @lang("common.app") @lang("common.url")"
                                               value="@if(!empty($application)) {{ $application->android_driver_link }} @endif"
                                               required>
                                        @if ($errors->has('android_driver_link'))
                                            <label class="danger">{{ $errors->first('android_driver_link') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("common.ios") @lang("$string_file.driver") @lang("common.app") @lang("common.id")
                                        </label>
                                        <input type="text" class="form-control"
                                               id="ios_driver_appid"
                                               name="ios_driver_appid"
                                               placeholder="@lang("common.ios") @lang("$string_file.driver") @lang("common.app") @lang("common.id")"
                                               value="@if(!empty($application)) {{ $application->ios_driver_appid }} @endif">
                                        @if ($errors->has('ios_driver_appid'))
                                            <label class="danger">{{ $errors->first('ios_driver_appid') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="form-actions right" style="margin-bottom: 3%">
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-square-o"></i> Save
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection