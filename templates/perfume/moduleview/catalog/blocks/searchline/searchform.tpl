<form method="GET" action="{$router->getUrl('catalog-front-listproducts', [])}" class="searchForm" id="queryBox">
    <div class="oh">
        <input type="text" name="query" class="query{if !$param.hideAutoComplete} autocomplete{/if}" placeholder="{t}поиск по каталогу{/t}" autocomplete="off" value="{$query}" data-source-url="{$router->getUrl('catalog-block-searchline', ['sldo' => 'ajaxSearchItems', _block_id => $_block_id])}">
        <input type="submit" value="" class="submit" title="{t}Найти{/t}">
    </div>
</form>