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
                        @if(Auth::user('merchant')->can('create_drivers'))
                            <a href="{{route('merchant.bus_booking.bus.create')}}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-plus" title="@lang(" $string_file.add_driver")"></i>
                                </button>
                            </a>
                        @endif

                    </div>
                    <h3 class="panel-title"><i class="fa fa-taxi" aria-hidden="true"></i>
                        @lang("$string_file.all_buses")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>"",'url'=>route('merchant.driver.allvehicles'),'method'=>"GET"]) !!}
                    <div class="table_search row">
                        @php $vehicletype = NULL; $vehicle_number = "";$searched_param = NULL; $searched_area = NULL; $searched_text = ""; @endphp
                        @if(!empty($arr_search))
                            @php $vehicletype = isset($arr_search['vehicletype']) ? $arr_search['vehicletype'] : NULL ;
                    $searched_param = isset($arr_search['parameter']) ? $arr_search['parameter'] : NULL;
                    $searched_area = isset($arr_search['area_id']) ? $arr_search['area_id'] : NULL;

                    $vehicle_number = isset($arr_search['vehicleNumber']) ? $arr_search['vehicleNumber'] : ""; @endphp
                        @endif


                        <div class="col-md-2 col-xs-12 form-group active-margin-top">
                            <div class="">
                                {!! Form::select('area_id',add_blank_option($areas,trans("$string_file.area")),$searched_area,['class'=>'form-control select2','id'=>'area_id']) !!}
                            </div>
                        </div>
                        <div class="col-md-2 col-xs-12 form-group active-margin-top">
                            <div class="">
                                <select class="form-control" name="vehicletype" id="vehicletype">
                                    <option value="">--@lang("$string_file.vehicle_type")--</option>
                                    @foreach($vehicle_types as $vehicle_type)
                                        <option value="{{ $vehicle_type->id }}"
                                                @if($vehicletype==$vehicle_type->id) selected @endif>{{ $vehicle_type->VehicleTypeName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 col-xs-12 form-group active-margin-top">
                            <div class="input-group">
                                <input type="text" id="" name="vehicleNumber" value="{{$vehicle_number}}"
                                       placeholder="@lang(" $string_file.vehicle_number") "
                                       class=" form-control col-md-12 col-xs-12">
                            </div>
                        </div>
                        <div class="col-sm-2  col-xs-12 form-group active-margin-top">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"
                                                                             aria-hidden="true"></i>
                            </button>
                            <a href="{{route('merchant.driver.allvehicles')}}">
                                <button class="btn btn-success" type="button"><i class="fa fa-refresh"
                                                                                 aria-hidden="true"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    {!! Form::close() !!}
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.vehicle") </th>
                            <th>@lang("$string_file.vehicle") @lang("$string_file.details")</th>
                            <th>@lang("$string_file.other") @lang("$string_file.details")</th>
                            @if($vehicle_model_expire == 1)
                                <th>@lang("$string_file.registered_date")</th>
                                <th>@lang("$string_file.expire_date")</th>
                            @endif
                            <th>@lang("$string_file.image")</th>
                            <th>@lang("$string_file.number_plate")</th>
                            <th>@lang("$string_file.action")</th>
                            <th>@lang("$string_file.created_at") </th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $buses->firstItem() @endphp
                        @foreach($buses as $bus)

                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    {{ $bus->VehicleType->VehicleTypeName }}<br>
                                    {{ $bus->VehicleMake->VehicleMakeName }}<br>
                                    {{ $bus->VehicleModel->VehicleModelName }}
                                </td>
                                <td>
                                    @lang("$string_file.name") : {{ $bus->bus_name }}<br>
                                    @lang("$string_file.color") : {{ $bus->vehicle_color }}<br>
                                    @lang("$string_file.type") : {{ $bus_types[$bus->type] }}<br>
                                    @lang("$string_file.design_type") : {{ $bus_design_types[$bus->design_type] }}
                                </td>
                                <td>
                                    @lang("$string_file.number") : {{ $bus->vehicle_number }}<br>
                                    @lang("$string_file.id") : {{ $bus->shareCode }}
                                </td>
                                @if($vehicle_model_expire == 1)
                                    <td>
                                        {!! convertTimeToUSERzone($bus->vehicle_register_date, $bus->CountryArea->timezone,null,$bus->Merchant, 2) !!}
                                    </td>
                                    <td>
                                        {!! convertTimeToUSERzone($bus->vehicle_expire_date, $bus->CountryArea->timezone,null,$bus->Merchant, 2) !!}
                                    </td>
                                @endif
                                <td class="text-center">
                                    <a target="_blank"
                                       href="{{ get_image($bus->vehicle_image,'vehicle_document') }}">
                                        <img src="{{ get_image($bus->vehicle_image,'vehicle_document') }}"
                                             alt="avatar" style="width: 80px;height: 80px;border-radius:10px;">
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a target="_blank"
                                       href="{{ get_image($bus->vehicle_number_plate_image,'vehicle_document') }}">
                                        <img src="{{ get_image($bus->vehicle_number_plate_image,'vehicle_document') }}"
                                             alt="avatar" style="width: 80px;height: 80px;border-radius:10px;">
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('merchant.bus-booking.bus.show',$bus->id) }}"
                                       class="btn btn-sm btn-info menu-icon btn_detail action_btn"><span
                                                class="fa fa-list-alt"
                                                data-original-title="@lang(" $string_file.bus") @lang("$string_file.details")"
                                                data-toggle="tooltip"></span></a>

                                    @if(Auth::user('merchant')->can('edit_vehicle'))
                                        <a href="{{ route('merchant.bus_booking.bus.create',[$bus->id]) }}"
                                           data-original-title="@lang(" $string_file.edit_vehicle") "
                                           data-toggle=" tooltip" data-placement="top"
                                           class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                            <i class="fa fa-edit"></i> </a>
                                    @endif
                                    <a href="{{ route('merchant.bus_booking.bus.seat_config',[$bus->id]) }}"
                                       data-original-title="@lang(" $string_file.seat_config") "
                                       data-toggle=" tooltip" data-placement="top"
                                       class="btn btn-sm btn-info menu-icon btn_edit action_btn">
                                        <i class="fa fa-sign-out"></i> </a>
                                    {{--@if(Auth::user('merchant')->can('delete_vehicle'))--}}
                                        {{--<button onclick="DeleteEvent({{ $bus->id }})" type="submit"--}}
                                                {{--data-original-title="@lang(" $string_file.delete")"--}}
                                                {{--data-toggle="tooltip" data-placement="top"--}}
                                                {{--class="btn btn-sm btn-danger menu-icon btn_delete action_btn">--}}
                                            {{--<i class="fa fa-trash"></i></button>--}}
                                    {{--@endif--}}
                                </td>
                                <td class="text-center">
                                    {!! convertTimeToUSERzone($bus->created_at, $bus->CountryArea->timezone, null, $bus->Merchant) !!}
                                </td>
                            </tr>
                            @php $sr++ @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $buses, 'data' => $arr_search])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script>
        {{--function DeleteEvent(id) {--}}
            {{--var token = $('[name="_token"]').val();--}}
            {{--swal({--}}
                {{--title: "@lang("$string_file.are_you_sure ")",--}}
                {{--text: "@lang("$string_file.delete_warning ")",--}}
                {{--icon: "warning",--}}
                {{--buttons: true,--}}
                {{--dangerMode: true,--}}
            {{--}).then((isConfirm) => {--}}
                {{--if (isConfirm) {--}}
                    {{--$.ajax({--}}
                        {{--headers: {--}}
                            {{--'X-CSRF-TOKEN': token--}}
                        {{--},--}}
                        {{--type: "GET",--}}
                        {{--url: "{{ route('driver.delete.pendingvehicle') }}" + "/" + id,--}}
                    {{--}).done(function (data) {--}}
                        {{--swal({--}}
                            {{--title: "DELETED!",--}}
                            {{--text: data,--}}
                            {{--type: "success",--}}
                        {{--});--}}
                        {{--window.location.href = "{{ route('merchant.driver.allvehicles') }}";--}}
                    {{--});--}}
                {{--} else {--}}
                    {{--swal("@lang("$string_file.data_is_safe ")");--}}
                {{--}--}}
            {{--});--}}
        {{--}--}}
    </script>
@endsection
