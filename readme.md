# Framework Coltman — Guía completa

> **Versión 1.14.0** — Reemplaza ACF sin dependencias externas ni licencias Pro.

Módulo PHP reutilizable para WordPress que registra Custom Post Types, taxonomías, metaboxes con campos personalizados, meta de términos y meta de usuario. Se distribuye como sub-repositorio git independiente — copia la carpeta `classes/` y funciona.

---

## Requisitos mínimos

| Componente | Mínimo | Motivo |
|---|---|---|
| **PHP** | **8.0** | Union types, `str_starts_with()`, match expression |
| **WordPress** | **5.0** | `determine_locale()`, `register_post_meta()`, `wp_normalize_path()` |
| **ext-iconv** | — | `formaturltext()` usa `iconv` para eliminar tildes |

> Para ejecutar los tests se necesita PHP **8.1** y las extensiones `dom`, `mbstring`, `xml`, `xmlwriter`.

---

## Índice

1. [Instalación y carga](#1-instalación-y-carga)
2. [ColtmanRegisterPost — Custom Post Types](#2-coltmanregisterpost--custom-post-types)
3. [ColtmanRegisterTaxonomy — Taxonomías](#3-coltmanregistertaxonomy--taxonomías)
4. [ColtmanCreateMetabox — Metaboxes](#4-coltmancreatemetabox--metaboxes)
5. [Tipos de campo](#5-tipos-de-campo)
   - [text](#text--texto-simple), [textarea](#textarea--área-de-texto-html), [number](#number--número), [date](#date--fecha), [email](#email--correo), [url](#url), [color](#color--selector-de-color), [checkbox](#checkbox--casilla), [select](#select--lista-desplegable)
    - [editor](#editor--wysiwyg-tinymce), [media](#media--selector-de-archivoimagen), [gallery](#gallery--galería-de-imágenes), [list](#list--lista-de-texto)
   - [accordion](#accordion--ítems-repetibles-faq-pasos), [repeater](#repeater--filas-repetibles-configurables)
   - [relationship / get_posts](#relationship--get_posts--posts-relacionados), [get_terms](#get_terms--términos-de-taxonomía)
   - [group](#group--agrupador-de-campos), [map](#map--selector-de-coordenadas)
6. [ColtmanTermMeta — Campos en términos](#6-coltmantermmeta--campos-en-términos)
7. [ColtmanCreateUserMeta — Campos en usuarios](#7-coltmancreateusermetaa--campos-en-usuarios)
8. [Soporte REST API y Gutenberg](#8-soporte-rest-api-y-gutenberg)
9. [Utilidades](#9-utilidades)
10. [Leer valores en el frontend — resumen](#10-leer-valores-en-el-frontend--resumen)
11. [Ejemplo de uso completo: ficha de producto](#11-ejemplo-de-uso-completo-ficha-de-producto)
12. [Estructura de archivos](#12-estructura-de-archivos)
13. [Seguridad](#13-seguridad)
14. [Pruebas unitarias](#14-pruebas-unitarias)

---

## 1. Instalación y carga

### En un tema (functions.php)

```php
require get_stylesheet_directory() . '/classes/class.php';
```

### En un plugin

```php
require plugin_dir_path(__FILE__) . 'classes/class.php';
```

`class.php` carga todas las clases en el orden correcto. Nada más es necesario.

### Constantes disponibles tras la carga

| Constante | Ejemplo tema | Ejemplo plugin |
|---|---|---|
| `COLTMAN_CONTEXT` | `'theme'` | `'plugin'` |
| `COLTMAN_ASSETS_URL` | `https://sitio.com/wp-content/themes/mi-tema/classes/assets` | `https://sitio.com/wp-content/plugins/mi-plugin/classes/assets` |
| `COLTMAN_DIR` | `/var/www/html/.../classes` | `/var/www/html/.../classes` |
| `COLTMAN_TEXT_DOMAIN` | `'coltman'` | `'coltman'` |

Para sobreescribir `COLTMAN_ASSETS_URL` (CDN, ruta no estándar), definir **antes** del require:

```php
define('COLTMAN_ASSETS_URL', 'https://cdn.ejemplo.com/assets');
require '.../classes/class.php';
```

---

## 2. ColtmanRegisterPost — Custom Post Types

Registra un CPT completo con todos sus labels generados automáticamente.

```php
new ColtmanRegisterPost(
    array      $labelArgs,   // name, item, domain
    string     $post_name,   // slug del CPT
    array      $args,        // comportamiento
    array      $supports,    // capacidades del editor
    array      $taxonomies,  // taxonomías asociadas
    array|bool $rewrite      // regla de URL o false
);
```

### Backend — CPT público

```php
new ColtmanRegisterPost(
    ['name' => 'Noticias', 'item' => 'Noticia', 'domain' => 'mi-tema'],
    'mi_noticia',
    [
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-megaphone',
        'has_archive'         => 'noticias',
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'show_in_rest'        => true,
        'map_meta_cap'        => true,
    ],
    ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
    ['categoria_noticia'],
    ['slug' => 'noticia', 'with_front' => false]
);
```

### Frontend — loop de posts del CPT

```php
$noticias = new WP_Query([
    'post_type'      => 'mi_noticia',
    'posts_per_page' => 6,
    'post_status'    => 'publish',
]);
while ($noticias->have_posts()) {
    $noticias->the_post();
    echo '<h2><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
}
wp_reset_postdata();
```

---

## 3. ColtmanRegisterTaxonomy — Taxonomías

```php
new ColtmanRegisterTaxonomy(
    array      $config,
    string     $taxonomy_name,
    array      $post_types,
    array|bool $rewrite
);
```

### Backend

```php
new ColtmanRegisterTaxonomy(
    [
        'plural_name'       => 'Materiales',
        'singular_name'     => 'Material',
        'item'              => 'Material',
        'text_domain'       => 'mi-tema',
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_in_rest'      => true,
        'rest_base'         => 'materiales',
    ],
    'joya_material',
    ['anillo_jewelry'],
    ['slug' => 'material', 'with_front' => false]
);
```

### Frontend — términos de una taxonomía

```php
$terms = get_terms(['taxonomy' => 'joya_material', 'hide_empty' => false]);
foreach ($terms as $term) {
    echo '<a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a>';
}
```

---

## 4. ColtmanCreateMetabox — Metaboxes

Crea un panel de campos en el editor de posts/páginas/CPTs.

```php
new ColtmanCreateMetabox([
    'title'      => 'Datos del producto',
    'prefix'     => 'producto_',
    'cpt'        => 'mi_producto',        // string CSV o 'post,page'
    'context'    => 'normal',             // 'normal' | 'side' | 'advanced'
    'priority'   => 'high',
    'fields'     => [ /* ver sección 5 */ ],
]);
```

Los valores se guardan con `update_post_meta()` y se recuperan con `get_post_meta($post_id, $field_id, true)`.

---

## 5. Tipos de campo

Todos los tipos se definen dentro del array `'fields'`.

---

### `text` — Texto simple

**Backend:**
```php
[
    'label'       => 'Título alternativo',
    'id'          => 'alt_title',
    'type'        => 'text',
    'default'     => '',
    'description' => 'Se mostrará en lugar del título principal',
    // 'pattern'  => '[A-Za-z0-9]+',  // validación HTML5 opcional
]
```

**Frontend:**
```php
$titulo = get_post_meta(get_the_ID(), 'alt_title', true);
echo $titulo ? esc_html($titulo) : get_the_title();
```

---

### `textarea` — Área de texto (HTML)

Acepta HTML estructural (`<p>`, `<strong>`, `<table>`, `<div>`…). Guardado con `wp_kses_post()`.

**Backend:**
```php
[
    'label'       => 'Resumen',
    'id'          => 'resumen',
    'type'        => 'textarea',
    'default'     => '',
    'rows'        => 4,
    'placeholder' => 'Escribe un breve resumen…',
]
```

**Frontend:**
```php
$resumen = get_post_meta(get_the_ID(), 'resumen', true);
if ($resumen) {
    echo wp_kses_post($resumen);      // muestra HTML seguro
    // o bien:
    echo wpautop(esc_html($resumen)); // si es texto plano con saltos de línea
}
```

---

### `number` — Número

**Backend:**
```php
[
    'label'   => 'Precio',
    'id'      => 'precio',
    'type'    => 'number',
    'default' => '0',
    'min'     => 0,
    'max'     => 99999,
    'step'    => 0.01,
]
```

**Frontend:**
```php
$precio = (float) get_post_meta(get_the_ID(), 'precio', true);
echo '$' . number_format($precio, 2);
```

---

### `date` — Fecha

**Backend:**
```php
[
    'label'   => 'Fecha del evento',
    'id'      => 'fecha_evento',
    'type'    => 'date',
    'default' => '',
    'min'     => '2024-01-01',
    'max'     => '2030-12-31',
]
```

**Frontend:**
```php
$fecha = get_post_meta(get_the_ID(), 'fecha_evento', true);
if ($fecha) {
    $ts = strtotime($fecha);
    echo date_i18n(get_option('date_format'), $ts); // formato del sitio
}
```

---

### `email` — Correo

**Backend:**
```php
[
    'label'   => 'Email de contacto',
    'id'      => 'email_contacto',
    'type'    => 'email',
    'default' => '',
]
```

**Frontend:**
```php
$email = get_post_meta(get_the_ID(), 'email_contacto', true);
if ($email) {
    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
}
```

---

### `url`

**Backend:**
```php
[
    'label'   => 'Sitio web',
    'id'      => 'website',
    'type'    => 'url',
    'default' => '',
]
```

**Frontend:**
```php
$url = get_post_meta(get_the_ID(), 'website', true);
if ($url) {
    echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">'
        . esc_html($url) . '</a>';
}
```

---

### `color` — Selector de color

Usa `wp-color-picker` nativo de WordPress.

**Backend:**
```php
[
    'label'   => 'Color de acento',
    'id'      => 'color_acento',
    'type'    => 'color',
    'default' => '#2271b1',
]
```

**Frontend:**
```php
$color = get_post_meta(get_the_ID(), 'color_acento', true) ?: '#2271b1';
echo '<style>.producto-header { background-color: ' . esc_attr($color) . '; }</style>';
```

---

### `checkbox` — Casilla

**Backend:**
```php
[
    'label'       => 'Destacado en portada',
    'id'          => 'is_featured',
    'type'        => 'checkbox',
    'checked'     => false,
    'description' => 'Actívalo para mostrarlo en la sección destacados',
]
```

**Frontend:**
```php
$destacado = get_post_meta(get_the_ID(), 'is_featured', true);
if ($destacado === 'on') {
    echo '<span class="badge-featured">⭐ Destacado</span>';
}
```

---

### `select` — Lista desplegable

**Backend:**
```php
[
    'label'   => 'Estado del pedido',
    'id'      => 'estado_pedido',
    'type'    => 'select',
    'default' => 'pendiente',
    'options' => [
        'pendiente'  => 'Pendiente',
        'procesando' => 'Procesando',
        'enviado'    => 'Enviado',
        'entregado'  => 'Entregado',
    ],
]
```

**Frontend:**
```php
$estados = [
    'pendiente'  => 'Pendiente',
    'procesando' => 'Procesando',
    'enviado'    => 'Enviado',
    'entregado'  => 'Entregado',
];
$estado = get_post_meta(get_the_ID(), 'estado_pedido', true);
$label  = $estados[$estado] ?? 'Desconocido';
echo '<span class="estado estado--' . esc_attr($estado) . '">' . esc_html($label) . '</span>';
```

---

### `editor` — WYSIWYG (TinyMCE)

**Backend:**
```php
[
    'label'   => 'Descripción extendida',
    'id'      => 'descripcion_extra',
    'type'    => 'editor',
    'default' => '',
    // 'wpautop'       => true,
    // 'media-buttons' => true,
    // 'teeny'         => false,
    // 'rows'          => 20,
]
```

**Frontend:**
```php
$contenido = get_post_meta(get_the_ID(), 'descripcion_extra', true);
if ($contenido) {
    // Sin filtros — el contenido ya fue sanitizado por wp_filter_post_kses() al guardar
    echo $contenido;

    // Con auto-párrafos (si el usuario escribió texto con saltos de línea)
    // echo wpautop($contenido);

    // Con shortcodes procesados
    // echo do_shortcode($contenido);

    // Con TODA la cadena the_content (wpautop + shortcodes + filtros de plugins)
    // Usar solo cuando se necesiten específicamente hooks externos
    // echo apply_filters('the_content', $contenido);
}
```

---

### `media` — Selector de archivo/imagen

**Backend:**
```php
// Guardar URL
[
    'label'       => 'Imagen de portada',
    'id'          => 'portada_custom',
    'type'        => 'media',
    'return'      => 'url',   // 'url' | 'id'
    'button-text' => 'Seleccionar imagen',
    'modal-title' => 'Elige la portada',
    'default'     => '',
]

// Guardar ID del attachment (para más control)
[
    'label'  => 'PDF del catálogo',
    'id'     => 'catalogo_pdf',
    'type'   => 'media',
    'return' => 'id',
    'default'=> '',
]
```

**Frontend:**
```php
// Con return => 'url'
$url = get_post_meta(get_the_ID(), 'portada_custom', true);
if ($url) {
    echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(get_the_title()) . '">';
}

// Con return => 'id'
$att_id = (int) get_post_meta(get_the_ID(), 'catalogo_pdf', true);
if ($att_id) {
    echo '<a href="' . esc_url(wp_get_attachment_url($att_id)) . '">Descargar catálogo</a>';
    // O mostrar como imagen responsiva:
    echo wp_get_attachment_image($att_id, 'large');
}
```

---

### `gallery` — Galería de imágenes

Cada ítem tiene: alt text editable, campo de URL, botón Upload y drag & drop para reordenar.

**Backend:**
```php
[
    'label'   => 'Galería del producto',
    'id'      => 'galeria_producto',
    'type'    => 'gallery',
    'default' => '',
    // 'button-text' => 'Seleccionar',
    // 'modal-title' => 'Galería',
]
```

**Guardado** — JSON array:
```json
[
  {"id": 123, "url": "https://…/foto.jpg", "alt": "Alt personalizado",
   "title": "Foto 1", "item": "20260528_001", "sizes": {…}, "mime": "image/jpeg",
   "width": 1200, "height": 800}
]
```

---

### `list` — Lista de texto

Cada ítem tiene un textarea, drag & drop para reordenar, y botón add/remove. Similar a `gallery` pero con texto en lugar de imágenes.

**Backend:**
```php
[
    'label'   => 'Lista de contenidos',
    'id'      => 'lista_contenidos',
    'type'    => 'list',
    'default' => '',
]
```

**Guardado** — JSON array:
```json
[
  {"item": "20260528_001", "text": "Contenido del primer ítem"},
  {"item": "20260528_002", "text": "Contenido del segundo ítem"}
]
```

**Frontend:**
```php
$json  = get_post_meta(get_the_ID(), 'lista_contenidos', true);
$items = $json ? json_decode($json) : [];

if ($items) : ?>
<ul class="lista">
    <?php foreach ($items as $item) : ?>
    <li><?php echo esc_html($item->text); ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
```

**Frontend:**
```php
$json     = get_post_meta(get_the_ID(), 'galeria_producto', true);
$imagenes = $json ? json_decode($json) : [];

if ($imagenes) : ?>
<div class="galeria">
    <?php foreach ($imagenes as $img) : ?>
    <figure class="galeria__item">
        <img src="<?php echo esc_url($img->url); ?>"
             alt="<?php echo esc_attr($img->alt); ?>"
             width="<?php echo (int) $img->width; ?>"
             height="<?php echo (int) $img->height; ?>">
        <?php if (!empty($img->title)) : ?>
        <figcaption><?php echo esc_html($img->title); ?></figcaption>
        <?php endif; ?>
    </figure>
    <?php endforeach; ?>
</div>
<?php endif; ?>
```

**Frontend — usando `wp_get_attachment_image` para responsividad:**
```php
foreach ($imagenes as $img) {
    if ($img->id) {
        echo wp_get_attachment_image($img->id, 'large', false, [
            'alt' => esc_attr($img->alt),
        ]);
    } else {
        echo '<img src="' . esc_url($img->url) . '" alt="' . esc_attr($img->alt) . '">';
    }
}
```

---

### `accordion` — Ítems repetibles (FAQ, pasos…)

Editor WYSIWYG propio (contenteditable) con soporte HTML completo, drag & drop y botón de imagen opcional.

**Backend:**
```php
// FAQ — sin imagen
[
    'label'     => 'Preguntas frecuentes',
    'id'        => 'faqs',
    'type'      => 'accordion',
    'add_image' => 'false',   // string 'false', no bool false
    'default'   => '',
]

// Testimonios — con imagen
[
    'label'   => 'Testimonios',
    'id'      => 'testimonios',
    'type'    => 'accordion',
    'default' => '',
]
```

**Guardado** — JSON array:
```json
[
  {"id": "faqs_1234_parent", "title": "¿Cuánto tarda?",
   "content": "<p>Entre <strong>2 y 4 semanas</strong>.</p>", "image": ""}
]
```

**Frontend — FAQ con `<details>`:**
```php
$json  = get_post_meta(get_the_ID(), 'faqs', true);
$items = $json ? json_decode($json) : [];
?>
<dl class="faq">
<?php foreach ($items as $item) : ?>
    <dt><?php echo esc_html($item->title); ?></dt>
    <dd><?php echo wp_kses_post($item->content); ?></dd>
<?php endforeach; ?>
</dl>
```

**Frontend — testimonios con imagen:**
```php
foreach ($items as $item) {
    echo '<div class="testimonio">';
    if (!empty($item->image)) {
        echo '<img src="' . esc_url($item->image) . '" alt="">';
    }
    echo '<blockquote>' . wp_kses_post($item->content) . '</blockquote>';
    echo '<cite>' . esc_html($item->title) . '</cite>';
    echo '</div>';
}
```

---

### `repeater` — Filas repetibles configurables

Tabla de filas donde cada fila tiene los sub-campos que definas. Drag & drop para reordenar.

**Backend:**
```php
[
    'label'      => 'Características',
    'id'         => 'caracteristicas',
    'type'       => 'repeater',
    'default'    => '',
    'sub_fields' => [
        ['id' => 'icono',       'type' => 'text',     'label' => 'Icono (clase CSS)'],
        ['id' => 'titulo',      'type' => 'text',     'label' => 'Título'],
        ['id' => 'descripcion', 'type' => 'textarea', 'label' => 'Descripción'],
    ],
]
```

**Sub-campos disponibles:** `text`, `textarea`, `number`, `email`, `url`, `select`, `checkbox`, `color`, `media`.

**Guardado** — JSON array de filas:
```json
[
  {"icono": "dashicons-star-filled", "titulo": "Calidad premium", "descripcion": "Materiales seleccionados"},
  {"icono": "dashicons-awards",      "titulo": "Garantía 2 años", "descripcion": "Reparaciones sin costo"}
]
```

**Frontend:**
```php
$json  = get_post_meta(get_the_ID(), 'caracteristicas', true);
$filas = $json ? json_decode($json, true) : [];
?>
<ul class="caracteristicas">
<?php foreach ($filas as $fila) : ?>
    <li>
        <span class="<?php echo esc_attr($fila['icono'] ?? ''); ?>"></span>
        <strong><?php echo esc_html($fila['titulo'] ?? ''); ?></strong>
        <p><?php echo wp_kses_post($fila['descripcion'] ?? ''); ?></p>
    </li>
<?php endforeach; ?>
</ul>
```

---

### `relationship` / `get_posts` — Posts relacionados

`<select multiple>` con búsqueda AJAX en tiempo real (Select2). `get_posts` es un alias de `relationship` que mantiene compatibilidad con código previo.

**Backend:**
```php
// Relacionar con un solo CPT
[
    'label'     => 'Productos relacionados',
    'id'        => 'productos_relacionados',
    'type'      => 'relationship',
    'post_type' => 'mi_producto',
    'default'   => '[]',
]

// Relacionar con múltiples CPTs (string CSV o array)
[
    'label'     => 'Contenido relacionado',
    'id'        => 'contenido_rel',
    'type'      => 'relationship',
    'post_type' => 'post,page,mi_noticia',   // CSV
    // 'post_type' => ['post', 'page'],        // o array
    'default'   => '[]',
]
```

**Guardado:** JSON array de IDs: `"[45, 67, 89]"`

**Frontend:**
```php
$json = get_post_meta(get_the_ID(), 'productos_relacionados', true);
$ids  = $json ? json_decode($json) : [];

if (!empty($ids)) :
    $relacionados = get_posts([
        'post__in'       => array_map('intval', $ids),
        'post_type'      => 'mi_producto',
        'posts_per_page' => -1,
        'orderby'        => 'post__in',  // respetar el orden guardado
    ]);
    ?>
    <div class="productos-relacionados">
        <?php foreach ($relacionados as $rel) : ?>
        <a href="<?php echo get_permalink($rel->ID); ?>">
            <?php echo get_the_post_thumbnail($rel->ID, 'thumbnail'); ?>
            <span><?php echo esc_html($rel->post_title); ?></span>
        </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

---

### `get_terms` — Términos de taxonomía

`<select>` con búsqueda AJAX (Select2). **Multiple selección activada por defecto.**

**Backend:**
```php
// Selección múltiple (por defecto)
[
    'label'    => 'Materiales',
    'id'       => 'materiales',
    'type'     => 'get_terms',
    'taxonomy' => 'joya_material',
    'default'  => '[]',
    // 'placeholder' => 'Busca un material…',
]

// Selección única (desactivar explícitamente)
[
    'label'    => 'Categoría principal',
    'id'       => 'categoria_principal',
    'type'     => 'get_terms',
    'taxonomy' => 'category',
    'multiple' => false,
    'default'  => '',
]

// Múltiples taxonomías en un solo campo
[
    'label'    => 'Clasificación',
    'id'       => 'clasificacion',
    'type'     => 'get_terms',
    'taxonomy' => 'joya_material,joya_estilo',
    'default'  => '[]',
]
```

**Guardado:**
- Multiple (defecto): JSON array de IDs `"[3, 7, 12]"`
- Único (`multiple: false`): string con el ID `"3"`

**Frontend — múltiple:**
```php
$json = get_post_meta(get_the_ID(), 'materiales', true);
$ids  = $json ? json_decode($json) : [];

foreach ($ids as $term_id) {
    $term = get_term((int) $term_id, 'joya_material');
    if ($term && !is_wp_error($term)) {
        echo '<a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a>';
    }
}
```

**Frontend — único:**
```php
$term_id = get_post_meta(get_the_ID(), 'categoria_principal', true);
if ($term_id) {
    $term = get_term((int) $term_id, 'category');
    if ($term && !is_wp_error($term)) {
        echo esc_html($term->name);
    }
}
```

---

### `group` — Agrupador de campos

Un único panel colapsable que agrupa varios campos relacionados. Los sub-campos se guardan en meta keys individuales, como si fueran campos independientes.

Tiene dos modos que se pueden combinar:
- **Campos estáticos** — definidos en PHP con `'fields'`.
- **Campos dinámicos** — añadidos desde el backoffice vía el panel ⚙ "Manage fields" (sin tocar código). El esquema dinámico se guarda en `wp_options` y es global para todos los posts con ese grupo.

**Backend:**
```php
[
    'label'       => 'SEO',
    'id'          => 'seo_group',
    'type'        => 'group',
    'description' => 'Metadatos para motores de búsqueda',
    'fields'      => [
        ['id' => 'seo_title',       'type' => 'text',     'label' => 'Título SEO'],
        ['id' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'rows' => 3],
        ['id' => 'seo_image',       'type' => 'media',    'label' => 'Imagen OG', 'return' => 'url'],
        ['id' => 'seo_noindex',     'type' => 'checkbox', 'label' => 'No indexar esta página'],
    ],
]
```

> Para añadir campos dinámicos desde el admin, hacer clic en ⚙ "Manage fields" dentro del grupo.

**Frontend — cada sub-campo se lee con su propia meta key:**
```php
$seo_title = get_post_meta(get_the_ID(), 'seo_title', true)
             ?: get_the_title();
$seo_desc  = get_post_meta(get_the_ID(), 'seo_description', true);
$seo_img   = get_post_meta(get_the_ID(), 'seo_image', true);
$noindex   = get_post_meta(get_the_ID(), 'seo_noindex', true) === 'on';
?>
<title><?php echo esc_html($seo_title); ?></title>
<meta name="description" content="<?php echo esc_attr($seo_desc); ?>">
<?php if ($noindex) : ?>
<meta name="robots" content="noindex,nofollow">
<?php endif; ?>
<?php if ($seo_img) : ?>
<meta property="og:image" content="<?php echo esc_url($seo_img); ?>">
<?php endif; ?>
```

---

### `map` — Selector de coordenadas

Mapa Leaflet interactivo. Clic para colocar marcador, draggable, sincronización en tiempo real.

**Backend:**
```php
[
    'label'    => 'Ubicación',
    'id'       => 'ubicacion',
    'type'     => 'map',
    'zoom'     => 13,    // zoom por defecto cuando no hay coordenadas
    'default'  => '',
]
```

**Guardado:** `{"lat": 4.7109886, "lng": -74.0720887, "zoom": 14}`

**Frontend — mapa con Leaflet:**
```php
$coords_json = get_post_meta(get_the_ID(), 'ubicacion', true);
$coords      = $coords_json ? json_decode($coords_json, true) : null;

if ($coords) :
    wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9/dist/leaflet.css' );
    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9/dist/leaflet.js', [], null, true);
    ?>
    <div id="mapa-tienda" style="height:350px"></div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var map = L.map('mapa-tienda').setView(
            [<?php echo (float) $coords['lat']; ?>, <?php echo (float) $coords['lng']; ?>],
            <?php echo (int) $coords['zoom']; ?>
        );
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        L.marker([<?php echo (float) $coords['lat']; ?>, <?php echo (float) $coords['lng']; ?>])
         .addTo(map)
         .bindPopup('<?php echo esc_js(get_the_title()); ?>');
    });
    </script>
<?php endif; ?>
```

**Frontend — solo latitud/longitud para un mapa externo (Google Maps):**
```php
$coords = json_decode(get_post_meta(get_the_ID(), 'ubicacion', true), true);
if ($coords) {
    $url = "https://www.google.com/maps?q={$coords['lat']},{$coords['lng']}";
    echo '<a href="' . esc_url($url) . '" target="_blank">Ver en Google Maps</a>';
}
```

---

## 6. ColtmanTermMeta — Campos en términos

Agrega campos personalizados a las pantallas de crear/editar términos de taxonomía.

> **Diferencia con `ColtmanCreateMetabox`:** el array `'fields'` usa las **claves** del array como IDs (no la propiedad `'id'` dentro del campo).

```php
new ColtmanTermMeta([
    'taxonomy' => 'joya_material',
    'title'    => 'Información del material',
    'fields'   => [
        'mat_descripcion' => [
            'label'   => 'Descripción',
            'type'    => 'textarea',
            'default' => '',
        ],
        'mat_imagen' => [
            'label'       => 'Imagen representativa',
            'type'        => 'media',
            'return'      => 'url',
            'button-text' => 'Seleccionar',
            'modal-title' => 'Imagen del material',
            'default'     => '',
        ],
        'mat_color' => [
            'label'   => 'Color representativo',
            'type'    => 'color',
            'default' => '#c0a060',
        ],
        'mat_activo' => [
            'label'       => 'Visible en catálogo',
            'type'        => 'checkbox',
            'description' => 'Desmarcar para ocultar del filtro',
        ],
    ],
]);
```

**Frontend:**
```php
// En un template de archivo de taxonomía
$term = get_queried_object();

$descripcion = get_term_meta($term->term_id, 'mat_descripcion', true);
$imagen      = get_term_meta($term->term_id, 'mat_imagen', true);
$color       = get_term_meta($term->term_id, 'mat_color', true) ?: '#cccccc';
$activo      = get_term_meta($term->term_id, 'mat_activo', true) === 'on';

if ($imagen) {
    echo '<img src="' . esc_url($imagen) . '" alt="' . esc_attr($term->name) . '">';
}
echo '<h1 style="color:' . esc_attr($color) . '">' . esc_html($term->name) . '</h1>';
echo wp_kses_post($descripcion);

// Desde un post: obtener meta de los términos asignados
$terms = get_the_terms(get_the_ID(), 'joya_material');
if ($terms) {
    foreach ($terms as $t) {
        $color = get_term_meta($t->term_id, 'mat_color', true);
        echo '<span style="background:' . esc_attr($color) . '">'
             . esc_html($t->name) . '</span>';
    }
}
```

---

## 7. ColtmanCreateUserMeta — Campos en usuarios

Agrega campos personalizados al perfil de usuario en el admin.

```php
new ColtmanCreateUserMeta([
    'title'  => 'Información profesional',
    'fields' => [
        [
            'label'   => 'Especialidad',
            'id'      => 'user_especialidad',
            'type'    => 'text',
            'default' => '',
        ],
        [
            'label'       => 'Foto de perfil',
            'id'          => 'user_foto',
            'type'        => 'media',
            'return'      => 'url',
            'button-text' => 'Seleccionar foto',
            'modal-title' => 'Foto de perfil',
            'default'     => '',
        ],
        [
            'label'   => 'Biografía',
            'id'      => 'user_bio_custom',
            'type'    => 'textarea',
            'default' => '',
            'rows'    => 4,
        ],
        [
            'label'   => 'Rol visible',
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
            'label'       => 'Perfil verificado',
            'id'          => 'user_verificado',
            'type'        => 'checkbox',
            'description' => 'Actívalo cuando el perfil haya sido revisado',
        ],
    ],
]);
```

**Frontend:**
```php
// En single.php — datos del autor del post
$user_id     = get_the_author_meta('ID');
$foto        = get_user_meta($user_id, 'user_foto', true);
$bio         = get_user_meta($user_id, 'user_bio_custom', true);
$especialidad = get_user_meta($user_id, 'user_especialidad', true);
$verificado  = get_user_meta($user_id, 'user_verificado', true) === 'on';
?>
<div class="autor-card">
    <?php if ($foto) : ?>
    <img src="<?php echo esc_url($foto); ?>" alt="<?php the_author(); ?>" class="autor-foto">
    <?php endif; ?>
    <div class="autor-info">
        <strong><?php the_author(); ?></strong>
        <?php if ($verificado) : ?>
        <span class="verificado" title="Perfil verificado">✓</span>
        <?php endif; ?>
        <?php if ($especialidad) : ?>
        <em><?php echo esc_html($especialidad); ?></em>
        <?php endif; ?>
        <?php if ($bio) : ?>
        <p><?php echo wp_kses_post($bio); ?></p>
        <?php endif; ?>
    </div>
</div>
```

---

## 8. Soporte REST API y Gutenberg

Cualquier campo puede exponerse en la REST API y en el panel lateral de Gutenberg añadiendo `'rest' => true` en su definición.

### Backend — activar campo en REST

```php
new ColtmanCreateMetabox([
    'title'  => 'SEO',
    'prefix' => 'seo_',
    'cpt'    => 'mi_producto',
    'fields' => [
        ['id' => 'seo_title',       'type' => 'text',     'label' => 'Título SEO',     'rest' => true],
        ['id' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description','rest' => true],
        ['id' => 'precio',          'type' => 'number',   'label' => 'Precio',          'rest' => true],
        // Campos sin 'rest' => true NO se exponen en la API
    ],
]);
```

Lo que ocurre automáticamente:
- `register_post_meta()` es llamado en el hook `init` con `show_in_rest: true`.
- El panel ⚙ "Coltman Fields" aparece en la barra lateral de Gutenberg con controles para editar los campos directamente desde el editor de bloques.
- Tipos complejos (`gallery`, `repeater`, `group`…) muestran un aviso informativo — editarlos desde el metabox clásico.

### Frontend — leer desde la REST API

```bash
GET /wp-json/wp/v2/mi_producto/123
```

```json
{
  "id": 123,
  "title": { "rendered": "Anillo de oro" },
  "meta": {
    "seo_title": "Anillo de oro 18k | Mi Joyería",
    "seo_description": "Anillo artesanal…",
    "precio": "890"
  }
}
```

```js
// En JavaScript / React
fetch('/wp-json/wp/v2/mi_producto/123')
  .then(r => r.json())
  .then(post => {
      console.log(post.meta.seo_title);
      console.log(post.meta.precio);
  });
```

---

## 9. Utilidades

### `coltman_trim_content_text_fn()` — Recortar texto

```php
$resumen = coltman_trim_content_text_fn(get_the_content(), 30, '…');
// → "Las primeras treinta palabras del contenido…"
```

### `formaturltext()` — Texto seguro para URL

```php
$slug = formaturltext('Joyería & Diseño Único');
// → "Joyeria+Diseno+Unico"

// Uso típico: construir una URL de búsqueda
$url = home_url('/buscar/?q=' . formaturltext($busqueda));
```

### `get_estimated_reading_time()` — Tiempo de lectura

```php
$tiempo = get_estimated_reading_time([
    'post'          => get_the_ID(),
    'wpm'           => 200,
    'single_suffix' => 'minuto',
    'plural_suffix' => 'minutos',
]);
echo "Lectura: {$tiempo->time} {$tiempo->suffix}"; // → "Lectura: 4 minutos"
```

### `get_extracted_headings_array()` — Tabla de contenidos

```php
<?php
$headings = get_extracted_headings_array(); // extraer antes de the_content()
?>
<?php if ($headings) : ?>
<nav class="tabla-contenidos">
    <ol>
    <?php foreach ($headings as $h) : ?>
        <li><a href="#<?php echo esc_attr($h->id); ?>"><?php echo esc_html($h->text); ?></a></li>
    <?php endforeach; ?>
    </ol>
</nav>
<?php endif; ?>
<?php the_content(); // los headings ya tendrán sus IDs inyectados ?>
```

---

## 10. Leer valores en el frontend — resumen

```php
$id = get_the_ID();

// ── Tipos simples ──────────────────────────────────────────────
$texto    = get_post_meta($id, 'campo_text',     true);
$num      = (float) get_post_meta($id, 'campo_number', true);
$fecha    = get_post_meta($id, 'campo_date',     true); // 'YYYY-MM-DD'
$email    = get_post_meta($id, 'campo_email',    true);
$url      = get_post_meta($id, 'campo_url',      true);
$color    = get_post_meta($id, 'campo_color',    true); // '#rrggbb'
$select   = get_post_meta($id, 'campo_select',   true); // valor de la opción
$checkbox = get_post_meta($id, 'campo_checkbox', true) === 'on';

// ── HTML ───────────────────────────────────────────────────────
$textarea  = get_post_meta($id, 'campo_textarea', true);
echo wp_kses_post($textarea); // HTML seguro

$editor  = get_post_meta($id, 'campo_editor', true);
echo $editor;                                       // sanitizado en el guardado — echo directo es seguro
// echo wpautop($editor);                           // + auto-párrafos
// echo do_shortcode($editor);                      // + shortcodes
// echo apply_filters('the_content', $editor);      // + todos los filtros WP/plugins

// ── Media ──────────────────────────────────────────────────────
$media_url = get_post_meta($id, 'campo_media_url', true);
echo '<img src="' . esc_url($media_url) . '">';

$media_id  = (int) get_post_meta($id, 'campo_media_id', true);
echo wp_get_attachment_image($media_id, 'large');

// ── Gallery ────────────────────────────────────────────────────
$imagenes = json_decode(get_post_meta($id, 'campo_gallery', true) ?: '[]');
foreach ($imagenes as $img) {
    echo '<img src="' . esc_url($img->url) . '" alt="' . esc_attr($img->alt) . '">';
}

// ── Accordion ─────────────────────────────────────────────────
$items = json_decode(get_post_meta($id, 'campo_accordion', true) ?: '[]');
foreach ($items as $item) {
    echo '<h3>' . esc_html($item->title) . '</h3>';
    echo wp_kses_post($item->content);
}

// ── Repeater ─────────────────────────────────────────────────
$filas = json_decode(get_post_meta($id, 'campo_repeater', true) ?: '[]', true);
foreach ($filas as $fila) {
    echo esc_html($fila['mi_subfield'] ?? '');
}

// ── Relationship / get_posts ───────────────────────────────────
$ids  = json_decode(get_post_meta($id, 'campo_relationship', true) ?: '[]');
$posts = get_posts(['post__in' => $ids, 'post_type' => 'any', 'posts_per_page' => -1]);
foreach ($posts as $p) {
    echo '<a href="' . get_permalink($p->ID) . '">' . esc_html($p->post_title) . '</a>';
}

// ── get_terms (multiple — defecto) ────────────────────────────
$term_ids = json_decode(get_post_meta($id, 'campo_terms', true) ?: '[]');
foreach ($term_ids as $tid) {
    $t = get_term((int) $tid);
    echo $t ? esc_html($t->name) : '';
}

// ── get_terms (único: multiple => false) ──────────────────────
$term_id = get_post_meta($id, 'campo_term_unico', true);
$term    = $term_id ? get_term((int) $term_id) : null;
echo $term ? esc_html($term->name) : '';

// ── Group ─────────────────────────────────────────────────────
// Cada sub-campo del group tiene su propia meta key:
$seo_title = get_post_meta($id, 'seo_title', true);
$seo_desc  = get_post_meta($id, 'seo_description', true);

// ── Map ───────────────────────────────────────────────────────
$coords = json_decode(get_post_meta($id, 'ubicacion', true) ?: '{}', true);
$lat    = $coords['lat']  ?? null;
$lng    = $coords['lng']  ?? null;
$zoom   = $coords['zoom'] ?? 13;
```

---

## 11. Ejemplo de uso completo: ficha de producto

### Backend — `include/features/producto/metabox.php`

```php
new ColtmanCreateMetabox([
    'title'   => 'Datos del producto',
    'prefix'  => 'prod_',
    'cpt'     => 'mi_producto',
    'context' => 'normal',
    'fields'  => [
        // ── Precios ──────────────────────────────────────────
        [
            'id'      => 'precio_base',
            'label'   => 'Precio base (USD)',
            'type'    => 'number',
            'min'     => 0,
            'step'    => 0.01,
            'default' => '0',
            'rest'    => true,
        ],
        [
            'id'          => 'en_oferta',
            'label'       => '¿En oferta?',
            'type'        => 'checkbox',
            'description' => 'Actívalo para mostrar el precio de oferta',
        ],
        [
            'id'    => 'precio_oferta',
            'label' => 'Precio de oferta (USD)',
            'type'  => 'number',
            'min'   => 0,
            'step'  => 0.01,
        ],

        // ── Medios ───────────────────────────────────────────
        [
            'id'          => 'imagen_principal',
            'label'       => 'Imagen principal',
            'type'        => 'media',
            'return'      => 'id',
            'button-text' => 'Seleccionar imagen',
            'modal-title' => 'Imagen principal del producto',
        ],
        [
            'id'    => 'galeria',
            'label' => 'Galería de fotos',
            'type'  => 'gallery',
        ],

        // ── Clasificación ─────────────────────────────────────
        [
            'id'       => 'materiales',
            'label'    => 'Materiales',
            'type'     => 'get_terms',
            'taxonomy' => 'joya_material',
        ],
        [
            'id'       => 'estado',
            'label'    => 'Estado',
            'type'     => 'select',
            'default'  => 'activo',
            'options'  => [
                'activo'   => 'Disponible',
                'agotado'  => 'Agotado',
                'oculto'   => 'Oculto',
            ],
        ],

        // ── Características ───────────────────────────────────
        [
            'id'         => 'caracteristicas',
            'label'      => 'Características',
            'type'       => 'repeater',
            'sub_fields' => [
                ['id' => 'icono',  'type' => 'text', 'label' => 'Icono (dashicons)'],
                ['id' => 'texto',  'type' => 'text', 'label' => 'Descripción'],
            ],
        ],

        // ── SEO ───────────────────────────────────────────────
        [
            'id'     => 'seo',
            'label'  => 'SEO',
            'type'   => 'group',
            'fields' => [
                ['id' => 'seo_title', 'type' => 'text',     'label' => 'Título', 'rest' => true],
                ['id' => 'seo_desc',  'type' => 'textarea', 'label' => 'Descripción', 'rows' => 2],
            ],
        ],

        // ── Ubicación ─────────────────────────────────────────
        [
            'id'    => 'fabricacion_lugar',
            'label' => 'Lugar de fabricación',
            'type'  => 'map',
            'zoom'  => 12,
        ],
    ],
]);
```

### Frontend — `single-mi_producto.php`

```php
<?php get_header(); ?>

<?php while (have_posts()) : the_post();
    $pid = get_the_ID();

    // Leer todos los campos
    $precio_base   = (float) get_post_meta($pid, 'precio_base', true);
    $en_oferta     = get_post_meta($pid, 'en_oferta', true) === 'on';
    $precio_oferta = (float) get_post_meta($pid, 'precio_oferta', true);
    $imagen_id     = (int) get_post_meta($pid, 'imagen_principal', true);
    $galeria       = json_decode(get_post_meta($pid, 'galeria', true) ?: '[]');
    $materiales    = json_decode(get_post_meta($pid, 'materiales', true) ?: '[]');
    $estado        = get_post_meta($pid, 'estado', true) ?: 'activo';
    $caracteristicas = json_decode(get_post_meta($pid, 'caracteristicas', true) ?: '[]', true);
    $seo_title     = get_post_meta($pid, 'seo_title', true) ?: get_the_title();
    $coords        = json_decode(get_post_meta($pid, 'fabricacion_lugar', true) ?: '{}', true);
?>

<article class="producto">

    <!-- Imagen principal -->
    <div class="producto__imagen">
        <?php if ($imagen_id) : ?>
        <?php echo wp_get_attachment_image($imagen_id, 'large', false, ['class' => 'producto__img']); ?>
        <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="producto__info">
        <h1><?php the_title(); ?></h1>

        <!-- Precio -->
        <div class="producto__precio">
            <?php if ($en_oferta && $precio_oferta > 0) : ?>
            <del>$<?php echo number_format($precio_base, 2); ?></del>
            <strong>$<?php echo number_format($precio_oferta, 2); ?></strong>
            <?php else : ?>
            <strong>$<?php echo number_format($precio_base, 2); ?></strong>
            <?php endif; ?>
        </div>

        <!-- Estado -->
        <?php if ($estado === 'agotado') : ?>
        <p class="producto__agotado">Agotado temporalmente</p>
        <?php endif; ?>

        <!-- Materiales -->
        <?php if ($materiales) : ?>
        <p class="producto__materiales">
            <?php foreach ($materiales as $tid) :
                $t = get_term((int) $tid, 'joya_material');
                if ($t && !is_wp_error($t)) :
            ?>
            <a href="<?php echo get_term_link($t); ?>"><?php echo esc_html($t->name); ?></a>
            <?php endif; endforeach; ?>
        </p>
        <?php endif; ?>

        <!-- Características -->
        <?php if ($caracteristicas) : ?>
        <ul class="producto__caracteristicas">
            <?php foreach ($caracteristicas as $c) : ?>
            <li>
                <span class="<?php echo esc_attr($c['icono'] ?? ''); ?>"></span>
                <?php echo esc_html($c['texto'] ?? ''); ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <!-- Descripción del post -->
        <div class="producto__descripcion">
            <?php the_content(); ?>
        </div>
    </div>

    <!-- Galería -->
    <?php if ($galeria) : ?>
    <div class="producto__galeria">
        <?php foreach ($galeria as $img) : ?>
        <figure>
            <img src="<?php echo esc_url($img->url); ?>"
                 alt="<?php echo esc_attr($img->alt); ?>"
                 loading="lazy">
        </figure>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Mapa de fabricación -->
    <?php if (!empty($coords['lat'])) : ?>
    <div class="producto__mapa">
        <h3>Lugar de fabricación</h3>
        <div id="mapa-prod" style="height:300px"></div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var m = L.map('mapa-prod').setView(
                [<?php echo (float) $coords['lat']; ?>, <?php echo (float) $coords['lng']; ?>],
                <?php echo (int) ($coords['zoom'] ?? 13); ?>
            );
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(m);
            L.marker([<?php echo (float) $coords['lat']; ?>, <?php echo (float) $coords['lng']; ?>]).addTo(m);
        });
        </script>
    </div>
    <?php endif; ?>

</article>

<?php endwhile; ?>
<?php get_footer(); ?>
```

---

## 12. Estructura de archivos

```
classes/
├── class.php                    ← Loader + constantes + i18n
├── class-post-types.php         ← ColtmanRegisterPost
├── class-taxonomy.php           ← ColtmanRegisterTaxonomy
├── class-metabox.php            ← ColtmanCreateMetabox
├── class-termeta.php            ← ColtmanTermMeta
├── class-usermetabox.php        ← ColtmanCreateUserMeta
├── input-fields.php             ← ColtmanInputFields (renderizado)
├── ajax.php                     ← Handlers wp_ajax_* (relationship, terms, group schema)
├── readme.md                    ← Esta guía
├── context.md                   ← Arquitectura técnica detallada
├── CHANGELOG.md                 ← Historial de versiones
├── assets/
│   ├── css/
│   │   └── admin.css            ← Estilos del admin (encolado)
│   ├── js/
│   │   ├── media.js             ← JS del admin: todos los campos interactivos
│   │   └── gutenberg-panel.js   ← Panel sidebar Gutenberg (sin build step)
│   └── libs/
│       ├── select2/             ← Select2 v4.0.13 (local)
│       └── leaflet/             ← Leaflet v1.9.4 (local)
├── docs/
│   ├── roadmap.md
│   ├── mejoras.md
│   └── issues-y-soluciones.md
├── languages/
│   ├── coltman.pot
│   ├── coltman-es_ES.po / .mo
│   └── coltman-nl_NL.po / .mo
├── tests/
│   ├── bootstrap.php
│   ├── TestCase.php
│   ├── Stubs/wordpress.php
│   └── Unit/
│       ├── RegisterPostTest.php       (18 tests)
│       ├── RegisterTaxonomyTest.php   (19 tests)
│       ├── CreateMetaboxTest.php      (36 tests)
│       ├── InputFieldsTest.php        (68 tests)
│       ├── TermMetaTest.php           (26 tests)
│       ├── UserMetaTest.php           (29 tests)
│       └── Utils/
│           ├── UtilsTest.php          (9 tests)
│           ├── ReadTimeTest.php       (13 tests)
│           └── NavigationsAnchorsTest.php (21 tests)
└── utils/
    ├── utils.php
    ├── read-time.php
    ├── navigations_archors.php
    └── optimizations/remove_scripts.php
```

---

## 13. Seguridad

El framework implementa las siguientes medidas de seguridad:

| Capa | Medida |
|---|---|
| `save_post` | Verificación de nonce + `current_user_can('edit_post')` |
| `wpturbo_save_meta_fields` | `current_user_can('manage_categories')` |
| `save_user_meta` | Nonce + `current_user_can('edit_user', $user_id)` |
| AJAX | `check_ajax_referer()` + capacidad mínima en cada handler |
| Salida HTML | `esc_html()`, `esc_attr()`, `esc_url()`, `esc_textarea()` en todos los renders |
| Guardado texto | `sanitize_text_field()`, `sanitize_email()`, `esc_url_raw()` |
| Guardado HTML | `wp_kses_post()` — permite HTML seguro, bloquea scripts |
| Guardado editor | `wp_filter_post_kses()` |
| Guardado mapa | Validación de rangos lat ±90 / lng ±180 |
| XSS en labels | `esc_html()` en labels de campos; `esc_attr()` en atributos |
| CSRF en group schema | Nonce `coltman_group_schema` + `manage_options` |

---

## 14. Pruebas unitarias

Suite PHPUnit 10 — no requiere WordPress instalado.

```bash
cd classes/
composer install
php vendor/bin/phpunit
# → OK (253 tests, 464 assertions)
```

### Sistema de spy — helpers disponibles

```php
// Forzar retorno de una función WP
$this->setStub('get_post_meta', 'valor_de_prueba');

// Controlar flags de contexto
$this->setFlag('is_admin', true);
$this->setFlag('metadata_exists', true);
$this->setFlag('current_user_can', false); // simular sin permisos

// Verificar llamadas
$calls = $this->spyCalls('update_post_meta');
$this->assertNotEmpty($calls);
$this->assertSame('mi_campo', $calls[0]['key']);

// Capturar output de echo
$html = $this->capture(fn() => $this->f->input(['id' => 'x', 'type' => 'text'], 'val'));
$this->assertStringContainsString('value="val"', $html);
```
