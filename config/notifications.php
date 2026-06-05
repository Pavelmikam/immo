<?php

return [
    'channels' => [
        'mail'     => env('NOTIF_MAIL_ENABLED', true),
        'database' => env('NOTIF_DATABASE_ENABLED', true),
    ],
    'types' => [
        'rental_request_received',
        'rental_request_accepted',
        'rental_request_refused',
        'message_received',
        'property_approved',
        'property_rejected',
        'visit_scheduled',
        'saved_search_match',
    ],
];
