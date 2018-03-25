{$bc=$app->breadcrumbs->getBreadCrumbs()}
{if !empty($bc)}
<ul class="breadcrumbs" xmlns:v="http://rdf.data-vocabulary.org/#">
    {foreach $bc as $item}
        <li {if $item@first}class="first"{/if} typeof="v:Breadcrumb">
            {if empty($item.href)}
                <span property="v:title">{$item.title}</span>
            {else}
                <a href="{$item.href}" rel="v:url" property="v:title">{$item.title}</a>        
            {/if}
        </li>
    {/foreach}
</ul>
{/if}