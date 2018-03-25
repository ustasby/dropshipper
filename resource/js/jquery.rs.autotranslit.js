/**
* Plugin, активирующий автоматическую транслитерацию поля
* @author ReadyScript lab.
*/
(function($){
    $.fn.autoTranslit = function(method) {
        var defaults = {
            formAction: 'form[action]',
            context:'form, .virtual-form',
            addPredicate: '=add',
            targetName: null,
            showUpdateButton: true,
        }, 
        args = arguments;
        
        return this.each(function() {
            var $this = $(this), 
                data = $this.data('autoTranslit');
            
            var methods = {
                init: function(initoptions) {                    
                    if (data) return;
                    data = {}; $this.data('autoTranslit', data);
                    data.options = $.extend({}, defaults, initoptions);
                    if ($this.data('autotranslit')) {
                        data.options.targetName = $this.data('autotranslit');
                    }
                    data.options.target = $('input[name="'+data.options.targetName+'"]', $(this).closest(data.options.context));
                    if (data.options.target) {
                        //Подключаем автоматическую транслитерацию, если происходит создание объекта
                        var isAdd = $this.closest(data.options.formAction).attr('action').indexOf(data.options.addPredicate) > -1;
                        if (isAdd) {
                            $this.on('blur', onBlur);
                        }
                        if (data.options.showUpdateButton) {
                            var update = $('<a class="update-translit"></a>').click(onUpdateTranslit).attr('title', lang.t('Транслитерировать заново'));
                            $(data.options.target).after(update).parent().trigger('new-content');
                        }
                    }
                }
            }
            
            //private 
            var onBlur = function() {
                if (data.options.target.val() == '') {
                    onUpdateTranslit();
                }
            },
            onUpdateTranslit = function() {
                data.options.target.val( translit( $this.val() ) );
            },
            translit = function( text ) {
                return text.replace( /([а-яё])|([\s_])|([^a-z\d])/gi,
                    function( all, ch, space, words, i ) {
                        if ( space || words ) {
                            return space ? '-' : '';
                        }

                        var code = ch.charCodeAt(0),
                            next = text.charAt( i + 1 ),
                            index = code == 1025 || code == 1105 ? 0 :
                                code > 1071 ? code - 1071 : code - 1039,
                            t = ['yo','a','b','v','g','d','e','zh',
                                'z','i','y','k','l','m','n','o','p',
                                'r','s','t','u','f','h','c','ch','sh',
                                'shch','','y','','e','yu','ya'
                            ],
                            next = next && next.toUpperCase() === next ? 1 : 0;

                        return ch.toUpperCase() === ch ? next ? t[ index ].toUpperCase() :
                                t[ index ].substr(0,1).toUpperCase() +
                                t[ index ].substring(1) : t[ index ];
                    }
                ).toLowerCase();
            };
            
            
            
            if ( methods[method] ) {
                methods[ method ].apply( this, Array.prototype.slice.call( args, 1 ));
            } else if ( typeof method === 'object' || ! method ) {
                return methods.init.apply( this, args );
            }
        });
    }

    $.contentReady(function() {
        $('input[data-autotranslit]', this).autoTranslit();
    });

})(jQuery);