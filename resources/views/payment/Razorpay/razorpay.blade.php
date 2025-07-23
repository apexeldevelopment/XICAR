<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            text-align: center;
        }
        .container {
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
        }
        button {
            padding: 12px 20px;
            font-size: 18px;
            color: white;
            background-color: #3399cc;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="container">
        <button id="rzp-button1">Pay Now</button>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        var options = {
            "key": "{{$data['order_id']}}", 
            "amount": "{{$data['amount']}}", 
            "currency": "INR",
            "name": "{{$data['business_name']}}",
            "description": "RazorPay Payment",
            "order_id": "{{$data['order_id']}}",
            "callback_url": "{{ route('razorpay-callback') }}", 
            "prefill": {
                "name": "{{$data['user_name']}}",
                "email": "{{$data['email']}}"
            },
            "notes": {
                "address": "{{$data['address']}}"
            },
            "theme": {
                "color": "#3399cc"
            }
        };

        var rzp1 = new Razorpay(options);
        document.getElementById('rzp-button1').onclick = function(e) {
            rzp1.open();
            e.preventDefault();
        }
    </script>

</body>
</html>
