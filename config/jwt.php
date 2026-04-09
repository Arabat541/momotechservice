<?php

return [
    'secret' => env('JWT_SECRET', ''),
    'ttl' => env('JWT_TTL', 1440), // minutes (24h)
    'algo' => 'HS256',
];
