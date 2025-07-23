<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .submit-btn {
            width: 100%;
            background-color: #28a745;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    
    <div class="container">
        <h2>Secure Payment</h2>
        <form name="form" id="x1" method="POST" action="https://paynow.netcash.co.za/site/paynow.aspx" target="_top">
            
            <input type="hidden" name="M1" value="{{$M1}}">
            <input type="hidden" name="M2" value="{{$M2}}">
            <input type="hidden" name="Budget" value="Y"> <!-- Compulsory and should be Y-->

            <div class="input-group">
                <label for="p2">Transaction ID</label>
                <input type="text" class="trans" id="p2" name="p2" value="{{$transaction_id}}" readonly required>
            </div>

            <div class="input-group">
                <label for="p3">Description</label>
                <input type="text" id="p3" name="p3" required>
            </div>

            <div class="input-group">
                <label for="p4">Amount ({{$currency}})</label>
                <input type="text" id="p4" name="p4" required>
            </div>

            <button type="submit" class="submit-btn">Pay Now</button>

        </form>
    </div>

</body>
</html>
