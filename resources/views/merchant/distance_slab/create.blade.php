@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
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
                            <a href="{{ route('distance.slab.index') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.add") @lang("$string_file.distance_slab") 
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification"
                              enctype="multipart/form-data" action="{{ route('distance.slab.store',$id) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.name")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="name" value="{{isset($DistanceSlab->name)? $DistanceSlab->name : ""}}" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            @if(empty($id))
                            <div id="b2b_weight_main_div" sr_number='1'>
                                <div class="form-row" id="b2b_weight_child_div">
                                    <div class="form-group col-md-2">
                                        <label for="exampleFormControlInput1">@lang("$string_file.from") @lang("$string_file.km")
                                        <span class="text-danger">*</span></label>
                                        {{ Form::number("distance_content[1][from]",old("distance_content[1][from]"),["class" => "form-control form-control-sm from","id"=>"from",  "step" => "1","max"=>'2000', "oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="exampleFormControlInput1">@lang("$string_file.to")
                                            @lang("$string_file.km")<span class="text-danger">*</span></label>
                                        {{ Form::number("distance_content[1][to]",old("distance_content[1][to]"),["class" => "form-control form-control-sm to","id"=>"to", "step" => "1","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="exampleFormControlInput1">@lang("$string_file.fare")
                                            <span class="text-danger">*</span></label>
                                        {{ Form::number("distance_content[1][fare]",old("distance_content[1][fare]"),["class" => "form-control form-control-sm fare", "id"=>"fare", "step" => "1","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                    </div>
                                    <div class="form-group col-md-1">
                                        <button class="btn btn-dark mt-4 mr-2 rounded-circle" id="add_b2b_weight_div" type="button"><svg xmlns="http://www.w3.org/2000/svg"
                                            width="24" height="24"
                                            viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-plus">
                                           <line x1="12" y1="5" x2="12"
                                                 y2="19"></line>
                                           <line x1="5" y1="12" x2="19"
                                                 y2="12"></line>
                                       </svg></button>
                                    </div>
                                </div>
                            </div>
                            @else
                            @php $sr=1; $display = ""; @endphp
                                <div id="b2b_weight_main_div" sr_number='{{$sr}}'>
                                    @foreach(json_decode($DistanceSlab->details) as $key=>$data)
                                    <div class="form-row" id="add-row-content-{{$sr}}">
                                        <div class="form-group col-md-2">
                                            <label for="exampleFormControlInput1" class="{{$display}}">@lang("$string_file.from") @lang("$string_file.km")
                                            <span class="text-danger">*</span></label>
                                            {{ Form::number("distance_content[$sr][from]",old("distance_content[1][from]",$data->from),["class" => "form-control form-control-sm from","id"=>"from",  "step" => "1","max"=>'2000',"oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="exampleFormControlInput1" class="{{$display}}">@lang("$string_file.to")
                                                @lang("$string_file.km")<span class="text-danger">*</span></label>
                                            {{ Form::number("distance_content[$sr][to]",old("distance_content[1][to]",$data->to),["class" => "form-control form-control-sm to","id"=>"to", "step" => "1","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="exampleFormControlInput1" class="{{$display}}">@lang("$string_file.Fare")
                                                <span class="text-danger">*</span></label>
                                            {{ Form::number("distance_content[$sr][fare]",old("distance_content[1][fare]",$data->fare),["class" => "form-control form-control-sm fare", "id"=>"fare", "step" => "1","oninput"=>"numberOnly(this.id)",'required'=>true]) }}
                                        </div>
                                        <div class="form-group col-md-1">
                                            @if($sr == 1)
                                            <button class="btn btn-dark mt-4 mr-2 rounded-circle add_b2b_weight_div" id="add_b2b_weight_div"
                                                    type="button">
                                                +
                                            </button>
                                            @else
                                            <button class="btn btn-dark mt-0 mr-2 rounded-circle remove-row-button" type="button" onclick="removeRowButtonWeight({{$sr}})"><strong>-</strong></button>
                                            @endif
                                        </div>
                                    </div>
                                    @php $sr++; $display= "d-none"; @endphp
                                    @endforeach
                                </div>
                            @endif
                            <div class="form-actions right" style="margin-bottom: 3%">
                                @if($id == NULL || $edit_permission)
                                <button type="submit" class="btn btn-primary float-right">
                                    <i class="fa fa-check-circle"></i>
                                    @lang("$string_file.save")
                                </button>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
    <script src="{{ asset('global/vendor/jquery/jquery.js') }}"></script>
    <script>
        function numberOnly(id) {
            var element = document.getElementById(id);
            element.value = element.value.replace(/[^0-9]/gi, "");
        }

        $('document').ready(function(){
            $('#add_b2b_weight_div').click(function(){
                var current_rows = parseInt($("#b2b_weight_main_div").attr('sr_number'));
                console.log("current_rows-" + current_rows);
                var active_row = current_rows + 1;
                var unique_id = Date.now();
                var row_for_weight = "<div class=\"form-row mt-0\" id=\"add-row-content-" + unique_id + "\">\n" +
                "                                                            <div class=\"form-group col-md-2\">\n" +
                "                                                                <input type=\"number\" value=\"\" class=\"form-control form-control-sm from\" id=\"from"+unique_id+"\" name=\"distance_content[" + unique_id + "][from]\" step='1' max='2000' required oninput='numberOnly(this.id)'>" +
                "                                                            </div>\n" +
                "                                                            <div class=\"form-group col-md-3\">\n" +
                "                                                                <input type=\"number\" value=\"\" class=\"form-control form-control-sm to\" id=\"to"+unique_id+"\" name=\"distance_content[" + unique_id + "][to]\" step='1' required max='10000' oninput='numberOnly(this.id)'>" +
                "                                                            </div>\n " +
                "                                                            <div class=\"form-group col-md-3\">" +
                "                                                                <input type=\"number\" value=\"\" step=\"1\" class=\"form-control form-control-sm fare\" id=\"fare"+unique_id+"\" name=\"distance_content[" + unique_id + "][fare]\" step='1' required max='10000' oninput='numberOnly(this.id)'>" +
                "                                                            </div>" +
                "                                                              <div class=\"form-group col-md-1\">\n" +
                "                                                                <button class=\"btn btn-dark mb-2 mr-2 rounded-circle remove-row-button-weight\" type=\"button\" onclick=\"removeRowButtonWeight(" + unique_id + ")\"'><strong>-</strong></button>\n" +
                "                                                            </div>\n";

                $('#b2b_weight_main_div').append(row_for_weight);
                $("#b2b_weight_main_div").attr('sr_number',active_row);
            });
            
        });

        function removeRowButtonWeight(e) {
                console.log('Removed-' + e);
                $("#add-row-content-" + e).remove();
            }

    </script>
@endsection

