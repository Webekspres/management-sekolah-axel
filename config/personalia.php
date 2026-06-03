<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Import queue connection
    |--------------------------------------------------------------------------
    |
    | Impor siswa/guru memakai antrian Laravel. Nilai "sync" memproses semua
    | baris langsung saat unggah — cocok untuk hosting tanpa akses terminal
    | (tidak perlu php artisan queue:listen).
    |
    | Set ke null agar mengikuti QUEUE_CONNECTION global (.env).
    |
    */

    'import_queue_connection' => env('PERSONALIA_IMPORT_QUEUE', 'sync'),

];
