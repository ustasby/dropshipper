/**
* Привязываем события в карточке товара
* 
*/
function initProductEvents()
{
    /**
    * Прокрутка нижних фото у товара в карточке
    */ 
    $('.gallery .wrap').jcarousel({
        items: "li:visible"
    });
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
    
    $(window).resize(function() {
        $('.gallery .scrollWrap').jcarousel('scroll', 0);
    })
    
    /**
    * Увеличение фото в карточке
    */
    $('.productImages .zoom').each(function() {
        $(this).zoom({
            url: $(this).data('zoom-src'),
            onZoomIn: function() {
                $(this).siblings('.winImage').css('visibility', 'hidden');
                
            },
            onZoomOut: function() {
                $(this).siblings('.winImage').css('visibility', 'visible');
            }            
        });
    });
    
    /**
    * Открытие главного фото товара в colorbox
    */ 
    $('.product .main a.item[rel="bigphotos"]').colorbox({
       rel:'bigphotos',
       className: 'titleMargin',
       opacity:0.2
    });
   
    /**
    * Нажатие на маленькие иконки фото
    */
    $('.gallery .preview').click(function() {
        var n = $(this).data('n');
        $('.product .main .item').addClass('hidden');
        $('.product .main .item[data-n="'+n+'"]').removeClass('hidden');
        
        return false;
    });
    
    //Переключение показа написания комментария
    $('.gotoComment').click(function() {
        $('.writeComment .title').switcher('switchOn');
    });
}

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
    $('.tabs').activeTabs();
});