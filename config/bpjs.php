<?php

return [

    /*
    |--------------------------------------------------------------------------
    | BPJS VClaim API Configuration
    |--------------------------------------------------------------------------
    */

    'base_url'   => env('BPJS_BASE_URL', 'https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev'),
    'cons_id'    => env('BPJS_CONS_ID', ''),
    'secret_key' => env('BPJS_SECRET_KEY', ''),
    'user_key'   => env('BPJS_USER_KEY', ''),
    'app_code'   => env('BPJS_APP_CODE', '095'),
    'cache_ttl'  => env('BPJS_CACHE_TTL', 3600),

];
