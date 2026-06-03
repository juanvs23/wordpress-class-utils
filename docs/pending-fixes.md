# Bugs pendientes — revisión 2026-06-01

Hallazgos identificados tras revisión directa del código fuente.
Todos corregidos en **v1.14.1**.

---

## FIX-01 — `editor()`: `textarea_rows` siempre es `true/false`

**Archivo:** `input-fields.php` — línea 44  
**Severidad:** Media — bug funcional silencioso

```php
// ROTO: isset() devuelve bool, no el valor
'textarea_rows' => isset( $field['rows'] ) ? isset( $field['rows'] ) : 20,

// CORRECTO
'textarea_rows' => isset( $field['rows'] ) ? $field['rows'] : 20,
```

Cualquier campo `editor` con `'rows' => N` siempre renderiza con `textarea_rows = true` (1 fila).
La configuración `rows` nunca tuvo efecto.

---

## FIX-02 — `accordion()`: atributos HTML sin `esc_attr()`

**Archivo:** `input-fields.php` — líneas 468 y 479  
**Severidad:** Baja — el HTML puede romperse si el JSON guardado contiene comillas simples fuera del escape `'`

```php
// ROTO
name="<?php echo $field['id']; ?>"
id="<?php echo $field['id']; ?>"
value='<?php echo json_encode( $value ); ?>'

data-id="<?php echo $field['id']; ?>"
id="<?php echo $id; ?>"

// CORRECTO — todos los atributos pasan por esc_attr()
```

---

## FIX-03 — `add_meta_boxes()`: usa `'cpt'` string en vez de `'post-type'` array

**Archivo:** `class-metabox.php` — método `add_meta_boxes()`  
**Severidad:** Alta — metabox invisible cuando se registran múltiples CPTs

```php
// ROTO: pasa el string CSV original a add_meta_box()
add_meta_box( ..., $this->config['cpt'], ... );

// CORRECTO: usa el array normalizado por process_cpts()
add_meta_box( ..., $this->config['post-type'], ... );
```

`process_cpts()` ya convierte el CSV a array en `$this->config['post-type']`.
WordPress ≥ 4.4 acepta array en el parámetro `$screen`. Con un solo CPT funcionaba por casualidad (el string coincidía).

---

## FIX-04 — `save_post()` campo `map`: sin null-check tras `json_decode()`

**Archivo:** `class-metabox.php` — case `map` en `save_post()`  
**Severidad:** Media — PHP 8 deprecation warning con datos corruptos

```php
// ROTO: si json_decode devuelve null, isset(null['lat']) → warning PHP 8
$raw = json_decode( wp_unslash( $_POST[$field['id']] ), true );
$lat = isset( $raw['lat'] ) ? (float) $raw['lat'] : null;

// CORRECTO: salir si el JSON es inválido
$raw = json_decode( wp_unslash( $_POST[$field['id']] ), true );
if ( ! is_array( $raw ) ) break;
$lat = isset( $raw['lat'] ) ? (float) $raw['lat'] : null;
```

---

## FIX-05 — `save_post()` campos `get_posts`/`relationship`: sin `is_array()`

**Archivo:** `class-metabox.php` — cases `get_posts` y `relationship` en `save_post()`  
**Severidad:** Baja — guarda JSON malformado en edge cases (un solo valor sin `[]`)

```php
// ROTO: json_encode("string") → '"string"' en vez de '["string"]'
json_encode( $_POST[$field['id']] )

// CORRECTO (igual que en el case 'group')
is_array( $_POST[$field['id']] ) ? json_encode( $_POST[$field['id']] ) : '[]'
```

---

## FIX-06 — `group_field()`: `description` sin `esc_html()`

**Archivo:** `class-metabox.php` — método `group_field()`  
**Severidad:** Baja — inconsistencia con el resto del método

```php
// ROTO
echo $field['description'];

// CORRECTO
echo esc_html( $field['description'] );
```

---

## FIX-07 — `class-usermetabox.php`: Select2 v3.4.8 CDN incompatible con media.js

**Archivo:** `class-usermetabox.php` — método `admin_enqueue_scripts()`  
**Severidad:** Alta — campos `get_terms` y `get_posts` en user meta completamente rotos

`media.js` usa la API de Select2 v4 (`ajax: { url, dataType, processResults }`).
La v3 usa `initSelection` + `query` — API distinta. Además carga desde CDN externo sin SRI.

```php
// ROTO
wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.css', ... );
wp_register_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.js', ... );

// CORRECTO — igual que class-metabox.php y class-termeta.php
wp_register_style( 'select2css', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.css', false, '4.0.13', 'all' );
wp_register_script( 'select2', COLTMAN_ASSETS_URL . '/libs/select2/select2.min.js', [ 'jquery' ], '4.0.13', true );
```

---

## FIX-08 — `class-usermetabox.php`: enqueues faltantes en `admin_enqueue_scripts()`

**Archivo:** `class-usermetabox.php` — método `admin_enqueue_scripts()`  
**Severidad:** Alta — JS error que rompe todo el admin en la página de perfil

Faltan tres cosas que `class-metabox.php` y `class-termeta.php` sí hacen:

1. **`coltman-admin` CSS** — estilos del admin no aplican en el perfil de usuario.
2. **`leaflet` CSS + JS** — campos `map` en user meta no renderizan.
3. **`wp_localize_script('coltman-media', 'coltmanVars', [...])`** — `media.js` accede a `coltmanVars.assetsUrl` en la inicialización del mapa. Sin esto, `coltmanVars is not defined` rompe todos los scripts del admin.
4. **`jquery-ui-sortable`** como dependencia de `coltman-media` — gallery/list/repeater/accordion no son reordenables.

---

## FIX-09 — `class-usermetabox.php`: tipos `relationship`, `color`, `repeater`, `map` no implementados

**Archivo:** `class-usermetabox.php` — métodos `render_field()` y `save_user_meta()`  
**Severidad:** Media — esos tipos caen a `<input type="text">` en el perfil de usuario

En `render_field()` faltan los cases:
- `relationship` → `$this->coltmanInputs->relationship()`
- `color` → `$this->coltmanInputs->color()`
- `repeater` → `$this->coltmanInputs->repeater()`
- `map` → `$this->coltmanInputs->map()`

En `save_user_meta()` faltan los cases:
- `relationship` → `is_array($value) ? json_encode($value) : '[]'`
- `color` → `sanitize_text_field($value)`
- `repeater` — serialización JSON por filas (igual que `class-metabox.php`)
- `map` — validación lat/lng/zoom (igual que `class-metabox.php`)
- `get_terms` — `json_encode` (multiple) o `sanitize_text_field` (single)

---

## Estado

| ID | Archivo | Descripción | Estado |
|---|---|---|---|
| FIX-01 | `input-fields.php:44` | `editor()` rows bug | ✅ Resuelto en v1.14.1 |
| FIX-02 | `input-fields.php:468,479` | `accordion()` esc_attr faltante | ✅ Resuelto en v1.14.1 |
| FIX-03 | `class-metabox.php` | `add_meta_boxes()` multi-CPT roto | ✅ Resuelto en v1.14.1 |
| FIX-04 | `class-metabox.php` | `save_post()` map null-check | ✅ Resuelto en v1.14.1 |
| FIX-05 | `class-metabox.php` | `save_post()` get_posts is_array | ✅ Resuelto en v1.14.1 |
| FIX-06 | `class-metabox.php` | `group_field()` description sin esc_html | ✅ Resuelto en v1.14.1 |
| FIX-07 | `class-usermetabox.php` | Select2 v3 CDN → v4 local | ✅ Resuelto en v1.14.1 |
| FIX-08 | `class-usermetabox.php` | Enqueues faltantes | ✅ Resuelto en v1.14.1 |
| FIX-09 | `class-usermetabox.php` | Tipos no implementados | ✅ Resuelto en v1.14.1 |
