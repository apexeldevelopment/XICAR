<!DOCTYPE html>
<html>
<head>
    <title>PogoPlux</title>
    <script src=" https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://sandbox-paybox.pagoplux.com/paybox/index_web_cards.js"></script>
    <script type="text/javascript">
        if({{$gateway_env}} == 1){
            var PayboxProductionVal = true;
            var PayboxEnvironmentVal = "prod";
        }else{
            var PayboxProductionVal = false;
            var PayboxEnvironmentVal = "sandbox";
        }
        // console.log('PayboxProduction:'+PayboxProduction);
        var data = {

            /* Required (string). Email of the PagoPlux account of the Establishment or ID of the html element that has the value */

            PayboxRemail: "{{$pagoplux_email}}",

            /*Required (base64-string encrypted). When the cards are listed.
          * Not necessary: when registering a card.*/

            // PayboxIdSuscription: "NDJiNzBkNTYtMzQ2My00OTg4LWE0ZjgtZjAxNzM4YTVjYjUw",

            /*Required(string). Customer phone number, required when registering the card.
        *Not necessary. By listing the cards.*/

            PayBoxClientPhone: "{{$customer_phone}}",

            /*Required (string). Defines the type of resource to load, with the following values:
        0: To register the card
        1: To list the cards*/

            PayboxListCard: "{{$PayboxListCard}}",




            /* Required (string). Name of the establishment in PagoPlux, it is shown on the screen of the payment button*/

            PayboxRename: "Nombre Establecimiento",

            /* Required (string). Name of the customer registering the card
            Not necessary. When the cards are listed */

            PayboxSendname: "{{$customer_name}}",


            /* Required (string). Card customer mail
           Not necessary. When customer cards are listed */

            PayboxSendmail: "{{$customer_email}}",


            /* Required (string). customer address
           Not necessary. When customer cards are listed */

            PayboxDirection: "{{$customer_address}}",


            /* Required (boolean) Execution Type
             * Production: true (Production mode, consultations and registrations will be made in the production environment, it will also affect the tdc for test payments when registering the card).
             * Production: false (Test Mode, test charges will be made and not
                will not be saved or affect the production system)
            */
            PayboxProduction: PayboxProductionVal,

            /* Required (string): When the card is registered. ID or name of the associated plan for card registration.
             * Not necessary: When the card is listed.
            */

            PayboxIdPlan: "{{$PayboxIdPlan}}",


            /* Required (string): Customer ID.
             * Not necessary: When the card is listed. */

            PayBoxClientIdentification: "{{$PayBoxClientIdentification}}",

            /*Required (string)* Card listing and registration execution environment: prod, sandbox */
            PayboxEnvironment: PayboxEnvironmentVal
        };
        console.log(data);
    </script>
    <script type="text/javascript">
        var onAuthorize = function(response)
        {
            console.log('response :'+JSON.stringify(response));
            // La variable response posee un Objeto con la respuesta de PagoPlux.
            if (response.status == 'succeeded')
            {
                // console.log(response.detail);
                // console.log(response.detail.idSuscription);
                $('#idSuscription').val(response.detail.idSuscription);
                $('#token').val(response.detail.token);
                $('#cardInfo').val(response.detail.cardInfo);
                $('#cardIssuer').val(response.detail.cardIssuer);
                $('#clientName').val(response.detail.clientName);
                $('#data_form').submit();
                // document.getElementById("idSuscription").value = response.detail.idSuscription;
                // Pago exitoso response contiene la informaci贸n del pago la cual puede
                // usarse para validaciones
                // cardInfo: ""
                // cardIssuer: ""
                // cardType: "credit"
                // clientID: ""
                // clientName: ""
                // idSuscription: ""
                // idTransaccion: ""
                // token: "" // Token de tarjeta para consumos
                // proceso: "REGISTRO-TARJETA"
                // state: "REGISTRADO"
            }else{
                window.location.href = '{{$fail_url}}?msg='+response.description;
            }
        }
        // if (typeof onAuthorize === 'undefined') {
        //     console.log("onAuthorize not defined");
        // }else{
        //     console.log("onAuthorize :"onAuthorize);
        // }
    </script>
    <script>
        $(document).ready(function() {
            setTimeout(function(){
                $('#pay').trigger('click');
            }, 1000);
        });
    </script>

</head>
<body style="text-align:center">
<br><br>
<div id="ButtonRegisterCard"></div>
<br><br><br><br>
<h3>Please Wait PopUp Will Load Automatically!!</h3>
<!--<button id="bt" onClick="al()">View</button>-->
<form id="data_form" action="{{$redirect_form}}" method="post">
    <input type="hidden" name="idSuscription" id="idSuscription" value="">
    <input type="hidden" name="token" id="token" value="">
    <input type="hidden" name="cardInfo" id="cardInfo" value="">
    <input type="hidden" name="cardIssuer" id="cardIssuer" value="">
    <input type="hidden" name="clientName" id="clientName" value="">
    <input type="hidden" name="payment_option_id" id="payment_option_id" value="{{$payment_option_id}}">
    <input type="hidden" name="user_driver_id" id="user_driver_id" value="{{$user_driver_id}}">
    <input type="hidden" name="type" id="type" value="{{$type}}">
    <!--<input type="hidden" name="idSuscription" value="">-->
</form>
</body>
</html>