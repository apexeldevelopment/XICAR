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
                </div>
                <h3 class="panel-title"><i class="fa-book" aria-hidden="true"></i>
                    @lang("$string_file.applied_jobs")
                </h3>
            </header>
            <div class="panel-body container-fluid">
                <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.user_details")</th>
                            <th>@lang("$string_file.title")</th>
                            <th>@lang("$string_file.resume")</th>
                            <th>@lang("$string_file.additional_notes")</th>
                            <th>@lang("$string_file.created_at")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = $applied_jobs->firstItem() @endphp
                        @foreach($applied_jobs as $applied_job)
                        <tr>
                            <td>{{ $sr }}</td>
                            <td>{{ $applied_job->User->first_name.' '.$applied_job->User->last_name }}
                                <br>
                                {{ $applied_job->User->email}}
                                <br>
                                {{ $applied_job->User->UserPhone}}
                            </td>


                            <td>
                                {{ $applied_job->JobVacancy->LanguageSingle->title }}

                            </td>
                            <td>
                                @if($applied_job->cv)
                             <a href="{{ get_image($applied_job->cv,'user_document',$applied_job->merchant_id) }}" target="_blank" >@lang("$string_file.view")</a>   
                                @endif
                            </td>
                            <td>
                                {{ $applied_job->notes }}

                            </td>

                            <td>{!! $applied_job->created_at !!}</td>

                        </tr>
                        @php $sr++ @endphp
                        @endforeach
                    </tbody>
                </table>
                @include('merchant.shared.table-footer', ['table_data' => $applied_jobs, 'data' => []])
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection