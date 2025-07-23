@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('merchant.serviceType.index') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px">
                                    <i class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-edit" aria-hidden="true"></i>
                        @lang("$string_file.add") @lang("$string_file.handyman") @lang("$string_file.segment")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" enctype="multipart/form-data" action="{{ route('merchant.segment.save') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.name")<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="segment" name="segment" value="" placeholder="" required>
                                    @if ($errors->has('segment'))
                                        <label class="danger">{{ $errors->first('segment') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sequence">
                                        @lang("$string_file.sequence")<span class="text-danger">*</span>
                                    </label>
                                    {!! Form::number('sequence',old('sequence'),['class'=>'form-control','required'=>true,'id'=>'sequence','min'=>0,'max'=>100]) !!}
                                    @if ($errors->has('sequence'))
                                        <label class="danger">{{ $errors->first('sequence') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sequence">
                                        @lang("$string_file.is_coming_soon")<span class="text-danger">*</span>
                                    </label>

                                    {!! Form::select('is_coming_soon',[2=>trans("$string_file.no"),1 =>trans("$string_file.yes")],old('is_coming_soon',2),['class'=>'form-control']) !!}
                                    @if ($errors->has('is_coming_soon'))
                                        <label class="danger">{{ $errors->first('is_coming_soon') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="profile_image">
                                        @lang("$string_file.icon")<span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="icon" name="icon" placeholder="">
                                </div>
                            </div>
                            @if(isset(Auth::user('merchant')->ApplicationConfiguration) && isset(Auth::user('merchant')->ApplicationConfiguration->dynamic_url_configuration) && Auth::user('merchant')->ApplicationConfiguration->dynamic_url_configuration == 1)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="dynamic_url">
                                            @lang("$string_file.dynamic_url")<span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="dynamic_url" name="dynamic_url" placeholder="">
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="form-actions float-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-square-o"></i> @lang("$string_file.save")
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
