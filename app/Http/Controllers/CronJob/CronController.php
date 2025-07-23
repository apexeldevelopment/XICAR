<?php

namespace App\Http\Controllers\CronJob;

use App\Models\Booking;
use App\Models\DriverSubscriptionRecord;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CronController extends Controller
{
    /*all functions will be executed which are based on per minute*/

    public function perMinuteCron()
    {
        $per_minute = new PerMinuteCronController();
        //expire old booking and notify for upcoming
        $per_minute->booking();

        //expire bus bookings and notify for upcoming bookings and notify to admin to allocate driver for bookings
        $per_minute->busBooking();
        $per_minute->clearTelescopeEntries();
//        $per_minute->testCron();
    }

    /*all functions will be executed which are based on every day*/

    public function perDayCron()
    {
        $per_day = new PerDayCronController();
        // document expire and its reminder cron
        $per_day->document();
        //expire subscription package
        $per_day->subscriptionPackage();
        //expire handyman orders
        $per_day->expireHandymanOrder();
        // expire referral system
        $per_day->expireReferralSystem();
        //expire handyman bidding orders
        $per_day->expireHandymanBiddingOrder();

        $per_day->closeLowWalletBalanceStore();
    }

    public function perYearCron()
    {
        // $per_year = new PerYearCronController();
        //expire old booking and notify for upcoming
        // $per_year->perYear();

//        $per_minute->testCron();
    }
}
