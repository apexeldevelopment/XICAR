@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <div class="panel-heading">
                <div class="panel-actions">
                    @if(!empty($info_setting) && $info_setting->view_text != "")
                    <button class="btn btn-icon btn-primary float-right" style="margin:10px" data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                        <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                    </button>
                    @endif
                    @if(Auth::user('merchant')->can('create_documents'))
                    <a href="{{ route('bus_booking.bus_driver_mapping.create') }}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="wb-plus" title="@lang(" $string_file.bus_driver_mapping")"></i>
                        </button>
                    </a>
                    @endif
                </div>
                <h3 class="panel-title"><i class="fa-files-o" aria-hidden="true"></i>
                    @lang("$string_file.bus_driver_mapping")
                </h3>
            </div>
            @csrf
            <div class="panel-body container-fluid">
                <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.bus_routes") </th>
                            <th>@lang("$string_file.bus_details") </th>
                            <th>@lang("$string_file.driver_details") </th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = $bus_driver_mapping->firstItem() @endphp
                        @foreach($bus_driver_mapping as $bus_mapping)

                        <tr>
                            <td>{{ $sr }}</td>
                            <td>{{$bus_mapping->BusRoute->Name}}</td>
                            <td>{{ $bus_mapping->Bus->vehicle_number . ' | ' . $bus_mapping->Bus->vehicle_color . ' | ' . $bus_mapping->Bus->VehicleType->VehicleTypeName }}</td>
                            <td>
                                {{ is_demo_data($bus_mapping->Driver->fullName, $bus_mapping->Driver->Merchant) }}<br>
                                {{ is_demo_data($bus_mapping->Driver->phoneNumber, $bus_mapping->Driver->Merchant) }}<br>
                                {{ is_demo_data($bus_mapping->Driver->email, $bus_mapping->Driver->Merchant) }}
                            </td>
                            <td>
                                @if($bus_mapping->status == 1)
                                <span class="badge badge-success">@lang("$string_file.active")</span>
                                @else
                                <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                @endif
                            </td>
                            <td style="width: 100px;float: left">
                                <a href="{{ route('bus_booking.bus_driver_mapping.create',[$bus_mapping->bus_route_id,$bus_mapping->bus_id,$bus_mapping->driver_id]) }}">
                                    <button type="button" class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                </a>
                                <a onclick='DeleteEvent("{{$bus_mapping->bus_route_id}}","{{$bus_mapping->bus_id}}","{{$bus_mapping->driver_id}}")'>
                                    <button type="button" class="btn btn-sm btn-danger menu-icon btn_edit action_btn">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </a>
                            </td>
                        </tr>
                        @php $sr++ @endphp
                        @endforeach
                    </tbody>
                </table>
                @include('merchant.shared.table-footer', ['table_data' => $bus_driver_mapping, 'data' => []])
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
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
        function DeleteEvent(bus_route_id, bus_id, driver_id) {
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
                        url: "{{ route('bus_booking.bus_driver_mapping.delete') }}",
                        data:{
                            bus_route_id:bus_route_id,
                            bus_id:bus_id,
                            driver_id:driver_id,
                        }
                    }).done(function (data) {
                        if(data.result == 1){
                            swal({
                                title: "DELETED!",
                                text: data.message,
                                type: "success",
                            }).then((isConfirm) => {
                                window.location.href = "{{ route('bus_booking.bus_driver_mapping') }}";
                            });
                        }else{
                            swal({
                                title: "WARNING!",
                                text: data.message,
                                type: "error",
                            });
                        }
                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }
    </script>
@endsection
