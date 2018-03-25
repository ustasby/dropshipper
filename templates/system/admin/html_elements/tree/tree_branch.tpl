{foreach $list as $key => $item}
    <li  class="{if isset($tree->options.disabledField) && $item.fields[$tree->options.disabledField] === $tree->options.disabledValue}disabled{/if}
               {if isset($tree->options.activeField) && $tree->options.activeValue == $item.fields[$tree->options.activeField]}current{/if}
               {if $item.fields[$tree->options.classField]} {$item.fields[$tree->options.classField]}{/if}
               {if ($item.fields.closed)}tree-collapsed{else}tree-expanded{/if}
               {if !empty($item.child)}tree-branch{/if}
               {if ($item.fields.is_root_element)}root noDraggable{/if}"
               {if !empty($item.fields.noDraggable)}data-notmove="notmove"{/if}
                data-id="{$item.fields[$tree->options.sortIdField]}">
        <div class="item">
            <div class="chk" unselectable="on">
                {if !$item.fields.noCheckbox && !$tree->options.noCheckbox}
                <input type="checkbox" name="{$tree->getCheckboxName()}" value="{$item.fields[$tree->options.activeField]}" {if $tree->isChecked($item.fields[$tree->options.activeField])}checked{/if} {if $item.fields.disabledCheckbox}disabled{/if}>
                {/if}
            </div>
            <div class="line">
                <div class="toggle">
                    <i class="zmdi"></i>
                </div>
                {if $tree->options.sortable}<div class="move{if !empty($item.fields.noDraggable)} no-move{/if}"><i class="zmdi zmdi-unfold-more"></i></div>{/if}
                {if !$item.fields.noRedMarker}
                <div class="redmarker"></div>
                {/if}
                <div class="data">
                    <div class="textvalue">
                    {$cell=$tree->getMainColumn($item.fields)}
                    {if isset($cell->property.href)}<a href="{$cell->getHref()}" class="call-update">{/if}
                        {include file=$cell->getBodyTemplate() cell=$cell}
                    {if isset($cell->property.href)}</a>{/if}
                    </div>
                    {if empty($item.fields.noOtherColumns)}
                        {if isset($item.fields.treeTools)}
                            {$item.fields.treeTools->setRow($item.fields)|devnull}
                            {include file=$item.fields.treeTools->getBodyTemplate() cell=$item.fields.treeTools}
                        {else}
                            {if $tree->getTools()}
                                {include file=$tree->getTools()->getBodyTemplate() cell=$tree->getTools($item.fields)}
                            {/if}
                        {/if}
                    {/if}
                </div>
            </div>
        </div>
        {if !empty($item.child)}
        <ul class="childroot">
            {include file="%system%/admin/html_elements/tree/tree_branch.tpl" list=$item.child level=$level+1}
        </ul>
        {/if}
    </li>
{/foreach}