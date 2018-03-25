/**
* Активирует работу комментариев
*/
/**
* Плагин, инициализирующий работу комментариев
*/
(function( $ ){
    $.fn.comments = function( method ) {
        var defaults = {
            stars: '.stars i',
            rate: '.rate',
            inputRate: '.inp_rate',
            rateDescr: '.rate .descr',
            rateText: [
                lang.t('нет оценки'),
                lang.t('ужасно'),
                lang.t('плохо'),
                lang.t('нормально'),
                lang.t('хорошо'),
                lang.t('отлично')]  
        },
        args = arguments;
        
        return this.each(function() {
            var $this = $(this), 
                data = $this.data('comments');

            var methods = {
                init: function(initoptions) {
                    if (data) return;
                    data = {}; $this.data('comments', data);
                    data.options = $.extend({}, defaults, initoptions);
                    
                    $(data.options.stars, $this).mouseover(overStar).click(setMark);
                    $(data.options.rate, $this).mouseout(restoreStars);
                    restoreStars();
                }

            };
            
            //private 
            var selectStars = function(index)
            {
                var li_all = $(data.options.stars, $this).removeClass('act');
                for(var i=0; i<=index-1; i++) {
                    $(li_all[i]).addClass('act');
                }
                
                $(data.options.rateDescr, $this).html( data.options.rateText[index] );
            },
            
            overStar = function() 
            {
                selectStars( $(this).index()+1 );
            },
                        
            restoreStars = function()
            {
                selectStars( $(data.options.inputRate, $this).val() );
            },
            
            setMark = function()
            {
                $(data.options.inputRate).val( $(this).index()+1 );
            };

            if ( methods[method] ) {
                methods[ method ].apply( this, Array.prototype.slice.call( args, 1 ));
            } else if ( typeof method === 'object' || ! method ) {
                return methods.init.apply( this, args );
            }
        });
    }

})( jQuery );


$(function() {
    $('.comments').comments();

    $('body').on('product.reloaded', function() {
        $('.comments').comments();
    });
});


