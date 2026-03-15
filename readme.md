# Clases útiles para wordpress

## Este proyecto posee herramientas practicas para desarrollar aplicaciones y métodos de WordPress para desarrollar plugins y temas.
****

Actualmente esta colección permite manejar los siguientes puntos:

1. **Cajas de meta-contenido**
2. **Contenidos personalizados**
3. **Taxonomías**
4. **Términos personalizados**
## Actualización
1. Se creo input field y se inicio el proceso de integración y unificación de los campos en "ColtmanTermMeta" y "ColtmanCreateMetabox" mediante la clase "ColtmanInputFields"
2. El desarrollo del campo accordion 


## Guía de uso: `ColtmanCreateMetabox`

Ejemplo (basado en el patrón usado en `includes/features/posts/postmeta.php`):

```php
// Definir configuración similar a override_post_writer
$config = [
	'title' => __('Override Post Writer Info', 'ing'),
	'description' => __('Change the post writer information for this post.', 'ing'),
	'prefix' => 'ing_override_post_writer_',
	'domain' => 'ing_override_post_writer',
	'class_name' => 'ing',
	'context' => 'normal', // 'normal'|'side'|'advanced'
	'priority' => 'high',
	'cpt' => 'post',
	'fields' => [
		[ 'label' => __('Override Post Writer', 'ing'), 'id' => 'override_post_writer', 'type' => 'text', 'default' => '' ],
		[ 'label' => __('Override Post Writer Job Title', 'ing'), 'id' => 'override_post_writer_job_title', 'type' => 'text', 'default' => '' ],
		[ 'label' => __('Override Post Writer Description', 'ing'), 'id' => 'override_post_writer_desc', 'type' => 'textarea', 'default' => '' ],
		[ 'label' => __('Override Post Writer Image', 'ing'), 'id' => 'override_post_writer_image', 'type' => 'media', 'return' => 'url', 'button-text' => __('Select Image', 'ing'), 'modal-title' => __('Choose Image', 'ing'), 'default' => '' ],
	]
];

new ColtmanCreateMetabox($config);
```

Descripción de la configuración
- `title`, `description`: texto visible en el metabox.
- `prefix`: prefijo para la metabox/key.
- `domain`: text domain para internacionalización.
- `class_name`: clase CSS opcional.
- `context`: ubicación del metabox (`normal`, `side`, `advanced`). Nota: Gutenberg puede ocultar `side`.
- `priority`: prioridad (ej. `high`).
- `cpt`: post type(s) objetivo (puede ser cadena con comas).
- `fields`: array de campos. Cada campo puede incluir `label`, `id`, `type`, `default`, `description`, `return`, `button-text`, `modal-title`, etc.

Propiedades públicas/privadas
- `private $config`: configuración completa.
- `public $coltmanInputs`: instancia de `ColtmanInputFields` usada para renderizar campos.

Métodos públicos y de ayuda
- `__construct($config)`: inicializa, normaliza CPTs, crea `ColtmanInputFields` si está disponible, registra hooks (`add_meta_boxes`, `admin_enqueue_scripts`, `admin_head`, `save_post`).
- `process_cpts()`: normaliza `cpt` en `post-type` (acepta lista separada por comas).
- `add_meta_boxes()`: registra el metabox con `add_meta_box()`.
- `admin_enqueue_scripts()`: encola `wp_enqueue_media`, `wp-color-picker`, Select2 y otros assets cuando se edita el post type objetivo.
- `admin_head()`: inyecta scripts y estilos inline necesarios (incluye referencia a `classes/assets/js/media.js`).
- `save_post($post_id)`: persiste los valores de `$this->config['fields']` en post meta. Maneja tipos específicos y aplica sanitización parcial (`editor`, `email`).
- `add_meta_box_callback()`: callback que llama a `fields_table()` para renderizar la UI.

Métodos privados de renderizado
- `fields_table()`: genera la tabla/form con cada campo llamando a `label()`, `field()` y `description()`.
- `description($field)`: imprime la descripción del campo si existe.
- `label($field)`: imprime la etiqueta según el tipo (ej. `editor` usa un contenedor distinto).
- `field($field)`: obtiene `value()` y `checked()` y delega a `ColtmanInputFields` según `type`.
- `value($field)`: recupera valor con `get_post_meta` o usa `default` y normaliza caracteres.
- `checked($field)`: calcula si el checkbox debe marcarse.

Mapeo de `field['type']` a métodos de `ColtmanInputFields`
- `checkbox` -> `checkbox($field, $checked)`
- `number`/`date` -> `input_minmax($field, $value)`
- `get_terms` -> `get_terms($field, $value)`
- `accordion` -> `accordion($field, $value)`
- `editor` -> `editor($field, $value)`
- `media` -> `media($field, $value)`
- `gallery` -> `gallery_input($field, $value)`
- `select` -> `select($field, $value)`
- `textarea` -> `textarea($field, $value)`
- `get_posts` -> `get_posts($field, $value)`
- `repeater` -> `repeater($field, $value)` (si está implementado en `ColtmanInputFields`)
- `default` -> `input($field, $value)`

