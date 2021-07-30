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
        'deposit/payumoney_confirm',
        'deposit/payumoney_fail',
        'deposit/payeer/payment/status',
        'deposit/checkout/payment/success',
        'merchant/api/*',
        'payment/form',
        'payment/payumoney_success',
        'payment/payumoney_fail',
        '/admin/dispute/change_reply_status',
        'ticket/change_reply_status',
        'request_payment/cancel',
        'send/crypto-notification',
    ];
}
