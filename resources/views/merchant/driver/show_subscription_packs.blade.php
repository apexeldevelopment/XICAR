@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('driver.activated_subscription', $driver->id)}}">
                            <button type="button" title=""
                                    class="btn btn-icon btn-success float-right"  style="margin:10px"><i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="fa-cube" aria-hidden="true"></i>
                        @lang("$string_file.assign_subscription_pack_to") <b> {{$driver->first_name .' '.$driver->last_name}} ({{$driver->phoneNumber}}) </b>
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.image")</th>
                            <th>@lang("$string_file.details") </th>
                            <th>@lang("$string_file.maximum_rides") </th>
                            <th>@lang("$string_file.description")</th>
                            <th>@lang("$string_file.package_type")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        @php $sr = $packages->firstItem();
                                            $arr_type = \Config::get('custom.package_type');

                        @endphp
                        <tbody>
                        @forelse($packages as $package)
                            <tr>
                                <td>{{ $sr  }}</td>
                                <td>
                                    <img src="{{ get_image($package->image,'package') }}"
                                         align="center" width="80px" height="80px"
                                         class="img-radius"
                                         alt="Image">
                                </td>
                                <td>
                                    {{ $package->name }}<br>
                                    {{ $package->PackageDuration->name }} <br>
                                    {{ $package->price }}
                                </td>
                                <td>
                                    {{ $package->max_trip }}
                                </td>
                                <td>
                                    {{ $package->description }}
                                </td>
                                <td>
                                    {{ $arr_type[$package->package_type] }}
                                </td>
                                <td>
                                    @if($package->package_type == 1)

                                        <form id="assign-form-{{ $package->id }}" action="{{ route('driver.subscription-assign',$driver->id) }}" method="post">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="package" value="{{ $package->id }}">

                                        </form>
                                        <button onclick="assign({{ $package->id}})" type="submit"
                                                data-original-title="@lang("$string_file.assign")"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                class="btn btn-sm btn-primary menu-icon btn_delete action_btn"><i
                                                    class="fa fa-tasks"></i>
                                        </button>
                                    @else

                                        <form id="cash-form-{{ $package->id }}" action="{{ route('driver.assign-subscription-package',$driver->id) }}" method="post">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="package" value="{{ $package->id }}">
                                            <input type="hidden" name="payment_method_id" value="1">

                                        </form>
                                        <form id="wallet-form-{{ $package->id }}" action="{{ route('driver.assign-subscription-package',$driver->id) }}" method="post">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="package" value="{{ $package->id }}">
                                            <input type="hidden" name="payment_method_id" value="3">

                                        </form>
                                        <button onclick="cash_buy({{ $package->id }})" type="submit"
                                                data-original-title="@lang("$string_file.cash")"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                class="btn btn-sm btn-primary menu-icon btn_delete action_btn"><i
                                                    class="icon fa-money"></i>
                                        </button>

                                        @if(Auth::user('merchant')->Configuration->driver_wallet_status == 1)
                                            <button onclick="walletbuy({{ $package->id }})" type="submit"
                                                    data-original-title="@lang("$string_file.wallet")"
                                                    data-toggle="tooltip"
                                                    data-placement="top"
                                                    class="btn btn-sm btn-primary menu-icon btn_delete action_btn"><i
                                                        class="icon fa-window-maximize"></i>
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @empty
{{--                            <p class="alert alert-warning">No Subscription Package Found.</p>--}}

                        @endforelse
                        </tbody>
                    </table>
                    <div class="pagination1 float-right">{{ $packages->links() }}</div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>

        function cash_buy(package) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                {{--text: "@lang('admin.merchant_delete_restaurant')",--}}
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $("#cash-form-"+package).submit();
                } else {
                    swal("@lang('admin.package_not_activated')");
                }
            });
        }

        function walletbuy(package) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                {{--text: "@lang('admin.merchant_delete_restaurant')",--}}
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $("#wallet-form-"+package).submit();
                } else {
                    swal("@lang('admin.package_not_activated')");
                }
            });
        }
        function assign(package) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.do_you_want_to_assign")",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $("#assign-form-"+package).submit();
                } else {
                    swal("@lang("$string_file.package_not_assigned")");
                }
            });
        }
    </script>
@endsection

