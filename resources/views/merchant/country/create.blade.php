@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('country.index') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px"><i
                                            class="wb-reply"></i>
                                </button>
                            </a>
                            @if(!empty($info_setting) && $info_setting->add_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                        data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title">
                        <i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.add_country")
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }}
                        )
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" id="countryForm"
                          enctype="multipart/form-data" action="{{ route('country.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.name")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="name" id="name" class="form-control select2" required>
                                        <option value=""> @lang("$string_file.select")</option>
                                        @foreach($default_country_list as $default_country)
                                            <option value="{{$default_country['Country_Name']}}"
                                                    data-isocode="{{$default_country['Dial']}}"
                                                    data-currency="{{$default_country['ISO4217_Currency_Alphabetic_Code']}}"
                                                    data-countrycode="{{$default_country['ISO3166_1_Alpha_2']}}"
                                                    data-currency_symbol="{{$default_country['currency_symbol']}}"
                                                    data-distance_unit="{{$default_country['distance_unit']}}"
                                                    data-phone_min_digit="{{$default_country['phone_min_digit']}}"
                                                    data-phone_max_digit="{{$default_country['phone_max_digit']}}"
                                                    data-online_transaction_code="{{$default_country['online_transaction_code']}}"
                                                    data-display_sequence="{{$default_country['display_sequence']}}"
                                            >{{$default_country['Country_Name']}}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('name'))
                                        <label class="text-danger">{{ $errors->first('name') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="profile_image">
                                    @lang("$string_file.isd_code")
                                    <span class="text-danger">*</span><i
                                            class="fa fa-info-circle"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            title=""></i>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">+</span>
                                    </div>
                                    <input type="number" name="phonecode" id="phonecode"
                                           class="form-control" required
                                           value="{{ old('phonecode') }}" min=0 maxlength=6
                                           placeholder=""
                                           aria-describedby="basic-addon1">
                                </div>
                                @if ($errors->has('phonecode'))
                                    <label class="text-danger">{{ $errors->first('phonecode') }}</label>
                                @endif
                            </div>
{{--                            <div class="col-md-4">--}}
{{--                                <div class="form-group">--}}
{{--                                    <label for="uniCode">--}}
{{--                                        @lang("$string_file.currency")--}}
{{--                                        <span class="text-danger">*</span>--}}
{{--                                    </label>--}}
{{--                                    <input type="text" class="form-control" id="currency"--}}
{{--                                           name="currency" required--}}
{{--                                           value="{{ old('currency') }}"--}}
{{--                                           placeholder="">--}}
{{--                                    @if ($errors->has('currency'))--}}
{{--                                        <label class="text-danger">{{ $errors->first('currency') }}</label>--}}
{{--                                    @endif--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="row">--}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.iso_code_detail")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="isocode" readonly
                                           name="isocode"
                                           value="{{ old('isocode') }}"
                                           placeholder="" required>
                                    @if ($errors->has('isocode'))
                                        <label class="text-danger">{{ $errors->first('isocode') }}</label>
                                    @endif
                                    <label class="text-danger">Eg:ISO code of $ is USD</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.country_code")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="country_code" readonly
                                           name="country_code"
                                           value="{{ old('country_code') }}"
                                           placeholder="" required>
                                    @if ($errors->has('country_code'))
                                        <label class="text-danger">{{ $errors->first('country_code') }}</label>
                                    @endif
                                    <label class="text-danger">Eg:Country code of India is IN</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.distance_unit") </label>
                                    {!! Form::select("distance_unit", $distance_units, old("distance_unit"), array("class"=>"c-select form-control", "id"=>"distance_unit","required")) !!}
                                    {{--<select class="c-select form-control" id="distance_unit"--}}
                                            {{--name="distance_unit" required>--}}
                                        {{--<option value=""> @lang("$string_file.select")</option>--}}
                                        {{--<option value="1"> @lang("$string_file.km")</option>--}}
                                        {{--<option value="2"> @lang("$string_file.miles")</option>--}}
                                    {{--</select>--}}
                                    @if ($errors->has('distance_unit'))
                                        <label class="text-danger">{{ $errors->first('distance_unit') }}</label>
                                    @endif
                                </div>
                            </div>
{{--                        </div>--}}
{{--                        <div class="row">--}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="profile_image">
                                        @lang("$string_file.min_digits")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="min_digits"
                                           name="minNumPhone" required 
                                           value="{{ old('minNumPhone') }}"
                                           placeholder=""
                                           min=1 max=25>
                                    @if ($errors->has('minNumPhone'))
                                        <label class="text-danger">{{ $errors->first('minNumPhone') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_digits">
                                        @lang("$string_file.max_digits")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="max_digits"
                                           name="maxNumPhone" required
                                           value="{{ old('maxNumPhone') }}"
                                           placeholder="" min=1 max=25>
                                    @if ($errors->has('maxNumPhone'))
                                        <label class="text-danger">{{ $errors->first('maxNumPhone') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="online_transaction">
                                        @lang("$string_file.online_transaction_code")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="online_transaction"
                                           name="online_transaction" required
                                           value="{{ old('online_transaction') }}"
                                           placeholder="">
                                    @if ($errors->has('online_transaction'))
                                        <label class="text-danger">{{ $errors->first('online_transaction') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sequance">
                                        @lang("$string_file.sequence")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="sequance"
                                           name="sequance" required
                                           value="{{ old('sequance') }}"
                                           placeholder="" min=0 maxlength=10>
                                    @if ($errors->has('sequance'))
                                        <label class="text-danger">{{ $errors->first('sequance') }}</label>
                                    @endif
                                </div>
                            </div>
                            @if(isset($configurations->sub_area_code_enable) && ($configurations->sub_area_code_enable == 1 || $configurations->sub_area_code_enable == 3))
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sequance">
                                            @lang("$string_file.sub_area_codes") (Comma Separated)
                                        </label>
                                        <input type="text" class="form-control" id="sub_area_codes"
                                               name="sub_area_codes"
                                               value="{{ old('sub_area_codes') }}"
                                               placeholder="">
                                        @if ($errors->has('sub_area_codes'))
                                            <label class="text-danger">{{ $errors->first('sub_area_codes') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                        @if($applicationConfig->tip_status == 1)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.user_tip_short_values")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control"
                                               id="tip_short_amount" min="0" max="9999999"
                                               name="tip_short_amount[]"
                                               placeholder="@lang("$string_file.enter_amount")"
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
                                        <input type="number" class="form-control"
                                               id="tip_short_amount" min="0" max="9999999"
                                               name="tip_short_amount[]"
                                               placeholder="@lang("$string_file.enter_value") 2"
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
                                        <input type="number" class="form-control"
                                               id="tip_short_amount" min="0" max="9999999"
                                               name="tip_short_amount[]"
                                               placeholder="@lang("$string_file.enter_value") 3"
                                               required>
                                        @if ($errors->has('tip_short_amount'))
                                            <label class="danger">{{ $errors->first('tip_short_amount') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if ($applicationConfig->user_document == 1)
                            <h5><i class="wb-book"></i> @lang("$string_file.user") @lang("$string_file.document_configuration")</h5>
                            <hr>
                            <div class="row">
                                 @if ($applicationConfig->local_citizen_foreigner_documents == 1)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="local_citizen_documents">
                                            @lang("$string_file.local_citizen_documents")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="select2 form-control" name="local_citizen_documents[]"
                                                id="local_citizen_documents"
                                                data-placeholder="@lang("$string_file.select_document")"
                                                multiple="multiple">
                                            @foreach($documents as $document)
                                                <option
                                                        value="{{ $document->id }}"
                                                >
                                                    {{ $document->DocumentName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('local_citizen_documents'))
                                            <label class="text-danger">{{ $errors->first('local_citizen_documents') }}</label>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="foreigner_documents">
                                            @lang("$string_file.foreigner_documents")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="select2 form-control" name="foreigner_documents[]"
                                                id="foreigner_documents"
                                                data-placeholder="@lang("$string_file.select_document")"
                                                multiple="multiple">
                                            @foreach($documents as $document)
                                                <option value="{{ $document->id }}"
                                                >
                                                    {{ $document->DocumentName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('foreigner_documents'))
                                            <label class="text-danger">{{ $errors->first('foreigner_documents') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="Documents">
                                            @lang("$string_file.document_for_user")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="select2 form-control" name="document[]"
                                                id="document"
                                                data-placeholder="@lang("$string_file.select_document")"
                                                multiple="multiple">
                                            @foreach($documents as $document)
                                                <option   value="{{ $document->id }}"
                                                >
                                                    {{ $document->DocumentName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('document'))
                                            <label class="text-danger">{{ $errors->first('document') }}</label>
                                        @endif
                                    </div>
                                </div>

                            @endif
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="Documents">
                                            @lang("$string_file.auto_verify")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" name="document_auto_verify">
                                            <option value="">@lang("$string_file.select")</option>
                                            <option value="1">@lang("$string_file.enable")</option>
                                            <option value="2">@lang("$string_file.disable")</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($carpooling_enable)
                            {{--@if ($configurations->countrywise_payment_gateway == 1)--}}
                                {{--<h5><i class="wb-cash"></i>@lang('common.payment')  @lang('common.gateway') @lang('common.configuration')</h5><hr>--}}
                                {{--<div class="row">--}}
                                    {{--<div class="col-md-4">--}}
                                        {{--<div class="form-group">--}}
                                            {{--<label for="Documents">--}}
                                                {{--@lang("$string_file.carpooling")    @lang('common.cashin') @lang('common.configuration')--}}
                                                {{--<span class="text-danger">*</span>--}}
                                            {{--</label>--}}
                                            {{--<select class="select2 form-control"--}}
                                                    {{--name="payment_option[]"--}}
                                                    {{--id="payment_option"--}}
                                                    {{--data-placeholder="@lang('common.select') @lang('common.payment') @lang('common.gateway')"--}}
                                                    {{--multiple="multiple">--}}
                                                {{--@foreach($payment_options as $payment_option)--}}
                                                    {{--<option value="{{ $payment_option->id }}">{{ $payment_option->payment_gateway_provider }}</option>--}}
                                                {{--@endforeach--}}
                                            {{--</select>--}}
                                            {{--@if ($errors->has('payment_option'))--}}
                                                {{--<label class="text-danger">{{ $errors->first('payment_option') }}</label>--}}
                                            {{--@endif--}}
                                        {{--</div>--}}
                                    {{--</div>--}}

                                    {{--<div class="form-group">--}}
                                        {{--<label for="Documents">--}}
                                            {{--@lang("$string_file.carpooling")    @lang('common.cashout') @lang('common.configuration')--}}
                                            {{--<span class="text-danger">*</span>--}}
                                        {{--</label>--}}
                                        {{--<label for="manual">--}}
                                            {{--<input type="checkbox" id="manual_cashout" name="manual_cashout" value="1" >--}}
                                            {{--Bank Transfer</label><br>--}}
                                        {{--<select class="select2 form-control"--}}
                                                {{--name="payin_option"--}}
                                                {{--id="payin_option"--}}
                                                {{--data-placeholder=" @lang('common.select') @lang('common.payment') @lang('common.method')"--}}
                                                {{--multiple="multiple" >--}}
                                            {{--@foreach($payment_options as $payment_option)--}}

                                                {{--<option value="{{ $payment_option->id }}">{{ $payment_option->payment_gateway_provider }}</option>--}}
                                            {{--@endforeach--}}
                                        {{--</select>--}}
                                        {{--@if ($errors->has('payin_option'))--}}
                                            {{--<label class="text-danger">{{ $errors->first('payin_option') }}</label>--}}
                                        {{--@endif--}}
                                        {{--<br>--}}
                                        {{--<div class="from-group">--}}
                                            {{--<input type="checkbox" id="automatic_cashin" name="automatic_cashin" value="1">--}}
                                            {{--<label>Please register with number that have a wallet activate on it</label>--}}
                                        {{--</div>--}}

                                    {{--</div>--}}
                                {{--</div>--}}
                            {{--@endif--}}
                            <h5><i class="wb-cash"></i> @lang('common.commission') @lang('common.configuration')</h5><hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wallet_to_bank'">
                                            @lang('common.wallet_to_bank')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="wallet_to_bank"
                                               name="wallet_to_bank"
                                               value=""
                                               placeholder="">
                                        @if ($errors->has('wallet_to_bank'))
                                            <label class="text-danger">{{ $errors->first('wallet_to_bank') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="bank_to_wallet">
                                            @lang('common.bank_to_wallet')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="bank_to_wallet"
                                               name="bank_to_wallet"
                                               value=""
                                               placeholder="">
                                        @if ($errors->has('bank_to_wallet'))
                                            <label class="text-danger">{{ $errors->first('bank_to_wallet') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <h5><i class="wb-cash"></i>@lang("$string_file.carpooling")   @lang('common.amount') @lang('common.configuration')</h5><hr>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="minimum_amount">
                                            @lang('common.minimum_payin')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="minimum_payin"
                                               name="minimum_payin"
                                               value=""
                                               placeholder="">
                                        @if ($errors->has('minimum_payin'))
                                            <label class="text-danger">{{ $errors->first('minimum_payin') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="maximum_amount">
                                            @lang('common.maximum_payin')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="maximum_payin"
                                               name="maximum_payin"
                                               value=""
                                               placeholder="">
                                        @if ($errors->has('maximum_payin'))
                                            <label class="text-danger">{{ $errors->first('maximum_payin') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="minimum_amount">
                                            @lang('common.minimum_payout')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="minimum_payout"
                                               name="minimum_payout"
                                               value=""
                                               placeholder="">
                                        @if ($errors->has('minimum_payout'))
                                            <label class="text-danger">{{ $errors->first('minimum_payout') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="maximum_amount">
                                            @lang('common.maximum_payout')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="maximum_payout"
                                               name="maximum_payout"
                                               value=""
                                               placeholder="">
                                        @if ($errors->has('maximum_payout'))
                                            <label class="text-danger">{{ $errors->first('maximum_payout') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if (isset($configurations->country_wise_payment_gateway) && $configurations->country_wise_payment_gateway == 1)
                            <h5><i class="wb-book"></i> @lang("$string_file.country_wise_payment_gateway")</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="Documents">
                                            @lang("$string_file.payment_gateway")
                                        </label>
                                        <select class="select form-control"
                                                name="country_wise_payment_gateway"
                                                id="country_wise_payment_gateway">
                                            <option value="">@lang("$string_file.all")</option>
                                            @foreach($country_wise_payment_gateway_list as $key => $item)
                                                <option value="{{ $key }}">{{ $item }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('country_wise_payment_gateway'))
                                            <label class="text-danger">{{ $errors->first('country_wise_payment_gateway') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if (isset($configurations->driver_address) && $configurations->driver_address == 1)
                            <h5><i class="wb-book"></i> @lang("$string_file.driver_address_fields")</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="driver_address_fields">
                                            @lang("$string_file.driver_address_fields")
                                        </label>
                                        {!! Form::select("driver_address_fields[]",$driver_address_fields,old('driver_address_fields'), array("class" => "select2 form-control", "id" => "driver_address_fields[]", "multiple" =>true)) !!}
                                        @if ($errors->has('driver_address_fields'))
                                            <label class="text-danger">{{ $errors->first('driver_address_fields') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script>
        $(document).ready(
            function () {
                $("input[name=additional_details]").each(function () {
                    // console.log($(this).attr('value'));
                    if ($(this).is(':checked')) {
                        if ($(this).attr('value') == 1) {
                            $('#parameter_name').attr('required', true);
                            $('#parameter_name').attr('disabled', false);
                            $('#parameter_name').parent().parent().removeClass('hide');
                            $('#placeholder').attr('required', true);
                            $('#placeholder').attr('disabled', false);
                            $('#placeholder').parent().parent().removeClass('hide');

                        }
                        //console.log("IN IF: "+$(this).attr('id')+' '+$(this).attr('value'));
                        // $(this).removeAttr('required');
                    }
                });
            });

        function extraparameters(data) {
            //console.log(data);
            if (data == 1) {
                $('#parameter_name').attr('required', true);
                $('#parameter_name').attr('disabled', false);
                $('#parameter_name').parent().parent().removeClass('hide');
                $('#placeholder').attr('required', true);
                $('#placeholder').attr('disabled', false);
                $('#placeholder').parent().parent().removeClass('hide');

            } else {
                $('#parameter_name').attr('required', false);
                $('#parameter_name').attr('disabled', true);
                $('#parameter_name').parent().parent().addClass('hide');
                $('#placeholder').attr('required', false);
                $('#placeholder').attr('disabled', true);
                $('#placeholder').parent().parent().addClass('hide');
            }

        }

        $(document).ready(function () {
            $('form#countryForm').submit(function () {
                $(this).find(':input[type=submit]').prop('disabled', true);
            });
            $(document).on('change', '#name', function () {
                var isocode = $('option:selected', this).attr('data-isocode');
                var currency = $('option:selected', this).attr('data-currency');
                var countrycode = $('option:selected', this).attr('data-countrycode');
                var currency_symbol = $('option:selected', this).attr('data-currency_symbol');
                var distance_unit = $('option:selected', this).attr('data-distance_unit');
                var phone_min_digit = $('option:selected', this).attr('data-phone_min_digit');
                var phone_max_digit = $('option:selected', this).attr('data-phone_max_digit');
                var online_transaction_code = $('option:selected', this).attr('data-online_transaction_code');
                var display_sequence = $('option:selected', this).attr('data-display_sequence');

                $('#phonecode').val(isocode);
                $('#isocode').val(currency);
                $('#country_code').val(countrycode);

                $('#currency').val(currency_symbol);
                $('#distance_unit').val(distance_unit);
                $('#min_digits').val(phone_min_digit);
                $('#max_digits').val(phone_max_digit);
                $('#online_transaction').val(online_transaction_code);
                $('#sequance').val(display_sequence);
            });
        });
         $(function () {
            $("#manual_cashout").click(function () {
                if ($(this).is(":checked")) {
                    $("#payin_option").attr("disabled", "disabled");
                    $("#automatic_cashin").attr("disabled", "disabled");


                }else{
                    $("#payin_option").removeAttr("disabled");
                    $("#payin_option").focus();
                    $("#automatic_cashin").removeAttr("disabled");
                    $("#automatic_cashin").focus();
                }
            });
         });

    </script>
@endsection
