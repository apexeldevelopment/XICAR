<h5>@lang("$string_file.service_wise_commission")</h5>
<hr>
<div class="row">
    @foreach($arr_services as $service)
        @php
            $amount = isset($service->HandymanCommissionDetail) ? $service->HandymanCommissionDetail->amount : NULL;
            $detail_id = isset($service->HandymanCommissionDetail) ? $service->HandymanCommissionDetail->id : NULL;
        @endphp
{{--        @dd($service->serviceName($merchant_id));--}}
    <div class="col-md-4">
        <div class="form-group">
            <label for="firstName3">
               {{!empty($service->serviceName($merchant_id)) ? $service->serviceName($merchant_id) : $service->serviceName}}
                <span class="text-danger">*</span>
            </label>
            {!! Form::hidden('detail_id['.$service->id.']',old('detail_id',$detail_id),['class'=>'form-control','id'=>'detail_id','placeholder'=>"",'required'=>true]) !!}
            {!! Form::number('service_amount['.$service->id.']',old('service_amount',$amount),['class'=>'form-control','id'=>'sequence_number','placeholder'=>"",'required'=>true,'min'=>0, 'step'=>0.1]) !!}

            @if ($errors->has('service_amount'))
                @dd($service);
                <label class="text-danger">{{ $errors->first('service_amount') }}</label>
            @endif
        </div>
    </div>
@endforeach
</div>
