@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('merchant.agent.add')}}">
                            <button type="button" title="@lang("$string_file.add_agent")"
                                    class="btn btn-icon btn-success float-right"  style="margin:10px"><i class="wb-plus"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="fa fa-building" aria-hidden="true"></i>
                        @lang("$string_file.agents")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.agent")</th>
                            <th>@lang("$string_file.logo")</th>
                            <th>@lang("$string_file.country")</th>
                            <th>@lang("$string_file.login_url")</th>
                            <th>@lang("$string_file.address")</th>
                            {{--<th>@lang("$string_file.bank_details")</th>--}}
                            <th>@lang("$string_file.company_contact_pers")</th>
                            <th>@lang("$string_file.no_of_driver")</th>
                            {{--<th>@lang("$string_file.wallet_money")</th>--}}
                            {{--<th>@lang("$string_file.transactions")</th>--}}
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = 1 @endphp
                        @foreach($agents as $agent)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    <b>{{ is_demo_data($agent->name, $agent->Merchant) }}</b><br>
                                    <i>{{ is_demo_data($agent->email, $agent->Merchant) }}</i><br>
                                    {{ is_demo_data($agent->phone, $agent->Merchant) }}
                                </td>
                                <td>
                                    <img src="{{get_image($agent->agent_image,'agent_logo')}}" width="50px" height="50px">
                                </td>
                                <td>
                                    {{ $agent->country->CountryName }}
                                </td>
                                <td>
                                    <a href="{{ config('app.url') }}agent/admin/{{$merchant->alias_name}}/{{ $agent->alias_name }}/login"
                                       target="_blank" rel="noopener noreferrer"  class="btn btn-icon btn-info btn_eye action_btn"><i class="icon fa-sign-in"></i></a>
                                </td>
                                <td>
                                    {{ is_demo_data($agent->address, $agent->Merchant) }}
                                </td>
                                {{--<td>--}}
                                {{--@if(Auth::user()->demo == 1) {{ "********".substr($agent->bank_name, -2) }} @else {{ $agent->bank_name }} @endif,--}}
                                {{--@if(Auth::user()->demo == 1) {{ "********".substr($agent->account_holder_name, -2) }} @else {{ $agent->account_holder_name }} @endif <br>--}}
                                {{--@if(Auth::user()->demo == 1) {{ "********".substr($agent->account_number, -2) }} @else {{ $agent->account_number }} @endif,--}}
                                {{--@if(Auth::user()->demo == 1) {{ "********".substr((isset($agent->AccountType->LangAccountTypeSingle->name) ? $agent->AccountType->LangAccountTypeSingle->name : 'Unknown'), -2) }} @else {{ (isset($agent->AccountType->LangAccountTypeSingle->name) ? $agent->AccountType->LangAccountTypeSingle->name : 'Unknown') }} @endif <br>--}}
                                {{--@if(Auth::user()->demo == 1) {{ "********".substr($agent->online_transaction, -2) }} @else {{ $agent->online_transaction }} @endif--}}
                                {{--</td>--}}
                                <td>
                                    {{ is_demo_data($agent->contact_person, $agent->Merchant) }}
                                </td>
                                <td>
                                    <a href="{{route("driver.index", ["agent_id" => $agent->id])}}" target="_blank">
                                        {{ isset($agent->Driver) ? $agent->Driver->count() : 0 }}
                                    </a>
                                </td>
                                {{--<td>--}}
                                {{--{{ isset($agent->wallet_money) ? $agent->wallet_money : 0 }}--}}
                                {{--</td>--}}
                                {{--<td class="text-center">--}}
                                {{--<a href="{{ route('merchant.taxicompany.transactions',[$agent->id])}}"><i class="fa fa-random"></i> </a>--}}
                                {{--</td>--}}
                                <td>
                                    @if($agent->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td  style="width: 100px;float: left">
                                    {{--<span data-toggle="modal"--}}
                                    {{--data-target="#examplePositionCenter">--}}
                                    {{--<a href="#"--}}
                                    {{--onclick="AddWalletMoneyMod(this)"--}}
                                    {{--data-ID="{{ $agent->id }}"--}}
                                    {{--data-original-title="@lang("$string_file.add_money")"--}}
                                    {{--data-toggle="tooltip"--}}

                                    {{--data-placement="top"--}}
                                    {{--class="btn text-white btn-sm btn-success">--}}
                                    {{--<i class="icon fa-money"></i>--}}
                                    {{--</a></span>--}}
                                    {{--<a href="{{ route('merchant.taxicompany.wallet.show',$agent->id) }}"--}}
                                    {{--data-original-title="Wallet Transaction" data-toggle="tooltip"--}}
                                    {{--class="btn btn-sm menu-icon btn-primary btn_money action_btn">--}}
                                    {{--<i class="icon fa-window-maximize">--}}
                                    {{--</i>--}}
                                    {{--</a>--}}
                                    <a href="{{route('merchant.agent.add',$agent->id)}}"
                                       data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                       class="btn btn-sm btn-warning">
                                        <i class="wb-edit"></i>
                                    </a>

                                    @if($agent->status == 1)
                                        <a href="{{ route('merchant.agent.status',['id'=>$agent->id,'status'=>2]) }}"
                                           data-original-title="@lang("$string_file.inactive")" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-danger menu-icon btn_eye_dis action_btn"> <i
                                                    class="fa fa-eye-slash"></i> </a>
                                    @else
                                        <a href="{{ route('merchant.agent.status',['id'=>$agent->id,'status'=>1]) }}"
                                           data-original-title="@lang("$string_file.active")" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-success menu-icon btn_eye action_btn"> <i
                                                    class="icon fa-eye"></i> </a>
                                    @endif
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $agents, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    {{--<div class="modal fade" id="examplePositionCenter" aria-hidden="true" aria-labelledby="examplePositionCenter"--}}
    {{--role="dialog" tabindex="-1">--}}
    {{--<div class="modal-dialog modal-simple modal-center">--}}
    {{--<div class="modal-content">--}}
    {{--<div class="modal-header">--}}
    {{--<label class="modal-title text-text-bold-600" id="myModalLabel33"><b>@lang("$string_file.add_money_in_wallet")</b></label>--}}
    {{--<button type="button" class="close" data-dismiss="modal" aria-label="Close">--}}
    {{--<span aria-hidden="true">&times;</span>--}}
    {{--</button>--}}
    {{--</div>--}}
    {{--<form>--}}
    {{--@csrf--}}
    {{--<div class="modal-body">--}}
    {{--<label>@lang("$string_file.payment_method"): </label>--}}
    {{--<div class="form-group">--}}
    {{--<select class="form-control" name="payment_method" id="payment_method" required>--}}
    {{--<option value="1">@lang("$string_file.cash")</option>--}}
    {{--<option value="2">@lang("$string_file.no_cash")</option>--}}
    {{--</select>--}}
    {{--</div>--}}

    {{--<label>@lang("$string_file.receipt_number"): </label>--}}
    {{--<div class="form-group">--}}
    {{--<input type="text" name="receipt_number" id="receipt_number"--}}
    {{--class="form-control" required>--}}
    {{--</div>--}}

    {{--<label>@lang("$string_file.amount"): </label>--}}
    {{--<div class="form-group">--}}
    {{--<input type="number" name="amount" id="amount" placeholder=""--}}
    {{--class="form-control" required min="1">--}}
    {{--<input type="hidden" name="add_money_taxi_company_id" id="add_money_taxi_company_id">--}}
    {{--</div>--}}

    {{--<label>@lang("$string_file.description"): </label>--}}
    {{--<div class="form-group">--}}
    {{--<textarea class="form-control" id="title1" rows="3" name="description"--}}
    {{--placeholder=""></textarea>--}}
    {{--</div>--}}
    {{--</div>--}}
    {{--<div class="modal-footer">--}}
    {{--<input type="reset" class="btn btn-secondary" data-dismiss="modal" value="@lang("$string_file.close")">--}}
    {{--<input type="button" id="add_money_button" class="btn btn-primary" value="@lang("$string_file.add")">--}}
    {{--</div>--}}
    {{--</form>--}}
    {{--</div>--}}
    {{--</div>--}}
    {{--</div>--}}
@endsection
@section('js')
    <script>
        // function AddWalletMoneyMod(obj) {
        //     let ID = obj.getAttribute('data-ID');
        //     $(".modal-body #add_money_taxi_company_id").val(ID);
        //     $('#addWalletMoneyModel').modal('show');
        // }
        {{--$('#add_money_button').on('click', function () {--}}
        {{--$('#add_money_button').prop('disabled',true);--}}
        {{--$('#myLoader').removeClass('d-none');--}}
        {{--$('#myLoader').addClass('d-flex');--}}
        {{--var token = $('[name="_token"]').val();--}}
        {{--var payment_method = document.getElementById('payment_method').value;--}}
        {{--var receipt_number = document.getElementById('receipt_number').value;--}}
        {{--var amount = document.getElementById('amount').value;--}}
        {{--var desc = document.getElementById('title1').value;--}}
        {{--var taxi_company_id = document.getElementById('add_money_taxi_company_id').value;--}}
        {{--$.ajax({--}}
        {{--headers: {--}}
        {{--'X-CSRF-TOKEN': token--}}
        {{--},--}}
        {{--type: "POST",--}}
        {{--data: {payment_method_id : payment_method,receipt_number : receipt_number,amount : amount,description : desc,taxi_company_id : taxi_company_id},--}}
        {{--url: "{{ route('taxicompany.AddMoney') }}",--}}
        {{--success: function (data) {--}}
        {{--console.log(data);--}}
        {{--if(data.result == 1){--}}
        {{--$('#myLoader').removeClass('d-flex');--}}
        {{--$('#myLoader').addClass('d-none');--}}
        {{--swal({--}}
        {{--title: "@lang("$string_file.taxi_company_account")",--}}
        {{--text: "@lang("$string_file.amount_added_successfully")",--}}
        {{--icon: "success",--}}
        {{--buttons: true,--}}
        {{--dangerMode: true,--}}
        {{--}).then((isConfirm) => {--}}
        {{--window.location.href = "{{ route('merchant.taxi-company') }}";--}}

        {{--});--}}
        {{--}--}}
        {{--}, error: function (err) {--}}
        {{--$('#myLoader').removeClass('d-flex');--}}
        {{--$('#myLoader').addClass('d-none');--}}
        {{--}--}}
        {{--});--}}
        {{--});--}}
    </script>
@endsection
