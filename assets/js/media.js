
const COLTMAN_DEBUG = false;
const coltmanLog = (...args) => COLTMAN_DEBUG && console.log(...args);

jQuery.noConflict();

(function($) {

    $(document).ready(function($) {

        // ── Group toggle ──────────────────────────────────────────────────────
        $('body').on('click', '.coltman-group-toggle', function() {
            var groupId = $(this).data('group');
            var $body   = $('#coltman-group-' + groupId);
            var open    = $(this).attr('aria-expanded') === 'true';
            if (open) {
                $body.hide();
                $(this).attr('aria-expanded', 'false').text('▼');
            } else {
                $body.show();
                $(this).attr('aria-expanded', 'true').text('▲');
            }
        });

        // ── Group field manager ───────────────────────────────────────────────
        $('body').on('click', '.coltman-field-manager-toggle', function() {
            var $panel   = $(this).closest('.coltman-field-manager').find('.coltman-field-manager-panel');
            var expanded = $(this).attr('aria-expanded') === 'true';
            $panel.slideToggle(200);
            $(this).attr('aria-expanded', expanded ? 'false' : 'true');
        });

        $('body').on('click', '.coltman-add-dynamic-field', function() {
            var $manager  = $(this).closest('.coltman-field-manager');
            var group_id  = $manager.data('group');
            var nonce     = $manager.data('nonce');
            var type      = $manager.find('.coltman-new-field-type').val();
            var key       = $.trim($manager.find('.coltman-new-field-key').val());
            var label     = $.trim($manager.find('.coltman-new-field-label').val());

            if (!key || !label) {
                alert('Please enter a key and a label for the field.');
                return;
            }
            if (!/^[a-z0-9_]+$/.test(key)) {
                alert('Key may only contain lowercase letters, numbers and underscores.');
                return;
            }

            var $btn = $(this).prop('disabled', true).text('Adding…');

            $.post(ajaxurl, {
                action:   'coltman_add_group_field',
                nonce:    nonce,
                group_id: group_id,
                type:     type,
                key:      key,
                label:    label
            })
            .done(function(res) {
                if (!res.success) {
                    alert(res.data || 'Error adding field.');
                    return;
                }
                // Add to the dynamic fields list
                var $list = $manager.find('.coltman-dynamic-fields-list');
                $list.find('.coltman-no-dynamic-fields').remove();
                $list.append(
                    '<div class="coltman-dynamic-field-item" data-key="' + key + '">' +
                    '<span class="coltman-dynamic-field-info">' + type + ' &middot; ' + label + ' <code>' + key + '</code></span>' +
                    '<button type="button" class="coltman-remove-dynamic-field" data-key="' + key + '">&#10005;</button>' +
                    '</div>'
                );
                // Inject the new input row into the group body, before the field manager
                var $body = $('#coltman-group-' + group_id);
                $body.find('.coltman-field-manager').before(coltmanBuildFieldRow(type, key, label));
                // Reset the form
                $manager.find('.coltman-new-field-key').val('');
                $manager.find('.coltman-new-field-label').val('');
            })
            .fail(function() { alert('Request failed.'); })
            .always(function() { $btn.prop('disabled', false).text('+ Add field'); });
        });

        $('body').on('click', '.coltman-remove-dynamic-field', function() {
            var $item    = $(this).closest('.coltman-dynamic-field-item');
            var $manager = $(this).closest('.coltman-field-manager');
            var group_id = $manager.data('group');
            var nonce    = $manager.data('nonce');
            var key      = String($(this).data('key'));

            if (!confirm('Remove dynamic field "' + key + '"? Saved values are not deleted.')) return;

            $.post(ajaxurl, {
                action:   'coltman_remove_group_field',
                nonce:    nonce,
                group_id: group_id,
                key:      key
            })
            .done(function(res) {
                if (!res.success) { alert(res.data || 'Error removing field.'); return; }
                $item.remove();
                $('#coltman-group-' + group_id).find('[data-dynamic-key="' + key + '"]').remove();
                var $list = $manager.find('.coltman-dynamic-fields-list');
                if ($list.find('.coltman-dynamic-field-item').length === 0) {
                    $list.append('<p class="coltman-no-dynamic-fields">No dynamic fields added yet.</p>');
                }
            })
            .fail(function() { alert('Request failed.'); });
        });

        // ── Media picker ──────────────────────────────────────────────────────
        $('body').on('click', '.rwp-media-toggle', function(e) {
            e.preventDefault();
            let button = $(this);
            let rwpMediaUploader = null;
            rwpMediaUploader = wp.media({
                title: button.data('modal-title'),
                button: {
                    text: button.data('modal-button')
                },
                multiple: true
            }).on('select', function() {
                let attachment = rwpMediaUploader.state().get('selection').first().toJSON();
                coltmanLog(attachment);
                const getImageEl = e.currentTarget.closest('.get-image');
                const mediaWrap  = e.currentTarget.closest('.coltman-media');
                if(getImageEl){
                    const imageItem    = getImageEl;
                    const galleryItem  = imageItem.parentNode;
                    const gallery      = galleryItem.parentNode.parentNode;
                    const inputGallery = gallery.querySelector('input.gallery-data');
                    const inputItem    = imageItem.querySelector('.image-url');
                    const altInput     = imageItem.querySelector('.image-alt');
                    const dataItem     = galleryItem.dataset.item;
                    // Pre-fill alt from attachment; user can override via the alt input
                    if (altInput && !altInput.value) altInput.value = attachment.alt || '';
                    const altValue = altInput ? altInput.value : (attachment.alt || '');
                    // Refresh thumbnail
                    const thumbEl = galleryItem.querySelector('.coltman-gallery-thumb');
                    if (thumbEl && inputItem.value) { thumbEl.src = inputItem.value; thumbEl.classList.add('has-image'); }
                    let imageGallery = JSON.parse(inputGallery.value || '[]');
                    // Update existing entry if same item id, otherwise append
                    const existIdx = imageGallery.findIndex(function(img) { return String(img.item) === String(dataItem); });
                    const entry = {id: attachment.id, alt: altValue, sizes: attachment.sizes, title: attachment.title, mime: attachment.mime, height: attachment.height, width: attachment.width, item: dataItem, url: inputItem.value};
                    if (existIdx !== -1) {
                        imageGallery[existIdx] = entry;
                    } else {
                        imageGallery = [...imageGallery, entry];
                    }
                    inputGallery.value = JSON.stringify(imageGallery);
                } else if (mediaWrap) {
                    const urlInput = mediaWrap.querySelector('.coltman-media-url');
                    const altInput = mediaWrap.querySelector('.coltman-media-alt');
                    if (urlInput) urlInput.value = attachment[button.data('return')];
                    if (altInput && !altInput.value) altInput.value = attachment.alt || '';
                    const thumbUrl = (attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url)
                        || attachment.url
                        || '';
                    coltmanMediaUpdatePreview(mediaWrap, urlInput ? urlInput.value : '', thumbUrl);
                } else {
                    button.prev().val(attachment[button.data('return')]);
                }
            }).open();
        });

        // ── Media clear button ────────────────────────────────────────────────
        $('body').on('click', '.coltman-media-clear', function () {
            var wrap = this.closest('.coltman-media');
            if (!wrap) return;
            var urlInput = wrap.querySelector('.coltman-media-url');
            var altInput = wrap.querySelector('.coltman-media-alt');
            if (urlInput) urlInput.value = '';
            if (altInput) altInput.value = '';
            coltmanMediaUpdatePreview(wrap, '');
        });

        // ── Accordion sortable ────────────────────────────────────────────────
        if (typeof $.fn.sortable === 'function') {
            $('.accordion-container').sortable({
                handle:               '.accordion-drag-handle',
                axis:                 'y',
                cursor:               'grabbing',
                tolerance:            'pointer',
                placeholder:          'accordion-sort-placeholder',
                forcePlaceholderSize: true,
                stop: function() {
                    var $container   = $(this);
                    var $accordion   = $container.closest('.accordion');
                    var $hiddenInput = $accordion.find('input[type="hidden"]');
                    if (!$hiddenInput.length) return;
                    try {
                        var currentData = JSON.parse($hiddenInput.val() || '[]');
                        var newOrder    = [];
                        $container.find('.accordion-item').each(function() {
                            var id    = String($(this).attr('id'));
                            var entry = currentData.find(function(item) { return String(item.id) === id; });
                            if (entry) newOrder.push(entry);
                        });
                        $hiddenInput.val(JSON.stringify(newOrder));
                    } catch (err) {
                        coltmanLog('Accordion sortable stop error:', err);
                    }
                },
            });
        }

        // ── Gallery sortable ─────────────────────────────────────────────────
        if (typeof $.fn.sortable === 'function') {
            $('.gallery-sortable').sortable({
                handle:               '.gallery-drag-handle',
                axis:                 'y',
                cursor:               'grabbing',
                tolerance:            'pointer',
                placeholder:          'gallery-sort-placeholder',
                forcePlaceholderSize: true,
                stop: function() {
                    var $container   = $(this);
                    var $inputGallery = $container.closest('.coltman-gallery').find('.gallery-data');
                    try {
                        var currentData = JSON.parse($inputGallery.val() || '[]');
                        var newOrder    = [];
                        $container.find('.gallery-item').each(function() {
                            var id    = String($(this).data('item'));
                            var entry = currentData.find(function(img) { return String(img.item) === id; });
                            if (entry) newOrder.push(entry);
                        });
                        $inputGallery.val(JSON.stringify(newOrder));
                    } catch (err) {}
                },
            });
        }

        // ── List sortable ──────────────────────────────────────────────────────
        if (typeof $.fn.sortable === 'function') {
            $('.list-sortable').sortable({
                handle:               '.list-drag-handle',
                axis:                 'y',
                cursor:               'grabbing',
                tolerance:            'pointer',
                placeholder:          'list-sort-placeholder',
                forcePlaceholderSize: true,
                stop: function() {
                    var $container  = $(this);
                    var $inputList  = $container.closest('.coltman-list').find('.list-data');
                    try {
                        var currentData = JSON.parse($inputList.val() || '[]');
                        var newOrder    = [];
                        $container.find('.list-item').each(function() {
                            var id    = String($(this).data('item'));
                            var entry = currentData.find(function(item) { return String(item.item) === id; });
                            if (entry) newOrder.push(entry);
                        });
                        $inputList.val(JSON.stringify(newOrder));
                    } catch (err) {}
                },
            });
        }

        // ── List textarea live sync ────────────────────────────────────────────
        $('body').on('input change', '.list-textarea', function() {
            var $textarea  = $(this);
            var $item      = $textarea.closest('.list-item');
            if (!$item.length) return;
            var $list       = $textarea.closest('.coltman-list');
            var $inputList  = $list.find('.list-data');
            var dataItem    = String($item.data('item'));
            try {
                var data = JSON.parse($inputList.val() || '[]');
                var idx  = data.findIndex(function(entry) { return String(entry.item) === dataItem; });
                if (idx !== -1) {
                    data[idx].text = $textarea.val();
                } else {
                    data.push({item: dataItem, text: $textarea.val()});
                }
                $inputList.val(JSON.stringify(data));
            } catch(e) {}
        });

        // ── Gallery thumbnail image load error ────────────────────────────
        $('body').on('error', '.coltman-gallery-thumb', function() {
            $(this).removeClass('has-image');
        });

        // ── Gallery thumbnail sync on manual URL edit ─────────────────────────
        $('body').on('input change', '.image-url', function() {
            var $input = $(this);
            var $item  = $input.closest('.coltman-gallery-item');
            if (!$item.length) return;
            var $thumb = $item.find('.coltman-gallery-thumb');
            var url    = $input.val().trim();
            if (url) {
                $thumb.attr('src', url).addClass('has-image');
            } else {
                $thumb.attr('src', '').removeClass('has-image');
            }
            // Sync hidden JSON
            var $gallery      = $input.closest('.coltman-gallery');
            var $inputGallery = $gallery.find('.gallery-data');
            var dataItem      = String($item.data('item'));
            try {
                var data   = JSON.parse($inputGallery.val() || '[]');
                var imgIdx = data.findIndex(function(img) { return String(img.item) === dataItem; });
                if (imgIdx !== -1) {
                    data[imgIdx].url = url;
                    $inputGallery.val(JSON.stringify(data));
                }
            } catch(e) {}
        });

        // ── Gallery alt text live sync ────────────────────────────────────────
        $('body').on('input change', '.image-alt', function() {
            var $altInput    = $(this);
            var $item        = $altInput.closest('.gallery-item');
            var $gallery     = $altInput.closest('.coltman-gallery');
            var $inputGallery = $gallery.find('.gallery-data');
            if (!$item.length || !$inputGallery.length) return;
            var dataItem = String($item.data('item'));
            try {
                var data = JSON.parse($inputGallery.val() || '[]');
                var idx  = data.findIndex(function(img) { return String(img.item) === dataItem; });
                if (idx !== -1) {
                    data[idx].alt = $altInput.val();
                    $inputGallery.val(JSON.stringify(data));
                }
            } catch (err) {}
        });

        // ── Select2 init (inside document.ready so DOM is available) ─────────
        if (typeof $.fn.select2 === 'function') {
            $('.js-select2').each(function() {
                var config = {
                    placeholder: $(this).data('placeholder') || '',
                    width: '100%',
                };
                if ($(this).data('allow-clear')) {
                    config.allowClear = true;
                }
                $(this).select2(config);
            });
        }

        // ── Relationship select (Select2 + AJAX search) ───────────────────
        if (typeof $.fn.select2 === 'function') {
            $('.js-relationship-select').each(function() {
                var $el = $(this);
                $el.select2({
                    ajax: {
                        url: (typeof ajaxurl !== 'undefined') ? ajaxurl : '',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action:    'coltman_relationship_search',
                                nonce:     $el.data('nonce')     || '',
                                q:         params.term           || '',
                                post_type: $el.data('post-type') || 'post',
                                page:      params.page           || 1,
                            };
                        },
                        processResults: function(data) {
                            return {
                                results:    data.results || [],
                                pagination: { more: !!data.more },
                            };
                        },
                        cache: true,
                    },
                    placeholder:        $el.data('placeholder') || '',
                    minimumInputLength: 1,
                    width:              '100%',
                    allowClear:         true,
                });
            });
        }

        // ── Term select (Select2 + AJAX search) ──────────────────────────
        if (typeof $.fn.select2 === 'function') {
            $('.js-term-select').each(function() {
                var $el = $(this);
                $el.select2({
                    ajax: {
                        url: (typeof ajaxurl !== 'undefined') ? ajaxurl : '',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action:   'coltman_term_search',
                                nonce:    $el.data('nonce')    || '',
                                q:        params.term          || '',
                                taxonomy: $el.data('taxonomy') || 'category',
                                page:     params.page          || 1,
                            };
                        },
                        processResults: function(data) {
                            return {
                                results:    data.results || [],
                                pagination: { more: !!data.more },
                            };
                        },
                        cache: true,
                    },
                    placeholder:        $el.data('placeholder') || '',
                    minimumInputLength: 1,
                    width:              '100%',
                    allowClear:         !!$el.data('allow-clear'),
                });
            });
        }

        // ── Color picker init ─────────────────────────────────────────────
        if (typeof $.fn.wpColorPicker === 'function') {
            $('.coltman-color-picker').wpColorPicker();
        }

        // ── Map field (Leaflet) ───────────────────────────────────────────
        if (typeof L !== 'undefined') {
            $('.coltman-map-container').each(function() {
                coltmanInitMap(this);
            });
        }

        // ── Repeater sortable ─────────────────────────────────────────────
        if (typeof $.fn.sortable === 'function') {
            $('.coltman-repeater .repeater-rows').sortable({
                handle:               '.repeater-drag-handle',
                axis:                 'y',
                cursor:               'grabbing',
                tolerance:            'pointer',
                placeholder:          'repeater-sort-placeholder',
                forcePlaceholderSize: true,
                stop: function(event, ui) {
                    $(this).find('.repeater-row').each(function(i) {
                        var num = $(this).find('.repeater-row-num');
                        if (num.length) num.text('Row ' + (i + 1));
                    });
                },
            });
        }

    });

})(jQuery);


// ── Helpers ───────────────────────────────────────────────────────────────────

function coltmanMediaUpdatePreview(wrap, value, thumbUrl) {
    var isUrlImg   = value && /\.(jpg|jpeg|png|gif|webp|svg)(\?[^#]*)?$/i.test(value);
    var previewSrc = thumbUrl || (isUrlImg ? value : '');
    var thumb       = wrap.querySelector('.coltman-media-thumb');
    var placeholder = wrap.querySelector('.coltman-media-placeholder');
    var clearBtn    = wrap.querySelector('.coltman-media-clear');
    if (thumb) {
        thumb.src = previewSrc;
        thumb.classList.toggle('has-image', !!previewSrc);
    }
    if (placeholder) placeholder.style.display = previewSrc ? 'none' : '';
    if (clearBtn) clearBtn.classList.toggle('hidden', !value);
}

function coltmanBuildFieldRow(type, key, label) {
    var inputHtml;
    if (type === 'textarea') {
        inputHtml = '<textarea id="' + key + '" name="' + key + '" class="large-text" rows="4"></textarea>';
    } else {
        inputHtml = '<input id="' + key + '" name="' + key + '" type="' + type + '" class="regular-text">';
    }
    return '<div class="coltman-group-field-row" data-dynamic-key="' + key + '">' +
        '<label for="' + key + '">' + label + '</label>' +
        inputHtml +
        '</div>';
}

// ── Map (Leaflet) ─────────────────────────────────────────────────────────────

function coltmanLeafletIcon() {
    var base = (window.coltmanVars && window.coltmanVars.assetsUrl) ? window.coltmanVars.assetsUrl : '';
    // Delete _getIconUrl so mergeOptions is not bypassed by Leaflet's CSS auto-detection
    delete L.Icon.Default.prototype._getIconUrl;
    return L.icon({
        iconUrl:       base + '/libs/leaflet/images/marker-icon.png',
        iconRetinaUrl: base + '/libs/leaflet/images/marker-icon-2x.png',
        shadowUrl:     base + '/libs/leaflet/images/marker-shadow.png',
        iconSize:    [25, 41],
        iconAnchor:  [12, 41],
        popupAnchor: [1, -34],
        shadowSize:  [41, 41],
    });
}

function coltmanInitMap(el) {
    var $el      = jQuery(el);
    var fieldId  = $el.data('field');
    var rawLat   = $el.data('lat');
    var rawLng   = $el.data('lng');
    var hasCoord = rawLat !== '' && rawLng !== '' && rawLat !== undefined && rawLng !== undefined;
    var defLat   = hasCoord ? parseFloat(rawLat)          : 40.4168;
    var defLng   = hasCoord ? parseFloat(rawLng)          : -3.7038;
    var defZoom  = parseInt($el.data('zoom'), 10) || 13;
    var $wrap    = $el.closest('.coltman-map-wrap');

    var map = L.map(el).setView([defLat, defLng], defZoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    var marker = hasCoord ? L.marker([defLat, defLng], { draggable: true, icon: coltmanLeafletIcon() }).addTo(map) : null;

    function syncValue(latlng) {
        var zoom = map.getZoom();
        jQuery('#' + fieldId).val(JSON.stringify({ lat: latlng.lat, lng: latlng.lng, zoom: zoom }));
        $wrap.find('.coltman-map-lat').val(latlng.lat.toFixed(6));
        $wrap.find('.coltman-map-lng').val(latlng.lng.toFixed(6));
    }

    if (marker) {
        marker.on('dragend', function(e) { syncValue(e.target.getLatLng()); });
    }

    map.on('click', function(e) {
        if (!marker) {
            marker = L.marker(e.latlng, { draggable: true, icon: coltmanLeafletIcon() }).addTo(map);
            marker.on('dragend', function(ev) { syncValue(ev.target.getLatLng()); });
        } else {
            marker.setLatLng(e.latlng);
        }
        syncValue(e.latlng);
    });

    map.on('zoomend', function() {
        if (marker) syncValue(marker.getLatLng());
    });

    $wrap.find('.coltman-map-clear').on('click', function() {
        if (marker) { map.removeLayer(marker); marker = null; }
        jQuery('#' + fieldId).val('');
        $wrap.find('.coltman-map-lat, .coltman-map-lng').val('');
    });
}

function normalizeQuotes(str) {
  if (typeof str !== 'string') return str;
  return str.replace(/'/g, '’').replace(/"/g, '“');
}

function escapeForPhpJson(str) {
  if (typeof str !== 'string') return str;
  return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function sanitizeForJSON(value) {
    if (typeof value !== 'string') return value;
    return value
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/&(?!(quot|#39|lt|gt|#amp);)/g, '&amp;');
}


// ── WYSIWYG (contenteditable) ─────────────────────────────────────────────────

// Use <p> as the default block element instead of <div> (improvement #4)
document.execCommand('defaultParagraphSeparator', false, 'p');

// Copies contenteditable innerHTML to the associated hidden textarea
function syncWysiwyg(body) {
    var id = body.dataset.sync;
    if (!id) return;
    var textarea = document.getElementById(id);
    if (textarea) textarea.value = body.innerHTML;
}

// ── Improvement #1: active state — highlight toolbar buttons that match current
// selection state (bold, italic, underline, strikeThrough).
// Also syncs the headings select to the block tag under the cursor.
var WYSIWYG_INLINE_CMDS = ['bold', 'italic', 'underline', 'strikeThrough'];
var WYSIWYG_BLOCK_TAGS  = { P: 'p', H2: 'h2', H3: 'h3', H4: 'h4', BLOCKQUOTE: 'blockquote' };

function updateWysiwygToolbar(body) {
    var wysiwyg = body.closest('.coltman-wysiwyg');
    if (!wysiwyg) return;

    // Inline button active states
    WYSIWYG_INLINE_CMDS.forEach(function(cmd) {
        var btn = wysiwyg.querySelector('[data-cmd="' + cmd + '"]');
        if (!btn) return;
        try {
            btn.classList.toggle('is-active', document.queryCommandState(cmd));
        } catch(e) {}
    });

    // Headings select: walk up the DOM from the anchor node to find the block tag
    var sel = window.getSelection();
    var hSel = wysiwyg.querySelector('.coltman-wysiwyg-headings');
    if (hSel && sel && sel.rangeCount) {
        var node = sel.anchorNode;
        var el   = node && node.nodeType === 3 ? node.parentElement : node;
        var found = 'p';
        while (el && el !== body) {
            if (WYSIWYG_BLOCK_TAGS[el.tagName]) { found = WYSIWYG_BLOCK_TAGS[el.tagName]; break; }
            el = el.parentElement;
        }
        hSel.value = found;
    }
}

document.addEventListener('selectionchange', function() {
    var body = document.activeElement;
    if (body && body.classList.contains('coltman-wysiwyg-body')) {
        updateWysiwygToolbar(body);
    }
});

// ── Toolbar button clicks — delegated on document so cloned items work
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.coltman-wysiwyg-btn');
    if (!btn) return;
    e.preventDefault();
    var cmd     = btn.dataset.cmd;
    var wysiwyg = btn.closest('.coltman-wysiwyg');
    var body    = wysiwyg ? wysiwyg.querySelector('.coltman-wysiwyg-body') : null;
    if (!body) return;
    body.focus();
    if (cmd === 'createLink') {
        var url = window.prompt(btn.title || 'URL:');
        if (!url) return;
        document.execCommand('createLink', false, url);
    } else {
        document.execCommand(cmd, false, null);
    }
    syncWysiwyg(body);
    updateWysiwygToolbar(body);
});

// ── Improvement #3: headings select — formatBlock on change
document.addEventListener('change', function(e) {
    var sel = e.target.closest('.coltman-wysiwyg-headings');
    if (!sel) return;
    var wysiwyg = sel.closest('.coltman-wysiwyg');
    var body    = wysiwyg ? wysiwyg.querySelector('.coltman-wysiwyg-body') : null;
    if (!body) return;
    body.focus();
    document.execCommand('formatBlock', false, sel.value);
    syncWysiwyg(body);
});

// ── Improvement #4: normalize Enter to always insert <p>, not <div>
// Fires before the browser's default — prevents Chrome inserting <div>.
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Enter' || e.shiftKey) return;
    var body = e.target.closest('.coltman-wysiwyg-body');
    if (!body) return;
    // Inside a list item: let the browser handle it naturally
    var sel = window.getSelection();
    if (sel && sel.rangeCount) {
        var node = sel.anchorNode;
        var el   = node && node.nodeType === 3 ? node.parentElement : node;
        while (el && el !== body) {
            if (el.tagName === 'LI') return;
            el = el.parentElement;
        }
    }
    e.preventDefault();
    document.execCommand('insertParagraph', false, null);
    syncWysiwyg(body);
});

// ── Improvement #2: paste sanitizer — strip Word / Google Docs markup
document.addEventListener('paste', function(e) {
    var body = e.target.closest('.coltman-wysiwyg-body');
    if (!body) return;
    e.preventDefault();

    var clip = e.clipboardData || window.clipboardData;
    var html  = clip.getData('text/html');
    var clean = '';

    if (html) {
        clean = html
            // Remove style/script blocks
            .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')
            .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '')
            // Strip Office/Word XML namespace elements (<o:p>, <w:p>, <m:...> etc.)
            .replace(/<\/?[a-z][a-z0-9]*:[^>]*>/gi, '')
            // Strip Word conditional comments (<!--[if ...]>...<![endif]-->)
            .replace(/<!--\[if[\s\S]*?<!\[endif\]-->/gi, '')
            // Strip remaining HTML comments
            .replace(/<!--[\s\S]*?-->/g, '')
            // Strip inline style= attributes (Word pastes inline CSS on every tag)
            .replace(/\s+style\s*=\s*(?:"[^"]*"|'[^']*')/gi, '')
            // Unwrap empty <span> shells left after stripping styles
            .replace(/<span(?:\s[^>]*)?>(\s*)<\/span>/gi, '$1')
            .trim();
        // div, table, br and all other structural tags are kept as-is
    } else {
        // Plain text: split on double newlines → paragraphs
        var text = clip.getData('text/plain') || '';
        clean = text
            .split(/\n{2,}/)
            .map(function(p) { return '<p>' + p.replace(/\n/g, '<br>') + '</p>'; })
            .join('');
    }

    document.execCommand('insertHTML', false, clean);
    syncWysiwyg(body);
});

// Auto-sync on every keystroke
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('coltman-wysiwyg-body')) {
        syncWysiwyg(e.target);
    }
});

// Auto-sync on blur (blur doesn't bubble — use capture phase)
document.addEventListener('blur', function(e) {
    if (e.target.classList.contains('coltman-wysiwyg-body')) {
        syncWysiwyg(e.target);
    }
}, true);

// On form submit: sync all wysiwyg bodies, then rebuild every accordion's hidden JSON
// from the current DOM so unsaved rows are always included.
document.addEventListener('submit', function() {
    document.querySelectorAll('.coltman-wysiwyg-body').forEach(syncWysiwyg);

    document.querySelectorAll('.accordion').forEach(function(accordion) {
        const hiddenInput = accordion.querySelector('input[type="hidden"]');
        if (!hiddenInput) return;
        const data = [];
        accordion.querySelectorAll('.accordion-item').forEach(function(item) {
            const titleEl   = item.querySelector('.input-title');
            const contentEl = item.querySelector('.input-content');
            const imageEl   = item.querySelector('.image-url-accodeon');
            const title     = titleEl ? titleEl.value.trim() : '';
            if (!title) return;
            data.push({
                id:      item.id,
                title:   title,
                content: contentEl ? contentEl.value : '',
                image:   imageEl   ? imageEl.value   : '',
            });
        });
        hiddenInput.value = JSON.stringify(data);
    });
});


// ── Gallery ───────────────────────────────────────────────────────────────────

function removeiTem(e) {
    const parentGallery = e.parentNode.parentNode.parentNode;
    const inputGallery = parentGallery.querySelector('input.gallery-data');
    const container = e.parentNode.parentNode;
    const item = e.parentNode;
    const dataItem = item.dataset.item;
    const inputItem = item.querySelector('.image-url');

    if (container.children.length > 1) {
        coltmanLog('more');
        const imageGallery = JSON.parse(inputGallery.value);
        inputGallery.value = JSON.stringify(imageGallery.filter((image) => image.item != dataItem));
        item.remove();
    } else {
        coltmanLog('less');
        inputItem.value = '';
        inputGallery.value = JSON.stringify([]);
    }
}

function addiTemImage(e){
    const gallery = e.closest('.coltman-gallery');
    const galleryContainer = gallery.querySelector('.gallery-container');
    const galleryItems = galleryContainer.querySelectorAll('.gallery-item');
    const galleryItemExample = galleryItems[0].cloneNode(true);
    const uniqueId = Date.now().toString() + Math.floor(Math.random() * 10000);
    galleryItemExample.dataset.item = uniqueId;
    galleryItemExample.querySelector('.image-url').value = '';
    const altInput = galleryItemExample.querySelector('.image-alt');
    if (altInput) altInput.value = '';
    const thumbEl = galleryItemExample.querySelector('.coltman-gallery-thumb');
    if (thumbEl) { thumbEl.src = ''; thumbEl.classList.remove('has-image'); }
    galleryContainer.appendChild(galleryItemExample);
    // Refresh sortable so the new item is draggable
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.sortable === 'function') {
        jQuery(galleryContainer).sortable('refresh');
    }
}

// ── List ──────────────────────────────────────────────────────────────────────

function addiTemList(e){
    const list = e.closest('.coltman-list');
    const container = list.querySelector('.list-container');
    const inputList = list.querySelector('input.list-data');
    const items = container.querySelectorAll('.list-item');
    const example = items[0].cloneNode(true);
    const uniqueId = Date.now().toString() + Math.floor(Math.random() * 10000);
    example.dataset.item = uniqueId;
    const textarea = example.querySelector('.list-textarea');
    if (textarea) textarea.value = '';
    container.appendChild(example);
    // Add empty entry to JSON so the textarea sync handler can find it
    const data = JSON.parse(inputList.value || '[]');
    data.push({item: uniqueId, text: ''});
    inputList.value = JSON.stringify(data);
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.sortable === 'function') {
        jQuery(container).sortable('refresh');
    }
}

function removeListItem(e) {
    const list = e.closest('.coltman-list');
    const inputList = list.querySelector('input.list-data');
    const container = list.querySelector('.list-container');
    const item = e.closest('.list-item');
    const dataItem = item.dataset.item;
    if (container.children.length > 1) {
        const listData = JSON.parse(inputList.value);
        inputList.value = JSON.stringify(listData.filter((entry) => entry.item != dataItem));
        item.remove();
    } else {
        const textarea = item.querySelector('.list-textarea');
        if (textarea) textarea.value = '';
        inputList.value = JSON.stringify([]);
    }
}

// ── Accordion ─────────────────────────────────────────────────────────────────

// Clones the first accordion item as a new empty item.
// The contenteditable approach means no TinyMCE reinit — just update data attributes and IDs.
function cloneElement(parentElment) {
    const parentAccordeon   = parentElment,
        accordion_container = parentAccordeon.querySelector('.accordion-container'),
        accordion_items     = parentAccordeon.querySelectorAll('.accordion-item');

    // Block clone when every visible item is still empty
    let proceed = true;
    accordion_items.forEach(function(item) {
        const itemTitle   = item.querySelector('.input-title');
        const itemContent = item.querySelector('.input-content');
        const itemImage   = item.querySelector('.image-url-accodeon');
        if (
            (itemTitle   ? itemTitle.value.trim()   : '') === '' &&
            (itemContent ? itemContent.value.trim() : '') === '' &&
            (itemImage   ? itemImage.value.trim()   : '') === ''
        ) {
            proceed = false;
        }
    });
    if (!proceed) return null;

    const baseItem = accordion_items[0].cloneNode(true);
    const baseId   = baseItem.dataset.id;

    const rand         = Math.floor(Math.random() * (10000 - 1000) + 1000);
    const newItemId    = baseId + '_' + rand + '_parent';
    const newContentId = baseId + '_' + rand + '_content';
    baseItem.id = newItemId;

    // Clear title and image
    const titleEl = baseItem.querySelector('.input-title');
    if (titleEl) titleEl.value = '';
    const imageEl = baseItem.querySelector('.image-url-accodeon');
    if (imageEl) imageEl.value = '';

    // Update contenteditable wysiwyg: clear content, rewire sync target
    const wysiwygWrap = baseItem.querySelector('.coltman-wysiwyg');
    if (wysiwygWrap) wysiwygWrap.dataset.for = newContentId;
    const wysiwygBody = baseItem.querySelector('.coltman-wysiwyg-body');
    if (wysiwygBody) {
        wysiwygBody.innerHTML    = '';
        wysiwygBody.dataset.sync = newContentId;
    }

    // Update hidden textarea id/name
    const contentTextarea = baseItem.querySelector('.input-content');
    if (contentTextarea) {
        contentTextarea.id    = newContentId;
        contentTextarea.name  = newContentId;
        contentTextarea.value = '';
    }

    coltmanLog('cloneElement newItemId:', newItemId, 'newContentId:', newContentId);
    accordion_container.appendChild(baseItem);
}

