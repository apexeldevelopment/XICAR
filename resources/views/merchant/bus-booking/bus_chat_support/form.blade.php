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
                            <a href="{{ route('bus_booking.traveller') }}">
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
                        {!! $bus_chat_support['page_title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$bus_chat_support['submit_url'],'class'=>'steps-validation wizard-notification', 'enctype' => "multipart/form-data"]) !!}
                    @php
                        $id = $bus_chat_support_id = isset($bus_chat_support['data']->id) ? $bus_chat_support['data']->id : "";
                    @endphp
                    {!! Form::hidden('bus_chat_support_id',!empty($bus_chat_support['data'])? $bus_chat_support['data']->id: "",['id'=>'bus_chat_support_id','readonly'=>true]) !!}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.title") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('title',old('title',isset($bus_chat_support['data']->LanguageSingle->title) ? $bus_chat_support['data']->LanguageSingle->title : ''),['id'=>'name','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('title'))
                                    <label class="text-danger">{{ $errors->first('title') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="title">
                                    @lang("$string_file.subtitle") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('subtitle',old('subtitle',isset($bus_chat_support['data']->LanguageSingle->subtitle) ? $bus_chat_support['data']->LanguageSingle->subtitle : ''),['id'=>'name','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('subtitle'))
                                    <label class="text-danger">{{ $errors->first('subtitle') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tax_method">
                                    @lang("$string_file.type") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('type',config::get("custom.bus_chat_support_types"),old('tax_method',isset($bus_chat_support['data']->type) ? $bus_chat_support['data']->type : ''),['id'=>'type','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('type'))
                                    <label class="text-danger">{{ $errors->first('type') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tax">
                                    @lang("$string_file.chat_support") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('chat_support',old('chat_support',isset($bus_chat_support['data']->chat_support) ? $bus_chat_support['data']->chat_support : ''),['id'=>'chat_support','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('tax'))
                                    <label class="text-danger">{{ $errors->first('chat_support') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="icon">
                                    @lang("$string_file.image")
                                    <span class="text-danger">*</span> :
                                    @if(!empty($bus_chat_support['data']) && !empty($bus_chat_support['data']->icon))
                                        <a href="{{get_image($bus_chat_support['data']->icon,'bus_chat_support')}}"
                                           target="_blank">@lang("$string_file.view") </a>
                                    @endif
                                </label>
                                <input type="file" class="form-control" id="icon"
                                       name="icon"
                                       placeholder="@lang("$string_file.icon")">
                                @if ($errors->has('image'))
                                    <label class="text-danger">{{ $errors->first('icon')}}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-actions float-right">
                        @if($id == NULL || $edit_permission)
                            {!! Form::submit($bus_chat_support['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}
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
