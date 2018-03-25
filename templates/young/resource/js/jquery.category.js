/**
* @author ReadyScript lab.
* Плагин корректно размещает горизонтальный выпадающий список категорий на экране
*/
(function( $ ){
    $.fn.category = function( method ) {
        var defaults = {
            menu: '.catalog',
            topitem: '>li',
            submenu: '>ul',
            correct:-10           
        },
        args = arguments;
        
        return this.each(function() {
            var $this = $(this), 
                data = $this.data('category');

            var methods = {
                init: function(initoptions) {
                    if (data) return;
                    data = {}; $this.data('category', data);
                    data.options = $.extend({}, defaults, initoptions);
                    
                    $(data.options.menu, $this).find(data.options.topitem).mouseenter(function() {
                        var max_right = $this.offset().left + $this.width();
                        var right = $(this).offset().left + $(data.options.submenu, this).width();
                        if (right > max_right) {
                            $(data.options.submenu, this).css('left', max_right-right+data.options.correct);
                        } else {
                            $(data.options.submenu, this).css('left', 0);
                        }
                    });
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