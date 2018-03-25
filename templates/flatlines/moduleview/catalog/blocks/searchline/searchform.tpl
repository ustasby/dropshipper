{* Поиск по товарам на сайте *}

{if !$param.hideAutoComplete}
    {addjs file="libs/jquery.autocomplete.js"}
    {addjs file="rs.searchline.js"}
{/if}
<form method="GET" class="query on" action="{$router->getUrl('catalog-front-listproducts', [])}" {if !$param.hideAutoComplete}id="queryBox"{/if}>
    <input type="text" class="theme-form_search{if !$param.hideAutoComplete} rs-autocomplete{/if}" name="query" value="{$query}" autocomplete="off" data-source-url="{$router->getUrl('catalog-block-searchline', ['sldo' => 'ajaxSearchItems', _block_id => $_block_id])}" placeholder="Поиск по каталогу">
    <button type="submit" class="theme-btn_search">{t}Найти{/t}</button>
</form>