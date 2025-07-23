@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title"><i class="wb-earning" aria-hidden="true"></i>
                        @lang("common.operator")   @lang("common.configuration") </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              action="{{ route('merchant.gateway.intouch.operator.store')}}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("common.operator") <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="operator" name="operator"
                                               placeholder=" @lang("common.enter") @lang("common.operator") "
                                               value=""
                                               required>
                                        @if ($errors->has('operator'))
                                            <label class="danger">{{ $errors->first('operator') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("common.cashin") @lang("common.service") @lang("common.id") <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="cash_in"
                                               name="cash_in"
                                               placeholder=" @lang("common.enter") @lang("common.service") @lang("common.id") "
                                               value=""
                                               required>
                                        @if ($errors->has('cash_in'))
                                            <label class="danger">{{ $errors->first('cash_in') }}</label>
                                        @endif
                                    </div>
                                </div>
                                   <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("common.cashout") @lang("common.service") @lang("common.id") <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="cash_out"
                                               name="cash_out"
                                               placeholder=" @lang("common.enter") @lang("common.service") @lang("common.id") "
                                               value=""
                                               required>
                                        @if ($errors->has('cash_out'))
                                            <label class="danger">{{ $errors->first('cash_out') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                             <div class="form-actions right" style="margin-bottom: 3%">
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-square-o"></i> Save
                                </button>
                            </div>
                        </form>
                        
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection