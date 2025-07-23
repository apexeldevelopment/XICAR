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
                            <a href="{{route('excel.user')}}" data-toggle="tooltip">
                                <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                    <i class="wb-download" title="@lang("common.export") @lang("common.excel")"></i>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="wb-earning" aria-hidden="true"></i>
                        @lang("$string_file.carpooling")  @lang("common.earning")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('merchant.carpooling.earning.search') }}" method="get">
                        <div class="table_search row p-3">
                            <div class="col-md-2 col-xs-6 active-margin-top">@lang("common.search") @lang("common.by")
                                :
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <select class="form-control" name="parameter"
                                            id="parameter"
                                            required>
                                        <option value="1">@lang("common.name")</option>
                                        <option value="2">@lang("common.email")</option>
                                        <option value="3">@lang("common.phone")</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <input type="text" name="keyword"
                                           placeholder="@lang("common.enter") @lang("common.text")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                                <div class="input-group">
                                    <select class="form-control" name="country_id"
                                            id="country_id">
                                        <option value="">--@lang("common.country")--</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}"> {{
                                                                    $country->CountryName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                .
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
                            <th>@lang("common.sn")</th>
                            <th>Ride ID</th>
                            <th>Date</th>
                            <th>Country </th>
                            <th>Area</th>
                            <th>@lang("common.user") @lang("common.details")</th>
                           
                            @if($config->gender == 1)
                                <th>@lang("common.gender")</th>
                            @endif
                            <th>@lang("common.total") @lang("common.ride") </th>
                            <th>@lang("$string_file.carpooling") @lang("common.total") @lang("common.ride") @lang("common.amount")</th>
                            <th>@lang("common.wallet") @lang("common.amount") </th>
                            <th>@lang("common.referal") @lang("common.earning")</th>
                            <th>@lang("common.driver") @lang("common.earning")</th>
                            <th>@lang("common.company") @lang("common.earning")</th>
                            <th>@lang("common.tax")</th>
                            <th>@lang("common.cancel") @lang("common.amount") </th>
                            <th>@lang("common.payment") @lang("common.method")</th>

                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $users->firstItem() @endphp
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $sr }}  </td>
                                <td>{{$user->CarpoolingRide->count()}}  </td>
                                <td>{{ $user->created_at->toformatteddatestring() }} </td>
                                <td>{{ $user->Country->LanguageCountrySingle->name}}   </td>
                                <td>{{ $user->CountryArea->LanguageSingle->AreaName }}  </td>
                                @if(Auth::user()->demo == 1)
                                    <td>
                                        <span class="long_text">   {!! nl2br("********".substr($user->last_name, -2)."\n"."********".substr($user->UserPhone, -2)."\n"."********".substr($user->email, -2)) !!}</span>
                                    </td>
                                @else
                                    <td>
                                        <span class="long_text">   {!! nl2br($user->first_name." ".$user->last_name."\n".$user->UserPhone."\n".$user->email) !!}</span>
                                    </td>
                                @endif
                                @if($config->gender == 1)
                                    @switch($user->user_gender)
                                        @case(1)
                                        <td>@lang("common.male")</td>
                                        @break
                                        @case(2)
                                        <td>@lang("common.female")</td>
                                        @break
                                        @default
                                        <td>------</td>
                                    @endswitch
                                @endif
                                <td>{{$user->CarpoolingRide->count()}}</td>
                                <td>
                                 {{$user->Country->isoCode." ".$user->CarpoolingRide->sum('total_amount')}}
                                </td>
                                
                                <td>
                                   {{ $user->Country->isoCode." ".$user->wallet_balance}}
                                </td>
                                <td>
                                {{$user->Country->isoCode." ".$user->CarpoolingRide->sum('company_commission')}}
                                </td>
                                <td>{{$user->Country->isoCode." ".$user->CarpoolingRide->sum('driver_earning')}}</td>
                                <td>{{$user->Country->isoCode." ".$user->CarpoolingRide->sum('company_commission')}}</td>
                                <td>{{$user->Country->isoCode." ".$user->CarpoolingRide->sum('service_charges')}}</td>
                                <td>{{$user->Country->isoCode." ".$user->CarpoolingRide->sum('cancel_amount')}}</td>
                                <td>{{$user->payment_status==1?"cash":"Wallet"}}</td>
                                </tr>
                                </div>
                                </div>
                                
                            
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $users, 'data' => $data])
                    {{--                    <div class="pagination1 float-right">{{  $cusers->appends($data)->links() }}</div>--}}
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

