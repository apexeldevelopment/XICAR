@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
          @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        {{-- @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif --}}
                    </div>
                    <h4 class="panel-title">
                        <i class=" icon fa-gears" aria-hidden="true"></i>
                        @lang("$string_file.whatsapp_template")
                        
                    </h4>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          action="{{ route('merchant.whatsappTemplate.store') }}">
                        @csrf
                        <div>
                            <h5>
                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                @lang("$string_file.ride_book_event")
                            </h5>
                            @php
                            $vars = (!empty($book_event->template_variables)) ? explode(",",$book_event->template_variables): [];
                            $vars_count = count($vars);
                            @endphp
                            <input type="hidden" name="ride_book_event_var_count" id="ride_book_event_var_count" value="{{$vars_count}}">
                            <div class="row">
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="ride_book_template_name">
                                            @lang("$string_file.template_name")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="ride_book_template_name"
                                            placeholder="@lang("$string_file.template_name")"
                                            value="@if(!empty($book_event->template_name)){{$book_event->template_name}}@endif"
                                            >
                                        @if ($errors->has('ride_book_template_name'))
                                            <label class="danger">{{ $errors->first('ride_book_template_name') }}</label>
                                        @endif
                                    </div>
                                    
                                    
                                </div>
                                
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="template_language">
                                            @lang("$string_file.template_lang")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="ride_book_template_lang"
                                            placeholder='@lang("$string_file.template_lang")''
                                            value="@if(!empty($book_event->template_language)){{$book_event->template_language}}@endif"
                                            >
                                        @if ($errors->has('template_language'))
                                            <label class="danger">{{ $errors->first('template_language') }}</label>
                                        @endif
                                    </div>
                                    
                                </div>

                                <div class="col-md-6" >
                                    <h5>
                                        <div class="container">
                                            <div class="row">
                                            <div class="col">
                                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                                @lang("$string_file.template_variables")
                                            </div>
                                            <div class="col">
                                                <button  style="margin-top: 10px;" type="button" class="btn-success" onclick="add_variables('ride_book_event','ride_book_event_var_count', 'ride_book_variables', 'ride_book_event_variables')">Add</button>
                                            </div>
                                            </div>
                                        </div>

                                    </h5>
                                    <div id="ride_book_event">
                                        @for($i=0; $i<count($vars); $i++)
                                        <div class="form-group" id="ride_book_event_variables{{$i}}">
                                                <label for="template_variable">
                                                    @lang("$string_file.template_vars")<span class="text-danger">*</span>
                                                </label>
                
                
                                                <div class="container">
                                                <div class="row">
                                                    <div class="col">
                                                        <select name="ride_book_variables[]" class="form-control">
                                                            <option @if($vars[$i] == "departure")selected @endif value="departure">Departure</option>
                                                            <option @if($vars[$i] == "destination")selected @endif  value="destination">Destination</option>
                                                        </select> 
                                                    </div>
                                                    <div class="col">
                                                        <button type="button" class="btn-danger" onclick="delete_variable('ride_book_event_variables{{$i}}', 'ride_book_event_var_count')">remove</button>    
                                                    </div>
                                                </div>
                                                </div>
                                        </div>

                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h5>
                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                @lang("$string_file.ride_later_book_event")
                            </h5>
                            @php
                            $vars1 = (!empty($book_later_event->template_variables)) ? explode(",",$book_later_event->template_variables): [];
                            $vars1_count = count($vars1);
                            @endphp
                            <input type="hidden" name="ride_later_book_event_var_count" id="ride_later_book_event_var_count" value="{{$vars1_count}}">
                            <div class="row">
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="ride_later_book_template_name">
                                            @lang("$string_file.template_name")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="ride_later_book_template_name"
                                            placeholder="@lang("$string_file.template_name")"
                                            value="@if(!empty($book_later_event->template_name)){{$book_later_event->template_name}}@endif"
                                            >
                                        @if ($errors->has('ride_later_book_template_name'))
                                            <label class="danger">{{ $errors->first('ride_later_book_template_name') }}</label>
                                        @endif
                                    </div>
                                    
                                    
                                </div>
                                
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="template_language">
                                            @lang("$string_file.template_lang")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="ride_later_book_template_lang"
                                            placeholder='@lang("$string_file.template_lang")'
                                            value="@if(!empty($book_later_event->template_language)){{$book_later_event->template_language}}@endif"
                                            >
                                        @if ($errors->has('template_language'))
                                            <label class="danger">{{ $errors->first('template_language') }}</label>
                                        @endif
                                    </div>
                                    
                                </div>

                                <div class="col-md-6" >
                                    <h5>
                                        <div class="container">
                                            <div class="row">
                                            <div class="col">
                                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                                @lang("$string_file.template_variables")
                                            </div>
                                            <div class="col">
                                                <button  style="margin-top: 10px;" type="button" class="btn-success" onclick="add_variables('ride_later_book_event','ride_later_book_event_var_count', 'ride_later_book_variables', 'ride_later_book_event_variables')">Add</button>
                                            </div>
                                            </div>
                                        </div>

                                    </h5>
                                    <div id="ride_later_book_event">
                                        @for($i=0; $i<count($vars1); $i++)
                                        <div class="form-group" id="ride_later_book_event_variables{{$i}}">
                                                <label for="template_variable">
                                                    @lang("$string_file.template_vars")<span class="text-danger">*</span>
                                                </label>
                
                
                                                <div class="container">
                                                <div class="row">
                                                    <div class="col">
                                                        <select name="ride_later_book_variables[]" class="form-control">
                                                            <option @if($vars1[$i] == "departure")selected @endif value="departure">Departure</option>
                                                            <option @if($vars1[$i] == "destination")selected @endif  value="destination">Destination</option>
                                                            <option @if($vars1[$i] == "pick_up_date_time")selected @endif  value="pick_up_date_time">Pick up date time</option>
                                                        </select> 
                                                    </div>
                                                    <div class="col">
                                                        <button type="button" class="btn-danger" onclick="delete_variable('ride_later_book_event_variables{{$i}}', 'ride_later_book_event_var_count')">remove</button>    
                                                    </div>
                                                </div>
                                                </div>
                                        </div>

                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h5>
                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                @lang("$string_file.ride_start_event")
                            </h5>
                            @php
                            $vars2 = (!empty($start_event->template_variables)) ? explode(",",$start_event->template_variables): [];
                            $vars2_count = count($vars2);
                            @endphp
                            <input type="hidden" name="ride_start_event_var_count" id="ride_start_event_var_count" value="{{$vars2_count}}">
                            <div class="row">
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="ride_book_template_name">
                                            @lang("$string_file.template_name")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="ride_start_template_name"
                                            placeholder="@lang("$string_file.template_name")"
                                            value="@if(!empty($start_event->template_name)){{$start_event->template_name}}@endif"
                                            >
                                        @if ($errors->has('ride_start_template_name'))
                                            <label class="danger">{{ $errors->first('ride_start_template_name') }}</label>
                                        @endif
                                    </div>
                                    
                                    
                                </div>
                                
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="template_language">
                                            @lang("$string_file.template_lang")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="ride_start_template_lang"
                                            placeholder='@lang("$string_file.template_lang")'
                                            value="@if(!empty($start_event->template_language)){{$start_event->template_language}}@endif"
                                            >
                                        @if ($errors->has('ride_start_template_lang'))
                                            <label class="danger">{{ $errors->first('ride_start_template_lang') }}</label>
                                        @endif
                                    </div>
                                    
                                </div>

                                <div class="col-md-6" >
                                    <h5>
                                        <div class="container">
                                            <div class="row">
                                            <div class="col">
                                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                                @lang("$string_file.template_variables")
                                            </div>
                                            <div class="col">
                                                <button  style="margin-top: 10px;" type="button" class="btn-success" onclick="add_variables('ride_start_event','ride_start_event_var_count', 'ride_start_variables', 'ride_start_event_variables')">Add</button>
                                            </div>
                                            </div>
                                        </div>

                                    </h5>
                                    <div id="ride_start_event">
                                        @for($i=0; $i<count($vars2); $i++)
                                        <div class="form-group" id="ride_start_event_variables{{$i}}">
                                                <label for="template_variable">
                                                    @lang("$string_file.template_vars")<span class="text-danger">*</span>
                                                </label>
                
                
                                                <div class="container">
                                                <div class="row">
                                                    <div class="col">
                                                        <select name="ride_start_variables[]" class="form-control">
                                                            <option @if($vars2[$i] == "departure")selected @endif value="departure">Departure</option>
                                                            <option @if($vars2[$i] == "destination")selected @endif  value="destination">Destination</option>
                                                            <option @if($vars2[$i] == "driver_name")selected @endif value="driver_name">Driver Name</option>
                                                            <option @if($vars2[$i] == "driver_contact")selected @endif value="driver_contact">Driver Contact</option>
                                                            <option @if($vars2[$i] == "start_datetime")selected @endif value="start_datetime">Start Date Time </option>
                                                            <option @if($vars2[$i] == "end_datetime")selected @endif value="end_datetime">End Date Time</option>
                                                        </select> 
                                                    </div>
                                                    <div class="col">
                                                        <button type="button" class="btn-danger" onclick="delete_variable('ride_start_event_variables{{$i}}', 'ride_start_event_var_count')">remove</button>    
                                                    </div>
                                                </div>
                                                </div>
                                        </div>

                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h5>
                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                @lang("$string_file.ride_end_event")
                            </h5>
                            @php
                            $vars3 = (!empty($end_event->template_variables)) ? explode(",",$end_event->template_variables): [];
                            $vars3_count = count($vars3);
                            @endphp
                            <input type="hidden" name="ride_end_event_var_count" id="ride_end_event_var_count" value="{{$vars3_count}}">
                            <div class="row">
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="ride_end_template_name">
                                            @lang("$string_file.template_name")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="ride_end_template_name"
                                            placeholder="@lang("$string_file.template_name")"
                                            value="@if(!empty($end_event->template_name)){{$end_event->template_name}}@endif"
                                            >
                                        @if ($errors->has('ride_end_template_name'))
                                            <label class="danger">{{ $errors->first('ride_end_template_name') }}</label>
                                        @endif
                                    </div>
                                    
                                    
                                </div>
                                
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="template_language">
                                            @lang("$string_file.template_lang")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="ride_end_template_lang"
                                            placeholder='@lang("$string_file.template_lang")'
                                            value="@if(!empty($end_event->template_language)){{$end_event->template_language}}@endif"
                                            >
                                        @if ($errors->has('ride_end_template_lang'))
                                            <label class="danger">{{ $errors->first('ride_end_template_lang') }}</label>
                                        @endif
                                    </div>
                                    
                                </div>

                                <div class="col-md-6" >
                                    <h5>
                                        <div class="container">
                                            <div class="row">
                                            <div class="col">
                                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                                @lang("$string_file.template_variables")
                                            </div>
                                            <div class="col">
                                                <button  style="margin-top: 10px;" type="button" class="btn-success" onclick="add_variables('ride_end_event','ride_end_event_var_count', 'ride_end_variables', 'ride_end_event_variables')">Add</button>
                                            </div>
                                            </div>
                                        </div>

                                    </h5>
                                    <div id="ride_end_event">
                                            @for($i=0; $i<count($vars3); $i++)
                                            <div class="form-group" id="ride_end_event_variables{{$i}}">
                                                    <label for="template_variable">
                                                        @lang("$string_file.template_vars")<span class="text-danger">*</span>
                                                    </label>
                    
                    
                                                    <div class="container">
                                                    <div class="row">
                                                        <div class="col">
                                                            <select name="ride_end_variables[]" class="form-control">
                                                                <option @if($vars3[$i] == "departure")selected @endif value="departure">Departure</option>
                                                                <option @if($vars3[$i] == "destination")selected @endif  value="destination">Destination</option>
                                                                <option @if($vars3[$i] == "driver_name")selected @endif value="driver_name">Driver Name</option>
                                                                <option @if($vars3[$i] == "driver_contact")selected @endif value="driver_contact">Driver Contact</option>
                                                                <option @if($vars3[$i] == "start_datetime")selected @endif value="start_datetime">Start Date Time </option>
                                                                <option @if($vars3[$i] == "end_datetime")selected @endif value="end_datetime">End Date Time</option>
                                                            </select> 
                                                        </div>
                                                        <div class="col">
                                                            <button type="button" class="btn-danger" onclick="delete_variable('ride_end_event_variables{{$i}}', 'ride_end_event_var_count')">remove</button>    
                                                        </div>
                                                    </div>
                                                    </div>
                                            </div>

                                            @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h5>
                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                @lang("$string_file.cancelled_event")
                            </h5>
                            @php
                            $vars4 = (!empty($cancelled_event->template_variables)) ? explode(",",$cancelled_event->template_variables): [];
                            $vars4_count = count($vars4);
                            @endphp
                            <input type="hidden" name="cancelled_event_var_count" id="cancelled_event_var_count" value="{{$vars4_count}}">
                            <div class="row">
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="cancelled_template_name">
                                            @lang("$string_file.template_name")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="cancelled_template_name"
                                            placeholder="@lang("$string_file.template_name")"
                                            value="@if(!empty($cancelled_event->template_name)){{$cancelled_event->template_name}}@endif"
                                            >
                                        @if ($errors->has('cancelled_template_name'))
                                            <label class="danger">{{ $errors->first('cancelled_template_name') }}</label>
                                        @endif
                                    </div>
                                    
                                    
                                </div>
                                
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="template_language">
                                            @lang("$string_file.template_lang")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="cancelled_template_lang"
                                            placeholder='@lang("$string_file.template_lang")'
                                            value="@if(!empty($cancelled_event->template_language)){{$cancelled_event->template_language}}@endif"
                                            >
                                        @if ($errors->has('cancelled_template_lang'))
                                            <label class="danger">{{ $errors->first('cancelled_template_lang') }}</label>
                                        @endif
                                    </div>
                                    
                                </div>

                                <div class="col-md-6" >
                                    <h5>
                                        <div class="container">
                                            <div class="row">
                                            <div class="col">
                                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                                @lang("$string_file.template_variables")
                                            </div>
                                            <div class="col">
                                                <button  style="margin-top: 10px;" type="button" class="btn-success" onclick="add_variables('cancelled_event','cancelled_event_var_count', 'cancelled_variables', 'cancelled_event_variables')">Add</button>
                                            </div>
                                            </div>
                                        </div>

                                    </h5>
                                    <div id="cancelled_event">
                                        @for($i=0; $i<count($vars4); $i++)
                                        <div class="form-group" id="cancelled_event_variables{{$i}}">
                                                <label for="template_variable">
                                                    @lang("$string_file.template_vars")<span class="text-danger">*</span>
                                                </label>
                
                
                                                <div class="container">
                                                <div class="row">
                                                    <div class="col">
                                                        <select name="cancelled_variables[]" class="form-control">
                                                            <option @if($vars4[$i] == "departure")selected @endif value="departure">Departure</option>
                                                            <option @if($vars4[$i] == "destination")selected @endif  value="destination">Destination</option>
                                                            <option @if($vars4[$i] == "driver_name")selected @endif value="driver_name">Driver Name</option>
                                                            <option @if($vars4[$i] == "driver_contact")selected @endif value="driver_contact">Driver Contact</option>
                                                            <option @if($vars4[$i] == "start_datetime")selected @endif value="start_datetime">Start Date Time </option>
                                                            <option @if($vars4[$i] == "end_datetime")selected @endif value="end_datetime">End Date Time</option>
                                                        </select> 
                                                    </div>
                                                    <div class="col">
                                                        <button type="button" class="btn-danger" onclick="delete_variable('cancelled_event_variables{{$i}}', 'cancelled_event_var_count')">remove</button>    
                                                    </div>
                                                </div>
                                                </div>
                                        </div>

                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h5>
                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                @lang("$string_file.arrived_event")
                            </h5>
                            @php
                            $vars5 = (!empty($arrived_event->template_variables)) ? explode(",",$arrived_event->template_variables): [];
                            $vars5_count = count($vars5);
                            @endphp
                            <input type="hidden" name="arrived_event_var_count" id="arrived_event_var_count" value="{{$vars5_count}}">
                            <div class="row">
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="arrived_template_name">
                                            @lang("$string_file.template_name")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="arrived_template_name"
                                            placeholder="@lang("$string_file.template_name")"
                                            value="@if(!empty($arrived_event->template_name)){{$arrived_event->template_name}}@endif"
                                            >
                                        @if ($errors->has('arrived_template_name'))
                                            <label class="danger">{{ $errors->first('arrived_template_name') }}</label>
                                        @endif
                                    </div>
                                    
                                    
                                </div>
                                
                                <div class="col-md-3">
                                    
                                    <div class="form-group">
                                        <label for="template_language">
                                            @lang("$string_file.template_lang")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                            name="arrived_template_lang"
                                            placeholder='@lang("$string_file.template_lang")'
                                            value="@if(!empty($arrived_event->template_language)){{$arrived_event->template_language}}@endif"
                                            >
                                        @if ($errors->has('template_language'))
                                            <label class="danger">{{ $errors->first('template_language') }}</label>
                                        @endif
                                    </div>
                                    
                                </div>

                                <div class="col-md-6" >
                                    <h5>
                                        <div class="container">
                                            <div class="row">
                                            <div class="col">
                                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                                @lang("$string_file.template_variables")
                                            </div>
                                            <div class="col">
                                                <button  style="margin-top: 10px;" type="button" class="btn-success" onclick="add_variables('arrived_event','arrived_event_var_count', 'arrived_variables', 'arrived_event_variables')">Add</button>
                                            </div>
                                            </div>
                                        </div>

                                    </h5>
                                    <div id="arrived_event">
                                        @for($i=0; $i<count($vars5); $i++)
                                        <div class="form-group" id="arrived_event_variables{{$i}}">
                                                <label for="template_variable">
                                                    @lang("$string_file.template_vars")<span class="text-danger">*</span>
                                                </label>
                
                
                                                <div class="container">
                                                <div class="row">
                                                    <div class="col">
                                                        <select name="arrived_variables[]" class="form-control">
                                                            <option @if($vars5[$i] == "departure")selected @endif value="departure">Departure</option>
                                                            <option @if($vars5[$i] == "destination")selected @endif  value="destination">Destination</option>
                                                            <option @if($vars5[$i] == "driver_name")selected @endif value="driver_name">Driver Name</option>
                                                            <option @if($vars5[$i] == "driver_contact")selected @endif value="driver_contact">Driver Contact</option>
                                                            <option @if($vars5[$i] == "start_datetime")selected @endif value="start_datetime">Start Date Time </option>
                                                            <option @if($vars5[$i] == "start_datetime")selected @endif value="start_datetime">End Date Time</option>
                                                        </select> 
                                                    </div>
                                                    <div class="col">
                                                        <button type="button" class="btn-danger" onclick="delete_variable('arrived_event_variables{{$i}}', 'arrived_event_var_count')">remove</button>    
                                                    </div>
                                                </div>
                                                </div>
                                        </div>

                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div>
                            <h5>
                                <i class="icon fa-envelope" aria-hidden="true"></i>
                                @lang("$string_file.ride_later_start_to_pickup_event")
                            </h5>
                            @php
                                $vars6 = (!empty($book_later_start_to_pickup_event->template_variables)) ? explode(",",$book_later_start_to_pickup_event->template_variables): [];
                                $vars6_count = count($vars6);
                            @endphp
                            <input type="hidden" name="ride_later_start_to_pickup_event_var_count" id="ride_later_start_to_pickup_event_var_count" value="{{$vars6_count}}">
                            <div class="row">
                                <div class="col-md-3">

                                    <div class="form-group">
                                        <label for="ride_later_start_to_pickup_template_name">
                                            @lang("$string_file.template_name")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                               name="ride_later_start_to_pickup_template_name"
                                               placeholder="@lang("$string_file.template_name")"
                                               value="@if(!empty($book_later_start_to_pickup_event->template_name)){{$book_later_start_to_pickup_event->template_name}}@endif"
                                        >
                                        @if ($errors->has('ride_later_start_to_pickup_template_name'))
                                            <label class="danger">{{ $errors->first('ride_later_start_to_pickup_template_name') }}</label>
                                        @endif
                                    </div>


                                </div>

                                <div class="col-md-3">

                                    <div class="form-group">
                                        <label for="ride_later_start_to_pickup_template_lang">
                                            @lang("$string_file.template_lang")<span class="text-danger">*</span>
                                        </label>
                                        <input style="height: 0%" type="text" class="form-control"
                                               name="ride_later_start_to_pickup_template_lang"
                                               placeholder='@lang("$string_file.template_lang")'
                                               value="@if(!empty($book_later_start_to_pickup_event->template_language)){{$book_later_start_to_pickup_event->template_language}}@endif"
                                        >
                                        @if ($errors->has('ride_later_start_to_pickup_template_lang'))
                                            <label class="danger">{{ $errors->first('ride_later_start_to_pickup_template_lang') }}</label>
                                        @endif
                                    </div>

                                </div>

                                <div class="col-md-6" >
                                    <h5>
                                        <div class="container">
                                            <div class="row">
                                                <div class="col">
                                                    <i class="icon fa-envelope" aria-hidden="true"></i>
                                                    @lang("$string_file.template_variables")
                                                </div>
                                                <div class="col">
                                                    <button  style="margin-top: 10px;" type="button" class="btn-success" onclick="add_variables('ride_later_start_to_pickup_event','ride_later_start_to_pickup_event_var_count', 'ride_later_start_to_pickup_variables', 'ride_later_start_to_pickup_event_variables')">Add</button>
                                                </div>
                                            </div>
                                        </div>

                                    </h5>
                                    <div id="ride_later_start_to_pickup_event">
                                        @for($i=0; $i<count($vars6); $i++)
                                            <div class="form-group" id="ride_later_start_to_pickup_event_variables{{$i}}">
                                                <label for="template_variable">
                                                    @lang("$string_file.template_vars")<span class="text-danger">*</span>
                                                </label>


                                                <div class="container">
                                                    <div class="row">
                                                        <div class="col">
                                                            <select name="ride_later_start_to_pickup_variables[]" class="form-control">
                                                                <option @if($vars6[$i] == "departure")selected @endif value="departure">Departure</option>
                                                                <option @if($vars6[$i] == "destination")selected @endif  value="destination">Destination</option>
                                                                <option @if($vars6[$i] == "driver_name")selected @endif value="driver_name">Driver Name</option>
                                                                <option @if($vars6[$i] == "driver_contact")selected @endif value="driver_contact">Driver Contact</option>
                                                                <option @if($vars6[$i] == "pick_up_date_time")selected @endif  value="pick_up_date_time">Pick up date time</option>

                                                            </select>
                                                        </div>
                                                        <div class="col">
                                                            <button type="button" class="btn-danger" onclick="delete_variable('ride_later_start_to_pickup_event_variables{{$i}}', 'ride_later_start_to_pickup_event_var_count')">remove</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="form-actions right" style="margin-bottom: 3%">
                            @if(Auth::user('merchant')->can('edit_email_configurations'))
                                @if($edit_permission)
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-square-o"></i> @lang("$string_file.save")
                                </button>
                            @else
                                <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                            @endif
                            @endif
                        </div>
                    </form>
                </div>
               
            </div>
        </div>
    </div>

    <select name="" id="">

        <option value="option">option</option>
    </select>
    

    {{-- @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text']) --}}
@endsection
@section('js')
<script>
    function add_variables(id, counter_id, name, dynamic_ids){
        let parent = document.getElementById(id);
        let hidden_counter =  document.getElementById(counter_id);
        let counter = hidden_counter.value;
        let newDiv = document.createElement('div');
        newDiv.className = 'form-group';
        newDiv.id = dynamic_ids + counter;

        if(id == 'ride_book_event'){
            newDiv.innerHTML = `
               
                                <label for="template_variable">
                                    @lang("$string_file.template_vars")<span class="text-danger">*</span>
                                </label>


                                <div class="container">
                                <div class="row">
                                    <div class="col">
                                        <select name="${name}[]" class="form-control">
                                            <option value="departure">Departure</option>
                                            <option value="destination">Destination</option>
                                        </select> 
                                    </div>
                                    <div class="col">
                                        <button type="button" class="btn-danger" onclick="delete_variable('${dynamic_ids}${counter}', '${counter_id}')">remove</button>    
                                    </div>
                                </div>
                                </div>


                                
                                @if ($errors->has('template_variable'))
                                    <label class="danger">{{ $errors->first('template_variable') }}</label>
                                @endif
                        
                    `;

        }
        else if(id == 'ride_later_book_event'){
            newDiv.innerHTML = `
               
               <label for="template_variable">
                   @lang("$string_file.template_vars")<span class="text-danger">*</span>
               </label>


               <div class="container">
               <div class="row">
                   <div class="col">
                       <select name="${name}[]" class="form-control">
                           <option value="departure">Departure</option>
                           <option value="destination">Destination</option>
                           <option value="pick_up_date_time">Pick up date time</option>
                       </select> 
                   </div>
                   <div class="col">
                       <button type="button" class="btn-danger" onclick="delete_variable('${dynamic_ids}${counter}')">remove</button>    
                   </div>
               </div>
               </div>


               
               @if ($errors->has('template_variable'))
                   <label class="danger">{{ $errors->first('template_variable') }}</label>
               @endif
       
            `;
        }else if(id == 'ride_later_start_to_pickup_event'){
            newDiv.innerHTML = `
                <label for="template_variable">
                    @lang("$string_file.template_vars")<span class="text-danger">*</span>
                </label>


                <div class="container">
                <div class="row">
                    <div class="col">
                        <select name="${name}[]" class="form-control">
                            <option value="departure">Departure</option>
                            <option value="destination">Destination</option>
                            <option value="driver_name">Driver Name</option>
                            <option value="driver_contact">Driver Contact</option>
                            <option value="pick_up_date_time">Pick up date time</option>
                        </select> 
                    </div>
                    <div class="col">
                        <button type="button" class="btn-danger" onclick="delete_variable('${dynamic_ids}${counter}')">remove</button>    
                    </div>
                </div>
                </div>


                
                @if ($errors->has('template_variable'))
                    <label class="danger">{{ $errors->first('template_variable') }}</label>
                @endif
            
                `;
        }else{
            newDiv.innerHTML = `
               
                            <label for="template_variable">
                                @lang("$string_file.template_vars")<span class="text-danger">*</span>
                            </label>


                            <div class="container">
                            <div class="row">
                                <div class="col">
                                    <select name="${name}[]" class="form-control">
                                        <option value="departure">Departure</option>
                                        <option value="destination">Destination</option>
                                        <option value="driver_name">Driver Name</option>
                                        <option value="driver_contact">Driver Contact</option>
                                        <option value="start_datetime">Start Date Time </option>
                                        <option value="end_datetime">End Date Time</option>
                                    </select> 
                                </div>
                                <div class="col">
                                    <button type="button" class="btn-danger" onclick="delete_variable('${dynamic_ids}${counter}')">remove</button>    
                                </div>
                            </div>
                            </div>


                            
                            @if ($errors->has('template_variable'))
                                <label class="danger">{{ $errors->first('template_variable') }}</label>
                            @endif
                        
                `;
        }


        // parent.innerHTML += div;
        parent.appendChild(newDiv);

        hidden_counter.value = ++counter;
    }

    function delete_variable(divId, counter_id) {
        let elementToRemove = document.getElementById(divId);
        elementToRemove.parentNode.removeChild(elementToRemove);
        let counter = document.getElementById(counter_id);
        counter_val = parseInt(counter.value)-1;
        counter.value = counter_val;

    }
</script>
@endsection