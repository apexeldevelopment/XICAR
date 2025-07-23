@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('merchant.driver.allvehicles') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang("$string_file.all") @lang("$string_file.vehicles") "></i>
                            </button>
                        </a>
                    </div>
                    {{-- <h3 class="panel-title"><i class="fa fa-taxi" aria-hidden="true"></i>
                        @lang("$string_file.vehicle") @lang("$string_file.details") @lang("$string_file.of") {{ $vehicle->vehicle_number }}
                        @if(!$result)
                            <span style="color:red; font-size: 14px;">@lang('$string_file.mandatory_document_not_uploaded')</span>
                        @endif
                    </h3> --}}
                </header>
                <div class="panel-body container-fluid">
                    <div class="card-body">
{{--                        <h5> @lang("$string_file.vehicle") @lang("$string_file.type") : {{ $vehicle->VehicleType->VehicleTypeName }}</h5>--}}
{{--                        <hr>--}}
                        <h5>@lang("$string_file.vehicle")  @lang("$string_file.details")</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
{{--                                <span class="">@lang("$string_file.vehicle") @lang("$string_file.type") </span> : {{!empty($vehicle->VehicleType) ? $vehicle->VehicleType->VehicleTypeName : ""}}--}}
                                <span class="">@lang("$string_file.vehicle") @lang("$string_file.model")  </span> : {{!empty($vehicle->VehicleModel) ? $vehicle->VehicleModel->VehicleModelName : ""}}
                            </div>
                            <div class="col-md-4">
                                <span class="">@lang("$string_file.vehicle") @lang("$string_file.model")  </span> : {{$vehicle->VehicleModel->VehicleModelName}}
                            </div>
                            <div class="col-md-4">
{{--                                <span class="">@lang("$string_file.vehicle") @lang("$string_file.make")  </span> : {{$vehicle->VehicleMake->VehicleMakeName}}--}}
                                <span class="">@lang("$string_file.vehicle") @lang("$string_file.make")  </span> : {{!empty($vehicle->VehicleMake) ? $vehicle->VehicleMake->VehicleMakeName : ""}}
                            </div>
                            <div class="col-md-4">
                                <span class="">@lang("$string_file.vehicle") @lang("$string_file.number") </span> : {{$vehicle->vehicle_number}}
                            </div>
                            <div class="col-md-4">
                                @lang("$string_file.vehicle") @lang("$string_file.registered") @lang("$string_file.date")   : {{$vehicle->vehicle_register_date}}
                            </div>
                        </div>

                        
                        <div class="row">
                            <div class="col-md-5">
                                <h5>@lang("$string_file.vehicle") @lang("$string_file.image") </h5>
                               @php $vehicle_image = get_image($vehicle->vehicle_image,'user_vehicle_document'); @endphp
                                <div class="" style="width: 6.5rem;">
                                    <div class=" bg-light">
                                        <a href="{{ $vehicle_image }}" target="_blank">
                                            <img src="{{ $vehicle_image }}" class="rounded" alt="@lang("$string_file.vehicle") @lang("$string_file.image") " width="100" height="100">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <h5>@lang("$string_file.vehicle")  @lang("$string_file.number") @lang("$string_file.plate")  @lang("$string_file.image") </h5>
                                @php $number_plate = get_image($vehicle->vehicle_number_plate_image,'user_vehicle_document'); @endphp
                                <div class="" style="width: 6.5rem;">
                                    <div class=" bg-light">
                                        <a href="{{ $number_plate }}" target="_blank">
                                            <img src="{{ $number_plate }}" class="rounded" alt="@lang("$string_file.vehicle") @lang("$string_file.image") " width="100" height="100">
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
                                    <th>@lang("$string_file.document") @lang("$string_file.name")</th>
                                    <th>@lang("$string_file.document") </th>
                                    <th>@lang("$string_file.status")</th>
                                    <th>@lang("$string_file.expire") @lang("$string_file.date")  </th>
                                    <th>@lang("$string_file.uploaded") @lang("$string_file.time") </th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $sn= 1; @endphp
                                @foreach($vehicle->UserVehicleDocument as $document)
                                    <tr>
                                        <td>{{$sn}}</td>
                                        <td> {{ $document->Document->documentname }}</td>
                                        <td>
                                            <a href="{{ get_image($document->document,'user_vehicle_document') }}"
                                               target="_blank"><img
                                                        src="{{ get_image($document->document,'user_vehicle_document') }}"
                                                        style="width:60px;height:60px;border-radius: 10px"></a>
                                        </td>
                                        <td>
                                            @switch($document->document_verification_status)
                                                @case(1)
                                                @lang("$string_file.pending") @lang("$string_file.for") @lang("$string_file.verification")
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
                                            {{ $document->expire_date  }}
                                        </td>
                                        <td>
                                            {{ $document->created_at }}
                                        </td>
                                        @php $sn = $sn+1; @endphp
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="text-right m-3">
                      @if($result && $vehicle->vehicle_verification_status != 2)
                            <a href="{{ route('merchant.uservehicles-vehicle-verify',[$vehicle->id,2]) }}">
                                <button class="btn btn-md btn-success" style="width: 80px">@lang("$string_file.approve") </button>
                            </a>
                        @endif
                        <a href="#">
                            <button class="btn btn-md btn-danger" style="width: 80px"
                                    data-toggle="modal"
                                    data-target="#exampleModalCenter">@lang("$string_file.reject")
                            </button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form class="form-group" action="{{ route('merchant.user-vehicle-reject') }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalCenterTitle">@lang("$string_file.reject") @lang("$string_file.vehicle") </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h5>@lang("$string_file.vehicle") @lang("$string_file.documents")</h5>
                            </div>
                            <input type="hidden" value="{{ $vehicle->user_id }}" name="user_id">
                            <input type="hidden" value="{{ $vehicle->id }}"
                                   name="user_vehicle_id">
                            @foreach($vehicle->UserVehicleDocument as $document)
                                <div class="col-md-6">
                                    <input type="checkbox" value="{{ $document->id }}"
                                           name="vehicle_documents[]"> {{ $document->Document->documentname }}
                                </div>
                            @endforeach
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                {!! Form::hidden('request_from','vehicle_details') !!}
                                <textarea class="form-control" placeholder="@lang("$string_file.comments")" name="comment" required></textarea>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">@lang("$string_file.close")</button>
                        <button type="submit" class="btn btn-primary">@lang("$string_file.reject") </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection



