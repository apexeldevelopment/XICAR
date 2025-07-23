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
                                <i class="wb-reply" title="@lang("$string_file.all_drivers")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-user" aria-hidden="true"></i>
                        @lang("$string_file.driver_profile")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <div id="user-profile">
                        <div class="col-md-12">
                            <h5>@lang("$string_file.personal_details")</h5>
                            <hr>
                            <div class="row">
                                <!-- Column -->
                                <div class="col-md-4 col-xs-12">
                                    <div class="card my-2 shadow  bg-white h-240">
                                        <div class="justify-content-center p-3">
                                            <div class="col-md-12 col-xs-12"
                                                 style="text-align:center;justify-content:center">
                                                <div class="mt-15 mb-15 h-100">
                                                    <img height="100" width="100" class="rounded-circle"
                                                         src="@if ($driver->profile_image) {{ get_image($driver->profile_image,'driver') }}@endif">
                                                </div>
                                            </div>
                                            <div class="overlay-box">
                                                <div class="user-content " style="text-align:center">
                                                    <h5 class="user-name mt-5 mb-3">@lang("$string_file.name")
                                                        : {{ is_demo_data($driver->fullName, $driver->Merchant) }}</h5>
                                                    <h6 class="user-job mb-3"> @lang("$string_file.email")
                                                        : {{ is_demo_data($driver->email, $driver->Merchant) }}</h6>
                                                    <h6 class="user-location mb-5"> @lang("$string_file.phone")
                                                        : {{ is_demo_data($driver->phoneNumber, $driver->Merchant) }}</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                                <div class="col-md-8 col-xs-12 mt-20">
                                    <div class="row">
                                        <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                            <div class="border-left-success">
                                                <div class="">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="h6  text-uppercase mb-1">@lang("$string_file.service_area") </div>
                                                            <div class="h6 mb-0 font-weight-400"
                                                                 style="color:#7c8c9a">@if($driver->CountryArea->LanguageSingle) {{ $driver->CountryArea->LanguageSingle->AreaName }} @else  {{ $driver->CountryArea->LanguageAny->AreaName }} @endif</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- display driver wallet number--}}
                                        @if($config->driver_wallet_status == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3 ">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.wallet_money")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a"> {{ $driver->wallet_money }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($config->bank_details_enable == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6  text-uppercase mb-1">@lang("$string_file.account_holder_name")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{$driver->account_holder_name}}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                    <div class="row">
                                        @if($config->driver_limit == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6  text-uppercase mb-1">@lang("$string_file.radius_limit")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">
                                                                    @if(isset($driver_config->radius)){{$driver_config->radius}}
                                                                    <a target="_blank"
                                                                       href="https://www.google.com/maps/place/{{ $driver_config->latitude }},{{  $driver_config->longitude }}"
                                                                       class="ml-2" title="View Map">
                                                                        <i class="fa fa-map" aria-hidden="true"></i>
                                                                    </a>
                                                                    @else -- @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if(!empty(Auth::user('merchant')->ApplicationConfiguration) && Auth::user('merchant')->ApplicationConfiguration->gender == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.gender")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">
                                                                    @if($driver->driver_gender == 1) @lang("$string_file.male") @else @lang("$string_file.female")  @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($config->bank_details_enable == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.bank_name")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{$driver->bank_name}}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                    <div class="row">
                                        @if($driver->account_type_id)
                                            <div class="col-md-4 col-sm-4 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.account_type")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{ $driver->AccountType->name }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($driver->online_code)
                                            <div class="col-md-4 col-sm-4 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.transaction_code")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{ $driver->online_code }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if(!empty(Auth::user('merchant')->ApplicationConfiguration) && Auth::user('merchant')->ApplicationConfiguration->smoker == 1)
                                            <div class="col-md-4 col-sm-4 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1"> @lang("$string_file.smoke")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">
                                                                    @if($driver->DriverRideConfig) @if($driver->DriverRideConfig->smoker_type == 1)  @lang("$string_file.smoker") @else  @lang("$string_file.non_smoker") @endif @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="row">
                                        @if($config->bank_details_enable == 1)
                                            <div class="col-md-4 col-sm-4 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.account_number")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{$driver->account_number}}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($config->driver_address)
                                            <div class="col-md-4 col-sm-6 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.address")</div>
                                                                @php $additionalData = json_decode($driver->driver_additional_data, true); @endphp
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">
                                                                    @if(!empty($additionalData))
                                                                        @foreach($additionalData as $key => $value)
                                                                            {{ trans("$string_file.$key") .' : '. is_demo_data(ucwords($value), $driver->Merchant) }}<br>
                                                                        @endforeach
                                                                    @else
                                                                        @lang("$string_file.data_not_found")
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if(!empty(Auth::user('merchant')->ApplicationConfiguration) && Auth::user('merchant')->ApplicationConfiguration->driver_vat_configuration == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6  text-uppercase mb-1">@lang("$string_file.vat_number")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{$driver->vat_number}}({{($driver->is_vat_liable == 1) ? 'VAT liable' : 'VAT exempted'}})</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if(isset($driver->subscription_wise_commission) && $driver->subscription_wise_commission != NULL)
                                            <div class="col-md-4 col-sm-6 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.commission_type")</div>
                                                                @if($driver->subscription_wise_commission == 2)
                                                                    <div class="h6 mb-0 font-weight-400"
                                                                         style="color:#7c8c9a">
                                                                        @lang("$string_file.commission_based")
                                                                    </div>
                                                                @elseif($driver->subscription_wise_commission == 1)
                                                                    <div class="h6 mb-0 font-weight-400"
                                                                         style="color:#7c8c9a">
                                                                        @lang("$string_file.subscription_based")<br>
                                                                        @lang("$string_file.current_package")
                                                                        :- {{ isset($package_name) ? $package_name : '---' }}
                                                                    </div>
                                                                @else
                                                                    @lang("$string_file.data_not_found")
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-12 mt-20">
                                    <h5>@lang("$string_file.personal_document")</h5>
                                    <hr>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable">
                                            <thead>
                                            <tr class="text-center">
                                                <th>@lang("$string_file.sn")</th>
                                                <th>@lang("$string_file.document_name")</th>
                                                <th>@lang("$string_file.document") </th>
                                                <th>@lang("$string_file.expire_date")  </th>
                                                <th>@lang("$string_file.status")</th>
                                                <th>@lang("$string_file.uploaded_at") </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @php $doc_sn = 1 @endphp
                                            @foreach($driver->DriverDocument as $document)
                                                <tr>
                                                    <td class="text-center">
                                                        {{$doc_sn}}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $document->Document->DocumentName }}
                                                    </td>
                                                    <td class="text-center">
                                                        @php
                                                            $is_pdf = check_file_extension($document->document_file);
                                                            $p_doc = get_image($document->document_file,'driver_document');
                                                        @endphp
                                                        @if($is_pdf)
                                                            <embed src="{{ $p_doc }}" width="100px" height="100px" />
                                                            <br>
                                                            <a target="_blank" href="{{ $p_doc }}">@lang("$string_file.view")</a>
                                                        @else
                                                            <a target="_blank" href="{{ $p_doc }}">
                                                                <img src="{{ $p_doc }}" alt="avatar" style="width: 100px;height: 100px;">
                                                            </a>
                                                        @endif

                                                    </td>
                                                    <!-- <td class="text-center">{!! convertTimeToUSERzone($document->expire_date, $driver->CountryArea->timezone,null,$driver->Merchant, 2) !!}</td> -->
                                                    <td class="text-center">{!! $document->expire_date  !!}</td>
                                                    <td class="text-center">
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
                                                            @case(4)
                                                                @lang("$string_file.expired")
                                                                @break
                                                        @endswitch
                                                    </td>
                                                    <td class="text-center">
                                                        {!! convertTimeToUSERzone($document->updated_at, $driver->CountryArea->timezone,null,$driver->Merchant) !!}
                                                    </td>
                                                </tr>
                                                @php $doc_sn = $doc_sn+1; @endphp
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <form action="{{route('driver.training.profile.update', ['id'=> $driver->id])}}" method="post">
                                    @csrf
                                    @if(!empty($all_vehicle_details))
                                        @foreach($all_vehicle_details as $vehicle_details )
                                            <div class="col-md-12 mt-20 mb-10">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h5>@lang("$string_file.vehicle_details")</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row mt-20">
                                                        <div class="col-lg-8 mb-30">
                                                            <div class="">
                                                                <span class="">@lang("$string_file.vehicle_type") </span> : {{$vehicle_details->VehicleType->VehicleTypeName}}  |
                                                                <span class="">@lang("$string_file.vehicle_model")  </span> : {{$vehicle_details->VehicleModel->VehicleModelName}} |
                                                                <span class="">@lang("$string_file.vehicle_make")  </span> : {{$vehicle_details->VehicleMake->VehicleMakeName}} <br>

                                                                <span class="">@lang("$string_file.vehicle_number") </span> : {{$vehicle_details->vehicle_number}} <br>@if($config->vehicle_model_expire == 1) @lang("$string_file.vehicle_registered_date") : {!! convertTimeToUSERzone($vehicle_details->vehicle_register_date, $driver->CountryArea->timezone,null,$driver->Merchant,2) !!} |  @lang("$string_file.expire_date") : {!! convertTimeToUSERzone($vehicle_details->vehicle_expire_date, $driver->CountryArea->timezone,null,$driver->Merchant,2) !!} @endif
                                                                <br>
                                                                <span>@lang("$string_file.services") : {{ implode(',',array_pluck($vehicle_details->ServiceTypes,'serviceName'))}}</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-4">
                                                            <div class="row">
                                                                <div class="col-md-6 col-sm-5">
                                                                    <h6>@lang("$string_file.vehicle_image") </h6>
                                                                    <div class="" style="width: 6.5rem;">
                                                                        <div class=" bg-light">

                                                                            @php
                                                                                $is_pdf = check_file_extension($vehicle_details->vehicle_image);
                                                                                $vehicle_image = get_image($vehicle_details->vehicle_image,'vehicle_document');
                                                                            @endphp
                                                                            @if($is_pdf)
                                                                                <embed src="{{ $vehicle_image }}" width="100px" height="100px" />
                                                                                <br>
                                                                                <a target="_blank" href="{{ $vehicle_image }}">@lang("$string_file.view")</a>
                                                                            @else
                                                                                <a target="_blank" href="{{ $vehicle_image }}">
                                                                                    <img src="{{ $vehicle_image }}" alt="avatar" style="width: 100px;height: 100px;">
                                                                                </a>
                                                                            @endif



                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6 col-sm-7">
                                                                    <h6>@lang("$string_file.vehicle")  @lang("$string_file.number_plate")  @lang("$string_file.image") </h6>
                                                                    <div class="" style="width: 6.5rem;">
                                                                        <div class=" bg-light">
                                                                            @php $vehicle_number_plate_image = get_image($vehicle_details->vehicle_number_plate_image,'vehicle_document'); @endphp
                                                                            <a href="{{ $vehicle_number_plate_image }}" target="_blank"><img src="{{ $vehicle_number_plate_image }}" style="width:100%;height:80px;"></a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6 col-sm-5">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input"
                                                                               type="checkbox"
                                                                               name="vehicle_id[]"
                                                                               value="{{$vehicle_details->id}}"
                                                                               id="corporate_pref{{$vehicle_details->id}}}">
                                                                        <label class="form-check-label"
                                                                               for="corporate_pref{{$vehicle_details->id}}}">
                                                                            @lang("$string_file.preferred_for_corporate")
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <h5>@lang("$string_file.current_vehicle_documents")</h5>
                                                    <hr>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered" id="dataTable">
                                                            <thead>
                                                            <tr>
                                                                <th>@lang("$string_file.sn")</th>
                                                                <th>@lang("$string_file.document_name")</th>
                                                                <th>@lang("$string_file.document")</th>
                                                                <th>@lang("$string_file.status")</th>
                                                                <th>@lang("$string_file.expire_date")</th>
                                                                <th>@lang("$string_file.uploaded_at")</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            @php $sn = 1; @endphp
                                                            @foreach($vehicle_details->DriverVehicleDocument as $document)
                                                                <tr>
                                                                    <td>{{$sn}}</td>
                                                                    <td> {{ $document->Document->documentname }}</td>
                                                                    <td>
                                                                        @php $vehicle_file = get_image($document->document,'vehicle_document'); @endphp
                                                                        <a href="{{ $vehicle_file }}" target="_blank"><img src="{{ $vehicle_file }}" style="width:60px;height:60px;border-radius: 10px"></a>
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
                                                                            @case(4)
                                                                                @lang("$string_file.expired")
                                                                                @break
                                                                        @endswitch
                                                                    </td>
                                                                    <td>
                                                                        <!-- {!! convertTimeToUSERzone($document->expire_date, $driver->CountryArea->timezone,null,$driver->Merchant,2) !!} -->
                                                                        {!! $document->expire_date !!}
                                                                    </td>
                                                                    <td>
                                                                        {!! convertTimeToUSERzone($document->updated_at, $driver->CountryArea->timezone,null,$driver->Merchant) !!}
                                                                    </td>
                                                                    @php $sn = $sn+1; @endphp
                                                                </tr>
                                                            @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                        @endforeach
                                    @endif

                                    <div class="col-md-12 col-xs-12">
                                        <div class="card">
                                            <div class="card-header">
                                                @lang("$string_file.driver") @lang("$string_file.bank") @lang("$string_file.details")
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="bank_name">@lang("$string_file.bank_name")<span class="text-danger">*</span> </label>
                                                            <input type="text" name="bank_name" id="bank_name" class="form-control" >
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="bank_name">@lang("$string_file.bank_name")<span class="text-danger">*</span> </label>
                                                            <select name="account_type_id" class="form-control" id="account_type_id">

                                                                @foreach($account_types as $account_type)
                                                                    <option value="{{$account_type->id}}">
                                                                        {{$account_type->getNameAttribute()}}</option>

                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="account_holder_name">@lang("$string_file.account_holder_name")<span class="text-danger">*</span> </label>
                                                            <input type="text" name="account_holder_name" id="account_holder_name" class="form-control" >
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="account_number">@lang("$string_file.account_number")<span class="text-danger">*</span> </label>
                                                            <input type="text" name="account_number" id="account_number" class="form-control" >
                                                        </div>
                                                    </div>
{{--                                                    <div class="col-md-6">--}}
{{--                                                        <div class="form-group">--}}
{{--                                                            <label for="routing_number">@lang("$string_file.routing_number") </label>--}}
{{--                                                            <input type="text" name="routing_number" id="routing_number" class="form-control" >--}}
{{--                                                        </div>--}}
{{--                                                    </div>--}}
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="mpesa_number">Mpesa @lang("$string_file.number") </label>
                                                            <input type="text" name="mpesa_number" id="mpesa_number" class="form-control" >
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="float-right mt-10">
                                        <div class="float-right mt-10">
                                            <button type="button" class="btn btn-danger float-right mr-2">
                                                <a href="{{route("driver.training.profile.reject", ["id"=>$driver->id])}}" style="color: white" >@lang("$string_file.reject")</a>
                                            </button>
                                        </div>
                                        <div class="float-right mt-10">
                                            <button type="submit" class="btn btn-primary float-right mr-2">@lang("$string_file.approve")</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            @if($tempDocUploaded > 0)
                                <div class="row">
                                    @if($driver->DriverDocument->where('temp_document_file','!=','')->count()>0)
                                        <div class="col-md-12 mt-20">
                                            <h5>@lang("$string_file.temporary_personal_documents")</h5>
                                            <hr>
                                            <div class="table-responsive">
                                                <table class="table table-bordered" id="dataTable">
                                                    <thead>
                                                    <tr class="text-center">
                                                        <th>@lang("$string_file.sn")</th>
                                                        <th>@lang("$string_file.document_name")</th>
                                                        <th>@lang("$string_file.document") </th>
                                                        <th>@lang("$string_file.expire_date")</th>
                                                        <th>@lang("$string_file.status")</th>
                                                        <th>@lang("$string_file.uploaded_at") </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @php $doc_sn = 1 @endphp
                                                    @foreach($driver->DriverDocument as $document)
                                                        @if(!empty($document->temp_document_file))
                                                            <tr>
                                                                <td class="text-center">
                                                                    {{$doc_sn}}
                                                                </td>
                                                                <td class="text-center">
                                                                    {{ $document->Document->DocumentName }}
                                                                </td>
                                                                <td class="text-center">
                                                                    @php $file = get_image($document->temp_document_file,'driver_document'); @endphp
                                                                    <a target="_blank"  href="{{ $file }}"><img src="{{ $file }}" alt="avatar" style="width: 100px;height: 100px;"></a>


                                                                    @php
                                                                        $is_pdf = check_file_extension($document->temp_document_file);
                                                                        $file = get_image($document->temp_document_file,'driver_document');
                                                                    @endphp
                                                                    @if($is_pdf)
                                                                        <embed src="{{ $file }}" width="100px" height="100px" />
                                                                        <br>
                                                                        <a target="_blank" href="{{ $file }}">@lang("$string_file.view")</a>
                                                                    @else
                                                                        <a target="_blank" href="{{ $file }}">
                                                                            <img src="{{ $file }}" alt="avatar" style="width: 100px;height: 100px;">
                                                                        </a>
                                                                    @endif



                                                                </td>
                                                                <!-- <td class="text-center">{!! convertTimeToUSERzone($document->temp_expire_date, $driver->CountryArea->timezone,null,$driver->Merchant, 2) !!}</td> -->
                                                                <td class="text-center">{!! $document->temp_expire_date !!}</td>
                                                                <td class="text-center">
                                                                    @switch($document->temp_doc_verification_status)
                                                                        @case(1)
                                                                            @lang("$string_file.pending_for_verification")
                                                                            @break
                                                                        @case(2)
                                                                            @lang("$string_file.verified")
                                                                            @break
                                                                        @case(3)
                                                                            @lang("$string_file.rejected")
                                                                            @break
                                                                        @case(4)
                                                                            @lang("$string_file.expired")
                                                                            @break
                                                                    @endswitch
                                                                </td>
                                                                <td class="text-center">
                                                                    {!! convertTimeToUSERzone($document->updated_at, $driver->CountryArea->timezone,null,$driver->Merchant) !!}
                                                                </td>
                                                                @php $doc_sn = $doc_sn+1; @endphp
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                    @if($driver->segment_group_id == 2 && !empty($arr_segment))
                                        <div class="col-md-12 mt-20">
                                            <h5>@lang("$string_file.temporary_segment_documents")</h5>
                                            <hr>
                                            <div class="table-responsive">
                                                <table class="table table-bordered" id="dataTable">
                                                    <thead>
                                                    <tr class="text-center">
                                                        <th>@lang("$string_file.sn")</th>
                                                        <th>@lang("$string_file.segment")</th>
                                                        <th>@lang("$string_file.documents")</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @php $doc_sn = 1 ;  $arr_days = get_days($string_file);@endphp
                                                    @foreach($arr_segment as $segment)
                                                        <tr class="text-center">
                                                            <td class="">
                                                                {{$doc_sn}}
                                                            </td>
                                                            <td class="">
                                                                {{ $segment->Name($driver->merchant_id) }}
                                                            </td>
                                                            <td class="">
                                                                @if($segment->DriverSegmentDocument->where('temp_document_file','!=','')->count() > 0)
                                                                    <table class="table table-bordered" id="dataTable">
                                                                        <thead>
                                                                        <tr class="text-center">
                                                                            <th>@lang("$string_file.name")</th>
                                                                            <th>@lang("$string_file.document")</th>
                                                                            <th>@lang("$string_file.expire_date")  </th>
                                                                            <th>@lang("$string_file.status")</th>
                                                                            <th>@lang("$string_file.uploaded_at") </th>
                                                                        </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                        @php $doc_sn = 1 @endphp
                                                                        @foreach($segment->DriverSegmentDocument as $document)
                                                                            @if(!empty($document->temp_document_file))
                                                                                <tr>
                                                                                    <td class="text-center">
                                                                                        {{ $document->Document->DocumentName }}
                                                                                    </td>
                                                                                    <td class="text-center">
                                                                                        {{--@php $p_doc = get_image($document->document_file,'segment_document'); @endphp--}}
                                                                                        {{--<a target="_blank" href="{{ $p_doc }}">--}}
                                                                                        {{--<img src="{{ $p_doc }}" alt="avatar" style="width: 50px;height: 50px;">--}}
                                                                                        {{--</a>--}}

                                                                                        @php
                                                                                            $is_pdf = check_file_extension($document->document_file);
                                                                                            $p_doc = get_image($document->document_file,'driver_document');
                                                                                        @endphp
                                                                                        @if($is_pdf)
                                                                                            <embed src="{{ $p_doc }}" width="100px" height="100px" />
                                                                                            <br>
                                                                                            <a target="_blank" href="{{ $p_doc }}">@lang("$string_file.view")</a>
                                                                                        @else
                                                                                            <a target="_blank" href="{{ $p_doc }}">
                                                                                                <img src="{{ $p_doc }}" alt="avatar" style="width: 100px;height: 100px;">
                                                                                            </a>
                                                                                        @endif


                                                                                    </td>
                                                                                    <!-- <td class="text-center">{!! convertTimeToUSERzone($document->expire_date, $driver->CountryArea->timezone,null,$driver->Merchant, 2) !!}</td> -->
                                                                                    <td class="text-center">{!! $document->expire_date !!}</td>
                                                                                    <td class="text-center">
                                                                                        @switch($document->temp_doc_verification_status)
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
                                                                                    <td class="text-center">
                                                                                        {!! convertTimeToUSERzone($document->updated_at, $driver->CountryArea->timezone,null,$driver->Merchant) !!}
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                @endif
                                                            </td>
                                                            @php $doc_sn = $doc_sn+1; @endphp
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        @if(!empty($vehicle_details))
                                            @if($vehicle_details->DriverVehicleDocument->where('temp_document_file','!=','')->count()>0)
                                                <div class="col-md-12 mt-20">
                                                    <h5>@lang("$string_file.temporary_vehicle_documents")</h5>
                                                    <hr>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered" id="dataTable">
                                                            <thead>
                                                            <tr class="text-center">
                                                                <th>@lang("$string_file.sn")</th>
                                                                <th>@lang("$string_file.document_name")</th>
                                                                <th>@lang("$string_file.document") </th>
                                                                <th>@lang("$string_file.expire_date")</th>
                                                                <th>@lang("$string_file.status")</th>
                                                                <th>@lang("$string_file.uploaded_at") </th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            @php $doc_sr = 1 @endphp
                                                            @foreach($vehicle_details->DriverVehicleDocument as $document)
                                                                @if(!empty($document->temp_document_file))
                                                                    <tr>
                                                                        <td class="text-center">{{$doc_sr}}</td>
                                                                        <td class="text-center">{{ $document->Document->documentname }}</td>
                                                                        <td class="text-center">
                                                                            {{--@php $vehicle_file = get_image($document->temp_document_file,'vehicle_document'); @endphp--}}
                                                                            {{--<a href="{{ $vehicle_file }}" target="_blank"><img src="{{ $vehicle_file }}" style="width:60px;height:60px;border-radius: 10px"></a>--}}



                                                                            @php
                                                                                $is_pdf = check_file_extension($document->document_file);
                                                                                $vehicle_file = get_image($document->document_file,'vehicle_document');
                                                                            @endphp
                                                                            @if($is_pdf)
                                                                                <embed src="{{ $vehicle_file }}" width="100px" height="100px" />
                                                                                <br>
                                                                                <a target="_blank" href="{{ $vehicle_file }}">@lang("$string_file.view")</a>
                                                                            @else
                                                                                <a target="_blank" href="{{ $vehicle_file }}">
                                                                                    <img src="{{ $vehicle_file }}" alt="avatar" style="width: 100px;height: 100px;">
                                                                                </a>
                                                                            @endif



                                                                        </td>
                                                                        <td class="text-center">
                                                                            <!-- {!! convertTimeToUSERzone($document->temp_expire_date, $driver->CountryArea->timezone,null,$driver->Merchant, 2) !!} -->
                                                                            {!! $document->temp_expire_date !!}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @switch($document->temp_doc_verification_status)
                                                                                @case(1)
                                                                                    @lang("$string_file.pending_for_verification")
                                                                                    @break
                                                                                @case(2)
                                                                                    @lang("$string_file.verified")
                                                                                    @break
                                                                                @case(3)
                                                                                    @lang("$string_file.rejected")
                                                                                    @break
                                                                                @case(4)
                                                                                    @lang("$string_file.expired")
                                                                                    @break
                                                                            @endswitch
                                                                        </td>
                                                                        <td class="text-center">
                                                                            {!! convertTimeToUSERzone($document->updated_at, $driver->CountryArea->timezone,null,$driver->Merchant) !!}
                                                                        </td>
                                                                        @php $doc_sr = $doc_sr+1; @endphp
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                                @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
