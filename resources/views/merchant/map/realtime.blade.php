@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @csrf
            <section id="gmaps-utils">
                <div class="panel panel-bordered">
                    <header class="panel-heading">
                        @if(count($merchant_segments) > 1)
                            <div class="panel-actions">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group" style="margin-top: 15px;">
                                            {!! Form::select('segment_id',$merchant_segments,[],['class'=>'form-control', 'id'=>'segment_id','data-placeholder'=>trans("$string_file.segment"), 'onchange'=>"getDriverLocationo()"]) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {!! Form::hidden('segment_id',array_key_first($merchant_segments)) !!}
                        @endif
                        <h3 class="panel-title"><i class=" wb-flag" aria-hidden="true"></i>
                            @lang("$string_file.real_time_map")
                        </h3>
                    </header>
                    <div class="panel-body container-fluid">
                        <div id="map" style="width: 100%;height: 550px;"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
    <script src="https://maps.googleapis.com/maps/api/js?key=<?=get_merchant_google_key(NULL, 'admin_backend');?>&libraries=visualization"></script>
@endsection
@section('js')
    <script src="{{ asset('js/map_icon.js')}}" type="text/javascript"></script>
    <script>
        let map;
        let markers = {};
        let marker;
        let markerslocations;
        let infowindow;
        var static_icon = "{{ asset('basic-images/car-2.png') }}";
        var numDeltas = 50;
        var delay = 60; //milliseconds

        //Load google map
        google.maps.event.addDomListener(window, 'load', initMap);

        function initMap() {
            var latlng = new google.maps.LatLng(8.7832, 34.5085);

            var myOptions = {
                zoom: 2,
                center: latlng,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
            };
            map = new google.maps.Map(document.getElementById("map"), myOptions);
            getDriverLocation();
        }

        function getDriverLocation(flag = true) {
            var token = $('[name="_token"]').val();
            var segment_id = null;
            if ($('#segment_id').val() != '') {
                segment_id = [$('#segment_id').val()];
            }
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: "{{ route('getDriverOnMap') }}",
                processData: false,
                data: {
                    segment_id: segment_id,
                },
                success: function (data) {
                    var data = JSON.parse(data);
                    drivers = data.map_markers;
                    if(flag == true){
                        changeCountryCanter(data.country);
                        createMarkers(drivers);
                    }else{
                        drivers.map(driver => {
                            // console.log(driver.marker_id);
                            // console.log(markers);
                            if (markers.hasOwnProperty(driver.marker_id)) {
                                // update marker
                                transition(driver);
                            } else {
                                // create marker
                                createSingleMarker(driver);
                            }
                        });
                    }
                }, error: function (e) {
                    console.log(e);
                }
            });
        }

        function createMarkers(drivers) {

            removeMarkers();

            drivers.map(driver => {
                var marker_icon = driver.marker_icon;
                var icon = {
                    url: marker_icon,
                    scaledSize: new google.maps.Size(50, 50), // scaled size
                    origin: new google.maps.Point(0, 0), // origin
                    anchor: new google.maps.Point(0, 0), // anchor
                };

                var markerlatlng = new google.maps.LatLng(parseFloat(driver.marker_latitude), parseFloat(driver.marker_longitude));
                var content = '<table><tr><td rowspan="4"><img src="' + driver.marker_image + '" height="60" width="60"></td></tr><tr><td>&nbsp;&nbsp;Email: </td><td><b>' + driver.marker_email + '</b></td></tr><tr><td>&nbsp;&nbsp;Mobile: </td><td><b>' + driver.marker_number + '</b></td></tr></table>';

                var marker = new google.maps.Marker({
                    map: map,
                    title: driver.marker_name,
                    animation: google.maps.Animation.DROP,
                    position: markerlatlng,
                    icon: icon, //driver.marker_icon
                });

                infowindow = new google.maps.InfoWindow();
                google.maps.event.addListener(marker, 'click', (function (marker, content, infowindow) {
                    return function () {
                        infowindow.setContent(content);
                        infowindow.open(map, marker);
                        map.panTo(this.getPosition());
                    };
                })(marker, content, infowindow));

                markers[driver.marker_id] = marker;
            })
        }

        function createSingleMarker(driver) {
            var icon = {
                url: driver.marker_icon,
                scaledSize: new google.maps.Size(50, 50), // scaled size
                origin: new google.maps.Point(0, 0), // origin
                anchor: new google.maps.Point(0, 0) // anchor
            };

            var markerlatlng = new google.maps.LatLng(parseFloat(driver.marker_latitude), parseFloat(driver.marker_longitude));
            var content = '<table><tr><td rowspan="4"><img src="' + driver.marker_image + '" height="60" width="60"></td></tr><tr><td>&nbsp;&nbsp;Email: </td><td><b>' + driver.marker_email + '</b></td></tr><tr><td>&nbsp;&nbsp;Mobile: </td><td><b>' + driver.marker_number + '</b></td></tr></table>';

            const marker = new google.maps.Marker({
                map: map,
                title: driver.marker_name,
                animation: google.maps.Animation.DROP,
                position: markerlatlng,
                icon : icon,
            });
            infowindow = new google.maps.InfoWindow();
            google.maps.event.addListener(marker, 'click', (function (marker, content, infowindow) {
                return function () {
                    infowindow.setContent(content);
                    infowindow.open(map, marker);
                    map.panTo(this.getPosition());
                };
            })(marker, content, infowindow));

            markers[driver.marker_id] = marker;

            console.log('New Driver Added');
        }

        function removeMarkers(){
            // Loop through markers and set map to null for each
            for (var i=0; i<markers.length; i++) {

                markers[i].setMap(null);
            }

            // Reset the markers array
            markers = [];
        }

        function changeCountryCanter(countryText) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': countryText}, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    map.setZoom(5);
                    map.setCenter(results[0].geometry.location);
                }
            });
        }

        function transition(data){
            let marker_icon = data.marker_icon;
            // var a = (async() => {
            //     marker_icon = await imageToDataURL(static_icon, 65);
            //     $("#test-img").prop("src", marker_icon);
            //     // console.log(marker_icon);
            //     // console.log("sdfsdf");
            //     return marker_icon;
            // })();
            // console.log(a);
            // var a = imageToDataURL(static_icon, 65).then(result => marker_icon = result);
            // console.log(a);

            let i = 0;
            let marker = markers[data.marker_id];
            let oldData = marker.position;
            const deltaLat = (parseFloat(data.marker_latitude) - oldData.lat())/numDeltas;
            const deltaLng = (parseFloat(data.marker_longitude) - oldData.lng())/numDeltas;
            var icon = {
                url: marker_icon,
                scaledSize: new google.maps.Size(50, 50), // scaled size
                origin: new google.maps.Point(0, 0), // origin
                anchor: new google.maps.Point(0, 0) // anchor
            };
            marker.setIcon(icon);
            moveMarker(marker, deltaLat , deltaLng , oldData.lat(), oldData.lng(),i);

            // console.log('Driver Moving');
        }

        function moveMarker(marker, deltaLat, deltaLng , lat , lng , i){
            lat += deltaLat;
            lng += deltaLng;
            var latlng = new google.maps.LatLng(lat, lng);
            marker.setPosition(latlng);
            if(i != numDeltas){
                i++;
                setTimeout(moveMarker, delay, marker, deltaLat, deltaLng , lat , lng , i);
            }
        }

        // function generateIcon(icon_url, deg) {
        //     if(deg <= 0){
        //         deg = 20;
        //     }
        //     return {
        //         url: RotateIcon
        //             .makeIcon(static_icon)
        //             .setRotation({deg: parseFloat(deg)})
        //             .getUrl()
        //     }
        // }

        // async function imageToDataURL(imageUrl, deg) {
        //     let img = await fetch(imageUrl, {
        //         method: 'GET',
        //         withCredentials: true,
        //         crossorigin: true,
        //         mode: 'no-cors',
        //     });
        //     img = await img.blob();
        //     let bitmap = await createImageBitmap(img);
        //     let canvas = document.createElement("canvas");
        //     let ctx = canvas.getContext("2d");
        //     canvas.width = bitmap.width;
        //     canvas.height = bitmap.height;
        //     // ctx.drawImage(bitmap, 0, 0, bitmap.width, bitmap.height);
        //
        //     var angle = deg * Math.PI / 180;
        //     var centerX = bitmap.width/2;
        //     var centerY = bitmap.height/2;
        //     ctx.clearRect(0, 0, bitmap.width, bitmap.height);
        //     ctx.save();
        //     ctx.translate(centerX, centerY);
        //     ctx.rotate(angle);
        //     ctx.translate(-centerX, -centerY);
        //     ctx.drawImage(bitmap, 0, 0, bitmap.width, bitmap.height);
        //     // canvas.drawImage(this.rImg, 0, 0);
        //     ctx.restore();
        //
        //     return canvas.toDataURL("image/png");
        // };

        // (async() => {
        //     let dataUrl = await imageToDataURL(static_icon, 90)
        //     $("#test-img").prop("src", dataUrl);
        //     // console.log(dataUrl)
        // })();

        setInterval(function() { getDriverLocation(false); }, 5000);
    </script>
@endsection
