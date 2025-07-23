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
                        @if(Auth::user('merchant')->can('reward_points'))
                            <a href="{{route('reward-points.create')}}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-plus" title="@lang('admin.Add') @lang('admin.reward.add')"></i>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="wb-users" aria-hidden="true"></i>
                        @lang("$string_file.reward_points")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%;">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.country")</th>
                            <th>@lang("$string_file.area")</th>
                            <th>@lang("$string_file.app")</th>
                            {{--<th>@lang('admin.rating_reward')</th>--}}
                            {{--<th>@lang('admin.writing_comment')</th>--}}
                            {{--<th>@lang('admin.referral_reward')</th>--}}
                            {{--<th>@lang("$string_file.trip_expenses")</th>--}}
                            {{--<th>@lang('admin.message772')</th>--}}
                            {{--<th>@lang('admin.commission_paid')</th>--}}
                            {{--<th>@lang('admin.peak_hours')</th>--}}
                            <th>@lang("$string_file.status")</th>
                            @if(Auth::user('merchant')->can('reward_points') || Auth::user('merchant')->can('reward_points'))
                                <th>@lang("$string_file.action")</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $sn = 0;
                        @endphp
                        @foreach ($reward_system as $reward)
                            <tr>
                                <td>{{ ++$sn }}</td>
                                <td>@if(!empty($reward->Country)) {{ $reward->Country->CountryName }} @else --- @endif</td>
                                <td>@if(!empty($reward->countryArea)) {{ $reward->countryArea->CountryAreaName}} @else --- @endif</td>
                                <td>
                                    @if($reward->application == 1)
                                        @lang("$string_file.user")
                                    @else
                                        @lang('admin.driver')
                                    @endif
                                </td>
                                {{--<td>--}}
                                {{--    @if ($reward->rating_reward == 1)--}}
                                {{--        <span class="text-success"> @lang('admin.enabled') </span>--}}
                                {{--        <br>Points :{{$reward->rating_points}}--}}
                                {{--        <br>Expire :{{$reward->rating_expire_in_days}}--}}
                                {{--    @else--}}
                                {{--        <span class="text-danger"> @lang('admin.disabled') </span>--}}
                                {{--    @endif--}}
                                {{--</td>--}}
                                {{--<td>--}}
                                {{--    @if ($reward->comment_reward == 1)--}}
                                {{--        <span class="text-success"> @lang('admin.enabled') </span>--}}
                                {{--        <br>Min Words :{{$reward->comment_min_words}} --}}
                                {{--        <br>Points :{{$reward->comment_points}}--}}
                                {{--        <br>Expire :{{$reward->comment_expire_in_days}}--}}
                                {{--    @else--}}
                                {{--        <span class="text-danger"> @lang('admin.disabled') </span>--}}
                                {{--    @endif--}}
                                {{--</td>--}}
                                {{--<td>--}}
                                {{--    @if ($reward->referral_reward == 1)--}}
                                {{--        <span class="text-success"> @lang('admin.enabled') </span>--}}
                                {{--        <br>Points :{{$reward->referral_points}}--}}
                                {{--        <br>Expire :{{$reward->referral_expire_in_days}}--}}
                                {{--    @else--}}
                                {{--        <span class="text-danger"> @lang('admin.disabled') </span>--}}
                                {{--    @endif--}}
                                {{--</td>--}}
                                <!--<td>-->
                                <!--    @if ($reward->trip_expense_reward == 1 || $reward->trip_expense_reward == 3 || $reward->trip_expense_reward == 4)-->
                                <!--        <span class="text-success"> @lang("$string_file.enable") </span>-->
                                <!--        <br>Amount Per Points :{{$reward->amount_per_points}}-->
                                <!--        <br>Expire :{{$reward->expenses_expire_in_days}}-->
                                <!--    @else-->
                                <!--        <span class="text-danger"> @lang("$string_file.disable") </span>-->
                                <!--    @endif-->
                                <!--</td>-->
                                {{--<td>--}}
                                {{--    @if ($reward->online_time_reward == 1)--}}
                                {{--        <span class="text-success"> @lang('admin.enabled') </span>--}}
                                {{--        <br>Per Hour Points :{{$reward->points_per_hour}}--}}
                                {{--        <br>Expire :{{$reward->online_time_expire_in_days}}--}}
                                {{--    @else--}}
                                {{--        <span class="text-danger"> @lang('admin.disabled') </span>--}}
                                {{--    @endif--}}
                                {{--</td>--}}
                                {{--<td>--}}
                                {{--    @if ($reward->commission_paid_reward == 1)--}}
                                {{--        <span class="text-success"> @lang('admin.enabled') </span>--}}
                                {{--        <br>Comission Per Points :{{$reward->commission_amount_per_point}}--}}
                                {{--        <br>Expire :{{$reward->commission_expire_in_days}}--}}
                                {{--    @else--}}
                                {{--        <span class="text-danger"> @lang('admin.disabled') </span>--}}
                                {{--    @endif--}}
                                {{--</td>--}}
                                {{--<td>--}}
                                {{--    @if ($reward->peak_hours == 1)--}}
                                {{--        <span class="text-success"> @lang('admin.enabled') </span>--}}
                                {{--    @else--}}
                                {{--        <span class="text-danger"> @lang('admin.disabled') </span>--}}
                                {{--    @endif--}}
                                {{--</td>--}}
                                <td>
                                    @if ($reward->status == 1)
                                        <span class="text-success"> @lang("$string_file.active") </span>
                                    @else
                                        <span class="text-danger"> @lang("$string_file.deactivated") </span>
                                    @endif
                                </td>
                                <td>
                                    @if(Auth::user('merchant')->can('reward_points'))
                                        <a class="mr-1 btn btn-sm btn-warning"
                                           href="{{route('merchant.rewardSystem.edit' , ['id' => $reward->id])}}">
                                            <span class="fas fa-edit"></span>
                                        </a>
                                    @endif
                                    @if(Auth::user('merchant')->can('reward_points'))
                                        <button class="btn btn-sm btn-danger" onclick="
                                            if(confirm('Do you want to delete ?')) {
                                            $('#delete-reward-{{$reward->id}}').submit();
                                            }
                                            ">
                                            <span class="fas fa-trash"></span>
                                        </button>
                                        <form id="delete-reward-{{$reward->id}}" method="post"
                                              action="{{route('merchant.rewardSystem.delete' , ['id' => $reward->id])}}">
                                            @csrf
                                            @method('delete')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="pagination1 float-right">{{ $reward_system->appends($data)->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
