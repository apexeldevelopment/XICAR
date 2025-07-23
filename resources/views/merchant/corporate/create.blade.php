@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('corporate.index') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.add_corporate")
                    </h3>
                </div>
                @php
                    $id = isset($corporate->id) ? $corporate->id : NULL;
                    $required = !empty($id) ? "" : "required"
                @endphp
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification" id="corporate-form" name="corporate-form"
                              enctype="multipart/form-data" action="{{ route('corporate.store',$id) }}">
                            @csrf
                            {!! Form::hidden('id',$id,array("id" => "corporate_id")) !!}
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.name") :<span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="corporate_name"
                                               name="corporate_name"
                                               value="{{old('corporate_name',isset($corporate->corporate_name) ? $corporate->corporate_name : NULL)}}"
                                               placeholder="" required>
                                        @if ($errors->has('corporate_name'))
                                            <label class="text-danger">{{ $errors->first('corporate_name') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.country")</label>
                                        <select class="form-control" name="country" id="country"
                                                required>
                                            <option value="">@lang("$string_file.select")</option>
                                            @foreach($countries  as $country)
                                                <option data-min="{{ $country->maxNumPhone }}"
                                                        data-max="{{ $country->maxNumPhone }}"
                                                        value="{{ $country->id }}" @if(!empty($corporate) && $corporate->country_id == $country->id) selected @endif>{{  $country->CountryName }}({{ $country->phonecode }})</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('country'))
                                            <label class="text-danger">{{ $errors->first('country') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.segment")</label>
                                        <select class="form-control" name="segment_id" id="segment">
                                            <option value="">@lang("$string_file.select")</option>
                                            @foreach($segments as $segment)
                                                <option value="{{$segment->id}}" @if(!empty($corporate) && $corporate->segment_id == $segment->id) selected @endif>{{$segment->name}}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('segment_id'))
                                            <label class="text-danger">{{ $errors->first('segment_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.price_type")<span class="text-danger">*</span></label>
                                        <select class="form-control" name="price_type" id="price_type" onChange="checkPriceType(this.value)">
                                            <option value="">@lang("$string_file.select")</option>
                                            <option value="1" @if(!empty($corporate) && $corporate->price_type == 1) selected @endif>@lang("$string_file.price_card")</option>
                                            <option value="2" @if(!empty($corporate) && $corporate->price_type == 2) selected @endif>@lang("$string_file.fixed")</option>
                                            <option value="3" @if(!empty($corporate) && $corporate->price_type == 3) selected @endif>@lang("$string_file.discount")</option>
                                        </select>
                                        @if ($errors->has('price_type'))
                                            <label class="text-danger">{{ $errors->first('price_type') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4 custom-hidden" id="price_card_amount_div">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.price") or @lang("$string_file.percentage")<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="price_card_amount" id="price_card_amount" @if(!empty($corporate)) value="{{$corporate->price_card_amount}}" @endif>
                                        @if ($errors->has('price_card_amount'))
                                            <label class="text-danger">{{ $errors->first('price_card_amount') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.phone")<span class="text-danger">*</span>
                                        </label>
                                        {{--                                        {{p($corporate->Country->phonecode)}}--}}
                                        <input type="text" class="form-control" id="user_phone"
                                               name="phone" value="{{old('corporate_name',isset($corporate->corporate_phone) ? str_replace($corporate->Country->phonecode,"",$corporate->corporate_phone) : NULL)}}"
                                               placeholder="" required>
                                        @if ($errors->has('phone'))
                                            <label class="text-danger">{{ $errors->first('phone') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.email")<span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" id="email"
                                               name="email" placeholder="" value="{{old('corporate_name',isset($corporate->email) ? $corporate->email : NULL)}}"
                                               required>
                                        @if ($errors->has('email'))
                                            <label class="text-danger">{{ $errors->first('email') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.address")<span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="address"
                                               name="address"
                                               placeholder="" value="{{old('corporate_address',isset($corporate->corporate_address) ? $corporate->corporate_address : NULL)}}"
                                               required>
                                        @if ($errors->has('address'))
                                            <label class="text-danger">{{ $errors->first('address') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.logo")<span class="text-danger">*</span>
                                        </label>
                                        <input type="file" class="form-control" id="corporate_logo"
                                               name="corporate_logo"
                                                {{$required}}>
                                        @if ($errors->has('corporate_logo'))
                                            <label class="text-danger">{{ $errors->first('corporate_logo') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.password")<span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="password"
                                               name="password" placeholder=""
                                                {{$required}}>
                                        @if ($errors->has('password'))
                                            <label class="text-danger">{{ $errors->first('password') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.confirm_password")<span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="password_confirmation"
                                               name="password_confirmation" placeholder=""
                                                {{$required}}>
                                        @if ($errors->has('password_confirmation'))
                                            <label class="text-danger">{{ $errors->first('password_confirmation') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions right" style="margin-bottom: 3%">
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="wb-check-circle"></i> @lang("$string_file.save")
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function checkPriceType(val){
            console.log('val: '+val);
            if(val == "" || val == 1){
                $('#price_card_amount_div').hide();
            }else{
                $('#price_card_amount_div').show();
            }
        }

        $(document).ready(function(){
            var pricetype = $('#price_type').val();
            checkPriceType(pricetype);
        });
    </script>
@endsection
