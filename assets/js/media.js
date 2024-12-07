

jQuery.noConflict();

(function($) {
   
    $(document).ready(function($) {
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
                console.log(attachment);
                button.prev().val(attachment[button.data('return')]);
                if(e.currentTarget.parentNode.classList.contains('get-image')){
                    const imageItem = e.currentTarget.parentNode
                    const gallery = imageItem.parentNode.parentNode.parentNode
                    const inputGallery = gallery.querySelector('input.gallery-data');
                    const inputItem = imageItem.querySelector('.image-url');
                    const dataItem = imageItem.parentNode.dataset.item
                    let imageGallery = JSON.parse(inputGallery.value)
                    inputGallery.value = JSON.stringify([...imageGallery,{id: attachment.id, alt: attachment.alt,sizes: attachment.sizes,title: attachment.title,mime: attachment.mime, height: attachment.height, width: attachment.width, item: dataItem, url: inputItem.value}]);
                }
            }).open();
        });
    });
  /*   $('.get_posts').select2({
         placeholder: 'Select an option',
         search: true,
    }); */

    
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

// Copy Accordeon Item
function cloneElement(parentElment){
   // console.log(parentElment);
    const parentAccordeon = parentElment,
        post_accordion = parentAccordeon.querySelector('input[type="hidden"]')
        accordion_container = parentAccordeon.querySelector('.accordion-container'),
        accordion_items = parentAccordeon.querySelectorAll('.accordion-item');
    const baseItem = accordion_items[0].cloneNode(true);
    const  title = baseItem.querySelector('.input-title');
    const content = baseItem.querySelector('textarea');
    const image = baseItem.querySelector('.image-url-accodeon')

    let proced = true;

    accordion_items.forEach((item) => {
       if (item.querySelector('.input-title').value == '' && item.querySelector('textarea').value == '' && image.value == '') {
        console.log('object');
        proced = false;
       }
    });

    if (proced === false) return null;
    title.value = '';
    content.value = '';
    image.value = ''; 
    const baseId = baseItem.dataset.id
    const post_accordion_id = baseId +'_'+ (Math.floor(Math.random() * (10000 - 1000) + 1000)).toLocaleString()+"_parent";
    baseItem.id = post_accordion_id;
   /*  baseItem.querySelector('texarea').id = baseId +'_'+ (Math.floor(Math.random() * (10000 - 1000) + 1000)).toLocaleString()+'-content'; */
    console.log(content.id);
    content.id = baseId +'_'+ (Math.floor(Math.random() * (10000 - 1000) + 1000)).toLocaleString()+'-content';
    accordion_container.appendChild(baseItem );

  
}

// add Accordeon Item
function addAccordeonItem(e){
   const parentAccordeon = e.parentNode.parentNode;
   const post_accordion = parentAccordeon.querySelector('input[type="hidden"]')
   const accordion_items = parentAccordeon.querySelectorAll('.accordion-item');
   const post_accordion_data = parentAccordeon.querySelector('input[type="hidden"]').value;
   const post_accordionData = JSON.parse(post_accordion_data);
   accordion_items.forEach((item,index) => {
      const title = item.querySelector('.input-title').value;
      const textarea = item.querySelector('textarea').value;
      const image = item.querySelector('.image-url-accodeon').value;
      console.log(image);
      const post_accordion_id = item.id;
      //if (title == '' && textarea == '') return null;
      const itemData = {id: post_accordion_id, title: title, content: textarea, image: image};
      if(post_accordionData.find((post_accordion_item) => post_accordion_item.id === post_accordion_id)===undefined && title !== '') {
         post_accordionData.push(itemData);
         post_accordion.value = JSON.stringify(post_accordionData);
      };
   })
   cloneElement(parentAccordeon);
}


// remove Accordeon Item
function removeAccordeonItem(e){

    const item = e.parentNode.parentNode;
    const parentItem = item.parentNode;
    const post_accordionElement = parentItem.parentNode.querySelector('input[type="hidden"]');
    const itemId = item.id;
    const postAccordeonData = JSON.parse(post_accordionElement.value);
    const newpostAccordeonData = postAccordeonData.filter((item) => item.id != itemId);
    post_accordionElement.value = JSON.stringify(newpostAccordeonData);

    console.log(post_accordionElement, 'post_accordionElement');
    if(parentItem.children.length>1){
        item.remove();
    }else{
        item.querySelector('.input-title').value = '';
        item.querySelector('textarea').value = '';
        item.querySelector('.image-url-accodeon').value = '';
    };

}

// save Accordeon Item data
function saveAccordeonItemData(e){
    const parenContainer = e.parentNode.parentNode.parentNode.parentNode;
    const item = e.parentNode.parentNode;
    console.log(item,'item');
    const post_accordionElement = parenContainer.querySelector('input[type="hidden"]');
    const postAccordeonData = JSON.parse(post_accordionElement.value);
    const title = item.querySelector('.input-title');
    const content = item.querySelector('textarea');
    const image = item.querySelector('.image-url-accodeon');
    const itemId = item.id;
    console.log(title.value, content.value, 'title, content');
    if (title.value =='') return false;

    const newpostAccordeonData = postAccordeonData.push({id: itemId, title: title.value, content: content.value, image: image.value});
    console.log(postAccordeonData);
    post_accordionElement.value = JSON.stringify(postAccordeonData);
    return true
}

// save Accordeon Item
function saveAccordeonItem(e){
    const saved = saveAccordeonItemData(e);
    if (saved === false) return null;
    cloneElement(e.parentNode.parentNode.parentNode.parentNode);
}


function addBlockItem(e){
    console.log(e);
}
