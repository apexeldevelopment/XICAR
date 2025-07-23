@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content container-fluid">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <div class="panel-heading">
                <div class="panel-actions">
                    @if(!empty($info_setting) && $info_setting->add_text != "")
                    <button class="btn btn-icon btn-primary float-right" style="margin:10px" data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                        <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                    </button>
                    @endif
                    <a href="{{ route('cancel.policies') }}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="wb-reply"></i>
                        </button>
                    </a>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                            @lang("$string_file.add")
                        </h3>
                    </div>
                </div>
            </div>

            @php $id = $cancel_policy ? $cancel_policy->id : null @endphp

            <div class="panel-body container-fluid">
                <form method="POST" enctype="multipart/form-data" class="steps-validation wizard-notification" enctype="multipart/form-data" action="{{ route('cancel.policy.store',$id) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-control-label" for="application">
                                    @lang("$string_file.policy_for")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select("application",add_blank_option([2=>trans("$string_file.driver")]),old("application",isset($cancel_policy->application) ? $cancel_policy->application : 2),["class"=>"form-control select2 search-input","id"=>"application"]) !!}
                                @if ($errors->has('application'))
                                <label class="text-danger">{{ $errors->first('application') }}</label>
                                @endif
                            </div>
                        </div>


                        <!-- </div>

                    <div class="row"> -->


                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-control-label" for="country_area_id">@lang("$string_file.area")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select("country_area_id",add_blank_option($arr_areas),old("country_area_id",isset($cancel_policy->country_area_id) ? $cancel_policy->country_area_id : null),["class"=>"form-control select2 search-input","id"=>"country_area_id"]) !!}
                                @if ($errors->has('country_area_id'))
                                <label class="text-danger">{{ $errors->first('country_area_id') }}</label>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-3" id="segment_id_div">
                            <div class="form-group">
                                <label class="form-control-label" for="application">
                                    @lang("$string_file.segment")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select("segment_id",$arr_segment,old("segment_id",isset($cancel_policy->segment_id) ? $cancel_policy->segment_id : null),["class"=>"form-control select2 search-input","id"=>"segment_id"]) !!}
                                @if ($errors->has('segment_id'))
                                <label class="text-danger">{{ $errors->first('segment_id') }}</label>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-control-label" for="location3">@lang("$string_file.charges_type")
                                    <span class="text-danger">*</span>
                                </label>

                                {!! Form::select("charge_type",[1=>trans("$string_file.fixed_amount"),2=>trans("$string_file.percentage")],old("charge_type",isset($cancel_policy->charge_type) ? $cancel_policy->charge_type : null),["class"=>"form-control select2 search-input","id"=>"charge_type","onchange"=>"changeChargeType()"]) !!}
                                {{--<select name="charge_type" id="charge_type" class="form-control select2" required onchange="changeChargeType()">--}}
                                    {{--<option value="1" id="offer_type_fixed_amount">@lang("$string_file.fixed_amount")</option>--}}
                                    {{--<option value="2" id="offer_type_discount">@lang("$string_file.percentage")</option>--}}
                                {{--</select>--}}

                                @if ($errors->has('charge_type'))
                                <label class="text-danger">{{ $errors->first('charge_type') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-control-label" for="location3">@lang("$string_file.cancellation_charges")
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="offer_value_symbol"></span>
                                    </div>
                                    <input type="number" step="0.01" min="0" class="form-control" id="cancellation_charges" name="cancellation_charges" value="{{old("cancellation_charges",isset($cancel_policy->cancellation_charges) ? $cancel_policy->cancellation_charges : "")}}" placeholder="" autocomplete="off" required />
                                </div>
                                @if ($errors->has('offer_value'))
                                    <label class="text-danger">{{ $errors->first('cancellation_charges') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3" id="">
                            <div class="form-group">
                                <label class="form-control-label" for="service_type">
                                    @lang("$string_file.service_type")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select("service_type",add_blank_option([1=>trans("$string_file.now"),2=>trans("$string_file.later")]),old("service_type",isset($cancel_policy->service_type) ? $cancel_policy->service_type : ""),["class"=>"form-control","id"=>"service_type"]) !!}
                                @if ($errors->has('service_type'))
                                    <label class="text-danger">{{ $errors->first('service_type') }}</label>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label" for="location3">@lang("$string_file.free_time") (@lang("$string_file.in_minutes"))

                                    <span class="text-danger" id="service_now">(@lang("$string_file.cancel_now")) *</span>
                                    <span class="text-danger custom-hidden" id="service_later">(@lang("$string_file.cancel_later")) *</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="free_time"></span>
                                    </div>
                                    <input type="number" step=0.01 min=0 class="form-control" id="free_time" name="free_time" value="{{old("free_time",isset($cancel_policy->free_time) ? $cancel_policy->free_time : "")}}" placeholder="" autocomplete="off" required />
                                </div>
                                @if ($errors->has('offer_value'))
                                    <label class="text-danger">{{ $errors->first('offer_value') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-control-label" for="title">@lang("$string_file.title")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text("title",old("title",isset($cancel_policy->LanguageSingle->title) ? $cancel_policy->LanguageSingle->title : ""),["class"=>"form-control","id"=>"title","required"=> true,"maxlength"=>150]) !!}
                                @if ($errors->has('title'))
                                    <label class="text-danger">{{ $errors->first('title') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label" for="description">@lang("$string_file.cancellation_alert")/ @lang("$string_file.description")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text("description",old("description",isset($cancel_policy->LanguageSingle->description) ? $cancel_policy->LanguageSingle->description : ""),["class"=>"form-control","id"=>"description","required"=> true,"maxlength"=>"500","rows"=> 3]) !!}
                                @if ($errors->has('description'))
                                    <label class="text-danger">{{ $errors->first('description') }}</label>
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="form-actions float-right" style="margin-bottom: 1%">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check-circle"></i> @lang("$string_file.save") </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="currency" value="" />
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
<script>
    $(document).ready(function() {
        // disableAllControl();
        $(document).on("change", "#country_area_id", function() {

            // $("#loader1").show();
            var token = $('[name="_token"]').val();
            let country_area_id = $(this).val();

            // $("#segment_id_div").empty();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: '<?php echo route('get.area.segment') ?>',
                data: {
                    area_id: country_area_id,
                    // option_type: "CHECK-BOX"
                },
                success: function(data) {
                    // console.log(data);
                    // $("#segment_id_div").empty();
                    $('#segment_id').html(data);
                }
            });
            // $("#loader1").hide();
            // }
        });
    });

    $(document).on("change","#service_type",function(){
        var id = $("#service_type").val();
        switch (id) {
            case "1":
                $('#cancel_later').hide();
                $('#cancel_now').show();

                break;
            case "2":
                $('#cancel_now').hide();
                $('#cancel_later').show();
                break;
        }
    });



    function changeChargeType() {

        var offer_type = $("#charge_type").val();
        if (offer_type == 2) {
            $("#maximum_offer_amount").attr("disabled", false);
            $("#offer_value_symbol").html("%");
        } else if (offer_type == 1) {
            $("#maximum_offer_amount").attr("disabled", true);
            $("#offer_value_symbol").html($("#currency").val());
        } else {
            $("#maximum_offer_amount").attr("disabled", true);
        }
    }


</script>
@endsection