@extends('merchant.layouts.main')
@section('content')
    <style>
        .impo-text {
            color: red;
            font-size: 15px;
            text-wrap: normal;
            display: none;
        }
    </style>
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" style="margin:10px">
                            @if(!empty($info_setting) && $info_setting->add_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin-left:10px" data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-add-file" aria-hidden="true"></i>
                        @lang("$string_file.bus_booking") @lang("$string_file.configuration")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=> route("merchant.bus_booking.configuration"),'class'=>'steps-validation wizard-notification']) !!}
                    <div class="row">
                        <div class="col-md-4">
                            <label>@lang("$string_file.booking_days_before") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::number('booking_days_before',old('booking_days_before',isset($bus_booking_config->booking_days_before) ? $bus_booking_config->booking_days_before:""),['id'=>'booking_days_before','class'=>'form-control','required'=>true, 'step'=>1, 'max'=>100]) !!}
                                @if ($errors->has('booking_days_before'))
                                    <label class="text-danger">{{ $errors->first('booking_days_before') }}</label>
                                @endif
                            </div>

                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.cash") @lang("$string_file.payment") @lang("$string_file.enable"): <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::select('cash_payment_enable', [1 => 'Enabled', 2 => 'Disabled'], old('cash_payment_enable', isset($bus_booking_config->cash_payment_enable) ? $bus_booking_config->cash_payment_enable : ""), ['id' => 'cash_payment_enable', 'class' => 'form-control', 'required' => true]) !!}
                                @if ($errors->has('booking_days_before'))
                                    <label class="text-danger">{{ $errors->first('booking_days_before') }}</label>
                                @endif
                            </div>

                        </div>
                    </div>
                    <div class="form-actions float-right">
                        @if($edit_permission)
                            {!! Form::submit(trans("$string_file.save"),['class'=>'btn btn-primary','id'=>'']) !!}
                        @else
                            <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                        @endif
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection
