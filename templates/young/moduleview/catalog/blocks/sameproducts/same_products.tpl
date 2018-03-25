{if !empty($sameproducts)}
<div class="recommended">
    <h3>{t}Похожие товары{/t}</h3>
    <ul>
        {foreach $sameproducts as $product}
        {$main_image=$product->getMainImage()}
        <li><a href="{$product->getUrl()}" title="{$product.title}"><img src="{$main_image->getUrl(100, 100)}" alt="{$main_image.title|default:"{$product.title}"}"/></a></li>
        {/foreach}
    </ul>
</div>
{/if}