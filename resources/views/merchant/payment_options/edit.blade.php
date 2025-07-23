@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                      
                        <a href="{{ route('merchant.payment-option') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.payment_option_translation") </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          action="{{route('merchant.payment-option.update',$payment->id)}}">
                        @php $required = false; @endphp
                        @csrf
                        <fieldset>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.name")<span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="payment_option_name"
                                               name="payment_option_name"
                                               value="{{$payment->name}}"
                                               placeholder="" required>
                                        @if ($errors->has('payment_option_name'))
                                            <label class="danger">{{ $errors->first('payment_option_name') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.translation")<span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="payment_option_translation"
                                               name="payment_option_translation"
                                               value="{{$payment->PaymentOptionTranslation ? $payment->PaymentOptionTranslation->name : '' }}"
                                               placeholder="" required>
                                        @if ($errors->has('payment_option_translation'))
                                            <label class="danger">{{ $errors->first('payment_option_translation') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <div class="form-actions float-right">
                            @if($edit_permission)
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-square-o"></i> @lang("$string_file.update")
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
