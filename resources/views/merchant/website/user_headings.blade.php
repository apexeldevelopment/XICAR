@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a class="heading-elements-toggle"><i
                                    class="ft-ellipsis-h font-medium-3"></i></a>
                        <div class="heading-elements">
                            <ul class="list-inline mb-0">
                                <li><a data-action="collapse"><i class="ft-minus"></i></a></li>
                                <li><a data-action="reload"><i class="ft-rotate-cw"></i></a></li>
                            </ul>
                        </div>
                    </div>
                    <h3 class="panel-title">
                        <i class="fa-globe" aria-hidden="true"></i>
                        @lang("$string_file.website_headings")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data" action="{{ route('website-user-home-headings.store') }}">
                            @csrf
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.header")
                            </h5>
                            <hr>
                            @php  $id =  !empty($details->id) ? $details->id : NULL; @endphp
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="app_logo">
                                            @lang("$string_file.app_logo") (512x512):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->logo))
                                                <a href="{{get_image($details->logo,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="app_logo"
                                               name="app_logo"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('app_logo'))
                                            <label class="text-danger">{{ $errors->first('app_logo') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="header_title">
                                            @lang("$string_file.title") :
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="title"
                                               name="app_title"
                                               placeholder="" value="{{!empty($details)  ? $details->WebsiteFeature->Title : ""}}" required>
                                    </div>
                                </div>
                            </div>

                            @if($total_segments >= 1)
                                <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.home_screen_banner")
                                </h5>
                                <hr>
                                <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="banner_image">
                                            @lang("$string_file.banner_image") (1500x1000):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->user_banner_image))
                                                <a href="{{get_image($details->user_banner_image,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="banner_image"
                                               name="banner_image"
                                               placeholder="" value="" @if(empty($details)) required @endif>
                                        @if ($errors->has('banner_image'))
                                            <label class="text-danger">{{ $errors->first('banner_image') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="banner_title">
                                            @lang("$string_file.title") :
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="banner_title"
                                               name="banner[title]"
                                               placeholder="" value="{{isset($arr_feature_banner['title']) ? $arr_feature_banner['title'] : ""}}" required>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="banner_title">
                                            @lang("$string_file.description") :
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="banner_description"
                                               name="banner[description]"
                                                  placeholder="" value="" required>
                                            {{isset($arr_feature_banner['description']) ? $arr_feature_banner['description'] : ""}}
                                        </textarea>
                                    </div>
                                </div>
                            </div>
                            @endif
                            


                            <!--//TODO: URL Links -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.login_buttons")
                            </h5>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="login_text">
                                                @lang("$string_file.login_text"):
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="login_text"
                                                   name="login_text"
                                                   value="@if(!empty($details)){{$details->login_text}}@endif"
                                                   placeholder="" @if(empty($details)) required @endif>
        
                                            @if ($errors->has('login_text'))
                                                <label class="text-danger">{{ $errors->first('login_text') }}</label>
                                            @endif
        
                                        </div>
                                    </div>
        
        
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="signup_text">
                                                @lang("$string_file.signup_text"):
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="signup_text"
                                                   name="signup_text"
                                                   value="@if(!empty($details)){{$details->signup_text}}@endif"
                                                   placeholder="" @if(empty($details)) required @endif>
        
                                            @if ($errors->has('signup_text'))
                                                <label class="text-danger">{{ $errors->first('signup_text') }}</label>
                                            @endif
        
                                        </div>
                                    </div>
                                </div>
    
    
                                </div>
                             <!--//TODO: URL Links -->
                            

                            
                            <!-- TODO: Home Page dynamic images -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.home_screen_images")
                            </h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="image_1">
                                            @lang("$string_file.image_1") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->home_page_image_1))
                                                <a href="{{get_image($details->home_page_image_1,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="image_1"
                                               name="image_1"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('image_1'))
                                            <label class="text-danger">{{ $errors->first('image_1') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="image_2">
                                            @lang("$string_file.image_2") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->home_page_image_2))
                                                <a href="{{get_image($details->home_page_image_2,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="image_2"
                                               name="image_2"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('image_2'))
                                            <label class="text-danger">{{ $errors->first('image_2') }}</label>
                                        @endif

                                    </div>
                                </div>
                            </div>
                            <!--//TODO: Home Page dynamic images Ends -->
                            
                            
                            <!--//TODO: service and pomise headings -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.content_headings")
                            </h5>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="service_heading">
                                        @lang("$string_file.service_heading"):
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="service_heading"
                                           name="service_heading"
                                           value="@if(!empty($details)){{$details->service_heading}}@endif"
                                           placeholder="" @if(empty($details)) required @endif>

                                    @if ($errors->has('service_heading'))
                                        <label class="text-danger">{{ $errors->first('service_heading') }}</label>
                                    @endif

                                </div>
                            </div>


                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="features_heading">
                                        @lang("$string_file.features_heading"):
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="features_heading"
                                           name="features_heading"
                                           value="@if(!empty($details)){{$details->features_heading}}@endif"
                                           placeholder="" @if(empty($details)) required @endif>

                                    @if ($errors->has('features_heading'))
                                        <label class="text-danger">{{ $errors->first('features_heading') }}</label>
                                    @endif

                                </div>
                            </div>


                             <!--//TODO: service and pomise headings -->
                            
                                                        
                            <!-- TODO: Home Page feature description -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.fearture_description")
                            </h5>
                            <div class="row">
                                <div class="col-md-4">
                                <div class="form-group">
                                    <label for="feature_description_image">
                                        @lang("$string_file.feature_description_image") (1080x1080):
                                        <span class="text-danger">*</span>
                                        @if(!empty($details->feature_description_image))
                                            <a href="{{get_image($details->feature_description_image,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                        @endif 
                                    </label>
                                    <input type="file" class="form-control" id="feature_description_image"
                                           name="feature_description_image"
                                           placeholder="" @if(empty($details)) required @endif>

                                    @if ($errors->has('feature_description_image'))
                                        <label class="text-danger">{{ $errors->first('feature_description_image') }}</label>
                                    @endif
                                </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="banner_title">
                                            @lang("$string_file.feature_details") :
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="feature_details"
                                                  name="feature_details"
                                                  placeholder="" value="" required>@if(!empty($details)){{$details->feature_details}}@endif</textarea>
                                    </div>
                                </div>
                            </div>
                            <!-- TODO: Home Page feature description -->


                            
                            

                            <!-- TODO: Home Page dynamic Icons -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.home_screen_icons")
                            </h5>
                            <div class="row">
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="icon_heading">
                                            @lang("$string_file.heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="icon_heading"
                                                name="icon_heading"
                                                @if(!empty($details))value="{{$details->home_page_icon_heading}}"@endif
                                                placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('icon_heading'))
                                            <label class="text-danger">{{ $errors->first('icon_heading') }}</label>
                                        @endif

                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="icon_1">
                                            @lang("$string_file.icon_1") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->home_page_icon_1))
                                                <a href="{{get_image($details->home_page_icon_1,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="icon_1"
                                               name="icon_1"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('icon_1'))
                                            <label class="text-danger">{{ $errors->first('icon_1') }}</label>
                                        @endif

                                    </div>
                                </div>
                                
                                <!-- Icon Heading 1 -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="icon_1_heading">
                                            @lang("$string_file.icon_1_heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="icon_1_heading"
                                               name="icon_1_heading"
                                               value="@if(!empty($details)){{$details->home_page_icon_content_1}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('icon_1_heading'))
                                            <label class="text-danger">{{ $errors->first('icon_1_heading') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <!-- Icon Heading 1 -->
                                
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="icon_2">
                                            @lang("$string_file.icon_2") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->home_page_icon_2))
                                                <a href="{{get_image($details->home_page_icon_2,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="icon_2"
                                               name="icon_2"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('icon_2'))
                                            <label class="text-danger">{{ $errors->first('icon_2') }}</label>
                                        @endif

                                    </div>
                                </div>
                                
                                
                                 <!-- Icon Heading 2 -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="icon_2_heading">
                                            @lang("$string_file.icon_2_heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="icon_2_heading"
                                               name="icon_2_heading"
                                               value="@if(!empty($details)){{$details->home_page_icon_content_2}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('icon_2_heading'))
                                            <label class="text-danger">{{ $errors->first('icon_2_heading') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <!-- Icon Heading 2 -->
                                
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="icon_3">
                                            @lang("$string_file.icon_3") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->home_page_icon_3))
                                                <a href="{{get_image($details->home_page_icon_3,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="icon_3"
                                               name="icon_3"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('icon_3'))
                                            <label class="text-danger">{{ $errors->first('icon_3') }}</label>
                                        @endif

                                    </div>
                                </div>
                                
                                
                                <!-- Icon Heading 3 -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="icon_3_heading">
                                            @lang("$string_file.icon_3_heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="icon_3_heading"
                                               name="icon_3_heading"
                                               value="@if(!empty($details)){{$details->home_page_icon_content_3}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('icon_3_heading'))
                                            <label class="text-danger">{{ $errors->first('icon_3_heading') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <!-- Icon Heading 3 -->
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="icon_4">
                                            @lang("$string_file.icon_4") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->home_page_icon_4))
                                                <a href="{{get_image($details->home_page_icon_4,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="icon_4"
                                               name="icon_4"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('icon_4'))
                                            <label class="text-danger">{{ $errors->first('icon_4') }}</label>
                                        @endif

                                    </div>
                                </div>
                            </div>
                            
                            
                            <!-- Icon Heading 4 -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="icon_4_heading">
                                        @lang("$string_file.icon_4_heading"):
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="icon_4_heading"
                                           name="icon_4_heading"
                                           value="@if(!empty($details)){{$details->home_page_icon_content_4}}@endif"
                                           placeholder="" @if(empty($details)) required @endif>

                                    @if ($errors->has('icon_4_heading'))
                                        <label class="text-danger">{{ $errors->first('icon_4_heading') }}</label>
                                    @endif

                                </div>
                            </div>
                            <!-- Icon Heading 3 -->
                            
                            <!-- TODO: Home Page dynamic Icons Ends-->
                            
                            
                           
                            <!-- TODO: Home Page Ad Heading -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.home_screen_advertisement")
                            </h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ad_heading">
                                            @lang("$string_file.heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="ad_heading"
                                                name="ad_heading"
                                                @if(!empty($details)) value="{{$details->home_page_advert_header}}"@endif
                                                placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('ad_heading'))
                                            <label class="text-danger">{{ $errors->first('ad_heading') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ad_content">
                                            @lang("$string_file.content"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="ad_content"
                                                name="ad_content"
                                                @if(!empty($details))value="{{$details->home_page_advert_content}}"@endif
                                                placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('ad_content'))
                                            <label class="text-danger">{{ $errors->first('ad_content') }}</label>
                                        @endif

                                    </div>
                                </div>
                            </div>
                            <!-- TODO: Home Page Ad Heading Ends-->

 



                            
                            <!-- TODO: Home Page QR images -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.home_screen_qr")
                            </h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="qr_image_1">
                                            @lang("$string_file.qr_1") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->home_page_qr_image_1))
                                                <a href="{{get_image($details->home_page_qr_image_1,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="qr_image_1"
                                                name="qr_image_1"
                                                placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('qr_image_1'))
                                            <label class="text-danger">{{ $errors->first('qr_image_1') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="qr_image_2">
                                            @lang("$string_file.qr_2") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->home_page_qr_image_2))
                                                <a href="{{get_image($details->home_page_qr_image_2,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="qr_image_2"
                                                name="qr_image_2"
                                                placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('qr_image_2'))
                                            <label class="text-danger">{{ $errors->first('qr_image_2') }}</label>
                                        @endif

                                    </div>
                                </div>
                            </div>
                            <!--//TODO: Home Page QR images Ends -->
                            
                            
                            <!-- TODO: Home Additional Headers and Contents  -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.additional_headers_and_content")
                            </h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="additional_header_1">
                                            @lang("$string_file.header")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="additional_header_1"
                                                name="additional_header_1"
                                                value="@if(!empty($details)){{$details->additional_header_1}}@endif"
                                                placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('additional_header_1'))
                                            <label class="text-danger">{{ $errors->first('additional_header_1') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="additional_header_content_1">
                                            @lang("$string_file.content") 
                                            <span class="text-danger">*</span>
                                        </label>
                                                <textarea class="form-control" id="additional_header_content_1"
                                                name="additional_header_content_1"
                                                placeholder="" value="" required>
                                                @if(!empty($details)){{$details->additional_header_content_1}}@endif
                                                </textarea>

                                        @if ($errors->has('additional_header_content_1'))
                                            <label class="text-danger">{{ $errors->first('additional_header_content_1') }}</label>
                                        @endif

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="additional_header_2">
                                            @lang("$string_file.header")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="additional_header_2"
                                                name="additional_header_2"
                                                value="@if(!empty($details)){{$details->additional_header_2}}@endif"
                                                placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('additional_header_2'))
                                            <label class="text-danger">{{ $errors->first('additional_header_2') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="additional_header_content_2">
                                            @lang("$string_file.content") 
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="additional_header_content_2"
                                        name="additional_header_content_2"
                                        placeholder="" value="" required>
                                        @if(!empty($details)){{$details->additional_header_content_2}}@endif
                                        </textarea>

                                        @if ($errors->has('additional_header_content_2'))
                                            <label class="text-danger">{{ $errors->first('additional_header_content_2') }}</label>
                                        @endif

                                    </div>
                                </div>
                            </div>
                            <!--//TODO: Home Additional Headers and Contents  -->

                            <!-- TODO: Home Additional Headers and Contents  -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.extra_content")
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="banner_title">
                                            @lang("$string_file.extra_content_1") :
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="extra_content_1"
                                                  name="extra_content_1"
                                                  placeholder="" value="" >@if(!empty($details)){{$details->extra_content_1}}@endif</textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="banner_title">
                                            @lang("$string_file.extra_content_2") :
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="extra_content_2"
                                                  name="extra_content_2"
                                                  placeholder="" value="" >@if(!empty($details)){{$details->extra_content_2}}@endif</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="banner_title">
                                            @lang("$string_file.extra_content_3") :
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="extra_content_3"
                                                  name="extra_content_3"
                                                  placeholder="" value="" >@if(!empty($details)){{$details->extra_content_3}}@endif</textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="banner_title">
                                            @lang("$string_file.extra_content_4") :
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="extra_content_4"
                                                  name="extra_content_4"
                                                  placeholder="" value="" >@if(!empty($details)){{$details->extra_content_4}}@endif</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="extra_image_2">
                                            @lang("$string_file.extra_image_1") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->extra_image_1))
                                                <a href="{{get_image($details->extra_image_1,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="extra_image_1"
                                               name="extra_image_1"
                                               placeholder="" value="" >
                                        @if ($errors->has('extra_image_1'))
                                            <label class="text-danger">{{ $errors->first('extra_image_1') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="extra_image_2">
                                            @lang("$string_file.extra_image_2") (1080x1080):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->extra_image_2))
                                                <a href="{{get_image($details->extra_image_2,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="extra_image_2"
                                               name="extra_image_2"
                                               placeholder="" value="" >
                                        @if ($errors->has('extra_image_2'))
                                            <label class="text-danger">{{ $errors->first('extra_image_2') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <!--//TODO: Home Additional Headers and Contents  -->

                            
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.segment_banner")
                            </h5>
                            <hr>

                            @foreach($arr_segments as $segment)

                                @php $required =true; @endphp
                                <h6 class="form-section col-md-12" ><i class="wb-add-file"></i> {{$segment->Name($merchant_id)}} @lang("$string_file.home_screen_banner")
                                </h6>
                                <hr>
                                <div class="row">
                                    {{Form::hidden('arr_segment_id['.$segment->id.']',$segment->id)}}
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="banner_image">
                                                @lang("$string_file.banner_image") (1500x1000):
                                                <span class="text-danger">*</span>
                                                @if(isset($arr_component[$segment->id]) && !empty($arr_component[$segment->id]['banner_image']))
                                                    <a href="{{$arr_component[$segment->id]['banner_image']}}" target="_blank">@lang("$string_file.view")</a>
                                                    @php $required =false; @endphp
                                                @endif
                                            </label>
                                            <input type="file" class="form-control" id="banner_image"
                                                   name="segment_banner_image[{{$segment->id}}]"
                                                   placeholder="" value="" @if($required) required @endif>
                                            @if ($errors->has('banner_image'))
                                                <label class="text-danger">{{ $errors->first('banner_image') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="banner_title">
                                                @lang("$string_file.title") :
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="banner_title"
                                                   name="segment_banner_title[{{$segment->id}}]"
                                                   placeholder="" value="{{isset($arr_component[$segment->id]) ? $arr_component[$segment->id]['banner_title'] : ""}}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="banner_title">
                                                @lang("$string_file.description") :
                                                <span class="text-danger">*</span>
                                            </label>
                                            <textarea class="form-control" id="banner_description"
                                                      name="segment_banner_description[{{$segment->id}}]"
                                                      placeholder="" value="" required>
                                            {{isset($arr_component[$segment->id]) ? $arr_component[$segment->id]['banner_description'] : ""}}
                                        </textarea>
                                        </div>
                                    </div>
                                </div>

                            @endforeach



                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.theme")</h5>
                            <hr>
                            <div class="row">

                                <div class="col-md-3">
                                    <div class="form-group px-2">
                                        <label>@lang("$string_file.bg_color_primary"):</label>
                                        <span class="text-danger">*</span>
                                        <input type="color" class="form-control" name="bg_color_primary"  value="{{isset($details['bg_color_primary']) ? $details['bg_color_primary'] : ""}}" required >
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group px-2">
                                        <label>@lang("$string_file.bg_color_secondary"):</label>
                                        {{--<span class="text-danger">*</span>--}}
                                        <input type="color" class="form-control" name="bg_color_secondary"  value="{{isset($details['bg_color_secondary']) ? $details['bg_color_secondary'] : ''}}" required >
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group px-2">
                                        <label>@lang("$string_file.text_color_primary"):</label>
                                        <span class="text-danger">*</span>
                                        <input type="color" class="form-control" name="text_color_primary" value="{{isset($details['text_color_primary']) ? $details['text_color_primary'] : ''}}" required>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group px-2">
                                        <label>@lang("$string_file.text_color_secondary"):</label>
                                        <span class="text-danger">*</span>
                                        <input type="color" class="form-control" name="text_color_secondary" value="{{isset($details['text_color_secondary']) ? $details['text_color_secondary'] : ''}}" required>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <h5 class="form-section col-md-12"><i class="wb-add-file"></i> @lang("$string_file.footer")</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="footer_app_logo">
                                            @lang("$string_file.footer_logo") (512x512):
                                            <span class="text-danger">*</span>
                                            @if(!empty($details->logo))
                                                <a href="{{get_image($details->logo,'website_images')}}" target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="footer_app_logo"
                                               name="footer_app_logo"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('footer_app_logo'))
                                            <label class="text-danger">{{ $errors->first('footer_app_logo') }}</label>
                                        @endif

                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="header_title">
                                            @lang("$string_file.footer_title") :
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="title"
                                               name="footer_title"
                                               placeholder="" value="{{!empty($details)  ? $details->WebsiteFeature->Title : ""}}" required>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <h6 class="form-section col-md-12">1. @lang("$string_file.left_content")</h6>
                            <hr>
                            <div class="row">
                              <div class="col-md-5">
                               <div class="form-group">
                                <label for="">@lang("$string_file.short_content") :
                                <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="footer_left_content"
                                       name="footer_left_content[]"
                                       placeholder=""  required>
                                        {{ isset($arr_feature_footer_left[0]) ? $arr_feature_footer_left[0] : ""}}
                                    </textarea>
                                    </div>
                                </div>
                                <div class="col-md-7">
                               <div class="form-group">
                                <label for="">@lang("$string_file.detail_content") :
                                <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="detail_content"
                                       name="footer_left_content[]"
                                       placeholder=""  required>
                                       {{ isset($arr_feature_footer_left[1]) ? $arr_feature_footer_left[1] : ""}}
                                    </textarea>
                                    </div>
                                </div>
                                </div>

                            <h6 class="form-section col-md-12">2. @lang("$string_file.right_content")</h6>
                            <hr>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="">@lang("$string_file.service_title") :
                                            <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="service_title"
                                                  name="service_content[title]"
                                                  placeholder="" value=" {{ isset($arr_feature_footer_right['title']) ? $arr_feature_footer_right['title'] : ""}}" required>

                                    </div>
                                </div>
                            </div>
                            {{--<h7 class="form-section col-md-12">@lang("$string_file.service_list")</h7><span class="text-danger">*</span>--}}
                            {{--<hr>--}}
                            {{--<div class="" id="service_list_original">--}}

                                    {{--<input type="hidden" id="total_service_list" value="1">--}}
                                    {{--@if(!$add_status)--}}
                                     {{--@php $arr_services =  isset($arr_feature_footer_right['list']) ? $arr_feature_footer_right['list'] : []; @endphp--}}
                                     {{--@foreach($arr_services as $key=> $service_list)--}}
                                        {{--<div class="row" id="list_row_id_{{$key}}">--}}
                                        {{--<div class="col-md-8">--}}
                                            {{--<div class="form-group">--}}
                                                {{--<input type="text" class="form-control" id="service_content_list"--}}
                                                       {{--name="service_content[list][]"--}}
                                                       {{--placeholder="" value=" {{ !empty($service_list) ? $service_list : ""}}" required>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                         {{--@if($key == 0)--}}
                                            {{--<div class="col-md-1">--}}
                                                {{--<div class="form-group">--}}
                                                    {{--<button class="btn btn-info btn-sm float-right" type="button" id="add_more_service_list">--}}
                                                        {{--<i class="fa fa-plus"></i>--}}
                                                    {{--</button>--}}

                                                {{--</div>--}}
                                            {{--</div>--}}
                                         {{--@else--}}
                                            {{--<div class="col-md-1">--}}
                                                {{--<div class="form-group">--}}
                                                    {{--<label for="name"></label>--}}
                                                    {{--<button type="button" class="btn btn-danger btn-sm list_remove_button" id="{{$key}}"><i class="fa fa-remove"></i>--}}
                                                        {{--</button>--}}
                                                    {{--</div>--}}
                                                {{--</div>--}}
                                         {{--@endif--}}
                                        {{--</div>--}}
                                     {{--@endforeach--}}
                                    {{--@else--}}
                                        {{--<div class="row" id="list_row_id_0">--}}
                                        {{--<div class="col-md-8">--}}
                                        {{--<div class="form-group">--}}
                                            {{--<input type="text" class="form-control" id="service_content_list"--}}
                                                   {{--name="service_content[list][]"--}}
                                                   {{--placeholder="" value="" required>--}}
                                        {{--</div>--}}
                                        {{--</div>--}}
                                        {{--<div class="col-md-1">--}}
                                            {{--<div class="form-group">--}}
                                                {{--<button class="btn btn-info btn-sm float-right" type="button" id="add_more_service_list">--}}
                                                    {{--<i class="fa fa-plus"></i>--}}
                                                {{--</button>--}}

                                            {{--</div>--}}
                                        {{--</div>--}}
                                        {{--</div>--}}
                                    {{--@endif--}}
                                {{--</div>--}}

                            <h7 class="form-section col-md-12">@lang("$string_file.service_points")</h7><span class="text-danger">*</span>
                            <hr>
                            <div class="" id="service_point_original">
                                <input type="hidden" id="total_service_list" value="1">

                                    @if(!$add_status)
                                        @php $arr_services =  isset($arr_feature_footer_right['point']) ? $arr_feature_footer_right['point'] : []; @endphp
                                        @foreach($arr_services as $key1=> $service_list)
                                            <div class="row" id="point_row_id_{{$key1}}">
                                            <div class="col-md-8">
                                            <div class="form-group">
                                                <input type="text" class="form-control" id="service_content_list"
                                                       name="service_content[point][]"
                                                       placeholder="" value=" {{ !empty($service_list) ? $service_list : ""}}" required>
                                            </div>
                                            </div>
                                            @if($key1 == 0)
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <button class="btn btn-info btn-sm float-right" type="button" id="add_more_service_point">
                                                        <i class="fa fa-plus"></i>
                                                    </button>

                                                </div>
                                            </div>
                                            @else
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <label for="name"></label>
                                                    <button type="button" class="btn btn-danger btn-sm point_remove_button" id="{{$key1}}"><i class="fa fa-remove"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                            </div>
                                        @endforeach
                                    @else
                                    <div class="row" id="point_row_id_0">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="service_content_point"
                                                      name="service_content[point][]"
                                                      placeholder="" value=" " required>
                                        </div>
                                    </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <button class="btn btn-info btn-sm float-right" type="button" id="add_more_service_point">
                                                    <i class="fa fa-plus"></i>
                                                </button>

                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            

                            <!--//TODO: Footer Bottom headings -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.footer_bottom_headings")
                            </h5>


                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="about_us_heading">
                                            @lang("$string_file.about_us_heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="bottom_about_us_heading"
                                               name="bottom_about_us_heading"
                                               value="@if(!empty($details)){{$details->bottom_about_us_heading}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>
    
                                        @if ($errors->has('bottom_about_us_heading'))
                                            <label class="text-danger">{{ $errors->first('bottom_about_us_heading') }}</label>
                                        @endif
    
                                    </div>
                                </div>
    
    
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="about_us_head">
                                            @lang("$string_file.bottom_services_heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="bottom_services_heading"
                                               name="bottom_services_heading"
                                               value="@if(!empty($details)){{$details->bottom_services_heading}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>
    
                                        @if ($errors->has('bottom_services_heading'))
                                            <label class="text-danger">{{ $errors->first('bottom_services_heading') }}</label>
                                        @endif
    
                                    </div>
                                </div>
    
    
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="privacy_policy_heading">
                                            @lang("$string_file.privacy_policy_heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="bottom_privacy_policy_heading"
                                               name="bottom_privacy_policy_heading"
                                               value="@if(!empty($details)){{$details->bottom_privacy_policy_heading}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>
    
                                        @if ($errors->has('bottom_privacy_policy_heading'))
                                            <label class="text-danger">{{ $errors->first('bottom_privacy_policy_heading') }}</label>
                                        @endif
    
                                    </div>
                                </div>
    
    
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="contact_us_heading">
                                            @lang("$string_file.contact_us_heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="bottom_contact_us_heading"
                                               name="bottom_contact_us_heading"
                                               value="@if(!empty($details)){{$details->bottom_contact_us_heading}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>
    
                                        @if ($errors->has('bottom_contact_us_heading'))
                                            <label class="text-danger">{{ $errors->first('bottom_contact_us_heading') }}</label>
                                        @endif
    
                                    </div>
                                </div>
    
    
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="terms_and_ser_heading">
                                            @lang("$string_file.terms_and_ser_heading"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="bottom_terms_and_ser_heading"
                                               name="bottom_terms_and_ser_heading"
                                               value="@if(!empty($details)){{$details->bottom_terms_and_ser_heading}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>
    
                                        @if ($errors->has('bottom_terms_and_ser_heading'))
                                            <label class="text-danger">{{ $errors->first('bottom_terms_and_ser_heading') }}</label>
                                        @endif
    
                                    </div>
                                </div>
                            </div>


                             <!--//TODO: service and pomise headings -->
                            





                            <!--//TODO: URL Links -->
                            <hr>
                            <h5 class="form-section col-md-12" ><i class="wb-add-file"></i> @lang("$string_file.app_links")
                            </h5>


                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="android_user_link">
                                            @lang("$string_file.android_user_link"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="android_user_link"
                                               name="android_user_link"
                                               value="@if(!empty($details)){{$details->android_user_url_link}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>
    
                                        @if ($errors->has('android_user_link'))
                                            <label class="text-danger">{{ $errors->first('android_user_link') }}</label>
                                        @endif
    
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="android_user_link_text">
                                            @lang("$string_file.android_user_link_text"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="android_user_link_text"
                                               name="android_user_link_text"
                                               value="@if(!empty($details)){{$details->android_user_link_text}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('android_user_link_text'))
                                            <label class="text-danger">{{ $errors->first('android_user_link_text') }}</label>
                                        @endif

                                    </div>
                                </div>
    
    
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="android_driver_link">
                                            @lang("$string_file.android_driver_link"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="android_driver_link"
                                               name="android_driver_link"
                                               value="@if(!empty($details)){{$details->android_driver_url_link}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>
    
                                        @if ($errors->has('android_driver_link'))
                                            <label class="text-danger">{{ $errors->first('android_driver_link') }}</label>
                                        @endif
    
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="android_driver_link_text">
                                            @lang("$string_file.android_driver_link_text"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="android_driver_link_text"
                                               name="android_driver_link_text"
                                               value="@if(!empty($details)){{$details->android_driver_link_text}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('android_driver_link_text'))
                                            <label class="text-danger">{{ $errors->first('android_driver_link_text') }}</label>
                                        @endif

                                    </div>
                                </div>
    
    
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ios_user_link">
                                            @lang("$string_file.ios_user_link"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="ios_user_link"
                                               name="ios_user_link"
                                               value="@if(!empty($details)){{$details->ios_user_url_link}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>
    
                                        @if ($errors->has('ios_user_link'))
                                            <label class="text-danger">{{ $errors->first('ios_user_link') }}</label>
                                        @endif
    
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ios_user_link_text">
                                            @lang("$string_file.ios_user_link_text"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="ios_user_link_text"
                                               name="ios_user_link_text"
                                               value="@if(!empty($details)){{$details->ios_user_link_text}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('ios_user_link_text'))
                                            <label class="text-danger">{{ $errors->first('ios_user_link_text') }}</label>
                                        @endif

                                    </div>
                                </div>
    
    
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ios_driver_link">
                                            @lang("$string_file.ios_driver_link"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="ios_driver_link"
                                               name="ios_driver_link"
                                               value="@if(!empty($details)){{$details->ios_driver_url_link}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>
    
                                        @if ($errors->has('ios_driver_link'))
                                            <label class="text-danger">{{ $errors->first('ios_driver_link') }}</label>
                                        @endif
    
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ios_driver_link_text">
                                            @lang("$string_file.ios_driver_link_text"):
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="ios_driver_link_text"
                                               name="ios_driver_link_text"
                                               value="@if(!empty($details)){{$details->ios_driver_link_text}}@endif"
                                               placeholder="" @if(empty($details)) required @endif>

                                        @if ($errors->has('ios_driver_link_text'))
                                            <label class="text-danger">{{ $errors->first('ios_driver_link_text') }}</label>
                                        @endif

                                    </div>
                                </div>
                            </div>


                             <!--//TODO: URL Links -->
                            



                            
<div class="form-actions float-right">
        @if($id == NULL || $edit_permission)
            <button type="submit" class="btn btn-primary">
            <i class="fa fa-check-square-o"></i> @lang("$string_file.save")
            </button>
        @endif
</div>
</form>
</section>
</div>
</div>
</div>
</div>
@endsection
@section('js')
    <script type="text/javascript">

        // add more slots
        $(document).ready(function (e) {
            $(document).on("click", "#add_more_service_list", function (e) {
                var total_service_list = $("#total_service_list").val();
                var row_id = total_service_list;
                var next_row = parseInt(row_id) + 1;
                $("#total_service_list").val(next_row);
                var new_row ='<div class="row" id="list_row_id_' + row_id +'"><div class="col-md-8"><div class="form-group">' +
                    '{!! Form::text('service_content[list][]',old('distance_to[]',''),['class'=>'form-control','id'=>'distance_from','placeholder'=>'','required'=>true]) !!}' +
                    '@if ($errors->has('distance_to'))' +
                    '<span class="help-block"><strong>{{ $errors->first('distance_from') }}</strong></span>' +
                    '@endif' +
                    '</div>' +
                    '</div>'  +
                    '<div class="col-md-1">' +
                    '<div class="form-group">' +
                    '<label for="name"></label>' +
                    '<button type="button" class="btn btn-danger btn-sm list_remove_button" id="' + row_id + '"><i class="fa fa-remove"></i>' +
                    '</button>'+
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                $("#service_list_original").append(new_row);

            });
        });

        $(document).ready(function (e)
        {
            $(document).on("click", ".list_remove_button", function (e)
            {
                var row_id = $(this).attr('id');
                $("#list_row_id_"+row_id).remove();
            });
        });

        $(document).ready(function (e) {
            $(document).on("click", "#add_more_service_point", function (e) {
                var total_service_point = $("#total_service_point").val();
                var row_id = total_service_point;
                var next_row = parseInt(row_id) + 1;
                $("#total_service_point").val(next_row);
                var new_row ='<div class="row" id="point_row_id_' + row_id + '"><div class="col-md-8"><div class="form-group">' +
                    '{!! Form::text('service_content[point][]',old('distance_to[]',''),['class'=>'form-control','id'=>'distance_from','placeholder'=>'','required'=>true]) !!}' +
                    '@if ($errors->has('distance_to'))' +
                    '<span class="help-block"><strong>{{ $errors->first('distance_from') }}</strong></span>' +
                    '@endif' +
                    '</div>' +
                    '</div>'  +
                    '<div class="col-md-1">' +
                    '<div class="form-group">' +
                    '<label for="name"></label>' +
                    '<button type="button" class="btn btn-danger btn-sm point_remove_button" id="' + row_id + '"><i class="fa fa-remove"></i>' +
                    '</button>'+
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                $("#service_point_original").append(new_row);

            });
        });

        $(document).ready(function (e)
        {
            $(document).on("click", ".point_remove_button", function (e)
            {
                var row_id = $(this).attr('id');
                $("#point_row_id_"+row_id).remove();
            });
        });

    </script>

@endsection