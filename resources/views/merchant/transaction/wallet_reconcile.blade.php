@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title"><i class="fa-exchange" aria-hidden="true"></i>
                        @lang("$string_file.wallet")  @lang("$string_file.reconcile")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="post" action="{{ route('Wallet.reconcile.save') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="table_search row">
                            <div class="col-md-4  form-group ">
                                <div class="input-group">
                                    <input type="file" id="" name="wallet_reconcile_sheet"
                                           placeholder="@lang("$string_file.reconcile") @lang("$string_file.sheet")"
                                           class="form-control col-md-12 ">
                                </div>
                            </div>

                            <div class="col-sm-1  col-xs-12 form-group ">
                                <button class="btn btn-primary" type="submit" name="seabt12">@lang("$string_file.save")
                                </button>
                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                        <thead>
                        <th>@lang("$string_file.sn")</th>
                        <th>@lang("$string_file.driver_details")</th>
                        <th>@lang("$string_file.transaction_type") </th>
                        <th>@lang("$string_file.amount")</th>
                        <th>@lang("$string_file.description")</th>
                        <th>@lang("$string_file.date")</th>
                        </thead>
                        <tbody>
                        @php $s = 0; @endphp
                        @foreach ($transactions as $transaction)
                            @php $s++; @endphp

                            <tr>
                            <td>{{$s}}</td>
                            <td>{{$transaction->Driver->first_name}} {{$transaction->Driver->last_name}}<br>
                                {{$transaction->Driver->email}}<br>
                                {{$transaction->Driver->phoneNumber}}<br>
                            </td>
                            <td>{{$transaction->type}}</td>
                            <td>{{$transaction->total_amount}}</td>
                            <td>{{$transaction->narration}}</td>
                            <td>{{convertTimeToUSERzone($transaction->created_at, $transaction->Driver->CountryArea->timezone, null, $transaction->Driver->Merchant)}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $transactions, 'data' => []])
                    {{--                    <div class="pagination1 float-right">{{ $transactions->links() }}</div>--}}
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade text-left" id="detailBooking" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33"><b>@lang("$string_file.transaction")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <input type="reset" class="btn btn-outline-secondary btn" data-dismiss="modal"
                           value="@lang("$string_file.close")">
                </div>
            </div>
        </div>
    </div>
@endsection