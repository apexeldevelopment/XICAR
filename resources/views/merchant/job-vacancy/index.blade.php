@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content container-fluid">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <div class="panel-actions">
                    @if(!empty($info_setting) && $info_setting->view_text != "")
                    <button class="btn btn-icon btn-primary float-right" style="margin:10px" data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                        <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                    </button>
                    @endif
                   
                    <a href="{{route('merchant.jobs.add')}}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin: 10px;">
                            <i class="wb-plus" title="@lang(" $string_file.promo_code")"></i>
                        </button>
                    </a>
                </div>
                <h3 class="panel-title"><i class="fa-book" aria-hidden="true"></i>
                    @lang("$string_file.job_management")
                </h3>
            </header>
            <div class="panel-body container-fluid">
                <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.title")</th>
                            <!-- <th>@lang("$string_file.promo_code")</th>
                            <th>@lang("$string_file.service_area")</th>
                            <th>@lang("$string_file.segment")</th>
                            <th>@lang("$string_file.promo_code_parameter") </th> -->

                            <th>@lang("$string_file.description")</th>
                            <th>@lang("$string_file.organization")</th>
                            <!-- <th>@lang("$string_file.discount")</th>
                            <th>@lang("$string_file.validity")</th> -->
                            <th>@lang("$string_file.start_date")</th>
                            <th>@lang("$string_file.end_date")</th>
                            <!-- <th>@lang("$string_file.limit")</th>
                            <th>@lang("$string_file.limit_per_user")</th>
                            <th>@lang("$string_file.applicable_for")</th> -->
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.created_at")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = $job_vacancies->firstItem() @endphp
                        @foreach($job_vacancies as $job_vacancy)
                        <tr>
                            <td>{{ $sr }}</td>


                            <td>@if(!empty($job_vacancy->LanguageSingle))
                                {{ $job_vacancy->LanguageSingle->title }}
                                @elseif(!empty($job_vacancy->LanguageAny ))
                                <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                <span class="text-primary">( In {{ $job_vacancy->LanguageAny->LanguageName->name }}
                                    : {{ $job_vacancy->LanguageAny->title }}
                                    )</span>
                                @else
                                <span class="text-primary">------</span>
                                @endif
                            </td>
                            <td>@if(!empty($job_vacancy->LanguageSingle))
                                {{ $job_vacancy->LanguageSingle->description }}
                                @elseif(!empty($job_vacancy->LanguageAny ))
                                <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                <span class="text-primary">( In {{ $job_vacancy->LanguageAny->LanguageName->description }}
                                    : {{ $job_vacancy->LanguageAny->description }}
                                    )</span>
                                @else
                                <span class="text-primary">------</span>
                                @endif
                            </td>
                            <td>@if(!empty($job_vacancy->LanguageSingle))
                                {{ $job_vacancy->LanguageSingle->organization }}
                                @elseif(!empty($job_vacancy->LanguageAny ))
                                <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                <span class="text-primary">( In {{ $job_vacancy->LanguageAny->LanguageName->organization }}
                                    : {{ $job_vacancy->LanguageAny->organization }}
                                    )</span>
                                @else
                                <span class="text-primary">------</span>
                                @endif
                            </td>


                            <td>
                                @if($job_vacancy->start_date)
                                {{ $job_vacancy->start_date }}
                                @else
                                -----
                                @endif
                            </td>
                            <td>
                                @if($job_vacancy->end_date)
                                {{ $job_vacancy->end_date }}
                                @else
                                -----
                                @endif
                            </td>
                           
                            <td>
                                @if($job_vacancy->status == 1)
                                <span class="badge badge-success">@lang("$string_file.active")</span>
                                @else
                                <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                @endif
                            </td>
                            
                            <td>{!! $job_vacancy->created_at !!}</td>
                            <td style="width:200px">
                                <a href="{{ route('merchant.jobs.add',$job_vacancy->id) }}" data-original-title="@lang(" $string_file.edit")" data-toggle="tooltip" data-placement="top" class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                    <i class="fa fa-edit"></i> </a>
                               
                                @if($delete_permission)
                                <a href="{{ route('merchant.jobs.delete',$job_vacancy->id) }}" data-original-title="@lang(" $string_file.delete")" data-toggle="tooltip" data-placement="top" class="btn btn-sm btn-danger menu-icon btn_delete action_btn">
                                    <i class="fa fa-trash"></i> </a>
                                @endif
                            </td>
                        </tr>
                        @php $sr++ @endphp
                        @endforeach
                    </tbody>
                </table>
                @include('merchant.shared.table-footer', ['table_data' => $job_vacancies, 'data' => []])
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection