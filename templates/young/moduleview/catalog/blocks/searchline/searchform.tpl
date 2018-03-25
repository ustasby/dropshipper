<form method="GET" action="{$router->getUrl('catalog-front-listproducts', [])}" class="searchForm" id="queryBox">
    <div class="oh">
    <input type="text" name="query" class="query{if !$param.hideAutoComplete} autocomplete{/if}" placeholder="поиск по каталогу" autocomplete="off" value="{$query}" data-source-url="{$router->getUrl('catalog-block-searchline', ['sldo' => 'ajaxSearchItems', _block_id => $_block_id])|escape}">
    <input type="submit" value="" class="submit" title="Найти">
    </div>
</form>