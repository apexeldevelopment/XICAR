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
                            <a href="{{ route('bus_booking.bus_driver_mapping') }}">
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
                        {!! $bus_driver_mapping['title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$bus_driver_mapping['submit_url'],'class'=>'steps-validation wizard-notification']) !!}
                    @php

                        $bus_driver_mapping_id = $id = $bus_driver_mapping['data'] ? $bus_driver_mapping['data']->id : null;
                    @endphp

                    {!! Form::hidden('bus_driver_mapping_id',$bus_driver_mapping_id,['id'=>'route_config_id','readonly'=>true]) !!}

                    <div class="row">
                        <div class="col-md-4">
                            <label>@lang("$string_file.routes") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                @if($id)
                                    {!! Form::hidden('bus_route_id',$bus_driver_mapping['data']->bus_route_id,['class'=>'form-control','id'=>'bus_id']) !!}
                                    {!! Form::text('bus_route',isset($bus_driver_mapping['data']->BusRoute->LanguageSingle)? $bus_driver_mapping['data']->BusRoute->LanguageSingle->title: "",['class'=>'form-control','disabled'=>true]) !!}
                                @else
                                    {!! Form::select('bus_route_id',add_blank_option($bus_driver_mapping['arr_routes'],trans("$string_file.select")),old('bus_route_id',isset($bus_driver_mapping['data']->bus_route_id) ? $bus_driver_mapping['data']->bus_route_id:null),['id'=>'bus_route_id','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('bus_route_id'))
                                        <label class="text-danger">{{ $errors->first('bus_route_id') }}</label>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.all_buses") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                @if($id)
                                    {!! Form::hidden('bus_id',$bus_driver_mapping['data']->bus_id,['class'=>'form-control','id'=>'bus_id']) !!}
                                    {!! Form::text('bus',$bus_driver_mapping['data']->Bus->vehicle_number.' | '.$bus_driver_mapping['data']->Bus->vehicle_color.' | '.$bus_driver_mapping['data']->Bus->VehicleType->VehicleTypeName,['class'=>'form-control']) !!}
                                @else
                                    {!! Form::select('bus_id',add_blank_option([],trans("$string_file.select")),old('bus_id',isset($bus_driver_mapping['data']->bus_id) ? $bus_driver_mapping['data']->bus_id:null),['id'=>'bus_id','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('bus_id'))
                                        <label class="text-danger">{{ $errors->first('bus_id') }}</label>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.driver") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                @if($id)
                                    {!! Form::hidden('driver_id',$bus_driver_mapping['data']->driver_id,['class'=>'form-control','id'=>'driver_id']) !!}
                                    {!! Form::text('driver', $bus_driver_mapping['data']->Driver->first_name.' | '.$bus_driver_mapping['data']->Driver->last_name.' | '.$bus_driver_mapping['data']->Driver->phoneNumber,['class'=>'form-control']) !!}
                                @else
                                    {!! Form::select('driver_id',add_blank_option([],trans("$string_file.select")),old('driver_id',isset($bus_driver_mapping['data']->driver_id) ? $bus_driver_mapping['data']->driver_id : null),['id'=>'driver_id','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('driver_id'))
                                        <label class="text-danger">{{ $errors->first('driver_id') }}</label>
                                    @endif
                                @endif
                            </div>
                        </div>
                        {{--<div class="col-md-4">--}}
                            {{--<label>@lang("$string_file.status") : <span class="text-danger">*</span></label>--}}
                            {{--<div class="form-group">--}}
                                {{--{!! Form::select('status',add_blank_option($bus_driver_mapping['arr_status'],trans("$string_file.select")),old('status',isset($bus_driver_mapping['data']->status) ? $bus_driver_mapping['data']->status:null),['id'=>'status','class'=>'form-control','required'=>true]) !!}--}}
                                {{--@if ($errors->has('status'))--}}
                                    {{--<label class="text-danger">{{ $errors->first('status') }}</label>--}}
                                {{--@endif--}}
                            {{--</div>--}}
                        {{--</div>--}}
                    </div>
                    <div id="service_time_slot_div">
                        {!! $bus_driver_mapping['service_time_slot'] !!}
                    </div>
                    <div class="form-actions float-right">
                        @if($id == NULL || $edit_permission)
                            {!! Form::submit($bus_driver_mapping['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}
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
        $(document).on("click", ".time-slot-checkbox", function (e) {
            var val = $(this).val();
            if ($(this).is(':checked')) {
            }
        })

        $(document).ready(function () {
            function getBusRouteTimeSlot() {
                var id = $("#bus_route_id option:selected").val();
                var bus_id = $("#bus_id option:selected").val();
                if (id != "" && bus_id != "") {
                    var token = $('[name="_token"]').val();
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        method: 'POST',
                        url: "{{route('get.bus_route_bus_timeslot')}}",
                        data: {
                            bus_route_id: id,
                            bus_id: bus_id,
                        },
                        success: function (data) {
                            console.log(data);
                            $("#service_time_slot_div").html(data);
                        }
                    });
                } else {
                    $("#service_time_slot_div").html(null);
                }
            }

            function getBuses() {
                var id = $("#bus_route_id option:selected").val();
                if (id != "") {
                    var token = $('[name="_token"]').val();
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        method: 'POST',
                        url: "{{route('get.route.buses')}}",
                        data: {
                            bus_route_id: id,
                        },
                        success: function (data) {
                            console.log(data);
                            $("#bus_id").html(data.buses);
                            // $("#service_time_slot_div").html(data.service_time_slot);
                        }
                    });
                } else {
                    $("#bus_id").html(null);
                    $("#service_time_slot_div").html(null);
                }
            }

            function getDrivers() {
                var id = $("#bus_route_id option:selected").val();
                var token = $('[name="_token"]').val();
                if (id != "") {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        method: 'POST',
                        url: "{{route('get.bus_booking.drivers')}}",
                        data: {
                            bus_route_id: id,
                        },
                        success: function (data) {
                            console.log(data);
                            $("#driver_id").html(data);
                        }
                    });
                } else {
                    $("#driver_id").html(null);
                }
            }

            $(document).on("change", "#bus_route_id", function () {
                getBuses();
                getDrivers();
            });

            $(document).on("change", "#bus_id", function () {
                getBusRouteTimeSlot();
            });
        });
    </script>
@endsection
