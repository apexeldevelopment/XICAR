@extends('merchant.layouts.main')
@section('content')
    @php
        $segment = App\Http\Controllers\Helper\Merchant::MerchantSegments();
    @endphp
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(Auth::user('merchant')->can('create_rider'))
                            <a href="{{route('excel.earning')}}" data-toggle="tooltip">
                                <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                    <i class="wb-download" title="@lang("common.export") @lang("common.excel")"></i>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="wb-earning" aria-hidden="true"></i>
                        @lang("$string_file.ride") @lang("common.earning") @lang("common.statics")</h3>
                </header>

                <div class="panel-body container-fluid">

                    <form action="{{ route('merchant.carpooling.earning.search') }}" method="get">
                        <div class="col-md-2 col-xs-6 active-margin-top">@lang("common.search") @lang("common.by")
                            :
                        </div>
                        <br>
                        <div class="table_search row p-3">

                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">

                                    <input type="text" name="ride_id" value=""
                                           placeholder="@lang("common.enter") @lang("common.ride") @lang("common.id")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="text" name="keyword"
                                           placeholder="@lang("common.enter") @lang("common.email")/@lang("common.phone")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <select class="form-control" name="country_id"
                                            id="country_id">
                                        <option value="">--@lang("$string_file.service_area")--</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}">
                                                {{   $country->LanguageSingle->AreaName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="date" name="start"
                                           placeholder="@lang("common.start") @lang("common.date")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class=" col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="date" name="end"
                                           placeholder="@lang("common.end") @lang("common.date")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-6 form-group active-margin-top">
                                <button class="btn btn-primary" type="submit"><i
                                            class="fa fa-search"
                                            aria-hidden="true">&nbsp;&nbsp;Search&nbsp;</i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-xl-3 col-md-6 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-warning">
                                        <i class="icon fa-cab"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400">@lang("$string_file.rides")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-20 font-weight-100">{{$total_rides}}</span>
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
                                    <span class="ml-15 font-weight-400">@lang("$string_file.ride") @lang("common.amount")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-20 font-weight-100">{{$ride_amount}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-3 info-panel">
                            <div class="card card-shadow">
                                <div class="card-block bg-grey-100 p-20">
                                    <button type="button" class="btn btn-floating btn-sm btn-danger">
                                        <i class="icon fa-percent"></i>
                                    </button>
                                    <span class="ml-15 font-weight-400">@lang("common.merchant") @lang("common.earning")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-20 font-weight-100">{{$merchant_amount}}</span>
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
                                    <span class="ml-15 font-weight-400">@lang("$string_file.driver") @lang("common.earning")</span>
                                    <div class="content-text text-center mb-0">
                                        <span class="font-size-20 font-weight-100">{{$driver_amount}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <table id="customDataTable"
                           class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("common.sn")</th>
                            <th>@lang("$string_file.ride") @lang("common.id")</th>
                            <th>@lang("$string_file.payment") @lang("$string_file.method")</th>
                            <th>@lang("$string_file.service_area") </th>
                            <th>@lang("$string_file.driver") @lang("common.details")</th>
                            <th>@lang("$string_file.driver") @lang("common.earning")</th>
                            <th>@lang("common.merchant") @lang("common.earning")</th>
                            <th>@lang("$string_file.tax") @lang("common.amount")</th>
                            <th>@lang("$string_file.ride") @lang("common.amount")</th>
                            <th>@lang("common.ride") @lang("common.date")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $ride->firstItem() @endphp
                        @foreach($ride as $rides)
                            <tr>
                                <td>{{ $sr }}  </td>
                                <td>
                                    <a href="{{route('merchant.offer.user.details',['id'=>$rides->id])}}">{{ $rides->id }}</a>
                                </td>
                                <td>{{$rides->payment_type==1?"cash/wallet":"wallet"}}</td>
                                <td>{{ $rides->CountryArea->LanguageSingle->AreaName }} </td>
                                <td>
                                    <span class="long_text">
                                        {!! is_demo_data($rides->User->UserName, $rides->Merchant) !!}<br>
                                        {!! is_demo_data($rides->User->UserPhone, $rides->Merchant) !!}<br>
                                        {!! is_demo_data($rides->User->email, $rides->Merchant) !!}
                                    </span>
                                </td>
                                <td>{{$rides->User->Country->isoCode." ".round($rides->driver_earning,2)}}</td>
                                <td>{{$rides->User->Country->isoCode." ".round($rides->company_commission,2)}}</td>
                                <td>{{$rides->User->Country->isoCode." ".round($rides->service_charges,2)}}</td>
                                <td>{{$rides->User->Country->isoCode." ".round($rides->total_amount,2)}}</td>
                                <td>{{date('y/m/d ', $rides->ride_timestamp)}}</td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $ride, 'data' => $data])
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    <div class="modal fade text-left" id="sendNotificationModelUser" tabindex="-1" role="dialog"
         aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33"><b>@lang("common.send") @lang("common.notification") </b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('merchant.sendsingle-user') }}" enctype="multipart/form-data" method="post">
                    @csrf
                    <div class="modal-body">
                        <label>@lang("common.title") </label>
                        <div class="form-group">
                            <input type="text" class="form-control" id="title"
                                   name="title"
                                   placeholder="" required>
                        </div>

                        <label>@lang("common.message") </label>
                        <div class="form-group">
                           <textarea class="form-control" id="message" name="message"
                                     rows="3"
                                     placeholder=""></textarea>
                        </div>
                        <label>@lang("common.image") </label>
                        <div class="form-group">
                            <input type="file" class="form-control" id="image"
                                   name="image"
                                   placeholder="@lang("common.image")">
                            <input type="hidden" name="persion_id" id="persion_id" required>
                        </div>
                        <label>@lang("common.show") @lang("common.in") @lang("$string_file.promotion") </label>
                        <div class="form-group">
                            <input type="checkbox" value="1" name="expery_check"
                                   id="expery_check_two">
                        </div>
                        <label>@lang("common.expire") @lang("common.date") </label>
                        <div class="input-group">
                            <input type="text" class="form-control customDatePicker1 bg-this-color"
                                   id="datepicker" name="date" readonly
                                   placeholder="">
                        </div>

                        <label>@lang("common.url") </label>
                        <div class="form-group">
                            <input type="url" class="form-control" id="url"
                                   name="url"
                                   placeholder="@lang("common.url")(@lang("common.optional"))">
                            <label class="danger">@lang("common.example") : https://www.google.com/</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-outline-secondary btn" data-dismiss="modal"
                               value="@lang("common.close")">
                        <input type="submit" class="btn btn-outline-primary btn" value="@lang("common.send")">
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{--add money in user wallet--}}
    <div class="modal fade text-left" id="addMoneyModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33"><b>@lang("common.add") @lang("common.money") @lang("common.in") @lang("common.wallet")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('merchant.user.add.wallet') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <label>@lang("common.payment") @lang("common.method") </label>
                        <div class="form-group">
                            <select class="form-control" name="payment_method" id="payment_method" required>
                                <option value="1">@lang("common.cash")</option>
                                <option value="2">@lang("common.non_cash")</option>
                            </select>
                        </div>

                        <label>@lang("common.amount") </label>
                        <div class="form-group">
                            <input type="text" name="amount" placeholder=""
                                   class="form-control" required>
                            <input type="hidden" name="add_money_user_id" id="add_money_driver_id">
                        </div>

                        <label>@lang("common.receipt") @lang("common.number") </label>
                        <div class="form-group">
                            <input type="text" name="receipt_number" placeholder=""
                                   class="form-control" required>
                        </div>
                        <label>@lang("common.description") </label>
                        <div class="form-group">
                            <textarea class="form-control" id="title1" rows="3" name="description"
                                      placeholder=""></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-secondary" data-dismiss="modal"
                               value="@lang("common.close")">
                        <input type="submit" id="sub" class="btn btn-primary" value="@lang("common.add")">
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $('#sub').on('click', function () {
            $('#myLoader').removeClass('d-none');
            $('#myLoader').addClass('d-flex');
        });
    </script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        function DeleteEvent(id) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("common.are_you_sure")",
                text: "@lang("common.delete_warning")",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        type: "DELETE",
                        url: "{{ route('users.index') }}/" + id,
                    }).done(function (data) {
                        swal({
                            title: "DELETED!",
                            text: data,
                            type: "success",
                        });
                        window.location.href = "{{ route('users.index') }}";
                    });
                } else {
                    swal("@lang("common.data_is_safe")");
                }
            });
        }
    </script>
@endsection

