{if $paginator->total_pages>1}
    {$pagestr = t('Страница %page', ['page' => $paginator->page])}
    {if $paginator->page > 1 && !substr_count($app->title->get(), $pagestr)}
        {$app->title->addSection($pagestr, 0, 'after')|devnull}
        {$caonical = implode('', ['<link rel="canonical" href="', $SITE->getRootUrl(true), substr($paginator->getPageHref(1),1), '"/>'])}
        {$app->setAnyHeadData($caonical)|devnull}
    {/if}
    <div class="paginator">
        {if $paginator->showFirst()}
        <a href="{$paginator->getPageHref(1)}" class="first" title="{t}первая страница{/t}">&laquo;</a>
        {/if}
        {if $paginator->page>1}
        <a href="{$paginator->getPageHref($paginator->page-1)}" class="prev" title="{t}предыдущая страница{/t}">&laquo;<span class="text"> {t}назад{/t}</span></a>
        {/if}
        {foreach from=$paginator->getPageList() item=page}            
        <a href="{$page.href}" {if $page.act}class="act"{/if}>{if $page.class=='left'}&laquo;{$page.n}{elseif $page.class=='right'}{$page.n}&raquo;{else}{$page.n}{/if}</a>
        {/foreach}
        {if $paginator->page < $paginator->total_pages}
        <a href="{$paginator->getPageHref($paginator->page+1)}" class="next" title="{t}следующая страница{/t}"><span class="text">{t}вперед{/t}</span> &raquo;</a>
        {/if}
        {if $paginator->showLast()}
        <a href="{$paginator->getPageHref($paginator->total_pages)}" class="end" title="{t}последняя страница{/t}">&raquo;</a>
        {/if}
    </div>
{/if}