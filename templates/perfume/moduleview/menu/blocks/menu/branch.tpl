{foreach from=$menu_level item=item}
<li class="{if !empty($item.child)}node{/if}{if $item.fields.typelink=='separator'} separator{/if}{if $item.fields->isAct()} act{/if}" {if $item.fields.typelink != 'separator'}{$item.fields->getDebugAttributes()}{/if}>
    {if $item.fields.typelink!='separator'}
        <a href="{$item.fields->getHref()}" {if $item.fields.target_blank}target="_blank"{/if}>{$item.fields.title}</a>
    {else}
        &nbsp;
    {/if}
    {if !empty($item.child)}
    <ul>
        {include file="blocks/menu/branch.tpl" menu_level=$item.child}
    </ul>
    {/if}
</li>
{/foreach}