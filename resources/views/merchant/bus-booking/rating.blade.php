@extends('merchant.layouts.main')
@section('content')
<style>
    table.dataTable.nowrap th, table.dataTable.nowrap td {
    white-space: inherit;
}
</style>
@php
    $bus_id = isset($arr_search['bus_id']) ? $arr_search['bus_id'] : "";
    $booking_id = isset($arr_search['booking_id']) ? $arr_search['booking_id'] : "";
    $booking_master_id = isset($arr_search['booking_master_id']) ? $arr_search['booking_master_id'] : "";
@endphp
    <div class="page">
        <div class="page-content container-fluid">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">

                    </div>
                    <h3 class="panel-title"><i class="fa-star-o" aria-hidden="true"></i>
                        @lang("$string_file.reviews") @lang("$string_file.and_symbol") @lang("$string_file.ratings")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('merchant.bus_booking.rating.index') }}" method="get">
                        <div class="table_search row">
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="bus_id"
                                           placeholder="@lang("$string_file.bus") @lang("$string_file.id")"
                                           class="form-control col-md-12 col-xs-12" value="{{$bus_id}}">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="booking_id"
                                           placeholder="@lang("$string_file.booking") @lang("$string_file.id")"
                                           class="form-control col-md-12 col-xs-12" value="{{$booking_id}}">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="booking_master_id"
                                           placeholder="@lang("$string_file.master") @lang("$string_file.booking") @lang("$string_file.id")"
                                           class="form-control col-md-12 col-xs-12" value="{{$booking_master_id}}">
                                </div>
                            </div>

                            <div class="col-sm-2  col-xs-12 form-group ">
                                <button class="btn btn-primary" type="submit" name="seabt12"><i
                                            class="fa fa-search" aria-hidden="true"></i>
                                </button>
                                <a href="{{route('merchant.bus_booking.rating.index')}}" ><button class="btn btn-success" type="button"><i class="fa fa-refresh" aria-hidden="true"></i></button></a>
                            </div>
                            <div class="col-sm-4 float-right form-group">

                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.booking") @lang("$string_file.master") @lang("$string_file.id")</th>
                            <th>@lang("$string_file.service") @lang("$string_file.type")</th>
                            <th>@lang("$string_file.bus") @lang("$string_file.details")</th>
                            <th>@lang("$string_file.user") @lang("$string_file.details")</th>
                            <th>@lang("$string_file.driver") @lang("$string_file.details")</th>
                            <th>@lang("$string_file.user") @lang("$string_file.rating")</th>
                            <th>@lang("$string_file.user") @lang("$string_file.review")</th>
                            <th>@lang("$string_file.images")</th>
                            <th>@lang("$string_file.reasons")</th>
                            <th>@lang("$string_file.provider") @lang("$string_file.comments")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $booking_ratings->firstItem() @endphp
                        @foreach($booking_ratings as $rating)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td><a target="_blank" class="address_link"
                                       href="{{ route('merchant.bus_booking.detail',$rating->bus_booking_master_id) }}">{{ $rating->bus_booking_master_id }}</a>
                                </td>
                                <td>
                                    {{ $rating->BusBookingMaster->ServiceType->ServiceName($rating->BusBookingMaster->merchant_id) }}
                                </td>
                                <td>
                                    {{ $rating->BusBookingMaster->Bus->busName($rating->BusBookingMaster->Bus) }}
                                </td>

                                @if(Auth::user()->demo == 1)
                                    <td>
                                            <span class="long_text">
                                                {{ "********".substr($rating->BusBooking->User->UserName,-2) }}
                                               <br>
                                               {{ "********".substr($rating->BusBooking->User->UserPhone,-2) }}
                                               <br>
                                              {{ "********".substr($rating->BusBooking->User->email,-2) }}
                                            </span>
                                    </td>
                                @else
                                    <td>
                                            <span class="long_text">
                                                {{ $rating->BusBooking->User->UserName }}
                                               <br>
                                               {{ $rating->BusBooking->User->UserPhone }}
                                               <br>
                                              {{ $rating->BusBooking->User->email }}
                                            </span>
                                    </td>
                                @endif
                                <td>
                                    <span class="long_text">
                                        @if($rating->BusBookingMaster->Driver)
                                             {{ is_demo_data($rating->BusBookingMaster->Driver->fullName, $rating->BusBookingMaster->Merchant) }}<br>
                                             {{ is_demo_data($rating->BusBookingMaster->Driver->phoneNumber, $rating->BusBookingMaster->Merchant) }}<br>
                                             {{ is_demo_data($rating->BusBookingMaster->Driver->email, $rating->BusBookingMaster->Merchant) }}
                                         @else
                                             @lang("$string_file.not_assigned_yet")
                                         @endif
                                    </span>
                                </td>
                                <td>
                                    @if ($rating->user_rating)
                                        @while($rating->user_rating>0)
                                            @if($rating->user_rating >0.5)
                                                <img src="{{ view_config_image("static-images/star.png") }}"
                                                     alt='Whole Star'>
                                            @else
                                                <img src="{{ view_config_image('static-images/halfstar.png') }}"
                                                     alt='Half Star'>
                                            @endif
                                            @php $rating->user_rating--; @endphp
                                        @endwhile
                                    @else
                                        @lang("$string_file.not_rated_yet")
                                    @endif
                                </td>
                                <td>
                                    @if($rating->user_comment)
                                        {{ $rating->user_comment }}
                                    @else
                                        ------
                                    @endif
                                </td>
                                <td>
                                    
                                    
                                    @php $rating_imgs="";
                                        if(!empty($rating->rating_images)){
                                            $rating_imgs = explode(",",$rating->rating_images);
                                        }
                                    @endphp
                                    @if(!empty($rating_imgs))
                                        @foreach($rating_imgs as $rating_img)
                                            <a href="{{get_image($rating_img, 'bus_booking_rating_image', $rating->BusBooking->User->merchant_id, true, true)}}" target="_blank">View</a><br/>
                                        @endforeach
                                    @endif
                                    
                                </td>
                                <td>
                                    @php
                                        if(!empty(($rating->reasons)) && is_array($rating->reasons)){
                                            $reasons = implode(", ", json_decode($rating->reasons)) ;
                                        }
                                        else
                                            $reasons = "";
                                    @endphp
                                    @if($rating->reasons)
                                        {{ $reasons }}
                                    @else
                                        ------
                                    @endif
                                </td>
                                <td>
                                    @if($rating->provider_comments)
                                        {{ $rating->provider_comments }}
                                    @else
                                        ------
                                    @endif
                                </td>
                                <td>
                                    @if(empty($rating->provider_comments))
                                    <button class="btn brn-sm btn-warning" type="button" data-rating_id = "{{$rating->id}}" onclick="openReviewModal(this)">Add Comment</button>
                                    @endif
                                </td>
                                
                            </tr>
                            @php $sr++;  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $booking_ratings, 'data' => $arr_search])
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade text-left" id="addReviewModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33">@lang("$string_file.add") @lang("$string_file.review")</label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('merchant.bus_booking.add_provider_comment') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    {!! Form::hidden('rating_id','') !!}
                    <div class="modal-body">

                        <div class="row mb-5">
                            <div class="col-lg-6">
                                <label class="modal-title text-text-bold-600"
                                       id="provider_comment">@lang("$string_file.provider") @lang("$string_file.comment")</label>
                            </div>
                            <div class="col-lg-6">
                                <textarea class="form-control" name="provider_comment"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-secondary" data-dismiss="modal"
                               value="@lang("$string_file.close")">
                        <input type="submit" class="btn btn-primary" value="@lang("$string_file.save")">
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
<script>
    function openReviewModal(obj){
        var rating_id = $(obj).data('rating_id');
        $("#addReviewModal input[name='rating_id']").val(rating_id);
        $("#addReviewModal").modal('show');
    }
</script>
@endsection