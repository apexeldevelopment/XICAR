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
                    <a href="{{ route('bus_booking.add_bus_stops') }}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="wb-plus" title="@lang(" $string_file.add_document")"></i>
                        </button>
                    </a>
                    @endif
                </div>
                <h3 class="panel-title"><i class="fa-files-o" aria-hidden="true"></i>
                    @lang("$string_file.bus_stop_management")
                </h3>
            </div>
            <div class="panel-body container-fluid">
                <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.segment") / @lang("$string_file.service_type")</th>
                            <th>@lang("$string_file.name") </th>
                            <th>@lang("$string_file.address") </th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = $bus_stops->firstItem() @endphp
                        @foreach($bus_stops as $bus_stop)
                        <tr>
                            <td>{{ $sr }}</td>

                            <td>{{$bus_stop->Segment->Name($bus_stop->merchant_id)." / ".$bus_stop->ServiceType->ServiceName($bus_stop->merchant_id)}}</td>

                            <td><span class="long_text">
                                    @if(empty($bus_stop->LanguageSingle))
                                    <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                    <span class="text-primary">( In {{ $bus_stop->LanguageAny->LanguageName->name }}
                                        : {{ $bus_stop->LanguageAny->name }}
                                        )</span>
                                    @else
                                    {{ $bus_stop->LanguageSingle->name }}
                                    @endif

                                </span>
                            </td>
                            <td>{{ $bus_stop->address }}</td>
                            <td>
                                @if($bus_stop->status == 1)
                                <span class="badge badge-success">@lang("$string_file.active")</span>
                                @else
                                <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                @endif
                            </td>
                            <td style="width: 100px;float: left">
                                <!-- @if(Auth::user('merchant')->can('edit_documents')) -->
                                <a href="{{ route('bus_booking.edit_bus_stops',$bus_stop->id) }}">
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
                @include('merchant.shared.table-footer', ['table_data' => $bus_stops, 'data' => []])
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
