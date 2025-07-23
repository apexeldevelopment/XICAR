@extends('merchant.layouts.main')
@section('content')
    <style>
        #map {
            height: 100%;
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions"></div>
                            <h3 class="panel-title"><i class="fas fa-fw fa-map" aria-hidden="true"></i> @lang('admin.driver_tracking_map')</h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="gmaps-utils">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-content collapse show">
                                    </div>
                                    <div class="card-body">
                                        <div id="map" style="width: 100%;height: 550px;"></div>
                                        <input type="hidden" id="vehicle_type_image" value="{{view_config_image($booking->VehicleType->vehicleTypeMapImage)}}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <br>
@endsection
@section('js')  
    <script>

        @php

        $driver = $booking->Driver;
        $lat = $driver->current_latitude;
        $long = $driver->current_longitude;
        $working_with_redis = $booking->Merchant->ApplicationConfiguration->working_with_redis;

        if($working_with_redis == 1){
            $driver_data = getDriverCurrentLatLong($driver);
            $lat =  $driver_data['latitude'];
            $long = $driver_data['longitude'];
        }

        @endphp
        var map;
        var position = [{{$booking->pickup_latitude}},{{$booking->pickup_longitude}}];
        var vehicleTypeImage = document.getElementById('vehicle_type_image').value;
    
        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: {{$booking->pickup_latitude}}, lng: {{$booking->pickup_longitude}}},
                zoom: 12
            });
            var driverLocation = new google.maps.LatLng({{$lat}},{{$long}});
            var icon = {
                url: vehicleTypeImage, // url
                scaledSize: new google.maps.Size(50, 50), // scaled size
            };
            marker = new google.maps.Marker({
                position: driverLocation,
                map: map,
                animation: google.maps.Animation.DROP,
                icon: icon
            });
            var refreshId = setInterval(function () {
                $.ajax({
                    type: "GET",
                    dataType: "json",
                    url: "{{route('driverTrack')}}",
                    data: 'driver_id=' + {{ $booking->Driver->id}} ,
                    success:
                        function (data) {
                            var lat = data.current_latitude;
                            var long = data.current_longitude;
                            var result = [lat, long];
                            transition(result);
                            var latLng = new google.maps.LatLng(lat, long);
                            map.panTo(latLng);
                        }
                });
            }, 5000);
            var numDeltas = 100;
            var delay = 100; //milliseconds
            var i = 0;
            var deltaLat;
            var deltaLng;
    
            function transition(result) {
                i = 0;
                deltaLat = (result[0] - position[0]) / numDeltas;
                deltaLng = (result[1] - position[1]) / numDeltas;
    
                moveMarker();
    
            }
    
            function moveMarker() {
                position[0] += deltaLat;
                position[1] += deltaLng;
                var latlng = new google.maps.LatLng(position[0], position[1]);
                marker.setPosition(latlng);
                if (i != numDeltas) {
                    i++;
                    setTimeout(moveMarker, delay);
                }
            }
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{$booking->Merchant->BookingConfiguration->google_key}}&callback=initMap"
            async defer></script>
@endsection