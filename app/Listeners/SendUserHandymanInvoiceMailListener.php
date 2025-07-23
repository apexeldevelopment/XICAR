<?php

namespace App\Listeners;

use App\Jobs\SendUserHandymanInvoiceMailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\Merchant\emailTemplateController;

class SendUserHandymanInvoiceMailListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        // dispatch(new SendUserHandymanInvoiceMailJob($event->handymanOrder));
        $email_listener = new emailTemplateController();
        $email_listener->SendUserHandymanInvoiceMail($event->handymanOrder);
    }
}
