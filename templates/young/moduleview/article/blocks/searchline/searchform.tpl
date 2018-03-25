<form method="GET" action="{$router->getUrl('article-front-search')}" class="searchForm" id="queryBox">
    <div class="oh">
    <input type="text" name="query" class="query{if !$param.hideAutoComplete} autocomplete{/if}" placeholder="поиск статьи" autocomplete="off" value="{$query}" data-source-url="{$router->getUrl('article-block-searchline', ['sldo' => 'ajaxSearchItems', _block_id => $_block_id])|escape}">
    <input type="submit" value="" class="submit" title="Найти">
    </div>
</form>