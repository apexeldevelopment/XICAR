@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                            <a href="{{ route('users.index') }}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-reply"></i>
                                </button>
                            </a>
                        @if($user->account_number == null)
                            <a href="{{ route('users.edit',$user->id) }}" class="text-reset">
                                <span>@lang('common.complete') @lang('common.your') @lang('common.bank') @lang('common.details') @lang('common.first')
                                    @lang('common.for') @lang('common.add') @lang('common.vehicle')
                                </span>
                            </a>
                        @else
                        <a href="{{route('merchant.user.vehicle_add',['id'=>$user->id])}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-plus" title="@lang("common.add") @lang("common.user")"></i>
                            </button>
                        </a>
                        @endif
                    </div>

                    <h3 class="panel-title"><i class="far fa-car" aria-hidden="true"></i>
                       <spam> {{ucwords($user->first_name)." ".$user->last_name}}</spam> ------> @lang("$string_file.vehicle") @lang("common.management")
                    </h3>
                </header>
                <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                       style="width:100%">
                    <thead>
                    <tr>
                        <th>@lang("common.sn")</th>
                        <th>@lang("$string_file.vehicle") @lang('common.details')</th>
                        <th>@lang("$string_file.vehicle") @lang('common.number')</th>
                        <th>@lang("$string_file.vehicle") @lang('common.color')</th>
                        <th>@lang("$string_file.vehicle") @lang('common.image')</th>
                        <th>@lang("$string_file.vehicle") @lang("$string_file.plate") @lang('common.number') @lang('common.image')</th>
                        <th>@lang('common.action')</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php $sr = $user_vehicle->firstItem() @endphp
                    @foreach($user_vehicle as $vehicle_list)
                    <tr>
                        <td>{{ $sr }}  </td>
                        <td>{{$vehicle_list->vehicleType->vehicleTypeName}}
                            <br>
                            {{$vehicle_list->vehicleMake->vehicleMakeName}}
                            <br>
                            {{isset($vehicle_list->vehicleModel) ? $vehicle_list->vehicleModel->vehicleModelName: ""}}
                        </td>
                        <td>{{$vehicle_list->vehicle_number}}</td>
                        <td>{{$vehicle_list->vehicle_color}}</td>
                        <td class="text-center">
                            <img src="{{ get_image($vehicle_list->vehicle_image,'user_vehicle_document') }}"
                                 alt="avatar" style="width: 100px;height: 100px;">
                        </td>
                        <td class="text-center">
                            <img src="{{ get_image($vehicle_list->vehicle_number_plate_image,'user_vehicle_document') }}"
                                 alt="avatar" style="width: 100px;height: 100px;">
                        </td>
                        <td>
                            <a href="{{route('merchant.user.vehicle.edit',['id'=>$vehicle_list->id])}}"
                               data-original-title="@lang("common.edit") @lang("$string_file.vehicle") "
                               data-toggle="tooltip"
                               data-placement="top"
                               class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                <i class="fa fa-edit"></i> </a>
                        </td>
                    </tr>
                    @php $sr++  @endphp
                    @endforeach
                    </tbody>
                </table>
                @include('merchant.shared.table-footer', ['table_data' => $user_vehicle, 'data' => []])

            </div>
        </div>
    </div>
@endsection
