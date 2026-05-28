# Nativos Launchpad · PHP + Supabase

Reescritura completa del frontend React/Vite a **PHP puro**, con el catálogo en **Supabase**.

## Estructura

```
php/
├── index.php                  Página principal
├── api/
│   ├── product.php            HTML del modal de detalle (fetch desde el front)
│   └── chat.php               Endpoint del chatbot (proxy a Gemini)
├── includes/
│   ├── data.php               Carga products / testimonials / tech_stack desde Supabase
│   ├── supabase.php           Cliente PostgREST + caché por archivo con TTL
│   ├── helpers.php            e(), filter_products(), paginate(), build_query(), icon()
│   ├── contact_handler.php    POST del form de contacto → storage/contact.log
│   ├── product_card.php       Card del listado
│   └── product_modal.php      Contenido del modal
├── db/
│   └── schema.sql             CREATE TABLE + RLS + seed de los 15 productos
├── assets/
│   ├── css/styles.css
│   └── js/app.js
├── storage/                   Logs + caché (auto-creado)
├── .env.example
└── README.md
```

## Setup

1. **PHP 8.0+** con `curl` habilitado.
2. **Supabase project** creado (https://app.supabase.com).
3. Cargar el esquema:
   - Supabase Dashboard → **SQL Editor → New query**
   - Pegar el contenido de [`db/schema.sql`](db/schema.sql) y **Run**.
   - Esto crea `products`, `testimonials`, `tech_stack`, activa RLS con políticas de lectura pública, e inserta los 15 productos + 3 testimonios + 12 items del tech stack.
4. Copiar credenciales:
   - Supabase Dashboard → **Project Settings → API**.
   - `SUPABASE_URL` = "Project URL".
   - `SUPABASE_SERVICE_ROLE_KEY` = "service_role" (clic en *Reveal*).
5. Configurar `.env`:
   ```bash
   cd php
   cp .env.example .env
   # editar .env y completar SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, GEMINI_API_KEY
   ```
6. Levantar el server:
   ```bash
   php -S localhost:8000
   ```
   Abrir <http://localhost:8000>.

## Variables de entorno

| Variable | Obligatoria | Default | Para qué |
|---|---|---|---|
| `SUPABASE_URL` | sí | — | URL del proyecto Supabase |
| `SUPABASE_SERVICE_ROLE_KEY` | sí | — | Service role (server-only). Bypassa RLS |
| `SUPABASE_SCHEMA` | no | `public` | Schema de las tablas |
| `SUPABASE_CACHE_TTL` | no | `60` | Segundos de caché del catálogo (0 = sin caché) |
| `GEMINI_API_KEY` | sí (para chat) | — | API key de Gemini |
| `GEMINI_MODEL` | no | `gemini-1.5-flash-latest` | Modelo a usar |

## Flujo de datos

```
Browser → index.php (PHP)
           └─ data.php
               └─ cached('products', …)
                   ├─ HIT  → lee php/storage/cache/products.json
                   └─ MISS → supabase_get() vía cURL → REST de Supabase
                              y refresca el archivo de caché
```

- Si Supabase está caído y hay caché, sirve **el caché viejo** (stale-on-error) y loguea el error en `error_log`.
- Si nunca hubo caché y Supabase falla, la página renderiza vacía sin 500.

## Editar el catálogo

Tres formas:
1. **Supabase Dashboard → Table Editor → products** (lo más cómodo).
2. **SQL Editor** con `UPDATE / INSERT / DELETE`.
3. Por código (PHP) con la service_role; lo agregamos cuando montemos un panel admin.

> Los cambios tardan hasta `SUPABASE_CACHE_TTL` segundos en aparecer. Para forzar, borrá `php/storage/cache/products.json`.

## Mapeo Supabase ↔ PHP

La tabla `products` usa snake_case y JSONB; el código de la app espera el shape original camelCase. El mapeo lo hace `map_product_row()` en `includes/supabase.php`:

| Columna SQL | Tipo | Campo PHP |
|---|---|---|
| `id` | text PK | `id` |
| `title` | text | `title` |
| `category` | text | `category` |
| `description` | text | `description` |
| `price` | text | `price` |
| `image` | text | `image` |
| `stats` | jsonb | `stats` (array decodificado) |
| `details` | jsonb | `details` |
| `included` | jsonb | `included` |
| `is_verified` | bool | `isVerified` |
| `security_rating` | numeric | `securityRating` |
| `seller_verified` | bool | `sellerVerified` |
| `sort_order` | int | (usado solo para ordenar) |

## Seguridad

- **service_role nunca llega al navegador**: vive en `.env` del server.
- RLS activado: la `anon key` solo permite `SELECT` (lectura pública del catálogo).
- Output siempre escapado con `e()` (htmlspecialchars).
- El form de contacto valida en server. Para producción: agregar CSRF token.

## Qué cambió respecto al original React/Vite

- **Estado**: ahora el filtrado/paginación es server-side via query string. Funciona sin JS.
- **Datos**: salen de Supabase, ya no están hardcodeados.
- **Chatbot**: la API key vive en `.env`, no en el navegador.
- **Veo (video con IA)**: reemplazado por placeholder. Si lo necesitás, te armo `/api/video.php` análogo a `chat.php`.
- **Fuse.js**: reemplazado por búsqueda PHP simple (`mb_strpos` sobre title+category+description).
- **framer-motion**: animaciones rehechas con CSS keyframes + `IntersectionObserver`.
