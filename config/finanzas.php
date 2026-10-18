<?php

$administradores = explode(',', (string) env('FINANZAS_ADMIN_USERS', ''));

return [
    'admin_users' => array_values(array_filter(array_map(
        static fn (string $usuario): string => mb_strtolower(trim($usuario)),
        $administradores
    ))),
];
