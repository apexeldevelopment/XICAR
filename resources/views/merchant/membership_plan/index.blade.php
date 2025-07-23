@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        {{-- @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif --}}
                        <a href="{{route('merchant.membershipPlan.create')}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-plus"
                                   title="@lang("$string_file.add_membership_plan")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.membership_plan_management")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.plan_title")</th>
                            <th>@lang("$string_file.plan_name")</th>
                            <th>@lang("$string_file.price")</th>
                            <th>@lang("$string_file.number_order")</th>
                            <th>@lang("$string_file.max_amount_valid")</th>
                            <th>@lang("$string_file.period")</th>
                            <th>@lang("$string_file.description")</th>
                             {{-- @if(Auth::user('merchant')->can('edit_membership_plan'))  --}}
                                <th>@lang("$string_file.action")</th>
                            {{-- @endif  --}}
                        </tr>
                        </thead>
                        <tfoot></tfoot>
                        <tbody>
                        @if($plan)
                            @php $sr = 1 @endphp
                            @foreach($plan as $value)
                                <tr>
                                    <td>{{ $sr }}</td>
                                    <td>{{$value->plan_title}}</td>
                                    <td>{{$value->plan_name}}</td>
                                    <td>{{$value->price}}</td>
                                    <td>{{$value->number_of_order}}</td>
                                    <td>{{$value->max_amount_valid ?? 0}}</td>
                                    <td>{{$value->period}}</td>
                                    <td>{{$value->description}}</td>
                                    {{-- @if(Auth::user('merchant')->can('edit_membership_plan')) --}}
                                        <td>
                                            <a href="{{ route('merchant.membershipPlan.edit',['id'=>$value->id]) }}"
                                            data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                            data-placement="top"
                                            class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                                <i class="fa fa-edit"></i> </a>
                                            <a href="{{ route('merchant.membershipPlan.delete',['id'=>$value->id]) }}"
                                                    data-original-title="@lang("$string_file.delete")" data-toggle="tooltip"
                                                    data-placement="top"
                                                    class="btn btn-sm btn-danger menu-icon btn_edit action_btn">
                                                    <i class="fa fa-trash"></i> </a>
                                        </td>
                                    {{-- @endif --}}
                                    @php $sr++ @endphp
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    {{-- @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text']) --}}
@endsection