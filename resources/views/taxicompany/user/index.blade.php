@extends('taxicompany.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('excel.user')}}">
                            <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                <i class="wb-download" title="@lang("$string_file.export_excel")"></i>
                            </button>
                        </a>
                        <a href="{{route('taxicompany.users.create')}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-plus" title="@lang("$string_file.add_user")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                       @lang("$string_file.user_management")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('users.search') }}" method="post">
                        @csrf
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
                            <div class="col-md-2 col-xs-6 form-group active-margin-top">
                                <button class="btn btn-primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.user_details")</th>
                            <th>@lang("$string_file.gender")</th>
                            <th>@lang("$string_file.service_statistics")</th>
                            <th>@lang("$string_file.wallet_money")</th>
                            <th>@lang("$string_file.referral_code")</th>
                            <th>@lang("$string_file.signup_details")</th>
                            <th>@lang("$string_file.registered_date")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $users->firstItem() @endphp
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $sr }} </td>
                                <td>
                                    <span class="long_text">   {!! nl2br($user->first_name." ".$user->last_name."\n".$user->UserPhone."\n".$user->email) !!}</span>
                                </td>
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
                                <td>
                                    @if($user->total_trips)
                                        {{ $user->total_trips }}  @lang("$string_file.rides")
                                    @else
                                        @lang("$string_file.no_ride")
                                    @endif
                                    <br>
                                    @if ($user->rating == "0.0")
                                        @lang("$string_file.not_rated_yet")
                                    @else
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
                                    @endif
                                </td>
                                <td>
                                    @if($user->wallet_balance)
                                        {{ $user->wallet_balance }}
                                    @else
                                        0.00
                                    @endif
                                </td>
                                <td>{{ $user->ReferralCode }}</td>
                                <td>
                                    @if($user->user_type == 1)
                                        @lang("$string_file.corporate_user")
                                    @else
                                        @lang("$string_file.retail")
                                    @endif
                                    <br>
                                    @switch($user->UserSignupType)
                                        @case(1)
                                        @lang("$string_file.normal")
                                        @break
                                        @case(2)
                                        @lang("$string_file.google")
                                        @break
                                        @case(3)
                                        @lang("$string_file.facebook")
                                        @break
                                    @endswitch
                                    <br>
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
                                    @endswitch
                                </td>
                                <td>
                                    {!! convertTimeToUSERzone($user->created_at, null,null,$user->Merchant,2) !!}
                                </td>
                                <td>
                                    @if($user->UserStatus == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td>
                                    @if($config->user_wallet_status == 1)
                                        <span data-target="#addMoneyModel"
                                              data-toggle="modal" id="{{ $user->id }}"><a
                                                    data-original-title="@lang("$string_file.add_money")"
                                                    data-toggle="tooltip"
                                                    id="{{ $user->id }}" data-placement="top"
                                                    class="btn text-white btn-sm btn-success menu-icon btn_detail action_btn"> <i
                                                        class="fa fa-money"></i> </a></span>
                                        <a href="{{ route('taxicompany.user.wallet',$user->id) }}"
                                           data-original-title="Wallet Transactions"
                                           data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-secondary menu-icon btn_money action_btn">
                                            <i class="fa fa-window-maximize"></i> </a>
                                    @endif
                                    <button onclick="DeleteEvent({{ $user->id }})"
                                            type="submit"
                                            data-original-title="@lang("$string_file.delete")"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            class="btn btn-sm btn-danger menu-icon btn_delete action_btn">
                                        <i class="fa fa-trash"></i></button>
                                    <button type="submit"
                                            data-original-title="@lang("$string_file.edit")"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            class="btn btn-sm btn-warning menu-icon btn_delete action_btn">
                                        <a href="{{ route('taxicompany.users.edit',$user->id) }}"
                                           style="color: white">
                                            <i class="fa fa-edit"></i></a></button>
                                    <a href="{{ route('taxicompany.users.show',$user->id) }}"
                                       class="btn btn-sm btn-info menu-icon btn_delete action_btn"
                                       data-original-title="@lang("$string_file.details")"
                                       data-toggle="tooltip"
                                       data-placement="top"><span
                                                class="fa fa-user"></span></a>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    <div class="pagination1 float-right">{{ $users->links() }}</div>
                </div>
            </div>
        </div>
    </div>
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
                <form action="{{ route('taxicompany.user.add.wallet') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <label>@lang("$string_file.payment_method") </label>
                        <div class="form-group">
                            <select class="form-control" name="payment_method" id="payment_method" required>
                                <option value="1">@lang("$string_file.cash")</option>
                                <option value="2">@lang("$string_file.non_cash")</option>
                            </select>
                        </div>

                        <label>@lang("$string_file.amount") </label>
                        <div class="form-group">
                            <input type="text" name="amount" placeholder="@lang("$string_file.amount")"
                                   class="form-control" required>
                            <input type="hidden" name="add_money_user_id" id="add_money_driver_id">
                        </div>

                        <label>@lang("$string_file.receipt_number") </label>
                        <div class="form-group">
                            <input type="text" name="receipt_number" placeholder="@lang("$string_file.receipt_number")"
                                   class="form-control" required>
                        </div>
                        <label>@lang("$string_file.description") </label>
                        <div class="form-group">
                            <textarea class="form-control" id="title1" rows="3" name="description"
                                      placeholder="@lang("$string_file.description")"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-secondary" data-dismiss="modal" value="@lang("$string_file.close")">
                        <input type="submit" id="sub" class="btn btn-primary" value="@lang("$string_file.add")">
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    {{--    <script>--}}
    {{--        $('#sub').on('click', function () {--}}
    {{--            $('#myLoader').removeClass('d-none');--}}
    {{--            $('#myLoader').addClass('d-flex');--}}
    {{--        });--}}
    {{--    </script>--}}
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        function DeleteEvent(id) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                text: "@lang("$string_file.delete_warning")",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        type: "DELETE",
                        url: 'users/' + id,
                    }).done(function (data) {
                        swal({
                            title: "DELETED!",
                            text: data,
                            type: "success",
                        });
                        window.location.href = "{{ route('taxicompany.users.index') }}";
                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }
    </script>
@endsection

