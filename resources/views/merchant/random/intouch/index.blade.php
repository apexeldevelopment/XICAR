@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                            <a href="{{route('merchant.gateway.intouch.create')}}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-plus" title="@lang("common.add") @lang("common.operator")"></i>
                                </button>
                            </a>
                    </div>
                    <h3 class="panel-title"><i class="fa fa-money" aria-hidden="true"></i>
                        @lang("common.intouch") @lang("common.configuration")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <h4 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                         @lang("common.configuration")
                    </h4>
                    <table class="display nowrap table table-hover table-stripedw-full" id="customDataTable"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("common.sn")</th>
                            <th>@lang("common.country")</th>
                            <th>@lang("common.partner") @lang("common.id")</th>
                            <th>@lang("common.agency") @lang("common.code")</th>
                            <th>@lang("common.login") @lang("common.api")</th>
                            <th>@lang("common.password") @lang("common.api")</th>
                            <th>@lang("common.action")</th>
                           
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr=$intouch_data->firstItem() @endphp
                        @foreach($intouch_data as $data)
                            <tr>
                                <td>{{$sr}}</td>
                                @foreach($country_lit as $country)
                                @if($country->id==$data->country_id)
                                
                                <td>{{$country->CountryName}}</td>
                               
                                @endif
                                @endforeach
                                
                                
                                <td>{{$data->partner_id}}</td>
                               <td>{{$data->agency_code}}</td>
                                <td>{{$data->login_api}}</td>
                                <td>{{$data->password_api}}</td>
                                
                                
                                <td style="width:100px; float:left">
                                     <a href="{{route('merchant.gateway.intouch.edit',$data->id)}}"
                                           data-original-title="@lang("common.edit")" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-success menu-icon btn_eye action_btn">
                                            <i class="wb-flag"></i>
                                        </a>
                                     
                                       <a href="{{route('merchant.gateway.intouch.delete',$data->id)}}"
                                           data-original-title="@lang("common.delete")" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-danger menu-icon btn_eye action_btn">
                                            <i class="wb-trash"></i>
                                        </a>
                                        
                                </td>
                            </tr>
                        
                          @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                   
                </div>
            </div>
        </div>
    </div>
   
@endsection