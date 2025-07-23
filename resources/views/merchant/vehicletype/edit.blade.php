@extends('merchant.layouts.main')
@section('content')
<style>
    /* Custom styling for selected image */
    .selected {
            border: 2px solid blue;
    }

    .gallery_image {
        width: 50px;
        height: 50px;
    }
</style>
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
                            <a href="{{ route('vehicletype.index') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px"><i
                                            class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title">
                        @lang("$string_file.vehicle_type")
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                @php  $id = !empty($vehicle->id) ? $vehicle->id : NULL @endphp
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          name="vehicle-type-edit" id="vehicle-type-edit"
                          action="{{route('vehicletype.update', $vehicle->id)}}">
                        {{method_field('PUT')}}
                        @csrf
                        <fieldset>
                            <div class="row">

                                @if($engine_type_enable == 1)
                                    <div class="col-md-4">
                                        <label>@lang("$string_file.engine_type") <span class="text-danger">*</span></label>
                                        <div class="form-group">
                                            <select name="engine_type" id="engine_type" class="form-control" required>
                                                <option value="1" @if($vehicle->engine_type == 1) Selected @endif >Engine Based</option>
                                                <option value="2" @if($vehicle->engine_type == 2) Selected @endif >Non Engine Based</option>
                                            </select>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.vehicle_type")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="vehicle_name"
                                               name="vehicle_name"
                                               value="@if($vehicle->LanguageVehicleTypeSingle){{ $vehicle->LanguageVehicleTypeSingle->vehicleTypeName }}@endif"
                                               placeholder=""
                                               required>
                                        @if ($errors->has('vehicle_name'))
                                            <label class="text-danger">{{ $errors->first('vehicle_name')
                                                            }}</label>
                                        @endif
                                    </div>
                                </div>
                                @if(in_array('DELIVERY',$segment) && isset($merchant->ApplicationConfiguration->delivery_app_theme) && $merchant->ApplicationConfiguration->delivery_app_theme == 3)
                                    <div class="col-md-4 max_package_weight_range" >
                                        <label> @lang("$string_file.max_package_weight_range")(in kg)
                                            <span class="text-danger">*</span></label>
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="max_package_weight_range"
                                            name="max_package_weight_range" placeholder="100" value="{{$vehicle->package_weight_range}}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-4 volumetric_capacity">
                                        <label> @lang("$string_file.volumetric_capacity")(in cubic m)
                                            <span class="text-danger">*</span></label>
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="volumetric_capacity"
                                            name="volumetric_capacity" placeholder="0-10" value="{{$vehicle->volumetric_capacity}}"/>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                             @lang("$string_file.vehicle_rank")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="vehicle_rank"
                                               name="vehicle_rank"
                                               value="{{ $vehicle->vehicleTypeRank }}"
                                               placeholder=""
                                               min="1"
                                               required>
                                        @if ($errors->has('vehicle_rank'))
                                            <label class="text-danger">{{ $errors->first('vehicle_rank')
                                                            }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.sequence")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="sequence"
                                               name="sequence"
                                               value="{{ $vehicle->sequence }}"
                                               placeholder=""
                                               min="1"
                                               required>
                                        @if ($errors->has('sequence'))
                                            <label class="text-danger">{{ $errors->first('sequence') }}</label>
                                        @endif
                                    </div>
                                </div>
                                @if($vehicle_model_expire == 1)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            <label>@lang("$string_file.model_expire_year") <span class="text-danger">*</span></label>
                                        </label>
                                        <input type="number" class="form-control" id="model_expire_year"
                                               name="model_expire_year" value="{{ $vehicle->model_expire_year }}" placeholder="" min="1" max="50" required>
                                        @if ($errors->has('model_expire_year'))
                                            <label class="text-danger">{{ $errors->first('model_expire_year') }}</label>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="lastName3">
                                            @lang("$string_file.description")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="description"
                                                  name="description"
                                                  placeholder=""
                                                  rows="3">@if($vehicle->LanguageVehicleTypeSingle) {{ $vehicle->LanguageVehicleTypeSingle->vehicleTypeDescription }} @endif</textarea>
                                        @if ($errors->has('description'))
                                            <label class="text-danger">{{ $errors->first('description') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.image")
                                            <span class="text-danger">*</span>
                                        </label><span
                                                style="color: blue">(@lang("$string_file.size") 100*100 px)</span><i
                                                class="fa fa-info-circle fa-1"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                title=""></i>
                                        <input style="    height: 0%;" type="file" class="form-control" id="vehicle_image"
                                               name="vehicle_image"
                                               placeholder="">
                                        @if ($errors->has('vehicle_image'))
                                            <label class="text-danger">{{ $errors->first('vehicle_image')
                                                            }}</label>
                                        @endif
                                    </div>
                                    OR
                                    <div class="form-group">
                                        <label>@lang("$string_file.gallery")</label><br>
                                        <input type="text" class="form-control" id="gallery_image"
                                            name="gallery_image" readonly style="display: none;"/>
                                        <button type="button" class="form-control" id="gallery_cancel"
                                                name="gallery_cancel" style="display: none;">X
                                        </button>
                                        <button type="button" class="btn btn-primary mt-3" id="gallery_choose"
                                                data-toggle="modal" data-target="#imageModal">Choose
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    @if($vehicle->is_gallery_image_upload == 1)
                                    <img src="{{ get_image($vehicle->vehicleTypeImage, 'vehicle',NULL,true,true,"",'gallery_image')  }}" style="width:50%; height:50%; ">
                                    @else
                                     <img src="{{ get_image($vehicle->vehicleTypeImage, 'vehicle')  }}" style="width:50%; height:50%; ">
                                    @endif
                                </div>
                                
                                <!--Seat Capacity-->
                                @if($seat_capacity_config == 1)
                                   <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            <label>@lang("$string_file.passenger_seat_capacity")
                                        </label>
                                        <input type="number" class="form-control" id="passenger_seat_capacity"
                                               name="passenger_seat_capacity" value="{{ $vehicle->passenger_seat_capacity }}" min=0 maxlength=3 placeholder="">
                                        @if ($errors->has('passenger_seat_capacity'))
                                            <label class="text-danger">{{ $errors->first('passenger_seat_capacity') }}</label>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.map_image")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <span
                                                style="color: blue">(@lang("$string_file.size") 60*60 px)
                                                        </span>
                                        <i
                                                class="fa fa-info-circle fa-1"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                title=""></i>
                                        <div class="row">
                                            @foreach(get_config_image('map_icon') as $path)
                                                <br>
                                                <div class="col-md-4 col-sm-6">
                                                    <input type="radio" name="vehicleTypeMapImage"
                                                           value="{{ $path }}"
                                                           id="male-radio-{{ $path }}" @if($vehicle['vehicleTypeMapImage'] == $path) checked @endif>                                            &nbsp;
                                                    <label for="male-radio-{{ $path }}"><img
                                                                src="{{ view_config_image($path)  }}"
                                                                style="width:10%; height:10%; margin-right:3%;">{{ explode_image_path($path) }}
                                                    </label>
                                                </div>
                                            @endforeach
                                            @if ($errors->has('vehicleTypeMapImage'))
                                                <label class="text-danger">{{ $errors->first('vehicleTypeMapImage') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="checkbox-custom checkbox-primary">
                                                <input type="checkbox" value="1" name="ride_now" class="ride_type"
                                                       id="ride_now" @if($vehicle->ride_now == 1) checked=""  @endif>
                                                <label class="font-weight-400">@lang("$string_file.request_now")</label>
                                                <br>
                                                <input type="checkbox" value="1" name="ride_later" class="ride_type"
                                                       id="ride_later" @if($vehicle->ride_later == 1) checked="" @endif>
                                                <label class="font-weight-400">@lang("$string_file.request_later")</label>
                                                @if(isset($merchant->BookingConfiguration->in_drive_enable) && $merchant->BookingConfiguration->in_drive_enable == 1)
                                                    <br>
                                                <input type="checkbox" value="1" name="in_drive_enable" class="ride_type"
                                                       id="in_drive_enable" @if($vehicle->in_drive_enable == 1) checked="" @endif>
                                                <label class="font-weight-400">@lang("$string_file.in_drive_enable")</label>
                                                @endif
                                                <br>
                                                @if(in_array(5,$merchant->Service))
                                                <input type="checkbox" value="1" name="pool_enable"
                                                       id="pool_enable"
                                                       @if($vehicle->pool_enable == 1) checked=""  @endif>
                                                <label class="font-weight-400">@lang("$string_file.pool_enable")</label>
                                                    <br>
                                                    @endif
                                            </div><br>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            @if($id == NULL || $edit_permission)
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.update")
                            </button>
                            @else
                                <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Image Modal -->
     <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Select Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row" id="imageGallery">
                        <!-- Images will be dynamically added here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="chooseImageBtn">Choose Image</button>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'edit_text'])
@endsection
@section("js")
<script>
    // Images data (replace with your image URLs)
    const images = JSON.parse('{!! $images_arr !!}');
    // Function to load images into gallery
    function loadImages() {
        const gallery = document.getElementById('imageGallery');
        gallery.innerHTML = '';
        images.forEach((image, index) => {
            const imgElement = document.createElement('img');
            imgElement.src = image;
            imgElement.classList.add('img-thumbnail', 'm-2', 'cursor-pointer', 'gallery_image');
            imgElement.setAttribute('data-index', index);
            imgElement.addEventListener('click', selectImage);
            gallery.appendChild(imgElement);
        });
    }

// Function to handle image selection
function selectImage(event) {
    const selectedImage = document.querySelector('.selected');
    if (selectedImage) {
        selectedImage.classList.remove('selected');
    }
    event.target.classList.add('selected');
}

// Function to handle choose image button click
document.getElementById('chooseImageBtn').addEventListener('click', () => {
    const selectedImage = document.querySelector('.selected');
    if (selectedImage) {
        document.getElementById('gallery_image').value = selectedImage.src;
        // Close the modal
        // const modal = document.getElementById('imageModal');
        // const modalInstance = bootstrap.Modal.getInstance(modal);
        // modalInstance.hide();
        $('#imageModal').modal('hide');
        document.getElementById('gallery_image').style.display = 'block';
        document.getElementById('gallery_cancel').style.display = 'block';
        document.getElementById('gallery_choose').style.display = 'none';
    } else {
        alert('Please select an image.');
    }
});

document.getElementById('gallery_cancel').addEventListener('click', () => {
    document.getElementById('gallery_image').style.display = 'none';
    document.getElementById('gallery_cancel').style.display = 'none';
    document.getElementById('gallery_choose').style.display = 'block';
    document.getElementById('gallery_image').value = null;
});

// Initial loading of images
window.addEventListener('load', loadImages);
window.addEventListener('load', checkEngineType);
function checkEngineType(){
    var engineType = $('#engine_type').val();
    if(engineType == 2){
        $('.max_package_weight_range').hide();
        $('.volumetric_capacity').hide();
    }else{
        $('.max_package_weight_range').show();
        $('.volumetric_capacity').show();
    }
 }

$('#engine_type').on('change', function(){
    checkEngineType();
});
</script>
@endsection
