<?php

namespace App\Listeners;

use App\Events\DriverSignupEmailOtpEvent;
use App\Http\Controllers\Merchant\emailTemplateController;
use App\Jobs\DriverSignupEmailOtpJob;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DriverSignupEmailOtpListener
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
     * @param  DriverSignupEmailOtpEvent  $event
     * @return void
     */
    public function handle($event)
    {
        $log_data = array(
            'listen_mer_id' => $event->merchant_id,
            'driver_email' => $event->driver_email,
            'otp' => $event->otp,
            'hit_time' => date('Y-m-d H:i:s')
        );
        \Log::channel('maillog')->info($log_data);
        // dispatch(new DriverSignupEmailOtpJob($event->merchant_id, $event->driver_email, $event->otp));
        $email_listener = new emailTemplateController();
        $email_listener->DriverSignupEmailOtp($event->merchant_id,$event->driver_email, $event->otp);
    }
}
