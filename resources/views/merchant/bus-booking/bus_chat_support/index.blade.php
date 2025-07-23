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
                            <a href="{{ route('bus_booking.chat-support.add') }}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-plus" title="@lang("$string_file.add") @lang("$string_file.bus_chat_support") "></i>
                                </button>
                            </a>
                    </div>
                    <h3 class="panel-title"><i class="fa-files-o" aria-hidden="true"></i>
                        @lang("$string_file.bus_chat_support")
                    </h3>
                </div>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.title") </th>
                            <th>@lang("$string_file.subtitle") </th>
                            <th>@lang("$string_file.type") </th>
                            <th>@lang("$string_file.icon")</th>
                            <th>@lang("$string_file.chat_support")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $bus_chat_support->firstItem() @endphp
                        @foreach($bus_chat_support as $support)

                            <tr>
                                <td>{{ $sr }}</td>

                                <td>
                                <span class="long_text">
                                    {{$support->getNameAttribute()}}
                                </span>
                                </td>
                                <td>
                                <span class="long_text">
                                    {{$support->getSubtitleAttribute()}}
                                </span>
                                </td>

                                <td>
                                <span class="long_text">
                                    {{$support->type}}
                                </span>
                                </td>


                                <td>
                                <span class="long_text">
                                    <img src="{{ get_image($support->icon, "bus_chat_support", $support->merchant_id) }}" height="80" width="80" />
                                </span>
                                </td>

                                <td>
                                <span class="long_text">
                                    {{$support->chat_support}}
                                </span>
                                </td>
                                <td style="width: 100px;float: left">
                                    <a href="{{ route('bus_booking.chat-support.add',$support->id) }}">
                                        <button type="button" class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                    </a>
                                </td>
                                <td style="width: 100px;float: left">
                                    <a href="{{ route('bus_booking.delete-chat-support',$support->id) }}">
                                        <button type="button" class="btn btn-sm btn-danger menu-icon btn_edit action_btn">
                                            <i class="fa fa-remove"></i>
                                        </button>
                                    </a>
                                </td>
                            </tr>
                            @php $sr++ @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $bus_chat_support, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
