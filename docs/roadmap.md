# Roadmap — Framework Coltman

Cambios planificados en orden de prioridad. El objetivo de cada fase es acercar el framework a un reemplazo completo y robusto de ACF sin depender de plugins externos.

---

## ✅ Fase 1 — Estabilidad y seguridad — COMPLETADA (v1.4.0)

Estos cambios deben aplicarse antes de usar el framework en entornos de producción con múltiples editores.

### ✅ 1.1 Verificación de nonce en `save_post` — RESUELTO (v1.4.0)
**Clase:** `ColtmanCreateMetabox`  
**Problema:** `save_post()` guarda campos sin verificar nonce ni permisos. Cualquier request POST al endpoint de WordPress puede alterar el meta.  
**Cambio:** Agregar campo nonce en `add_meta_box_callback()` y verificarlo al inicio de `save_post()`.

### ✅ 1.2 Sanitización completa por tipo en `save_post` — RESUELTO (v1.4.0)
**Clase:** `ColtmanCreateMetabox`  
**Problema:** `textarea` y el caso `default` guardan el valor raw de `$_POST`. ~~Además, `textarea` no tiene `break` — cae al caso `default` y llama a `update_post_meta` dos veces.~~ ✅ `break` corregido en v1.3.1.  
**Cambio pendiente:** Centralizar la sanitización en un método privado `sanitize_field($field, $value)` que aplique la función correcta según `type`.

### ✅ 1.3 Verificación de nonce y permisos en `ColtmanTermMeta` — RESUELTO (v1.4.0)
**Clase:** `ColtmanTermMeta`  
**Problema:** `wpturbo_save_meta_fields()` no verifica `current_user_can()`.  
**Cambio:** Agregar verificación `manage_categories` o la capacidad del taxonomy antes de guardar.

### ✅ 1.4 Eliminar `var_dump` y `console.log` de producción — RESUELTO (v1.4.0)
**Archivos:** `class-metabox.php` (línea con `var_dump` comentado), `assets/js/media.js` (múltiples `console.log`)  
**Cambio:** Eliminar los `var_dump` comentados en PHP y los `console.log` del JS, o envolverlos en una bandera de debug.

---

## ✅ Fase 2 — Unificación del renderizado de campos — COMPLETADA (v1.4.0)

### ✅ 2.1 Migrar `ColtmanTermMeta` a `ColtmanInputFields` — RESUELTO (v1.4.0)
**Problema:** `ColtmanTermMeta` tiene su propio renderizado (`wpturbo_render_input_field`) que solo soporta `select`, `textarea`, `media` y el fallback `default`. Los tipos `gallery`, `accordion`, `get_posts`, `get_terms` y `editor` no funcionan en campos de término.  
**Cambio:** Refactorizar `ColtmanTermMeta` para instanciar `ColtmanInputFields` (igual que hace `ColtmanCreateMetabox`) y delegar todo el renderizado a esa clase.

### ✅ 2.2 Estandarizar la definición de campos — RESUELTO (v1.4.0)
**Problema:** `ColtmanCreateMetabox` define campos como `['id' => 'campo', 'type' => '…']` pero `ColtmanTermMeta` usa la clave del array como ID `['campo' => ['type' => '…']]`. La inconsistencia obliga a recordar qué convención usa cada clase.  
**Cambio:** Adoptar el formato de `ColtmanCreateMetabox` para todas las clases. Migrar `ColtmanTermMeta` para aceptar el mismo formato.

---

## ✅ Fase 3 — Nuevos tipos de campo — COMPLETADA (v1.10.0)

### ✅ 3.1 Implementar el campo `repeater` — RESUELTO (v1.7.0)
**Solución aplicada:** `ColtmanInputFields::repeater()` con sub-campos configurables por fila (JSON). Tipos de sub-campo: `text`, `textarea`, `select`, `checkbox`, `color`, `email`, `url`. Drag-and-drop con jQuery UI Sortable. JS `addRepeaterRow()` / `removeRepeaterRow()` en `media.js`. Sanitización por tipo de sub-campo en `save_post()` y `wpturbo_save_meta_fields()`.

### ✅ 3.2 Campo `color` — RESUELTO (v1.7.0)
**Solución aplicada:** `ColtmanInputFields::color()` — `<input type="text">` con clase `coltman-color-picker` inicializado por `wp-color-picker` en `$(document).ready`. Atributo `data-default-color` configurable. Sanitización con `sanitize_text_field()`.

### ✅ 3.3 Campo `relationship` (relación entre posts) — RESUELTO (v1.7.0)
**Solución aplicada:** `ColtmanInputFields::relationship()` — `<select multiple>` con Select2 AJAX. Handler `wp_ajax_coltman_relationship_search` en `ajax.php` (nuevo archivo). Búsqueda paginada (20/página), nonce `coltman_relationship` embebido en `data-nonce`. Pre-popula opciones desde JSON al renderizar.

### ✅ 3.4 Campo `wysiwyg` como alias de `editor` — RESUELTO (v1.7.0)
**Solución aplicada:** `ColtmanInputFields::wysiwyg()` llama directamente a `$this->editor()`. Añadido en el `switch` de `field()` en `ColtmanCreateMetabox` y `wpturbo_render_input_field()` en `ColtmanTermMeta`.


### ✅ 3.5 Select2 para `get_posts` y `get_terms` — RESUELTO (v1.5.0)
**Integración transparente:** `ColtmanInputFields` agrega internamente la clase `js-select2` y los atributos `data-placeholder` / `data-allow-clear`. `media.js` detecta la clase y activa Select2. El desarrollador no cambia nada en la definición del campo.

### ✅ 3.6 Editor WYSIWYG propio en `accordion` — RESUELTO (v1.5.0)
**Método:** `ColtmanInputFields::accordion_editor()` renderiza un `<div contenteditable>` con barra de herramientas (B, I, U, listas, enlace, limpiar) y un `<textarea hidden>` sincronizado en tiempo real. Elimina `wp_editor()`/TinyMCE del acordeón — sin problemas de clonado ni reinicialización.

### ✅ 3.7 Drag-and-drop para reordenar ítems del `accordion` — RESUELTO (v1.5.0)
**Implementación:** handle de arrastre (icono grip) en cada ítem; jQuery UI Sortable (ya incluido en WP admin) con `handle: '.accordion-drag-handle'`. El orden se persiste automáticamente al enviar el formulario: el handler `document.submit` reconstruye el JSON desde el orden DOM actual.

### ✅ 3.8 Campo `group` — card colapsable con layout `<th>`/`<td>` — RESUELTO (v1.8.0)
**Diseño final:** Una única `<tr>` donde `<th>` contiene el label del grupo + botón toggle (▲/▼), y `<td>` apila todos los sub-campos definidos en código. Los sub-campos se guardan en meta keys individuales (no JSON). El toggle colapsa/expande `div.coltman-group-body` vía JS. Disponible en metabox y term-meta.

### ✅ 3.9 Campo `group` — constructor de campos dinámicos sin código — RESUELTO (v1.9.0)
**Implementación:** Panel ⚙ "Manage fields" al pie de cada grupo que permite al administrador añadir o eliminar sub-campos sin tocar PHP. El esquema (tipo, clave, etiqueta) se persiste en `wp_options` (`_coltman_group_schema_{id}`) y es global para todos los posts/términos que usen ese grupo. Los campos dinámicos se renderizan después de los estáticos (prioridad al estático en caso de colisión de clave) y sus valores se guardan en meta keys individuales. Tipos de campo disponibles: `text`, `textarea`, `number`, `email`, `url`. Cambios de esquema via AJAX (`coltman_add_group_field`, `coltman_remove_group_field`) con nonce `coltman_group_schema` y capacidad `manage_options`.

