@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="mr--10 ml--10">
                        <div class="row " style="margin-right: 0rem;margin-left: 0rem">
                            <div class="col-md-2 col-sm-5">
                                <h3 class="panel-title"><i class="fa-users" aria-hidden="true"></i>
                                    @lang("$string_file.all_driver")</h3>
                            </div>
                            <div class="col-md-10 col-sm-7">
                                @if(!empty($info_setting) && $info_setting->view_text != "")
                                    <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                            data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                        <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                                    </button>
                                @endif
                                @if(Auth::user('merchant')->can('create_drivers'))
                                    <a href="{{route('driver.add')}}">
                                        <button type="button" class="btn btn-icon btn-success float-right"
                                                style="margin:10px">
                                            <i class="wb-plus"
                                               title="@lang("$string_file.add_driver")"></i>
                                        </button>
                                    </a>
                                    @if($export_permission)
                                    <a href="{{route('excel.driver',$arr_search)}}" data-toggle="tooltip">
                                        <button type="button" class="btn btn-icon btn-primary float-right"
                                                style="margin:10px">
                                            <i class="wb-download"
                                               title="@lang("$string_file.export_excel")"></i>
                                        </button>
                                    </a>
                                    @endif
                                @endif

                            </div>
                        </div>
                        <div class="row " style="margin-right: 0rem;margin-left: 0rem">
                            <div class="col-md-12 col-sm-7">
                                @if(Auth::user('merchant')->can('basic_driver_signup'))
                                    <a href="{{ route('merchant.driver.basic') }}">
                                        <button type="button" class="btn btn-icon btn-primary float-right"
                                                style="margin:10px">
                                            @lang("$string_file.basic_signup_completed")
                                            <span class="badge badge-pill">{{ $basicDriver }}
                                    </span>
                                        </button>
                                    </a>
                                @endif
                                @if(Auth::user('merchant')->can('pending_drivers_approval'))
                                    <a href="{{ route('merchant.driver.temp-doc-pending.show') }}">
                                        <button type="button" class="btn btn-icon btn-info float-right"
                                                style="margin:10px">
                                            @lang("$string_file.temp_doc_approve")
                                            <span class="badge badge-pill">{{ $tempDocUploaded }}
                                    </span>
                                        </button>
                                    </a>
                                    <a href="{{ route('merchant.driver.pending.show') }}">
                                        <button type="button"
                                                class="btn btn-icon btn-warning float-right" style="margin:10px">
                                            @lang("$string_file.pending_driver_approval")
                                            <span class="badge badge-pill">{{ $pendingdrivers }}
                                        </span>
                                        </button>
                                    </a>
                                @endif
                                @if(Auth::user('merchant')->can('pending_drivers_approval') && $config->driver_training == 1)
                                    <a href="{{ route('merchant.driver.training.show') }}">
                                        <button type="button"
                                                class="btn btn-icon btn-danger float-right" style="margin:10px">
                                            @lang("$string_file.pending") @lang("$string_file.training")
                                            <span class="badge badge-pill">{{ $pendingTrainingDrivers }}
                                    </span>
                                        </button>
                                    </a>
                                @endif
                                
                                    <a href="{{ route('merchant.driver.pending.details') }}">
                                        <button type="button" class="btn btn-icon btn-danger float-right"
                                                style="margin:10px">
                                            @lang("$string_file.approval_drivers_details")
                                            <span class="badge badge-pill">{{ $not_approve_driver_details }}
                                        </span>
                                        </button>
                                    </a>
                               
                                @if(Auth::user('merchant')->can('rejected_drivers'))
                                    <a href="{{ route('merchant.driver.rejected') }}">
                                        <button type="button" class="btn btn-icon btn-danger float-right"
                                                style="margin:10px">
                                            @lang("$string_file.rejected_drivers")
                                            <span class="badge badge-pill">{{ $rejecteddrivers }}
                                        </span>
                                        </button>
                                    </a>
                                @endif
                                <a href="{{ route('merchant.driver.deleted') }}">
                                    <button type="button"
                                            class="btn btn-icon btn-dark float-right" style="margin:10px">
                                        @lang("common.deleted") @lang("$string_file.drivers")
                                        <span class="badge badge-pill"></span>
                                    </button>
                                </a>
                            </div>
                        </div>
                    </div>
                </header>
                <div class="panel-body container-fluid">
                    {!! $search_view !!}
                    {{-- <div class="row">
                        <div class="col-md-2">
                            <label for="">Select Entries</label>
                            <select class="form-control" name="entries" id="entries">
                                <option @if(!empty($per_page)) @if($per_page == 50) selected @endif @endif value="100">50</option>
                                <option @if(!empty($per_page)) @if($per_page == 100) selected @endif @endif value="100">100</option>
                                <option @if(!empty($per_page)) @if($per_page == 200) selected @endif @endif value="200">200</option>
                                <option @if(!empty($per_page)) @if($per_page == 500) selected @endif @endif value="500">500</option>
                            </select>
                        </div>
                    </div> --}}
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th> @lang("$string_file.id")</th>
                            <th>@lang("$string_file.service_area") </th>
                            <th>@lang("$string_file.driver_details")</th>
                            @if($config->gender == 1)
                                <th>@lang("$string_file.gender")</th>
                            @endif
                            <th>@lang("$string_file.service_statistics")</th>
                            {{--                            <th>@lang("$string_file.referral_code")</th>--}}
                            <th>@lang("$string_file.transaction_amount")</th>
                            <th>@lang("$string_file.registered_date")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.last_location_date_time")  </th>
                            <th>@lang("$string_file.last_location_updated")  </th>
                            @if($config->driver_remarks_and_history == 1)
                                <th>@lang("$string_file.driver") @lang("$string_file.remarks") @lang("$string_file.history")</th>
                            @endif
                            @if($config->driver_live_tracking_web == 1)
                                <th>@lang("$string_file.driver") @lang("$string_file.live") @lang("$string_file.tracking")</th>
                            @endif
                            @if($config->driver_kin_person_details_on_signup == 1)
                                <th>@lang("$string_file.kin_person_details")</th>
                            @endif
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $drivers->firstItem() @endphp
                        @foreach($drivers as $driver)
                            <tr>
                                <td>{{$sr}}</td>
                                <td><a href="{{ route('driver.show',$driver->id) }}"
                                       class="hyperLink">{{ $driver->merchant_driver_id }}</a>
                                </td>
                                <td>
                                    {{ $driver->CountryArea->CountryAreaName }}
                                    @if($config->enable_super_driver == 1 && isset($driver->is_super_driver) && $driver->is_super_driver == 1)
                                        <br><span class="badge badge-info">@lang("$string_file.special")</span>
                                    @endif
                                    @if(isset($config->driver_agent_enable) && $config->driver_agent_enable == 1 && !empty($driver->agent_id))
                                        <br><span class="badge badge-info">{{ $driver->Agent->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="long_text">
                                        {{ is_demo_data($driver->fullName,$driver->Merchant) }}<br>
                                        {{ is_demo_data($driver->phoneNumber,$driver->Merchant) }}<br>
                                        {{ is_demo_data($driver->email,$driver->Merchant) }}
                                    </span>
                                </td>
                                @if($config->gender == 1)
                                    @switch($driver->driver_gender)
                                        @case(1)
                                        <td>@lang("$string_file.male")</td>
                                        @break
                                        @case(2)
                                        <td>@lang("$string_file.female")</td>
                                        @break
                                        @default
                                        <td>------</td>
                                    @endswitch
                                @endif
                                <td>
                                    @if($driver->segment_group_id == 1)
                                        @php
                                            $arr_segment = array_pluck($driver->Segment,'slag');

                                        @endphp
                                        @if(array_intersect($arr_segment,$booking_segment))
                                            @php
                                                $bookings = $driver->Booking->where('booking_status',1005)->count();

                                            @endphp
                                            <a href="{{ route('merchant.driver.jobs',['booking',$driver->id]) }}">
                                                <span class="badge badge-success font-weight-100">@lang("$string_file.rides") : {{ $bookings }}</span>
                                            </a>
                                            <br>
                                        @endif
                                        @if(array_intersect($arr_segment,$order_segment))
                                            @php
                                                $orders = $driver->Order->where('order_status',11)->count();
                                            @endphp
                                            <a href="{{ route('merchant.driver.jobs',['order',$driver->id]) }}">
                                                <span class="badge badge-success font-weight-100">@lang("$string_file.orders") : {{ $orders }}</span>
                                            </a>
                                        @endif
                                    @else
                                        @php
                                            $handyman_orders = isset($driver->HandymanOrder) ? $driver->HandymanOrder->where('order_status',7)->count() : 0;
                                        @endphp
                                        <a href="{{ route('merchant.driver.jobs',['handyman-order',$driver->id]) }}">
                                            <span class="badge badge-success font-weight-100">@lang("$string_file.bookings") : {{ $handyman_orders }}</span>
                                        </a>
                                    @endif
                                    <br>
                                    @lang("$string_file.rating") :
                                    @if (!empty($driver->rating) && $driver->rating>0)
                                        @while($driver->rating>0)
                                            @if($driver->rating >0.5)
                                                <img src="{{ view_config_image('static-images/star.png') }}"
                                                     alt='Whole Star'>
                                            @else
                                                <img src="{{ view_config_image('static-images/halfstar.png') }}"
                                                     alt='Half Star'>
                                            @endif
                                            @php $driver->rating--; @endphp
                                        @endwhile
                                    @else
                                        @lang("$string_file.not_rated_yet")
                                    @endif
                                </td>
                                <td style="width:250px;float:left">
                                    @php
                                    $merchant = new \App\Http\Controllers\Helper\Merchant();
                                    @endphp
                                    @if($driver->total_earnings)
                                        @lang("$string_file.earning")
                                        :- {{ $driver->CountryArea->Country->isoCode." ". $merchant->PriceFormat($merchant->TripCalculation($driver->total_earnings, $driver->merchant_id), $driver->merchant_id) }}
                                    @else
                                        @lang("$string_file.earning")
                                        :- @lang("$string_file.no_services")
                                    @endif
                                    <br>
                                    @if($driver->total_comany_earning)
                                        @lang("$string_file.company_profit")
                                        :- {{ $driver->CountryArea->Country->isoCode." ".$driver->total_comany_earning }}
                                    @else
                                        ---
                                    @endif
                                    <br>
                                    @if($config->driver_wallet_status == 1)
                                        @if($driver->wallet_money)
                                            @lang("$string_file.wallet_money") :- <a
                                                    href="{{ route('merchant.driver.wallet.show',$driver->id) }}">{{ $driver->wallet_money }}</a>
                                        @else
                                            @lang("$string_file.wallet_money") :- ------
                                        @endif
                                    @endif
                                    <br>
                                <td>{!! convertTimeToUSERzone($driver->created_at, $driver->CountryArea->timezone, null, $driver->Merchant) !!}</td>
                                <td>
                                    @if($driver->segment_group_id != 2)
                                        @if($driver->driver_admin_status == 1)
                                            @if($driver->login_logout == 1)
                                                @if($driver->online_offline == 1)
                                                    @if($driver->free_busy == 1)
                                                        <span class="badge badge-info">@lang("$string_file.busy")</span>
                                                    @else
                                                        @php
                                                            $minute = isset($driver->Merchant->DriverConfiguration->inactive_time) ? $driver->Merchant->DriverConfiguration->inactive_time : null;
                                                            $location_updated_last_time = '';
                                                            //date_default_timezone_set($driver->CountryArea->timezone);
                                                            if($minute > 0)
                                                            {
                                                                $date = new DateTime;
                                                                $date->modify("-$minute minutes");
                                                                $location_updated_last_time = $date->format('Y-m-d H:i:s');
                                                            }
                                                        @endphp
                                                        @if($driver->last_location_update_time >= $location_updated_last_time)
                                                            <span class="badge badge-success">@lang("$string_file.online")</span>
                                                        @else
                                                            <span class="badge badge-secondary">@lang("$string_file.location_update_expire")</span>
                                                        @endif
                                                    @endif
                                                @elseif($driver->segment_group_id == 4)
                                                    <span class="badge badge-info">@lang("$string_file.active")</span>
                                                @else
                                                    <span class="badge badge-info">@lang("$string_file.offline")</span>
                                                @endif
                                            @else
                                                <span class="badge badge-warning">@lang("$string_file.logout")</span>
                                            @endif
                                        @else
                                            <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($driver->segment_group_id != 2)
                                            @if(!empty($driver->current_latitude))
                                                @php $last_location_update_time = convertTimeToUSERzone($driver->last_location_update_time, $driver->CountryArea->timezone, null, $driver->Merchant); @endphp
                                               
                                                    {!! $last_location_update_time !!}
                                            @else
                                                ----------
                                            @endif
                                    @endif
                                </td>
                                <td>
                                    {{--@if(!empty($driver->segment_group_id) && $driver->segment_group_id == 2)
                                        @php
                                         $latitude = "";
                                          if(!empty($driver->WorkShopArea))
                                              {
                                                $address = $driver->WorkShopArea; // workshop area of driver
                                                $updated = $address->updated_at;
                                                $latitude = $address->latitude;
                                                $longitude = $address->longitude;
                                              }
//                                     @endphp
                                         @if(!empty($latitude))
                                            @php $last_location_update_time = convertTimeToUSERzone($updated, $driver->CountryArea->timezone, null, $driver->Merchant); @endphp
                                            <a class="map_address hyperLink " target="_blank"
                                               href="https://www.google.com/maps/place/{{ $latitude}},{{$longitude}}">
                                                {!! $last_location_update_time !!}
                                            </a>

                                         @else
                                          @lang("$string_file.workshop_area_not_found")
                                         @endif
                                    @else --}}
                                    @if($driver->segment_group_id != 2)
                                        @if(!empty($driver->ats_id) && !empty($socket_enable) && $driver->ats_id !="NA")
                                            <button class="badge badge-info border-0 view_current_location"
                                                    id="{{$driver->ats_id}}" driver_id="{{$driver->id}}"
                                                    timezone="{{$driver->CountryArea->timezone}}">@lang("$string_file.current_location")</button>
                                            <div id="{{$driver->id}}"></div>
                                        @else
                                            @if(!empty($driver->current_latitude))
                                               {{-- @php $last_location_update_time = convertTimeToUSERzone($driver->last_location_update_time, $driver->CountryArea->timezone, null, $driver->Merchant); @endphp --}}
                                                <a class="map_address hyperLink " target="_blank"
                                                   href="https://www.google.com/maps/place/{{ $driver->current_latitude }},{{$driver->current_longitude}}">
                                                     <i class="fa fa-map-marker" style="font-size: 24px" aria-hidden="true"></i>
                                                    {{-- {!! $last_location_update_time !!} --}}
{{--                                                    {{$driver->last_location_update_time}}--}}
                                                </a>
                                            @else
                                                ----------
                                            @endif
                                        @endif
                                    @endif
                                    {{--@endif--}}
                                </td>
                                @if($config->driver_remarks_and_history == 1)
                                    <td>
                                        <a href="{{route("merchant.driver.remarks.history", ['id'=>$driver->id])}}">
                                            <button type="button" class="btn btn-sm btn-primary">
                                                @lang("$string_file.view") @lang("$string_file.history")  <span class="badge badge-light">{{ count($driver->DriverRemarks) }}</span>
                                            </button>
                                        </a>
                                    </td>
                                @endif
                                @if($config->driver_live_tracking_web == 1)
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="setDriverIdForModal({{$driver->id}})" data-toggle="modal" data-target="#movingStatusModal">
                                            @lang("$string_file.driver") @lang("$string_file.live_tracking")
                                        </button>
                                    </td>
                                @endif
                                @if($config->driver_kin_person_details_on_signup == 1)
                                    <td>
                                        @php
                                            $details = !empty($driver->kin_details) ? json_decode($driver->kin_details, true) : [];

                                        @endphp
                                        @if(is_array($details) && array_key_exists(0, $details))
                                            <span class="badge badge-primary"><strong>@lang("$string_file.name")</strong> : {{$details[0]['kin_name']}}</span><br>
                                            <span class="badge badge-warning"><strong>@lang("$string_file.phone")</strong>: {{$details[0]['kin_phone_number']}}</span><br>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <div class="button-margin">
                                        @if(Auth::user('merchant')->can('edit_drivers'))
                                            <a href="{{ route('driver.add',$driver->id) }}"
                                               data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                               data-placement="top"
                                               class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                                <i class="wb-edit"></i> </a>
                                        @endif

                                        {{--                                        @if(Auth::user('merchant')->can('edit_drivers'))--}}
                                        {{--                                            <a href="{{ route('driver.editDocument',$driver->id) }}"--}}
                                        {{--                                               data-original-title="@lang('admin.editDoc')"--}}
                                        {{--                                               data-toggle="tooltip"--}}
                                        {{--                                               data-placement="top"--}}
                                        {{--                                               class="btn btn-sm btn-primary menu-icon btn_edit action_btn">--}}
                                        {{--                                                <i class="wb-file"></i> </a>--}}
                                        {{--                                        @endif--}}

                                        @if($config->subscription_package == 1 && Auth::user('merchant')->can('view_drivers'))
                                            <a href="{{ route('driver.activated_subscription',$driver->id) }}"
                                               data-original-title="@lang("$string_file.activated_subscription")"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               class="btn btn-sm btn-info menu-icon btn_edit action_btn">
                                                <i class="wb-indent-increase"></i> </a>
                                        @endif

                                        @if(Auth::user('merchant')->can('create_promotion'))
                                            <span data-target="#sendNotificationModel"
                                                  data-toggle="modal"
                                                  id="{{ $driver->id }}"><a
                                                        data-original-title="@lang("$string_file.send_notification")"
                                                        data-toggle="tooltip"
                                                        id="{{ $driver->id }}"
                                                        data-placement="top"
                                                        class="btn  text-white btn-sm btn-warning menu-icon btn_eye action_btn">
                                                    <i class="wb-bell"></i> </a></span>
                                        @endif

                                        @if($config->driver_wallet_status == 1 && Auth::user('merchant')->can('delete_drivers'))
                                            <a onclick="AddWalletMoneyMod(this)"
                                               data-ID="{{ $driver->id }}"
                                               data-original-title="@lang("$string_file.add_money")"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               class="btn text-white btn-sm btn-success">
                                                <i class="fa fa-money"></i> </a>
                                            <a href="{{ route('merchant.driver.wallet.show',$driver->id) }}"
                                               data-original-title="@lang("$string_file.wallet_transaction")"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               class="btn btn-sm menu-icon btn-primary btn_money action_btn">
                                                <span class="icon fa-window-maximize"></span></a>
                                        @endif

                                        @if(Auth::user('merchant')->can('view_drivers'))
                                            <a href="{{ route('driver.show',$driver->id) }}"
                                               data-original-title="@lang("$string_file.view_profile")"
                                               data-toggle="tooltip" data-placement="top"
                                               class="btn btn-sm btn-info menu-icon btn_detail action_btn">
                                                <span class="wb-user"></span></a>
                                        @endif

                                        <a onclick="getDeviceDetails({{$driver->id}})"
                                           class="btn btn-sm btn-info menu-icon btn_detail action_btn">
                                            <span class="wb-mobile"></span>
                                        </a>

                                        @if($driver->segment_group_id == 1 && Auth::user('merchant')->can('view_drivers'))
                                            <a href="{{ route('merchant.driver-vehicle',$driver->id) }}"
                                               data-original-title="@lang("$string_file.view_vehicles")"
                                               data-toggle="tooltip" data-placement="top"
                                               class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                                <i class="fa fa-car"></i></a>
                                        @endif
                                        @if($change_status_permission)
                                            @if($driver->driver_admin_status == 1 && Auth::user('merchant')->can('edit_drivers'))
                                                <a href="{{ route('merchant.driver.active.deactive',['id'=>$driver->id,'status'=>2]) }}"
                                                   data-original-title="@lang("$string_file.inactive")"
                                                   data-toggle="tooltip" data-placement="top"
                                                   class="btn btn-sm btn-danger menu-icon btn_eye_dis action_btn">
                                                    <i class="fa fa-eye-slash"></i> </a>
                                            @else
                                                <a href="{{ route('merchant.driver.active.deactive',['id'=>$driver->id,'status'=>1]) }}"
                                                   data-original-title="@lang("$string_file.active")"
                                                   data-toggle="tooltip" data-placement="top"
                                                   class="btn btn-sm btn-success menu-icon btn_eye action_btn">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            @endif
                                        @endif

                                        @if($driver->login_logout == 1 && Auth::user('merchant')->can('edit_drivers'))
                                            <a href="{{ route('merchant.driver.logout',$driver->id) }}"
                                               data-original-title="@lang("$string_file.logout")"
                                               data-toggle="tooltip" data-placement="top"
                                               class="btn btn-sm btn-secondary menu-icon btn_delete action_btn">
                                                <i
                                                        class="fa fa-sign-out"></i>
                                            </a>
                                        @endif

                                            @if(Auth::user('merchant')->can('delete_drivers') && $delete_permission)
                                                <button onclick="DeleteEvent({{ $driver->id }})"
                                                        type="submit"
                                                        data-original-title="@lang("$string_file.delete")"
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        class="btn btn-sm btn-danger menu-icon btn_delete action_btn">
                                                    <i
                                                            class="fa fa-trash"></i></button>
                                            @endif
                                        @if(isset($config->stripe_connect_enable) && $config->stripe_connect_enable == 1)
                                            <a href="{{ route('merchant.driver.stripe_connect',$driver->id) }}"
                                               data-original-title="Stripe Connect View"
                                               data-toggle="tooltip" data-placement="top"
                                               class="btn btn-sm btn-secondary menu-icon action_btn">
                                                <i class="icon wb-grid-9"></i>
                                            </a>
                                            <a href="{{ route('merchant.driver.stripe_connect.sync',$driver->id) }}"
                                               data-original-title="Stripe Connect Sync"
                                               data-toggle="tooltip" data-placement="top"
                                               class="btn btn-sm btn-secondary menu-icon action_btn">
                                                <i class="wb wb-refresh"></i>
                                            </a>
                                        @endif
                                        @if($config->dynamic_calling_button_from_admin == 1)
                                            @if($driver->calling_button == 1)
                                                <button onclick="removeCallEvent({{ $driver->id }}, 2)"
                                                    type="button"
                                                    data-original-title="@lang("$string_file.add") @lang("$string_file.or") @lang("$string_file.remove")  @lang("$string_file.calling") @lang("$string_file.button")"
                                                    data-toggle="tooltip"
                                                    data-placement="top"
                                                    class="btn btn-sm btn-success menu-icon btn_delete action_btn">
                                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                                    </button>
                                            @else
                                                <button onclick="removeCallEvent({{ $driver->id }}, 1)"
                                                         type="button"
                                                         data-original-title="@lang("$string_file.add") @lang("$string_file.or") @lang("$string_file.remove")  @lang("$string_file.calling") @lang("$string_file.button")"
                                                         data-toggle="tooltip"
                                                         data-placement="top"
                                                         class="btn btn-sm btn-danger menu-icon btn_delete action_btn">
                                                    <i class="fa fa-times" aria-hidden="true"></i>
                                                    </button>
                                            @endif
                                        @endif
                                            
                                            @if($driver->tracking_freeze_enable == 1)
                                                <button onclick="freezeTrackingScreen({{ $driver->id }}, 2)"
                                                    type="button"
                                                    data-original-title="@lang("$string_file.freeze_tracking_screen")"
                                                    data-toggle="tooltip"
                                                    data-placement="top"
                                                    class="btn btn-sm btn-success menu-icon btn_delete action_btn">
                                                    <i class="a fa-eercast" aria-hidden="true"></i>
                                                    </button>
                                            @else
                                                <button onclick="freezeTrackingScreen({{ $driver->id }}, 1)"
                                                         type="button"
                                                         data-original-title="@lang("$string_file.freeze_tracking_screen")"
                                                         data-toggle="tooltip"
                                                         data-placement="top"
                                                         class="btn btn-sm btn-danger menu-icon btn_delete action_btn">
                                                    <i class="a fa-eercast" aria-hidden="true"></i>
                                                    </button>
                                            @endif
                                            @if($config->driver_remarks_and_history == 1)
                                                <button onclick="saveDriverRemark('{{ $driver->first_name." ".$driver->last_name }}', {{$driver->id}})"
                                                        type="button"
                                                        data-original-title="@lang("$string_file.driver") @lang("$string_file.remarks")"
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        class="btn btn-sm btn-primary menu-icon action_btn">
                                                    <i class="fa fa-th-list" aria-hidden="true"></i>
                                                </button>
                                            @endif

                                            @if($config->driver_guarantor_details == 1)
                                            @php
                                                $details = !empty($driver->driver_guarantor_details) ? json_decode($driver->driver_guarantor_details, true) : [];

                                            @endphp
                                            @if(isset($details['name']))
                                            @php
                                                $image = get_image($details['image'], 'driver_additional_details', $driver->merchant_id);
                                            @endphp
                                            <button onclick="setGuaranterData('{{ $details['name'] }}', '{{ $details['phone'] }}', '{{ $details['description'] }}', '{{$image}}')"
                                                    type="button"
                                                    data-original-title="@lang("$string_file.view") @lang("$string_file.details")"
                                                    data-toggle="tooltip"
                                                    data-placement="top"
                                                    data-target="#guarantorDetailsModal"
                                                    class="btn btn-sm btn-primary menu-icon action_btn">
                                                <i class="fa fa-th-list" aria-hidden="true"></i>
                                            </button>
                                            @endif
                                            @endif

                                    </div>
                                </td>
                            </tr>
                            @php $sr++; @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $drivers, 'data' => $arr_search])
                    {{--                    <div class="pagination1 float-right">{{ $drivers->appends($arr_search)->links() }}</div>--}}
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade text-left" id="sendNotificationModel" tabindex="-1" role="dialog"
         aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33"><b>@lang("$string_file.send_notification")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('merchant.sendsingle-driver') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <label>@lang("$string_file.title"): </label>
                        <div class="form-group">
                            <input type="text" class="form-control" id="title"
                                   name="title"
                                   placeholder="" required>
                        </div>

                        <label>@lang("$string_file.message"): </label>
                        <div class="form-group">
                           <textarea class="form-control" id="message" name="message"
                                     rows="3"
                                     placeholder=""></textarea>
                        </div>

                        <label>@lang("$string_file.image"): </label>
                        <div class="form-group">
                            <input type="file" class="form-control" id="image"
                                   name="image"
                                   placeholder="">
                            <input type="hidden" name="persion_id" id="persion_id">
                        </div>
                        <label>@lang("$string_file.show_in_promotion")
                            : </label>
                        <div class="form-group">
                            <input type="checkbox" value="1" name="expery_check"
                                   id="expery_check_two">
                        </div>

                        <label>@lang("$string_file.expire_date"):</label>
                        <div class="form-group">
                            <input type="text" class="form-control customDatePicker1"
                                   id="datepicker-backend" name="date"
                                   placeholder="" disabled readonly>
                        </div>

                        <label>@lang("$string_file.url") (@lang("$string_file.optional")): </label>
                        <div class="form-group">
                            <input type="url" class="form-control" id="url"
                                   name="url"
                                   placeholder="">
                            <label class="danger">@lang("$string_file.example") : https://www.google.com/</label>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-outline-secondary btn-lg" data-dismiss="modal"
                               value="@lang("$string_file.close")">
                        <input type="submit" class="btn btn-outline-primary btn-lg" value="@lang("$string_file.send")">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade text-left" id="addWalletMoneyModel" tabindex="-1" role="dialog"
         aria-labelledby="myModalLabel33"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modal-title text-text-bold-600"
                           id="myModalLabel33"><b>@lang("$string_file.add_money_in_driver_wallet")</b></label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
{{--                    @csrf--}}
                    <div class="modal-body">
                        <label>@lang("$string_file.payment_method"): </label>
                        <div class="form-group">
                            <select class="form-control" name="payment_method" id="payment_method" required>
                                <option value="1">@lang("$string_file.cash")</option>
                                <option value="2">@lang("$string_file.non_cash")</option>
                            </select>
                        </div>

                        <label>@lang("$string_file.receipt_number"): </label>
                        <div class="form-group">
                            <input type="text" name="receipt_number" id="receipt_number" placeholder="" class="form-control" required>
                        </div>

                        <label for="transaction_type">
                            @lang("$string_file.transaction_type")<span
                                    class="text-danger">*</span>
                        </label>
                        <div class="form-group">
                            <select id="transaction_type" name="transaction_type" class="form-control" required>
                                <option value="1">@lang("$string_file.credit")</option>
                                <option value="2">@lang("$string_file.debit")</option>
                            </select>
                        </div>

                        <label>@lang("$string_file.amount"): </label>
                        <div class="form-group">
                            <input type="number" name="amount" id="amount" placeholder="@lang("$string_file.amount")"
                                   class="form-control" required>
                        </div>
                        <input type="hidden" name="add_money_driver_id" id="add_money_driver_id">
{{--                        <p id="amount_error" class="d-none text-danger">The amount must be atleast 1.</p>--}}

                        <label>@lang("$string_file.description"): </label>
                        <div class="form-group">
                            <textarea class="form-control" id="title1" rows="3" name="description"
                                      placeholder=""></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="reset" class="btn btn-secondary" data-dismiss="modal"
                               value="@lang("$string_file.close")">
                        <input type="submit" id="add_money_button" class="btn btn-primary"
                               value="@lang("$string_file.save")">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="device-details" tabindex="-1" role="dialog"
         aria-labelledby="deviceDetailsPopup" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="deviceDetailsPopupTitle">@lang("$string_file.device") @lang("$string_file.details") : <label id="driver-name"></label></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="model-data">

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="device-remark" tabindex="-1" role="dialog"
         aria-labelledby="deviceRemarkPopup" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="deviceRemarkPopupTitle">@lang("$string_file.device") @lang("$string_file.remarks") : <label id="remark-driver-name"></label></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('merchant.driver.remark.store')}}" method="post">
                    <div class="modal-body" id="model-data-remarks">
                            @csrf
                            <input type="hidden" name="remark_driver_id" id="remark_driver_id">
                            <textarea class="form-control" name="driver_remark" id="driver_remark"></textarea>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- The Modal -->
    <div class="modal" id="movingStatusModal">
        <div class="modal-dialog">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header"  >
                    <h4  class="modal-title" id="moving_status">@lang("$string_file.loading_pls_wait")</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <input type="hidden" id="moving_driver_id">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m10!1m8!1m3!1d116862.54554679655!2d90.40409584970706!3d23.749000170125925!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sbd!4v1550040341458" width="100%" height="450" frameborder="0" style="border:0" allowfullscreen></iframe>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>

