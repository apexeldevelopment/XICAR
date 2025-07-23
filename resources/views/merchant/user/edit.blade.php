@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
           @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->edit_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{ route('users.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-edit" aria-hidden="true"></i>
                        @lang("$string_file.user_details")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    @if(Auth::user()->demo != 1)
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              name="user-register" id="user-register-edit"
                              action="{{route('users.update', $user->id)}}">
                            {{method_field('PUT')}}
                            @csrf
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="firstName3">
                                             @lang("$string_file.first_name")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="first_name"
                                               name="first_name" value="{{$user->first_name}}"
                                               placeholder=" @lang("$string_file.first_name")" required>
                                        @if ($errors->has('first_name'))
                                            <label class="text-danger">{{ $errors->first('first_name')
                                                            }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.last_name")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="last_name"
                                               name="last_name" value="{{$user->last_name}}"
                                               placeholder="@lang("$string_file.last_name")" required>
                                        @if ($errors->has('last_name'))
                                            <label class="text-danger">{{ $errors->first('last_name')
                                                            }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="lastName3"> @lang("$string_file.phone")
                                            @if(isset($user->UserPhone))
                                                    <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <div class="row">
                                            <input type="text"
                                                   class="form-control col-md-3 ml-15 col-sm-3 col-3" id="isd"
                                                   name="isd" value="{{old('isd',isset($user->country) ? $user->Country->phonecode : NULL)}}"
                                                   placeholder="@lang("$string_file.isd_code")" readonly/>
                                            <input type="number" class="form-control col-md-8 col-sm-8 col-8"
                                                   id="user_phone" name="user_phone" value="{{old('user_phone',isset($user->country) ?  str_replace($user->Country->phonecode,'',$user->UserPhone) : NULL)}}"
                                                   placeholder="" @if(isset($user->UserPhone)) required @endif/>
                                        </div>
                                        @if ($errors->has('phonecode'))
                                                    <label class="text-danger">{{ $errors->first('phonecode') }}</label>
                                        @endif
                                        @if ($errors->has('user_phone'))
                                            <label class="text-danger">{{ $errors->first('user_phone') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.email")
                                            @if(isset($user->email))
                                                    <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <input type="email" class="form-control" id="user_email"
                                               name="user_email"
                                               placeholder=""
                                               value="{{$user->email}}" @if(isset($user->email)) required @endif>
                                        @if ($errors->has('user_email'))
                                            <label class="text-danger">{{ $errors->first('user_email')
                                                            }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.password")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="password"
                                               name="password"
                                               placeholder="" disabled>
                                        @if ($errors->has('password'))
                                            <label class="text-danger">{{ $errors->first('password')
                                                            }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" value="1" name="edit_password"
                                                   id="edit_password" onclick="EditPassword()">
                                            @lang("$string_file.edit_password")
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="user_gender_enable" name="user_gender_enable" value="{{$appConfig->gender}}"/>
                            <div class="row">
                                @if($appConfig->gender == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="location3">@lang("$string_file.gender")
                                                :</label>
                                            <select class="form-control" name="user_gender"
                                                    id="user_gender"
                                                    required>
                                                <option value="1"
                                                        @if($user->user_gender == 1) selected
                                                        @endif>@lang("$string_file.male")
                                                </option>
                                                <option value="2"
                                                        @if($user->user_gender == 2) selected
                                                        @endif>@lang("$string_file.female")
                                                </option>
                                            </select>
                                            @if ($errors->has('user_gender'))
                                                <label class="text-danger">{{ $errors->first('user_gender')
                                                            }}</label>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                    <input type="hidden" id="smoker_enable" name="smoker_enable" value="{{$appConfig->smoker}}"/>
                                @if($appConfig->smoker == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="location3"> @lang("$string_file.smoke")
                                                :</label>

                                            <label class="radio-inline"
                                                   style="margin-left: 5%;margin-right: 10%;margin-top: 1%;">
                                                <input type="radio" value="1"
                                                       checked id="smoker_type"
                                                       name="smoker_type"
                                                       @if($user->smoker_type == 1) checked
                                                       @endif
                                                       required> @lang("$string_file.smoker")
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" value="2" id="smoker_type"
                                                       name="smoker_type"
                                                       @if($user->smoker_type == 2) checked
                                                       @endif
                                                       required> @lang("$string_file.non_smoker")
                                            </label>
                                            <br>
                                            <label class="checkbox-inline" style="margin-left: 5%;margin-top: 1%;">
                                                <input type="checkbox" name="allow_other_smoker"
                                                       id="allow_other_smoker"
                                                       @if($user->allow_other_smoker == 1) checked @endif
                                                       value="1">  @lang("$string_file.allow_other_to_smoke")
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @if($bookConfig->user_cancellation_charge_card_enable == 1)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="cancellation_charge_card_payment">
                                            @lang("$string_file.cancellation_charge_card_payment")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="cancellation_charge_card_payment" name="cancellation_charge_card_payment">
                                            <option value="2" @if($user->cancellation_charge_card_payment == 2) selected @endif>@lang("$string_file.disable")</option>
                                            <option value="1"@if($user->cancellation_charge_card_payment == 1) selected @endif>@lang("$string_file.enable")</option>

                                        </select>

                                        @if ($errors->has('cancellation_charge_card_payment'))
                                            <label class="text-danger">{{ $errors->first('cancellation_charge_card_payment') }}</label>
                                        @endif
                                    </div>
                                </div> 
                            </div>
                            @endif
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="profile_image">
                                            @lang("$string_file.profile_image")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="file" class="form-control" id="profile"
                                               name="profile"
                                               placeholder="@lang("$string_file.profile_image")"
                                               onchange="readURL(this)">
                                        @if ($errors->has('profile'))
                                            <label class="text-danger">{{ $errors->first('profile')
                                                            }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group text-center">
                                        @if(!empty($user->UserProfileImage))
                                            <img id="show_image" style="border-radius: 50%;"
                                                 src="@if($user->corporate_id){{ get_image($user->UserProfileImage,'corporate_user',$user->merchant_id)}}@else{{ get_image($user->UserProfileImage,'user')}}@endif" width="150"
                                                 height="150">
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                @if($config->kin_person_details_on_signup == 1)
                                    @php
                                    $kin_details = !empty($user->user_kin_details) ? json_decode($user->user_kin_details)[0] : null;
                                    @endphp
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="kin_person_name">
                                                @lang("$string_file.kin_person_name")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="kin_person_name"
                                                   name="kin_person_name" value="{{!empty($kin_details)? $kin_details->kin_name: ''}}"
                                                   placeholder="@lang("$string_file.kin_person_name")" required>
                                            @if ($errors->has('kin_person_name'))
                                                <label class="text-danger">{{ $errors->first('kin_person_name') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="kin_person_phone">
                                                @lang("$string_file.kin_person_phone")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="kin_person_phone"
                                                   name="kin_person_phone" value="{{!empty($kin_details)? $kin_details->kin_phone_number: ''}}"
                                                   placeholder="@lang("$string_file.kin_person_phone")" required>
                                            @if ($errors->has('kin_person_phone'))
                                                <label class="text-danger">{{ $errors->first('kin_person_phone') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                            </div>
                            @if(isset($config->user_bank_details_enable) && $config->user_bank_details_enable == 1)
                                <br>
                                <h5 class="form-section col-md-12" style="color: black;"><i
                                            class="fa fa-bank"></i> @lang("common.bank") @lang("common.details")
                                </h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="lastName3">@lang("common.bank") @lang("common.name")
                                            </label>
                                            <input type="text" class="form-control" id="bank_name"
                                                   name="bank_name"
                                                   value="{{old('bank_name',isset($user->bank_name) ? $user->bank_name : NULL)}}"
                                                   placeholder="" required
                                                   autocomplete="off"/>
                                        </div>
                                        @if ($errors->has('bank_name'))
                                            <label class="text-danger">{{ $errors->first('bank_name') }}</label>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="lastName3">@lang("common.account") @lang("common.holder") @lang("common.name")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="account_holder_name"
                                                   name="account_holder_name"
                                                   value="{{old('account_holder_name',isset($user->account_holder_name) ? $user->account_holder_name : NULL)}}"
                                                   placeholder="" required
                                                   autocomplete="off"/>
                                        </div>
                                        @if ($errors->has('account_holder_name'))
                                            <label class="text-danger">{{ $errors->first('account_holder_name') }}</label>
                                        @endif
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="lastName3">@lang("common.account") @lang("common.number")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="account_number"
                                                   name="account_number"
                                                   value="{{old('account_number',isset($user->account_number) ? $user->account_number : NULL)}}"
                                                   placeholder="@lang("common.account") @lang("common.number")"
                                                   autocomplete="off"/>
                                        </div>
                                        @if ($errors->has('account_number'))
                                            <label class="text-danger">{{ $errors->first('account_number') }}</label>
                                        @endif
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="location3">@lang("common.online") @lang("common.transaction") @lang("common.code")
                                            </label>
                                            <input type="text" class="form-control" id="online_transaction"
                                                   name="online_transaction"
                                                   value="{{old('online_transaction',isset($user->online_code) ? $user->online_code : NULL)}}"
                                                   placeholder="@lang("common.online") @lang("common.transaction") @lang("common.code")"
                                                   autocomplete="off"/>
                                        </div>
                                        @if ($errors->has('online_transaction'))
                                            <label class="text-danger">{{ $errors->first('online_transaction') }}</label>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-control-label"
                                                   for="location3">@lang("common.account") @lang("common.type")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select type="text" class="form-control" name="account_types"
                                                    id="account_types">
                                                @foreach($account_types as $account_type)
                                                    <option value="{{$account_type->id}}" @if($user->account_type_id == $account_type->id) selected @endif>
                                                        @if($account_type->LangAccountTypeSingle)
                                                            {{$account_type->LangAccountTypeSingle->name}}
                                                        @else
                                                            {{$account_type->LangAccountTypeAny->name}}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('account_types'))
                                                <label class="text-danger">{{ $errors->first('account_types') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="form-actions d-flex flex-row-reverse p-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.update")
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="card bg-danger text-white shadow">
                            <div class="card-body">
                                <div class="large">@lang("$string_file.demo_user_cant_edited").</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="examplePositionSidebar" aria-labelledby="examplePositionSidebar"
         role="dialog" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-simple modal-sidebar modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    {{--                    <h4 class="modal-title">Country</h4>--}}
                </div>
                <div class="modal-body">
                    @if(!empty($info_setting) && $info_setting->edit_text != "")
                        {!! $info_setting->edit_text !!}
                    @else
                        <p>No information content found...</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        function EditPassword() {
            if (document.getElementById("edit_password").checked = true) {
                document.getElementById('password').disabled = false;
            }
        }

    </script>
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#show_image')
                        .attr('src', e.target.result)
                        .width(200)
                        .height(200);
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection

