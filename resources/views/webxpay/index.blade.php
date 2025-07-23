<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>WebXPay</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
    <h2>Payment Details</h2>
    <form action="{{$url}}" method="POST">
        <div class="form-group">
            <label for="first_name">First name:</label>
            <input type="text" class="form-control" id="first_name" value="{{$user_data['first_name']}}" name="first_name">
        </div>
        <div class="form-group">
            <label for="last_name">Last name:</label>
            <input type="text" class="form-control" id="last_name" value="{{$user_data['last_name']}}" name="last_name">
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" id="email" value="{{$user_data['email']}}" name="email">
        </div>
        <div class="form-group">
            <label for="contact_number">Contact Number:</label>
            <input type="text" class="form-control" id="contact_number" value="{{$user_data['contact_number']}}" name="contact_number">
        </div>
        <input type="hidden" name="address_line_one" value="{{$user_data['address_line_one']}}">
        <input type="hidden" name="address_line_two" value="{{$user_data['address_line_two']}}">
        <input type="hidden" name="city" value="{{$user_data['city']}}">
        <input type="hidden" name="state" value="{{$user_data['state']}}">
        <input type="hidden" name="postal_code" value="{{$user_data['postal_code']}}">
        <div class="form-group">
            <label for="country">Country:</label>
            <input type="text" class="form-control" id="country" value="{{$user_data['country']}}" name="country">
        </div>
        <div class="form-group">
            <label for="process_currency">Currency:</label>
            <input type="text" class="form-control" id="process_currency" value="{{$user_data['process_currency']}}" name="process_currency">
        </div>
        <input type="hidden" name="cms" value="{{$user_data['cms']}}">
        <input type="hidden" name="custom_fields" value="{{$user_data['custom_fields']}}">
        <input type="hidden" name="enc_method" value="{{$user_data['enc_method']}}">
        <input type="hidden" name="secret_key" value="{{$user_data['secret_key']}}">
        <input type="hidden" name="payment" value="{{$user_data['payment']}}">

        {{--First name: <input type="text" name="first_name" value="{{$user_data['first_name']}}"><br>--}}
        {{--Last name: <input type="text" name="last_name" value="{{$user_data['last_name']}}"><br>--}}
        {{--Email: <input type="text" name="email" value="{{$user_data['email']}}"><br>--}}
        {{--Contact Number: <input type="text" name="contact_number" value="{{$user_data['contact_number']}}"><br>--}}
        {{--<input type="hidden" name="address_line_one" value="{{$user_data['address_line_one']}}">--}}
        {{--<input type="hidden" name="address_line_two" value="{{$user_data['address_line_two']}}">--}}
        {{--<input type="hidden" name="city" value="{{$user_data['city']}}">--}}
        {{--<input type="hidden" name="state" value="{{$user_data['state']}}">--}}
        {{--<input type="hidden" name="postal_code" value="{{$user_data['postal_code']}}">--}}
        {{--Country: <input type="text" name="country" value="{{$user_data['country']}}"><br>--}}
        {{--currency: <input type="text" name="process_currency" value="{{$user_data['process_currency']}}"><!-- currency value must be LKR or USD -->--}}
        {{--<input type="hidden" name="cms" value="{{$user_data['cms']}}">--}}
        {{--<input type="hidden" name="custom_fields" value="{{$user_data['custom_fields']}}">--}}
        {{--<input type="hidden" name="enc_method" value="{{$user_data['enc_method']}}">--}}
        {{--<input type="hidden" name="secret_key" value="{{$user_data['secret_key']}}">--}}
        {{--<input type="hidden" name="payment" value="{{$user_data['payment']}}">--}}
        <button type="submit" class="btn btn-primary">Pay Now</button>
    </form>
</div>
</body>
</html>