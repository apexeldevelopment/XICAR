<?php

namespace App\Jobs;

use App\Http\Controllers\Merchant\emailTemplateController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UserSignupEmailOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $otp;
    public $user_email;
    public $merchant_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($merchant_id, $user_email, $otp)
    {
        $this->otp = $otp;
        $this->user_email = $user_email;
        $this->merchant_id = $merchant_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $email_listener = new emailTemplateController();
        // $log_data = array(
        //     'job_merid' => $this->merchant_id,
        //     'useremail' => $this->user_email,
        //     'otp' => $this->otp,
        //     'hit_time' => date('Y-m-d H:i:s')
        // );
        // \Log::channel('maillog')->info($log_data);
        // $email_listener->UserSignupEmailOtp($this->merchant_id, $this->user_email, $this->otp);

    }
}
