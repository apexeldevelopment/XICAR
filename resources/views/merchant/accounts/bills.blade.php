@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content container-fluid">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-md-6 col-sm-7">
                            <h3 class="panel-title">
                                <a href="{{ route('driver.show',$driver->id) }}" target="_blank">{{ $driver->fullName }}</a> @lang("common.bill") </h3>
                        </div>
                        <div class="col-md-6 col-sm-5">
                            <a href="{{ route('accounts.index') }}" data-toggle="tooltip">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px"><i class="wb-reply" title="@lang("$string_file.driver") @lang("common.account")"></i>
                                </button>
                            </a>
                            <a href="{{ route('accounts.edit',$driver->id) }}">
                                <button class="btn btn-icon btn-primary float-right" style="margin:10px">@lang("$string_file.generate") @lang("common.bill") @lang("common.of") {{ $driver->fullName }}
                                </button>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="panel-body container-fluid">
{{--                    <div class="table-wrapper-scroll-x my-custom-scrollbar">--}}
                        <table id="customDataTable" class="display nowrap table table-hover table-striped w-full">
                            <thead>
                            <tr>
                                <th>@lang("common.sn")</th>
                                <th>@lang("common.bill") @lang("common.date") </th>
                                <th>@lang('admin.message472')</th>
                                <th>@lang('admin.total_before_discount_tax')</th>
                                <th>@lang("common.company") @lang("common.cut")</th>
                                <th>@lang('admin.driverCut')</th>
                                <th>@lang('admin.cash')</th>
                                <th>@lang("common.bill") @lang("common.amount")</th>
                                <th>@lang('admin.cancel_charges_receive')</th>
                                <th>@lang('admin.tip_charge')</th>
                                <th>@lang('admin.toll_charge')</th>
                                <th>@lang("$string_file.referral") @lang("common.amount")</th>
                                <th>@lang('admin.trip_outstanding_amount')</th>
                                <th>@lang('admin.message474')</th>
                                <th>@lang('admin.message473')</th>
                                <th>@lang("common.reference") @lang("common.no")</th>
                                <th>@lang('admin.message477')</th>
                                <th>@lang('admin.message475')</th>
                                <th>@lang('admin.message476')</th>
                                {{--<th>@lang('admin.block_date')</th>
                                <th>@lang('admin.due_date')</th>
                                <th>@lang('admin.fee_after_grace_period')</th>--}}
                                <th>@lang("common.action")</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $sr = $bills->firstItem() @endphp
                            @foreach($bills as $bill)
                                <tr>
                                    <td>{{ $sr }}</td>
                                    <td>{{ $bill->created_at }}</td>
                                    <td>
                                        {{ $bill->from_date }}
                                        <br>
                                        To
                                        <br>
                                        {{ $bill->to_date }}
                                    </td>
                                    <td>{{ $bill->fare_amount }}</td>
                                    <td>{{ $bill->company_commission }}</td>
                                    <td>{{ $bill->amount + $bill->cash_payment_received }}</td>
                                    <td>{{ $bill->cash_payment_received }}</td>
                                    <td>{{ $bill->amount }}</td>
                                    <td>{{ $bill->cancellation_charges }}</td>
                                    <td>{{ $bill->tip_amount }}</td>
                                    <td>{{ $bill->toll_amount }}</td>
                                    <td>{{ $bill->referral_amount }}</td>
                                    <td>{{ $bill->trips_outstanding_sum + $bill->referral_amount }}</td>
                                    <td>
                                        @if($bill->status == 1)
                                            @lang("$string_file.un_settled")
                                        @else
                                            @lang("$string_file.settled")
                                        @endif
                                    </td>
                                    <td>
                                        {{ $bill->CreateBy->merchantFirstName }}
                                        <br>
                                        {{ $bill->CreateBy->merchantPhone }}
                                        <br>
                                        {{ $bill->CreateBy->email }}
                                    </td>
                                    <td>
                                        @if($bill->referance_number)
                                            {{ $bill->referance_number }}
                                        @else
                                            ------
                                        @endif
                                    </td>
                                    <td>
                                        @if($bill->settle_type)
                                            @if($bill->settle_type == 1)
                                                @lang("common.cash")
                                            @else
                                                @lang("common.non_cash")
                                            @endif
                                        @else
                                            ------
                                        @endif
                                    </td>
                                    <td>
                                        @if($bill->settle_by)
                                            {{ $bill->SettleBy->merchantFirstName }}
                                            <br>
                                            {{ $bill->SettleBy->merchantPhone }}
                                            <br>
                                            {{ $bill->SettleBy->email }}
                                        @else
                                            ------
                                        @endif
                                    </td>
                                    <td>
                                        @if($bill->settle_date)
                                            {{ $bill->settle_date }}
                                        @else
                                            ------
                                        @endif
                                    </td>

                                {{--<td>
                                    @if($bill->block_date)
                                        {{ $bill->block_date }}
                                    @else
                                        ------
                                    @endif
                                </td>

                                <td>
                                    @if($bill->due_date)
                                        {{ $bill->due_date }}
                                    @else
                                        ------
                                    @endif
                                </td>

                                <td>
                                    @if($bill->fee_after_grace_period)
                                        {{ $bill->fee_after_grace_period }}
                                    @else
                                        ------
                                    @endif
                                </td>--}}

                                <td>
                                    @if($bill->status == 1)
                                        <button type="button" id="{{ $bill->id }}"
                                                class="btn btn-primary"
                                                data-toggle="modal"
                                                data-target="#settlementBill">@lang('admin.settle')
                                        </button>
                                    @else
                                        <br>
                                        <button type="button" id="{{ $bill->id }}"
                                                class="btn btn-info btn-sm" onclick="send_email(this);">@lang('admin.send_email')
                                        </button>
                                        <br> &nbsp;
                                        <a href="{{ route('merchant.DriverBill',$bill->id) }}" target="_blank">
                                            <button class="btn btn-secondary btn-sm" style="position:relative;">
                                               @lang("common.invoice")
                                            </button>
                                        </a>

                                        @endif
                                    </td>
                                </tr>
                                @php $sr++  @endphp
                            @endforeach
                            </tbody>
                        </table>
{{--                    </div>--}}
                    <div class="col-sm-12">
                        <div class="pagination1">{{ $bills->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade text-left" id="settlementBill" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600" id="myModalLabel33">@lang('admin.message481')</label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('accounts.store') }}" enctype="multipart/form-data" method="post">
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
@section('js')
    <script>
        function send_email(data){
            var token = $('[name="_token"]').val();
            console.log(token);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: "POST",
                url: "{{ route('merchant.billDriverEmail') }}",
                cache: false,
                data: {
                    param: data.id,
                },
                success: function (data) {
                    console.log('Success');
                    window.location.reload();
                },
                error:function(data){
                    console.log('failed');
                }
            });
        }
    </script>
@endsection