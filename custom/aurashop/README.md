# AuraShop

Tienda en línea de AURA LUXURY como extensión de Dolibarr. Dolibarr es la única fuente de verdad:
productos, stock, clientes, pedidos y facturas viven en sus tablas nativas y se escriben con sus clases.

Estado actual: **fases 0 y 1** (fundaciones + catálogo). Siguiente: fase 2 (carrito y clientes).

## Arquitectura

Hexagonal en backend y frontend. El dominio y los casos de uso no dependen de Dolibarr, Vue ni HTTP.

```
custom/aurashop/
├── api/                 API REST pública (sin login): index.php + bootstrap.php
├── src/                 PHP, namespace AuraShop\ (autoloader propio, sin Composer en runtime)
│   ├── Domain/          Catalog (producto, variantes, disponibilidad, categorías), Shared (Money, Page)
│   ├── Application/     Port/ (interfaces) y UseCase/
│   ├── Infrastructure/  Dolibarr/ (adaptadores), Http/ (router, controladores), Image/
│   └── Bootstrap/       Container.php: único lugar que une puertos con adaptadores
├── frontend/            Vue 3 + Vite + TypeScript + Tailwind
│   └── src/             domain/ · application/ · infrastructure/ · di/ · stores/ · router/ · ui/
├── scripts/demo_data.php
└── tests/               PHPUnit

public/AuraShop/
├── index.php            Shell de la SPA: SEO por ruta (title, Open Graph, JSON-LD) + config inyectada
└── assets/              Build de Vite (generado)
```

Apache corre con `AllowOverride None`, así que las rutas usan PATH_INFO:

- Tienda: `/public/AuraShop/index.php/catalogo`, `/public/AuraShop/index.php/producto/{REF}`
- API: `/custom/aurashop/api/index.php/v1/...`

## API v1

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/v1/config` | Nombre de la tienda, moneda, WhatsApp |
| GET | `/v1/catalog/categories` | Categorías de producto visibles |
| GET | `/v1/catalog/products?category=&q=&sort=&page=&perPage=` | Listado. `sort`: `newest`, `price_asc`, `price_desc`, `name`. Filtrar por una categoría incluye sus subcategorías |
| GET | `/v1/catalog/products/{ref}` | Ficha con variantes (tallas) y disponibilidad por variante |
| GET | `/v1/images/{ref}/{archivo}?size=mini\|card\|full` | Fotos del producto. `card` (720×900) se genera en `documents/aurashop/cache/images` |

Precios en centavos (`{"amount": 145000, "currency": "MXN"}`) con IVA incluido. La API nunca expone
cantidades exactas de stock, solo `in_stock`, `low_stock` (≤ 3) u `out_of_stock`. Solo se publican
productos con estado "En venta"; las variantes se muestran dentro de su producto padre.

## Configuración

Inicio → Configuración → Módulos → AuraShop (engrane):

- **Almacén de venta en línea**: de dónde sale la disponibilidad. Vacío = suma de todos los almacenes.
- **Número de WhatsApp**: con código de país (ej. `5215512345678`). Activa el botón "Pedir por WhatsApp".

## Comandos

Todo corre en Docker; no hace falta Node ni PHP en tu máquina.

```bash
# Compilar la tienda (verifica tipos y genera public/AuraShop/assets)
docker compose --profile front run --rm aurashop-front npm run build

# Desarrollo con recarga en caliente: http://localhost:5173 (proxy a la API de Dolibarr)
docker compose --profile front up aurashop-front

# Pruebas del frontend
docker compose --profile front run --rm aurashop-front npm test

# Pruebas del backend (requiere `composer install` en custom/aurashop una vez)
docker compose exec -w /var/www/html/custom/aurashop dolibarr php vendor/bin/phpunit

# Incluyendo las de integración contra Dolibarr real (saneamiento de HTML, etc.)
docker compose exec -u www-data -e AURASHOP_DOLIBARR=1 -w /var/www/html/custom/aurashop dolibarr php vendor/bin/phpunit

# Datos de demostración (productos DEMO-*) y su limpieza
docker compose exec -u www-data dolibarr php /var/www/html/custom/aurashop/scripts/demo_data.php seed
docker compose exec -u www-data dolibarr php /var/www/html/custom/aurashop/scripts/demo_data.php purge
```

## Despliegue

- Publica `custom/aurashop` **sin** `vendor/`, `frontend/node_modules/` ni `tests/`: están dentro de la
  raíz web de Dolibarr y en producción no se necesitan (el runtime no usa Composer).
- Publica `public/AuraShop/index.php` y `public/AuraShop/assets/` ya compilado.
- Tras cambiar menús o permisos del descriptor, desactiva y reactiva el módulo.
