@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content container-fluid">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('accounts.create')}}">
                            <button class="btn btn-icon btn-primary" style="margin: 10px;">@lang("common.generate") @lang("common.bill")</button>
                        </a>
                        <a href="{{route('excel.driveraccounts')}}">
                            <button type="button" class="btn btn-icon btn-primary mr-1 float-right" style="margin:10px" data-original-title="@lang("common.export") @lang("common.excel")" data-toggle="tooltip"><i
                                        class="fa fa-download"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa fa-money" aria-hidden="true"></i>
                        @lang("$string_file.driver") @lang("common.account")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('merchant.accounts.search') }}" method="GET">
                        @csrf
                        <div class="table_search row">
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                @lang("common.search") @lang("common.by"):
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="">
                                    <select class="form-control" name="parameter" id="parameter" required>
                                        <option value="1" @if(isset($_GET['parameter']) && $_GET['parameter'] == 1) selected @endif>@lang("common.name")</option>
                                        <option value="2" @if(isset($_GET['parameter']) && $_GET['parameter'] == 2) selected @endif>@lang("common.email")</option>
                                        <option value="3" @if(isset($_GET['parameter']) && $_GET['parameter'] == 3) selected @endif>@lang("common.phone")</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="text" id="" name="keyword" value="@if(isset($_GET['keyword'])){{$_GET['keyword']}}@endif"
                                           placeholder="@lang("common.enter") @lang("common.text")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="text" id="" name="date"
                                           placeholder="@lang("$string_file.ride")  @lang("common.date")"
                                           class="form-control col-md-12 col-xs-12 datepickersearch"
                                           id="datepickersearch" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="">
                                    <select class="form-control" name="settle_type" id="settle_type">
                                        <option value="">@lang("common.both")</option>
                                        <option value="1" @if(isset($_GET['settle_type']) && $_GET['settle_type'] == 1) selected @endif>@lang("$string_file.settled")</option>
                                        <option value="2" @if(isset($_GET['settle_type']) && $_GET['settle_type'] == 2) selected @endif>@lang("$string_file.un_settled")</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2  col-xs-12 form-group active-margin-top">
                                <button class="btn btn-primary" type="submit"><i
                                            class="fa fa-search" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("common.sn")</th>
                            <th>@lang("$string_file.driver") @lang("common.details")</th>
                            <th>@lang("common.area")</th>
                            <th>@lang("common.wallet") @lang("common.amount")</th>
                            <th>@lang("common.last") @lang("common.bill") @lang("common.generated")</th>
                            <th>@lang("common.registered") @lang("common.date")</th>
                            <th>@lang("common.bill") </th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $drivers->firstItem() @endphp
                        @foreach($drivers as $driver)
                            <tr>
                                <td>{{ $sr }}</td>
                                @if(Auth::user()->demo == 1)
                                    <td>
                                            <span class="long_text">
                                                {{ "********".substr($driver->last_name, -2) }}<br>
                                                {{ "********".substr($driver->email, -2) }}<br>
                                                {{ "********".substr($driver->phoneNumber, -2) }}
                                             </span>
                                    </td>
                                @else
                                    <td><span class="long_text">
                                            {{ $driver->first_name." ".$driver->last_name }}
                                            <br>{{ $driver->email }}
                                            <br>
                                            {{ $driver->phoneNumber }}
                                            </span>
                                    </td>
                                @endif
                                <td>{{ $driver->CountryArea->CountryAreaName}}</td>
                                <td>
                                    @if($driver->wallet_money)
                                        <a href="{{ route('merchant.driver.wallet.show',$driver->id) }}">{{ $driver->CountryArea->Country->isoCode." ". $driver->wallet_money }}</a>
                                    @else
                                        ------
                                    @endif
                                </td>
                                <td>{{ $driver->last_bill_generated }}</td>
                                <td>{{ $driver->created_at->toDateString() }}
                                <br>
                                {{ $driver->created_at->toTimeString() }}</td>
                                <td>
                                    <a href="{{ route('accounts.show',$driver->id) }}">
                                        <button type="button" class="btn btn-info">Info</button>
                                    </a>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
{{--                    <div class="pagination1 float-right">{{ $drivers->links() }}</div>--}}
                    @include('merchant.shared.table-footer', ['table_data' => $drivers, 'data' => []])
                </div>
            </div>
        </div>
    </div>

@endsection