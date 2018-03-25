<div class="block blockCompare compareBlock{if !count($list)} hidden{else} active{/if}">
    <p class="caption">{t}Товары для  сравнения{/t}</p>
    <ul class="compareProducts">
        {$list_html}        
    </ul>
    <a href="{$router->getUrl('catalog-front-compare')}" class="colorButton doCompareButton" target="_blank">{t}Сравнить{/t}</a>
</div>