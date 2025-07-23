<table class="table display nowrap table-striped table-bordered">
    <thead>
    <tr>
        <th>@lang("$string_file.device")</th>
        <th>@lang("$string_file.player_id") </th>
        <th>@lang("$string_file.apk_version") </th>
        <th>@lang("$string_file.model") </th>
        <th>@lang("$string_file.operating_system") </th>
        <th>@lang("$string_file.package_name")</th>
    </tr>
    </thead>
    <tbody>
    @foreach($device_details as $detail)
        <tr>
            <td>
                @if($detail['device'] == 1)
                    @lang("$string_file.android")
                @elseif($detail['device'] == 2)
                    @lang("$string_file.ios")
                @else
                    @lang("$string_file.admin")
                @endif
            </td>
            <td>{{$detail['player_id']}}</td>
            <td>{{$detail['apk_version']}}</td>
            <td>{{$detail['model']}}</td>
            <td>{{$detail['operating_system']}}</td>
            <td>{{$detail['package_name']}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
