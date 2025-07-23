@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('users.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="far fa-user" aria-hidden="true"></i>
                        @lang("common.user") @lang("common.address")
                    </h3>
                </header>
                <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                       style="width:100%">
                    <thead>
                    <tr>

                        <th>@lang("common.sn")</th>
                        <th>@lang("common.category")</th>
                        <th> @lang('common.address_line_1')</th>
                        <th> @lang('common.landmark')</th>
                        <th>@lang('common.address')</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php $sr = $user_address->firstItem() @endphp

                    @foreach($user_address as $address)
                    <tr>

                        <td>{{$sr}}</td>
                        <td>
                            @if($address->category == 1)
                                <span>{{trans('common.home')}}</span>
                                @elseif($address->category == 2)
                                <span>{{trans('common.work')}}</span>
                            @else
                            <span>{{trans('common.other')}}</span>
                            @endif
                        </td>
                        <td>
                            {{$address->house_name ? $address->house_name : ''}}
                            <br>
                            {{$address->floor ? $address->floor : ''}}
                            <br>
                             {{$address->building ? $address->building : ''}}
                        </td>
                        <td> {{$address->land_mark ? $address->land_mark : ''}}</td>
                        <td>{{$address->address ? $address->address : ''}}</td>

                    </tr>
                    @php $sr++  @endphp
                    @endforeach
                    </tbody>
                    @include('merchant.shared.table-footer', ['table_data' => $user_address,'data' => []])
                </table>
            </div>
        </div>
    </div>

@endsection