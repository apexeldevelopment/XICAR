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
                    <a href="{{ route('bus_booking.add_bus_routes') }}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="wb-plus" title="@lang(" $string_file.add_document")"></i>
                        </button>
                    </a>
                    @endif
                </div>
                <h3 class="panel-title"><i class="fa-files-o" aria-hidden="true"></i>
                    @lang("$string_file.bus_route_management")
                </h3>
            </div>
            <div class="panel-body container-fluid">
                <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.title") </th>
                            <th>@lang("$string_file.segment") / @lang("$string_file.service_type")</th>
                            <th>@lang("$string_file.start_point") </th>
                            <th>@lang("$string_file.end_point")</th>
                            <th>@lang("$string_file.stop_points")</th>
                            <th>@lang("$string_file.configured") / @lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = $bus_routes->firstItem() @endphp
                        @foreach($bus_routes as $bus_route)

                        <tr>
                            <td>{{ $sr }}</td>

                            <td><span class="long_text">
                                    @if(empty($bus_route->LanguageSingle))
                                    <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                    <span class="text-primary">( In {{ $bus_route->LanguageAny->LanguageName->title }}
                                        : {{ $bus_route->LanguageAny->title }}
                                        )</span>
                                    @else
                                    {{ $bus_route->LanguageSingle->title }}
                                    @endif

                                </span>
                            </td>
                            <td>{{$bus_route->Segment->Name($bus_route->merchant_id)." / ".$bus_route->ServiceType->ServiceName($bus_route->merchant_id)}}</td>
                            <td><span class="long_text">
                                    @if(empty($bus_route->LanguageSingle))
                                    <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                    <span class="text-primary">( In {{ $bus_route->LanguageAny->LanguageName->name }}
                                        : {{ $bus_route->StartPoint->LanguageAny->name }}
                                        )</span>
                                    @else
                                    {{ $bus_route->StartPoint->LanguageSingle->name }}
                                    @endif
                                </span>
                            </td>
                            <td><span class="long_text">
                                    @if(empty($bus_route->LanguageSingle))
                                    <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                    <span class="text-primary">( In {{ $bus_route->LanguageAny->LanguageName->name }}
                                        : {{ $bus_route->EndPoint->LanguageAny->name }}
                                        )</span>
                                    @else
                                    {{ $bus_route->EndPoint->LanguageSingle->name }}
                                    @endif
                                </span>
                            </td>

                            <td>
                                {{$bus_route->getBusStopList()}}
                            </td>

                            <td>
                                @if($bus_route->is_configured == 1)
                                    <span class="badge badge-success">@lang("$string_file.yes")</span>
                                @else
                                    <span class="badge badge-danger">@lang("$string_file.no")</span>
                                @endif
                                /
                                @if($bus_route->status == 1)
                                    <span class="badge badge-success">@lang("$string_file.active")</span>
                                @else
                                    <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                @endif
                            </td>
                            <td style="width: 100px;float: left">
                                <a href="{{ route('bus_booking.add_bus_route_config',$bus_route->id) }}">
                                    <button type="button" class="btn btn-sm btn-info menu-icon btn_edit action_btn" title="@lang("$string_file.config")">
                                        <i class="fa fa-sign-out"></i>
                                    </button>
                                </a>
                                <a href="{{ route('bus_booking.add_bus_routes',$bus_route->id) }}">
                                    <button type="button" class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                </a>
                            </td>
                        </tr>
                        @php $sr++ @endphp
                        @endforeach
                    </tbody>
                </table>
                @include('merchant.shared.table-footer', ['table_data' => $bus_routes, 'data' => []])
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
