@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('merchant.delivery_package') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px"><i
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
                    <h3 class="panel-title">
                         @lang("$string_file.add_delivery_package")</h3>
                </header>
                @php $id = isset($package) && !empty($package) ? $package->id : NULL; @endphp
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data" name="delivery-package-form" id="delivery-package-form"
                          action="{{route('merchant.delivery-package.save',$id)}}">
                        @csrf
                        <fieldset>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.service_area")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" name="area" id="area">
                                            <option value="">---Choose Service Area---</option>
                                            @foreach ($areas as $area)
                                                <option value="{{$area->id}}" {{ ($package && $package->country_area_id == $area->id) ? 'selected' : '' }}>{{$area->CountryAreaName}}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('vehicle_type'))
                                            <label class="text-danger">{{ $errors->first('vehicle_type') }}</label>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.vehicle_type")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" name="vehicle_type" id="vehicle_type">
                                            <option value="">---Choose Vehicle---</option>
                                            @foreach ($vehicleType as $key=> $vehicle)
                                                <option value="{{$key}}" {{ ($package && $package->vehicle_type_id == $key) ? 'selected' : '' }}>{{$vehicle}}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('vehicle_type'))
                                            <label class="text-danger">{{ $errors->first('vehicle_type') }}</label>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.package_name")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="package_name"
                                               name="package_name"
                                               placeholder=""
                                               value="{{ isset($package->package_name) ? $package->package_name : '' }}"
                                               required>
                                        @if ($errors->has('package_name'))
                                            <label class="text-danger">{{ $errors->first('package_name') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.dead_weight")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="dead_weight"
                                               name="dead_weight"
                                               placeholder=""
                                               value="@if(!empty($package)) {{ $package->weight }} @endif"
                                               required>
                                        @if ($errors->has('dead_weight'))
                                            <label class="text-danger">{{ $errors->first('dead_weight') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.image")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="file" class="form-control" id="image"
                                               name="image"
                                               placeholder=""
                                               @if(empty($package->package_image)) required @endif>
                                        @if ($errors->has('image'))
                                            <label class="text-danger">{{ $errors->first('image') }}</label>
                                        @endif
                                    </div>
                                </div>
                                @if(isset($package))
                                <div class="col-md-3">
                                    <img src="{{ get_image($package->package_image, 'vehicle_delivery_package_image')  }}" style="width:50%; height:50%; ">
                                </div>
                                @endif
                                <div class="col-sm-12 row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.engine_type")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" name="engine_type" id="engine_type">
                                                <option value="">---Choose Engine Type---</option>
                                                <option value="1" {{isset($package) && $package->engine_type == 1 ? 'selected' : ''}}>Engine Based</option>
                                                <option value="2" {{isset($package) && $package->engine_type == 2 ? 'selected' : ''}}>Non Engine Based</option>
                                            </select>
                                            @if ($errors->has('engine_type'))
                                                <label class="text-danger">{{ $errors->first('engine_type') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 row volume">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.length")(in m)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="package_length"
                                                   name="package_length"
                                                   placeholder=""
                                                   value="@if(!empty($package)) {{ $package->package_length }} @endif">
                                            @if ($errors->has('package_length'))
                                                <label class="text-danger">{{ $errors->first('package_length') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.width")(in m)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="package_width"
                                                   name="package_width"
                                                   placeholder=""
                                                   value="@if(!empty($package)) {{ $package->package_width }} @endif">
                                            @if ($errors->has('package_width'))
                                                <label class="text-danger">{{ $errors->first('package_width') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.height")(in m)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="package_height"
                                                   name="package_height"
                                                   placeholder=""
                                                   value="@if(!empty($package)) {{ $package->package_height }} @endif">
                                            @if ($errors->has('package_height'))
                                                <label class="text-danger">{{ $errors->first('package_height') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <div class="form-actions d-flex flex-row-reverse p-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.update")
                                </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'edit_text'])
@endsection
@section("js")
<script>
    $('#engine_type').on('change', function(){
        var engineType = $(this).val();
        if(engineType == 2){
            $('.volume').hide();
        }else{
            $('.volume').show();
        }
    });

$(document).ready(function () {
        let selectedType = $('#engine_type').val();
        // Show the selected section
        if (selectedType == 1) {
            $('.volume').show();
        }else{
             // Hide all sections
        $('.volume').hide();
        }
});


</script>
@endsection
