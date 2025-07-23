@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('carpooling.price_card.add')}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-plus" title="@lang("$string_file.add") @lang("$string_file.price_card")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="icon fa-money" aria-hidden="true"></i>
                        @lang("$string_file.price_card") @lang("$string_file.list")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.service") @lang("$string_file.area")</th>
                            <th>@lang("$string_file.distance") @lang("$string_file.charges")</th>
                            <th>@lang("$string_file.service") @lang("$string_file.charges")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $pricecards->firstItem();
                        @endphp
                        @foreach($pricecards as $price_card)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    {{ $price_card->CountryArea->CountryAreaName }}
                                </td>
                                <td>{{ $price_card->CountryArea->Country->isoCode.' '.$price_card->distance_charges }}</td>
                                <td>{{ ($price_card->service_charges > 0) ? $price_card->CountryArea->Country->isoCode.' '.$price_card->service_charges : "--" }}</td>
                                <td>
                                    @if($price_card->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('carpooling.price_card.add',[$price_card->id]) }}"
                                       data-original-title="Edit" data-toggle="tooltip" data-placement="top"
                                       class="btn btn-sm btn-warning menu-icon btn_edit action_btn"> <i
                                                class="fa fa-edit"></i> </a>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $pricecards, 'data' => []])
                </div>
            </div>
        </div>
    </div>
@endsection

