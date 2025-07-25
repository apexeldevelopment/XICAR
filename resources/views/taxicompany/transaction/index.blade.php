@extends('taxicompany.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <haeder class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('excel.taxicompany-transaction')}}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                <i class="wb-download" title="@lang("$string_file.export_excel")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="wb-info" aria-hidden="true"></i>
                        @lang("$string_file.taxi_company_transaction")
                    </h3>
                </haeder>
                <div class="panel-body container-fluid">
                    <form method="post" action="{{ route('taxicompany.transaction.search') }}">
                        @csrf
                        <div class="table_search row">
                            <div class="col-md-1 col-xs-12 form-group ">
                                @lang("$string_file.search_by"):
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="booking_id"
                                           placeholder="@lang("$string_file.ride_id")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="rider"
                                           placeholder="@lang("$string_file.user_details")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="driver"
                                           placeholder="@lang("$string_file.driver_details")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>

                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="date"
                                           placeholder="@lang("$string_file.from_date")" readonly
                                           class="form-control col-md-12 col-xs-12 datepickersearch bg-this-color"
                                           id="datepickersearch">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="date1"
                                           placeholder="@lang("$string_file.to_date")" readonly
                                           class="form-control col-md-12 col-xs-12 datepickersearch bg-this-color"
                                           id="datepickersearch">
                                </div>
                            </div>
                            <div class="col-sm-1  col-xs-12 form-group ">
                                <button class="btn btn-primary" type="submit" name="seabt12"><i
                                            class="fa fa-search" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                        <thead>
                        <th>@lang("$string_file.sn")</th>
                        <th>@lang("$string_file.ride_id")</th>
                        <th>@lang("$string_file.ride_type")</th>
                        <th>@lang("$string_file.service_area")</th>
                        <th>@lang("$string_file.commission_type")</th>
                        <th>@lang("$string_file.user_details")</th>
                        <th>@lang("$string_file.driver_details")</th>
                        <th>@lang("$string_file.payment")</th>
                        <th>@lang("$string_file.total_amount")</th>
                        <th>@lang("$string_file.promo_discount")</th>
                        <th>@lang("$string_file.tax")</th>
                        <th>@lang("$string_file.company_cut")</th>
                        <th>@lang("$string_file.driver_cut")</th>
                        <th>@lang("$string_file.total_payout")</th>
                        <th>@lang("$string_file.total_outstanding")</th>
                        <th>@lang("$string_file.travelled_distance")</th>
                        <th>@lang("$string_file.travelled_time")</th>
                        <th>@lang("$string_file.estimated_bill")</th>
                        <th>@lang("$string_file.date")</th>
                        <th>@lang("$string_file.details")</th>
                        </thead>
                        <tbody>
                        @php $s = 0; @endphp
                        @foreach($transactions as $transaction)
                            <tr>
                                <td><a target="_blank" class="address_link"
                                       href="{{ route('taxicompany.booking.details',$transaction->id) }}">{{ ++$s }}</a>
                                </td>
                                <td>{{ $transaction->id }}</a>
                                </td>
                                <td>
                                    @if($transaction->booking_type == 1)
                                        @lang("$string_file.ride_now")
                                    @else
                                        @lang("$string_file.ride")  @lang("$string_file.later")
                                    @endif

                                </td>
                                <td>{{ $transaction->CountryArea->CountryAreaName }}</td>
                                <td>
                                    @if(isset($transaction['BookingTransaction']['commission_type']) && $transaction['BookingTransaction']['commission_type'] == 1)
                                        @lang("$string_file.pre_paid")
                                    @elseif(isset($transaction['BookingTransaction']['commission_type']) && $transaction['BookingTransaction']['commission_type'] == 2)
                                        @lang("$string_file.post_paid")
                                    @else
                                        ----
                                    @endif
                                </td>
                                <td><span class="long_text">
                                                                {{ $transaction->User->UserName }}
                                                                <br>
                                                                {{ $transaction->User->UserPhone }}
                                                                <br>
                                                                {{ $transaction->User->email }}
                                                                </span>
                                </td>
                                <td><span class="long_text">
                                                                {{ $transaction->Driver->first_name." ".$transaction->Driver->last_name }}
                                                                <br>
                                                                {{ $transaction->Driver->phoneNumber }}
                                                                <br>
                                                                {{ $transaction->Driver->email }}
                                                                </span>
                                </td>
                                <td>{{  $transaction->PaymentMethod->payment_method }}</td>
                                <td>@if(!empty($transaction['BookingTransaction'])) {{  $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['customer_paid_amount'] }}
                                    @else {{  $transaction->CountryArea->Country->isoCode." ".$transaction->final_amount_paid }} @endif
                                </td>
                                <td>@if(!empty($transaction['BookingTransaction'])) {{  $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['discount_amount'] }}
                                    @else {{  $transaction->CountryArea->Country->isoCode." ".$transaction['BookingDetail']['promo_discount'] }} @endif </td>
                                <td>@if(!empty($transaction['BookingTransaction'])) {{  $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['tax_amount'] }}
                                     @else {{  $transaction->CountryArea->Country->isoCode." 0" }} @endif
                                </td>
                                <td>@if(!empty($transaction['BookingTransaction'])) {{  $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['company_earning'] }}
                                    @else {{  $transaction->CountryArea->Country->isoCode." ".$transaction->company_cut }} @endif
                                </td>
                                <td>@if(!empty($transaction['BookingTransaction'])) {{  $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['driver_earning'] }}
                                    @else {{  $transaction->CountryArea->Country->isoCode." ".$transaction->driver_cut }} @endif
                                </td>
                                <td>@if(!empty($transaction['BookingTransaction'])) {{  $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['driver_total_payout_amount'] }}
                                    @else {{  $transaction->CountryArea->Country->isoCode." ".$transaction->driver_cut }} @endif
                                </td>
                                <td>@if(!empty($transaction['BookingTransaction'])) {{  $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['trip_outstanding_amount'] }}
                                    @else {{  $transaction->CountryArea->Country->isoCode." 0" }} @endif
                                </td>
                                <td>{{  $transaction->travel_distance }}</td>
                                <td>{{  $transaction->travel_time }}</td>
                                <td>{{  $transaction->CountryArea->Country->isoCode." ".$transaction->estimate_bill }}</td>
                                <td>{!! convertTimeToUSERzone($transaction->created_at, $transaction->CountryArea->timezone,null,$transaction->Merchant) !!}</td>
                                <td> <span class="openPopup" data-href="{{ $transaction->BookingDetail->bill_details }}" data-id="{{ $transaction->id }}">
                                            <a data-original-title="Bill Details" data-toggle="tooltip" id="Model" data-placement="top"
                                               class="btn btn-sm btn-default btn-outline-secondary btn"> <h7>@lang("$string_file.bill_details") </h7></a></span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="pagination1 float-right">{{ $transactions->links() }}</div>
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
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <input type="reset" class="btn btn-outline-secondary btn" data-dismiss="modal"
                           value="@lang("$string_file.close")">
                </div>
            </div>
        </div>
    </div>
@endsection