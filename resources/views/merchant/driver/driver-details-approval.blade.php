@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('driver.index') }}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang("$string_file.all_driver")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa fa-taxi" aria-hidden="true"></i>
                        @lang("$string_file.drivers_details_for_approval")</h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! $search_view !!}
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.id")</th>
                            <th>@lang("$string_file.old_driver_details")</th>
                            <th>@lang("$string_file.updated_details")</th>
                            <th>@lang("$string_file.updated_at")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = 1 ;
                        @endphp
                            @foreach($drivers as $driver)
                                @php 
                                    $newChangesDriver = json_decode($driver->driver_details,true);
                                @endphp
                                <tr>
                                    <td>{{$sr}}</td>
                                    <td>{{$driver->driver_id}}</td>
                                    <td>
                                        <span class="long_text">
                                            {{ $driver->first_name . $driver->last_name }}<br>
                                            {{ $driver->phoneNumber }}<br>
                                            {{ $driver->email }} <br>
                                            <img src="{{ get_image($driver->profile_image,'driver') }}" alt="avatar" style="width: 50px;height: 50px;">
                                        </span>
                                    </td>
                                    <td>
                                        @if(isset($newChangesDriver['profile_image']))
                                        <img src="{{ get_image($newChangesDriver['profile_image'],'driver') }}" alt="avatar" style="width: 50px;height: 50px;">
                                        @else
                                        {{str_replace('"', '', trim(json_encode($newChangesDriver), '{}'))}}
                                        @endif
                                    </td>
                                    <td>{{$driver->updated_at}}</td>
                                    <td>
                                       
                                        @if($driver->is_reject == 1)
                                          <span class="badge bg-danger">Rejected</span>
                                       @else
                                        <a href="{{ route('driver.show.update',$driver->driver_id) }}"
                                           class="btn btn-sm btn-info menu-icon btn_detail action_btn">
                                           @lang("$string_file.approve")</h3>
                                        </a>
                                        <button data-toggle="modal" data-target="#exampleModal" data-id="{{ $driver->driver_id }}" class="btn btn-sm btn-danger reject_btn"> <i class="fa fa-times"></i>   @lang("$string_file.reject") </button>
                                       @endif
                                    </td>
                                </tr>
                                @php $sr++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                   <div class="pagination1 float-right">{{ $drivers->links() }}</div>
                </div>
            </div>
        </div>
    </div>


  <!-- Modal -->
  <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Reject Reason</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('merchant.driver.reject.details') }}" method="POST">
            @csrf
            <input type="hidden" name="driver_id" id="driver_id" >
        <div class="modal-body">
        <textarea name="reject_reason" class="form-control" placeholder="Enter reason hare..." required></textarea>
        </div>
        <div class="modal-footer">
          <button  class="btn btn-primary">Submit</button>
        </div>
    </form>
      </div>
    </div>
  </div>
@endsection
@section('js')
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        $('.toast').toast('show');
        $('.reject_btn').click(function (e) { 
            e.preventDefault();
            $('#driver_id').val($(this).data('id'));
        });
    </script>

    @error('reject_reason')
        <script>
            $('#exampleModal').modal('show');
        </script>
    @enderror
@endsection