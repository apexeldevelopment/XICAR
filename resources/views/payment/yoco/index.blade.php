<!-- Include the Yoco SDK in your web page -->
<script src="https://js.yoco.com/sdk/v1/yoco-sdk-web.js"></script>

<!-- Create a pay button that will open the popup-->
<button id="checkout-button" style="display:none;">Pay</button>

<script>
    window.onload = function () {
        document.getElementById("checkout-button").click();
    };
    var yoco = new window.YocoSDK({
        publicKey: "{{$payment_option_config->api_public_key}}" //'pk_test_3a4ebb59vkzqVLJ02594',
    });
    var checkoutButton = document.querySelector('#checkout-button');
    checkoutButton.addEventListener('click', function () {
        yoco.showPopup({
            amountInCents: "{{$amount}}",
            currency: 'ZAR',
            name: "{{$user->Merchant->BusinessName." Payment"}}",
            description: 'Taxi Payment',
            callback: function (result) {
                // This function returns a token that your server can use to capture a payment
                if (result.error) {
                    const errorMessage = result.error.message;
                    window.location.replace("{{$error_url}}"+"/"+errorMessage);
                    // alert("error occured: " + errorMessage);
                } else {
                    window.location.replace("{{$success_url}}"+"/"+ result.id);
                    // alert("card successfully tokenised: " + result.id);
                }
            }
        })
    });
</script>
