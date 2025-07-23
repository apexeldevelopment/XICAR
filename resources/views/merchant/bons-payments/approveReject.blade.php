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
                        @lang("$string_file.bons_payment_gateway_approval_request")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.bank_name")</th>
                            <th>@lang("$string_file.account_name")</th>
                            <th>@lang("$string_file.document_image")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tfoot></tfoot>
                        <tbody>
                        @php $sr = 1 @endphp
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{$bonsQrPayment->BankName}}</td>
                                <td>{{$bonsQrPayment->AccountName}}</td>
                                <td>
                                        <a target="_blank"
                                                   href="{{get_image($transaction->checkout_id,'bons_qr_image',$transaction->merchant_id)}}">
                                                    <img src="{{get_image($transaction->checkout_id,'bons_qr_image',$transaction->merchant_id)}}"
                                                         alt="avatar"
                                                         style="width: 50px;height: 50px;border-radius:10px;">
                                        </a>
                                </td>
                                    <td>
                                        @if($transaction->request_status == 1)
                                            <a href="{{ route('merchant.bons_approval',$transaction->id) }}"
                                               class="btn btn-sm btn-info menu-icon btn_detail action_btn">
                                               @lang("$string_file.approve")</h3>
                                            </a>
                                            <!-- Button trigger modal -->
                                            <button type="button" class="btn btn-sm btn-danger reject_btn" data-toggle="modal" data-target="#rejectModal" data-id="{{ $transaction->id }}">
                                                <i class="fa fa-times"></i> @lang("$string_file.reject")
                                            </button>
                                        @elseif($transaction->request_status == 3)
                                            <span class="badge badge-danger">@lang("$string_file.rejected")</span>
                                        @elseif($transaction->request_status == 2)
                                            <span class="badge badge-success">@lang("$string_file.approved")</span>
                                        @endif
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
    <!-- Reject Confirmation Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <form id="rejectForm" method="POST" action="{{ route('merchant.bons_rejected') }}">
          @csrf
          <!-- Replace with your reject route -->
          <input type="hidden" name="id" id="reject_id">
    
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="rejectModalLabel">Confirm Rejection</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              Are you sure you want to reject?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger">Yes, Reject</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    
@endsection

@section('js')
<script>
    $(document).on('click', '.reject_btn', function () {
        var id = $(this).data('id');
        $('#reject_id').val(id);
    });
</script>
@endsection