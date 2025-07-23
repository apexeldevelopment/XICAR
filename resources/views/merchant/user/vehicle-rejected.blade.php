@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('users.index')}}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang("common.all") @lang("common.users") "></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa-taxi" aria-hidden="true"></i>
                        @lang("common.rejected") @lang("$string_file.vehicle") </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th> @lang("common.id")</th>
                            <th>@lang("common.user") @lang("common.details")</th>
                            <th>@lang("$string_file.vehicle") @lang("common.number")</th>
                            <th>@lang("$string_file.vehicle") @lang("common.type") </th>
                            <th>@lang("common.image")</th>
                            <th>@lang("common.number") @lang("$string_file.plate") @lang("common.image")</th>
                            <th>@lang("common.registered") @lang("common.date")</th>
                            <th>@lang("common.update")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sn = 1; @endphp
                        @if(count($vehicles) > 0)
                            @foreach($vehicles as $vehicle)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.show',$vehicle->User->id) }}"
                                           class="address_link">{{ $sn }}</a>
                                    </td>
                                    <td>
                                        <span class="long_text">
                                            {!! is_demo_data($vehicle->User->UserName, $vehicle->User->Merchant) !!}<br>
                                            {!! is_demo_data($vehicle->User->UserPhone, $vehicle->User->Merchant) !!}<br>
                                            {!! is_demo_data($vehicle->User->email, $vehicle->User->Merchant) !!}
                                        </span>
                                    </td>
                                    <td>{{ $vehicle->vehicle_number }}</td>
                                    <td>
                                        {{ $vehicle->VehicleType->VehicleTypeName}}
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
                                    <td>{{ $vehicle->created_at->toDateString() }}
                                    <br>
                                    {{ $vehicle->created_at->toTimeString() }}</td>
                                    <td>{{ $vehicle->updated_at->toDateString() }}
                                    <br>
                                    {{ $vehicle->updated_at->toTimeString() }}</td>
                                </tr>
                                @php $sn++; @endphp
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $vehicles, 'data' => []])
                </div>
            </div>
        </div>
    </div>
@endsection
