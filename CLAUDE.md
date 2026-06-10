# Repuestos de Bici Jorge — Contexto del proyecto

## Qué es y para qué sirve

Catálogo digital de repuestos de bicicleta para Jorge, un jubilado que vende repuestos desde su casa en Virrey del Pino, Buenos Aires, Argentina. Los clientes llegan al sitio escaneando un código QR pegado en el taller.

El sitio muestra los productos con foto, nombre y descripción. Cada tarjeta tiene un botón que abre WhatsApp con un mensaje pre-armado para consultar precio con Jorge directamente.

**No hay carrito, precios, ni backend.** El sitio es un catálogo de vitrina, nada más.

## Perfil de usuarios

- Vecinos del barrio, adultos, muchos con baja alfabetización digital
- Acceden desde smartphones Android de gama baja
- Conexión variable (3G/4G)
- Llegan por QR, no por búsqueda orgánica
- El catálogo cambia pocas veces por año

## Arquitectura de archivos

```
repuestos-bici/
├── index.html              # TODO el sitio: HTML + CSS + JS en un solo archivo
├── manifest.json           # Configuración PWA
├── sw.js                   # Service Worker: cache offline
├── cartel_como_funciona.html  # Cartel A4 imprimible con QR y pasos de uso
├── qr-code.jpg             # Imagen del código QR para el cartel
├── fotos/                  # Fotos de productos (JPEGs, nombres con espacios)
│   ├── Camara.jpg
│   ├── Cadena.jpg
│   └── ... (ver lista completa en el array `productos`)
└── icons/
    ├── icon-192.png         # Ícono PWA 192×192 (emoji 🚲 sobre fondo blanco)
    └── icon-512.png         # Ícono PWA 512×512 (ídem)
```

### `index.html` — archivo principal

Todo el código vive acá. Estructura interna:

1. `<head>` — metas, OG tags, manifest, preconnect a Google Fonts (Nunito)
2. `<style>` — CSS completo con variables en `:root`
3. `<svg style="display:none">` — símbolo del ícono de WhatsApp, definido una sola vez y reutilizado con `<use href="#wa-icon">`
4. HTML estático — header, wa-banner, reparaciones, buscador, filtros de categoría, grid de productos, sección de encargo, footer
5. `<script>` — constante WA, array `productos`, array `categorias`, funciones `buildCats()`, `setCat()`, `render()`, `norm()`, `waLink()`, registro del Service Worker

## Tecnologías

- **HTML/CSS/JS vanilla** — sin frameworks, sin build
- **Google Fonts** — Nunito (400, 700, 800)
- **PWA** — manifest.json + Service Worker para uso offline
- **WhatsApp Business** — links `https://wa.me/<número>?text=<mensaje>` como único canal de venta

No hay base de datos, servidor, ni dependencias npm.

## Decisiones técnicas importantes

### Único archivo HTML
Elegido deliberadamente: sin proceso de build, fácil de editar por alguien sin conocimientos técnicos, fácil de hostear en cualquier lado (GitHub Pages, Netlify, etc.).

### Número de WhatsApp centralizado
```js
const WA = '5491154767247'; // ← único lugar a cambiar si cambia el número
```
Todos los links de WhatsApp se generan desde esta constante. Nunca hardcodear el número en otro lado.

### Búsqueda insensible a acentos
```js
function norm(str) {
  return str.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}
```
La búsqueda normaliza tanto el query como los datos de cada producto antes de comparar. Así "camara" encuentra "Cámara de aire". Esto es crítico porque el producto más buscado es "cámara" y la mayoría lo escribe sin acento.

### Categoría por defecto: "Todos"
`let catActual = 'todos';` — se abre mostrando todos los productos para que usuarios con baja alfabetización digital no piensen que el catálogo solo tiene 6 ítems (los "más pedidos").

### Búsqueda ignora categoría activa
Cuando hay texto en el buscador, se ignora el filtro de categoría. Evita el caso confuso de "busco 'cadena' en 'Frenos'" y aparecen 0 resultados.

### `destacado: true` en lugar de categoría duplicada
Los productos de "Lo más pedido" tienen una flag `destacado: true` en el array. No es una categoría separada. Esto evita duplicar la definición de cada producto.

### `object-fit: contain` en imágenes
Las fotos de productos son de distintas dimensiones. Se usa `contain` (no `cover`) para que se vea el producto completo sin recorte.

### Fallback de imagen a emoji
Si la foto no carga (o el producto no tiene foto), se muestra el emoji del producto:
```js
onerror="this.style.display='none'; this.parentElement.querySelector('.emoji-fallback').style.display='flex'"
```

### Scroll al tope al cambiar categoría
```js
function setCat(id) {
  catActual = id;
  buildCats();
  render();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
```

### Colores de WhatsApp oscurecidos por accesibilidad
El verde de WhatsApp `#25D366` tiene contraste 1.98:1 con texto blanco (mínimo WCAG AA: 4.5:1). Se usa `#166534` (Tailwind green-800, ~7.1:1) en botones y `#14532d` para hover. El verde brillante `#25D366` solo aparece en el `theme-color` del meta tag (no es texto).

Variables CSS relevantes:
```css
:root {
  --verde-dark: #16a34a;   /* verde para elementos de UI no-WA */
  --wa:         #166534;   /* fondo de botones WhatsApp */
  --wa-dark:    #14532d;   /* hover de botones WhatsApp */
  --texto-suave: #475569;  /* texto secundario — oscurecido para contraste */
  --texto-leve:  #4b5563;  /* texto terciario — oscurecido para contraste */
}
```

