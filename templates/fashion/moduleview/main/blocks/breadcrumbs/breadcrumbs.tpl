{assign var=bc value=$app->breadcrumbs->getBreadCrumbs()}
{if !empty($bc)}
<div class="breadcrumbs" xmlns:v="http://rdf.data-vocabulary.org/#">
    {foreach $bc as $item}
        {if empty($item.href)}
            <i typeof="v:Breadcrumb">
                <span {if $item@first}class="first"{/if} property="v:title">{$item.title}</span>
            </i>
        {else}
            <i typeof="v:Breadcrumb">
                <a href="{$item.href}" {if $item@first}class="first"{/if} rel="v:url" property="v:title">{$item.title}</a> 
            </i>
        {/if}
    {/foreach}
</div>
{/if}