@extends('merchant.layouts.main')
@section('content')
    <style>
        .impo-text {
            color: red;
            font-size: 15px;
            text-wrap: normal;
            display: none;
        }
    </style>
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('bus_booking.services') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                            @if(!empty($info_setting) && $info_setting->add_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin-left:10px"
                                        data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-add-file" aria-hidden="true"></i>
                        {!! $bus_service['title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$bus_service['submit_url'],'class'=>'steps-validation wizard-notification', 'enctype' => "multipart/form-data"]) !!}
                    @php
                        $id = $bus_service_id = isset($bus_service['data']->id) ? $bus_service['data']->id : "";
                    @endphp
                    {!! Form::hidden('bus_service_id',$bus_service_id,['id'=>'bus_service_id','readonly'=>true]) !!}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.name") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('title',old('title',isset($bus_service['data']->LanguageSingle->title) ? $bus_service['data']->LanguageSingle->title : ''),['id'=>'title','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('title'))
                                    <label class="text-danger">{{ $errors->first('title') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.sequence") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::number('sequence',old('sequence',isset($bus_service['data']->sequence) ? $bus_service['data']->sequence : ''),['id'=>'sequence','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('sequence'))
                                    <label class="text-danger">{{ $errors->first('sequence') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.is_general_info") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('is_general_info',$bus_service['status'],old('is_general_info',isset($bus_service['data']->is_general_info) ? $bus_service['data']->is_general_info : ''),['id'=>'is_general_info','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('is_general_info'))
                                    <label class="text-danger">{{ $errors->first('is_general_info') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.description") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('description',old('description',isset($bus_service['data']->LanguageSingle->description) ? $bus_service['data']->LanguageSingle->description : ''),['id'=>'description','class'=>'form-control','placeholder'=>'']) !!}
                                @if ($errors->has('description'))
                                    <label class="text-danger">{{ $errors->first('description') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="icon">
                                    @lang("$string_file.icon")
                                    <span class="text-danger">*</span> :
                                    @if(!empty($bus_service['data']) && !empty($bus_service['data']->icon))
                                        <a href="{{get_image($bus_service['data']->icon,'bus_service')}}"
                                           target="_blank">@lang("$string_file.view") </a>
                                    @endif
                                </label>
                                <input type="file" class="form-control" id="icon"
                                       name="icon"
                                       placeholder="@lang("$string_file.icon")"
                                       onchange="readURL(this)">
                                @if ($errors->has('icon'))
                                    <label class="text-danger">{{ $errors->first('icon')
                                                            }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-actions float-right">
                        @if($id == NULL || $edit_permission)
                            {!! Form::submit($bus_service['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}
                        @else
                            <span style="color: red"
                                  class="float-right">@lang("$string_file.demo_warning_message")</span>
                        @endif
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
