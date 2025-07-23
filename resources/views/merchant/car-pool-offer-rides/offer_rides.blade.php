@extends('merchant.layouts.main')
@section('content')

    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">

                    </div>
                    <h3 class="panel-title"><i class="far fa-car" aria-hidden="true"></i>
                        @lang("$string_file.offer") @lang("$string_file.ride")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{route('merchant.carpooling.offer.rides.search')}}" method="get">
                        <div class="table_search row p-3">
                            <div class="col-md-2 col-xs-6 active-margin-top">@lang("$string_file.search") @lang("$string_file.by")
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="text" name="id"
                                           placeholder="@lang("$string_file.ride") @lang("$string_file.id")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="text" name="user"
                                           placeholder="@lang("$string_file.user") @lang("$string_file.details")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="text" id="" name="date"
                                           placeholder="@lang('$string_file.from') @lang('$string_file.date')"
                                           class="form-control col-md-12 col-xs-12 customDatePicker2"
                                           id="datepickersearch" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="text" id="" name="date1"
                                           placeholder="@lang('$string_file.to') @lang('$string_file.date')"
                                           class="form-control col-md-12 col-xs-12 customDatePicker2"
                                           id="datepickersearch" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-6 form-group active-margin-top">
                                <button class="btn btn-primary" type="submit"><i
                                            class="fa fa-search"
                                            aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.ride") @lang("$string_file.id")</th>
                            <th>@lang("$string_file.merchant") @lang("$string_file.ride") @lang("$string_file.id") </th>
                            <th>@lang("$string_file.user") @lang("$string_file.details")</th>
                            <th>@lang("$string_file.area")</th>
                            <th>@lang("$string_file.ride") @lang("$string_file.status")</th>
                            <th>@lang("$string_file.ride") @lang("$string_file.date")</th>
                            <th>@lang("$string_file.booked") @lang("$string_file.seat")</th>
                            <th>@lang("$string_file.available") @lang("$string_file.seat")</th>
                            <th>@lang("$string_file.additional") @lang("$string_file.notes")</th>
                            <th>@lang("$string_file.ac_ride")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $offer_ride->firstItem() @endphp
                        @foreach($offer_ride as $offer)

                            <tr>
                                <td>{{$sr}}</td>
                                <td>{{$offer->id ? : ''}}</td>
                                <td>{{$offer->merchant_ride_id ? : ''}}</td>
                                <td>
                                    <span class="long_text">
                                        {!! is_demo_data($offer->User->UserName, $offer->Merchant) !!}<br>
                                        {!! is_demo_data($offer->User->UserPhone, $offer->Merchant) !!}<br>
                                        {!! is_demo_data($offer->User->email, $offer->Merchant) !!}
                                    </span>
                                </td>
                                <td>{{$offer->CountryArea->CountryAreaName}}</td>
                                <td>
                                    @switch($offer->ride_status)
                                        @case(1)
                                        <span>@lang("$string_file.booked")</span>
                                        @break
                                        @case(2)
                                        <span>@lang("$string_file.booked") @lang("$string_file.seat")</span>
                                        @break
                                        @case(3)
                                        <span>  @lang("$string_file.active")@lang("$string_file.ride")</span>
                                        @break
                                        @case(4)
                                        <span>@lang("$string_file.complete") @lang("$string_file.ride")</span>
                                        @break
                                        @case(5)
                                        <span>@lang("$string_file.cancel") @lang("$string_file.ride")</span>
                                        @break
                                        @case(6)
                                        <span>@lang("$string_file.auto") @lang("$string_file.cancel")  @lang("$string_file.ride")</span>
                                        @break

                                        @default
                                        <span>Something went wrong, please try again</span>
                                    @endswitch
                                </td>
                                <td>{{date('Y-m-d ',$offer->ride_timestamp)}}</td>
                                <td>{{$offer->booked_seats ? : ''}}</td>
                                <td>{{$offer->available_seats ? :  ''}}</td>

                                <td>{{$offer->additional_notes}}</td>
                                <td>{{$offer->ac_ride == 0 ? trans('$string_file.no') : ($offer->ac_ride == 1 ? trans("$string_file.yes") : ' ')}}</td>
                                <td>
                                    @if($carpooling_enable)
                                        <a href="{{route('merchant.offer.rides.details',['id'=>$offer->id])}}"
                                           data-original-title="@lang("$string_file.ride") @lang("$string_file.details") "
                                           data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-primary menu-icon btn_car action_btn">
                                            <i class="fa fa-car"></i> </a>
                                    @endif
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                        @include('merchant.shared.table-footer', ['table_data' => $offer_ride, 'data' => []])
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
