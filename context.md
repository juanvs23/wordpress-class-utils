# Contexto: carpeta `classes/`

> **Versión actual: 1.15.2** — 2026-06-01  
> Suite: **256 tests / 470 assertions** (`php vendor/bin/phpunit`)  
> Ver `CHANGELOG.md` para el historial completo, `docs/roadmap.md` para el estado de cada fase.

---

## Propósito del módulo

El framework **Coltman** es un reemplazo liviano de **Advanced Custom Fields (ACF)** sin dependencias externas ni licencias Pro. Cubre el 95 % de los casos de uso de ACF:

- **Registrar Custom Post Types** con todos sus labels generados automáticamente.
- **Registrar taxonomías personalizadas** con el mismo enfoque declarativo.
- **Metaboxes con campos personalizados** — 18 tipos de campo, incluyendo repeater, relationship, map, group con constructor dinámico y soporte Gutenberg.
- **Campos en términos de taxonomía** y **campos en perfiles de usuario**.
- **Soporte REST API y Gutenberg** — campos con `'rest' => true` se exponen en la REST API y aparecen en el panel lateral del editor de bloques.

El módulo vive como **sub-repositorio git independiente**, se copia entre proyectos y no requiere ninguna configuración adicional.

---

## Reglas de mantenimiento

### 1. CHANGELOG.md — siempre al día

