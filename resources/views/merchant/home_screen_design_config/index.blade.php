@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        {{--<div class="btn-group float-right">--}}
                            {{--<a href="{{ route('merchant.brands') }}">--}}
                                {{--<button type="button" class="btn btn-icon btn-success" style="margin:10px"><i--}}
                                            {{--class="wb-reply"></i>--}}
                                {{--</button>--}}
                            {{--</a>--}}
                        {{--</div>--}}
                    </div>
                    <h3 class="panel-title">
                        <i class=" fa fa-building" aria-hidden="true"></i>
                        {!! $data['title'] !!}
                    </h3>
                </div>
                @php $id = isset($data['brand']['id']) ? $data['brand']['id'] : NULL; @endphp
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'home_screen_design','id'=>'home_screen_design','files'=>true,'url'=>$data['save_url'],'method'=>'POST'] ) !!}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">
                                    @lang("$string_file.top_seller") @lang("$string_file.product")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('top_seller_products[]',$product_list,old('top_seller_products', $top_seller_products),['id'=>'','class'=>'form-control', 'multiple' => true, 'required'=>true]) !!}
                                @if ($errors->has('top_seller_products'))
                                    <label class="text-danger">{{ $errors->first('top_seller_products') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">
                                    @lang("$string_file.top_brands")
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::select('top_brands[]',$brand_list,old('top_brands',$top_brand_list),['id'=>'','class'=>'form-control', 'multiple' => true, 'required'=>true]) !!}
                                @if ($errors->has('top_brands'))
                                    <label class="text-danger">{{ $errors->first('top_brands') }}</label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="form-actions d-flex flex-row-reverse p-2">
                        @if($id == NULL || $edit_permission)
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i>@lang("$string_file.save")
                            </button>
                        @else
                            <span style="color: red" class="float-right">@lang("$string_file.demo_warning_message")</span>
                        @endif
                    </div>
                    {!!  Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection
