/**
* Привязываем события в карточке товара
* 
*/
function initProductEvents()
{
    /**
    * Прокрутка нижних фото у товара в карточке
    */ 
    $('.gallery').jcarousel({
        items: "li:visible"
    }).swipeCarousel();
    $('.control').on({
        'inactive.jcarouselcontrol': function() {
            $(this).addClass('disabled');
        },
        'active.jcarouselcontrol': function() {
            $(this).removeClass('disabled');
        }
    });
    $('.control.prev').jcarouselControl({
        target: '-=3'
    });
    $('.control.next').jcarouselControl({
        target: '+=3'
    });
    
    /**
    * Открытие главного фото товара в colorbox
    */
    $('.no-touch .product a.main[rel="bigphotos"]').colorbox({
       rel:'bigphotos',
       className: 'titleMargin',
       opacity:0.2
    });
   
   /**
   * Нажатие на маленькие иконки фото
   */
   $('.gallery .preview').click(function() {
        var n = $(this).data('n');
        $('.product .main').addClass('hidden');
        $('.product .main[data-n="'+n+'"]').removeClass('hidden');
        return false;
    });
    
    //Переключение показа написания комментария
    $('.gotoComment').click(function() {
        $('.writeComment .title').switcher('switchOn');
    });
    
    $('.commentFormBlock .handler').click(function() {
        $(this).parent().addClass('open');
        return false;
    });
    
    $('.commentFormBlock .close').click(function() {
        $(this).closest('.commentFormBlock').removeClass('open');
        return false;
    });     
    
    if (location.hash=='#comments') {
        $('.product .rsTabs .tab').removeClass('act');
        $('.product .rsTabs .tab-comments').addClass('act');
        $('.product .rsTabs .tabList li').removeClass('act');
        $('.product .rsTabs .tabList [data-href=".tab-comments"]').addClass('act');
    }    
    
}

$(window).resize(function() {
    $('.gallery .scrollWrap').jcarousel('scroll', 0);
});

/**
* Скрипт активирует необходимые функции на странице просмотра товаров
*/
$(window).load(function() {
    initProductEvents();
});

/**
* Вешаемся на события обновления контента карточки товара
* 
*/
$(window).on('product.reloaded', function(){
    initProductEvents();
    $('.inDialog').openInDialog();
    $('.rsTabs').activeTabs();
});