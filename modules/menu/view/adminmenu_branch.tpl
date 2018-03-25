{* Один уровень меню административной панели *}
{foreach $list as $item}
    <li {if $item.child} class="sm-node rs-meter-group"{/if}{if $item.fields.typelink=='separator'} class="separator"{/if}>
        {if $item.fields.typelink != 'separator'}
        <a class="{if isset($sel_id) && $sel_id==$item.fields.id}active{/if}" href="{$item.fields.link}">
            {nocache}
                {if $item.child}{meter}
                {else}{meter key="rs-admin-menu-{$item.fields.alias}"}{/if}
            {/nocache}
            <span class="sm-node-title">{$item.fields.title}</span>
        </a>
        {/if}
        {if $item.child}
            <ul>
            {include file="adminmenu_branch.tpl" list=$item.child is_first_level=false}
            </ul>
        {/if}
    </li>
{/foreach}