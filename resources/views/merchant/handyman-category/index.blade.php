@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
           @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{route("segment.handyman-category.add")}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px"
                                data-toggle="modal" data-target="#inlineForm">
                            <i class="wb-plus" title="@lang("$string_file.category")"></i>
                        </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class=" wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.handyman") @lang("$string_file.categories")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.category")</th>
                            <th>@lang("$string_file.segment") </th>
                            <th>@lang("$string_file.description")</th>
                            <th>@lang("$string_file.icon")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $handyman_categories->firstItem() @endphp
                        @foreach($handyman_categories as $handyman_category)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>@if(empty($handyman_category->LanguageSingle))
                                        <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                        <span class="text-primary">( In {{ $handyman_category->LanguageAny->LanguageName->category }}
                                                                : {{ $handyman_category->LanguageAny->category }}
                                                                )</span>
                                    @else
                                        {{ $handyman_category->LanguageSingle->category }}
                                    @endif
                                </td>
                                <td>{{ array_key_exists($handyman_category->segment_id,$merchant_segments) ? $merchant_segments[$handyman_category->segment_id] : '--'}}</td>
                                <td>@if(empty($handyman_category->LanguageSingle))
                                        <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                        <span class="text-primary">( In {{ $handyman_category->LanguageAny->LanguageName->description }}
                                                                : {{ $handyman_category->LanguageAny->category }}
                                                                )</span>
                                    @else
                                        {{ $handyman_category->LanguageSingle->description }}
                                    @endif
                                </td>
                                <td>
                                    <img src="{{ get_image($handyman_category->icon,'category',$handyman_category->merchant_id)}}" width="80px" height="80px">
                                </td>
                                <td>
                                    @if($handyman_category->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>

                                <td style="width:100px;float:left">
                                    <a href="{{ route('segment.handyman-category.add',$handyman_category->id) }}"
                                       data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                       data-placement="top"
                                       class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                        <i class="fa fa-edit"></i> </a>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $handyman_categories, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
