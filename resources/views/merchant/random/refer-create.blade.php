@extends('merchant.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="app-content content">
            <div class="content-wrapper">

                <div class="content-header row">
                    <div class="col-md-6 col-12">
                        @if(session('refer'))
                            <div class="col-md-6 alert alert-icon-right alert-info alert-dismissible mb-2" role="alert">
                                <span class="alert-icon"><i class="fa fa-info"></i></span>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                                <strong>@lang('admin.rideradded')</strong>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="content-body">
                    <section id="validation">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="content-header-title mb-0 d-inline-block"><i
                                                    class="fas fa-user-plus"></i> @lang('admin.message318')</h3>
                                        <div class="btn-group float-md-right">
                                            <a href="{{ route('merchant.refer.index') }}">
                                                <button type="button" class="btn btn-icon btn-success mr-1"><i
                                                            class="fa fa-reply"></i>
                                                </button>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-content collapse show">
                                        <div class="card-body">
                                            <form method="POST" enctype="multipart/form-data"
                                                  class="steps-validation wizard-notification"
                                                  enctype="multipart/form-data"
                                                  action="{{ route('merchant.refer.store') }}">
                                                @csrf
                                                <fieldset>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location3">@lang("common.service") @lang("common.area")</label>
                                                                <select class="form-control" name="country_id"
                                                                        id="country_id"
                                                                        required>
                                                                    <option value="">@lang("common.select")</option>
                                                                    @foreach($countries  as $country)
                                                                        <option value="{{ $country->id }}">{{  $country->CountryName }}</option>
                                                                    @endforeach
                                                                </select>
                                                                @if ($errors->has('country_id'))
                                                                    <label class="text-danger">{{ $errors->first('country_id') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location3">@lang('admin.message319')</label>
                                                                <select class="form-control required"
                                                                        name="sender_discount"
                                                                        id="sender_discount"
                                                                        required>
                                                                    <option value="1">@lang("common.yes")</option>
                                                                    <option value="2">@lang("common.no")</option>
                                                                </select>
                                                                @if ($errors->has('sender_discount'))
                                                                    <label class="text-danger">{{ $errors->first('sender_discount') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>


                                                    </div>


                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location3">@lang('admin.message320')</label>
                                                                <select class="form-control required"
                                                                        name="receiver_discount" id="receiver_discount"
                                                                        required>
                                                                    <option value="1">@lang("common.yes")</option>
                                                                    <option value="2">@lang("common.no")</option>
                                                                </select>
                                                                @if ($errors->has('sender_discount'))
                                                                    <label class="text-danger">{{ $errors->first('sender_discount') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="emailAddress5">
                                                                    @lang("common.start")  @lang("common.date") <span
                                                                            class="text-danger">*</span>
                                                                </label>
                                                                <input type="text"
                                                                       class="form-control datepicker bg-this-color"
                                                                       id="datepicker" name="start_date" readonly
                                                                       placeholder="@lang('admin.message655')">
                                                                @if ($errors->has('start_date'))
                                                                    <label class="text-danger">{{ $errors->first('start_date') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>


                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="emailAddress5">
                                                                    @lang("common.end")  @lang("common.date")<span
                                                                            class="text-danger">*</span>
                                                                </label>
                                                                <input type="text"
                                                                       class="form-control datepicker bg-this-color"
                                                                       id="datepicker" name="end_date" readonly
                                                                       placeholder="@lang("common.end")  @lang("common.date")">
                                                                @if ($errors->has('end_date'))
                                                                    <label class="text-danger">{{ $errors->first('message656') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location3">@lang("common.offer") @lang("common.type") </label>
                                                                <select class="form-control required" name="offer_type"
                                                                        id="offer_type"
                                                                        required>
                                                                    <option value="1">@lang("common.free") @lang("$string_file.ride")</option>
                                                                    <option value="2">@lang("common.discount")</option>
                                                                    <option value="3">@lang("common.fixed") @lang("common.amount")</option>
                                                                </select>
                                                                @if ($errors->has('offer_type'))
                                                                    <label class="text-danger">{{ $errors->first('offer_type') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>


                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location">
                                                                    @lang("common.offer") @lang("common.value") <span
                                                                            class="text-danger">*</span>
                                                                </label>
                                                                <input type="text" class="form-control"
                                                                       id="offer_value"
                                                                       name="offer_value"
                                                                       placeholder="@lang("common.offer") @lang("common.value") " required>
                                                                @if ($errors->has('offer_value'))
                                                                    <label class="text-danger">{{ $errors->first('offer_value') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location3">@lang("common.offer") @lang("common.type") </label>
                                                                <select class="form-control required" name="status"
                                                                        id="status"
                                                                        required>
                                                                    <option value="1">@lang("common.active")</option>
                                                                    <option value="2">@lang("common.inactive")</option>
                                                                </select>
                                                                @if ($errors->has('status'))
                                                                    <label class="text-danger">{{ $errors->first('status') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                </fieldset>
                                                <div class="form-actions right" style="margin-bottom: 3%">
                                                    <button type="submit" class="btn btn-primary float-right">
                                                        <i class="fa fa-check-circle"></i> Save
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>
            </div>
        </div>
    </div>

@endsection
