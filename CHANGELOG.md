# Changelog — Coltman Framework

Todos los cambios notables de este módulo se documentan aquí.  
Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/).  
Versiones siguiendo [Semantic Versioning](https://semver.org/lang/es/).

---

## [1.15.2] — 2026-06-01

### Cambiado — rediseño visual de `accordion` y `repeater` (`admin.css`)

Todos los cambios son puramente CSS. No se tocó PHP, JS ni estructura HTML. Se mantienen todas las clases existentes.

**Accordion — `.accordion-item` / `.accodeon-item-content` / `.accodeon-item-panel`:**
- Item: reemplaza `bg-slate-100 p-4` por tarjeta blanca con `border: 1px solid #c3c4c7`, `border-radius: 3px`, padding cero.
- Columna de contenido (`.accodeon-item-content`): `flex: 1`, padding `14px 16px 16px`, gap `10px`. Título `<h3>` en uppercase 0.7rem con línea inferior sutil.
- Panel lateral (`.accodeon-item-panel`): franja fija de 50px, fondo `#f6f7f7`, borde izquierdo `#efefef`. Sustituye el `w-2/12` flotante.
- **Botón Remove** (1.º del panel): fondo `#d63638`, icono blanco visible. Hover: `#b32d2e`. Los SVG tenían `fill="white"` hardcodeado — fondo opaco es necesario para que sean visibles.
- **Botón Save** (2.º del panel): fondo `#2271b1`, icono blanco visible. Hover: `#135e96`.
- **Botón "Add Row"**: azul WP `#2271b1`, texto blanco, `width: fit-content`, `padding: 10px`, hover `#135e96`.
- `.accordion-sort-placeholder`: actualizado a `#f0f6fc` + borde punteado `#72aee6`.

**Repeater — `.repeater-row` / cabecera / sub-campos:**
- Fila: reemplaza `bg-slate-100 p-4 border-gray-300` por tarjeta blanca con `border: 1px solid #c3c4c7`, `border-radius: 3px`, gap y padding cero.
- **Cabecera** (`.repeater-row > div:first-child`): barra gris `#f6f7f7` con `border-bottom: 1px solid #efefef`, padding `6px 10px 6px 12px`.
- **Label "Row N"** (`.repeater-row-num`): uppercase 0.7rem, `font-weight: 600`, `#646970`.
- **Botón Remove**: fondo `#d63638`, icono blanco visible, 28×26px. Hover: `#b32d2e`.
- **Sub-campos**: padding `0 12px`; primer campo `padding-top: 10px`; entre campos `margin-top: 6px`; último campo `padding-bottom: 10px`. Labels `font-weight: 500`, `#3c434a`.
- **Botón "Add Row"**: azul WP `#2271b1`, texto blanco, `width: fit-content`, `padding: 10px`, hover `#135e96`.
- `.repeater-sort-placeholder`: actualizado a `#f0f6fc` + borde punteado `#72aee6`.

---

## [1.15.1] — 2026-06-01

### Agregado — campo alt text en el componente `media`

- **`media()` (`input-fields.php`)**: nuevo input `name="field_id_alt"` visible debajo del URL readonly. Valor cargado desde `$field['_alt_value']` (inyectado por cada clase de metabox al renderizar).
- **`class-metabox.php` — `field()`**: al despachar tipo `media`, se inyecta `$field['_alt_value']` leyendo `get_post_meta($post->ID, $field_id . '_alt', true)`.
- **`class-metabox.php` — `save_post()`**: nuevo `case 'media':` que sanitiza el valor (URL con `esc_url_raw` o ID con `absint` según `$field['return']`) y guarda el alt con `sanitize_text_field` bajo la meta key `field_id_alt`.
- **`class-termeta.php` — `wpturbo_render_input_field()`**: añadido parámetro `int $term_id = 0`; `case 'media':` inyecta `_alt_value` con `get_term_meta`.
- **`class-termeta.php` — `wpturbo_edit_meta_fields()`**: pasa `$term->term_id` a `wpturbo_render_input_field` para que el alt se cargue correctamente.
- **`class-termeta.php` — `wpturbo_save_meta_fields()`**: `case 'media':` separado de `case 'url':`. Guarda el valor con sanitización por tipo (`absint`/`esc_url_raw`) y el alt bajo `field_id_alt` con `continue 2` para evitar el `update_term_meta` genérico del final del loop.
- **`class-usermetabox.php` — `render_field_row()`**: inyecta `$field['_alt_value']` con `get_user_meta` antes de llamar a `render_field()`.
- **`class-usermetabox.php` — `save_user_meta()`**: nuevo `case 'media':` con misma lógica de sanitización + guarda `field_id_alt`.
- **`media.js` — select handler**: pre-rellena el `.coltman-media-alt` con `attachment.alt` cuando no tiene valor previo.
- **`media.js` — clear handler**: limpia también el `.coltman-media-alt` al hacer clear.

**Convención de meta keys:** el alt text de un campo `'id' => 'imagen_cover'` se guarda en `imagen_cover_alt`. Se lee en el frontend con `get_post_meta($post_id, 'imagen_cover_alt', true)`.

---

## [1.15.0] — 2026-06-01

### Agregado — rediseño del componente `media`

- **`media()` (`input-fields.php`)**: nuevo diseño visual del campo media. Reemplaza el layout `[input URL] [botón Upload]` por una tarjeta con:
  - **Thumbnail 64×64** — muestra preview automático para URLs de imagen (jpg, jpeg, png, gif, webp, svg). Ícono de archivo cuando está vacío o es un tipo no-imagen.
  - **Input URL readonly** — el campo es de solo lectura (los usuarios no deben escribir URLs a mano). Muestra el valor o `"No file selected"` como placeholder.
  - **Botón Upload** — abre el media picker de WordPress. Conserva `data-return="url|id"` y compatibilidad con todos los contextos existentes (standalone, inside accordion, inside group).
  - **Botón Clear** — aparece solo cuando hay valor. Limpia el input y resetea el thumbnail al ícono placeholder.
  - Clase `coltman-media-url` en el input + clase extra de `$field['class']` preservada para backward compat (acordeón usa `image-url-accodeon`).

- **`coltmanMediaUpdatePreview(wrap, value)` (`media.js`)**: función helper global que actualiza el thumbnail, el placeholder y el botón clear dado el wrapper `.coltman-media` y el nuevo valor.

- **Handler del picker actualizado (`media.js`)**: el select handler de `wp.media` ahora prioriza `.coltman-media` (componente nuevo) sobre el fallback legacy `button.prev().val()`. Orden de resolución: gallery item → coltman-media → legacy.

- **Handler de clear (`media.js`)**: nuevo `$('body').on('click', '.coltman-media-clear', ...)` que limpia el input y actualiza la preview vía `coltmanMediaUpdatePreview`.

- **Estilos `.coltman-media*` (`admin.css`)**: `.coltman-media`, `.coltman-media-preview`, `.coltman-media-thumb`, `.coltman-media-placeholder`, `.coltman-media-body`, `.coltman-media-url`, `.coltman-media-actions`, `.coltman-media-btn`, `.coltman-media-clear`. Diseño consistente con el componente `gallery`.

---

## [1.14.1] — 2026-06-01

### Corregido

- **`input-fields.php` — `editor()`: `textarea_rows` siempre devolvía `true/false`** en vez del número de filas configurado. La expresión `isset($field['rows']) ? isset($field['rows']) : 20` retornaba el resultado de `isset()` (bool) como valor. Corregido a `isset($field['rows']) ? $field['rows'] : 20`.
- **`input-fields.php` — `accordion()`: atributos HTML sin `esc_attr()`** en el hidden input (`name`, `id`, `value`) y en los wrappers de cada ítem (`data-id`, `id`). Todos pasan ahora por `esc_attr()`.
- **`class-metabox.php` — `add_meta_boxes()` ignoraba CPTs adicionales en configuración multi-CPT.** `add_meta_box()` recibía `$this->config['cpt']` (string CSV original) en vez de `$this->config['post-type']` (array normalizado por `process_cpts()`). Con un único CPT funcionaba por casualidad; con varios, el metabox no aparecía en ninguno.
- **`class-metabox.php` — `save_post()` campo `map`: sin null-check tras `json_decode()`.** Si el POST llegaba con JSON malformado, `$raw` era `null` y `isset($raw['lat'])` lanzaba un warning de PHP 8. Se añade `if ( ! is_array( $raw ) ) break;` antes de leer las claves.
- **`class-metabox.php` — `save_post()` campos `get_posts`/`relationship`: sin `is_array()`.** Sin la comprobación, `json_encode("string")` guardaba `'"string"'` en vez de `'["string"]'` cuando el POST traía un único valor no envuelto en array. Ahora usa `is_array() ? json_encode(...) : '[]'`, igual que el case `group`.
- **`class-metabox.php` — `group_field()`: `$field['description']` sin `esc_html()`.** El bloque `<p class="description">` hacía `echo $field['description']` directo. Corregido a `echo esc_html(...)`.
- **`class-usermetabox.php` — Select2 v3.4.8 desde CDN incompatible con `media.js`.** `media.js` usa la API de Select2 v4 (`ajax: { processResults }`). La v3 tiene una API distinta (`initSelection`, `query`), haciendo que los campos `get_terms`/`get_posts` en perfiles de usuario no funcionasen. Migrado a Select2 v4.0.13 local (`COLTMAN_ASSETS_URL/libs/select2/`), igual que `class-metabox.php` y `class-termeta.php`.
- **`class-usermetabox.php` — Enqueues faltantes en `admin_enqueue_scripts()`.** Añadidos: `coltman-admin` CSS (estilos del admin), `leaflet` CSS + JS (campos `map`), `jquery-ui-sortable` como dependencia de `coltman-media` (reordenar gallery/list/repeater/accordion), y `wp_localize_script('coltman-media', 'coltmanVars', [...])` (`media.js` accede a `coltmanVars.assetsUrl` al inicializar el mapa — sin esto lanzaba `ReferenceError` en cualquier página de perfil).
- **`class-usermetabox.php` — Tipos `relationship`, `color`, `repeater`, `map` y `get_terms` no implementados.** En `render_field()` caían al `default` (`<input type="text">`). En `save_user_meta()` no tenían sanitización propia. Añadidos ambos switches con la misma lógica que `class-metabox.php`.
- **`class-termeta.php` — Labels con interpolación directa sin escapar.** `"{$field['label']}"` en `wpturbo_render_meta_fields()` y `wpturbo_edit_meta_fields()` sustituido por `esc_html($field['label'])` y `esc_attr($field_id)`.

---

## [1.14.0] — 2026-05-28

### Agregado — nuevo componente `list`
- **`list_input()`** (`input-fields.php`): nuevo método que renderiza un componente de lista repetible con textarea por ítem. Sigue el mismo patrón que `gallery_input()`: hidden JSON array `[{item, text}]`, drag-and-drop sortable, add/remove con botones, y footer con "Add item".
- **Caso `list` en save handlers** (`class-metabox.php`, `class-termeta.php`, `class-usermetabox.php`): almacenamiento raw del JSON generado por JS, junto a `gallery` y `accordion`.
- **Caso `list` en rendering switches** (`class-metabox.php`, `class-termeta.php`, `class-usermetabox.php`): llama a `list_input()` para renderizar el campo.
- **List sortable + textarea sync** (`media.js`): jQuery UI Sortable sobre `.list-sortable` con handle `.list-drag-handle`; al soltar reordena el JSON array. Evento `input/change` en `.list-textarea` actualiza `data[idx].text` en el JSON oculto.
- **`addiTemList()` / `removeListItem()`** (`media.js`): funciones globales para añadir/clonar ítems vacíos y eliminar ítems filtrando el JSON por `data-item`.

### Corregido — componente `list` no persistía ni mostraba datos
- **`addiTemList()`** (`media.js`): clonaba el DOM con un `data-item` nuevo pero no creaba la entrada en el hidden `list-data`. El textarea handler buscaba `data[idx].item === dataItem`, no encontraba coincidencia (`idx === -1`), y descartaba el cambio. Ahora hace `data.push({item: uniqueId, text: ''})` al añadir un ítem.
- **Textarea sync handler** (`media.js`): solo actualizaba si `idx !== -1`. Si la entrada no existía (fallback de seguridad), el cambio se perdía. Ahora el `else` crea la entrada con `data.push({item: dataItem, text: $textarea.val()})`.
- **Estilos list** (`admin.css`): `.coltman-list`, `.coltman-list-item`, `.list-drag-handle`, `.list-textarea`, `.list-sort-placeholder`, `.coltman-list-footer`.
- **Tests** (`InputFieldsTest.php`): 3 tests nuevos (`test_list_input_outputs_list_wrapper`, `test_list_input_includes_add_item_button`, `test_list_input_renders_existing_items`). Suite: 256 tests / 470 assertions.

---

## [1.13.1] — 2026-05-28

### Corregido — componente `gallery` no persistía datos
- **Upload handler** (`media.js`): `e.currentTarget.parentNode.classList.contains('get-image')` revisaba `.coltman-gallery-url-row`, que no tiene la clase `get-image` (está en `.coltman-gallery-fields`). Se cambió a `e.currentTarget.closest('.get-image')` para que el bloque que construye el JSON con `id, alt, sizes, url` se ejecute al subir una imagen.
- **Sortable stop** (`media.js`): `$container.closest('.gallery')` no encontraba el wrapper (la clase es `.coltman-gallery`). El `$inputGallery` quedaba vacío y el nuevo orden nunca se persistía al JSON. Corregido a `.coltman-gallery`.
- **Alt text sync** (`media.js`): mismo error — `$altInput.closest('.gallery')` → `.coltman-gallery`. El alt edit nunca actualizaba el JSON.
- **URL manual sync** (`media.js`): se sincronizaba la miniatura pero no el hidden gallery-data. Ahora también actualiza `data[idx].url`.
- **Thumbnail `onerror`** (`input-fields.php`): el atributo inline causaba race condition con el `src=""` inicial, eliminando la clase `has-image` tras un upload exitoso. Reemplazado por handler delegado en `media.js`.
- **`addiTemImage`** (`media.js`): `e.parentNode` apuntaba a `.coltman-gallery-footer` (padre del botón), pero `.gallery-container` y `.gallery-item` son hermanos. Corregido con `e.closest('.coltman-gallery')` y búsqueda de items dentro del contenedor correcto.

---

## [1.13.0] — 2026-05-28

### Mejoras visuales — componente `gallery`
- **Rediseño completo del componente** (`input-fields.php`, `admin.css`, `media.js`) — layout tipo card por ítem en lugar de filas planas.
- **Miniatura en tiempo real** — `<img class="coltman-gallery-thumb">` de 60×60 con `object-fit:cover`. Muestra placeholder SVG cuando no hay imagen; se actualiza al escribir la URL manualmente o al seleccionar desde el media picker. El clonado de ítems limpia la miniatura correctamente.
- **Botón eliminar compacto** — icono ✕ en gris que pasa a rojo en hover; elimina los botones de texto grandes.
- **Footer separado** — zona `coltman-gallery-footer` con fondo `#f9f9f9` y separador visual que contiene el botón "Add image".
- **Sort placeholder** — azul punteado coherente con accordion y repeater.
- **Clases renombradas** — `div.gallery` → `div.coltman-gallery`; botones usan `.button` estándar de WP en lugar de clases Tailwind de color.
- Sin cambio en el formato JSON ni en la lógica de guardado.

---

## [1.12.0] — 2026-05-28

### Agregado — mejoras al componente `gallery`
- **Alt text editable** por ítem — `<input class="image-alt">` debajo del URL. Pre-rellena con `attachment.alt` del media picker (sin sobrescribir si el usuario ya escribió algo). Sincronización inmediata con el JSON oculto vía evento `input/change`.
- **Drag & drop para reordenar** — jQuery UI Sortable sobre `.gallery-sortable` con handle `.gallery-drag-handle`. Al soltar, reconstruye el array JSON en el nuevo orden leyendo `data-item` de cada ítem DOM. `addiTemImage()` llama `sortable('refresh')` al añadir un ítem.
- **Backward compat** — el campo `alt` ya existía en el JSON (`attachment.alt`); proyectos previos cargan sin cambio. Formato `gallery-data` idéntico.

### Mejoras al componente `wysiwyg` / `accordion_editor`
- **Estado activo en botones** — evento `selectionchange` + `document.queryCommandState()` añade `.is-active` a Bold / Italic / Underline / Strikethrough según el formato del cursor.
- **Selector de headings** — `<select class="coltman-wysiwyg-headings">` con opciones Normal / H2 / H3 / H4. Ejecuta `formatBlock` al cambiar; se sincroniza con el tag de bloque bajo el cursor.
- **Botón Strikethrough** añadido a la toolbar.
- **Normalizador de Enter** — `execCommand('defaultParagraphSeparator', false, 'p')` global + handler `keydown` que intercepta Enter fuera de listas y ejecuta `insertParagraph`, evitando que Chrome inserte `<div>`.
- **Sanitizador de paste** — handler `paste` que extrae el HTML del portapapeles, elimina `<style>`, `<script>`, elementos de namespace Office (`<o:p>`, `<w:*>`), comentarios condicionales de Word y atributos `style=` inline. Conserva `<div>`, `<table>`, `<br>` y todos los tags estructurales.

### Cambiado
- **`get_terms` — `multiple` por defecto** — `$multiple` pasa de `! empty($field['multiple'])` a `! (isset($field['multiple']) && !$field['multiple'])`. El campo es multiple a menos que se declare `'multiple' => false` explícitamente. Backward compat: valores previos guardados como string simple se pre-cargan correctamente vía fallback en el render.
- **`textarea` acepta HTML** — `sanitize_textarea_field()` reemplazado por `wp_kses_post()` en los 8 puntos de guardado (`save_post`, group sub-fields, repeater sub-fields en metabox y termeta; `save_user_meta`). Permite `<p>`, `<strong>`, `<table>`, `<div>`, etc. El render con `esc_textarea()` no cambia.

### Tests
- Stubs añadidos: `esc_html_e()`, `esc_attr__()`, `wp_kses_post()`.
- Tests actualizados para nuevas aserciones de comportamiento.
- **253 tests / 464 assertions** — todos pasan.

---

## [1.11.1] — 2026-05-28

### Corregido — vulnerabilidades de seguridad (XSS + CSRF)

#### XSS — salida sin escapar en `input-fields.php`
- **`input()`** — `$value`, `$field['id']`, `$field['class']`, `$field['pattern']` ahora con `esc_attr()`. Atributos `pattern`, `min`, `max`, `step` cambiados de comillas simples a dobles (`esc_attr()`).
- **`input_minmax()`** — `$value` y todos los atributos numéricos con `esc_attr()`.
- **`textarea()`** — `$value` con `esc_textarea()`; `placeholder` e `id`/`name` con `esc_attr()`.
- **`checkbox()`** — `$field['id']` con `esc_attr()`; `$field['description']` con `esc_html()`.
- **`gallery_input()`** — `$item->item`, `$item->url`, `$modal_button`, `$modal_title`, `$return`, `$text_button` con `esc_attr()` / `esc_html()`. `json_encode($value)` con `esc_attr()`.

#### XSS — salida sin escapar en `class-metabox.php`
- **`label()`** — `$field['label']` con `esc_html()`; `$field['id']` con `esc_attr()` en los tres casos del switch.
- **`description()`** — `$field['description']` con `esc_html()`.
- **Config description** en el header del metabox con `esc_html()`.

#### Datos sin sanitizar en `class-metabox.php`
- **`case 'checkbox':` en `save_post()`** — valor raw reemplazado por `sanitize_text_field()`.

#### CSRF — falta de nonce en `class-usermetabox.php`
- `add_user_meta_section()` ahora emite `wp_nonce_field('coltman_user_meta_save_{user_id}', 'coltman_user_meta_nonce')`.
- `save_user_meta()` verifica el nonce con `wp_verify_nonce()` antes de cualquier guardado. Se eliminó el comentario "opcional pero recomendado" que era incorrecto.

### Tests
- Stubs añadidos: `wp_unslash()`, `wp_json_encode()`, `register_post_meta()`, `get_current_screen()`, `wp_localize_script()`.
- `UserMetaTest::setUp()` inyecta el nonce en `$_POST` para que todos los tests existentes sigan pasando.
- Tests de `input_minmax` actualizados a comillas dobles (`min="0"`, `max="100"`, `step="0.5"`).

---

## [1.11.0] — 2026-05-28

### Agregado — Fase 4: soporte para el editor de bloques (Gutenberg)

#### 4.1 — Registro de meta en la REST API (`show_in_rest`)
- **`register_rest_meta(): void`** en `ColtmanCreateMetabox` — nuevo método público llamado en el hook `init`. Itera los campos del metabox y llama a `register_post_meta()` para cada campo que tenga `'rest' => true`. Parámetros registrados: `show_in_rest: true`, `single: true`, tipo REST mapeado por `rest_field_type()`, `auth_callback` con `current_user_can('edit_posts')`.
- **`rest_field_type(string $type): string`** — privado. Devuelve `'number'` para campos `number`, `'string'` para todos los demás.
- **Hook `init`** añadido al constructor junto a `enqueue_block_editor_assets`.

  **Uso:**
  ```php
  ['id' => 'seo_title', 'type' => 'text', 'label' => 'SEO Title', 'rest' => true]
  ```

#### 4.2 — Panel lateral en Gutenberg (`PluginDocumentSettingPanel`)
- **`enqueue_gutenberg_panel(): void`** en `ColtmanCreateMetabox` — hook `enqueue_block_editor_assets`. Solo actúa si la pantalla actual coincide con uno de los CPTs del metabox y hay al menos un campo `rest: true`. Encola `assets/js/gutenberg-panel.js` con dependencias `wp-plugins`, `wp-edit-post`, `wp-element`, `wp-components`, `wp-data`. Pasa las definiciones de campos y el título del panel via `wp_localize_script` como `window.coltmanGutenbergData`.
- **`assets/js/gutenberg-panel.js`** — componente `ColtmanPanel` registrado con `wp.plugins.registerPlugin`. Usa `wp.data.useSelect` para leer meta del store `core/editor` y `useDispatch.editPost` para escribir. Tipos soportados con control interactivo: `text`, `email`, `url`, `number`, `date`, `color`, `textarea`, `select`, `checkbox`. Tipos complejos (`gallery`, `accordion`, `repeater`, `relationship`, `get_posts`, `get_terms`, `group`, `map`) se muestran como `wp.components.Notice` informativo — se editan en el metabox clásico y son accesibles desde la REST API.
- **Sin proceso de build** — usa `wp.element.createElement` directamente, compatible con cualquier tema clásico sin webpack/Babel.

### Compatibilidad con classic themes
Ambas funcionalidades son **completamente aditivas**: en un classic theme sin el editor de bloques activo, los hooks se registran pero no tienen efecto visible. Los metaboxes clásicos siguen funcionando exactamente igual.

### Tests
- **4 nuevos en `CreateMetaboxTest`** — registro de campos `rest: true`, mapeo de tipo `number`, omisión cuando no hay campos REST, verificación de `show_in_rest: true` y `single: true`.
- Stubs añadidos: `register_post_meta()`, `get_current_screen()`, `wp_localize_script()`.
- **Total: 250 tests / 457 assertions** — todos pasan.

---

## [1.10.0] — 2026-05-28

### Agregado — campo `map` (selector de coordenadas con Leaflet)

- **`ColtmanInputFields::map()`** (`input-fields.php`) — renderiza un `<input type="hidden">` (guarda el JSON final), dos inputs readonly de lat/lng, botón "Clear" y un `<div.coltman-map-container>` con atributos `data-field`, `data-lat`, `data-lng`, `data-zoom`. Parámetros de configuración: `provider` (actualmente solo `leaflet`), `zoom` (nivel por defecto cuando no hay valor almacenado).
- **Leaflet 1.9.4 local** — `assets/libs/leaflet/leaflet.min.css` (11 KB), `leaflet.min.js` (145 KB) y las tres imágenes del marcador en `assets/libs/leaflet/images/`. Sin dependencia de CDN externo.
- **`case 'map':` en `field()` y `save_post()`** (`class-metabox.php`) — renderizado delegado a `ColtmanInputFields::map()`; guardado con `wp_json_encode` tras validar que `lat ∈ [-90, 90]` y `lng ∈ [-180, 180]`. Si el campo está vacío se limpia la meta key.
- **`case 'map':` en `wpturbo_render_input_field()` y `wpturbo_save_meta_fields()`** (`class-termeta.php`) — misma lógica para term-meta.
- **Enqueue en `admin_enqueue_scripts()`** (metabox y termeta) — `wp_enqueue_style('leaflet')`, `wp_enqueue_script('leaflet')` y `wp_localize_script('coltman-media', 'coltmanVars', ['assetsUrl' => COLTMAN_ASSETS_URL])`.
- **`coltmanLeafletIcon()` y `coltmanInitMap()`** (`media.js`) — inicialización completa del mapa: OpenStreetMap tiles, marcador draggable, click-para-colocar, sincronización de lat/lng/zoom al `<input hidden>`, botón Clear. La función `coltmanLeafletIcon()` elimina `L.Icon.Default.prototype._getIconUrl` y crea un `L.icon({...})` explícito con rutas absolutas desde `coltmanVars.assetsUrl` para evitar que Leaflet intente auto-detectar las rutas desde el CSS (causa raíz de los iconos rotos).
- **CSS** (`admin.css`) — sección "Map": `.coltman-map-wrap`, `.coltman-map-coords`, `.coltman-map-coord-label`, `.coltman-map-container` (350 px de altura).
- **Stubs** (`tests/Stubs/wordpress.php`) — añadidos `wp_unslash()` y `wp_json_encode()`.

### Modelo de datos
Guarda `{"lat": 40.4168, "lng": -3.7038, "zoom": 12}` en una sola meta key. Lectura en frontend: `json_decode(get_post_meta($id, 'campo', true), true)`.

### Tests
- **6 nuevos en `InputFieldsTest`** — hidden input con valor, container con data-attributes, valores vacíos, inputs readonly, botón Clear, zoom por defecto desde config.
- **3 nuevos en `CreateMetaboxTest`** — save coords válidas, rechazo de coordenadas fuera de rango, limpieza con cadena vacía.
- **Total: 246 tests / 448 assertions** — todos pasan.

---

## [1.9.0] — 2026-05-28

### Agregado — campo `group` con constructor de campos dinámicos

- **`get_group_schema()` en `class-metabox.php` y `class-termeta.php`** — método privado que lee el esquema de sub-campos dinámicos desde `wp_options` bajo la clave `_coltman_group_schema_{group_id}`. Devuelve `[]` si no existe o si el valor no es un array.
- **Panel "Manage fields"** en el footer de cada grupo — al pie del `div.coltman-group-body` aparece un botón ⚙ que abre un panel colapsable con:
  - Lista de campos dinámicos actuales (tipo · etiqueta `clave`) con botón ✕ por cada uno.
  - Formulario "Add field": selector de tipo (`text`, `textarea`, `number`, `email`, `url`), input de clave (solo `[a-z0-9_]`) e input de etiqueta.
- **AJAX `coltman_add_group_field`** (`ajax.php`) — añade un campo al esquema en `wp_options`. Valida nonce (`coltman_group_schema`), capacidad `manage_options`, tipos permitidos y unicidad de clave.
- **AJAX `coltman_remove_group_field`** (`ajax.php`) — elimina un campo del esquema por clave. Mismo nonce y capacidad.
- **JS en `media.js`** — tres handlers delegados en `body`: toggle del panel, añadir campo (POST AJAX + inyección de fila sin recargar), eliminar campo (confirmación + POST AJAX + limpieza DOM). Helper `coltmanBuildFieldRow(type, key, label)` genera el HTML del nuevo input.
- **CSS en `admin.css`** — sección "Group Field Manager" con `.coltman-field-manager`, `.coltman-field-manager-panel`, `.coltman-dynamic-field-item`, `.coltman-add-field-form`, `.coltman-add-dynamic-field`, `.coltman-field-manager-note`, etc.
- **Stubs `get_option` / `update_option`** en `tests/Stubs/wordpress.php` para tests unitarios sin WP.

### Cambiado
- **`save_post()` / `wpturbo_save_meta_fields()` case `'group'`** — fusionan `$field['fields']` (estáticos, prioridad por clave) + esquema dinámico de `wp_options` antes de iterar y guardar. Los valores siguen en meta keys individuales.
- **`group_field()` / `wpturbo_render_group()`** — renderizan campos dinámicos con `data-dynamic-key` después de los estáticos, seguidos del panel `.coltman-field-manager` al final del cuerpo del grupo.

### Modelo de datos
- **Esquema** (definición de campos) → `wp_options` `_coltman_group_schema_{id}`, global para todos los posts/términos con ese grupo.
- **Valores** → meta keys individuales, igual que los campos estáticos.

### Tests
- **237 tests / 423 assertions** — todos pasan.

---

## [1.8.0] — 2026-05-27

### Cambiado — campo `group` (visual mejorado)
- **`group_field()` en `class-metabox.php`** — rediseñado como una **única `<tr>`**: el título del grupo + botón de colapso queda en la columna `<th>` (izquierda), y todos los sub-campos apilados en la `<td>` (derecha). Antes cada sub-campo generaba su propio `<tr>`, lo que mezclaba los campos del grupo con los campos normales del metabox.
- **`wpturbo_render_group()` en `class-termeta.php`** — mismo rediseño para term-meta: cabecera con label + toggle, cuerpo colapsable con sub-campos apilados dentro de un `div.coltman-group-body`.
- **`admin.css`** — estilos de grupo actualizados para la nueva disposición: `.coltman-group-header` (flex, borde azul inferior), `.coltman-group-label`, `.coltman-group-toggle` (hover azul), `.coltman-group-body` (flex column, gap), `.coltman-group-field-row`.
- **`media.js`** — toggle de colapso actualizado para usar `#coltman-group-{id}` como target (el `div.coltman-group-body` dentro de la `<td>`).
- Sub-campos siguen guardándose en sus propias meta keys individuales (sin cambio de modelo de datos).
- **`media` en `repeater_sub_field()`** — tipo `media` añadido al switch de sub-campos, disponible en `repeater` y como sub-campo de `group`. Renderiza `<input type="text">` + botón `.rwp-media-toggle`.

### También incluido en v1.8.0
- **`get_posts` delega en `relationship`** — one-liner; mantiene API pública y hereda AJAX, paginación, multi-`post_type`.
- **Multi-`post_type` en `relationship`/`get_posts`** — acepta string, CSV o array.
- **`get_terms` mejorado** — Select2 AJAX (`coltman_term_search`), `multiple`, multi-taxonomy, paginación.
- **Campo `wysiwyg` eliminado** — usar `type: 'editor'` directamente.

### Tests
- **237 tests / 423 assertions** — todos pasan sin cambios en la suite (los cambios son de renderizado PHP/CSS/JS, sin impacto en el modelo de datos).

---

## [1.7.0] — 2026-05-27

### Agregado
- **Campo `repeater`** (`ColtmanInputFields::repeater()`) — sub-campos configurables por fila (JSON en post meta). Tipos de sub-campo soportados: `text`, `textarea`, `select`, `checkbox`, `color`, `email`, `url`. Filas drag-and-drop (jQuery UI Sortable). JS `addRepeaterRow()` / `removeRepeaterRow()` en `media.js`. Estilos `.repeater-drag-handle` y `.repeater-sort-placeholder` en `admin.css`. Sanitización por tipo de sub-campo en `save_post()` y `wpturbo_save_meta_fields()` (Fase 3.1).
- **Campo `color`** (`ColtmanInputFields::color()`) — `<input type="text">` con clase `coltman-color-picker` e inicialización automática de `wp-color-picker` en `$(document).ready`. Atributo `data-default-color` configurable en la definición del campo. Sanitización con `sanitize_text_field()` (Fase 3.2).
- **Campo `relationship`** (`ColtmanInputFields::relationship()`) — `<select multiple>` con búsqueda AJAX en tiempo real vía Select2. Nonce `coltman_relationship` embebido en `data-nonce`. Handler AJAX `wp_ajax_coltman_relationship_search` en `ajax.php` (nuevo archivo). Devuelve resultados paginados (20 por página). Pre-popula opciones seleccionadas desde JSON al renderizar. Sanitización: `json_encode` del array de IDs enteros (Fase 3.3).
- **Campo `wysiwyg`** (`ColtmanInputFields::wysiwyg()`) — alias de `ColtmanInputFields::editor()`. Compatibilidad con desarrolladores que vienen de ACF (Fase 3.4).
- **`classes/ajax.php`** — archivo nuevo para handlers AJAX del framework. Requerido desde `class.php`. Actualmente contiene `coltman_relationship_search`.
- **`admin.css`** ampliado con estilos del repeater: `.repeater-drag-handle`, `.repeater-drag-handle:hover`, `.repeater-drag-handle:active`, `.repeater-sort-placeholder`.

### Cambiado
- **`media.js`** — añadidos: inicialización de Select2 AJAX para `.js-relationship-select`, inicialización de `wp-color-picker` para `.coltman-color-picker`, Sortable para `.coltman-repeater .repeater-rows` con renumeración de índices al reordenar.
- **`class-metabox.php` / `class-termeta.php`** — `field()` / `wpturbo_render_input_field()` y los métodos `save_post()` / `wpturbo_save_meta_fields()` ampliados con los 4 nuevos tipos (`wysiwyg`, `color`, `relationship`, `repeater`).
- **`class.php`** — añadido `require __DIR__ . '/ajax.php'` en la lista de requires.

### Tests
- **232 tests / 409 assertions** — todos pasan.
- `InputFieldsTest`: 14 nuevos tests (2 `wysiwyg`, 3 `color`, 6 `repeater`, 4 `relationship`).
- `tests/Stubs/wordpress.php`: añadida función `esc_attr_e()`.

---

## [1.6.0] — 2026-05-27

### Agregado
- **`classes/assets/css/admin.css`** — Todos los estilos utilitarios (clases Tailwind, WYSIWYG, acordeón, WP Editor) extraídos a un archivo CSS externo encolado con `wp_enqueue_style('coltman-admin', ...)`. Elimina el bloque `<style>` inline de `admin_head()` en `ColtmanCreateMetabox` y `ColtmanTermMeta`, y el `<style>` inline en `fields_table()` (Fase 6.2).
- **Select2 v4.0.13 local** — Archivos `select2.min.css` y `select2.min.js` en `assets/libs/select2/`. El registro pasa de CDN externo (`cdnjs.cloudflare.com/3.4.8`) a rutas locales vía `COLTMAN_ASSETS_URL`. Funciona sin conexión a internet y con cualquier política de Content Security Policy (Fase 6.1).
- **Tipos PHP 8 en `ColtmanRegisterTaxonomy`** — Propiedades `$labels`, `$taxonomy_name`, `$post_types`, `$args`, `$capabilities` declaradas con tipos explícitos (`array`, `string`). Antes solo tenían PHPDoc `@var`; ahora el motor de PHP las valida en runtime (Fase 6.3).

### Cambiado
- **`namespace Coltman\Framework`** en `class.php` — Reemplaza `namespace Class\AddicClinicDirectory` (legacy de la clínica). El namespace solo aplica a `class.php`; las clases incluidas con `require` están en el namespace global y no se ven afectadas (Fase 5.2).
- **`admin_head()` vaciado** en `ColtmanCreateMetabox` y `ColtmanTermMeta` — El método se mantiene registrado en el hook (retrocompatibilidad de tests) pero ya no emite CSS; los estilos se cargan desde `admin.css`.
- **Versión de `coltman-media`** en los enqueues: `'1.0.0'` → `'1.6.0'` para forzar invalidación de caché en navegadores que tenían cacheada la versión anterior.

### Tests
- **217 tests / 369 assertions** — todos pasan sin modificaciones (los cambios son CSS/namespace/tipos, sin impacto en la lógica testeada).

---

## [1.5.0] — 2026-05-26

### Agregado
- **Editor WYSIWYG propio en el campo `accordion`** (`ColtmanInputFields::accordion_editor()`): nuevo método privado que renderiza un `<div contenteditable>` con barra de herramientas (negrita, cursiva, subrayado, listas, enlace, limpiar) y un `<textarea hidden>` sincronizado en tiempo real vía JS. Elimina la dependencia de `wp_editor()`/TinyMCE en el acordeón — sin problemas de clonado, sin reinicialización.
- **Select2 transparente para `get_posts` y `get_terms`**: `ColtmanInputFields` agrega internamente la clase `js-select2` y los atributos `data-placeholder` / `data-allow-clear`. El inicializador en `media.js` detecta esa clase y activa Select2. La API de definición de campos no cambia.
- **Drag-and-drop para reordenar ítems del acordeón**: cada ítem muestra un handle de arrastre (icono grip). Implementado con jQuery UI Sortable (incluido en WP admin). El orden se persiste automáticamente al enviar el formulario — el handler `submit` reconstruye el JSON desde el orden DOM actual.
- **CSS de `.coltman-wysiwyg`** en `admin_head()` de `ColtmanCreateMetabox` y `ColtmanTermMeta`: estilos para la barra de herramientas, botones, separadores, área editable y placeholder de reordenación.
- **Metabox de prueba completo** en `include/features/joyeria/metaboxes.php` (`prefix: coltman_test_`): cubre todos los tipos de campo implementados con sus opciones documentadas y tipos sugeridos comentados.

### Corregido
- **`COLTMAN_ASSETS_URL` con URL rota en temas** (`class.php`): `plugins_url('assets', __FILE__)` generaba una URL incorrecta al estar en un tema (`wp-content/plugins/…/var/www/…`). Reemplazado por el cálculo de ruta relativa a `WP_CONTENT_DIR` con `content_url()`.
- **`media.js` no se cargaba** (`class-metabox.php`, `class-termeta.php`): el script se emitía como `<script defer src="…">` en `admin_head()`, sin pasar por la cola de WordPress. Movido a `wp_enqueue_script()` en `admin_enqueue_scripts()` con dependencias `['jquery', 'select2', 'jquery-ui-sortable']`.
- **Select2 sin dependencia declarada**: `coltman-media` dependía solo de `['jquery']`; Select2 podía cargarse después. Corregido registrando Select2 antes de `coltman-media` y añadiéndolo como dependencia.
- **Select2 init fuera de `$(document).ready`**: la inicialización de `.js-select2` se ejecutaba antes de que el DOM estuviera disponible. Movida dentro del handler `ready`.
- **`saveAccordeonItem` = guardar + clonar** (`media.js`): la separación entre "Save" y "Add Row" rompía el flujo esperado (clic en Save → nuevo ítem vacío). Restaurado: "Save" llama a `saveAccordeonItem()` que guarda el ítem y clona uno vacío; "Add Row" solo clona.
- **Clonado del segundo ítem del acordeón** (TinyMCE): `cloneNode(true)` copiaba el HTML de TinyMCE con los IDs del ítem original, haciendo que `wp.editor.initialize()` fallara en el segundo ítem. Resuelto reemplazando TinyMCE por el editor `contenteditable` propio.

### Cambios internos
- `ColtmanInputFields::accordion()`: eliminado el bucle de pre-procesamiento `str_replace`, llamadas a `wp_editor()` reemplazadas por `accordion_editor()`, botón "Save" cambia a `saveAccordeonItem(this)`.
- `media.js` — acordeón completamente reescrito para el enfoque `contenteditable`:
  - `cloneElement()`: actualiza `data-sync`, `id`/`name` del textarea oculto y `data-for` del wrapper — sin reinicialización TinyMCE.
  - `removeAccordeonItem()`: limpia `innerHTML` del contenteditable en lugar de llamar `wp.editor.remove()`.
  - `saveAccordeonItemData()`: lee directamente del textarea oculto (auto-sincronizado), sin detección de TinyMCE.
  - Handler `document.submit`: sincroniza todos los editores WYSIWYG y reconstruye el JSON de cada acordeón desde el DOM antes de enviar.
  - Eliminados: `coltmanEditorSettings`, `coltmanSyncTinyMce`, `coltmanGetAccordionContent`.

### Tests
- **217 tests / 369 assertions** — todos pasan sin modificaciones en la suite (los cambios son JS/CSS/PHP de render, sin impacto en PHPUnit).

---

## [1.4.0] — 2026-05-26

### Agregado
- **`ColtmanTermMeta` delega en `ColtmanInputFields`** (ISSUE-04): `wpturbo_render_input_field()` reescrito con `ob_start()`/`ob_get_clean()` para capturar el output de los métodos echo de `ColtmanInputFields`. Todos los tipos (`text`, `textarea`, `email`, `select`, `media`, `gallery`, `editor`, `accordion`, `number`, `date`) ahora usan el renderizado centralizado.
- **`ColtmanTermMeta::$coltmanInputs`** — propiedad pública para facilitar inspección en tests.
- **Normalización de `id` en campos de `ColtmanTermMeta`** (ISSUE-05): el constructor inyecta `field['id']` desde la clave del array si no está declarado explícitamente.
- **`require` de `optimizations.php` en `utils.php`** (ISSUE-06): `utils/optimizations/optimizations.php` se cargaba solo si el tema lo requería manualmente. Ahora se auto-carga desde `utils/utils.php`.

### Corregido
- **`save_post()` sin seguridad** (ISSUE-02 / ISSUE-08): añadido nonce (`wp_nonce_field` en `fields_table()`, verificación con `wp_verify_nonce` en `save_post()`), `current_user_can('edit_post', $post_id)` y guard `DOING_AUTOSAVE`. Sanitización completa por tipo de campo: `sanitize_text_field`, `sanitize_textarea_field`, `sanitize_email`, `wp_filter_post_kses`, `esc_url_raw`.
- **`wpturbo_save_meta_fields()` sin permisos ni sanitización** (ISSUE-03): añadida verificación `current_user_can('manage_categories')` y sanitización idéntica a `save_post()` por tipo de campo.
- **`var_dump($config)` en producción** (ISSUE-01): eliminado del constructor de `ColtmanCreateMetabox`.
- **CSS duplicado con múltiples instancias de metabox/termeta** (ISSUE-10): `admin_head()` usa variables estáticas (`static $printed = false`) en `ColtmanCreateMetabox` y `ColtmanTermMeta` para emitir los estilos solo una vez.
- **`$have_image` lógica invertida en `input-fields.php`** (ISSUE-07): condición reescrita como `!(isset($field['add_image']) && ($field['add_image'] === false || $field['add_image'] === 'false'))` eliminando el doble negativo ambiguo.
- **`console.log` en producción en `media.js`** (ISSUE-09): todas las llamadas a `console.log` reemplazadas por `coltmanLog()`, controlado por la constante `COLTMAN_DEBUG = false`.
- **`use Class\AddicClinicDirectory` en `class-termeta.php`** (ISSUE-13): eliminado el `use` de namespace obsoleto que causaba un warning de clase no encontrada.

### Tests
- Suite actualizada a **217 tests / 369 assertions**.
- `tests/Stubs/wordpress.php`: añadidos `wp_create_nonce`, `wp_verify_nonce`, `wp_nonce_field` y flag `wp_verify_nonce`.
- `CreateMetaboxTest`: añadidos 5 nuevos tests (3 de seguridad en `save_post`, 2 de sanitización); todos los tests existentes de `save_post` actualizados para incluir nonce.
- `TermMetaTest`: añadidos 4 nuevos tests (`coltmanInputs`, normalización de `id`, permisos en save, sanitización de texto/textarea); tests de render actualizados para el output de `ColtmanInputFields` (comillas dobles, textos de botón media).

---

## [1.3.1] — 2026-05-25

### Corregido
- **`break` faltante en `save_post()` tras el case `textarea`** (`class-metabox.php` línea 287): el fall-through al `default` hacía que `update_post_meta` se ejecutara dos veces por cada campo `textarea`, guardando el valor correctamente pero generando trabajo duplicado innecesario.

---

## [1.3.0] — 2026-05-22

### Agregado
- **Sistema de pruebas unitarias completo** (PHPUnit ^10).
  - `composer.json` con dependencia `phpunit/phpunit ^10`.
  - `phpunit.xml` — configuración del runner con cobertura declarada.
  - `tests/bootstrap.php` — carga constantes, clases y funciones WP stub antes de los archivos fuente.
  - `tests/Stubs/wordpress.php` — implementaciones stub de todas las funciones WP usadas por el framework, con sistema de spy (`_coltman_spy`) y overrides por test (`_coltman_stubs`, `_coltman_flags`).
  - `tests/TestCase.php` — clase base con helpers `spyCalls()`, `firstCall()`, `assertCalled()`, `getProperty()`, `callMethod()`, `capture()`.
  - **207 tests / 357 assertions** cubriendo todas las clases y utilidades:
    - `tests/Unit/RegisterPostTest.php` — 18 tests para `ColtmanRegisterPost`.
    - `tests/Unit/RegisterTaxonomyTest.php` — 19 tests para `ColtmanRegisterTaxonomy`.
    - `tests/Unit/CreateMetaboxTest.php` — 24 tests para `ColtmanCreateMetabox`.
    - `tests/Unit/InputFieldsTest.php` — 40 tests para `ColtmanInputFields`.
    - `tests/Unit/TermMetaTest.php` — 26 tests para `ColtmanTermMeta`.
    - `tests/Unit/UserMetaTest.php` — 27 tests para `ColtmanCreateUserMeta`.
    - `tests/Unit/Utils/UtilsTest.php` — 9 tests para `coltman_trim_content_text_fn` y `formaturltext`.
    - `tests/Unit/Utils/ReadTimeTest.php` — 13 tests para `get_estimated_reading_time`.
    - `tests/Unit/Utils/NavigationsAnchorsTest.php` — 21 tests para `process_headings_and_get_data`, `filter_content_inject_headings` y `get_extracted_headings_array`.

### Corregido
- **Bug de tipo en `read-time.php`:** `ceil()` devuelve `float` en PHP 8+. La comparación estricta `$reading_time === 1` siempre era `false` (comparando `1.0 === 1`), haciendo que el sufijo singular nunca se usara. Corregido con cast `(int) ceil(...)`.

---

## [1.2.0] — 2026-05-22

### Agregado
- **Sistema de traducción completo** (`COLTMAN_TEXT_DOMAIN = 'coltman'`).
  - Carpeta `languages/` con plantilla `.pot` y traducciones para `es_ES` y `nl_NL`.
  - Archivos `.mo` compilados y listos para producción.
  - Carga automática del textdomain en el hook `init` (prioridad 1).
- **Idioma holandés** (`coltman-nl_NL.po` / `coltman-nl_NL.mo`).

### Corregido
- **Fatal error de namespace en `coltman_load_textdomain`:** la función con nombre definida dentro del namespace `Class\AddicClinicDirectory` era invisible para WordPress al resolver el callback del hook `init`. Reemplazada por una closure estática (`static function() { ... }`) que no pertenece a ningún namespace.
- **3 strings sin envolver en `__()`** en `input-fields.php`: `"Don't have posts available"`, `"Select a term"`, `"Don't have terms available"`.
- **String hardcodeado en español** `'Elige una imagen'` en `class-termeta.php` → reemplazado por `'Choose an image'` con el dominio correcto.
- **3 text domains incorrectos** unificados bajo `COLTMAN_TEXT_DOMAIN`:
  - `'advanced-options'` → `COLTMAN_TEXT_DOMAIN` (6 ocurrencias en `input-fields.php`, 3 en `class-termeta.php`)
  - `'addic-clinic-directory'` → `COLTMAN_TEXT_DOMAIN` (7 ocurrencias en `input-fields.php`)
  - `'udh'` → `COLTMAN_TEXT_DOMAIN` (1 ocurrencia en `input-fields.php`)

---

## [1.1.0] — 2026-05-22

### Agregado
- **`COLTMAN_CONTEXT`** — constante auto-detectada que indica el contexto de carga: `'theme'`, `'plugin'`, `'mu-plugin'` o `'unknown'`. Se calcula comparando `__FILE__` contra los directorios de WordPress usando `wp_normalize_path()` y `str_starts_with()`. No requiere configuración manual.
- **`COLTMAN_DIR`** — constante con la ruta absoluta del filesystem a la carpeta `classes/`.
- **`COLTMAN_ASSETS_URL`** — constante con la URL absoluta a `classes/assets/`, calculada automáticamente con `plugins_url('assets', __FILE__)`. Funciona en temas, plugins y mu-plugins sin distinción. Sobreescribible con `define()` antes del `require` (CDN, rutas no estándar).
- Enqueue de `media.js` activado en `ColtmanCreateUserMeta::admin_enqueue_scripts()`, que estaba comentado y apuntaba a una ruta obsoleta.
- Carpeta `docs/` con tres documentos:
  - `docs/roadmap.md` — cambios planificados en 6 fases.
  - `docs/mejoras.md` — ideas de evolución del framework.
  - `docs/issues-y-soluciones.md` — 14 issues catalogados con solución concreta.
- `context.md` — arquitectura técnica completa del módulo.
- `readme.md` — guía de uso completa con snippets listos para usar por clase y tipo de campo.

### Cambiado
- **`WORK_CONTEXT` eliminado** — se definía manualmente como `'theme'` pero nunca se leía en ningún archivo. Reemplazado por `COLTMAN_CONTEXT` (auto-detectado) y `COLTMAN_ASSETS_URL` (calculado).
- Rutas de assets en `class-metabox.php` y `class-termeta.php` migradas de `get_stylesheet_directory_uri() . '/classes/assets/...'` a `COLTMAN_ASSETS_URL . '/...'`.

### Corregido
- `esc_url()` agregado al output de la URL de `media.js` en `class-metabox.php` y `class-termeta.php`.

---

## [1.0.0] — inicial

### Estado inicial del módulo

- `ColtmanRegisterPost` — registro declarativo de Custom Post Types con labels auto-generados.
- `ColtmanRegisterTaxonomy` — registro declarativo de taxonomías con capacidades estándar.
- `ColtmanCreateMetabox` — metaboxes con campos dinámicos: `text`, `textarea`, `number`, `date`, `email`, `checkbox`, `select`, `editor`, `media`, `gallery`, `get_posts`, `get_terms`, `accordion`.
- `ColtmanInputFields` — renderizado centralizado de todos los tipos de campo.
- `ColtmanTermMeta` — campos personalizados en términos de taxonomía.
- `ColtmanCreateUserMeta` — campos personalizados en perfiles de usuario.
- `utils/read-time.php` — `get_estimated_reading_time()`.
- `utils/navigations_archors.php` — inyección de IDs en headings y `get_extracted_headings_array()`.
- `utils/optimizations/remove_scripts.php` — eliminación de jQuery Migrate.
- `assets/js/media.js` — media picker, galería y acordeón para el admin.

### Deuda técnica conocida en esta versión
- `WORK_CONTEXT` definida pero nunca usada.
- Rutas de assets hardcodeadas a `get_stylesheet_directory_uri()` — no funciona en plugins.
- 3 text domains distintos e incorrectos (`advanced-options`, `addic-clinic-directory`, `udh`).
- 3 strings sin envolver en `__()`.
- 1 string hardcodeado en español.
- `save_post()` sin nonce ni `current_user_can()`.
- `ColtmanTermMeta` no usa `ColtmanInputFields` — renderizado propio con soporte reducido.
- `media.js` en `ColtmanCreateUserMeta` comentado e inactivo.
