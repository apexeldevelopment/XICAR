@extends('merchant.layouts.main')
@section('content')
    @php
        $segment = App\Http\Controllers\Helper\Merchant::MerchantSegments();
    @endphp
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        @if(Auth::user('merchant')->can('create_rider'))
                            @if($export_permission)
                                <a href="{{route('excel.user',$data['export_search'])}}" data-toggle="tooltip">
                                    <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                        <i class="wb-download" title="@lang("$string_file.export_excel")"></i>
                                    </button>
                                </a>
                             @endif
                            <a href="{{route('users.create')}}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-plus" title="@lang("$string_file.add_user")"></i>
                                </button>
                            </a>
                        @endif
                        @if($config->user_document == 1)
                            <a href="{{ route('pending_rider_approval') }}">
                                <button type="button"
                                        class="btn btn-icon btn-warning float-right" style="margin:10px">
                                    @lang("common.pending") @lang("common.user") @lang("common.approval")
                                    <span class="badge badge-pill">{{ $pending_user }}</span>
                                </button>
                            </a>
                        @endif
                            <a href="{{ route('merchant.deleted.user') }}">
                                <button type="button"
                                        class="btn btn-icon btn-dark float-right" style="margin:10px">
                                    @lang("common.deleted") @lang("common.users")
                                    <span class="badge badge-pill">{{ $deleted_users_count }}</span>
                                </button>
                            </a>
                        @if($carpooling_enable)
                            <a href="{{ route('merchant.user.pending.vehicle.list') }}">
                                <button type="button"
                                        class="btn btn-icon btn-warning float-right" style="margin:10px">
                                    @lang("common.pending") @lang("common.user") @lang("$string_file.vehicle") @lang("common.approval")
                                    <span class="badge badge-pill">{{ $pending_vehicle_users }}
                            </span>
                                </button>
                            </a>
                            <a href="{{ route('merchant.user.vehicle.rejected') }}">
                                <button type="button" class="btn btn-icon btn-danger float-right"
                                        style="margin:10px">
                                    @lang("common.rejected") @lang("common.user") @lang("$string_file.vehicle")
                                    <span class="badge badge-pill">{{ $rejected_user_vehicles }}
                                </span>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="wb-users" aria-hidden="true"></i>
                       @lang("$string_file.user_management")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('merchant.user.search') }}" method="get">
                        <div class="table_search row p-3">
                            <div class="col-md-2 col-xs-6 active-margin-top">@lang("$string_file.search_by") :</div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <select class="form-control" name="parameter"
                                            id="parameter"
                                            required>
                                        <option value="1">@lang("$string_file.name")</option>
                                        <option value="2">@lang("$string_file.email")</option>
                                        <option value="3">@lang("$string_file.phone")</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="text" name="keyword"
                                           placeholder="@lang("$string_file.enter_text")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <select class="form-control" name="country_id"
                                            id="country_id">
                                        <option value="">--@lang("$string_file.country")--</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}"> {{
                                                                    $country->CountryName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if($carpooling_enable)
                                <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                    <div class="input-group">
                                        <select class="form-control" name="user_type"
                                                id="user_type">
                                            <option value="">--@lang("common.user") @lang("common.type")--</option>
                                            <option value="1">@lang("common.offer") @lang("common.user")</option>
                                            <option value="2">@lang("common.normal") @lang("common.user")</option>

                                        </select>
                                    </div>
                                    .
                                </div>
                            @endif
                            <div class="col-md-2 col-xs-6 form-group active-margin-top">
                                <button class="btn btn-primary" type="submit"><i
                                            class="fa fa-search"
                                            aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.user_id")</th>
                            <th>@lang("$string_file.user_details")</th>
                            @if($config->gender == 1)
                                <th>@lang("$string_file.gender")</th>
                            @endif
                            <th>@lang("$string_file.service_statistics")</th>
                            <th>@lang("$string_file.wallet_money")</th>
{{--                            <th>@lang("$string_file.referral_code")</th>--}}
                            <th>@lang("$string_file.signup_details")</th>
                            <th>@lang("$string_file.registered_date")</th>
                            @if($config->kin_person_details_on_signup == 1)
                                <th>@lang("$string_file.kin_person_details")</th>
                            @endif
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $users->firstItem() @endphp
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $sr }}  </td>
                                <td>{{$user->user_merchant_id}}</td>
                                <td>
                                    <span class="long_text" style="word-wrap: break-word; white-space: normal;">
                                        {!! is_demo_data($user->UserName, $user->Merchant) !!}<br>
                                        {!! is_demo_data($user->UserPhone, $user->Merchant) !!}<br>
                                        {!! is_demo_data($user->email, $user->Merchant) !!}
                                    </span>
                                </td>
                                @if($config->gender == 1)
                                    @switch($user->user_gender)
                                        @case(1)
                                        <td>@lang("$string_file.male")</td>
                                        @break
                                        @case(2)
                                        <td>@lang("$string_file.female")</td>
                                        @break
                                        @default
                                        <td>------</td>
                                    @endswitch
                                @endif
                                <td>
                                    @if($user->total_trips)
                                    <a href="{{ route('merchant.user.jobs',['booking',$user->id]) }}">
                                                <span class="badge badge-success font-weight-100">@lang("$string_file.rides") : {{ $user->total_trips }}</span>
                                    </a>
                                        <!-- {{ $user->total_trips }}  @lang("$string_file.rides") -->
                                    @else
                                        @lang("$string_file.no_ride")
                                    @endif
                                    @if(in_array('CARPOOLING',$segment))
                                        <br>
                                        @if(isset($user->total_offer_rides) && $user->total_offer_rides > 0)
                                            {{ $user->total_offer_rides }} @lang("$string_file.offer")  @lang("$string_file.rides")
                                        @else
                                            @lang("$string_file.no") @lang("$string_file.offer") @lang("$string_file.ride")
                                        @endif
                                    @endif
                                    <br>
                                    @lang("$string_file.rating") :
                                    @if (!empty($user->rating) && $user->rating > 0)
                                        @while($user->rating>0)
                                            @if($user->rating >0.5)
                                                <img src="{{ view_config_image("static-images/star.png") }}"
                                                     alt='Whole Star'>
                                            @else
                                                <img src="{{ view_config_image('static-images/halfstar.png') }}"
                                                     alt='Half Star'>
                                            @endif
                                            @php $user->rating--; @endphp
                                        @endwhile
                                    @else
                                     @lang("$string_file.not_rated_yet")
                                    @endif
                                </td>
                                <td><a class="hyperLink"
                                       href="{{ route('merchant.user.wallet',$user->id) }}" j>
                                        @if($user->wallet_balance)
                                            {{ $user->wallet_balance }}
                                        @else
                                            0.00
                                        @endif
                                    </a>
                                </td>
                                {{--                                <td>{{ $user->ReferralCode }}</td>--}}
                                <td>
                                    @lang("$string_file.user_type") :
                                    @if($user->user_type == 1)
                                     @lang("$string_file.corporate_user")
                                    @else
                                     @lang("$string_file.retail")
                                    @endif
                                    <br>
                                        @lang("$string_file.signup_type") :
                                    @switch($user->UserSignupType)
                                        @case(1)
                                        @lang("$string_file.app")/@lang("$string_file.admin")
                                        @break
                                        @case(2)
                                        @lang("$string_file.google")
                                        @break
                                        @case(3)
                                        @lang("$string_file.facebook")
                                        @break
                                    @endswitch
                                    <br>
                                    @lang("$string_file.signup_from") :
                                    @switch($user->UserSignupFrom)
                                        @case(1)
                                        @lang("$string_file.application")
                                        @break
                                        @case(2)
                                        @lang("$string_file.admin")
                                        @break
                                        @case(3)
                                        @lang("$string_file.web")
                                        @break
                                        @case(4)
                                        @lang("$string_file.whatsapp")
                                        @break
                                    @endswitch
                                </td>
                                <td>
                                    @if(isset($user->CountryArea->timezone))
                                        {!! convertTimeToUSERzone($user->created_at, $user->CountryArea->timezone, null, $user->Merchant) !!}
                                    @else
                                        {!! convertTimeToUSERzone($user->created_at, null, null, $user->Merchant) !!}
                                    @endif
                                </td>
                                
                                @if($config->kin_person_details_on_signup == 1)
                                <td>
                                    @php
                                        $details = !empty($user->user_kin_details) ? json_decode($user->user_kin_details, true) : [];
                                
                                    @endphp
                                    @if(array_key_exists(0, $details) && isset($details[0]))
                                     <span class="badge badge-primary"><strong>@lang("$string_file.name")</strong> : {{$details[0]['kin_name']}}</span><br>
                                     <span class="badge badge-warning"><strong>@lang("$string_file.phone")</strong>: {{$details[0]['kin_phone_number']}}</span><br>
                                    @endif
                                </td>
                                @endif
                                <td>
                                    @if($user->UserStatus == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="button-margin">
                                        @if(Auth::user('merchant')->can('edit_rider'))
                                            <a href="{{ route('users.edit',$user->id) }}"
                                               data-original-title="@lang("$string_file.edit")" data-toggle="tooltip" data-placement="top"
                                               class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('user.upload.document',$user->id) }}"
                                           data-original-title="Edit Document" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                            <i class="fa fa-file-archive-o"></i>
                                        </a>
                                        <a href="{{ route('users.show',$user->id) }}"
                                           class="btn btn-sm btn-info menu-icon btn_delete action_btn"
                                           data-original-title="@lang("$string_file.details")"
                                           data-toggle="tooltip"
                                           data-placement="top"><span class="fa fa-user"></span>
                                        </a>
                                        @if(Auth::user('merchant')->can('edit_rider') && $change_status_permission)
                                            @if($user->UserStatus == 1)
                                                <a href="{{ route('merchant.user.active-deactive',['id'=>$user->id,'status'=>2]) }}"
                                                   title="@lang("$string_file.inactive")" data-toggle="tooltip" data-placement="top"
                                                   class="btn btn-sm btn-danger menu-icon btn_eye_dis action_btn">
                                                    <i class="fa fa-eye-slash"></i>
                                                </a>
                                            @else
                                                <a href="{{ route('merchant.user.active-deactive',['id'=>$user->id,'status'=>1]) }}"
                                                   title="@lang("$string_file.active")" data-toggle="tooltip" data-placement="top"
                                                   class="btn btn-sm btn-success menu-icon btn_eye action_btn">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            @endif
                                        @endif

                                        <a onclick="getDeviceDetails({{$user->id}})" class="btn btn-sm btn-info menu-icon btn_detail action_btn">
                                            <span class="wb-mobile"></span>
                                        </a>

                                        @if($delete_permission)
                                            @if(Auth::user('merchant')->can('delete_rider'))
                                                <button onclick="DeleteEvent({{ $user->id }})"
                                                        type="submit" title="@lang("$string_file.delete")" data-toggle="tooltip"
                                                        data-placement="top" class="btn btn-sm btn-danger menu-icon btn_delete action_btn">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endif
                                        @endif

                                            @if(Auth::user('merchant')->can('create_promotion'))

                                                <span data-target="#sendNotificationModelUser"
                                                      data-toggle="modal"
                                                      id="{{ $user->id }}"><a
                                                            title="@lang("$string_file.send_notification")"
                                                            data-toggle="tooltip"
                                                            id="{{ $user->id }}"
                                                            data-placement="top"
                                                            class="btn  text-white btn-sm btn-warning menu-icon btn_eye action_btn">
                                                    <i class="wb-bell"></i> </a></span>

                                            @endif
                                            @if($config->user_wallet_status == 1)
                                                <span data-target="#addMoneyModel" data-toggle="modal" id="{{ $user->id }}">
                                                    <a title="@lang("$string_file.add_money")"
                                                       id="{{ $user->id }}" data-placement="top"
                                                       class="btn text-white btn-sm btn-success menu-icon btn_eye action_btn" role="menuitem">
                                                        <i class="icon fa-money"></i>
                                                    </a>
                                                </span>
                                            @endif
                                            @if($config->user_wallet_status == 1)
                                                <a href="{{ route('merchant.user.wallet',$user->id) }}"
                                                   title="@lang("$string_file.wallet_transaction")" data-placement="top"
                                                   class="btn text-white btn-sm btn-info menu-icon btn_eye action_btn" role="menuitem">
                                                    <i class="icon fa-window-maximize"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('merchant.user.favourite-location',$user->id) }}"
                                               class="btn text-white btn-sm btn-info menu-icon btn_eye action_btn" role="menuitem"
                                               title="@lang("$string_file.saved_address")"
                                               data-placement="top"><i class="icon fa fa-location-arrow"></i>
                                            </a>
                                            @if(isset($merchant) && $merchant->ApplicationConfiguration->favourite_driver_module == 1)
                                                <a href="{{ route('merchant.user.favourite-driver',$user->id) }}"
                                                   class="btn text-white btn-sm btn-info menu-icon btn_eye action_btn" role="menuitem"
                                                   title="@lang("$string_file.favourite_drivers")" data-placement="top">
                                                    <i class="icon fa fa-id-card"></i>
                                                </a>
                                            @endif
                                            @if ($config->user_document == 1)
                                                <a href="{{ route('merchant.user.documents',['id'=>$user->id]) }}"
                                                   title="@lang("$string_file.documents")" data-placement="top"
                                                   class="btn text-white btn-sm btn-info menu-icon btn_eye action_btn" role="menuitem">
                                                    <i class="icon fa fa-file"></i></a>
                                            @endif
                                            @if(in_array('CARPOOLING',$segment))
                                                <a href="{{route('merchant.user.vehicle_list',['id'=>$user->id])}}"
                                                   data-original-title="Documents" data-placement="top"
                                                   class="btn btn-outline dropdown-item" role="menuitem">
                                                    <i class="icon fa fa-car"></i>
                                                    <b>@lang("$string_file.vehicle") @lang("common.management")</b></a>
                                            @endif
                                            @if(in_array('CARPOOLING',$segment))
                                                <a href="{{route('merchant.user.address',['id'=>$user->id])}}"
                                                   data-original-title="Documents" data-placement="top"
                                                   class="btn btn-outline dropdown-item" role="menuitem">
                                                    <i class="icon fa fa-user"></i>
                                                    <b>@lang("common.user") @lang("common.address")</b></a>
                                            @endif
                                    </div>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $users, 'data' => $data])
                    {{--                    <div class="pagination1 float-right">{{ $users->appends($data)->links() }}</div>--}}
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade text-left" id="sendNotificationModelUser" tabindex="-1" role="dialog"
         aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600" id="myModalLabel33"><b>@lang("$string_file.send_notification") </b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('merchant.sendsingle-user') }}" enctype="multipart/form-data" method="post">
                    @csrf
                    <div class="modal-body">
                        <label>@lang("$string_file.title") </label>
                        <div class="form-group">
                            <input type="text" class="form-control" id="title"
                                   name="title"
                                   placeholder="" required>
                        </div>

                        <label>@lang("$string_file.message") </label>
                        <div class="form-group">
                           <textarea class="form-control" id="message" name="message"
                                     rows="3"
                                     placeholder=""></textarea>
                        </div>
                        <label>@lang("$string_file.image") </label>
                        <div class="form-group">
                            <input type="file" class="form-control" id="image"
                                   name="image"
                                   placeholder="@lang("$string_file.image")">
                            <input type="hidden" name="persion_id" id="persion_id" required>
                        </div>
                        <label>@lang("$string_file.show_in_promotion") </label>
                        <div class="form-group">
                            <input type="checkbox" value="1" name="expery_check"
                                   id="expery_check_two">
                        </div>
                        <label>@lang("$string_file.expire_date") </label>
                        <div class="input-group">
                            <input type="text" class="form-control customDatePicker1 bg-this-color"
                                   id="datepicker" name="date" readonly
                                   placeholder="">
                        </div>

                        <label>@lang("$string_file.url") </label>
                        <div class="form-group">
                            <input type="url" class="form-control" id="url"
                                   name="url"
                                   placeholder="@lang("$string_file.url")(@lang("$string_file.optional"))">
                            <label class="danger">@lang("$string_file.example") :  https://www.google.com/</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-outline-secondary btn" data-dismiss="modal" value="@lang("$string_file.close")">
                        <input type="submit" class="btn btn-outline-primary btn" value="@lang("$string_file.send")">
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{--add money in user wallet--}}
    <div class="modal fade text-left" id="addMoneyModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600" id="myModalLabel33"><b>@lang("$string_file.add_money_in_wallet")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('merchant.user.add.wallet') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <label>@lang("$string_file.payment_method") </label>
                        <div class="form-group">
                            <select class="form-control" name="payment_method" id="payment_method" required>
                                <option value="1">@lang("$string_file.cash")</option>
                                <option value="2">@lang("$string_file.non_cash")</option>
                            </select>
                        </div>

                        <label for="transaction_type">
                            @lang("$string_file.transaction_type")<span
                                    class="text-danger">*</span>
                        </label>
                        <div class="form-group">
                            <select id="transaction_type" name="transaction_type" class="form-control" required>
                                <option value="1">@lang("$string_file.credit")</option>
                                <option value="2">@lang("$string_file.debit")</option>
                            </select>
                        </div>

                        <label>@lang("$string_file.amount") </label>
                        <div class="form-group">
                            <input type="number" name="amount" placeholder=""
                                   class="form-control" required>
                            <input type="hidden" name="add_money_user_id" id="add_money_driver_id">
                        </div>

                        <label>@lang("$string_file.receipt_number") </label>
                        <div class="form-group">
                            <input type="text" name="receipt_number" placeholder=""
                                   class="form-control" required>
                        </div>
                        <label>@lang("$string_file.description") </label>
                        <div class="form-group">
                            <textarea class="form-control" id="title1" rows="3" name="description"
                                      placeholder=""></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-secondary" data-dismiss="modal" value="@lang("$string_file.close")">
                        <input type="submit" id="sub" class="btn btn-primary" value="@lang("$string_file.save")">
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="device-details" tabindex="-1" role="dialog"
         aria-labelledby="deviceDetailsPopup" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="deviceDetailsPopupTitle">@lang("$string_file.device") @lang("$string_file.details") : <label id="driver-name"></label></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="model-data">

                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script>
        function getDeviceDetails(user_id) {
            $("#model-data").html(null);
            $("#sender-name").html(null);
            $("#loader1").show();
            $.ajax({
                method: 'GET',
                url: '<?php echo route('user.device.details') ?>',
                data: {
                    user_id: user_id,
                },
                success: function (data) {
                    if (data.status == "success") {
                        $("#model-data").html(data.data.view);
                        $("#driver-name").html(data.data.name);
                        $('#device-details').modal('toggle');
                    } else {
                        alert(data.message);
                    }
                }
            });
            $("#loader1").hide();
        }

        $('#sub').on('click', function () {
            $('#myLoader').removeClass('d-none');
            $('#myLoader').addClass('d-flex');
        });
    </script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        function DeleteEvent(id) {
            var token = "{{csrf_token()}}";
        swal({
            title: '@lang("$string_file.are_you_sure")',
            text: '@lang("$string_file.delete_warning")',
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((isConfirm) => {
            if (isConfirm) {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    type: "GET",
                    url: "{{ route('merchant.user.delete', ':id') }}".replace(':id', id),
                })
                .done(function (response) {
                    if (response.status === "success") {
                        swal({
                            title: "DELETED!",
                            text: response.message,
                            icon: "success",
                        }).then(() => {
                            window.location.href = "{{ route('users.index') }}";
                        });
                    } else {
                        swal({
                            title: "Error!",
                            text: response.message,
                            icon: "warning",
                        });
                    }
                })
                .fail(function (xhr) {
                    swal({
                        title: "Error!",
                        text: xhr.responseJSON?.message || "An error occurred.",
                        icon: "error",
                    });
                });
            } else {
                swal("@lang('$string_file.data_is_safe')", {
                    icon: "info",
                });
            }
        });
    }
    </script>
@endsection

