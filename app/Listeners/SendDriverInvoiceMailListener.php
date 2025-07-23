<?php

namespace App\Listeners;

use App\Events\SendUserInvoiceMailEvent;
use App\Http\Controllers\Merchant\emailTemplateController;
use App\Jobs\SendDriverInvoiceMailJob;

class SendDriverInvoiceMailListener
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
     * @param  SendUserInvoiceMailEvent  $data
     * @return void
     */
    public function handle(SendDriverInvoiceMailEvent $data)
    {
        dispatch(new SendDriverInvoiceMailJob($data->booking_data));
//        $email_listener = new emailTemplateController();
//        $email_listener->SendInvoiceEmail($data->template_name, $data->booking_data);
    }
}
