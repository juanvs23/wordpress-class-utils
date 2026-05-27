# Changelog — Coltman Framework

Todos los cambios notables de este módulo se documentan aquí.  
Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/).  
Versiones siguiendo [Semantic Versioning](https://semver.org/lang/es/).

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
