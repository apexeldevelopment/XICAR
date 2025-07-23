@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            @if(Session::has('vehicle-document-expire-warning'))
                <p class="alert alert-info">{{ Session::get('vehicle-document-expire-warning') }}</p>
            @endif
            @if(Session::has('vehicle-document-expired-error'))
                <div class="alert dark alert-icon alert-danger" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <i class="icon fa-warning" aria-hidden="true"></i> {{ Session::get('vehicle-document-expired-error') }}
                </div>
            @endif
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->add_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{ route('driver.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.add_vehicle")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          onSubmit="return validateForm();"
                          id="driver-vehicle-form",
                          name="driver-vehicle-form"
                          action="{{ route('merchant.driver.vehicle.store',$driver->id) }}">
                        @csrf
                        {!! Form::hidden('vehicle_model_expire',$vehicle_model_expire,['class'=>'']) !!}
                        {!! Form::hidden('taxDueDate',"",['class'=>'']) !!}
                        {!! Form::hidden('fuelType',"",['class'=>'']) !!}
                        @php
                            $vehicle_image = NULL;
                            $plate_image = NULL;
                            $vehicle_model_id = NULL;
                            $vehicle_id = NULL;
                            $registration_date = NULL;
                            $expire_date = NULL;
                            $vehicle_edit = $driver->signupStep == 9 && $vehicle_id && $existing_booking ? false : true ;
                            $yes_no = ["0"=>trans("$string_file.no"),"1"=>trans("$string_file.yes")];
                            $engine_nonEngine = ["1"=>trans("$string_file.engine_based"),"2"=>trans("$string_file.non_engine_based")];
                        @endphp
                        @if(!empty($vehicle_details))
                            @php
                                $vehicle_id = $vehicle_details['id'];
                                $vehicle_image = $vehicle_details['vehicle_image'];
                                $registration_date = $vehicle_details['vehicle_register_date'];
                                $expire_date = $vehicle_details['vehicle_expire_date'];
                                $plate_image = $vehicle_details['vehicle_number_plate_image'];
                                $vehicle_edit = $driver->signupStep == 9 && $vehicle_id && $existing_booking ? false : true ;
                            @endphp
                        @endif
                        {!! Form::hidden('vehicle_id',$vehicle_id,['id'=>"vehicle_id"]) !!}
                        {!! Form::hidden('request_from',$request_from,['id'=>"request_from"]) !!}
                        <div class="row">

                            @if($engine_based_vehicle_type)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ac_nonac">@lang("$string_file.engine_type") :</label>
                                        @if($vehicle_edit)
                                            {!! Form::select('engine_type',$engine_nonEngine,old('engine_type',isset($vehicle_details->VehicleType->engine_type) ? $vehicle_details->VehicleType->engine_type : 1),['class'=>'form-control','id'=>'engine_type']) !!}
                                        @else
                                            {!! Form::select('engine_type',$engine_nonEngine,old('engine_type',isset($vehicle_details->VehicleType->engine_type) ? $vehicle_details->VehicleType->engine_type : 1),['class'=>'form-control','id'=>'engine_type', 'disabled' =>true]) !!}
                                        @endif
                                        @if ($errors->has('engine_type'))
                                            <label class="text-danger">{{ $errors->first('engine_type') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.vehicle_type") <span class="text-danger">*</span>
                                        :</label>
                                    @if($vehicle_edit)
                                    <select class="form-control required"
                                            name="vehicle_type_id"
                                            id="vehicle_type_id"
                                            required>
                                          <option value="">@lang("$string_file.vehicle_type")</option>
                                         @foreach($vehicletypes as $vehicle)
                                             <option value="{{ $vehicle->id }}" {!! isset($vehicle_details['vehicle_type_id']) && $vehicle_details['vehicle_type_id'] == $vehicle->id ? "selected" : NULL !!}>{{ $vehicle->VehicleTypeName }}</option>
                                         @endforeach
                                     </select>
                                     @if ($errors->has('vehicle_type_id'))
                                         <label class="text-danger">{{ $errors->first('vehicle_type_id') }}</label>
                                     @endif
                                     @else
                                         {!! Form::text('vehicle_type',$vehicle_details->VehicleType->vehicleTypeName,['class'=>'form-control','disabled'=>true]) !!}
                                     @endif
                                 </div>
                             </div>
                             @if($appConfig->vehicle_make_text == 1 && $vehicle_edit)
                                 <div class="col-md-4">
                                     <div class="form-group">
                                         <label for="location3">@lang("$string_file.vehicle_make") <span class="text-danger">*</span>
                                             :</label>
                                         <input type="text" class="form-control" name="vehicle_make_id" value="@if(!empty($vehicle_details)){{$vehicle_details->VehicleMake->vehicleMakeName}}@endif"
                                                id="vehicle_make_id" required>
                                         @if ($errors->has('vehicle_make_id'))
                                             <label class="text-danger">{{ $errors->first('vehicle_make_id') }}</label>
                                         @endif
                                     </div>
                                 </div>

                             @else
                                 <div class="col-md-4">
                                     <div class="form-group">
                                         <label for="location3">@lang("$string_file.vehicle_make") <span class="text-danger">*</span>
                                             :</label>
                                         @if($vehicle_edit)
                                         <select class="form-control required"
                                                 name="vehicle_make_id"
                                                 onchange="return vehicleModel(this.value)"
                                                 id="vehicle_make_id"
                                                 required>
                                             <option value="">--@lang("$string_file.select")--</option>
                                             @foreach($vehiclemakes as $vehiclemake)
                                                 <option value="{{ $vehiclemake->id }}" {!! isset($vehicle_details['vehicle_make_id']) && $vehicle_details['vehicle_make_id'] == $vehiclemake->id ? "selected" : NULL !!}>{{ $vehiclemake->VehicleMakeName }}</option>
                                             @endforeach
                                         </select>
                                         @if ($errors->has('vehicle_make_id'))
                                             <label class="text-danger">{{ $errors->first('vehicle_make_id') }}</label>
                                         @endif
                                         @else
                                             @if(!empty($vehicle_details->VehicleMake))
                                                {!! Form::text('vehicle_make',$vehicle_details->VehicleMake->vehicleMakeName,['class'=>'form-control','disabled'=>true]) !!}
                                             @else
                                                 {!! Form::text('vehicle_make',"",['class'=>'form-control','disabled'=>true]) !!}
                                             @endif
                                         @endif
                                     </div>
                                 </div>
                             @endif
                             @if($appConfig->vehicle_model_text == 1 && $vehicle_edit)
                                 <div class="col-md-4">
                                     <div class="form-group">
                                         <label for="location3">@lang("$string_file.vehicle_model") <span class="text-danger">*</span>
                                             :</label>
                                         <input class="form-control" type="text" name="vehicle_model_id" value="@if(!empty($vehicle_details)){{$vehicle_details->VehicleModel->vehicleModelName}}@endif"
                                                id="vehicle_model_id" required>
                                         @if ($errors->has('vehicle_make_id'))
                                             <label class="text-danger">{{ $errors->first('vehicle_make_id') }}</label>
                                         @endif
                                     </div>
                                 </div>
                                 {{--<div class="col-md-4">--}}
                                    {{--<div class="form-group">--}}
                                        {{--<label for="location3">@lang("$string_file.vehicle_seat") <span class="text-danger">*</span>--}}
                                            {{--:</label>--}}
                                        {{--<input class="form-control" type="text" name="vehicle_seat" id="vehicle_seat"--}}
                                               {{--required>--}}
                                        {{--@if ($errors->has('vehicle_seat'))--}}
                                            {{--<label class="text-danger">{{ $errors->first('vehicle_seat') }}</label>--}}
                                        {{--@endif--}}
                                    {{--</div>--}}
                                {{--</div>--}}
                            @else
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.vehicle_model") <span class="text-danger">*</span>
                                            :</label>
                                        @if($vehicle_edit)
                                        <select class="form-control required"
                                                name="vehicle_model_id"
                                                id="vehicle_model_id"
                                                required>
                                            <option value="">--@lang("$string_file.select")--</option>
                                            @foreach($vehicle_models as $vehicle_model)
                                                <option value="{{ $vehicle_model->id }}" {!! isset($vehicle_details['vehicle_model_id']) && $vehicle_details['vehicle_model_id'] == $vehicle_model->id ? "selected" : NULL !!}>{{ $vehicle_model->VehicleModelName }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('vehicle_make_id'))
                                            <label class="text-danger">{{ $errors->first('vehicle_make_id') }}</label>
                                        @endif
                                        @else
                                            @if(!empty($vehicle_details->VehicleModel))
                                            {!! Form::text('vehicle_model',$vehicle_details->VehicleModel->vehicleModelName,['class'=>'form-control','disabled'=>true]) !!}
                                            @else
                                                {!! Form::text('vehicle_model',"",['class'=>'form-control','disabled'=>true]) !!}
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.vehicle_number") <span class="text-danger">*</span>
                                        :</label>
                                    <input type="text" class="form-control"
                                           id="vehicle_number"
                                           name="vehicle_number"
                                           placeholder="@lang("$string_file.vehicle_number") "
                                           value="{!! isset($vehicle_details['vehicle_number']) ? $vehicle_details['vehicle_number'] : NULL !!}"
                                           required
                                    >
                                    @if ($errors->has('vehicle_number'))
                                        <label class="text-danger">{{ $errors->first('vehicle_number') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.vehicle_image") <span class="text-danger">*</span>
                                        :</label>
                                    @if(!empty($vehicle_image))
                                        <a href="{{get_image($vehicle_image,'vehicle_document')}}"
                                           target="_blank">@lang("$string_file.view") </a>
                                    @endif
                                    <input type="file" class="form-control" id="car_image"
                                           name="car_image" {!! empty($vehicle_image) ? "required" : '' !!}
                                    >
                                    @if ($errors->has('car_image'))
                                        <label class="text-danger">{{ $errors->first('car_image') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.number_plate")
                                        :</label>
                                    @if(!empty($plate_image))
                                        <a href="{{get_image($plate_image,'vehicle_document')}}"
                                           target="_blank">@lang("$string_file.view") </a>
                                    @endif
                                    <input type="file" class="form-control"
                                           id="car_number_plate_image"
                                           name="car_number_plate_image">
                                    @if ($errors->has('car_number_plate_image'))
                                        <label class="text-danger">{{ $errors->first('car_number_plate_image') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.vehicle_color")  <span
                                                class="text-danger">*</span> :</label>
                                    <input type="text" class="form-control"
                                           id="vehicle_color"
                                           name="vehicle_color"
                                           value="{!! isset($vehicle_details['vehicle_color']) ? $vehicle_details['vehicle_color'] : NULL !!}"
                                           placeholder="@lang("$string_file.vehicle")  @lang("$string_file.color") "
                                           required>
                                    @if ($errors->has('vehicle_color'))
                                        <label class="text-danger">{{ $errors->first('vehicle_color') }}</label>
                                    @endif
                                </div>
                            </div>
                            @if($vehicle_model_expire == 1)
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.registered_date")  <span class="text-danger">*</span>
                                        :</label>
                                    <input type="text"
                                           class="form-control customDatePicker2"
                                           name="vehicle_register_date"
                                           value="{{old('vehicle_register_date',$registration_date)}}"
                                           placeholder="@lang("$string_file.vehicle_registered_date")  "
                                          required autocomplete="off">
                                    @if ($errors->has('vehicle_register_date'))
                                        <label class="text-danger">{{ $errors->first('vehicle_register_date') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.expire_date")  <span class="text-danger">*</span>
                                        :</label>
                                    <input type="text"
                                           class="form-control customDatePicker1"
                                           name="vehicle_expire_date"
                                           value="{{old('vehicle_expire_date',$expire_date)}}"
                                           placeholder=""
                                           required
                                           autocomplete="off">
                                    @if ($errors->has('vehicle_expire_date'))
                                        <label class="text-danger">{{ $errors->first('vehicle_expire_date') }}</label>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if(!empty($baby_seat_enable))
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="baby_seat">@lang("$string_file.baby_seat_enable") :</label>
                                        {!! Form::select('baby_seat',$yes_no,old('baby_seat',isset($vehicle_details['baby_seat']) ? $vehicle_details['baby_seat'] : 0),['class'=>'form-control','id'=>'baby_seat']) !!}
                                        @if ($errors->has('baby_seat'))
                                            <label class="text-danger">{{ $errors->first('baby_seat') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @if(!empty($wheel_chair_enable))
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="wheel_chair">@lang("$string_file.wheel_chair_enable") :</label>
                                        {!! Form::select('wheel_chair',$yes_no,old('wheel_chair',isset($vehicle_details['wheel_chair']) ? $vehicle_details['wheel_chair'] : 0),['class'=>'form-control','id'=>'wheel_chair']) !!}
                                        @if ($errors->has('wheel_chair'))
                                            <label class="text-danger">{{ $errors->first('wheel_chair') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @if(!empty($vehicle_ac_enable))
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ac_nonac">@lang("$string_file.ac_enable") :</label>
                                        {!! Form::select('ac_nonac',$yes_no,old('ac_nonac',isset($vehicle_details['ac_nonac']) ? $vehicle_details['ac_nonac'] : 0),['class'=>'form-control','id'=>'ac_nonac']) !!}
                                    @if ($errors->has('ac_nonac'))
                                            <label class="text-danger">{{ $errors->first('ac_nonac') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div id="vehicle-doc-segment">
                            {!! $vehicle_doc_segment !!}
                        </div>

                        <div class="form-actions d-flex flex-row-reverse p-2">
                            @if($vehicle_id == NULL || $edit_permission)
                                <button type="submit"
                                        class="btn btn-primary">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                                </button>
                             @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script>
        $(document).on('change','#vehicle_type_id',function(e){
            var vehicle_type  = $("#vehicle_type_id option:selected").val();
            var driver = {!! $driver->id !!};
            if (driver != "") {
                var token = $('[name="_token"]').val();
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: "{{ route('ajax.services') }}",
                    data: {driver_id: driver, vehicle: vehicle_type},
                    success: function (data) {
                        $('#vehicle-doc-segment').html(data);
                        var dateToday = new Date();
                        $('.customDatePicker1').datepicker({
                            format: 'yyyy-mm-dd',
                            startDate: dateToday,
                            onRender: function (date) {
                                return date.valueOf() < now.valueOf() ? 'disabled' : '';
                            }
                        });
                    }
                });
            }
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
                    url: "{{ route('ajax.vehiclemodel') }}",
                    data: {vehicle_type_id: vehicle_type_id, vehicle_make_id: vehicle_make_id},
                    success: function (data) {
                        $('#vehicle_model_id').html(data);
                    }
                });
            }
        }

        //@ayush
        //engine based vehicle and dvla checks starts

        $(document).ready(function() {
            // let engine_type_selected = $('#engine_type').val().trim() != "" ?  $('#engine_type').val() : 1;
            let engine_type_selected = $('#engine_type').length && $('#engine_type').val().trim() !== "" ? $('#engine_type').val() : 1;
            let country_area_id = "{{$driver->country_area_id}}";
            getVehicleTypes(country_area_id, engine_type_selected);
        })

        $(document).on("change", "#engine_type", function(){
            let engine_type_selected = $('#engine_type').val();
            let country_area_id = "{{$driver->country_area_id}}";
            getVehicleTypes(country_area_id, engine_type_selected);

        })

        function getVehicleTypes(country_area_id, engine_type){
            let baseUrl = "{{ route('ajax.services.vehicleTypes', ['countryAreaID' => '__COUNTRY_AREA_ID__', 'engineType' => '__ENGINE_TYPE__']) }}";
            let url = baseUrl.replace('__COUNTRY_AREA_ID__', country_area_id).replace('__ENGINE_TYPE__', engine_type);
            let string_file_translated = "{{ __('' . $string_file . '.vehicle_type') }}";

            $.ajax({
                url: url,
                method: "GET",
                headers: {
                    "X-CSRF-TOKEN": "{{csrf_token()}}",
                },
                success: function(data){
                    let select_field = $('#vehicle_type_id');
                    select_field.empty();
                    select_field.append($('<option></option>').attr('value', "").text(string_file_translated))
                    let vehicle_type_id = @json($vehicle_details['vehicle_type_id'] ?? null);
                    data.forEach(element => {
                        // let option = $('<option></option>').attr('value', element.id).text(element.name);
                        // select_field.append(option);
                        let option = $('<option></option>').attr('value', element.id).text(element.name);
                        
                        if (vehicle_type_id == element.id) {
                            option.attr('selected', 'selected');
                        }
                        select_field.append(option);

                    });

                    if(engine_type == 2){
                        $('#vehicle_make_id').removeAttr('required').prop("disabled", true);
                        $('#vehicle_model_id').removeAttr('required').prop("disabled", true);
                        $('#vehicle_number').removeAttr('required').prop("disabled", true);
                        $('#car_number_plate_image').removeAttr('required').prop("disabled", true);
                        $('#vehicle_color').attr('required', true).prop("disabled", false);
                        $('#vehicle_image').removeAttr('required').prop("disabled", true);
                        $('#car_image').attr('required', true).prop("disabled", false);

                    }
                    else{
                        $('#vehicle_make_id').attr('required', true).prop("disabled", false);
                        $('#vehicle_model_id').attr('required', true).prop("disabled", false);
                        $('#vehicle_number').attr('required', true).prop("disabled", false);
                        if(vehicle_type_id){
                            $('#car_number_plate_image').prop("disabled", false);
                        }
                        else{
                            $('#car_number_plate_image').attr('required', true).prop("disabled", false);
                        }
                        $('#vehicle_color').attr('required', true).prop("disabled", false);
                        $('#vehicle_image').attr('required', true).prop("disabled", false);
                        $('#car_image').attr('required', true).prop("disabled", false);
                    }
                },
                error: function(err){
                    console.log(err);
                }
            })
        }
        $(document).on("blur", '#vehicle_number' ,function(){
            let is_dvla_enabled = $('#dvla_verification_enabled').val();
            let vehicle_number = $('#vehicle_number').val();
            let merchant_id = "{{$driver->merchant_id}}";

            let base_url = "{{ route('ajax.services.dvla', ['registration_number' => 'REGISTRATION_NUMBER', 'merchant_id' => 'MERCHANT_ID']) }}";
            base_url = base_url.replace("REGISTRATION_NUMBER", vehicle_number).replace("MERCHANT_ID", merchant_id)

            if(is_dvla_enabled == "ENABLED"){
                $.ajax({
                    url: base_url,
                    method: "GET",
                    headers: {
                        "X-CSRF-TOKEN" : "{{csrf_token()}}"
                    },
                    beforeSend: function() {
                            swal.fire({
                            title: 'Now loading',
                            allowEscapeKey: false,
                            allowOutsideClick: false,
                            onOpen: () => {
                              swal.showLoading();
                            }
                          });
                        },
                    success: function(response){
                        let data = JSON.parse(response);
                        swal.close();


                        let inputDate = new Date(data.monthOfFirstRegistration + '-01'); // Adding '-01' to make it a valid date
                        let currentDate = new Date();
                        let yearsDifference = currentDate.getFullYear() - inputDate.getFullYear();

                        if (currentDate.getMonth() < inputDate.getMonth() ||
                            (currentDate.getMonth() === inputDate.getMonth() && currentDate.getDate() < inputDate.getDate())) {
                            yearsDifference--;
                        }

                        let taxDueDate = data.taxDueDate;
                        let make = data.make;
                        let color = data.colour;
                        let fuelType = data.fuelType;
                        let registrationNumber = data.registrationNumber;

                        if(yearsDifference < 3){

                            if(data.taxStatus == "Taxed"){
                                $('#vehicle_color').val(color);
                                $('#taxDueDate').val(taxDueDate);
                                $('#fuelType').val(fuelType);
                                $('#vehicle_make_id').val(make);

                            }
                            else{
                                 Swal.fire({
                                  icon: "error",
                                  title: "DVLA Verification Failed",
                                  text: "Vehicle is not Taxed",
                                });
                                    $('#vehicle_color').val("");
                                    $('#vehicle_number').val("");
                                    $('#taxDueDate').val("");
                                    $('#fuelType').val("");
                            }

                        }
                        else{
                            if(data.motStatus == "Valid"){
                                if(data.taxStatus == "Taxed"){
                                    $('#vehicle_color').val(color);
                                    $('#taxDueDate').val(taxDueDate);
                                    $('#fuelType').val(fuelType);
                                    $('#vehicle_make_id').val(make);
                                }
                                else{
                                     Swal.fire({
                                      icon: "error",
                                      title: "DVLA Verification Failed",
                                      text: "Vehicle is not Taxed",
                                    });
                                    $('#vehicle_color').val("");
                                    $('#vehicle_number').val("");
                                    $('#taxDueDate').val("");
                                    $('#fuelType').val("");
                                }
                            }
                            else{
                                Swal.fire({
                                      icon: "error",
                                      title: "DVLA Verification Failed",
                                      text: "Vehicle MOT Status is invalid !",
                                    });
                                    $('#vehicle_color').val("");
                                    $('#vehicle_number').val("");
                                    $('#taxDueDate').val("");
                                    $('#fuelType').val("");
                            }

                        }
                    },
                    error: function(err){
                        console.log(err);
                        swal.close();
                    }
                })
            }
        })

    </script>
@endsection
