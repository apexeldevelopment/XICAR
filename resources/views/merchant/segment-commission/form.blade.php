@extends('merchant.layouts.main')
@section('content')
    @php
        $arr_cal_method =  get_commission_method($string_file);
    @endphp
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->add_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('merchant.segment.commission') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        {{ $data['title'] }}
                    </h3>
                </header>
                @php $id = NULL; @endphp
                @if(isset($data['commission']['id']))
                    @php $id = $data['commission']['id']; @endphp
                @endif
                <div class="panel-body container-fluid">
                    <section id="validation">
                        {!! Form::open(["id" => "handyman-commission-form", "name" => "handyman-commission-form", "class"=>"steps-validation wizard-notification","files"=>true,"url"=>route("segment.commission.save",$id)]) !!}
                        {!! Form::hidden('id',$id) !!}
                        <fieldset>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">@lang("$string_file.service_area") <span class="text-danger">*</span>
                                            </label>
                                            {!! Form::select('country_area_id',add_blank_option($data['arr_areas'],trans("$string_file.select")),old('country_area_id',isset($data['commission']['country_area_id']) ? $data['commission']['country_area_id'] :NULL),['class'=>'form-control','required'=>true,'id'=>'country_area_id','onChange'=>"getSegment()"]) !!}
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
                                                {!! Form::select('segment_id',add_blank_option($data['arr_segment'],trans("$string_file.select")),old('segment_id',isset($data['commission']['segment_id']) ? $data['commission']['segment_id'] :NULL),["class"=>"form-control","id"=>"area_segment","required"=>true, "onChange" => ($merchant->BookingConfiguration->handyman_commission_type == 2) ? "getService()" : ""]) !!}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="emailAddress5">@lang("$string_file.commission_method")<span class="text-danger">*</span>
                                            </label>
                                            {!! Form::select('commission_method',add_blank_option($arr_cal_method,trans("$string_file.select")),old('commission_method',isset($data['commission']['commission_method']) ? $data['commission']['commission_method'] : NULL),["class"=>"form-control","id"=>"commission_method","required"=>true, "onChange" => ($merchant->BookingConfiguration->handyman_commission_type == 2) ? "getService()" : ""]) !!}
                                            @if ($errors->has('commission_method'))
                                                <label class="text-danger">{{ $errors->first('commission_method') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    @if($merchant->BookingConfiguration->handyman_commission_type == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.amount")
                                                <span class="text-danger">*</span>
                                            </label>
                                            {!! Form::number('commission',old('commission',isset($data['commission']['commission']) ? $data['commission']['commission'] : ''),['class'=>'form-control','id'=>'sequence_number','placeholder'=>"",'required'=>true,'min'=>0,'step'=>'any']) !!}
                                            @if ($errors->has('commission'))
                                                <label class="text-danger">{{ $errors->first('commission') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.tax") {{--<span class="text-danger">*</span> --}}
                                            </label>
                                            {!! Form::number('tax',old('tax',isset($data['commission']['tax']) ? $data['commission']['tax'] : ''),['class'=>'form-control','id'=>'tax','placeholder'=>"",'required'=>true,'min'=>0,'maxlength'=>3]) !!}
                                            @if ($errors->has('tax'))
                                                <label class="text-danger">{{ $errors->first('tax') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>@lang("$string_file.status") <span class="text-danger">*</span>
                                            </label>
                                            <div class="form-group">
                                                {!! Form::select('status',$data['arr_status'],old('service_type_id',isset($data['commission']['status']) ? $data['commission']['status'] :NULL),['class'=>'form-control','required'=>true,'id'=>'status']) !!}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="@if($id == NULL) custom-hidden @endif" id="service_type_div">
                                            {!! $data['arr_services'] !!}
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="form-actions float-right">

                                @if($id == NULL || $edit_permission)
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
        function getSegment() {
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
                    data: {area_id: area_id,segment_group_id:2},
                    success: function (data) {
                        $("#area_segment").empty();
                        $('#area_segment').html(data);
                    }
                });
                $("#loader1").hide();
            }
        }
    </script>

    <script type="text/javascript">
        //@ayush ( Config based Commission)
        function getService() {
            var area_id = $("#country_area_id option:selected").val();
            var segment_id = $("#area_segment option:selected").val();
            var handyman_commission_id = $("#handyman_commission_id").val();
            $('#service_type_div').html("");
            $("#service_type_div").hide();
            if (area_id != "" && segment_id != "" && commission_method != "") {

                var token = $('[name="_token"]').val();
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: '<?php echo route('segment.commission.services') ?>',
                    data: {area_id: area_id,segment_id:segment_id,segment_group:2,handyman_commission_id:handyman_commission_id},
                    success: function (data) {
                        $('#service_type_div').html(data);
                        $("#service_type_div").show();
                    },
                    error: function(err){
                        console.log(err);
                    }
                });
                // $("#loader1").hide();
            }
        }
    </script>

@endsection
