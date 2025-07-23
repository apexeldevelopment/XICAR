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
                    </div>
                    <h3 class="panel-title">
                        <i class=" wb-user-plus" aria-hidden="true"></i>
                        @lang("$string_file.website_string")
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})</h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              action="{{ route('merchant.website-string.submit') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="string_data">@lang("$string_file.strings") <span
                                                    class="text-danger">*</span></label>
                                        {!! Form::textarea('string_data',old('string_data', $string_content),['id'=>'string_data','rows' => '16', 'class'=>'form-control','required'=>true]) !!}
                                        @if ($errors->has('string_data'))
                                            <label class="text-danger">{{ $errors->first('string_data') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions right" style="margin-bottom: 3%">
                                Note:- Please don't change/remove '%s' as it is being used in apps and can stop working
                                after alteration.
                                @if(Auth::user('merchant')->can('edit_language_strings'))
                                    <button type="submit" class="btn btn-primary float-right">
                                        <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                                    </button>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
