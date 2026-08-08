<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Persist Missing Translation Keys
    |--------------------------------------------------------------------------
    |
    | translate() renders a humanised fallback whenever a key is absent from the
    | locale's messages.php. Historically it also wrote that key back into the
    | file, which meant a running application mutated a version-controlled
    | source file. In production that left the deployment working tree dirty and
    | blocked the next git pull; it also let arbitrary runtime values - store
    | names, transaction references, third-party API messages, exception text -
    | become permanent translation entries.
    |
    | The default is false everywhere: fail-safe and deterministic. Enable it
    | deliberately in a local or staging environment when you want new keys
    | discovered for the admin Translations screen, then commit them as source.
    |
    | Disabling persistence never changes what a user sees. The fallback string
    | is identical either way.
    |
    */

    'persist_missing_keys' => (bool) env('TRANSLATION_PERSIST_MISSING_KEYS', false),

];
