{assign var=bc value=$app->breadcrumbs->getBreadCrumbs()}
{if !empty($bc)}
<ul class="breadcrumbs" xmlns:v="http://rdf.data-vocabulary.org/#">
    {foreach $bc as $item}
        {if empty($item.href)}
            <li {if $item@first}class="first"{/if} typeof="v:Breadcrumb">
                <span property="v:title">{$item.title}</span>
            </li>
        {else}
            <li {if $item@first}class="first"{/if} typeof="v:Breadcrumb">
                <a href="{$item.href}" {if $item@first}class="first"{/if} rel="v:url" property="v:title">{$item.title}</a>
            </li>
        {/if}
    {/foreach}
</ul>
{/if}