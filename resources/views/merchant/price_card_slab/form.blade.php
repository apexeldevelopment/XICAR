@extends('merchant.layouts.main')
@section('content')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('merchant.pricecard.slabs') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.add") @lang("$string_file.price_card_slab")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" id="form" class="steps-validation wizard-notification"
                              enctype="multipart/form-data" action="{{ route('merchant.pricecard.slab.save',isset($price_card_slab->id) ? $price_card_slab->id : NULL) }}">
                            {!! Form::hidden('id',$id,['class'=>'form-control','id'=>'id']) !!}
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.name")
                                            <span class="text-danger">*</span>
                                        </label>
                                        {!! Form::text("name",old("name",!empty($price_card_slab) ? $price_card_slab->name : NULL), ["id"=>"name", "class" => "form-control", "required"]) !!}
                                        @if ($errors->has('name'))
                                            <label class="text-danger">{{ $errors->first('name') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.area")
                                            <span class="text-danger">*</span>
                                        </label>
                                        @if(empty($id))
                                            {!! Form::select("country_area_id",$areas,old("country_area_id",isset($price_card_slab->country_area_id) ? $price_card_slab->country_area_id : NULL), array("id"=>"country_area_id", "class" => "form-control", "required")) !!}
                                            @if ($errors->has('country_area_id'))
                                                <label class="text-danger">{{ $errors->first('country_area_id') }}</label>
                                            @endif
                                        @else
                                            {!! Form::text('country_area_id',isset($price_card_slab->CountryArea->CountryAreaName) ? $price_card_slab->CountryArea->CountryAreaName : NULL,['class'=>'form-control','id'=>'country_area_id','disabled'=>true]) !!}
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.type")
                                            <span class="text-danger">*</span>
                                        </label>
                                        @php $type_arr = get_price_card_slab_types($string_file, false);@endphp
                                        @if(empty($id))
                                            {!! Form::select("type",$type_arr,old("type",isset($price_card_slab->type) ? $price_card_slab->type : NULL), array("id"=>"type", "class" => "form-control", "required")) !!}
                                            @if ($errors->has('type'))
                                                <label class="text-danger">{{ $errors->first('type') }}</label>
                                            @endif
                                        @else
                                            {!! Form::text('type',$type_arr[$price_card_slab->type],['class'=>'form-control','id'=>'type','disabled'=>true]) !!}
                                            {!! Form::hidden("type_value",$price_card_slab->type, array("id" => "type_value")) !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if(!empty($price_card_slab->PriceCardSlabDetail))
                            <br>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 for="night_slot">@lang("$string_file.slot_details")<span class="text-danger">*</span></h5>
                                </div>
                                <div class="col-md-6" style="text-align: right;">
                                    <button class="btn btn-dark rounded-circle" id="add_parent_div" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                </div>
                            </div>
                            <hr>
                            @endif
                            @if(!empty($price_card_slab->PriceCardSlabDetail) && count($price_card_slab->PriceCardSlabDetail) > 0)
                                <div id="parent_div" sr_number='{{$price_card_slab->PriceCardSlabDetail->count()}}'>
                                    @foreach($price_card_slab->PriceCardSlabDetail as $key => $detail)
                                        @php $week_days = explode(",", $detail->week_days); @endphp
                                        <div id="add-parent-row-content-{{$key}}">
                                            <div class="row">
                                                <div class="col-md-5" >
                                                    <label for="weekdays">
                                                        @lang("$string_file.select_week_days"):<span class="text-danger">*</span>
                                                    </label>
                                                    <div class="form-group">
                                                        <div class="weekDays-selector">
                                                            <input type="checkbox" name="slab[{{$key}}][week_days][]" value="MON" @if(in_array('MON', $week_days)) checked @endif id="weekday_mon_{{$key}}" class="weekday mr-1 ml-1">
                                                            <label for="weekday_mon_{{$key}}">Mon</label>
                                                            <input type="checkbox" name="slab[{{$key}}][week_days][]" value="TUE" @if(in_array('TUE', $week_days)) checked @endif id="weekday_tue_{{$key}}" class="weekday mr-1 ml-1">
                                                            <label for="weekday_tue_{{$key}}">Tue</label>
                                                            <input type="checkbox" name="slab[{{$key}}][week_days][]" value="WED" @if(in_array('WED', $week_days)) checked @endif id="weekday_wed_{{$key}}" class="weekday mr-1 ml-1">
                                                            <label for="weekday_wed_{{$key}}">Wed</label>
                                                            <input type="checkbox" name="slab[{{$key}}][week_days][]" value="THU" @if(in_array('THU', $week_days)) checked @endif id="weekday_thu_{{$key}}"class="weekday mr-1 ml-1">
                                                            <label for="weekday_thu_{{$key}}">Thu</label>
                                                            <input type="checkbox" name="slab[{{$key}}][week_days][]" value="FRI" @if(in_array('FRI', $week_days)) checked @endif id="weekday_fri_{{$key}}"class="weekday mr-1 ml-1">
                                                            <label for="weekday_fri_{{$key}}">Fri</label>
                                                            <input type="checkbox" name="slab[{{$key}}][week_days][]" value="SAT" @if(in_array('SAT', $week_days)) checked @endif id="weekday_sat_{{$key}}" class="weekday mr-1 ml-1">
                                                            <label for="weekday_sat_{{$key}}">Sat</label>
                                                            <input type="checkbox" name="slab[{{$key}}][week_days][]" value="SUN" @if(in_array('SUN', $week_days)) checked @endif id="weekday_sun_{{$key}}" class="weekday mr-1 ml-1">
                                                            <label for="weekday_sun_{{$key}}">Sun</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="night_slot">
                                                            @lang("$string_file.start_time")<span
                                                                    class="text-danger">*</span></label>
                                                        <input type="time" name="slab[{{$key}}][from_time]" value="{{$detail->from_time}}" class="form-control" id="from_time_{{$key}}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="night_slot">
                                                        @lang("$string_file.end_time")<span
                                                                class="text-danger">*</span></label>
                                                    <div class="form-group">
                                                        <input type="time" name="slab[{{$key}}][to_time]"  value="{{$detail->to_time}}" class="form-control" id="to_time_{{$key}}" required>
                                                    </div>
                                                </div>
                                                @if($key != 0)
                                                    <div class="form-group col-md-1">
                                                        <button class="btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight" type="button" onclick="parent_remove_row({{$key}})"><strong>-</strong></button>
                                                    </div>
                                                @endif
                                            </div>
                                            @php $slabs = json_decode($detail->details,true); @endphp
                                            @if($price_card_slab->type == "DISTANCE")
                                                <div id="slab_div_{{$key}}" sr_number='{{count($slabs)}}' parent_number='{{$key}}'>
                                                    @foreach($slabs as $slab_key => $slab)
                                                        @php $unique_id = rand(10000,99999); @endphp
                                                        <div class="form-row" id="add-row-content-{{$unique_id}}">
                                                            <div class="form-group col-md-2">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.distance_from")<span class="text-danger">*</span></label> @endif
                                                                {{ Form::number("slab[$key][$slab_key][distance_from]",$slab['distance_from'],["class" => "form-control form-control-sm distance_from","id"=>"distance_from_$key.$slab_key",  "step" => ".001", "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            <div class="form-group col-md-2">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.distance_to")<span class="text-danger">*</span></label> @endif
                                                                {{ Form::number("slab[$key][$slab_key][distance_to]",$slab['distance_to'],["class" => "form-control form-control-sm distance_to","id"=>"distance_to_$key.$slab_key", "step" => ".001", "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            <div class="form-group col-md-2">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.unit")<span class="text-danger">*</span></label> @endif
                                                                {{ Form::number("slab[$key][$slab_key][unit]",$slab['unit'],["class" => "form-control form-control-sm unit", "id"=>"unit_$key.$slab_key", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            <div class="form-group col-md-3">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.charges")<span class="text-danger">*</span></label>@endif
                                                                {{ Form::number("slab[$key][$slab_key][charges]",$slab['charges'],["class" => "form-control form-control-sm charges", "id"=>"charges_$key.$slab_key", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            @if($slab_key == 0)
                                                                <div class="form-group col-md-1">
                                                                    <button class="btn btn-dark mt-4 mr-2 rounded-circle slab_add_row" id="slab_add_row" parent_number="{{$key}}" type="button">+</button>
                                                                </div>
                                                            @else
                                                                <div class="form-group col-md-1">
                                                                    <button class="btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight" onclick="slab_remove_row({{$unique_id}})" type="button">-</button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($price_card_slab->type == "BASE_FARE")
                                                <div id="slab_div_{{$key}}" sr_number='{{count($slabs)}}' parent_number='{{$key}}'>
                                                    @foreach($slabs as $slab_key => $slab)
                                                        @php $unique_id = rand(10000,99999); @endphp
                                                        <div class="form-row" id="add-row-content-{{$unique_id}}">
                                                            <div class="form-group col-md-3">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.base_fare")<span class="text-danger">*</span></label> @endif
                                                                {{ Form::number("slab[$key][$slab_key][base_fare]",$slab['base_fare'],["class" => "form-control form-control-sm base_fare","id"=>"distance_from_$key.$slab_key",  "step" => ".001", "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            <div class="form-group col-md-3">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.free_distance")<span class="text-danger">*</span></label> @endif
                                                                {{ Form::number("slab[$key][$slab_key][free_distance]",$slab['free_distance'],["class" => "form-control form-control-sm free_distance","id"=>"distance_to_$key.$slab_key", "step" => ".001", "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            <div class="form-group col-md-3">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.free_time")<span class="text-danger">*</span></label> @endif
                                                                {{ Form::number("slab[$key][$slab_key][free_time]",$slab['free_time'],["class" => "form-control form-control-sm free_time", "id"=>"unit_$key.$slab_key", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($price_card_slab->type == "RIDE_TIME")
                                                <div id="slab_div_{{$key}}" sr_number='{{count($slabs)}}' parent_number='{{$key}}'>
                                                    @foreach($slabs as $slab_key => $slab)
                                                        @php $unique_id = rand(10000,99999); @endphp
                                                        <div class="form-row" id="add-row-content-{{$unique_id}}">
                                                            <div class="form-group col-md-2">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.from")<span class="text-danger">*</span></label> @endif
                                                                {{ Form::number("slab[$key][$slab_key][from]",$slab['from'],["class" => "form-control form-control-sm from","id"=>"from_$key.$slab_key",  "step" => ".001", "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            <div class="form-group col-md-2">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.to")<span class="text-danger">*</span></label> @endif
                                                                {{ Form::number("slab[$key][$slab_key][to]",$slab['to'],["class" => "form-control form-control-sm to","id"=>"to_$key.$slab_key", "step" => ".001", "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            <div class="form-group col-md-2">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.unit")<span class="text-danger">*</span></label> @endif
                                                                {{ Form::number("slab[$key][$slab_key][unit]",$slab['unit'],["class" => "form-control form-control-sm unit", "id"=>"unit_$key.$slab_key", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            <div class="form-group col-md-3">
                                                                @if($slab_key == 0) <label for="exampleFormControlInput1">@lang("$string_file.charges")<span class="text-danger">*</span></label>@endif
                                                                {{ Form::number("slab[$key][$slab_key][charges]",$slab['charges'],["class" => "form-control form-control-sm charges", "id"=>"charges_$key.$slab_key", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                            </div>
                                                            @if($slab_key == 0)
                                                                <div class="form-group col-md-1">
                                                                    <button class="btn btn-dark mt-4 mr-2 rounded-circle slab_add_row" id="slab_add_row" parent_number="{{$key}}" type="button">+</button>
                                                                </div>
                                                            @else
                                                                <div class="form-group col-md-1">
                                                                    <button class="btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight" onclick="slab_remove_row({{$unique_id}})" type="button">-</button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(!empty($price_card_slab))
                                <div id="parent_div" sr_number='0'>
                                    <div class="row">
                                        <div class="col-md-5" >
                                            <label for="weekdays">
                                                @lang("$string_file.select_week_days"):<span class="text-danger">*</span>
                                            </label>
                                            <div class="form-group">
                                                <div class="weekDays-selector">
                                                    <input type="checkbox" name="slab[0][week_days][]" value="MON" id="weekday_mon_0" class="weekday mr-1 ml-1">
                                                    <label for="weekday_mon_0">Mon</label>
                                                    <input type="checkbox" name="slab[0][week_days][]" value="TUE" id="weekday_tue_0" class="weekday mr-1 ml-1">
                                                    <label for="weekday_tue_0">Tue</label>
                                                    <input type="checkbox" name="slab[0][week_days][]" value="WED" id="weekday_wed_0" class="weekday mr-1 ml-1">
                                                    <label for="weekday_wed_0">Wed</label>
                                                    <input type="checkbox" name="slab[0][week_days][]" value="THU" id="weekday_thu_0"class="weekday mr-1 ml-1">
                                                    <label for="weekday_thu_0">Thu</label>
                                                    <input type="checkbox" name="slab[0][week_days][]" value="FRI" id="weekday_fri_0"class="weekday mr-1 ml-1">
                                                    <label for="weekday_fri_0">Fri</label>
                                                    <input type="checkbox" name="slab[0][week_days][]" value="SAT" id="weekday_sat_0" class="weekday mr-1 ml-1">
                                                    <label for="weekday_sat_0">Sat</label>
                                                    <input type="checkbox" name="slab[0][week_days][]" value="SUN" id="weekday_sun_0" class="weekday mr-1 ml-1">
                                                    <label for="weekday_sun_0">Sun</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="night_slot">
                                                @lang("$string_file.start_time")<span
                                                        class="text-danger">*</span></label>
                                            <input type="time" name="slab[0][from_time]" value="" class="form-control" id="from_time_0" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="night_slot">
                                                @lang("$string_file.end_time")<span
                                                        class="text-danger">*</span></label>
                                            <div class="form-group">
                                                <input type="time" name="slab[0][to_time]"  value="" class="form-control" id="to_time_0" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="slab_div_0" sr_number='0' parent_number='0'>
                                        @if($price_card_slab->type == "DISTANCE")
                                            <div class="form-row">
                                            <div class="form-group col-md-2">
                                                <label for="exampleFormControlInput1">@lang("$string_file.distance_from")<span class="text-danger">*</span></label>
                                                {{ Form::number("slab[0][0][distance_from]",old("slab[0][0][distance_from]"),["class" => "form-control form-control-sm distance_from","id"=>"distance_from_1",  "step" => ".001", "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="exampleFormControlInput1">@lang("$string_file.distance_to")<span class="text-danger">*</span></label>
                                                {{ Form::number("slab[0][0][distance_to]",old("slab[0][0][distance_to]"),["class" => "form-control form-control-sm distance_to","id"=>"distance_to_1", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                            </div>
                                            <div class="form-group col-md-1">
                                                <label for="exampleFormControlInput1">@lang("$string_file.unit")<span class="text-danger">*</span></label>
                                                {{ Form::number("slab[0][0][unit]",old("slab[0][0][unit]"),["class" => "form-control form-control-sm unit", "id"=>"unit_1", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="exampleFormControlInput1">@lang("$string_file.charges")<span class="text-danger">*</span></label>
                                                {{ Form::number("slab[0][0][charges]",old("slab[0][0][charges]"),["class" => "form-control form-control-sm charges", "id"=>"charges_1", "step" => ".001","max" => "1000","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                            </div>
                                            <div class="form-group col-md-1">
                                                <button class="btn btn-dark mt-4 mr-2 rounded-circle slab_add_row" id="slab_add_row" parent_number="0" type="button">+</button>
                                            </div>
                                        </div>
                                        @elseif($price_card_slab->type == "BASE_FARE")
                                            <div class="form-row">
                                                <div class="form-group col-md-3">
                                                    <label for="exampleFormControlInput1">@lang("$string_file.base_fare")<span class="text-danger">*</span></label>
                                                    {{ Form::number("slab[0][0][base_fare]",old("slab[0][0][base_fare]"),["class" => "form-control form-control-sm base_fare","id"=>"base_fare_1",  "step" => ".001", "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="exampleFormControlInput1">@lang("$string_file.free_distance")<span class="text-danger">*</span></label>
                                                    {{ Form::number("slab[0][0][free_distance]",old("slab[0][0][free_distance]"),["class" => "form-control form-control-sm free_distance","id"=>"free_distance_1", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="exampleFormControlInput1">@lang("$string_file.free_time")<span class="text-danger">*</span></label>
                                                    {{ Form::number("slab[0][0][free_time]",old("slab[0][0][free_time]"),["class" => "form-control form-control-sm free_time", "id"=>"free_time_1", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                </div>
                                            </div>
                                        @elseif($price_card_slab->type == "RIDE_TIME")
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="exampleFormControlInput1">@lang("$string_file.from")<span class="text-danger">*</span></label>
                                                    {{ Form::number("slab[0][0][from]",old("slab[0][0][from]"),["class" => "form-control form-control-sm from","id"=>"from_1",  "step" => ".001", "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="exampleFormControlInput1">@lang("$string_file.to")<span class="text-danger">*</span></label>
                                                    {{ Form::number("slab[0][0][to]",old("slab[0][0][to]"),["class" => "form-control form-control-sm to","id"=>"to_1", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                </div>
                                                <div class="form-group col-md-1">
                                                    <label for="exampleFormControlInput1">@lang("$string_file.unit")<span class="text-danger">*</span></label>
                                                    {{ Form::number("slab[0][0][unit]",old("slab[0][0][unit]"),["class" => "form-control form-control-sm unit", "id"=>"unit_1", "step" => ".001","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="exampleFormControlInput1">@lang("$string_file.charges")<span class="text-danger">*</span></label>
                                                    {{ Form::number("slab[0][0][charges]",old("slab[0][0][charges]"),["class" => "form-control form-control-sm charges", "id"=>"charges_1", "step" => ".001","max" => "1000","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                                </div>
                                                <div class="form-group col-md-1">
                                                    <button class="btn btn-dark mt-4 mr-2 rounded-circle slab_add_row" id="slab_add_row" parent_number="0" type="button">+</button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="form-actions right" style="margin-bottom: 3%">
                                <button type="submit" class="btn btn-primary float-right" id="submit">
                                    <i class="fa fa-check-circle"></i>
                                    @lang("$string_file.save")
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('global/vendor/jquery/jquery.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.19.4/moment.js"></script>

    <script>
        function numberOnly(id) {
            return true;
            // var element = document.getElementById(id);
            // element.value = element.value.replace(/[^0-9]/gi, "");
        }

        $(document).on("click",".slab_add_row",function(){
            var current_type_value = $("#type_value").val();
            var current_parent = $(this).attr("parent_number");
            var current_rows = parseInt($("#slab_div_"+current_parent).attr('sr_number'));
            console.log("current_rows-" + current_rows);
            var active_row = current_rows + 1;
            var unique_id = Date.now();
            if(current_type_value == "DISTANCE"){
                var row_for_weight =
                    "  <div class=\"form-row mt-0\" id=\"add-row-content-" + unique_id + "\">\n" +
                    "    <div class=\"form-group col-md-2\">\n" +
                    "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm distance_from\" id=\"distance_from"+unique_id+"\" name=\"slab[" + current_parent + "][" + active_row + "][distance_from]\" step='.001' required oninput='numberOnly(this.id)'>" +
                    "    </div>\n" +
                    "    <div class=\"form-group col-md-2\">\n" +
                    "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm distance_to\" id=\"distance_to"+unique_id+"\" name=\"slab[" + current_parent + "][" + active_row + "][distance_to]\" step='.001' required oninput='numberOnly(this.id)'>" +
                    "    </div>\n " +
                    "    <div class=\"form-group col-md-1\">" +
                    "        <input type=\"number\" value=\"\" class=\"form-control form-control-sm unit\" id=\"unit"+unique_id+"\" name=\"slab[" + current_parent + "][" + active_row + "][unit]\" step='.001' required oninput='numberOnly(this.id)'>" +
                    "    </div>" +
                    "    <div class=\"form-group col-md-3\">" +
                    "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm charges\" id=\"charges"+unique_id+"\" name=\"slab[" + current_parent + "][" + active_row + "][charges]\" step='.001' required max='1000' oninput='numberOnly(this.id)'>" +
                    "    </div>" +
                    "    <div class=\"form-group col-md-1\">\n" +
                    "       <button class=\"btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight\" type=\"button\" onclick=\"slab_remove_row(" + unique_id + ")\"'><strong>-</strong></button>\n" +
                    "    </div>\n";
            }else if(current_type_value == "RIDE_TIME"){
                var row_for_weight =
                    "  <div class=\"form-row mt-0\" id=\"add-row-content-" + unique_id + "\">\n" +
                    "    <div class=\"form-group col-md-2\">\n" +
                    "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm from\" id=\"from"+unique_id+"\" name=\"slab[" + current_parent + "][" + active_row + "][from]\" step='.001' required oninput='numberOnly(this.id)'>" +
                    "    </div>\n" +
                    "    <div class=\"form-group col-md-2\">\n" +
                    "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm to\" id=\"to"+unique_id+"\" name=\"slab[" + current_parent + "][" + active_row + "][to]\" step='.001' required oninput='numberOnly(this.id)'>" +
                    "    </div>\n " +
                    "    <div class=\"form-group col-md-1\">" +
                    "        <input type=\"number\" value=\"\" class=\"form-control form-control-sm unit\" id=\"unit"+unique_id+"\" name=\"slab[" + current_parent + "][" + active_row + "][unit]\" step='.001' required oninput='numberOnly(this.id)'>" +
                    "    </div>" +
                    "    <div class=\"form-group col-md-3\">" +
                    "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm charges\" id=\"charges"+unique_id+"\" name=\"slab[" + current_parent + "][" + active_row + "][charges]\" step='.001' required max='1000' oninput='numberOnly(this.id)'>" +
                    "    </div>" +
                    "    <div class=\"form-group col-md-1\">\n" +
                    "       <button class=\"btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight\" type=\"button\" onclick=\"slab_remove_row(" + unique_id + ")\"'><strong>-</strong></button>\n" +
                    "    </div>\n";
            }

            $("#slab_div_"+current_parent).append(row_for_weight);
            $("#slab_div_"+current_parent).attr('sr_number',active_row);
        });

        $('document').ready(function(){
            $('#add_parent_div').click(function(){
                var current_type_value = $("#type_value").val();
                console.log(current_type_value);
                var current_rows = parseInt($("#parent_div").attr('sr_number'));
                var active_row = current_rows + 1;
                var unique_id = Date.now();
                var row_for_weight =
                    "<div id=\"add-parent-row-content-"+active_row+unique_id+"\"><hr>" +
                    "<div class=\"row\">" +
                    "<div class=\"col-md-5\">\n" +
                    "       <label for=\"weekdays"+unique_id+"\">Select Week Days<span class=\"text-danger\">*</span></label>" +
                    "     <div class=\"form-group\">" +
                    "           <div class=\"weekDays-selector\">" +
                    "             <input type=\"checkbox\" name=\"slab["+active_row+"][week_days][]\" value=\"MON\" id=\"weekday_mon_"+unique_id+"\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_mon_"+unique_id+"\">Mon</label>" +
                    "             <input type=\"checkbox\" name=\"slab["+active_row+"][week_days][]\" value=\"TUE\" id=\"weekday_tue_"+unique_id+"\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_tue_"+unique_id+"\">Tue</label>" +
                    "             <input type=\"checkbox\" name=\"slab["+active_row+"][week_days][]\" value=\"WED\" id=\"weekday_wed_"+unique_id+"\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_wed_"+unique_id+"\">Wed</label>" +
                    "             <input type=\"checkbox\" name=\"slab["+active_row+"][week_days][]\" value=\"THU\" id=\"weekday_thu_"+unique_id+"\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_thu_"+unique_id+"\">Thu</label>" +
                    "             <input type=\"checkbox\" name=\"slab["+active_row+"][week_days][]\" value=\"FRI\" id=\"weekday_fri_"+unique_id+"\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_fri_"+unique_id+"\">Fri</label>" +
                    "             <input type=\"checkbox\" name=\"slab["+active_row+"][week_days][]\" value=\"SAT\" id=\"weekday_sat_"+unique_id+"\" class=\"weekday mr-1 ml-1\">" +
                    "             <label for=\"weekday_sat_"+unique_id+"\">Sat</label>" +
                    "             <input type=\"checkbox\" name=\"slab["+active_row+"][week_days][]\" value=\"SUN\" id=\"weekday_sun_"+unique_id+"\" class=\"weekday mr-1 ml-1\">" +
                    "            <label for=\"weekday_sun_"+unique_id+"\">Sun</label>" +
                    "           </div>\n" +
                    "     </div>\n" +
                    "</div>\n" +
                    "<div class=\"col-md-2\">" +
                    "<div class=\"form-group\">" +
                    "<label for=\"start_time\">Start Time<span class=\"text-danger\">*</span></label>" +
                    "<input type=\"time\" name=\"slab[" + active_row + "][from_time]\" value=\"\" class=\"form-control\" id=\"from_time_"+unique_id+"\" required>" +
                    "</div>" +
                    "</div>" +
                    "<div class=\"col-md-2\">" +
                    "<label for=\"end_time\">End Time<span class=\"text-danger\">*</span></label>" +
                    "<div class=\"form-group\">" +
                    "<input type=\"time\" name=\"slab[" + active_row + "][to_time]\"  value=\"\" class=\"form-control\" id=\"to_time_"+unique_id+"\" required>" +
                    "</div>" +
                    "</div>" +
                    "<div class=\"form-group col-md-1\">\n" +
                    "<button class=\"btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight\" type=\"button\" onclick=\"parent_remove_row(" + active_row+unique_id + ")\"'><strong>-</strong></button>\n" +
                    "</div>\n" +
                    "</div>";

                if(current_type_value == "DISTANCE"){
                    row_for_weight +=
                        "<div id=\"slab_div_"+active_row+"\" sr_number='1' parent_number=\""+active_row+"\">" +
                        "  <div class=\"form-row mt-0\">\n" +
                        "    <div class=\"form-group col-md-2\">\n" +
                        "       <label for=\"distance_from"+unique_id+"\">Distance From<span class=\"text-danger\">*</span></label>" +
                        "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm distance_from\" id=\"distance_from"+unique_id+"\" name=\"slab[" + active_row + "][1][distance_from]\" step='.001' required oninput='numberOnly(this.id)'>" +
                        "    </div>\n" +
                        "    <div class=\"form-group col-md-2\">\n" +
                        "       <label for=\"distance_to"+unique_id+"\">Distance To<span class=\"text-danger\">*</span></label>" +
                        "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm distance_to\" id=\"distance_to"+unique_id+"\" name=\"slab[" + active_row + "][1][distance_to]\" step='.001' required oninput='numberOnly(this.id)'>" +
                        "    </div>\n " +
                        "    <div class=\"form-group col-md-2\">" +
                        "       <label for=\"unit"+unique_id+"\">Unit<span class=\"text-danger\">*</span></label>" +
                        "        <input type=\"number\" value=\"\" class=\"form-control form-control-sm unit\" id=\"unit"+unique_id+"\" name=\"slab[" + active_row + "][1][unit]\" step='.001' required oninput='numberOnly(this.id)'>" +
                        "    </div>" +
                        "    <div class=\"form-group col-md-3\">" +
                        "       <label for=\"charges"+unique_id+"\">Charges<span class=\"text-danger\">*</span></label>" +
                        "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm charges\" id=\"charges"+unique_id+"\" name=\"slab[" + active_row + "][1][charges]\" step='.001' required max='1000' oninput='numberOnly(this.id)'>" +
                        "    </div>" +
                        "    <div class=\"form-group col-md-1\">\n" +
                        "       <button class=\"btn btn-dark mt-4 mr-2 rounded-circle slab_add_row\" id=\"slab_add_row_"+unique_id+"\" parent_number=\""+active_row+"\" type=\"button\">+</button>" +
                        "    </div>\n" +
                        "</div>\n";
                }else if(current_type_value == "BASE_FARE"){
                    row_for_weight +=
                        "<div id=\"slab_div_"+active_row+"\" sr_number='1' parent_number=\""+active_row+"\">" +
                        "  <div class=\"form-row mt-0\">\n" +
                        "    <div class=\"form-group col-md-3\">\n" +
                        "       <label for=\"base_fare"+unique_id+"\">Base Fare<span class=\"text-danger\">*</span></label>" +
                        "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm base_fare\" id=\"base_fare"+unique_id+"\" name=\"slab[" + active_row + "][1][base_fare]\" step='.001' required oninput='numberOnly(this.id)'>" +
                        "    </div>\n" +
                        "    <div class=\"form-group col-md-3\">\n" +
                        "       <label for=\"free_distance"+unique_id+"\">Free Distance<span class=\"text-danger\">*</span></label>" +
                        "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm free_distance\" id=\"free_distance"+unique_id+"\" name=\"slab[" + active_row + "][1][free_distance]\" step='.001' required oninput='numberOnly(this.id)'>" +
                        "    </div>\n " +
                        "    <div class=\"form-group col-md-3\">" +
                        "       <label for=\"free_time"+unique_id+"\">Fee Time<span class=\"text-danger\">*</span></label>" +
                        "        <input type=\"number\" value=\"\" class=\"form-control form-control-sm free_time\" id=\"free_time"+unique_id+"\" name=\"slab[" + active_row + "][1][free_time]\" step='.001' required oninput='numberOnly(this.id)'>" +
                        "    </div>" +
                        "</div>\n";
                }else if(current_type_value == "RIDE_TIME"){
                    row_for_weight +=
                        "<div id=\"slab_div_"+active_row+"\" sr_number='1' parent_number=\""+active_row+"\">" +
                        "  <div class=\"form-row mt-0\">\n" +
                        "    <div class=\"form-group col-md-2\">\n" +
                        "       <label for=\"from"+unique_id+"\">From<span class=\"text-danger\">*</span></label>" +
                        "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm from\" id=\"from"+unique_id+"\" name=\"slab[" + active_row + "][1][from]\" step='.001' required oninput='numberOnly(this.id)'>" +
                        "    </div>\n" +
                        "    <div class=\"form-group col-md-2\">\n" +
                        "       <label for=\"to"+unique_id+"\">To<span class=\"text-danger\">*</span></label>" +
                        "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm to\" id=\"to"+unique_id+"\" name=\"slab[" + active_row + "][1][to]\" step='.001' required oninput='numberOnly(this.id)'>" +
                        "    </div>\n " +
                        "    <div class=\"form-group col-md-2\">" +
                        "       <label for=\"unit"+unique_id+"\">Unit<span class=\"text-danger\">*</span></label>" +
                        "        <input type=\"number\" value=\"\" class=\"form-control form-control-sm unit\" id=\"unit"+unique_id+"\" name=\"slab[" + active_row + "][1][unit]\" step='.001' required oninput='numberOnly(this.id)'>" +
                        "    </div>" +
                        "    <div class=\"form-group col-md-3\">" +
                        "       <label for=\"charges"+unique_id+"\">Charges<span class=\"text-danger\">*</span></label>" +
                        "       <input type=\"number\" value=\"\" class=\"form-control form-control-sm charges\" id=\"charges"+unique_id+"\" name=\"slab[" + active_row + "][1][charges]\" step='.001' required max='1000' oninput='numberOnly(this.id)'>" +
                        "    </div>" +
                        "    <div class=\"form-group col-md-1\">\n" +
                        "       <button class=\"btn btn-dark mt-4 mr-2 rounded-circle slab_add_row\" id=\"slab_add_row_"+unique_id+"\" parent_number=\""+active_row+"\" type=\"button\">+</button>" +
                        "    </div>\n" +
                        "</div>\n";
                }

                $('#parent_div').append(row_for_weight);
                $("#parent_div").attr('sr_number',active_row);
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
