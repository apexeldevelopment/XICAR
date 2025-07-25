@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('laundry-outlet.index') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px"><i
                                            class="wb-reply"></i>
                                </button>
                            </a>
                            {{--                            @dd($data)--}}
                            @if(!empty($info_setting) && $info_setting->add_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                        data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title">
                        <i class=" fa fa-building" aria-hidden="true"></i>
                        {!! $data['title'] !!}
                    </h3>
                </div>
                @php $id = NULL;  @endphp
                @php $disabled = false; @endphp
                {{--                @if(isset($data['laundry_outlet'  ]['id']))--}}
                {{--                    @php $id = $data['laundry_outlet']['id']; $disabled = true @endphp--}}
                {{--                @endif--}}
                {{--                @if($id == NULL)--}}
                {{--                    @php $default_open_time = "00:00"; $default_close_time = "23:55"; @endphp--}}
                {{--                @endif--}}
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'handyman-store','id'=>'handyman-store-form','files'=>true,'url'=>$data['save_url'],'method'=>'POST','autocomplete'=>'off'] ) !!}
                    {{--                    {!! Form::hidden('id',old('id',isset($data['laundry_outlet']['id']) ? $data['laundry_outlet']['id'] : NULL),['id'=>'id']) !!}--}}
                    {{--                    {!! Form::hidden('slug',$data['slug'],array("id" => "slug")) !!}--}}
                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.name")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('full_name',old('full_name',isset($data['laundry_outlet']['full_name']) ? $data['laundry_outlet']['full_name'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('full_name'))
                                    <label class="text-danger">{{ $errors->first('full_name') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="lastName3">
                                    @lang("$string_file.email")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('email',old('email',isset($data['laundry_outlet']['email']) ? $data['laundry_outlet']['email'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('email'))
                                    <label class="text-danger">{{ $errors->first('email') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-{!! !empty($id) ? 2 : 4 !!}">
                            <div class="form-group">
                                <label for="password">
                                    @lang("$string_file.password")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::password('password',['id'=>'password','class'=>'form-control','required'=>true,"disabled"=>$disabled]) !!}
                                @if ($errors->has('password'))
                                    <label class="text-danger">{{ $errors->first('password') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2 {!! !empty($id) ? "" : "custom-hidden" !!} mt-40">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" value="1" name="edit_password" id="edit_password">
                                    @lang("$string_file.edit_password")
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.country")
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-control select2" name="country_id"
                                        id="country_id"  onchange="changeCountry()" required>
                                    @foreach($data['countries']  as $country)
                                        <option data-currency-code="{{ $country->isoCode }}"
                                                value="{{ $country->id }}" data-country-name = "{{isset($country->LanguageCountrySingle->name) ? $country->LanguageCountrySingle->name : $country->LanguageCountryAny->name }}"
                                                @if(isset($data['laundry_outlet']['country_id']) && $data['laundry_outlet']['country_id'] == $country->id) selected @endif>
                                            ({{ $country->phonecode }}) {{ isset($country->LanguageCountrySingle->name) ? $country->LanguageCountrySingle->name : $country->LanguageCountryAny->name }}
                                        </option>
                                    @endforeach
                                </select>
                                {{--                                {!! Form::select('country_id',$data['countries'],old('country_id',isset($data['laundry_outlet']['country_id']) ? $data['laundry_outlet']['country_id'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}--}}
                                @if ($errors->has('country_id'))
                                    <label class="text-danger">{{ $errors->first('country_id') }}</label>
                                @endif
                            </div>
                            <input type="hidden" class="form-control" value="{{$default_lat}}" name="country_lat" id="country_lat">
                            <input type="hidden" class="form-control" value="{{$default_long}}" name="country_long" id="country_long">
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.phone")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('phone_number',old('phone_number',isset($data['laundry_outlet']['phone_number']) ? $data['laundry_outlet']['phone_number'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('phone_number'))
                                    <label class="text-danger">{{ $errors->first('phone_number') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.is_popular")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('is_popular',$data['is_popular'],old('is_popular',isset($data['laundry_outlet']['is_popular']) ? $data['laundry_outlet']['is_popular'] : NULL),['id'=>'is_popular','class'=>'form-control','required'=>true]) !!}
                            </div>
                        </div>
                        <div class="col-md-4" id="areaList">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.landmark")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('landmark',old('landmark',isset($data['laundry_outlet']['landmark']) ? $data['laundry_outlet']['landmark'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('landmark'))
                                    <label class="text-danger">{{ $errors->first('landmark') }}</label>
                                @endif
                            </div>
                        </div>
                        {{--                        @if($data['slug'] == 'GROCERY')--}}
                        {{--                            <div class="col-md-4">--}}
                        {{--                                <div class="form-group">--}}
                        {{--                                    <label for="grocery_configuration_instant_slot">--}}
                        {{--                                        @lang("$string_file.grocery_configuration_instant_slot")--}}
                        {{--                                        <span class="text-danger">*</span>--}}
                        {{--                                    </label>--}}
                        {{--                                    {!! Form::select('groery_configuration_instant_slot',$grocery_instant_slot,old('grocery_configuration_instant_slot',isset($data['laundry_outlet']['grocery_configuration_instant_slot']) ? $data['laundry_outlet']['grocery_configuration_instant_slot'] : NULL),['id'=>'grocery_configuration_instant_slot','class'=>'form-control','required'=>true]) !!}--}}
                        {{--                                    @if ($errors->has('grocery_configuration_instant_slot'))--}}
                        {{--                                        <label class="text-danger">{{ $errors->first('grocery_configuration_instant_slot') }}</label>--}}
                        {{--                                    @endif--}}
                        {{--                                </div>--}}
                        {{--                            </div>--}}
                        {{--                        @endif--}}
                        @if(in_array(6,$arr_merchant_service_type))
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.self_pickup")
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('self_pickup',$data['self_pickup'],old('self_pickup',isset($data['laundry_outlet']['self_pickup']) ? $data['laundry_outlet']['self_pickup'] : NULL),['id'=>'self_pickup','class'=>'form-control','required'=>true]) !!}
                                </div>
                            </div>
                        @endif
                        @if(in_array(7,$arr_merchant_service_type))
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.dine_in")
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('dine_in',$data['dine_in'],old('dine_in',isset($data['laundry_outlet']['dine_in']) ? $data['laundry_outlet']['dine_in'] : NULL),['id'=>'dine_in','class'=>'form-control','required'=>true]) !!}
                                </div>
                            </div>
                        @endif
                        <div class="col-md-4 ">
                            <div class="form-group">
                                <label for="minimum_amount">
                                    @lang("$string_file.rating")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::number("rating",old("rating",isset($data ['laundry_outlet']['rating']) ? $data ['laundry_outlet']['rating'] : NULL),["step"=>"0.1", "min"=>0,"max"=>5,"class"=>"form-control", "id"=>"rating","placeholder"=>'',"required"=>true]) !!}
                                @if ($errors->has('rating'))
                                    <label class="text-danger">{{ $errors->first('rating') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 corporate_inr">
                            <div class="form-group">
                                <label for="location3">
                                    @lang("$string_file.logo")
                                    <span class="text-danger">*</span>
                                    @php $required = true; @endphp
                                    @if(!empty($data['laundry_outlet']['business_logo']))
                                        @php $required = false; @endphp
                                        <a href="{{get_image($data['laundry_outlet']['business_logo'],'laundry_outlet_logo')}}"
                                           target="_blank">View Logo</a>
                                    @endif
                                </label>
                                <input style="height: 0%;" class="form-control" type="file" name="business_logo"
                                       id="business_logo" {{$required ? "required":""}}>
                                @if ($errors->has('business_logo'))
                                    <label class="text-danger">{{ $errors->first('business_logo') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="login_background_image">
                                    @lang("$string_file.login_background_image") :
                                    <span class="danger">*</span>
                                </label>

                                @if(!empty($data['laundry_outlet']['login_background_image']))
                                    <a href="{{get_image($data['laundry_outlet']['login_background_image'],'laundry_outlet_login_background_image')}}"
                                       target="_blank">@lang("$string_file.view")</a>
                                @endif
                                <input type="file" class="form-control" id="login_background_image"
                                       name="login_background_image"
                                       placeholder="@lang("$string_file.login_background_image")">
                                <br>
                                <span style="color:red;">@lang("$string_file.login_image_warning")</span>
                                @if ($errors->has('login_background_image'))
                                    <label class="danger">{{ $errors->first('login_background_image') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="profile_image">
                                    @lang("$string_file.profile_image") :
                                    <span class="danger">*</span>
                                </label>
                                @if(!empty($data['laundry_outlet']['business_profile_image']))
                                    <a href="{{get_image($data['laundry_outlet']['business_profile_image'],'laundry_outlet_profile_image')}}"
                                       target="_blank">@lang("$string_file.view")</a>
                                @endif
                                <input type="file" class="form-control" id="business_profile_image"
                                       name="business_profile_image"
                                       placeholder="@lang("$string_file.profile_image")">
                                <br>
                                <span style="color:red;">@lang("$string_file.profile_image_warning")</span>
                                @if ($errors->has('business_profile_image'))
                                    <label class="danger">{{ $errors->first('business_profile_image') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.status")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('status',$data['arr_status'],old('status',isset($data['laundry_outlet']['status']) ? $data['laundry_outlet']['status'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('status'))
                                    <label class="text-danger">{{ $errors->first('status') }}</label>
                                @endif
                            </div>
                        </div>
                        {{--                        <div class="col-md-4">--}}
                        {{--                            <div class="form-group">--}}
                        {{--                                <label for="emailAddress5">--}}
                        {{--                                    @lang("$string_file.order_request_open_receiver")--}}
                        {{--                                    <span class="text-danger">*</span>--}}
                        {{--                                </label>--}}
                        {{--                                {!! Form::select('order_request_receiver',$data['request_receiver'],old('order_request_receiver',isset($data['laundry_outlet']['order_request_receiver']) ? $data['laundry_outlet']['order_request_receiver'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}--}}
                        {{--                                @if ($errors->has('order_request_receiver'))--}}
                        {{--                                    <label class="text-danger">{{ $errors->first('order_request_receiver') }}</label>--}}
                        {{--                                @endif--}}
                        {{--                            </div>--}}
                        {{--                        </div>--}}

                        <div class="col-md-4 ">
                            <div class="form-group">
                                <label for="minimum_amount">
                                    @lang("$string_file.merchant_commission_method")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select("commission_method",add_blank_option(array("1" => trans("$string_file.fixed")." ".trans("$string_file.amount"), "2" => trans("$string_file.percentage"))),old("commission_method",isset($data ['laundry_outlet']['commission_method']) ? $data ['laundry_outlet']['commission_method'] : NULL),["class"=>"form-control select2", "id"=>"commission_method","placeholder"=>'',"required"=>true, "onchange"=>"changeOfferType()"]) !!}
                                @if ($errors->has('commission_method'))
                                    <label class="text-danger">{{ $errors->first('commission_method') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 ">
                            <div class="form-group">
                                <label for="minimum_amount">
                                    @lang("$string_file.merchant_commission")
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="commission_method_symbol">
                                            @if(isset($data ['laundry_outlet']['commission_method']) && $data ['laundry_outlet']['commission_method'] == 1)
                                                {{ $data['laundry_outlet']->CountryArea->Country->isoCode }}
                                            @elseif(isset($data ['laundry_outlet']['commission_method']) && $data ['laundry_outlet']['commission_method'] == 2)
                                                %
                                            @endif
                                        </span>
                                    </div>
                                    {!! Form::number("commission",old("commission",isset($data ['laundry_outlet']['commission']) ? $data ['laundry_outlet']['commission'] : NULL),["step"=>"0.01", "min"=>"0","class"=>"form-control", "id"=>"commission","placeholder"=>'',"required"=>true]) !!}
                                </div>
                                @if ($errors->has('commission'))
                                    <label class="text-danger">{{ $errors->first('commission') }}</label>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="latitude">
                                    @lang("$string_file.latitude")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('latitude',old('latitude',isset($data['laundry_outlet']['latitude']) ? $data['laundry_outlet']['latitude'] : NULL),['id'=>'lat','class'=>'form-control','required'=>true,'readonly' => true]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.longitude")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('longitude',old('longitude',isset($data['laundry_outlet']['longitude']) ? $data['laundry_outlet']['longitude'] : NULL),['id'=>'lng','class'=>'form-control','required'=>true,'readonly' => true]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.address")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('address',old('address',isset($data['laundry_outlet']['address']) ? $data['laundry_outlet']['address'] : NULL),['id'=>'location','class'=>'form-control','required'=>true,'readonly' => true]) !!}
                                @if ($errors->has('address'))
                                    <label class="text-danger">{{ $errors->first('address') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" id="edit_lat_long" onclick="editLatLong(this)">
                                <label for="edit_lat_long">@lang("$string_file.edit_latitude_longitude")
                                    . </label>
                            </div>
                        </div>
                        {{--                        @if(empty($id))--}}
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            {!! Form::label('searchInput', trans("$string_file.address"), ['class' => 'control-label']) !!}
                            <input id="searchInput" class="input-controls" type="text"
                                   placeholder="@lang("$string_file.enter_address")">
                            <div class="map" id="map" style="width: 100%; height: 300px;"></div>
                        </div>
                        <script type="text/javascript"
                                src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyC7lIIgBajzx409vxmmY_CJPcRvDb114w4"></script>
                        {{--                        @endif--}}
                    </div>
                    <hr>
                    <div class="row">
                        @php
                            $arr_open_time = isset($data['laundry_outlet']['open_time']) ? json_decode($data['laundry_outlet']['open_time']) : NULL;
                            $arr_close_time = isset($data['laundry_outlet']['close_time']) ? json_decode($data['laundry_outlet']['close_time']) : NULL;


                            $opentime = "00:00";
                            $closetime = "23:55";
                        @endphp
                        @foreach($data['arr_day'] as $day_key=> $day)
                            <div class="col-md-2">
                                <div class="form-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="wb-check"
                                                                          aria-hidden="true"></i></span>
                                        {{--                                        {!! Form::hidden('days[]',$day_key,['class'=>'form-control']) !!}--}}
                                        {!! Form::text('day_name[]',NULL,['class'=>'form-control','id'=>'time','placeholder'=>$day,'readonly'=>true]) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="wb-time" aria-hidden="true"></i></span>

                                        {!! Form::text('open_time['.$day_key.']',old('open_time',isset($arr_open_time[$day_key]) ? $arr_open_time[$day_key] : $opentime),['class'=>'timepicker form-control','data-plugin'=>'clockpicker','data-autoclose'=>"true",'id'=>'time','placeholder'=>'','autocomplete'=>'off']) !!}
                                    </div>
                                    @if ($errors->has('open_time'))
                                        <label class="text-danger">{{ $errors->first('open_time') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="wb-time" aria-hidden="true"></i></span>
                                        {!! Form::text('close_time['.$day_key.']',old('close_time',isset($arr_close_time[$day_key]) ? $arr_close_time[$day_key] : $closetime),['class'=>'timepicker form-control','data-plugin'=>'clockpicker','data-autoclose'=>"true",'id'=>'time','placeholder'=>'','autocomplete'=>'off']) !!}
                                    </div>
                                    @if($errors->has('close_time'))
                                        <label class="text-danger">{{ $errors->first('close_time') }}</label>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                    </div>
                    <hr>
                    <h5>@lang("$string_file.bank_details")</h5>
                    @php $arr_account_info = !empty($data['laundry_outlet']['bank_details']) ? json_decode($data['laundry_outlet']['bank_details'],true) : [];  @endphp
                    <div class="row">
                        <div class="col-md-4 ">
                            <div class="form-group">
                                <label for="minimum_amount">
                                    @lang("$string_file.bank_name")
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    {!! Form::text("bank_name",old("bank_name",isset($arr_account_info['account_number']) ? $arr_account_info['bank_name'] : NULL),["class"=>"form-control", "id"=>"bank_name","placeholder"=>'',"required"=>true]) !!}
                                </div>
                                @if ($errors->has('bank_name'))
                                    <label class="text-danger">{{ $errors->first('bank_name') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 ">
                            <div class="form-group">
                                <label for="minimum_amount">
                                    @lang("$string_file.account_holder_name")
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    {!! Form::text("account_holder_name",old("account_holder_name",isset($arr_account_info['account_holder_name']) ? $arr_account_info['account_holder_name'] : NULL),["class"=>"form-control", "id"=>"account_holder_name","placeholder"=>'',"required"=>true]) !!}
                                </div>
                                @if ($errors->has('account_holder_name'))
                                    <label class="text-danger">{{ $errors->first('account_holder_name') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 ">
                            <div class="form-group">
                                <label for="minimum_amount">
                                    @lang("$string_file.account_number")
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    {!! Form::text("account_number",old("account_number",isset($arr_account_info['account_number']) ? $arr_account_info['account_number'] : NULL),["class"=>"form-control", "id"=>"account_number","placeholder"=>'',"required"=>true]) !!}
                                </div>
                                @if ($errors->has('account_number'))
                                    <label class="text-danger">{{ $errors->first('account_number') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 ">
                            <div class="form-group">
                                <label for="bank_code">
                                    @lang("$string_file.bank_code")
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    {!! Form::text("bank_code",old("bank_code",isset($arr_account_info['bank_code']) ? $arr_account_info['bank_code'] : NULL),["class"=>"form-control", "id"=>"bank_code","placeholder"=>'',"required"=>true]) !!}
                                </div>
                                @if ($errors->has('bank_code'))
                                    <label class="text-danger">{{ $errors->first('bank_code') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                                        <h5>@lang("$string_file.onesignal_details")</h5>
                                        <div class="row">
                                            <div class="col-md-4 ">
                                                <div class="form-group">
                                                    <label for="minimum_amount">
                                                        @lang("$string_file.application_key")
                                                    </label>
                                                    <div class="input-group">
                                                        {!! Form::text("application_key",old("application_key",isset($onesignal_config['application_key']) ? $onesignal_config['application_key'] : NULL),["class"=>"form-control", "id"=>"application_key","placeholder"=>'',"required"=>false]) !!}
                                                    </div>
                                                    @if ($errors->has('application_key'))
                                                        <label class="text-danger">{{ $errors->first('application_key') }}</label>
                                                    @endif
                                                </div>
                                            </div>
                                            <!-- <div class="col-md-4 ">
                                                <div class="form-group">
                                                    <label for="minimum_amount">
                                                        @lang("$string_file.rest_key")
                                                    </label>
                                                    <div class="input-group">
                                                        {!! Form::text("rest_key",old("rest_key",isset($arr_account_info['account_holder_name']) ? $arr_account_info['account_holder_name'] : NULL),["class"=>"form-control", "id"=>"rest_key","placeholder"=>'',"required"=>false]) !!}
                                                    </div>
                                                    @if ($errors->has('rest_key'))
                                                        <label class="text-danger">{{ $errors->first('rest_key') }}</label>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-4 ">
                                                <div class="form-group">
                                                    <label for="minimum_amount">
                                                        @lang("$string_file.channel_id")
                                                    </label>
                                                    <div class="input-group">
                                                        {!! Form::text("channel_id",old("channel_id",isset($arr_account_info['account_number']) ? $arr_account_info['account_number'] : NULL),["class"=>"form-control", "id"=>"channel_id","placeholder"=>'',"required"=>false]) !!}
                                                    </div>
                                                    @if ($errors->has('channel_id'))
                                                        <label class="text-danger">{{ $errors->first('channel_id') }}</label>
                                                    @endif
                                                </div>
                                            </div> -->
                                        </div>
                    <br>
                    <div class="form-actions d-flex flex-row-reverse p-2">
                        @if($id == NULL || $edit_permission)
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                            </button>
                        @else
                            <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                        @endif
                    </div>
                    {!!  Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" id="currency" value="{{ isset($data ['laundry_outlet']['commission_method']) ? $data ['laundry_outlet']->CountryArea->Country->isoCode : "" }}"/>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script type="text/javascript">

        // $(document).ready(function(){
        function initialize() {

            var lat = "{{ isset($data['laundry_outlet']['latitude']) ? $data['laundry_outlet']['latitude'] : $default_lat }}";
            var long = "{{ isset($data['laundry_outlet']['longitude']) ? $data['laundry_outlet']['longitude'] : $default_long }}";


            var latlng = new google.maps.LatLng(lat, long);
            var map = new google.maps.Map(document.getElementById('map'), {
                center: latlng,
                zoom: 10
            });
            var marker = new google.maps.Marker({
                map: map,
                position: latlng,
                draggable: true,
                anchorPoint: new google.maps.Point(0, -29)
            });
            var input = document.getElementById('searchInput');
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
            var geocoder = new google.maps.Geocoder();
            var autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', map);
            var infowindow = new google.maps.InfoWindow();
            autocomplete.addListener('place_changed', function () {
                infowindow.close();
                marker.setVisible(false);
                var place = autocomplete.getPlace();
                if (!place.geometry) {
                    window.alert("Autocomplete's returned place contains no geometry");
                    return;
                }

                // If the place has a geometry, then present it on a map.
                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }

                marker.setPosition(place.geometry.location);
                marker.setVisible(true);

                bindDataToForm(place.formatted_address, place.geometry.location.lat(), place.geometry.location.lng());
                infowindow.setContent(place.formatted_address);
                infowindow.open(map, marker);

            });
            // this function will work on marker move event into map
            google.maps.event.addListener(marker, 'dragend', function () {
                geocoder.geocode({'latLng': marker.getPosition()}, function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        if (results[0]) {
                            bindDataToForm(results[0].formatted_address, marker.getPosition().lat(), marker.getPosition().lng());
                            infowindow.setContent(results[0].formatted_address);
                            infowindow.open(map, marker);
                        }
                    }
                });
            });
        }

        function editLatLong(ss) {
            var checkValue = ss.checked ? 1 : 0;
            console.log(checkValue);
            if (checkValue == 1) {
                $('#lat').attr('readonly', false);
                $('#lng').attr('readonly', false);
                $('#location').attr('readonly', false);
            } else {
                $('#lat').attr('readonly', true);
                $('#lng').attr('readonly', true);
                $('#location').attr('readonly', true);
            }
        }

        $(document).on("click", "#edit_password", function () {
            $("#password").prop("disabled", true);
            if ($("#edit_password").prop("checked") == true) {
                $("#password").prop("disabled", false);
            }
        });

        function bindDataToForm(address, lat, lng) {
            document.getElementById('location').value = address;
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
        }

        google.maps.event.addDomListener(window, 'load', initialize);
        // })

        function changeOfferType() {
            var commission_method = $("#commission_method").val();
            if (commission_method == 2) {
                $("#commission_method_symbol").html("%");
            } else if (commission_method == 1) {
                $("#commission_method_symbol").html($("#currency").val());
            }
        }
{{--        function getDriverAgency() {--}}
{{--            var delivery_service = $("#delivery_service").val();--}}
{{--            $("#driver_agency_id").prop("required",false);--}}
{{--            $("#driver_agencies").hide();--}}
{{--            if (delivery_service == 2) {--}}
{{--                $("#driver_agencies").show();--}}
{{--                $("#driver_agency_id").prop("required",true);--}}
{{--            }--}}
{{--        }--}}

        function changeCountry(){
            // var country = $("#country_id option:selected").val();
            // var geocoder = new google.maps.Geocoder();
            // var countryName = $("#country_id option:selected").attr("data-country-name");
            // geocoder.geocode( { 'address': countryName}, function(results, status) {
            //   if (status == google.maps.GeocoderStatus.OK)
            //   {
            //       var lat = results[0].geometry.location.lat();
            //       var long = results[0].geometry.location.lng();
            //     //   console.log(results[0].geometry.location.lat(),results[0].geometry.location.lng(),'lat_long');
            //       var countryLat = $("#country_lat").val(lat);
            //       var countryLong = $("#country_long").val(long);
            //       var latlng = new google.maps.LatLng(lat, long);
            //       var map = new google.maps.Map(document.getElementById('map'), {
            //             center: latlng,
            //             zoom: 19
            //         });
            //         var marker = new google.maps.Marker({
            //             map: map,
            //             position: latlng,
            //             draggable: true,
            //             anchorPoint: new google.maps.Point(0, -29)
            //         });
            //         var input = document.getElementById('searchInput');
            //         console.log(input,'kk');
            //         map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
            //         var autocomplete = new google.maps.places.Autocomplete(input);
            //         autocomplete.bindTo('bounds', map);
            //         var infowindow = new google.maps.InfoWindow();
            //         autocomplete.addListener('place_changed', function () {
            //             infowindow.close();
            //             marker.setVisible(false);
            //             var place = autocomplete.getPlace();
            //             if (!place.geometry) {
            //                 window.alert("Autocomplete's returned place contains no geometry");
            //                 return;
            //             }

            //             // If the place has a geometry, then present it on a map.
            //             if (place.geometry.viewport) {
            //                 map.fitBounds(place.geometry.viewport);
            //             } else {
            //                 map.setCenter(place.geometry.location);
            //                 map.setZoom(17);
            //             }

            //             marker.setPosition(place.geometry.location);
            //             marker.setVisible(true);

            //             bindDataToForm(place.formatted_address, place.geometry.location.lat(), place.geometry.location.lng());
            //             infowindow.setContent(place.formatted_address);
            //             infowindow.open(map, marker);

            //         });
            //         console.log(lat,long,map,marker);
            //   }
            // });
            var currency_code = $("#country_id option:selected").attr("data-currency-code");
            $("#currency").val(currency_code);
            var commission_method = $("#commission_method").val();
            if (commission_method == 1) {
                $("#commission_method_symbol").html(currency_code);
            }
        }
    </script>
@endsection
