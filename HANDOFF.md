# HANDOFF — Repuestos de Bici Jorge
**Fecha:** 2026-06-12  
**Rama de trabajo:** `claude/audit-small-business-ux-qeA0F`  
**Rama de handoff:** `migracion/handoff-2026-06-12`

---

## Objetivo de la sesión

Auditoría UX y técnica completa del catálogo digital de Jorge (vendedor de repuestos de bicicleta en Virrey del Pino, Buenos Aires). Se auditó, corrigió e implementaron todas las mejoras acordadas con el usuario. La sesión abarcó desde el análisis inicial hasta llevar Lighthouse Accessibility a 100/100.

---

## Estado actual: TODO COMPLETADO ✓

No hay trabajo pendiente. El proyecto quedó en estado producción-listo.

### Qué se hizo (en orden cronológico)

| Commit | Qué se hizo |
|---|---|
| `86e8d98` | Refactor UX completo: data model, bugs de rutas de fotos, estructura general |
| `f5fb3dd` | Búsqueda insensible a acentos (`norm()` con NFD), arranque en categoría "Todos" |
| `46009b8` | Restaurar placeholder con ejemplos ("Ej: cámara, cadena, freno..."), preconnect Fonts, fallback emoji |
| `1da34d7` | 3 productos nuevos confirmados por el usuario: Cambio, Grip playera, Caramañola |
| `454c685` | `object-fit: contain` en imágenes (antes se recortaban) |
| `a11895c` | Número WA centralizado en constante `WA`, scroll-to-top al cambiar categoría, `theme-color` meta, eliminar archivo vacío `catalogo_jorge` |
| `3d27365` | PWA completa: manifest.json, sw.js (cache v1), íconos generados con Playwright |
| `53b83aa` | Ícono PWA definitivo: emoji 🚲 sobre fondo blanco, centrado con `translateY(-17%)` |
| `92edfee` | Accesibilidad WCAG AA: textos oscurecidos, `h3→h2`, agregar `<main>` landmark |
| `6ecf4a5` | Contraste botones WhatsApp: `#16a34a→#166534` (~7.1:1 con texto blanco) |
| `8cc295a` | Crear CLAUDE.md con contexto completo para futuras sesiones |

### Scores Lighthouse finales

| Categoría | Score |
|---|---|
| Performance | 86 *(servidor dev; en producción será mayor)* |
| Accessibility | **100** |
| Best Practices | 96 |
| SEO | 100 |

---

## Tareas pendientes (ideas futuras, no urgentes)

En orden de impacto real para el usuario:

1. **Optimización de imágenes** *(mayor impacto práctico)*  
   Las fotos en `fotos/` son JPEGs sin comprimir. Convertir a WebP y comprimir reduciría datos móviles para usuarios con conexión lenta (el público objetivo usa 3G/4G).  
   Herramienta recomendada: `squoosh` CLI o `sharp`.  
   Después de comprimir, **incrementar `CACHE`** en `sw.js` (actualmente en `v5`).

2. **Subir a producción**  
   El sitio vive en el repo pero no tiene hosting. GitHub Pages o Netlify son opciones simples y gratuitas. Pasos: activar Pages desde `Settings → Pages`, seleccionar la rama `main` y carpeta raíz.

3. **Actualizar el QR**  
   `qr-code.jpg` y `cartel_como_funciona.html` usan el QR actual. Si cambia la URL de producción, hay que regenerar el QR y reemplazar la imagen.

---

## Errores conocidos

*No hay errores activos.* Todos los bugs encontrados durante la sesión fueron resueltos:

| Problema (resuelto) | Solución aplicada |
|---|---|
| Fotos con `(1)` en el nombre no cargaban | Rutas corregidas a `Herradura freno.jpg`, `Inflador de taller.jpg` |
| `Tornillitos` apuntaba a foto incorrecta | `foto: null` (muestra emoji fallback) |
| Regex de acentos con bytes UTF-8 en lugar de escapes `\u` | Reescrita con `̀-ͯ` como literal string |
| Íconos PWA con verde viejo luego de cambiar fondo | Bump de versión en `CACHE` para invalidar cache de imágenes |
| `#25D366` (WhatsApp green) + texto blanco = 1.98:1 contraste | `--wa: #166534` (~7.1:1) |
| `#16a34a` + texto blanco = 3.29:1 (también insuficiente) | Darkened further to `#166534` |
| `h3` directo bajo `h1` (jerarquía rota) | `h3→h2` en secciones reparaciones y encargo |
| Sin landmark `<main>` | Agregado envolviendo el contenido principal |

---

## Comandos especiales de este proyecto

### Correr localmente
```bash
cd /home/user/repuestos-bici
python3 -m http.server 7777
# abrir http://localhost:7777/
```

### Lighthouse
```bash
CHROME_PATH=/opt/pw-browsers/chromium-1194/chrome-linux/chrome \
  npx lighthouse http://localhost:7777/ \
  --only-categories=performance,accessibility,best-practices,seo \
  --chrome-flags="--headless --no-sandbox"
```
> Nota: la categoría `pwa` fue removida en Lighthouse 12+. No incluirla o falla.

### Regenerar íconos PWA
```bash
node -e "
const {chromium} = require('/opt/node22/lib/node_modules/playwright');
(async () => {
  const browser = await chromium.launch();
  // tamaño fuente: Math.round(size * 0.58)
  // translateY: -17%  ← ajuste crítico para centrar el emoji verticalmente
  // fondo: blanco (#ffffff)
  browser.close();
})();
"
```

---

## Configuración y variables relevantes

| Ítem | Valor / Ubicación |
|---|---|
| Número WhatsApp | `const WA = '5491154767247'` — línea 458 de `index.html` |
| Versión de caché SW | `const CACHE = 'repuestos-jorge-v5'` — línea 2 de `sw.js` |
| Rama de trabajo | `claude/audit-small-business-ux-qeA0F` |
| Color botones WA | `--wa: #166534` (no cambiar a verde más claro — falla WCAG AA) |
| Categoría default | `let catActual = 'todos'` — decisión UX deliberada, no cambiar |

---

## Nota sobre CLAUDE.md

Existe un `CLAUDE.md` en la raíz con documentación técnica exhaustiva del proyecto (arquitectura, decisiones, restricciones, cómo agregar productos). Este `HANDOFF.md` documenta específicamente el estado de la sesión de migración. Para contexto de largo plazo, leer `CLAUDE.md`.
