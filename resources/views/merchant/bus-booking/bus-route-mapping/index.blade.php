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
                    <a href="{{ route('bus_booking.bus_route_mapping.create') }}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="wb-plus" title="@lang(" $string_file.bus_route_mapping")"></i>
                        </button>
                    </a>
                    @endif
                </div>
                <h3 class="panel-title"><i class="fa-files-o" aria-hidden="true"></i>
                    @lang("$string_file.bus_route_mapping")
                </h3>
            </div>
            <div class="panel-body container-fluid">
                <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.bus_routes") </th>
                            <th>@lang("$string_file.bus_details") </th>
                            <th>@lang("$string_file.days") </th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = $bus_route_mapping->firstItem() @endphp
                        @foreach($bus_route_mapping as $bus_mapping)

                        <tr>
                            <td>{{ $sr }}</td>

                            <td><span class="long_text">
                            @if(empty($bus_mapping->BusRoute->LanguageSingle))
                                    <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                    <span class="text-primary">( In {{ $bus_mapping->BusRoute->LanguageAny->LanguageName->name }}
                                        : {{ $bus_mapping->BusRoute->LanguageAny->title }}
                                        )</span>
                                    @else
                                    {{ $bus_mapping->BusRoute->LanguageSingle->title }}
                                    @endif
                                </span>
                            </td>
                           
                            <td><span class="long_text">
                                    {{ $bus_mapping->Bus->vehicle_number . ' | ' . $bus_mapping->Bus->vehicle_color . ' | ' . $bus_mapping->Bus->VehicleType->VehicleTypeName }}
                                </span>
                            </td>

                            <td><span class="long_text">
                                    Sunday-Saturday
                                </span>
                            </td>

                           
                            <td>
                                @if($bus_mapping->status == 1)
                                <span class="badge badge-success">@lang("$string_file.active")</span>
                                @else
                                <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                @endif
                            </td>
                            <td style="width: 100px;float: left">
                                <!-- @if(Auth::user('merchant')->can('edit_documents')) -->
                                <a href="{{ route('bus_booking.bus_route_mapping.create',[$bus_mapping->bus_route_id,$bus_mapping->bus_id]) }}">
                                    <button type="button" class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @php $sr++ @endphp
                        @endforeach
                    </tbody>
                </table>
                @include('merchant.shared.table-footer', ['table_data' => $bus_route_mapping, 'data' => []])
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection