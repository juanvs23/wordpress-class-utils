# Posibles mejoras — Framework Coltman

Ideas de evolución del framework más allá de las correcciones del roadmap. Estas mejoras ampliarían el alcance del módulo y lo acercarían más a una alternativa completa a ACF.

---

## API declarativa con un único método de registro

En lugar de instanciar cada clase por separado, exponer una API fluida o de array único:

```php
// Idea: registro completo de una entidad en un solo lugar
coltman_register_entity([
    'post_type' => [
        'slug'    => 'joyas_a_medida',
        'label'   => 'Joyas',
        'item'    => 'Joya',
        'icon'    => 'dashicons-superhero-alt',
        'archive' => false,
    ],
    'taxonomy' => [
        'slug'  => 'tipo_de_joyeria',
        'label' => 'Tipos de joya',
    ],
    'metabox' => [
        'title'  => 'Datos de la joya',
        'fields' => [
            ['id' => 'gallery', 'label' => 'Galería', 'type' => 'gallery'],
            ['id' => 'material', 'label' => 'Material', 'type' => 'text'],
        ],
    ],
    'term_meta' => [
        ['id' => 'jewel_material', 'label' => 'Material', 'type' => 'text'],
    ],
]);
```

Esto reduciría el boilerplate de cada feature a un solo bloque y haría el código autodocumentado.

---

## Validación de campos antes de guardar

Actualmente los campos se guardan sin validar el formato o los valores requeridos. Se podría agregar una clave `validate` en el array del campo:

```php
[
    'id'       => 'precio',
    'type'     => 'number',
    'validate' => ['required', 'min:0', 'max:9999'],
],
[
    'id'       => 'email_contacto',
    'type'     => 'email',
    'validate' => ['required', 'email'],
],
```

`save_post()` correría las validaciones y devolvería errores admin mediante `add_settings_error()` o una solución similar, evitando guardar datos inválidos.

---

## Campos condicionales

Mostrar u ocultar un campo dependiendo del valor de otro. Implementado en JS con un atributo `data-condition`:

```php
[
    'id'        => 'precio_oferta',
    'type'      => 'number',
    'condition' => ['field' => 'tiene_oferta', 'value' => 'on'],
],
```

Requiere lógica en `media.js` que observe cambios en los campos y muestre/oculte las filas correspondientes.

---

## Campo `repeater` con sub-campos configurables

A diferencia del `accordion` (que tiene título + contenido + imagen fija), el `repeater` permitiría definir sub-campos libremente:

```php
[
    'id'     => 'variantes',
    'type'   => 'repeater',
    'fields' => [
        ['id' => 'color',   'type' => 'text',  'label' => 'Color'],
        ['id' => 'precio',  'type' => 'number', 'label' => 'Precio'],
        ['id' => 'imagen',  'type' => 'media',  'label' => 'Imagen'],
    ],
],
```

Cada fila del repeater mostraría los sub-campos configurados. El valor se guardaría como JSON array de objetos.

---

## Campo `group` (agrupación visual)

Agrupar campos relacionados bajo un título colapsable en el metabox, sin afectar el almacenamiento:

```php
[
    'type'   => 'group',
    'label'  => 'Datos SEO',
    'fields' => [
        ['id' => 'seo_title',       'type' => 'text'],
        ['id' => 'seo_description', 'type' => 'textarea'],
    ],
],
```

---

## Soporte de campo `wysiwyg` con configuración expandida

El tipo `editor` actual usa configuración mínima. Una versión mejorada permitiría pasar opciones directamente:

```php
[
    'id'   => 'contenido_extendido',
    'type' => 'wysiwyg',
    'options' => [
        'media_buttons' => true,
        'wpautop'       => true,
        'teeny'         => false,
        'rows'          => 15,
    ],
],
```

---

## Auto-registro de meta para REST API

Para evitar el trabajo manual de llamar `register_post_meta()` por cada campo que deba exponerse en la REST API o en Gutenberg, agregar una clave `rest`:

```php
['id' => 'precio', 'type' => 'number', 'rest' => true],
```

`ColtmanCreateMetabox` llamaría a `register_post_meta()` automáticamente para ese campo con `show_in_rest: true` y el tipo correcto.

---

## Exportar/importar configuración de campos

Un botón en el metabox que exporte la configuración de campos como JSON para copiarla a otro CPT o proyecto. Útil para reutilizar configuraciones entre distintos proyectos donde vive el framework.

---

## Panel lateral nativo en Gutenberg

Crear un bloque `PluginDocumentSettingPanel` (React + `@wordpress/plugins`) que lea los campos registrados y los muestre en el panel lateral del editor de bloques, manteniendo la misma UX que el metabox clásico pero integrado nativamente con Gutenberg.

---

## Modo de solo lectura para campos en el admin

Opción `'readonly' => true` en un campo para mostrarlo como información de referencia sin permitir edición:

```php
['id' => 'fecha_creacion', 'type' => 'text', 'readonly' => true],
```

---

## Logging de cambios de meta (auditoría)

Registrar en una tabla custom o en `wp_postmeta` cada vez que un campo se actualiza, incluyendo el valor anterior, el nuevo, el usuario y la fecha. Útil para auditoría de contenido editorial.

---

## Campo `map` (mapa de Google / Leaflet)

Para el CPT de ciudades (`anillo_city`) sería útil un campo de mapa que guarde latitud y longitud, con un selector visual en el admin. Requiere integración con la API de Google Maps o una librería open source como Leaflet.

---

## Separar el CSS inline en un archivo encolado

El bloque `<style>` que inyectan `ColtmanCreateMetabox::admin_head()` y `ColtmanTermMeta::admin_head()` duplica el mismo CSS cuando hay múltiples instancias. Extraer a `classes/assets/css/admin.css` y encolarlo una sola vez con `wp_enqueue_style()`.

---

## Documentación generada automáticamente

Script PHP/Node que lea los archivos de definición de campos en `include/features/` y genere automáticamente una tabla en Markdown con todos los CPTs, taxonomías, campos y shortcodes registrados. Facilitaría mantener `context.md` actualizado sin hacerlo manualmente.
