{if count($products)}
<div class="block lastViewed">
    <p class="blockTitle">Вы смотрели</p>
    <ul>
        {foreach $products as $product}
        {$main_image=$product->getMainImage()}
        <li><a href="{$product->getUrl()}" title="{$product.title}"><img src="{$main_image->getUrl(78,109, 'xy')}" alt="{$main_image.title|default:"{$product.title}"}"/></a></li>
        {/foreach}
    </ul>
</div>
{/if}