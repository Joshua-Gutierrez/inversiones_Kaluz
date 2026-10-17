# 🧠 CONTEXTO IA - TAXI KALUZ PWA (Claude Code / Copilot / Gemini)

Este repositorio (`sistema/`) contiene la plataforma monolítica **Taxi Kaluz PWA** construida con **Laravel 12, Blade, Tailwind CSS (Vite) y Supabase**.

## 📁 Estructura del Proyecto (En Español)

```text
sistema/
├── .cursorrules                     # 🧠 CONTEXTO IA (Cursor / Windsurf)
├── CLAUDE.md                        # 🧠 CONTEXTO IA (Claude Code / Copilot / Gemini)
├── vercel.json                      # Configuración de despliegue rápido en Vercel
├── app/
│   ├── Models/                      # Modelos compartidos (Se crean en la Hora 1 y no se tocan más)
│   │   ├── Conductor.php            # Incluye scopes: habilitados(), flotaPropia()
│   │   ├── SolicitudViaje.php
│   │   ├── OfertaConductor.php
│   │   ├── PagoComision.php
│   │   └── PaqueteTuristico.php
│   │
│   ├── Http/
│   │   ├── Middleware/
│   │   │   └── CandadoFinancieroMiddleware.php  # Bloquea la PWA si debe S/ 1.00 o permiso vencido
│   │   │
│   │   └── Controllers/
│   │       ├── Despacho/            # 👨‍💻 ZONA DEV 1 (Subasta y PWA Conductor)
│   │       │   ├── ConductorPwaController.php
│   │       │   └── SubastaAtomicaController.php
│   │       ├── Finanzas/            # 👨‍💻 ZONA DEV 2 (Candado, Padrón y S/ 1.00)
│   │       │   ├── PadronController.php
│   │       │   └── ValidacionYapeController.php
│   │       ├── Turismo/             # 👨‍💻 ZONA DEV 3 (Combi, Tours y Mapa GPS)
│   │       │   ├── PaqueteTuristicoController.php
│   │       │   └── RastreoGpsController.php
│   │       └── Bot/                 # 👨‍💻 ZONA DEV 4 (WhatsApp Webhook y KPIs)
│   │           ├── WebhookWhatsAppController.php
│   │           ├── SimuladorChatController.php
│   │           └── TableroKpiController.php
│   │
│   └── Services/                    # Lógica de negocio e Inteligencia de cada módulo
│       ├── Despacho/
│       │   └── AsignacionAtomicaService.php
│       ├── Finanzas/
│       │   └── ValidacionCandadoService.php
│       ├── Turismo/
│       │   └── PrioridadFlotaPropiaService.php
│       └── Bot/
│           └── AnalizadorWhatsAppService.php
│
├── resources/views/
│   ├── layouts/
│   │   ├── pwa-movil.blade.php      # Layout Mobile-First (Sin barras, estilo App Nativa)
│   │   └── torre-control.blade.php  # Layout Torre de Control (Sidebar Azul Marino #0B1B3D)
│   ├── components/                  # Botones, Badges de semáforo y Tarjetas Kaluz
│   ├── despacho/                    # 👨‍💻 Vistas Dev 1 (Radar de viajes, botones contraoferta)
│   ├── finanzas/                    # 👨‍💻 Vistas Dev 2 (Tabla de 300 socios, modal foto Yape)
│   ├── turismo/                     # 👨‍💻 Vistas Dev 3 (Grid de tours, vista /rastreo/{id})
│   └── bot/                         # 👨‍💻 Vistas Dev 4 (Home de Torre de Control + Simulador WA)
│
└── routes/
    ├── web.php                      # Mantiene el Login intacto y hace require de las 4 rutas modulares:
    ├── despacho.php                 # 👨‍💻 Rutas Dev 1 (Archivo propio = Cero conflictos Git)
    ├── finanzas.php                 # 👨‍💻 Rutas Dev 2 (Archivo propio = Cero conflictos Git)
    ├── turismo.php                  # 👨‍💻 Rutas Dev 3 (Archivo propio = Cero conflictos Git)
    └── bot.php                      # 👨‍💻 Rutas Dev 4 (Archivo propio = Cero conflictos Git)
```

## 🔐 Reglas Principales
1. **No romper el Login existente**: Las rutas `/` y `POST /login` validan contra la tabla `usuarios` de Supabase y redirigen a `/dashboard`.
2. **Cero conflictos Git**: Cada desarrollador trabaja exclusivamente en su carpeta de Controladores, Servicios, Vistas y su propio archivo en `routes/`.
3. **Convención de nombres**: Nombres en español, sin caracteres especiales ni tildes en nombres de clases/archivos PHP (`SubastaAtomicaController`).
