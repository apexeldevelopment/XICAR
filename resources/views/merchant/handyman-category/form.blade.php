@extends('merchant.layouts.main')
@section('content')
    @php $id = NULL; $images_required = true; @endphp
    @if(isset($data['handyman_category']['id']))
        @php $id = $data['handyman_category']['id']; @endphp
        @php $images_required = false;@endphp
    @endif
    <div class="page">
        <div class="page-content">
            {{--            file to display error and success message --}}
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->add_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('segment.handyman-category') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i></button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.handyman") @lang("$string_file.category")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        {!! Form::open(["class"=>"steps-validation wizard-notification","id" => "handyman-category-form","files"=>true,"url"=>route("segment.handyman-category.save",$id)]) !!}
                        {!! Form::hidden('id',$id) !!}
                        <fieldset>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>@lang("$string_file.segment") <span class="text-danger">*</span>
                                        </label>
                                        @if($id != NULL)
                                            <div class="form-group">
                                                {{$data['handyman_category']->Segment->Name($data['handyman_category']['merchant_id'])}}
                                                {!! Form::hidden("segment_id",$data['handyman_category']['segment_id'], ['id' => "segment_id"]) !!}
                                            </div>
                                        @else
                                            <div class="form-group">
                                                {!! Form::select('segment_id',add_blank_option($data['arr_segment'],trans("$string_file.select")),old('segment_id',isset($data['handyman_category']['segment_id']) ? $data['handyman_category']['segment_id'] :NULL),["class"=>"form-control","id"=>"segment_id","required"=>true,'onChange'=>"getService()"]) !!}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>@lang("$string_file.category") <span class="text-danger">*</span>
                                        </label>
                                        <div class="form-group">
                                            {!! Form::text('category',old('category',isset($data['handyman_category']['category']) ? $data['handyman_category']['category'] :NULL),["class"=>"form-control","id"=>"category","required"=>true]) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>@lang("$string_file.description") <span class="text-danger">*</span>
                                        </label>
                                        <div class="form-group">
                                            {!! Form::text('description',old('description',isset($data['handyman_category']['description']) ? $data['handyman_category']['description'] :NULL),["class"=>"form-control","id"=>"description","required"=>true]) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="service_types">
                                            @lang("$string_file.services")<span class="text-danger">*</span>
                                        </label>
                                        {!! Form::select('service_types[]',$data['service_types'],old('service_types',$data['selected_service_types']),["class"=>"form-control select2","id"=>"service_types","multiple"=>true,'required'=>true]) !!}
                                        @if ($errors->has('service_types'))
                                            <label class="text-danger">{{ $errors->first('service_types') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="icon">
                                        @lang("$string_file.icon")<span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group form-group decrement">
                                        {!! Form::file('icon',['id'=>'icon','class'=>'form-control','required'=>$images_required]) !!}
                                        @if ($errors->has('icon'))
                                            <label class="text-danger">{{ $errors->first('icon') }}</label>
                                        @endif
                                    </div>
                                    @if(isset($data['handyman_category']->icon) && !empty($data['handyman_category']->icon))
                                        @php $img = get_image($data['handyman_category']->icon,'category',$data['handyman_category']['merchant_id']) @endphp
                                        <div class="input-group" style="max-width: 100px; height: 100px; float: left">
                                            <a href="{{ $img }}" target="_blank">
                                                <img src="{{ $img }}" class="img-responsive rounded img-bordered img-bordered-primary"
                                                     style="max-width: 80px; height: 80px;"
                                                     title="{!! trans("$string_file.view_images") !!}">
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>@lang("$string_file.status") <span class="text-danger">*</span>
                                        </label>
                                        <div class="form-group">
                                            {!! Form::select('status',$data['arr_status'],old('status',isset($data['handyman_category']['status']) ? $data['handyman_category']['status'] :NULL),['class'=>'form-control','required'=>true,'id'=>'status']) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <div class="form-actions float-right">
                            @if($id == NULL || $edit_permission)
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-square-o"></i>{!! $data['submit_button'] !!}
                            </button>
                            @else
                                <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                            @endif
                        </div>
                        {!! Form::close() !!}
                    </section>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script>
        function getService() {
            var segment_id = $("#segment_id option:selected").val();

            $("#loader1").show();
            var token = $('[name="_token"]').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: "{{route('segment.services')}}",
                data: {
                    segment_id: segment_id
                },
                success: function (data) {
                    console.log(service_types);
                    $('#service_types').html(data);
                }
            });
            $("#loader1").hide();
        }
    </script>
@endsection
