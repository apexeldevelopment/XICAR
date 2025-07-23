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
                                    @lang("$string_file.driver") @lang("$string_file.status")</h3>
                            </div>
                            <div class="col-md-10 col-sm-7">
                                @if(!empty($info_setting) && $info_setting->view_text != "")
                                    <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                            data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                        <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="row " style="margin-right: 0rem;margin-left: 0rem">
                        </div>
                    </div>
                </header>
                <div class="panel-body container-fluid">
                    {!! $search_view !!}

                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th> @lang("$string_file.id")</th>
                            <th>@lang("$string_file.service_area") </th>
                            <th>@lang("$string_file.driver_details")</th>

                            <th>@lang("$string_file.registered_date")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
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
                                    {{ isset($driver->CountryArea)? $driver->CountryArea->CountryAreaName: "---------" }}

                                </td>
                                <td>
                                    <span class="long_text">
                                        {{ is_demo_data($driver->fullName,$driver->Merchant) }}<br>
                                        {{ is_demo_data($driver->phoneNumber,$driver->Merchant) }}<br>
                                        {{ is_demo_data($driver->email,$driver->Merchant) }}
                                    </span>
                                </td>


                                <td>
                                    @php
                                        $timezone = isset( $driver->CountryArea)? $driver->CountryArea->timezone : null;
                                    @endphp
                                    @if(!empty($timezone))
                                        {!! convertTimeToUSERzone($driver->created_at, $driver->CountryArea->timezone, null, $driver->Merchant) !!}
                                    @else
                                        {{$driver->created_at}}
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $signup_status = "---------";
                                            switch ($driver->signupStep){
                                                case "1":
                                                case "2":
                                                    $signup_status = '<span class="badge badge-secondary">'.trans("$string_file.basic_signup_completed").'</span>';
                                                    break;
                                                case '3':
                                                    $signup_status = '<span class="badge badge-warning">'.trans("$string_file.personal")." ". trans("$string_file.document")." ".trans("$string_file.pending").'</span>';
                                                    break;
                                                case '4':
                                                    $signup_status = '<span class="badge badge-warning">'.trans("$string_file.vehicle")." ". trans("$string_file.not_added").'</span>';
                                                    break;
                                                case '5':
                                                    $signup_status = '<span class="badge badge-warning">'.trans("$string_file.vehicle")." ". trans("$string_file.document")." ".trans("$string_file.pending").'</span>';
                                                    break;
                                                case '6':
                                                    $signup_status = '<span class="badge badge-warning">'.trans("$string_file.vehicle")." ". trans("$string_file.services_configuration")." ".trans("$string_file.not_added").'</span>';
                                                    break;
                                                case '8':
                                                    $signup_status = '<span class="badge badge-primary">'.trans("$string_file.pending_driver_approval").'</span>';
                                                    break;
                                                case '9':
                                                    $signup_status = '<span class="badge badge-success">'.trans("$string_file.signup")." ".trans("$string_file.process")." ".trans("$string_file.completed").'</span>';
                                                    break;
                                            }

                                        if($driver->signupStep == 8 && $driver->reject_driver == 1 && $driver->is_approved == 2){
                                            $signup_status = '<span class="badge badge-primary">'.trans("$string_file.pending_driver_approval").'</span>';
                                        }
                                        elseif($driver->signupStep == 8 && $driver->reject_driver == 1 && $driver->is_approved == 1 && ($driver->in_training == 1 || $driver->in_training == 3)){
                                              $signup_status = '<span class="badge badge-primary">'.trans("$string_file.pending")." ".trans("$string_file.training").'</span>';
                                        }
                                        elseif($driver->signupStep == 8 && $driver->reject_driver == 2){
                                              $signup_status = '<span class="badge badge-danger">'.trans("$string_file.driver")." ".trans("$string_file.rejected").'</span>';
                                        }
                                        if($driver->driver_delete == 1){
                                            $signup_status = '<span class="badge badge-danger">'.trans("$string_file.driver")." ".trans("$string_file.deleted").'</span>';
                                        }
                                 @endphp
                                    {!! $signup_status !!}
                                </td>


                                <td>

                                    <div>
                                        @if(Auth::user('merchant')->can('view_drivers') && isset($driver->CountryArea))
                                            <a href="{{ route('driver.show',$driver->id) }}"
                                               data-original-title="@lang("$string_file.view_profile")"
                                               data-toggle="tooltip" data-placement="top"
                                               class="btn btn-sm btn-info menu-icon btn_detail action_btn">
                                                <span class="wb-user"></span></a>
                                        @endif

                                        <a onclick="getDeviceDetails({{$driver->id}})"
                                           class="btn btn-sm btn-info menu-icon btn_detail action_btn">
                                            <span class="wb-mobile"></span>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                            @php $sr++; @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $drivers, 'data' => $arr_search])
                    {{--                    <div class="pagination1 float-right">{{ $drivers->appends($arr_search)->links() }}</div>--}}
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade text-left" id="sendNotificationModel" tabindex="-1" role="dialog"
         aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33"><b>@lang("$string_file.send_notification")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('merchant.sendsingle-driver') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <label>@lang("$string_file.title"): </label>
                        <div class="form-group">
                            <input type="text" class="form-control" id="title"
                                   name="title"
                                   placeholder="" required>
                        </div>

                        <label>@lang("$string_file.message"): </label>
                        <div class="form-group">
                           <textarea class="form-control" id="message" name="message"
                                     rows="3"
                                     placeholder=""></textarea>
                        </div>

                        <label>@lang("$string_file.image"): </label>
                        <div class="form-group">
                            <input type="file" class="form-control" id="image"
                                   name="image"
                                   placeholder="">
                            <input type="hidden" name="persion_id" id="persion_id">
                        </div>
                        <label>@lang("$string_file.show_in_promotion")
                            : </label>
                        <div class="form-group">
                            <input type="checkbox" value="1" name="expery_check"
                                   id="expery_check_two">
                        </div>

                        <label>@lang("$string_file.expire_date"):</label>
                        <div class="form-group">
                            <input type="text" class="form-control customDatePicker1"
                                   id="datepicker-backend" name="date"
                                   placeholder="" disabled readonly>
                        </div>

                        <label>@lang("$string_file.url") (@lang("$string_file.optional")): </label>
                        <div class="form-group">
                            <input type="url" class="form-control" id="url"
                                   name="url"
                                   placeholder="">
                            <label class="danger">@lang("$string_file.example") : https://www.google.com/</label>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-outline-secondary btn-lg" data-dismiss="modal"
                               value="@lang("$string_file.close")">
                        <input type="submit" class="btn btn-outline-primary btn-lg" value="@lang("$string_file.send")">
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                            <input type="text" name="receipt_number" id="receipt_number" placeholder=""
                                   class="form-control" required>
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

    <div class="modal fade" id="device-details" tabindex="-1" role="dialog"
         aria-labelledby="deviceDetailsPopup" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="deviceDetailsPopupTitle">@lang("$string_file.device") @lang("$string_file.details") : <label
                                id="driver-name"></label></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="model-data">

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="device-remark" tabindex="-1" role="dialog"
         aria-labelledby="deviceRemarkPopup" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="deviceRemarkPopupTitle">@lang("$string_file.device") @lang("$string_file.remarks") : <label
                                id="remark-driver-name"></label></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('merchant.driver.remark.store')}}" method="post">
                    <div class="modal-body" id="model-data-remarks">
                        @csrf
                        <input type="hidden" name="remark_driver_id" id="remark_driver_id">
                        <textarea class="form-control" name="driver_remark" id="driver_remark"></textarea>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- The Modal -->
    <div class="modal" id="movingStatusModal">
        <div class="modal-dialog">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title" id="moving_status">@lang("$string_file.loading_pls_wait")</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <input type="hidden" id="moving_driver_id">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m10!1m8!1m3!1d116862.54554679655!2d90.40409584970706!3d23.749000170125925!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sbd!4v1550040341458"
                            width="100%" height="450" frameborder="0" style="border:0" allowfullscreen></iframe>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>

    {{--    Guaranto Details modal--}}
    <div class="modal fade" id="guarantorDetailsModal" tabindex="-1" role="dialog"
         aria-labelledby="guarantorDetailsLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="guarantorDetailsLabel">@lang("$string_file.driver") @lang("$string_file.guarantor") @lang("$string_file.details")</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center">
                            <img src="{{ asset('basic-images/android.png') }}"
                                 alt="Passport Photo"
                                 class="img-thumbnail"
                                 style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px; margin-top: 5px;"
                                 id="guarantor_image">
                        </div>
                        <div class="col-md-8">
                        <span class="" id="guarantor_name">
                        <strong>@lang("$string_file.name")</strong>: <span id="guarantor_name_text"></span>
                        </span><br>
                            <span class="" id="guarantor_phone">
                            <strong>@lang("$string_file.phone")</strong>: <span id="guarantor_phone_text"></span>
                        </span><br>
                            <span class="" id="guarantor_desc">
                            <strong>@lang("$string_file.description")</strong>: <span id="guarantor_desc_text"></span>
                        </span><br>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>

        function saveDriverRemark(name, id) {
            $("#remark-driver-name").html(name);
            $("#remark_driver_id").val(id)
            $('#device-remark').modal('show');
        }

        function setGuaranterData(name, phone, desc, url){
            document.getElementById('guarantor_image').src = url;
            $("#guarantor_name_text").html(name);
            $("#guarantor_phone_text").html(phone)
            $('#guarantor_desc_text').html(desc);
            $('#guarantorDetailsModal').modal('toggle');
        }

        function getDeviceDetails(driver_id) {
            $("#model-data").html(null);
            $("#sender-name").html(null);
            $("#loader1").show();
            $.ajax({
                method: 'GET',
                url: '<?php echo route('driver.device.details') ?>',
                data: {
                    driver_id: driver_id,
                },
                success: function (data) {
                    if (data.status == "success") {
                        $("#model-data").html(data.data.view);
                        $("#driver-name").html(data.data.name);
                        $('#device-details').modal('toggle');
                    } else {
                        alert(data.message);
                    }
                }
            });
            $("#loader1").hide();
        }

        $('#export-excel').on('click', function () {
            var action = '{{route("excel.driver")}}';
            var arr_param = [];
            var arr_param = $("#driver-search").serializeArray();
            $.ajax({
                type: "GET",
                data: {arr_param},
                url: action,
                success: function (data) {
                    console.log(data);
                }, error: function (err) {
                }
            });
        });
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
                        driver_id: driver_id
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
                                    window.location.href = "{{ route('driver.index') }}";
                                } else {
                                    window.location.href = "{{ route('driver.index') }}";
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
            $(".modal-body #add_money_driver_id").val(ID);
            $('#addWalletMoneyModel form')[0].reset();
            $('#amount_error').addClass('d-none');
            $('#addWalletMoneyModel').modal('show');
        }

    </script>

    <script>
        function DeleteEvent(id) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                text: "@lang("$string_file.delete_warning")",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        type: "POST",
                        data: {
                            id: id,
                        },
                        url: "{{ route('driverDelete') }}",
                    }).done(function (data) {
                        swal({
                        title: data,
                        text: "",
                        icon: "success",
                        buttons: true,
                    }).then(function() {
                        window.location.href = "{{ route('driver.index') }}";
                    });


                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }

        function removeCallEvent(id, action) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                text: "",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        type: "POST",
                        data: {
                            id: id,
                            action: action
                        },
                        url: "{{ route('driver.removeCallButton') }}",
                    }).done(function (data) {
                        swal({
                            title: "@lang("$string_file.success")",
                            text: "",
                            icon: "success",
                            buttons: true,
                        }).then(function() {
                            window.location.href = "{{ route('driver.index') }}";
                        });
                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }


        function freezeTrackingScreen(id, action) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                text: "@lang("$string_file.freeze_tracking_screen")",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        type: "POST",
                        data: {
                            id: id,
                            action: action
                        },
                        url: "{{ route('driver.freezeTrackingScreen') }}",
                    }).done(function (data) {
                        swal({
                            title: "@lang("$string_file.success")",
                            text: "",
                            icon: "success",
                            buttons: true,
                        }).then(function() {
                            window.location.href = "{{ route('driver.index') }}";
                        });
                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }

        function selectSearchFields() {
            var segment_id = $('#segment_id').val();
            var area_id = $('#area_id').val();
            var by = $('#by_param').val();
            var by_text = $('#keyword').val();
            if (segment_id.length == 0 && area_id == "" && by == "" && by_text == "" && driver_status == "") {
                alert("Please select at least one search field");
                return false;
            } else if (by != "" && by_text == "") {
                alert("Please enter text according to selected parameter");
                return false;
            } else if (by_text != "" && by == "") {
                alert("Please select parameter according to entered text");
                return false;
            }
        }

        // get location
        $('.view_current_location').on('click', function () {
            var ats_id = $(this).attr("id");
            var driver_id = $(this).attr("driver_id");
            var timezone = $(this).attr("timezone");

            if (ats_id == "" || ats_id == "NA") {
                alert("@lang("$string_file.ats_id_error")");
                return true;
            }
            var token = $('[name="_token"]').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                type: "POST",
                data: {
                    "ats_id": ats_id,
                    "driver_timezone": timezone,
                },
                url: "{{ route('merchant.get-lat-long') }}",
                success: function (data) {
                    console.log(data);
                    $("#" + driver_id).html(data);
                }, error: function (err) {
                    $("#" + driver_id).text("No Data");
                }
            });
        });

        // $('#entries').on('change', function() {
        //     var selectedValue = $(this).val();
        //     var baseUrl = "{{ route('driver.index') }}";
        //     var currentUrl = baseUrl + window.location.search; // Get the current URL including existing query parameters

        //     // Parse the query parameters into an object
        //     var queryParams = new URLSearchParams(window.location.search);
        //     if (queryParams.has('per_page')) {
        //         queryParams.set('per_page', selectedValue);
        //     } else {
        //         queryParams.append('per_page', selectedValue);
        //     }

        //     // Construct the updated URL with the modified query parameters
        //     var updatedUrl = baseUrl + '?' + queryParams.toString();
        //     location.href = updatedUrl;
        // });

    function setDriverIdForModal(id){
        $("#moving_driver_id").val(id);
    }

    function changeMap(driverId){
        $.ajax({
        url: '{{route('ajax.services.getDriverMovingStatus')}}',
        type: 'GET',
        data: { driver_id: driverId },
            success: function (response) {
                if (response.latitude && response.longitude) {
                    var newMapUrl = `https://www.google.com/maps/embed/v1/place?key=${response.key}&q=${response.latitude},${response.longitude}`;
                    $('#movingStatusModal iframe').attr('src', newMapUrl);
                    if (response.moving_location_distance !== null && response.moving_location_distance !== undefined && response.moving_location_distance > 70) {
                        $("#moving_status").html(`
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{asset('basic-images/car-speed.png')}}" width="50" height="50">
                                <span> @lang("$string_file.moving")</span>
                            </div>
                        `);
                        } else {
                        $("#moving_status").html(`
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{asset('basic-images/traffic-light.png')}}" width="50" height="50">
                                <span> @lang("$string_file.still")</span>
                            </div>
                        `);                    }
                }
            },
            error: function (error) {
                console.log('Error fetching driver location:', error);
            }
        });
    }

   $(document).ready(function () {
    var intervalId;
    $('#movingStatusModal').on('shown.bs.modal', function () {
        var driverId = $("#moving_driver_id").val();
        if (driverId) {
            clearInterval(intervalId);
            changeMap(driverId);
            intervalId = setInterval(function(){
                changeMap(driverId);
            }, 10000);
        }
    });

    $('#movingStatusModal').on('hidden.bs.modal', function () {
        clearInterval(intervalId); // Stop interval when modal is closed
    });
});


    </script>
@endsection
