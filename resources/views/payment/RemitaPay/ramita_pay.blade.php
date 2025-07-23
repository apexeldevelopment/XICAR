<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title> Remita Pay</title>
</head>

<body id="paymentResponse">

    <script type="text/javascript" src="https://demo.remita.net/payment/v1/remita-pay-inline.bundle.js"></script>

    <script>
        let paymentHandled = false; // Flag to track if redirect already happened

        function makePayment() {
            var paymentEngine = RmPaymentEngine.init({
                key: "{{ $key }}",
                processRrr: true,
                transactionId: "{{ $transactionId }}",
                extendedData: {
                    customFields: [
                        {
                            name: "rrr",
                            value: "{{ $rrr }}"
                        }
                    ]
                },
                onSuccess: function (response) {
                    if (!paymentHandled) {
                        paymentHandled = true;
                        window.location.href = "{{ route('remita.success', $rrr) }}";
                    }
                },
                onError: function (response) {
                    if (!paymentHandled) {
                        paymentHandled = true;
                        window.location.href = "{{ route('remita.fail', $rrr) }}";
                    }
                },
                onClose: function () {
                    if (!paymentHandled) {
                        paymentHandled = true;
                        window.location.href = "{{ route('remita.fail', $rrr) }}";
                    }
                }
            });

            paymentEngine.showPaymentWidget();
        }

        window.onload = function () {
            makePayment();
        };
    </script>

</body>

</html>