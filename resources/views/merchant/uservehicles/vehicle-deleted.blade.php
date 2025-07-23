@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('merchant.driver.allvehicles')}}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang("$string_file.all") @lang("$string_file.vehicles") "></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa-taxi" aria-hidden="true"></i>
                        @lang("$string_file.rejected") @lang("$string_file.vehicle") </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th> @lang("$string_file.id")</th>
                            <th>@lang("$string_file.vehicle") @lang("$string_file.id")</th>
                            <th>@lang("$string_file.user") @lang("$string_file.details")</th>
                            <th>@lang("$string_file.vehicle") @lang("$string_file.type") </th>
                            <th>@lang("$string_file.vehicle") @lang("$string_file.number")</th>

                            <th>@lang("$string_file.image")</th>
                            <th>@lang("$string_file.number") @lang("$string_file.plate") @lang("$string_file.image")</th>
                            <th>@lang("$string_file.registered") @lang("$string_file.date")</th>
                            <th>@lang("$string_file.update")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $user_vehicles->firstItem() @endphp
                        @foreach($user_vehicles as $vehicle)
                                <tr>
                                    <td>{{ $sr }}</td>
                                    <td>{{ $vehicle->id }}</td>
                                    <td>
                                        <span class="long_text">
                                            {!! is_demo_data($vehicle->OwnerUser->UserName, $vehicle->OwnerUser->Merchant) !!}<br>
                                            {!! is_demo_data($vehicle->OwnerUser->UserPhone, $vehicle->OwnerUser->Merchant) !!}<br>
                                            {!! is_demo_data($vehicle->OwnerUser->email, $vehicle->OwnerUser->Merchant) !!}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                    {{ $vehicle->VehicleType->VehicleTypeName}}
                                    </td>
                                    <td class="text-center">
                                        {{ $vehicle->vehicle_number }}
                                    </td>


                                    <td class="text-center">
                                        <a target="_blank"
                                           href="{{ get_image($vehicle->vehicle_image,'user_vehicle_document') }}">
                                            <img src="{{ get_image($vehicle->vehicle_image,'user_vehicle_document') }}"
                                                 alt="avatar"
                                                 style="width: 80px;height: 80px;border-radius:10px;">
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a target="_blank"
                                           href="{{ get_image($vehicle->vehicle_number_plate_image,'user_vehicle_document') }}">
                                            <img src="{{ get_image($vehicle->vehicle_number_plate_image,'user_vehicle_document') }}"
                                                 alt="avatar"
                                                 style="width: 80px;height: 80px;border-radius:10px;">
                                        </a>
                                    </td>

                                    <td class="text-center">
                                        {{ $vehicle->created_at->toDateString() }}
                                        <br>
                                        {{ $vehicle->created_at->toTimeString() }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('merchant.driver-vehicledetails',$vehicle->id) }}"
                                           class="btn btn-sm btn-info menu-icon btn_detail action_btn"><span
                                                    class="fa fa-list-alt"
                                                    data-original-title="@lang("$string_file.vehicle")  @lang("$string_file.details")"
                                                    data-toggle="tooltip"></span></a>

                                    </td>
                                </tr>
                                @php $sr++  @endphp
                            @endforeach
                        </tbody>
                    </table>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $user_vehicles, 'data' => []])
                </div>
            </div>
        </div>
    </div>
@endsection
