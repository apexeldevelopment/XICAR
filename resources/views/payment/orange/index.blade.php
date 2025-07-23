<html>
<body>
<!-- Form with all request fields as prepared in PHP code above. Note all fields are hidden-->
<form method="post" name="paymentForm" id="paymentForm" action="https://api.paiementorangemoney.com/">
    <input type="hidden" name="S2M_IDENTIFIANT" value="{{$identifier}}">
    <input type="hidden" name="S2M_SITE" value="{{$site_id}}">
    <input type="hidden" name="S2M_TOTAL" value="{{$amount}}">
    <input type="hidden" name="S2M_REF_COMMANDE" value="{{$order_id}}">
    <input type="hidden" name="S2M_COMMANDE" value="{{$message}}">
    <input type="hidden" name="S2M_DATEH" value="{{$date}}">
    <input type="hidden" name="S2M_HTYPE" value="{{$algo}}">
    <input type="hidden" name="S2M_HMAC" value="{{$hmac}}">
    <!--    <input type="image" name="submit" src="Http://www.orange-money.sn/tpl/images/logo.jpg" style="border:0px; border-radius:10px; -moz-border-radius: 10px; -webkit-border-radius:10px;" alt="pay">-->
</form>
<!-- Automatic submission of request form to JCC upon load using JavaScript-->
<script language="JavaScript">
    document.forms["paymentForm"].submit();
</script>
</body>
</html>