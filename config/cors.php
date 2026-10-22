<?php

return [

    /*
    | CORS: la API publica (solo lectura) puede consumirse desde otro origen, por ejemplo
    | el frontend Angular en http://localhost:4200.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'OPTIONS'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
