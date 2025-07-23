@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            
            <div class="panel panel-bordered">
                {!! Form::open(['name' => '', 'url' => route('merchant.subscription.report'), 'method' => 'GET']) !!} 
                <div class="panel-heading">
                    <div class="panel-actions">
                        <!-- Excel Button -->
                        <button type="submit" class="btn btn-icon btn-success float-right" style="margin:10px" name="report" value="excel" formtarget="_blank">
                            <i class="wb-download" title="@lang("$string_file.excel")"></i>
                        </button>
                    </div>
                    <h3 class="panel-title"><i class="fa-files-o" aria-hidden="true"></i>
                        @lang("$string_file.subscription") @lang("$string_file.report")
                    </h3>
                </div>
                <div class="panel-body container-fluid table-responsive">
                    <div class="table_search row">
                        <!-- Date Range Filter -->
                        <div class="col-md-4 col-xs-12 form-group active-margin-top">
                            <div class="input-daterange" data-plugin="datepicker">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="icon wb-calendar" aria-hidden="true"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control" name="start" value="{{ request('start') }}" placeholder="Start Date" />
                                </div>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">to</span>
                                    </div>
                                    <input type="text" class="form-control" name="end" value="{{ request('end') }}" placeholder="End Date" />
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Type Filter -->
                        <div class="col-md-2 col-xs-12 form-group active-margin-top">
                            <div class="">
                                <select class="form-control" name="vehicletype" id="vehicletype">
                                    <option value="">--@lang("$string_file.vehicle_type")--</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ request('vehicletype') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->VehicleTypeName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Entries Filter -->
                        <div class="col-md-2 col-xs-12 form-group active-margin-top">
                            <div class="">
                                <select class="form-control" name="entries" id="entries">
                                    <option value="20" {{ request('entries') == 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ request('entries') == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('entries') == 100 ? 'selected' : '' }}>100</option>
                                    <option value="150" {{ request('entries') == 150 ? 'selected' : '' }}>150</option>
                                    <option value="200" {{ request('entries') == 200 ? 'selected' : '' }}>200</option>
                                </select>
                            </div>
                        </div>

                        <!-- Search and Reset Buttons -->
                        <div class="col-sm-2 col-xs-12 form-group active-margin-top">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa fa-search" aria-hidden="true"></i>
                            </button>
                            <a href="{{ route('merchant.subscription.report') }}">
                                <button class="btn btn-success" type="button">
                                    <i class="fa fa-refresh" aria-hidden="true"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    {!! Form::close() !!}
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.subscription") @lang("$string_file.id")  </th>
                            <th>@lang("$string_file.driver") @lang("$string_file.id")</th>
                            <th>@lang("$string_file.payment_method")</th>
                            <th>@lang("$string_file.driver") @lang("$string_file.name")</th>
                            <th>@lang("$string_file.driver") @lang("$string_file.phone")</th>
                            <th>@lang("$string_file.vehicle") @lang("$string_file.category")</th>
                            <th>@lang("$string_file.subscription") @lang("$string_file.for") @lang("$string_file.date")</th>
                            <th>@lang("$string_file.earning") @lang("$string_file.on") @lang("$string_file.that")</th>
                            <th>@lang("$string_file.subscription") @lang("$string_file.amount")</th>
                            <th>@lang("$string_file.payment") @lang("$string_file.date")</th>
                            <th>@lang("$string_file.payment") @lang("$string_file.reference") @lang("$string_file.number")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $subscriptiondata->firstItem(); @endphp
                        @foreach($subscriptiondata as $item)
                            <tr>
                                <td>{{$sr}}</td>
                                <td>{{$item->id}}</td>
                                <td>{{ $item->driver_id }}</td>
                                <td>{{ $item->payment_method }}</td>
                                <td>{{ $item->driver_name }}</td>
                                <td>{{ $item->phoneNumber }}</td>
                                <td>{{ $item->vehicle_category }}</td>
                                <td>{{$item->subscription_date != null ? \Carbon\Carbon::parse($item->subscription_date)->format('d-m-Y') : '' }}</td>
                                <td>{{ $item->earning }}</td>
                                <td>{{ $item->subscription_fee }}</td>
                                <td>{{$item->transaction_date != null ? \Carbon\Carbon::parse($item->transaction_date)->format('d-m-Y') : '' }}</td>
                                <td>{{ $item->reference_id }}</td>
                            </tr>
                            @php $sr++; @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $subscriptiondata, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
