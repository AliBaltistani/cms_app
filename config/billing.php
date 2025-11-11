<?php

return [
    // Commission rate applied to trainer invoices (e.g., 0.10 = 10%)
    'commission_rate' => env('BILLING_COMMISSION_RATE', 0.10),

    // Default currency for payments
    'currency' => env('BILLING_CURRENCY', 'usd'),
];