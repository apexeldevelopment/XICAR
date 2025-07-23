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
                                <i class="wb-reply" title="@lang("common.all") @lang("$string_file.drivers")"></i>
                            </button>
                        </a>
                        <a href="{{route('excel.rejecteddriver')}}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                <i class="wb-download" title="@lang("common.export") @lang("common.excel")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa fa-taxi" aria-hidden="true"></i>
                        @lang("common.rejected") @lang("$string_file.drivers")</h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! $search_view !!}
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("common.sn")</th>
                            <th> @lang("common.id")</th>
                            <th>@lang("common.service") @lang("common.area") </th>
                            <th>@lang("$string_file.driver") @lang("common.details")</th>
                            <th>@lang("$string_file.vehicle") @lang("common.number")</th>
                            <th>@lang("common.reject") @lang("common.reason")</th>
                            <th>@lang("common.registered") @lang("common.date")</th>
                            <th>@lang("common.updated") @lang("common.at")</th>
                            <th>@lang("common.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $drivers->firstItem() @endphp
                            @foreach($drivers as $driver)
                                <tr>
                                    <td>{{$sr}}</td>
                                    <td><a href="{{ route('driver.show',$driver->id) }}"
                                           class="address_link">{{ $driver->merchant_driver_id }}</a></td>
                                    <td>{{ $driver->CountryArea->CountryAreaName }}</td>
                                    @if(Auth::user()->demo == 1)
                                        <td>
                                            {{ "********".substr($driver->last_name, -2) }}<br>
                                            {{ "********".substr($driver->phoneNumber, -2) }}
                                            <br>
                                            {{ "********".substr($driver->email, -2) }}

                                        </td>
                                    @else
                                        <td>{{ $driver->first_name." ".$driver->last_name }}<br>
                                            {{ $driver->email }}<br>
                                            {{ $driver->phoneNumber }}</td>
                                    @endif
                                    <td>
                                        @foreach($driver->DriverVehicle as $vehicle)
                                            {{$vehicle->vehicle_number}},
                                        @endforeach
                                    </td>
                                    <td>{{ $driver->admin_msg }}</td>
                                    <td>{{ $driver->created_at->toDateString() }}
                                    <br>
                                    {{ $driver->created_at->totimeString()}}</td>
                                    <td>{{ $driver->updated_at->toDateString() }}
                                    <br>
                                    {{ $driver->updated_at->toTimeString() }}</td>
                                    <td>
                                        <a href="{{ route('driver.show',$driver->id) }}"
                                           class="btn btn-sm btn-info menu-icon btn_detail action_btn">
                                            <span class="fa fa-list-alt" title="View Driver Profile"></span>
                                        </a>
                                        <button type="button" onclick="EditDoc(this)" data-ID="{{ $driver->id }}" class="btn btn-sm btn-success"
                                                data-toggle="tooltip" data-placement="bottom" title="Move To Pending">
                                            <span class="fa fa-eyedropper"></span>
                                        </button>
                                        <button onclick="DeleteEvent({{ $driver->id }})"
                                                type="submit"
                                                data-original-title="@lang("common.delete")"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                class="btn btn-sm btn-danger menu-icon btn_delete action_btn">
                                            <i class="fa fa-trash"></i>
                                        </button>
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
    <div class="modal fade text-left" id="moveToPending" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600" id="myModalLabel33">@lang('admin.auth_required')</label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form action="{{route('merchant.driver.move-to-pending')}}" method="post">
                    @csrf
                    <div class="modal-body">
                        <h1 class="text-danger text-center" style="font-size:60px"><i class="fa fa-exclamation-circle"></i></h1>
                        <h5 class="text-danger text-center">@lang('admin.confirmation_to_move')</h5><br>
                        <input type="hidden" id="docId" name="driver_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">@lang("common.close")</button>
                        <button type="submit" class="btn btn-success">@lang('admin.submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        $('.toast').toast('show');
        function EditDoc(obj) {
            let ID = obj.getAttribute('data-ID');
            $(".modal-body #docId").val(ID);
            $('#moveToPending').modal('show');
        }
    </script>
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
                        type: "POST",
                        data: {
                            id: id,
                            request_from:"rejected"
                        },
                        url: "{{ route('driverDelete') }}",
                    }).done(function (data) {
                        swal({
                            title: "DELETED!",
                            text: data,
                            type: "success",
                        });
                        window.location.href = "{{ route('merchant.driver.rejected') }}";
                    });
                } else {
                    swal("@lang('admin.message893')");
                }
            });
        }
    </script>
@endsection