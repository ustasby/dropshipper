{assign var=bc value=$app->breadcrumbs->getBreadCrumbs()}
{if !empty($bc)}
<div class="oh">
    <div class="centered">
        <ul class="breadcrumbs">
            {foreach $bc as $item}
                {if empty($item.href)}
                    <li {if $item@first}class="first"{/if}>{$item.title}</li>
                {else}
                    <li {if $item@first}class="first"{/if}>
                        <a href="{$item.href}" {if $item@first}class="first"{/if}>{$item.title}</a>
                    </li>
                {/if}
            {/foreach}
        </ul>
    </div>
</div>
{/if}