@extends('merchant.layouts.main')
@section('content')
    @php
        $page_title = "Add Merchant";
        $submit_button_text = "Save";
        $user_app_logo_url = "";
        $driver_app_logo = "";
        $store_app_logo = "";
        $user_intro_logo_old = "";
        $driver_intro_text = "";
        $user_intro_text = "";
        $driver_intro_logo_old = "";
        $user_intro_logo_image = "";
        $user_intro = [];
        $driver_intro = [];
        $id = null;
        $food_grocery = is_merchant_segment_exist(['FOOD','GROCERY']);

        if($application_theme){
            $id = $application_theme->id;
            $user_intro = json_decode($application_theme->UserIntroText,true);
            $user_intro_logo = json_decode($application_theme->user_intro_screen,true);
            $driver_intro = json_decode($application_theme->DriverIntroText,true);
            $driver_intro_logo = json_decode($application_theme->driver_intro_screen,true);

            $user_app_logo_url = get_image($application_theme->user_app_logo, 'user_app_theme',$application_theme->merchant_id);
            $driver_app_logo = get_image($application_theme->driver_app_logo, 'driver_app_theme',$application_theme->merchant_id);
            $store_app_logo = get_image($application_theme->store_app_logo, 'business_logo',$application_theme->merchant_id);
        }

    @endphp
    @php $tdt_segment_condition = is_merchant_segment_exist(['TAXI','DELIVERY','TOWING']); @endphp
    <div class="page">
        <div class="page-content">
            @if(session('applicationtheme'))
                <div class="alert dark alert-icon alert-success alert-dismissible"
                     role="alert">
                    <button type="button" class="close" data-dismiss="alert"
                            aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <i class="icon wb-info" aria-hidden="true"></i>@lang('admin.message8611')
                </div>
            @endif
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions"></div>
                    <h3 class="panel-title">
                        <i class="icon fa-paint-brush" aria-hidden="true"></i>
                        @lang("$string_file.application") @lang("$string_file.theme")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          action="{{ route('merchant.applicationtheme.submit') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="firstName3">@lang("$string_file.user_app_color")<span
                                                class="text-danger">*</span></label>
                                    <input type="color" class="form-control"
                                           id="primary_color_user" name="primary_color_user"
                                           placeholder="User App Color"
                                           value="@if($application_theme){!!$application_theme->primary_color_user!!}@endif"
                                           required>
                                    @if ($errors->has('primary_color_user'))
                                        <label class="danger">{{ $errors->first('primary_color_user') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="firstName3">@lang("$string_file.driver_app_color")<span
                                                class="text-danger">*</span></label>
                                    <input type="color" class="form-control"
                                           id="primary_color_driver"
                                           name="primary_color_driver"
                                           placeholder="Driver App Color"
                                           value="@if(!empty($application_theme)){{$application_theme->primary_color_driver}}"
                                           @endif required>
                                    @if ($errors->has('primary_color_driver'))
                                        <label class="danger">{{ $errors->first('primary_color_driver') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_app_logo">@lang("$string_file.user_app_logo")</label>
                                    {!! Form::file('user_app_logo', $attributes = array('class' => 'form-control', 'id' => 'user_app_logo','onchange' => 'readURL(this);', isset($application_theme->user_app_logo)? '' : 'required')) !!}
                                    <p class="help-block">@lang("$string_file.size_100_100")</p>
                                    @if ($errors->has('user_app_logo'))
                                        <span class="help-block">
                                      <strong>{{ $errors->first('user_app_logo') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                <img src="{{ $user_app_logo_url }}" id="blah" class="avatar img-circle" alt=""
                                     height="100px" width="100px" style="margin-bottom:10px;">
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="driver_app_logo">@lang("$string_file.driver_app_logo")</label>
                                    {!! Form::file('driver_app_logo', $attributes = array('class' => 'form-control', 'id' => 'driver_app_logo','onchange' => 'readURL(this);', isset($application_theme->driver_app_logo)? '' : 'required')) !!}
                                    <p class="help-block">@lang("$string_file.size_100_100")</p>
                                    @if ($errors->has('driver_app_logo'))
                                        <span class="help-block">
                                    <strong>{{ $errors->first('driver_app_logo') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                <img src="{{ $driver_app_logo }}" id="blah" class="avatar img-circle" alt=""
                                     height="100px" width="100px" style="margin-bottom:10px;">
                            </div>
                        </div>
                        @if($food_grocery)
                            <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">@lang("$string_file.store_app_color")</label>
                                    {!! Form::color('primary_color_store', old('primary_color_store', isset($application_theme->primary_color_store) ? $application_theme->primary_color_store : ''), array('class' => 'form-control', 'id' => 'primary_color_store', 'placeholder' => 'Store App Color')) !!}
                                    @if ($errors->has('primary_color_store'))
                                        <span class="help-block">
                                        <strong>{{ $errors->first('primary_color_store') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="driver_app_logo">@lang("$string_file.store_app_logo")</label>
                                    {!! Form::file('store_app_logo', $attributes = array('class' => 'form-control', 'id' => 'store_app_logo','onchange' => 'readURL(this);', isset($application_theme->store_app_logo)? '' : 'required')) !!}
                                    <p class="help-block">@lang("$string_file.size_100_100")</p>
                                    @if ($errors->has('store_app_logo'))
                                        <span class="help-block">
                                        <strong>{{ $errors->first('store_app_logo') }}</strong>
                                    </span>
                                    @endif

                                    <img src="{{ $store_app_logo }}" id="blah" class="avatar img-circle" alt=""
                                         height="100px" width="100px" style="margin-bottom:10px;">
                                </div>
                            </div>
                        </div>
                        @endif
                        <hr>
                        <h2>@lang("$string_file.user_intro_screen")</h2>
                        @for($i=0; $i < 3; $i++)
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        @if($application_theme)
                                            @php $user_intro_text = isset($user_intro[$i]) ? $user_intro[$i]['text'] : ""; @endphp
                                        @endif
                                        <label for="business_name">@lang("$string_file.screen") {{$i+1}} @lang("$string_file.text") </label>
                                        {!! Form::text('user_intro_text[]', old('user_intro_text',$user_intro_text), array('class' => 'form-control', 'id' => 'user_intro_text', 'placeholder' => 'Intro text')) !!}
                                        @if ($errors->has('user_intro_text'))
                                            <span class="help-block">
                              <strong>{{ $errors->first('user_intro_text') }}</strong>
                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label for="user_app_logo">@lang("$string_file.screen") @lang("$string_file.logo")</label>
                                        @php
                                            if($application_theme){
                                            $user_intro_logo_old = isset($user_intro_logo[$i]) ? $user_intro_logo[$i]['image'] : "";
                                            $user_intro_logo_image = $user_intro_logo_old ? get_image($user_intro_logo_old, 'user_app_theme',$application_theme->merchant_id) : "";
                                            }
                                        @endphp
                                        @php $required =  $i == 0 ? 'required' : '';  @endphp
                                        {!! Form::hidden('user_intro_logo_old_'.$i, $user_intro_logo_old ) !!}
                                        {!! Form::file('user_intro_logo_'.$i, $attributes = array('class' => 'form-control', 'id' => 'user_intro_logo','onchange' => 'readURL(this);', isset($application_theme->user_app_logo)? '' : $required)) !!}
                                        <p class="help-block">@lang("$string_file.size_100_100")</p>
                                        @if ($errors->has('user_intro_logo'))
                                            <span class="help-block">
                              <strong>{{ $errors->first('user_intro_logo') }}</strong>
                            </span>
                                        @endif
                                    </div>
                                    <img src="{{ $user_intro_logo_image }}" id="blah" class="avatar img-circle" alt=""
                                         height="100px" width="100px" style="margin-bottom:10px;">
                                </div>
                            </div>
                        @endfor
                        <div class="form-actions right" style="margin-bottom: 3%">
                            @if(Auth::user('merchant')->can('edit_application_theme'))

                            @endif
                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
