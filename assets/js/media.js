
jQuery.noConflict();
(function($) {
    $(function() {
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
                button.prev().val(attachment[button.data('return')]);
                if(e.currentTarget.parentNode.classList.contains('get-image')){
                    const imageItem = e.currentTarget.parentNode
                    const gallery = imageItem.parentNode.parentNode.parentNode
                    const inputGallery = gallery.querySelector('input.gallery-data');
                    const inputItem = imageItem.querySelector('.image-url');
                    const dataItem = imageItem.parentNode.dataset.item
                    let imageGallery = JSON.parse(inputGallery.value)
                    inputGallery.value = JSON.stringify([...imageGallery,{item: dataItem, url: inputItem.value}]);
                }
            }).open();
        });
        $('.rwp-color-picker').wpColorPicker();
    });
})(jQuery);


function removeiTem(e) {
    const parentGallery = e.parentNode.parentNode.parentNode;
    const inputGallery = parentGallery.querySelector('input.gallery-data');
    const container = e.parentNode.parentNode;
    const item = e.parentNode;
    const dataItem = item.dataset.item
    const inputItem = item.querySelector('.image-url');

    if (container.children.length > 1) {
        console.log('more');
        const imageGallery = JSON.parse(inputGallery.value);
        inputGallery.value = JSON.stringify(imageGallery.filter((image) => image.item != dataItem));
        item.remove();
    }else{
        console.log('less');
        inputItem.value = '';
        inputGallery.value = JSON.stringify([]);
    }
}

function addiTemImage(e){
    const gallery = e.parentNode;
    const galleryContainer = gallery.querySelector('.gallery-container');
    const galleryItems = gallery.querySelectorAll('.gallery-item');
    const imageCount = galleryItems.length;
    const galleryItemExample = galleryItems[0].cloneNode(true);
    const uniqueId = Date.now().toString() + Math.floor(Math.random() * 10000);
    galleryItemExample.dataset.item = uniqueId;

    galleryItemExample.querySelector('.image-url').value = '';
    
    galleryContainer.appendChild(galleryItemExample);
}

