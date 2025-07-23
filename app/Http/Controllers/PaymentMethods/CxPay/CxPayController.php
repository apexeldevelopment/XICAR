<?php

namespace App\Http\Controllers\PaymentMethods\CxPay;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\Order;
use App\Models\HandymanOrder;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CxPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    
    public function getCxPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'CXPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    public function processStepOne($request,$payment_option_config,$calling_from)
    {
        if($calling_from == "USER"){
            $user = $request->user('api');
            $firstName = $user->first_name;
            $lastName = $user->last_name;
            $phone = $user->UserPhone;
            $email = $user->Merchant->email;
            $countryCode = $user->Country->isoCode;
            $businessName = $user->Merchant->BusinessName;
            $merchant_id = $user->merchant_id;
            $address1 = '123 Main Hall';
            $id = $user->id;
            
        }
        else{
            $user = $request->user('api-driver');
            $firstName = $user->first_name;
            $lastName = $user->last_name;
            $phone = $user->phoneNumber;
            $email = $user->Merchant->email;
            $countryCode = $user->Country->isoCode;
            $businessName = $user->Merchant->BusinessName;
            $merchant_id = $user->merchant_id;
            $address1 = '123 Main Hall';
            $id = $user->id;
        }
        $gatewayURL = $payment_option_config->payment_redirect_url;
        $APIKey = $payment_option_config->api_secret_key;
        // dd($gatewayURL,$APIKey);
        //  $gatewayURL = 'https://cxpay.transactiongateway.com/api/v2/three-step';
        // $APIKey = '2F822Rw39fx762MaV7Yy86jXGTC7sCDy';
        $request->merge(['first_name'=>$firstName,'last_name'=>$lastName,'phone'=>$phone,'email'=>$email,'country'=>$countryCode,'company'=>$businessName,'address1'=>$address1,'api_key'=>$APIKey,'merchant_id'=>$merchant_id]);
        // dd($request);
        $refId = 'Ref_'.time();
        $success_url = route('cxpay-success');
        $fail_url = route('cxpay-fail');
       
        // Your Step One logic here
        // Build XML request and send it to the gateway
        $xmlRequest = $this->buildStepOneXml($request);
        $data = $this->sendXMLviaCurl($xmlRequest, $gatewayURL);
        // dd($data);
        // Parse XML response
        $gwResponse = new \SimpleXMLElement($data);
        // dd($gwResponse);
        // Handle response and redirect to Step Two
        if ((string) $gwResponse->result == 1) {
            $formURL = $gwResponse->{'form-url'};
            DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $gwResponse->{'transaction-id'},
                    'amount' => $request->amount,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'request_status'=> 1,
                    'reference_id'=> $refId,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
            ]);
            $return_data = [
                'url' => route('cx-pay.step-two-form') . '?formURL=' . $formURL . '&merchantId='.$merchant_id,
                'success_url'=> $success_url,
                'fail_url' => $fail_url
            ];
            return $return_data;
            // return json_decode(json_encode ( $gwResponse ) , true);
        } else {
            // Handle error
            DB::table('transactions')->where('payment_transaction_id',$gwResponse->{'transaction-id'})->update([
                    'request_status' => 3,
                    'status_message' => "Failed",
            ]);
            return ['error'=> 'Error processing Step One'];
        }
        
        
    }
    
    public function stepTwoForm(Request $request){
        $formUrl = $request->query('formURL');
        $merchantId = $request->query('merchantId');
        return view('payment.CxPay.card-form',compact('formUrl','merchantId'));
    }
    
    public function redirectStepTwo(Request $request){
        if($request['token-id']){

            $payment_option = $this->getCxPayConfig($request->merchantId);
            $gatewayURL = $payment_option->payment_redirect_url;
            $APIKey = $payment_option->api_secret_key;
            // $APIKey = '2F822Rw39fx762MaV7Yy86jXGTC7sCDy';
            // $gatewayURL = 'https://cxpay.transactiongateway.com/api/v2/three-step';
            $request->merge(["api_key"=> $APIKey]);
            // Your Step Two logic here
            // Retrieve sensitive payment information from the form
            $xmlRequest = $this->buildStepTwoXml($request);
            // Process Step Two: Submit sensitive payment information to the Payment Gateway
            $data = $this->sendXMLviaCurl($xmlRequest, $gatewayURL);
            // Parse Step Two's XML response
            $gwResponse = new \SimpleXMLElement((string) $data);
            // dd($gwResponse);
            // Handle response and redirect to Step Three
            if ((string) $gwResponse->result == 1) {
                $tokenId = $gwResponse->{'token-id'};
                DB::table('transactions')->where('payment_transaction_id',$gwResponse->{'transaction-id'})->update([
                    'request_status' => 2,
                    'status_message' => "Success",
                ]);
                // return view('payment.CxPay.complete-transaction',compact('data','gwResponse'));
                return redirect()->route('cxpay-success');
            }
            elseif((string) $gwResponse->result == 3){
                // dd($gwResponse->{'result-text'});
                $message = $gwResponse->{'result-text'};
                return redirect()->route('cxpay-fail', ['message' => $message]);
                // return view('payment.CxPay.complete-transaction',compact('data','gwResponse'));
                
            } else {
                // Handle error
                DB::table('transactions')->where('payment_transaction_id',$gwResponse->{'transaction-id'})->update([
                    'request_status' => 3,
                    'status_message' => "Failed",
                ]);
                $message = $gwResponse->{'result-text'};
                return redirect()->route('cxpay-fail',['message' => $message]);
            }
        }
        else{
            throw new \Exception('Token id not found');
        }
    }
    
    
    // Build XML for Step One
    private function buildStepOneXml(Request $request)
    {
    $xmlRequest = new \DOMDocument('1.0','UTF-8');

    $xmlRequest->formatOutput = true;
    $xmlSale = $xmlRequest->createElement('sale');
    // dd(route('cx-pay.redirect'));
    // Amount, authentication, and Redirect-URL are typically the bare minimum.
    $this->appendXmlNode($xmlRequest, $xmlSale,'api-key',$request->api_key);
    $this->appendXmlNode($xmlRequest, $xmlSale,'redirect-url',route('cx-pay.redirect',['merchantId'=> $request->merchant_id]));
    $this->appendXmlNode($xmlRequest, $xmlSale, 'amount', $request['amount']);
    $this->appendXmlNode($xmlRequest, $xmlSale, 'ip-address', $_SERVER["SERVER_ADDR"]);
    $this->appendXmlNode($xmlRequest, $xmlSale, 'currency', 'USD');

    // // Some additonal fields may have been previously decided by user
    // $this->appendXmlNode($xmlRequest, $xmlSale, 'order-id', '1234');
    // $this->appendXmlNode($xmlRequest, $xmlSale, 'order-description', 'Small Order');
    // $this->appendXmlNode($xmlRequest, $xmlSale, 'merchant-defined-field-1' , 'Red');
    // $this->appendXmlNode($xmlRequest, $xmlSale, 'merchant-defined-field-2', 'Medium');
    // $this->appendXmlNode($xmlRequest, $xmlSale, 'tax-amount' , '0.00');
    // $this->appendXmlNode($xmlRequest, $xmlSale, 'shipping-amount' , '0.00');

    // Set the Billing and Shipping from what was collected on initial shopping cart form
    $xmlBillingAddress = $xmlRequest->createElement('billing');
    $this->appendXmlNode($xmlRequest, $xmlBillingAddress,'first-name', $request['first_name']);
    $this->appendXmlNode($xmlRequest, $xmlBillingAddress,'last-name', $request['last_name']);
    //billing-address-email
    $this->appendXmlNode($xmlRequest, $xmlBillingAddress,'country', $request['country']);
    $this->appendXmlNode($xmlRequest, $xmlBillingAddress,'email', $request['email']);
    $this->appendXmlNode($xmlRequest, $xmlBillingAddress,'phone', $request['phone']);
    $this->appendXmlNode($xmlRequest, $xmlBillingAddress,'company', $request['company']);
    $this->appendXmlNode($xmlRequest, $xmlBillingAddress,'address1', $request['address1']);
    $xmlSale->appendChild($xmlBillingAddress);


//     $xmlShippingAddress = $xmlRequest->createElement('shipping');
//   $this->appendXmlNode($xmlRequest, $xmlShippingAddress,'first-name', $request['first_name']);
//     $this->appendXmlNode($xmlRequest, $xmlShippingAddress,'last-name', $request['last_name']);
//     //billing-address-email
//     $this->appendXmlNode($xmlRequest, $xmlShippingAddress,'country', $request['country']);
//     $this->appendXmlNode($xmlRequest, $xmlShippingAddress,'email', $request['email']);
//     $this->appendXmlNode($xmlRequest, $xmlShippingAddress,'phone', $request['phone']);
//     $this->appendXmlNode($xmlRequest, $xmlShippingAddress,'company', $request['company']);
//     $this->appendXmlNode($xmlRequest, $xmlShippingAddress,'address1', $request['address1']);
//     $xmlSale->appendChild($xmlShippingAddress);


    // Products already chosen by user
    // $xmlProduct = $xmlRequest->createElement('product');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'product-code' , 'SKU-123456');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'description' , 'test product description');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'commodity-code' , 'abc');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'unit-of-measure' , 'lbs');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'unit-cost' , '5.00');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'quantity' , '1');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'total-amount' , '7.00');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'tax-amount' , '2.00');

    // $this->appendXmlNode($xmlRequest, $xmlProduct,'tax-rate' , '1.00');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'discount-amount', '2.00');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'discount-rate' , '1.00');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'tax-type' , 'sales');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'alternate-tax-id' , '12345');

    // $xmlSale->appendChild($xmlProduct);

    // $xmlProduct = $xmlRequest->createElement('product');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'product-code' , 'SKU-123456');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'description' , 'test 2 product description');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'commodity-code' , 'abc');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'unit-of-measure' , 'lbs');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'unit-cost' , '2.50');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'quantity' , '2');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'total-amount' , '7.00');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'tax-amount' , '2.00');

    // $this->appendXmlNode($xmlRequest, $xmlProduct,'tax-rate' , '1.00');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'discount-amount', '2.00');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'discount-rate' , '1.00');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'tax-type' , 'sales');
    // $this->appendXmlNode($xmlRequest, $xmlProduct,'alternate-tax-id' , '12345');

    // $xmlSale->appendChild($xmlProduct);
    $xmlRequest->appendChild($xmlSale);
        return $xmlRequest;
    }
    
    
    
    private function buildStepTwoXml(Request $request)
    {
        $tokenId = $request['token-id'];
        $xmlRequest = new \DOMDocument('1.0','UTF-8');
        $xmlRequest->formatOutput = true;
        
        $xmlCompleteTransaction = $xmlRequest->createElement('complete-action');
        $this->appendXmlNode($xmlRequest, $xmlCompleteTransaction,'api-key',$request->api_key);
        $this->appendXmlNode($xmlRequest, $xmlCompleteTransaction,'token-id',$tokenId);
        $xmlRequest->appendChild($xmlCompleteTransaction);
        
        return $xmlRequest;
    }
    
    private function sendXMLviaCurl($xmlRequest, $gatewayURL)
    {
        // dd($xmlRequest);
        $ch = curl_init(); // Initialize curl handle
    curl_setopt($ch, CURLOPT_URL, $gatewayURL); // Set POST URL

    $headers = array();
    $headers[] = "Content-type: text/xml";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Add http headers to let it know we're sending XML
    $xmlString = $xmlRequest->saveXML();
    curl_setopt($ch, CURLOPT_FAILONERROR, 1); // Fail on errors
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Allow redirects
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return into a variable
    curl_setopt($ch, CURLOPT_PORT, 443); // Set the port number
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Times out after 30s
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString); // Add XML directly in POST

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);


    // This should be unset in production use. With it on, it forces the ssl cert to be valid
    // before sending info.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    if (!($data = curl_exec($ch))) {
        print  "curl error =>" .curl_error($ch) ."\n";
        throw New Exception(" CURL ERROR :" . curl_error($ch));

    }
    curl_close($ch);

    return $data;
    }
    
    private function appendXmlNode($domDocument, $parentNode, $name, $value)
    {
        $childNode = $domDocument->createElement($name);
        $childNodeValue = $domDocument->createTextNode($value);
        $childNode->appendChild($childNodeValue);
        $parentNode->appendChild($childNode);
    }
    
    public function CxPaySuccess(Request $request)
    {
        \Log::channel('cxpay')->emergency($request->all());
        echo "<h1>SUCCESS</h1>";
    }

    public function CxPayFail(Request $request)
    {
        \Log::channel('cxpay')->emergency($request->all());
        // echo "<h1>".$request['message'][0]."</h1>";
        echo "<h1>FAILED</h1>";
    }
    
    
}