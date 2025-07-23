@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="mr--10 ml--10">
                        <div class="row " style="margin-right: 0rem;margin-left: 0rem">
                            <div class="col-md-1w2 col-sm-5">
                                <h3 class="panel-title"><i class="fa-users" aria-hidden="true"></i>
                                    @lang("$string_file.wallet_recharge_requests")</h3>
                            </div>
                            <div class="col-md-10 col-sm-7">



                            </div>
                        </div>
                        <div class="row " style="margin-right: 0rem;margin-left: 0rem">
                            <div class="col-md-12 col-sm-7">
                                    <a href="{{ route('wallet.recharge.requests') }}?status=success">
                                        <button type="button" class="btn btn-icon btn-success float-right"
                                                style="margin:10px">
                                            @lang("$string_file.success")
                                            <span class="badge badge-pill">{{$succeded_recharge_request}}
                                    </span>
                                        </button>
                                    </a>
                                    <a href="{{ route('wallet.recharge.requests') }}?status=pending">
                                        <button type="button" class="btn btn-icon btn-warning float-right"
                                                style="margin:10px">
                                            @lang("$string_file.pending")
                                            <span class="badge badge-pill">{{$pending_recharge_request}}</span>
                                        </button>
                                    </a>
                            </div>
                        </div>
                    </div>
                </header>
                <div class="panel-body container-fluid">


                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.driver") </th>
                            <th>@lang("$string_file.user")</th>
                            <th>@lang("$string_file.requested") @lang("$string_file.amount")</th>
                            <th>@lang("$string_file.comment")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.date")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $wallet_recharge_requests->firstItem() @endphp
                        @foreach($wallet_recharge_requests as $request)
                            <tr>
                                <td>{{$sr}}</td>

                                <td>
                                    @if(!empty($request->Driver))
                                        {{ $request->Driver->first_name }} {{ $request->Driver->last_name }}
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($request->User))
                                        {{ $request->User->first_name }} {{ $request->User->last_name }}
                                    @endif
                                </td>
                                <td>
                                        {{ $request->amount_requested }}
                                </td>
                                <td>
                                    @if(!empty($request->comment))
                                        {{ $request->comment }}
                                    @else
                                            ---------
                                    @endif
                                </td>

                                <td>
                                    @if($request->request_status == 0)
                                        <button class="btn btn-warning">@lang("$string_file.pending")</button>
                                    @elseif($request->request_status == 1)
                                        <button class="btn btn-success">@lang("$string_file.success")</button>
                                    @elseif($request->request_status == 2)
                                        <button class="btn btn-danger">@lang("$string_file.failed")</button>
                                    @endif

                                </td>
                                <td>{{ $request->created_at->format('j M Y') }}</td>
                                <td>
                                    @if($config->driver_wallet_status == 1   && !empty($request->Driver) && ($request->request_status == 0))
                                        <a onclick="AddWalletMoneyMod(this, '{{$request->id}}')"
                                           data-ID="{{ $request->Driver->id }}"
                                           data-original-title="@lang("$string_file.add_money")"
                                           data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn text-white btn-sm btn-success">
                                            <i class="fa fa-money"></i> </a>
                                    @endif
                                    @if($config->user_wallet_status == 1 && !empty($request->User) && ($request->request_status == 0))
                                            <span data-target="#addMoneyModel" data-toggle="modal" id="{{ $request->User->id }}">
                                                    <a title="@lang("$string_file.add_money")" onclick="AddUserWalletMoneyMod('{{$request->id}}')"
                                                       id="{{ $request->User->id }}" data-placement="top"
                                                       class="btn text-white btn-sm btn-success menu-icon btn_eye action_btn" role="menuitem">
                                                        <i class="icon fa-money"></i>
                                                    </a>
                                                </span>
                                    @endif
                                </td>
                            </tr>
                            @php $sr++; @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $wallet_recharge_requests, 'data' => []])
                    {{--                    <div class="pagination1 float-right">{{ $drivers->appends($arr_search)->links() }}</div>--}}
                </div>
            </div>
        </div>
    </div>

    {{--    User Wallet Model--}}
    <div class="modal fade text-left" id="addMoneyModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600" id="myModalLabel33"><b>@lang("$string_file.add_money_in_wallet")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('merchant.user.add.wallet') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <label>@lang("$string_file.payment_method") </label>
                        <div class="form-group">
                            <select class="form-control" name="payment_method" id="payment_method" required>
                                <option value="1">@lang("$string_file.cash")</option>
                                <option value="2">@lang("$string_file.non_cash")</option>
                            </select>
                        </div>

                        <label for="transaction_type">
                            @lang("$string_file.transaction_type")<span
                                    class="text-danger">*</span>
                        </label>
                        <div class="form-group">
                            <select id="transaction_type" name="transaction_type" class="form-control" required>
                                <option value="1">@lang("$string_file.credit")</option>
                                <option value="2">@lang("$string_file.debit")</option>
                            </select>
                        </div>

                        <label>@lang("$string_file.amount") </label>
                        <div class="form-group">
                            <input type="text" name="amount" placeholder=""
                                   class="form-control" required>
                            <input type="hidden" name="add_money_user_id" id="add_money_driver_id">
                        </div>

                        <label>@lang("$string_file.receipt_number") </label>
                        <div class="form-group">
                            <input type="text" name="receipt_number" placeholder=""
                                   class="form-control" required>
                        </div>
                        <label>@lang("$string_file.description") </label>
                        <div class="form-group">
                            <textarea class="form-control" id="title1" rows="3" name="description"
                                      placeholder=""></textarea>
                        </div>
                    </div>
                        <input type="hidden" name="user_wallet_recharge_request_id" id="user_wallet_recharge_request_id">
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-secondary" data-dismiss="modal" value="@lang("$string_file.close")">
                        <input type="submit" id="sub" class="btn btn-primary" value="@lang("$string_file.save")">
                    </div>
                </form>
            </div>
        </div>
    </div>


