<!DOCTYPE html>
<html lang="en">
<head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
            margin: 0;
        }
        .container {
            background: white;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .input-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .input-group .trans{
            background: lightgrey;
        }
        .submit-button {
            width: 100%;
            background-color: #28a745;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .submit-button:hover {
            background-color: #218838;
        }
    </style>
      <title>Payment</title>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/crypto-js.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/hmac-sha256.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/enc-base64.min.js"></script>
</head>


<body>

      <div class="container">
        <h2>Secure Payment</h2>
            <form action="{{$post_url}}" method="POST" onsubmit="generateSignature()">

                <input type="hidden" id="amount" name="amount" value="{{$amount}}" class="form" required>
                <input type="hidden" id="tax_amount" name="tax_amount" value ="0" class="form" required>
                <div class="input-group">
                    <label>Total Amount:</label>
                    <input type="text" id="total_amount"  name="total_amount" value="{{$amount}}" class="form"  required>
                </div>
                
                <div class="input-group">
                    <label>Transaction UUID:</label>
                    <input type="text" id="transaction_uuid" name="transaction_uuid" value="{{$trans_uuid}}" class="form" required>
                </div>
                <input type="hidden" id="product_code"  name="product_code" value ="EPAYTEST" class="form"  required>
                
                <input type="hidden" id="product_service_charge"  name="product_service_charge" value="0" class="form"  required>
                <input type="hidden" id="product_delivery_charge" name="product_delivery_charge" value="0" class="form"  required>
                <input type="hidden" id="success_url" name="success_url" value="{{route('esewa-success',$merchant_id)}}" class="form"  required>
                <input type="hidden" id="failure_url" name="failure_url"  value="{{route('esewa-fail',[$merchant_id,$trans_uuid])}}" class="form"  required>
                <input type="hidden" id="signed_field_names" name="signed_field_names" value="transaction_uuid,product_code,total_amount" class="form"  required>
                <input type="hidden" id="signature" name="signature" value="4Ov7pCI1zIOdwtV2BRMUNjz1upIlT/COTxfLhWvVurE=" class="form"  required>
              
        
        
              <input type="submit" name="buttonPostData" value="Proceed to Pay" id="buttonPostData" class="submit-button">

            </form>
      </div>

      <script>
	   var currentTime = new Date();
      var formattedTime = currentTime.toISOString().slice(2, 10).replace(/-/g, '') + '-' + currentTime.getHours() + currentTime.getMinutes() + currentTime.getSeconds();
            document.getElementById("transaction_uuid").value = {!! $trans_uuid !!};

			// Function to auto-generate signature
            function generateSignature() {
                  var total_amount = document.getElementById("total_amount").value;
                  var transaction_uuid = document.getElementById("transaction_uuid").value;
                  var product_code = document.getElementById("product_code").value;
                 
                  var hash = CryptoJS.HmacSHA256(`transaction_uuid=${transaction_uuid},product_code=${product_code},total_amount=${total_amount}`,'8gBm/:&EnhH.1/q');//secret key for 
                  var hashInBase64 = CryptoJS.enc.Base64.stringify(hash);
				  console.log(hashInBase64)
                  document.getElementById("signature").value = hashInBase64;
            }

            // Event listeners to call generateSignature() when inputs are changed
            document.getElementById("total_amount").addEventListener("input", generateSignature);
            document.getElementById("transaction_uuid").addEventListener("input", generateSignature);
            document.getElementById("product_code").addEventListener("input", generateSignature);
         
      </script>
	

</body>
</html>