// "Add Row" button handler
function addAccordeonItem(e) {
    cloneElement(e.parentNode.parentNode);
}

// Removes an accordion item (or clears it if it's the only one)
function removeAccordeonItem(e) {
    const item       = e.parentNode.parentNode;
    const parentItem = item.parentNode;
    const post_accordionElement = parentItem.parentNode.querySelector('input[type="hidden"]');
    const itemId     = item.id;

    const postAccordeonData = JSON.parse(post_accordionElement.value);
    post_accordionElement.value = JSON.stringify(postAccordeonData.filter((i) => i.id != itemId));

    if (parentItem.children.length > 1) {
        item.remove();
    } else {
        const titleEl     = item.querySelector('.input-title');
        const imageEl     = item.querySelector('.image-url-accodeon');
        const contentEl   = item.querySelector('.input-content');
        const wysiwygBody = item.querySelector('.coltman-wysiwyg-body');
        if (titleEl)     titleEl.value     = '';
        if (imageEl)     imageEl.value     = '';
        if (contentEl)   contentEl.value   = '';
        if (wysiwygBody) wysiwygBody.innerHTML = '';
    }
}

// Saves the current accordion item to the field's hidden JSON (manual save)
function saveAccordeonItemData(e) {
    const parenContainer        = e.parentNode.parentNode.parentNode.parentNode;
    const item                  = e.parentNode.parentNode;
    const post_accordionElement = parenContainer.querySelector('input[type="hidden"]');
    const postAccordeonData     = JSON.parse(post_accordionElement.value);

    const titleEl     = item.querySelector('.input-title');
    const contentEl   = item.querySelector('.input-content');
    const imageEl     = item.querySelector('.image-url-accodeon');
    const wysiwygBody = item.querySelector('.coltman-wysiwyg-body');
    const itemId      = item.id;

    const titleValue = titleEl ? titleEl.value.trim() : '';
    if (!titleValue) return false;

    // Ensure wysiwyg is synced before reading the textarea
    if (wysiwygBody) syncWysiwyg(wysiwygBody);

    const contentValue = contentEl ? contentEl.value : '';
    const imageVal     = imageEl   ? imageEl.value   : '';

    const isDuplicate = postAccordeonData.some((i) =>
        i.id      === itemId       &&
        i.title   === titleValue   &&
        i.content === contentValue &&
        i.image   === imageVal
    );
    if (isDuplicate) return true;

    const existingIndex = postAccordeonData.findIndex((i) => i.id === itemId);
    const newData = { id: itemId, title: titleValue, content: contentValue, image: imageVal };

    if (existingIndex !== -1) {
        postAccordeonData[existingIndex] = newData;
    } else {
        postAccordeonData.push(newData);
    }

    post_accordionElement.value = JSON.stringify(postAccordeonData);
    return true;
}


// Saves the current item and adds a new empty row (combined Save+Clone flow)
function saveAccordeonItem(e) {
    const saved = saveAccordeonItemData(e);
    if (saved === false) return null;
    cloneElement(e.parentNode.parentNode.parentNode.parentNode);
}


function addBlockItem(e){
    coltmanLog(e);
}


// ── Repeater ──────────────────────────────────────────────────────────────────

// Clones the first row as a new empty row and appends it to the repeater.
function addRepeaterRow(btn) {
    const rowsContainer = btn.previousElementSibling; // .repeater-rows
    const rows = rowsContainer.querySelectorAll('.repeater-row');
    if (!rows.length) return;

    const template = rows[0].cloneNode(true);
    const newIndex = Date.now();

    template.dataset.index = newIndex;

    // Update all named inputs: field_id[OLD_INDEX][sub_id] → field_id[newIndex][sub_id]
    template.querySelectorAll('[name]').forEach(function(el) {
        el.name = el.name.replace(/\[\d+\](?=\[)/, '[' + newIndex + ']');
        if (el.tagName !== 'SELECT' && el.type !== 'checkbox' && el.type !== 'radio') {
            el.value = '';
        }
        if (el.type === 'checkbox') el.checked = false;
    });
    template.querySelectorAll('select').forEach(function(s) { s.selectedIndex = 0; });

    // Update HTML id and label-for: field_id_OLD_sub → field_id_newIndex_sub
    template.querySelectorAll('[id]').forEach(function(el) {
        el.id = el.id.replace(/_\d+_(?=[^_]+$)/, '_' + newIndex + '_');
    });
    template.querySelectorAll('label[for]').forEach(function(lbl) {
        lbl.htmlFor = lbl.htmlFor.replace(/_\d+_(?=[^_]+$)/, '_' + newIndex + '_');
    });

    // Update row number
    const rowNum = template.querySelector('.repeater-row-num');
    if (rowNum) rowNum.textContent = 'Row ' + (rows.length + 1);

    rowsContainer.appendChild(template);

    // Re-init color pickers added in the new row
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.wpColorPicker === 'function') {
        jQuery(template).find('.coltman-color-picker').wpColorPicker();
    }
}

// Removes a repeater row, or clears its inputs if it is the last row.
function removeRepeaterRow(btn) {
    const row = btn.closest('.repeater-row');
    const rowsContainer = row.parentNode;

    if (rowsContainer.querySelectorAll('.repeater-row').length > 1) {
        row.remove();
    } else {
        row.querySelectorAll('input:not([type="hidden"]), textarea').forEach(function(el) {
            el.value = '';
            if (el.type === 'checkbox') el.checked = false;
        });
        row.querySelectorAll('select').forEach(function(s) { s.selectedIndex = 0; });
    }

    // Re-number remaining rows
    rowsContainer.querySelectorAll('.repeater-row').forEach(function(r, i) {
        var num = r.querySelector('.repeater-row-num');
        if (num) num.textContent = 'Row ' + (i + 1);
    });
}
