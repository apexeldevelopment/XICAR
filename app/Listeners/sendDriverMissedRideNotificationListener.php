<?php

namespace App\Listeners;

use App\Events\sendDriverMissedRideNotification;
use App\Models\Onesignal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class sendDriverMissedRideNotificationListener
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
     * @param sendDriverMissedRideNotification $event
     * @return void
     */
    public function handle(sendDriverMissedRideNotification $event)
    {
        //
        $driver_id = $event->driver_id;
        $merchant_id = $event->merchant_id;
        $message = $event->message;
        $title = $event->title;
        $large_icon = '';
        $data = array(
            'notification_type' => "RIDE_UPDATE",
            'segment_type' => "TAXI",
            'segment_data' => time(),
            'notification_gen_time' => time(),
        );

        $arr_param = ['driver_id' => $driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => $large_icon];
        Onesignal::DriverPushMessage($arr_param);
    }
}
