@extends('merchant.layouts.main')
@section('content')
    @php
        $arr_yes_no = add_blank_option(get_status(true,$string_file),trans("$string_file.select"));
    @endphp
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
                            <a href="{{ route('bus_booking.price_card') }}">
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
                        {!! $price_card['title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$price_card['submit_url'],'class'=>'steps-validation wizard-notification']) !!}
                    @php

                        $price_card_id = $id = $price_card['data'] ? $price_card['data']->id : null;
                    @endphp
                    {!! Form::hidden('price_card_id',$price_card_id,['id'=>'price_card_id','readonly'=>true]) !!}
                    <div class="row">
                        <div class="col-md-4">
                            <label>@lang("$string_file.service_area") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                @if($id)
                                    {!! Form::text('country_area_id',$price_card['data']->CountryArea->CountryAreaName,['class'=>'form-control','id'=>'area','disabled'=>true]) !!}
                                @else
                                    {!! Form::select('country_area_id',add_blank_option($price_card['arr_area'],trans("$string_file.select")),old('country_area_id',isset($price_card['data']->country_area_id) ? $price_card['data']->country_area_id:null),['id'=>'area','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('end_point'))
                                        <label class="text-danger">{{ $errors->first('end_point') }}</label>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.vehicle_type") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                @if($id)
                                    {!! Form::text('vehicle_type_id',$price_card['data']->VehicleType->VehicleTypeName,['class'=>'form-control','id'=>'vehicle_type_id','disabled'=>true]) !!}
                                @else
                                    {!! Form::select('vehicle_type_id',add_blank_option($price_card['vehicle_type_arr'],trans("$string_file.select")),old('vehicle_type_id',isset($price_card['data']->vehicle_type_id) ? $price_card['data']->country_area_id:null),['id'=>'vehicle_type_id','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('vehicle_type_id'))
                                        <label class="text-danger">{{ $errors->first('vehicle_type_id') }}</label>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.bus_route") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                @if($id)
                                    {!! Form::text('bus_route_id',$price_card['data']->BusRoute->Name,['class'=>'form-control','id'=>'','disabled'=>true]) !!}
                                @else
                                    {!! Form::select('bus_route_id',add_blank_option([],trans("$string_file.select")),old('bus_route_id',isset($price_card['data']->bus_route_id) ? $price_card['data']->bus_route_id:null),['id'=>'bus_route_id','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('bus_route_id'))
                                        <label class="text-danger">{{ $errors->first('bus_route_id') }}</label>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.base_fare") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::text('base_fare',old('base_fare',isset($price_card['data']->base_fare) ? $price_card['data']->base_fare:""),['id'=>'base_fare','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('base_fare'))
                                    <label class="text-danger">{{ $errors->first('base_fare') }}</label>
                                @endif
                            </div>

                        </div>
                    </div>
                    @if($price_card['package_delivery_config'])
                        <div class="col-md-4">
                            <label>@lang("$string_file.package_delivery") @lang("$string_file.base") @lang("$string_file.price") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::text('package_delivery_base_fare',old('package_delivery_base_fare',isset($price_card['data']->package_delivery_base_fare) ? $price_card['data']->package_delivery_base_fare:""),['id'=>'package_delivery_base_fare','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('package_delivery_base_fare'))
                                    <label class="text-danger">{{ $errors->first('package_delivery_base_fare') }}</label>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div id="stop_points_data">
                        @if($id)
                            <div class="row">
                                <div class="col-md-4">
                                    <label><b>@lang("$string_file.start_point")</b> : <span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        @if($id)
                                            {!! Form::text('start_point',$price_card['data']->BusRoute->StartPoint->Name,['class'=>'form-control','id'=>'start_point','disabled'=>true]) !!}
                                        @endif
                                    </div>
                                </div>
                                {{--<div class="col-md-3">--}}
                                {{--<label>@lang("$string_file.time") (@lang("$string_file.in_minutes")): <span class="text-danger">*</span></label>--}}
                                {{--<div class="form-group">--}}
                                {{--@if($id)--}}
                                {{--{!! Form::text('start_time','00',['class'=>'form-control','id'=>'start_point','disabled'=>true]) !!}--}}
                                {{--@endif--}}
                                {{--</div>--}}
                                {{--</div>--}}
                                <div class="col-md-4">
                                    <label>@lang("$string_file.fare"): <span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        @if($id)
                                            {!! Form::text('start_stop_fare',$price_card['data']->start_stop_fare,['class'=>'form-control','id'=>'start_stop_fare','disabled'=>true]) !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @php $sn = 0;   @endphp
                            @foreach($price_card['data']->StopPointsPrice as $stop)
                                <div class="row">
                                    <div class="col-md-4">
                                        <label>@lang("$string_file.stop_point") {{++$sn}}: <span
                                                    class="text-danger">*</span></label>
                                        <div class="form-group">
                                            @if($id)
                                                {!! Form::text('stop_point[]',$stop->Name,['class'=>'form-control','id'=>'stop_point','disabled'=>true]) !!}
                                            @endif
                                        </div>
                                    </div>
                                    {{--<div class="col-md-4">--}}
                                    {{--<label>@lang("$string_file.time") (@lang("$string_file.in_minutes")): <span class="text-danger">*</span></label>--}}
                                    {{--<div class="form-group">--}}
                                    {{--@if($id)--}}
                                    {{--{!! Form::text('stop_time['.$stop->id.']',$stop->StopPointsConfig[0]['pivot']->time,['class'=>'form-control','id'=>'stop_time','disabled'=>true]) !!}--}}
                                    {{--@endif--}}
                                    {{--</div>--}}
                                    {{--</div>--}}
                                    <div class="col-md-4">
                                        <label>@lang("$string_file.fare"): <span class="text-danger">*</span></label>
                                        <div class="form-group">
                                            @if($id)
                                                {!! Form::text('stops_fare['.$stop->id.']',$stop->StopPointsPrice[0]['pivot']->price,['class'=>'form-control','id'=>'stop_fare']) !!}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <div class="row">
                                <div class="col-md-4">
                                    <label><b>@lang("$string_file.end_point")</b> : <span
                                                class="text-danger">*</span></label>
                                    <div class="form-group">
                                        @if($id)
                                            {!! Form::text('end_point',$price_card['data']->BusRoute->EndPoint->Name,['class'=>'form-control','id'=>'end_point','disabled'=>true]) !!}
                                        @endif
                                    </div>
                                </div>
                                {{--<div class="col-md-4">--}}
                                    {{--<label>@lang("$string_file.time") (@lang("$string_file.in_minutes")): <span--}}
                                                {{--class="text-danger">*</span></label>--}}
                                    {{--<div class="form-group">--}}
                                        {{--@if($id)--}}
                                            {{--{!! Form::text('end_time','00',['class'=>'form-control','id'=>'end_time','disabled'=>true]) !!}--}}
                                        {{--@endif--}}
                                    {{--</div>--}}
                                {{--</div>--}}
                                <div class="col-md-4">
                                    <label>@lang("$string_file.fare"): <span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        @if($id)
                                            {!! Form::text('end_stop_fare',$price_card['data']->end_stop_fare,['class'=>'form-control','id'=>'end_time']) !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <label>@lang("$string_file.status") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::select('status',$price_card['arr_status'],old('status',isset($price_card['data']->status) ? $price_card['data']->status:1),['id'=>'status','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('status'))
                                    <label class="text-danger">{{ $errors->first('status') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <h5 class="form-section col-md-12" style="color: black"><i
                                class="fa fa-paperclip"></i> @lang("$string_file.cancel_charges")
                    </h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">@lang("$string_file.cancel_charges")<span
                                            class="text-danger">*</span></label>
                                {!! Form::select('cancel_charges',$arr_yes_no,old('cancel_charges',isset($price_card['data']->cancel_charges) ? $price_card['data']->cancel_charges :NULL),['class'=>'form-control','required'=>true,'id'=>'cancel_charges']) !!}
                                @if ($errors->has('cancel_charges'))
                                    <label class="text-danger">{{ $errors->first('cancel_charges') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4" id="cancel_first">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.cancel_time")
                                    <span class="text-danger">*</span>
                                </label>{!! Form::number('cancel_time',old('cancel_time',isset($price_card['data']->cancel_time) ? $price_card['data']->cancel_time :NULL),['class'=>'form-control','id'=>'cancel_time','placeholder'=>"","min"=>"0"]) !!}
                            </div>
                        </div>
                        <div class="col-md-4" id="cancel_second">
                            <div class="form-group">
                                <label for="emailAddress5">@lang("$string_file.cancel_amount")<span
                                            class="text-danger">*</span></label>
                                {!! Form::number('cancel_amount',old('cancel_amount',isset($price_card['data']->cancel_amount) ? $price_card['data']->cancel_amount :NULL),['class'=>'form-control','id'=>'cancel_amount','placeholder'=>"","min"=>"0", "step"=>"0.01"]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="form-actions float-right">
                        @if($id == NULL || $edit_permission)
                            {!! Form::submit($price_card['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}
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

            {{--function getRouteConfig() {--}}

            {{--var id = $("#area option:selected").val();--}}

            {{--// if (id != "") {--}}
            {{--var token = $('[name="_token"]').val();--}}
            {{--$.ajax({--}}
            {{--headers: {--}}
            {{--'X-CSRF-TOKEN': token--}}
            {{--},--}}
            {{--method: 'POST',--}}
            {{--url: "{{route('get.route_config_area')}}",--}}
            {{--data: {--}}
            {{--country_area_id: id,--}}
            {{--},--}}
            {{--success: function(data) {--}}
            {{--console.log(data);--}}
            {{--$("#route_config_id").html(data);--}}
            {{--}--}}
            {{--});--}}

            {{--// }--}}
            {{--}--}}

            function getBusRoute() {
                var id = $("#area option:selected").val();
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
                    success: function (data) {
                        console.log(data);
                        $("#bus_route_id").html(data);
                    }
                });
            }

            $(document).on("change", "#area", function () {
                // getVehicle();
                // getRouteConfig();
                getBusRoute();
            });

            $(document).on("change", "#bus_route_id", function () {
                var bus_route_id = $("#bus_route_id option:selected").val();
                // var bus_route_id = $(this).find(':selected').attr('data-route-id');
                // let route_config_id = $("#route_config_id option:selected").val();
                var token = $('[name="_token"]').val();

                if (bus_route_id != "") {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        method: 'POST',
                        url: "{{route('get.config.bus_stops')}}",
                        data: {
                            // route_config_id: route_config_id,
                            bus_route_id: bus_route_id,
                        },
                        success: function (data) {
                            // console.log(data);
                            var start_point = data.start_point;
                            var end_point = data.end_point;
                            var stop_point = "<div class='row'><div class='col-md-4'>" +
                            "<div class='form-group'>" +
                                "<label for='start_point'> @lang("$string_file.start_point") : " +
                                    "<span class='text-danger'>*</span>" +
                                    "</label>" +
                                    "<input type='text' class='form-control' id='start_point' name='start_point' value='" + start_point + "' readonly>" +
                                "</div>" +
                            "</div>" +
                                {{-- "<div class='col-md-4'>" + --}}
                                    {{-- "<div class='form-group'>" + --}}
                                    {{-- "<label for='begintime'>" + --}}
                                    {{-- "@lang(\"$string_file.time\"):" + --}}
                                    {{-- "<span class='text-danger'>*</span>" + --}}
                                    {{-- "</label>" + --}}
                                    {{-- "<input type='text' id='stop_time' name='start_stop_time' value='00' class='form-control' placeholder='' readonly>" + --}}
                                    {{-- "</div>" + --}}
                                {{-- "</div>" + --}}
                            "<div class='col-md-4'>" +
                                "<div class='form-group'>" +
                                    "<label for='begintime'>" +
                                        "@lang("$string_file.fare"):" +
                                        "<span class='text-danger'>*</span>" +
                                    "</label>" +
                                    "<input type='text' id='route_fare' name='start_stop_fare' value='00' class='form-control' placeholder='' readonly>" +
                                "</div>" +
                            "</div></div>";

                            var count = 0;
                            var arr_points = data.stop_points;
                            arr_points.map(function (item) {
                                count++;
                                let key = item.id;
                                let value = item.name;
                                let time = item.time;
                                stop_point = stop_point +
                                    "<div class='row'><div class='col-md-4'>" +
                                    "<div class='form-group'>" +
                                        "<label for='bus_stop_id'> @lang("$string_file.stop_points") : " + count +
                                        "<span class='text-danger'>*</span>" +
                                        "</label>" +
                                        "<input type='hidden' id='bus_stop_id_" + key + "' name='bus_stop_id[" + key + "]' value='" + key + "' />" +
                                        "<input type='text' class='form-control' id='text_bus_stop_id" + key + "' name='text_bus_stop_id[" + key + "]' value='" + value + "' placeholder='" + value + "' autocomplete='off' readonly>" +
                                    "</div>" +
                                "</div>" +
                                
                                    {{-- "<div class='col-md-4'>" + --}}
                                        {{-- "<div class='form-group'>" + --}}
                                        {{-- "<label for='begintime'>" + --}}
                                        {{-- "@lang(\"$string_file.time\")(\"$string_file.in_minutes\"):" + --}}
                                        {{-- "<span class='text-danger'>*</span>" + --}}
                                        {{-- "</label>" + --}}
                                        {{-- "<input type='number' id='stop_time' name='stop_time[" + key + "]' value='" + time + "' class='form-control' placeholder='' min='5' required readonly>" + --}}
                                        {{-- "</div>" + --}}
                                    {{-- "</div>" + --}}
                                    
                                "<div class='col-md-4'>" +
                                    "<div class='form-group'>" +
                                        "<label for='begintime'>" +
                                            "@lang("$string_file.fare"):" +
                                            "<span class='text-danger'>*</span>" +
                                        "</label>" +
                                        "<input type='number' id='stop_time' name='stops_fare[" + key + "]' class='form-control' placeholder='' min='5' required>" +
                                    "</div>" +
                                "</div></div>" +
                                "<br>";
                            });
                            stop_point = stop_point + "<div class='row'><div class='col-md-4'>" +
                            "<div class='form-group'>" +
                                "<label for='end_point'> @lang("$string_file.end_point") : " +
                                "<span class='text-danger'>*</span>" +
                                "</label>" +
                                "<input type='text' class='form-control' id='end_stop_fare' name='end_point' value='" + end_point + "' readonly>" +
                            "</div>" +
                        "</div>" +
                        
                            {{-- "<div class='col-md-3'>" + --}}
                                {{-- "<div class='form-group'>" + --}}
                                {{-- "<label for='begintime'>" + --}}
                                {{-- "@lang("$string_file.time"):" + --}}
                                {{-- "<span class='text-danger'>*</span>" + --}}
                                {{-- "</label>" + --}}
                                {{-- "<input type='text' id='stop_time' name='end_stop_time' value='00' class='form-control' readonly>" + --}}
                                {{-- "</div>" + --}}
                            {{-- "</div>" + --}}
                        
                        "<div class='col-md-4'>" +
                            "<div class='form-group'>" +
                                "<label for='begintime'>" +
                                    "@lang("$string_file.fare"):" +
                                    "<span class='text-danger'>*</span>" +
                                "</label>" +
                                "<input type='text' id='stop_fare' name='end_stop_fare' value='00' class='form-control'>" +
                            "</div>" +
                        "</div></div>";
                            $("#stop_points_data").html(stop_point);
                        }
                    });
                } else {
                    $("#stop_points_data").html(null);
                }
            });

        });
    </script>
@endsection