### ✅ 3.10 Campo `map` — selector de coordenadas (Leaflet) — RESUELTO (v1.10.0)
**Implementación:** `ColtmanInputFields::map()` renderiza un mapa Leaflet interactivo en el admin: marcador draggable, click-para-colocar, botón Clear, inputs readonly de lat/lng. Guarda `{"lat":…,"lng":…,"zoom":…}` como JSON en una sola meta key. Leaflet 1.9.4 incluido localmente en `assets/libs/leaflet/` (sin CDN externo). `wp_localize_script` pasa `coltmanVars.assetsUrl` al JS para resolver las rutas de los iconos del marcador. El helper `coltmanLeafletIcon()` elimina `L.Icon.Default.prototype._getIconUrl` y construye un `L.icon({...})` explícito con rutas absolutas, resolviendo el problema conocido de iconos rotos en entornos con rutas de assets no estándar. Disponible en metabox y term-meta.

---

## ✅ Fase 4 — Soporte para el editor de bloques (Gutenberg) — COMPLETADA (v1.11.0)

### ✅ 4.1 Registrar meta con `show_in_rest: true` — RESUELTO (v1.11.0)
**Solución aplicada:** Nuevo método `register_rest_meta()` en `ColtmanCreateMetabox`, llamado en el hook `init`. Cualquier campo con `'rest' => true` en su configuración se registra automáticamente con `register_post_meta()` (`show_in_rest: true`, `single: true`, tipo REST mapeado por `rest_field_type()`). No rompe classic themes: el hook se registra pero no tiene efecto si no hay campos marcados con `rest`.

### ✅ 4.2 Panel lateral en Gutenberg (`PluginDocumentSettingPanel`) — RESUELTO (v1.11.0)
**Solución aplicada:** `enqueue_gutenberg_panel()` encola `assets/js/gutenberg-panel.js` solo cuando la pantalla actual coincide con un CPT del metabox y hay campos `rest: true`. El script usa `wp.element.createElement` (sin JSX ni build step) y registra un `PluginDocumentSettingPanel` con controles interactivos para tipos simples (`text`, `email`, `url`, `number`, `textarea`, `select`, `checkbox`) y un aviso informativo para tipos complejos (`gallery`, `repeater`, `relationship`, `group`, `map`). Completamente compatible con classic themes — sin efecto si el editor de bloques no está activo.

---

## Fase 5 — Portabilidad y configuración

### ✅ 5.1 Rutas de assets independientes del contexto — RESUELTO (v1.1.0)
**Solución aplicada:** `WORK_CONTEXT` fue reemplazado por `COLTMAN_ASSETS_URL`, calculada automáticamente con `plugins_url('assets', __FILE__)` en `class.php`. Esta función de WordPress convierte la ruta del filesystem a URL y funciona correctamente tanto en temas como en plugins sin ninguna configuración adicional. Las rutas hardcodeadas en `class-metabox.php` y `class-termeta.php` fueron actualizadas, y el enqueue de `media.js` en `class-usermetabox.php` fue activado.

Para sobreescribir la URL (CDN, path personalizado):
```php
define('COLTMAN_ASSETS_URL', 'https://cdn.ejemplo.com/coltman/assets');
require '.../classes/class.php';
```

### ✅ 5.2 Actualizar namespace — RESUELTO (v1.6.0)
**Solución aplicada:** `namespace Class\AddicClinicDirectory` reemplazado por `namespace Coltman\Framework` en `class.php`. El namespace solo aplica al loader — las clases incluidas con `require` están en el namespace global y no se ven afectadas. También actualizado el comentario que lo explicaba.

---

## Fase 6 — Calidad y mantenimiento

