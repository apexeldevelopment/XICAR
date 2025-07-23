<?php

namespace App\Listeners;

use App\Events\SendMovingStatusNotification;
use App\Events\WebPushNotificationEvent;
use App\Models\Onesignal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMovingStatusNotificationListner
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
     * @param  \App\Events\SendMovingStatusNotification  $event
     * @return void
     */
    public function handle(SendMovingStatusNotification $event)
    {
        // for Driver
        $driver_id = $event->driver_id;
        $merchant_id = $event->merchant_id;
        $message = $event->message;
        $title = $event->title;
        $booking = $event->booking;
        $large_icon = '';
        $data = array(
            'notification_type' => "RIDE_MOVEMENT_UPDATE",
            'segment_type' => "TAXI",
            'segment_data' => time(),
            'notification_gen_time' => time(),
        );

        $arr_param = ['driver_id' => $driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => $large_icon];
        Onesignal::DriverPushMessage($arr_param);

        //For User
        $notification_data['notification_type'] = "RIDE_MOVEMENT_UPDATE";
        $notification_data['segment_type'] = $booking->Segment->slag;
        $notification_data['segment_group_id'] = $booking->Segment->segment_group_id;
        $notification_data['segment_sub_group'] = $booking->Segment->sub_group_for_app; // its segment sub group for app
        $notification_data['segment_data'] = [
            'booking_id' => $booking->id,
            'booking_status' => $booking->booking_status,
            'booking_type' => $booking->booking_type,
            'username' => $booking->User->UserName,
            'email' => $booking->User->email ?? "",
            'user_image' => get_image($booking->User->Userprofile_image, 'user', $booking->merchant_id, true, false),
            'phone' => $booking->User->UserPhone,
        ];
        $arr_param = ['user_id' => $booking->user_id, 'data' => $notification_data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => $large_icon];
        Onesignal::UserPushMessage($arr_param);

        //For Web
        event(new WebPushNotificationEvent($merchant_id, [], 1, $booking->ServiceType->type, $booking, "all_in_one", "RIDE_MOVEMENT_UPDATE"));
    }
}
