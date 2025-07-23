@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{route('merchant.brand.add')}}">
                            <button type="button" title="@lang("$string_file.add_brand")"
                                    class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-plus"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="fa fa-building" aria-hidden="true"></i>@lang("$string_file.brand")</h3>
                </header>
                <div class="panel-body">
                    @php
                        $brand = isset($arr_search['brand']) ? $arr_search['brand'] : "";
                    @endphp
                    {!! Form::open(['name'=>'','url'=>$search_route,'method'=>'GET']) !!}
                    <div class="table_search row">
                        <div class="col-md-3 col-xs-12 form-group active-margin-top">
                            <div class="input-group">
                                <input type="text" id="" name="brand_name" value="{{$brand_name}}"
                                       placeholder="@lang("$string_file.brand")"
                                       class="form-control col-md-12 col-xs-12">
                            </div>
                        </div>
                        <div class="col-sm-2  col-xs-12 form-group active-margin-top">
                            <button class="btn btn-primary" type="submit" name="seabt12"><i class="fa fa-search"
                                                                                            aria-hidden="true"></i>
                            </button>
                            <a href="{{$search_route}}">
                                <button class="btn btn-success" type="button"><i class="fa fa-refresh"
                                                                                 aria-hidden="true"></i></button>
                            </a>
                        </div>
                    </div>
                    {!! Form::close() !!}
                    <hr>
                    <table class="display nowrap table table-hover table-striped w-full" id="customDataTable"
                           style="width:100%"
                           cellspacing="0">
                        <thead>
                        <tr>
                            {{--<th class="w-50">--}}
                            {{--<span class="checkbox-custom checkbox-primary">--}}
                            {{--  <input class="example-select-all" type="checkbox" name="select_all" value="1" id="example-select-all">--}}
                            {{--  <label></label>--}}
                            {{--</span>--}}
                            {{--</th>--}}
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.segment")</th>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.sequence")</th>
                            <th>@lang("$string_file.image")</th>
                            <th>@lang("$string_file.created_at")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $sr = 1;
                        @endphp
                        @foreach($data as $brand)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    @foreach($brand->Segment as $segment)
                                        {{$segment->Name($brand->merchant_id)}},
                                    @endforeach
                                </td>
                                <td>
                                    {{ $brand->Name($brand->merchant_id) }}

                                </td>
                                <td>@if($brand->status == 1)
                                        @php $status = 2;@endphp
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        @php $status = 1;@endphp
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td>{{ $brand->sequence }}</td>
                                <td>
                                    @if(!empty($data) && !empty($brand['brand_image']))
                                        @php $image = get_image($brand->brand_image,'brand',$brand->merchant_id); @endphp
                                        <a href="{{ $image }}" target="_blank">
                                            <img src="{{ $image }}" height="30" width="30">
                                        </a>
                                    @endif
                                </td>
                                @php $created_at = convertTimeToUSERzone($brand->created_at, null, null, $brand->Merchant,2); @endphp
                                <td>{!! $created_at !!}</td>
                                <td>
                                    <a href="{!! route('merchant.brand.add',$brand->id) !!}"
                                       class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                        <i class="wb-edit"></i>
                                    </a>

                                    <a href="{{ route('merchant.brand.update.status',['id' => $brand->id,'status' => $status]) }}"
                                       data-original-title="@lang("$string_file.status")" data-toggle="tooltip"
                                       data-placement="top"
                                       class="btn btn-sm @if($status == 1) btn-success @else btn-danger @endif menu-icon btn_edit action_btn">
                                        <i class="fa fa-eye"></i>
                                    </a>

                                    @csrf
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $data, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        function DeleteEvent(id) {
            var is_ok = true;
            var token = $('[name="_token"]').val();
            if (id == 'group') {
                var searchIDs = $("#customDataTable input:checkbox:checked").map(function () {
                    return $(this).val();
                }).get();
                id = searchIDs
                if (id.length == 0) {
                    is_ok = false;
                    swal("@lang("$string_file.select_at_least_one_record")");
                }
            }
            if (is_ok) {
                swal({
                    title: "@lang("$string_file.are_you_sure")",
                    text: "@lang("$string_file.delete_category")",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((isConfirm) => {
                    if (isConfirm) {
                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': token
                            },
                            type: "POST",
                            data: {
                                id: id,
                            },
                            url: "{{ route('merchant.brand.destroy') }}",
                        })
                            .done(function (data) {
                                swal({
                                    title: "DELETED!",
                                    text: data,
                                    type: "success",
                                });
                                window.location.href = "{{ route('merchant.brands') }}";
                            });
                    } else {
                        swal("@lang("$string_file.data_is_safe")");
                    }
                });
            }
        }

        $(document).ready(function () {
            // Handle click on "Select all" control
            $('#example-select-all').on('click', function () {
                // Get all rows with search applied
                var table = $('#customDataTable').DataTable();
                var rows = table.rows({'search': 'applied'}).nodes();
                // Check/uncheck checkboxes for all rows in the table
                $('input[type="checkbox"]', rows).prop('checked', this.checked);
            });

            // Handle click on checkbox to set state of "Select all" control
            $('.selectable-item').on('click', function () {
                // If checkbox is not checked
                if (!this.checked) {
                    var el = $('#example-select-all').get(0);
                    // If "Select all" control is checked and has 'indeterminate' property
                    // if(el && el.checked && ('indeterminate' in el)){
                    if (el && el.checked) {
                        // Set visual state of "Select all" control
                        // as 'indeterminate'
                        el.indeterminate = true;
                        $('#example-select-all').prop('checked', false);
                    }
                }
            });
        });
    </script>
@endsection
