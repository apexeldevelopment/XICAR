@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(Auth::user()->demo == 1)
                            <a href="">
                                <button type="button" class="btn btn-icon btn-primary mr-1 float-right" data-original-title="@lang("$string_file.export_excel")" data-toggle="tooltip"><i
                                            class="fa fa-download"></i>
                                </button>
                            </a>
                        @else
                            <a href="{{route('excel.driveronlinetimereport',$data)}}">
                                <button type="button" class="btn btn-icon btn-primary mr-1 float-right" data-original-title="@lang("$string_file.export_excel")" data-toggle="tooltip"><i
                                            class="fa fa-download"></i>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title">
                        <i class=" wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.driver_online_time")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('report.driver.online.time') }}">
                        <div class="table_search">
                            <div class="row">
                                <div class="col-md-2 form-group ">
                                    <div class="input-group">
                                        <input type="text" id="" name="driver_name"
                                               placeholder="@lang("$string_file.first_name")"
                                               class="form-control col-md-12 col-xs-12">
                                    </div>
                                </div>
                                <div class="col-md-3 form-group ">
                                    <div class="input-group">
                                        <input type="text" id="" name="email"
                                               placeholder="@lang("$string_file.email")"
                                               class="form-control col-md-12 col-xs-12">
                                    </div>
                                </div>
                                <div class="col-sm-2 form-group ">
                                    <button class="btn btn-primary" type="submit" name="seabt12"><i
                                                class="fa fa-search" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.driver_details")</th>
                            <th>@lang("$string_file.online_time_text")</th>
                            <th>@lang("$string_file.offline_time_text")</th>
                            <th>@lang("$string_file.total_login_hour")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $driver_times->firstItem() @endphp
                        @foreach($driver_times as $driver_time)
                            <tr>
                                <td>{{ $sr }}  </td>
                                <td>
                                    <span class="long_text">
                                        {{ is_demo_data($driver_time->Driver->fullName,$driver_time->Driver->Merchant) }}<br>
                                        {{ is_demo_data($driver_time->Driver->phoneNumber,$driver_time->Driver->Merchant) }}<br>
                                        {{ is_demo_data($driver_time->Driver->email,$driver_time->Driver->Merchant) }}
                                    </span>
                                </td>
                                <td data-toggle="modal" data-target="#myModall{{$driver_time->id}}"><u>{{ convertTimeToUSERzone($driver_time->time_intervals[0]['online_time'], $driver_time->Driver->CountryArea->timezone) }}</u></td>
                                @php $endtime = $driver_time->time_intervals @endphp
                                <td> @if(end($endtime)['offline_time']) {{ convertTimeToUSERzone(end($endtime)['offline_time'], $driver_time->Driver->CountryArea->timezone, NULL,$driver_time->Driver->Merchant) }} @else --- @endif</td>
                                <td> {{ $driver_time->hours }} Hours {{$driver_time->minutes}} Minutes </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $driver_times, 'data' => $data])
                </div>
            </div>
        </div>
    </div>
    @foreach($driver_times as $driver_time)
        <div class="modal fade text-center" id="myModall{{$driver_time->id}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
             aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <label class="modal-title text-text-bold-600"
                               id="myModalLabel33"><b>@lang("$string_file.driver_online_time_detail") : {{ $driver_time->Driver->fullName }}</b></label>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body col-md-12">
                        <table class="table table-striped">
                            <tr>
                                <th style="width:30%;">@lang("$string_file.online_time_text")</th>
                                <th style="width:30%;">@lang("$string_file.offline_time_text")</th>
                                <th>@lang("$string_file.total_login_hour")</th>
                            </tr>
                            @php $online_offline_times = $driver_time->time_intervals @endphp
                            @foreach($online_offline_times as $online_offline_time)
                                <tr>
                                    <td>@if($online_offline_time['online_time']) {{convertTimeToUSERzone($online_offline_time['online_time'], $driver_time->Driver->CountryArea->timezone)}} @else ---- @endif</td>
                                    <td>@if($online_offline_time['offline_time']) {{convertTimeToUSERzone($online_offline_time['offline_time'], $driver_time->Driver->CountryArea->timezone)}} @else ---- @endif</td>
                                    <td>{{$online_offline_time['hours']}} Hours {{$online_offline_time['minutes']}} Minutes</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
