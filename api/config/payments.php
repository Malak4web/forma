<?php

/*
| Payment methods. Every method has a driver; today both are manual transfers that an admin
| approves after checking the receipt. A gateway (e.g. Paymob wallets) can be added later as
| another driver without touching the order flow.
*/

return [

    'currency' => 'EGP',

    'receipt' => [
        'disk' => 'local',          // private disk — receipts are never public
        'max_kb' => 5120,
    ],

    'methods' => [
        'vodafone_cash' => [
            'enabled' => env('VODAFONE_CASH_ENABLED', true),
            'driver' => \App\Payments\ManualTransferDriver::class,
            'label' => 'فودافون كاش',
            'hint' => 'حوّل من أي محفظة على رقم فودافون كاش',
            'account' => env('VODAFONE_CASH_NUMBER'),
            'payer_phone_required' => true,
        ],
        'instapay' => [
            'enabled' => env('INSTAPAY_ENABLED', true),
            'driver' => \App\Payments\ManualTransferDriver::class,
            'label' => 'InstaPay',
            'hint' => 'حوّل من تطبيق InstaPay على العنوان ده',
            'account' => env('INSTAPAY_ADDRESS'),
            'payer_phone_required' => false,
        ],
    ],

    // business hours promise shown to the customer
    'review_hours' => 24,
];
