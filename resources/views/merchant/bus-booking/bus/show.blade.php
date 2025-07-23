@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('merchant.bus_booking.bus.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang("$string_file.all_vehicles") "></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa fa-taxi" aria-hidden="true"></i>
                        @lang("$string_file.bus") @lang("$string_file.details") {{ $bus->vehicle_number }}
                        {{--@if(!$result)--}}
                            {{--<span style="color:red; font-size: 14px;">@lang("$string_file.mandatory_document_not_uploaded")</span>--}}
                        {{--@endif--}}
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <span class="">@lang("$string_file.vehicle_type") </span> : {{$bus->VehicleType->VehicleTypeName}}
                            </div>
                            <div class="col-md-4">
                                <span class="">@lang("$string_file.vehicle_model")  </span> : {{$bus->VehicleModel->VehicleModelName}}
                            </div>
                            <div class="col-md-4">
                                <span class="">@lang("$string_file.vehicle_make")  </span> : {{$bus->VehicleMake->VehicleMakeName}}
                            </div>
                            <div class="col-md-4">
                                <span class="">@lang("$string_file.vehicle_number") </span> : {{$bus->vehicle_number}}
                            </div>
                            @if($vehicle_model_expire == 1)
                            <div class="col-md-4">
                                @lang("$string_file.registered_date")   : {!! convertTimeToUSERzone($bus->vehicle_register_date, $bus->CountryArea->timezone,null,$bus->Merchant, 2) !!}
                            </div>
                            <div class="col-md-4">
                                @lang("$string_file.expire_date")   : {!! convertTimeToUSERzone($bus->vehicle_expire_date, $bus->CountryArea->timezone,null,$bus->Merchant, 2) !!}
                            </div>
                            @endif
                            @if(!empty($baby_seat_enable))
                            <div class="col-md-4">
                                @lang("$string_file.baby_seat_enable")   : {{$bus->baby_seat == 1 ? trans("$string_file.yes") : trans("$string_file.no")}}
                            </div>
                            @endif
                            @if(!empty($wheel_chair_enable))
                                <div class="col-md-4">
                                    @lang("$string_file.wheel_chair_enable")   : {{$bus->wheel_chair == 1 ? trans("$string_file.yes") : trans("$string_file.no")}}
                                </div>
                            @endif
                            @if(!empty($bus_ac_enable))
                                <div class="col-md-4">
                                    @lang("$string_file.ac_enable")   : {{$bus->ac_nonac == 1 ? trans("$string_file.yes") : trans("$string_file.no")}}
                                </div>
                            @endif
                        </div>

                        {{--<strong>@lang("$string_file.services") </strong>: {{ implode(',',array_pluck($bus->ServiceTypes,'serviceName')) }}--}}
{{--                        <hr>--}}
                        <div class="row">
                            <div class="col-md-5">
                                <h5>@lang("$string_file.vehicle_image") </h5>
                               @php $bus_image = get_image($bus->vehicle_image,'vehicle_document'); @endphp
                                <div class="" style="width: 6.5rem;">
                                    <div class=" bg-light">
                                        <a href="{{ $bus_image }}" target="_blank">
                                            <img src="{{ $bus_image }}" class="rounded" alt="@lang("$string_file.vehicle_image") " width="100" height="100">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <h5>@lang("$string_file.number_plate") </h5>
                                @php $number_plate = get_image($bus->vehicle_number_plate_image,'vehicle_document'); @endphp
                                <div class="" style="width: 6.5rem;">
                                    <div class=" bg-light">
                                        <a href="{{ $number_plate }}" target="_blank">
                                            <img src="{{ $number_plate }}" class="rounded" alt="@lang("$string_file.vehicle_image") " width="100" height="100">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable">
                                <thead>
                                <tr>
                                    <th>@lang("$string_file.sn")</th>
                                    <th>@lang("$string_file.document_name")</th>
                                    <th>@lang("$string_file.document") </th>
                                    <th>@lang("$string_file.status")</th>
                                    <th>@lang("$string_file.expire_date")  </th>
                                    <th>@lang("$string_file.uploaded_at") </th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $sn= 1; @endphp
                                @foreach($bus->BusDocument as $document)
                                    <tr>
                                        <td>{{$sn}}</td>
                                        <td> {{ $document->Document->documentname }}</td>
                                        <td>
                                            <a href="{{ get_image($document->document,'vehicle_document') }}"
                                               target="_blank"><img
                                                        src="{{ get_image($document->document,'vehicle_document') }}"
                                                        style="width:60px;height:60px;border-radius: 10px"></a>
                                        </td>
                                        <td>
                                            @switch($document->document_verification_status)
                                                @case(1)
                                                @lang("$string_file.pending_for_verification")
                                                @break
                                                @case(2)
                                                @lang("$string_file.verified")
                                                @break
                                                @case(3)
                                                @lang("$string_file.rejected")
                                                @break
                                            @endswitch
                                        </td>
                                        <td>
                                            {!! convertTimeToUSERzone($document->expire_date, $bus->CountryArea->timezone,null,$bus->Merchant, 2) !!}
                                        </td>
                                        <td>
                                            {!! convertTimeToUSERzone($document->created_at, $bus->CountryArea->timezone,null,$bus->Merchant) !!}
                                        </td>
                                        @php $sn = $sn+1; @endphp
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if(count($bus->BusService) > 0)
                                <br>
                                <h5 class="form-section">
                                    <i class="fa fa-taxi"></i> @lang("$string_file.bus_services")
                                </h5>
                                <hr>
                                <div class="row">
                                    @foreach($bus->BusService as $bus_service)
                                        <div class="col-md-3">
                                            <label for="bus_service_{{$bus_service->id}}">
                                                <img src="{{ get_image($bus_service->icon, "bus_service", $bus_service->merchant_id) }}" class="w-p10" >
                                                {{ $bus_service->Name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="location3"><b>@lang("$string_file.additional") @lang("$string_file.bus_details"):</b></label><br>
                                    {!!isset($bus['additional_info'])? $bus['additional_info']:NULL!!}
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection



