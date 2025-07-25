@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            @if(Session::has('personal-document-expire-warning'))
                <p class="alert alert-info">{{ Session::get('personal-document-expire-warning') }}</p>
            @endif
            @if(Session::has('personal-document-expired-error'))
                <div class="alert dark alert-icon alert-danger" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <i class="icon fa-warning"
                       aria-hidden="true"></i> {{ Session::get('personal-document-expired-error') }}
                </div>
            @endif
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->add_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{ route('driver.index') }}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang("$string_file.all_driver")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                    {!! $title !!}
                </header>
                <div class="panel-body container-fluid">
                    {{-- If demo is not exist --}}
                    @php $id = NULL; $required = "required"; @endphp
                    @if(!empty($driver->id))
                        @php $id = $driver->id; $required=""; @endphp

                    @endif
                    @if($id == NULL || $edit_permission)
                        {!! Form::open(["class"=>"steps-validation wizard-notification", "files"=>true,"url"=>route('driver.save',$id), "id" => "driver-register", "name" => "driver-register"]) !!}
                        {!! Form::hidden('id',$id,['id'=>"driver_id"]) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="form-section col-md-12" style="color: #000000;"><i
                                            class="wb-user"></i> @lang("$string_file.personal_details")
                                </h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="location3">@lang("$string_file.country")
                                                <span class="text-danger">*</span>
                                            </label>
                                            @if(empty($id) || (!empty($id) && empty($driver->country_id)))
                                                <select class="form-control" name="country" id="country"
                                                        onchange="getAreaList(this)" required>
                                                    <option value="">@lang("$string_file.select")</option>
                                                    @foreach($countries as $country)
                                                        <option data-min="{{ $country->minNumPhone }}"
                                                                data-max="{{ $country->maxNumPhone }}"
                                                                data-ISD="{{ $country->phonecode }}"
                                                                value="{{ $country->id }}"
                                                                data-id="{{ $country->id }}">{{ $country->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('country'))
                                                    <label class="text-danger">{{ $errors->first('country') }}</label>
                                                @endif
                                            @else
                                                {!! Form::text('county_name',$driver->Country->CountryName,['class'=>'form-control','disabled'=>true]) !!}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="location3">@lang("$string_file.service_area")
                                                <span class="text-danger">*</span>
                                            </label>
                                            @if(empty($id) || (!empty($id) && empty($driver->country_area_id)))
                                                <!--<select class="form-control" name="area" id="area">-->
                                                <!--    <option value="">--@lang("$string_file.select")--</option>-->
                                                <!--</select>-->
                                                {!! Form::select("area",$areas,null,["class" => "form-control", "id" => "area", "required" => true]) !!}
                                                @if ($errors->has('area'))
                                                    <label class="text-danger">{{ $errors->first('area') }}</label>
                                                @endif
                                            @else
                                                {!! Form::text('county_area_name',$driver->CountryArea->CountryAreaName,['class'=>'form-control','disabled'=>true]) !!}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="user_phone">@lang("$string_file.phone")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="row">
                                                <input type="text"
                                                       class="form-control col-md-3 ml-15 col-sm-3 col-3" id="isd"
                                                       name="isd"
                                                       value="{{old('isd',isset($driver->Country) ? $driver->Country->phonecode : NULL)}}"
                                                       placeholder="@lang("$string_file.isd_code")" readonly/>

                                                <input type="number" class="form-control col-md-8 col-sm-8 col-8"
                                                       id="phone" name="phone"
                                                       value="{{old('phone',old('phone',!empty($driver->country_id) ? str_replace($driver->Country->phonecode,"",$driver->phoneNumber): ""))}}"
                                                       placeholder="" required/>
                                            </div>
                                            @if ($errors->has('phonecode'))
                                                <label class="text-danger">{{ $errors->first('phonecode') }}</label>
                                            @endif
                                            @if ($errors->has('phone'))
                                                <label class="text-danger">{{ $errors->first('phone') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="first_name"> @lang("$string_file.first_name")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="first_name"
                                                   name="first_name"
                                                   value="{{old('first_name',isset($driver->first_name) ? $driver->first_name : NULL)}}"
                                                   placeholder=" @lang("$string_file.first_name")" required
                                                   autocomplete="off"/>
                                        </div>
                                        @if ($errors->has('first_name'))
                                            <label class="text-danger">{{ $errors->first('first_name') }}</label>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="firstName3">@lang("$string_file.last_name")
                                                {{--                                                    <span class="text-danger">*</span>--}}
                                            </label>
                                            <input type="text" class="form-control" id="last_name" name="last_name"
                                                   value="{{old('last_name',isset($driver->last_name) ? $driver->last_name : NULL)}}"
                                                   placeholder="@lang("$string_file.last_name")"
                                                   autocomplete="off"/>
                                        </div>
                                        @if ($errors->has('last_name'))
                                            <label class="text-danger">{{ $errors->first('last_name') }}</label>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="emailAddress5">@lang("$string_file.profile_image")
                                                <span class="text-danger">*</span>
                                                @if(!empty($driver->profile_image))
                                                    <a href="{{get_image($driver->profile_image,'driver',$driver->merchant_id)}}"
                                                       target="_blank">@lang("$string_file.view")</a>
                                                @endif
                                            </label>
                                            <input type="file" class="form-control" id="image" name="image"
                                                    {{$required}}/>
                                            @if ($errors->has('image'))
                                                <label class="text-danger">{{ $errors->first('image') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="cover_image">@lang("$string_file.cover_image")
                                                @if(!empty($driver->cover_image))
                                                    <a href="{{get_image($driver->cover_image,'driver',$driver->merchant_id)}}"
                                                       target="_blank">@lang("$string_file.view")</a>
                                                @endif
                                            </label>
                                            <input type="file" class="form-control" id="cover_image" name="cover_image"/>
                                            @if ($errors->has('cover_image'))
                                                <label class="text-danger">{{ $errors->first('cover_image') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label" for="email">@lang("$string_file.email")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                   placeholder="" autocomplete="off"
                                                   value="{{old('email',isset($driver->email) ? $driver->email : NULL)}}"
                                                   @if($config->driver_email_enable == 1) required @endif/>
                                            <input type="hidden" name="driver_email_enable" value="@if($config->driver_email_enable == 1) true @else false @endif">
                                        </div>
                                        @if ($errors->has('email'))
                                            <label class="text-danger">{{ $errors->first('email') }}</label>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="emailAddress5">@lang("$string_file.password")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="password" class="form-control" id="password"
                                                   name="password"
                                                   placeholder="" autocomplete="off"
                                                    {{$required}}/>
                                        </div>
                                        @if ($errors->has('password'))
                                            <label class="text-danger">{{ $errors->first('password') }}</label>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="location">{{trans("$string_file.confirm_password") }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="password" class="form-control" id="password_confirmation"
                                                   name="password_confirmation"
                                                   placeholder="" autocomplete="off"
                                                    {{$required}}/>
                                        </div>
                                    </div>
                                    <input type="hidden" id="single_group" name="single_group" value="{{$group['single_group']}}"/>
                                    <div class="col-md-4 @if(isset($group['single_group']) && $group['single_group'] == 1) custom-hidden @endif">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="segment_group_id">@lang("$string_file.segment_group")
                                                <span class="text-danger">*</span>
                                            </label>
                                            @if(empty($id) || (!empty($driver) && $driver->segment_group_id == NULL))
                                                {{Form::select('segment_group_id',$group['arr_group'],old('segment_group_id'),['class'=>'form-control','id'=>'segment_group_id','required'=>true])}}
                                                @if ($errors->has('segment_group_id'))
                                                    <label class="text-danger">{{ $errors->first('segment_group_id') }}</label>
                                                @endif
                                            @else
                                                {!! Form::text('segment_group',$group['arr_group'][$driver->segment_group_id],['class'=>'form-control','disabled'=>true]) !!}
                                            @endif
                                        </div>
                                    </div>
                                    @if($config->driver_vat_configuration == 1 )
                                        <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="location">{{trans("$string_file.driver_vat_number") }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="vat_number" class="form-control" id="vat_number"
                                                   name="vat_number" value="{{isset($driver->vat_number) ? $driver->vat_number : NULL}}"
                                                   placeholder="" autocomplete="off"/>
                                        </div>
                                    </div>
                                    @endif
                                    @if($config->enable_super_driver == 1)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label"
                                                       for="driver_gender">@lang("$string_file.driver") @lang("$string_file.category")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control" name="is_super_driver" id="is_super_driver" required>
                                                    <option value="1" @if(!empty($driver) && array_key_exists('is_super_driver', $driver->toArray()) && $driver->is_super_driver == 1) selected @endif>@lang("$string_file.special")</option>
                                                    <option value="2" @if(!empty($driver) && array_key_exists('is_super_driver', $driver->toArray()) && $driver->is_super_driver == "") selected @endif>@lang("$string_file.normal")</option>
                                                </select>
                                                @if ($errors->has('is_super_driver'))
                                                    <label class="text-danger">{{ $errors->first('is_super_driver') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    <input type="hidden" id="driver_gender_enable" name="driver_gender_enable" value="{{$config->gender}}"/>
                                    @if($config->gender == 1)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label"
                                                       for="driver_gender">@lang("$string_file.gender")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control" name="driver_gender" id="driver_gender"
                                                        required>
                                                    <option value="1"
                                                            @if(!empty($driver->driver_gender) && $driver->driver_gender == 1) selected @endif>@lang("$string_file.male")</option>
                                                    <option value="2"
                                                            @if(!empty($driver->driver_gender) && $driver->driver_gender == 2) selected @endif>@lang("$string_file.female")</option>
                                                </select>
                                                @if ($errors->has('driver_gender'))
                                                    <label class="text-danger">{{ $errors->first('driver_gender') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    @if(!empty($arr_commission_choice))
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label"
                                                       for="driver_gender">@lang("$string_file.driver_commission_choice")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                {{Form::select('pay_mode',$arr_commission_choice,old('pay_mode',isset($driver->pay_mode) ? $driver->pay_mode : NULL),['class'=>'form-control','id'=>'pay_mode','required'=>true])}}
                                                @if ($errors->has('pay_mode'))
                                                    <label class="text-danger">{{ $errors->first('pay_mode') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    @if($config->stripe_connect_enable == 1)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label">
                                                    @lang("$string_file.dob") <span class="text-danger">*</span>
                                                </label>
                                                <input type="text"
                                                       class="form-control customDatePicker1"
                                                       name="dob"
                                                       value="{{old('dob',isset($driver->dob) ? $driver->dob : NULL)}}"
                                                       placeholder=""
                                                       required
                                                       autocomplete="off">
                                                <span class="text-danger">{{$errors->first('dob')}}</span>
                                            </div>
                                        </div>
                                    @endif
                                    <input type="hidden" id="smoker_enable" name="smoker_enable" value="{{$config->smoker}}"/>
                                    @if($config->smoker == 1)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label"
                                                       for="location3"> @lang("$string_file.smoke")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control" name="smoker_type" id="smoker_type"
                                                        required>
                                                    <option value="1"
                                                            @if(!empty($driver->DriverRideConfig) && $driver->DriverRideConfig->smoker_type == 1)
                                                            selected="selected" @endif> @lang("$string_file.smoker")</option>
                                                    <option value="2"
                                                            @if(!empty($driver->DriverRideConfig) && $driver->DriverRideConfig->smoker_type == 2)
                                                            selected="selected" @endif> @lang("$string_file.non_smoker")</option>
                                                </select>

                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-control-label"></label>
                                            <div class="checkbox-inline"
                                                 style="margin-left: 5%;margin-top: 1%;">
                                                <input type="checkbox" name="allow_other_smoker" value="1"
                                                       id="allow_other_smoker"
                                                       @if(!empty($driver->DriverRideConfig) && $driver->DriverRideConfig->allow_other_smoker == 1) checked
                                                        @endif>
                                                <label> @lang("$string_file.allow_other_to_smoke")</label>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <br>
                                @if(isset($config->driver_address) && $config->driver_address == 1)
                                <h5 class="form-section col-md-12" style="color: black;">
                                    <i class="fa fa-address-card"></i> @lang("$string_file.address")
                                </h5>
                                <hr>
                                <div class="row" id="driver_address_content">
                                </div>
                                @endif
                                @if($config->bank_details == 1)
                                    <br>
                                    <h5 class="form-section col-md-12" style="color: black;"><i
                                                class="fa fa-bank"></i> @lang("$string_file.bank_details")
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label"
                                                       for="lastName3">@lang("$string_file.bank_name")
                                                </label>
                                                <input type="text" class="form-control" id="bank_name"
                                                       name="bank_name"
                                                       value="{{old('bank_name',isset($driver->bank_name) ? $driver->bank_name : NULL)}}"
                                                       placeholder="Your bank name"
                                                       autocomplete="off"/>
                                            </div>
                                            @if ($errors->has('bank_name'))
                                                <label class="text-danger">{{ $errors->first('bank_name') }}</label>
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label"
                                                       for="lastName3">@lang("$string_file.account_holder_name")
                                                </label>
                                                <input type="text" class="form-control" id="account_holder_name"
                                                       name="account_holder_name"
                                                       value="{{old('account_holder_name',isset($driver->account_holder_name) ? $driver->account_holder_name : NULL)}}"
                                                       placeholder=""
                                                       autocomplete="off"/>
                                            </div>
                                            @if ($errors->has('account_holder_name'))
                                                <label class="text-danger">{{ $errors->first('account_holder_name') }}</label>
                                            @endif
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label"
                                                       for="lastName3">@lang("$string_file.account_number")
                                                </label>
                                                <input type="text" class="form-control" id="account_number"
                                                       name="account_number"
                                                       value="{{old('account_number',isset($driver->account_number) ? $driver->account_number : NULL)}}"
                                                       placeholder="@lang("$string_file.account_number")"
                                                       autocomplete="off"/>
                                            </div>
                                            @if ($errors->has('account_number'))
                                                <label class="text-danger">{{ $errors->first('account_number') }}</label>
                                            @endif
                                        </div>
                                        @if($config->stripe_connect_enable == 1)
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-control-label"
                                                           for="lastName3">@lang("$string_file.bsb_routing_number")
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" class="form-control" id="bsb_routing_number"
                                                           name="bsb_routing_number"
                                                           value="{{ old('bsb_routing_number',isset($driver->routing_number) ? $driver->routing_number : NULL) }}"
                                                           placeholder=""
                                                           autocomplete="off" required/>
                                                </div>
                                                @if ($errors->has('bsb_routing_number'))
                                                    <label class="text-danger">{{ $errors->first('bsb_routing_number') }}</label>
                                                @endif
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-control-label"
                                                           for="lastName3">@lang("$string_file.abn_number")
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" class="form-control" id="abn_number"
                                                           name="abn_number"
                                                           value="{{ old('abn_number',isset($driver->abn_number) ? $driver->abn_number : '') }}"
                                                           placeholder=""
                                                           autocomplete="off"
                                                           required/>
                                                </div>
                                                @if ($errors->has('abn_number'))
                                                    <label class="text-danger">{{ $errors->first('abn_number') }}</label>
                                                @endif
                                            </div>
                                        @else
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-control-label"
                                                           for="location3"><div id="transaction_label">@lang("$string_file.online_transaction_code")</div>
                                                    </label>
                                                    <input type="text" class="form-control" id="online_transaction"
                                                           name="online_transaction"
                                                           value="{{old('online_transaction',isset($driver->online_code) ? $driver->online_code : NULL)}}"
                                                           placeholder="@lang("$string_file.online_transaction_code")"
                                                           autocomplete="off"/>
                                                </div>
                                                @if ($errors->has('online_transaction'))
                                                    <label class="text-danger">{{ $errors->first('online_transaction') }}</label>
                                                @endif
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-control-label"
                                                           for="location3">@lang("$string_file.account_type")
                                                    </label>
                                                    <select type="text" class="form-control" name="account_types"
                                                            id="account_types">
                                                        @foreach($account_types as $account_type)
                                                            <option value="{{$account_type->id}}">@if($account_type->LangAccountTypeSingle){{$account_type->LangAccountTypeSingle->name}}
                                                                @else {{$account_type->LangAccountTypeAny->name}} @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if ($errors->has('account_types'))
                                                        <label class="text-danger">{{ $errors->first('account_types') }}</label>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                <div class="row">
                                    @if($config->driver_kin_person_details_on_signup == 1)
                                        @php
                                            $kin_details = !empty($driver->kin_details) ? json_decode($driver->kin_details)[0] : null;
                                        @endphp
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="kin_person_name">
                                                    @lang("$string_file.kin_person_name")

                                                </label>
                                                <input type="text" class="form-control" id="kin_person_name"
                                                       name="kin_person_name" value="{{!empty($kin_details)? $kin_details->kin_name: ''}}"
                                                       placeholder="@lang("$string_file.kin_person_name")" >
                                                @if ($errors->has('kin_person_name'))
                                                    <label class="text-danger">{{ $errors->first('kin_person_name') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="kin_person_phone">
                                                    @lang("$string_file.kin_person_phone")

                                                </label>
                                                <input type="text" class="form-control" id="kin_person_phone"
                                                       name="kin_person_phone" value="{{!empty($kin_details)? $kin_details->kin_phone_number: ''}}"
                                                       placeholder="@lang("$string_file.kin_person_phone")" >
                                                @if ($errors->has('kin_person_phone'))
                                                    <label class="text-danger">{{ $errors->first('kin_person_phone') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                </div>

                            </div>
                            <br>
                        </div>

                        <div id="personal-document">
                            {!! $personal_document !!}
                        </div>
                        <div class="form-actions float-right" style="margin-bottom: 1%">
                            <button type="submit" class="btn btn-primary"><i
                                        class="fa fa-check-circle"></i> @lang("$string_file.save") & @lang("$string_file.proceed")
                            </button>
                        </div>
                        {!! Form::close() !!}
                    @else
                        <div class="alert dark alert-icon alert-danger" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <i class="icon fa-warning" aria-hidden="true"></i> @lang("$string_file.demo_user_cant_edited").
                        </div>
                        {{--                            <div class="card-body">--}}
                        {{--                                <div class="large">@lang("$string_file.demo_user_cant_edited").</div>--}}
                        {{--                            </div>--}}
                </div>
                @endif
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script>
        @if(!empty($id))
            getCountryConfig("{{$driver->country_id}}", "{{$id}}");
        @endif
        $(document).on('change', '#country', function () {
            var id = this.options[this.selectedIndex].getAttribute('data-id');
            var driver_id = "{{$id}}";
            getCountryConfig(id, driver_id);
        });

        function getCountryConfig(id, driver_id){
            $.ajax({
                method: 'GET',
                url: "{{ route('merchant.country.config') }}",
                data: {id: id, driver_id: driver_id},
                success: function (data) {
                    console.log(data);
                    $('#transaction_label').html(data.transaction_code);
                    $('#online_transaction').attr('placeholder', 'Enter ' + data.transaction_code);
                    $("#driver_address_content").html(data.driver_address_fields);
                }
            });
        }

        function getAreaList(obj) {
            var id = obj.options[obj.selectedIndex].getAttribute('data-id');
            $.ajax({
                method: 'GET',
                url: "{{ route('merchant.country.arealist') }}",
                data: {country_id: id},
                success: function (data) {
                    $('#area').html(data);
                }
            });
        }
        //
        // function validatesignup() {
        //     var driver_id = $('#driver_id').val();
        //     if (driver_id == "") {
        //         var password = document.getElementById('password').value;
        //         var con_password = document.getElementById('password_confirmation').value;
        //         if (password == "") {
        //             alert("Please enter password");
        //             return false;
        //         }
        //         if (con_password == "") {
        //             alert("Please enter confirm password");
        //             return false;
        //         }
        //         if (con_password != password) {
        //             alert("Password and Confirm password must be same");
        //             return false;
        //         }
        //     }
        // }

        $(document).on('change', '#area', function (e) {
            var area_id = $("#area option:selected").val();
            if (area_id != "") {
                var token = $('[name="_token"]').val();
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: "{{ route('merchant.driver.country-area-document') }}",
                    data: {area_id: area_id},
                    success: function (data) {
                        $('#personal-document').html(data);
                        var dateToday = new Date();
                        $('.customDatePicker1').datepicker({
                            format: 'yyyy-mm-dd',
                            startDate: dateToday,
                            onRender: function (date) {
                                return date.valueOf() < now.valueOf() ? 'disabled' : '';
                            }
                        });
                    }
                });
            }
        });
    </script>
@endsection
