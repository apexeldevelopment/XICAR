@extends('merchant.layouts.main')
@section('content')
    @php $tdt_segment_condition = is_merchant_segment_exist(['TAXI','DELIVERY','TOWING']); @endphp
    @php $fg_segment_condition = is_merchant_segment_exist(['FOOD','GROCERY','PHARMACY']); @endphp
    <div class="page">
        <div class="page-content">
           @include("merchant.shared.errors-and-messages")
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
                    <h3 class="panel-title">
                        <i class=" icon fa-gears" aria-hidden="true"></i>
                        @lang("$string_file.general_configuration")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              action="{{ route('merchant.general_configuration.store') }}">
                            @csrf
                            <fieldset>
                                <div class="row">
{{--                                    <div class="col-md-4">--}}
{{--                                        <div class="form-group">--}}
{{--                                            <label for="logo_hide">--}}
{{--                                                @lang("$string_file.logo_hide")--}}
{{--                                                <span class="text-danger">*</span>--}}
{{--                                            </label>--}}
{{--                                            <select class="form-control" name="logo_hide"--}}
{{--                                                    id="logo_hide" required>--}}
{{--                                                <option value="1" {{ $app_configuration->logo_hide == 1 ? 'selected' : ''}}>@lang("$string_file.on")</option>--}}
{{--                                                <option value="0" {{ $app_configuration->logo_hide == 0 ? 'selected' : ''}}>@lang("$string_file.off")</option>--}}
{{--                                            </select>--}}
{{--                                            @if ($errors->has('logo_hide'))--}}
{{--                                                <label class="danger">{{ $errors->first('logo_hide') }}</label>--}}
{{--                                            @endif--}}
{{--                                        </div>--}}
{{--                                    </div>--}}

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.report_issue_email")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" class="form-control"
                                                   id="report_issue_email"
                                                   name="report_issue_email"
                                                   placeholder="@lang("$string_file.report_issue_email")"
                                                   value="{{ $configuration->report_issue_email }}"
                                                   required>
                                            @if ($errors->has('report_issue_email'))
                                                <label class="danger">{{ $errors->first('report_issue_email') }}</label>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.additional")  @lang("$string_file.report_issue_email")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" class="form-control"
                                                   id="additional_report_issue_email"
                                                   name="additional_report_issue_email"
                                                   placeholder="@lang("$string_file.additional") @lang("$string_file.report_issue_email")"
                                                   value="{{ $configuration->additional_report_issue_email }}"
                                                   required>
                                            @if ($errors->has('additional_report_issue_email'))
                                                <label class="danger">{{ $errors->first('additional_report_issue_email') }}</label>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                               @lang("$string_file.report_issue_phone")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="report_issue_phone"
                                                   name="report_issue_phone"
                                                   placeholder=""
                                                   value="{{ $configuration->report_issue_phone }}"
                                                   required>
                                            @if ($errors->has('report_issue_phone'))
                                                <label class="danger">{{ $errors->first('report_issue_phone') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.android_user_maintenance_mode")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="android_user_maintenance_mode"
                                                    id="android_user_maintenance_mode" required>
                                                <option value="1"
                                                @if(isset($configuration->android_user_maintenance_mode)) {{$configuration->android_user_maintenance_mode == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->android_user_maintenance_mode)) {{$configuration->android_user_maintenance_mode == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('android_user_maintenance_mode'))
                                                <label class="danger">{{ $errors->first('android_user_maintenance_mode') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.android_user_app_version")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="android_user_version"
                                                   name="android_user_version"
                                                   placeholder="@lang("$string_file.android_user_app_version")"
                                                   value="{{ $configuration->android_user_version }}"
                                                   required>
                                            @if ($errors->has('android_user_version'))
                                                <label class="danger">{{ $errors->first('android_user_version') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.android_user_app_mandatory_update")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="android_user_mandatory_update"
                                                    id="android_user_mandatory_update" required>
                                                <option value="1"
                                                @if(isset($configuration->android_user_mandatory_update)) {{$configuration->android_user_mandatory_update == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->android_user_mandatory_update)) {{$configuration->android_user_mandatory_update == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('android_user_mandatory_update'))
                                                <label class="danger">{{ $errors->first('android_user_mandatory_update') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.android_driver_maintenance_mode")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="android_driver_maintenance_mode"
                                                    id="android_driver_maintenance_mode" required>
                                                <option value="1"
                                                @if(isset($configuration->android_driver_maintenance_mode)) {{$configuration->android_driver_maintenance_mode == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->android_driver_maintenance_mode)) {{$configuration->android_driver_maintenance_mode == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('android_driver_maintenance_mode'))
                                                <label class="danger">{{ $errors->first('android_driver_maintenance_mode') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                               @lang("$string_file.android_driver_app_version")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="android_driver_version"
                                                   name="android_driver_version"
                                                   placeholder=""
                                                   value="{{ $configuration->android_driver_version }}"
                                                   required>
                                            @if ($errors->has('android_driver_version'))
                                                <label class="danger">{{ $errors->first('android_driver_version') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.android_driver_app_mandatory_update")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="android_driver_mandatory_update"
                                                    id="android_driver_mandatory_update" required>
                                                <option value="1"
                                                @if(isset($configuration->android_driver_mandatory_update)) {{$configuration->android_driver_mandatory_update == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->android_driver_mandatory_update)) {{$configuration->android_driver_mandatory_update == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('android_driver_mandatory_update'))
                                                <label class="danger">{{ $errors->first('android_driver_mandatory_update') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.ios_user_maintenance_mode")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="ios_user_maintenance_mode"
                                                    id="ios_user_maintenance_mode" required>
                                                <option value="1"
                                                @if(isset($configuration->ios_user_maintenance_mode)) {{$configuration->ios_user_maintenance_mode == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->ios_user_maintenance_mode)) {{$configuration->ios_user_maintenance_mode == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('ios_user_maintenance_mode'))
                                                <label class="danger">{{ $errors->first('ios_user_maintenance_mode') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                 @lang("$string_file.ios_user_app_version")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="ios_user_version"
                                                   name="ios_user_version"
                                                   placeholder=""
                                                   value="{{ $configuration->ios_user_version }}"
                                                   required>
                                            @if ($errors->has('ios_user_version'))
                                                <label class="danger">{{ $errors->first('ios_user_version') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.ios_user_app_mandatory_update")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="ios_user_mandatory_update"
                                                    id="ios_user_mandatory_update" required>
                                                <option value="1"
                                                @if(isset($configuration->ios_user_mandatory_update)) {{$configuration->ios_user_mandatory_update == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->ios_user_mandatory_update)) {{$configuration->ios_user_mandatory_update == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('ios_user_mandatory_update'))
                                                <label class="danger">{{ $errors->first('ios_user_mandatory_update') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.ios_driver_maintenance_mode")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="ios_driver_maintenance_mode"
                                                    id="ios_driver_maintenance_mode" required>
                                                <option value="1"
                                                @if(isset($configuration->ios_driver_maintenance_mode)) {{$configuration->ios_driver_maintenance_mode == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->ios_driver_maintenance_mode)) {{$configuration->ios_driver_maintenance_mode == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('ios_driver_maintenance_mode'))
                                                <label class="danger">{{ $errors->first('ios_driver_maintenance_mode') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.ios_driver_app_version")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="ios_driver_version"
                                                   name="ios_driver_version"
                                                   placeholder="@lang("$string_file.ios_driver_app_version")"
                                                   value="{{ $configuration->ios_driver_version }}"
                                                   required>
                                            @if ($errors->has('ios_driver_version'))
                                                <label class="danger">{{ $errors->first('ios_driver_version') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.ios_driver_app_mandatory_update")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="ios_driver_mandatory_update"
                                                    id="ios_driver_mandatory_update" required>
                                                <option value="1"
                                                @if(isset($configuration->ios_driver_mandatory_update)) {{$configuration->ios_driver_mandatory_update == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->ios_driver_mandatory_update)) {{$configuration->ios_driver_mandatory_update == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('ios_driver_mandatory_update'))
                                                <label class="text-danger">{{ $errors->first('ios_driver_mandatory_update') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @php $segmentIds = array_column($merchant_segments, 'segment_id');@endphp
                                @if(in_array(3, $segmentIds) || in_array(4, $segmentIds) || in_array(32, $segmentIds) )
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.android_store_app_maintenance_mode")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="android_store_app_maintenance_mode"
                                                    id="android_store_app_maintenance_mode" required>
                                                <option value="1"
                                                @if(isset($configuration->android_store_app_maintenance_mode)) {{$configuration->android_store_app_maintenance_mode == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->android_store_app_maintenance_mode)) {{$configuration->android_store_app_maintenance_mode == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('android_store_app_maintenance_mode'))
                                                <label class="danger">{{ $errors->first('android_store_app_maintenance_mode') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.android_store_app_version")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="android_store_app_version"
                                                   name="android_store_app_version"
                                                   placeholder="@lang("$string_file.android_store_app_version")"
                                                   value="{{ $configuration->android_store_app_version }}"
                                                   required>
                                            @if ($errors->has('android_store_app_version'))
                                                <label class="danger">{{ $errors->first('android_store_app_version') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.android_store_app_mandatory_update")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="android_store_app_mandatory_update"
                                                    id="android_store_app_mandatory_update" required>
                                                <option value="1"
                                                @if(isset($configuration->android_store_app_mandatory_update)) {{$configuration->android_store_app_mandatory_update == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->android_store_app_mandatory_update)) {{$configuration->android_store_app_mandatory_update == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('android_store_app_mandatory_update'))
                                                <label class="text-danger">{{ $errors->first('android_store_app_mandatory_update') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.ios_store_app_maintenance_mode")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="ios_store_app_maintenance_mode"
                                                    id="ios_store_app_maintenance_mode" required>
                                                <option value="1"
                                                @if(isset($configuration->ios_store_app_maintenance_mode)) {{$configuration->ios_store_app_maintenance_mode == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->ios_store_app_maintenance_mode)) {{$configuration->ios_store_app_maintenance_mode == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('ios_store_app_maintenance_mode'))
                                                <label class="danger">{{ $errors->first('ios_store_app_maintenance_mode') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.ios_store_app_version")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="ios_store_app_version"
                                                   name="ios_store_app_version"
                                                   placeholder="@lang("$string_file.ios_store_app_version")"
                                                   value="{{ $configuration->ios_store_app_version }}"
                                                   required>
                                            @if ($errors->has('ios_store_app_version'))
                                                <label class="danger">{{ $errors->first('ios_store_app_version') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.ios_store_app_mandatory_update")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="ios_store_app_mandatory_update"
                                                    id="ios_store_app_mandatory_update" required>
                                                <option value="1"
                                                @if(isset($configuration->ios_store_app_mandatory_update)) {{$configuration->ios_store_app_mandatory_update == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->ios_store_app_mandatory_update)) {{$configuration->ios_store_app_mandatory_update == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('ios_store_app_mandatory_update'))
                                                <label class="text-danger">{{ $errors->first('ios_store_app_mandatory_update') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.admin_application_default_language")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="default_language"
                                                    id="default_language" required>
                                                @foreach($languages as $language)
                                                    <option value="{{ $language->locale }}" {{ $configuration->default_language == $language->locale ? 'selected' : ''}}>{{ $language->name }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('default_language'))
                                                <label class="danger">{{ $errors->first('default_language') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.user_application_default_language")<span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="user_default_language"
                                                    id="user_default_language" required>
                                                @foreach($languages as $language)
                                                    <option value="{{ $language->locale }}" {{ $app_configuration->user_default_language == $language->locale ? 'selected' : ''}}>{{ $language->name }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('user_default_language'))
                                                <label class="danger">{{ $errors->first('user_default_language') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                    @lang("$string_file.driver_application_default_language")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="driver_default_language"
                                                    id="driver_default_language" required>
                                                @foreach($languages as $language)
                                                    <option value="{{ $language->locale }}" {{ $app_configuration->driver_default_language == $language->locale ? 'selected' : ''}}>{{ $language->name }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('driver_default_language'))
                                                <label class="danger">{{ $errors->first('driver_default_language') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($configuration->user_wallet_status == 1)
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="firstName3">
                                                    @lang("$string_file.user_wallet_short_values")
                                                </label>
                                                @php $a = json_decode($configuration->user_wallet_amount,true);  @endphp
                                                <input type="text" class="form-control"
                                                       id="user_wallet_amount"
                                                       name="user_wallet_amount[]"
                                                       placeholder="@lang("$string_file.enter_amount")"
                                                       value="@if(array_key_exists(0, $a)) {{ $a[0]['amount'] }} @endif"
                                                       required>
                                                @if ($errors->has('user_wallet_amount'))
                                                    <label class="danger">{{ $errors->first('user_wallet_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label style="visibility: hidden" for="firstName3">
                                                    @lang('admin.message144')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="user_wallet_amount"
                                                       name="user_wallet_amount[]"
                                                       placeholder="@lang("$string_file.enter_value") 2"
                                                       value="@if(array_key_exists(1, $a)) {{ $a[1]['amount'] }} @endif"
                                                       required>
                                                @if ($errors->has('user_wallet_amount'))
                                                    <label class="danger">{{ $errors->first('user_wallet_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label style="visibility: hidden" for="firstName3">
                                                    @lang('admin.message144')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="user_wallet_amount"
                                                       name="user_wallet_amount[]"
                                                       placeholder="@lang("$string_file.enter_value") 3"
                                                       value="@if(array_key_exists(2, $a)) {{ $a[2]['amount'] }} @endif"
                                                       required>
                                                @if ($errors->has('user_wallet_amount'))
                                                    <label class="danger">{{ $errors->first('user_wallet_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if($configuration->driver_wallet_status == 1)
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="firstName3">
                                                    @lang("$string_file.driver_wallet_short_values")
                                                </label>
                                                @php $b = json_decode($configuration->driver_wallet_amount,true);  @endphp
                                                <input type="text" class="form-control"
                                                       id="driver_wallet_amount"
                                                       name="driver_wallet_amount[]"
                                                       placeholder="@lang("$string_file.enter_amount")"
                                                       value="@if(array_key_exists(0, $b)) {{ $b[0]['amount'] }} @endif"
                                                       required>
                                                @if ($errors->has('driver_wallet_amount'))
                                                    <label class="danger">{{ $errors->first('driver_wallet_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label style="visibility: hidden" for="firstName3">
                                                    @lang('admin.message144')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="driver_wallet_amount"
                                                       name="driver_wallet_amount[]"
                                                       placeholder="@lang("$string_file.enter_value") 2"
                                                       value="@if(array_key_exists(1, $b)) {{ $b[1]['amount'] }} @endif"
                                                       required>
                                                @if ($errors->has('driver_wallet_amount'))
                                                    <label class="danger">{{ $errors->first('driver_wallet_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label style="visibility: hidden" for="firstName3">
                                                    @lang('admin.message144')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="driver_wallet_amount"
                                                       name="driver_wallet_amount[]"
                                                       placeholder="@lang("$string_file.enter_value") 3"
                                                       value="@if(array_key_exists(2, $b)) {{ $b[2]['amount'] }} @endif"
                                                       required>
                                                @if ($errors->has('driver_wallet_amount'))
                                                    <label class="danger">{{ $errors->first('driver_wallet_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if($app_configuration->tip_status == 4)
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="firstName3">
                                                    @lang("$string_file.user_tip_short_values")
                                                </label>
                                                @php $b = !empty($app_configuration->tip_short_amount) ? json_decode($app_configuration->tip_short_amount,true) : [];  @endphp
                                                <input type="text" class="form-control"
                                                       id="tip_short_amount"
                                                       name="tip_short_amount[]"
                                                       placeholder="@lang("$string_file.enter_amount")"
                                                       value="@if(array_key_exists(0, $b)) {{ $b[0]['amount'] }} @endif"
                                                       required>
                                                @if ($errors->has('tip_short_amount'))
                                                    <label class="danger">{{ $errors->first('tip_short_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label style="visibility: hidden" for="firstName3">
                                                    @lang('admin.message144')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="tip_short_amount"
                                                       name="tip_short_amount[]"
                                                       placeholder="@lang("$string_file.enter_value") 2"
                                                       value="@if(array_key_exists(1, $b)) {{ $b[1]['amount'] }} @endif"
                                                       required>
                                                @if ($errors->has('tip_short_amount'))
                                                    <label class="danger">{{ $errors->first('tip_short_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label style="visibility: hidden" for="firstName3">
                                                    @lang('admin.message144')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="tip_short_amount"
                                                       name="tip_short_amount[]"
                                                       placeholder="@lang("$string_file.enter_value") 3"
                                                       value="@if(array_key_exists(2, $b)) {{ $b[2]['amount'] }} @endif"
                                                       required>
                                                @if ($errors->has('tip_short_amount'))
                                                    <label class="danger">{{ $errors->first('tip_short_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.reminder_expire_doc")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="reminder_expire_doc"
                                                   name="reminder_expire_doc"
                                                   placeholder="@lang("$string_file.reminder_expire_doc")"
                                                   value="{{$configuration->reminder_doc_expire}}"
                                                   required>
                                            @if ($errors->has('reminder_expire_doc'))
                                                <label class="danger">{{ $errors->first('reminder_expire_doc') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    @if($configuration->in_drive_enable == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.offer_amount_changes")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="offer_amount_changes"
                                                   name="offer_amount_changes"
                                                   placeholder="@lang("$string_file.offer_amount_changes")"
                                                   value="{{$configuration->offer_amount_changes}}"
                                                   required>
                                            @if ($errors->has('offer_amount_changes'))
                                                <label class="danger">{{ $errors->first('offer_amount_changes') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                @if($tdt_segment_condition)

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.fare_policy_text")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="fare_policy_text"
                                                   name="fare_policy_text"
                                                   placeholder="@lang("$string_file.fare_policy_text")"
                                                   value="{{$configuration->fare_policy_text}}"
                                                   required>
                                            @if ($errors->has('fare_policy_text'))
                                                <label class="danger">{{ $errors->first('fare_policy_text') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.api_version")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="api_version"
                                                   name="api_version"
                                                   placeholder="@lang("$string_file.api_version")"
                                                   value="{{isset($version_management->api_version) ? $version_management->api_version : '0.1'}}"
                                                   required>
                                            @if ($errors->has('api_version'))
                                                <label class="danger">{{ $errors->first('api_version') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="external_holder_color">
                                                @lang("$string_file.handyman_provider_start_before_booking_date")
                                            </label>
                                            <select class="form-control"
                                                    name="handyman_provider_start_before_booking_date"
                                                    id="handyman_provider_start_before_booking_date" required>
                                                <option value="1"
                                                @if(isset($configuration->handyman_provider_start_before_booking_date)) {{$configuration->handyman_provider_start_before_booking_date == 1 ? 'selected' : ''}} @endif>@lang("$string_file.enable")</option>
                                                <option value="2"
                                                @if(isset($configuration->handyman_provider_start_before_booking_date)) {{$configuration->handyman_provider_start_before_booking_date == 2 ? 'selected' : ''}} @endif>@lang("$string_file.disable")</option>
                                            </select>
                                            @if ($errors->has('handyman_provider_start_before_booking_date'))
                                                <label class="danger">{{ $errors->first('handyman_provider_start_before_booking_date') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    @if(isset($configuration->guest_user) && $configuration->guest_user == 1)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="firstName3">
                                                    @lang("$string_file.guest_user_country")
                                                </label>
                                                {{ Form::select("guest_user_country_id",$countries,old('guest_user_country_id',$configuration->guest_user_country_id),["class"=>"form-control"]) }}
                                                @if ($errors->has('guest_user_country_id'))
                                                    <label class="danger">{{ $errors->first('guest_user_country_id') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    @if(isset($app_configuration->show_logo_main) && $app_configuration->show_logo_main == 1)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="firstName3">
                                                    @lang("$string_file.app") @lang("$string_file.header") @lang("$string_file.logo")
                                                    @if(isset($app_configuration->logo_main) && !empty($app_configuration->logo_main))
                                                        <a href="{{get_image($app_configuration->logo_main,"business_logo")}}" target="_blank">View Image</a>
                                                    @endif
                                                </label>
                                                {{ Form::file("logo_main",["class"=>"form-control"]) }}
                                                @if ($errors->has('logo_main'))
                                                    <label class="danger">{{ $errors->first('logo_main') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @if(isset($configuration->twilio_call_masking) && $configuration->twilio_call_masking == 1)
                                    <br>
                                    <h5 class="form-section">
                                        <i class="fa fa-taxi"></i> @lang('admin.twilio_call_masking_configuration')
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="twilio_sid">
                                                    @lang('admin.twilio_sid')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="twilio_sid"
                                                       name="twilio_sid"
                                                       placeholder="@lang('admin.message168')"
                                                       value="{{ $configuration->twilio_sid }}"
                                                       required>
                                                @if ($errors->has('twilio_sid'))
                                                    <label class="danger">{{ $errors->first('twilio_sid') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="twilio_service_id">
                                                    @lang('admin.twilio_service_id')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="twilio_service_id"
                                                       name="twilio_service_id"
                                                       placeholder="@lang('admin.twilio_service_id')"
                                                       value="{{ $configuration->twilio_service_id }}"
                                                       required>
                                                @if ($errors->has('twilio_service_id'))
                                                    <label class="danger">{{ $errors->first('twilio_service_id') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="twilio_token">
                                                    @lang('admin.twilio_token')<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="twilio_token"
                                                       name="twilio_token"
                                                       placeholder="@lang('admin.twilio_token')"
                                                       value="{{ $configuration->twilio_token }}"
                                                       required>
                                                @if ($errors->has('twilio_token'))
                                                    <label class="danger">{{ $errors->first('twilio_token') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if(isset($configuration->social_signup) && $configuration->social_signup == 1)
                                    <br>
                                    <h5 class="form-section">
                                        <i class="fa fa-taxi"></i> @lang("$string_file.social_signup_config")
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="google_signup_key">
                                                    @lang("$string_file.google_signup_key")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="google_signup_key"
                                                       name="google_signup_key"
                                                       value="{{ $configuration->google_signup_key }}"
                                                       required>
                                                @if ($errors->has('google_signup_key'))
                                                    <label class="danger">{{ $errors->first('google_signup_key') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="twilio_service_id">
                                                    @lang("$string_file.facebook_signup_key")<span
                                                            class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="facebook_signup_key"
                                                       name="facebook_signup_key"
                                                       value="{{ $configuration->facebook_signup_key }}"
                                                       required>
                                                @if ($errors->has('facebook_signup_key'))
                                                    <label class="danger">{{ $errors->first('facebook_signup_key') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="twilio_service_id">
                                                @lang("$string_file.user_delete_url")
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="user_delete_url"
                                                   name="user_delete_url"
                                                   readonly
                                                   value="{{ route('user.login',Auth::user('merchant')->alias_name) }}"
                                                   required>
                                            @if ($errors->has('user_delete_url'))
                                                <label class="danger">{{ $errors->first('user_delete_url') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="twilio_service_id">
                                                @lang("$string_file.driver_delete_url")
                                            </label>
                                            <input type="text" class="form-control"
                                                   id="driver_delete_url"
                                                   name="driver_delete_url"
                                                   readonly
                                                   value="{{ route('driver.login',Auth::user('merchant')->alias_name) }}"
                                                   required>
                                            @if ($errors->has('driver_delete_url'))
                                                <label class="danger">{{ $errors->first('driver_delete_url') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    @if($fg_segment_condition)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="twilio_service_id">
                                                    @lang("$string_file.business_segment_delete_url")
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="business_segment_delete_url"
                                                       name="business_segment_delete_url"
                                                       readonly
                                                       value="{{ route('business-segment.user.login',Auth::user('merchant')->alias_name) }}"
                                                       required>
                                                @if ($errors->has('business_segment_delete_url'))
                                                    <label class="danger">{{ $errors->first('business_segment_delete_url') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @if($fg_segment_condition && ((isset($app_configuration->driver_phone) && $app_configuration->driver_phone == 2) || (isset($app_configuration->user_phone) && $app_configuration->user_phone == 2)))
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.auto_fill_otp")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control"
                                                    name="auto_fill_otp"
                                                    id="auto_fill_otp" required>
                                                <option value="1"
                                                @if(isset($configuration->auto_fill_otp)) {{$configuration->auto_fill_otp == 1 ? 'selected' : ''}} @endif>@lang("$string_file.on")</option>
                                                <option value="2"
                                                @if(isset($configuration->auto_fill_otp)) {{$configuration->auto_fill_otp == 2 ? 'selected' : ''}} @endif>@lang("$string_file.off")</option>
                                            </select>
                                            @if ($errors->has('auto_fill_otp'))
                                                <label class="danger">{{ $errors->first('auto_fill_otp') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="external_holder_color">
                                                @lang("$string_file.user_app_image")
                                            </label>
                                            <select class="form-control"
                                                    name="user_app_image_config"
                                                    id="user_app_image_config" required>
                                                <option value="1"
                                                @if(isset($app_configuration->user_app_image_config)) {{$app_configuration->user_app_image_config == 1 ? 'selected' : ''}} @endif>@lang("$string_file.optional")</option>
                                                <option value="2"
                                                @if(isset($app_configuration->user_app_image_config)) {{$app_configuration->user_app_image_config == 2 ? 'selected' : ''}} @endif>@lang("$string_file.mandatory")</option>
                                            </select>
                                            @if ($errors->has('user_app_image_config'))
                                                <label class="danger">{{ $errors->first('user_app_image_config') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="external_holder_color">
                                                @lang("$string_file.handyman_sp_base_price_start")
                                            </label>
                                            <input type="number" class="form-control"
                                                       id="handyman_sp_base_price_start"
                                                       name="handyman_sp_base_price_start"
                                                       value="@if(isset($app_configuration->handyman_sp_base_price_start)){{$app_configuration->handyman_sp_base_price_start}}@endif"
                                                       step=".01"
                                                       required>
                                                @if ($errors->has('handyman_sp_base_price_start'))
                                                    <label class="danger">{{ $errors->first('handyman_sp_base_price_start') }}</label>
                                                @endif
                                        </div>
                                    </div>
                                    @if($configuration->outstanding_extra_charge_enable == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="outstanding_extra_charge">
                                                @lang("$string_file.outstanding_extra_charge")
                                            </label>
                                            <input type="number" class="form-control"
                                                       id="outstanding_extra_charge"
                                                       name="outstanding_extra_charge"
                                                       value="@if(isset($configuration->outstanding_extra_charge)){{$configuration->outstanding_extra_charge}}@endif"
                                                       step=".01"
                                                       required>
                                                @if ($errors->has('outstanding_extra_charge'))
                                                    <label class="danger">{{ $errors->first('outstanding_extra_charge') }}</label>
                                                @endif
                                        </div>
                                    </div>
                                    @endif
                                    @if($configuration->random_notifications_to_driver == 1)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="online_offline_notification_diff_time">
                                                    @lang("$string_file.random") @lang("$string_file.notification") @lang("$string_file.to") @lang("$string_file.driver") @lang("$string_file.time") @lang("$string_file.diffrence") @lang("$string_file.in") @lang("$string_file.minutes")
                                                </label>
                                                <input type="number" class="form-control"
                                                       id="online_offline_notification_diff_time"
                                                       name="online_offline_notification_diff_time"
                                                       value="@if(isset($configuration->online_offline_notification_diff_time)){{$configuration->online_offline_notification_diff_time}}@endif"
                                                       step=".01"
                                                       required>
                                                @if ($errors->has('online_offline_notification_diff_time'))
                                                    <label class="danger">{{ $errors->first('online_offline_notification_diff_time') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    {{--only for contrato--}}
                                    @if($app_configuration->merchant_id == 601)
                                        @php
                                            $additional_variables = json_decode($app_configuration->additional_email_variables)
                                        @endphp
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="external_holder_color">
                                                    @lang("$string_file.email") @lang("$string_file.variable") 1
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="email_variable_1"
                                                       name="email_variable_1"
                                                       value="@if(isset($additional_variables)){{$additional_variables->email_variable_1}}@endif"
                                                       step=".01"
                                                       required>
                                                @if ($errors->has('handyman_sp_base_price_start'))
                                                    <label class="danger">{{ $errors->first('handyman_sp_base_price_start') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="external_holder_color">
                                                    @lang("$string_file.email") @lang("$string_file.variable") 2
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="email_variable_2"
                                                       name="email_variable_2"
                                                       value="@if(isset($additional_variables)){{$additional_variables->email_variable_2}}@endif"
                                                       step=".01"
                                                       required>
                                                @if ($errors->has('handyman_sp_base_price_start'))
                                                    <label class="danger">{{ $errors->first('handyman_sp_base_price_start') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @if(isset($app_configuration->main_screen_add_money_button) && $app_configuration->main_screen_add_money_button == 1)
                                    <h4>@lang("$string_file.user_home_screen_add_wallet_money_config")</h4>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="add_wallet_money_btntext">
                                                    @lang("$string_file.add_wallet_money_btntext")
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="add_wallet_money_btntext"
                                                       name="add_wallet_money_btntext"
                                                       value="{{isset($app_configuration->add_wallet_money_btntext) ? $app_configuration->add_wallet_money_btntext : ""}}"
                                                       required>
                                                @if ($errors->has('add_wallet_money_btntext'))
                                                    <label class="danger">{{ $errors->first('add_wallet_money_btntext') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="add_wallet_money_btncolor">
                                                    @lang("$string_file.add_wallet_money_btncolor")
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="add_wallet_money_btncolor"
                                                       name="add_wallet_money_btncolor"
                                                       value="{{isset($app_configuration->add_wallet_money_btncolor) ? $app_configuration->add_wallet_money_btncolor : ""}}"
                                                       required>
                                                @if ($errors->has('add_wallet_money_btncolor'))
                                                    <label class="danger">{{ $errors->first('add_wallet_money_btncolor') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="add_wallet_money_image">
                                                    @lang("$string_file.add_wallet_money_image")
                                                    @if(isset($app_configuration->add_wallet_money_image) && !empty($app_configuration->add_wallet_money_image))
                                                        <a href="{{get_image($app_configuration->add_wallet_money_image,"business_logo")}}" target="_blank">View Image</a>
                                                    @endif
                                                </label>
                                                {{ Form::file("add_wallet_money_image",["class"=>"form-control"]) }}
                                                @if ($errors->has('add_wallet_money_image'))
                                                    <label class="danger">{{ $errors->first('add_wallet_money_image') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="external_holder_color">
                                                    @lang("$string_file.external_holder_color")
                                                </label>
                                                <input type="text" class="form-control"
                                                       id="external_holder_color"
                                                       name="external_holder_color"
                                                       value="{{isset($app_configuration->zaaou_service_holder_color) ? $app_configuration->zaaou_service_holder_color : ""}}"
                                                       required>
                                                @if ($errors->has('external_holder_color'))
                                                    <label class="danger">{{ $errors->first('external_holder_color') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                <h5 class="form-section">
                                    <i class="fa fa-credit-card"></i> @lang("$string_file.payment_image_config")
                                </h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="payment_image">
                                                @lang("$string_file.payment_image")
                                            </label>
                                            <input type="file" class="form-control"
                                                   id="payment_image"
                                                   name="payment_image">
                                            @if(!empty($configuration->payment_image))
                                            @php
                                                $payment_image = get_image($configuration->payment_image,'merchant');
                                            @endphp
                                            <a href="{{ $payment_image }}" target="_blank">
                                                <img src="{{ $payment_image }}" alt="" class="payment-config-image" height="60px",width="60px">
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                @if($application_theme->user_intro_screen)
                                <h5 class="form-section">
                                    <i class="fa fa-credit-card"></i> @lang("$string_file.application_theme")
                                </h5>
                                <hr>
                                <div class="row">
                                    @for($i=0; $i < 3; $i++)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                    @php
                                                    $user_intro = isset($application_theme->UserIntroText) ? (count(json_decode($application_theme->UserIntroText,true)) > 0 ? json_decode($application_theme->UserIntroText,true) :  json_decode($application_theme->user_intro_screen,true)) : json_decode($application_theme->user_intro_screen,true) ;
                                                    $user_intro_text = isset($user_intro[$i]) ? $user_intro[$i]['text'] : "";
                                                    @endphp
                                                 @if($user_intro_text)
                                                    <label for="business_name">@lang("$string_file.screen") {{$i+1}} @lang("$string_file.text") </label>
                                                    <input type="text" name="user_intro_text[]" value="{{ old('user_intro_text', $user_intro_text) }}" class="form-control" id="user_intro_text" placeholder="Intro text"/>
                                                    @if ($errors->has('user_intro_text'))
                                                        <span class="help-block">
                                                            <strong>{{ $errors->first('user_intro_text') }}</strong>
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                @endfor
                                </div>
                                @endif
                            </fieldset>
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
