@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content container-fluid">
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
                        @if(Auth::user('merchant')->can('create_cancel_policy'))
                            <a href="{{route('cancel.policy.create')}}">
                                <button type="button" data-toggle="tooltip" class="btn btn-icon btn-success float-right"
                                        style="margin: 10px;">
                                    <i class="wb-plus"
                                       title="@lang("$string_file.add")"></i>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="fa-television" aria-hidden="true"></i>
                        @lang("$string_file.cancel_policies")
                    </h3>
                </header>

                <div class="panel-body container-fluid">
                   
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.title")</th>
                            <th>@lang("$string_file.description")</th>
                            <th>@lang("$string_file.service_area")</th>
                            <th>@lang("$string_file.application")</th>
                            <th>@lang("$string_file.service_type")</th>
                            <th>@lang("$string_file.segment")</th>
                            <th>@lang("$string_file.service_type")</th>
                            <th>@lang("$string_file.cancellation_charges")</th>
                            
                            <th>@lang("$string_file.status")</th>
                            @if(Auth::user('merchant')->can('edit_cancel_policy') || Auth::user('merchant')->can('delete_cancel_policy'))
                                <th>@lang("$string_file.action")</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $cancel_policies->firstItem() @endphp
                        @foreach($cancel_policies as $cancel_policy)
                            <tr>
                                <td>{{ $sr }}</td>

                                <td>{{ $cancel_policy->LanguageSingle ? $cancel_policy->LanguageSingle->title : "" }}</td>
                                <td>{{ $cancel_policy->LanguageSingle ? $cancel_policy->LanguageSingle->description : "" }}</td>

                                <td>{{ $cancel_policy->CountryArea->CountryAreaName }}</td>
                                <td>
                                    @switch($cancel_policy->application)
                                        @case(1)
                                        @lang("$string_file.user")
                                        @break
                                        @case(2)
                                        @lang("$string_file.driver")
                                        @break
                                    @endswitch
                                </td>
                                <td>{{ $cancel_policy->service_type ==1 ? trans("$string_file.now"):trans("$string_file.later")}}</td>
                                <td>{{ $cancel_policy-> segment_id ? $cancel_policy->Segment->Name($cancel_policy->merchant_id) : "" }}</td>

                                <td>
                                    @switch($cancel_policy->charge_type)
                                        @case(1)
                                        @lang("$string_file.fixed_amount")
                                        @break
                                        @case(2)
                                        @lang("$string_file.discount")
                                        @break
                                        @case(3)
                                        @lang("$string_file.according_to_commission_fare")
                                        @break
                                    @endswitch
                                </td>
                                <td>
                                    @switch($cancel_policy->charge_type)
                                        @case(1)
                                        {{ $cancel_policy->CountryArea->Country->isoCode." ".$cancel_policy->cancellation_charges }}
                                        @break
                                        @case(2)
                                        {{ $cancel_policy->cancellation_charges }} %
                                        @break
                                    @endswitch
                                </td>
                               
                                <td>
                                    @if($cancel_policy->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @elseif($cancel_policy->status == 2)
                                        <span class="badge badge-default">@lang("$string_file.inactive")</span>
                                   
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.deleted")</span>
                                    @endif
                                </td>
                                @if(Auth::user('merchant')->can('edit_cancel_policy') || Auth::user('merchant')->can('delete_cancel_policy'))
                                    <td>
                                        @if(Auth::user('merchant')->can('edit_cancel_policy') && in_array($cancel_policy->status,[1,2]))
                                            @if($change_status_permission)
                                                @if($cancel_policy->status == 1)
                                                    <a href="{{ route('cancel.policy.change-status',['id'=>$cancel_policy->id,'status'=>2]) }}"
                                                       data-original-title="@lang("$string_file.inactive")"
                                                       data-toggle="tooltip" data-placement="top"
                                                       class="btn btn-sm btn-danger menu-icon btn_eye_dis action_btn"> <i
                                                                class="fa fa-eye-slash"></i> </a>
                                                @else
                                                    <a href="{{ route('cancel.policy.change-status',['id'=>$cancel_policy->id,'status'=>1]) }}"
                                                       data-original-title="@lang("$string_file.active")"
                                                       data-toggle="tooltip"
                                                       data-placement="top"
                                                       class="btn btn-sm btn-success menu-icon btn_eye action_btn"> <i
                                                                class="fa fa-eye"></i> </a>
                                                @endif
                                            @endif
                                            <a href="{{ route('cancel.policy.create',['id'=>$cancel_policy->id]) }}"
                                               data-original-title="@lang("$string_file.edit")"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               class="btn btn-sm btn-primary menu-icon btn_edit action_btn"> <i
                                                        class="fa fa-edit"></i> </a>
                                        @endif
                                        @if(Auth::user('merchant')->can('delete_cancel_policy') && $cancel_policy->status != 4 && $delete_permission)
                                            <a href="#" class="btn btn-sm btn-danger menu-icon"
                                               data-original-title="Delete"
                                               data-toggle="tooltip"
                                               data-placement="top" data-Id="{{$cancel_policy->id}}" onclick="EditDoc(this)"> <i
                                                        class="fa fa-trash"></i>
                                            </a>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $cancel_policies, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade text-left" id="EditDOc" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33"><b> @lang("$string_file.cancel_policy")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" enctype="multipart/form-data" action="{{ route('cancel.policy.delete') }}">
                    @csrf
                    <div class="modal-body text-center">
                        <label><b class="text-danger">@lang("$string_file.delete_warning")</b></label>
                        <input type="hidden" id="referral_system_id" name="cancel_policy_id">
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-sm btn-secondary" data-dismiss="modal"
                               value="@lang("$string_file.close")">
                        <input type="submit" class="btn btn-sm btn-danger" value="@lang("$string_file.delete")">
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script>
        function EditDoc(obj) {
            let ID = obj.getAttribute('data-ID');
            $(".modal-body #referral_system_id").val(ID);
            $('#EditDOc').modal('show');
        }

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
