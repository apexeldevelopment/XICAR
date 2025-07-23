@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                       
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.payment_option_translation")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.translation")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tfoot></tfoot>
                        <tbody>
                        @php $sr = 1 @endphp
                        @foreach($payment as $value)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{$value->name}}</td>
                                <td>{{$value->PaymentOptionTranslation ? $value->PaymentOptionTranslation->name : ''}}</td>
                                    <td>
                                        <a href="{{ route('merchant.payment-option.edit',$value->id) }}"
                                           data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                            <i class="fa fa-edit"></i> </a>
                                    </td>
                                
                                @php $sr++ @endphp
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
