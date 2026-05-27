# Framework Coltman — Guía completa

Módulo PHP reutilizable para WordPress que reemplaza la necesidad de **ACF (Advanced Custom Fields)** y plugins similares. Permite registrar Custom Post Types, taxonomías, metaboxes con campos personalizados y meta de usuario sin dependencias externas.

Se usa en temas y plugins. Se distribuye como sub-repositorio git independiente — se copia la carpeta `classes/` y funciona.

---

## Requisitos mínimos

| Componente | Versión mínima | Motivo |
|---|---|---|
| **PHP** | **8.0** | Union types (`array\|bool`) en constructores · `str_starts_with()` en auto-detección de contexto |
| **WordPress** | **5.0** | `determine_locale()` (carga del textdomain) · `show_in_rest` en CPTs y taxonomías · `wp_normalize_path()` |
| **ext-iconv** | — | `formaturltext()` usa `iconv('UTF-8', 'ASCII//TRANSLIT', ...)` para eliminar tildes |

> **ext-iconv** viene compilada por defecto en la mayoría de instalaciones PHP. Si no está disponible, `formaturltext()` puede fallar silenciosamente. El resto del framework no depende de ella.

### Requisitos adicionales para ejecutar las pruebas unitarias

| Componente | Versión mínima | Motivo |
|---|---|---|
| **PHP** | **8.1** | PHPUnit 10 requiere PHP 8.1+ (el framework en sí funciona con 8.0) |
| **ext-dom** | — | PHPUnit |
| **ext-mbstring** | — | PHPUnit |
| **ext-xml** | — | PHPUnit |
| **ext-xmlwriter** | — | PHPUnit |

```bash
# Verificar los requisitos en un servidor
php -r "echo PHP_VERSION . PHP_EOL;"
php -r "echo function_exists('iconv') ? 'iconv: OK' : 'iconv: FALTA';"
php -m | grep -E 'dom|mbstring|xml'
```

---

## Índice

