<!DOCTYPE html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Ensures optimal rendering on mobile devices. -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge" /> <!-- Optimal Internet Explorer compatibility -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js" type="text/javascript"></script>
    <script src="{{$script_url}}"></script>
</head>

<body>

<div style="text-align: center; padding-bottom: 20px;">Amount to pay: <b>{{$amount}} {{$currency}}</b><br>Please choose a payment method below:</div>
<form style="text-align: center;" action="{{$redirect_url}}" class="paymentWidgets" data-brands="VISA MASTER AMEX">
</form>
<div style="text-align: center; padding-top: 20px;">
    <b>Note:-</b> Please <b>do not</b> press back, otherwise it would cancel the transaction.<br>
</div>
<script>
    var wpwlOptions = {    registrations: {        requireCvv: true,        hideInitialPaymentForms: true    },maskCvv: true}
</script>
</body>




