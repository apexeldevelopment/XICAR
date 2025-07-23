@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="mr--10 ml--10">
                        <div class="row " style="margin-right: 0rem;margin-left: 0rem">
                            <div class="col-md-5 col-sm-5">
                                <h3 class="panel-title"><i class="fa-users" aria-hidden="true"></i>
                                    @lang("$string_file.wallet_transaction") @lang("$string_file.balance")</h3>
                            </div>
                            <div class="col-md-7 col-sm-7">

                                @if($export_permission)
                                    @if(Auth::user('merchant')->can('create_drivers') || $slug != "DRIVER")
                                        <a href="{{route('excel.wallet-balance.report',$arr_search)}}"
                                           data-toggle="tooltip">
                                            <button type="button" class="btn btn-icon btn-success float-right"
                                                    style="margin:10px">
                                                <i class="wb-download"
                                                   title="@lang("$string_file.export_excel")"></i>
                                            </button>
                                        </a>
                                    @endif
                                        <a href="{{route("transaction.wallet-report.balance", ["slug"=>"BUSINESS_SEGMENT"])}}">
                                            <button type="button"
                                                    class="btn btn-outline-primary float-right @if($slug == "BUSINESS_SEGMENT") active @endif "
                                                    style="margin:10px">
                                                @lang("$string_file.business_segment")
                                            </button>
                                        </a>
                                    @if(Auth::user('merchant')->can('create_drivers'))
                                        <a href="{{route("transaction.wallet-report.balance", ["slug"=>"DRIVER"])}}">
                                            <button type="button"
                                                    class="btn btn-outline-primary float-right @if($slug == "DRIVER") active @endif "
                                                    style="margin:10px">
                                                @lang("$string_file.driver")
                                            </button>
                                        </a>
                                    @endif
                                        <a href="{{route("transaction.wallet-report.balance", ["slug"=>"USER"])}}">
                                            <button type="button"
                                                    class="btn btn-outline-primary float-right @if($slug == "USER") active @endif"
                                                    style="margin:10px">
                                                @lang("$string_file.user")
                                            </button>
                                        </a>
                                @endif


                            </div>
                        </div>
                    </div>
                </header>
                <div class="panel-body container-fluid">
                    {!! $search_view !!}
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th> @lang("$string_file.id")</th>
                            <th>@lang("$string_file.service_area") </th>
                            <th>@lang("$string_file.details")</th>
                            <th>@lang("$string_file.wallet_balance")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $data->firstItem() @endphp
                        @foreach($data as $user)
                            <tr>
                                <td>{{$sr}}</td>
                                <td>
                                    @if($slug == "DRIVER")
                                        <a href="{{ route('driver.show',$user->id) }}"
                                           class="hyperLink">{{ $user->merchant_driver_id }}</a>
                                    @elseif($slug == "USER")
                                        <a href="{{ route('users.show',$user->id) }}"
                                           class="hyperLink">{{ $user->id }}</a>
                                    @elseif($slug == "BUSINESS_SEGMENT")
                                        {{--                                        <a href="{{ route('users.show',$user->id) }}"--}}
                                        {{--                                           class="hyperLink">--}}
                                        #{{ $user->id }}
                                        {{--                                        </a>--}}
                                    @endif

                                </td>
                                <td>
                                    {{ isset($user->CountryArea) ? $user->CountryArea->CountryAreaName : "" }}
                                    @if($slug == "DRIVER")
                                        @if($config->enable_super_driver == 1 && isset($user->is_super_driver) && $user->is_super_driver == 1)
                                            <br><span class="badge badge-info">@lang("$string_file.special")</span>
                                        @endif
                                        @if(isset($config->driver_agent_enable) && $config->driver_agent_enable == 1 && !empty($user->agent_id))
                                            <br><span class="badge badge-info">{{ $user->Agent->name }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <span class="long_text">
                                       @if($slug == "DRIVER")
                                            {{ is_demo_data($user->fullName,$user->Merchant) }}<br>
                                            {{ is_demo_data($user->phoneNumber,$user->Merchant) }}<br>
                                            {{ is_demo_data($user->email,$user->Merchant) }}
                                        @elseif($slug == "USER")
                                            {{ is_demo_data($user->first_name.$user->last_name,$user->Merchant) }}<br>
                                            {{ is_demo_data($user->UserPhone,$user->Merchant) }}<br>
                                            {{ is_demo_data($user->email,$user->Merchant) }}
                                        @elseif($slug == "BUSINESS_SEGMENT")
                                            {{ is_demo_data($user->full_name,$user->Merchant) }}<br>
                                            {{ is_demo_data($user->phone_number,$user->Merchant) }}<br>
                                            {{ is_demo_data($user->email,$user->Merchant) }}
                                        @endif
                                    </span>
                                </td>
                                <td style="width:250px;float:left">
                                    @if($slug == "DRIVER")
                                        @if($config->driver_wallet_status == 1)
                                            @if($user->wallet_money)
                                                @lang("$string_file.wallet_money") :- <a
                                                        href="{{ route('merchant.driver.wallet.show',$user->id) }}">{{ $user->wallet_money }}</a>
                                            @else
                                                @lang("$string_file.wallet_money") :- ------
                                            @endif
                                        @endif
                                    @elseif($slug == "USER")

                                        @if($user->wallet_balance)
                                            @lang("$string_file.wallet_money") :- <a
                                                    href="{{ route('merchant.user.wallet',$user->id) }}">{{ $user->wallet_balance }}</a>
                                        @else
                                            @lang("$string_file.wallet_money") :- ------
                                        @endif
                                    @elseif($slug == "BUSINESS_SEGMENT")

                                        @if($user->wallet_amount)
                                            @lang("$string_file.wallet_money") :- {{$user->wallet_amount}}
                                            {{--                                            <a--}}
                                            {{--                                                    href="{{ route('merchant.user.wallet',$user->id) }}">{{ $user->wallet_balance }}</a>--}}
                                        @else
                                            @lang("$string_file.wallet_money") :- ------
                                @endif
                                @endif
                            </tr>
                            @php $sr++; @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $data, 'data' => $arr_search])
                    {{--                    <div class="pagination1 float-right">{{ $data->appends($arr_search)->links() }}</div>--}}
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['page_name'=>'view_text'])
@endsection
@section('js')
    {{--    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>--}}
    <script>

        $('#export-excel').on('click', function () {
            var action = '{{route("excel.wallet-balance.report")}}';
            var arr_param = [];
            var arr_param = $("#driver-wallet-search").serializeArray();
            $.ajax({
                type: "GET",
                data: {arr_param},
                url: action,
                success: function (data) {
                    console.log(data);
                }, error: function (err) {
                }
            });
        });

    </script>

    <script>

        function selectSearchFields() {
            var segment_id = $('#segment_id').val();
            var area_id = $('#area_id').val();
            var by = $('#by_param').val();
            var by_text = $('#keyword').val();
            if (segment_id.length == 0 && area_id == "" && by == "" && by_text == "" && driver_status == "") {
                alert("Please select at least one search field");
                return false;
            } else if (by != "" && by_text == "") {
                alert("Please enter text according to selected parameter");
                return false;
            } else if (by_text != "" && by == "") {
                alert("Please select parameter according to entered text");
                return false;
            }
        }

    </script>
@endsection
