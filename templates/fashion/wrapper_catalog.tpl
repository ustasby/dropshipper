{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
    <div class="box">
        {* Хлебные крошки *}
        {moduleinsert name="\Main\Controller\Block\BreadCrumbs"}
        <div class="rightColumn productList">
            {$app->blocks->getMainContent()}
        </div>
        <div class="leftColumn">
            {* Фильтр *}
            {moduleinsert name="\Catalog\Controller\Block\SideFilters"}
            
            {* Просмотренные товары *}
            {moduleinsert name="\Catalog\Controller\Block\LastViewed" pageSize="8"}
            
            {* Новости *}
            {moduleinsert name="\Article\Controller\Block\LastNews" indexTemplate="blocks/lastnews/lastnews.tpl" category="2" pageSize="5"}
        </div>
        <div class="clearBoth"></div>
    </div>
{/block}