{{-- <?php include 'security.php' ?> --}}

<html>
<head>
    <title>Secure Acceptance </title>
    <link rel="stylesheet" type="text/css" href="payment.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>

@if($is_live == 2)
    <form id="payment_confirmation" action="https://testsecureacceptance.cybersource.com/oneclick/pay" method="post"/>
    @else
    <form id="payment_confirmation" action="https://secureacceptance.in.cybersource.com/embedded/pay" method="post"/>
@endif
    <?php
        foreach($params as $name => $value) {
            echo "<input type=\"hidden\" id=\"" . $name . "\" name=\"" . $name . "\" value=\"" . $value . "\"/>\n";
        }
    ?>
<fieldset id="confirmation">
      
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
            <h5 class="card-title">Confirm ?</h5>
            
          </div>
        </div>
        <br><input type="submit" id="submit" value="Confirm"/>
        </div>
        <div class="col">
        </div>
      </div>
    </div>
    
</fieldset>


</form>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
