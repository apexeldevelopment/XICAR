<html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>Collect sensitive Customer Info </title>
        </head>
        <body>
        <p><h2>Step Two: Collect sensitive payment information and POST directly to payment gateway<br /></h2></p>
        <form action="{{$formUrl}}" method="POST">
        <h3> Payment Information</h3>
            <table>
                <tr><INPUT type ="hidden" name="merchant_id" value="{{$merchantId}}"> </td></tr>
                <tr><td>Credit Card Number</td><td><INPUT type ="text" name="billing-cc-number"> </td></tr>
                <tr><td>Expiration Date</td><td><INPUT type ="text" name="billing-cc-exp"> </td></tr>
                <tr><td>CVV</td><td><INPUT type ="text" name="cvv" > </td></tr>
                <tr><Td colspan="2" align=center><INPUT type ="submit" value="Submit Step Two"></td> </tr>
            </table>
        </form>
        </body>
        </html>