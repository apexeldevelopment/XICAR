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
                        {{$bus->bus_name}} | {{$bus->vehicle_number}} | @lang("$string_file.seat_config")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" enctype="multipart/form-data"
                          onSubmit="return validateForm();" id="driver-bus-form" , name="driver-vehicle-form"
                          action="{{ route('merchant.bus_booking.bus.seat_config',["id" => $bus->id]) }}">
                        @csrf
                        {!! Form::hidden('bus_id',$bus->id,['class'=>'']) !!}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type_txt">@lang("$string_file.type") :</label>
                                    {!! Form::text('type_txt', $bus_types[$bus->type],['class'=>'form-control','id'=>'type_txt', "disabled" => true]) !!}
                                    {!! Form::hidden('type', $bus->type,['class'=>'form-control','id'=>'type']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    @php
                                        preg_match_all('!\d+!', $bus_design_types[$bus->design_type], $matches);
//                                        echo '<pre>';print_r($matches);die;
                                        $upper_count = 0;
                                        $lower_count = $matches[0][0]+$matches[0][1];
                                        if($bus->type == "LOWER_UPPER"){
                                            $upper_count = $matches[0][2]+$matches[0][3];
                                        }
                                    @endphp
                                    <label for="design_type">@lang("$string_file.design_type") :</label>
                                    {!! Form::text('design_type_txt', $bus_design_types[$bus->design_type],['class'=>'form-control','id'=>'design_type_txt', "disabled" => true,'lower_count'=>$lower_count,'upper_count'=>$upper_count]) !!}
                                    {!! Form::hidden('design_type', $bus->design_type,['class'=>'form-control','id'=>'design_type']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            @if($bus->type == "LOWER" || $bus->type == "LOWER_UPPER")
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="lower_seats">@lang("$string_file.lower_seats") :</label>
                                        {!! Form::number('lower_seats', count($bus->BusSeatDetail->where("type", "LOWER")),['class'=>'form-control seat','id'=>'lower_seats', "min" => 0, "max" => 50, "step" => 1]) !!}
                                    </div>
                                </div>
                            @endif
                            @if($bus->type == "LOWER_UPPER")
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="upper_seats">@lang("$string_file.upper_seats") :</label>
                                        {!! Form::number('upper_seats', count($bus->BusSeatDetail->where("type", "UPPER")),['class'=>'form-control seat','id'=>'upper_seats', "min" => 0, "max" => 50, "step" => 1]) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="country_area_id">@lang("$string_file.total_seats") :</label>
                                    {!! Form::number('total_seats', $bus->total_seats,['class'=>'form-control','id'=>'total_seats', "disabled" => true]) !!}
                                </div>
                            </div>
                        </div>
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            @if($edit_permission)
                                @if(count($bus->BusSeatDetail) == 0)
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                                    </button>
                                @endif
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
        calcualteTotalSeats();

        $("#lower_seats").change(function(){
            var lower_count = $("#design_type_txt").attr('lower_count');
            var lower_seat_count = $("#lower_seats").val();
            if(lower_seat_count % lower_count !=0){
                $("#lower_seats").val(0);
                swal("Lower Seat count must be in the multiple of "+lower_count);
            }
        });
        if (document.getElementById('upper_seats')) {
            $("#upper_seats").change(function () {
                var upper_count = $("#design_type_txt").attr('upper_count');
                var upper_seat_count = $("#upper_seats").val();
                if (upper_seat_count % upper_count != 0) {
                    $("#upper_seats").val(0);
                    swal("Lower Seat count must be in the multiple of " + upper_count);
                }
            });
        }
        $(".seat").change(function () {
            calcualteTotalSeats()
        });


        function calcualteTotalSeats() {
            var upper_seats = 0;
            if (document.getElementById('upper_seats')) {
                upper_seats = $("#upper_seats").val();
            }
            $("#total_seats").val(parseInt($("#lower_seats").val()) + parseInt(upper_seats));
        }
    </script>
@endsection
