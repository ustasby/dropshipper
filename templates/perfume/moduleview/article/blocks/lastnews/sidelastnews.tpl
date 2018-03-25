{if $category && $news}
<div class="block lastNews">
    <h4 class="bordered">{$category.name}</h4>
    {foreach $news as $item}
    <div class="newsItem" {$item->getDebugAttributes()}>
        <p class="date">{$item.dateof|dateformat:"%d %v %Y, %H:%M"}</p>
        <a href="{$item->getUrl()}" class="title">{$item.title}</a>
        <p class="descr">{$item->getPreview()}</p>
    </div>
    {/foreach}
    <a href="{$router->getUrl('article-front-previewlist', [category => $category->getUrlId()])}" class="all">{t}Все новости{/t}</a>
</div>
{/if}