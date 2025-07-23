<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\Helper\Merchant;

class SendWhatsappNotificationListener
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
        //
        $ride_event = $event->ride_event;
        $merchant_id = $event->merchant_id;
        $booking = $event->booking;
        $merchant = new Merchant();
        $merchant->SendWhatsappNotification($merchant_id, $ride_event, $booking);
    }
}
