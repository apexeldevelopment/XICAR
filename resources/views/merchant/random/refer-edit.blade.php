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
                                <strong>@lang('admin.message421')</strong>
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
                                        <h3 class="content-header-title mb-0 d-inline-block"><i class="fas fa-user-plus"></i> @lang('admin.message318')</h3>
                                        <div class="btn-group float-md-right">
                                            <a href="{{ route('merchant.refer.index') }}">
                                                <button type="button" class="btn btn-icon btn-success mr-1"><i class="fa fa-reply"></i>
                                                </button>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-content collapse show">
                                        <div class="card-body">
                                            <form method="POST" enctype="multipart/form-data"
                                                  class="steps-validation wizard-notification"
                                                  enctype="multipart/form-data" action="{{ route('merchant.refer.update',$refer->id) }}">
                                                @csrf
                                                <fieldset>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location3">@lang('admin.message319')</label>
                                                                <select class="form-control required" name="sender_discount" id="sender_discount"
                                                                        required>
                                                                    <option value="1"
                                                                            @if($refer->sender_discount == 1) selected @endif>@lang("common.yes")</option>
                                                                    <option value="2"
                                                                            @if($refer->sender_discount == 2) selected @endif>@lang("common.no")</option>
                                                                </select>
                                                                @if ($errors->has('sender_discount'))
                                                                    <label class="text-danger">{{ $errors->first('sender_discount') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location3">@lang('admin.message320')</label>
                                                                <select class="form-control required" name="receiver_discount" id="receiver_discount"
                                                                        required>
                                                                    <option value="1"
                                                                            @if($refer->receiver_discount == 1) selected @endif>@lang("common.yes")</option>
                                                                    <option value="2"
                                                                            @if($refer->receiver_discount == 2) selected @endif>@lang("common.no")</option>
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
                                                                <label for="emailAddress5">
                                                                    @lang("common.start")  @lang("common.date") <span class="text-danger">*</span>
                                                                </label>
                                                                <input type="text" class="form-control datepicker bg-this-color"
                                                                       id="datepicker" value="{{ $refer->start_date }}" name="start_date" readonly
                                                                       placeholder="@lang('admin.message655')">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="emailAddress5">
                                                                    @lang("common.end")  @lang("common.date")<span class="text-danger">*</span>
                                                                </label>
                                                                <input type="text" class="form-control datepicker bg-this-color"
                                                                       id="datepicker" value="{{ $refer->end_date }}" name="end_date"
                                                                       placeholder="@lang('admin.message656')" readonly>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location3">@lang("common.offer") @lang("common.type") </label>
                                                                <select class="form-control required" name="offer_type" id="offer_type"
                                                                        required>
                                                                    <option value="1"
                                                                            @if($refer->offer_type == 1) selected @endif>@lang("common.free") @lang("$string_file.ride")</option>
                                                                    <option value="2"
                                                                            @if($refer->offer_type == 2) selected @endif>@lang("common.discount")</option>
                                                                    <option value="3"
                                                                            @if($refer->offer_type == 3) selected @endif>@lang("common.fixed") @lang("common.amount")</option>
                                                                </select>
                                                                @if ($errors->has('offer_type'))
                                                                    <label class="text-danger">{{ $errors->first('offer_type') }}</label>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location">
                                                                    @lang("common.offer") @lang("common.value") <span class="text-danger">*</span>
                                                                </label>
                                                                <input type="text" class="form-control"
                                                                       id="offer_value"
                                                                       name="offer_value"
                                                                       placeholder="@lang('admin.message657')" value="{{ $refer->offer_value }}" required>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="location3">@lang("common.offer") @lang("common.type") </label>
                                                                <select class="form-control required" name="status" id="status"
                                                                        required>
                                                                    <option value="1"
                                                                            @if($refer->status == 1) selected @endif>@lang("common.active")</option>
                                                                    <option value="2"
                                                                            @if($refer->status == 2) selected @endif>@lang("common.inactive")</option>
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