### Service Worker — cache por versión
`sw.js` usa una constante `CACHE = 'repuestos-jorge-v5'`. **Cada vez que se actualiza el catálogo hay que incrementar ese número** para forzar que los usuarios reciban la versión nueva (las imágenes se cachean con cache-first).

Estrategias:
- Imágenes → cache-first (se guardan al primer acceso)
- Navegación HTML → network-first con fallback a cache (siempre intenta bajar la última versión)
- Resto → network con fallback a cache

### Íconos PWA
Generados con Playwright (no Pillow — calidad insuficiente). El emoji 🚲 aparece con `transform: translateY(-17%)` para compensar las métricas de texto del emoji que lo desplazan hacia abajo. Fondo blanco.

Script de generación (requiere Playwright instalado):
```js
// Requiere: require('/opt/node22/lib/node_modules/playwright')
// Tamaño de fuente: Math.round(size * 0.58) px
// translateY: -17%
```

## Features implementados

| Feature | Cómo funciona |
|---|---|
| Catálogo con fotos | Array `productos[]` → función `render()` genera tarjetas |
| Filtro por categoría | Botones `.cat-btn`, función `setCat()` |
| Buscador | `<input>` → `render()` con `norm()` para acentos |
| "Lo más pedido" | Flag `destacado: true` en productos seleccionados |
| WhatsApp por producto | `waLink(nombre)` genera URL `wa.me` con mensaje pre-armado |
| WA general y por encargo | IDs `wa-main` y `wa-encargo`, links asignados en JS desde constante `WA` |
| Fallback sin foto | Emoji definido por producto, se muestra si falla el `<img>` |
| Badge "Por encargo" | Campo `stock: 'encargo'` en el producto (actualmente solo Piñón 7 coronas) |
| Offline / PWA | manifest.json + sw.js + icons |
| Cartel imprimible | `cartel_como_funciona.html` — diseño A4 con QR y 3 pasos |

## Cosas que NO hacer

- **No hardcodear el número de WhatsApp** en el HTML. Siempre usar la constante `WA` en JS.
- **No usar `object-fit: cover`** en imágenes de producto — los productos se recortarían.
- **No cambiar `--wa` a `#25D366`** (el verde brillante de WhatsApp) — falla WCAG AA con texto blanco.
- **No olvidar incrementar** `CACHE` en `sw.js` cuando se actualiza el catálogo (fotos o productos).
- **No crear un sistema de build** ni agregar npm — la simplicidad es una feature, no una deuda.
- **No agregar precios al catálogo** — Jorge los negocia por WhatsApp caso a caso.
- **No cambiar la categoría por defecto** de `'todos'` a ninguna otra — fue decisión deliberada de UX.
- **No usar `h3` directamente debajo de `h1`** — hay que respetar la jerarquía h1→h2 para accesibilidad.

## Cómo correr localmente

No hay servidor ni dependencias. Cualquier servidor estático sirve:

```bash
python3 -m http.server 7777
# abre http://localhost:7777/
```

Para Lighthouse:
```bash
CHROME_PATH=/opt/pw-browsers/chromium-1194/chrome-linux/chrome \
  npx lighthouse http://localhost:7777/ \
  --only-categories=performance,accessibility,best-practices,seo \
  --chrome-flags="--headless --no-sandbox"
```

Para regenerar los íconos PWA (si fuera necesario):
```bash
node -e "
const {chromium} = require('/opt/node22/lib/node_modules/playwright');
// ver lógica: tamaño fuente = size * 0.58, translateY = -17%
"
```

## Agregar o modificar un producto

Todo está en el array `productos` dentro de `<script>` en `index.html`. Cada producto:

```js
{
  nombre: 'Nombre visible',
  desc:   'Descripción corta',
  cat:    'ruedas' | 'pedaleo' | 'frenos' | 'manejo' | 'varios',
  destacado: true,          // opcional — aparece en "Lo más pedido"
  emoji:  '⚫',             // fallback si no hay foto
  foto:   'fotos/Nombre.jpg', // null si no tiene foto
  stock:  'encargo',        // opcional — muestra badge "Por encargo"
}
```

Después de agregar un producto con foto nueva, hay que **incrementar el número de caché** en `sw.js`.

## Scores Lighthouse (estado actual)

| Categoría | Score |
|---|---|
| Performance | 86 (medido en servidor de desarrollo; en producción será mayor) |
| Accessibility | **100** |
| Best Practices | 96 |
| SEO | 100 |

## Tareas pendientes / ideas a futuro

- **Optimización de imágenes**: convertir las fotos de `fotos/` a WebP y comprimir. Ahorraría datos móviles significativos para usuarios con conexión lenta. Herramienta: `squoosh` CLI o `sharp`.
- **Subir a producción**: el sitio vive en el repo pero no tiene hosting configurado. GitHub Pages o Netlify serían opciones simples y gratuitas.
- **Actualizar el QR** si cambia la URL de producción (`qr-code.jpg` y `cartel_como_funciona.html`).

## Problemas conocidos y soluciones

| Problema | Solución |
|---|---|
| Foto no coincide con producto | Algunos `foto:` apuntan a archivos con nombre ligeramente distinto al que existe en `fotos/`. Verificar con `ls fotos/` si una imagen no carga. |
| Ícono PWA no se actualiza | Incrementar `CACHE` en `sw.js` para invalidar el caché de imágenes. |
| Buscador no encuentra con acento | Usar `norm()` — ya implementado. Si falla, verificar que la regexp use `̀-ͯ` (caracteres Unicode literales, no bytes UTF-8). |
| Performance baja en Lighthouse local | Normal — servidor de desarrollo es lento. En producción con CDN el score será mayor. |