{{--    Guaranto Details modal--}}
    <div class="modal fade" id="guarantorDetailsModal" tabindex="-1" role="dialog" aria-labelledby="guarantorDetailsLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="guarantorDetailsLabel">@lang("$string_file.driver") @lang("$string_file.guarantor") @lang("$string_file.details")</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center">
                            <img src="{{ asset('basic-images/android.png') }}"
                                 alt="Passport Photo"
                                 class="img-thumbnail"
                                 style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px; margin-top: 5px;"
                                 id="guarantor_image">
                        </div>
                        <div class="col-md-8">
                        <span class="" id="guarantor_name">
                        <strong>@lang("$string_file.name")</strong>: <span id="guarantor_name_text"></span>
                        </span><br>
                        <span class="" id="guarantor_phone">
                            <strong>@lang("$string_file.phone")</strong>: <span id="guarantor_phone_text"></span>
                        </span><br>
                        <span class="" id="guarantor_desc">
                            <strong>@lang("$string_file.description")</strong>: <span id="guarantor_desc_text"></span>
                        </span><br>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>

        function saveDriverRemark(name, id) {
            $("#remark-driver-name").html(name);
            $("#remark_driver_id").val(id)
            $('#device-remark').modal('show');
        }

        function setGuaranterData(name, phone, desc, url){
            document.getElementById('guarantor_image').src = url;
            $("#guarantor_name_text").html(name);
            $("#guarantor_phone_text").html(phone)
            $('#guarantor_desc_text').html(desc);
            $('#guarantorDetailsModal').modal('toggle');
        }

        function getDeviceDetails(driver_id) {
            $("#model-data").html(null);
            $("#sender-name").html(null);
            $("#loader1").show();
            $.ajax({
                method: 'GET',
                url: '<?php echo route('driver.device.details') ?>',
                data: {
                    driver_id: driver_id,
                },
                success: function (data) {
                    if (data.status == "success") {
                        $("#model-data").html(data.data.view);
                        $("#driver-name").html(data.data.name);
                        $('#device-details').modal('toggle');
                    } else {
                        alert(data.message);
                    }
                }
            });
            $("#loader1").hide();
        }

        $('#export-excel').on('click', function () {
            var action = '{{route("excel.driver")}}';
            var arr_param = [];
            var arr_param = $("#driver-search").serializeArray();
            $.ajax({
                type: "GET",
                data: {arr_param},
                url: action,
                success: function (data) {
                    console.log(data);
                }, error: function (err) {
                }
            });
        });
        $('#add_money_button').on('click', function () {
            $('#add_money_button').prop('disabled', true);
            $('#myLoader').removeClass('d-none');
            $('#myLoader').addClass('d-flex');
            var token = $('[name="_token"]').val();
            var payment_method = document.getElementById('payment_method').value;
            var receipt_number = document.getElementById('receipt_number').value;
            var amount = document.getElementById('amount').value;
            var transaction_type = document.getElementById('transaction_type').value;
            var desc = document.getElementById('title1').value;
            var driver_id = document.getElementById('add_money_driver_id').value;
            // console.log(payment_method,receipt_number,'recipt');
            // if(!receipt_number){
            //     alert()
            // }
            if (amount > 0) {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    type: "POST",
                    data: {
                        payment_method_id: payment_method,
                        receipt_number: receipt_number,
                        amount: amount,
                        transaction_type: transaction_type,
                        description: desc,
                        driver_id: driver_id
                    },
                    url: "{{ route('merchant.AddMoney') }}",
                    success: function (data) {
                        console.log(data,'hello');
                        if (data.result == 1) {
                            $('#myLoader').removeClass('d-flex');
                            $('#myLoader').addClass('d-none');
                            swal({
                                title: "@lang("$string_file.driver_account")",
                                text: "@lang("$string_file.money_added_successfully")",
                                icon: "success",
                                buttons: true,
                                dangerMode: true,
                            }).then((isConfirm) => {
                                if (isConfirm) {
                                    window.location.href = "{{ route('driver.index') }}";
                                } else {
                                    window.location.href = "{{ route('driver.index') }}";
                                }
                            });
                        }else{
                            alert(data.message);
                            $('#add_money_button').prop('disabled', false);
                        }
                    }, error: function (err) {
                        $('#myLoader').removeClass('d-flex');
                        $('#myLoader').addClass('d-none');
                    }
                });
            } else {
                $('#amount_error').removeClass('d-none');
                $('#add_money_button').prop('disabled', false);
            }

        });

        function AddWalletMoneyMod(obj) {
            let ID = obj.getAttribute('data-ID');
            $(".modal-body #add_money_driver_id").val(ID);
            $('#addWalletMoneyModel form')[0].reset();
            $('#amount_error').addClass('d-none');
            $('#addWalletMoneyModel').modal('show');
        }

    </script>

    <script>
        function DeleteEvent(id) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                text: "@lang("$string_file.delete_warning")",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        type: "POST",
                        data: {
                            id: id,
                        },
                        url: "{{ route('driverDelete') }}",
                    }).done(function (data) {
                        swal({
                        title: data,
                        text: "",
                        icon: "success", 
                        buttons: true,  
                    }).then(function() {
                        window.location.href = "{{ route('driver.index') }}";
                    });
                        
                        
                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }

        function removeCallEvent(id, action) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                text: "",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        type: "POST",
                        data: {
                            id: id,
                            action: action
                        },
                        url: "{{ route('driver.removeCallButton') }}",
                    }).done(function (data) {
                        swal({
                            title: "@lang("$string_file.success")",
                            text: "",
                            icon: "success",
                            buttons: true,
                        }).then(function() {
                            window.location.href = "{{ route('driver.index') }}";
                        });
                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }


        function freezeTrackingScreen(id, action) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                text: "@lang("$string_file.freeze_tracking_screen")",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        type: "POST",
                        data: {
                            id: id,
                            action: action
                        },
                        url: "{{ route('driver.freezeTrackingScreen') }}",
                    }).done(function (data) {
                        swal({
                            title: "@lang("$string_file.success")",
                            text: "",
                            icon: "success",
                            buttons: true,
                        }).then(function() {
                            window.location.href = "{{ route('driver.index') }}";
                        });
                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }

        function selectSearchFields() {
            var segment_id = $('#segment_id').val();
            var area_id = $('#area_id').val();
            var by = $('#by_param').val();
            var by_text = $('#keyword').val();
            if (segment_id.length == 0 && area_id == "" && by == "" && by_text == "" && driver_status == "") {
                alert("Please select at least one search field");
                return false;
            } else if (by != "" && by_text == "") {
                alert("Please enter text according to selected parameter");
                return false;
            } else if (by_text != "" && by == "") {
                alert("Please select parameter according to entered text");
                return false;
            }
        }

        // get location
        $('.view_current_location').on('click', function () {
            var ats_id = $(this).attr("id");
            var driver_id = $(this).attr("driver_id");
            var timezone = $(this).attr("timezone");

            if (ats_id == "" || ats_id == "NA") {
                alert("@lang("$string_file.ats_id_error")");
                return true;
            }
            var token = $('[name="_token"]').val();
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                type: "POST",
                data: {
                    "ats_id": ats_id,
                    "driver_timezone": timezone,
                },
                url: "{{ route('merchant.get-lat-long') }}",
                success: function (data) {
                    console.log(data);
                    $("#" + driver_id).html(data);
                }, error: function (err) {
                    $("#" + driver_id).text("No Data");
                }
            });
        });
        
        // $('#entries').on('change', function() {
        //     var selectedValue = $(this).val(); 
        //     var baseUrl = "{{ route('driver.index') }}"; 
        //     var currentUrl = baseUrl + window.location.search; // Get the current URL including existing query parameters
            
        //     // Parse the query parameters into an object
        //     var queryParams = new URLSearchParams(window.location.search);
        //     if (queryParams.has('per_page')) {
        //         queryParams.set('per_page', selectedValue);
        //     } else {
        //         queryParams.append('per_page', selectedValue);
        //     }
            
        //     // Construct the updated URL with the modified query parameters
        //     var updatedUrl = baseUrl + '?' + queryParams.toString();
        //     location.href = updatedUrl;
        // });

    function setDriverIdForModal(id){
        $("#moving_driver_id").val(id);
    }

    function changeMap(driverId){
        $.ajax({
        url: '{{route('ajax.services.getDriverMovingStatus')}}',
        type: 'GET',
        data: { driver_id: driverId },
            success: function (response) {
                if (response.latitude && response.longitude) {
                    var newMapUrl = `https://www.google.com/maps/embed/v1/place?key=${response.key}&q=${response.latitude},${response.longitude}`;
                    $('#movingStatusModal iframe').attr('src', newMapUrl);
                    if(response.working_with_redis == 1){
                        $("#moving_status").html(`
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{asset('basic-images/car-speed.png')}}" width="50" height="50">
                            </div>
                        `);
                    }
                    else if (response.moving_location_distance !== null && response.moving_location_distance !== undefined && response.moving_location_distance > 70) {
                        $("#moving_status").html(`
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{asset('basic-images/car-speed.png')}}" width="50" height="50">
                                <span> @lang("$string_file.moving")</span>
                            </div>
                        `);
                    } else {
                    $("#moving_status").html(`
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="{{asset('basic-images/traffic-light.png')}}" width="50" height="50">
                            <span> @lang("$string_file.still")</span>
                        </div>
                    `);
                    }
                }
            },
            error: function (error) {
                console.log('Error fetching driver location:', error);
            }
        });
    }

   $(document).ready(function () {
    var intervalId;
    $('#movingStatusModal').on('shown.bs.modal', function () {
        var driverId = $("#moving_driver_id").val();
        if (driverId) {
            clearInterval(intervalId);
            changeMap(driverId);
            intervalId = setInterval(function(){
                changeMap(driverId);
            }, 5000);
        }
    });

    $('#movingStatusModal').on('hidden.bs.modal', function () {
        clearInterval(intervalId); // Stop interval when modal is closed
    });
});


    </script>
@endsection
