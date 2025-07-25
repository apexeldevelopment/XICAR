<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Wallet Recharge</title>
    <meta name="description" content="A demo of a payment on Stripe" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        
        /* Variables */
* {
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, sans-serif;
  font-size: 16px;
  -webkit-font-smoothing: antialiased;
  display: flex;
  justify-content: center;
  align-content: center;
  height: 100vh;
  width: 100vw;
  background: linear-gradient(0deg, rgba(2,0,36,1) 0%, rgba(254,255,255,1) 0%, rgba(0,212,255,1) 100%);

}

form {
  width: 30vw;
  min-width: 500px;
  align-self: center;
  box-shadow: 0px 0px 0px 0.5px rgba(50, 50, 93, 0.1),
    0px 2px 5px 0px rgba(50, 50, 93, 0.1), 0px 1px 1.5px 0px rgba(0, 0, 0, 0.07);
  border-radius: 7px;
  padding: 40px;
  background-color:#FFFFFF;

}

.hidden {
  display: none;
}

#payment-message {
  color: rgb(105, 115, 134);
  font-size: 16px;
  line-height: 20px;
  padding-top: 12px;
  text-align: center;
}

#payment-element {
  margin-bottom: 24px;
}

/* Buttons and links */
button {
  background: #5469d4;
  font-family: Arial, sans-serif;
  color: #ffffff;
  border-radius: 4px;
  border: 0;
  padding: 12px 16px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: block;
  transition: all 0.2s ease;
  box-shadow: 0px 4px 5.5px 0px rgba(0, 0, 0, 0.07);
  width: 100%;
}
button:hover {
  filter: contrast(115%);
}
button:disabled {
  opacity: 0.5;
  cursor: default;
}

/* spinner/processing state, errors */
.spinner,
.spinner:before,
.spinner:after {
  border-radius: 50%;
}
.spinner {
  color: #ffffff;
  font-size: 22px;
  text-indent: -99999px;
  margin: 0px auto;
  position: relative;
  width: 20px;
  height: 20px;
  box-shadow: inset 0 0 0 2px;
  -webkit-transform: translateZ(0);
  -ms-transform: translateZ(0);
  transform: translateZ(0);
}
.spinner:before,
.spinner:after {
  position: absolute;
  content: "";
}
.spinner:before {
  width: 10.4px;
  height: 20.4px;
  background: #5469d4;
  border-radius: 20.4px 0 0 20.4px;
  top: -0.2px;
  left: -0.2px;
  -webkit-transform-origin: 10.4px 10.2px;
  transform-origin: 10.4px 10.2px;
  -webkit-animation: loading 2s infinite ease 1.5s;
  animation: loading 2s infinite ease 1.5s;
}
.spinner:after {
  width: 10.4px;
  height: 10.2px;
  background: #5469d4;
  border-radius: 0 10.2px 10.2px 0;
  top: -0.1px;
  left: 10.2px;
  -webkit-transform-origin: 0px 10.2px;
  transform-origin: 0px 10.2px;
  -webkit-animation: loading 2s infinite ease;
  animation: loading 2s infinite ease;
}

@-webkit-keyframes loading {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@keyframes loading {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}

@media only screen and (max-width: 600px) {
  form {
    width: 80vw;
    min-width: initial;
  }
}
    </style>
    <script src="https://js.stripe.com/v3/"></script>
    <!--<script src="checkout.js" defer></script>-->
  </head>
  <body>
    <!-- Display a payment form -->
    <form id="payment-form">

    <div style="margin-bottom:10px;">
        <label for="wallet_amt">Amount</label>
        <input  type="number" step=".01"  name="wallet_amt" id="wallet_amt" placeholder="0.00" class="form-control" value="" style="padding-right: 85.2px;">
    </div>

      <div id="payment-element">
        <!--Stripe.js injects the Payment Element-->
      </div>
      <button id="submit" onclick="initialize();">
        <div class="spinner hidden" id="spinner"></div>
        <span id="button-text">Pay now</span>
      </button>
      <div id="payment-message" class="hidden"></div>
    </form>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>

    const stripe = Stripe("{{$payment_config->api_public_key}}");
    let elements;

    
    document
      .querySelector("#payment-form")
      .addEventListener("submit", handleSubmit);
    
    // Fetches a payment intent and captures the client secret
    async function initialize() {
        if(typeof elements === 'undefined'){
            const amt = document.getElementById('wallet_amt').value;
            const formData = new FormData();
            formData.append('value', amt);
            formData.append('currency', '{{$currency}}');
            formData.append('for', 'taxicompany');
            formData.append('_token', '{{csrf_token()}}');
            
          const { clientSecret } = await fetch("{{route('taxicompany.stripe.wallet-recharge.checkout')}}", {
            method: "POST",
            body: formData,
          }).then((r) => r.json());
    
          elements = stripe.elements({ clientSecret });
          const paymentElementOptions = {
            layout: "tabs",
          };
        
          const paymentElement = elements.create("payment", paymentElementOptions);
          paymentElement.mount("#payment-element");
        }
    }
    
    async function handleSubmit(e) {
      e.preventDefault();
      
      if(typeof elements !== 'undefined'){
          setLoading(true);
          const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
              return_url: "{{route('card-payment.stripe.success')}}",
            },
          });
          if (error.type === "card_error" || error.type === "validation_error") {
            showMessage(error.message);
          } else {
            showMessage("An unexpected error occurred.");
          }
        
          setLoading(false);
      }
      
      
    }
    
    function setLoading(isLoading) {
      if (isLoading) {
        // Disable the button and show a spinner
        document.querySelector("#submit").disabled = true;
        document.querySelector("#spinner").classList.remove("hidden");
        document.querySelector("#button-text").classList.add("hidden");
      } else {
        document.querySelector("#submit").disabled = false;
        document.querySelector("#spinner").classList.add("hidden");
        document.querySelector("#button-text").classList.remove("hidden");
      }
    }
    </script>
  </body>
</html>