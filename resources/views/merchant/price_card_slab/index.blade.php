@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content container-fluid">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('merchant.pricecard.slab.add')}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-plus" title="@lang("$string_file.add_price_card_slab")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa-money" aria-hidden="true"></i>
                        @lang("$string_file.price_card_slab") </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('merchant.pricecard.slabs') }}" method="GET">
                        @csrf
                        <div class="table_search row p-3">
                            <div class="col-md-2 active-margin-top">@lang("$string_file.search") :</div>
                            <div class="col-md-4 col-xs-12 form-group active-margin-top">
                                {!! Form::select('area',add_blank_option($areas,trans("$string_file.area")),old('area',$area_id),["class"=>"form-control serviceAreaList","id"=>"area","required"=>true]) !!}
                            </div>
                            <div class="col-md-1 col-xs-12 form-group active-margin-top">
                                <button class="btn btn-primary" type="submit" name="seabt12"><i class="fa fa-search" aria-hidden="true"></i></button>
                            </div>
                            <div class="col-md-1 col-xs-12 form-group active-margin-top">
                                <a href="{{ route('merchant.pricecard.slabs') }}">
                                    <button type="button" class="btn btn-primary"><i class="wb-reply"></i></button>
                                </a>
                            </div>
                        </div>
                    </form>
                    <table class="display nowrap table table-hover table-stripedw-full" id="customDataTable"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.service_area")</th>
                            <th>@lang("$string_file.type")</th>
                            <th>@lang("$string_file.details") @lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $price_card_slabs->firstItem();
                        @endphp
                        @foreach($price_card_slabs as $price_card_slab)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    {{ $price_card_slab->name }}
                                </td>
                                <td>
                                    {{ $price_card_slab->CountryArea->CountryAreaName }} <br>
                                    <i>({{ ($price_card_slab->CountryArea->is_geofence == 1) ? trans("$string_file.geofence") : trans("$string_file.service") }} @lang("$string_file.area"))</i>
                                </td>
                                <td>
                                    @switch($price_card_slab->type)
                                        @case("BASE_FARE")
                                        @lang("$string_file.base_fare")
                                        @break
                                        @case("DISTANCE")
                                        @lang("$string_file.distance")
                                        @break
                                        @case("RIDE_TIME")
                                        @lang("$string_file.ride_time")
                                        @break
                                    @endswitch
                                </td>
                                <td>
                                    @if(count($price_card_slab->PriceCardSlabDetail) > 0)
                                        <span class="badge badge-success">@lang("$string_file.added")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.not_added")</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('merchant.pricecard.slab.add',$price_card_slab->id) }}"
                                       data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                       data-placement="top"
                                       class="btn btn-sm btn-warning menu-icon btn_edit action_btn"> <i
                                                class="fa fa-edit"></i> </a>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $price_card_slabs, 'data' => $data])
                </div>
            </div>
        </div>
    </div>
@endsection