Ante cualquier cambio, crear entrada `## [X.Y.Z] — YYYY-MM-DD` con [Semantic Versioning](https://semver.org/lang/es/):

- **PATCH** — bugfix, fix de seguridad.
- **MINOR** — nueva funcionalidad compatible hacia atrás.
- **MAJOR** — cambio que rompe compatibilidad (renombrado de clase, cambio de constructor).

### 2. context.md — sincronizado con el CHANGELOG

**Ante cualquier cambio en `CHANGELOG.md`**, revisar y actualizar este archivo:

- Versión en la cabecera (`**Versión actual: X.Y.Z**`).
- Conteo de tests si cambia la suite.
- Estructura de archivos si se añaden o eliminan archivos.
- Tabla de tipos de campo si se añade o modifica un tipo.
- Hooks, sanitización o cualquier otra sección que describa el comportamiento modificado.
- Deuda técnica / mejoras pendientes si se cierra o abre un issue.

### 3. readme.md — sincronizado con la API pública

Actualizar cuando cambie: tipo de campo, firma de constructor, constante, nueva clase o comportamiento de guardado.

---

## Estructura de archivos

```
classes/
├── class.php                         ← Loader principal + constantes del framework
├── class-post-types.php              ← ColtmanRegisterPost
├── class-taxonomy.php                ← ColtmanRegisterTaxonomy
├── class-metabox.php                 ← ColtmanCreateMetabox  [CRLF — editar con Python]
├── class-termeta.php                 ← ColtmanTermMeta        [CRLF — editar con Python]
├── class-usermetabox.php             ← ColtmanCreateUserMeta
├── input-fields.php                  ← ColtmanInputFields (renderizado de todos los campos)
├── ajax.php                          ← Handlers wp_ajax_* del framework
├── composer.json                     ← dev: phpunit/phpunit ^10
├── phpunit.xml                       ← Configuración PHPUnit 10
├── readme.md                         ← Guía de uso pública
├── context.md                        ← Este archivo
├── CHANGELOG.md                      ← Historial (Semantic Versioning)
├── assets/
│   ├── css/
│   │   └── admin.css                 ← Estilos utilitarios del admin (encolado). Secciones: utilidades, WYSIWYG, accordion, group, map, field-manager, gallery, list, media, repeater
│   ├── js/
│   │   ├── media.js                  ← JS del admin: todos los campos interactivos
│   │   └── gutenberg-panel.js        ← Panel lateral Gutenberg (sin build step)
│   └── libs/
│       ├── select2/                  ← Select2 v4.0.13 (local, sin CDN)
│       │   ├── select2.min.css
│       │   └── select2.min.js
│       └── leaflet/                  ← Leaflet v1.9.4 (local, sin CDN)
│           ├── leaflet.min.css
│           ├── leaflet.min.js
│           └── images/               ← marker-icon.png, marker-icon-2x.png, marker-shadow.png
├── docs/
│   ├── roadmap.md                    ← 6 fases + Gutenberg — todas completadas
│   ├── mejoras.md                    ← Ideas de evolución (API declarativa, validación…)
│   └── issues-y-soluciones.md        ← Issues catalogados con solución concreta
├── languages/
│   ├── coltman.pot
│   ├── coltman-es_ES.po / .mo
│   └── coltman-nl_NL.po / .mo
├── tests/
│   ├── bootstrap.php
│   ├── TestCase.php                  ← Helpers: spy, stub, capture, reflection
│   ├── Stubs/
│   │   └── wordpress.php             ← Stubs de todas las funciones WP usadas
│   └── Unit/
│       ├── RegisterPostTest.php      ← 18 tests
│       ├── RegisterTaxonomyTest.php  ← 19 tests
│       ├── CreateMetaboxTest.php     ← 34 tests
│       ├── InputFieldsTest.php       ← 65 tests
│       ├── TermMetaTest.php          ← 26 tests
│       ├── UserMetaTest.php          ← 27 tests
│       └── Utils/
│           ├── UtilsTest.php              ← 9 tests
│           ├── ReadTimeTest.php           ← 13 tests
│           └── NavigationsAnchorsTest.php ← 21 tests
└── utils/
    ├── utils.php
    ├── read-time.php
    ├── navigations_archors.php
    └── optimizations/
        ├── optimizations.php
        └── remove_scripts.php
```

> **CRLF:** `class-metabox.php`, `class-termeta.php`, `class.php`, `class-taxonomy.php` usan terminaciones de línea Windows (CRLF). Editar siempre con `python3` usando `open(f,'rb')`/`open(f,'wb')` — nunca con el Edit tool de Claude directamente.

> `optimizations/` no se carga automáticamente desde `utils.php`. Solo se activa con `require` explícito.

---

## `class.php` — Loader y constantes

### Constantes definidas

| Constante | Valor | Descripción |
|---|---|---|
| `COLTMAN_DIR` | `__DIR__` | Ruta filesystem a `classes/` |
| `COLTMAN_CONTEXT` | `'theme'` \| `'plugin'` \| `'mu-plugin'` \| `'unknown'` | Auto-detectado |
| `COLTMAN_ASSETS_URL` | URL absoluta | `content_url()` relativo a `WP_CONTENT_DIR`. Sobreescribible con `define()` antes del `require` |
| `COLTMAN_TEXT_DOMAIN` | `'coltman'` | Text domain de todas las cadenas internas |

### Orden de require

`input-fields.php` → `class-post-types.php` → `class-taxonomy.php` → `class-metabox.php` → `class-termeta.php` → `ajax.php` → `utils/utils.php` → `class-usermetabox.php`

### Namespace

`namespace Coltman\Framework` — aplica solo a `class.php`. Las clases incluidas con `require` están en el namespace global.

---

## Requisitos mínimos

| Componente | Mínimo | Motivo |
|---|---|---|
| PHP | 8.0 | Union types, `str_starts_with()`, match expression |
| WordPress | 5.0 | `determine_locale()`, `register_post_meta()`, `wp_normalize_path()` |
| ext-iconv | — | `formaturltext()` |

---

## `ColtmanInputFields` — `input-fields.php`

Renderizado centralizado de todos los tipos de campo. Usado por `ColtmanCreateMetabox`, `ColtmanTermMeta` y `ColtmanCreateUserMeta`.

### Tipos de campo soportados

| Método | Tipo | Almacenamiento | Notas |
|---|---|---|---|
| `input()` | `text`, `email`, `url`, `date`, `color`… | string | fallback general |
| `input_minmax()` | `number`, `date` | string | atributos `min`/`max`/`step` |
| `textarea()` | `textarea` | string (HTML) | guardado con `wp_kses_post()` — acepta HTML estructural |
| `editor()` | `editor` | string (HTML) | `wp_editor()` |
| `checkbox()` | `checkbox` | `'on'` \| `''` | |
| `select()` | `select` | string | array `['value'=>…,'label'=>…]` |
| `media()` | `media` | URL o ID | Tarjeta con thumbnail 64×64, input URL readonly, input alt text, botón Upload + Clear. Alt guardado en `field_id_alt`. |
| `gallery_input()` | `gallery` | JSON array | `[{id,url,alt,sizes,title,…}]` — alt editable, drag-and-drop, miniatura en tiempo real |
| `list_input()` | `list` | JSON array | `[{item,text}]` — textarea por ítem, drag-and-drop, add/remove |
| `accordion()` | `accordion` | JSON array | `[{id,title,content,image}]` — WYSIWYG con headings, paste limpio, estado activo |
| `repeater()` | `repeater` | JSON array | filas configurables, drag-and-drop |
| `color()` | `color` | `#rrggbb` | `wp-color-picker` |
| `relationship()` | `relationship` | JSON array de IDs | Select2 AJAX, paginación, multi-post_type |
| `get_posts()` | `get_posts` | JSON array de IDs | delega en `relationship()` — API pública mantenida |
| `get_terms()` | `get_terms` | JSON array \| string | Select2 AJAX, multi-taxonomy — **multiple por defecto**; desactivar con `'multiple' => false` |
| `map()` | `map` | `{"lat":…,"lng":…,"zoom":…}` | Leaflet 1.9.4, marcador draggable, click-to-place |
| `repeater_sub_field()` | — | — | Renderizado de sub-campos dentro de repeater/group |

> `wysiwyg` fue eliminado en v1.8.0. Usar `editor` directamente.

### Campo `group`

No tiene método propio en `ColtmanInputFields`. Lo renderiza directamente `ColtmanCreateMetabox::group_field()` y `ColtmanTermMeta::wpturbo_render_group()`.

- Layout: `<th>` label+toggle / `<td>` sub-campos apilados.
- Sub-campos estáticos (config PHP) + dinámicos (schema en `wp_options`).
- Panel ⚙ "Manage fields": añadir/quitar campos sin tocar código.
- Almacenamiento: meta keys individuales por sub-campo.
- Schema key: `_coltman_group_schema_{group_id}` en `wp_options`.

---

## `ColtmanCreateMetabox` — `class-metabox.php`

### Constructor

```php
new ColtmanCreateMetabox([
    'title'       => 'Datos SEO',
    'prefix'      => 'seo_',
    'cpt'         => 'post,page',          // string CSV o array en 'post-type'
    'class_name'  => 'seo',
    'context'     => 'normal',             // 'normal' | 'side' | 'advanced'
    'priority'    => 'high',
    'fields'      => [
        ['id' => 'seo_title', 'type' => 'text',   'label' => 'Título', 'rest' => true],
        ['id' => 'seo_image', 'type' => 'media',  'label' => 'Imagen OG'],
        ['id' => 'ubicacion', 'type' => 'map',    'label' => 'Ubicación', 'zoom' => 12],
        [
            'id'     => 'grupo_seo',
            'type'   => 'group',
            'label'  => 'SEO Avanzado',
            'fields' => [
                ['id' => 'meta_desc', 'type' => 'textarea', 'label' => 'Meta description'],
            ],
        ],
    ],
]);
```

### Hooks registrados

| Hook | Método | Descripción |
|---|---|---|
| `add_meta_boxes` | `add_meta_boxes()` | Registra el metabox |
| `admin_enqueue_scripts` | `admin_enqueue_scripts()` | Select2, color picker, media, Leaflet, `coltmanVars` |
| `admin_head` | `admin_head()` | (vacío — CSS encolado vía `admin_enqueue_scripts`) |
| `save_post` | `save_post($post_id)` | Guarda campos con verificación de nonce y permisos |
| `init` | `register_rest_meta()` | Registra meta con `show_in_rest` para campos con `'rest' => true` |
| `enqueue_block_editor_assets` | `enqueue_gutenberg_panel()` | Encola panel Gutenberg si hay campos REST |

### Assets encolados

- `select2` v4.0.13 (local), `wp-color-picker`, `jquery-ui-sortable`
- `leaflet` v1.9.4 (local)
- `coltman-admin` CSS (`assets/css/admin.css`)
- `coltman-media` JS (`assets/js/media.js`)
- `wp_localize_script('coltman-media', 'coltmanVars', ['assetsUrl' => COLTMAN_ASSETS_URL])`

### Sanitización en `save_post()`

| Tipo | Sanitización |
|---|---|
| `text`, `color`, `date` | `sanitize_text_field()` |
| `textarea` | `wp_kses_post()` — permite HTML estructural (`<p>`, `<div>`, `<table>`, `<strong>`…) |
| `email` | `sanitize_email()` |
| `url`, `media` | `esc_url_raw()` |
| `editor` | `wp_filter_post_kses()` |
| `number` | `sanitize_text_field()` |
| `checkbox` | `sanitize_text_field()` |
| `gallery`, `accordion`, `list` | raw (JSON generado en JS) |
| `repeater` | sanitización por tipo de sub-campo |
| `relationship`, `get_posts` | `json_encode(array)` |
| `get_terms` | `json_encode` (multiple — defecto) o `sanitize_text_field` (`multiple:false`) |
| `map` | `wp_json_encode({lat,lng,zoom})` — valida rango ±90/±180 |
| `group` | itera sub-campos (estáticos + dinámicos) con sanitización por tipo |

### Soporte REST / Gutenberg

Campos con `'rest' => true`:
- Se registran con `register_post_meta()` en el hook `init`.
- Aparecen en el panel "Manage fields" del editor de bloques (`gutenberg-panel.js`).
- Tipos simples (text, textarea, select, checkbox, number, email, url) tienen control interactivo.
- Tipos complejos (gallery, repeater, relationship, group, map) muestran un aviso — se editan en el metabox clásico.

---

## `ColtmanTermMeta` — `class-termeta.php`

Campos personalizados en términos de taxonomía. Usa `ColtmanInputFields` para el renderizado — soporta todos los mismos tipos que `ColtmanCreateMetabox`.

### Constructor

```php
new ColtmanTermMeta([
    'taxonomy' => 'tipo_de_joyeria',
    'title'    => 'Información adicional',
    'fields'   => [
        ['id' => 'color_hex',  'type' => 'color',  'label' => 'Color'],
        ['id' => 'imagen',     'type' => 'media',  'label' => 'Imagen'],
        ['id' => 'posts_rel',  'type' => 'relationship', 'label' => 'Posts relacionados'],
    ],
]);
```

### Hooks registrados

| Hook | Método |
|---|---|
| `{taxonomy}_add_form_fields` | `wpturbo_render_meta_fields()` |
| `{taxonomy}_edit_form_fields` | `wpturbo_edit_meta_fields()` |
| `created_{taxonomy}` / `edited_{taxonomy}` | `wpturbo_save_meta_fields()` |
| `admin_enqueue_scripts` | `admin_enqueue_scripts()` — mismos assets que metabox |

### Sanitización en `wpturbo_save_meta_fields()`

Misma lógica que `ColtmanCreateMetabox::save_post()`. Usa `update_term_meta()`.

---

## `ColtmanCreateUserMeta` — `class-usermetabox.php`

Campos personalizados en el perfil de usuario. La clase con sanitización más completa del framework.

### Hooks registrados

`show_user_profile`, `edit_user_profile`, `personal_options_update`, `edit_user_profile_update`, `admin_enqueue_scripts` (solo en `profile.php` y `user-edit.php`).

### Sanitización en `save_user_meta()`

Verifica `current_user_can('edit_user', $user_id)`. Sanitiza por tipo: `json_encode`, `wp_filter_post_kses`, `sanitize_email`, `sanitize_textarea_field`, `esc_url_raw`, `sanitize_text_field`.

---

## `ajax.php` — Handlers AJAX

Requerido desde `class.php`. Todos usan `wp_ajax_*` (solo usuarios autenticados).

| Action | Nonce | Capacidad | Descripción |
|---|---|---|---|
| `coltman_relationship_search` | `coltman_relationship` | `edit_posts` | Búsqueda AJAX de posts para Select2. Params: `post_type` (string/CSV), `q`, `page`. |
| `coltman_term_search` | `coltman_term_search` | `edit_posts` | Búsqueda AJAX de términos. Params: `taxonomy` (string/CSV), `q`, `page`. Paginación N+1. |
| `coltman_add_group_field` | `coltman_group_schema` | `manage_options` | Añade un campo al schema dinámico del grupo en `wp_options`. |
| `coltman_remove_group_field` | `coltman_group_schema` | `manage_options` | Elimina un campo del schema dinámico del grupo. |

---

## `assets/js/media.js`

Script del admin para todos los campos interactivos. Se encola en `admin_enqueue_scripts` de metabox y termeta.

### Secciones principales

| Sección | Descripción |
|---|---|
| Group toggle | Colapsa/expande `div.coltman-group-body` |
| Group field manager | Panel ⚙ add/remove de campos dinámicos vía AJAX |
| Media picker | Abre `wp.media()` para campos `media`; rellena `.image-alt` y miniatura en gallery |
| Gallery sortable | jQuery UI Sortable en `.gallery-sortable`; stop reordena JSON |
| Gallery alt sync | Evento `input/change` en `.image-alt` → actualiza JSON |
| Gallery thumbnail | Evento `input/change` en `.image-url` → actualiza `<img>` en tiempo real |
| List sortable | jQuery UI Sortable en `.list-sortable`; stop reordena JSON |
| List textarea sync | Evento `input/change` en `.list-textarea` → actualiza JSON |
| Accordion sortable | jQuery UI Sortable en `.accordion-items` |
| Select2 init | `.js-select2` (get_terms/get_posts básico) |
| Relationship select | `.js-relationship-select` — Select2 AJAX con `coltman_relationship_search` |
| Term select | `.js-term-select` — Select2 AJAX con `coltman_term_search` |
| Color picker | `$('.coltman-color-picker').wpColorPicker()` |
| Map field | `coltmanInitMap()` — Leaflet, marcador draggable, sync JSON |
| Repeater sortable | jQuery UI Sortable en `.coltman-repeater .repeater-rows` |

### WYSIWYG (`coltman-wysiwyg` / `accordion_editor`)

| Función / evento | Descripción |
|---|---|
| `syncWysiwyg(body)` | Copia `innerHTML` del contenteditable al `<textarea>` oculto |
| `updateWysiwygToolbar(body)` | Actualiza botones activos (`queryCommandState`) y select de headings |
| `selectionchange` | Llama `updateWysiwygToolbar` cuando el foco está en un editor |
| `.coltman-wysiwyg-btn` click | Ejecuta `execCommand` o `createLink`; llama `updateWysiwygToolbar` |
| `.coltman-wysiwyg-headings` change | `execCommand('formatBlock')` con H2/H3/H4/p |
| `keydown` Enter | `insertParagraph` (nunca `<div>`); respeta `<li>` y Shift+Enter |
| `paste` | Extrae HTML del portapapeles, limpia basura de Word/Office (namespace, `style=`), conserva `<div>`, `<table>`, `<br>` |
| `.is-active` | Clase CSS en botones Bold/Italic/Underline/Strikethrough según estado del cursor |

### Helpers globales

| Función | Descripción |
|---|---|
| `coltmanBuildFieldRow(type, key, label)` | HTML de fila de campo dinámico de grupo |
| `coltmanLeafletIcon()` | `L.icon({...})` con rutas absolutas; elimina `_getIconUrl` |
| `coltmanInitMap(el)` | Leaflet completo en `div.coltman-map-container` |
| `addiTemImage(e)` | Añade ítem a gallery; limpia URL, alt y miniatura |
| `addiTemList(e)` | Añade ítem a list; clona el primero y limpia textarea |
| `removeiTem(e)` | Elimina ítem de gallery; filtra JSON por `data-item` |
| `removeListItem(e)` | Elimina ítem de list; filtra JSON por `data-item` |
| `normalizeQuotes(str)` | `'` → `'`, `"` → `"` |
| `escapeForPhpJson(str)` | Escapa `\`, `'`, `"` |
| `sanitizeForJSON(value)` | Escapa comillas y caracteres HTML |
| `addRepeaterRow(btn)` | Añade fila al repeater |
| `removeRepeaterRow(btn)` | Elimina fila del repeater |
| `addAccordeonItem(e)` | Añade ítem al acordeón |
| `removeAccordeonItem(e)` | Elimina ítem del acordeón |

---

## `assets/js/gutenberg-panel.js`

Panel `PluginDocumentSettingPanel` para el editor de bloques. Sin proceso de build — usa `wp.element.createElement` directamente.

- Solo se encola cuando el CPT actual tiene al menos un campo con `'rest' => true`.
- Datos inyectados vía `wp_localize_script` como `window.coltmanGutenbergData.fields[]`.
- Lee meta del store `core/editor` con `useSelect` y escribe con `useDispatch.editPost`.
- Tipos interactivos: `text`, `email`, `url`, `number`, `textarea`, `select`, `checkbox`.
- Tipos complejos (`gallery`, `accordion`, `repeater`, `relationship`, `get_posts`, `get_terms`, `group`, `map`): muestra `wp.components.Notice` informativo.

---

## `assets/css/admin.css`

Clases utilitarias y estilos de componentes. Encolado como `coltman-admin`.

| Sección | Clases principales |
|---|---|
| Utilidades | `.flex`, `.gap-*`, `.w-full`, `.bg-slate-100`, `.rounded`, etc. |
| WP Editor | `.wp-editor-container iframe` |
| WYSIWYG | `.coltman-wysiwyg-*` |
| Accordion | `.accordion-drag-handle`, `.accordion-sort-placeholder` |
| Group | `.coltman-group-header`, `.coltman-group-body`, `.coltman-group-field-row` |
| Group Field Manager | `.coltman-field-manager`, `.coltman-dynamic-field-item`, `.coltman-add-field-form` |
| Map | `.coltman-map-wrap`, `.coltman-map-container` (350px) |
| Repeater | `.repeater-drag-handle`, `.repeater-sort-placeholder` |

---

## `tests/` — Pruebas unitarias

Suite PHPUnit 10. No requiere WordPress instalado — usa stubs PHP puros en `tests/Stubs/wordpress.php`.

### Ejecutar

```bash
cd classes/
php vendor/bin/phpunit
# → OK (256 tests, 470 assertions)
```

### Cobertura de tests

| Suite | Clase | Tests |
|---|---|---|
| `RegisterPostTest` | `ColtmanRegisterPost` | 18 |
| `RegisterTaxonomyTest` | `ColtmanRegisterTaxonomy` | 19 |
| `CreateMetaboxTest` | `ColtmanCreateMetabox` | 36 |
| `InputFieldsTest` | `ColtmanInputFields` | 71 |
| `TermMetaTest` | `ColtmanTermMeta` | 26 |
| `UserMetaTest` | `ColtmanCreateUserMeta` | 29 |
| `UtilsTest` | `coltman_trim_content_text_fn`, `formaturltext` | 9 |
| `ReadTimeTest` | `get_estimated_reading_time` | 13 |
| `NavigationsAnchorsTest` | heading injection functions | 21 |
| **Total** | | **256 tests / 470 assertions** |

### Stubs disponibles en `tests/Stubs/wordpress.php`

`add_action`, `add_filter`, `register_post_type`, `register_taxonomy`, `add_meta_box`, `get_post_meta`, `update_post_meta`, `metadata_exists`, `get_term_meta`, `update_term_meta`, `sanitize_*`, `esc_*`, `esc_html_e`, `esc_attr__`, `wp_*`, `wp_kses_post`, `wp_unslash`, `wp_json_encode`, `current_user_can`, `is_admin`, `wp_editor`, `get_option`, `update_option`, `register_post_meta`, `get_current_screen`, `wp_localize_script`, `wp_nonce_field`, `wp_verify_nonce`, y más.

### Arquitectura del sistema de stubs

```php
$GLOBALS['_coltman_spy']   = [];   // registro de todas las llamadas WP
$GLOBALS['_coltman_stubs'] = [];   // retornos sobreescritos por test
$GLOBALS['_coltman_flags'] = [
    'is_admin'        => false,
    'metadata_exists' => false,
    'current_user_can'=> true,
];
$_coltman_options         = [];    // simula wp_options para get/update_option
$_coltman_registered_meta = [];    // captura llamadas a register_post_meta
```

`TestCase.php` expone: `setStub`, `setFlag`, `spyCalls`, `firstCall`, `assertCalled`, `assertNotCalled`, `capture`, `getProperty`, `callMethod`.

---

## Tabla comparativa de clases

| Clase | Qué registra | Contexto | Sanitización | REST/Gutenberg |
|---|---|---|---|---|
| `ColtmanRegisterPost` | CPT | `init` | N/A | N/A |
| `ColtmanRegisterTaxonomy` | Taxonomía | `init` | N/A | N/A |
| `ColtmanCreateMetabox` | Metabox de post | Admin + Gutenberg | Completa por tipo | ✅ `register_rest_meta()` + panel |
| `ColtmanTermMeta` | Campos de término | Admin | Completa por tipo | — |
| `ColtmanCreateUserMeta` | Campos de usuario | Admin | Completa por tipo | — |
| `ColtmanInputFields` | — (solo renderiza) | Admin | N/A | N/A |

---

## Leer valores en el frontend

```php
// Campo simple
$valor = get_post_meta($post->ID, 'campo_id', true);

// Relación / get_posts (JSON array de IDs)
$ids = json_decode(get_post_meta($post->ID, 'campo_id', true), true) ?: [];

// Mapa
$coords = json_decode(get_post_meta($post->ID, 'ubicacion', true), true);
// $coords['lat'], $coords['lng'], $coords['zoom']

// Repeater (JSON array de filas)
$rows = json_decode(get_post_meta($post->ID, 'campo_id', true), true) ?: [];
foreach ($rows as $row) { $row['sub_campo_id']; }

// Group (meta keys individuales)
$titulo = get_post_meta($post->ID, 'seo_title', true);
$desc   = get_post_meta($post->ID, 'meta_desc', true);

// REST API (campo con 'rest' => true)
// GET /wp-json/wp/v2/{post_type}/{id}  →  response.meta.campo_id
```

---

## Deuda técnica pendiente

No quedan problemas críticos conocidos. Las mejoras de largo plazo están documentadas en `docs/mejoras.md`:

- **API declarativa** — registrar CPT + taxonomy + metabox en un solo array.
- **Validación de campos** — clave `validate` con reglas (`required`, `min:0`, `email`…).
- **Campos condicionales** — mostrar/ocultar campo según el valor de otro (`data-condition`).
