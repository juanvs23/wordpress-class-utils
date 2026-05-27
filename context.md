# Contexto: carpeta `classes/`

## Propósito del módulo

El framework **Coltman** es un reemplazo liviano del plugin **Advanced Custom Fields (ACF)**. Su objetivo es eliminar la dependencia de plugins de terceros para las necesidades más comunes del desarrollo de temas y plugins WordPress:

- **Registrar Custom Post Types** con todos sus labels generados automáticamente — reemplaza la necesidad de código boilerplate repetitivo o de plugins como CPT UI.
- **Registrar taxonomías personalizadas** con el mismo enfoque declarativo.
- **Crear metaboxes con campos personalizados** (texto, galería, acordeón, medios, editor WYSIWYG, selects dinámicos…) — cubre el 90% de los casos de uso de ACF sin instalar ningún plugin.
- **Campos en términos de taxonomía** y **campos en perfiles de usuario** — áreas que ACF también cubre pero que aquí se manejan nativamente.

La ventaja frente a ACF es el control total del código, cero dependencias externas, sin licencias Pro y portabilidad entre proyectos copiando la carpeta. El módulo vive como **sub-repositorio git independiente** y se reutiliza en múltiples temas y plugins del mismo autor.

> Ver `docs/roadmap.md` para las mejoras planificadas, `docs/mejoras.md` para ideas de evolución y `docs/issues-y-soluciones.md` para problemas conocidos con su solución.

---

## Reglas de mantenimiento

Estas reglas son obligatorias para cualquier cambio en el módulo, sin excepción.

### 1. Control de versiones — `CHANGELOG.md` siempre al día

**Ante cualquier cambio en el código**, actualizar `CHANGELOG.md` en la raíz del módulo:

