
const COLTMAN_DEBUG = false;
const coltmanLog = (...args) => COLTMAN_DEBUG && console.log(...args);

jQuery.noConflict();

(function($) {

    $(document).ready(function($) {

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
                button.prev().val(attachment[button.data('return')]);
                if(e.currentTarget.parentNode.classList.contains('get-image')){
                    const imageItem = e.currentTarget.parentNode;
                    const gallery = imageItem.parentNode.parentNode.parentNode;
                    const inputGallery = gallery.querySelector('input.gallery-data');
                    const inputItem = imageItem.querySelector('.image-url');
                    const dataItem = imageItem.parentNode.dataset.item;
                    let imageGallery = JSON.parse(inputGallery.value);
                    inputGallery.value = JSON.stringify([...imageGallery,{id: attachment.id, alt: attachment.alt, sizes: attachment.sizes, title: attachment.title, mime: attachment.mime, height: attachment.height, width: attachment.width, item: dataItem, url: inputItem.value}]);
                }
            }).open();
        });

        // ── Accordion sortable ────────────────────────────────────────────────
        if (typeof $.fn.sortable === 'function') {
            $('.accordion-container').sortable({
                handle:      '.accordion-drag-handle',
                axis:        'y',
                cursor:      'grabbing',
                tolerance:   'pointer',
                placeholder: 'accordion-sort-placeholder',
                forcePlaceholderSize: true,
            });
        }

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

    });

})(jQuery);


// ── Helpers ───────────────────────────────────────────────────────────────────

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

// Copies contenteditable innerHTML to the associated hidden textarea
function syncWysiwyg(body) {
    const id = body.dataset.sync;
    if (!id) return;
    const textarea = document.getElementById(id);
    if (textarea) textarea.value = body.innerHTML;
}

// Toolbar button clicks — delegated on document so cloned items work immediately
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.coltman-wysiwyg-btn');
    if (!btn) return;
    e.preventDefault();
    const cmd     = btn.dataset.cmd;
    const wysiwyg = btn.closest('.coltman-wysiwyg');
    const body    = wysiwyg ? wysiwyg.querySelector('.coltman-wysiwyg-body') : null;
    if (!body) return;
    body.focus();
    if (cmd === 'createLink') {
        const url = window.prompt(btn.title || 'URL:');
        if (!url) return;
        document.execCommand('createLink', false, url);
    } else {
        document.execCommand(cmd, false, null);
    }
    syncWysiwyg(body);
});

// Auto-sync on every keystroke / paste
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('coltman-wysiwyg-body')) {
        syncWysiwyg(e.target);
    }
});

// Auto-sync on blur (blur doesn't bubble, so use capture phase)
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
    const gallery = e.parentNode;
    const galleryContainer = gallery.querySelector('.gallery-container');
    const galleryItems = gallery.querySelectorAll('.gallery-item');
    const galleryItemExample = galleryItems[0].cloneNode(true);
    const uniqueId = Date.now().toString() + Math.floor(Math.random() * 10000);
    galleryItemExample.dataset.item = uniqueId;
    galleryItemExample.querySelector('.image-url').value = '';
    galleryContainer.appendChild(galleryItemExample);
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
