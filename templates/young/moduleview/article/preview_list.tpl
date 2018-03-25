<div class="newsPage">
    {if !empty($list)}
        <h1 class="newsTitle">{t}Новости{/t}</h1>
        <ul class="news">
            {foreach $list as $item}
            <li class="item {if $item@iteration % 2==0}fr{else}fl{/if}" {$item->getDebugAttributes()}>
                <p class="date">{$item.dateof|date_format:"d.m.Y H:i"}</p>
                <a href="{$item->getUrl()}" class="title">{$item.title}</a>
                <div class="descr">{$item->getPreview()}</div>
            </li>
            {/foreach}
        </ul>
        {include file="%THEME%/paginator.tpl"}
    {else}
        <p class="empty">{t}Не найдено ни одной статьи{/t}</p>
    {/if}
</div>