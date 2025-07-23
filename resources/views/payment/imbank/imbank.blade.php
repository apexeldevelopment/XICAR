<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payment Form</title>
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
</head>
<body>
    <div class="container">
        <h2>Secure Payment</h2>
            <form id="donationForm" name="RequestforDebit" method="post" action="https://portal.host.iveri.com/Lite/Authorise.aspx">
                <label>Enter Amount ({{$currency}}):</label>
                <input type="number" id="donationAmount" placeholder="1000" value="{{$amount}}" required readonly>
            
                <label>Your Email:</label>
                <input type="email" id="donorEmail" placeholder="test@example.com" required>
            
                <input type="hidden" name="Lite_Order_Amount">
                <input type="hidden" name="Ecom_ConsumerOrderID" value="AUTOGENERATE">
                <input type="hidden" name="Ecom_BillTo_Online_Email">
                <input type="hidden" name="Lite_Order_LineItems_Product_1" value="Donation">
                <input type="hidden" name="Lite_Order_LineItems_Quantity_1" value="1">
                <input type="hidden" name="Lite_Order_LineItems_Amount_1">
                <input type="hidden" name="Lite_Merchant_ApplicationId" value="{{$application_id}}">
                <input type="hidden" name="Lite_Website_Successful_Url" value="{{route('imbank-callback',$merchantId)}}">
                <input type="hidden" name="Lite_Website_Fail_Url" value="{{route('imbank-callback',$merchantId)}}">
                <input type="hidden" name="Lite_Website_TryLater_Url" value="{{route('imbank-callback',$merchantId)}}">
                <input type="hidden" name="Lite_Website_Error_Url" value="{{route('imbank-callback',$merchantId)}}">
                <input type="hidden" name="Lite_ConsumerOrderID_PreFix" value="DONATION">
                <input type="hidden" name="Ecom_Payment_Card_Protocols" value="iVeri">
                <input type="hidden" name="Ecom_TransactionComplete" value="false">
            
                <button type="submit" class="checkout-btn">Pay</button>
            </form>

        <style>
        .checkout-btn {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 15px;
        }
        
        .checkout-btn:hover {
            background-color: #218838;
        }
        
        input {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        </style>

        <script>
        document.getElementById("donationForm").addEventListener("submit", function(e) {
            let amountInput = document.getElementById("donationAmount").value;
            let emailInput = document.getElementById("donorEmail").value;
        
            if (!amountInput || !emailInput) {
                alert("Please enter a valid amount and email.");
                e.preventDefault(); // Stop form submission if fields are empty
                return;
            }
        
            let amount = amountInput * 100; // Convert to cents
            let orderId = "DON-" + Math.floor(Math.random() * 1000000);
        
            document.querySelector('input[name="Lite_Order_Amount"]').value = amount;
            document.querySelector('input[name="Ecom_BillTo_Online_Email"]').value = emailInput;
            document.querySelector('input[name="Lite_Order_LineItems_Amount_1"]').value = amount;
            document.querySelector('input[name="Ecom_ConsumerOrderID"]').value = orderId;
        });
        </script>
    </div>
   
</body>
</html>

