/**
* Скрипт инициализирует стандартные функции для работы темы
*/
$.detectMedia = function( checkMedia ) {
    var init = function() {
        var detectMedia = function() {
            var 
                currentMedia = $('body').data('currentMedia'),
                newMedia = '';
                
            if ($(document).width() < 760) {
                newMedia = 'mobile';
            }
            if ($(document).width() >= 760 && $(document).width() <= 980) {
                newMedia = 'portrait';
            }
                        
            if (currentMedia != newMedia) {
                $('body').data('currentMedia', newMedia);
            }
        }        
        $(window).on('resize.detectMedia', detectMedia);
        detectMedia();
    }
    
    var check = function(media) {
        return $('body').data('currentMedia') == media;
    }
    
    if (checkMedia) {
        return check(checkMedia);
    } else {
        init();
    }
};

//Инициализируем работу data-href у ссылок
$.initDataHref = function() {
    $('a[data-href]:not(.addToCart):not(.applyCoupon):not(.ajaxPaginator)').on('click', function() {
        if ($.detectMedia('mobile') || !$(this).hasClass('inDialog')) {
            location.href = $(this).data('href');
            console.log('href');
        }
    });
};

//Инициализируем работу блока, скрывающего длинный текст
$.initCut = function() {
    $('.rs-cut').each(function(){
        $(this).css('max-height', ($(this).data('cut-height')) ? $(this).data('cut-height') : '200px');
        $(this).append('<div class="cut-switcher"></div>');
        $(this).children().last().click(function(){
            if ($(this).parent().hasClass('open')) {
                $(this).parent().css('max-height', ($(this).parent().data('cut-height')) ? $(this).parent().data('cut-height') : '200px');
            } else {
                $(this).parent().css('max-height', '10000px');
            }
            $(this).parent().toggleClass('open');
        });
    });
};

$(function() {
    
    //Решение для корректного отображения масштаба в Iphone, Ipad
    if (navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPad/i)) {
        var viewportmeta = document.querySelector('meta[name="viewport"]');
        if (viewportmeta) {
            viewportmeta.content = 'width=device-width, minimum-scale=1.0, maximum-scale=1.0, initial-scale=1.0';
            document.body.addEventListener('gesturestart', function () {
                viewportmeta.content = 'width=device-width, minimum-scale=0.25, maximum-scale=1.6';
            }, false);
        }
    }//----
    
    //Инициализируем корзину
    $.cart({
        cartTotalItems: '.productCount .value',
        saveScroll: '.scrollCartWrap',
        cartItemRemove: '.iconX'
    }); 
    
    $('.inDialog').openInDialog();
    $.detectMedia();
    $.initDataHref();


    //Инициализируем быстрый поиск по товарам
    $(window).resize(function() {
        $( ".query.autocomplete" ).autocomplete( "close" );
    });


    $.initDataHref();
    $.initCut();

    //Коррекция меню категорий для ipad
    if ($('html').hasClass('touch')) {
        $('.topMenu > li.node > a').click(function(e) { e.preventDefault(); });
    }
    
    $( ".query.autocomplete" ).each(function() {
        $(this).autocomplete({
            source: $(this).data('sourceUrl'),
            appendTo: '#queryBox',
            minLength: 3,
            select: function( event, ui ) {
                location.href=ui.item.url;
                return false;
            },
            messages: {
                noResults: '',
                results: function() {}
            }
        }).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
            ul.addClass('searchItems');
            var li = $( "<li />" );
            var link_class = "";
            if (item.image){
                var img = $('<img />').attr('src', item.image).css('visibility', 'hidden').load(function() {
                    $(this).css('visibility', 'visible');
                });    
                li.append($('<div class="image" />').append(img));
            }else{
                link_class = "class='noimage'";
            }

            if (item.type == 'search'){
                li.addClass('allSearchResults');
            }
            
            var item_html = '<a '+link_class+'><span class="label">' + item.label + '</span>';
            if (item.barcode){ //Если артикул есть
               item_html += '<span class="barcode">' + item.barcode + '</span>';
            }else if (item.type == 'brand'){
                item_html += '<span class="barcode">' + lang.t('Проиводитель') + '</span>';
            }else if (item.type == 'category'){
                item_html += '<span class="barcode">' + lang.t('Категория') + '</span>';
            }
            if (item.price){ //Если цена есть
               item_html += '<span class="price">' + item.price + '</span>';
            }
            console.log(item);
            if (item.preview){ //Если цена превью (для статей)
               item_html += '<span class="preview">' + item.preview + '</span>';
            }
            item_html += '</a>';
            
            return li
                .append( item_html )
                .appendTo( ul );
        };
    });
});
$(function() {

    $('.node > a').click(function() {
        if ($.detectMedia('mobile')) {
            $(this).closest('.node').toggleClass('open');
            return false;
        }
    });

    $('.menuGrid').category({
        menu: '.topCategory',
        topitem: '>li',
        submenu: '>ul',
        correct:-10
    });
});
//Инициализируем обновляемые зоны
$(window).bind('new-content', function(e) {
    $('.inDialog', e.target).openInDialog();
    $('.rs-parent-switcher', e.target).switcher({parentSelector: '*:first'});
    $.initDataHref();
    $.initCut();
});

