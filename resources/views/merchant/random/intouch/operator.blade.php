@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                            <a href="{{route('merchant.gateway.intouch.operator.add')}}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-plus" title="@lang("common.add") @lang("common.operator")"></i>
                                </button>
                            </a>
                    </div>
                    <h3 class="panel-title"><i class="fa fa-money" aria-hidden="true"></i>
                        @lang("common.intouch") @lang("common.operator")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <h4 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                         @lang("common.operator")
                    </h4>
                    <table class="display nowrap table table-hover table-stripedw-full" id="customDataTable"
                           style="width:100%">
                        <thead>
                            
                        <tr>
                            <th>@lang("common.sn")</th>
                            <th>@lang("common.operator")</th>
                            <th>@lang("common.cashin") @lang("common.service") @lang("common.id")</th>
                            <th>@lang("common.cashout") @lang("common.service") @lang("common.id")</th>
                            <th>@lang("common.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                             @php $sr = $intouch_operator_config->firstItem() @endphp
                             @foreach($intouch_operator_config as $operator_intouch)
                            <tr>
                                <td>{{$sr}}</td>
                                <td>{{$operator_intouch->operator}}</td>
                                <td>{{$operator_intouch->api_public_key}}</td>
                                <td>{{$operator_intouch->api_secret_key}}</td>
                                <td style="width:100px; float:left">
                                  
                                        
                                       <a href="{{route('merchant.gateway.intouch.operator.delete',$operator_intouch->id)}}"
                                           data-original-title="@lang("common.delete")" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-success menu-icon btn_eye action_btn">
                                            <i class="wb-trash"></i>
                                        </a>
                                     
                                </td>
                            </tr>
                        @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                   @include('merchant.shared.table-footer', ['table_data' => $intouch_operator_config, 'data' => 'view_text'])
                </div>
            </div>
        </div>
    </div>
   
@endsection