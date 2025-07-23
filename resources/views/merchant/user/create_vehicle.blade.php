@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('merchant.user.vehicle_list',['id'=>$user->id])}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("common.add") @lang("$string_file.vehicle")</h3>
                </header>
                <div class="panel-body container-fluid" id="validation">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data" ,files="true" ,
                          action="{{route('merchant.user.vehicle.save',['id'=>$user->id])}}" autocomplete="false">
                        @csrf
                        <meta name="csrf-token" content="{{ csrf_token() }}">
                        <fieldset>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang('common.area')
                                            <span class="text-danger">*</span>
                                        </label>

                                        <select class="form-control area_id" name="area_id"
                                                id="area_id" required>
                                            <option value="">@lang('common.select') @lang('common.area')</option>

                                            @foreach($country_area as $area_id)
                                                <option onclick="CountryArea({{$area_id->id}})"
                                                        value="{{ $area_id->id  }}">{{ $area_id->CountryAreaName }}</option>
                                            @endforeach


                                        </select>
                                        @if ($errors->has('area_id'))
                                            <label class="text-danger">{{ $errors->first('area_id') }}</label>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location4">@lang("$string_file.vehicle") @lang('common.type')
                                            <span class="text-danger">*</span>
                                        </label>

                                        <select class="form-control" name="vehicle_type_id"
                                                id="vehicle_type_id" required>
                                            <option value="">@lang('common.select') @lang("$string_file.vehicle") @lang('common.type')</option>
                                        </select>
                                        @if ($errors->has('vehicle_type_id'))
                                            <label class="text-danger">{{ $errors->first('vehicle_type_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.vehicle") @lang('common.make')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" name="vehicle_make_id"
                                                onchange="return vehicleModel(this.value)"
                                                id="vehicle_make_id" required>
                                            <option value="">@lang('common.select') @lang("$string_file.vehicle") @lang('common.make')</option>
                                            @foreach($vehicle_make as $make)
                                                <option value="{{ $make->id }}">{{ $make->vehicleMakeName }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('vehicle_make_id'))
                                            <label class="text-danger">{{ $errors->first('vehicle_make_id') }}</label>
                                        @endif
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.vehicle") @lang('common.model')
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" name="vehicle_model_id"
                                                id="vehicle_model_id" required>
                                            <option value="">@lang('common.select') @lang("$string_file.vehicle") @lang('common.model')</option>
                                        </select>
                                        @if ($errors->has('vehicle_model_id'))
                                            <label class="text-danger">{{ $errors->first('vehicle_model_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.vehicle") @lang("common.number")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="vehicle_number"
                                               name="vehicle_number"
                                               placeholder="@lang("common.enter") @lang("$string_file.vehicle") @lang("common.number")"
                                               value="{{ old('vehicle_number') }}" required>
                                        @if ($errors->has('vehicle_number'))
                                            <label class="text-danger">{{ $errors->first('vehicle_number') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.vehicle") @lang("common.color")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="first_name"
                                               name="vehicle_color"
                                               placeholder="@lang("common.enter") @lang("$string_file.vehicle") @lang("common.color")"
                                               value="{{ old('vehicle_color') }}" required>
                                        @if ($errors->has('vehicle_color'))
                                            <label class="text-danger">{{ $errors->first('vehicle_color') }}</label>
                                        @endif
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle_image">
                                            @lang("$string_file.vehicle") @lang("common.image")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="file" class="form-control" id="vehicle_image"
                                               name="vehicle_image"
                                               placeholder="@lang("$string_file.vehicle") @lang("common.image")" required>
                                        @if ($errors->has('vehicle_image'))
                                            <label class="text-danger">{{ $errors->first('vehicle_image') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle_number_plate_image">
                                            @lang("$string_file.vehicle") @lang("common.number")  @lang("$string_file.plate")  @lang("common.image")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="file" class="form-control" id="vehicle_number_plate_image"
                                               name="vehicle_number_plate_image"
                                               placeholder=" @lang("$string_file.vehicle") @lang("common.number")  @lang("$string_file.plate")  @lang("common.image")"
                                               required>
                                        @if ($errors->has('vehicle_number_plate_image'))
                                            <label class="text-danger">{{ $errors->first('vehicle_number_plate_image') }}</label>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </fieldset>
                        <div class="form-actions right" style="margin-bottom: 3%">
                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fa fa-check-circle"></i> @lang("common.save")
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script type="text/javascript">
        $(document).on('change', '#area_id', function () {
            if ($(this).data('options') == undefined) {
                $('#vehicle_type_id').empty().append('<option>Select Vehicle Type </option>');
                $('#vehicle_model_id').empty().append('<option>Select Vehicle Model </option>');
            }
            var token = $('[name="_token"]').val();
            var id = $(this).val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                type: "POST",
                url: "{{ route('merchant.user.get-area-vehicle-type')}} ",
                data: {
                    "id": id,
                },
                success: function (response) {

                    var len = 0;
                    if (response != null) {
                        len = response.length;
                    }

                    if (len > 0) {
                        // Read data and create <option >
                        for (var i = 0; i < len; i++) {

                            var id = response[i].id;

                            var name = response[i].vehicleTypeName;
                            var option = "<option value='" + id + "'>" + name + "</option>";

                            $("#vehicle_type_id").append(option);
                        }
                    }
                }


            });
        });

        function vehicleModel() {
            var vehicle_type_id = document.getElementById('vehicle_type_id').value;
            var vehicle_make_id = document.getElementById('vehicle_make_id').value;
            if (vehicle_type_id == "") {
                alert("Select Vehicle Type");
                var vehicle_make_index = document.getElementById('vehicle_make_id');
                vehicle_make_index.selectedIndex = 0;
                return false;
            } else {
                var token = $('[name="_token"]').val();
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: "{{route('merchant.user.get-vehicle-model')}}",
                    data: {vehicle_type_id: vehicle_type_id, vehicle_make_id: vehicle_make_id},
                    success: function (data) {
                        $('#vehicle_model_id').html(data);
                    }
                });
            }
        }


    </script>
@endsection