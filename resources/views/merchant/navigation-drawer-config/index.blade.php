@extends('merchant.layouts.main')
@section('content')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                data-target="#appLocationModel" data-toggle="modal" type="button">
                            <i class="wb-info ml-1 mr-1" title="Info" style=""></i> App Location Options
                        </button>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.navigation_drawer") @lang("$string_file.config")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" id="form" class="steps-validation wizard-notification"
                              enctype="multipart/form-data" action="{{ route('navigation-drawer-config.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 for="night_slot">@lang("$string_file.menu_items")<span class="text-danger">*</span></h5>
                                </div>
                                <div class="col-md-6" style="text-align: right;">
                                    <button class="btn btn-dark rounded-circle" id="add_parent_div" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                </div>
                            </div>
                            @if(!empty($merchant_navigation_drawer) && count($merchant_navigation_drawer) > 0)
                                <div id="parent_div" sr_number='{{$merchant_navigation_drawer->count()}}'>
                                    @foreach($merchant_navigation_drawer as $key => $menu)
                                        <hr>
                                        <div id="add-parent-row-content-{{$key}}">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label for="menu_name">
                                                        @lang("$string_file.menu_name")<span
                                                                class="text-danger">*</span></label>
                                                    <input type="text" name="slab[{{$key}}][menu_name]" value="{{$menu->Name}}" class="form-control" id="menu_name_{{$key}}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="menu_sequence">
                                                        @lang("$string_file.sequence")<span
                                                                class="text-danger">*</span></label>
                                                    <input type="number" name="slab[{{$key}}][menu_sequence]" value="{{$menu->sequence}}" class="form-control" id="menu_sequence_{{$key}}" oninput="numberOnly(this.id)" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="menu_icon">
                                                        @lang("$string_file.icon")
                                                    </label> @if(isset($menu->icon) && !empty($menu->icon))<a href="{{ get_image($menu->icon, "drawericons") }}" target="_blank">Show</a>@endif
                                                    <input type="file" name="slab[{{$key}}][menu_icon]" class="form-control" id="menu_icon_{{$key}}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="menu_type">
                                                        @lang("$string_file.menu_type")<span
                                                                class="text-danger">*</span></label>
                                                    <select class="form-control menu_type_row" name="slab[{{$key}}][menu_type]" ind="{{$key}}" id="menu_type_{{$key}}" required>
                                                        <option>-- Select -- </option>
                                                        <option value="REDIRECT_URL" @if($menu->type == "REDIRECT_URL") selected @endif>Redirect URL</option>
                                                        <option value="APP_LOCATION" @if($menu->type == "APP_LOCATION") selected @endif>App Location</option>
                                                        <option value="CATEGORY" @if($menu->type == "CATEGORY") selected @endif>Categories</option>
                                                        <option value="CMS_PAGE" @if($menu->type == "CMS_PAGE") selected @endif>CMS Page</option>
                                                        <option value="PARENT_MENU" @if($menu->type == "PARENT_MENU") selected @endif>Parent Menu</option>
                                                    </select>
                                                </div>
                                                <input type="hidden" name="slab[{{$key}}][menu_id]" value="{{$menu->id}}">
                                                @if($key != 0)
                                                    <div class="form-group col-md-1">
                                                        <button class="btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight" type="button" onclick="parent_remove_row({{$key}})"><strong>-</strong></button>
                                                    </div>
                                                @endif
                                                <div class="col-md-3">
                                                    <label for="menu_type">
                                                        @lang("$string_file.extra_data")</label>
                                                    <input type="text" name="slab[{{$key}}][menu_extra_data]" value="{{$menu->extra_data}}" class="form-control" id="menu_extra_data_{{$key}}" >
                                                </div>
                                            </div>
                                            @if($menu->type == "REDIRECT_URL")
                                                @php $unique_id = rand(10000,99999); @endphp
                                                <div id="slab_div_{{$key}}" sr_number='0' parent_number='{{$key}}'>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="redirect_url_{{$unique_id}}">
                                                                Redirect URL<span class="text-danger">*</span>
                                                            </label>
                                                            <input type="text" name="slab[{{$key}}][redirect_url]" value="{{$menu->value}}" class="form-control" id="redirect_url_{{$unique_id}}" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            @elseif($menu->type == "APP_LOCATION")
                                                @php $unique_id = rand(10000,99999); @endphp
                                                <div id="slab_div_{{$key}}" sr_number='0' parent_number='{{$key}}'>
                                                    <div id="slab_div_{{$key}}" sr_number='0' parent_number='{{$key}}'>
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <label for="app_location_{{$unique_id}}">
                                                                    App Location<span class="text-danger">*</span>
                                                                </label>
                                                                <input type="text" name="slab[{{$key}}][app_location]" value="{{$menu->value}}" class="form-control" id="app_location_{{$unique_id}}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @elseif($menu->type == "CATEGORY")
                                                @php $unique_id = rand(10000,99999); @endphp
                                                <div id="slab_div_{{$key}}" sr_number='0' parent_number='{{$key}}'>
                                                    <div id="slab_div_{{$key}}" sr_number='0' parent_number='{{$key}}'>
                                                        <div class="row">

                                                        </div>
                                                    </div>
                                                </div>
                                            @elseif($menu->type == "CMS_PAGE")
                                                @php $unique_id = rand(10000,99999); @endphp
                                                <div id="slab_div_{{$key}}" sr_number='0' parent_number='{{$key}}'>
                                                    <div id="slab_div_{{$key}}" sr_number='0' parent_number='{{$key}}'>
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <label for="cms_page_{{$unique_id}}">
                                                                    CMS Page<span class="text-danger">*</span>
                                                                </label>
                                                                <input type="text" name="slab[{{$key}}][cms_page]" value="{{$menu->value}}" class="form-control" id="cms_page_{{$unique_id}}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @elseif($menu->type == "PARENT_MENU")
                                                <div id="slab_div_{{$key}}" sr_number='{{count($menu->MerchantNavigationDrawerConfig)}}' parent_number='{{$key}}'>
                                                    @php $sub_menu = $menu->MerchantNavigationDrawerConfig->first(); @endphp
                                                    @php $unique_id = rand(10000,99999); $sub_menu_key = 0 @endphp
                                                    <h5 class='mt-1'>Sub Menu Detail</h5>
                                                    <div class="row">
                                                        <div class="col-md-2">
                                                            <label for="sub_menu_name_{{$unique_id}}">
                                                                Name<span class="text-danger">*</span>
                                                            </label>
                                                            <input type="text" name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][menu_name]" value="{{$sub_menu->Name}}" class="form-control" id="sub_menu_name_{{$unique_id}}" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label for="sub_menu_sequence_{{$unique_id}}">
                                                                Sequence<span class="text-danger">*</span>
                                                            </label>
                                                            <input type="number" name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][menu_sequence]" value="{{$sub_menu->sequence}}" class="form-control" id="sub_menu_name_{{$unique_id}}" oninput="numberOnly(this.id)" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label for="sub_menu_type_{{$unique_id}}">
                                                                Type<span class="text-danger">*</span>
                                                            </label>
                                                            <select name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][menu_type]" ind="{{$key}}_0" class="form-control sub_menu_type_row" id="sub_menu_type_{{$unique_id}}" required>
                                                                <option>-- Select -- </option>
                                                                <option value="REDIRECT_URL" @if($sub_menu->type == "REDIRECT_URL") selected @endif>Redirect URL</option>
                                                                <option value="APP_LOCATION" @if($sub_menu->type == "APP_LOCATION") selected @endif>App Location</option>
                                                                <option value="CMS_PAGE" @if($sub_menu->type == "CMS_PAGE") selected @endif>CMS Page</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label for="sub_menu_type_value_{{$unique_id}}">
                                                                Type Value<span class="text-danger">*</span>
                                                            </label>
                                                            <input type="text" name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][menu_type_value]" value="{{$sub_menu->value}}" class="form-control" id="sub_menu_type_value_{{$unique_id}}" required>
                                                        </div>
                                                        <div class="form-group col-md-1">
                                                                <button class="btn btn-dark mt-4 mr-2 rounded-circle slab_add_sub_menu_row" id="slab_add_row_{{$unique_id}}" ind="{{$key}}" parent_number="{{$key}}" type="button">+</button>
                                                        </div>
                                                        <input type="hidden" name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][sub_menu_id]" value="{{$sub_menu->id}}">
                                                    </div>
                                                    <div id="slab_sub_menu_div_{{$key}}" sr_number='{{$key}}' parent_number='{{$key}}'>
                                                        @foreach($menu->MerchantNavigationDrawerConfig->slice(1) as $sub_menu_key => $sub_menu)
                                                            @php $unique_id = rand(10000,99999); @endphp
                                                            <div class="row" id="add-row-content-{{$unique_id}}">
                                                                <div class="col-md-2">
                                                                    <input type="text" name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][menu_name]" value="{{$sub_menu->Name}}" class="form-control" id="sub_menu_name_{{$unique_id}}" required>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <input type="number" name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][menu_sequence]" value="{{$sub_menu->sequence}}" class="form-control" id="sub_menu_name_{{$unique_id}}" oninput="numberOnly(this.id)" required>
                                                                </div>
                                                                
                                                                <div class="col-md-2">
                                                                    <select name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][menu_type]" ind="{{$key}}_0" class="form-control sub_menu_type_row" id="sub_menu_type_{{$unique_id}}" required>
                                                                        <option>-- Select -- </option>
                                                                        <option value="REDIRECT_URL" @if($sub_menu->type == "REDIRECT_URL") selected @endif>Redirect URL</option>
                                                                        <option value="APP_LOCATION" @if($sub_menu->type == "APP_LOCATION") selected @endif>App Location</option>
                                                                        <option value="CMS_PAGE" @if($sub_menu->type == "CMS_PAGE") selected @endif>CMS Page</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <input type="text" name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][menu_type_value]" value="{{$sub_menu->value}}" class="form-control" id="sub_menu_type_value_{{$unique_id}}" required>
                                                                </div>
                                                                <div class="form-group col-md-1">
                                                                    <button class="btn btn-dark mr-2 rounded-circle slab_remove_sub_menu_row" id="slab_remove_row_{{$unique_id}}" ind="{{$key}}" parent_number="{{$key}}" onclick="slab_remove_row({{$unique_id}})" type="button">-</button>
                                                                </div>
                                                                <input type="hidden" name="slab[{{$key}}][sub_menu][{{$sub_menu_key}}][sub_menu_id]" value="{{$sub_menu->id}}">
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <hr>
                                <div id="parent_div" sr_number='0'>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="menu_name">
                                                @lang("$string_file.menu_name")<span
                                                        class="text-danger">*</span></label>
                                            <input type="text" name="slab[0][menu_name]" value="" class="form-control" id="menu_name_0" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="menu_sequence">
                                                @lang("$string_file.menu_sequence")<span
                                                        class="text-danger">*</span></label>
                                            <input type="number" name="slab[0][menu_sequence]" value="" class="form-control" id="menu_sequence_0" oninput="numberOnly(this.id)" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="menu_icon">
                                                @lang("$string_file.icon")
                                            </label>
                                            <input type="file" name="slab[0][menu_icon]" class="form-control" id="menu_icon_0">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="menu_type">
                                                @lang("$string_file.menu_type")<span
                                                        class="text-danger">*</span></label>
                                            <select class="form-control menu_type_row" name="slab[0][menu_type]" ind="0" id="menu_type_0" required>
                                                <option>-- Select -- </option>
                                                <option value="REDIRECT_URL">Redirect URL</option>
                                                <option value="APP_LOCATION">App Location</option>
                                                <option value="CATEGORY">Categories</option>
                                                <option value="CMS_PAGE">CMS Page</option>
                                                <option value="PARENT_MENU">Parent Menu</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="menu_extra_data">
                                                @lang("$string_file.extra_data")
                                            </label>
                                            <input type="text" name="slab[0][menu_extra_data]" class="form-control" id="menu_extra_data_0">
                                        </div>
                                    </div>
                                    <div id="slab_div_0" sr_number='0' parent_number='0'>
                                    </div>
                                </div>
                            @endif
                            <div class="form-actions right" style="margin-bottom: 3%">
                                <button type="submit" class="btn btn-primary float-right" id="submit">
                                    <i class="fa fa-check-circle"></i>
                                    @lang("$string_file.save")
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="appLocationModel" aria-labelledby="appLocationModel"
         role="dialog" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-simple modal-sidebar modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    App Location Options :<br>
                    1. Rate Us : rate-us<br>
                    2. Trip History : trip-history<br>
                    3. Wallet Activity : wallet-activity<br>
                    4. Terms and Condition : terms-and-condition<br>
                    5. Refer and Earn : refer-and-earn<br>
                    6. Logout : logout<br>
                    7. Contact Us : contact-us<br>
                    8. About Us : about-us<br>
                    9. Privacy Policy : privacy-policy<br>
                    10. Language : language<br>
                    11. Customer Support : customer-support<br>
                    12. Account Delete : account-delete<br>
                    13. Favourite : favourite<br>
                    14. QR Code (To make payment) : qr-code<br>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('global/vendor/jquery/jquery.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.19.4/moment.js"></script>

    <script>
        function numberOnly(id) {
            var element = document.getElementById(id);
            element.value = element.value.replace(/[^0-9]/gi, "");
        }

        $(document).on("click",".slab_add_sub_menu_row",function(){
            var current_type_ind = $(this).attr("ind");
            var current_rows = parseInt($("#slab_sub_menu_div_"+current_type_ind).attr('sr_number'));
            var active_row = current_rows + 1;
            var unique_id = Date.now();
            var row =
                "<div class=\"row\" id=\"add-row-content-"+unique_id+"\">\n" +
                "<div class=\"col-md-2\">\n" +
                "   <input type=\"text\" name=\"slab[" + current_type_ind + "][sub_menu]["+active_row+"][menu_name]\" value=\"\" class=\"form-control\" id=\"sub_menu_name_"+unique_id+"\" required>\n" +
                "</div>" +
                "<div class=\"col-md-2\">\n" +
                "   <input type=\"number\" name=\"slab[" + current_type_ind + "][sub_menu]["+active_row+"][menu_sequence]\" value=\"\" class=\"form-control\" id=\"sub_menu_sequence_"+unique_id+"\" oninput=\"numberOnly(this.id)\" required>\n" +
                "</div>" +
                "<div class=\"form-group\">" +
                "       <input type=\"text\" name=\"slab[" + active_row + "][extra_data]\" class=\"form-control\" id=\"extra_data_"+unique_id+"\" required>" +
                "</div>" +
                "<div class=\"col-md-2\">\n" +
                "   <select name=\"slab[" + current_type_ind + "][sub_menu]["+active_row+"][menu_type]\" ind=\""+current_type_ind+"_0\" class=\"form-control sub_menu_type_row\" id=\"sub_menu_type_"+unique_id+"\" required>\n" +
                "      <option>-- Select -- </option>\n" +
                "      <option value=\"REDIRECT_URL\">Redirect URL</option>\n" +
                "      <option value=\"APP_LOCATION\">App Location</option>\n" +
                "      <option value=\"CMS_PAGE\">CMS Page</option>\n" +
                "   </select>" +
                "</div>" +
                "<div class=\"col-md-2\">\n" +
                "   <input type=\"text\" name=\"slab[" + current_type_ind + "][sub_menu]["+active_row+"][menu_type_value]\" value=\"\" class=\"form-control\" id=\"sub_menu_type_value_"+unique_id+"\" required>\n" +
                "</div>" +
                "<div class=\"form-group col-md-1\">\n" +
                "   <button class=\"btn btn-dark mr-2 rounded-circle slab_remove_sub_menu_row\" onclick=\"slab_remove_row("+unique_id+")\" id=\"slab_remove_row_"+unique_id+"\" parent_number=\""+current_type_ind+"\" type=\"button\">-</button>" +
                "</div>\n" +
                "</div>";

            $("#slab_sub_menu_div_"+current_type_ind).append(row);
            $("#slab_sub_menu_div_"+current_type_ind).attr('sr_number',active_row);
        });

        $(document).on("change",".menu_type_row",function(){
            var current_type_value = $(this).val();
            var current_type_ind = $(this).attr("ind");
            var unique_id = Date.now();
            if(current_type_value == "REDIRECT_URL"){
                var row =
                    "<div class=\"row\">\n" +
                    "<div class=\"col-md-3\">\n" +
                    "   <label for=\"redirect_url_\"+unique_id+\">\n" +
                    "   Redirect URL<span class=\"text-danger\">*</span></label>\n" +
                    "   <input type=\"text\" name=\"slab[" + current_type_ind + "][redirect_url]\" value=\"\" class=\"form-control\" id=\"redirect_url_"+unique_id+"\" required>\n" +
                    "</div>" +
                    "</div>";
            }else if(current_type_value == "APP_LOCATION"){
                var row =
                    "<div class=\"row\">\n" +
                    "<div class=\"col-md-3\">\n" +
                    "   <label for=\"app_location_\"+unique_id+\">\n" +
                    "   App Location<span class=\"text-danger\">*</span></label>\n" +
                    "   <input type=\"text\" name=\"slab[" + current_type_ind + "][app_location]\" value=\"\" class=\"form-control\" id=\"app_location_"+unique_id+"\" required>\n" +
                    "</div>" +
                    "</div>";
            }else if(current_type_value == "CATEGORY"){
                var row =
                    "<div class=\"row\">\n" +
                    "</div>";
            }else if(current_type_value == "CMS_PAGE"){
                var row =
                    "<div class=\"row\">\n" +
                    "<div class=\"col-md-3\">\n" +
                    "   <label for=\"cms_page_\"+unique_id+\">\n" +
                    "   CMS Page Code<span class=\"text-danger\">*</span></label>\n" +
                    "   <input type=\"text\" name=\"slab[" + current_type_ind + "][cms_page]\" value=\"\" class=\"form-control\" id=\"cms_page_"+unique_id+"\" required>\n" +
                    "</div>" +
                    "</div>";
            }else if(current_type_value == "PARENT_MENU"){
                var row =
                    "<h5 class='mt-1'>Sub Menu Detail</h5>\n" +
                    "<div class=\"row\">\n" +
                    "<div class=\"col-md-2\">\n" +
                    "   <label for=\"sub_menu_name_\"+unique_id+\">\n" +
                    "   Name<span class=\"text-danger\">*</span></label>\n" +
                    "   <input type=\"text\" name=\"slab[" + current_type_ind + "][sub_menu][0][menu_name]\" value=\"\" class=\"form-control\" id=\"sub_menu_name_"+unique_id+"\" required>\n" +
                    "</div>" +
                    "<div class=\"col-md-2\">\n" +
                    "   <label for=\"sub_menu_sequence_\"+unique_id+\">\n" +
                    "   Sequence<span class=\"text-danger\">*</span></label>\n" +
                    "   <input type=\"number\" name=\"slab[" + current_type_ind + "][sub_menu][0][menu_sequence]\" value=\"\" class=\"form-control\" id=\"sub_menu_sequence_"+unique_id+"\" oninput=\"numberOnly(this.id)\" required>\n" +
                    "</div>" +
                    "<div class=\"col-md-2\">\n" +
                    "   <label for=\"sub_menu_type_\"+unique_id+\">\n" +
                    "   Type<span class=\"text-danger\">*</span></label>\n" +
                    "   <select name=\"slab[" + current_type_ind + "][sub_menu][0][menu_type]\" ind=\""+current_type_ind+"_0\" class=\"form-control sub_menu_type_row\" id=\"sub_menu_type_"+unique_id+"\" required>\n" +
                    "      <option>-- Select -- </option>\n" +
                    "      <option value=\"REDIRECT_URL\">Redirect URL</option>\n" +
                    "      <option value=\"APP_LOCATION\">App Location</option>\n" +
                    "      <option value=\"CMS_PAGE\">CMS Page</option>\n" +
                    "   </select>" +
                    "</div>" +
                    "<div class=\"col-md-2\">\n" +
                    "   <label for=\"sub_menu_type_value_\"+unique_id+\">\n" +
                    "   Type Value<span class=\"text-danger\">*</span></label>\n" +
                    "   <input type=\"text\" name=\"slab[" + current_type_ind + "][sub_menu][0][menu_type_value]\" value=\"\" class=\"form-control\" id=\"sub_menu_type_value_"+unique_id+"\" required>\n" +
                    "</div>" +
                    "<div class=\"form-group col-md-1\">\n" +
                    "   <button class=\"btn btn-dark mt-4 mr-2 rounded-circle slab_add_sub_menu_row\" id=\"slab_add_row_"+unique_id+"\" ind=\""+current_type_ind+"\" parent_number=\""+current_type_ind+"\" type=\"button\">+</button>" +
                    "</div>\n" +
                    "</div>" +
                    "<div id=\"slab_sub_menu_div_"+current_type_ind+"\" sr_number='0' parent_number='0'>\n" +
                    "</div>";
            }
            $("#slab_div_"+current_type_ind).html(row);
        });

        $('document').ready(function(){
            $('#add_parent_div').click(function(){
                var current_rows = parseInt($("#parent_div").attr('sr_number'));
                console.log(current_rows);
                var active_row = current_rows + 1;
                var unique_id = Date.now();
                var row_for_weight =
                    "<div id=\"add-parent-row-content-"+active_row+unique_id+"\"><hr>" +
                    "<div class=\"row\">" +
                        "<div class=\"col-md-3\">" +
                            "<label for=\"menu_name\">Menu Name<span class=\"text-danger\">*</span></label>" +
                            "<div class=\"form-group\">" +
                                "<input type=\"text\" name=\"slab[" + active_row + "][menu_name]\" value=\"\" class=\"form-control\" id=\"menu_name_"+unique_id+"\" required>" +
                            "</div>" +
                        "</div>" +
                        "<div class=\"col-md-2\">" +
                            "<label for=\"menu_sequence\">Sequence<span class=\"text-danger\">*</span></label>" +
                            "<div class=\"form-group\">" +
                                "<input type=\"number\" name=\"slab[" + active_row + "][menu_sequence]\" value=\"\" class=\"form-control\" id=\"menu_sequence_"+unique_id+"\" oninput=\"numberOnly(this.id)\" required>" +
                            "</div>" +
                        "</div>" +
                        "<div class=\"col-md-3\">" +
                        "   <label for=\"menu_icon\">Icon</label>" +
                        "   <div class=\"form-group\">" +
                        "       <input type=\"file\" name=\"slab[" + active_row + "][menu_icon]\" class=\"form-control\" id=\"menu_icon_"+unique_id+"\" required>" +
                        "   </div>" +
                        "</div>" +
                        "<div class=\"col-md-3\">" +
                            "<label for=\"menu_type\">Menu Type<span class=\"text-danger\">*</span></label>" +
                            "<div class=\"form-group\">" +
                                "<select class=\"form-control menu_type_row\" name=\"slab[" + active_row + "][menu_type]\" ind=\""+active_row+"\" required>"+
                                    "<option>-- Select -- </option>" +
                                    "<option value=\"REDIRECT_URL\">Redirect URL</option>" +
                                    "<option value=\"APP_LOCATION\">App Location</option>" +
                                    "<option value=\"CATEGORY\">Categories</option>" +
                                    "<option value=\"CMS_PAGE\">CMS Page</option>" +
                                    "<option value=\"PARENT_MENU\">Parent Menu</option>" +
                                "</select>" +
                            "</div>" +
                        "</div>" +
                        "<div class=\"form-group col-md-1\">\n" +
                            "<button class=\"btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight\" type=\"button\" onclick=\"parent_remove_row(" + active_row+unique_id + ")\"'><strong>-</strong></button>\n" +
                        "</div>\n" +
                        "<div class=\"col-md-3\">" +
                        "   <label for=\"menu_extra_data\">Extra Data</label>" +
                        "   <div class=\"form-group\">" +
                        "       <input type=\"text\" name=\"slab[" + active_row + "][menu_extra_data]\" class=\"form-control\" id=\"extra_data_"+unique_id+"\" required>" +
                        "   </div>" +
                        "</div>" +
                    "</div>" +
                    "<div id=\"slab_div_"+active_row+"\" sr_number=\""+active_row+"\" parent_number=\""+active_row+"\">\n" +
                    "</div>";
                $('#parent_div').append(row_for_weight);
                $("#parent_div").attr('sr_number',active_row);
            });
        });

        function slab_remove_row(e) {
            console.log('Removed-' + e);
            $("#add-row-content-" + e).remove();
        }

        function parent_remove_row(e) {
            console.log('Removed-' + e);
            $("#add-parent-row-content-" + e).remove();
        }
    </script>
@endsection
