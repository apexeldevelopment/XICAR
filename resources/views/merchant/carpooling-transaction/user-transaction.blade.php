@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
{{--                        <a href="{{ route('merchant.offer.rides') }}">--}}
{{--                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">--}}
{{--                                <i class="wb-reply"></i>--}}
{{--                            </button>--}}
{{--                        </a>--}}
                            @if(Auth::user('merchant')->can('create_rider'))
                            {{--<a href="{{route('excel.cashout')}}" data-toggle="tooltip">--}}
                            {{--    <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">--}}
                            {{--        <i class="wb-download" title="@lang("$string_file.export") @lang("$string_file.excel")"></i>--}}
                            {{--    </button>--}}
                            {{--</a>--}}
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="far fa-car" aria-hidden="true"></i>
                        @lang("$string_file.user") @lang("$string_file.cashout") @lang("$string_file.management")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.user") @lang("$string_file.details")</th>
                            <th>@lang("$string_file.amount") </th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action") @lang("$string_file.by")</th>
                            <th>@lang("$string_file.transaction") @lang("$string_file.id")</th>
                            <th>@lang("$string_file.comment")
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>

                        @php $sr = $user_cashout->firstItem() @endphp
                        @foreach($user_cashout as $cashout)
                        <tr>
                            <td>{{$sr}}</td>
                            <td>
                                <span class="long_text">
                                    {!! is_demo_data($cashout->User->UserName, $cashout->Merchant) !!}<br>
                                    {!! is_demo_data($cashout->User->UserPhone, $cashout->Merchant) !!}<br>
                                    {!! is_demo_data($cashout->User->email, $cashout->Merchant) !!}
                                </span>
                            </td>
                            <td>
                                @switch($cashout->cashout_status)
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
                            </td>
                            <td>
                                {{$cashout->User->Country->isoCode." ".$cashout->amount}}
                            </td>
                            <td>
                                {{$cashout->action_by}}
                            </td>
                            <td>
                                {{$cashout->transaction_id}}

                            </td>
                            <td>
                                {{$cashout->comment}}
                            </td>
                            <td>
                                  @switch($cashout->cashout_status)
                                    @case(0)
                                    @if($cashout->action_by=="Bank Transfer")
                                <a href="{{route('merchant.carpool.user.transaction.edit',['id'=>$cashout->id])}}"
                                   data-original-title="@lang("$string_file.edit") "
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                    <i class="fa fa-edit"></i> </a>
                                    @endif
                                    @break;
                                    @case(1)

                                    @break;
                                    @case(2)

                                    @break;
                                    @default
                                    ----
                                @endswitch



                            </td>
                        </tr>
                        @php $sr++  @endphp
                        @endforeach
                        </tbody>

                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $user_cashout, 'data' => []])
                </div>
            </div>
        </div>
    </div>
@endsection
