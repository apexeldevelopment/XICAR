@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->edit_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <div class="btn-group float-right">
                        </div>
                    </div>
                    <h3 class="panel-title">
                        @lang("$string_file.map_markers")
                    </h3>
                </header>
                @php  $id = !empty($map_marker) ? $map_marker->id : NULL @endphp
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data" name="vehicle-type-edit" id="vehicle-type-edit"
                          action="{{route('merchant.add.map.marker', $id)}}">
                        @csrf
                        <fieldset>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.pickup_map_marker")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <span style="color: blue">(@lang("$string_file.size") 60*60 px)</span>
                                        <i class="fa fa-info-circle fa-1" data-toggle="tooltip" data-placement="top" title=""></i>

                                        <div class="row">
                                            @foreach(get_config_image('mapmarkers') as $path)
                                                <br>
                                                <div class="col-md-12">
                                                    <input type="radio" name="pickup_map_marker" value="{{ $path }}"
                                                           id="male-radio-{{ $path }}" @if(!empty($map_marker) && $map_marker['pickup_map_marker'] == $path) checked @endif>                                            &nbsp;
                                                    <label for="male-radio-{{ $path }}">
                                                        <img src="{{ view_config_image($path) }}" style="width:10%; height:10%; margin-right:3%;">{{ explode_image_path($path) }}
                                                    </label>
                                                </div>
                                            @endforeach
                                            @if ($errors->has('pickup_map_marker'))
                                                <label class="text-danger">{{ $errors->first('pickup_map_marker') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.drop_map_marker")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <span style="color: blue">(@lang("$string_file.size") 60*60 px)</span>
                                        <i class="fa fa-info-circle fa-1" data-toggle="tooltip" data-placement="top" title=""></i>

                                        <div class="row">
                                            @foreach(get_config_image('mapmarkers') as $path)
                                                <br>
                                                <div class="col-md-12">
                                                    <input type="radio" name="drop_map_marker" value="{{ $path }}"
                                                           id="male-radio-{{ $path }}" @if(!empty($map_marker) && $map_marker['drop_map_marker'] == $path) checked @endif>                                            &nbsp;
                                                    <label for="male-radio-{{ $path }}">
                                                        <img src="{{ view_config_image($path) }}" style="width:10%; height:10%; margin-right:3%;">{{ explode_image_path($path) }}
                                                    </label>
                                                </div>
                                            @endforeach
                                            @if ($errors->has('pickup_map_marker'))
                                                <label class="text-danger">{{ $errors->first('pickup_map_marker') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.status")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="">@lang("$string_file.select")</option>
                                            <option value="1" @if(!empty($map_marker) && $map_marker['status'] == 1) selected @endif>@lang("$string_file.active")</option>
                                            <option value="2" @if(!empty($map_marker) && $map_marker['status'] == 2) selected @endif>@lang("$string_file.inactive")</option>
                                        </select>
                                        @if ($errors->has('status'))
                                            <label class="text-danger">{{ $errors->first('status') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            @if($id == NULL)
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.add")
                                </button>
                            @else
                                <button type="submit" class="btn btn-primary">
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