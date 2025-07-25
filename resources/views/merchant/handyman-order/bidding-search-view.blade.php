@php
    $booking_id = isset($arr_search['booking_id']) ? $arr_search['booking_id'] : "";
    $rider = isset($arr_search['rider']) ? $arr_search['rider'] : "";
    $start = isset($arr_search['start']) ? $arr_search['start'] : "";
    $end = isset($arr_search['end']) ? $arr_search['end'] : "";
    $driver = isset($arr_search['driver']) ? $arr_search['driver'] : "";
    $calling_view = isset($arr_search['calling_view']) ? $arr_search['calling_view'] : "";
    $arr_segment = isset($arr_search['arr_segment']) ? $arr_search['arr_segment'] : [];
    $segment_id = isset($arr_search['segment_id']) ? $arr_search['segment_id'] : NULL;
    $search_route = isset($arr_search['search_route']) ? $arr_search['search_route'] : "";
    $request_from = isset($arr_search['request_from']) ? $arr_search['request_from'] : "";
    $driver_id = isset($arr_search['driver_id']) ? $arr_search['driver_id'] : NULL;
@endphp
{!! Form::open(['name'=>'','url'=>$search_route,'method'=>'GET','autocomplete'=>false]) !!}
{!! Form::hidden('driver_id',$driver_id,['class'=>'form-control']) !!}
   <div class="table_search row">
        @if(!empty($arr_segment))
            <div class="col-md-2 col-xs-12 form-group active-margin-top">
                <div class="input-group">
                    {!! Form::select('segment_id',add_blank_option($arr_segment,trans("$string_file.segment")),$segment_id,['class'=>'form-control']) !!}
                </div>
            </div>
        @endif
        <div class="col-md-2 col-xs-12 form-group active-margin-top">
            <div class="input-group">
                <input type="text" id="" name="order_id" value="{{$booking_id}}"
                       placeholder="@lang("$string_file.order") @lang("$string_file.id")" 
                       class="form-control col-md-12 col-xs-12">
            </div>
        </div>
            

            <div class="col-md-4 col-xs-12 form-group active-margin-top">
            <div class="input-daterange" data-plugin="datepicker">
            <div class="input-group">
                <div class="input-group-prepend">
                          <span class="input-group-text">
                            <i class="icon wb-calendar" aria-hidden="true"></i>
                          </span>
                </div>
                <input type="text" class="form-control" name="start" value="{{$start}}" autocomplete="false" />
            </div>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">to</span>
                </div>
                <input type="text" class="form-control" name="end" value="{{$end}}" autocomplete="false" />
            </div>
        </div>
    </div>
    <div class="col-sm-2  col-xs-12 form-group active-margin-top">
        <button class="btn btn-primary" type="submit" name="seabt12"><i class="fa fa-search" aria-hidden="true"></i></button>
        <a href="{{$search_route}}" ><button class="btn btn-success" type="button"><i class="fa fa-refresh" aria-hidden="true"></i></button></a>
    </div>
</div>
{!! Form::close() !!}