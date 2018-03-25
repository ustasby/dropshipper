/**
* Плагин, активирует просмотр древовидных списков в административной панели ReadyScript
*
* @author ReadyScript lab.
*/
(function( $ ){

  $.fn.treeview = function( type ) {  
      
    return this.each(function() {
        if ($(this).data('treeview')) return false;
        $(this).data('treeview', {});
        
        var activetree = this;
        var $treeview = $('.treebody', this);
        var maxLevels = $treeview.data('maxLevels') ? $treeview.data('maxLevels') : 0;

        if ($treeview.is('.treesort')) {
            //Инициализируем сортировку внутри дерева
            $treeview.nestedSortable({
                maxLevels: maxLevels,
                branchClass: 'tree-branch',
                collapsedClass: 'tree-collapsed',
                expandedClass: 'tree-expanded',
                leafClass: 'tree-leaf',
                tabSize:30,
                disableParentChange: $treeview.data('noExpandCollapse'),
                isTree:!$treeview.data('noExpandCollapse'),
                forcePlaceholderSize:true,
                handle:'.move',
                placeholder: {
                    element: function(currentItem, ui) {
                        var placeholder = $(currentItem)
                                    .clone()
                                    .addClass('tree-placeholder')
                                    .removeClass('current')
                                    .css({
                                        position:'static',
                                        width:'auto'
                                    });

                        placeholder.find('.chk').empty();
                        placeholder.find('.line').empty();
                        return placeholder[0];

                    },
                    update: function(container, p) {
                        return;
                    }
                },
                listType: 'ul',
                items:'li[data-id]:not([data-notmove])',
                disabledClass:'noDraggable',
                expandOnHover:false,
                toleranceElement: '>.item',
                update:function(event, ui) {

                    var source_id = ui.item.data('id');

                    if (ui.item.parent().is('.treesort')) {
                        var parent_id = 0;
                    } else {
                        var parent_id = ui.item.parents('li[data-id]').data('id');
                    }

                    if (ui.item.next().length) {
                        var destination_id = ui.item.next().data('id');
                        var destination_direction = 'up';
                    } else {
                        var destination_id = ui.item.prev().data('id');
                        var destination_direction = 'down';
                    }

                    $.ajaxQuery({
                        url: $treeview.data('sortUrl'),
                        data: {
                            from:source_id,
                            to:destination_id,
                            flag:destination_direction,
                            parent:parent_id
                        }
                    });
                }
            });
        }

        var toggle = function()
        {
            var jquery_el = $(this).closest('li')
                                   .toggleClass('tree-collapsed')
                                   .toggleClass('tree-expanded');
            updatecookie.call(activetree);
            return false;
        }
        
        var openAll = function()
        {
            $('.tree-branch', $treeview)
                    .removeClass('tree-collapsed')
                    .addClass('tree-expanded');
            updatecookie.call(activetree);
        }
        
        var closeAll = function()
        {
            $('.tree-branch', $treeview)
                .addClass('tree-collapsed')
                .removeClass('tree-expanded');
            updatecookie.call(activetree);
        }
        
        var updatecookie = function()
        {
            if (!$(this).data('uniq')) return false;
            $(this).trigger('changeSize');

            var ids = new Array();
            $('li.tree-collapsed', $treeview).each(function() {
                ids.push($(this).data('id'));
            });
            
            if (ids.length > 0) {
                $.cookie($(this).data('uniq'), ids.join(','), {expires: 365})
            } else {
                $.cookie($(this).data('uniq'), null);
            }
        }

        $(this)
            .on('click', '.toggle', toggle)
            .on('click', '.allplus', openAll)
            .on('click', '.allminus', closeAll);

        /**
        * Устанавливаем красные маркеры
        */
        $($treeview).on('change', '.chk input[type="checkbox"]', function() {
            if (this.checked) {
                $(this).closest('li').find('>.item > .line > .redmarker').addClass('r_on');
                $(this).parents('.treebody li').each(function() {
                    $('>.item > .line > .redmarker', this).addClass('r_on');
                })
                
            } else {
                if (!$(this).closest('li').find('.r_on').length) {
                    $(this).closest('li').find('> .item > .line > .redmarker').removeClass('r_on');
                }
                
                $(this).parents('.treebody li').each(function() {
                    if (!$('ul .r_on', this).length && !$('.chk input:checked', this).length) {
                        $('>.item > .line > .redmarker', this).removeClass('r_on');
                    }
                });
            }
        });
        
        //Инициализируем все checkbox'ы которые уже активны
        $('.chk input[type="checkbox"]:checked', $treeview).trigger('change');
                
    }); //each

  };

    $.contentReady(function() {
        $('.activetree').treeview();
    });

})( jQuery );