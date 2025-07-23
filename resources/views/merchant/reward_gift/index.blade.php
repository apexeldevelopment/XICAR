@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        {{--@if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif --}}
                        @if(Auth::user('merchant')->can('reward_gift'))
                            <a href="{{route('reward-gifts.create')}}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-plus" title="@lang('admin.Add') @lang('admin.reward.add')"></i>
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="wb-users" aria-hidden="true"></i>
                        @lang("$string_file.reward_gifts")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%;">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.country")</th>
                            <th>@lang("$string_file.app")</th>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.image")</th>
                            <th>@lang("$string_file.reward_points")</th>
                            <th>@lang("$string_file.trips")</th>
                            <th>@lang("$string_file.amount")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $sn = 0;
                        @endphp
                        @foreach ($reward_gifts as $reward)
                            <tr>
                                <td>{{ ++$sn }}</td>
                                <td>@if(!empty($reward->Country)) {{ $reward->Country->CountryName }} @else --- @endif</td>
                                <td>
                                    @if($reward->application == 1)
                                        @lang("$string_file.user")
                                    @else
                                        @lang('admin.driver')
                                    @endif
                                </td>
                                <td>{{$reward->name}}</td>
                                <td>
                                    @if(isset($reward->image) && !empty($reward->image))
                                    <img src="{{ get_image($reward->image,'reward_gift',$merchant_id)  }}" align="center" width="100px" height="60px"
                                    class="img-radius"
                                    alt="reward-image"></td>
                                    @endif
                                <td>{{$reward->reward_points}}</td>
                                <td>{{$reward->trips}}</td>
                                <td>{{$reward->amount}}</td>
                                <td>
                                    @if ($reward->status == 1)
                                        <span class="text-success"> @lang("$string_file.active") </span>
                                    @else
                                        <span class="text-danger"> @lang("$string_file.deactivated") </span>
                                    @endif
                                </td>
                                <td>
                                    <a class="mr-1 btn btn-sm btn-warning"
                                           href="{{route('reward-gifts.edit',['id' => $reward->id])}}">
                                            <span class="fas fa-edit"></span>
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="
                                        if(confirm('Do you want to delete ?')) {
                                        $('#delete-reward-{{$reward->id}}').submit();
                                        }
                                        ">
                                        <span class="fas fa-trash"></span>
                                    </button>
                                    <form id="delete-reward-{{$reward->id}}" method="post"
                                          action="{{route('reward-gifts.delete' , ['id' => $reward->id])}}">
                                        @csrf
                                        @method('delete')
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endsection