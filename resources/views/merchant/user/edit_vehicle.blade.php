@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('merchant.user.vehicle_list',['id'=>$user_vehicle->user_id])}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("common.edit") @lang("$string_file.vehicle")</h3>
                </header>
                <div class="panel-body container-fluid" id="validation">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data" ,files="true" ,
                          action="{{route('merchant.user.vehicle.update',['id'=>$user_vehicle->id])}}" autocomplete="false">
                    @csrf

                        <fieldset>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.vehicle") @lang("common.number")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="vehicle_number"
                                               name="vehicle_number"
                                               placeholder="@lang("common.enter") @lang("$string_file.vehicle") @lang("common.number")"
                                               value="{{ old('vehicle_number',isset( $user_vehicle->vehicle_number) ? $user_vehicle->vehicle_number : NULL) }}" required>
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
                                               value="{{ old('vehicle_color',isset( $user_vehicle->vehicle_color) ? $user_vehicle->vehicle_color : NULL) }} ">
                                        @if ($errors->has('vehicle_color'))
                                            <label class="text-danger">{{ $errors->first('vehicle_color') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle_image">
                                            @lang("$string_file.vehicle") @lang("common.image")
                                            <span class="text-danger">*</span>
                                        </label>
                                        @if(!empty($user_vehicle->vehicle_image))
                                            <a href="{{get_image($user_vehicle->vehicle_image,'user_vehicle_document',$user_vehicle->merchant_id)}}" target="_blank">@lang("common.view")</a>
                                        @endif
                                        <input type="file" class="form-control" id="vehicle_image"
                                               name="vehicle_image"
                                               placeholder="@lang("$string_file.vehicle") @lang("common.image")"  value="{{ $user_vehicle->vehicle_image ? $user_vehicle->vehicle_image : '' }} ">
                                        @if ($errors->has('vehicle_image'))
                                            <label class="text-danger">{{ $errors->first('vehicle_image') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle_number_plate_image">
                                            @lang("$string_file.vehicle") @lang("common.number")  @lang("$string_file.plate")  @lang("common.image")
                                            <span class="text-danger">*</span>
                                        </label>
                                        @if(!empty($user_vehicle->vehicle_number_plate_image))
                                            <a href="{{get_image($user_vehicle->vehicle_number_plate_image,'user_vehicle_document',$user_vehicle->merchant_id)}}" target="_blank">@lang("common.view")</a>
                                        @endif
                                        <input type="file" class="form-control" id="vehicle_number_plate_image"
                                               name="vehicle_number_plate_image" value="{{$user_vehicle->vehicle_number_plate_image ? $user_vehicle->vehicle_number_plate_image : ''}}"
                                               placeholder=" @lang("$string_file.vehicle") @lang("common.number")  @lang("$string_file.plate")  @lang("common.image")"
                                              >
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
