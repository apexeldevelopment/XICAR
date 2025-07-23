<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<form id="sub_form" action="https://secure.paygate.co.za/payweb3/process.trans" method="POST" >
    <input type="hidden" name="PAY_REQUEST_ID" value="{{$pay_request_id}}">
    <input type="hidden" name="CHECKSUM" value="{{$checksum}}">
</form>
<script type="text/javascript">
    $(document).ready(function(){
        $('#sub_form').submit();
    });
</script>
