<h4>@lang("$string_file.pricing_parameter")</h4>
<hr>
@foreach ($parameters as $parameter)
    @php
        $checked = in_array($parameter->id, array_column($saved_parameter, 'pricing_parameter_id')) ? 'checked' : '';

        $checked_val = NULL;
        $disabled = '';
        $free_val = NULL;
        $child_val = NULL;
        $checkbox_value_type = NULL;
        if (in_array($parameter->parameterType, [6, 9, 13])) {
            if (!empty($checked)) {
                $key = array_search($parameter->id, array_column($saved_parameter, 'pricing_parameter_id'));
                $free_val = isset($saved_parameter[$key]['free_value']) ? $saved_parameter[$key]['free_value'] : NULL;
            }
        }
        if (in_array($parameter->parameterType, [13])) {
            if (!empty($checked)) {
                $checkbox_value_type = isset($saved_parameter[$key]['value_type']) ? $saved_parameter[$key]['value_type'] : NULL;
            }
        }
        if (in_array($parameter->parameterType, [11])) {
            if (!empty($checked)) {
                $keys = array_search($parameter->id, array_column($saved_parameter, 'pricing_parameter_id'));
                $checkbox_discount_type = isset($saved_parameter[$keys]['discount_value_type']) ? $saved_parameter[$keys]['discount_value_type'] : NULL;
            }
        }
        if (in_array($parameter->parameterType, [23])) {
            if (!empty($checked)) {
                $keys = array_search($parameter->id, array_column($saved_parameter, 'pricing_parameter_id'));
                $checkbox_additional_fare_type = isset($saved_parameter[$keys]['additional_fare_value_type']) ? $saved_parameter[$keys]['additional_fare_value_type'] : NULL;
            }
        }
        if ($parameter->parameterType == 10 && (!empty($price_card->base_fare) || (isset($price_card->base_fare_price_card_slab_id) && !empty($price_card->base_fare_price_card_slab_id)))) {
            $checked = "checked";
        } else {
            if (!empty($checked)) {
                $key = array_search($parameter->id, array_column($saved_parameter, 'pricing_parameter_id'));
                $checked_val = isset($saved_parameter[$key]['parameter_price']) ? $saved_parameter[$key]['parameter_price'] : NULL;
                $child_val = isset($saved_parameter[$key]['price_card_slab_id']) ? $saved_parameter[$key]['price_card_slab_id'] : NULL;
            }
        }
        if (empty($checked)) {
            $disabled = "disabled";
        }
    @endphp
    {{--Input By Driver--}}
    @if ($price_type == 3)
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="emailAddress5">{!! $parameter->ParameterName !!}:</label>
                    <input type="checkbox" id="input_provider" name="input_provider[]" value="{!! $parameter->id !!}" {!! $checked !!}>
                </div>
            </div>
        </div>
    @else
        @switch ($parameter->parameterType)
            @case(6)
                <div class="row" style="margin-bottom: 10px">
                    <div class="col-md-4">
                        <label for="ProfileImage">{!! $parameter->ParameterName !!}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                         <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                    </div>
                                </div>
                            </div>
                            <input value="{!! $checked_val !!}" type="number" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder="@lang('admin.message164')" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="ProfileImage">@lang("$string_file.base_fare") {!! $parameter->ParameterName !!}:</label>
                        <div class="form-group">
                            <div class="input-group-prepend">
                                 <input type="number" value="{!! $free_val !!}" class="form-control"  step="0.01" min="0" id="checkboxFreeArray"  name="checkboxFreeArray[{!! $parameter->id !!}]"  placeholder="">
                            </div>
                        </div>
                    </div>
                </div>
            @break
            @case(10)
                @php
                    $base_fare = !empty($price_card->base_fare) ? $price_card->base_fare : NULL;
                    $free_time = !empty($price_card->base_fare) ? $price_card->free_time : NULL;
                    $free_distance = !empty($price_card->base_fare) ? $price_card->free_distance : NULL;
                    $base_fare_price_card_slab_id = !empty($price_card->base_fare_price_card_slab_id) ? $price_card->base_fare_price_card_slab_id : NULL;
                @endphp
                <div class="row" style="margin-bottom: 10px">
                    <div class="col-md-4">
                        <label for="ProfileImage"> {!! $parameter->ParameterName !!}:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="basefareArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                         <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                    </div>
                                </div>
                            </div>
                            <input type="number" value="{!! $base_fare !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="base_fare" placeholder="" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="ProfileImage">@lang("$string_file.distance_included") {!! $parameter->ParameterName !!}:</label>
                        <div class="input-group">
                            <input type="number" step="0.01" value="{!! $free_distance !!}" class="form-control" min="0" id="freedistance" name="free_distance">
                            <div class="input-group-append">
                                <span class="input-group-text">{!! $distance_unit_type !!}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="ProfileImage">@lang("$string_file.time_included") {!! $parameter->ParameterName !!}:</label>
                        <div class="form-group">
                            <div class="input-group-prepend">
                                 <input type="number" value="{!! $free_time !!}" class="form-control" min="0" id="freetime"  name="free_time"  placeholder="" step="any">
                            </div>
                        </div>
                    </div>
                </div>
                @if(isset($config->slab_price_card) && $config->slab_price_card == 1)
                    @php
                        $price_card_slabs = \App\Models\PriceCardSlab::where("merchant_id",$parameter->merchant_id)->where("type","BASE_FARE")->get()->pluck("name","id")->toArray();
                        $price_card_slabs = add_blank_option($price_card_slabs);
                    @endphp
                    @if(!empty($price_card_slabs))
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <label>@lang("$string_file.or")</label>
                            </div>
                        </div>
                        <div class="row" style="margin-bottom: 10px">
                            <div class="col-md-4">
                                <label for="ProfileImage">{!! $parameter->ParameterName !!} @lang("$string_file.slab"):</label>
                                <div class="input-group">
                                    <select class="form-control" id="test-child{!! $parameter->id !!}" name="base_fare_price_card_slab_id" {!! $disabled !!}>
                                        @foreach($price_card_slabs as $key => $value)
                                            @php $optioned = $base_fare_price_card_slab_id == $key ? "selected" : ""; @endphp
                                            <option value="{!! $key !!}" {!! $optioned !!}>{!! $value !!}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            @break
            @case(9)
                <div class="row" style="margin-bottom: 10px">
                    <div class="col-md-4">
                        <label for="ProfileImage">{!! $parameter->ParameterName !!}:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                         <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                    </div>
                                </div>
                            </div>
                            <input type="number" value="{!! $checked_val !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder="" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="ProfileImage">@lang("$string_file.free_time_included"):</label>
                        <div class="form-group">
                            <div class="input-group-prepend">
                                 <input type="number" value="{!! $free_val !!}" min="0" class="form-control"  id="checkboxFreeArray"  name="checkboxFreeArray[{!! $parameter->id !!}]"  placeholder="">
                            </div>
                        </div>
                    </div>
                </div>
            @break
            @case(13)
                <div class="row" style="margin-bottom: 10px">
                    <div class="col-md-4">
                        <label for="ProfileImage">{!! $parameter->ParameterName !!}:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                         <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                    </div>
                                </div>
                            </div>
                            <input type="number" value="{!! $checked_val !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder="" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="ProfileImage">@lang("$string_file.type"):</label>
                        <div class="form-group">
                            <div class="input-group-prepend">
                                <select class="form-control" name="checkbox_value_type[{!! $parameter->id !!}]">
                                    <option value="1" @if(!empty($checkbox_value_type) && $checkbox_value_type == 1) selected @endif>@lang("$string_file.percentage")</option>
                                    <option value="2" @if(!empty($checkbox_value_type) && $checkbox_value_type == 2) selected @endif>@lang("$string_file.fixed")</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="ProfileImage">@lang("$string_file.please") @lang("$string_file.enter") {!! $parameter->ParameterName !!} @lang("$string_file.number"):</label>
                        <div class="form-group">
                            <div class="input-group-prepend">
                                 <input type="number" value="{!! $free_val !!}" step="0.01" min="0" class="form-control"  id="checkboxFreeArray"  name="checkboxFreeArray[{!! $parameter->id !!}]"  placeholder="">
                            </div>
                        </div>
                    </div>
                </div>
            @break
            @case(14)
                <div class="row" style="margin-bottom: 10px">
                    <div class="col-md-4">
                        <label for="ProfileImage">{!! $parameter->ParameterName !!}:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                         <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                    </div>
                                </div>
                            </div>
                            <input type="number" value="{!! $checked_val !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder="Enter AC Charges" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="ProfileImage">@lang("$string_file.charges") @lang("$string_file.type"):</label>
                        <div class="form-group">
                             <select  class="form-control"id="checkboxFreeArray"  name="checkboxFreeArray[{!! $parameter->id !!}]"  ><option value="1">' . @lang($string_file . '.nominal') . '</option><option value="2">' . @lang("$string_file.per_km") . '</option></select>
                        </div>
                    </div>
                </div>
            @break
            @case(15)
            <div class="row" style="margin-bottom: 10px">
                <div class="col-md-4">
                    <label for="ProfileImage">{!! $parameter->ParameterName !!}:</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                     <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                </div>
                            </div>
                        </div>
                        <input type="number" value="{!! $checked_val !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder="" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="ProfileImage">@lang("$string_file.maximum") @lang("$string_file.distance"):</label>
                    <div class="form-group">
                        <div class="input-group-prepend">
                             <input type="number" min="0" class="form-control"  id="checkboxFreeArray"  name="checkboxFreeArray[{!! $parameter->id !!}]"  placeholder="">
                        </div>
                    </div>
                </div>
            </div>
            @break
            @case(1)
                <div class="row" style="margin-bottom: 10px">
                    <div class="col-md-4">
                        <label for="ProfileImage">{!! $parameter->ParameterName !!}:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                         <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                    </div>
                                </div>
                            </div>
                            <input type="number" value="{!! $checked_val !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder="" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                            <div class="input-group-append">
                                <span class="input-group-text">{!! $distance_unit_type !!} @lang("$string_file.charges")</span>
                            </div>
                        </div>
                    </div>
                </div>
                @if(isset($config->slab_price_card) && $config->slab_price_card == 1)
                    @php
                        $price_card_slabs = \App\Models\PriceCardSlab::where("merchant_id",$parameter->merchant_id)->where("type","DISTANCE")->get()->pluck("name","id")->toArray();
                        $price_card_slabs = add_blank_option($price_card_slabs);
                    @endphp
                    @if(!empty($price_card_slabs))
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <label>@lang("$string_file.or")</label>
                            </div>
                        </div>
                        <div class="row" style="margin-bottom: 10px">
                            <div class="col-md-4">
                                <label for="ProfileImage">{!! $parameter->ParameterName !!} @lang("$string_file.slab"):</label>
                                <div class="input-group">
                                    <select class="form-control" id="test-child{!! $parameter->id !!}" name="check_box_child[{!! $parameter->id !!}]" {!! $disabled !!}>
                                    @foreach($price_card_slabs as $key => $value)
                                        @php $optioned = !empty($child_val) && $child_val == $key ? "selected" : ""; @endphp
                                        <option value="{!! $key !!}" {!! $optioned !!}>{!! $value !!}</option>
                                    @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            @break
            @case(8)
                <div class="row" style="margin-bottom: 10px">
                    <div class="col-md-4">
                        <label for="ProfileImage">{!! $parameter->ParameterName !!}:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                        <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                    </div>
                                </div>
                            </div>
                            <input type="number" value="{!! $checked_val !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder="" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                        </div>
                    </div>
                </div>
                @if(isset($config->slab_price_card) && $config->slab_price_card == 1)
                @php
                    $price_card_slabs = \App\Models\PriceCardSlab::where("merchant_id",$parameter->merchant_id)->where("type","RIDE_TIME")->get()->pluck("name","id")->toArray();
                    $price_card_slabs = add_blank_option($price_card_slabs);
                @endphp
                @if(!empty($price_card_slabs))
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <label>@lang("$string_file.or")</label>
                        </div>
                    </div>
                    <div class="row" style="margin-bottom: 10px">
                        <div class="col-md-4">
                            <label for="ProfileImage">{!! $parameter->ParameterName !!} @lang("$string_file.slab"):</label>
                            <div class="input-group">
                                <select class="form-control" id="test-child{!! $parameter->id !!}" name="check_box_child[{!! $parameter->id !!}]" {!! $disabled !!}>
                                    @foreach($price_card_slabs as $key => $value)
                                        @php $optioned = !empty($child_val) && $child_val == $key ? "selected" : ""; @endphp
                                        <option value="{!! $key !!}" {!! $optioned !!}>{!! $value !!}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
            @break
            @case(11)
            <div class="row" style="margin-bottom: 10px">
                <div class="col-md-4">
                    <label for="ProfileImage">{!! $parameter->ParameterName !!}:</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                     <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                </div>
                            </div>
                        </div>
                        <input type="number" value="{!! $checked_val !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder="" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="ProfileImage">@lang("$string_file.discount_type"):</label>
                    <div class="form-group">
                        <div class="input-group-prepend">
                            <select class="form-control" name="checkbox_discount_type[{!! $parameter->id !!}]">
                                <option value="2" @if(!empty($checkbox_discount_type) && $checkbox_discount_type == 2) selected @endif>@lang("$string_file.fixed")</option>
                                <option value="1" @if(!empty($checkbox_discount_type) && $checkbox_discount_type == 1) selected @endif>@lang("$string_file.percentage")</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @break
            @case(23)
            <div class="row" style="margin-bottom: 10px">
                <div class="col-md-4">
                    <label for="ProfileImage">{!! $parameter->ParameterName !!}:</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                     <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                </div>
                            </div>
                        </div>
                        <input type="number" value="{!! $checked_val !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder=""aria-describedby="checkbox-addon1" {!! $disabled !!}>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="ProfileImage">@lang("$string_file.additional_fare"):</label>
                    <div class="form-group">
                        <div class="input-group-prepend">
                            <select class="form-control" name="checkbox_additional_fare_type[{!! $parameter->id !!}]">
                                <option value="2" @if(!empty($checkbox_additional_fare_type) && $checkbox_additional_fare_type == 2) selected @endif>@lang("$string_file.fixed")</option>
                                <option value="1" @if(!empty($checkbox_additional_fare_type) && $checkbox_additional_fare_type == 1) selected @endif>@lang("$string_file.percentage")</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @break
            @default
            <div class="row" style="margin-bottom: 10px">
                <div class="col-md-4">
                    <label for="ProfileImage">{!! $parameter->ParameterName !!}:</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" onclick="invisibleInput(this.value)" id="{!! $parameter->id !!}" name="checkboxArray[{!! $parameter->id !!}]" value="{!! $parameter->id !!}" {!! $checked !!}>
                                     <label class="custom-control-label" for="{!! $parameter->id !!}"></label>
                                </div>
                            </div>
                        </div>
                        <input type="number" value="{!! $checked_val !!}" step="0.01" min="0" class="form-control" id="test{!! $parameter->id !!}" onkeypress="return NumberInput(event)" name="check_box_values[{!! $parameter->id !!}]" placeholder="" aria-describedby="checkbox-addon1" {!! $disabled !!}>
                    </div>
                </div>
            </div>
        @endswitch
        <hr style="border-style: dashed;">
    @endif
@endforeach
