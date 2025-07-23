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
                            <div class="btn-group float-right">
                                <a href="{{ route('vehicletype.index') }}">
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
                                <i class="icon wb-plus" aria-hidden="true"></i>
                                @lang("$string_file.add_vehicle")
                            (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                        </h3>
                    
                </header>
                <div class="panel-body container-fluid">
                    <form method="post" enctype="multipart/form-data" id="vehicle-type-add" name="vehicle-type-add" action="{{ route('vehicletype.store') }}">
                        @csrf
                            <div class="row">
                                @if($engine_type_enable == 1)
                                    <div class="col-md-4">
                                        <label>@lang("$string_file.engine_type") <span class="text-danger">*</span></label>
                                        <div class="form-group">
                                            <select name="engine_type" id="engine_type" class="form-control" required>
                                                <option value="1">Engine Based</option>
                                                <option value="2">Non Engine Based</option>
                                            </select>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <label>@lang("$string_file.vehicle_type") <span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        <input type="text" class="form-control" id="vehicle_name" name="vehicle_name"
                                               placeholder="" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label>@lang("$string_file.vehicle_rank")<span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        <input type="number" class="form-control" id="vehicle_rank" name="vehicle_rank" min="1"
                                            placeholder="" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label>@lang("$string_file.sequence") <span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        <input type="number" class="form-control" id="sequence" name="sequence" min="1"
                                               placeholder="" required>
                                    </div>
                                </div>
                                <input type="hidden" name="vehicle_model_expire_enable" id="vehicle_model_expire_enable" value="{{$vehicle_model_expire}}">
                                @if($vehicle_model_expire == 1)
                                <div class="col-md-4">
                                    <label>@lang("$string_file.model_expire_year") <span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        <input type="number" class="form-control" id="model_expire_year" name="model_expire_year" min="1" max="50" placeholder="" required>
                                    </div>
                                </div>
                                @endif
                                <!--Seat Capacity-->
                                @if($seat_capacity_config == 1)
                                    <div class="col-md-4">
                                        <label>@lang("$string_file.passenger_seat_capacity")
                                        <div class="form-group">
                                            <input type="number" class="form-control" id="passenger_seat_capacity" name="passenger_seat_capacity" min=0 maxlength=3 placeholder="">
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <label>  @lang("$string_file.image")<span class="text-danger">*</span> </label><span style="color: blue">(@lang("$string_file.size") 60*60 px)</span>
                                    <i class="fa fa-info-circle fa-1" data-toggle="tooltip" data-placement="top" title=""></i>
                                    <div class="form-group">
                                        <input style="" type="file" class="form-control" id="vehicle_image" name="vehicle_image" placeholder="" required>
                                    </div>
                                    OR
                                    <div class="col-md-4">
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
                                <div class="col-md-4">
                                    <label> @lang("$string_file.description")
                                        {{-- <span class="text-danger">*</span> --}}
                                    </label>
                                    <div class="form-group">
                                    <textarea class="form-control" maxlength="500" id="description" name="description" rows="3"
                                          placeholder=""></textarea>
                                    </div>
                                </div>
                                @if(in_array('DELIVERY',$merchant_segment) && isset($merchant->ApplicationConfiguration->delivery_app_theme) && $merchant->ApplicationConfiguration->delivery_app_theme == 3)
                                    <div class="col-md-4 max_package_weight_range">
                                        <label> @lang("$string_file.max_package_weight_range")(in kg)
                                            <span class="text-danger">*</span></label>
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="max_package_weight_range"
                                            name="max_package_weight_range" placeholder="0-10"/>
                                        </div>
                                    </div>
                                    <div class="col-md-4 volumetric_capacity">
                                        <label> @lang("$string_file.volumetric_capacity")(in cubic m)
                                            <span class="text-danger">*</span></label>
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="volumetric_capacity"
                                            name="volumetric_capacity" placeholder="0-10"/>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4 mt-md-15">
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" class="ride_type" value="1" name="ride_now"
                                               id="ride_now"/>
                                        <label class="font-weight-400">@lang("$string_file.ride_now")</label>
                                        <br>
                                        <input type="checkbox" class="ride_type" value="1" name="ride_later"
                                               id="ride_later"/>
                                        <label class="font-weight-400">@lang("$string_file.ride_later")</label>
                                        @if(isset($merchant->BookingConfiguration->in_drive_enable) && $merchant->BookingConfiguration->in_drive_enable == 1)
                                        <br>
                                        <input type="checkbox" class="ride_type" value="1" name="in_drive_enable"
                                               id="in_drive_enable"/>
                                            <label class="font-weight-400">@lang("$string_file.in_drive_enable")</label>
                                        @endif
                                        <br>
                                        @if(in_array(5,$merchant->Service))
                                            <input type="checkbox" value="1" name="pool_enable"
                                                   id="pool_enable"/>
                                            <label class="font-weight-400">@lang("$string_file.pool_enable")</label>
                                            <br>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <br>
                            <label> @lang("$string_file.map_image")
                                <span class="text-danger">*</span> </label><span style="color: blue">(@lang("$string_file.size") 100*100px)</span><i
                                    class="fa fa-info-circle fa-1" data-toggle="tooltip" data-placement="top" title=""></i>
                            <div class="form-group"> <div class="row">
                                    @foreach(get_config_image('map_icon') as $path)
                                        <div class="col-md-4">
                                            <input type="radio" name="vehicle_map_image" value="{{ $path }}"
                                                   id="vehicle_map_image"><label for="male-radio-{{ $path }}">
                                                <img src="{{ view_config_image($path) }}" class="w-p10" >
                                                {{ explode_image_path($path) }}
                                            </label>
                                        </div>
                                        <br>
                                    @endforeach
                                </div>
                            </div>
                        
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.submit")
                            </button>
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
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
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

 $('#engine_type').on('change', function(){
    var engineType = $(this).val();
    if(engineType == 2){
        $('.max_package_weight_range').hide();
        $('.volumetric_capacity').hide();
    }else{
        $('.max_package_weight_range').show();
        $('.volumetric_capacity').show();
    }
});

</script>
@endsection