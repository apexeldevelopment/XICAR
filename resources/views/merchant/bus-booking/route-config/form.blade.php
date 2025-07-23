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
                        <a href="{{ route('bus_booking.route_config') }}">
                            <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                            </button>
                        </a>
                        @if(!empty($info_setting) && $info_setting->add_text != "")
                        <button class="btn btn-icon btn-primary float-right" style="margin-left:10px" data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                            <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                        </button>
                        @endif
                    </div>
                </div>
                <h3 class="panel-title"><i class="wb-add-file" aria-hidden="true"></i>
                    {!! $route_config['title'] !!}
                    (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                </h3>
            </header>
            <div class="panel-body container-fluid">
                {!! Form::open(['name'=>'','url'=>$route_config['submit_url'],'class'=>'steps-validation wizard-notification']) !!}
                @php

                $route_config_id = $id = $route_config['data'] ? $route_config['data']->id : null;
                @endphp

                {!! Form::hidden('route_config_id',$route_config_id,['id'=>'route_config_id','readonly'=>true]) !!}

                <div class="row">
                    <div class="col-md-4">
                        <label>@lang("$string_file.title") : <span class="text-danger">*</span></label>
                        <div class="form-group">
                            {!! Form::text('title',old('title',isset($route_config['data']->LanguageSingle->title) ? $route_config['data']->LanguageSingle->title:""),['id'=>'title','class'=>'form-control','required'=>true]) !!}
                            @if ($errors->has('title'))
                            <label class="text-danger">{{ $errors->first('title') }}</label>
                            @endif
                        </div>

                    </div>
                    <div class="col-md-4">
                        <label>@lang("$string_file.service_area") : <span class="text-danger">*</span></label>
                        <div class="form-group">
                            @if($id)
                            {!! Form::text('country_area_id',$route_config['data']->CountryArea->CountryAreaName,['class'=>'form-control','id'=>'area','disabled'=>true]) !!}
                            @else
                            {!! Form::select('country_area_id',add_blank_option($route_config['arr_area'],trans("$string_file.select")),old('country_area_id',isset($route_config['data']->country_area_id) ? $route_config['data']->country_area_id:null),['id'=>'area','class'=>'form-control','required'=>true]) !!}
                            @if ($errors->has('end_point'))
                            <label class="text-danger">{{ $errors->first('end_point') }}</label>
                            @endif
                            @endif
                        </div>
                    </div>


                    <div class="col-md-4">
                        <label>@lang("$string_file.bus_routes") : <span class="text-danger">*</span></label>
                        <div class="form-group">
                            @if($id)
                            {!! Form::text('bus_route_id',$route_config['data']->BusRoute->LanguageSingle->title,['class'=>'form-control','id'=>'vehicle_type_id','disabled'=>true]) !!}
                            @else
                            {!! Form::select('bus_route_id',add_blank_option([],trans("$string_file.select")),old('bus_route_id',isset($route_config['data']->bus_route_id) ? $route_config['data']->bus_route_id:null),['id'=>'bus_route_id','class'=>'form-control','required'=>true]) !!}
                            @if ($errors->has('bus_route_id'))
                            <label class="text-danger">{{ $errors->first('bus_route_id') }}</label>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row" id="stop_points_data">
                    @if($id)
                    <div class="col-md-6">
                        <label><b>@lang("$string_file.start_point")</b> : <span class="text-danger">*</span></label>
                        <div class="form-group">
                            @if($id)
                            {!! Form::text('start_point',$route_config['data']->BusRoute->StartPoint->LanguageSingle->name,['class'=>'form-control','id'=>'start_point','disabled'=>true]) !!}
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>@lang("$string_file.time") (@lang("$string_file.in_minutes")): <span class="text-danger">*</span></label>
                        <div class="form-group">
                            @if($id)
                            {!! Form::text('start_time','00',['class'=>'form-control','id'=>'start_point','disabled'=>true]) !!}
                            @endif
                        </div>
                    </div>
                    @php $sn = 0; @endphp
                    @foreach($route_config['data']->StopPointsTime as $stop)
                    <div class="col-md-6">
                        <label>@lang("$string_file.stop_point") {{++$sn}}: <span class="text-danger">*</span></label>
                        <div class="form-group">
                            @if($id)
                            {!! Form::text('stop_point[]',$stop->LanguageSingle->name,['class'=>'form-control','id'=>'stop_point','disabled'=>true]) !!}
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>@lang("$string_file.time") (@lang("$string_file.in_minutes")): <span class="text-danger">*</span></label>
                        <div class="form-group">
                            @if($id)
                            {!! Form::text('stop_time['.$stop->id.']',$stop->StopPointsConfig[0]['pivot']->time,['class'=>'form-control','id'=>'stop_time']) !!}
                            @endif
                        </div>
                    </div>
                    @endforeach
                    <div class="col-md-6">
                        <label><b>@lang("$string_file.end_point")</b> : <span class="text-danger">*</span></label>
                        <div class="form-group">
                            @if($id)
                            {!! Form::text('end_point',$route_config['data']->BusRoute->EndPoint->LanguageSingle->name,['class'=>'form-control','id'=>'end_point','disabled'=>true]) !!}
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>@lang("$string_file.time") (@lang("$string_file.in_minutes")): <span class="text-danger">*</span></label>
                        <div class="form-group">
                            @if($id)
                            {!! Form::text('end_time','00',['class'=>'form-control','id'=>'end_time','disabled'=>true]) !!}
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                <div class="row">

                    <div class="col-md-4">
                        <label>@lang("$string_file.status") : <span class="text-danger">*</span></label>
                        <div class="form-group">
                            {!! Form::select('status',$route_config['arr_status'],old('status',isset($route_config['data']->status) ? $route_config['data']->status:1),['id'=>'status','class'=>'form-control','required'=>true]) !!}
                            @if ($errors->has('status'))
                            <label class="text-danger">{{ $errors->first('status') }}</label>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="form-actions float-right">
                    @if($id == NULL || $edit_permission)
                    {!! Form::submit($route_config['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}
                    @else
                    <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
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
    $(document).ready(function() {
        // function getVehicle() {
        //     // var id = $("#package_id option:selected").val();
        //     var id = $("#area option:selected").val();
        //     //alert(id);
        //     if (id != "") {
        //         // $("#loader1").show();
        //         //var area = $('[name="area"]').val();
        //         var token = $('[name="_token"]').val();
        //         //var service = $('[name="service"]').val();
        //         $.ajax({
        //             headers: {
        //                 'X-CSRF-TOKEN': token
        //             },
        //             method: 'POST',
        //             url: "{{route('get.area.vehicles')}}",
        //             data: {
        //                 area_id: id,
        //             },
        //             success: function(data) {
        //                 console.log(data);
        //                 $("#vehicle_type_id").html(data);
        //             }
        //         });

        //     }
        // }

        function getBusRoutes() {

            var id = $("#area option:selected").val();

            // if (id != "") {
            var token = $('[name="_token"]').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: "{{route('get.area.bus_routes')}}",
                data: {
                    country_area_id: id,
                },
                success: function(data) {
                    console.log(data);
                    $("#bus_route_id").html(data);
                }
            });

            // }
        }

        $(document).on("change", "#area", function() {
            // getVehicle();
            getBusRoutes();
        });

        $(document).on("change", "#bus_route_id", function() {
            var bus_route_id = $("#bus_route_id option:selected").val();

            var token = $('[name="_token"]').val();

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: "{{route('get.route.bus_stops')}}",
                data: {
                    // area_id: area_id,
                    bus_route_id: bus_route_id,

                },


                success: function(data) {
                    var start_point = data.start_point;
                    var end_point = data.end_point;
                    var stop_point = '<div class="col-md-6">' +
                        '<div class="form-group">' +
                        '<label for="start_point"> @lang("$string_file.start_point") : ' +
                        '<span class="text-danger">*</span>' +
                        '</label>' +
                        '<input type ="text" class ="form-control" id = "start_point" name = "start_point" value ="' + start_point + '" readonly>' +
                        '</div>' +
                        '</div>' +
                        '<div class="col-md-4">' +
                        '<div class="form-group">' +
                        '<label for="begintime">' +
                        '@lang("$string_file.time"):' +
                        '<span class="text-danger">*</span>' +
                        '</label>' +
                        '<input type="text" id="stop_time" name="start_stop_time" value="00" class = "form-control" placeholder="" readonly>' +
                        '</div>' +
                        '</div>';

                    var count = 0;
                    var arr_points = data.stop_points;
                    $.each(arr_points,function(index,data) {
                        count++;
                        var value = data.name;
                        var key = data.id;
                        stop_point = stop_point +
                            '<div class="col-md-6">' +
                            '<div class="form-group">' +
                            '<label for="bus_stop_id"> @lang("$string_file.stop_points") : ' + count +
                            '<span class="text-danger">*</span>' +
                            '</label>' +
                            '<input type = "text" class = "form-control" id = "bus_stop_id" name = "bus_stop_id[' + key + ']" value ="' + value + '" placeholder = "' + value + '" autocomplete = "off" readonly>' +
                            '</div>' +
                            '</div>' +

                            '<div class="col-md-4">' +
                            '<div class="form-group">' +
                            '<label for="begintime">' +
                            '@lang("$string_file.time") (("$string_file.in_minutes")):' +
                            '<span class="text-danger">*</span>' +
                            '</label>' +
                            '<input type="number" id="stop_time" name="stop_time[' + key + ']" class = "form-control" placeholder="" min="5" required>' +
                            '</div>' +
                            '</div><br>';
                    });
                    stop_point = stop_point + '<div class="col-md-6">' +
                        '<div class="form-group">' +
                        '<label for="end_point"> @lang("$string_file.end_point") : ' +
                        '<span class="text-danger">*</span>' +
                        '</label>' +
                        '<input type ="text" class ="form-control" id = "end_point" name = "end_point" value ="' + end_point + '" readonly>' +
                        '</div>' +
                        '</div>' +
                        '<div class="col-md-4">' +
                        '<div class="form-group">' +
                        '<label for="begintime">' +
                        '@lang("$string_file.time"):' +
                        '<span class="text-danger">*</span>' +
                        '</label>' +
                        '<input type="text" id="stop_time" name="end_stop_time" value="00" class = "form-control" readonly>' +
                        '</div>' +
                        '</div>';
                    // stop_point = stop_point + '</row>';
                    $("#stop_points_data").html(stop_point);
                    // console.log(data);
                    // data.forEach((a, i) =>

                    //     alert("Key is " + Object.keys(a) + ", value is " + Object.values(a))
                    // )
                }
            });
        });

    });
</script>
@endsection
