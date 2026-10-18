<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinanzasAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = mb_strtolower(trim((string) $request->session()->get('usuario')));
        $administradores = config('finanzas.admin_users', []);

        if ($usuario === '') {
            return redirect()->route('login');
        }

        abort_unless(in_array($usuario, $administradores, true), 403, 'No tienes permisos de administrador para Finanzas.');

        return $next($request);
    }
}
