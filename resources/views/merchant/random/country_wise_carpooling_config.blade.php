@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions"></div>
                    <h1 class="panel-title">
                        <i class="icon fa-gears" aria-hidden="true"></i>
                        @lang("$string_file.carpooling") @lang("$string_file.country") @lang("$string_file.configuration")
                    </h1>
                </header>

                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data"
                              action="{{route('merchant.carpooling.config.country.store')}}" id="config">
                            @csrf
                            <input type="hidden" id="id" name="update_id">
                            <div class="row">
                                <div class="col-md-3"></div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("$string_file.carpooling") @lang("$string_file.country")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="country_id"
                                                name="country_id" required>
                                            <option value=""> @lang("$string_file.select")  @lang("$string_file.country")</option>
                                            @foreach($country_list as $value)
                                                <option id="country_id"
                                                        value="{{$value->id}}">{{$value->CountryName}}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('country_id'))
                                            <label class="text-danger">{{ $errors->first('country_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3"></div>
                            </div>

                            <h3 class="panel-title">@lang("$string_file.other") @lang("$string_file.configuration")</h3>
                            <div class="row">
                                 <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("$string_file.short") @lang("$string_file.ride")

                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="short_ride"
                                               name="short_ride"
                                               value="{{ old('short_ride') }}"
                                               placeholder="@lang("$string_file.enter") @lang("$string_file.short") @lang("$string_file.ride") @lang("$string_file.in") @lang("$string_file.km")">
                                        @if ($errors->has('short_ride'))
                                            <label class="danger">{{ $errors->first('short_ride') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("$string_file.start") @lang("$string_file.location") @lang("$string_file.radius")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="start_location_radius"
                                               name="start_location_radius"
                                               value="{{ old('short_ride') }}"
                                               placeholder="@lang("$string_file.enter")  @lang("$string_file.drop") @lang("$string_file.location") @lang("$string_file.radius") @lang("$string_file.in") @lang("$string_file.km")">
                                        @if ($errors->has('drop_location_radius'))
                                            <label class="danger">{{ $errors->first('start_location_radius') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("$string_file.drop") @lang("$string_file.location") @lang("$string_file.radius")

                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="drop_location_radius"
                                               name="drop_location_radius"
                                               value=""
                                               placeholder="@lang("$string_file.enter")  @lang("$string_file.drop") @lang("$string_file.location") @lang("$string_file.radius") @lang("$string_file.in") @lang("$string_file.km")">
                                        @if ($errors->has('drop_location_radius'))
                                            <label class="danger">{{ $errors->first('drop_location_radius') }}</label>
                                        @endif
                                    </div>
                                </div>
                          
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("$string_file.short") @lang("$string_file.ride") @lang("$string_file.time")

                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="short_ride_time"
                                               name="short_ride_time"
                                               value=""
                                               placeholder="@lang("$string_file.enter")  @lang("$string_file.time") @lang("$string_file.in") @lang("$string_file.minute")">
                                        @if ($errors->has('short_ride_time'))
                                            <label class="danger">{{ $errors->first('short_ride_time') }}</label>
                                        @endif
                                    </div>
                                </div>
                                 <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("$string_file.long") @lang("$string_file.ride") @lang("$string_file.time")

                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="long_ride_time"
                                               name="long_ride_time"
                                               value=""
                                               placeholder="@lang("$string_file.enter")  @lang("$string_file.time") @lang("$string_file.in") @lang("$string_file.hour")">
                                        @if ($errors->has('long_ride_time'))
                                            <label class="danger">{{ $errors->first('long_ride_time') }}</label>
                                        @endif
                                    </div>
                                </div>
                                
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("$string_file.user") @lang("$string_file.ride") @lang("$string_file.start") @lang("$string_file.time")

                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="user_ride_start_time"
                                               name="user_ride_start_time"
                                               value=""
                                               placeholder="@lang("$string_file.enter")  @lang("$string_file.user") @lang("$string_file.ride") @lang("$string_file.start") @lang("$string_file.time")  @lang("$string_file.in") @lang("$string_file.minute")">
                                        @if ($errors->has('user_ride_start_time'))
                                            <label class="danger">{{ $errors->first('user_ride_start_time') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="google_key">
                                            @lang("$string_file.user") @lang("$string_file.reminder_expire_doc")

                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control"
                                               id="user_document_reminder_time"
                                               name="user_document_reminder_time"
                                               value=""
                                               placeholder="@lang("$string_file.enter") @lang("$string_file.user") @lang("$string_file.document") @lang("$string_file.reminder") @lang("$string_file.time")  @lang("$string_file.in") @lang("$string_file.hour")">
                                        @if ($errors->has('user_document_reminder_time'))
                                            <label class="danger">{{ $errors->first('user_document_reminder_time') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions d-flex flex-row-reverse p-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function(){
    var config = $('#config');
    $('#submit').click(function(){
        $.ajax({
            url: form.attr('action'),
            type:"POST",
            data: $('#config input').serialize(),

            success:function(data){
                console.log(data);
            }

            
        });
    });
        });   
        $(document).on('change', '#country_id', function () {
            var id = $(this).val();
            $.ajax({
                type: "GET",
                url: "{{route('merchant.carpooling.config')}}",
                data: {
                    "id":id,
                },
                success: function (response) {
                    $('#id').val(response.id);
                    $('#short_ride').val(response.short_ride);
                    $('#start_location_radius').val(response.start_location_radius);
                    $('#drop_location_radius').val(response.drop_location_radius);
                    $('#short_ride_time').val(response.short_ride_time);
                    $('#long_ride_time').val(response.long_ride_time);
                    $('#user_ride_start_time').val(response.user_ride_start_time);
                    $('#user_document_reminder_time').val(response.user_document_reminder_time);
       
                }
            });
        });
        
    </script>

@endsection