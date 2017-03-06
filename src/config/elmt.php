<?php

return [
    'key' => env('ELMT_KEY', 'elmt_secret_key'),
    'system-connection' => env('ELMT_SYSTEM_CONNECTION', 'elmt'),
    'tenant-connection' => env('ELMT_TENANT_CONNECTION', 'tenant'),
    'tenant-admin-connection' => env('ELMT_TENANT_ADMIN_CONNECTION', 'tenant_admin')
];