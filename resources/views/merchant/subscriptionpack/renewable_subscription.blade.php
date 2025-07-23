@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                    </div>
                    <h4 class="panel-title">
                        <i class=" icon fa-gears" aria-hidden="true"></i>
                        @lang("$string_file.renewable_subscriptions")

                    </h4>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          action="{{ route('merchant.renewable.subscription.store', ["id"=> (!empty($subscription)? $subscription->id : NULL)]) }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="stop_name">
                                        @lang("$string_file.name") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::text('name',isset($subscription)? $subscription->getNameAttribute() : old('name'),['id'=>'name','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                    @if ($errors->has('renewable_subs_name'))
                                        <label class="text-danger">{{ $errors->first('renewable_subs_name') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="service_area">
                                        @lang("$string_file.service_area") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('country_area_id',$arr_area,isset($subscription) ? $subscription->country_area_id : old('country_area_id'),['id'=>'country_area_id','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('country_area_id'))
                                        <label class="text-danger">{{ $errors->first('country_area_id') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vehicle_type">
                                        @lang("$string_file.vehicle_type") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('vehicle_type_id',$arr_vehicle_types,isset($subscription) ? $subscription->vehicle_type_id : old('vehicle_type_id'),['id'=>'vehicle_type_id','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('vehicle_type_id'))
                                        <label class="text-danger">{{ $errors->first('vehicle_type_id') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="description">
                                        @lang("$string_file.description") :
                                    </label>
                                    {!! Form::textArea('description',isset($subscription)? $subscription->getDescriptionAttribute() : old('name'),['id'=>'description','class'=>'form-control','required'=>true,'placeholder'=>'' , 'rows'=>4 , 'columns'=>20]) !!}
                                    @if ($errors->has('description'))
                                        <label class="text-danger">{{ $errors->first('description') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div>
                            <h5 style="margin-top: 40px;">
                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                @lang("$string_file.subscription_slabs")
                            </h5>
                            @php
                                $values =  !empty($subscription->RenewableSubscriptionValue) ? $subscription->RenewableSubscriptionValue  : [];
                                $vars_count = count($values);
                                if ($vars_count == 0) $vars_count = 1;
                            @endphp
                            <input type="hidden" name="slab_count" id="slab_count" value="{{ $vars_count }}">

                            <div id="subscription-container">
                                @if (count($values)>0)
                                    @foreach ($values as $index => $value)
                                        <div class="subscription-row" data-row-id="{{ $index + 1 }}">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="min_fare_{{ $index + 1 }}">@lang("$string_file.min_fare")<span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control" name="min_fare[]" id="min_fare_{{ $index + 1 }}" placeholder="@lang("$string_file.min_fare")" value="{{ $value->min_fare }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="max_fare_{{ $index + 1 }}">@lang("$string_file.max_fare")<span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control" name="max_fare[]" id="max_fare_{{ $index + 1 }}" placeholder="@lang("$string_file.max_fare")" value="{{ $value->max_fare }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="subscription_fee_{{ $index + 1 }}">@lang("$string_file.subscription_fee")<span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control" name="subscription_fee[]" id="subscription_fee_{{ $index + 1 }}" placeholder="@lang("$string_file.subscription_fee")" value="{{ $value->subscription_fee }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group mt-4">
                                                        <button type="button" class="btn btn-success add-row">@lang("$string_file.add")</button>
                                                        <button type="button" class="btn btn-danger remove-row">@lang("$string_file.remove")</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    {{-- Default empty row if no values exist --}}
                                    <div class="subscription-row" data-row-id="1">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="min_fare_1">@lang("$string_file.min_fare")<span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="min_fare[]" id="min_fare_1" placeholder="@lang("$string_file.min_fare")" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="max_fare_1">@lang("$string_file.max_fare")<span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="max_fare[]" id="max_fare_1" placeholder="@lang("$string_file.max_fare")" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="subscription_fee_1">@lang("$string_file.subscription_fee")<span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="subscription_fee[]" id="subscription_fee_1" placeholder="@lang("$string_file.subscription_fee")" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mt-4">
                                                    <button type="button" class="btn btn-success add-row">@lang("$string_file.add")</button>
                                                    <button type="button" class="btn btn-danger remove-row">@lang("$string_file.remove")</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                        </div>


                        <div class="form-actions right" style="margin-bottom: 3%">
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-square-o"></i> @lang("$string_file.save")
                                </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <select name="" id="">

        <option value="option">option</option>
    </select>


    {{-- @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text']) --}}
@endsection
@section('js')

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const container = document.getElementById("subscription-container");
            let rowCount = document.querySelectorAll(".subscription-row").length; // Start count from existing rows

            // Add new row
            document.addEventListener("click", function (event) {
                if (event.target.classList.contains("add-row")) {
                    rowCount++;

                    let newRow = event.target.closest(".subscription-row").cloneNode(true);
                    newRow.setAttribute("data-row-id", rowCount);

                    // Update input names and clear values
                    newRow.querySelectorAll("input").forEach((input) => {
                        let baseName = input.getAttribute("name").replace("[]", "");
                        input.setAttribute("id", baseName + "_" + rowCount);
                        input.value = ""; // Clear previous value
                    });

                    container.appendChild(newRow);

                    let last_count = parseInt($('#slab_count').val(), 10);
                    last_count++;
                    $('#slab_count').val(last_count);
                }
            });

            // Remove row
            document.addEventListener("click", function (event) {
                if (event.target.classList.contains("remove-row")) {
                    let row = event.target.closest(".subscription-row");
                    if (document.querySelectorAll(".subscription-row").length > 1) {
                        row.remove();

                        let last_count = parseInt($('#slab_count').val(), 10);
                        last_count--;
                        $('#slab_count').val(last_count);
                    } else {
                        alert("At least one row is required.");
                    }
                }
            });
        });

    </script>
@endsection