{* Хлебные крошки *}
{$bc = $app->breadcrumbs->getBreadCrumbs()}
{if !empty($bc)}
    <nav xmlns:v="http://rdf.data-vocabulary.org/#">
        <ol class="breadcrumb">
            {foreach $bc as $item}
                <li typeof="v:Breadcrumb">
                    {if !$item.href}
                        <span property="v:title">{$item.title}</span>
                    {else}
                        <a href="{$item.href}" rel="v:url" property="v:title">{$item.title}</a>
                    {/if}
                </li>
            {/foreach}
        </ol>
    </nav>
{/if}