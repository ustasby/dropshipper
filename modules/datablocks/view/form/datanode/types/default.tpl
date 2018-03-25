<tr>
    <td class="drag-handle">
        <a class="sort">
            <i class="zmdi zmdi-unfold-more"></i>
        </a>
    </td>
    <td>
        {block name="title"}
            <p>
                <label class="m-b-5 f-12">{t}Наименование{/t}</label><br>
                <input type="text" name="child_structure[{$uniq_key}][title]" value="{$field.title}"><br>
            </p>
        {/block}

        <p>
            <label class="m-b-5 f-12">{t}Идентификатор{/t}</label><br>
            <input type="text" name="child_structure[{$uniq_key}][name]" value="{$field.name}" {if $block_name}readonly{/if}><br>
        </p>

        {block name="tab_title"}
            <p>
                <label class="m-b-5 f-12">{t}Вкладка{/t}</label><br>
                <input type="text" name="child_structure[{$uniq_key}][tab_title]" value="{$field.tab_title}">
            </p>
        {/block}
    </td>
    <td>
        {block name="type"}
            <input type="hidden" name="child_structure[{$uniq_key}][type]" value="{$field.type}">
            {$field->getTypeTitle()}
        {/block}
    </td>
    <td>
        {block name="attributes"}
            {include file="%system%/admin/keyvaleditor.tpl" field_name="child_structure[{$uniq_key}][attributes]" arr=$field.attributes}
        {/block}
    </td>
    <td align="center">
        {block name="values"}
        -
        {/block}
    </td>
    <td>
        {block name="remove"}
            <a class=""><i class="zmdi zmdi-delete c-red f-18 datablocks-fields-remove"></i></a>
        {/block}
    </td>
</tr>