<?php

return [

    /*
    | First-admin bootstrap without SSH. Only the SHA-256 of a one-time token lives here;
    | the token itself stays with the owner (local .env ADMIN_CLAIM_TOKEN, never committed).
    | After the first successful claim it is locked forever (settings key "admin.claimed").
    | Override per environment with ADMIN_CLAIM_HASH, or set it empty to disable claiming.
    */
    'admin_claim_hash' => env('ADMIN_CLAIM_HASH', '66d8a34e44b21dfbf705474d65c954803f9c9bcaf45bd4c0ed4bc1a5fdfe7bce'),

];
