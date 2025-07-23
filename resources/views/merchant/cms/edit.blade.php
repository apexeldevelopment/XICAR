@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->edit_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{ route('cms.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-edit" aria-hidden="true"></i>
                        @lang("$string_file.cms_page")
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" id="cms-form" name="cms-form"
                          enctype="multipart/form-data"
                          action="{{ route('cms.update',$cmspage->id) }}">
                        {{method_field('PUT')}}
                        <input type="hidden" id="id" name="id" value="{{$cmspage->id}}">
                        @csrf
                        <fieldset>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lastName3">
                                            @lang("$string_file.page_title")<span
                                                    class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="title"
                                               name="title"
                                               value="@if($cmspage->LanguageSingle){{ $cmspage->LanguageSingle->title }} @else {{$cmspage->LanguageAny->title}} @endif"
                                               placeholder="@lang("$string_file.page_title")"
                                               required>
                                        @if ($errors->has('title'))
                                            <label class="text-danger">{{ $errors->first('title') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="content_type">
                                            @lang("$string_file.type")<span
                                                    class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" name="content_type"
                                                id="content_type" required>
                                            <option value="1" @if($cmspage->content_type == 1) selected @endif>@lang("$string_file.content")</option>
                                            <option value="2" @if($cmspage->content_type == 2) selected @endif>@lang("$string_file.url")</option>
                                        </select>
                                        @if ($errors->has('content_type'))
                                            <label class="text-danger">{{ $errors->first('content_type') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12" id="description_div" @if($cmspage->content_type == 2) style="display: none;" @endif>
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.description")<span
                                                    class="text-danger">*</span>
                                        </label>
                                        <textarea id="summernote" class="form-control" name="description" rows="5" placeholder="@lang("$string_file.description")" data-plugin="summernote">@if($cmspage->content_type == 1) @if($cmspage->LanguageSingle){{ $cmspage->LanguageSingle->description }} @else {{$cmspage->LanguageAny->description}} @endif @endif </textarea>
                                        @if ($errors->has('description'))
                                            <label class="text-danger">{{ $errors->first('description') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3" id="url_div" @if($cmspage->content_type == 1) style="display: none;" @endif>
                                    <div class="form-group">
                                        <label for="url">
                                            @lang("$string_file.url")<span
                                                    class="text-danger">*</span>
                                        </label>
                                        <input type="url" id="url" class="form-control" @if($cmspage->content_type == 2) value="@if($cmspage->LanguageSingle){{ $cmspage->LanguageSingle->description }}
                                        @else {{$cmspage->LanguageAny->description}}
                                        @endif" @endif name="url" placeholder="@lang("$string_file.url")" />
                                        @if ($errors->has('url'))
                                            <label class="text-danger">{{ $errors->first('url') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <div class="form-actions right" style="margin-bottom: 3%">
                            @if($edit_permission)
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.update")
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'edit_text'])
@endsection
@section('js')
    <script>
        $(document).on('change', '#content_type', function () {
            if ($(this).val() == 1) {
                $('#url_div').hide();
                $('#description_div').show();
            } else {
                $('#url_div').show();
                $('#description_div').hide();
            }
        });
    </script>
@endsection
