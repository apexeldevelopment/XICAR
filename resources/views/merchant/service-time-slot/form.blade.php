@extends('merchant.layouts.main')
@section('content')
@php $id = NULL; @endphp
@if(isset($data['service_time_slot']['id']))
@php $id = $data['service_time_slot']['id']; @endphp
@endif
<div class="page">
    <div class="page-content">
        {{-- file to display error and success message --}}
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <div class="panel-actions">
                    @if(!empty($info_setting) && $info_setting->add_text != "")
                    <button class="btn btn-icon btn-primary float-right" style="margin:10px" data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                        <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                    </button>
                    @endif
                    <div class="btn-group float-right" style="margin:10px">
                        <a href="{{ route('segment.service-time-slot') }}">
                            <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                </div>
                <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                    @lang("$string_file.service_time_slots")@if(!empty($id))
                    @lang("$string_file.for")
                    {{isset($data['arr_day'][$data['service_time_slot']['day']]) ? $data['arr_day'][$data['service_time_slot']['day']] :''}}
                    @endif
                </h3>
            </header>
            <div class="panel-body container-fluid">
                <section id="validation">
                    {!! Form::open(["id" => "service-time-slot-form","name" => "service-time-slot-form","class"=>"steps-validation wizard-notification","files"=>true,"url"=>route("segment.service-time-slot.save")]) !!}
                    {!! Form::hidden('time_format',$data['time_format']) !!}
                    <fieldset>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name">@lang("$string_file.service_area")<span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('country_area_id',add_blank_option($data['arr_areas'],trans("$string_file.select")),old('country_area_id'),['class'=>'form-control','required'=>true,'id'=>'country_area_id','onChange'=>"getSegment()"]) !!}
                                    @if ($errors->has('country_area_id'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('country_area_id') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang("$string_file.segment") <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-group">
                                        {!! Form::select('segment_id[]',$data['arr_segment'],old('segment_id'),["class"=>"form-control select2","id"=>"area_segment","multiple" => true, "required"=>true,'onChange'=>"getService()"]) !!}
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.maximum_no_of_slots")
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::number('max_slot',old('max_slot'),['class'=>'form-control','id'=>'sequence_number','placeholder'=>'','required'=>true,'min'=>2,'max'=>24]) !!}
                                    @if ($errors->has('max_slot'))
                                    <label class="text-danger">{{ $errors->first('max_slot') }}</label>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang("$string_file.start_time") <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-group">
                                        <input type="text" value="" class="form-control timepicker" data-autoclose="true" id="start_time" q="" name="start_time" placeholder="" autocomplete="off">
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang("$string_file.end_time")<span class="text-danger">*</span>
                                    </label>
                                    <div class="form-group">
                                        <input type="text" value="" class="form-control timepicker" data-autoclose="true" id="end_time" q="" name="end_time" placeholder="" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang("$string_file.status") <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-group">
                                        {!! Form::select('status',$data['arr_status'],old('service_type_id'),['class'=>'form-control','required'=>true,'id'=>'status']) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_fill"  name="auto_fill" >
                                    <label class="form-check-label" for="flexCheckChecked">
                                        @lang("$string_file.auto_fill")
                                    </label>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-actions float-right">
                        @if($edit_permission)
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check-square-o"></i>{!! $data['submit_button'] !!}
                        </button>
                        @else
                        <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                        @endif
                    </div>
                    {!! Form::close() !!}
                </section>
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
<script type="text/javascript">
 function  getSegment() {
$("#area_segment").empty();
$("#area_segment").append('<option value="">@lang("$string_file.select")</option>');
$("#service_type_id").empty();
$("#service_type_id").append('<option value="">@lang("$string_file.select")</option>');
var area_id = $("#country_area_id option:selected").val();
if (area_id != "") {
$("#loader1").show();
var token = $('[name="_token"]').val();
$.ajax({
headers: {
'X-CSRF-TOKEN': token
},
method: 'POST',
url: '<?php echo route('get.area.segment') ?>',
// For handyman segments and grocery based segments
data: {area_id: area_id,segment_group_id:[2,4], sub_group_for_app: [2,6], check_where_or:true},
success: function (data) {
$("#area_segment").empty();
$('#area_segment').html(data);
}
});
$("#loader1").hide();
}
}
@if($data['time_format'] == 2)
$('.timepicker').timepicker({
timeFormat: 'H:i',

});
@else
$('.timepicker').timepicker({

});
@endif
</script>
@endsection
