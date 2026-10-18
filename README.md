<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Finanzas (Developer 2)

El padrón consulta registros reales de `conductores` y `pagos_comisiones`; no incluye un modo de demostración con identidades ficticias. La cuota acumula S/ 1 por día calendario desde `conductores.created_at`, se liquida el último día del mes y cuenta únicamente días aprobados de `pagos_comisiones` dentro del mes, usando `fecha_registro` como período. Si quedó impaga, el candado se aplica durante el cierre; al iniciar el siguiente mes se reinicia el cálculo y no se arrastra el saldo. El acceso del conductor y el filtro de flota usan este cálculo actual, no el estado financiero obsoleto de un mes anterior.

La fuente de datos de Finanzas es Supabase: Eloquent consulta PostgreSQL (tablas existentes `conductores` y `pagos_comisiones`), el login consulta `usuarios` por la API REST de Supabase y los comprobantes van a Supabase Storage privado. No hay fallback silencioso a SQLite. En local y despliegue configura `DB_CONNECTION=pgsql` y los datos de conexión Supabase (`DB_URL` o `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`); la conexión exige TLS con `DB_SSLMODE=require`. Mantén las credenciales solo en el entorno, nunca en `.env.example` ni en Git. Habilita `pdo_pgsql` y `pgsql` en PHP. Para ejecutar localmente configura `APP_ENV=local` y `APP_URL=http://127.0.0.1:8000`, y ejecuta `php artisan serve --host=127.0.0.1 --port=8000`. No uses `APP_ENV=production` con el servidor HTTP local: en producción la aplicación fuerza HTTPS.

Las acciones del panel permiten registrar y editar conductores, revisar comprobantes Yape/Plin, registrar la mora calculada al cierre y anotar alertas visibles en el panel (sin envío por WhatsApp). El conductor puede subir la captura desde la pantalla de bloqueo el último día del mes; el servidor calcula el saldo y guarda el pago como pendiente, sin confiar en un monto enviado por el navegador. No se añaden columnas a las tablas existentes.

Las rutas de Finanzas y el panel administrativo requieren que `usuarios.usuario` esté en `FINANZAS_ADMIN_USERS`, configurado en `.env`/el entorno del despliegue con una lista separada por comas (ejemplo: `FINANZAS_ADMIN_USERS=usuario_admin`). La comparación ignora mayúsculas; si la lista está vacía, el panel queda cerrado por defecto. Para acceder a la PWA, el usuario de conductor debe iniciar sesión con el DNI de ocho dígitos que coincide con `conductores.dni`; el login establece la sesión del conductor y lo dirige al radar o a la pantalla del candado. Las cuentas de conductor no pueden abrir el panel administrativo.

Los comprobantes se guardan en un bucket privado de Supabase Storage (`comprobantes-yape` por defecto), no en el disco efímero de Vercel. Antes de habilitar la carga, crea manualmente el bucket privado en Supabase y configura `SUPABASE_SERVICE_ROLE_KEY` como secreto de servidor, además de `SUPABASE_STORAGE_BUCKET` si usas otro nombre. La clave de servicio no debe exponerse al navegador ni incluirse en Git. Configura `FINANZAS_YAPE_DESTINO` y/o `FINANZAS_PLIN_DESTINO` con la cuenta oficial para que la PWA la muestre; si no hay destino configurado, no se habilita la carga ni se muestra un número ficticio. Administración abre cada comprobante con un enlace firmado de cinco minutos. Este cambio no crea el bucket ni aplica modificaciones remotas.

La migración `2026_10_17_000000_create_alertas_financieras_table.php` añade `alertas_financieras` porque el esquema existente no cuenta con una bandeja persistente de alertas. Revisa y aplica las migraciones en el entorno elegido antes de habilitar esa acción; no se ejecutan automáticamente contra Supabase desde este cambio.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
