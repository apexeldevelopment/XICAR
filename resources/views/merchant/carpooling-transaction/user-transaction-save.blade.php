@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('merchant.carpool.user.transaction')}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.edit") @lang("$string_file.cashout")</h3>
                </header>
                <div class="panel-body container-fluid" id="validation">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data" ,files="true" ,
                          action="{{route('merchant.carpool.user.transaction.update',['id'=>$user_cashout->id])}}" autocomplete="false">
                        @csrf
                        <fieldset>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.wallet") @lang("$string_file.amount")
                                </label>
                                <br>
                                <label for="">{{ $user_cashout->User->Country->isoCode.' '.$user_cashout->User->wallet_balance }}</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.cashout") @lang("$string_file.amount")

                                </label>
                                <br>
                                <label for="">{{$user_cashout->User->Country->isoCode.' '.$user_cashout->amount}}</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.current") @lang("$string_file.cashout") @lang("$string_file.status")

                                </label>
                                <br>
                                @switch($user_cashout->cashout_status)
                                    @case(0)
                                    <small class="badge badge-round badge-warning float-left">@lang("$string_file.pending")</small>
                                    @break;
                                    @case(1)
                                    <small class="badge badge-round badge-info float-left">@lang("$string_file.success")</small>
                                    @break;
                                    @case(2)
                                    <small class="badge badge-round badge-danger float-left">@lang("$string_file.rejected")</small>
                                    @break;
                                    @default
                                    ----
                                @endswitch
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.status")
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" name="status"
                                        id="status">
                                    <option value="">@lang("$string_file.select") @lang("$string_file.option")</option>
                                    <option value="0" @if($user_cashout->cashout_status == 0) selected @endif>@lang("$string_file.pending")</option>
                                    <option value="1" @if($user_cashout->cashout_status == 1) selected @endif >@lang("$string_file.success")</option>
                                    <option value="2" @if($user_cashout->cashout_status == 2) selected @endif>@lang("$string_file.rejected")</option>
                                </select>
                                @if ($errors->has('status'))
                                    <label class="text-danger">{{ $errors->first('status') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.action") @lang("$string_file.by")
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="action_by"
                                       name="action_by"
                                       placeholder="@lang("$string_file.enter") @lang("$string_file.action")"
                                       value="{{ old('action_by',$user_cashout->action_by) }}" required>
                                @if ($errors->has('action_by'))
                                    <label class="text-danger">{{ $errors->first('action_by') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.comment")
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="comment"
                                       name="comment"
                                       placeholder="@lang("$string_file.enter") @lang("$string_file.comment")"
                                       value="{{ old('comment',$user_cashout->comment) }}" required>
                                @if ($errors->has('comment'))
                                    <label class="text-danger">{{ $errors->first('comment') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.transaction") @lang("$string_file.id")
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="transaction_id"
                                       name="transaction_id"
                                       placeholder="@lang("$string_file.transaction") @lang("$string_file.id")"
                                       value="{{ old('transaction_id',$user_cashout->transaction_id) }}" required>
                                @if ($errors->has('transaction_id'))
                                    <label class="text-danger">{{ $errors->first('transaction_id') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                        </fieldset>
                    <div class="form-actions right" style="margin-bottom: 3%">
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                        </button>
                    </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
