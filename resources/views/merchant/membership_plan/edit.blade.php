@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
           @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('merchant.membershipPlan.index') }}">
                            <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.add_membership_plan")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" name="hotel-form" id="hotel-form"
                          enctype="multipart/form-data" action="{{ route('merchant.membershipPlan.update',['id'=> $plan->id]) }}">
                        {{method_field('PUT')}}
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.plan_title") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="plan_title"
                                           name="plan_title"  value="@if(!empty($plan->LanguageMerchantMembershipPlanSingle)) {{ $plan->LanguageMerchantMembershipPlanSingle->plan_title }} @endif"
                                           placeholder="" required>
                                    @if ($errors->has('plan_title'))
                                        <label class="text-danger">{{ $errors->first('plan_title') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.plan_name") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="plan_name"
                                           name="plan_name" value="@if(!empty($plan->LanguageMerchantMembershipPlanSingle)) {{ $plan->LanguageMerchantMembershipPlanSingle->plan_name }} @endif"
                                           placeholder="" required>
                                    @if ($errors->has('plan_name'))
                                        <label class="text-danger">{{ $errors->first('plan_name') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.price") :</label>
                                    <input type="text" class="form-control" id="price"
                                    name="price" value="{{$plan->price}}"
                                    placeholder="" required>
                                    @if ($errors->has('price'))
                                        <label class="text-danger">{{ $errors->first('price') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.plan_type") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="plan_type" id="plan_type"
                                    required>
                                        <option value="">@lang("$string_file.select")</option>
                                        <option value="1" {{$plan->plan_type == 1 ? 'selected': ''}}>@lang("$string_file.period_based")</option>
                                        <option value="2" {{$plan->plan_type == 2 ? 'selected': ''}}>@lang("$string_file.order_based")</option>
                                    </select>
                                    @if ($errors->has('plan_type'))
                                        <label class="text-danger">{{ $errors->first('plan_type') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="show-order col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.number_order"):
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="number_order"
                                           name="number_order" value="{{$plan->number_of_order}}"
                                           placeholder="">
                                    @if ($errors->has('number_order'))
                                        <label class="text-danger">{{ $errors->first('number_order') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="lastName3">
                                            @lang("$string_file.number_period"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="period"
                                               name="period" value="{{$plan->period}}"
                                               placeholder="" required>
                                        @if ($errors->has('period'))
                                            <label class="text-danger">{{ $errors->first('period') }}</label>
                                        @endif
                                    </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.max_amount_valid"):
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="max_amount_valid"
                                           name="max_amount_valid" value="{{$plan->max_amount_valid}}"
                                           placeholder="" required>
                                    @if ($errors->has('max_amount_valid'))
                                        <label class="text-danger">{{ $errors->first('max_amount_valid') }}</label>
                                    @endif
                                </div>
                        </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.description") :
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="description"
                                           name="description" value="@if(!empty($plan->LanguageMerchantMembershipPlanSingle)) {{ $plan->LanguageMerchantMembershipPlanSingle->description }} @endif"
                                           placeholder="" required>
                                    @if ($errors->has('description'))
                                        <label class="text-danger">{{ $errors->first('description') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions float-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="wb-check-circle"></i> @lang("$string_file.save")
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
  <script>
    $(document).ready(function () {
        var plan_type  = $("#plan_type option:selected").val();
        if(plan_type == 2){
            $('.show-order').show();
        }else{
            $('.show-order').hide();
        }
        
    });
    $(document).on('change','#plan_type',function(e){
        var plan_type  = $("#plan_type option:selected").val();
        console.log($(this).val(), plan_type);
        if($(this).val() == 1){
            $('.show-order').hide();
        }else if($(this).val() == 2){
            $('.show-order').show()
        }else{
            $('.show-order').hide();
        }
    });
  </script>
@endsection