@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('merchant.events') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px"><i
                                            class="wb-reply"></i>
                                </button>
                            </a>
                            @if(!empty($info_setting) && $info_setting->add_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                        data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title">
                        <i class=" fa fa-building" aria-hidden="true"></i>
                        {!! $data['title'] !!}
                    </h3>
                </div>
                @php $id = isset($data['event']['id']) ? $data['event']['id'] : NULL; @endphp
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'event','id'=>'event-form','files'=>true,'url'=>$data['save_url'],'method'=>'POST'] ) !!}
                    {!! $data['segment_html'] !!}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name">@lang("$string_file.service_area")<span
                                            class="text-danger">*</span>
                                </label>
                                {!! Form::select('country_area_id',add_blank_option($data['arr_areas'],trans("$string_file.select")),old('country_area_id',isset($data['price_card']['country_area_id']) ? $data['price_card']['country_area_id'] :NULL),['class'=>'form-control','required'=>true,'id'=>'country_area_id','onChange'=>"getSegment()"]) !!}
                                @if ($errors->has('country_area_id'))
                                    <span class="help-block">
                                                    <strong>{{ $errors->first('country_area_id') }}</strong>
                                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="banner_name">
                                    @lang("$string_file.name")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('event_name',old('event_name',isset( $data['event']['id']) ? $data['event']->Name($data['event']['merchant_id']) : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('event_name'))
                                    <label class="text-danger">{{ $errors->first('banner_name') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">
                                    @lang("$string_file.status")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('status',$data['arr_status'],old('status',isset($data['event']['status']) ? $data['event']['status'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('status'))
                                    <label class="text-danger">{{ $errors->first('status') }}</label>
                                @endif
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="sequence">
                                    @lang("$string_file.sequence")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::number('sequence',old('sequence',isset($data['event']['sequence']) ? $data['event']['sequence'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('sequence'))
                                    <label class="text-danger">{{ $errors->first('sequence') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="sequence">
                                    @lang("$string_file.event_link")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('event_link',old('event_link',isset($data['event']['event_link']) ? $data['event']['event_link'] : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('event_link'))
                                    <label class="text-danger">{{ $errors->first('event_link') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group  ">
                                <label for="message26">
                                    @lang("$string_file.image") (W:{{ Config('custom.image_size.category.width')  }} *
                                    H:{{ Config('custom.image_size.category.height')  }})
                                </label>
                                @if(!empty($data['event']['event_image']))
                                    <a href="{{ get_image($data['event']['event_image'],'event',$data['event']['merchant_id']) }}"
                                       target="_blank">@lang("$string_file.view")</a>
                                @endif
                                {!! Form::file('event_image',['id'=>'event_image','class'=>'form-control']) !!}
                                @if ($errors->has('event_image'))
                                    <label class="text-danger">{{ $errors->first('event_image') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="form-actions d-flex flex-row-reverse p-2">
                        @if($id == NULL || $edit_permission)
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i>@lang("$string_file.save")
                            </button>
                        @else
                            <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                        @endif
                    </div>
                    {!!  Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection

