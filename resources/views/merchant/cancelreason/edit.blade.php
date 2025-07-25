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
                @php $id = !empty($cancelreason->id) ? $cancelreason->id : NULL; @endphp
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          name="cancel-reason-edit-form" id="cancel-reason-edit-form"
                          action="{{route('cancelreason.update', $cancelreason->id)}}">
                        {{method_field('PUT')}}
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <label>@lang("$string_file.segment")  <span class="text-danger">*</span></label>
                                <div class="form-group">
                                    <select class="form-control" name="segment_id" id="segment_id"
                                            required>
                                        <option value="">@lang("$string_file.select") </option>
                                        @foreach($merchant_segments  as $key => $value)
                                            <option value="{{$key}}" @if($cancelreason->segment_id == $key) selected @endif>{{$value}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label>@lang("$string_file.also_save_for_other") @lang("$string_file.segment") <span class="text-danger">*</span></label>
                                <div class="row">
                                    @foreach($other_cancel_reason_related_segments as $other_segment)
                                        <div class="col-md-2">
                                            <label>{{$other_segment['name']}}</label>
                                            <input type="checkbox" name="other_segments[{{$other_segment['id']}}]" id="other_segments_{{ $other_segment['id'] }}" class="form-check-field">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.reason")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="">@if($cancelreason->LanguageSingle){{$cancelreason->LanguageSingle->reason}}@endif</textarea>
                                    @if ($errors->has('reason'))
                                        <label class="text-danger">{{ $errors->first('reason') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            @if($id == NULL || $edit_permission)
                                <button type="submit" class="btn btn-primary" id="submitButton">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.update")
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
            $('#cancel-reason-edit-form').submit(function() {
                // Disable the submit button to prevent multiple clicks
                $('#submitButton').prop('disabled', true);

                // You can also display a loading message or spinner
                $('#submitButton').html('Submitting...');

                // After a delay or when the form submission is complete, enable the button again
                setTimeout(function() { $('#submitButton').prop('disabled', false); }, 5000);

                // If you want to cancel the form submission completely, return false here
                // Example: return false;
            });
        });
    </script>
@endsection
