@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="mr--10 ml--10">
                        <div class="row " style="margin-right: 0rem;margin-left: 0rem">
                            <div class="col-md-2 col-sm-5">
                                <h3 class="panel-title"><i class="fa-users" aria-hidden="true"></i>
                                    @lang("$string_file.deleted_driver")</h3>
                            </div>
                            <div class="col-md-10 col-sm-7"></div>
                        </div>
                    </div>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th> @lang("$string_file.id")</th>
                            <th>@lang("$string_file.service_area") </th>
                            <th>@lang("$string_file.driver_details")</th>
                            <th>@lang("$string_file.service_statistics")</th>
                            <th>@lang("$string_file.transaction_amount")</th>
                            <th>@lang("$string_file.registered_date")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.last_location_date_time")  </th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $drivers->firstItem() @endphp
                        @foreach($drivers as $driver)
                            <tr>
                                <td>{{$sr}}</td>
                                <td><a href="{{ route('driver.show',$driver->id) }}"
                                       class="hyperLink">{{ $driver->merchant_driver_id }}</a>
                                </td>
                                <td>
                                    {{ $driver->CountryArea->CountryAreaName }}
                                </td>
                                <td>
                                    <span class="long_text">
                                        {{ is_demo_data($driver->fullName,$driver->Merchant) }}<br>
                                        {{ is_demo_data($driver->phoneNumber,$driver->Merchant) }}<br>
                                        {{ is_demo_data($driver->email,$driver->Merchant) }}
                                    </span>
                                </td>
                                <td>
                                    @if($driver->segment_group_id == 1)
                                        @php
                                            $arr_segment = array_pluck($driver->Segment,'slag');

                                        @endphp
                                        @if(array_intersect($arr_segment,$booking_segment))
                                            @php
                                                $bookings = $driver->Booking->where('booking_status',1005)->count();

                                            @endphp
                                            <a href="{{ route('merchant.driver.jobs',['booking',$driver->id]) }}">
                                                <span class="badge badge-success font-weight-100">@lang("$string_file.rides") : {{ $bookings }}</span>
                                            </a>
                                            <br>
                                        @endif
                                        @if(array_intersect($arr_segment,$order_segment))
                                            @php
                                                $orders = $driver->Order->where('order_status',11)->count();
                                            @endphp
                                            <a href="{{ route('merchant.driver.jobs',['order',$driver->id]) }}">
                                                <span class="badge badge-success font-weight-100">@lang("$string_file.orders") : {{ $orders }}</span>
                                            </a>
                                        @endif
                                    @else
                                        @php
                                            $handyman_orders = isset($driver->HandymanOrder) ? $driver->HandymanOrder->where('order_status',7)->count() : 0;
                                        @endphp
                                        <a href="{{ route('merchant.driver.jobs',['handyman-order',$driver->id]) }}">
                                            <span class="badge badge-success font-weight-100">@lang("$string_file.bookings") : {{ $handyman_orders }}</span>
                                        </a>
                                    @endif
                                    <br>
                                    @lang("$string_file.rating") :
                                    @if (!empty($driver->rating) && $driver->rating>0)
                                        @while($driver->rating>0)
                                            @if($driver->rating >0.5)
                                                <img src="{{ view_config_image('static-images/star.png') }}"
                                                     alt='Whole Star'>
                                            @else
                                                <img src="{{ view_config_image('static-images/halfstar.png') }}"
                                                     alt='Half Star'>
                                            @endif
                                            @php $driver->rating--; @endphp
                                        @endwhile
                                    @else
                                        @lang("$string_file.not_rated_yet")
                                    @endif
                                </td>
                                <td style="width:250px;float:left">
                                    @php
                                    $merchant = new \App\Http\Controllers\Helper\Merchant();
                                    @endphp
                                    @if($driver->total_earnings)
                                        @lang("$string_file.earning")
                                        :- {{ $driver->CountryArea->Country->isoCode." ". $merchant->PriceFormat($merchant->TripCalculation($driver->total_earnings, $driver->merchant_id), $driver->merchant_id) }}
                                    @else
                                        @lang("$string_file.earning")
                                        :- @lang("$string_file.no_services")
                                    @endif
                                    <br>
                                    @if($driver->total_comany_earning)
                                        @lang("$string_file.company_profit")
                                        :- {{ $driver->CountryArea->Country->isoCode." ".$driver->total_comany_earning }}
                                    @else
                                        ---
                                    @endif
                                    
                                    <br>
                                </td>
                                <td>{!! convertTimeToUSERzone($driver->created_at, $driver->CountryArea->timezone, null, $driver->Merchant) !!}</td>
                                @php $sr++; @endphp
                        @endforeach
                        </tbody>
                    </table>
                    </div>
            </div>
        </div>
    </div>
@endsection