### ✅ 6.1 Actualizar Select2 a v4 — RESUELTO (v1.6.0)
**Solución aplicada:** `select2 3.4.8` desde CDN reemplazado por `select2 4.0.13` local en `assets/libs/select2/` (`select2.min.css` + `select2.min.js`). Registro y enqueue actualizados en `ColtmanCreateMetabox` y `ColtmanTermMeta` con `COLTMAN_ASSETS_URL . '/libs/select2/...'`. Sin dependencia de internet ni problemas de CSP.

### ✅ 6.2 Mover CSS inline a archivo externo — RESUELTO (v1.6.0)
**Solución aplicada:** Todo el CSS utilitario (Tailwind utilities, WYSIWYG, accordion, WP Editor) movido a `assets/css/admin.css`. Encolado con `wp_enqueue_style('coltman-admin', ...)` en `admin_enqueue_scripts()` de `ColtmanCreateMetabox` y `ColtmanTermMeta`. Los métodos `admin_head()` ya no emiten `<style>` inline.

### ✅ 6.3 PHPDoc completo — RESUELTO (v1.6.0)
**Solución aplicada:** `ColtmanRegisterTaxonomy` tenía propiedades sin tipos PHP 8 (`private $labels`, `private $taxonomy_name`, `private $post_types`, `private $args`, `private $capabilities`). Añadidos tipos explícitos (`array`, `string`) a todas las propiedades. `ColtmanRegisterPost` ya tenía tipos PHP 8 completos desde v1.0.0.

### ✅ 6.4 Sistema de traducción (i18n) — RESUELTO (v1.2.0)
**Solución aplicada:** Sistema completo de traducción con `COLTMAN_TEXT_DOMAIN = 'coltman'`. Archivos `.pot`, `.po` y `.mo` en `languages/` para `es_ES` y `nl_NL`. Carga automática en el hook `init` (prioridad 1) mediante una closure para evitar el problema de namespace. Los 3 text domains incorrectos (`advanced-options`, `addic-clinic-directory`, `udh`) y los 3 strings sin envolver en `__()` fueron corregidos.

### ✅ 6.5 Pruebas unitarias — RESUELTO (v1.3.0)
**Solución aplicada:** Suite completa con PHPUnit 10. No requiere instalación de WordPress — usa stubs PHP puros con un sistema de spy/stub integrado.

| Suite | Clase | Tests |
|---|---|---|
| `RegisterPostTest` | `ColtmanRegisterPost` | 18 |
| `RegisterTaxonomyTest` | `ColtmanRegisterTaxonomy` | 19 |
| `CreateMetaboxTest` | `ColtmanCreateMetabox` | 30 |
| `InputFieldsTest` | `ColtmanInputFields` | 59 |
| `TermMetaTest` | `ColtmanTermMeta` | 26 |
| `UserMetaTest` | `ColtmanCreateUserMeta` | 27 |
| `UtilsTest` | `coltman_trim_content_text_fn`, `formaturltext` | 9 |
| `ReadTimeTest` | `get_estimated_reading_time` | 13 |
| `NavigationsAnchorsTest` | heading injection functions | 21 |
| **Total** | | **250 tests / 457 assertions** |

```bash
cd classes/ && php vendor/bin/phpunit --no-coverage
# → OK (250 tests, 457 assertions)
```

**Bug corregido durante la implementación:** `ceil()` devuelve `float` en PHP 8+. La comparación estricta `$reading_time === 1` en `read-time.php` siempre era `false`, causando que el sufijo singular nunca se usara. Corregido con `(int) ceil(...)`.

### ✅ 6.6 Documentar requisitos mínimos — RESUELTO (v1.3.0)
**Solución aplicada:** Requisitos documentados en `class.php` (comentario al inicio del loader) y en `readme.md` (sección "Requisitos mínimos" con tabla y snippet de verificación).

| Componente | Mínimo | Motivo |
|---|---|---|
| PHP | 8.0 | Union types · `str_starts_with()` |
| WordPress | 5.0 | `determine_locale()` · `show_in_rest` |
| ext-iconv | — | `formaturltext()` |
