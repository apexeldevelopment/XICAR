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
                    <a href="{{ route('bus_booking.add_route_config') }}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="wb-plus" title="@lang(" $string_file.add_document")"></i>
                        </button>
                    </a>
                    @endif
                </div>
                <h3 class="panel-title"><i class="fa-files-o" aria-hidden="true"></i>
                    @lang("$string_file.route_config_management")
                </h3>
            </div>
            <div class="panel-body container-fluid">
                <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.title") </th>
                            <th>@lang("$string_file.service_area") </th>
                            <!-- <th>@lang("$string_file.vehicle_type") </th> -->
                            <th>@lang("$string_file.bus_routes") </th>
                            <th>@lang("$string_file.start_point") </th>
                            <th>@lang("$string_file.end_point")</th>
                            <!-- <th>@lang("$string_file.stop_points")</th> -->
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = $route_configs->firstItem() @endphp
                        @foreach($route_configs as $route_config)

                        <tr>
                            <td>{{ $sr }}</td>

                            <td><span class="long_text">
                            @if(empty($route_config->LanguageSingle))
                                    <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                    <span class="text-primary">( In {{ $route_config->LanguageAny->LanguageName->name }}
                                        : {{ $route_config->LanguageAny->title }}
                                        )</span>
                                    @else
                                    {{ $route_config->LanguageSingle->title }}
                                    @endif
                                </span>
                            </td>
                            <td><span class="long_text">
                                    {{ $route_config->CountryArea->CountryAreaName }}
                                </span>
                            </td>
                            
                            <td>
                                {{ $route_config->BusRoute->LanguageSingle->title }}
                            </td>
                            <td><span class="long_text">
                                    {{ $route_config->BusRoute->StartPoint->LanguageSingle->name }}
                                </span>
                            </td>
                            <td><span class="long_text">
                                    {{ $route_config->BusRoute->EndPoint->LanguageSingle->name }}

                                </span>
                            </td>

                           
                            <td>
                                @if($route_config->status == 1)
                                <span class="badge badge-success">@lang("$string_file.active")</span>
                                @else
                                <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                @endif
                            </td>
                            <td style="width: 100px;float: left">
                                <!-- @if(Auth::user('merchant')->can('edit_documents')) -->
                                <a href="{{ route('bus_booking.add_route_config',$route_config->id) }}">
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
                @include('merchant.shared.table-footer', ['table_data' => $route_configs, 'data' => []])
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection