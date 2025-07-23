@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions"></div>
                    <h1 class="panel-title">
                        <i class="icon fa-gears" aria-hidden="true"></i>
                        @lang("common.intouch") @lang("common.configuration")
                    </h1>
                </header>

                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              action="{{route('merchant.gateway.intouch.update',$intouch_operator->id)}}" >
                                @method('PUT')
                                @csrf
                            <div class="row">
                                <div class="col-md-3"></div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("$string_file.carpooling") @lang("common.country")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="country_id"
                                                name="country_id" required>
                                           
                                           
                                                <option id="country_id"
                                                        value="{{$country->id}}">{{$country->CountryName}}</option>
                                          
                                        </select>
                                        @if ($errors->has('country_id'))
                                            <label class="text-danger">{{ $errors->first('country_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3"></div>
                            </div>

                            <h3 class="panel-title">@lang("common.other") @lang("common.configuration")</h3>
                            <div class="row">
                                 <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("common.partner") @lang("common.id") 

                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="partner_id"
                                               name="partner_id"
                                               value="{{$intouch_operator->partner_id}}"
                                               placeholder="@lang("common.enter")   @lang("common.partner") @lang("common.id") ">
                                        @if ($errors->has('partner_id'))
                                            <label class="danger">{{ $errors->first('partner_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("common.operator")  @lang("common.name")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="select2 form-control"
                                                    name="operator_name[]"
                                                    id="operator_name"
                                                    data-placeholder=" @lang("common.enter") @lang("common.operator")  @lang("common.name")"
                                                    multiple="multiple">
                                           
                                                   @foreach($payment_options as $payment_option)
                                                
                                                <option
                                                        @if(in_array($payment_option->id, array_pluck($country->operator,'id'))) selected @endif
                                                value="{{ $payment_option->id }}">
                                                   {{ $payment_option->operator }}
                                                </option>
                                                 
                                                @endforeach
                                            </select>
                                       
                                        @if ($errors->has('operator_name'))
                                            <label class="danger">{{ $errors->first('operator_name') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("common.login") @lang("common.api")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="login_api"
                                               name="login_api"
                                               value="{{$intouch_operator->login_api}}"
                                               placeholder="@lang("common.enter") @lang("common.login") @lang("common.api")">
                                        @if ($errors->has('login_api'))
                                            <label class="danger">{{ $errors->first('login_api') }}</label>
                                        @endif
                                    </div>
                                </div>
                          
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("common.password") @lang("common.api") 

                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="password_api"
                                               name="password_api"
                                               value="{{$intouch_operator->password_api}}"
                                               placeholder="@lang("common.enter") @lang("common.password") @lang("common.api")">
                                        @if ($errors->has('password_api'))
                                            <label class="danger">{{ $errors->first('password_api') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("common.agency") @lang("common.code") 

                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="agency_code"
                                               name="agency_code"
                                               value="{{$intouch_operator->agency_code}}"
                                               placeholder="@lang("common.enter") @lang("common.agency") @lang("common.code")">
                                        @if ($errors->has('agency_code'))
                                            <label class="danger">{{ $errors->first('agency_code') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions d-flex flex-row-reverse p-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check-circle"></i> @lang('common.update')
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>

@endsection
