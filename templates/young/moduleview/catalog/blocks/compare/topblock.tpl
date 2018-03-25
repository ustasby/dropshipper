{if $THEME_SETTINGS.enable_compare}
    {addjs file="jquery.compare.js"}
    <a class="doCompare compareTopBlock" id="compareBlock" data-compare-url='{ "add":"{$router->getUrl('catalog-block-compare', ["cpmdo" => "ajaxAdd", "_block_id" => $_block_id])|escape}", "remove":"{$router->getUrl('catalog-block-compare', ["cpmdo" => "ajaxRemove", "_block_id" => $_block_id])|escape}", "removeAll":"{$router->getUrl('catalog-block-compare', ["cpmdo" => "ajaxRemoveAll", "_block_id" => $_block_id])|escape}", "compare":"{$router->getUrl('catalog-front-compare')}" }'>
        <span class="title">{t}Сравнение{/t}</span>
        <span class="compareItemsCount">{$this_controller->api->getCount()}</span>
    </a>
{/if}