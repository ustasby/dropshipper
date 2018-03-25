{if count($products)}
<div class="block lastViewed">
    <h4>{t}Вы смотрели{/t}</h4>
    <ul>
        {foreach $products as $product}
        {$main_image=$product->getMainImage()}
        <li><a href="{$product->getUrl()}" title="{$product.title}"><img src="{$main_image->getUrl(102,102, 'xy')}" alt="{$main_image.title|default:"{$product.title}"}"></a></li>
        {/foreach}
    </ul>
</div>
{/if}