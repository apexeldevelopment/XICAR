<?php

namespace App\Listeners;

use App\Events\SendUserOrderInvoiceMailEvent;
use App\Http\Controllers\Merchant\emailTemplateController;
use App\Jobs\SendUserOrderInvoiceMailJob;

class SendUserOrderInvoiceMailListener
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
     * @param  SendUserOrderInvoiceMailEvent  $data
     * @return void
     */
    public function handle(SendUserOrderInvoiceMailEvent $data)
    {
        dispatch(new SendUserOrderInvoiceMailJob($data->order));
//        $email_listener = new emailTemplateController();
//        $email_listener->SendInvoiceEmail($data->template_name, $data->booking_data);
    }
}
