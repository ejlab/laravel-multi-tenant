<?php

return [
    'key' => env('ELMT_KEY', env('APP_KEY', 'elmt_secret_key')),
    'system-connection' => env('ELMT_SYSTEM_CONNECTION', 'system'),
    'tenant-connection' => env('ELMT_TENANT_CONNECTION', 'tenant'),
    'tenant-admin-connection' => env('ELMT_TENANT_ADMIN_CONNECTION', 'tenant_admin'),
    'tenant-id-column' => env('ELMT_TENANT_ID_COLUMN', 'domain'),
    'tenant-id-parameter' => env('ELMT_TENANT_ID_PARAMETER', 'domain'),
];