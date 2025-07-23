@extends('merchant.layouts.main')
@section('content')
    <style>
        .impo-text {
            color: red;
            font-size: 15px;
            text-wrap: normal;
            display: none;
        }
    </style>
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('bus_booking.bus_stops') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                            @if(!empty($info_setting) && $info_setting->add_text != "")
                                <button class="btn btn-icon btn-primary float-right" style="margin-left:10px" data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                    <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-add-file" aria-hidden="true"></i>
                        {!! $bus_stop['title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$bus_stop['submit_url'],'class'=>'steps-validation wizard-notification']) !!}
                    @php
                        $id = $bus_stop_id = NULL;
                    @endphp

                    {!! Form::hidden('bus_stop_id',$bus_stop_id,['id'=>'bus_stop_id','readonly'=>true]) !!}

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="package_duation_name">
                                    @lang("$string_file.name") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('stop_name',old('stop_name',isset($bus_stop['data']->LanguageSingle->name) ? $bus_stop['data']->LanguageSingle->name : ''),['id'=>'stop_name','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('stop_name'))
                                    <label class="text-danger">{{ $errors->first('stop_name') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="package_duation_name">
                                    @lang("$string_file.service_type") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('service_type',old('service_type', $bus_stop['data']->ServiceType->ServiceName($bus_stop['data']['merchant_id'])." (".$bus_stop['data']->Segment->Name($bus_stop['data']['merchant_id']).")"),['id'=>'service_type','class'=>'form-control','readonly' => true,'placeholder'=>'']) !!}
                                @if ($errors->has('service_type'))
                                    <label class="text-danger">{{ $errors->first('service_type') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.status") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::select('status',$bus_stop['arr_status'],old('status',isset($bus_stop['data']->status) ? $bus_stop['data']->status:1),['id'=>'status','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('status'))
                                    <label class="text-danger">{{ $errors->first('status') }}</label>
                                @endif

                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="latitude">
                                    @lang("$string_file.latitude")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('latitude',old('latitude',isset($bus_stop['data']['latitude']) ? $bus_stop['data']['latitude'] : NULL),['id'=>'lat','class'=>'form-control','required'=>true,'readonly' => true]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.longitude")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('longitude',old('longitude',isset($bus_stop['data']['longitude']) ? $bus_stop['data']['longitude'] : NULL),['id'=>'lng','class'=>'form-control','required'=>true,'readonly' => true]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="emailAddress5">
                                    @lang("$string_file.address")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('address',old('address',isset($bus_stop['data']['address']) ? $bus_stop['data']['address'] : NULL),['id'=>'location','class'=>'form-control','required'=>true,'readonly' => true]) !!}
                                @if ($errors->has('address'))
                                    <label class="text-danger">{{ $errors->first('address') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" id="edit_lat_long" onclick="editLatLong(this)">
                                <label for="edit_lat_long">@lang("$string_file.edit_latitude_longitude")
                                    . </label>
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            {!! Form::label('searchInput', trans("$string_file.address"), ['class' => 'control-label']) !!}
                            <input id="searchInput" class="input-controls" type="text" placeholder="@lang(" $string_file.enter_address")">
                            <div class="map" id="map" style="width: 100%; height: 300px;"></div>
                        </div>
                        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyC7lIIgBajzx409vxmmY_CJPcRvDb114w4"></script>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="service_types">
                                    @lang("$string_file.pickup_drop_points") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('pickup_drop_point_ids[]',$bus_stop['pickup_drop_points_arr'],old('pickup_drop_point_ids', !empty($bus_stop['data']->BusPickupDropPoint) ? $bus_stop['data']->BusPickupDropPoint->pluck("id") : []),['id'=>'pickup_drop_point_ids','class'=>'form-control select2', 'multiple'=>true]) !!}
                                @if ($errors->has('pickup_drop_point_ids'))
                                    <label class="text-danger">{{ $errors->first('pickup_drop_point_ids') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-actions float-right">
                        @if($id == NULL || $edit_permission)
                            {!! Form::submit($bus_stop['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}
                        @else
                            <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                        @endif
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script type="text/javascript">

        function initialize() {
            var lat = "{{ isset($bus_stop['data']['latitude']) ? $bus_stop['data']['latitude'] : 28.4594965 }}";
            var long = "{{ isset($bus_stop['data']['longitude']) ? $bus_stop['data']['longitude'] : 77.02663830000006 }}";
            var latlng = new google.maps.LatLng(lat, long);
            var map = new google.maps.Map(document.getElementById('map'), {
                center: latlng,
                zoom: 19
            });
            var marker = new google.maps.Marker({
                map: map,
                position: latlng,
                draggable: true,
                anchorPoint: new google.maps.Point(0, -29)
            });
            var input = document.getElementById('searchInput');
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
            var geocoder = new google.maps.Geocoder();
            var autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', map);
            var infowindow = new google.maps.InfoWindow();
            autocomplete.addListener('place_changed', function () {
                infowindow.close();
                marker.setVisible(false);
                var place = autocomplete.getPlace();
                if (!place.geometry) {
                    window.alert("Autocomplete's returned place contains no geometry");
                    return;
                }

                // If the place has a geometry, then present it on a map.
                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }

                marker.setPosition(place.geometry.location);
                marker.setVisible(true);

                bindDataToForm(place.formatted_address, place.geometry.location.lat(), place.geometry.location.lng());
                infowindow.setContent(place.formatted_address);
                infowindow.open(map, marker);

            });
            // this function will work on marker move event into map
            google.maps.event.addListener(marker, 'dragend', function () {
                geocoder.geocode({'latLng': marker.getPosition()}, function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        if (results[0]) {
                            bindDataToForm(results[0].formatted_address, marker.getPosition().lat(), marker.getPosition().lng());
                            infowindow.setContent(results[0].formatted_address);
                            infowindow.open(map, marker);
                        }
                    }
                });
            });
        }

        function editLatLong(ss) {
            var checkValue = ss.checked ? 1 : 0;
            console.log(checkValue);
            if (checkValue == 1) {
                $('#lat').attr('readonly', false);
                $('#lng').attr('readonly', false);
                $('#location').attr('readonly', false);
            } else {
                $('#lat').attr('readonly', true);
                $('#lng').attr('readonly', true);
                $('#location').attr('readonly', true);
            }
        }

        $(document).on("click", "#edit_password", function () {
            $("#password").prop("disabled", true);
            if ($("#edit_password").prop("checked") == true) {
                $("#password").prop("disabled", false);
            }
        });

        function bindDataToForm(address, lat, lng) {
            document.getElementById('location').value = address;
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
        }

        google.maps.event.addDomListener(window, 'load', initialize);
    </script>
@endsection
