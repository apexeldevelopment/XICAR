<!DOCTYPE html>
<html>
<head>
    <title>Tap Save Card</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bluebird/3.3.4/bluebird.min.js"></script>
    <script src="https://secure.gosell.io/js/sdk/tap.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .form-row {
            width: 70%;
            float: left;
            background-color: #ededed;
        }
        #card-element {
            background-color: transparent;
            height: 40px;
            border-radius: 4px;
            border: 1px solid transparent;
            box-shadow: 0 1px 3px 0 #e6ebf1;
            -webkit-transition: box-shadow 150ms ease;
            transition: box-shadow 150ms ease;
        }

        #card-element--focus {
            box-shadow: 0 1px 3px 0 #cfd7df;
        }

        #card-element--invalid {
            border-color: #fa755a;
        }

        #card-element--webkit-autofill {
            background-color: #fefde5 !important;
        }

        #submitbutton,#tap-btn{
            align-items:flex-start;
            background-attachment:scroll;background-clip:border-box;
            background-color:rgb(50, 50, 93);background-image:none;
            background-origin:padding-box;
            background-position-x:0%;
            background-position-y:0%;
            background-size:auto;
            border-bottom-color:rgb(255, 255, 255);
            border-bottom-left-radius:4px;
            border-bottom-right-radius:4px;border-bottom-style:none;
            border-bottom-width:0px;border-image-outset:0px;
            border-image-repeat:stretch;border-image-slice:100%;
            border-image-source:none;border-image-width:1;
            border-left-color:rgb(255, 255, 255);
            border-left-style:none;
            border-left-width:0px;
            border-right-color:rgb(255, 255, 255);
            border-right-style:none;
            border-right-width:0px;
            border-top-color:rgb(255, 255, 255);
            border-top-left-radius:4px;
            border-top-right-radius:4px;
            border-top-style:none;
            border-top-width:0px;
            box-shadow:rgba(50, 50, 93, 0.11) 0px 4px 6px 0px, rgba(0, 0, 0, 0.08) 0px 1px 3px 0px;
            box-sizing:border-box;color:rgb(255, 255, 255);
            cursor:pointer;
            display:block;
            float:left;
            font-family:"Helvetica Neue", Helvetica, sans-serif;
            font-size:15px;
            font-stretch:100%;
            font-style:normal;
            font-variant-caps:normal;
            font-variant-east-asian:normal;
            font-variant-ligatures:normal;
            font-variant-numeric:normal;
            font-weight:600;
            height:35px;
            letter-spacing:0.375px;
            line-height:35px;
            margin-bottom:0px;
            margin-left:12px;
            margin-right:0px;
            margin-top:28px;
            outline-color:rgb(255, 255, 255);
            outline-style:none;
            outline-width:0px;
            overflow-x:visible;
            overflow-y:visible;
            padding-bottom:0px;
            padding-left:14px;
            padding-right:14px;
            padding-top:0px;
            text-align:center;
            text-decoration-color:rgb(255, 255, 255);
            text-decoration-line:none;
            text-decoration-style:solid;
            text-indent:0px;
            text-rendering:auto;
            text-shadow:none;
            text-size-adjust:100%;
            text-transform:none;
            transition-delay:0s;
            transition-duration:0.15s;
            transition-property:all;
            transition-timing-function:ease;
            white-space:nowrap;
            width:150.781px;
            word-spacing:0px;
            writing-mode:horizontal-tb;
            -webkit-appearance:none;
            -webkit-font-smoothing:antialiased;
            -webkit-tap-highlight-color:rgba(0, 0, 0, 0);
            -webkit-border-image:none;

        }
    </style>
</head>
<body>
<form id="form-container" method="post" action="{{$redirect_url}}">
    <!-- Tap element will be here -->
    <div id="element-container"></div>
    <div id="error-handler" role="alert"></div>
    <div id="success" style=" display: none;;position: relative;float: left;">
        <!--    Success! Your token is <span id="token"></span>-->
    </div>
    <input type="hidden" name="customer_id" value="{{$customer_id}}">
    <input type="hidden" name="merchant_id" value="{{$merchant_id}}">
    <input type="hidden" name="type" value="{{$type}}">
    <input type="hidden" name="udid" value="{{$udid}}">
    <!-- Tap pay button -->
    <button id="tap-btn">Submit</button>
</form>
<script>
    //pass your public key from tap's dashboard
    var tap = Tapjsli('{{$public_key}}');

    var elements = tap.elements({});
    var style = {
        base: {
            color: '#535353',
            lineHeight: '18px',
            fontFamily: 'sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: 'rgba(0, 0, 0, 0.26)',
                fontSize:'15px'
            }
        },
        invalid: {
            color: 'red'
        }
    };
    // input labels/placeholders
    var labels = {
        cardNumber:"Card Number",
        expirationDate:"MM/YY",
        cvv:"CVV",
        cardHolder:"Card Holder Name"
    };
    //payment options
    var paymentOptions = {
        currencyCode:["KWD","USD","SAR","JOD","BHD"],
        paymentAllowed:['VISA', 'MASTERCARD', 'AMEX', 'MADA'],
        labels : labels,
        TextDirection:'ltr'
    }
    //create element, pass style and payment options
    var card = elements.create('card', {style: style},paymentOptions);
    //mount element
    card.mount('#element-container');
    //card change event listener
    card.addEventListener('change', function(event) {
        if(event.BIN){
            console.log(event.BIN)
        }
        if(event.loaded){
            console.log("UI loaded :"+event.loaded);
            console.log("current currency is :"+card.getCurrency())
        }
        var displayError = document.getElementById('error-handler');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Handle form submission
    var form = document.getElementById('form-container');
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        tap.createToken(card).then(function(result) {
            console.log(result);
            if (result.error) {
                // Inform the user if there was an error
                var errorElement = document.getElementById('error-handler');
                errorElement.textContent = result.error.message;
            } else {
                // Send the token to your server
                var errorElement = document.getElementById('success');
                errorElement.style.display = "block";
                // var tokenElement = document.getElementById('token');
                // tokenElement.textContent = result.id;
                // $('#card_token').val(result.id);
                // console.log(result.id);
                tapTokenHandler(result);
            }
        });
    });

    function tapTokenHandler(token) {
        // Insert the token ID into the form so it gets submitted to the server
        var form = document.getElementById('form-container');
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'tapToken');
        hiddenInput.setAttribute('value', token.id);
        form.appendChild(hiddenInput);
        // Submit the form
        form.submit();
    }
</script>
</body>
</html>