- Crear una nueva entrada `## [X.Y.Z] — YYYY-MM-DD` siguiendo [Semantic Versioning](https://semver.org/lang/es/):
  - **PATCH** `X.Y.Z+1` — corrección de bug, fix de seguridad, ajuste sin impacto en la API.
  - **MINOR** `X.Y+1.0` — funcionalidad nueva compatible hacia atrás (nuevo tipo de campo, nueva clase, nueva constante).
  - **MAJOR** `X+1.0.0` — cambio que rompe compatibilidad hacia atrás (renombrado de clase, cambio de firma de constructor, eliminación de constante).
- Clasificar cada cambio bajo la sección correspondiente: `Agregado`, `Cambiado`, `Corregido`, `Eliminado`, `Seguridad`.
- Describir el **qué** y el **por qué**, no solo el archivo modificado.
- La versión más reciente siempre va al principio del archivo.

### 2. Documentación — `readme.md` siempre sincronizado

**Ante cualquier cambio que afecte la interfaz pública del módulo**, actualizar `readme.md`:

Cambios que **requieren actualizar el readme**:
- Nuevo tipo de campo en `ColtmanInputFields` → agregar a la sección "Tipos de campo disponibles".
- Cambio en la firma del constructor de cualquier clase → actualizar la tabla de parámetros y el snippet de ejemplo.
- Nueva constante (`COLTMAN_*`) → actualizar la sección "Auto-detección de contexto".
- Nueva clase → agregar sección completa con descripción, parámetros y snippet.
- Nuevo idioma en `languages/` → actualizar la nota sobre agregar traducciones.
- Cambio en el comportamiento de guardado o lectura de un campo → actualizar el snippet de "Leer valores guardados en el frontend".

Cambios que **no requieren actualizar el readme** (son internos):
- Refactors internos sin cambio de API.
- Correcciones de bugs que no alteran el comportamiento esperado.
- Cambios en `docs/`, `CHANGELOG.md` o archivos de contexto.

---

Framework PHP reutilizable **Coltman** — conjunto de clases para registrar CPTs, taxonomías, metaboxes y campos de formulario en WordPress. Se usa en temas y plugins del mismo autor. Esta copia vive como sub-repositorio git independiente dentro del tema.

---

## Estructura de archivos

```
classes/
├── class.php                        ← Loader principal + constantes del framework
├── class-post-types.php             ← ColtmanRegisterPost
├── class-taxonomy.php               ← ColtmanRegisterTaxonomy
├── class-metabox.php                ← ColtmanCreateMetabox
├── class-termeta.php                ← ColtmanTermMeta
├── class-usermetabox.php            ← ColtmanCreateUserMeta
├── input-fields.php                 ← ColtmanInputFields (renderizado de campos)
├── composer.json                    ← Dependencias: phpunit/phpunit ^10 (dev)
├── phpunit.xml                      ← Configuración de PHPUnit 10
├── readme.md                        ← Guía de uso completa
├── context.md                       ← Este archivo
├── CHANGELOG.md                     ← Historial de versiones (Semantic Versioning)
├── assets/
│   └── js/
│       ├── media.js                 ← JS del admin: galería, acordeón, media picker
│       └── tailwind.js              ← (comentado en producción)
├── docs/
│   ├── roadmap.md                   ← Mejoras planificadas en 6 fases
│   ├── mejoras.md                   ← Ideas de evolución del framework
│   └── issues-y-soluciones.md       ← 14 issues catalogados con solución concreta
├── languages/
│   ├── coltman.pot                  ← Plantilla de traducción (fuente para traductores)
│   ├── coltman-es_ES.po/.mo         ← Español (España)
│   └── coltman-nl_NL.po/.mo         ← Neerlandés
├── tests/
│   ├── bootstrap.php                ← Carga stubs, constantes y archivos fuente
│   ├── TestCase.php                 ← Clase base con helpers spy/stub/capture
│   ├── Stubs/
│   │   └── wordpress.php            ← Stubs de todas las funciones WP usadas
│   └── Unit/
│       ├── RegisterPostTest.php     ← 18 tests — ColtmanRegisterPost
│       ├── RegisterTaxonomyTest.php ← 19 tests — ColtmanRegisterTaxonomy
│       ├── CreateMetaboxTest.php    ← 24 tests — ColtmanCreateMetabox
│       ├── InputFieldsTest.php      ← 40 tests — ColtmanInputFields
│       ├── TermMetaTest.php         ← 26 tests — ColtmanTermMeta
│       ├── UserMetaTest.php         ← 27 tests — ColtmanCreateUserMeta
│       └── Utils/
│           ├── UtilsTest.php             ← 9 tests — coltman_trim_content_text_fn, formaturltext
│           ├── ReadTimeTest.php          ← 13 tests — get_estimated_reading_time
│           └── NavigationsAnchorsTest.php ← 21 tests — heading injection functions
└── utils/
    ├── utils.php                    ← Loader + helpers: trim, formaturltext
    ├── read-time.php                ← get_estimated_reading_time()
    ├── navigations_archors.php      ← process_headings_and_get_data(), inyección de IDs a headings
    └── optimizations/
        ├── optimizations.php        ← Loader de optimizaciones
        └── remove_scripts.php       ← Elimina jQuery Migrate del frontend
```

> **Nota:** `optimizations/` **no** está incluido en `utils.php`. Solo se carga si se hace `require` explícito desde el tema.

---

## `class.php` — Loader y constantes del framework

El loader define cuatro constantes de solo lectura y hace `require` de todos los módulos.

### Constantes definidas

| Constante | Valor | Descripción |
|---|---|---|
| `COLTMAN_DIR` | `__DIR__` | Ruta absoluta de filesystem a `classes/` |
| `COLTMAN_CONTEXT` | `'theme'` \| `'plugin'` \| `'mu-plugin'` \| `'unknown'` | Detectado automáticamente comparando `__FILE__` contra los directorios de WordPress |
| `COLTMAN_ASSETS_URL` | URL absoluta | Calculada con `plugins_url('assets', __FILE__)`. Funciona en temas, plugins y mu-plugins sin configuración. Sobreescribible con `define()` antes del `require` (CDN, rutas no estándar) |
| `COLTMAN_TEXT_DOMAIN` | `'coltman'` | Text domain de todas las cadenas internas del framework |

Para sobreescribir `COLTMAN_ASSETS_URL` antes del require:
```php
define('COLTMAN_ASSETS_URL', 'https://cdn.ejemplo.com/coltman/assets');
require 'path/to/classes/class.php';
```

### Sistema de traducción

El loader registra en el hook `init` (prioridad 1) una closure que carga el archivo `.mo` correspondiente al locale activo:

```php
add_action('init', static function() {
    $locale  = determine_locale();
    $mo_file = COLTMAN_DIR . '/languages/coltman-' . $locale . '.mo';
    if (file_exists($mo_file)) {
        load_textdomain(COLTMAN_TEXT_DOMAIN, $mo_file);
    }
}, 1);
```

Se usa closure estática (no función con nombre) para evitar que quede bajo el namespace `Class\AddicClinicDirectory` y sea invisible para WordPress al resolver el hook.

### Orden de require

1. `input-fields.php`
2. `class-post-types.php`
3. `class-taxonomy.php`
4. `class-metabox.php`
5. `class-termeta.php`
6. `utils/utils.php`
7. `class-usermetabox.php`

> El namespace `Class\AddicClinicDirectory` es un residuo del proyecto original. No afecta la funcionalidad — las clases no usan `use` ni referencias de namespace. Ver Fase 5.2 del roadmap para la migración a `Coltman\Framework`.

---

## Requisitos mínimos

### Entorno de ejecución

| Componente | Mínimo | Motivo |
|---|---|---|
| PHP | 8.0 | Union types (`array\|bool`), `str_starts_with()` |
| WordPress | 5.0 | `determine_locale()`, `show_in_rest`, `wp_normalize_path()` |
| ext-iconv | — | `formaturltext()` usa `iconv('UTF-8', 'ASCII//TRANSLIT', ...)` |

### Entorno de pruebas (PHPUnit)

| Componente | Mínimo |
|---|---|
| PHP | 8.1 |
| ext-dom, ext-mbstring, ext-xml, ext-xmlwriter | — |

Snippet de verificación en el theme/plugin que carga el framework:
```php
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Coltman Framework requiere PHP 8.0+.</p></div>';
    });
    return;
}
```

---

## `languages/` — Sistema de traducción (i18n)

Todas las cadenas internas del framework usan el text domain `coltman` (constante `COLTMAN_TEXT_DOMAIN`). Los archivos viven en `classes/languages/`:

| Archivo | Descripción |
|---|---|
| `coltman.pot` | Plantilla para traductores |
| `coltman-es_ES.po` / `.mo` | Español (España) — compilado |
| `coltman-nl_NL.po` / `.mo` | Neerlandés — compilado |

Para agregar un nuevo idioma:
```bash
# 1. Copiar la plantilla
cp languages/coltman.pot languages/coltman-fr_FR.po

# 2. Traducir las cadenas en el .po

# 3. Compilar
msgfmt languages/coltman-fr_FR.po -o languages/coltman-fr_FR.mo
```

---

## `tests/` — Pruebas unitarias

Suite PHPUnit 10 de 207 tests / 357 assertions. No requiere WordPress instalado — usa stubs PHP puros.

### Ejecutar

```bash
cd classes/
php vendor/bin/phpunit --no-coverage
# → OK (207 tests, 357 assertions)
```

### Instalar dependencias de test

```bash
COMPOSER_ALLOW_SUPERUSER=1 php /path/to/composer install --no-interaction
apt-get install -y php8.x-dom php8.x-mbstring php8.x-xml php8.x-xmlwriter
```

### Arquitectura del sistema de stubs

No usa Brain Monkey ni Mockery. Funciona con globals PHP puros:

```php
$GLOBALS['_coltman_spy']   = [];   // registro de todas las llamadas a funciones WP
$GLOBALS['_coltman_stubs'] = [];   // valores de retorno sobreescritos por test
$GLOBALS['_coltman_flags'] = [     // flags booleanos para comportamiento condicional
    'is_admin'        => false,
    'metadata_exists' => false,
    'current_user_can'=> true,
];
```

`TestCase.php` expone los helpers:

| Método | Descripción |
|---|---|
| `setStub($fn, $value)` | Fija el retorno de una función WP para el test |
| `setFlag($flag, $value)` | Cambia un flag de comportamiento |
| `spyCalls($fn)` | Devuelve todas las llamadas registradas a `$fn` |
| `firstCall($fn)` | Primera llamada registrada |
| `assertCalled($fn)` | Falla si `$fn` no fue llamada |
| `assertNotCalled($fn)` | Falla si `$fn` fue llamada |
| `capture(callable)` | Captura output de funciones que hacen `echo` |
| `getProperty($obj, $prop)` | Accede a propiedades privadas vía Reflection |
| `callMethod($obj, $method, $args)` | Invoca métodos privados vía Reflection |

### Cobertura de tests

| Suite | Clase | Tests |
|---|---|---|
| `RegisterPostTest` | `ColtmanRegisterPost` | 18 |
| `RegisterTaxonomyTest` | `ColtmanRegisterTaxonomy` | 19 |
| `CreateMetaboxTest` | `ColtmanCreateMetabox` | 24 |
| `InputFieldsTest` | `ColtmanInputFields` | 40 |
| `TermMetaTest` | `ColtmanTermMeta` | 26 |
| `UserMetaTest` | `ColtmanCreateUserMeta` | 27 |
| `UtilsTest` | `coltman_trim_content_text_fn`, `formaturltext` | 9 |
| `ReadTimeTest` | `get_estimated_reading_time` | 13 |
| `NavigationsAnchorsTest` | heading injection functions | 21 |
| **Total** | | **207 / 357 assertions** |

---

## `ColtmanRegisterPost` — `class-post-types.php`

Encapsula el registro de un Custom Post Type con todos sus labels generados automáticamente.

### Constructor

```php
new ColtmanRegisterPost(
    array  $labelArgs,   // 'name', 'item', 'domain'
    string $post_name,   // slug del CPT
    array  $args,        // ver tabla de args abajo
    array  $supports,    // ['thumbnail', 'editor', 'title', 'revisions', 'custom-fields'…]
    array  $taxonomies,  // slugs de taxonomías a asociar
    array|bool $rewrite  // ['slug'=>'…', 'with_front'=>bool] o false
);
```

### `$args` aceptados

| Clave | Tipo | Descripción |
|---|---|---|
| `description` | string | Descripción del CPT |
| `hierarchical` | bool | Si admite jerarquía (como páginas) |
| `public` | bool | Visible en frontend |
| `show_ui` | bool | Mostrar en admin |
| `show_in_menu` | bool | Mostrar en menú del admin |
| `show_in_admin_bar` | bool | Mostrar en barra de admin |
| `show_in_nav_menus` | bool | Disponible en menús de nav |
| `menu_position` | int | Posición en el menú |
| `menu_icon` | string | `dashicons-*` |
| `can_export` | bool | Exportable |
| `has_archive` | bool\|string | `false` o slug del archivo |
| `exclude_from_search` | bool | Excluir del buscador |
| `publicly_queryable` | bool | Consultable en frontend |
| `capability_type` | string | `'post'` normalmente |
| `show_in_rest` | bool | Exponer en REST API |
| `rest_base` | string | Slug REST |
| `map_meta_cap` | bool | Mapear capacidades meta |

### Hook registrado

`add_action('init', [$this, 'register_new_post_type'])` — llama a `register_post_type()`.

### Ejemplo de uso en este tema

```php
// include/features/joyeria/post-type.php
new ColtmanRegisterPost(
    ['name' => 'Joyas', 'item' => 'Joya', 'domain' => 'anillosdepedida'],
    'joyas_a_medida',
    [
        'description'         => __('Catálogo de joyas', 'anillosdepedida'),
        'hierarchical'        => true,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-superhero-alt',
        'can_export'          => true,
        'has_archive'         => 'joyas-a-medida',
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'page',
        'show_in_rest'        => false,
        'rest_base'           => 'joyas-a-medida',
        'map_meta_cap'        => true,
    ],
    ['thumbnail', 'custom-fields', 'editor', 'revisions', 'title'],
    ['tipo_de_joyeria', 'materiales', 'gema', 'estilo'],
    ['slug' => 'joyas-a-medida', 'with_front' => false, 'pages' => true, 'feeds' => true]
);
```

---

## `ColtmanRegisterTaxonomy` — `class-taxonomy.php`

Registra taxonomías personalizadas con capacidades estándar.

### Constructor

```php
new ColtmanRegisterTaxonomy(
    array      $config,        // ver tabla abajo
    string     $taxonomy_name,
    array      $post_types,    // slugs de CPTs a los que se adjunta
    array|bool $rewrite        // ['slug'=>'…'] o false
);
```

### `$config` aceptado

| Clave | Tipo | Requerido |
|---|---|---|
| `plural_name` | string | Sí |
| `singular_name` | string | Sí |
| `item` | string | Sí |
| `text_domain` | string | Sí |
| `hierarchical` | bool | Sí |
| `public` | bool | Sí |
| `show_ui` | bool | Sí |
| `show_admin_column` | bool | Sí |
| `show_in_nav_menus` | bool | Sí |
| `show_in_rest` | bool | Sí |
| `rest_base` | string | Sí |
| `show_in_menu` | bool | No (default: `true`) |
| `capabilities` | array | No (default: capacidades estándar) |
| `show_tagcloud` | bool | No (default: `true`) |

### Capacidades por defecto

```php
[
    'manage_terms' => 'manage_categories',
    'edit_terms'   => 'manage_categories',
    'delete_terms' => 'manage_categories',
    'assign_terms' => 'edit_posts',
]
```

### Hook registrado

`add_action('init', [$this, 'register_new_taxonomy'])` — llama a `register_taxonomy()`.

---

## `ColtmanCreateMetabox` — `class-metabox.php`

Crea metaboxes en el editor de posts/páginas con campos dinámicos.

### Constructor

```php
new ColtmanCreateMetabox(array $config);
```

### `$config` completo

```php
$config = [
    'title'       => 'Título del metabox',
    'description' => 'Descripción visible',
    'prefix'      => 'mi_prefix_',       // se usa como ID del metabox
    'domain'      => 'text-domain',
    'class_name'  => 'css-class',
    'context'     => 'normal',           // 'normal' | 'side' | 'advanced'
    'priority'    => 'high',             // 'high' | 'default' | 'low'
    'cpt'         => 'post,page',        // string con comas o un solo CPT
    'fields'      => [
        [
            'label'   => 'Campo de texto',
            'id'      => 'mi_campo',
            'type'    => 'text',
            'default' => '',
        ],
        // … más campos
    ],
];
```

### Hooks registrados

| Hook | Método | Descripción |
|---|---|---|
| `add_meta_boxes` | `add_meta_boxes()` | Registra el metabox |
| `admin_enqueue_scripts` | `admin_enqueue_scripts()` | Encola media, color picker, Select2 |
| `admin_head` | `admin_head()` | Inyecta `media.js` + clases Tailwind inline |
| `save_post` | `save_post($post_id)` | Guarda los campos |

### Proceso de `save_post`

Itera `$config['fields']` y aplica según `type`:

| Tipo | Sanitización |
|---|---|
| `get_posts` | `json_encode($_POST[$id])` o `'[]'` |
| `checkbox` | Valor raw o `''` |
| `editor` | `wp_filter_post_kses()` |
| `email` | `sanitize_email()` |
| `textarea` | Sin sanitizar (raw) — ver nota abajo |
| Resto | Raw — ver nota abajo |

> **Advertencia de seguridad:** `textarea` y el caso `default` no aplican `sanitize_text_field()`. Considerar agregar sanitización explícita según el campo.

> **Advertencia:** `save_post` no verifica nonce ni `current_user_can()`. Agregar para producción robusta.

### Método `process_cpts()`

Convierte `cpt` (string con comas) en `post-type` (array). Permite `'cpt' => 'post,page,anillo_jewelry'`.

### CSS inyectado en `admin_head()`

El método inyecta un bloque `<style>` inline con clases utilitarias Tailwind-like: `flex`, `gap-*`, `bg-blue-500`, `bg-red-500`, `rounded`, `text-white`, etc. Esto permite usar clases de utilidad en los campos sin cargar Tailwind completo.

---

## `ColtmanInputFields` — `input-fields.php`

Renderiza el HTML de cada tipo de campo. Usado por `ColtmanCreateMetabox`, `ColtmanTermMeta` y `ColtmanCreateUserMeta`.

### Métodos públicos

#### `checkbox($field, $checked = '')`
```html
<label class="rwp-checkbox-label">
  <input [checked] id="…" name="…" type="checkbox"> [description]
</label>
```

#### `editor($field, $value = '')`
Llama a `wp_editor()` con opciones de `wpautop`, `media_buttons`, `teeny`, `quicktags`.

#### `input($field, $value = '')`
```html
<input class="regular-text block w-full min-h-10 [class]" type="[type]" value="[value]">
```
Si `type` es `media` o `accordion`, lo cambia a `text`.

#### `input_minmax($field, $value = '')`
Igual que `input()` pero agrega atributos `min`, `max`, `step`. Usado para `number` y `date`.

#### `textarea($field, $value = '')`
```html
<textarea rows="[rows|5]" placeholder="[placeholder]">…</textarea>
```

#### `select($field, $value = '')`
Delega a `select_options()` para generar los `<option>`. Soporta:
- Array `['key' => 'label']`
- Array de arrays `[['value'=>…, 'label'=>…, 'selected'=>bool]]`

#### `media($field, $value = '')`
Combina `input()` + `media_button()` en un `div.flex`.  
Requiere `wp_enqueue_media()` y `media.js` para abrir el selector.

#### `gallery_input($field, $value = '')`
Campo de galería múltiple. El valor se guarda como **JSON** con este esquema por ítem:
```json
{"id": 123, "url": "https://…", "alt": "…", "item": "uniqueId", "sizes": {…}, "title": "…"}
```
Renderiza un contenedor `.gallery` con `.gallery-item` repetibles, manejados por `media.js`.

#### `get_posts($field, $value = '')`
`<select multiple>` con todos los posts de `$field['post_type']` publicados. El valor guardado es un **JSON array de IDs** (`json_encode($_POST[$id])`).  
Usa Select2 para la interfaz de selección múltiple.

#### `get_terms($field, $value = '')`
`<select>` simple con los términos de `$field['taxonomy']`. Valor guardado: `term_id` como string.

#### `accordion($field, $value)`
Campo complejo de ítems repetibles (título + contenido + imagen opcional). Valor guardado como **JSON**:
```json
[{"id": "field_1234_parent", "title": "…", "content": "…", "image": "https://…"}]
```
Controlado por `media.js` (funciones `saveAccordeonItem`, `removeAccordeonItem`, `addAccordeonItem`).  
Para desactivar la imagen por ítem: `'add_image' => 'false'` en el array del campo.

---

## `ColtmanTermMeta` — `class-termeta.php`

Agrega campos personalizados a los términos de una taxonomía.

### Constructor

```php
new ColtmanTermMeta([
    'taxonomy' => 'tipo_de_joyeria',
    'title'    => 'Información adicional',
    'fields'   => [
        'jewel_material' => [
            'label'   => 'Material',
            'type'    => 'text',
            'default' => '',
        ],
    ],
]);
```

> **Importante:** las claves del array `fields` son los IDs de los campos (a diferencia de `ColtmanCreateMetabox` donde `id` es una propiedad dentro del array del campo).

### Hooks registrados (solo en `is_admin()`)

| Hook | Método |
|---|---|
| `{taxonomy}_add_form_fields` | `wpturbo_render_meta_fields()` |
| `{taxonomy}_edit_form_fields` | `wpturbo_edit_meta_fields()` |
| `created_{taxonomy}` | `wpturbo_save_meta_fields()` |
| `edited_{taxonomy}` | `wpturbo_save_meta_fields()` |
| `admin_enqueue_scripts` | `admin_enqueue_scripts()` |
| `admin_head` | `admin_head()` |

### Tipos de campo soportados en `wpturbo_render_input_field()`

`select`, `textarea`, `media`, `default` (input text/number/etc.)

> Solo soporta un subconjunto de los tipos de `ColtmanInputFields`. Para `gallery`, `accordion`, `get_posts`, etc., renderiza el fallback `default` (input text).

### Sanitización en `wpturbo_save_meta_fields()`

| Tipo | Sanitización |
|---|---|
| `email` | `sanitize_email()` |
| `text` | `sanitize_text_field()` |
| Resto | Sin sanitizar — raw |

---

## `ColtmanCreateUserMeta` — `class-usermetabox.php`

Agrega una sección de campos personalizados al perfil de usuario del admin.

### Constructor

```php
new ColtmanCreateUserMeta([
    'title'       => 'Información adicional',
    'description' => 'Descripción opcional',
    'fields'      => [
        [
            'label'   => 'Nombre de empresa',
            'id'      => 'user_company',
            'type'    => 'text',
            'default' => '',
        ],
    ],
]);
```

### Hooks registrados

| Hook | Método |
|---|---|
| `show_user_profile` | `add_user_meta_section()` |
| `edit_user_profile` | `add_user_meta_section()` |
| `personal_options_update` | `save_user_meta()` |
| `edit_user_profile_update` | `save_user_meta()` |
| `admin_enqueue_scripts` | `admin_enqueue_scripts()` |

Solo activa los scripts en `profile.php` y `user-edit.php`.

### Sanitización en `save_user_meta()`

| Tipo | Sanitización |
|---|---|
| `get_posts` | `json_encode()` o `'[]'` |
| `editor` | `wp_filter_post_kses()` |
| `email` | `sanitize_email()` |
| `textarea` | `sanitize_textarea_field()` |
| `url` | `esc_url_raw()` |
| `default` | `sanitize_text_field()` |

Verifica `current_user_can('edit_user', $user_id)` antes de guardar.

> Es la clase mejor sanitizada del framework. Las demás deberían adoptar este patrón.

---

## Utilidades — `utils/`

### `utils.php`

**`coltman_trim_content_text_fn($content, $length = 15, $ellipsis = '...')`**  
Wrapper de `wp_trim_words()`. Recorta contenido a `$length` palabras.

**`formaturltext($text)`**  
Convierte texto a formato URL-safe: elimina tildes/diacríticos (`iconv TRANSLIT`), elimina caracteres no alfanuméricos y reemplaza espacios por `+`. Útil para parámetros GET o búsquedas por URL.

---

### `read-time.php`

**`get_estimated_reading_time($args)`**

Calcula el tiempo de lectura estimado de un post.

```php
$result = get_estimated_reading_time([
    'post'          => $post_id,   // int|WP_Post|null (null = global $post)
    'wpm'           => 200,        // palabras por minuto
    'single_suffix' => 'min',
    'plural_suffix' => 'mins',
]);
// $result->time   → int (mínimo 1)
// $result->suffix → 'min' | 'mins'
```

Proceso: `strip_shortcodes()` → `wp_strip_all_tags()` → `str_word_count()` → `ceil(count / wpm)`.

---

### `navigations_archors.php`

Inyecta IDs únicos en los headings (`h1`–`h6`) del contenido para generar tablas de contenido.

**`process_headings_and_get_data($content)`**  
Recibe HTML, agrega `id="slug-del-titulo"` a cada heading (si no tiene uno), y devuelve:
```php
[
    'content'  => '<h2 id="mi-titulo">…</h2>…', // HTML modificado
    'headings' => [
        (object)['id' => 'mi-titulo', 'text' => 'Mi Título'],
        // …
    ]
]
```
Los IDs se generan con `sanitize_title()` y se deduplican con sufijo numérico si hay repetidos.

**`filter_content_inject_headings($content)`**  
Conectado al filtro `the_content` (prioridad 15). Actualiza la variable global `$rc_global_headings` con los headings del post actual.

**`get_extracted_headings_array()`**  
Función pública para obtener los headings desde una plantilla. Debe llamarse **después** de `the_content()`. Si se llama antes, usa `get_the_content()` como fallback y reprocesa manualmente.

Ejemplo de uso en plantilla:
```php
the_content();
$headings = get_extracted_headings_array();
foreach ($headings as $h) {
    echo '<a href="#' . $h->id . '">' . $h->text . '</a>';
}
```

---

### `optimizations/remove_scripts.php`

**`adctn_remove_jquery_migrate($scripts)`**  
Elimina `jquery-migrate` de las dependencias de jQuery en el frontend (hook `wp_default_scripts`, prioridad 999999).  
La eliminación completa de jQuery está comentada.

> Este archivo **no** se carga automáticamente desde `utils.php`. Requiere `require` explícito.

---

## `assets/js/media.js`

Script del admin que maneja la interacción con el Media Picker de WordPress y los campos de galería/acordeón.

### Funcionalidad principal

**Media picker (`.rwp-media-toggle`):**  
Abre `wp.media()` al click. Al seleccionar, escribe la URL/ID en el input anterior (`button.prev().val()`). Si el botón está dentro de `.get-image` (galería), actualiza también el `input.gallery-data` (JSON de la galería).

**Galería:**

| Función | Descripción |
|---|---|
| `addiTemImage(e)` | Clona el primer `.gallery-item` para agregar una nueva fila |
| `removeiTem(e)` | Elimina un ítem de la galería o limpia su valor si es el último |

El JSON de la galería usa esta estructura por ítem:
```json
{"id": 123, "url": "…", "alt": "…", "item": "uniqueId", "sizes": {…}, "mime": "image/jpeg", "height": 800, "width": 600, "title": "…"}
```

**Acordeón:**

| Función | Descripción |
|---|---|
| `saveAccordeonItem(e)` | Guarda el ítem actual en el JSON oculto y clona un nuevo ítem vacío |
| `saveAccordeonItemData(e)` | Actualiza o agrega el ítem en el JSON (detecta duplicados) |
| `removeAccordeonItem(e)` | Elimina el ítem del DOM y del JSON, o limpia sus campos si es el último |
| `addAccordeonItem(e)` | Lee todos los ítems actuales, los agrega al JSON y clona uno nuevo |
| `cloneElement(parentElement)` | Clona el primer `.accordion-item`, resetea sus valores y lo agrega al contenedor |

**Helpers de serialización:**

| Función | Descripción |
|---|---|
| `normalizeQuotes(str)` | Convierte `'` → `’` y `"` → `”` para evitar romper JSON |
| `escapeForPhpJson(str)` | Escapa `\`, `'`, `"` para serialización PHP-JSON |
| `sanitizeForJSON(value)` | Escapa comillas y caracteres HTML peligrosos con entidades |

---

## Tabla comparativa de clases

| Clase | Qué registra | Contexto | Sanitización |
|---|---|---|---|
| `ColtmanRegisterPost` | CPT | `init` | N/A |
| `ColtmanRegisterTaxonomy` | Taxonomía | `init` | N/A |
| `ColtmanCreateMetabox` | Metabox de post | Admin | Parcial (email, editor) |
| `ColtmanTermMeta` | Campos de término | Admin | Parcial (email, text) |
| `ColtmanCreateUserMeta` | Campos de usuario | Admin | Completa (por tipo) |
| `ColtmanInputFields` | — (solo renderiza) | Admin | N/A |

---

## Diferencias clave entre `ColtmanCreateMetabox` y `ColtmanTermMeta`

| Aspecto | `ColtmanCreateMetabox` | `ColtmanTermMeta` |
|---|---|---|
| Definición de campos | Array con `'id'` como propiedad | Array asociativo donde la **clave** es el ID |
| Tipos soportados | Todos los de `ColtmanInputFields` | `select`, `textarea`, `media`, `default` |
| Almacenamiento | `update_post_meta()` | `update_term_meta()` |
| Verificación de permisos | Ninguna | Ninguna |

---

## Problemas conocidos y deuda técnica

1. **Sin nonce en `save_post`:** `ColtmanCreateMetabox::save_post()` no verifica nonce ni `current_user_can()`. Vulnerable a CSRF si el formulario es enviado por terceros.

2. **Sanitización incompleta:** `textarea` y el caso `default` en `save_post` guardan el valor raw. Para campos de texto libre, agregar `sanitize_text_field()` o `sanitize_textarea_field()`.

3. **`ColtmanTermMeta` no usa `ColtmanInputFields`:** Implementa su propio renderizado (`wpturbo_render_input_field`) con soporte reducido. Solo soporta `select`, `textarea`, `media` y `default`. Si se necesita `gallery` o `accordion` en términos, hay que extender este método.

4. **Select2 versión obsoleta (3.4.8):** Se carga desde CDN. La versión actual es 4.x con API distinta. No actualizar sin revisar el JS que lo usa.

5. **Namespace incorrecto:** `Class\AddicClinicDirectory` no refleja este proyecto. Cambiar requeriría actualizar todos los archivos que usan `use Class\AddicClinicDirectory` (actualmente solo `class-termeta.php` línea 25 — que además es un `use` incorrecto ya que no hay clases bajo ese namespace que se importen).

6. **`add_image => 'false'` es string, no bool:** En `accordion()` de `ColtmanInputFields` la comprobación es `$field['add_image'] != 'false'`. Pasar `false` (bool) activará imágenes de todos modos. Siempre usar el string `'false'`.

7. **`optimizations/` no se carga automáticamente:** `utils/utils.php` no incluye `optimizations/optimizations.php`. Para activar la eliminación de jQuery Migrate desde aquí, agregar el require manualmente en `utils.php` o en el loader del tema.