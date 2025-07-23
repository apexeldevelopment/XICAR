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
                            <a href="{{ route('merchant.faq') }}">
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
                        {!! $faq['title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$faq['submit_url'],'class'=>'steps-validation wizard-notification', 'enctype' => "multipart/form-data"]) !!}
                    @php
                        $id = $faq_type_id = isset($faq['data']->id) ? $faq['data']->id : "";
                    @endphp
                    {!! Form::hidden('faq_type_id',$faq_type_id,['id'=>'faq_type_id','readonly'=>true]) !!}
                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.application")<span
                                            class="text-danger">*</span>
                                </label>
                                <select class="form-control" name="application"
                                        id="application" required>
                                    <option value="2">@lang("$string_file.driver")</option>
                                    <option value="1">@lang("$string_file.user")</option>
                                </select>
                                @if ($errors->has('application'))
                                    <label class="text-danger">{{ $errors->first('application') }}</label>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.faq") @lang("$string_file.type")<span
                                            class="text-danger">*</span>
                                </label>
                                <select class="form-control" name="faq_type"
                                        id="faq_type" required>
                                    @foreach($faq['faq_types'] as $faq_type)
                                        <option value="{{$faq_type->id}}">{{$faq_type->LanguageSingle->title}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('faq_type'))
                                    <label class="text-danger">{{ $errors->first('faq_type') }}</label>
                                @endif
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.question") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::textarea('question',old('question',isset($faq['data']->LanguageSingle->question) ? $faq['data']->LanguageSingle->question : ''),['id'=>'title','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('question'))
                                    <label class="text-danger">{{ $errors->first('question') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-12" id="description_div">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.answer")<span
                                            class="text-danger">*</span>
                                </label>
                                <textarea id="description" class="form-control"
                                          name="answer" rows="5"
                                          placeholder="@lang("$string_file.answer")" data-plugin="summernote">
                                        {{isset($faq['data']->LanguageSingle->answer) ? $faq['data']->LanguageSingle->answer : '';}}</textarea>
                                @if ($errors->has('description'))
                                    <label class="text-danger">{{ $errors->first('answer') }}</label>
                                @endif
                            </div>
                        </div>

                    </div>
                    <div class="form-actions float-right">

                            {!! Form::submit($faq['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}

                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
