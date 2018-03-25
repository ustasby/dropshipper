/* Скрипт активирует административные функции работы со Структурами данных */
$(function() {
    $('body')
        .on('click', '.datablocks-fields-add-button', function() {
            var context = $(this).closest('.datablocks-fields');
            var table = context.find('.datablocks-fields-table');
            var type = context.find('.datablocks-fields-add-type').val();

            $.ajaxQuery({
                url: context.data('getLineUrl'),
                data: {
                    type: type
                },
                success: function(response) {
                    table.append(response.html).trigger('new-content');
                }
            });
        })
        .on('click', '.datablocks-fields-remove', function() {
            if (confirm(lang.t('Вы действительно желаете удалить параметр?'))) {
                var context = $(this).closest('.datablocks-fields');
                var line = $(this).closest('tr').remove();
            }
        });

    $.contentReady(function() {
        $('table.datablocks-fields-table').tableDnD({
            dragHandle: ".drag-handle",
            onDragClass: "in-drag"
        });
    });
});