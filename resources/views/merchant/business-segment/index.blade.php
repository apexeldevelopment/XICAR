@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                             @if ($stripe_connect_store_enable == 1)
                            @if(Auth::user('merchant')->can('create_business_segment_' . $slug))
                                <a href="{{route('merchant.business-segment/sync-all-stripe-connect', [$merchent_id])}}">
                                    <button type="button" title="@lang("$string_file.add") {{$title}}"
                                        class="btn btn-sm btn-secondary float-right" style="margin:10px"> <i class="icon wb-refresh" title="Sync all stripe connect account"></i>
                                    </button>
                                </a>
                            @endif
                        @endif

                        @if(Auth::user('merchant')->can('create_business_segment_' . $slug))
                            <a href="{{route('merchant.business-segment/add', [$slug])}}">
                                <button type="button" title="@lang("$string_file.add") {{$title}}"
                                    class="btn btn-icon btn-success float-right" style="margin:10px"><i class="wb-plus"></i>
                                </button>
                            </a>
                        @endif
                       
                    </div>
                    <h3 class="panel-title">
                        <i class="fa fa-building" aria-hidden="true"></i>
                        {{$title}}
                    </h3>
                </header>
                <div class="panel-body">
                    {!! $search_view !!}
                    <table class="display nowrap table table-hover table-striped w-full" id="customDataTable"
                        style="width:100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>@lang("$string_file.sn")</th>
                                <th>@lang("$string_file.contact_details")</th>
                                <th>@lang("$string_file.address")</th>
                                <th>@lang("$string_file.login_url")</th>
                                <th>@lang("$string_file.direct_url")</th>
                                <th>@lang("$string_file.rating")</th>
                                <th>@lang("$string_file.wallet_money")</th>
                                <th>@lang("$string_file.action")</th>
                                @if(Auth::user('merchant')->can('order_statistics_' . $slug))
                                    <th>@lang("$string_file.order_statistics")</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php $sr = $data->firstItem(); @endphp
                            @foreach($data as $business_segment)
                                <tr>
                                    <td>{{ $sr }}</td>
                                    <td>
                                        @lang("$string_file.name"): {{ $business_segment->full_name }} <br>
                                        @lang("$string_file.phone"):
                                        {!! is_demo_data($business_segment->phone_number, $business_segment->Merchant) !!}
                                    </td>
                                    <td>
                                        @if(!empty($business_segment->address))
                                            <a title="{{$business_segment->address}}" target="_blank"
                                                href="https://www.google.com/maps/place/{{ $business_segment->address}}">
                                                @if($business_segment->business_logo)
                                                    <img src="{{get_image($business_segment->business_logo, 'business_logo', $business_segment->merchant_id)}}"
                                                        height="40" width="60">
                                                @else
                                                    <span class="btn btn-icon btn-success"><i class="icon wb-map"></i></span>
                                                @endif
                                            </a>
                                        @else
                                            ----
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $merchant_alias = $business_segment->merchant->alias_name;
                                            $url = "business-segment/admin/$merchant_alias/$business_segment->alias_name/login";
                                        @endphp
                                        <a href="{!! URL::to('/' . $url) !!}" target="_blank" rel="noopener noreferrer"
                                            class="btn btn-icon btn-info btn_eye action_btn">
                                            @lang("$string_file.login_url")
                                        </a>
                                        <br>
                                        @lang("$string_file.email"): {{ $business_segment->email }}
                                    </td>
                                    <td>

                                        <a href="{{route('business-segment.direct.login', ['id' => encrypt($business_segment->id)])}}"
                                            target="_blank" rel="noopener noreferrer"
                                            class="btn btn-icon btn-info btn_eye action_btn">
                                            @lang("$string_file.login") <span
                                                class="badge badge-light">@lang("$string_file.incognito")</span>
                                        </a>
                                        <span></span>
                                    </td>
                                    <td>{{ $business_segment->rating }}</td>
                                    <td>{{$business_segment->wallet_amount}}</td>
                                    <td>

                                        @if($business_segment->status == 1)
                                            <span class="badge badge-success font-size-14">@lang("$string_file.active")</span>
                                        @else
                                            <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                        @endif
                                        <a onclick="AddWalletMoneyMod(this)" data-ID="{{ $business_segment->id }}"
                                            data-original-title="@lang("$string_file.add_money")" data-toggle="tooltip"
                                            data-placement="top" class="btn text-white btn-sm btn-success">
                                            <i class="fa fa-money"></i> </a>
                                        @if(Auth::user('merchant')->can('create_business_segment_' . $slug))
                                            <a href="{{route('merchant.business-segment/add', ['slug' => $business_segment->Segment->slag, 'id' => $business_segment->id])}}"
                                                data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                                class="btn btn-sm btn-warning">
                                                <i class="wb-edit"></i>
                                            </a>
                                        @endif
                                        @if(isset(Auth::user('merchant')->Configuration) && Auth::user('merchant')->Configuration->copy_product_data_to_another == 1)
                                            <a href="#" data-toggle="modal" data-target="#copyStoreProductModal"
                                                data-business-segment-id="{{$business_segment->id}}"
                                                data-original-title="@lang("$string_file.copy_store_product")"
                                                id="copy_store_product" class="btn btn-sm btn-warning">
                                                <i class="fa-regular fa-copy"></i>
                                            </a>
                                        @endif
                                        @if ($stripe_connect_store_enable == 1)
                                            <a href="{{route('merchant.business-segment/stripe-connect', ['id' => $business_segment->id])}}"
                                                data-original-title="@lang("$string_file.stripe_connect")" data-toggle="tooltip"
                                                class="btn btn-sm btn-secondary">
                                                <i class="fa fa-cc-stripe"></i>
                                            </a>
                                            @if ($business_segment->sc_account_id != null)
                                                <a href="{{route('merchant.business-segment/stripe-connect-sync', ['id' => $business_segment->id])}}"
                                                    data-original-title="@lang("$string_file.sync_stripe_connect")"
                                                    data-toggle="tooltip" class="btn btn-sm btn-secondary">
                                                    <i class="icon wb-refresh" title="Sync"></i>
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                    @if(Auth::user('merchant')->can('order_statistics_' . $slug))
                                        <td class="text-center">
                                            <a href="{{route('merchant.business-segment.statistics', ['slug' => $business_segment->Segment->slag, 'b_id' => $business_segment->id])}}"
                                                data-original-title="@lang("$string_file.view_statistics")" data-toggle="tooltip"
                                                class="btn btn-sm btn-success">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                                @php $sr++  @endphp
                            @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $data, 'data' => $arr_search])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting', ['info_setting' => $info_setting, 'page_name' => 'view_text'])

    <div class="modal fade" id="copyStoreProductModal" tabindex="-1" role="dialog"
        aria-labelledby="copyStoreProductModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="copyStoreProductModalLabel">@lang("$string_file.copy_store_product")</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="copyStoreProductForm">
                        <div class="form-group">
                            <input type="hidden" name="slug"
                                value="{{isset($business_segment) ? $business_segment->Segment->slag : ""}}" id="slug">
                            <input type="hidden" id="businessSegmentId" name="business_segment_id">
                            <label for="storeSelect">@lang("$string_file.select_store")</label>
                            <select class="form-control" id="storeSelect" name="store">
                                <option value="">---select option----</option>
                                @foreach ($data as $bs)
                                    <option value="{{$bs->id}}">{{$bs->full_name}}</option>
                                @endforeach
                            </select>

                        </div>
                        <button type="submit" class="btn btn-primary">@lang("$string_file.copy")</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade text-left" id="addWalletMoneyModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                        id="myModalLabel33"><b>@lang("$string_file.add_money_in_business_segment_wallet")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    {{-- @csrf--}}
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
                            <input type="text" name="receipt_number" id="receipt_number" placeholder="" class="form-control"
                                required>
                        </div>

                        <label for="transaction_type">
                            @lang("$string_file.transaction_type")<span class="text-danger">*</span>
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
                        <input type="hidden" name="add_money_business_segment" id="add_money_business_segment">
                        {{-- <p id="amount_error" class="d-none text-danger">The amount must be atleast 1.</p>--}}

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

