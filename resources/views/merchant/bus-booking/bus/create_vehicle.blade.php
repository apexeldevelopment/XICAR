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
                    <i class="icon fa-warning"
                       aria-hidden="true"></i> {{ Session::get('vehicle-document-expired-error') }}
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
                        <a href="{{ route('merchant.bus_booking.bus.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.add_bus")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" enctype="multipart/form-data"
                          onSubmit="return validateForm();" id="driver-bus-form" , name="driver-vehicle-form"
                          action="{{ route('merchant.bus-booking.bus.store') }}">
                        @csrf
                        {!! Form::hidden('vehicle_model_expire',$vehicle_model_expire,['class'=>'']) !!}
                        @php
                            $vehicle_image = NULL;
                            $plate_image = NULL;
                            $vehicle_model_id = NULL;
                            $vehicle_id = NULL;
                            $registration_date = NULL;
                            $expire_date = NULL;
                            $vehicle_edit = $vehicle_id ? false : true ;
                            $selected_traveller_id = NULL;
                            $yes_no = ["0"=>trans("$string_file.no"),"1"=>trans("$string_file.yes")];
                        @endphp
                        @if(!empty($bus))
                            @php
                                $vehicle_id = $bus['id'];
                                $vehicle_image = $bus['vehicle_image'];
                                $registration_date = $bus['vehicle_register_date'];
                                $expire_date = $bus['vehicle_expire_date'];
                                $plate_image = $bus['vehicle_number_plate_image'];
                                $selected_traveller_id=isset($bus->BusTraveller)? $bus->BusTraveller->id: NULL;
                            @endphp
                        @endif
                        {!! Form::hidden('vehicle_id',$vehicle_id,['id'=>"vehicle_id"]) !!}
                        {!! Form::hidden('request_from',$request_from,['id'=>"request_from"]) !!}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="country_area_id">@lang("$string_file.service_area") :</label>
                                    {!! Form::select('country_area_id',$arr_areas,old('country_area_id',isset($bus['country_area_id']) ? $bus['country_area_id'] : 0),['class'=>'form-control','id'=>'country_area_id']) !!}
                                    @if ($errors->has('country_area_id'))
                                        <label class="text-danger">{{ $errors->first('country_area_id') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vehicle_type_id">@lang("$string_file.vehicle_type") <span
                                                class="text-danger">*</span>
                                        :</label>
                                    @if($vehicle_edit)
                                        <select class="form-control required" name="vehicle_type_id"
                                                id="vehicle_type_id" required>
                                            <option value="">@lang("$string_file.vehicle_type")</option>
                                            @foreach($vehicletypes as $vehicle)
                                                <option value="{{ $vehicle->id }}" {!! isset($bus['vehicle_type_id']) && $bus['vehicle_type_id']==$vehicle->id ? "selected" : NULL !!}>{{ $vehicle->VehicleTypeName }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('vehicle_type_id'))
                                            <label class="text-danger">{{ $errors->first('vehicle_type_id') }}</label>
                                        @endif
                                    @else
                                        {!! Form::text('vehicle_type',$bus->VehicleType->vehicleTypeName,['class'=>'form-control','disabled'=>true]) !!}
                                    @endif
                                </div>
                            </div>
                            @if($appConfig->vehicle_make_text == 1 && $vehicle_edit)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.vehicle_make") <span
                                                    class="text-danger">*</span>
                                            :</label>
                                        <input type="text" class="form-control" name="vehicle_make_id"
                                               id="vehicle_make_id" required>
                                        @if ($errors->has('vehicle_make_id'))
                                            <label class="text-danger">{{ $errors->first('vehicle_make_id') }}</label>
                                        @endif
                                    </div>
                                </div>

                            @else
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.vehicle_make") <span
                                                    class="text-danger">*</span>
                                            :</label>
                                        @if($vehicle_edit)
                                            <select class="form-control required" name="vehicle_make_id"
                                                    onchange="return vehicleModel(this.value)" id="vehicle_make_id"
                                                    required>
                                                <option value="">--@lang("$string_file.select")--</option>
                                                @foreach($vehiclemakes as $vehiclemake)
                                                    <option value="{{ $vehiclemake->id }}" {!! isset($bus['vehicle_make_id']) && $bus['vehicle_make_id']==$vehiclemake->id ? "selected" : NULL !!}>{{ $vehiclemake->VehicleMakeName }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('vehicle_make_id'))
                                                <label class="text-danger">{{ $errors->first('vehicle_make_id') }}</label>
                                            @endif
                                        @else
                                            {!! Form::text('vehicle_make',$bus->VehicleMake->vehicleMakeName,['class'=>'form-control','disabled'=>true]) !!}
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @if($appConfig->vehicle_model_text == 1 && $vehicle_edit)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.vehicle_model") <span
                                                    class="text-danger">*</span>
                                            :</label>
                                        <input class="form-control" type="text" name="vehicle_model_id"
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
                                        <label for="location3">@lang("$string_file.vehicle_model") <span
                                                    class="text-danger">*</span>
                                            :</label>
                                        @if($vehicle_edit)
                                            <select class="form-control required" name="vehicle_model_id"
                                                    id="vehicle_model_id" required>
                                                <option value="">--@lang("$string_file.select")--</option>
                                            </select>
                                            @if ($errors->has('vehicle_make_id'))
                                                <label class="text-danger">{{ $errors->first('vehicle_make_id') }}</label>
                                            @endif
                                        @else
                                            {!! Form::text('vehicle_model',$bus->VehicleModel->vehicleModelName,['class'=>'form-control','disabled'=>true]) !!}
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.vehicle_number") <span
                                                class="text-danger">*</span>
                                        :</label>
                                    <input type="text" class="form-control" id="vehicle_number" name="vehicle_number"
                                           placeholder="@lang(" $string_file.vehicle_number") "
                                           value=" {!! isset($bus['vehicle_number']) ? $bus['vehicle_number'] : NULL !!}"
                                           required>
                                    @if ($errors->has('vehicle_number'))
                                        <label class="text-danger">{{ $errors->first('vehicle_number') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.name") <span class="text-danger">*</span>
                                        :</label>
                                    <input type="text" class="form-control" id="bus_name" name="bus_name" placeholder=""
                                           value="{!! isset($bus['bus_name']) ? $bus['bus_name'] : NULL !!}" required>
                                    @if ($errors->has('bus_name'))
                                        <label class="text-danger">{{ $errors->first('bus_name') }}</label>
                                    @endif
                                </div>
                            </div>
                            <!-- <div class="col-md-4">
                                <div class="form-group">
                                    <label for="traveller_name">@lang("$string_file.traveller") @lang("$string_file.name")
                                        :</label>
                                    <input type="text" class="form-control" id="traveller_name" name="traveller_name" placeholder=""
                                           value="{!! isset($bus['traveller_name']) ? $bus['traveller_name'] : NULL !!}">
                                    @if ($errors->has('traveller_name'))
                                        <label class="text-danger">{{ $errors->first('traveller_name') }}</label>
                                    @endif
                                </div>
                            </div> -->

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.bus_traveller") <span
                                                class="text-danger">*</span>
                                        :</label>
                                        <select class="form-control required" name="bus_traveller_id"
                                                id="bus_traveller_id" required>
                                            <option value="">--@lang("$string_file.select")--</option>
                                            @foreach($bus_travellers as $traveller)
                                            <option value="{{$traveller->id}}" @if($selected_traveller_id == $traveller->id) selected @endif>{{$traveller->getNameAttribute()}}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('bus_traveller_id'))
                                            <label class="text-danger">{{ $errors->first('bus_traveller_id') }}</label>
                                        @endif
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.vehicle_image") <span
                                                class="text-danger">*</span>
                                        :</label>
                                    @if(!empty($vehicle_image))
                                        <a href="{{get_image($vehicle_image,'vehicle_document')}}"
                                           target="_blank">@lang("$string_file.view") </a>
                                    @endif
                                    <input type="file" class="form-control" id="car_image"
                                           name="car_image" {!! empty($vehicle_image) ? "required" : '' !!}>
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
                                    <input type="file" class="form-control" id="car_number_plate_image"
                                           name="car_number_plate_image">
                                    @if ($errors->has('car_number_plate_image'))
                                        <label class="text-danger">{{ $errors->first('car_number_plate_image') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.ac_enable"):</label>
                                    {!! Form::select('ac_nonac',$yes_no,old('ac_nonac',isset($bus['ac_nonac']) ? $bus['ac_nonac'] : 0),['class'=>'form-control','id'=>'ac_nonac']) !!}
                                    @if ($errors->has('ac_nonac'))
                                        <label class="text-danger">{{ $errors->first('ac_nonac') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.type"):</label>
                                    @if(empty($bus))
                                        {!! Form::select('type',$bus_types,old('type',isset($bus['type']) ? $bus['type'] : 0),['class'=>'form-control','id'=>'type', 'onChange'=>"getDesignType()"]) !!}
                                        @if ($errors->has('type'))
                                            <label class="text-danger">{{ $errors->first('type') }}</label>
                                        @endif
                                    @else
                                        {{ Form::text("type_txt",$bus_types[$bus['type']],array("class" => "form-control", "id" => "type_txt", "disabled" => true)) }}
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="design_type">@lang("$string_file.design_type"):</label>
                                    @if(empty($bus))
                                        {!! Form::select('design_type',[],old('design_type',isset($bus['design_type']) ? $bus['design_type'] : 0),['class'=>'form-control','id'=>'design_type']) !!}
                                        @if ($errors->has('design_type'))
                                            <label class="text-danger">{{ $errors->first('design_type') }}</label>
                                        @endif
                                    @else
                                        {{ Form::text("design_type_txt",$bus_design_types[$bus['design_type']],array("class" => "form-control", "id" => "design_type_txt", "disabled" => true)) }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.vehicle_color") <span
                                                class="text-danger">*</span> :</label>
                                    <input type="text" class="form-control" id="vehicle_color" name="vehicle_color"
                                           value="{!! isset($bus['vehicle_color']) ? $bus['vehicle_color'] : NULL !!}"
                                           placeholder="@lang(" $string_file.vehicle") @lang("$string_file.color") "
                                           required>
                                    @if ($errors->has('vehicle_color'))
                                        <label class=" text-danger">{{ $errors->first('vehicle_color') }}</label>
                                    @endif
                                </div>
                            </div>
                            @if($vehicle_model_expire == 1)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.registered_date") <span
                                                    class="text-danger">*</span>
                                            :</label>
                                        <input type="text" class="form-control customDatePicker2"
                                               name="vehicle_register_date"
                                               value="{{old('vehicle_register_date',$registration_date)}}"
                                               placeholder="@lang(" $string_file.vehicle_registered_date") "
                                               required autocomplete=" off">
                                        @if ($errors->has('vehicle_register_date'))
                                            <label class="text-danger">{{ $errors->first('vehicle_register_date') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location3">@lang("$string_file.expire_date") <span
                                                    class="text-danger">*</span>
                                            :</label>
                                        <input type="text" class="form-control customDatePicker1"
                                               name="vehicle_expire_date"
                                               value="{{old('vehicle_expire_date',$expire_date)}}" placeholder=""
                                               required autocomplete="off">
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
                                        {!! Form::select('baby_seat',$yes_no,old('baby_seat',isset($bus['baby_seat']) ? $bus['baby_seat'] : 0),['class'=>'form-control','id'=>'baby_seat']) !!}
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
                                        {!! Form::select('wheel_chair',$yes_no,old('wheel_chair',isset($bus['wheel_chair']) ? $bus['wheel_chair'] : 0),['class'=>'form-control','id'=>'wheel_chair']) !!}
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
                                        {!! Form::select('ac_nonac',$yes_no,old('ac_nonac',isset($bus['ac_nonac']) ? $bus['ac_nonac'] : 0),['class'=>'form-control','id'=>'ac_nonac']) !!}
                                        @if ($errors->has('ac_nonac'))
                                            <label class="text-danger">{{ $errors->first('ac_nonac') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div id="bus-doc-segment">
                            {!! $vehicle_doc_segment !!}
                        </div>
                        @if(count($bus_services) > 0)
                            <br>
                            <h5 class="form-section">
                                <i class="fa fa-taxi"></i> @lang("$string_file.bus_services")
                            </h5>
                            <hr>
                            <div class="row">
                                @foreach($bus_services as $bus_service)
                                    <div class="col-md-3">
                                        <input type="checkbox" name="bus_services[]" value="{{ $bus_service->id }}"
                                               @if(in_array($bus_service->id, $selected_bus_services)) checked @endif
                                               id="bus_service_{{$bus_service->id}}"><label for="bus_service_{{$bus_service->id}}">
                                            <img src="{{ get_image($bus_service->icon, "bus_service", $bus_service->merchant_id) }}" class="w-p10" >
                                            {{ $bus_service->Name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <br>
                        <h5 class="form-section">
                            <i class="fa fa-taxi"></i> @lang("$string_file.additional") @lang("$string_file.bus_details")
                        </h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location3">@lang("$string_file.additional") @lang("$string_file.bus_details"):</label>
                                    <textarea class="form-control" id="additional_info" name="additional_info" rows="6" data-plugin="summernote">{!!isset($bus['additional_info'])? $bus['additional_info']:NULL!!}</textarea>
                                    @if ($errors->has('additional_info'))
                                        <label class="text-danger">{{ $errors->first('additional_info') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <br>
                        <h5 class="form-section">
                            <i class="fa fa-taxi"></i> @lang("$string_file.bus_policy")
                            <button class="btn btn-icon btn-primary float-right" style="margin:-3px"
                                    data-target="#myModal" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        </h5>
                        
                        <hr>
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            @if($vehicle_id == NULL || $edit_permission)
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                                </button>
                            @endif
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>

    </div>
    <div class="modal fade" id="myModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
    
          <!-- Modal Header -->
          <div class="modal-header">
            <h4 class="modal-title">Add Bus Policy</h4>
            <button type="button" class="close" data-dismiss="modal"></button>
          </div>
    
          <!-- Modal body -->
          <div class="modal-body">
        <form method="POST" action="{{ route('bus.policy_save') }}">
          @csrf
          <input type="hidden" class="form-control" id="bus_id" name="bus_id" placeholder=""
                               value="{{$vehicle_id}}">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="policy_name">Policy Name:</label>
                        <input type="text" class="form-control" id="policy_name" name="policy_name" placeholder=""
                               value="">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea class="form-control" id="description" name="description" rows="3" data-plugin="summernote"></textarea>
                    </div>
                    </div>
                </div>
            </div>
    
          <!-- Modal footer -->
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success" </button>Submit</button>
          </div>
          </form>
        </div>
      </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script>
        $(document).on('change', '#vehicle_type_id', function (e) {
            var vehicle_type = $("#vehicle_type_id option:selected").val();
            let country_area_id = $("#country_area_id option:selected").val();
            var token = $('[name="_token"]').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: "{{ route('bus.doc.segment') }}",
                data: {
                    country_area_id: country_area_id,
                    vehicle_type_id: vehicle_type
                },
                success: function (data) {
                    $('#bus-doc-segment').html(data);
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
        });
        $(document).on('change', '#country_area_id', function (e) {
            let country_area_id = $("#country_area_id option:selected").val();

            var token = $('[name="_token"]').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: "{{ route('bus.vehicle_type') }}",
                data: {
                    country_area_id: country_area_id,
                    segment_id: "<?php echo $segment_id ?>",
                },
                success: function (data) {
                    $('#vehicle_type_id').html(data);
                }
            });
        });
        @if(!empty($bus))
        vehicleModel();

        @endif

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
                    data: {
                        vehicle_type_id: vehicle_type_id,
                        vehicle_make_id: vehicle_make_id
                    },
                    success: function (data) {
                        $('#vehicle_model_id').html(data);
                    }
                });
            }
        }

    $(document).ready(function(){
        getDesignType();
    });

        function getDesignType(){
            let bus_design_types = <?php echo json_encode($bus_design_types); ?>;
            let  type = $("#type").val()
            let option = "";
            if(type=="LOWER"){
                let entry = Object.entries(bus_design_types)[0];
                option += `<option value="${entry[0]}">${entry[1]}</option>`
            }
            else{
                let keys = Object.keys(bus_design_types);
                let values = Object.values(bus_design_types);

                for (let i = 1; i < keys.length; i++) {
                     option += `<option value="${keys[i]}">${values[i]}</option>`
                }
            }
            $("#design_type").html(option);
        }
        
        
    </script>
@endsection
