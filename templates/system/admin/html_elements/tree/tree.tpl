{addjs file="nestedSortable/jquery.mjs.nestedSortable.js" basepath="common"}
{addjs file="jquery.rs.treeview.js" basepath="common"}
        
<div class="activetree tree" data-uniq="{$tree->options.uniq}">
    <ul class="treehead">
        <li>
            {if !$tree->options.noCheckbox}
            <div class="chk"><input type="checkbox" class="select-page" data-name="{$tree->getCheckboxName()}"></div>
            {/if}
            {if !$tree->options.noExpandCollapseButton}
            <a class="allplus" title="развернуть все"><i class="zmdi zmdi-plus"></i></a>
            <a class="allminus" title="свернуть все"><i class="zmdi zmdi-minus"></i></a>
            {/if}
            {foreach from=$tree->getHeadButtons() item=button}
                {if $button.tag}{$tag=$button.tag}{else}{$tag="a"}{/if}
                <{$tag} {foreach from=$button.attr|default:array() key=key item=value} {$key}="{$value}"{/foreach}>{$button.text}</{$tag}>
            {/foreach}
        </li>
    </ul>

    <ul class="treebody root{if $tree->options.sortable} treesort{/if}"
        data-sort-url="{$tree->options.sortUrl}"
        {if $tree->options.noExpandCollapseButton}data-no-expand-collapse="true"{/if}
        {if $tree->options.maxLevels}data-max-levels="{$tree->options.maxLevels}"{/if}>

        {include file="%system%/admin/html_elements/tree/tree_branch.tpl" level="0" list=$tree->getData()}
        {if !count($tree->getData())}
        <li class="empty-tree-row">{t}Нет элементов{/t}</li>
        {/if}
    </ul>
</div>