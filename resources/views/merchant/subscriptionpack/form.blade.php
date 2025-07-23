@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('subscription.index') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px"><i class="wb-reply"></i>
                                </button>
                            </a>
                            @if(!empty($info_setting) && $info_setting->add_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin:10px" data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title">
                        <i class=" wb-user-plus" aria-hidden="true"></i>
                        {{$title}}
                    </h3>
                </header>
                @php $id = !empty($package_edit->id) ? $package_edit->id : NULL; @endphp
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" enctype="multipart/form-data" action="{{ $submit_url }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="package_name">
                                        @lang("$string_file.package_name") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::text('name',old('name', isset($package_edit) && !empty($package_edit) ? (!empty($package_edit->LangSubscriptionPackageSingle) ? $package_edit->LangSubscriptionPackageSingle['name'] : $package_edit->LangSubscriptionPackageAny['name']) : ''),['class'=>'form-control','id'=>'package_name','required'=>true,'placeholder'=>""]) !!}
                                    @if ($errors->has('name'))
                                        <label class="text-danger">{{ $errors->first('name') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area">
                                        @lang("$string_file.area")
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('country_area_id',add_blank_option($arr_area,trans("$string_file.select")),old('areas',isset($package_edit) && !empty($package_edit) ? $package_edit->country_area_id : null),['class'=>'form-control select2','required'=>true,'id'=>"country_area_id",'onChange'=>"getSegment()"]) !!}
                                    @if ($errors->has('country_area_id'))
                                        <label class="text-danger">{{ $errors->first('country_area_id')
                                                            }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="for">
                                        @lang("$string_file.subscription") @lang("$string_file.for") :
                                    </label>
                                    <select name="for" id="for" class="form-control" onchange="showPriceType(this.value)">

                                        @if($subscription_creation_for == 3)
                                            <option value="2" @if(isset($package_edit)) @if($package_edit->package_for == 1) selected @endif @endif >@lang("$string_file.driver")</option>
                                            <option value="1" @if(isset($package_edit)) @if($package_edit->package_for == 1) selected @endif @endif >@lang("$string_file.user")</option>
                                        @elseif($subscription_creation_for == 2)
                                            <option value="2" @if(isset($package_edit)) @if($package_edit->package_for == 1) selected @endif @endif >@lang("$string_file.driver")</option>
                                        @elseif($subscription_creation_for == 1)
                                            <option value="1" @if(isset($package_edit)) @if($package_edit->package_for == 1) selected @endif @endif >@lang("$string_file.user")</option>
                                        @endif
                                    </select>
                                    @if ($errors->has('image'))
                                        <label class="text-danger">{{ $errors->first('subscription') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="segment">
                                        @lang("$string_file.segment")
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('segment_id',$arr_segments,old('areas',isset($package_edit) && !empty($package_edit) ? $package_edit->segment_id : null),['class'=>'form-control select2','required'=>true,"id"=>"area_segment"]) !!}
                                    @if ($errors->has('segment_id'))
                                        <label class="text-danger">{{ $errors->first('areas')}}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location6">@lang("$string_file.package_type") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('package_type',$package_type,old('package_type',isset($package_edit) && !empty($package_edit) ? $package_edit->package_type : 2),['class'=>'form-control','id'=>'package_type']) !!}
                                    @if ($errors->has('package_type'))
                                        <label class="text-danger">{{ $errors->first('package_type') }}</label>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_trip">
                                        @lang("$string_file.maximum_rides_order_bookings") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::number('max_trip',old('max_trip', isset($package_edit) && !empty($package_edit) ? $package_edit->max_trip : ''),['class'=>'form-control','id'=>'max_trip','required'=>true,'placeholder'=>"",'min'=>1]) !!}
                                    @if ($errors->has('max_trip'))
                                        <label class="text-danger">{{ $errors->first('max_trip') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4" id="price_type_div" style="@if(isset($package_edit)) @if($package_edit->package_for == 2) display: none; @endif @endif">
                                <div class="form-group">
                                    <label for="for">
                                        @lang("$string_file.price") @lang("$string_file.type"):
                                    </label>
                                    <select name="price_type" id="price_type" class="form-control">
                                        <option value="1" @if(isset($package_edit)) @if($package_edit->price_type == 1) selected @endif @endif >@lang("$string_file.fixed")</option>
                                        <option value="2" @if(isset($package_edit)) @if($package_edit->package_for == 2) selected @endif @endif >@lang("$string_file.percentage")</option>
                                    </select>
                                    @if ($errors->has('image'))
                                        <label class="text-danger">{{ $errors->first('price_type') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4" id="amount_type_div" style="@if(isset($package_edit)) @if($package_edit->package_for == 2) display: none; @endif @endif">
                                <div class="form-group">
                                    <label for="for">
                                        @lang("$string_file.amount"):
                                    </label>
                                    <input type="number" class="form-control" name="amount" id="amount" step="0.1" @if(isset($package_edit)) value="{{$package_edit->amount}}" @endif>
                                    @if ($errors->has('image'))
                                        <label class="text-danger">{{ $errors->first('price_type') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="package_price">
                                        @lang("$string_file.price") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    @php $disable = false;@endphp
                                    @if(isset($package_edit) && !empty($package_edit->id) && $package_edit->package_type == 1)
                                        @php $disable = true;@endphp
                                    @endif
                                    {!! Form::number('price',old('price', isset($package_edit) && !empty($package_edit) ? $package_edit->price : ''),['class'=>'form-control','id'=>'package_price','required'=>true,'placeholder'=>"",'min'=>0,'disabled'=>$disable]) !!}
                                    @if ($errors->has('price'))
                                        <label class="text-danger">{{ $errors->first('price') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location6">@lang("$string_file.duration") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('package_duration',$all_durations,old('package_duration',isset($package_edit->package_duration_id) ? $package_edit->package_duration_id : null),['class'=>'form-control','id'=>'location6','required'=>true]) !!}
                                    @if ($errors->has('package_duration'))
                                        <label class="text-danger">{{ $errors->first('package_duration') }}</label>
                                    @endif
                                </div>
                            </div>

                            {{--<div class="row">--}}
                            {{--<div class="col-md-4">--}}
                            {{--<div class="form-group">--}}
                            {{--<label for="location3">@lang("$string_file.select_services") :--}}
                            {{--<span class="text-danger">*</span>--}}
                            {{--</label>--}}
                            {{--<ul class="list-unstyled" style="display:inline;">--}}
                            {{--@foreach($all_services as $all_service)--}}
                            {{--<li>--}}
                            {{--<div class="checkbox">--}}
                            {{--<label class="checkbox-inline">--}}
                            {{--<input name="services[]" class="category" value="{{ $all_service['id'] }}" type="checkbox" @if(isset($selected_services) && in_array($all_service->id, $selected_services))checked="checked" @endif>--}}
                            {{--{{$all_service->serviceName}}--}}
                            {{--</label>--}}
                            {{--</div>--}}
                            {{--</li>--}}
                            {{--@endforeach--}}
                            {{--</ul>--}}
                            {{--@if ($errors->has('services'))--}}
                            {{--<label class="text-danger">{{ $errors->first('services') }}</label>--}}
                            {{--@endif--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--<div class="col-md-4">--}}
                            {{--<div class="form-group">--}}
                            {{--<label for="expire_date">--}}
                            {{--@lang("$string_file.expire_date") :--}}
                            {{--</label>--}}
                            {{--{!! Form::text('expire_date',old('expire_date', isset($package_edit) && !empty($package_edit) ? $package_edit->expire_date : ''),['class'=>'form-control customDatePicker1','id'=>'expire_date','placeholder'=>"",'autocomplete'=>'off']) !!}--}}
                            {{--@if ($errors->has('expire_date'))--}}
                            {{--<label class="text-danger">{{ $errors->first('expire_date') }}</label>--}}
                            {{--@endif--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{-- </div>--}}
                            {{-- <div class="row">--}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="image">
                                        @lang("$string_file.image") :
                                    </label>
                                    <input type="file" class="form-control" id="image" name="image" placeholder="@lang(" $string_file.image")">
                                    @if ($errors->has('image'))
                                        <label class="text-danger">{{ $errors->first('image') }}</label>
                                    @endif
                                </div>
                            </div>
                            {{--</div>--}}
                            <!-- <div class="row"> -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="message">
                                        @lang("$string_file.description") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="message" name="description" rows="4" placeholder="" required>{{ old('description',(isset($package_edit) && !empty($package_edit)) ? (!empty($package_edit->LangSubscriptionPackageSingle) ? $package_edit->LangSubscriptionPackageSingle['description'] : $package_edit->LangSubscriptionPackageAny['description']) : '') }}</textarea>
                                    @if ($errors->has('description'))
                                        <label class="text-danger">{{ $errors->first('description') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions float-right">
                            @if($id == NULL || $edit_permission)
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                                </button>
                            @endif
                        </div>
                    </form>
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
        var area_id = $("#country_area_id option:selected").val();
        var user_type = $("#for option:selected").val();
        if (area_id != "" && user_type == "2") {
            $("#loader1").show();
            var token = $('[name="_token"]').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: '<?php echo route('get.area.segment') ?>',
                data: {
                    area_id: area_id
                },
                success: function(data) {
                    $("#area_segment").empty();
                    $('#area_segment').html(data);
                }
            });
            $("#loader1").hide();
        }
        else if(area_id != "" && user_type == "1"){
            let data = "<option value=''>Select</option><option value='1'>Taxi</option><option value='2'>Delivery</option>";
            $('#area_segment').html(data);
        }
    }

    function showPriceType(val){
    getSegment();
        if(val == 1){
            $('#price_type_div').css("display", "block");
            $('#amount_type_div').css("display", "block");
        }
        else{
            $('#price_type_div').css("display", "none");
            $('#amount_type_div').css("display", "none");
        }
    }

    $(document).ready(function() {
        let elementFor = $('#for').val();
        showPriceType(elementFor);
    });
    </script>
@endsection