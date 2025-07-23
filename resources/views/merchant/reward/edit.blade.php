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
                        <a href="{{ route('reward-points.index') }}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang('admin.message530')"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                        @lang("$string_file.edit") @lang("$string_file.reward_points")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification" action="{{ route('merchant.rewardSystem.update',$reward_system->id) }}">
                            @csrf
                            <fieldset>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.app")</label>
                                            <select class="form-control" name="application" id="application" disabled>
                                                <option value=""> @lang("$string_file.select") </option>
                                                <option value="1" @if($reward_system->application == 1) selected @endif>@lang("$string_file.user")</option>
                                                <option value="2" @if($reward_system->application == 2) selected @endif>@lang("$string_file.driver")</option>
                                            </select>
                                            <span class="text-danger">{{ $errors->first('application')  }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 @if(empty($reward_system->country_id)) custom-hidden  @endif" id="country_div">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.select") @lang("$string_file.country")</label>
                                            <select class="form-control" name="country" disabled>
                                                <option value=""> @lang("$string_file.select") </option>
                                                @foreach($countries as $country)
                                                    <option value="{{$country->id}}" {{(old('country',$reward_system->country_id) == $country->id) ? ' selected' : ''}}>
                                                        {{ $country->CountryName  }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger">{{ $errors->first('country')  }}</span>

                                        </div>
                                    </div>
                                    <div class="col-md-4 @if(empty($reward_system->country_area_id)) custom-hidden @endif" id="country_area_div">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang('admin.area.select')</label>
                                            <select class="form-control" name="country_area" disabled>
                                                <option value=""> @lang('admin.select') </option>
                                                @foreach($country_areas as $area)
                                                    <option value="{{$area->id}}"
                                                            {{(old('country_area',$reward_system->country_area_id) == $area->id) ? ' selected' : ''}}
                                                    >
                                                        {{ $area->CountryAreaName  }}
                                                    </option>
                                                @endforeach

                                            </select>
                                            <span class="text-danger">{{ $errors->first('country_area')  }}</span>

                                        </div>
                                    </div>
                                </div>
                                {{--                            <hr>--}}
                                {{--                            <div class="row">    --}}
                                {{--                                <div class="col-md-2">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.message276')</label>--}}
                                {{--                                        <select class="form-control" name="rating" onchange="switchDisabled(this.value , 'rating-switch')">--}}
                                {{--                                            <option value="2"> @lang('admin.disable') </option>--}}
                                {{--                                            <option value="1" {{(old('rating',$reward_system->rating_reward) == 1) ? 'selected' : ''}}> @lang('admin.enable') </option>--}}
                                {{--                                        </select>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-3">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.rating_reward')</label>--}}
                                {{--                                        <input type="number" max="1000000" class="form-control rating-switch" name="rating_reward" value="{{old('rating_reward',$reward_system->rating_points)}}"--}}
                                {{--                                          {{(old('rating_reward',$reward_system->rating_reward) == 1) ? '' : 'disabled'}}--}}
                                {{--                                        />--}}
                                {{--                                        @if ($errors->has('rating_reward'))--}}
                                {{--                                            <label class="text-danger">{{ $errors->first('rating_reward') }}</label>--}}
                                {{--                                        @endif--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-3">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.expire_in_days')</label>--}}
                                {{--                                        <input type="number" max="1000000" class="form-control rating-switch" name="rating_expire_in_days" value="{{old('rating_expire_in_days',$reward_system->rating_expire_in_days)}}"--}}
                                {{--                                          {{(old('rating_expire_in_days',$reward_system->rating_reward) == 1) ? '' : 'disabled'}}--}}
                                {{--                                        />--}}
                                {{--                                        @if ($errors->has('rating_expire_in_days'))--}}
                                {{--                                            <label class="text-danger">{{ $errors->first('rating_expire_in_days') }}</label>--}}
                                {{--                                        @endif--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                            </div>--}}
                                {{--                            <hr>--}}
                                {{--                            <div class="row">--}}
                                {{--                                <div class="col-md-2">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.writing_comment')</label>--}}
                                {{--                                        <select class="form-control" name="writing_comment" onchange="switchDisabled(this.value , 'comment-switch')">--}}
                                {{--                                            <option value="2"> @lang('admin.disable') </option>--}}
                                {{--                                            <option value="1" {{(old('writing_comment',$reward_system->comment_reward) == 1) ? 'selected' : ''}}> @lang('admin.enable') </option>--}}
                                {{--                                        </select>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-3">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.comment_min_words')</label>--}}
                                {{--                                        <input type="number" max="1000000" class="form-control comment-switch" name="comment_min_words" value="{{old('comment_min_words',$reward_system->comment_min_words)}}"--}}
                                {{--                                          {{(old('writing_comment',$reward_system->comment_reward) == 1) ? '' : 'disabled'}}--}}
                                {{--                                        />--}}
                                {{--                                        @if ($errors->has('comment_min_words'))--}}
                                {{--                                            <label class="text-danger">{{ $errors->first('comment_min_words') }}</label>--}}
                                {{--                                        @endif--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-3">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.writing_comment_reward')</label>--}}
                                {{--                                        <input type="number" max="1000000" class="form-control comment-switch" name="comment_reward" value="{{old('comment_reward',$reward_system->comment_points)}}"--}}
                                {{--                                          {{(old('writing_comment',$reward_system->comment_reward) == 1) ? '' : 'disabled'}}--}}
                                {{--                                        />--}}
                                {{--                                        @if ($errors->has('comment_reward'))--}}
                                {{--                                            <label class="text-danger">{{ $errors->first('comment_reward') }}</label>--}}
                                {{--                                        @endif--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-3">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.expire_in_days')</label>--}}
                                {{--                                        <input type="number" max="1000000" class="form-control comment-switch" name="comment_expire_in_days" value="{{old('comment_expire_in_days',$reward_system->comment_expire_in_days)}}"--}}
                                {{--                                          {{(old('writing_comment',$reward_system->comment_reward) == 1) ? '' : 'disabled'}}--}}
                                {{--                                        />--}}
                                {{--                                        @if ($errors->has('comment_expire_in_days'))--}}
                                {{--                                            <label class="text-danger">{{ $errors->first('comment_expire_in_days') }}</label>--}}
                                {{--                                        @endif--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                            </div>--}}
                                {{--                            --}}
                                {{--                            <div id="referral_div">--}}
                                {{--                                <hr>--}}
                                {{--                                <div class="row">--}}
                                {{--                                    <div class="col-md-2">--}}
                                {{--                                        <div class="form-group">--}}
                                {{--                                            <label class="text-capitalize">@lang('admin.referral')</label>--}}
                                {{--                                            <select class="form-control" name="referral" onchange="switchDisabled(this.value , 'referral-switch')">--}}
                                {{--                                                <option value="2"> @lang('admin.disable') </option>--}}
                                {{--                                                <option value="1" {{(old('referral',$reward_system->referral_reward) == 1) ? 'selected' : ''}}> @lang('admin.enable') </option>--}}
                                {{--                                            </select>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="col-md-3">--}}
                                {{--                                        <div class="form-group">--}}
                                {{--                                            <label class="text-capitalize">@lang('admin.referral_reward')</label>--}}
                                {{--                                            <input type="number" max="1000000" class="form-control referral-switch" name="referral_reward" value="{{old('referral_reward',$reward_system->referral_points)}}"--}}
                                {{--                                              {{(old('referral',$reward_system->referral_reward) ==1) ? '' : 'disabled'}}--}}
                                {{--                                            />--}}
                                {{--                                            @if ($errors->has('referral_reward'))--}}
                                {{--                                                <label class="text-danger">{{ $errors->first('referral_reward') }}</label>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="col-md-3">--}}
                                {{--                                        <div class="form-group">--}}
                                {{--                                            <label class="text-capitalize">@lang('admin.expire_in_days')</label>--}}
                                {{--                                            <input type="number" max="1000000" class="form-control referral-switch" name="referral_expire_in_days" value="{{old('referral_expire_in_days',$reward_system->referral_expire_in_days)}}"--}}
                                {{--                                              {{(old('referral',$reward_system->referral_reward) == 1) ? '' : 'disabled'}}--}}
                                {{--                                            />--}}
                                {{--                                            @if ($errors->has('referral_expire_in_days'))--}}
                                {{--                                                <label class="text-danger">{{ $errors->first('referral_expire_in_days') }}</label>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                            </div>--}}

                                <div @if($reward_system->trip_expense_reward != 1) id="trip_expenses_div" @endif>
                                    <hr>
                                    <div class="row" >
                                        <!--<div class="col-md-2">-->
                                        <!--    <div class="form-group">-->
                                        <!--        <label class="text-capitalize">@lang("$string_file.trip_expenses")</label>-->
                                        <!--        <select class="form-control" name="trip_expenses" onchange="switchDisabled(this.value , 'expenses-switch')">-->
                                                    <!--<option value="2"> @lang("$string_file.disable") </option>-->
                                        <!--            <option value="1" {{(old('trip_expenses',$reward_system->trip_expense_reward) == 1) ? 'selected' : ''}}> @lang("$string_file.enable") </option>-->
                                        <!--        </select>-->
                                        <!--    </div>-->
                                        <!--</div>-->
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.according_to")</label>
                                                <select class="form-control" name="trips_select" id="trips_select" onchange="tripsCalc(this.value,'expenses-switch')">
                                                    <option value="">--select--- </option>
                                                    <option value="2" {{$reward_system->trip_expense_reward == 2 ? 'selected' : ''}}> @lang("$string_file.disable") </option>
                                                    <option value="1" {{$reward_system->trip_expense_reward == 1 ? 'selected' : ''}}> @lang("$string_file.trip_expenses") </option>
                                                    <option value="3" {{$reward_system->trip_expense_reward == 3 ? 'selected' : ''}}> @lang("$string_file.trip_expense_amount") </option>
                                                    <option value="4" {{$reward_system->trip_expense_reward == 4 ? 'selected' : ''}}> @lang("$string_file.no_of_trips") </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="trips_div">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.no_of_trips")</label>
                                                <input type="text" class="form-control expenses-switch" name="no_of_trips" value="{{old('no_of_trips',$reward_system->no_of_trips)}}"/>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2" id="type_div">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.trips_type")</label>
                                                <select class="form-control expenses-switch" name="trips_type">
                                                    <option value="1" {{$reward_system->trips_type == 1 ? 'selected' : ''}}> @lang("$string_file.one_time") </option>
                                                    <option value="2" {{$reward_system->trips_type == 2 ? 'selected' : ''}}> @lang("$string_file.recurring") </option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2" id="expense_div">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.trip_expense_amount")</label>
                                                <input type="text" class="form-control expenses-switch" name="trip_expense_amount" value="{{old('trip_expense_amount',$reward_system->expense_amount)}}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="point_trips">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.points_trip")</label>
                                                <input type="text" class="form-control expenses-switch" name="point_against_trips" value="{{old('point_against_trips',$reward_system->point_against_trips)}}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-3" >
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.per_point_amount")</label>
                                                <input type="number" min="0" step="0.01" max="1000000" class="form-control expenses-switch" name="per_point_amount" value="{{old('per_point_amount',$reward_system->amount_per_points)}}"
                                                        
                                                />
                                                @if ($errors->has('per_point_amount'))
                                                    <label class="text-danger">{{ $errors->first('per_point_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <!--<div class="col-md-2">-->
                                        <!--    <div class="form-group">-->
                                        <!--        <label class="text-capitalize">@lang("$string_file.reward_value")</label>-->
                                        <!--        <input type="text" class="form-control" name="reward_value" value="{{old('reward_value',$reward_system->reward_value)}}" readonly/>-->
                                        <!--    </div>-->
                                        <!--</div>-->
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.expire_in_days")</label>
                                                <input type="number" max="1000000" class="form-control expenses-switch" name="expenses_expire_in_days" value="{{old('expenses_expire_in_days',$reward_system->expenses_expire_in_days)}}"
        
                                                />
                                                @if ($errors->has('expenses_expire_in_days'))
                                                    <label class="text-danger">{{ $errors->first('expenses_expire_in_days') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.status")</label>
                                                <select class="form-control" name="status" id="status">
                                                    <option value="">--select--- </option>
                                                    <option value="1" {{$reward_system->status == 1 ? 'selected' : ''}}> @lang("$string_file.active") </option>
                                                    <option value="2" {{$reward_system->status == 2 ? 'selected' : ''}}> @lang("$string_file.deactive") </option>
                                                </select>
                                            </div>
                                        </div>

                                {{--                            <div @if($reward_system->online_time_reward != 1) id="online_time_div" @endif>--}}
                                {{--                                <hr>--}}
                                {{--                                <div class="row" >--}}
                                {{--                                    <div class="col-md-2">--}}
                                {{--                                        <div class="form-group">--}}
                                {{--                                            <label class="text-capitalize">@lang('admin.message772')</label>--}}
                                {{--                                            <select class="form-control" name="online_time" onchange="switchDisabled(this.value , 'online_time-switch')">--}}
                                {{--                                                <option value="2"> @lang('admin.disable') </option>--}}
                                {{--                                                <option value="1" {{(old('online_time',$reward_system->online_time_reward) == 1) ? 'selected' : ''}}> @lang('admin.enable') </option>--}}
                                {{--                                            </select>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="col-md-3">--}}
                                {{--                                        <div class="form-group">--}}
                                {{--                                            <label class="text-capitalize">@lang('admin.hours_per_point')</label>--}}
                                {{--                                            <input type="number" max="1000000" class="form-control online_time-switch" name="hours_per_point" value="{{old('hours_per_point',$reward_system->points_per_hour)}}"--}}
                                {{--                                              {{(old('online_time',$reward_system->online_time_reward) == 1) ? '' : 'disabled'}}--}}
                                {{--                                            />--}}
                                {{--                                            @if ($errors->has('hours_per_point'))--}}
                                {{--                                                <label class="text-danger">{{ $errors->first('hours_per_point') }}</label>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="col-md-3">--}}
                                {{--                                        <div class="form-group">--}}
                                {{--                                            <label class="text-capitalize">@lang('admin.expire_in_days')</label>--}}
                                {{--                                            <input type="number" max="1000000" class="form-control online_time-switch" name="online_time_expire_in_days" value="{{old('online_time_expire_in_days',$reward_system->online_time_expire_in_days)}}"--}}
                                {{--                                              {{(old('online_time',$reward_system->online_time_reward) == 1) ? '' : 'disabled'}}--}}
                                {{--                                            />--}}
                                {{--                                            @if ($errors->has('online_time_expire_in_days'))--}}
                                {{--                                                <label class="text-danger">{{ $errors->first('online_time_expire_in_days') }}</label>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                            </div>--}}
                                {{--                            --}}
                                
                                                            <div @if($reward_system->commission_paid_reward != 1) id="commission_paid_div" @endif>
                                                                <hr>
                                                                <div class="row">
                                                                    <div class="col-md-2">
                                                                        <div class="form-group">
                                                                            <label class="text-capitalize">@lang('admin.commission_paid')</label>
                                                                            <select class="form-control" name="commission_paid" onchange="switchDisabled(this.value , 'commission_paid-switch')">
                                                                                <option value="2"> @lang('admin.disable') </option>
                                                                                <option value="1" {{(old('commission_paid',$reward_system->commission_paid_reward) == 1) ? 'selected' : ''}}> @lang('admin.enable') </option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="form-group">
                                                                            <label class="text-capitalize">@lang('admin.per_point_amount')</label>
                                                                            <input type="number" max="1000000" class="form-control commission_paid-switch" name="comission_amount_per_point" value="{{old('comission_amount_per_point',$reward_system->commission_amount_per_point)}}"
                                                                              {{(old('commission_paid',$reward_system->commission_paid_reward) == 1) ? '' : 'disabled'}}
                                                                            />
                                                                            @if ($errors->has('comission_amount_per_point'))
                                                                                <label class="text-danger">{{ $errors->first('comission_amount_per_point') }}</label>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="form-group">
                                                                            <label class="text-capitalize">@lang('admin.expire_in_days')</label>
                                                                            <input type="number" max="1000000" class="form-control commission_paid-switch" name="commission_paid_expire_in_days" value="{{old('commission_paid_expire_in_days',$reward_system->commission_expire_in_days)}}"
                                                                              {{(old('commission_paid',$reward_system->commission_paid_reward) == 1) ? '' : 'disabled'}}
                                                                            />
                                                                            @if ($errors->has('commission_paid_expire_in_days'))
                                                                                <label class="text-danger">{{ $errors->first('commission_paid_expire_in_days') }}</label>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <hr>
                                {{--                            <div class="row">--}}
                                {{--                                <div class="col-md-2">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.peak_hours')</label>--}}
                                {{--                                        <select class="form-control" name="peak_hours" onchange="switchDisabled(this.value , 'peak-switch')">--}}
                                {{--                                            <option value="2"> @lang('admin.disable') </option>--}}
                                {{--                                            <option value="1" {{(old('peak_hours',$reward_system->peak_hours) == 1) ? 'selected' : ''}}> @lang('admin.enable') </option>--}}
                                {{--                                        </select>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                @php--}}
                                {{--                                    $slab_data = json_decode($reward_system->slab_data,true);--}}
                                {{--                                @endphp--}}
                                {{--                                <div class="col-md-2">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.slab') 1</label>--}}
                                {{--                                        <div class="row">--}}
                                {{--                                            <div class="col-md-6">--}}
                                {{--                                                <input type="text" class="form-control peak-switch timepicker" name="slab_from[]" placeholder="From" value="{{old('slab_1_from',$slab_data[0]['slab_from'])}}"--}}
                                {{--                                                  data-plugin="clockpicker" data-autoclose="true" id="time" autocomplete="off"--}}
                                {{--                                                  {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                                />--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="col-md-6">--}}
                                {{--                                                <input type="text" class="form-control peak-switch timepicker" name="slab_to[]" placeholder="To" value="{{old('slab_1_to',$slab_data[0]['slab_to'])}}"--}}
                                {{--                                                  data-plugin="clockpicker" data-autoclose="true" id="time" autocomplete="off"--}}
                                {{--                                                  {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                                />--}}
                                {{--                                            </div>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-1">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.points')</label>--}}
                                {{--                                        <input type="number" max="1000000" class="form-control peak-switch" name="peak_points_collection[]" value="{{old('peak_points_collection',$slab_data[0]['peak_points_collection'])}}"--}}
                                {{--                                          {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                        />--}}
                                {{--                                        @if ($errors->has('peak_points_collection'))--}}
                                {{--                                            <label class="text-danger">{{ $errors->first('peak_points_collection') }}</label>--}}
                                {{--                                        @endif--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-2">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.slab') 2</label>--}}
                                {{--                                        <div class="row">--}}
                                {{--                                            <div class="col-md-6">--}}
                                {{--                                                <input type="text" class="form-control peak-switch timepicker" name="slab_from[]" placeholder="From" value="{{old('slab_2_from',$slab_data[1]['slab_from'])}}"--}}
                                {{--                                                  data-plugin="clockpicker" data-autoclose="true" id="time" autocomplete="off"--}}
                                {{--                                                  {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                                />--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="col-md-6">--}}
                                {{--                                                <input type="text" class="form-control peak-switch timepicker" name="slab_to[]" placeholder="To" value="{{old('slab_2_to',$slab_data[1]['slab_to'])}}"--}}
                                {{--                                                  data-plugin="clockpicker" data-autoclose="true" id="time" autocomplete="off"--}}
                                {{--                                                  {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                                />--}}
                                {{--                                            </div>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-1">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.points')</label>--}}
                                {{--                                        <input type="number" max="1000000" class="form-control peak-switch" name="peak_points_collection[]" value="{{old('peak_points_collection',$slab_data[1]['peak_points_collection'])}}"--}}
                                {{--                                          {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                        />--}}
                                {{--                                        @if ($errors->has('peak_points_collection'))--}}
                                {{--                                            <label class="text-danger">{{ $errors->first('peak_points_collection') }}</label>--}}
                                {{--                                        @endif--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-2">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.slab') 3</label>--}}
                                {{--                                        <div class="row">--}}
                                {{--                                            <div class="col-md-6">--}}
                                {{--                                                <input type="text" class="form-control peak-switch timepicker" name="slab_from[]" placeholder="From" value="{{old('slab_3_from',$slab_data[2]['slab_from'])}}"--}}
                                {{--                                                  data-plugin="clockpicker" data-autoclose="true" id="time" autocomplete="off"--}}
                                {{--                                                  {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                                />--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="col-md-6">--}}
                                {{--                                                <input type="text" class="form-control peak-switch timepicker" name="slab_to[]" placeholder="To" value="{{old('slab_3_to',$slab_data[2]['slab_to'])}}"--}}
                                {{--                                                  data-plugin="clockpicker" data-autoclose="true" id="time" autocomplete="off"--}}
                                {{--                                                  {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                                />--}}
                                {{--                                            </div>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-1">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.points')</label>--}}
                                {{--                                        <input type="number" max="1000000" class="form-control peak-switch" name="peak_points_collection[]" value="{{old('peak_points_collection',$slab_data[2]['peak_points_collection'])}}"--}}
                                {{--                                          {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                        />--}}
                                {{--                                        @if ($errors->has('peak_points_collection'))--}}
                                {{--                                            <label class="text-danger">{{ $errors->first('peak_points_collection') }}</label>--}}
                                {{--                                        @endif--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-2"></div>--}}
                                {{--                                <div class="col-md-2">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.slab') 4</label>--}}
                                {{--                                        <div class="row">--}}
                                {{--                                            <div class="col-md-6">--}}
                                {{--                                                <input type="text" class="form-control peak-switch timepicker" name="slab_from[]" placeholder="From" value="{{old('slab_4_from',$slab_data[3]['slab_from'])}}"--}}
                                {{--                                                  data-plugin="clockpicker" data-autoclose="true" id="time" autocomplete="off"--}}
                                {{--                                                  {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                                />--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="col-md-6">--}}
                                {{--                                                <input type="text" class="form-control peak-switch timepicker" name="slab_to[]" placeholder="To" value="{{old('slab_4_to',$slab_data[3]['slab_to'])}}"--}}
                                {{--                                                  data-plugin="clockpicker" data-autoclose="true" id="time" autocomplete="off"--}}
                                {{--                                                  {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                                />--}}
                                {{--                                            </div>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                                <div class="col-md-1">--}}
                                {{--                                    <div class="form-group">--}}
                                {{--                                        <label class="text-capitalize">@lang('admin.points')</label>--}}
                                {{--                                        <input type="number" max="1000000" class="form-control peak-switch" name="peak_points_collection[]" value="{{old('peak_points_collection',$slab_data[3]['peak_points_collection'])}}"--}}
                                {{--                                          {{(old('peak_hours',$reward_system->peak_hours) == 1) ? '' : 'disabled'}}--}}
                                {{--                                        />--}}
                                {{--                                        @if ($errors->has('peak_points_collection'))--}}
                                {{--                                            <label class="text-danger">{{ $errors->first('peak_points_collection') }}</label>--}}
                                {{--                                        @endif--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                {{--                            </div>--}}
                            </fieldset>
                            <div class="form-actions right" style="margin-bottom: 3%">
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.save")
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
        $(document).ready(function () {
            $('#trips_div').hide();
            $('#expense_div').hide();
            // console.log($('#trips_select').val());
            if($('#trips_select').val() == 4){
                $('#trips_div').show();
                $('#point_trips').show();
                $('#type_div').show();
            }else if($('#trips_select').val() == 3){
                $('#expense_div').show();
                $('#point_trips').show();
            }else{
                $('#trips_div').hide();
                $('#expense_div').hide();
                $('#point_trips').hide();
                $('#type_div').hide();
            }
        });
        // function switchDisabled (value , target) {
        //     if (value == 1) {
        //         $('.'+target).prop('disabled' , false)
        //         return
        //     }
        //     $('.'+target).prop('disabled' , true)
        // }
        
        function tripsCalc(value,target){
            console.log(value,'kkk');
            if(value == 3){
                $('#trips_div').hide();
                $('#expense_div').show();
                $('#point_trips').show();
                $('#type_div').hide();
                $('.'+target).prop('disabled' , false)
            }else if(value == 4){
                $('#expense_div').hide();
                $('#trips_div').show();
                $('#point_trips').show();
                $('#type_div').show();
                $('.'+target).prop('disabled' , false)
                return 
            }else if(value == 1){
                $('#expense_div').hide();
                $('#trips_div').hide();
                $('#point_trips').hide();
                $('#type_div').hide();
                $('.'+target).prop('disabled' , false)
            }else{
                $('#expense_div').hide();
                $('#trips_div').hide();
                $('#point_trips').hide();
                $('#type_div').hide();
                $('.'+target).prop('disabled' , true)
            }
        }

        $(document).on('change','#application',function(){
            var app = this.value;
            if(app == 1){
                $('#country_div').show();
                $('#country_area_div').show();
                $('#online_time_div').hide();
                $('#commission_paid_div').hide();

                // $('#referral_div').show();
                $('#trip_expenses_div').show();
            }else if(app == 2){
                $('#country_div').hide();
                $('#country_area_div').show();
                // $('#referral_div').hide();
                $('#trip_expenses_div').hide();

                $('#online_time_div').show();
                $('#commission_paid_div').show();
            }else{
                $('#country_div').hide();
                $('#country_area_div').hide();

                // $('#referral_div').hide();
                $('#trip_expenses_div').hide();

                $('#online_time_div').hide();
                $('#commission_paid_div').hide();
            }
        });

        $(document).ready(function(){
            // $('#referral_div').hide();
            var application = $('#application').val();
            if(application == 1){
                $('#trip_expenses_div').show();
                $('#online_time_div').hide();
                $('#commission_paid_div').hide();
            }else{
                $('#trip_expenses_div').hide();
                $('#online_time_div').show();
                $('#commission_paid_div').show();
            }
        });
    </script>
@endsection
