@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                        {{isset($page_title_prefix) ? $page_title_prefix : ""}}
                        @lang("common.pending") @lang("common.users") @lang("$string_file.vehicle") @lang("common.approval")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("common.sn")</th>
                            <th> @lang("common.id")</th>
                            <th>@lang("common.user") @lang("common.details")</th>
                            <th>@lang("$string_file.vehicle") @lang("common.number")</th>
                            <th>@lang("common.registered") @lang("common.date")</th>
                            <th>@lang("common.update")</th>
                            <th>@lang("common.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                            @php $sr = $users->firstItem() @endphp
                            @foreach($users as $user)
                                <tr>
                                    <td>{{$sr}}</td>
                                    <td><a href="{{ route('users.show',$user->id) }}"
                                           class="address_link">{{ $user->user_merchant_id }}</a>
                                    </td>
                                    @if(Auth::user()->demo == 1)
                                        <td>
                                            {{ "********".substr($user->last_name, -2) }}<br>
                                            {{ "********".substr($user->UserPhone, -2) }} <br>
                                            {{ "********".substr($user->email, -2) }}

                                        </td>
                                    @else
                                        <td>{{ $user->first_name." ".$user->last_name }}<br>
                                            {{ $user->email }}<br>
                                            {{ $user->UserPhone }}</td>
                                    @endif
                                    <td>
                                        @foreach($user->UserVehicles as $vehicle)
                                            {{$vehicle->vehicle_number}},
                                        @endforeach
                                    </td>
                                    <td>{{ $user->created_at->toDateString() }}
                                    <br>
                                    {{ $user->created_at->toTimeString() }}</td>
                                    <td>{{ $user->updated_at->toDateString() }}
                                    <br>
                                    {{ $user->updated_at->toTimeString() }}</td>
                                    <td>
                                        <a href="{{ route('users.show',$user->id) }}"
                                           class="btn btn-sm btn-info menu-icon btn_detail action_btn"><span
                                                    class="fa fa-list-alt"
                                                    title="View User Profile"></span></a>
                                    </td>
                                </tr>
                                @php $sr++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $users, 'data' => []])
                </div>
            </div>
        </div>
    </div>
@endsection