<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MTN Mobile Money (MoMo) — Collections API
    |--------------------------------------------------------------------------
    | Sandbox : https://sandbox.momodeveloper.mtn.com
    | Production : https://proxy.momoapi.mtn.com
    |
    | Variables .env à définir :
    |   MTN_MOMO_ENVIRONMENT=sandbox          (sandbox | production)
    |   MTN_MOMO_BASE_URL=https://sandbox.momodeveloper.mtn.com
    |   MTN_MOMO_COLLECTION_KEY=<subscription_key>
    |   MTN_MOMO_USER_ID=<api_user_uuid>
    |   MTN_MOMO_API_KEY=<api_key>
    |   MTN_MOMO_CURRENCY=XOF
    |   MTN_MOMO_CALLBACK_URL=https://mesotravo.com/webhooks/momo
    */

    'environment'       => env('MTN_MOMO_ENVIRONMENT', 'sandbox'),
    'base_url'          => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
    'collection_key'    => env('MTN_MOMO_COLLECTION_KEY', ''),
    'user_id'           => env('MTN_MOMO_USER_ID', ''),
    'api_key'           => env('MTN_MOMO_API_KEY', ''),
    'currency'          => env('MTN_MOMO_CURRENCY', 'XOF'),
    'callback_url'      => env('MTN_MOMO_CALLBACK_URL', ''),

    /*
     * Délai max de polling en secondes avant d'abandonner.
     * Le client peut confirmer jusqu'à ce délai.
     */
    'polling_timeout_seconds' => 120,
];
