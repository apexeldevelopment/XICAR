@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        @if(Auth::user('merchant')->can('create_documents'))
                            <a href="{{ route('bus_booking.add_price_card')}}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-plus" title="@lang(" $string_file.price_card")"></i>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="fa-files-o" aria-hidden="true"></i>
                        @lang("$string_file.bus_booking") @lang("$string_file.price_card")
                    </h3>
                </div>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.title") </th>
                            <th>@lang("$string_file.base_fare") </th>
                            <th>@lang("$string_file.service_area") </th>
                            <th>@lang("$string_file.vehicle_type") </th>
                            <th>@lang("$string_file.bus_routes") </th>
                            <th>@lang("$string_file.start_point") </th>
                            <th>@lang("$string_file.end_point")</th>
                            <th>@lang("$string_file.stop_points")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $price_cards->firstItem() @endphp
                        @foreach($price_cards as $price_card)

                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    Title
                                </td>
                                <td>
                                    {{ $price_card->CountryArea->Country->isoCode}}{{ $price_card->base_fare}}
                                </td>
                                <td>
                                    {{ $price_card->CountryArea->CountryAreaName }}
                                </td>
                                <td>
                                    {{ $price_card->VehicleType->VehicleTypeName }}
                                </td>
                                <td>
                                    {{ $price_card->BusRoute->Name }}
                                </td>
                                <td>
                                    {{ $price_card->BusRoute->StartPoint->Name }}
                                </td>
                                <td>
                                    {{ $price_card->BusRoute->EndPoint->Name }}
                                </td>
                                <td>
                                    {{$price_card->BusRoute->getBusStopList()}}
                                </td>
                                <td>
                                    @if($price_card->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td style="width: 100px;float: left">
                                <!-- @if(Auth::user('merchant')->can('edit_documents')) -->
                                    <a href="{{ route('bus_booking.add_price_card',$price_card->id) }}">
                                        <button type="button"
                                                class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
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
                    @include('merchant.shared.table-footer', ['table_data' => $price_cards, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
