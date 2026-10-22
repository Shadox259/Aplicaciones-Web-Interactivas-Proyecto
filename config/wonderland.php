<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuracion propia de Library of Wonderland
    |--------------------------------------------------------------------------
    | Plataforma gratuita: no hay precios, carrito ni pagos.
    */

    // Libros por pagina en listados publicos.
    'per_page' => 12,

    // Carpeta fisica de portadas subidas (null = public/uploads/covers). Las pruebas la sustituyen.
    'covers_path' => null,

    // Tamano maximo de subida (KB): PDF 30 MB, portada 4 MB. XAMPP permite 40 MB por defecto.
    'max_pdf_kb' => 30720,
    'max_cover_kb' => 4096,

    // Muestra las cuentas de demostracion en la pagina de inicio de sesion. Por defecto solo en APP_ENV=local;
    // en Docker se enciende con WONDERLAND_SHOW_DEMO=true (ponlo en false al publicar el sitio).
    'show_demo_accounts' => filter_var(env('WONDERLAND_SHOW_DEMO', env('APP_ENV') === 'local'), FILTER_VALIDATE_BOOL),

    // Credenciales de demostracion (las crea el seeder). Se muestran al terminar la instalacion.
    'demo_accounts' => [
        ['role' => 'Administrador', 'email' => 'admin@wonderland.test', 'password' => 'admin123'],
        ['role' => 'Autor (creador de libros)', 'email' => 'autor@wonderland.test', 'password' => 'autor123'],
        ['role' => 'Cliente', 'email' => 'cliente@wonderland.test', 'password' => 'cliente123'],
    ],
];
