@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('cancelreason.index') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px"><i class="wb-reply"></i>
                                </button>
                            </a>
                            @if(!empty($info_setting) && $info_setting->edit_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                        data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title">@lang("$string_file.cancel_reason") (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          name="cancel-reason-form" id="cancel-reason-form"
                          action="{{route('cancelreason.store')}}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <label>@lang("$string_file.reason_for")  <span class="text-danger">*</span></label>
                                <div class="form-group">
                                    <select class="form-control select2" name="reason_for" id="reason_for"
                                            required>
                                        <option value="">--@lang("$string_file.select") --</option>
                                        <option value="1">@lang("$string_file.user") </option>
                                        <option value="2">@lang("$string_file.driver")</option>
                                        <option value="3">@lang("$string_file.dispatcher") </option>
                                        {{--                                will change condition later --}}
                                        @if(in_array(3,$merchant_segments) || in_array(4,$merchant_segments))
                                            <option value="4">@lang("$string_file.business_segment") </option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label>@lang("$string_file.segment")  <span class="text-danger">*</span></label>
                                <div class="form-group">
                                    <select class="form-control select2" multiple name="segment_id[]" id="segment_id"
                                            required>
                                        <option value="">@lang("$string_file.select") </option>
                                        @foreach($merchant_segments  as $key => $value)
                                            <option value="{{$key}}">{{$value}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label>@lang("$string_file.select") @lang("$string_file.all") @lang("$string_file.segment") <span class="text-danger">*</span></label>
                                <div class="form-group">
                                    <input type="checkbox" class="form-check-label" id="select_all" style="width: 20px; height:20px;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label>@lang("$string_file.reason_type_for")  <span class="text-danger">*</span></label>
                                <div class="form-group">
                                    <select class="form-control select2" name="reason_type_for" id="reason_type_for"
                                            required>
                                        <option value="">--@lang("$string_file.select") --</option>
                                        <option value="1">@lang("$string_file.cancel_ride_reason")</option>
                                        <option value="2">@lang("$string_file.account_delete") </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.reason")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" placeholder=""></textarea>
                                    @if ($errors->has('reason'))
                                        <label class="text-danger">{{ $errors->first('reason') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            @if($edit_permission)
                                <button type="submit" class="btn btn-primary" id="submitButton">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.add")
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'edit_text'])
@endsection
@section("js")
    <script>
        $(document).ready(function() {
            $('#cancel-reason-form').submit(function() {
                // Disable the submit button to prevent multiple clicks
                $('#submitButton').prop('disabled', true);

                // You can also display a loading message or spinner
                $('#submitButton').html('Submitting...');

                // After a delay or when the form submission is complete, enable the button again
                setTimeout(function() { $('#submitButton').prop('disabled', false); }, 5000);

                // If you want to cancel the form submission completely, return false here
                // Example: return false;
            });

            $("#select_all").click(function(){
                if($("#select_all").is(':checked') ){
                    $('#segment_id').select2('destroy').find('option').prop('selected', 'selected').end().select2();
                }else{
                    $('#segment_id').select2('destroy').find('option').prop('selected', false).end().select2();
                }
            });
        });
    </script>
@endsection
