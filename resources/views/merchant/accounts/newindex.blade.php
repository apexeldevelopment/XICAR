@extends('merchant.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="app-content content">
            <div class="content-wrapper">
                @if(session('settled'))
                    <div class="box no-border">
                        <div class="box-tools">
                            <p class="alert alert-warning alert-dismissible">
                                {{ session('settled') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </p>
                        </div>
                    </div>
                @endif
                <div class="card shadow ">
                    <div class="card-header py-3 ">
                        <h3 class="content-header-title mb-0 d-inline-block"><i
                                    class="fas fa-fw fa-money-bill"></i>@lang("$string_file.driver") @lang("common.account")</h3>
                        {{--<div class="btn-group float-md-right">
                            <div class="heading-elements">
                                <a title="@lang("common.generate") @lang("common.bill")"
                                   href="{{route('accounts.create')}}">
                                    <button class="btn btn-icon btn-warning mr-1">@lang('admin.bill')
                                    </button>
                                </a>
                            </div>
                        </div>--}}
                    </div>
                    {{--<div class="card-header py-3">--}}
                    {{--<form action="{{ route('merchant.accounts.search') }}" method="GET">--}}
                    {{--<div class="table_search row">--}}
                    {{--<div class="col-md-2 col-xs-4 form-group ">--}}
                    {{--<div class="input-group">--}}
                    {{--<select class="form-control" name="parameter"--}}
                    {{--id="parameter"--}}
                    {{--required>--}}
                    {{--<option value="1">@lang("common.name")</option>--}}
                    {{--<option value="2">@lang("common.email")</option>--}}
                    {{--<option value="3">@lang("common.phone")</option>--}}
                    {{--</select>--}}
                    {{--</div>--}}
                    {{--</div>--}}

                    {{--<div class="col-md-3 col-xs-6 form-group ">--}}
                    {{--<div class="input-group">--}}
                    {{--<input type="text" name="keyword"--}}
                    {{--placeholder="@lang("common.enter") @lang("common.text")"--}}
                    {{--class="form-control col-md-12 col-xs-12" required>--}}
                    {{--</div>--}}
                    {{--</div>--}}
                    {{--<div class="col-sm-2  col-xs-12 form-group ">--}}
                    {{--<button class="btn btn-primary" type="submit"><i class="fa fa-search"--}}
                    {{--aria-hidden="true"></i>--}}
                    {{--</button>--}}
                    {{--</div>--}}
                    {{--</div>--}}
                    {{--</form>--}}
                    {{--</div>--}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table display nowrap table-striped table-bordered" id="dataTable" width="100%"
                                   cellspacing="0">
                                <thead>
                                <tr>
                                    <th>@lang("common.sn")</th>
                                    <th>@lang("$string_file.driver") @lang("common.details")</th>
                                    <th>@lang("common.area")</th>
                                    <th>@lang('admin.bookingSolt')</th>
                                    <th>@lang('admin.billperiod')</th>
                                    <th>@lang('admin.totalTrip')</th>
                                    <th>@lang("common.company") @lang("common.cut")</th>
                                    <th>@lang('admin.driverCut')</th>
                                    <th>@lang('admin.cash')</th>
                                    <th>@lang('admin.netOutstanding')</th>
                                    <th>@lang("common.action")</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $sr = $driverAccounts->firstItem() @endphp
                                @foreach($driverAccounts as $driver)
                                    @php date_default_timezone_set($driver->timezone) @endphp
                                    <tr>
                                        <td>{{ $sr }}</td>
                                        @if(Auth::user()->demo == 1)
                                            <td>
                                                                <span class="long_text">
                                                                    {{ "********".substr($driver->Driver->last_name, -2) }}<br>
                                                                    {{ "********".substr($driver->Driver->email, -2) }}<br>
                                                                    {{ "********".substr($driver->Driver->phoneNumber, -2) }}
                                                                 </span>
                                            </td>
                                        @else
                                            <td><span class="long_text">
                                                        {{ $driver->Driver->first_name." ".$driver->Driver->last_name }}
                                                        <br>
                                                        {{ $driver->Driver->email }}
                                                        <br>
                                                        {{ $driver->Driver->phoneNumber }}
                                                        </span>
                                            </td>
                                        @endif
                                        <td>{{ $driver->Driver->CountryArea->CountryAreaName}}</td>
                                        <td>{{ $driver->booking_slot }}</td>
                                        <td>
                                            {{ date('Y-m-d H:i:s',$driver->bill_from) }}
                                            <br>To<br>
                                            {{ date('Y-m-d H:i:s',$driver->bill_to) }}
                                        </td>
                                        <td>{{ $driver->total_trip_amount }}</td>
                                        <td>{{ $driver->company_cut }}</td>
                                        <td>{{ $driver->driver_cut }}</td>
                                        <td>{{ $driver->cash_collect }}</td>
                                        <td>{{ $driver->final_outstanding }}</td>
                                        <td>
                                            @if($driver->status == 2)
                                                @lang('admin.settle')
                                            @else
                                                <button type="button" id="{{ $driver->id }}"
                                                        class="btn btn-primary"
                                                        data-toggle="modal"
                                                        data-target="#settlementBill">Settle
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @php $sr++  @endphp
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="pagination1">{{ $driverAccounts->links() }}</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <br>

    <div class="modal fade text-left" id="settlementBill" tabindex="-1" role="dialog"
         aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600" id="myModalLabel33">@lang('admin.message481')</label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{  route('newaccounts.changestatus') }}" enctype="multipart/form-data" method="post">
                    @csrf
                    <div class="modal-body">
                        <label>@lang("common.reference") @lang("common.no"): </label>
                        <div class="form-group">
                            <input type="text" class="form-control" id="referance_number"
                                   name="referance_number"
                                   placeholder="@lang("common.reference") @lang("common.no")" required>
                            <input type="hidden" id="bill_id"
                                   name="bill_id">

                        </div>

                        <label>@lang('admin.message477'): </label>
                        <div class="form-group">
                            <select class="form-control" name="settle_type"
                                    id="settle_type" required>
                                <option value="1">@lang("common.cash")</option>
                                <option value="2">@lang("common.non_cash")</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-outline-secondary btn-lg" data-dismiss="modal" value="@lang("common.close")">
                        <input type="submit" class="btn btn-outline-primary btn-lg" value="Settle">
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection