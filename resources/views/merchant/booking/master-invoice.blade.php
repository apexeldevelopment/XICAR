@extends('merchant.layouts.main')
@section('content')
<style>
    @media print {
        body * {
            visibility: hidden;
        }

        #section-to-print,
        #section-to-print * {
            visibility: visible;
        }

    }
</style>
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <div class="panel-actions">

                    <a href="{{ URL::previous() }}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="fa fa-reply"></i>
                        </button>
                    </a>
                    <button class="btn btn-icon btn-warning float-right print_invoice" style="margin:10px;width:115px;"><i class="icon wb-print" aria-hidden="true"></i>
                        @lang("$string_file.print")
                    </button>
                </div>
                <h3 class="panel-title">
                    <i class="wb-flag" aria-hidden="true"></i>
                    @lang("$string_file.master_invoice")
                </h3>
            </header>
            @if(Auth::user()->tax)
            <div class="panel-heading"> @php $a = json_decode(Auth::user()->tax,true);echo $a['name'] @endphp
                <strong>@php $a = json_decode(Auth::user()->tax,true);echo $a['tax_number'] @endphp </strong>
            </div>
            @endif
            <div id="section-to-print" class="panel">
                <div class="panel-body container-fluid printableArea">
                    <table id="" class="display nowrap table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>@lang("$string_file.sn")</th>
                                <th>@lang("$string_file.ride_id")</th>
                                <th>@lang("$string_file.user_details")</th>
                                <th>@lang("$string_file.bill_amount")</th>
                                <th>@lang("$string_file.date")</th>
                                <th>@lang("$string_file.pickup_drop")</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sr = 1; $before_discount = 0; $tax= 0; $discount = 0; $total=0; $paid =0; @endphp
                            @foreach($bookings as $booking)


                            @php $before_discount+= (isset($booking->BookingTransaction->sub_total_before_discount) ? $booking->BookingTransaction->sub_total_before_discount : 0.0);
                            $tax+= (isset($booking->BookingTransaction->tax_amount) ? $booking->BookingTransaction->tax_amount : 0.0);
                            $discount+= (isset($booking->BookingTransaction->discount_amount) ? $booking->BookingTransaction->discount_amount : 0.0);
                            $total+=$booking->final_amount_paid;
                            $paid+= (isset($booking->BookingTransaction->customer_paid_amount) ?  $booking->BookingTransaction->customer_paid_amount : 0.0);
                            @endphp

                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{ $booking->merchant_booking_id }}</td>


                                @if(Auth::user()->demo == 1)
                                <td>
                                    <span class="long_text">
                                        {{ "********".substr($booking->User->UserName,-2) }}
                                        <br>
                                        {{ "********".substr($booking->User->UserPhone,-2) }}
                                        <br>
                                        {{ "********".substr($booking->User->email,-2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="long_text">
                                        {{ "********".substr($booking->Driver->last_name,-2) }}
                                        <br>
                                        {{ "********".substr($booking->Driver->phoneNumber,-2) }}
                                        <br>
                                        {{ "********".substr($booking->Driver->email,-2) }}
                                    </span>
                                </td>
                                @else
                                <td>
                                    <span class="long_text">
                                        {{ $booking->User->UserName }}
                                        <br>
                                        {{ $booking->User->UserPhone }}
                                        <br>
                                        {{ $booking->User->email }}
                                    </span>
                                </td>
                                @endif
                                <td>
                                    {{ $booking->CountryArea->Country->isoCode . $booking->final_amount_paid }}
                                </td>
                                <td>
                                    {!! convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone,null,$booking->Merchant) !!}

                                </td>
                                <td>
                                    @if(!empty($booking->BookingDetail->start_location))
                                    {{ $booking->BookingDetail->start_location }} <br>

                                    @endif
                                    @if(!empty($booking->BookingDetail->end_location))


                                    @endif
                                </td>
                            </tr>
                            @php $sr++ @endphp
                            @endforeach
                        </tbody>
                    </table>
                    <hr>

                    <div class="row mt-20 mb-20">

                        <div class="col-lg-12 col-md-12 col-sm-12 mt-25">
                            <h4 class="ml-10">@lang("$string_file.invoice_summary")</h4>
                            <table class="table table-default" id="dataTable">
                                <tbody>
                                    <thead>
                                        <tr>
                                            <th colspan="2">@lang("$string_file.total_rides"): {{$sr}} </th>
                                        <tr>
                                    </thead>
                                    <tr>
                                        <th class="left">@lang("$string_file.sub_total_before_discount")</th>
                                        <th class="right">{{ $before_discount }}</th>
                                    </tr>
                                    <tr>
                                        <th class="left">@lang("$string_file.total_discount")</th>
                                        <th class="right">{{ $discount }}</th>
                                    </tr>
                                    <tr>
                                        <th class="left">@lang("$string_file.tax")
                                        </th>
                                        <th class="right">{{ $tax }}
                                        </th>
                                    </tr>
                                    <tr>
                                        <th class="left">@lang("$string_file.total_amount")</th>
                                        <th class="right">{{ $total }}</th>
                                    </tr>
                                    <tr>
                                        <th class="left">@lang("$string_file.final_amount_paid")
                                        </th>
                                        <th class="right">{{ $paid }}
                                            </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
@section('js')
<script src="{{ asset('js/jquery.PrintArea.js')}}" type="text/javascript"></script>
<script>
    $(document).ready(function() {
        $(".print_invoice").click(function() {
            var mode = 'popup'; //popup
            var close = mode == "popup";
            var options = {
                mode: mode,
                popClose: true,
                popHt: 900,
                popWd: 900


            };
            // window.print();
            $(".printableArea").printArea(options);
        });
    });
</script>
@endsection