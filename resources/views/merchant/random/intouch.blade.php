@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            @php
                $config = get_merchant_notification_provider();
                $firebase_required = isset($config->fire_base) && $config->fire_base == true ? "required" : "";
                $firebase_required_file = !empty($firebase_required) && empty($config->id) ? "required" : "";
                $onesignal_required = empty($firebase_required) ? "required" : "";
                $heading = empty($firebase_required) ? trans("common.onesignal") : trans("common.firebase");
            @endphp
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
                        <i class=" wb-user-plus" aria-hidden="true"></i>
                        {!! $heading !!} @lang("common.configuration")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          action="{{ route('merchant.onesignal.submit') }}">
                        @csrf
                        <div class="row">
                            @if(!empty($onesignal_required))
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="web_application_key">
                                        @lang("common.web") @lang("common.onesignal") @lang("common.app") @lang("common.key")
                                    </label>
                                    <input type="text" class="form-control"
                                           id="web_application_key" name="web_application_key"
                                           placeholder=""
                                           value="{{ $onesignal['web_application_key'] }}">
                                    @if ($errors->has('web_application_key'))
                                        <label class="danger">{{ $errors->first('web_application_key') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="web_rest_key">
                                        @lang("common.web") @lang("common.onesignal") @lang("common.rest") @lang("common.key")
                                    </label>
                                    <input type="text" class="form-control" id="web_rest_key"
                                           name="web_rest_key"
                                           placeholder=""
                                           value="{{ $onesignal['web_rest_key'] }}">
                                    @if ($errors->has('web_rest_key'))
                                        <label class="danger">{{ $errors->first('web_rest_key') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("common.user") @lang("common.application") @lang("common.key")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control"
                                           id="user_application_key" name="user_application_key"
                                           placeholder=""
                                           value="{{ isset($onesignal->user_application_key) ? $onesignal->user_application_key : NULL }}"
                                           {!! $onesignal_required !!}>
                                    @if ($errors->has('user_application_key'))
                                        <label class="danger">{{ $errors->first('user_application_key') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("common.user") @lang("common.rest") @lang("common.key")<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="user_rest_key"
                                           name="user_rest_key"
                                           placeholder="@lang('admin_x.message88')"
                                           value="{{ isset($onesignal->user_rest_key) ? $onesignal->user_rest_key : NULL }}" {!! $onesignal_required !!}>
                                    @if ($errors->has('user_rest_key'))
                                        <label class="danger">{{ $errors->first('user_rest_key') }}</label>
                                    @endif
                                </div>
                            </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("common.user") @lang("common.channel") @lang("common.id")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="user_channel_id"
                                               name="user_channel_id"
                                               placeholder=""
                                               value="{{ isset($onesignal->user_channel_id) ? $onesignal->user_channel_id : NULL }}" {!! $onesignal_required !!}>
                                        @if ($errors->has('user_channel_id'))
                                            <label class="danger">{{ $errors->first('user_channel_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                @if(isset($config->driver_enable) && $config->driver_enable == 1)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.driver") @lang("common.application") @lang("common.key")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="driver_application_key"
                                               name="driver_application_key"
                                               placeholder=""
                                               value="{{ isset($onesignal->driver_application_key) ? $onesignal->driver_application_key : NULL }}"
                                                {!! $onesignal_required !!}>
                                        @if ($errors->has('driver_application_key'))
                                            <label class="danger">{{ $errors->first('driver_application_key') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.driver") @lang("common.rest") @lang("common.key")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="driver_rest_key"
                                               name="driver_rest_key"
                                               placeholder=""
                                               value="{{ isset($onesignal->driver_rest_key) ? $onesignal->driver_rest_key : NULL }}" {!! $onesignal_required !!}>
                                        @if ($errors->has('driver_rest_key'))
                                            <label class="danger">{{ $errors->first('driver_rest_key') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.driver") @lang("common.channel") @lang("common.id")<span
                                                    class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="driver_channel_id"
                                               name="driver_channel_id"
                                               placeholder="@lang('admin_x.message90')"
                                               value="{{ isset($onesignal->driver_channel_id) ?  $onesignal->driver_channel_id : NULL }}" {!! $onesignal_required !!}>
                                        @if ($errors->has('driver_channel_id'))
                                            <label class="danger">{{ $errors->first('driver_channel_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            @else
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang('admin_x.firebase_api_key')<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="driver_rest_key"
                                           name="firebase_api_key_android"
                                           placeholder="@lang('admin_x.firebase_api_key')"
                                           value="{{ isset($onesignal->firebase_api_key_android) ? $onesignal->firebase_api_key_android : NULL }}" {!! $firebase_required !!}>
                                    @if ($errors->has('firebase_api_key_android'))
                                        <label class="danger">{{ $errors->first('firebase_api_key_android') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang('admin_x.firebase_ios_pem_user')<span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="document"
                                           name="firebase_ios_pem_user" placeholder="" {!! $firebase_required_file !!}>
                                    @if ($errors->has('firebase_ios_pem_user'))
                                        <label class="text-danger">{{ $errors->first('firebase_ios_pem_user') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang('admin_x.pem_password_user')<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="pem_password_user"
                                           name="pem_password_user"
                                           placeholder="@lang('admin_x.pem_password_user')"
                                           value="{{ isset($onesignal->pem_password_user) ? $onesignal->pem_password_user : NULL }}" {!! $firebase_required !!}>
                                    @if ($errors->has('pem_password_user'))
                                        <label class="danger">{{ $errors->first('pem_password_user') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang('admin_x.firebase_ios_pem_driver')<span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="document"
                                           name="firebase_ios_pem_driver" placeholder="" {!! $firebase_required_file !!}>
                                    @if ($errors->has('firebase_ios_pem_driver'))
                                        <label class="text-danger">{{ $errors->first('firebase_ios_pem_driver') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang('admin_x.pem_password_driver')<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="pem_password_driver"
                                           name="pem_password_driver"
                                           placeholder="@lang('admin_x.pem_password_driver')"
                                           value="{{ isset($onesignal->pem_password_driver) ? $onesignal->pem_password_driver : NULL }}" {!! $firebase_required !!}>
                                    @if ($errors->has('pem_password_driver'))
                                        <label class="danger">{{ $errors->first('pem_password_driver') }}</label>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="form-actions right" style="margin-bottom: 3%">
                            @if(Auth::user('merchant')->can('edit_onesignal'))
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-circle"></i> @lang("common.save")
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
