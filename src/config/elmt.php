<?php

return [
    'key' => env('ELMT_KEY', 'elmt_secret_key'),
    'system-connection-name' => env('ELMT_SYSTEM_CONNECTION', 'elmt'),
    'tenant-connection-name' => env('ELMT_TENANT_CONNECTION', 'tenant'),
    'tenant-admin-connection-name' => env('ELMT_TENANT_ADMIN_CONNECTION', 'tenant_admin'),
];