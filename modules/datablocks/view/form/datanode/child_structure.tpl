{addjs file="%datablocks%/child_structure.js"}
{addjs file="jquery.tablednd/jquery.tablednd.js" basepath="common"}
<div class="datablocks-fields" data-get-line-url="{adminUrl do=addField}">
    <table class="rs-table datablocks-fields-table no-default-sort">
        <thead>
            <tr>
                <td></td>
                <td></td>
                <td>{t}Тип{/t}</td>
                <td>{t}Атрибуты{/t}</td>
                <td>{t}Значения{/t}</td>
                <td></td>
            </tr>
        </thead>
        <tbody class="datablocks-fields-container">
        {foreach $elem->getChildStructureObjects() as $item}
            {$item->getFieldView(true)}
        {/foreach}
        </tbody>
    </table>

    <div class="p-10 bg-warning datablocks-fields-add">
        <label class="f-10">{t}Добавить параметр следующего типа{/t}:</label><br>
        <select class="datablocks-fields-add-type">
            {foreach $field->field_types as $key => $item}
            <option value="{$key}">{$item}</option>
            {/foreach}
        </select>
        <input type="button" value="{t}Добавить{/t}" class="btn btn-success datablocks-fields-add-button">
    </div>
</div>