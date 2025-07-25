@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @if(session('typeupdated'))
                <div class="alert dark alert-icon alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <i class="icon wb-info" aria-hidden="true"></i>{{ session('typeupdated') }}
                </div>
            @endif
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('delivery-types.index') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px">
                                    <i class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-edit" aria-hidden="true"></i>
                        @lang('admin.delivery.type.edit')
                    </h3>
                </div>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data" action="{{ route('delivery-types.update' , ['id' => $delivery_type->id]) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang('admin.delivery.type.name')<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control"
                                           id="name"
                                           value="{{$delivery_type->name}}"
                                           name="name">
                                    @if ($errors->first('name'))
                                        <span class="text-danger"> {{ $errors->first('name') }} </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="">@lang('admin.rank')</label> <span class="text-danger">*</span>
                                    <input type="text" class="form-control" name="rank" value="{{$delivery_type->rank}}">
                                    @if ($errors->first('rank'))
                                        <span class="text-danger"> {{ $errors->first('rank') }} </span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("common.description")<span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control"
                                              id="description"
                                              name="description"
                                    >{{$delivery_type->description }}</textarea>
                                    @if ($errors->first('description'))
                                        <span class="text-danger"> {{ $errors->first('description') }} </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions right" style="margin-bottom: 3%">
                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fa fa-check-circle"></i> @lang("common.update")
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


