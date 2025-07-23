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
                    @php

                        $id = $bus_route_id = NULL;
                    @endphp

                    {!! Form::hidden('bus_route_id',$bus_route_id,['id'=>'bus_route_id','readonly'=>true]) !!}

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.name") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('title',old('title',isset($bus_route['data']->LanguageSingle->title) ? $bus_route['data']->LanguageSingle->title : ''),['id'=>'title','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('title'))
                                    <label class="text-danger">{{ $errors->first('title') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.service_area") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::select('country_area_id',$bus_route['arr_area'],old('country_area_id',isset($bus_route['data']->country_area_id) ? $bus_route['data']->country_area_id:1),['id'=>'country_area_id','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('end_point'))
                                    <label class="text-danger">{{ $errors->first('end_point') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.service_type") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::select('service_type_id',$bus_route['service_type_arr'],old('service_type_id',isset($bus_route['data']->service_type_id) ? $bus_route['data']->service_type_id:1),['id'=>'service_type_id','onChange' => "getStopList()",'class'=>'form-control','readonly' => $id == NULL ? false : true, 'required'=>true]) !!}
                                @if ($errors->has('service_type_id'))
                                    <label class="text-danger">{{ $errors->first('service_type_id') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>@lang("$string_file.start_point") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::select('start_point',$bus_route['arr_stop_points'],old('status',isset($bus_route['data']->start_point) ? $bus_route['data']->start_point:null),['id'=>'start_point','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('start_point'))
                                    <label class="text-danger">{{ $errors->first('start_point') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.end_point") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::select('end_point',$bus_route['arr_stop_points'],old('end_point',isset($bus_route['data']->end_point) ? $bus_route['data']->end_point:null),['id'=>'end_point','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('end_point'))
                                    <label class="text-danger">{{ $errors->first('end_point') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.status") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::select('status',$bus_route['arr_status'],old('status',isset($bus_route['data']->status) ? $bus_route['data']->status:1),['id'=>'status','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('status'))
                                    <label class="text-danger">{{ $errors->first('status') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    {{--<br>--}}
                    {{--<div class="row">--}}
                        {{--<div class="col-md-6">--}}
                            {{--<h5 for="night_slot">@lang("$string_file.slot_details")<span class="text-danger">*</span>--}}
                            {{--</h5>--}}
                        {{--</div>--}}
                        {{--<div class="col-md-6" style="text-align: right;">--}}
                            {{--<button class="btn btn-dark rounded-circle" id="add_parent_div" type="button">--}}
                                {{--<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"--}}
                                     {{--fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"--}}
                                     {{--stroke-linejoin="round" class="feather feather-plus">--}}
                                    {{--<line x1="12" y1="5" x2="12" y2="19"></line>--}}
                                    {{--<line x1="5" y1="12" x2="19" y2="12"></line>--}}
                                {{--</svg>--}}
                            {{--</button>--}}
                        {{--</div>--}}
                    {{--</div>--}}
                    {{--<hr>--}}
                    {{--@if(!empty($bus_route['data']->BusRouteSchedule) && count($bus_route['data']->BusRouteSchedule) > 0)--}}
                        {{--<div id="parent_div" sr_number='{{$bus_route['data']->BusRouteSchedule->count()}}'>--}}
                            {{--@foreach($bus_route['data']->BusRouteSchedule as $key => $detail)--}}
                                {{--@php $week_days = explode(",", $detail->available_days); @endphp--}}
                                {{--@php $timing = explode(",", $detail->timing); @endphp--}}
                                {{--<div id="add-parent-row-content-{{$key}}">--}}
                                    {{--<div class="row">--}}
                                        {{--<div class="col-md-5">--}}
                                            {{--<label for="weekdays">--}}
                                                {{--@lang("$string_file.select_week_days"):<span--}}
                                                        {{--class="text-danger">*</span>--}}
                                            {{--</label>--}}
                                            {{--<div class="form-group">--}}
                                                {{--<div class="weekDays-selector">--}}
                                                    {{--<input type="checkbox" name="slab[{{$key}}][week_days][]"--}}
                                                           {{--value="MON" @if(in_array('MON', $week_days)) checked--}}
                                                           {{--@endif id="weekday_mon_{{$key}}" class="weekday mr-1 ml-1">--}}
                                                    {{--<label for="weekday_mon_{{$key}}">Mon</label>--}}
                                                    {{--<input type="checkbox" name="slab[{{$key}}][week_days][]"--}}
                                                           {{--value="TUE" @if(in_array('TUE', $week_days)) checked--}}
                                                           {{--@endif id="weekday_tue_{{$key}}" class="weekday mr-1 ml-1">--}}
                                                    {{--<label for="weekday_tue_{{$key}}">Tue</label>--}}
                                                    {{--<input type="checkbox" name="slab[{{$key}}][week_days][]"--}}
                                                           {{--value="WED" @if(in_array('WED', $week_days)) checked--}}
                                                           {{--@endif id="weekday_wed_{{$key}}" class="weekday mr-1 ml-1">--}}
                                                    {{--<label for="weekday_wed_{{$key}}">Wed</label>--}}
                                                    {{--<input type="checkbox" name="slab[{{$key}}][week_days][]"--}}
                                                           {{--value="THU" @if(in_array('THU', $week_days)) checked--}}
                                                           {{--@endif id="weekday_thu_{{$key}}" class="weekday mr-1 ml-1">--}}
                                                    {{--<label for="weekday_thu_{{$key}}">Thu</label>--}}
                                                    {{--<input type="checkbox" name="slab[{{$key}}][week_days][]"--}}
                                                           {{--value="FRI" @if(in_array('FRI', $week_days)) checked--}}
                                                           {{--@endif id="weekday_fri_{{$key}}" class="weekday mr-1 ml-1">--}}
                                                    {{--<label for="weekday_fri_{{$key}}">Fri</label>--}}
                                                    {{--<input type="checkbox" name="slab[{{$key}}][week_days][]"--}}
                                                           {{--value="SAT" @if(in_array('SAT', $week_days)) checked--}}
                                                           {{--@endif id="weekday_sat_{{$key}}" class="weekday mr-1 ml-1">--}}
                                                    {{--<label for="weekday_sat_{{$key}}">Sat</label>--}}
                                                    {{--<input type="checkbox" name="slab[{{$key}}][week_days][]"--}}
                                                           {{--value="SUN" @if(in_array('SUN', $week_days)) checked--}}
                                                           {{--@endif id="weekday_sun_{{$key}}" class="weekday mr-1 ml-1">--}}
                                                    {{--<label for="weekday_sun_{{$key}}">Sun</label>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                    {{--</div>--}}
                                    {{--@if(!empty($timing))--}}
                                        {{--@foreach($timing as $t_key => $timing)--}}
                                            {{--@php $unique_id = rand(10000,99999); @endphp--}}
                                            {{--<div class="form-row" id="add-row-content-{{$unique_id}}">--}}
                                                {{--<div class="col-md-2">--}}
                                                    {{--<label for="from_time_0_0">--}}
                                                        {{--@lang("$string_file.start_time")<span--}}
                                                                {{--class="text-danger">*</span></label>--}}
                                                    {{--<input type="time" name="slab[{{$key}}][time][]" value="{{$timing}}"--}}
                                                           {{--class="form-control"--}}
                                                           {{--id="from_time_{{$key}}_{{$key}}" required>--}}
                                                {{--</div>--}}
                                                {{--@if($t_key == 0)--}}
                                                    {{--<div class="form-group col-md-1">--}}
                                                        {{--<button class="btn btn-dark mt-4 mr-2 rounded-circle slab_add_row"--}}
                                                                {{--id="slab_add_row" parent_number="{{$key}}"--}}
                                                                {{--type="button">+--}}
                                                        {{--</button>--}}
                                                    {{--</div>--}}
                                                {{--@else--}}
                                                    {{--<div class="form-group col-md-1">--}}
                                                        {{--<button class="btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight"--}}
                                                                {{--onclick="slab_remove_row({{$unique_id}})" type="button">--}}
                                                            {{-----}}
                                                        {{--</button>--}}
                                                    {{--</div>--}}
                                                {{--@endif--}}
                                            {{--</div>--}}
                                        {{--@endforeach--}}
                                    {{--@else--}}
                                        {{--<div class="form-row mt-0">--}}
                                            {{--<div class="col-md-2">--}}
                                                {{--<label for="from_time_0_0">--}}
                                                    {{--@lang("$string_file.start_time")<span--}}
                                                            {{--class="text-danger">*</span></label>--}}
                                                {{--<input type="time" name="slab[0][time][]" value=""--}}
                                                       {{--class="form-control"--}}
                                                       {{--id="from_time_0_0" required>--}}
                                            {{--</div>--}}
                                            {{--<div class="form-group col-md-1">--}}
                                                {{--<button class="btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight"--}}
                                                        {{--onclick="slab_remove_row({{$unique_id}})" type="button">--}}
                                                    {{-----}}
                                                {{--</button>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                    {{--@endif--}}
                                {{--</div>--}}
                            {{--@endforeach--}}
                        {{--</div>--}}
                    {{--@else--}}
                        {{--<div id="parent_div" sr_number='0'>--}}
                            {{--<div class="row">--}}
                                {{--<div class="col-md-5">--}}
                                    {{--<label for="weekdays">--}}
                                        {{--@lang("$string_file.select_week_days"):<span class="text-danger">*</span>--}}
                                    {{--</label>--}}
                                    {{--<div class="form-group">--}}
                                        {{--<div class="weekDays-selector">--}}
                                            {{--<input type="checkbox" name="slab[0][week_days][]" value="MON"--}}
                                                   {{--id="weekday_mon_0" class="weekday mr-1 ml-1">--}}
                                            {{--<label for="weekday_mon_0">Mon</label>--}}
                                            {{--<input type="checkbox" name="slab[0][week_days][]" value="TUE"--}}
                                                   {{--id="weekday_tue_0" class="weekday mr-1 ml-1">--}}
                                            {{--<label for="weekday_tue_0">Tue</label>--}}
                                            {{--<input type="checkbox" name="slab[0][week_days][]" value="WED"--}}
                                                   {{--id="weekday_wed_0" class="weekday mr-1 ml-1">--}}
                                            {{--<label for="weekday_wed_0">Wed</label>--}}
                                            {{--<input type="checkbox" name="slab[0][week_days][]" value="THU"--}}
                                                   {{--id="weekday_thu_0" class="weekday mr-1 ml-1">--}}
                                            {{--<label for="weekday_thu_0">Thu</label>--}}
                                            {{--<input type="checkbox" name="slab[0][week_days][]" value="FRI"--}}
                                                   {{--id="weekday_fri_0" class="weekday mr-1 ml-1">--}}
                                            {{--<label for="weekday_fri_0">Fri</label>--}}
                                            {{--<input type="checkbox" name="slab[0][week_days][]" value="SAT"--}}
                                                   {{--id="weekday_sat_0" class="weekday mr-1 ml-1">--}}
                                            {{--<label for="weekday_sat_0">Sat</label>--}}
                                            {{--<input type="checkbox" name="slab[0][week_days][]" value="SUN"--}}
                                                   {{--id="weekday_sun_0" class="weekday mr-1 ml-1">--}}
                                            {{--<label for="weekday_sun_0">Sun</label>--}}
                                        {{--</div>--}}
                                    {{--</div>--}}
                                {{--</div>--}}
                            {{--</div>--}}
                            {{--<div id="slab_div_0" sr_number='0' parent_number="0">--}}
                                {{--<div class="form-row mt-0">--}}
                                    {{--<div class="col-md-2">--}}
                                        {{--<label for="from_time_0_0">--}}
                                            {{--@lang("$string_file.start_time")<span--}}
                                                    {{--class="text-danger">*</span></label>--}}
                                        {{--<input type="time" name="slab[0][time][]" value="" class="form-control"--}}
                                               {{--id="from_time_0_0" required>--}}
                                    {{--</div>--}}
                                    {{--<div class="form-group col-md-1">--}}
                                        {{--<button class="btn btn-dark mt-4 mr-2 rounded-circle slab_add_row" id="slab_add_row"--}}
                                                {{--parent_number="0" type="button">+--}}
                                        {{--</button>--}}
                                    {{--</div>--}}
                                {{--</div>--}}
                            {{--</div>--}}
                        {{--</div>--}}
                    {{--@endif--}}
                    <div class="form-actions float-right">
                        @if($id == NULL || $edit_permission)
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
        function getStopList() {
            selectElement = document.querySelector('#service_type_id');
            var id = selectElement.value;
            $.ajax({
                method: 'GET',
                url: "{{ route('bus_booking.get_bus_stops') }}",
                data: {service_type_id: id, name: "start_point"},
                success: function (data) {
                    $('#start_point').html(data);
                }
            });

            $.ajax({
                method: 'GET',
                url: "{{ route('bus_booking.get_bus_stops') }}",
                data: {service_type_id: id, name: "end_point"},
                success: function (data) {
                    $('#end_point').html(data);
                }
            });
        }

        $(document).on("click", ".slab_add_row", function () {
            var current_type_value = $("#type_value").val();
            var current_parent = $(this).attr("parent_number");
            var current_rows = parseInt($("#slab_div_" + current_parent).attr('sr_number'));
            console.log("current_rows-" + current_rows);
            var active_row = current_rows + 1;
            var unique_id = Date.now();
            var row_for_weight =
                "  <div class=\"form-row mt-0\" id=\"add-row-content-" + unique_id + "\">\n" +
                "    <div class=\"col-md-2\">\n" +
                "           <input type=\"time\" name=\"slab[" + current_parent + "][time][]\" value=\"\" class=\"form-control\" id=\"from_time_" + current_parent + unique_id + "\" required>\n" +
                "    </div>\n" +
                "    <div class=\"form-group col-md-1\">\n" +
                "       <button class=\"btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight\" type=\"button\" onclick=\"slab_remove_row(" + unique_id + ")\"'><strong>-</strong></button>\n" +
                "    </div>\n";


            $("#slab_div_" + current_parent).append(row_for_weight);
            $("#slab_div_" + current_parent).attr('sr_number', active_row);
        });

        $('document').ready(function () {
            $('#add_parent_div').click(function () {
                var current_type_value = $("#type_value").val();
                console.log(current_type_value);
                var current_rows = parseInt($("#parent_div").attr('sr_number'));
                var active_row = current_rows + 1;
                var unique_id = Date.now();
                var row_for_weight =
                    "<div id=\"add-parent-row-content-" + active_row + unique_id + "\"><hr>" +
                    "<div class=\"row\">" +
                    "<div class=\"col-md-5\">\n" +
                    "       <label for=\"weekdays" + unique_id + "\">Select Week Days<span class=\"text-danger\">*</span></label>" +
                    "     <div class=\"form-group\">" +
                    "           <div class=\"weekDays-selector\">" +
                    "             <input type=\"checkbox\" name=\"slab[" + active_row + "][week_days][]\" value=\"MON\" id=\"weekday_mon_" + unique_id + "\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_mon_" + unique_id + "\">Mon</label>" +
                    "             <input type=\"checkbox\" name=\"slab[" + active_row + "][week_days][]\" value=\"TUE\" id=\"weekday_tue_" + unique_id + "\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_tue_" + unique_id + "\">Tue</label>" +
                    "             <input type=\"checkbox\" name=\"slab[" + active_row + "][week_days][]\" value=\"WED\" id=\"weekday_wed_" + unique_id + "\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_wed_" + unique_id + "\">Wed</label>" +
                    "             <input type=\"checkbox\" name=\"slab[" + active_row + "][week_days][]\" value=\"THU\" id=\"weekday_thu_" + unique_id + "\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_thu_" + unique_id + "\">Thu</label>" +
                    "             <input type=\"checkbox\" name=\"slab[" + active_row + "][week_days][]\" value=\"FRI\" id=\"weekday_fri_" + unique_id + "\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_fri_" + unique_id + "\">Fri</label>" +
                    "             <input type=\"checkbox\" name=\"slab[" + active_row + "][week_days][]\" value=\"SAT\" id=\"weekday_sat_" + unique_id + "\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_sat_" + unique_id + "\">Sat</label>" +
                    "             <input type=\"checkbox\" name=\"slab[" + active_row + "][week_days][]\" value=\"SUN\" id=\"weekday_sun_" + unique_id + "\" class=\"weekday mr-1 ml-1\">" +
                    "            <label for=\"weekday_sun_" + unique_id + "\">Sun</label>" +
                    "           </div>\n" +
                    "     </div>\n" +
                    "</div>\n" +
                    "<div class=\"form-group col-md-1\">\n" +
                    "<button class=\"btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight\" type=\"button\" onclick=\"parent_remove_row(" + active_row + unique_id + ")\"'><strong>-</strong></button>\n" +
                    "</div>\n" +
                    "</div>";

                row_for_weight +=
                    "<div id=\"slab_div_" + active_row + "\" sr_number='1' parent_number=\"" + active_row + "\">" +
                    "  <div class=\"form-row mt-0\">\n" +
                    "       <div class=\"col-md-2\">\n" +
                    "           <label for=\"" + active_row + unique_id + "\">\n" +
                    "               Start Time<span class=\"text-danger\">*</span>" +
                    "           </label>\n" +
                    "           <input type=\"time\" name=\"slab[" + active_row + "][time][]\" value=\"\" class=\"form-control\" id=\"from_time_" + active_row + unique_id + "_\" required>\n" +
                    "        </div>" +
                    "       <div class=\"form-group col-md-1\">\n" +
                    "           <button class=\"btn btn-dark mt-4 mr-2 rounded-circle slab_add_row\" id=\"slab_add_row_" + unique_id + "\" parent_number=\"" + active_row + "\" type=\"button\">+</button>" +
                    "       </div>\n" +
                    "   </div>" +
                    "</div>\n";

                $('#parent_div').append(row_for_weight);
                $("#parent_div").attr('sr_number', active_row);
            });
        });

        function slab_remove_row(e) {
            console.log('Removed-' + e);
            $("#add-row-content-" + e).remove();
        }

        function parent_remove_row(e) {
            console.log('Removed-' + e);
            $("#add-parent-row-content-" + e).remove();
        }
    </script>
@endsection
