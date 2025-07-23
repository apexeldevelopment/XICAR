<html>
<head>
    <title>Secure Acceptance</title>
    <link rel="stylesheet" type="text/css" href="payment.css"/>
    <script type="text/javascript" src="jquery-1.7.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
<form id="payment_form" action="{{route('confirm-crdb-pay', ['id'=>$id, 'calling_from'=>$calling_from, 'config_id'=>$payment_option_config])}}" method="post">
    <input type="hidden" name="access_key" value="{{$data['access_key']}}">
    <input type="hidden" name="profile_id" value="{{$data['profile_id']}}">
    <input type="hidden" name="transaction_uuid" value="{{$data['transaction_id']}}">
    <input type="hidden" name="signed_field_names" value="access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency">
    <input type="hidden" name="unsigned_field_names">
    <input type="hidden" name="signed_date_time" value="{{$data['signed_date_time']}}">
    <input type="hidden" name="locale" value="{{$data['locale']}}">
    
     <input type="hidden" name="is_live" value="{{$data['is_live']}}">
      <input type="hidden" name="secret_key" value="{{$data['secret_key']}}">
    <fieldset>
        
        <div id="paymentDetailsSection" class="section">
            <input type="hidden" name="transaction_type" value="{{$data['transaction_type']}}" ><br/>
            <input type="hidden" name="reference_number" value="{{$data['reference_number']}}" ><br/>
            <input type="hidden" name="currency" value="{{$data['currency']}}"><br/>
            <input type="hidden" name="amount" value="{{$data['amount']}}"><br/>
            <!--<input type="text" name="card_type" placeholder="CVV" size="25" ><br/>-->
            <!--<input type="text" name="card_number" placeholder="NUMBER" size="25"><br/>-->
            <!--<input type="text" name="card_expiry_date" placeholder="Expiry" size="25"><br/>-->
            
            
        </div>
    </fieldset>
    
    
    <div class="container text-center">
      <div class="row">
        <div class="col">
        </div>
        <div class="col">
              <div class="card">
          <div class="card-header">
            CRDB
          </div>
          <div class="card-body">
            <h5 class="card-title">Procees To Payment</h5>
            <p class="card-text">Amount: {{$data['amount']}}</p>
          </div>
        </div>
        <br><input type="submit" id="submit" name="submit" value="Submit"/>
        </div>
        <div class="col">
        </div>
      </div>
    </div>
    
    

</form>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>

@php

@endphp
