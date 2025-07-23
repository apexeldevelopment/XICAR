<html>
<head>
    <title>Secure Acceptance</title>
    <link rel="stylesheet" type="text/css" href="payment.css"/>
    <script type="text/javascript" src="jquery-1.7.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
  @if($data['url_type'] == 1)
    <form id="payment-form" action="https://api.maxicashapp.com/PayEntryPost" method="POST">
@else
    <form id="payment-form" action="https://api-testbed.maxicashapp.com/PayEntryPost" method="POST">
@endif
    <input type="hidden" name="PayType" value="MaxiCash">
    <input type="hidden" name="Amount" value="{{ $data['Amount'] }}">
    <input type="hidden" name="Currency" value="{{$data['Currency']}}">
    <input type="hidden" name="Telephone" value="{{ $data['Telephone'] }}">
    <input type="hidden" name="Email" value="{{ $data['Email'] }}">
    <input type="hidden" name="MerchantID" value="{{ $data['MerchantID'] }}">
    <input type="hidden" name="MerchantPassword" value="{{ $data['MerchantPassword'] }}">
    <input type="hidden" name="Language" value="En">
    <input type="hidden" name="Reference" value="{{ $data['Reference'] }}">
    <input type="hidden" name="accepturl" value="{{ $data['accepturl'] }}">
    <input type="hidden" name="cancelurl" value="{{ $data['cancelurl'] }}">
    <input type="hidden" name="declineurl" value="{{ $data['declineurl'] }}">
    <input type="hidden" name="notifyurl" value="{{ $data['notifyurl'] }}">
    <!-- Remove the submit button, we will submit automatically -->
    <!-- <input type="submit" value="Submit" style="padding: 10px 20px; font-size: 16px; cursor: pointer;"> -->
</form>

<script>
    // Automatically submit the form when the page loads
    window.onload = function() {
        document.getElementById('payment-form').submit();
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>
