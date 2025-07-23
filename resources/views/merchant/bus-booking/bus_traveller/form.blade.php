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
                        {!! $bus_traveller['title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$bus_traveller['submit_url'],'class'=>'steps-validation wizard-notification', 'enctype' => "multipart/form-data"]) !!}
                    @php
                        $id = $bus_traveller_id = isset($bus_traveller['data']->id) ? $bus_traveller['data']->id : "";
                    @endphp
                    {!! Form::hidden('bus_traveller_id',$bus_traveller_id,['id'=>'bus_traveller_id','readonly'=>true]) !!}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name">
                                    @lang("$string_file.name") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('name',old('name',isset($bus_traveller['data']->LanguageSingle->title) ? $bus_traveller['data']->LanguageSingle->title : ''),['id'=>'title','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('name'))
                                    <label class="text-danger">{{ $errors->first('name') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tax_method">
                                    @lang("$string_file.tax") @lang("$string_file.method") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('tax_method',["1"=>trans("$string_file.flat"), "2"=>trans("$string_file.percentage")],old('tax_method',isset($bus_traveller['data']->tax_method) ? $bus_traveller['data']->tax_method : ''),['id'=>'tax_method','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('tax_method'))
                                    <label class="text-danger">{{ $errors->first('tax_method') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tax">
                                    @lang("$string_file.tax") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::number('tax',old('tax',isset($bus_traveller['data']->tax) ? $bus_traveller['data']->tax : ''),['id'=>'tax','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('tax'))
                                    <label class="text-danger">{{ $errors->first('tax') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="description">
                                    @lang("$string_file.description") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('description',old('description',isset($bus_traveller['data']->LanguageSingle->description) ? $bus_traveller['data']->LanguageSingle->description : ''),['id'=>'description','class'=>'form-control','placeholder'=>'']) !!}
                                @if ($errors->has('description'))
                                    <label class="text-danger">{{ $errors->first('description') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="icon">
                                    @lang("$string_file.image")
                                    <span class="text-danger">*</span> :
                                    @if(!empty($bus_traveller['data']) && !empty($bus_traveller['data']->image))
                                        <a href="{{get_image($bus_traveller['data']->image,'bus_service')}}"
                                           target="_blank">@lang("$string_file.view") </a>
                                    @endif
                                </label>
                                <input type="file" class="form-control" id="image"
                                       name="image"
                                       placeholder="@lang("$string_file.image")"
                                       onchange="readURL(this)">
                                @if ($errors->has('image'))
                                    <label class="text-danger">{{ $errors->first('image')
                                                            }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-actions float-right">
                        @if($id == NULL || $edit_permission)
                            {!! Form::submit($bus_traveller['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}
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
