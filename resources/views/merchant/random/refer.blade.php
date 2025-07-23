@extends('merchant.layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="app-content content">
            <div class="content-wrapper">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h3 class="content-header-title mb-0 d-inline-block"><i class="fas fa-user-plus"></i> @lang('admin.message318')</h3>
                        <div class="btn-group float-md-right">
                            <div class="heading-elements">
                                @if(Auth::user('merchant')->can('create_refer'))
                                <a href="{{route('merchant.refer.create')}}">
                                    <button type="button" data-original-title="@lang('admin.referal')" data-toggle="tooltip"
                                            class="btn btn-icon btn-success mr-1"><i class="fa fa-plus"></i>
                                    </button>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table display nowrap table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                <tr>
                                    <th>@lang("common.sn")</th>
                                    <th>@lang('admin.Country')</th>
                                    <th>@lang('admin.message319')</th>
                                    <th>@lang('admin.message320')</th>
                                    <th>@lang("common.start")  @lang("common.date") </th>
                                    <th>@lang("common.end")  @lang("common.date")</th>
                                    <th>@lang("common.offer") @lang("common.type") </th>
                                    <th>@lang("common.offer") @lang("common.value") </th>
                                    <th>@lang("common.status")</th>
                                    <th>@lang("common.action")</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $sr = $refers->firstItem() @endphp
                                @foreach($refers as $refer)
                                    <tr>
                                        <td>{{ $sr }}</td>
                                        <td>{{ $refer->Country->CountryName }}</td>
                                        <td>@if($refer->sender_discount == 1)
                                                @lang("common.yes")
                                            @else
                                                @lang("common.no")
                                            @endif
                                        </td>
                                        <td>@if($refer->receiver_discount == 1)
                                                @lang("common.yes")
                                            @else
                                                @lang("common.no")
                                            @endif
                                        </td>
                                        <td>{{ $refer->start_date }}</td>
                                        <td>{{ $refer->end_date }}</td>
                                        <td>
                                            @switch($refer->offer_type)
                                                @case(1)
                                                @lang("common.free") @lang("$string_file.ride")
                                                @break
                                                @case(2)
                                                @lang("common.discount")
                                                @break
                                                @case(3)
                                                @lang("common.fixed") @lang("common.amount")
                                                @break
                                            @endswitch
                                        </td>
                                        <td>
                                            @switch($refer->offer_type)
                                                @case(1)
                                                {{ $refer->offer_value }}
                                                @break
                                                @case(2)
                                                {{ $refer->offer_value }}%
                                                @break
                                                @case(3)
                                                {{ $refer->offer_value }}
                                                @break
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($refer->status == 1)
                                                <label class="label_success">@lang("common.active")</label>
                                            @else
                                                <label class="label_danger">@lang("common.inactive")</label>
                                            @endif
                                        </td>
                                        <td>
                                            @if(Auth::user('merchant')->can('edit_refer'))
                                                <a href="{{ route('merchant.refer.edit',$refer->id) }}"
                                                   data-original-title="@lang("common.edit")" data-toggle="tooltip"
                                                   data-placement="top"
                                                   class="btn btn-sm btn-warning menu-icon btn_edit action_btn"> <i
                                                            class="fa fa-edit"></i> </a>
                                            
                                                @if($refer->status == 1)
                                                    <a href="{{ route('merchant.refer.active-deactive',['id'=>$refer->id,'status'=>2]) }}"
                                                       data-original-title="@lang("common.inactive")" data-toggle="tooltip"
                                                       data-placement="top"
                                                       class="btn btn-sm btn-danger menu-icon btn_eye_dis action_btn"> <i
                                                                class="fa fa-eye-slash"></i> </a>
                                                @else
                                                    <a href="{{ route('merchant.refer.active-deactive',['id'=>$refer->id,'status'=>1]) }}"
                                                       data-original-title="@lang("common.active")" data-toggle="tooltip"
                                                       data-placement="top"
                                                       class="btn btn-sm btn-success menu-icon btn_eye action_btn"> <i
                                                                class="fa fa-eye"></i> </a>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    @php $sr++  @endphp
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="col-sm-12">
                            <div class="pagination1">{{ $refers->links() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection