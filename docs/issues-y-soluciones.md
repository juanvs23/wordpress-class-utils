# Issues conocidos y soluciones — Framework Coltman

Lista exhaustiva de problemas identificados en el código actual, su impacto real y la solución concreta a aplicar.

---

## CRÍTICOS — Afectan seguridad

### ISSUE-01: `save_post` sin verificación de nonce

**Clase:** `ColtmanCreateMetabox::save_post()`  
**Archivo:** `class-metabox.php` — línea 241  
**Impacto:** Cualquier request POST a `wp-admin/post.php` puede modificar el meta de un post sin que el usuario lo haya iniciado desde el formulario (CSRF).

**Solución:**

1. Agregar nonce en `add_meta_box_callback()`:
```php
wp_nonce_field('coltman_metabox_' . $this->config['prefix'], 'coltman_nonce');
```

2. Verificar al inicio de `save_post()`:
```php
public function save_post($post_id) {
    if (!isset($_POST['coltman_nonce'])) return;
    if (!wp_verify_nonce($_POST['coltman_nonce'], 'coltman_metabox_' . $this->config['prefix'])) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    // … resto del guardado
}
```

---

### ISSUE-02: `save_post` sin sanitización en `textarea` y campos `default`

**Clase:** `ColtmanCreateMetabox::save_post()`  
**Archivo:** `class-metabox.php` — líneas 264-273  
**Estado parcial:** El `break` faltante tras el case `textarea` fue corregido en v1.3.1 — ya no se llama `update_post_meta` dos veces. La sanitización sigue pendiente.  
**Impacto:** Un campo `textarea` o `text` puede guardar HTML arbitrario o scripts si el usuario editor tiene intenciones maliciosas o si el campo es manipulado.

**Solución:** Agregar casos explícitos en el switch:

```php
case 'textarea':
    update_post_meta($post_id, $field['id'], sanitize_textarea_field($_POST[$field['id']]));
    break;
case 'url':
    update_post_meta($post_id, $field['id'], esc_url_raw($_POST[$field['id']]));
    break;
case 'number':
    update_post_meta($post_id, $field['id'], intval($_POST[$field['id']]));
    break;
default:
    update_post_meta($post_id, $field['id'], sanitize_text_field($_POST[$field['id']]));
```

---

### ISSUE-03: `ColtmanTermMeta::wpturbo_save_meta_fields` sin verificación de permisos

**Clase:** `ColtmanTermMeta`  
**Archivo:** `class-termeta.php` — línea 318  
**Impacto:** Cualquier usuario con acceso al admin puede modificar term meta si conoce el endpoint.

**Solución:**

```php
public function wpturbo_save_meta_fields(int $term_id): void {
    if (!current_user_can('manage_categories')) return;
    if (!isset($_POST['coltman_term_nonce_' . $this->config['taxonomy']])) return;
    if (!wp_verify_nonce($_POST['coltman_term_nonce_' . $this->config['taxonomy']], 'coltman_term_' . $term_id)) return;
    // … resto
}
```

---

## MODERADOS — Afectan funcionamiento

### ISSUE-04: `ColtmanTermMeta` no usa `ColtmanInputFields`

**Archivo:** `class-termeta.php` — método `wpturbo_render_input_field()` (línea 269)  
**Impacto:** Los tipos `gallery`, `accordion`, `get_posts`, `get_terms` y `editor` no funcionan en campos de término — se renderizan como un `<input type="gallery">` inútil.

**Solución:** Instanciar `ColtmanInputFields` en el constructor e igualar el switch al de `ColtmanCreateMetabox::field()`:

```php
public function __construct(array $config) {
    $this->coltmanInputs = class_exists('ColtmanInputFields') ? new ColtmanInputFields() : false;
    // … hooks
}

public function wpturbo_render_input_field(string $field_id, array $field, string $field_value): string {
    ob_start();
    // El campo necesita 'id' como propiedad para ColtmanInputFields
    $field['id'] = $field_id;
    switch ($field['type']) {
        case 'media':    $this->coltmanInputs->media($field, $field_value);        break;
        case 'gallery':  $this->coltmanInputs->gallery_input($field, $field_value); break;
        case 'editor':   $this->coltmanInputs->editor($field, $field_value);        break;
        case 'select':   $this->coltmanInputs->select($field, $field_value);        break;
        case 'textarea': $this->coltmanInputs->textarea($field, $field_value);      break;
        default:         $this->coltmanInputs->input($field, $field_value);
    }
    return ob_get_clean();
}
```

---

### ISSUE-05: Inconsistencia en la definición de campos entre clases

**Clases:** `ColtmanCreateMetabox` vs `ColtmanTermMeta`  
**Problema:**  
```php
// ColtmanCreateMetabox — 'id' es una propiedad dentro del array
'fields' => [
    ['id' => 'mi_campo', 'type' => 'text', 'label' => '…']
]

// ColtmanTermMeta — la clave del array ES el id
'fields' => [
    'mi_campo' => ['type' => 'text', 'label' => '…']
]
```
**Impacto:** Obliga a recordar la convención de cada clase. Un campo definido para `ColtmanCreateMetabox` no puede reutilizarse directamente en `ColtmanTermMeta` y viceversa.

**Solución:** Migrar `ColtmanTermMeta` para aceptar el formato de `ColtmanCreateMetabox`. En el constructor, normalizar el array:

```php
foreach ($config['fields'] as $key => $field) {
    if (!isset($field['id'])) {
        $config['fields'][$key]['id'] = $key;
    }
}
```

---

### ISSUE-06: `optimizations/` no se carga automáticamente

**Archivo:** `utils/utils.php`  
**Problema:** `optimizations/optimizations.php` existe pero `utils.php` no lo requiere. La función `adctn_remove_jquery_migrate()` definida en `remove_scripts.php` nunca se ejecuta a menos que se haga un `require` manual.  
**Impacto:** jQuery Migrate sigue cargándose en el frontend aunque esté el archivo de optimización presente.

**Solución:** Agregar al final de `utils/utils.php`:

```php
require __DIR__ . '/optimizations/optimizations.php';
```

O bien, cargar `optimizations.php` directamente desde `class.php` si se quiere control granular.

---

### ISSUE-07: `add_image => 'false'` debe ser string, no bool

**Clase:** `ColtmanInputFields::accordion()`  
**Archivo:** `input-fields.php` — línea 279  
**Problema:**
```php
$have_image = !isset($field['add_image']) || $field['add_image'] != 'false' ? true : false;
```
Si se pasa `'add_image' => false` (bool), la comparación `false != 'false'` es `true`, por lo que **se muestran las imágenes** aunque la intención era ocultarlas.

**Solución inmediata (sin cambiar la clase):** Siempre usar el string `'false'`:
```php
['id' => 'rc_faq', 'type' => 'accordion', 'add_image' => 'false']
```

**Solución definitiva (en la clase):** Normalizar la comparación:
```php
$have_image = !(isset($field['add_image']) && ($field['add_image'] === false || $field['add_image'] === 'false'));
```

---

## MENORES — Calidad de código

### ISSUE-08: `var_dump` comentado en producción

**Archivo:** `class-metabox.php` — línea 55  
**Código:**
```php
// var_dump($config);
```
**Solución:** Eliminar la línea.

---

### ISSUE-09: `console.log` en `media.js` en producción

**Archivo:** `assets/js/media.js` — líneas 23, 67, 72, 116, 146, 163, 189…  
**Impacto:** Contamina la consola del navegador en producción.  
**Solución:** Envolverlos en una bandera de debug o eliminarlos:

```js
const COLTMAN_DEBUG = false;
const coltmanLog = (...args) => COLTMAN_DEBUG && console.log(...args);
```

---

### ISSUE-10: CSS Tailwind duplicado en `admin_head()`

**Clases:** `ColtmanCreateMetabox::admin_head()` y `ColtmanTermMeta::admin_head()`  
**Problema:** Ambas clases inyectan el mismo bloque `<style>` inline. Si hay dos metaboxes en la misma página, el CSS se duplica en el HTML.  
**Impacto:** Carga innecesaria de CSS repetido.  
**Solución:** Usar una bandera estática para encolarlo solo una vez:

```php
public function admin_head() {
    static $coltman_styles_printed = false;
    if ($coltman_styles_printed) return;
    $coltman_styles_printed = true;
    // … <style> …
}
```

O moverlo a `classes/assets/css/admin.css` y encolarlo con `wp_enqueue_style()`.

---

### ISSUE-11: Select2 cargado desde CDN externo en versión obsoleta

**Archivo:** `class-metabox.php` — líneas 98-101  
**Problema:** Se carga Select2 `3.4.8` desde CDNJS. Problemas:
1. Dependencia de CDN externo (falla sin internet, privacidad GDPR).
2. La versión 3.x es obsoleta — la API de v4 es distinta.
3. El comentario en `media.js` (línea 34) muestra que el init de Select2 está comentado, lo que sugiere que posiblemente no funcione correctamente.

**Solución:**
1. Descargar Select2 v4 y guardarlo en `assets/libs/select2/`.
2. Registrarlo como archivo local en `admin_enqueue_scripts()`.
3. Activar el init en `media.js`: `$('.get_posts').select2({placeholder: 'Selecciona…'})`.

---

### ISSUE-12: Namespace incorrecto

**Archivo:** `class.php` — línea 2  
**Código:** `namespace Class\AddicClinicDirectory;`  
**Problema:** El namespace hace referencia al proyecto original (clínica de adicciones). No afecta el funcionamiento porque ninguna clase usa `use` para importar desde ese namespace, pero es confuso para cualquier desarrollador que lea el código.

**Solución:** Cambiar a `namespace Coltman\Framework;` y actualizar la única referencia externa: `use Class\AddicClinicDirectory;` en `class-termeta.php` línea 25 (que además es una declaración `use` inútil ya que no importa ninguna clase concreta).

---

### ISSUE-13: `use Class\AddicClinicDirectory` inútil en `class-termeta.php`

**Archivo:** `class-termeta.php` — línea 25  
**Código:** `use Class\AddicClinicDirectory;`  
**Problema:** Este `use` importa el namespace pero no importa ninguna clase concreta. No tiene efecto funcional y genera confusión.  
**Solución:** Eliminar la línea.

---

### ISSUE-14: Typo en parámetro `with_descrtiption`

**Función:** `anillosdepedida_lightBoxCarousel()` — `include/functions/lightBoxCarousel.php`  
**Problema:** El parámetro tiene una errata (`descrtiption` en vez de `description`). No está dentro de `classes/` pero el error se origina en el mismo patrón de desarrollo.  
**Impacto:** Si se intenta usar `'with_description'` (correcto), no funciona.  
**Solución:** Corregir el nombre del parámetro y actualizar todos los callsites donde se use `'with_descrtiption'`.  
**Precaución:** Buscar con `grep -r "with_descrtiption"` antes de cambiar para no romper usos existentes.

---

## Resumen por prioridad

| ID | Prioridad | Tipo | Estado |
|---|---|---|---|
| ISSUE-01 | 🔴 Crítico | Seguridad | Pendiente |
| ISSUE-02 | 🔴 Crítico | Seguridad | 🟡 Parcial — `break` resuelto (v1.3.1), sanitización pendiente |
| ISSUE-03 | 🔴 Crítico | Seguridad | Pendiente |
| ISSUE-04 | 🟠 Moderado | Funcionalidad | Pendiente |
| ISSUE-05 | 🟠 Moderado | API inconsistente | Pendiente |
| ISSUE-06 | 🟠 Moderado | Bug silencioso | Pendiente |
| ISSUE-07 | 🟠 Moderado | Bug de tipo | Pendiente |
| ISSUE-08 | 🟡 Menor | Limpieza | Pendiente |
| ISSUE-09 | 🟡 Menor | Limpieza | Pendiente |
| ISSUE-10 | 🟡 Menor | Performance | Pendiente |
| ISSUE-11 | 🟡 Menor | Dependencia externa | Pendiente |
| ISSUE-12 | 🟡 Menor | Claridad | Pendiente |
| ISSUE-13 | 🟡 Menor | Código muerto | Pendiente |
| ISSUE-14 | 🟡 Menor | Typo | Pendiente |
