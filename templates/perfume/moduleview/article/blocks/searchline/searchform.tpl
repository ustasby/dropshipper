<form method="GET" action="{$router->getUrl('article-front-search')}" class="searchForm" id="queryBox">
    <div class="oh">
        <input type="text" name="query" class="query{if !$param.hideAutoComplete} autocomplete{/if}" placeholder="{t}поиск статьи{/t}" autocomplete="off" value="{$query}" data-source-url="{$router->getUrl('article-block-searchline', ['sldo' => 'ajaxSearchItems', _block_id => $_block_id])}">
        <input type="submit" value="" class="submit" title="{t}Найти{/t}">
    </div>
</form>