@extends('merchant.layouts.main')
@section('content')
    <style>
        #ecommerceRecentride .table-row .card-block .table td {
            vertical-align: middle !important;
            height: 15px !important;
            font-size: 14px !important;
            padding: 8px 8px !important;
        }

        .dataTables_filter, .dataTables_info {
            display: none;
        }
    </style>
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if($export_permission)
                        <a href="{{route('excel.refer',$arr_search)}}">
                            <button type="button" title="@lang("$string_file.export_excel_with_refer")"
                                    class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-download"></i>
                            </button>
                        </a>
                        <a href="{{route('excel.driver.without.refer')}}">
                            <button type="button" title="@lang("$string_file.export_excel_without_refer")"
                                    class="btn btn-icon btn-danger float-right" style="margin:10px"><i
                                        class="wb-download"></i>
                            </button>
                        </a>
                        @endif
                    </div>

                    <h3 class="panel-title">
                        <i class="icon wb-list" aria-hidden="true"></i>
                        @lang("$string_file.referral_reports")
                        </span>
                    </h3>

                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=> route("report.referral"),'method'=>'GET']) !!}
                    <div class="table_search row">
                        <div class="col-md-4 col-xs-12 form-group active-margin-top">
                            <div class="input-daterange" data-plugin="datepicker">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                          <span class="input-group-text">
                            <i class="icon wb-calendar" aria-hidden="true"></i>
                          </span>
                                    </div>
                                    <input type="text" class="form-control" name="start" value="{{ old('start',isset($arr_search['start']) ? $arr_search['start'] : "") }}"/>
                                </div>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">to</span>
                                    </div>
                                    <input type="text" class="form-control" name="end" value="{{ old('end',isset($arr_search['end']) ? $arr_search['end'] : "") }}"/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-xs-12 form-group active-margin-top">
                            <input type="text" class="form-control" name="referral_code" placeholder="Referral Code" value="{{ old('end',isset($arr_search['referral_code']) ? $arr_search['referral_code'] : "") }}"/>
                        </div>
                        <div class="col-sm-2  col-xs-12 form-group active-margin-top">
                            <button class="btn btn-primary" type="submit" name="seabt12"><i class="fa fa-search"
                                                                                            aria-hidden="true"></i>
                            </button>
                            <a href="{{route("report.referral")}}">
                                <button class="btn btn-success" type="button"><i class="fa fa-refresh"
                                                                                 aria-hidden="true"></i></button>
                            </a>
                        </div>
                    </div>
                    {!! Form::close() !!}
                    <hr>
                    <!-- First Row -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-warning">
                                        <i class="icon wb-user-add"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400">@lang("$string_file.user_referral")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-20 font-weight-100">{{$states_data['user_referral']}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-3 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-danger">
                                        <i class="icon wb-user-add"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400">@lang("$string_file.driver_referral")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-20 font-weight-100">{{$states_data['driver_referral']}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-3 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-danger">
                                        <i class="icon fa-dollar"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400">@lang("$string_file.user_referral_amount")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-20 font-weight-100">{{$states_data['user_referral_amount']}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-3 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-danger">
                                        <i class="icon fa-dollar"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400">@lang("$string_file.driver_referral_amount")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-20 font-weight-100">{{$states_data['driver_referral_amount']}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <!-- Third Row -->
                    <!-- Third Left -->
                    <div class="row">
                        <div class="col-lg-12" id="ecommerceRecentride">
                            <div class="card card-shadow table-row">
                                <div class="card-block bg-white table-responsive">
                                    <table id="customDataTable"
                                           class="display nowrap table table-hover table-bordered report_table"
                                           style="width:100%">
                                        <thead>
                                        <tr class="text-center">
                                            <th>@lang("$string_file.sn")</th>
                                            <th>@lang("$string_file.sender")</th>
                                            <th>@lang("$string_file.receiver")</th>
                                            <th>@lang("$string_file.total_refer")</th>
                                            <th>@lang("$string_file.date_used")</th>
                                            @if(isset($arr_search['start']) && isset($arr_search['end']))
                                                <th>@lang("$string_file.created_date_at") @lang("$string_file.in_range") <br> ({{$arr_search['start']}} - {{$arr_search['end']}})</th>
                                            @else
                                                <th>@lang("$string_file.created_date_at")</th>
                                            @endif
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php $sr = 1; @endphp
                                        @if(isset($referral_details) && !empty($referral_details))
                                            @foreach($referral_details as $referral_detail)
                                                @if(isset($referral_detail->sender_details) && !empty($referral_detail->sender_details))
                                                    <tr>
                                                        <td>{{ $sr }}</td>
                                                        <td>
                                                            {{ is_demo_data($referral_detail->sender_details['name'], $referral_detail->Merchant) }}<br>
                                                            {{ is_demo_data($referral_detail->sender_details['phone'], $referral_detail->Merchant) }}<br>
                                                            {{ is_demo_data($referral_detail->sender_details['email'], $referral_detail->Merchant) }}<br>
                                                            <b>Type : </b>{{$referral_detail->sender_details['type']}}
                                                        </td>
                                                        <td>
                                                            @php $receiverCounter = 0;@endphp
                                                            @if(isset($referral_detail->receiver_details) && !empty($referral_detail->receiver_details))
                                                                @foreach($referral_detail->receiver_details as $receiver)
                                                                    @php $receiverCounter++; @endphp
                                                                    @if ($receiverCounter < 2)
                                                                        {{ is_demo_data($receiver['name'], $referral_detail->Merchant) }}<br>
                                                                        {{ is_demo_data($receiver['phone'], $referral_detail->Merchant) }}<br>
                                                                        {{ is_demo_data($receiver['email'], $referral_detail->Merchant) }}<br>
                                                                        <b>Type : </b>{{ $receiver['type'] }}<br>
                                                                    @elseif($receiverCounter == 1)
                                                                        {{ is_demo_data($receiver['name'], $referral_detail->Merchant) }}<br>
                                                                        {{ is_demo_data($receiver['phone'], $referral_detail->Merchant) }}<br>
                                                                        {{ is_demo_data($receiver['email'], $referral_detail->Merchant) }}<br>
                                                                        <b>Type : </b>{{ $receiver['type'] }}<br>
                                                                    @endif
                                                                @endforeach
                                                            @endif
                                                            <a href="#"><label style="cursor:pointer;width: 35%;"
                                                                               onclick="checkReferralDiscount({{$referral_detail->id}})"
                                                                               class="label label_success"
                                                                               referral-discount-id="{{$referral_detail->id}}">@lang("$string_file.full_details")</label></a>
                                                        </td>
                                                        <td> {{!empty($referral_detail->receiver_details) ? count($referral_detail->receiver_details) : NULL}} </td>
                                                       

                                                         <td>
                                                             {{isset($referral_detail->receiver_details) && !empty($referral_detail->receiver_details) && isset(($referral_detail->receiver_details)[0]) && isset(($referral_detail->receiver_details)[0]['date'])? ($referral_detail->receiver_details)[0]['date']->toDateString() : $referral_detail->created_at->toDateString() }}
                                                         </td>

                                                         
                                                        <td>{{$referral_detail->created_at->toDateString()}}
                                                    </tr>
                                                    @php $sr++ @endphp
                                                @endif
                                            @endforeach
                                        @endif
                                        </tbody>
                                    </table>
                                    @include('merchant.shared.table-footer', ['table_data' => $referral_details, 'data' => $arr_search])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="receiver-details" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="exampleModalCenterTitle">@lang("$string_file.referral_of") <label id="sender-name"></label></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="model-data">

                </div>
            </div>
        </div>
    </div>
@endsection
@section("js")
    <script>
        function checkReferralDiscount(referral_discount_id) {
            $("#model-data").html(null);
            $("#sender-name").html(null);
            $("#loader1").show();
            $.ajax({
                method: 'GET',
                url: '<?php echo route('report.referral.receiver-details') ?>',
                data: {
                    referral_discount_id: referral_discount_id,
                },
                success: function (data) {
                    if (data.status == "success") {
                        $("#model-data").html(data.data.view);
                        $("#sender-name").html(data.data.name);
                        $('#receiver-details').modal('toggle');
                    } else {
                        alert(data.message);
                    }
                }
            });
            $("#loader1").hide();
        }
    </script>
@endsection
