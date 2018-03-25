/**
* @author ReadyScript lab.
* Плагин, инициализирующий загрузку новых страниц элементов при нажатии кнопки "показать еще", 
* кнопка может также автоматически нажиматься при попадании в область видимости экрана, 
* т.е. при опеделенном значении scroll
* 
* Плагин позволяет работать с любыми котроллерами поддерживащими обычную постраничную пагинацию.
*/
(function( $ ){
    $.fn.ajaxPagination = function( method ) {
        var defaults = {
            method: 'get',      //каким методом делать запрос на сервер
            appendElement: '',  //какой элемент DOM дополнять новыми значениями
            findElement: null,  //в каком элементе DOM из response искать данные для вставки. Задайте false, чтобы вставлять весь response
            clickOnScroll:false,            //Автоматически выполнять подгрузку при попадании в зону видимости
            context: 'body',                //Ограничивает работу пагина данным элементом. Применяется когда на странице несколько списков с загрузкой
            loaderElement: '.ajaxPaginator',    //какой элемент DOM в response считать новым elseLoader'ом (если не будет найден, то elseLoader пропадет)
            loadingClass: 'inloading',          //класс, который добавляется кнопке "показать еще" во время загрузки контента
            scrollDisance: 100                 //расстояние до кнопки "показать еще" в px, на котором уже начинает загрузка данных
        },
        args = arguments;
        
        return this.each(function() {
            var $this = $(this), 
                data = $this.data('ajaxPagination');

            var methods = {
                init: function(initoptions) {
                    if (data) return;
                    data = {}; $this.data('ajaxPagination', data);
                    data.options = $.extend({}, defaults, initoptions, $this.data('paginationOptions'));
                    if (data.options.findElement === null) {
                        data.options.findElement = data.options.appendElement;
                    }
                    data.context = $(data.options.context);
                    $this.on('click', methods.load);
                    if (data.options.clickOnScroll) {
                        $(window).bind('scroll', methods.checkScroll);
                    }
                },
                load: function() {
                    if ($this.hasClass(data.options.loadingClass)) return false;
                    
                    var href = $this.attr('href') ? $this.attr('href') : $this.data('href');
                    $this.addClass(data.options.loadingClass);
                    
                    $.ajax({
                        url: href,
                        dataType: 'json',
                        type: data.options.method,
                        success: function(response) {
                            var parsed = $(response.html);
                            var appendData = parsed.filter(data.options.findElement)
                                                .add(parsed.find(data.options.findElement))
                                                .children();
                            
                            $(data.options.appendElement, data.context).append(appendData);
                            
                            //Обновляем элемент "показать еще"
                            var new_loader = parsed.filter(data.options.loaderElement)
                                                .add(parsed.find(data.options.loaderElement))
                                                .data('ajaxPagination', data)
                                                .on('click', methods.load);
                            
                            $(data.options.loaderElement, data.context).replaceWith(new_loader);
                            $this = new_loader;
                        }
                    });
                    return false;
                },
                checkScroll: function() {
                    if ($this.length) {
                        var bottom = $(window).scrollTop() + $(window).height() + data.options.scrollDisance;
                        if (!$this.hasClass(data.options.loadingClass) && bottom > $this.offset().top) {
                            $this.trigger('click');
                        }
                    } else {
                        $(window).unbind('scroll', methods.checkScroll);
                    }
                }
            }
            
            if ( methods[method] ) {
                methods[ method ].apply( this, Array.prototype.slice.call( args, 1 ));
            } else if ( typeof method === 'object' || ! method ) {
                return methods.init.apply( this, args );
            }
        });
    }
})( jQuery );


$(function() {
    $('.ajaxPaginator', this).ajaxPagination();
});