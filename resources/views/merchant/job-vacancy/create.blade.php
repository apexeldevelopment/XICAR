@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <div class="panel-actions">
                    @if(!empty($info_setting) && $info_setting->add_text != "")
                    <button class="btn btn-icon btn-primary float-right" style="margin:10px" data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                        <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                    </button>
                    @endif
                    <div class="btn-group float-right" style="margin:10px">
                        <a href="{{ route('merchant.jobs.index') }}">
                            <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                </div>
                <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                    @lang("$string_file.add_job")
                </h3>
            </header>
            @php $id = isset($job_vacancy->id) ? $job_vacancy->id : NULL;
            $arr_status = [1=>trans("$string_file.active"),2=>trans("$string_file.inactive")]
            @endphp
            <div class="panel-body container-fluid">
                <section id="validation">
                    <form method="POST" class="steps-validation wizard-notification" enctype="multipart/form-data" action="{{ route('merchant.jobs.save',$id) }}">
                        @csrf
                        {!! Form::hidden('id',$id) !!}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.title")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="title" name="title" placeholder="" value="{{ old('title',isset($job_vacancy->LanguageSingle->title) ? $job_vacancy->LanguageSingle->title : NULL) }}" required>
                                    @if ($errors->has('title'))
                                    <label class="text-danger">{{ $errors->first('title') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.organization")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="organization" name="organization" placeholder="" value="{{ old('organization',isset($job_vacancy->LanguageSingle->organization) ? $job_vacancy->LanguageSingle->organization : NULL) }}" required>
                                    @if ($errors->has('organization'))
                                    <label class="text-danger">{{ $errors->first('organization') }}</label>
                                    @endif
                                </div>
                            </div>
                            <!-- </div>
                            <div class="row"> -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.description")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="description" name="description" placeholder="" required>{{ old('description',isset($job_vacancy->LanguageSingle->description) ? $job_vacancy->LanguageSingle->description : "") }}</textarea>
                                    @if ($errors->has('description'))
                                    <label class="text-danger">{{ $errors->first('description') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.start_date")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control customDatePicker1" name="start_date" placeholder="" value="{{ old('start_date', isset($job_vacancy->start_date) ? $job_vacancy->start_date : NULL) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group" id="">
                                    <label for="emailAddress5">
                                        @lang("$string_file.end_date")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control customDatePicker1" name="end_date" placeholder="" value="{{ old('end_date', isset($job_vacancy->end_date) ? $job_vacancy->end_date : NULL) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.status")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('status',$arr_status,old('status',isset($arr_status['status']) ? $arr_status['status'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('status'))
                                    <label class="text-danger">{{ $errors->first('status') }}</label>
                                @endif
                            </div>
                        </div>
                        </div>

                        <div class="form-actions right" style="margin-bottom: 3%">
                            @if($id == NULL || $edit_permission)
                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fa fa-check-circle" onclick="return Validate()"></i>
                                @lang("$string_file.save")
                            </button>
                            @endif
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>
@include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
<script>
</script>
@endsection