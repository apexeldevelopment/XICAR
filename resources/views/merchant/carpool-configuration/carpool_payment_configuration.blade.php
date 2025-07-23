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
                        @lang("carpooling.carpooling")   @lang("common.payment") @lang("common.configuration")
                    </h1>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              action="{{route('merchant.carpooling.payment_configuration.save')}}">
                            @csrf
                            <fieldset>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize"> @lang("common.hold") @lang("common.money") @lang("common.before") @lang("$string_file.ride") @lang("common.start")</label>
                                            <input name="payment_duration_time" class="form-control" value="{{$carpool_payment_config ? $carpool_payment_config->hold_money_before_ride_start : ''}}"
                                                   placeholder="@lang('common.enter') @lang('common.time') @lang('common.in') @lang('common.hour')">
                                            @if($errors->first('payment_duration_time'))
                                                <span class="text-danger">{{$errors->first('payment_duration_time')}}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize"> @lang("common.transfer") @lang("common.money") @lang("common.to")  @lang("common.user")</label>
                                            <input name="transfer_money_to_user" class="form-control" value="{{$carpool_payment_config ? $carpool_payment_config->transfer_money_to_user : ''}}"
                                                   placeholder="@lang('common.enter') @lang('common.time') @lang('common.in') @lang('common.hour')">
                                            @if($errors->first('transfer_money_to_user'))
                                                <span class="text-danger">{{$errors->first('transfer_money_to_user')}}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="form-actions right" style="margin-bottom: 3%">
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-circle"></i> @lang("common.save")
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
