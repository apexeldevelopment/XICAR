@extends('merchant.layouts.main')
@section('content')
@php
    $merchant = get_merchant_id(false);
    $merchant_id = $merchant->id;
    $payment_config = \App\Models\PaymentConfiguration::firstOrCreate(['merchant_id' => $merchant_id])->first();
    $all_grocery_clone = \App\Models\Segment::where("sub_group_for_app",2)->get()->pluck("slag")->toArray();
    $all_food_clone = \App\Models\Segment::where("sub_group_for_app",1)->get()->pluck("slag")->toArray();
    $all_food_grocery_clone = \App\Models\Segment::whereIn("sub_group_for_app",[1,2])->get()->pluck("slag")->toArray();
    $grocery_clone = (count(array_intersect($merchant_segment, $all_grocery_clone)) > 0) ? true :false;
    $grocery_food_exist = (count(array_intersect($merchant_segment, $all_food_grocery_clone)) > 0) ? true :false;
    $food_clone = (count(array_intersect($merchant_segment, $all_food_clone)) > 0) ? true :false;
    // $grocery_clone = (in_array('GROCERY',$merchant_segment) || in_array('PHARMACY',$merchant_segment) || in_array('GAS_DELIVERY',$merchant_segment)|| in_array('WATER_TANK_DELIVERY',$merchant_segment)|| in_array('MEAT_SHOP',$merchant_segment)|| in_array('SWEET_SHOP',$merchant_segment)|| in_array('PAAN_SHOP',$merchant_segment)|| in_array('ARTIFICIAL_JEWELLERY',$merchant_segment) || in_array('GIFT_SHOP',$merchant_segment)|| in_array('CONVENIENCE_SHOP',$merchant_segment) || in_array('ELECTRONIC_SHOP',$merchant_segment) || in_array('WINE_DELIVERY',$merchant_segment) || in_array('FLOWER_DELIVERY',$merchant_segment) || in_array('PET_SHOP',$merchant_segment));
@endphp
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->add_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{ route('cms.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-edit" aria-hidden="true"></i>
                        @lang("$string_file.cms_page")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" id="cms-form" name="cms-form"
                          enctype="multipart/form-data" action="{{ route('cms.store') }}">
                        @csrf
                        <input type="hidden" id="id" name="id" value="">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.page_type")<span
                                                class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="page" id="page"
                                            required>
                                        <option value="">--@lang("$string_file.select")--</option>
                                        @foreach($pages as $page)
                                            <option value="{{ $page->slug }}">{{ $page->page }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('page'))
                                        <label class="text-danger">{{ $errors->first('page') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.select")<span
                                                class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="country" id="country"
                                            required>
                                        <option value="">@lang("$string_file.select")</option>
                                        @foreach($countries  as $country)
                                            <option value="{{ $country->id }}">{{  $country->CountryName }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('page'))
                                        <label class="danger">{{ $errors->first('page') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.application")<span
                                                class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="application"
                                            id="application" required>
                                        <option value="2">@lang("$string_file.driver")</option>
                                        <option value="1">@lang("$string_file.user")</option>
                                        @if(in_array('FOOD',$merchant_segment) || in_array('GROCERY',$merchant_segment) || $grocery_clone || $food_clone || in_array(2,$merchant_segment_group))
                                            <option value="3">@lang("$string_file.store")</option>
                                        @endif
                                    </select>
                                    @if ($errors->has('application'))
                                        <label class="text-danger">{{ $errors->first('application') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.page_title")<span
                                                class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="title"
                                           name="title"
                                           placeholder="@lang("$string_file.page_title")"
                                           required>
                                    @if ($errors->has('title'))
                                        <label class="text-danger">{{ $errors->first('title') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="content_type">
                                        @lang("$string_file.type")<span
                                                class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="content_type"
                                            id="content_type" required>
                                        <option value="1">@lang("$string_file.content")</option>
                                        <option value="2">@lang("$string_file.url")</option>
                                    </select>
                                    @if ($errors->has('content_type'))
                                        <label class="text-danger">{{ $errors->first('content_type') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12" id="description_div">
                                <div class="form-group">
                                    <label for="emailAddress5">
                                        @lang("$string_file.description")<span
                                                class="text-danger">*</span>
                                    </label>
                                    <textarea id="description" class="form-control" name="description" rows="5" placeholder="@lang("$string_file.description")" data-plugin="summernote"></textarea>
                                    @if ($errors->has('description'))
                                        <label class="text-danger">{{ $errors->first('description') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3" id="url_div">
                                <div class="form-group">
                                    <label for="url">
                                        @lang("$string_file.url")<span
                                                class="text-danger">*</span>
                                    </label>
                                    <input type="url" id="url" class="form-control" name="url" placeholder="@lang("$string_file.url")" />
                                    @if ($errors->has('url'))
                                        <label class="text-danger">{{ $errors->first('url') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions right" style="margin-bottom: 3%">
                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                            </button>
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
        $('#url_div').hide();
        $(document).on('change', '#page', function () {
            if ($('#page option:selected').val() == 'terms_and_Conditions') {
                $('#country').attr('disabled', false);
            } else {
                $('#country').attr('disabled', true);
            }

            if ($('#page option:selected').val() == 'help_center') {
                $('textarea#description').summernote('pasteHTML',"I hope this message finds you well. I am writing to you with an urgent matter regarding my account. Unfortunately, I am experiencing difficulties accessing my account, and I'm in need of immediate assistance to resolve this issue. I have tried the standard troubleshooting steps such as resetting my password, clearing cache and cookies, and trying different browsers, but none of them seem to work. I am certain that I am entering the correct login credentials, but I keep receiving an error message. Kindly help in resolving this issue asap");
            }else{
                $('textarea#description').summernote('code','');
            }
        });
        $(document).on('change', '#content_type', function () {
            if ($(this).val() == 1) {
                $('#url_div').hide();
                $('#description_div').show();
            } else {
                $('#url_div').show();
                $('#description_div').hide();
            }
        });
    </script>
@endsection
