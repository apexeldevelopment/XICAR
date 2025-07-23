<h5>@lang("$string_file.time_slots")</h5>
<hr>
@foreach($segment_time_slot['time_slots'] as $day_slot)
@if($day_slot['segment_id'] == $segment_id && count($day_slot['service_time_slot']) > 0)
<div class="row">
    <div class="col-md-2">
        <div class="form-group">
            <label for="location3">{!! $day_slot['day_title'] !!}</label>
        </div>
    </div>
    <div class="col-md-10">
        <div class="row">
            @foreach($day_slot['service_time_slot'] as $key=> $time_slot)
            <div class="col-md-4">
                <label for="ProfileImage"></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input time-slot-checkbox" id="{{$time_slot['id']}}" name="arr_time_slot[]" value="{{$time_slot['id']}}" {{ in_array($time_slot['id'],$selected_slots) ? "checked" : ""}}>
                                <label class="custom-control-label" for="{{$time_slot['id']}}"></label>
                            </div>
                        </div>
                    </div>
                    <input value="{{$time_slot['slot_time_text']}}" class="form-control" id="time-slot-{{$time_slot['id']}}" name="" placeholder="" aria-describedby="" disabled>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
<hr>
@endif
@endforeach