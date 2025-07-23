<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'whatsapp-message',
        'message-status',
        'https://u-ride.adminkloud.com/public/paypay-callback',
        'https://admin.globaltaxi.tech/public/paypay-callback',
    ];
}
