{addjs file="jquery.compare.js"}
{$total=$this_controller->api->getCount()}
<div class="compare{if $total} active{/if}" id="compareBlock" data-compare-url='{ "add":"{$router->getUrl('catalog-block-compare', ["cpmdo" => "ajaxAdd", "_block_id" => $_block_id])}", "remove":"{$router->getUrl('catalog-block-compare', ["cpmdo" => "ajaxRemove", "_block_id" => $_block_id])}", "removeAll":"{$router->getUrl('catalog-block-compare', ["cpmdo" => "ajaxRemoveAll", "_block_id" => $_block_id])}", "compare":"{$router->getUrl('catalog-front-compare')}" }'>
    <a class="doCompare"><span class="text">{t}Сравнить{/t}</span><i class="icon"></i></a>
    <span class="compareItemsCount">{$total}</span>
</div>            