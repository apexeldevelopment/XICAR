<?php

namespace App\Listeners;

use App\Events\UserSignupEmailOtpEvent;
use App\Http\Controllers\Merchant\emailTemplateController;
use App\Jobs\UserSignupEmailOtpJob;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserSignupEmailOtpListener
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
     * @param  UserSignupEmailOtpEvent  $event
     * @return void
     */
    public function handle($event)
    {
       $log_data = array(
            'listen_mer_id' => $event->merchant_id,
            'user_email' => $event->user_email,
            'otp' => $event->otp,
            'hit_time' => date('Y-m-d H:i:s')
        );
        \Log::channel('maillog')->info($log_data);
        $email_listener = new emailTemplateController();
        // $log_data = array(
        //     'job_merid' => $this->merchant_id,
        //     'useremail' => $this->user_email,
        //     'otp' => $this->otp,
        //     'hit_time' => date('Y-m-d H:i:s')
        // );
        // \Log::channel('maillog')->info($log_data);
        $email_listener->UserSignupEmailOtp($event->merchant_id, $event->user_email, $event->otp);
        // dispatch(new UserSignupEmailOtpJob($event->merchant_id, $event->user_email, $event->otp));
    }
}
