@extends('merchant.layouts.main')
@section('content')
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
                        <a href="{{ route('merchant.packagewise.onesignal') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class=" wb-user-plus" aria-hidden="true"></i>
                        @if(!empty($onesignal)) @lang("$string_file.edit") @else @lang("$string_file.add") @endif @lang("$string_file.package_wise") @lang("$string_file.onesignal")</h3>
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          action="{{ route('merchant.packagewise.onesignal.submit',["id" => !empty($onesignal) ? $onesignal->id : null]) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="web_application_key">
                                        @lang("$string_file.package_name")
                                    </label>
                                    @if(!empty($onesignal))
                                        <strong>{{$onesignal['package_name']}}</strong>
                                    @else
                                        <input type="text" class="form-control"
                                               id="package_name" name="package_name"
                                               placeholder=""
                                               value="{{ !empty($onesignal) ? $onesignal['package_name'] : "" }}">
                                        @if ($errors->has('package_name'))
                                            <label class="danger">{{ $errors->first('package_name') }}</label>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="web_application_key">
                                        @lang("$string_file.web_onesignal_app_key")
                                    </label>
                                    <input type="text" class="form-control"
                                           id="web_application_key" name="web_application_key"
                                           placeholder=""
                                           value="{{ !empty($onesignal) ? $onesignal['web_application_key'] : "" }}">
                                    @if ($errors->has('web_application_key'))
                                        <label class="danger">{{ $errors->first('web_application_key') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="web_rest_key">
                                        @lang("$string_file.web_onesignal_rest_key")
                                    </label>
                                    <input type="text" class="form-control" id="web_rest_key"
                                           name="web_rest_key"
                                           placeholder=""
                                           value="{{ !empty($onesignal) ? $onesignal['web_rest_key'] : "" }}">
                                    @if ($errors->has('web_rest_key'))
                                        <label class="danger">{{ $errors->first('web_rest_key') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.user_application_key")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control"
                                           id="user_application_key" name="user_application_key"
                                           placeholder=""
                                           value="{{ !empty($onesignal) ? $onesignal->user_application_key : "" }}"
                                    >
                                    @if ($errors->has('user_application_key'))
                                        <label class="danger">{{ $errors->first('user_application_key') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.user_rest_key")<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="user_rest_key"
                                           name="user_rest_key"
                                           placeholder=""
                                           value="{{ !empty($onesignal) ?  $onesignal->user_rest_key : "" }}">
                                    @if ($errors->has('user_rest_key'))
                                        <label class="danger">{{ $errors->first('user_rest_key') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.user_channel_id")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="user_channel_id"
                                           name="user_channel_id"
                                           placeholder=""
                                           value="{{ !empty($onesignal) ? $onesignal->user_channel_id : "" }}">
                                    @if ($errors->has('user_channel_id'))
                                        <label class="danger">{{ $errors->first('user_channel_id') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.driver_application_key")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control"
                                           id="driver_application_key"
                                           name="driver_application_key"
                                           placeholder=""
                                           value="{{ !empty($onesignal) ? $onesignal->driver_application_key : "" }}"
                                    >
                                    @if ($errors->has('driver_application_key'))
                                        <label class="danger">{{ $errors->first('driver_application_key') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.driver_rest_key")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="driver_rest_key"
                                           name="driver_rest_key"
                                           placeholder=""
                                           value="{{ !empty($onesignal) ? $onesignal->driver_rest_key : "" }}">
                                    @if ($errors->has('driver_rest_key'))
                                        <label class="danger">{{ $errors->first('driver_rest_key') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.driver_channel_id")<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="driver_channel_id"
                                           name="driver_channel_id"
                                           placeholder=""
                                           value="{{ !empty($onesignal) ?  $onesignal->driver_channel_id : "" }}">
                                    @if ($errors->has('driver_channel_id'))
                                        <label class="danger">{{ $errors->first('driver_channel_id') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions right" style="margin-bottom: 3%">
                            @if($edit_permission)
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                                </button>
                            @else
                                <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
