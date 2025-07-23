<?php

namespace App\Listeners;

use App\Events\SosEmailNotification;
use App\Http\Controllers\Merchant\emailTemplateController;
use App\Jobs\SendUserInvoiceMailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SosEmailNotificationListener
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
     * @param SosEmailNotification $data
     * @return void
     */
    public function handle(SosEmailNotification $data)
    {
        //
//        dispatch(new SendUserInvoiceMailJob($data->booking_data));
        $email_listener = new emailTemplateController();
        $email_listener->SendSosNotificationEmail($data->booking_data, $data->location_name, $data->request_from);
    }
}
