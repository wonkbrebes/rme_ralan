<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SatuSehat (FHIR) API Configuration
    |--------------------------------------------------------------------------
    */

    'base_url'      => env('SATUSEHAT_BASE_URL', 'https://api-satusehat-stg.dto.kemkes.go.id'),
    'client_id'     => env('SATUSEHAT_CLIENT_ID', ''),
    'client_secret' => env('SATUSEHAT_CLIENT_SECRET', ''),
    'org_id'        => env('SATUSEHAT_ORG_ID', ''),

];
