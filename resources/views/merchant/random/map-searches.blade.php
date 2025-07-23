@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include("merchant.shared.errors-and-messages")
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">

                    </div>
                    <h3 class="panel-title"><i class="fa fa-map" aria-hidden="true"></i>
                        @lang("$string_file.map") @lang("$string_file.searches")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="get" action="{{ route('merchant.map-searches') }}">
                        <div class="table_search">
                            <div class="row">

                                <div class="col-md-4  form-group ">
                                    <div class="input-group">
                                        <select name="map" id="map" class="form-control">
                                            <option >SELECT</option>
                                            <option value="GOOGLE" {{isset($search_param['map']) && $search_param['map'] == "GOOGLE" ? "SELECTED" : ""}}>GOOGLE</option>
                                            <option value="HERE_MAP" {{isset($search_param['map']) && $search_param['map'] == "HERE_MAP" ? "SELECTED" : ""}}>HERE_MAP</option>
                                            <option value="MAP_BOX" {{isset($search_param['map']) && $search_param['map'] == "MAP_BOX" ? "SELECTED" : ""}}>MAP_BOX</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2 col-xs-12 form-group ">
                                    <div class="input-group">
                                        <input type="text" id="" name="date" value="{{isset($search_param['date']) ? $search_param['date'] : ""}}"
                                               placeholder="@lang("$string_file.date")"
                                               class="form-control col-md-12 col-xs-12 datepickersearch"
                                               id="datepickersearch">
                                    </div>
                                </div>
                                <div class="col-sm-2  col-xs-12 form-group ">
                                    <button class="btn btn-primary" type="submit" name="seabt12"><i
                                                class="fa fa-search" aria-hidden="true"></i>
                                    </button>

                                    <a href="{{ route('merchant.map-searches') }}">
                                        <button class="btn btn-success" type="button"><i class="fa fa-refresh" aria-hidden="true"></i>
                                        </button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.country")</th>
                            <th>@lang("$string_file.keyword")</th>
                            <th>@lang("$string_file.map")</th>
                            <th>@lang("$string_file.data")</th>
                            <th>@lang("$string_file.created_at")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $searchable_places->firstItem() @endphp
                        @foreach($searchable_places as $place)
                            @php
                                $response = null;
                                if(!empty($place->response)){
                                    $response = json_decode($place->response);
                                }
                            @endphp
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                        {{ !empty($place->Country) ? $place->Country->getCountryNameAttribute() : "" }}
                                </td>
                                <td>
                                <span class="" style="
                                    font-size: 15px;
                                    font-weight: bold;
                                    max-width: 300px;
                                    display: inline-block;
                                    white-space: normal;
                                    word-break: break-word;
                                    overflow-wrap: break-word;
                                    /*line-height: 1.4;*/
                                ">
                                {{ urldecode($place->keyword) }}
                                </span>

                                </td>
                                <td>

                                    @php
                                        $map = null;
                                        if(!empty($response[0]->map)){
                                            $map = $response[0]->map;
                                        }
                                    @endphp
                                    <span class="badge badge-danger" style="font-size:  1rem">{{$map}}</span><br>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#exampleModal" onclick="viewData('{{$place->response}}')">
                                        @lang("$string_file.view")
                                    </button>


                                </td>


                                <td>
                                        {!! convertTimeToUSERzone($place->created_at, null, null, $place->Merchant, 2) !!}
                                </td>

                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $searchable_places, 'data' => $data])
                    {{--                    <div class="pagination1 float-right">{{ $promotions->appends($data)->links() }}</div>--}}
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Response Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
<script>
    function viewData(data) {
        let parsed_data = JSON.parse(data);
        let html = '';

        parsed_data.forEach((item, index) => {
            html += `
                <div class="result-entry p-3 border-bottom">
                    <h5 class="mb-1 text-primary">${item.main_text}</h5>
                    <p class="mb-1 text-muted">${item.description}</p>
                    <small class="text-secondary">Map: ${item.map}</small>
                </div>
            `;
        });

        $('#exampleModal').find('.modal-body').html(html);
    }
</script>
@endsection