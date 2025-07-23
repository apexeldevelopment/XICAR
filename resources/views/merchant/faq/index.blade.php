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
                        <a href="{{ route('merchant.faq.create') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-plus" title="@lang(" $string_file.add") @lang(" $string_file.faq")  @lang(" $string_file.type")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa-files-o" aria-hidden="true"></i>
                        @lang("$string_file.faq")
                    </h3>
                </div>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.faq") @lang("$string_file.type")</th>
                            <th>@lang("$string_file.question") </th>
                            <th>@lang("$string_file.status") </th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $faqs->firstItem() @endphp
                        @foreach($faqs as $faq)

                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{$faq->FaqType->Name}}</td>
                                <td>
                                <span class="long_text">
                                    {{$faq->Name}}
                                </span>
                                </td>
                                <td>
                                    @switch($faq->status)
                                        @case(0)
                                            @php $status = 1;@endphp
                                            <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                            @break
                                        @case(1)
                                            @php $status = 0;@endphp
                                            <span class="badge badge-success">@lang("$string_file.active")</span>
                                            @break
                                    @endswitch
                                </td>
                                <td style="width: 100px;float: left">
                                    <a href="{{ route('merchant.faq.create', ['id' => $faq->id]) }}"
                                       data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                       data-placement="top"
                                       class="btn btn-sm btn-warning menu-icon btn_edit action_btn"> <i
                                                class="fa fa-edit"></i> </a>

                                    <a href="{{ route('merchant.faq.change_status',['id' => $faq->id,'status' => $status]) }}" data-original-title="@lang(" $string_file.status")" data-toggle="tooltip" data-placement="top" class="btn btn-sm @if($status == 1) btn-success @else btn-danger @endif menu-icon btn_edit action_btn">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @php $sr++ @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $faqs, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
