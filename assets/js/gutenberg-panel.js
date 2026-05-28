/**
 * Coltman Framework — Gutenberg Document Settings Panel
 *
 * Renders a PluginDocumentSettingPanel in the block editor sidebar for every
 * ColtmanCreateMetabox instance that has at least one field with 'rest' => true.
 *
 * Field definitions and panel title are injected via wp_localize_script as
 * window.coltmanGutenbergData = { fields: [...], panelTitle: '...' }.
 *
 * Supported field types: text, email, url, number, date, color,
 *                        textarea, editor, select, checkbox.
 * Complex types (gallery, accordion, repeater, relationship, group, map)
 * are shown as a read-only notice — their values are saved/edited in the
 * classic metabox and are available in the REST API for reading only.
 */
(function () {
    if ( typeof wp === 'undefined' || ! wp.element || ! wp.editPost ) return;

    var data = window.coltmanGutenbergData;
    if ( ! data || ! data.fields || ! data.fields.length ) return;

    var el          = wp.element.createElement;
    var useSelect   = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var registerPlugin              = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel  = wp.editPost.PluginDocumentSettingPanel;
    var TextControl     = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var SelectControl   = wp.components.SelectControl;
    var CheckboxControl = wp.components.CheckboxControl;
    var Notice          = wp.components.Notice;

    var COMPLEX_TYPES = [ 'gallery', 'accordion', 'repeater', 'relationship', 'get_posts', 'get_terms', 'group', 'map' ];

    function ColtmanPanel() {
        var meta     = useSelect( function ( select ) {
            return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
        } );
        var editPost = useDispatch( 'core/editor' ).editPost;

        function set( key, value ) {
            var update = {};
            update[ key ] = value;
            editPost( { meta: update } );
        }

        return el(
            PluginDocumentSettingPanel,
            {
                name:  'coltman-fields-panel',
                title: data.panelTitle || 'Custom Fields',
                icon:  'admin-generic',
            },
            data.fields.map( function ( field ) {
                var value   = meta[ field.id ] !== undefined ? meta[ field.id ] : ( field.default || '' );
                var help    = field.description || null;
                var key     = field.id;
                var label   = field.label;

                // ── Complex types: read-only notice ──────────────────────
                if ( COMPLEX_TYPES.indexOf( field.type ) !== -1 ) {
                    return el( Notice, {
                        key:         key,
                        status:      'info',
                        isDismissible: false,
                    }, label + ' (' + field.type + ') — edit in the classic metabox below.' );
                }

                // ── Select ────────────────────────────────────────────────
                if ( field.type === 'select' ) {
                    var options = ( field.options || [] ).map( function ( o ) {
                        return { label: o.label || o.value, value: o.value };
                    } );
                    return el( SelectControl, {
                        key:      key,
                        label:    label,
                        value:    value,
                        options:  options,
                        onChange: function ( v ) { set( field.id, v ); },
                        help:     help,
                    } );
                }

                // ── Checkbox ──────────────────────────────────────────────
                if ( field.type === 'checkbox' ) {
                    return el( CheckboxControl, {
                        key:      key,
                        label:    label,
                        checked:  value === 'on',
                        onChange: function ( checked ) { set( field.id, checked ? 'on' : '' ); },
                        help:     help,
                    } );
                }

                // ── Textarea / editor ─────────────────────────────────────
                if ( field.type === 'textarea' ) {
                    return el( TextareaControl, {
                        key:      key,
                        label:    label,
                        value:    value,
                        onChange: function ( v ) { set( field.id, v ); },
                        help:     help,
                    } );
                }

                // ── Text, email, url, number, date, color ─────────────────
                var inputType = ( field.type === 'email' || field.type === 'url' || field.type === 'number' )
                    ? field.type
                    : 'text';

                return el( TextControl, {
                    key:      key,
                    label:    label,
                    value:    value,
                    type:     inputType,
                    onChange: function ( v ) { set( field.id, v ); },
                    help:     help,
                } );
            } )
        );
    }

    registerPlugin( 'coltman-document-settings', { render: ColtmanPanel } );
} )();
