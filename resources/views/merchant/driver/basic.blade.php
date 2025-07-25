@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('driver.index') }}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang("$string_file.all_driver")"></i>
                            </button>
                        </a>
                        @if(!empty($merchant) && $merchant->demo != 1)
                        <a href="{{route('excel.basicsignupdriver')}}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                <i class="wb-download" title="@lang("$string_file.export_excel")"></i>
                            </button>
                        </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="wb-user" aria-hidden="true"></i>
                        @lang("$string_file.basic_signup_completed")</h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! $search_view !!}
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.service_area") </th>
                            <th>@lang("$string_file.profile_image")</th>
                            <th>@lang("$string_file.driver_details")</th>
                            <th>@lang("$string_file.registered_date")</th>
                            <th>@lang("$string_file.update")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $drivers->firstItem() @endphp
                            @foreach($drivers as $driver)
                                <tr>
                                    <td>{{$sr}}</td>
                                    <td>{{  !empty($driver->country_area_id) ? $driver->CountryArea->CountryAreaName : "" }}</td>
                                    <td class="text-center">
                                        <img
                                                src="{{ get_image($driver->profile_image,'driver') }}"
                                                alt="avatar" style="width: 100px;height: 100px;">
                                    </td>
                                    <td>
                                        <span class="long_text">
                                            {{ is_demo_data($driver->fullName,$driver->Merchant) }}<br>
                                            {{ is_demo_data($driver->phoneNumber,$driver->Merchant) }}<br>
                                            {{ is_demo_data($driver->email,$driver->Merchant) }}
                                        </span>
                                    </td>
                                    @php $created_at = $driver->created_at; $updated_at = $driver->updated_at; @endphp
                                    @if(!empty($driver->CountryArea->timezone))
                                        @php
                                            $created_at = convertTimeToUSERzone($created_at, $driver->CountryArea->timezone, null, $driver->Merchant);
                                            $updated_at = convertTimeToUSERzone($updated_at, $driver->CountryArea->timezone, null, $driver->Merchant);
                                        @endphp
                                    @endif
                                    <td>{!! $created_at !!}</td>
                                    <td>{!! $updated_at !!}</td>
                                    <td>
                                        @if(Auth::user('merchant')->can('edit_drivers'))
                                            <a href="{{ route('driver.add',$driver->id) }}"
                                               data-original-title="@lang("$string_file.complete_signup")"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                                <i
                                                        class="fa fa-edit"></i> </a>
                                        @endif
                                        @if(Auth::user('merchant')->can('delete_drivers') && $delete_permission)
                                            <button onclick="DeleteEvent({{ $driver->id }})"
                                                    type="submit"
                                                    data-original-title="@lang("$string_file.delete")"
                                                    data-toggle="tooltip"
                                                    data-placement="top"
                                                    class="btn menu-icon btn-sm btn-danger action_btn"><i
                                                        class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @php $sr++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $drivers, 'data' => $arr_search])
{{--                    <div class="pagination1 float-right">{{ $drivers->links() }}</div>--}}
                </div>
            </div>
        </div>
    </div>
    <form>
        @csrf
    </form>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        function DeleteEvent(id) {
            var token = $('[name="_token"]').val();
            console.log(token);
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
                            title: "DELETED!",
                            text: data,
                            type: "success",
                        });
                        window.location.href = "{{ route('merchant.driver.basic') }}";
                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }
    </script>
    <br>
    <br>
@endsection
