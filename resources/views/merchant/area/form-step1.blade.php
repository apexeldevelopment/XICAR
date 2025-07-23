@extends('merchant.layouts.main')
@section('content')
    <style>
        em {
            color: red;
        }
    </style>
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->add_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin-left: 10px;"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{ route('countryareas.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" >
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class=" wb-user-plus" aria-hidden="true"></i>
                        @lang("$string_file.service_area") (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})</h3>
                </header>
                @php $display = true; $selected_doc = []; $id = NULL @endphp
                @if(isset($area->id) && !empty($area->id))
                @php
                    $display = false;
                    $selected_doc = $area->Documents
                        ->filter(fn($doc) => $doc->pivot->document_type == 1)
                        ->pluck('id')
                        ->toArray();
                    $id = $area->id;
                @endphp
            @endif
            

                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"  enctype="multipart/form-data" action="{{ route('countryareas.save',$id) }}" id="country-area-step1">
                        @csrf
                        {!! Form::hidden("id",$id,['class'=>'','id'=>'id']) !!}
                        <h5>
                            <i class="m-1 fa fa-map"></i>
                            @lang("$string_file.area_basic_configuration")
                        </h5>
                        <hr/>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.name")
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::text('name',old('name',isset($area->LanguageSingle) ? $area->LanguageSingle->AreaName : ''),['class'=>'form-control','id'=>'name','placeholder'=>'']) !!}
                                    @if ($errors->has('name'))
                                        <label class="text-danger">{{ $errors->first('name') }}</label>
                                    @endif
                                </div>
                            </div>

                            <input type="hidden" name="geofence_module" id="geofence_module" value="{{$config->geofence_module}}"/>
                            @if(isset($config->geofence_module) && $config->geofence_module == 1 && $display == true)
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="geofence_area">
                                            @lang("$string_file.geofence_area")<span class="text-danger">*</span>
                                        </label>
                                        {!! Form::select('is_geofence',get_status(true,$string_file),old('is_geofence',2),["class"=>"form-control","id"=>"is_geofence","required"=>true, "onchange"=>"fieldsAccGeofence(this.value)"]) !!}
                                        @if ($errors->has('is_geofence'))
                                            <label class="text-danger">{{ $errors->first('is_geofence')}}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-3" style="display:none" id="geofence_base_area_div">
                                <div class="form-group">
                                    <label for="service_area">
                                        @lang("$string_file.service_area")<span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('geofence_base_area',$area_list,null,["class"=>"form-control","id"=>"geofence_base_area","required"=>true]) !!}
                                    @if ($errors->has('geofence_base_area'))
                                        <label class="text-danger">{{ $errors->first('geofence_base_area')}}</label>
                                    @endif
                                </div>
                            </div>

                            <div id="newOpenstreet" style="width: 300px;">
                                <input type="text" class="form-control" id="google_area" name="google_area"placeholder="@lang("$string_file.enter_area")"style="padding:4px;margin-top: 5px;border: 4px solid;">
                            </div>

                            @if($display == true)
                                <div class="col-md-3" id="country_div">
                                    <div class="form-group field">
                                        <label for="location3">@lang("$string_file.country")<span class="text-danger">*</span></label>
                                        {!! Form::select('country',$countries,old('country'),["class"=>"form-control select","id"=>"country"]) !!}
                                        @if ($errors->has('country'))
                                            <label class="text-danger">{{ $errors->first('country') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-3" id="timezone_div">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.timezone")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control select2" name="timezone"
                                            id="timezone">
                                        @foreach($timezones as $time)
                                            <option value="{{ $time }}" @if($display == true && $time == old('timezone')) selected @else @if(isset($area->timezone) && $time == $area->timezone) selected @endif @endif> {{ $time }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('timezone'))
                                        <label class="text-danger">{{ $errors->first('timezone') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3" id="status_div">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.status")
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('status',$arr_status,old('status',isset($area->status) ? $area->status : NULL),['id'=>'','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('status'))
                                        <label class="text-danger">{{ $errors->first('status') }}</label>
                                    @endif
                                </div>
                            </div>
                            <input type="hidden" name="driver_wallet_status" id="driver_wallet_status" value="{{$config->driver_wallet_status}}"/>
                            @if($config->driver_wallet_status == 1)
                                <div class="col-md-3" id="minimum_wallet_amount_div">
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.driver_wallet_min_amount") <span class="text-danger">*</span>
                                        </label>
                                        {!! Form::number('minimum_wallet_amount',old('minimum_wallet_amount',isset($area->minimum_wallet_amount) ? $area->minimum_wallet_amount : 0),['class'=>'form-control','id'=>'minimum_wallet_amount','placeholder'=>""]) !!}
                                    </div>
                                </div>
                            @endif
                            <input type="hidden" name="no_driver_availabe_enable" id="no_driver_availabe_enable" value="{{$config->no_driver_availabe_enable}}"/>
                            @if(isset($config->no_driver_availabe_enable) && $config->no_driver_availabe_enable == 1)
                                <div class="col-md-3" id="auto_updradaion_div">
                                    <div class="form-group">
                                        <label for="emailAddress5">@lang("$string_file.auto_upgradation")<span class="text-danger">*</span> </label>
                                        {{ Form::select('auto_upgradetion', ['' => trans("$string_file.select"), '1' => trans("$string_file.enable"), '2' => trans("$string_file.disable")], old('auto_upgradetion',$area->auto_upgradetion ?? 2), ['class'=>'form-control','id' =>'auto_upgradetion'])  }}
                                    </div>
                                </div>
                            @endif
                            <input type="hidden" name="manual_downgrade_enable" id="manual_downgrade_enable" value="{{$config->manual_downgrade_enable}}"/>
                            @if(isset($config->manual_downgrade_enable) && $config->manual_downgrade_enable == 1)
                                <div class="col-md-3" id="manual_downgradation_div">
                                    <div class="form-group">
                                        <label for="emailAddress5">@lang("$string_file.manual_downgradation")<span class="text-danger">*</span> </label>
                                        {{ Form::select('manual_downgradation', ['' => trans("$string_file.select"), '1' => trans("$string_file.enable"), '2' => trans("$string_file.disable")], old('manual_downgradation',$area->manual_downgradation ?? 2), ['class'=>'form-control','id' =>'manual_downgradation','required'=>true])  }}
                                    </div>
                                </div>
                            @endif
                            @if($config->in_drive_enable == 1)
                                <div class="col-md-3" id="in_drive_enable_div">
                                    <div class="form-group">
                                        <label for="in_drive_enable">
                                            @lang("$string_file.in_drive_enable")<span class="text-danger">*</span>
                                        </label>
                                        {!! Form::select('in_drive_enable',['1' => trans("$string_file.enable"), '2' => trans("$string_file.disable")],old('in_drive_enable', isset($area->in_drive_enable) ? $area->in_drive_enable : 2),["class"=>"form-control","id"=>"in_drive_enable","required"=>true]) !!}
                                        @if ($errors->has('in_drive_enable'))
                                            <label class="text-danger">{{ $errors->first('in_drive_enable')}}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                           

                            @if ($Applicationconfig->local_citizen_foreigner_documents == 1)
                                <div class="col-md-3" id="personal_document_div">
                                    <div class="form-group">
                                        <label for="local_citizen_documents">@lang("$string_file.local_citizen_documents") <span class="text-danger">*</span></label>
                                        {!! Form::select('local_citizen_documents[]',$documents,old('local_citizen_documents',$CitizenDocuments['localCitizenDocuments']),["class"=>"form-control select2","id"=>"local_citizen_documents","multiple"=>true,'required'=>true]) !!}
                                        @if ($errors->has('local_citizen_documents'))
                                            <label class="text-danger">{{ $errors->first('local_citizen_documents') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3" id="personal_document_div">
                                    <div class="form-group">
                                        <label for="foreigner_documents">@lang("$string_file.foreigner_documents") <span class="text-danger">*</span></label>
                                        {!! Form::select('foreigner_documents[]',$documents,old('foreigner_documents',$CitizenDocuments['foreignerDocuments']),["class"=>"form-control select2","id"=>"foreigner_documents","multiple"=>true,'required'=>true]) !!}
                                        @if ($errors->has('foreigner_documents'))
                                            <label class="text-danger">{{ $errors->first('foreigner_documents') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @else
                            <div class="col-md-3" id="personal_document_div">
                                <div class="form-group">
                                    <label for="document">@lang("$string_file.driver") @lang("$string_file.personal_document") <span class="text-danger">*</span></label>
                                    {!! Form::select('driver_document[]',$documents,old('driver_document',$selected_doc),["class"=>"form-control select2","id"=>"document","multiple"=>true,'required'=>true]) !!}
                                    @if ($errors->has('driver_document'))
                                        <label class="text-danger">{{ $errors->first('driver_document') }}</label>
                                    @endif
                                </div>
                            </div>
                            @endif
                            
                            <div class="col-md-3" id="payment_method_div">
                                <div class="form-group">
                                    <label for="payment_method">
                                     @lang("$string_file.payment_method")<span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('payment_method[]',$payment_method,old('payment_method',$selected_payment_method),["class"=>"form-control select2","id"=>"payment_method","multiple"=>true,'required'=>true]) !!}
                                    @if ($errors->has('payment_method'))
                                        <label class="text-danger">{{ $errors->first('payment_method') }}</label>
                                    @endif
                                </div>
                            </div>
                            @if($config->driver_guarantor_details == 1)
                                <div class="col-md-3" id="guarantor_details_div">
                                    <div class="form-group">
                                        <label for="guarantor_details">
                                            @lang("$string_file.guarantor") @lang("$string_file.details")<span class="text-danger">*</span>
                                        </label>
                                        {!! Form::select('need_driver_guarantor_details',[""=> "select" ,"1"=>trans("$string_file.required"), "2"=>trans("$string_file.not")." ".trans("$string_file.required")],old('need_driver_guarantor_details',$need_driver_guarantor_details),["class"=>"form-control","id"=>"need_driver_guarantor_details",'required'=>true]) !!}
                                        @if ($errors->has('need_driver_guarantor_details'))
                                            <label class="text-danger">{{ $errors->first('need_driver_guarantor_details') }}</label>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <input type="hidden" name="driver_cash_limit" id="driver_cash_limit" value="{{$config->driver_cash_limit}}"/>
                            @if($config->driver_cash_limit == 1)
                                <div class="col-md-4" id="driver_cash_limit_amount_div">
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.driver_cash_limit_amount")
                                            <span class="text-danger">*</span>
                                        </label>
                                        {!! Form::number("driver_cash_limit_amount",old("driver_cash_limit_amount",isset($area->driver_cash_limit_amount) ? $area->driver_cash_limit_amount : NULL),["step"=>"0.01", "min"=>"0","class"=>"form-control", "id"=>"driver_cash_limit_amount","placeholder"=>"","required"=>true]) !!}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="emailAddress5">
                                    @lang("$string_file.draw_map")
                                    <span class="text-danger">*</span>
                                </label>
                                <div id="polygons" style="height: 400px;width: 100%"></div>
                                @if(!empty($id))
                                    <br>
                                    <span class="text-danger">@lang("$string_file.note") :- @lang("$string_file.service_area_document_warning")</span>
                                @endif
                                <input type="hidden" class="form-control " id="lat" name="lat">
                                @if ($errors->has('lat'))
                                    <label class="text-danger">{{ $errors->first('lat') }}</label>
                                @endif
                            </div>
                        </div>
                        <hr/>
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            @if($id == NULL || $edit_permission)
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i>@lang("$string_file.save")
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
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo get_merchant_google_key(NULL,'admin_backend'); ?>&libraries=places,drawing"></script>
    @if($display)
        <script>
            var map;
            var polygonArray = [];
            let inputSerach;
            let polygon;
            var drawingManager;
            let triangleCoords = [];
            var AreaLatlong = [];
            var bounds = new google.maps.LatLngBounds();

            function initMap() {
                map = new google.maps.Map(
                    document.getElementById("polygons"), {
                        center: new google.maps.LatLng(37.4419, -122.1419),
                        zoom: 10,
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    });

                drawingManager = new google.maps.drawing.DrawingManager({
                    drawingMode: google.maps.drawing.OverlayType.POLYGON,
                    drawingControl: true,
                    drawingControlOptions: {
                        position: google.maps.ControlPosition.TOP_CENTER,
                        drawingModes: ['polygon']
                    },
                    polygonOptions: {
                        fillColor: '#93BE52',
                        fillOpacity: 0.5,
                        strokeWeight: 2,
                        strokeColor: '#000000',
                        clickable: false,
                        editable: true,
                        draggable: true,
                        zIndex: 1
                    }
                });
                drawingManager.setMap(map);
                var options = {
                    types: ['(cities)'],
                };
                inputSerach = document.getElementById('newOpenstreet');
                autoPlace = document.getElementById('google_area');
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(inputSerach);
                var autocomplete = new google.maps.places.Autocomplete(autoPlace, options);
                autocomplete.bindTo('bounds', map);
                autocomplete.addListener('place_changed', function () {
                    var place = autocomplete.getPlace();
                    if (!place.geometry) {
                        window.alert("Autocomplete's returned place contains no geometry");
                        return;
                    }
                    map.setCenter(place.geometry.location);
                    map.setZoom(10);
                    var shortName = place.address_components[0] && place.address_components[0].short_name || '';
                    var long_name = place.address_components[0] && place.address_components[0].long_name || '';
                    var url = "https://nominatim.openstreetmap.org/search.php?polygon_geojson=1&format=json&q=" + shortName;
                    $.getJSON(url, function (result) {
                        console.log(result);
                        var arrayLength = result.length;
                        document.getElementById('lat').value = "";
                        for (var i = 0; i < polygonArray.length; i++) {
                            polygonArray[i].setMap(null);
                        }
                        for (var i = 0; i < arrayLength; i++) {
                            if (result[i].geojson.type === "Polygon" || result[i].geojson.type === "MultiPolygon") {
                                var PlaceId = result[i].place_id;
                                break;
                            }
                        }
                        if (PlaceId) {
                            var bounds = new google.maps.LatLngBounds();
                            var url = "https://nominatim.openstreetmap.org/details.php?polygon_geojson=1&format=json&place_id=" + PlaceId;
                            $.getJSON(url, function (result) {
                                var data;
                                if (result.geometry.type === "Polygon") {
                                    data = result.geometry.coordinates[0];
                                } else if (result.geometry.type === "MultiPolygon") {
                                    data = result.geometry.coordinates[0][0];
                                } else {
                                }
                                if (data) {
                                    var myObject = JSON.stringify(data);
                                    var count = Object.keys(myObject).length;
                                    console.log('object has a length of ' + count);

                                    triangleCoords = [];
                                    for (var i = 0; i < data.length; i++) {
                                        item = {}
                                        item["latitude"] = data[i][1].toString();
                                        item["longitude"] = data[i][0].toString();
                                        AreaLatlong.push(item);
                                        triangleCoords.push(new google.maps.LatLng(data[i][1], data[i][0]));
                                    }
                                    for (i = 0; i < triangleCoords.length; i++) {
                                        bounds.extend(triangleCoords[i]);
                                    }
                                    var latlng = bounds.getCenter();
                                    polygon = new google.maps.Polygon({
                                        paths: triangleCoords,
                                        strokeColor: '#FF0000',
                                        draggable: true,
                                        editable: true,
                                        strokeOpacity: 0.8,
                                        strokeWeight: 2,
                                        fillColor: '#FF0000',
                                        fillOpacity: 0.35
                                    });
                                    polygonArray.push(polygon);
                                    if (count > 50000) {
                                        alert("This area can't be draw. Please create manually.");
                                    }else{
                                        polygon.setMap(map);
                                    }
                                    map.fitBounds(bounds);
                                    map.setCenter(latlng)
                                    drawingManager.setDrawingMode(null);
                                    drawingManager.setOptions({
                                        // drawingControl: false
                                    });
                                    let NewJson = JSON.stringify(AreaLatlong);
                                    document.getElementById('lat').value = NewJson;
                                    AreaLatlong = [];
                                }
                            });
                        }
                    });
                });
                var centerControlDiv = document.createElement('div');
                var centerControl = new CenterControl(centerControlDiv, map);
                centerControlDiv.index = 1;
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(centerControlDiv);

                google.maps.event.addListener(drawingManager, 'polygoncomplete', function (polygon) {
                    for (var i = 0; i < polygon.getPath().getLength(); i++) {
                        // document.getElementById('lat').value += polygon.getPath().getAt(i).toUrlValue(6) + "|";
                        var xy = polygon.getPath().getAt(i);
                        item = {}
                        item["latitude"] = xy.lat().toString();
                        item["longitude"] = xy.lng().toString();
                        AreaLatlong.push(item);
                    }
                    let NewJson = JSON.stringify(AreaLatlong);
                    document.getElementById('lat').value = NewJson;
                    AreaLatlong = [];
                    polygonArray.push(polygon);
                    drawingManager.setDrawingMode(null);
                    drawingManager.setOptions({
                        // drawingControl: false
                    });
                    google.maps.event.addListener(polygon.getPath(), "insert_at", function () {
                        for (var i = 0; i < polygon.getPath().getLength(); i++) {
                            // document.getElementById('lat').value += polygon.getPath().getAt(i).toUrlValue(6) + "|";
                            var xy = polygon.getPath().getAt(i);
                            item = {}
                            item["latitude"] = xy.lat().toString();
                            item["longitude"] = xy.lng().toString();
                            AreaLatlong.push(item);
                        }
                        let NewJson = JSON.stringify(AreaLatlong);
                        document.getElementById('lat').value = NewJson;
                        AreaLatlong = [];
                    });
                    google.maps.event.addListener(polygon.getPath(), "set_at", function () {
                        for (var i = 0; i < polygon.getPath().getLength(); i++) {
                            // document.getElementById('lat').value += polygon.getPath().getAt(i).toUrlValue(6) + "|";
                            var xy = polygon.getPath().getAt(i);
                            item = {}
                            item["latitude"] = xy.lat().toString();
                            item["longitude"] = xy.lng().toString();
                            AreaLatlong.push(item);
                        }
                        let NewJson = JSON.stringify(AreaLatlong);
                        document.getElementById('lat').value = NewJson;
                        AreaLatlong = [];
                    });
                });

                google.maps.event.addListener(drawingManager, "drawingmode_changed", function () {
                    if (drawingManager.getDrawingMode() != null) {
                        document.getElementById('lat').value = "";
                        for (var i = 0; i < polygonArray.length; i++) {
                            polygonArray[i].setMap(null);
                        }
                        polygonArray = [];
                        AreaLatlong = [];
                    }
                });
            }
            function getEventTarget(e) {
                e = e || window.event;
                return e.target || e.srcElement;
            }
            function openStreetMap() {
                var query = $('#google_area').val();
                var url = "https://nominatim.openstreetmap.org/search.php?polygon_geojson=1&format=json&q=" + query;
                $.getJSON(url, function (result) {
                    var arrayLength = result.length;
                    $('.list-gpfrm').empty();
                    for (var i = 0; i < arrayLength; i++) {
                        var myhtml = "<li value=" + result[i].place_id + ">" + result[i].display_name + "</li>";
                        $(".list-gpfrm").append(myhtml);
                    }
                });
            }

            function changeCanter(s) {
                var country = s[s.selectedIndex].id;
                if (country != "") {
                    var geocoder;
                    geocoder = new google.maps.Geocoder();
                    geocoder.geocode({'address': country}, function (results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            //alert(results[0].geometry.location);
                            map.setZoom(6);
                            map.setCenter(results[0].geometry.location)
                        }
                    });
                }
            }

            function CenterControl(controlDiv, map) {
                var controlUI = document.createElement('div');
                controlUI.style.backgroundColor = '#fff';
                controlUI.style.border = '2px solid #fff';
                controlUI.style.borderRadius = '3px';
                controlUI.style.marginRight = '1px';
                controlUI.style.marginTop = '5px';
                controlUI.style.boxShadow = '0 2px 6px rgba(0,0,0,.3)';
                controlUI.style.cursor = 'pointer';
                controlUI.style.marginBottom = '22px';
                controlUI.style.textAlign = 'center';
                controlUI.title = 'Delete Polygon';
                controlDiv.appendChild(controlUI);
                var controlText = document.createElement('div');
                controlText.style.color = 'rgb(25,25,25)';
                controlText.style.fontFamily = 'Roboto,Arial,sans-serif';
                controlText.style.fontSize = '16px';
                controlText.style.lineHeight = '20px';
                controlText.style.paddingLeft = '5px';
                controlText.style.paddingRight = '5px';
                controlText.innerHTML = '<i class="fa fa-trash" aria-hidden="true"></i>';
                controlUI.appendChild(controlText);

                // Setup the click event listeners: simply set the map to Chicago.
                controlUI.addEventListener('click', function () {
                    document.getElementById('lat').value = "";
                    for (var i = 0; i < polygonArray.length; i++) {
                        polygonArray[i].setMap(null);
                    }
                    polygonArray = [];
                    AreaLatlong = [];
                });

            }

            initMap();

    </script>
    @else
        <script>
            var map;
            let polygon;
            var NewJson;
            var polygonArray = [];
            let data = {!! $area->AreaCoordinates !!};
            let triangleCoords = [];
            var bounds = new google.maps.LatLngBounds();
            var drawingManager;
            var AreaLatlong = [];

            function initMap() {
                map = new google.maps.Map(
                    document.getElementById("polygons"), {
                        center: new google.maps.LatLng(data[0].latitude, data[0].longitude),
                        zoom: 8,
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    });

                drawingManager = new google.maps.drawing.DrawingManager({
                    drawingMode: google.maps.drawing.OverlayType.POLYGON,
                    drawingControl: true,
                    drawingControlOptions: {
                        position: google.maps.ControlPosition.TOP_CENTER,
                        drawingModes: ['polygon']
                    },
                    polygonOptions: {
                        fillColor: '#93BE52',
                        fillOpacity: 0.5,
                        strokeWeight: 2,
                        strokeColor: '#000000',
                        clickable: false,
                        editable: true,
                        draggable: true,
                        zIndex: 1
                    }
                });

                var centerControlDiv = document.createElement('div');
                var centerControl = new CenterControl(centerControlDiv, map);
                centerControlDiv.index = 1;
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(centerControlDiv);
                for (var i = 0; i < data.length; i++) {
                    triangleCoords.push(new google.maps.LatLng(data[i].latitude, data[i].longitude));
                }
                for (i = 0; i < triangleCoords.length; i++) {
                    bounds.extend(triangleCoords[i]);
                }
                var latlng = bounds.getCenter();
                map.setCenter(latlng)
                polygon = new google.maps.Polygon({
                    paths: triangleCoords,
                    strokeColor: '#FF0000',
                    draggable: true,
                    editable: true,
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: '#FF0000',
                    fillOpacity: 0.35
                });
                polygon.setMap(map);
                polygonArray.push(polygon);
                map.fitBounds(bounds);
                google.maps.event.addListener(polygon.getPath(), "insert_at", getPolygonCoords);
                google.maps.event.addListener(polygon.getPath(), "set_at", getPolygonCoords);
                inputSerach = document.getElementById('newOpenstreet');
                autoPlace = document.getElementById('google_area');
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(inputSerach);
                var autocomplete = new google.maps.places.Autocomplete(autoPlace);
                autocomplete.bindTo('bounds', map);
                autocomplete.addListener('place_changed', function () {
                    var place = autocomplete.getPlace();
                    if (!place.geometry) {
                        window.alert("Autocomplete's returned place contains no geometry");
                        return;
                    }
                    map.setCenter(place.geometry.location);
                    map.setZoom(10);
                    var shortName = place.address_components[0] && place.address_components[0].short_name || '';
                    var long_name = place.address_components[0] && place.address_components[0].long_name || '';
                    var url = "https://nominatim.openstreetmap.org/search.php?polygon_geojson=1&format=json&q=" + shortName;
                    $.getJSON(url, function (result) {
                        var arrayLength = result.length;
                        document.getElementById('lat').value = "";
                        for (var i = 0; i < polygonArray.length; i++) {
                            polygonArray[i].setMap(null);
                        }
                        for (var i = 0; i < arrayLength; i++) {
                            if (result[i].geojson.type === "Polygon" || result[i].geojson.type === "MultiPolygon") {
                                var PlaceId = result[i].place_id;
                                break;
                            }
                        }
                        if (PlaceId) {
                            var bounds = new google.maps.LatLngBounds();
                            var url = "https://nominatim.openstreetmap.org/details.php?polygon_geojson=1&format=json&place_id=" + PlaceId;
                            $.getJSON(url, function (result) {
                                var data;
                                if (result.geometry.type === "Polygon") {
                                    data = result.geometry.coordinates[0];
                                } else if (result.geometry.type === "MultiPolygon") {
                                    data = result.geometry.coordinates[0][0];
                                } else {
                                }
                                if (data) {
                                    triangleCoords = [];
                                    for (var i = 0; i < data.length; i++) {
                                        item = {}
                                        item["latitude"] = data[i][1].toString();
                                        item["longitude"] = data[i][0].toString();
                                        AreaLatlong.push(item);
                                        triangleCoords.push(new google.maps.LatLng(data[i][1], data[i][0]));
                                    }
                                    for (i = 0; i < triangleCoords.length; i++) {
                                        bounds.extend(triangleCoords[i]);
                                    }
                                    var latlng = bounds.getCenter();
                                    polygon = new google.maps.Polygon({
                                        paths: triangleCoords,
                                        strokeColor: '#FF0000',
                                        draggable: true,
                                        editable: true,
                                        strokeOpacity: 0.8,
                                        strokeWeight: 2,
                                        fillColor: '#FF0000',
                                        fillOpacity: 0.35
                                    });
                                    polygonArray.push(polygon);
                                    polygon.setMap(map);
                                    map.fitBounds(bounds);
                                    map.setCenter(latlng)
                                    drawingManager.setDrawingMode(null);
                                    drawingManager.setOptions({
                                        // drawingControl: false
                                    });
                                    let NewJson = JSON.stringify(AreaLatlong);
                                    document.getElementById('lat').value = NewJson;
                                    AreaLatlong = [];
                                }
                            });
                        }
                    });


                });
            }

            function getPolygonCoords() {
                var len = polygon.getPath().getLength();
                var AreaLatlong = [];
                for (var i = 0; i < polygon.getPath().getLength(); i++) {
                    var xy = polygon.getPath().getAt(i);
                    item = {}
                    item["latitude"] = xy.lat().toString();
                    item["longitude"] = xy.lng().toString();
                    AreaLatlong.push(item);
                }
                NewJson = JSON.stringify(AreaLatlong);
                document.getElementById('lat').value = NewJson;
                AreaLatlong = [];
            }

            function CenterControl(controlDiv, map) {
                var controlUI = document.createElement('div');
                controlUI.style.backgroundColor = '#fff';
                controlUI.style.border = '2px solid #fff';
                controlUI.style.borderRadius = '3px';
                controlUI.style.marginRight = '1px';
                controlUI.style.marginTop = '5px';
                controlUI.style.boxShadow = '0 2px 6px rgba(0,0,0,.3)';
                controlUI.style.cursor = 'pointer';
                controlUI.style.marginBottom = '22px';
                controlUI.style.textAlign = 'center';
                controlUI.title = 'Delete Polygon';
                controlUI.id = 'delete_polygon';
                controlDiv.appendChild(controlUI);
                var controlText = document.createElement('div');
                controlText.style.color = 'rgb(25,25,25)';
                controlText.style.fontFamily = 'Roboto,Arial,sans-serif';
                controlText.style.fontSize = '16px';
                controlText.style.lineHeight = '20px';
                controlText.style.paddingLeft = '5px';
                controlText.style.paddingRight = '5px';
                controlText.innerHTML = '<i class="fa fa-trash" aria-hidden="true"></i>';
                controlUI.appendChild(controlText);
                var count = 0;
                // Setup the click event listeners: simply set the map to Chicago.
                controlUI.addEventListener('click', function () {
                    $('#delete_polygon').hide();
                    count += 1;
                    polygon.setMap(null);
                    if (count <= 1){
                        drawingManager = new google.maps.drawing.DrawingManager({
                            drawingMode: google.maps.drawing.OverlayType.POLYGON,
                            drawingControl: true,
                            drawingControlOptions: {
                                position: google.maps.ControlPosition.TOP_CENTER,
                                drawingModes: ['polygon']
                            },
                            polygonOptions: {
                                fillColor: '#93BE52',
                                fillOpacity: 0.5,
                                strokeWeight: 2,
                                strokeColor: '#000000',
                                clickable: false,
                                editable: true,
                                draggable: true,
                                zIndex: 1
                            }
                        });
                        drawingManager.setMap(map);

                        google.maps.event.addListener(drawingManager, 'polygoncomplete', function (polygon) {
                            for (var i = 0; i < polygon.getPath().getLength(); i++) {
                                // document.getElementById('lat').value += polygon.getPath().getAt(i).toUrlValue(6) + "|";
                                var xy = polygon.getPath().getAt(i);
                                item = {}
                                item["latitude"] = xy.lat().toString();
                                item["longitude"] = xy.lng().toString();
                                AreaLatlong.push(item);
                            }
                            let NewJson = JSON.stringify(AreaLatlong);
                            document.getElementById('lat').value = NewJson;
                            AreaLatlong = [];
                            polygonArray.push(polygon);
                            drawingManager.setDrawingMode(null);
                            drawingManager.setOptions({
                                // drawingControl: false
                            });

                            google.maps.event.addListener(polygon.getPath(), "insert_at", function () {
                                for (var i = 0; i < polygon.getPath().getLength(); i++) {
                                    // document.getElementById('lat').value += polygon.getPath().getAt(i).toUrlValue(6) + "|";
                                    var xy = polygon.getPath().getAt(i);
                                    item = {}
                                    item["latitude"] = xy.lat().toString();
                                    item["longitude"] = xy.lng().toString();
                                    AreaLatlong.push(item);
                                }
                                let NewJson = JSON.stringify(AreaLatlong);
                                document.getElementById('lat').value = NewJson;
                                AreaLatlong = [];
                            });
                            google.maps.event.addListener(polygon.getPath(), "set_at", function () {
                                for (var i = 0; i < polygon.getPath().getLength(); i++) {
                                    // document.getElementById('lat').value += polygon.getPath().getAt(i).toUrlValue(6) + "|";
                                    var xy = polygon.getPath().getAt(i);
                                    item = {}
                                    item["latitude"] = xy.lat().toString();
                                    item["longitude"] = xy.lng().toString();
                                    AreaLatlong.push(item);
                                }
                                let NewJson = JSON.stringify(AreaLatlong);
                                document.getElementById('lat').value = NewJson;
                                AreaLatlong = [];
                            });
                        });
                        google.maps.event.addListener(drawingManager, "drawingmode_changed", function () {
                            if (drawingManager.getDrawingMode() != null) {
                                document.getElementById('lat').value = "";
                                for (var i = 0; i < polygonArray.length; i++) {
                                    polygonArray[i].setMap(null);
                                }
                                polygonArray = [];
                                AreaLatlong = [];
                            }
                        });
                    }
                });
            }

            initMap();
        </script>
    @endif

    <script>
        $(document).on('keypress','#manual_toll_price',function (event) {
            if ( event.keyCode == 46 || event.keyCode == 8 ) {
            }
            else {
                if (event.keyCode < 48 || event.keyCode > 57 ) {
                    event.preventDefault();
                }
            }
        });

        function changeBill(type) {
            switch (type) {
                case "1":
                    document.getElementById('start_time').style.display = 'block';
                    document.getElementById('start_day').style.display = 'none';
                    document.getElementById('start_date').style.display = 'none';
                    break;
                case "2":
                    document.getElementById('start_time').style.display = 'none';
                    document.getElementById('start_day').style.display = 'block';
                    document.getElementById('start_date').style.display = 'none';
                    break;
                case "3":
                    document.getElementById('start_time').style.display = 'none';
                    document.getElementById('start_day').style.display = 'none';
                    document.getElementById('start_date').style.display = 'block';
                    break;
                default:
                    document.getElementById('start_time').style.display = 'none';
                    document.getElementById('start_day').style.display = 'none';
                    document.getElementById('start_date').style.display = 'none';
            }
        }


        // segmentSetting();
        // $(document).ready(function(){
        //  $(document).on("click",".area_segment",function(){
        //      segmentSetting();
        //  })
        // });
        // function segmentSetting()
        // {
        //     var  segment = [];
        //     $(".services").hide();
        //     $('.other_segment').prop('required', false);
        //     $.each($(".area_segment"), function(){
        //         var segment_id = $(this).val();
        //         if(this.checked)
        //         {
        //              segment.push(segment_id);
        //             $("#segment_" + segment_id).show();
        //             // $('.segment_service_'+ segment_id).prop('required', true);
        //             $('.segment_service'+ segment_id).prop('checked', true);
        //         }
        //         else
        //         {
        //             $('.segment_service'+ segment_id).prop('checked', false);
        //         }
        //     });
        //     // console.log(segment)
        //     if($.inArray("1", segment) > -1  || $.inArray("2", segment) > -1)
        //     {
        //     }
        //
        // }


        document.addEventListener("DOMContentLoaded", function() {
          var geofenceElement = document.getElementById("is_geofence");
          if (geofenceElement) {
            fieldsAccGeofence(geofenceElement.value);
          }
        });

        function fieldsAccGeofence(value){
            if(value == "1"){
                $("#country_div").hide();
                $("#timezone_div").hide();
                $("#status_div").hide();
                $("#driver_wallet_status").hide();
                $("#minimum_wallet_amount_div").hide();
                $("#no_driver_availabe_enable").hide();
                $("#auto_updradaion_div").hide();
                $("#manual_downgrade_enable").hide();
                $("#manual_downgradation_div").hide();
                $("#in_drive_enable_div").hide();
                $("#payment_method_div").hide();
                $("#guarantor_details_div").hide();
                $("#guarantor_details_div").hide();
                $("#personal_document_div").hide();
                $("#geofence_base_area_div").css("display", "block");
            }
            else{
                $("#country_div").show();
                $("#timezone_div").show();
                $("#status_div").show();
                $("#driver_wallet_status").show();
                $("#minimum_wallet_amount_div").show();
                $("#no_driver_availabe_enable").show();
                $("#auto_updradaion_div").show();
                $("#manual_downgrade_enable").show();
                $("#manual_downgradation_div").show();
                $("#in_drive_enable_div").show();
                $("#payment_method_div").show();
                $("#guarantor_details_div").show();
                $("#guarantor_details_div").show();
                $("#personal_document_div").show();
                $("#geofence_base_area_div").css("display", "none");
            }
        }

    </script>
@endsection
