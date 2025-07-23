<?php

namespace App\Http\Controllers\StripeConnect;

use App\Models\Driver;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentSplit\StripeConnect;

class StripeController extends Controller
{
    public function showConnectCardForm($publishableKey, $connectAccountId, $currency)
    {
        return view('stripe.add-card', [
            'publishableKey' => decrypt($publishableKey),
            'connectAccountId' => decrypt($connectAccountId),
            'currency' => decrypt($currency)
        ]);
    }
    public function saveCardToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'sc_account_id' => 'required',
        ]);

        try {
            $driver = Driver::where('sc_account_id', $request->sc_account_id)->first();

            if (!$driver) {
                return response("❌ Driver not found.", 404);
            }

            if ($driver->DriverDetail != null) {
                $driver->DriverDetail->card_token = $request->token;
                $driver->DriverDetail->save();
            } else {
                $driver_detail = new \App\Models\DriverDetail();
                $driver_detail->driver_id = $driver->id;
                $driver_detail->card_token = $request->token;
                $driver_detail->save();
            }

            $res = StripeConnect::add_debit_card($driver);

            if ($res === true) {
                return response("✅ Card added.", 200);
            } else {
                return response("❌ Failed to add card to Stripe account.", 500);
            }
        } catch (\Exception $e) {
            \Log::error('Stripe error: ' . $e->getMessage());
            return response("❌ Error: " . $e->getMessage(), 500);
        }
    }
}
