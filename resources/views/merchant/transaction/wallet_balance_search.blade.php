@php $search_route = isset($arr_search['search_route']) ? $arr_search['search_route'] : route('transaction.wallet-report.balance');@endphp
{!! Form::open(['name'=>'','class'=>'','url'=>$search_route,"onsubmit"=>"return selectSearchFields()",'method'=>"GET","id"=>"driver-wallet-search"]) !!}
@php $searched_segment = []; $searched_param = NULL; $searched_area = NULL; $searched_text = ""; $searched_lt_gt_zero = "";
$driver_status = NULL; $selected_agent = NULL; $arr_status_list = arr_driver_search_status($string_file); $searched_country = NULL;
@endphp

@if(!empty($arr_search))
    @php $searched_segment = isset($arr_search['segment_id']) ? $arr_search['segment_id'] : [] ;
     $searched_param = isset($arr_search['parameter']) ? $arr_search['parameter'] : NULL;
     $searched_country = isset($arr_search['country_id']) ? $arr_search['country_id'] : NULL;
     $searched_area = isset($arr_search['area_id']) ? $arr_search['area_id'] : NULL;
     $searched_text = isset($arr_search['keyword']) ? $arr_search['keyword'] : "";
     $online_offline = isset($arr_search['online_offline']) ? $arr_search['online_offline'] : "";
     $driver_status = isset($arr_search['driver_status']) ? $arr_search['driver_status'] : "";
     $selected_agent = isset($arr_search['agent_id']) ? $arr_search['agent_id'] : "";
     $searched_lt_gt_zero = isset($arr_search['lt_gt']) ? $arr_search['lt_gt'] : "";
    @endphp
@endif
<div class="table_search row p-3 ">
{{--    <div class="col-md-2 active-margin-top">@lang('admin.message687') :</div>--}}
    @if(isset($arr_segment) && count($arr_segment) > 1)
        <div class="col-md-2">
            {!! Form::select('segment_id[]',$arr_segment,$searched_segment,['class'=>'form-control select2','multiple'=>true,'id'=>'segment_id','data-placeholder'=>trans("$string_file.segment")]) !!}
        </div>
    @endif
    <div class="col-md-2 col-xs-12 form-group active-margin-top">
        {!! Form::select('country_id',add_blank_option($countries,trans("$string_file.country")),$searched_country,['class'=>'form-control select2','id'=>'country_id']) !!}
    </div>
    <div class="col-md-2 col-xs-12 form-group active-margin-top">
        {!! Form::select('area_id',add_blank_option($areas,trans("$string_file.area")),$searched_area,['class'=>'form-control select2','id'=>'area_id']) !!}
    </div>
    <div class="col-md-2 col-xs-12 form-group active-margin-top">
        {!! Form::select('parameter',add_blank_option($search_param,trans("$string_file.select_by")),$searched_param,['class'=>'form-control','id'=>'by_param']) !!}
    </div>
    <div class="col-md-2 col-xs-12 form-group active-margin-top">
        <input id="keyword" name="keyword" placeholder="@lang("$string_file.enter_text")" value="{{$searched_text}}"
               class="form-control" type="text">
    </div>
    <div class="col-md-2 col-xs-12 form-group active-margin-top">
        <select id="lt_gt" name="lt_gt" placeholder="@lang("$string_file.wallet_lt_gt")" value="{{$searched_lt_gt_zero}}" class="form-control">
            <option value="">---Select--</option>
            <option value="lt">Wallet balance less than zero</option>
            <option value="gt">Wallet balance greater than zero</option>
        </select>
    </div>
    @if(isset($driver_agent_enable) && $driver_agent_enable == 1)
        <div class="col-md-2 col-xs-12 form-group active-margin-top">
            {!! Form::select('agent_id',add_blank_option($agents,trans("$string_file.agent")),$selected_agent,['class'=>'form-control select2','id'=>'agent_id']) !!}
        </div>
    @endif
    <div class="col-md-2 col-xs-12 form-group active-margin-top">
        <button class="btn btn-primary" type="submit" name="seabt12"><i class="fa fa-search" aria-hidden="true"></i>
        </button>
        <a href="{{$search_route}}">
            <button class="btn btn-success" type="button"><i class="fa fa-refresh" aria-hidden="true"></i>
            </button>
        </a>
    </div>
</div>
<hr>
{!! Form::close() !!}
