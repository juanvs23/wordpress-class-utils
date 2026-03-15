# Contexto de la carpeta `classes`

Resumen rápido: esta carpeta contiene utilidades reutilizables para temas/plugins de WordPress: registro de post types, taxonomías, metaboxes, campos de entrada y helpers/optimizaciones.

**Archivos y clases principales**

- **Archivo:** [whiteriver/wp-content/themes/recoverycentre/classes/class.php](whiteriver/wp-content/themes/recoverycentre/classes/class.php#L1)
  - **Qué contiene:** punto de entrada; define namespace `Class\AddicClinicDirectory`, constante `WORK_CONTEXT` y `require` de los módulos (input-fields, post-types, taxonomy, metabox, termeta, utils).
  - **Propósito:** cargar y ensamblar las clases de la colección.

- **Archivo:** [whiteriver/wp-content/themes/recoverycentre/classes/class-metabox.php](whiteriver/wp-content/themes/recoverycentre/classes/class-metabox.php#L1)
  - **Clase:** `ColtmanCreateMetabox`
  - **Propósito:** crear metaboxes en la pantalla de edición de WordPress, renderizar campos y guardar meta.
  - **Métodos clave:** `__construct()`, `add_meta_boxes()`, `admin_enqueue_scripts()`, `admin_head()`, `save_post()`, `add_meta_box_callback()`, `fields_table()`, `field()`, `value()`, `checked()`.
  - **Dependencias:** `ColtmanInputFields` (desde `input-fields.php`), funciones WP: `add_meta_box`, `update_post_meta`, `wp_enqueue_media`, etc. Usa assets JS/CSS en `classes/assets/js/media.js`.

- **Archivo:** [whiteriver/wp-content/themes/recoverycentre/classes/input-fields.php](whiteriver/wp-content/themes/recoverycentre/classes/input-fields.php#L1)
  - **Clase:** `ColtmanInputFields`
  - **Propósito:** abstracción y renderizado de tipos de campo usados por metaboxes y term meta (checkbox, editor, media, gallery, select, accordion, textarea, etc.).
  - **Métodos clave:** `checkbox()`, `editor()`, `get_posts()`, `get_terms()`, `input()`, `media()`, `gallery_input()`, `select()`, `accordion()` y helpers privados.
  - **Notas:** contiene HTML/JS helpers; maneja serialización JSON para galerías/accordions.

- **Archivo:** [whiteriver/wp-content/themes/recoverycentre/classes/class-post-types.php](whiteriver/wp-content/themes/recoverycentre/classes/class-post-types.php#L1)
  - **Clase:** `ColtmanRegisterPost`
  - **Propósito:** encapsula el registro de custom post types (genera labels y args; llama a `register_post_type` en `init`).
  - **Métodos clave:** `__construct()`, `register_new_post_type()`.

- **Archivo:** [whiteriver/wp-content/themes/recoverycentre/classes/class-taxonomy.php](whiteriver/wp-content/themes/recoverycentre/classes/class-taxonomy.php#L1)
  - **Clase:** `ColtmanRegisterTaxonomy`
  - **Propósito:** encapsula el registro de taxonomías personalizadas (labels, args, rewrite) y registra con `register_taxonomy`.
  - **Métodos clave:** `__construct()`, `register_new_taxonomy()`.

- **Archivo:** [whiteriver/wp-content/themes/recoverycentre/classes/class-termeta.php](whiteriver/wp-content/themes/recoverycentre/classes/class-termeta.php#L1)
  - **Clase:** `ColtmanTermMeta`
  - **Propósito:** agregar/editar/guardar campos personalizados para términos (taxonomy term meta). Renderiza inputs en pantallas `add`/`edit` y persiste con `update_term_meta`.
  - **Métodos clave:** `__construct()`, `wpturbo_render_meta_fields()`, `wpturbo_edit_meta_fields()`, `wpturbo_render_input_field()`, `wpturbo_save_meta_fields()`, `admin_enqueue_scripts()`, `admin_head()`.
  - **Dependencias:** WP term APIs y similares hooks admin.

- **Archivo:** [whiteriver/wp-content/themes/recoverycentre/classes/utils/utils.php](whiteriver/wp-content/themes/recoverycentre/classes/utils/utils.php#L1)
  - **Funciones:** `coltman_trim_content_text_fn()` — wrapper pequeño para `wp_trim_words`.

- **Archivo:** [whiteriver/wp-content/themes/recoverycentre/classes/utils/optimizations/remove_scripts.php](whiteriver/wp-content/themes/recoverycentre/classes/utils/optimizations/remove_scripts.php#L1)
  - **Qué hace:** filtros para quitar `jquery-migrate` (y ejemplo comentado para quitar `jquery`) mediante `wp_default_scripts`.

**Observaciones generales / Contexto de uso**

- Flujo típico de uso: el archivo `class.php` incluye los módulos; un theme/plugin puede instanciar `ColtmanRegisterPost`, `ColtmanRegisterTaxonomy`, `ColtmanCreateMetabox` y `ColtmanTermMeta` pasándoles configuraciones (arrays) para crear CPTs, taxonomías, metaboxes y campos en términos.
- `ColtmanInputFields` centraliza la renderización de campos; es la dependencia clave para `ColtmanCreateMetabox` y `ColtmanTermMeta`.
- Las clases usan hooks de WP (`add_action('init'...)`, `add_meta_boxes`, `save_post`, etc.) para integrarse en el ciclo de WordPress.

**Recomendaciones rápidas**

- Si quieres reutilizar en un plugin, cambiar `WORK_CONTEXT` y el namespace o adaptar rutas de assets puede ser necesario.
- Considerar sanitización adicional en algunos campos antes de `update_post_meta` (aunque ya hay sanitización para `email` y `editor` en algunos casos).
- Añadir PHPDoc consistentemente (algunas clases ya lo tienen) y tests unitarios si se requiere mayor estabilidad.

---
Generado automáticamente por revisión de los archivos en esta carpeta. Si quieres, puedo:

- 1) Hacer un commit con este archivo.
- 2) Extraer ejemplos de uso (snippets) para cada clase.
- 3) Ejecutar una búsqueda de referencias en el tema para ver dónde se usan.
