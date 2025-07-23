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
                            <a href="{{ route('merchant.faq_types') }}">
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
                        {!! $faq_types['title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$faq_types['submit_url'],'class'=>'steps-validation wizard-notification', 'enctype' => "multipart/form-data"]) !!}
                    @php
                        $id = $faq_type_id = isset($faq_types['data']->id) ? $faq_types['data']->id : "";
                    @endphp
                    {!! Form::hidden('faq_type_id',$faq_type_id,['id'=>'faq_type_id','readonly'=>true]) !!}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.name") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('title',old('title',isset($faq_types['data']->LanguageSingle->title) ? $faq_types['data']->LanguageSingle->title : ''),['id'=>'title','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('title'))
                                    <label class="text-danger">{{ $errors->first('title') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-actions float-right">

                            {!! Form::submit($faq_types['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}

                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
