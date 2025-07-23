@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @if(session('reward'))
                <div class="alert dark alert-icon alert-info alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <i class="icon wb-info" aria-hidden="true"></i>{{ session('reward') }}
                </div>
            @endif
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('reward-gifts.index') }}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang('admin.message530')"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                        @lang("$string_file.edit") @lang("$string_file.reward_gifts")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification" action="{{ route('reward-gifts.update',$reward_gift->id) }}" enctype="multipart/form-data">
                            @csrf
                            <fieldset>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.app")</label>
                                            <select class="form-control" name="application" id="application">
                                                <option value=""> @lang("$string_file.select") </option>
                                                <option value="1" {{$reward_gift->application == 1 ? 'selected' : ""}} >@lang("$string_file.user")</option>
                                                <option value="2" {{$reward_gift->application == 2 ? 'selected' : ""}}>@lang("$string_file.driver")</option>
                                            </select>
                                            <span class="text-danger">{{ $errors->first('application')  }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.select") @lang("$string_file.country")</label>
                                            <select class="form-control" name="country">
                                                <option value=""> @lang("$string_file.select") </option>
                                                @foreach($countries as $country)
                                                    <option value="{{$country->id}}"
                                                        {{(old('country',$reward_gift->country_id) == $country->id) ? ' selected' : ''}}
                                                    >
                                                        {{ $country->CountryName  }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger">{{ $errors->first('country')  }}</span>

                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.name")</label>
                                            <input type="text" class="form-control" name="name" value="{{$reward_gift->name}}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.reward_points")</label>
                                            <input type="number" class="form-control" name="reward_points" value="{{$reward_gift->reward_points}}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.trips")</label>
                                            <input type="number" class="form-control" name="trips" value="{{$reward_gift->rides}}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.image")</label>
                                            <input type="file" class="form-control" name="image"/>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <img src="{{ get_image($reward_gift->image,'reward_gift',$merchant_id)  }}" width="80px" height="70px" alt="reward-image">
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.amount")</label>
                                            <input type="number" class="form-control" name="amount" value="{{$reward_gift->amount}}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.status")</label>
                                            <select class="form-control" name="status" id="status">
                                                <option value=""> @lang("$string_file.select") </option>
                                                <option value="1" {{$reward_gift->status == 1 ? 'selected' : ""}} >@lang("$string_file.active")</option>
                                                <option value="2" {{$reward_gift->status == 2 ? 'selected' : ""}}>@lang("$string_file.deactivated")</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.comment")</label>
                                            <input type="text" class="form-control" name="comment" value="{{$reward_gift->comment}}"/>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="form-actions right" style="margin-bottom: 3%">
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.update")
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