@endsection
@section('js')
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        $(document).ready(function () {
            // Event listener for showing the modal
            $('#copyStoreProductModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var businessSegmentId = button.data('business-segment-id'); // Extract info from data-* attributes
                var modal = $(this);
                modal.find('#businessSegmentId').val(businessSegmentId);
                // Remove the select option matching the business segment ID
                $('#storeSelect option').each(function () {
                    if ($(this).val() == businessSegmentId) {
                        $(this).remove();
                    }
                });
            });
            // Event listener for form submission
            $('#copyStoreProductForm').on('submit', function (e) {
                e.preventDefault();
                var selectedStore = $('#storeSelect').val();
                var businessSegmentId = $('#businessSegmentId').val();
                var token = "{{csrf_token()}}";
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: "POST",
                    url: "{{ route('merchant.business-segment.productcopy') }}",
                    cache: false,
                    data: {
                        selectedId: selectedStore,
                        bsId: businessSegmentId,
                    },
                    success: function (response) {
                        if (response.status === 'error') {
                            $('#copyStoreProductModal').modal('hide');
                            // Display error message
                            alert(response.message);
                        } else if (response.status === 'success') {
                            // Display success message
                            $('#copyStoreProductModal').modal('hide');
                            alert(response.message);
                            Window.location.reload();
                        }
                    },
                    error: function (xhr, status, error) {
                        // Handle any other errors
                        console.error(xhr.responseText);
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });

        $('#add_money_button').on('click', function () {
            $('#add_money_button').prop('disabled', true);
            $('#myLoader').removeClass('d-none');
            $('#myLoader').addClass('d-flex');
            var token = "{{csrf_token()}}";
            var payment_method = document.getElementById('payment_method').value;
            var receipt_number = document.getElementById('receipt_number').value;
            var amount = document.getElementById('amount').value;
            var transaction_type = document.getElementById('transaction_type').value;
            var desc = document.getElementById('title1').value;
            var receiver_id = document.getElementById('add_money_business_segment').value;
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
                        receiver_id: receiver_id
                    },
                    url: "{{ route('merchant.business-segment.AddMoney') }}",
                    success: function (data) {
                        console.log(data, 'hello');
                        if (data.result == 1) {
                            $('#myLoader').removeClass('d-flex');
                            $('#myLoader').addClass('d-none');
                            swal({
                                title: "@lang("$string_file.business_segment_account")",
                                text: "@lang("$string_file.money_added_successfully")",
                                icon: "success",
                                buttons: true,
                                dangerMode: true,
                            }).then((isConfirm) => {
                                if (isConfirm) {
                                    window.location.href = "{{ route('merchant.business-segment', ['FOOD']) }}";
                                } else {
                                    window.location.href = "{{ route('merchant.business-segment', ['FOOD']) }}";
                                }
                            });
                        } else {
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

        function AddWalletMoneyMod(obj) {
            let ID = obj.getAttribute('data-ID');
            $(".modal-body #add_money_business_segment").val(ID);
            $('#addWalletMoneyModel form')[0].reset();
            $('#amount_error').addClass('d-none');
            $('#addWalletMoneyModel').modal('show');
        }
    </script>
@endsection