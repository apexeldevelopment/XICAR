@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('merchant.delivery-package.add')}}">
                            <button type="button" title="@lang("$string_file.add_delivery_package")"
                                    class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-plus"></i>
                            </button>
                        </a>

                        @if(!empty($info_setting) && $info_setting->view_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                        data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                    </div>
                    <h3 class="panel-title">
                        <i class="fa fa-building" aria-hidden="true"></i>@lang("$string_file.delivery_package")</h3>
                </header>
                <div class="panel-body">
                    <table class="display nowrap table table-hover table-striped w-full" id="customDataTable"
                           style="width:100%"
                           cellspacing="0">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.vehicle")</th>
                            <th>@lang("$string_file.service_area")</th>
                            <th>@lang("$string_file.package_image")</th>
                            <th>@lang("$string_file.package_name")</th>
                            <th>@lang("$string_file.weight")</th>
                            <th>@lang("$string_file.volumetric_capacity")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $sr = 1;
                        @endphp
                        @foreach($packages as $package)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{ $package->VehicleType->vehicle_type_name }}</td>
                                <td>{{ $package->CountryArea->CountryAreaName }}</td>
                                <td><img src="{{get_image($package->package_image, 'vehicle_delivery_package_image')}}" style="width:50%; height:50%; "></td>
                                <td>
                                    {{ $package->package_name}}
                                </td>
                                <td>
                                    {{ $package->weight}}

                                </td>
                                <td>{{ $package->volumetric_capacity}}
                                </td>

                                <td>
                                    <a href="{!! route('merchant.delivery-package.add',$package->id) !!}"
                                       class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                        <i class="wb-edit"></i>
                                    </a>

                                    @csrf
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