{{--    Driver Wallet Model--}}

    <div class="modal fade text-left" id="addWalletMoneyModel" tabindex="-1" role="dialog"
         aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33"><b>@lang("$string_file.add_money_in_driver_wallet")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    {{--                    @csrf--}}
                    <div class="modal-body">

                        <label>@lang("$string_file.payment_method"): </label>
                        <div class="form-group">
                            <select class="form-control" name="payment_method" id="payment_method" required>
                                <option value="1">@lang("$string_file.cash")</option>
                                <option value="2">@lang("$string_file.non_cash")</option>
                            </select>
                        </div>

                        <label>@lang("$string_file.receipt_number"): </label>
                        <div class="form-group">
                            <input type="text" name="receipt_number" id="receipt_number" placeholder="" class="form-control" required>
                        </div>

                        <label for="transaction_type">
                            @lang("$string_file.transaction_type")<span
                                    class="text-danger">*</span>
                        </label>
                        <div class="form-group">
                            <select id="transaction_type" name="transaction_type" class="form-control" required>
                                <option value="1">@lang("$string_file.credit")</option>
                                <option value="2">@lang("$string_file.debit")</option>
                            </select>
                        </div>

                        <label>@lang("$string_file.amount"): </label>
                        <div class="form-group">
                            <input type="number" name="amount" id="amount" placeholder="@lang("$string_file.amount")"
                                   class="form-control" required>
                        </div>
                        <input type="hidden" name="wallet_recharge_request_id" id="wallet_recharge_request_id">
                        <input type="hidden" name="add_money_driver_id" id="add_money_driver_id">
                        {{--                        <p id="amount_error" class="d-none text-danger">The amount must be atleast 1.</p>--}}

                        <label>@lang("$string_file.description"): </label>
                        <div class="form-group">
                            <textarea class="form-control" id="title1" rows="3" name="description"
                                      placeholder=""></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-secondary" data-dismiss="modal"
                               value="@lang("$string_file.close")">
                        <input type="submit" id="add_money_button" class="btn btn-primary"
                               value="@lang("$string_file.save")">
                    </div>
                </form>
            </div>
        </div>
    </div>


{{--    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])--}}
@endsection
@section('js')
<script>
    $('#add_money_button').on('click', function () {
    $('#add_money_button').prop('disabled', true);
    $('#myLoader').removeClass('d-none');
    $('#myLoader').addClass('d-flex');
    var token = $('[name="_token"]').val();
    var payment_method = document.getElementById('payment_method').value;
    var receipt_number = document.getElementById('receipt_number').value;
    var amount = document.getElementById('amount').value;
    var transaction_type = document.getElementById('transaction_type').value;
    var desc = document.getElementById('title1').value;
    var driver_id = document.getElementById('add_money_driver_id').value;
    var wallet_recharge_request_id  = document.getElementById('wallet_recharge_request_id').value;
    // console.log(payment_method,receipt_number,'recipt');
    // if(!receipt_number){
    //     alert()
    // }
    if (amount > 0) {
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': token
            },
            type: "POST",
            data: {
                payment_method_id: payment_method,
                receipt_number: receipt_number,
                amount: amount,
                transaction_type: transaction_type,
                description: desc,
                driver_id: driver_id,
                wallet_recharge_request_id: wallet_recharge_request_id
            },
            url: "{{ route('merchant.AddMoney') }}",
                    success: function (data) {
                        console.log(data,'hello');
                        if (data.result == 1) {
                            $('#myLoader').removeClass('d-flex');
                            $('#myLoader').addClass('d-none');
                            swal({
                                title: "@lang("$string_file.driver_account")",
                                text: "@lang("$string_file.money_added_successfully")",
                                icon: "success",
                                buttons: true,
                                dangerMode: true,
                            }).then((isConfirm) => {
                                if (isConfirm) {
                                    window.location.href = "{{ route('wallet.recharge.requests') }}";
                                } else {
                                    window.location.href = "{{ route('wallet.recharge.requests') }}";
                                }
                            });
                        }else{
                            alert(data.message);
                            $('#add_money_button').prop('disabled', false);
                        }
                    }, error: function (err) {
                        $('#myLoader').removeClass('d-flex');
                        $('#myLoader').addClass('d-none');
                    }
                });
            } else {
                $('#amount_error').removeClass('d-none');
                $('#add_money_button').prop('disabled', false);
            }

        });

        function AddWalletMoneyMod(obj, req) {
            let ID = obj.getAttribute('data-ID');
            $(".modal-body #add_money_driver_id").val(ID);
            $(".modal-body #wallet_recharge_request_id").val(req);
            $('#addWalletMoneyModel form')[0].reset();
            $('#amount_error').addClass('d-none');
            $('#addWalletMoneyModel').modal('show');
        }

        function AddUserWalletMoneyMod(req){
         $("#user_wallet_recharge_request_id").val(req);
        }
</script>
@endsection