/**
* Плагин инициализирует работу контекстных меню в режиме редактирования
*
* @author ReadyScript lab.
*/
(function($){

    $.fn.debugContextMenu = function(method) {
        var defaults = {
            moduleWrap: '.module-wrapper',
            menuId: 'debug-context-box',
            correction: {
                x:-10,
                y:15
            }
        },
        args = arguments;

        return this.each(function() {
            var $this = $(this),
                data = $this.data('debugContext');

            var methods = {
                init: function(initoptions) {
                    if (data) return;
                    data = {}; $this.data('debugContext', data);
                    data.options = $.extend({}, defaults, initoptions);
                    data.items = $this.data('debugContextmenu');
                    if (data.items.length) {
                        $this.on('contextmenu.debugContext', showContextMenu);
                    }
                },
                hide: function() {
                    $('#'+data.options.menuId).hide();
                    var menuData = $('#'+data.options.menuId).data('debugContextMenu');
                    if (menuData) {
                        menuData.module.removeClass('hover');
                    }
                }
            };

            var showContextMenu = function(e) {
                methods.hide();
                var module = $(e.currentTarget).closest(data.options.moduleWrap).addClass('hover');

                var $menu = getContextMenuDiv(data.items, module);
                $menu.css({
                    top:e.pageY + data.options.correction.y,
                    left:e.pageX + data.options.correction.x
                }).data('debugContextMenu', {module:module}).trigger('showContextMenu');
                e.preventDefault();
                e.stopPropagation();
            },

            getContextMenuDiv = function(items, module_wrapper) {
                //Контейнер, который следует обновить, после выполнения действия
                var updateContainer = $('.module-content.updatable', module_wrapper);

                var $menu = $('#'+data.options.menuId);
                if (!$menu.length) {
                    $menu = $('<div id="'+data.options.menuId+'">'+
                                  '<div class="debug-context-back" />'+
                                  '<i class="debug-context-corner" />'+
                                  '<ul class="debug-context-items" />'+
                              '</div>').appendTo('body');

                    $(module_wrapper).on('click.debugContext', function(e){e.stopPropagation();});
                    $('html').on('click.debugContext', function(e) {
                        methods.hide();
                    })
                    .on('keypress.debugContext', function(e) {if (e.keyCode == 27 ) methods.hide();} );
                }
                var $ul = $menu.show().find('.debug-context-items').empty();
                for(var i in items) {
                    var $li = $('<li />').append(
                        $('<a />').attr(items[i].attributes).html(items[i].title)
                            .bind('click', function() {
                                methods.hide();
                            })
                            .data('crudOptions', {updateElement: updateContainer, ajaxParam:{noUpdateHash: true}})
                    );
                    $ul.append($li);
                }
                $menu.trigger('new-content');
                return $menu;
            };


            if ( methods[method] ) {
                methods[ method ].apply( this, Array.prototype.slice.call( args, 1 ));
            } else if ( typeof method === 'object' || ! method ) {
                return methods.init.apply( this, args );
            }
        });
    };

    $.adaptTop = function() {
        var checkWidth = function() {
            if ($(document).width() <= 760 && !$('#debug-top-block').hasClass('debug-mobile')) {
                $('#debug-top-block').addClass('debug-mobile');
            }
            if ($(document).width() > 760 && $('#debug-top-block').hasClass('debug-mobile')) {
                $('#debug-top-block').removeClass('debug-mobile');
            }
        };
        $(window).on('resize.detectMedia', checkWidth);
        checkWidth();
    };

    $(function()
    {
        $('.module-wrapper').hover(function() {
            $(this).addClass('over');
        }, function() {
            $(this).removeClass('over');
        });
        $('.debug-hint').on('click remove', function() {
                $(this).tooltip('hide');
            })
            .tooltip({
                trigger:'hover',
                html:true,
                placement:'bottom',
                container:$('.admin-style:first')});
        $('.module-tools').draggable({handle:'.dragblock'});

        //Придаем обрамляющему блоку, некоторые стили от внутреннего контента
        $('.module-wrapper .module-content').each(function() {
            var $item = $(this).find('> *:first');
            var $wrapper = $(this).closest('.module-wrapper');
            var keys = ['display', 'float'];
            $.each(keys, function(k, v) {
                var new_val = $item.css(v);
                if (v == 'display' && (new_val == 'inline' || new_val == 'none')) new_val = 'inline-block';
                if (v == 'display' && new_val == 'flex') new_val = 'block';
                $wrapper.css(v, new_val);
            });

            if ($item.css('position') == 'absolute') {
                $item.insertAfter($wrapper);

                var newStyle = {
                    position:       'absolute',
                    marginTop:      $item.css('margin-top'),
                    marginBottom:   $item.css('margin-bottom'),
                    marginLeft:   $item.css('margin-left'),
                    marginRight:   $item.css('margin-right'),
                    left:           $item.css('left'),
                    top:            $item.css('top')
                },
                itemStyle = {
                    position:'static',
                    marginLeft:     0,
                    marginRight:    0,
                    marginTop:      0,
                    marginBottom:   0
                };
                $wrapper.css(newStyle).find('.module-content').append($item.css(itemStyle));
            }
        });

        //Добавляем элемент, который будет сужить псевдо тегом body для элементов админисративной панели
        //Испльзуется для избежания конфликтов CSS.
        $('<div class="admin-body admin-style"/>').appendTo('body');
        $.adaptTop();
    });

    $.contentReady(function() {
        $('[data-debug-contextmenu]', this).debugContextMenu();
        $('.debug-mode-switcher .toggle-switch').off();
        $('.debug-mode-switcher .toggle-switch').on($.rs.clickEventName, function() {
            $.ajaxQuery({
                url: $(this).data('url'),
                success: function() {
                    location.reload();
                }
            });
        });
    });

})(jQuery);