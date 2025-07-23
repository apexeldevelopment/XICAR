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
                        @lang("$string_file.api") @lang("$string_file.usage")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="get" action="{{ route('merchant.view.api.usages') }}">
                        <div class="table_search">
                            <div class="row">
                                <div class="col-md-2 col-xs-12 form-group ">
                                    <div class="input-group">
                                        <input type="text" id="date_start" name="date_start" value="{{isset($search_param['date_start']) ? $search_param['date_start'] : ""}}"
                                               placeholder="@lang("$string_file.date") @lang("$string_file.start")"
                                               class="form-control col-md-12 col-xs-12 datepickersearch"
                                               id="datepickersearch" required>
                                    </div>
                                </div>
                                <div class="col-md-2 col-xs-12 form-group ">
                                    <div class="input-group">
                                        <input type="text" id="date_end" name="date_end" value="{{isset($search_param['date_end']) ? $search_param['date_end'] : ""}}"
                                               placeholder="@lang("$string_file.date") @lang("$string_file.end")"
                                               class="form-control col-md-12 col-xs-12 datepickersearch"
                                               id="datepickersearch" required>
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
                            <th>@lang("$string_file.data")</th>
                            <th>@lang("$string_file.created_at")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $usages->firstItem() @endphp
                        @foreach($usages as $usage)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" onclick="viewData('{{$usage->usage_record}}')">
                                        @lang("$string_file.view")
                                    </button>
                                </td>
                                <td>
                                    {!! convertTimeToUSERzone($usage->created_at, null, $usage->merchant_id, [], 2) !!}
                                </td>

                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $usages, 'data' => $data])
                    {{--                    <div class="pagination1 float-right">{{ $promotions->appends($data)->links() }}</div>--}}
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">@lang("$string_file.api") @lang("$string_file.usage")</h5>
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
                <div class="result-entry p-3 border-bottom d-flex justify-content-between">
                    <div class="text-start">
                        <h5 class="mb-1 text-secondary">
                            <strong>Provider Api: </strong>${item.provider_end_point}
                        </h5>
                        <p class="mb-1 text-secondary">
                            <strong>Api endpoint: </strong>${item.api_end_point}
                        </p>
                    </div>
                    <div class="text-end">
                        <p class="mb-1 text-success">
                            <strong>Count: </strong>${item.count}
                        </p>
                        <small class="text-danger">
                            <strong>Map: </strong>${item.map_type}
                        </small>
                    </div>
                </div>
            `;

            });

            $('#exampleModal').find('.modal-body').html(html);
        }
    </script>
@endsection