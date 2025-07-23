@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content container-fluid">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('distance.slab.create')}}">
                            <button type="button" class="btn btn-icon btn-success float-right"
                                    style="margin: 10px;">
                                <i class="wb-plus"
                                   title="@lang("$string_file.add")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa-percent" aria-hidden="true"></i>
                        @lang("$string_file.distance_slab")  @lang("$string_file.management")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.details")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.created_at")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $allDistanceSlabs->firstItem() @endphp
                        @foreach($allDistanceSlabs as $allDistanceSlab)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{ $allDistanceSlab->name }}</td>
                                <td>
                                    @empty($allDistanceSlab)
                                        @foreach(json_decode($allDistanceSlab->details) as $key=>$data)
                                            @lang("$string_file.from"): {{$data->from}}  @lang("$string_file.to"): {{$data->to}} = @lang("$string_file.fare"): {{$data->fare}} <br>
                                        @endforeach
                                    @endempty
                                </td>
                                <td>
                                    @if($allDistanceSlab->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td>{{$allDistanceSlab->created_at}}</td>
                                <td>
                                    <a href="{{ route('distance.slab.create',$allDistanceSlab->id) }}"
                                        data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                        data-placement="top"
                                        class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                         <i class="fa fa-edit"></i> </a>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $allDistanceSlabs, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
