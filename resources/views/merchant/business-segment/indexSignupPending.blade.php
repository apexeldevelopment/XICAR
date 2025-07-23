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
                    </div>
                    <h3 class="panel-title">
                        <i class="fa fa-building" aria-hidden="true"></i>
                        {{$title}}
                    </h3>
                </header>
                <div class="panel-body">
                    {!! $search_view !!}
                    <table class="display nowrap table table-hover table-striped w-full" id="customDataTable" style="width:100%"
                           cellspacing="0">
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
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $data->firstItem(); @endphp
                        @foreach($data as $business_segment)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    @lang("$string_file.name"): {{ $business_segment->full_name }} <br>
                                    @lang("$string_file.phone"): {!! is_demo_data($business_segment->phone_number, $business_segment->Merchant) !!}
                                </td>
                                <td>
                                    @if(!empty($business_segment->address))
                                        <a title="{{$business_segment->address}}"
                                           target="_blank"
                                           href="https://www.google.com/maps/place/{{ $business_segment->address}}">
                                            @if($business_segment->business_logo)
                                            <img src="{{get_image($business_segment->business_logo,'business_logo',$business_segment->merchant_id)}}" height="40" width="60">
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
                                    <a href="{!! URL::to('/'.$url) !!}"
                                       target="_blank" rel="noopener noreferrer"class="btn btn-icon btn-info btn_eye action_btn">
                                        @lang("$string_file.login_url")
                                    </a>
                                    <br>
                                    @lang("$string_file.email"): {{ $business_segment->email }}
                                </td>
                                <td>
                                
                                    <a href="{{route('business-segment.direct.login', ['id'=>  encrypt($business_segment->id)])}}"
                                       target="_blank" rel="noopener noreferrer"class="btn btn-icon btn-info btn_eye action_btn">
                                        @lang("$string_file.login")  <span class="badge badge-light">@lang("$string_file.incognito")</span>
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
                                    @if(Auth::user('merchant')->can('create_business_segment_'.$slug))
                                        <a href="{{route('merchant.business-segment/add-pending-details',['slug'=>$business_segment->Segment->slag,'id'=>$business_segment->id])}}"
                                           data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                           class="btn btn-sm btn-warning">
                                            <i class="wb-edit"></i>
                                        </a>
                                    @endif
                                </td>
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
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])

    
@endsection
@section('js')
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
$(document).ready(function() {
    // Event listener for showing the modal
    $('#copyStoreProductModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var businessSegmentId = button.data('business-segment-id'); // Extract info from data-* attributes
        var modal = $(this);
        modal.find('#businessSegmentId').val(businessSegmentId);
        // Remove the select option matching the business segment ID
        $('#storeSelect option').each(function() {
                if ($(this).val() == businessSegmentId) {
                    $(this).remove();
                }
            });
    });
    // Event listener for form submission
    $('#copyStoreProductForm').on('submit', function(e) {
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
                    bsId:businessSegmentId,
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
                error: function(xhr, status, error) {
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
                        console.log(data,'hello');
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
                                    window.location.href = "{{ route('merchant.business-segment',['FOOD']) }}";
                                } else {
                                    window.location.href = "{{ route('merchant.business-segment',['FOOD']) }}";
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

function AddWalletMoneyMod(obj) {
            let ID = obj.getAttribute('data-ID');
            $(".modal-body #add_money_business_segment").val(ID);
            $('#addWalletMoneyModel form')[0].reset();
            $('#amount_error').addClass('d-none');
            $('#addWalletMoneyModel').modal('show');
        }
</script>
@endsection