Notas y recomendaciones
- Seguridad: en `save_post` agregar verificación de `nonce`, `current_user_can()` y sanitización específica según tipo (`sanitize_text_field`, `sanitize_url`, `intval`, etc.).
- Compatibilidad con Gutenberg: los metaboxes en la columna lateral pueden no aparecer. Para exponer campos en el editor de bloques, registrar meta con `register_post_meta(..., ['show_in_rest'=>true])` y crear un panel JS o usar un plugin que muestre meta clásicos.
- Personalización: para cambiar la UI modifica `ColtmanInputFields` (renderizado) o añade nuevos `field['type']` y sus handlers.

Ejemplo de lectura en front-end
```php
$value = get_post_meta($post_id, 'override_post_writer_image', true);
if ($value) {
	echo '<img src="'.esc_url($value).'" alt="" />';
}
```

Si quieres, puedo:
- agregar un bloque `examples/metabox-usage.php` ejecutable con `nonce` y checks, o
- documentar cada método con firmas y ejemplos más detallados dentro de este README.

## Tabla de tipos de campo (mapeo y ejemplos)

A continuación se listan los tipos de campo que `ColtmanCreateMetabox` reconoce, las opciones más relevantes, cómo se guardan y un snippet de ejemplo:

- **checkbox**
	- Opciones: `id`, `description`, `checked`
	- Guardado: `$_POST['id']` (string), `update_post_meta`
	- Ejemplo:
		```php
		['label'=>'Activo','id'=>'rc_active','type'=>'checkbox','checked'=>true]
		```

- **number**
	- Opciones: `min`, `max`, `step`
	- Guardado: `$_POST['id']` (recomendado `intval`)
	- Ejemplo:
		```php
		['label'=>'Cantidad','id'=>'rc_qty','type'=>'number','min'=>0,'max'=>100]
		```

- **date**
	- Guardado: `$_POST['id']` (string, validar formato)
	- Ejemplo:
		```php
		['label'=>'Fecha','id'=>'rc_date','type'=>'date']
		```

- **editor**
	- Usa `wp_editor` para render; guardado sanitizado con `wp_filter_post_kses()`
	- Ejemplo:
		```php
		['label'=>'Descripción','id'=>'rc_long_desc','type'=>'editor']
		```

- **media**
	- Opciones: `return` ('url'|'id'), `button-text`, `modal-title`
	- Render: input + botón que abre media selector (depende de `wp_enqueue_media()` y `media.js`)
	- Guardado: URL o attachment ID en `$_POST['id']`
	- Ejemplo:
		```php
		['label'=>'Imagen','id'=>'rc_image','type'=>'media','return'=>'url','button-text'=>'Seleccionar']
		```

- **gallery**
	- Render: componente que guarda JSON (array de objetos)
	- Guardado: JSON en un campo oculto
	- Ejemplo:
		```php
		['label'=>'Galería','id'=>'rc_gallery','type'=>'gallery']
		```

- **get_posts**
	- Opciones: `post_type`
	- Render: select multiple con posts; guardado: JSON
	- Ejemplo:
		```php
		['label'=>'Relacionar posts','id'=>'rc_posts','type'=>'get_posts','post_type'=>'post']
		```

- **get_terms**
	- Opciones: `taxonomy`
	- Render: select con términos; guardado: valor seleccionado
	- Ejemplo:
		```php
		['label'=>'Categoría','id'=>'rc_term','type'=>'get_terms','taxonomy'=>'category']
		```

- **select**
	- Opciones: `options` (array)
	- Guardado: valor seleccionado
	- Ejemplo:
		```php
		['label'=>'Color','id'=>'rc_color','type'=>'select','options'=>['red'=>'Rojo','blue'=>'Azul']]
		```

- **textarea**
	- Guardado: `$_POST['id']` (recomendado `sanitize_textarea_field`)
	- Ejemplo:
		```php
		['label'=>'Notas','id'=>'rc_notes','type'=>'textarea']
		```

- **accordion**
	- Render: componente complejo (guardado como JSON en un hidden)
	- Opciones: `add_image` para incluir imagen en ítems
	- Ejemplo:
		```php
		['label'=>'FAQ','id'=>'rc_faq','type'=>'accordion','add_image'=>'false']
		```

- **repeater**
	- Mapeado a `repeater($field,$value)` pero NO se encontró implementación en `ColtmanInputFields` (si lo necesitas, implementarlo)
	- Ejemplo (conceptual):
		```php
		['label'=>'Bloques','id'=>'rc_blocks','type'=>'repeater']
		```

- **email**
	- Guardado: sanitizado con `sanitize_email`
	- Ejemplo:
		```php
		['label'=>'Correo','id'=>'rc_email','type'=>'email']
		```

- **default / otros**
	- Se renderizan con `input($field,$value)` usando `field['type']` como `type` HTML
	- Ejemplo:
		```php
		['label'=>'Código','id'=>'rc_code','type'=>'text']
		```

Notas importantes:
- `save_post` aplica sanitización parcial: `editor` y `email` tienen tratamiento; el resto requiere que añadas sanitización según tu caso (`sanitize_text_field`, `esc_url_raw`, `intval`, etc.).
- `media` y `gallery` dependen de `wp_enqueue_media()` y del JS `classes/assets/js/media.js` para abrir el selector.
- Si añades tipos nuevos, implementa el render en `ColtmanInputFields` y añade tratamiento en `save_post` si es necesario.



