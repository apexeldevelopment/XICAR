@extends('taxicompany.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">

                    </div>
                    <h3 class="panel-title"><i class="fa fa-taxi" aria-hidden="true"></i>
                        @lang("$string_file.pending_vehicle_approval")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                            <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                                <thead>
                                <tr>
                                    <th>@lang("$string_file.sn")</th>
                                    <th>@lang("$string_file.driver_details")</th>
                                    <th> @lang("$string_file.vehicle")  @lang("$string_file.type")</th>
                                    <th>@lang("$string_file.service_type")</th>
                                    <th>@lang("$string_file.vehicle_number")</th>
                                    <th>@lang("$string_file.color")</th>
                                    <th>@lang("$string_file.image")</th>
                                    <th>@lang("$string_file.number_plate")</th>
                                    <th>@lang("$string_file.action")</th>
                                    <th>@lang("$string_file.created_at") </th>
                                </tr>
                                </thead>
                                <tfoot></tfoot>
                                <tbody>
                                @php $sr = 1; @endphp
                                @foreach($driver_vehicles as $value)
                                    @foreach($value->DriverVehicles as $vehicle)
                                        <tr>
                                            <td>{{ $sr }}</td>
                                            
                                            @if(Auth::user()->demo == 1)
                                                    <td>
                                                            
                                                        {{ "********".substr($value->last_name,-2) }}
                                                        <br>
                                                        {{ "********".substr($value->phoneNumber,-2) }}
                                                        <br>
                                                        {{ "********".substr($value->email,-2) }}
                                                        
                                                    </td>
                                              @else
                                            <td>
                                                {{ $value->first_name." ".$value->last_name }}
                                                <br>
                                                {{ $value->phoneNumber }}
                                                <br>
                                                {{ $value->email }}
                                            </td>
                                            
                                              @endif    
                                            <td>
                                                @if(empty($vehicle->VehicleType->LanguageVehicleTypeSingle))
                                                    {{ $vehicle->VehicleType->LanguageVehicleTypeAny->vehicleTypeName }}
                                                @else
                                                    {{ $vehicle->VehicleType->LanguageVehicleTypeSingle->vehicleTypeName }}
                                                @endif
                                            </td>
                                            <?php $a = array() ?>
                                            @foreach($vehicle->ServiceTypes as $serviceType)
                                                <?php $a[] = $serviceType->serviceName; ?>
                                            @endforeach
                                            <td class="text-center">
                                                {{ implode(',',$a) }}
                                            </td>
                                            <td class="text-center">
                                                {{ $vehicle->vehicle_number }}
                                            </td>
                                            <td class="text-center">
                                                {{ $vehicle->vehicle_color }}
                                            </td>
                                            <td class="text-center">
                                                <a target="_blank"
                                           href="{{ get_image($vehicle->vehicle_image,'vehicle_document', $vehicle->merchant_id) }}">
                                            <img src="{{ get_image($vehicle->vehicle_image,'vehicle_document', $vehicle->merchant_id) }}"
                                                 alt="avatar"
                                                 style="width: 80px;height: 80px;border-radius:10px;">
                                        </a>
                                            </td>
                                            <td class="text-center">
                                                <a target="_blank"
                                           href="{{ get_image($vehicle->vehicle_number_plate_image,'vehicle_document', $vehicle->merchant_id) }}">
                                            <img src="{{ get_image($vehicle->vehicle_number_plate_image,'vehicle_document', $vehicle->merchant_id) }}"
                                                 alt="avatar"
                                                 style="width: 80px;height: 80px;border-radius:10px;">
                                        </a>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('taxicompany.driver.vehicledetails',$vehicle->id) }}"
                                                   class="btn btn-sm btn-info menu-icon btn_detail action_btn"><span
                                                            class="fa fa-list-alt" data-original-title="@lang("$string_file.vehicle")  @lang("$string_file.details")"
                                                            data-toggle="tooltip"></span></a>

                                            </td>
                                            <td class="text-center">
                                                {{ $vehicle->created_at }}
                                            </td>
                                        </tr>
                                        @php $sr++  @endphp
                                    @endforeach
                                @endforeach
                                </tbody>
                            </table>
                </div>
            </div>


                    <div class="col-sm-12">
                        <div class="pagination">{{ $driver_vehicles->links() }}</div>
                    </div>
                </div>


            </div>
        </div>
    </div>

@endsection