<form method="GET" class="query on" action="{$router->getUrl('catalog-front-listproducts', [])}" id="queryBox">
    <input type="text" class="input query{if !$param.hideAutoComplete} autocomplete{/if}" name="query" value="{$query}" autocomplete="off" data-source-url="{$router->getUrl('catalog-block-searchline', ['sldo' => 'ajaxSearchItems', _block_id => $_block_id])}" placeholder="Поиск по каталогу">
    <input type="submit" class="submit" value="" title="Найти в каталоге">
</form>