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
                    </div>
                    <h3 class="panel-title"><i class="wb-users" aria-hidden="true"></i>
                        @lang("$string_file.deleted") @lang("$string_file.users")</h3>
                </header>
                <div class="panel-body container-fluid">

                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.user_id")</th>
                            <th>@lang("$string_file.user_details")</th>
                            <th>@lang("$string_file.service_statistics")</th>
                            <th>@lang("$string_file.wallet_money")</th>
                            {{--                            <th>@lang("$string_file.referral_code")</th>--}}
                            <th>@lang("$string_file.signup_details")</th>
                            <th>@lang("$string_file.registered_date")</th>
                            <th>@lang("$string_file.account_delete_reason")</th>
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

                                <td>
                                    @if(!empty($user->CancelReason))
                                        {{$user->CancelReason->ReasonName}}
                                    @endif
                                </td>


                                <td>
                                    <div class="button-margin">

                                        <a href="#" onclick="restoreUser('{{$user->id}}')"
                                           class="btn btn-sm btn-success menu-icon btn_edit action_btn">
                                            <i class="fa fa-retweet"></i>
                                        </a>
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
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        function restoreUser(user_id) {
            
            var token = "{{csrf_token()}}";
            console.log(token)
            swal({
                title: '@lang("$string_file.are_you_sure")',
                text: '@lang("$string_file.restore") @lang("$string_file.user")',
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        type: "POST",
                        url: "{{ route('merchant.restore.user') }}",
                        data: {
                            id: user_id
                        }
                    }).done(function (data) {
                        swal({
                            title: "Account Restored !",
                            text: data.message || "User has been Restored.",
                            icon: "success",
                        }).then(() => {
                            window.location.href = "{{ route('users.index') }}";
                        });
                    });
                } else {
                    swal("@lang("$string_file.cancelled")");
                }
            });
        }


    </script>
@endsection

