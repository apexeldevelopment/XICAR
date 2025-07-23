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
                            <a href="{{ route('bus_booking.bus_routes') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                            @if(!empty($info_setting) && $info_setting->add_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin-left:10px"
                                        data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-add-file" aria-hidden="true"></i>
                        {!! $bus_route['title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$bus_route['submit_url'],'class'=>'steps-validation wizard-notification']) !!}
                    {!! Form::hidden('bus_route_id',$bus_route['data']->id,['id'=>'bus_route_id','readonly'=>true]) !!}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.name") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('title',old('title',isset($bus_route['data']->LanguageSingle->title) ? $bus_route['data']->LanguageSingle->title : ''),['id'=>'title','class'=>'form-control','readonly'=>true]) !!}
                                @if ($errors->has('title'))
                                    <label class="text-danger">{{ $errors->first('title') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.service_area") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::text('country_area_id',old('country_area_id',$bus_route['data']->CountryArea->CountryAreaName),['id'=>'country_area_id','class'=>'form-control','readonly'=>true]) !!}
                                @if ($errors->has('country_area_id'))
                                    <label class="text-danger">{{ $errors->first('country_area_id') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.service_type") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::text('service_type_id',old('service_type_id',$bus_route['data']->segment_service_type_name),['id'=>'service_type_id','class'=>'form-control','readonly'=>true]) !!}
                                @if ($errors->has('service_type_id'))
                                    <label class="text-danger">{{ $errors->first('service_type_id') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <label>@lang("$string_file.start_point") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::hidden("start_point", $bus_route['data']->start_point,["id" => "start_point"]) !!}
                                {!! Form::text('start_point_text', $bus_route['data']->StartPoint->Name,['id'=>'start_point_text','class'=>'form-control','readonly'=>true]) !!}
                                @if ($errors->has('start_point'))
                                    <label class="text-danger">{{ $errors->first('start_point') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.time") (@lang("$string_file.in_minutes")): <span
                                        class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::text('start_time','00',['class'=>'form-control','id'=>'start_point', 'disabled'=>true]) !!}
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h4>@lang("$string_file.add") @lang("$string_file.stop")
                        <button type="button" class="btn btn-icon btn-success" id="add_row"><i class="wb-plus"></i>
                        </button>
                    </h4>
                    <div id="add_row_data">
                        @php $key = 0; @endphp
                        @php $first_stop_point = $bus_route['data']->StopPoints->first(); @endphp
                        @php $last_stop_point = $bus_route['data']->StopPoints->last(); @endphp
                        @if($bus_route['data']->StopPoints->count() > 1)
                            @php $first_stop_point = $bus_route['data']->StopPoints[0]; @endphp
                            @foreach($bus_route['data']->StopPoints->slice(1) as $stop)
                                @php $random_number = rand(10000,999999); @endphp
                                <div class="row row_content_{{$random_number}}" id="row_content_{{$random_number}}">
                                    <div class="col-md-4">
                                        <label>
                                            @lang("$string_file.stop_points"):<span class="text-danger">*</span>
                                            <button type="button" class="btn btn-icon btn-danger"
                                                    onclick="remove_row({{$random_number}})"
                                                    attr="random_number_{{$random_number}}"><i class="wb-close"></i>
                                            </button>
                                        </label>
                                        <div class="form-group">
                                            {!! Form::select('stop_points[]',$bus_route['arr_stop_points'],old('stop_points', $stop->id),["class"=>"form-control select2","required"=>true]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label>@lang("$string_file.time") (@lang("$string_file.in_minutes"): <span
                                                    class="text-danger">*</span></label>
                                        <div class="form-group">
                                            {!! Form::text('stop_time[]',$stop->pivot->time,['class'=>'form-control','id'=>'stop_time']) !!}
                                        </div>
                                    </div>
                                </div>
                                @php $key++; @endphp
                            @endforeach
                        @endif
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <label>@lang("$string_file.end_point") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::hidden("end_point", $bus_route['data']->end_point,["id" => "end_point"]) !!}
                                {!! Form::text('end_point_text', $bus_route['data']->EndPoint->Name,['id'=>'end_point_text','class'=>'form-control','readonly'=>true]) !!}
                                @if ($errors->has('end_point'))
                                    <label class="text-danger">{{ $errors->first('end_point') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.time") (@lang("$string_file.in_minutes")): <span
                                        class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::text('end_time',$last_stop_point->pivot->time,['class'=>'form-control','id'=>'end_time']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="form-actions float-right">
                        @if($edit_permission)
                            {!! Form::submit($bus_route['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}
                        @else
                            <span style="color: red"
                                  class="float-right">@lang("$string_file.demo_warning_message")</span>
                        @endif
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#add_row').click(function () {
                var random_number = Math.floor(Math.random() * 100) + 1;
                var className = "row_content_" + random_number;
                var stop_div = "<div class=\"row " + className + "\" id=\"row_content_" + random_number + "\"><div class=\"col-md-4\">\n" +
                    "                        <label>\n" +
                    "                            {!! trans("$string_file.stop_points") !!} : <span class=\"text-danger\">*</span>\n" +
                    "                            <button type=\"button\" class=\"btn btn-icon btn-danger\" onclick=\"remove_row(" + random_number + ")\" attr=" + random_number + "><i class=\"wb-close\"></i></button>\n" +
                    "                        </label>\n" +
                    "                        <div class=\"form-group\">\n" +
                    '                            {!! Form::select('stop_points[]',$bus_route['arr_stop_points'],old('stop_points'),["class"=>"form-control select2","required"=>true]) !!}\n' +
                    "                        </div>\n" +
                    "                    </div>" +
                    "                    <div class=\"col-md-4\">\n" +
                    "                            <label>{!! trans("$string_file.time") !!} ({!! trans("$string_file.in_minutes") !!}): <span\n" +
                    "                                        class=\"text-danger\">*</span></label>\n" +
                    "                            <div class=\"form-group\">\n" +
                    '                                {!! Form::text('stop_time[]','00',['class'=>'form-control','id'=>'stop_time']) !!}\n' +
                    "                            </div>\n" +
                    "                    </div>" +
                    "           </div>";
                $('#add_row_data').append(stop_div);
            });
        });

        function remove_row(random_id) {
            $(".row_content_" + random_id).remove();
        }
    </script>
@endsection