0. [Requisitos mínimos](#requisitos-mínimos)
1. [Instalación y carga](#1-instalación-y-carga)
2. [ColtmanRegisterPost — Custom Post Types](#2-coltmanregisterpost--custom-post-types)
3. [ColtmanRegisterTaxonomy — Taxonomías](#3-coltmanregistertaxonomy--taxonomías)
4. [ColtmanCreateMetabox — Metaboxes](#4-coltmancreatemetabox--metaboxes)
5. [Tipos de campo disponibles](#5-tipos-de-campo-disponibles)
6. [ColtmanTermMeta — Campos en términos](#6-coltmantermmeta--campos-en-términos)
7. [ColtmanCreateUserMeta — Campos en perfiles de usuario](#7-coltmancreateusermetaa--campos-en-perfiles-de-usuario)
8. [Utilidades](#8-utilidades)
9. [Leer valores guardados en el frontend](#9-leer-valores-guardados-en-el-frontend)
10. [Estructura de archivos](#10-estructura-de-archivos)
11. [Notas de seguridad](#11-notas-de-seguridad)
12. [Pruebas unitarias](#12-pruebas-unitarias)

---

## 1. Instalación y carga

### En un tema

En `functions.php`:

```php
// Carga todo el framework Coltman
require get_stylesheet_directory() . '/classes/class.php';
```

`class.php` se encarga de cargar todas las clases en el orden correcto. No es necesario incluir nada más.

### En un plugin

```php
// Al inicio del archivo principal del plugin — no se necesita ninguna configuración extra
require plugin_dir_path(__FILE__) . 'classes/class.php';
```

### Auto-detección de contexto

El framework detecta automáticamente si está cargado en un tema, plugin o mu-plugin comparando la ruta de `class.php` contra los directorios de WordPress. No se necesita ninguna configuración manual.

Al cargar `class.php` quedan disponibles tres constantes de solo lectura:

| Constante | Valor ejemplo en tema | Valor ejemplo en plugin |
|---|---|---|
| `COLTMAN_CONTEXT` | `'theme'` | `'plugin'` |
| `COLTMAN_ASSETS_URL` | `https://sitio.com/wp-content/themes/mi-tema/classes/assets` | `https://sitio.com/wp-content/plugins/mi-plugin/classes/assets` |
| `COLTMAN_DIR` | `/var/www/html/.../themes/mi-tema/classes` | `/var/www/html/.../plugins/mi-plugin/classes` |

Los valores posibles de `COLTMAN_CONTEXT` son: `'theme'`, `'plugin'`, `'mu-plugin'`, `'unknown'`.

**Para sobreescribir** (CDN, ruta no estándar) — definir ANTES del require:

```php
define('COLTMAN_ASSETS_URL', 'https://cdn.ejemplo.com/coltman/assets');
require '.../classes/class.php';
```

> La constante anterior `WORK_CONTEXT` fue eliminada: se definía manualmente pero nunca se leía en ningún archivo. El nuevo sistema es completamente automático.

---

## 2. ColtmanRegisterPost — Custom Post Types

Registra un Custom Post Type completo con todos sus labels generados automáticamente. Equivale a llamar a `register_post_type()` pero con la mitad del código.

### Cómo funciona

El constructor recibe la configuración, genera los 20+ labels de WordPress automáticamente y engancha `register_post_type()` en el hook `init`. No hay que hacer nada más.

### Parámetros del constructor

```php
new ColtmanRegisterPost(
    array      $labelArgs,   // Textos visibles en el admin
    string     $post_name,   // Slug del CPT (único, sin espacios)
    array      $args,        // Configuración de comportamiento
    array      $supports,    // Funcionalidades del editor habilitadas
    array      $taxonomies,  // Taxonomías pre-asociadas
    array|bool $rewrite      // Regla de URL personalizada o false
);
```

### Snippet básico — CPT público con archivo

```php
// CPT de noticias, visible en frontend, con página de archivo
new ColtmanRegisterPost(
    [
        'name'   => __('Noticias', 'mi-tema'),   // nombre plural
        'item'   => __('Noticia', 'mi-tema'),    // nombre singular
        'domain' => 'mi-tema',                  // text domain
    ],
    'mi_noticia', // slug del CPT — se usará en URLs y funciones WP
    [
        'description'         => __('Artículos de noticias', 'mi-tema'),
        'hierarchical'        => false,  // false = como posts, true = como páginas
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-megaphone',
        'can_export'          => true,
        'has_archive'         => 'noticias',    // slug del archivo: /noticias/
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'show_in_rest'        => true,          // habilita el editor de bloques
        'rest_base'           => 'noticias',    // endpoint REST: /wp-json/wp/v2/noticias
        'map_meta_cap'        => true,
    ],
    ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
    ['categoria_noticia'],  // taxonomías a asociar (deben existir)
    ['slug' => 'noticia', 'with_front' => false] // URL: /noticia/mi-titulo/
);
```

### Snippet — CPT privado solo para el admin (sin URL pública)

```php
// CPT de configuración interna — no accesible en frontend
new ColtmanRegisterPost(
    [
        'name'   => __('Configuraciones', 'mi-tema'),
        'item'   => __('Configuración', 'mi-tema'),
        'domain' => 'mi-tema',
    ],
    'mi_config',
    [
        'description'         => '',
        'hierarchical'        => false,
        'public'              => false,   // no accesible en frontend
        'show_ui'             => true,    // pero sí visible en admin
        'show_in_menu'        => true,
        'show_in_admin_bar'   => false,
        'show_in_nav_menus'   => false,
        'menu_position'       => 99,
        'menu_icon'           => 'dashicons-admin-settings',
        'can_export'          => false,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'capability_type'     => 'post',
        'show_in_rest'        => false,
        'rest_base'           => '',
        'map_meta_cap'        => true,
    ],
    ['title', 'custom-fields'],
    [],
    false // sin rewrite de URL
);
```

### Iconos disponibles

Los `menu_icon` son dashicons de WordPress. Algunos útiles:

| Icono | Constante |
|---|---|
| Personas | `dashicons-groups` |
| Ubicación | `dashicons-location` |
| Calendario | `dashicons-calendar-alt` |
| Estrella | `dashicons-star-filled` |
| Carpeta | `dashicons-portfolio` |
| Productos | `dashicons-cart` |
| Lista | `dashicons-list-view` |
| Imagen | `dashicons-format-image` |

Ver todos en: https://developer.wordpress.org/resource/dashicons/

---

## 3. ColtmanRegisterTaxonomy — Taxonomías

Registra una taxonomía personalizada con labels completos, capacidades estándar y rewrite de URL. Equivale a `register_taxonomy()` pero sin escribir los 15+ labels manualmente.

### Cómo funciona

El constructor genera los labels, configura las capacidades y engancha `register_taxonomy()` en `init`. La taxonomía puede ser jerárquica (como categorías) o plana (como etiquetas).

### Parámetros del constructor

```php
new ColtmanRegisterTaxonomy(
    array      $config,        // Configuración completa
    string     $taxonomy_name, // Slug de la taxonomía
    array      $post_types,    // CPTs a los que se adjunta
    array|bool $rewrite        // Regla de URL o false
);
```

### Snippet — Taxonomía jerárquica (como categorías)

```php
// Taxonomía de materiales para el CPT de joyas
new ColtmanRegisterTaxonomy(
    [
        'plural_name'      => __('Materiales', 'mi-tema'),
        'singular_name'    => __('Material', 'mi-tema'),
        'item'             => __('Material', 'mi-tema'),
        'text_domain'      => 'mi-tema',
        'hierarchical'     => true,   // true = muestra como árbol (categorías)
        'public'           => true,
        'show_ui'          => true,
        'show_admin_column'=> true,   // muestra columna en la lista de posts
        'show_in_nav_menus'=> true,
        'show_in_rest'     => true,
        'rest_base'        => 'materiales',
    ],
    'joya_material',           // slug de la taxonomía
    ['anillo_jewelry'],        // CPTs a los que se adjunta
    ['slug' => 'material', 'with_front' => false]
);
```

### Snippet — Taxonomía plana (como etiquetas)

```php
// Etiquetas de estilo para múltiples CPTs
new ColtmanRegisterTaxonomy(
    [
        'plural_name'      => __('Estilos', 'mi-tema'),
        'singular_name'    => __('Estilo', 'mi-tema'),
        'item'             => __('Estilo', 'mi-tema'),
        'text_domain'      => 'mi-tema',
        'hierarchical'     => false,  // false = muestra como tags (sin jerarquía)
        'public'           => true,
        'show_ui'          => true,
        'show_admin_column'=> true,
        'show_in_nav_menus'=> true,
        'show_in_rest'     => true,
        'rest_base'        => 'estilos',
        'show_tagcloud'    => true,   // opcional: mostrar nube de tags
    ],
    'joya_estilo',
    ['anillo_jewelry', 'anillo_stepsprocess'], // adjuntar a varios CPTs
    false
);
```

### Snippet — Taxonomía privada (solo admin, sin URL pública)

```php
new ColtmanRegisterTaxonomy(
    [
        'plural_name'      => __('Estados internos', 'mi-tema'),
        'singular_name'    => __('Estado interno', 'mi-tema'),
        'item'             => __('Estado', 'mi-tema'),
        'text_domain'      => 'mi-tema',
        'hierarchical'     => false,
        'public'           => false,   // no accesible en frontend
        'show_ui'          => true,    // pero sí visible en admin
        'show_admin_column'=> true,
        'show_in_nav_menus'=> false,
        'show_in_rest'     => false,
        'rest_base'        => '',
    ],
    'estado_interno',
    ['mi_noticia'],
    false
);
```

---

## 4. ColtmanCreateMetabox — Metaboxes

Crea una sección de campos personalizados en la pantalla de edición de posts, páginas o cualquier CPT. Es el corazón del framework y el equivalente directo a los Field Groups de ACF.

### Cómo funciona

1. El constructor recibe la configuración y registra 4 hooks de WordPress.
2. `add_meta_boxes` → renderiza el panel en el editor.
3. `admin_enqueue_scripts` → carga media picker, Select2 y color picker.
4. `admin_head` → inyecta `media.js` y estilos utilitarios inline.
5. `save_post` → guarda cada campo en `post_meta` al guardar el post.

Los valores se guardan individualmente con `update_post_meta($post_id, $field['id'], $value)` y se recuperan con `get_post_meta($post_id, $field['id'], true)`.

### Estructura del array `$config`

```php
$config = [
    // Textos del panel
    'title'       => 'Nombre visible en el admin',
    'description' => 'Descripción opcional bajo el título',

    // Identificadores
    'prefix'      => 'mi_prefix_',   // prefijo para el ID del metabox
    'domain'      => 'mi-tema',      // text domain (para i18n)
    'class_name'  => 'mi-clase-css', // clase CSS opcional en el metabox

    // Posición en el editor
    'context'  => 'normal',  // 'normal' | 'side' | 'advanced'
    'priority' => 'high',    // 'high' | 'default' | 'low'

    // A qué pantallas aplica (string con comas o un solo valor)
    'cpt' => 'post',              // solo posts
    'cpt' => 'post,page',         // posts y páginas
    'cpt' => 'anillo_jewelry',    // un CPT específico

    // Definición de campos
    'fields' => [ /* ver sección 5 */ ],
];

new ColtmanCreateMetabox($config);
```

### Snippet completo — metabox de producto con varios tipos de campo

```php
new ColtmanCreateMetabox([
    'title'       => __('Datos del producto', 'mi-tema'),
    'description' => __('Información adicional del producto', 'mi-tema'),
    'prefix'      => 'producto_',
    'domain'      => 'mi-tema',
    'class_name'  => 'producto-metabox',
    'context'     => 'normal',
    'priority'    => 'high',
    'cpt'         => 'mi_producto',
    'fields'      => [
        [
            'label'   => __('Precio', 'mi-tema'),
            'id'      => 'producto_precio',
            'type'    => 'number',
            'default' => '0',
            'min'     => 0,
            'step'    => 0.01,
            'description' => __('Precio en USD', 'mi-tema'),
        ],
        [
            'label'   => __('Disponible', 'mi-tema'),
            'id'      => 'producto_disponible',
            'type'    => 'checkbox',
            'checked' => true,  // marcado por defecto
            'description' => __('Marcar si está en stock', 'mi-tema'),
        ],
        [
            'label'   => __('Imagen principal', 'mi-tema'),
            'id'      => 'producto_imagen',
            'type'    => 'media',
            'return'  => 'url',             // guarda la URL de la imagen
            'button-text'  => __('Seleccionar imagen', 'mi-tema'),
            'modal-title'  => __('Elige la imagen del producto', 'mi-tema'),
            'default' => '',
        ],
        [
            'label'   => __('Galería de fotos', 'mi-tema'),
            'id'      => 'producto_galeria',
            'type'    => 'gallery',
            'default' => '',
        ],
        [
            'label'   => __('Descripción larga', 'mi-tema'),
            'id'      => 'producto_descripcion',
            'type'    => 'editor',
            'default' => '',
        ],
        [
            'label'   => __('Estado', 'mi-tema'),
            'id'      => 'producto_estado',
            'type'    => 'select',
            'default' => 'activo',
            'options' => [
                'activo'    => __('Activo', 'mi-tema'),
                'inactivo'  => __('Inactivo', 'mi-tema'),
                'agotado'   => __('Agotado', 'mi-tema'),
            ],
        ],
        [
            'label'     => __('Preguntas frecuentes', 'mi-tema'),
            'id'        => 'producto_faqs',
            'type'      => 'accordion',
            'add_image' => 'false', // sin imagen en cada FAQ — DEBE ser string 'false', no bool
            'default'   => '',
        ],
    ],
]);
```

### Posicionamiento (`context`)

| Valor | Resultado |
|---|---|
| `'normal'` | Debajo del editor principal |
| `'side'` | En la barra lateral derecha |
| `'advanced'` | En la zona inferior del editor |

> En el editor de bloques (Gutenberg), `'side'` puede quedar oculto. Usar `'normal'` para mayor compatibilidad.

---

## 5. Tipos de campo disponibles

Todos los tipos se usan dentro del array `'fields'` de `ColtmanCreateMetabox` o `ColtmanTermMeta`.

---

### `text` — Texto simple

```php
[
    'label'       => 'Título alternativo',
    'id'          => 'alt_title',
    'type'        => 'text',
    'default'     => '',
    'description' => 'Se mostrará en lugar del título principal',
    // 'pattern'  => '[A-Za-z]+', // validación HTML5 opcional
]
```

**Guardado:** `string` — recuperar con `get_post_meta($id, 'alt_title', true)`

---

### `textarea` — Área de texto

```php
[
    'label'       => 'Resumen',
    'id'          => 'resumen',
    'type'        => 'textarea',
    'default'     => '',
    'rows'        => 4,          // altura del textarea (por defecto: 5)
    'placeholder' => 'Escribe un breve resumen…',
]
```

**Guardado:** `string` con saltos de línea — usar `nl2br()` o `wpautop()` al mostrar.

---

### `number` — Número

```php
[
    'label'   => 'Posición',
    'id'      => 'orden',
    'type'    => 'number',
    'default' => '0',
    'min'     => 0,
    'max'     => 100,
    'step'    => 1,
]
```

**Guardado:** `string` numérico — usar `intval()` o `floatval()` al leer.

---

### `date` — Fecha

```php
[
    'label'   => 'Fecha de publicación',
    'id'      => 'fecha_pub',
    'type'    => 'date',
    'default' => '',
    'min'     => '2020-01-01',  // fecha mínima (formato YYYY-MM-DD)
    'max'     => '2030-12-31',  // fecha máxima
]
```

**Guardado:** `string` en formato `YYYY-MM-DD`.

---

### `email` — Correo electrónico

```php
[
    'label'   => 'Correo de contacto',
    'id'      => 'email_contacto',
    'type'    => 'email',
    'default' => '',
]
```

**Guardado:** sanitizado con `sanitize_email()`.

---

### `checkbox` — Casilla de verificación

```php
[
    'label'       => 'Mostrar en portada',
    'id'          => 'show_home',
    'type'        => 'checkbox',
    'checked'     => false,      // true = marcado por defecto en posts nuevos
    'description' => 'Activa esta opción para destacarlo en portada',
]
```

**Guardado:** `'on'` cuando está marcado, `''` cuando no.  
**Leer:**
```php
$show = get_post_meta($post_id, 'show_home', true);
if ($show === 'on') {
    // está marcado
}
```

---

### `select` — Lista desplegable

```php
[
    'label'   => 'Categoría de dificultad',
    'id'      => 'dificultad',
    'type'    => 'select',
    'default' => 'media',
    'options' => [
        // 'value' => 'Etiqueta visible'
        'baja'  => 'Baja',
        'media' => 'Media',
        'alta'  => 'Alta',
    ],
    // También se puede usar array de arrays:
    // 'options' => [
    //     ['value' => 'baja',  'label' => 'Baja',  'selected' => false],
    //     ['value' => 'media', 'label' => 'Media', 'selected' => true],
    // ],
]
```

**Guardado:** el `value` de la opción seleccionada como `string`.

---

### `editor` — Editor WYSIWYG (TinyMCE)

```php
[
    'label'         => 'Contenido extendido',
    'id'            => 'contenido_extra',
    'type'          => 'editor',
    'default'       => '',
    // Opciones opcionales:
    // 'wpautop'       => true,   // convertir saltos de línea en <p>
    // 'media-buttons' => true,   // mostrar botón "Añadir media"
    // 'teeny'         => false,  // true = versión reducida del editor
    // 'rows'          => 20,     // altura
]
```

**Guardado:** HTML sanitizado con `wp_filter_post_kses()`.  
**Mostrar:**
```php
$contenido = get_post_meta($post_id, 'contenido_extra', true);
echo apply_filters('the_content', $contenido); // aplica filtros estándar de WP
```

---

### `media` — Selector de imagen / archivo

```php
[
    'label'        => 'Imagen de portada personalizada',
    'id'           => 'portada_custom',
    'type'         => 'media',
    'return'       => 'url',    // 'url' guarda la URL | 'id' guarda el attachment ID
    'button-text'  => 'Seleccionar imagen',
    'modal-title'  => 'Elige una imagen',
    'default'      => '',
]
```

**Guardado:** URL o ID del attachment según `return`.  
**Mostrar con `return => 'url'`:**
```php
$url = get_post_meta($post_id, 'portada_custom', true);
if ($url) {
    echo '<img src="' . esc_url($url) . '" alt="">';
}
```
**Mostrar con `return => 'id'`:**
```php
$attachment_id = (int) get_post_meta($post_id, 'portada_custom', true);
if ($attachment_id) {
    echo wp_get_attachment_image($attachment_id, 'large');
}
```

---

### `gallery` — Galería de imágenes múltiple

```php
[
    'label'   => 'Galería del producto',
    'id'      => 'galeria_producto',
    'type'    => 'gallery',
    'default' => '',
    // 'return' => 'url', // por defecto ya es url
]
```

**Guardado:** JSON con este esquema por imagen:
```json
[
  {
    "id": 123,
    "url": "https://sitio.com/wp-content/uploads/imagen.jpg",
    "alt": "Descripción de la imagen",
    "title": "Título",
    "item": "uniqueId_1234",
    "sizes": {"thumbnail": {…}, "medium": {…}, "large": {…}},
    "mime": "image/jpeg",
    "width": 1200,
    "height": 800
  }
]
```

**Mostrar:**
```php
$json = get_post_meta($post_id, 'galeria_producto', true);
$imagenes = $json ? json_decode($json) : [];

foreach ($imagenes as $img) {
    echo '<img src="' . esc_url($img->url) . '" alt="' . esc_attr($img->alt) . '">';
}
```

---

### `get_posts` — Selector de posts relacionados

Muestra un `<select multiple>` con todos los posts publicados de un CPT específico. Usa Select2 para búsqueda.

```php
[
    'label'     => 'Posts relacionados',
    'id'        => 'posts_relacionados',
    'type'      => 'get_posts',
    'post_type' => 'post',       // CPT del que cargar los posts
    'default'   => '[]',
]
```

**Guardado:** JSON array de IDs: `"[45, 67, 89]"`  
**Leer:**
```php
$json = get_post_meta($post_id, 'posts_relacionados', true);
$ids = $json ? json_decode($json) : [];

if (!empty($ids)) {
    $posts = get_posts(['post__in' => $ids, 'post_type' => 'post', 'posts_per_page' => -1]);
    foreach ($posts as $post) {
        echo '<a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a>';
    }
}
```

---

### `get_terms` — Selector de término de taxonomía

`<select>` simple con todos los términos de una taxonomía.

```php
[
    'label'    => 'Categoría principal',
    'id'       => 'categoria_principal',
    'type'     => 'get_terms',
    'taxonomy' => 'category',    // slug de la taxonomía
    'default'  => '',
]
```

**Guardado:** `term_id` como string.  
**Leer:**
```php
$term_id = get_post_meta($post_id, 'categoria_principal', true);
if ($term_id) {
    $term = get_term($term_id);
    echo $term->name;
}
```

---

### `accordion` — Ítems repetibles (FAQ, pasos, etc.)

Campo complejo para listas de ítems con título, contenido y opcionalmente imagen. Perfecto para FAQs, pasos de proceso, características o cualquier contenido repetible.

```php
// Con imagen en cada ítem (por defecto)
[
    'label'   => 'Galería de testimonios',
    'id'      => 'testimonios',
    'type'    => 'accordion',
    'default' => '',
]

// Sin imagen (solo título + contenido)
[
    'label'     => 'Preguntas frecuentes',
    'id'        => 'faqs',
    'type'      => 'accordion',
    'add_image' => 'false',   // IMPORTANTE: debe ser el string 'false', no el bool false
    'default'   => '',
]
```

**Guardado:** JSON array de ítems:
```json
[
  {"id": "faqs_1234_parent", "title": "¿Cuánto tarda?", "content": "Entre 2 y 4 semanas.", "image": ""},
  {"id": "faqs_5678_parent", "title": "¿Qué materiales usan?", "content": "Oro 18k y plata.", "image": ""}
]
```

**Mostrar:**
```php
$json = get_post_meta($post_id, 'faqs', true);
$items = $json ? json_decode($json) : [];

foreach ($items as $item) {
    echo '<details>';
    echo '<summary>' . esc_html($item->title) . '</summary>';
    echo '<p>' . esc_html($item->content) . '</p>';
    echo '</details>';
}
```

**Mostrar con imagen:**
```php
foreach ($items as $item) {
    echo '<div class="testimonio">';
    if ($item->image) {
        echo '<img src="' . esc_url($item->image) . '" alt="">';
    }
    echo '<h3>' . esc_html($item->title) . '</h3>';
    echo '<p>' . esc_html($item->content) . '</p>';
    echo '</div>';
}
```

---

## 6. ColtmanTermMeta — Campos en términos

Agrega campos personalizados a la pantalla de creación y edición de términos de una taxonomía (categorías, etiquetas o taxonomías personalizadas).

### Cómo funciona

Al instanciar la clase se registran hooks en `{taxonomy}_add_form_fields` y `{taxonomy}_edit_form_fields` para renderizar los campos, y en `created_{taxonomy}` y `edited_{taxonomy}` para guardarlos con `update_term_meta()`.

> **Diferencia importante con `ColtmanCreateMetabox`:** el array `'fields'` usa las **claves** del array como IDs de campo, no una propiedad `'id'` dentro del campo.

### Parámetros

```php
new ColtmanTermMeta([
    'taxonomy' => 'slug_de_la_taxonomia',
    'title'    => 'Título de la sección',
    'fields'   => [
        // La clave del array ES el id del campo
        'nombre_del_campo' => [
            'label'   => 'Etiqueta visible',
            'type'    => 'text',  // text | textarea | select | media
            'default' => '',
        ],
    ],
]);
```

### Tipos soportados

`text`, `number`, `email`, `date`, `textarea`, `select`, `media`

> Los tipos `gallery`, `accordion`, `editor`, `get_posts` y `get_terms` aún no están implementados en esta clase. Ver `docs/roadmap.md` → Fase 2.

### Snippet completo

```php
// Agrega campos a los términos de la taxonomía 'tipo_de_joyeria'
new ColtmanTermMeta([
    'taxonomy' => 'tipo_de_joyeria',
    'title'    => 'Información del tipo de joya',
    'fields'   => [
        // 'nombre_campo' => ['label', 'type', ...]
        'jewel_material' => [
            'label'   => __('Material principal', 'mi-tema'),
            'type'    => 'text',
            'default' => '',
        ],
        'jewel_origin' => [
            'label'   => __('País de origen', 'mi-tema'),
            'type'    => 'select',
            'default' => 'colombia',
            'options' => [
                'colombia' => 'Colombia',
                'peru'     => 'Perú',
                'mexico'   => 'México',
            ],
        ],
        'jewel_image' => [
            'label'        => __('Imagen del tipo', 'mi-tema'),
            'type'         => 'media',
            'return'       => 'url',
            'button-text'  => 'Seleccionar',
            'modal-title'  => 'Elige una imagen',
            'default'      => '',
        ],
        'jewel_description' => [
            'label'   => __('Descripción', 'mi-tema'),
            'type'    => 'textarea',
            'default' => '',
        ],
    ],
]);
```

### Leer un campo de término

```php
// Obtener el material de un término de 'tipo_de_joyeria'
$term_id = get_queried_object_id(); // en archive de taxonomía
$material = get_term_meta($term_id, 'jewel_material', true);
echo esc_html($material);

// O desde un post que tiene el término asignado
$terms = get_the_terms($post_id, 'tipo_de_joyeria');
if ($terms) {
    $term = $terms[0]; // primer término
    $material = get_term_meta($term->term_id, 'jewel_material', true);
}
```

---

## 7. ColtmanCreateUserMeta — Campos en perfiles de usuario

Agrega una sección con campos personalizados a la pantalla de perfil del usuario en el admin (`/wp-admin/profile.php` y `/wp-admin/user-edit.php`).

### Cómo funciona

Registra hooks en `show_user_profile` y `edit_user_profile` para renderizar, y en `personal_options_update` y `edit_user_profile_update` para guardar con `update_user_meta()`. Verifica `current_user_can('edit_user')` antes de guardar.

### Snippet completo

```php
new ColtmanCreateUserMeta([
    'title'       => __('Información profesional', 'mi-tema'),
    'description' => __('Datos adicionales del colaborador', 'mi-tema'),
    'fields'      => [
        [
            'label'   => __('Especialidad', 'mi-tema'),
            'id'      => 'user_especialidad',
            'type'    => 'text',
            'default' => '',
        ],
        [
            'label'        => __('Foto de perfil', 'mi-tema'),
            'id'           => 'user_foto',
            'type'         => 'media',
            'return'       => 'url',
            'button-text'  => 'Seleccionar foto',
            'modal-title'  => 'Elige tu foto',
            'default'      => '',
        ],
        [
            'label'   => __('Biografía', 'mi-tema'),
            'id'      => 'user_bio_custom',
            'type'    => 'textarea',
            'default' => '',
            'rows'    => 5,
        ],
        [
            'label'   => __('Rol visible', 'mi-tema'),
            'id'      => 'user_rol',
            'type'    => 'select',
            'default' => 'colaborador',
            'options' => [
                'colaborador' => 'Colaborador',
                'editor'      => 'Editor',
                'experto'     => 'Experto invitado',
            ],
        ],
        [
            'label'       => __('Perfil verificado', 'mi-tema'),
            'id'          => 'user_verificado',
            'type'        => 'checkbox',
            'description' => 'Marca si el perfil ha sido verificado',
        ],
    ],
]);
```

### Leer campos de usuario

```php
// Obtener foto de un usuario
$user_id = get_the_author_meta('ID');
$foto = get_user_meta($user_id, 'user_foto', true);
if ($foto) {
    echo '<img src="' . esc_url($foto) . '" alt="Foto de perfil">';
}

// Verificar si el perfil está verificado
$verificado = get_user_meta($user_id, 'user_verificado', true);
if ($verificado === 'on') {
    echo '<span class="verificado">✓ Verificado</span>';
}
```

---

## 8. Utilidades

### `coltman_trim_content_text_fn()` — Recortar texto

Recorta un texto al número de palabras indicado.

```php
// $content = texto a recortar
// $length  = número de palabras (default: 15)
// $ellipsis = sufijo (default: '...')
$resumen = coltman_trim_content_text_fn(get_the_content(), 30, '…');
echo $resumen;
// → "Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod…"
```

---

### `formaturltext()` — Formatear texto para URL

Convierte texto con tildes y espacios a un formato apto para parámetros de URL.

```php
$texto = "Anillos de Compromiso";
$url_param = formaturltext($texto);
// → "Anillos+de+Compromiso"

$texto_tildes = "Joyería Única";
$url_param = formaturltext($texto_tildes);
// → "Joyeria+Unica"
```

---

### `get_estimated_reading_time()` — Tiempo de lectura

Calcula el tiempo estimado de lectura de un post en minutos.

```php
// Parámetros:
// 'post'          → ID del post, objeto WP_Post o null (usa el post global)
// 'wpm'           → palabras por minuto (default: 200)
// 'single_suffix' → sufijo singular (default: 'min')
// 'plural_suffix' → sufijo plural (default: 'mins')

$tiempo = get_estimated_reading_time([
    'post'          => get_the_ID(),
    'wpm'           => 200,
    'single_suffix' => 'minuto',
    'plural_suffix' => 'minutos',
]);

echo 'Lectura: ' . $tiempo->time . ' ' . $tiempo->suffix;
// → "Lectura: 3 minutos"
```

---

### `process_headings_and_get_data()` y `get_extracted_headings_array()` — Tabla de contenidos

Inyecta IDs en los headings del contenido y permite generar una tabla de contenidos (índice navegable).

**Cómo funciona:**
1. El filtro `the_content` es interceptado automáticamente (prioridad 15).
2. Cada `<h1>`–`<h6>` recibe un `id="slug-del-titulo"` si no lo tiene.
3. Después de llamar a `the_content()`, `get_extracted_headings_array()` devuelve los headings detectados.

**Uso en plantilla para construir un índice:**

```php
<?php
// 1. Obtener el contenido ya procesado (inyecta IDs en headings)
$headings = get_extracted_headings_array(); // llamar ANTES si quieres el índice arriba
?>

<?php if (!empty($headings)): ?>
<nav class="tabla-de-contenidos">
    <p><strong>En este artículo:</strong></p>
    <ol>
        <?php foreach ($headings as $heading): ?>
        <li>
            <a href="#<?php echo esc_attr($heading->id); ?>">
                <?php echo esc_html($heading->text); ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ol>
</nav>
<?php endif; ?>

<?php the_content(); // los headings ya tendrán sus IDs inyectados ?>
```

> Si llamas `get_extracted_headings_array()` antes de `the_content()`, la función usa `get_the_content()` como fallback y procesa el contenido en seco para extraer los headings sin imprimirlos.

---

## 9. Leer valores guardados en el frontend

Resumen rápido de cómo recuperar cada tipo de campo:

```php
$id = get_the_ID();

// Texto, email, number, date, select, checkbox
$valor = get_post_meta($id, 'mi_campo', true);

// Media (url)
$url = get_post_meta($id, 'mi_campo', true);
echo '<img src="' . esc_url($url) . '">';

// Media (id)
$att_id = (int) get_post_meta($id, 'mi_campo', true);
echo wp_get_attachment_image($att_id, 'medium');

// Gallery
$items = json_decode(get_post_meta($id, 'mi_campo', true)) ?: [];
foreach ($items as $img) {
    echo '<img src="' . esc_url($img->url) . '" alt="' . esc_attr($img->alt) . '">';
}

// Accordion / FAQ
$items = json_decode(get_post_meta($id, 'mi_campo', true)) ?: [];
foreach ($items as $item) {
    echo '<h3>' . esc_html($item->title) . '</h3>';
    echo '<div>' . esc_html($item->content) . '</div>';
    if ($item->image) echo '<img src="' . esc_url($item->image) . '">';
}

// Get posts (IDs relacionados)
$ids = json_decode(get_post_meta($id, 'mi_campo', true)) ?: [];
$posts = get_posts(['post__in' => $ids, 'post_type' => 'any', 'posts_per_page' => -1]);

// Get terms (term_id)
$term_id = get_post_meta($id, 'mi_campo', true);
$term = get_term($term_id);
echo $term ? esc_html($term->name) : '';

// Término de taxonomía (term meta)
$valor = get_term_meta($term_id, 'nombre_campo', true);

// Campo de usuario
$valor = get_user_meta($user_id, 'nombre_campo', true);
```

---

## 10. Estructura de archivos

```
classes/
├── class.php               ← Loader: require de todos los módulos
├── class-post-types.php    ← ColtmanRegisterPost
├── class-taxonomy.php      ← ColtmanRegisterTaxonomy
├── class-metabox.php       ← ColtmanCreateMetabox
├── class-termeta.php       ← ColtmanTermMeta
├── class-usermetabox.php   ← ColtmanCreateUserMeta
├── input-fields.php        ← ColtmanInputFields (renderizado interno)
├── readme.md               ← Esta guía
├── context.md              ← Arquitectura técnica detallada
├── assets/
│   └── js/
│       ├── media.js        ← Galería, acordeón y media picker (admin)
│       └── tailwind.js     ← (inactivo en producción)
├── docs/
│   ├── roadmap.md          ← Cambios planificados por fase
│   ├── mejoras.md          ← Ideas de evolución del framework
│   └── issues-y-soluciones.md ← Problemas conocidos con solución concreta
└── utils/
    ├── utils.php           ← Loader + helpers
    ├── read-time.php       ← get_estimated_reading_time()
    ├── navigations_archors.php ← Headings y tabla de contenidos
    └── optimizations/
        └── remove_scripts.php  ← Elimina jQuery Migrate
```

---

## 11. Notas de seguridad

El framework está funcional pero tiene aspectos de seguridad pendientes de reforzar para entornos de producción con múltiples editores. Ver detalle completo en `docs/issues-y-soluciones.md`.

**Resumen de lo más importante:**

- `ColtmanCreateMetabox::save_post()` no verifica nonce ni `current_user_can()`. Agregar ambas verificaciones antes de usar en sitios con múltiples usuarios con permisos de edición. Ver **ISSUE-01** y **ISSUE-02**.
- `ColtmanTermMeta::wpturbo_save_meta_fields()` tampoco verifica permisos. Ver **ISSUE-03**.
- El tipo `textarea` y los campos `text` no aplican sanitización en `save_post`. Usar `sanitize_text_field()` o `sanitize_textarea_field()` según el caso. Ver **ISSUE-02**.
- `ColtmanCreateUserMeta` sí tiene verificación de permisos y sanitización completa — es el modelo a seguir para las demás clases.

---

## 12. Pruebas unitarias

El módulo incluye un sistema de pruebas con **PHPUnit 10** que no depende de una instalación WordPress — funciona con stubs PHP puros.

### Requisitos

- PHP 8.0+
- Extensiones PHP: `dom`, `mbstring`, `xml`, `xmlwriter`

### Instalación

```bash
cd classes/
composer install
```

### Ejecución

```bash
# Desde la carpeta classes/
php vendor/bin/phpunit --no-coverage
```

### Resultado esperado

```
OK (207 tests, 357 assertions)
```

### Estructura

```
tests/
├── bootstrap.php            # Constantes WP, clases stub, carga de fuentes
├── TestCase.php             # Clase base con helpers (spy, reflection, capture)
├── Stubs/
│   └── wordpress.php        # Stubs de todas las funciones WP usadas
└── Unit/
    ├── RegisterPostTest.php       # ColtmanRegisterPost      (18 tests)
    ├── RegisterTaxonomyTest.php   # ColtmanRegisterTaxonomy  (19 tests)
    ├── CreateMetaboxTest.php      # ColtmanCreateMetabox     (24 tests)
    ├── InputFieldsTest.php        # ColtmanInputFields       (40 tests)
    ├── TermMetaTest.php           # ColtmanTermMeta          (26 tests)
    ├── UserMetaTest.php           # ColtmanCreateUserMeta    (27 tests)
    └── Utils/
        ├── UtilsTest.php               # coltman_trim_content_text_fn, formaturltext
        ├── ReadTimeTest.php            # get_estimated_reading_time
        └── NavigationsAnchorsTest.php  # process_headings_and_get_data, filter_content_inject_headings
```

### Sistema de spy

Los tests usan un sistema de spy sin dependencias externas:

```php
// En el test: forzar un valor de retorno
$this->setStub('get_post_meta', 'stored_value');

// Verificar que una función WP fue llamada
$calls = $this->spyCalls('update_post_meta');
$this->assertNotEmpty($calls);

// Controlar contexto
$this->setFlag('is_admin', true);
$this->setFlag('metadata_exists', true);
```
