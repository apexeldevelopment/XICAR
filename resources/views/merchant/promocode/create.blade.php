@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
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
                        <a href="{{ route('promocode.index') }}">
                            <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                </div>
                <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                    @lang("$string_file.promo_code")
                </h3>
            </header>
            @php $id = isset($promocode->id) ? $promocode->id : NULL;@endphp
            <div class="panel-body container-fluid">
                <section id="validation">
                    <form method="POST" class="steps-validation wizard-notification" id="promocode-form" name="promocode-form" enctype="multipart/form-data" action="{{ route('promocode.store',$id) }}">
                        @csrf
                        {!! Form::hidden('id',$id) !!}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.service_area")
                                        <span class="text-danger">*</span>
                                    </label>
                                    @if(!empty($id))
                                    {!! Form::text('area_id',$promocode->CountryArea->CountryAreaName,['class'=>"form-control",'disabled'=>true]) !!}
                                    {!! Form::hidden('area',$promocode->country_area_id,[]) !!}
                                    @else
                                    <select class="form-control" name="area" id="area" onchange="getSegment(this.value)" {{--                                                    onchange="getServices(this.value)" --}} required>
                                        <option value="">--@lang("$string_file.select")--</option>
                                        @foreach($areas as $area)
                                        <option id="{{ $area->id }}" value="{{ $area->id }}">{{ $area->CountryAreaName}}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('area'))
                                    <label class="text-danger">{{ $errors->first('area') }}</label>
                                    @endif
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.segment")
                                        <span class="text-danger">*</span>
                                    </label>
                                    @if(!empty($id))
                                    {!! Form::text('seg_id',($promocode->segment_id != NULL) ? $segment_list[$promocode->segment_id] : "---",['class'=>"form-control",'disabled'=>true]) !!}
                                    {!! Form::hidden('segment_id',$promocode->segment_id,[]) !!}
                                    @else
                                    {!! Form::select('segment_id',add_blank_option($segment_list,trans("$string_file.select")),old('segment_id'),array('class' => 'form-control','required'=>true,'id'=>'segment_id','onChange'=>'getBusinessSegment()')) !!}
                                    @if ($errors->has('segment_id'))
                                    <label class="text-danger">{{ $errors->first('segment_id') }}</label>
                                    @endif
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4 @if(empty($promocode->id))custom-hidden @else @endif" id="business_segment">
                                <div class="form-group">
                                    <label for="">
                                        @lang("$string_file.business_segment")
                                    </label>
                                    @if(!empty($id))
                                    {!! Form::text('business_seg_id',($promocode->business_segment_id) ? $promocode->BusinessSegment->full_name : "---",['class'=>"form-control",'disabled'=>true]) !!}
                                    {!! Form::hidden('business_segment_id',$promocode->business_segment_id,[]) !!}
                                    @else
                                    {{ Form::select('business_segment_id', [],old('business_segment_id', isset($promocode->business_segment_id ) ? $promocode->business_segment_id  : ''), ['class' => 'form-control', 'id' => 'business_segment_id'])}}
                                    @if ($errors->has('business_segment_id'))
                                    <label class="text-danger">{{ $errors->first('business_segment_id') }}</label>
                                    @endif
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.promo_code")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="promocode" name="promocode" placeholder="FIRSTDELFREE" value="{{ old('promocode',isset($promocode->promoCode) ? $promocode->promoCode : NULL) }}" required>
                                    @if ($errors->has('promocode'))
                                    <label class="text-danger">{{ $errors->first('promocode') }}</label>
                                    @endif
                                </div>
                            </div>
                            <!-- </div>
                        <div class="row"> -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.type")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="promo_code_value_type" id="promo_code_value_type" onchange="changeText(this.value)" required>
                                        <option id="1" value="1" @if(!empty($id) && $promocode->promo_code_value_type == 1) selected @endif> @lang("$string_file.flat")</option>
                                        <option id="2" value="2" @if(!empty($id) && $promocode->promo_code_value_type == 2) selected @endif> @lang("$string_file.percentage")</option>
                                    </select>
                                    @if ($errors->has('promo_code_value_type'))
                                    <label class="text-danger">{{ $errors->first('promo_code_value_type') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.discount")<span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min=0 class="form-control" id="promo_code_value" name="promo_code_value" placeholder="" value="{{ old('promo_code_value',isset($promocode->promo_code_value) ? $promocode->promo_code_value : NULL) }}" required>
                                    @if ($errors->has('promo_code_value'))
                                    <label class="text-danger">{{ $errors->first('promo_code_value') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.description")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="promo_code_description" name="promo_code_description" placeholder="" required>{{ old('promo_code_description',isset($promocode->promo_code_description) ? $promocode->promo_code_description : "") }}</textarea>
                                    @if ($errors->has('promo_code_description'))
                                    <label class="text-danger">{{ $errors->first('promo_code_description') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.validity")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="icheckbox_minimal checked hover active" style="position: relative;">
                                        <input type="radio" id="promo_code_validity_permanent" value="1" name="promo_code_validity" onclick="javascript:yesnoCheck()" checked @if(!empty($id) && $promocode->promo_code_validity == 1) checked @endif>
                                        <label for="promo_code_validity_permanent" class="">@lang("$string_file.permanent")</label>
                                        <input type="radio" id="promo_code_validity_custom" value="2" name="promo_code_validity" onclick="javascript:yesnoCheck()" style="margin-left: 20px;" @if(!empty($id) && $promocode->promo_code_validity == 2) checked @endif>
                                        <label for="promo_code_validity_custom" class="">@lang("$string_file.custom")</label>
                                        <input type="radio" id="promo_code_validity_conditional" value="3" name="promo_code_validity" onclick="javascript:yesnoCheck()" style="margin-left: 20px;" @if(!empty($id) && $promocode->promo_code_validity == 3) checked @endif>
                                        <label for="promo_code_validity_conditional" class="">@lang("$string_file.conditional")</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4" id="vehicle_type_div" style="display: none;">
                                <div class="form-group">
                                <label for="vehicle_type">
                                        @lang("$string_file.vehicle_type")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="select2 form-control" name="promo_code_vehicle_type[]" id="vehicle_type" multiple >
                                        @foreach ($vehicle_list as $vehicle)
                                            <option value="{{ $vehicle->id }}"
                                                @if(!empty($promocode->promo_code_vehicle_type) && in_array($vehicle->id, json_decode($promocode->promo_code_vehicle_type, true))) 
                                                    selected 
                                                @endif>
                                                {{ $vehicle->VehicleTypeName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('vehicle_type'))
                                    <label class="text-danger">{{ $errors->first('vehicle_type') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group custom-hidden" id="start-div">
                                    <label for="emailAddress5">
                                        @lang("$string_file.start_date")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control customDatePicker1" name="start_date" placeholder="" value="{{ old('start_date', isset($promocode->start_date) ? $promocode->start_date : NULL) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group @if(empty($id) || (!empty($id) && ($promocode->promo_code_validity == 1 || $promocode->promo_code_validity == 3))) custom-hidden @endif" id="end-div">
                                    <label for="emailAddress5">
                                        @lang("$string_file.end_date")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control customDatePicker1" name="end_date" placeholder="" value="{{ old('end_date', isset($promocode->end_date) ? $promocode->end_date : NULL) }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            @php
                                $condition = null;
                                if(!empty($id) && $promocode->promo_code_validity == 3 && !empty($promocode->additional_conditions)){
                                    $condition = json_decode($promocode->additional_conditions);
                                }
                            @endphp
                            <div class="col-md-4">
                                <div class="form-group @if(empty($id) || (!empty($id) && ($promocode->promo_code_validity == 1 || $promocode->promo_code_validity == 2))) custom-hidden @endif" id="condition-div">
                                    <label for="promo_condition">
                                        @lang("$string_file.condition")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="promo_condition" id="promo_condition" onchange="handleConditions(this.value)">
                                        <option id="1" value="START_AT_SIGNUP" @if(!empty($condition) && $condition->promo_condition == "START_AT_SIGNUP") selected @endif>
                                            @lang("$string_file.start") @lang("$string_file.from")  @lang("$string_file.signup")
                                        </option>
                                        <option id="2" value="NUMBER_OF_RIDE_CONDITION" @if(!empty($condition) && $condition->promo_condition == "NUMBER_OF_RIDE_CONDITION") selected @endif>
                                            @lang("$string_file.number_of_ride_in_time")
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group @if(empty($id) || (!empty($id) && ($promocode->promo_code_validity == 1 || $promocode->promo_code_validity == 2))) custom-hidden @endif" id="no-of-day-div">
                                    <label for="no_of_days">
                                        @lang("$string_file.no_of_days")
                                        <span class="text-danger" id="no_of_days_required_mark">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="no_of_days" placeholder="" value="{{ !empty($condition) ? $condition->no_of_days : '' }}">
                                </div>
                            </div>

                            <div class="col-md-12 @if(empty($id) || (!empty($id) && !empty($condition->promo_condition) && ($condition->promo_condition != 'NUMBER_OF_RIDE_CONDITION'))) custom-hidden @endif" id="ride-condition-fields">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="ride_time">
                                                @lang("$string_file.time_limit") (in minutes)<span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="ride_time" id="ride_time" value="{{ !empty($condition) && !empty($condition->ride_time) ? $condition->ride_time : '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="number_of_rides">
                                                @lang("$string_file.number_of_rides") <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="number_of_rides" id="number_of_rides" value="{{ !empty($condition) && !empty($condition->no_of_ride) ? $condition->no_of_ride : '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.applicable_for")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="applicable_for" id="applicable_for" onchange="UserType(this.value)" required>
                                        <option value="1" @if(!empty($id) && $promocode->applicable_for == 1) selected @endif> @lang("$string_file.all_users")</option>
                                        <option value="2" @if(!empty($id) && $promocode->applicable_for == 2) selected @endif> @lang("$string_file.new_user")</option>
                                        @if($config->corporate_admin == 1)
                                        <option value="3" @if(!empty($id) && $promocode->applicable_for == 3) selected @endif>@lang("$string_file.corporate_users")</option>
                                        @endif
                                    </select>
                                    @if ($errors->has('applicable_for'))
                                    <label class="text-danger">{{ $errors->first('applicable_for') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.limit")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="promo_code_limit" name="promo_code_limit" placeholder="" value="{{ old('promo_code_limit',isset($promocode->promo_code_limit) ? $promocode->promo_code_limit : NULL) }}" required>
                                    @if ($errors->has('promo_code_limit'))
                                    <label class="text-danger">{{ $errors->first('promo_code_limit') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.limit_per_user")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="promo_code_limit_per_user" name="promo_code_limit_per_user" placeholder="" value="{{ old('promo_code_limit_per_user',isset($promocode->promo_code_limit_per_user) ? $promocode->promo_code_limit_per_user : NULL) }}" required>
                                    @if ($errors->has('promo_code_limit_per_user'))
                                    <label class="text-danger">{{ $errors->first('promo_code_limit_per_user') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.minimum_bill_amount")
                                        {{-- <span class="text-danger">*</span>--}}
                                    </label>
                                    <input type="number" class="form-control" id="order_minimum_amount" name="order_minimum_amount" placeholder="" value="{{ old('order_minimum_amount',isset($promocode->order_minimum_amount) ? $promocode->order_minimum_amount : NULL) }}">
                                    @if ($errors->has('order_minimum_amount'))
                                    <label class="text-danger">{{ $errors->first('order_minimum_amount') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.promo_percentage_maximum_discount")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="promo_percentage_maximum_discount" name="promo_percentage_maximum_discount" placeholder="" value="{{old('promo_percentage_maximum_discount',isset($promocode->promo_percentage_maximum_discount) ? $promocode->promo_percentage_maximum_discount:NULL) }}" disabled>
                                    @if ($errors->has('promo_percentage_maximum_discount'))
                                    <label class="text-danger">{{ $errors->first('promo_percentage_maximum_discount') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.promo_code_parameter")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="promo_code_name" name="promo_code_name" placeholder="" value="{{ old('promo_code_name',!empty($id) ? $promocode->PromoName : NULL) }}" required>
                                    @if ($errors->has('promo_code_name'))
                                    <label class="text-danger">{{ $errors->first('promo_code_name') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="lastName3">
                                    @lang("$string_file.default") @lang("$string_file.promo_code")
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" name="is_default_promo_code" id="is_default_promo_code">
                                    <option value=""> @lang("$string_file.select")</option>
                                    <option value="1" @if(!empty($id) && $promocode->is_default_promo_code == 1) selected @endif> @lang("$string_file.yes")</option>
                                    <option value="2" @if(!empty($id) && $promocode->is_default_promo_code == 2) selected @endif> @lang("$string_file.no")</option>
                                </select>
                                @if ($errors->has('is_default_promo_code'))
                                    <label class="text-danger">{{ $errors->first('promo_code_name') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="row @if(empty($id) || !empty($id) && $promocode->applicable_for != 3) custom-hidden @endif" id="corporate_div">
                            <div class="col-md-4 corporate_inr">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.corporate_name")</label>
                                    <select class="form-control" name="corporate_id" id="corporate_id">
                                        <option value="">--@lang("$string_file.select")--</option>
                                        @foreach($corporates as $corporate)
                                        <option value="{{ $corporate->id }}">{{ $corporate->corporate_name }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('rider_type'))
                                    <label class="text-danger">{{ $errors->first('rider_type') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions right" style="margin-bottom: 3%">
                            @if($id == NULL || $edit_permission)
                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fa fa-check-circle" onclick="return Validate()"></i>
                                @lang("$string_file.save")
                            </button>
                            @endif
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
<script>

    function handleConditions(value) {
        const rideConditionFields = document.getElementById('ride-condition-fields');
        const noOfDaysMark = document.getElementById('no_of_days_required_mark');

        if (value === 'NUMBER_OF_RIDE_CONDITION') {
            rideConditionFields.classList.remove('custom-hidden');
            noOfDaysMark.style.display = 'none';
        } else {
            rideConditionFields.classList.add('custom-hidden');
            noOfDaysMark.style.display = 'inline';
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        handleConditions(document.getElementById('promo_condition').value);
    });

    function Validate() {
        var promo_code_value_type = document.getElementById('promo_code_value_type').value;
        var promo_code_value = document.getElementById('promo_code_value').value;
        if (promo_code_value_type == 2 && promo_code_value > 100) {
            alert('Enter Value Less Then 100');
            return false;
        }
    }





    @if(!empty($id))

        document.addEventListener("DOMContentLoaded", function () {
         let val = document.getElementById("promo_code_value_type").value;
         changeText(val);
     });

    @endif

    function changeText(val) {
        let firstmsg = "";
        let firstmsg2 = "";
        if (val == "2") {
            $('#promo_percentage_maximum_discount').prop("disabled", false);
            $('#promo_code_value').attr("placeholder", firstmsg2);
        } else {
            $('#promo_percentage_maximum_discount').prop("disabled", true);
            $('#promo_code_value').attr("placeholder", firstmsg);
        }
    }

    function UserType(val) {
        if (val == "3") {
            document.getElementById('corporate_div').style.display = 'block';
        } else {
            document.getElementById('corporate_div').style.display = 'none';
        }
    }

    function yesnoCheck() {
        const rideConditionFields = document.getElementById('ride-condition-fields');
    const promoCondition = document.getElementById('promo_condition')?.value || '';
        if (document.getElementById('promo_code_validity_permanent').checked) {
            document.getElementById('start-div').style.display = 'none';
            document.getElementById('end-div').style.display = 'none';

            document.getElementById('condition-div').style.display = 'none';
            document.getElementById('no-of-day-div').style.display = 'none';
            rideConditionFields.classList.add('custom-hidden');
        }
        if (document.getElementById('promo_code_validity_custom').checked) {
            document.getElementById('start-div').style.display = 'block';
            document.getElementById('end-div').style.display = 'block';

            document.getElementById('condition-div').style.display = 'none';
            document.getElementById('no-of-day-div').style.display = 'none';
            rideConditionFields.classList.add('custom-hidden');
        }
        if (document.getElementById('promo_code_validity_conditional').checked) {
            document.getElementById('condition-div').style.display = 'block';
            document.getElementById('no-of-day-div').style.display = 'block';

            document.getElementById('start-div').style.display = 'none';
            document.getElementById('end-div').style.display = 'none';
            if (promoCondition === 'NUMBER_OF_RIDE_CONDITION') {
                rideConditionFields.classList.remove('custom-hidden');
            } else {
                rideConditionFields.classList.add('custom-hidden');
            }
        }
    }



    function getSegment(val) {
        // console.log(val);
        $('#business_segment').hide();
        $("#segment_id").empty();
        var area_id = val;
        var data = {
            area_id: area_id,
            segment_group_id: 1
        };
        $("#segment_id").append('<option value="">@lang("$string_file.select")</option>');
        @if($handyman_apply_promocode)
        data = {
            area_id: area_id
        };
        @endif
        if (area_id != "") {
            $("#loader1").show();
            var token = $('[name="_token"]').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: '<?php echo route('get.area.segment') ?>',
                data: data,
                success: function(data) {
                    $("#segment_id").empty();
                    $('#segment_id').html(data);
                }
            });
            $("#loader1").hide();
        }
    }

    function getBusinessSegment() {
        var segment_id = $('#segment_id').val();
        if(segment_id == 1 || segment_id == 2)
        {
            $('#vehicle_type_div').css('display', 'block');
        }
        else
        {
             $('#vehicle_type_div').css('display', 'none');
        }
        $('#business_segment').hide();

        $.ajax({
            type: "GET",
            data: {
                id: $('#segment_id').val(),
                area_id: $('#area').val(),
            },
            url: "{{ route('segment.get.business-segment') }}",
        }).done(function(data) {
            if (data.length == 0) {
                $('#business_segment').hide();
            } else {
                $('#business_segment').show();
                $('#business_segment_id').empty().append('<option selected="selected" value="">@lang("$string_file.select")</option>');
                $.each(data, function(i, data) {
                    var div_data = "<option value=" + i + ">" + data + "</option>";
                    $(div_data).appendTo('#business_segment_id');
                });
            }
        });
    }
</script>
@endsection
