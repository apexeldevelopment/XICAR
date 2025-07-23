@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{ route('countryareas.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="wb-user-plus" aria-hidden="true"></i>
                        {{ isset($area->LanguageSingle) ? $area->LanguageSingle->AreaName : '' }} 
                        -> @lang("$string_file.vehicle_type_categorization")
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>

                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','class'=>'steps-validation wizard-notification','id'=>'country-area-step3','files'=>true,'url'=>route('country-area.category.vehicle.type.save',$area->id ?? '')]) !!}
                    {!! Form::hidden("country_area_id", $area->id ?? '', ['class'=>'', 'id'=>'country_area_id']) !!}
                    @foreach($arr_segment_services as $segment_id => $segment)
                        {!! Form::hidden("segment_ids[]", $segment_id, ['class'=>'', 'id'=>'segment_id_'.$segment_id]) !!}
                        @if($segment_id == 1 && $merchant->ApplicationConfiguration->home_screen_view == 1)
                            <div class="row mt3">
                                <div class="col-md-12 mt-10">
                                    <h5><i class="m-1 fa fa-taxi"></i> {{ $segment['name'] }}'s @lang("$string_file.service_type")</h5>
                                </div>
                            </div>
    
                            
                            <div class="border rounded p-4 mt-10 shadow-sm bg-light">
                                <div class="border rounded p-4 mb-2 bg-white">
                                    <div class="row">
                                        <div class="col-md-3"><h5>@lang("$string_file.service_type")</h5></div>
                                        <div class="col-md-9"><h5>@lang("$string_file.category_vehicle_type")</h5></div>
                                    </div>
                                    <hr>
                                        @foreach($segment['arr_services'] as $key => $service)
                                            @php 
                                                $service_type_id = $service['id'];
                                                $selected_vehicles = $arr_selected_vehicle[$segment_id][$service_type_id] ?? [];
                                            @endphp
        
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                {!! $service['locale_service_name'] !!}
                                                                {!! Form::hidden("service_type_id[$segment_id][]", $service_type_id, ["class"=>"form-control", "id"=>"service_type_id_$segment_id"]) !!}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-9">
                                                            <div class="row">
                                                              @foreach($arr_category as $key_inner=>$category)
                                                                @if(in_array($segment_id, $category->segment->pluck('segment_id')->toArray()))
                                                                    @php 
                                                                        $category_id = $category->id;
                                                                        $selected_vehicle = $selected_vehicles[$category_id] ?? [];
                                                                        $dropdown_id = "category_vehicle_{$segment_id}_{$service_type_id}_{$key_inner}";
                                                                    @endphp
                                                            
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <input type="hidden" name="service_category[{{ $segment_id }}][{{ $service_type_id }}][]" value="{{ $category_id }}">
                                                                            {!! $category->Name($category->merchant_id) !!}
                                                                        </div>
                                                                        {!! Form::select(
                                                                            "category_vehicle[$segment_id][$service_type_id][$category_id][]", 
                                                                            $arr_vehicle[$segment_id][$service_type_id] ?? [], 
                                                                            old('category_vehicle', $selected_vehicle), 
                                                                            ["class" => "form-control select2", "id" => $dropdown_id, "multiple" => true]
                                                                        ) !!}
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <br>
                                        @endforeach
                                </div>
                            </div>
                        @elseif($segment_id ==2 && $merchant->ApplicationConfiguration->delivery_home_screen_view == 1)
                            <div class="row mt3">
                                <div class="col-md-12 mt-10">
                                    <h5><i class="m-1 fa fa-taxi"></i> {{ $segment['name'] }}'s @lang("$string_file.service_type")</h5>
                                </div>
                            </div>
    
                            
                            <div class="border rounded p-4 mt-10 shadow-sm bg-light">
                                <div class="border rounded p-4 mb-2 bg-white">
                                    <div class="row">
                                        <div class="col-md-3"><h5>@lang("$string_file.service_type")</h5></div>
                                        <div class="col-md-9"><h5>@lang("$string_file.category_vehicle_type")</h5></div>
                                    </div>
                                    <hr>
                                        @foreach($segment['arr_services'] as $key => $service)
                                            @php 
                                                $service_type_id = $service['id'];
                                                $selected_vehicles = $arr_selected_vehicle[$segment_id][$service_type_id] ?? [];
                                            @endphp
        
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                {!! $service['locale_service_name'] !!}
                                                                {!! Form::hidden("service_type_id[$segment_id][]", $service_type_id, ["class"=>"form-control", "id"=>"service_type_id_$segment_id"]) !!}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-9">
                                                            <div class="row">
                                                              @foreach($arr_category as $key_inner=>$category)
                                                                @if(in_array($segment_id, $category->segment->pluck('segment_id')->toArray()))
                                                                    @php 
                                                                        $category_id = $category->id;
                                                                        $selected_vehicle = $selected_vehicles[$category_id] ?? [];
                                                                        $dropdown_id = "category_vehicle_{$segment_id}_{$service_type_id}_{$key_inner}";
                                                                    @endphp
                                                            
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <input type="hidden" name="service_category[{{ $segment_id }}][{{ $service_type_id }}][]" value="{{ $category_id }}">
                                                                            {!! $category->Name($category->merchant_id) !!}
                                                                        </div>
                                                                        {!! Form::select(
                                                                            "category_vehicle[$segment_id][$service_type_id][$category_id][]", 
                                                                            $arr_vehicle[$segment_id][$service_type_id] ?? [], 
                                                                            old('category_vehicle', $selected_vehicle), 
                                                                            ["class" => "form-control select2", "id" => $dropdown_id, "multiple" => true]
                                                                        ) !!}
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <br>
                                        @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                    <div class="form-actions d-flex flex-row-reverse p-2">
                        @if(empty($area->id) || $edit_permission)
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i>@lang("$string_file.save")
                            </button>
                        @else
                            <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                        @endif
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting', ['info_setting' => $info_setting, 'page_name' => 'view_text'])
@endsection

@section('js')
<script>
</script>
@endsection