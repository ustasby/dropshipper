{if count($products)}
<div class="block blockLastViewed">
    <p class="caption">{t}Вы смотрели{/t}</p>
    <ul>
        {foreach $products as $product}
        {$main_image=$product->getMainImage()}
        <li><a href="{$product->getUrl()}" title="{$product.title}"><img src="{$main_image->getUrl(100,100, 'xy')}" alt="{$main_image.title|default:"{$product.title}"}"/></a></li>
        {/foreach}
    </ul>
</div>
{/if}