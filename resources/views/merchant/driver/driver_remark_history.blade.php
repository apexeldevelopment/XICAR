@extends('merchant.layouts.main')
@php
    $date_start = isset($data['date_start']) ? $data['date_start'] : "";
    $date_end = isset($data['date_end']) ? $data['date_end'] : "";
@endphp
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.driver") @lang("$string_file.remarks") @lang("$string_file.history")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('merchant.driver.remarks.history', ['id'=> $id]) }}" method="get">
                        <div class="table_search row">
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="date" id="" name="date_start" required
                                           placeholder="@lang("$string_file.date") @lang("$string_file.start")"
                                           class="form-control col-md-12 col-xs-12" value="{{$date_start}}">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="date" id="" name="date_end" required
                                           placeholder="@lang("$string_file.date") @lang("$string_file.end")"
                                           class="form-control col-md-12 col-xs-12" value="{{$date_end}}">
                                </div>
                            </div>
                            <div class="col-sm-2  col-xs-12 form-group ">
                                <button class="btn btn-primary" type="submit" name="seabt12"><i
                                            class="fa fa-search" aria-hidden="true"></i>
                                </button>
                                <a href="{{ route('merchant.driver.remarks.history', ['id'=> $id]) }}" ><button class="btn btn-success" type="button"><i class="fa fa-refresh" aria-hidden="true"></i></button></a>
                            </div>
                            <div class="col-sm-4 float-right form-group">

                            </div>
                        </div>
                    </form>
                    <div class="tab-content pt-20">
                        <div class="tab-pane active" id="exampleTabsLineTopOne" role="tabpanel">
                            <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                                   style="width:100%">
                                <thead>
                                <tr>
                                    <th>@lang("$string_file.sn")</th>
                                    <th>@lang("$string_file.id")</th>
                                    <th>@lang("$string_file.remarks")</th>
                                    <th>@lang("$string_file.date")</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $sr = $history->firstItem() @endphp
                                @foreach($history as $record)
                                    <tr>
                                        <td>
                                            {{ $sr }}
                                        </td>
                                        <td>
                                            {{ "#".$record->id }}
                                        </td>

                                        <td>
                                            {{ $record->remark }}
                                        </td>
                                        <td>
                                            {{ date('d-m-Y', strtotime($record->created_at))  }}
                                        </td>

                                    </tr>
                                    @php $sr++ @endphp
                                @endforeach
                                </tbody>
                            </table>
                            @include('merchant.shared.table-footer', ['table_data' => $history, 'data' => $data])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#dataTable2').DataTable({
                searching: false,
                paging: false,
                info: false,
                "bSort": false,
            });
        });
    </script>
@endsection
